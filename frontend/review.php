<?php
// Saves, deletes or takes down a review, then goes back to the book's page (the reviews section lives there).
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/reviews.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: shop.php");
    exit();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] ?? '') === 'admin';
$book_id = (int) ($_POST['book_id'] ?? 0);

// Goes back to the book, leaving a one-time message (and what was typed, if something was wrong)
function k_review_done(int $book_id, string $type, string $message, array $old = []): void
{
    $_SESSION['review_flash'] = ['book' => $book_id, 'type' => $type, 'message' => $message, 'old' => $old];
    header("Location: product.php?id=" . $book_id . "#reviews");
    exit();
}

$stmt = $conn->prepare("SELECT id, seller_id FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) {
    header("Location: shop.php");
    exit();
}

if (!k_csrf_valid($_POST['csrf'] ?? '')) {
    k_review_done($book_id, 'error', 'Your session expired, so nothing was saved. Please try again.');
}
k_reviews_ready($conn);

$action = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';

if ($action === 'save') {
    $rating = filter_var($_POST['rating'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    $comment = is_string($_POST['comment'] ?? null) ? trim($_POST['comment']) : '';
    $old = ['rating' => $rating === false ? 0 : $rating, 'comment' => $comment];

    if ((int) $book['seller_id'] === $user_id) {
        k_review_done($book_id, 'error', 'You listed this book, so you cannot review it.');
    }
    if ($rating === false) {
        k_review_done($book_id, 'error', 'Please choose a rating from 1 to 5 stars.', $old);
    }
    if (mb_strlen($comment) > REVIEW_MAX_COMMENT) {
        k_review_done($book_id, 'error', 'Please keep your review under ' . REVIEW_MAX_COMMENT . ' characters.', $old);
    }

    $existing = k_review_of($conn, $book_id, $user_id);
    if ($existing && $existing['status'] === 'rejected') {
        k_review_done($book_id, 'error', 'Your review was removed by our team and cannot be changed.');
    }

    if (!$existing) {
        try {
            $stmt = $conn->prepare("INSERT INTO reviews (book_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'approved')");
            $stmt->bind_param("iiis", $book_id, $user_id, $rating, $comment);
            $stmt->execute();
            k_review_done($book_id, 'success', 'Thank you, your review is posted.');
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() !== 1062) { // anything but "this person already reviewed it a moment ago"
                k_review_done($book_id, 'error', 'Something went wrong saving your review. Please try again.', $old);
            }
            $existing = k_review_of($conn, $book_id, $user_id);
        }
    }
    if (!$existing) {
        k_review_done($book_id, 'error', 'Something went wrong saving your review. Please try again.', $old);
    }

    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND user_id = ? AND status <> 'rejected'");
    $stmt->bind_param("isii", $rating, $comment, $existing['id'], $user_id);
    $stmt->execute();
    k_review_done($book_id, 'success', 'Your review was updated.');
}

if ($action === 'delete') {
    // A taken-down review stays down: deleting it would let the person post again around the moderators
    $stmt = $conn->prepare("DELETE FROM reviews WHERE book_id = ? AND user_id = ? AND status <> 'rejected'");
    $stmt->bind_param("ii", $book_id, $user_id);
    $stmt->execute();
    k_review_done($book_id, 'success', 'Your review was deleted.');
}

if ($action === 'remove' && $is_admin) {
    $review_id = (int) ($_POST['review_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ? AND book_id = ?");
    $stmt->bind_param("ii", $review_id, $book_id);
    $stmt->execute();
    k_review_done($book_id, 'success', 'The review was removed.');
}

k_review_done($book_id, 'error', 'That did not work. Please try again.');
