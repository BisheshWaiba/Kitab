<?php
// Book reviews: shown on the product page (frontend/product.php), saved by frontend/review.php. Needs config/db.php.
//
// A review is a 1-5 rating with an optional comment, one per person per book. New reviews are published at once
// (status 'approved'); an admin can take one down ('rejected'), and the person can edit or delete their own.

const REVIEW_MAX_COMMENT = 1000;
const REVIEW_PAGE_SIZE = 20;   // reviews shown before "Show all"

// "Bishesh Waiba" -> "Bishesh W.": reviews are public, so only the first name and last initial are shown
function k_review_name(string $full): string
{
    $words = preg_split('/\s+/u', trim($full), -1, PREG_SPLIT_NO_EMPTY);
    if (!$words) {
        return 'A reader';
    }
    return count($words) > 1 ? $words[0] . ' ' . mb_strtoupper(mb_substr(end($words), 0, 1)) . '.' : $words[0];
}

// Five stars filled up to $rating (fractions too), with the value spoken for screen readers
function k_stars(float $rating): string
{
    $pct = round(max(0, min(5, $rating)) / 5 * 100, 1);
    $spoken = rtrim(rtrim(number_format($rating, 1), '0'), '.');
    return '<span class="k-stars" style="--pct:' . $pct . '%" role="img" aria-label="' . $spoken . ' out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>';
}

// The same five stars as k_stars(), drawn as an SVG mask (glass look: assets/css/glass.css)
function k_glass_stars(float $rating): string
{
    $pct = round(max(0, min(5, $rating)) / 5 * 100, 1);
    $spoken = rtrim(rtrim(number_format($rating, 1), '0'), '.');
    return '<span class="hg-stars" style="--p:' . $pct . '%" role="img" aria-label="' . $spoken . ' out of 5 stars"></span>';
}

// One person, one review per book. Needs a unique index so two quick posts cannot both go in; it is added on first
// use (and silently skipped if the table already holds duplicates, in which case the code below still copes).
function k_reviews_ready(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $has = $conn->query("SHOW INDEX FROM reviews WHERE Key_name = 'user_book'");
    if ($has && $has->num_rows === 0) {
        try {
            $conn->query("ALTER TABLE reviews ADD UNIQUE KEY user_book (user_id, book_id)");
        } catch (Throwable $e) {
            error_log('KITAB: could not add the unique review index: ' . $e->getMessage());
        }
    }
}

// Number of published reviews, their average and how many gave each rating: [count, average, [5 => n, ..., 1 => n]]
function k_review_summary(mysqli $conn, int $book_id): array
{
    $stmt = $conn->prepare("SELECT rating, COUNT(*) FROM reviews WHERE book_id = ? AND status = 'approved' GROUP BY rating");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $by_rating = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $count = 0;
    $sum = 0;
    foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$rating, $n]) {
        $by_rating[(int) $rating] = (int) $n;
        $count += (int) $n;
        $sum += (int) $rating * (int) $n;
    }
    return [$count, $count > 0 ? $sum / $count : 0.0, $by_rating];
}

// The published reviews, newest first
// (each review also says whether its writer has bought the book: an order of theirs that was not cancelled contains it)
function k_review_list(mysqli $conn, int $book_id, int $limit): array
{
    $stmt = $conn->prepare(
        "SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, u.name,
                EXISTS (SELECT 1 FROM orders o JOIN order_items oi ON oi.order_id = o.id
                        WHERE o.user_id = r.user_id AND oi.book_id = r.book_id AND o.status <> 'cancelled') AS verified
         FROM reviews r LEFT JOIN users u ON u.id = r.user_id
         WHERE r.book_id = ? AND r.status = 'approved'
         ORDER BY r.created_at DESC, r.id DESC LIMIT ?"
    );
    $stmt->bind_param("ii", $book_id, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// This person's own review of the book (in any state), or null
function k_review_of(mysqli $conn, int $book_id, int $user_id): ?array
{
    $stmt = $conn->prepare("SELECT id, rating, comment, status FROM reviews WHERE book_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $book_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
