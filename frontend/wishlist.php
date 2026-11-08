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

$page_title = 'My Wishlist';
require_once '../includes/header.php';
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">My Wishlist</h1>
        <p data-wish-total><?php echo $total === 0 ? 'Books you save will wait for you here' : $total . ($total === 1 ? ' book' : ' books') . ' saved for later'; ?></p>
    </div>
</section>

<div class="k-shop">
    <main class="k-shop__main">
        <?php if ($total > 0): ?>
            <div class="k-grid" data-wish-list>
                <?php foreach ($books as $row): ?>
                    <?php k_book_card($row); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="k-empty">
                <p>Your wishlist is empty. Tap the heart on any book to save it for later.</p>
                <a href="shop.php" class="k-btn k-btn--primary">Browse Books</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
