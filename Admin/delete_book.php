<?php
// Deletes one book, but never one that appears in an order: order_items is set to cascade, so deleting such a
// book would silently erase part of that order's history. Answers with a message on the book list.
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

function k_back_to_books(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: " . BASE_URL . "admin/books.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "admin/books.php");
    exit();
}
if (!k_csrf_valid($_POST['csrf'] ?? '')) {
    k_back_to_books('error', 'Your session expired, so nothing was deleted. Please try again.');
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT title, image FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) {
    k_back_to_books('error', 'That book no longer exists.');
}

$stmt = $conn->prepare("SELECT COUNT(DISTINCT order_id) FROM order_items WHERE book_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$orders = (int) $stmt->get_result()->fetch_row()[0];
$refused = '"' . $book['title'] . '" is in ' . $orders . ($orders === 1 ? ' order' : ' orders')
    . ', so it can\'t be deleted. Orders keep a record of the books they contain.';
if ($orders > 0) {
    k_back_to_books('error', $refused);
}

// The NOT EXISTS keeps the same rule even if an order slips in between the check above and this delete.
// Reviews, cart and wishlist rows of the book go with it (their foreign keys cascade).
$stmt = $conn->prepare("DELETE FROM books WHERE id = ? AND NOT EXISTS (SELECT 1 FROM order_items WHERE book_id = ?)");
$stmt->bind_param("ii", $id, $id);
if (!$stmt->execute()) {
    k_back_to_books('error', 'Something went wrong deleting "' . $book['title'] . '". Please try again.');
}
if ($stmt->affected_rows < 1) {
    k_back_to_books('error', $refused);
}

k_delete_upload($book['image']);
k_back_to_books('success', '"' . $book['title'] . '" was deleted.');
