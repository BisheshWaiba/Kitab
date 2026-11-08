<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Handled before the header is output: saving/removing a book answers with a redirect (or JSON), and the page needs a login.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wants_json = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    $reply = function (int $status, array $data) use ($wants_json): void {
        if ($wants_json) {
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            // Without JavaScript: back to the page the heart was on (a path on this site only)
            $back = (string) ($_POST['back'] ?? '');
            $safe = preg_match('#^/(?!/)[^\r\n\\\\]*\z#', $back) === 1;
            header("Location: " . ($status === 401 ? BASE_URL . 'frontend/login.php' : ($safe ? $back : BASE_URL . 'frontend/wishlist.php')));
        }
        exit();
    };

    if (!isset($_SESSION['user_id'])) {
        $reply(401, ['login' => true]);
    }
    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $reply(400, ['error' => 'expired']);
    }

    $user_id = (int) $_SESSION['user_id'];
    $book_id = (int) ($_POST['book_id'] ?? 0);

    if (($_POST['do'] ?? '') === 'remove') {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
    } else {
        // Saves the book only if it exists, and does nothing if it is already saved
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, book_id) SELECT ?, id FROM books WHERE id = ? ON DUPLICATE KEY UPDATE user_id = user_id");
    }
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(book_id = ?), 0) FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("ii", $book_id, $user_id);
    $stmt->execute();
    [$count, $saved] = $stmt->get_result()->fetch_row();

    $reply(200, ['ok' => true, 'wished' => (int) $saved > 0, 'count' => (int) $count]);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare(
    "SELECT b.* FROM wishlist w JOIN books b ON b.id = w.book_id
     WHERE w.user_id = ? ORDER BY w.created_at DESC, w.id DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total = count($books);

// The Wishlist page has the glass look (from the design canvas): a body class and a stylesheet only the redesigned pages load
$body_class = 'is-glass is-wishlist';
$extra_css = 'assets/css/glass.css';
$page_title = 'My Wishlist';
require_once '../includes/header.php';

// Light from the books: the two most recently saved covers glow softly behind the top of the page
$ambient = [];
foreach ($books as $b) {
    $img = trim((string) $b['image']);
    if ($img !== '') {
        $ambient[] = strpos($img, 'http') === 0 ? $img : BASE_URL . $img;
        if (count($ambient) === 2) {
            break;
        }
    }
}
?>

<div class="hg-ambient" aria-hidden="true">
    <?php foreach ($ambient as $src): ?>
        <img src="<?php echo htmlspecialchars($src); ?>" alt="">
    <?php endforeach; ?>
</div>

<section class="gs-head hg-wrap">
    <h1 class="hg-h1 gs-title">My Wishlist</h1>
    <p class="hg-lede" data-wish-total><?php echo $total === 0 ? 'Books you save will wait for you here' : $total . ($total === 1 ? ' book' : ' books') . ' saved for later'; ?></p>
</section>

<div class="hg-wrap gs-main">
    <?php if ($total > 0): ?>
        <div class="hg-grid" data-wish-list>
            <?php foreach ($books as $row): ?>
                <?php k_glass_tile($row); ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="k-empty">
            <svg class="k-empty__icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z" />
            </svg>
            <p>Your wishlist is empty. Tap the heart on any book to save it for later.</p>
            <a href="shop.php" class="k-btn k-btn--primary">Browse Books</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
