<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page with glass buttons (is-gbtn); the Terracotta & Sand colours are added by includes/header.php
$body_class = 'is-gbtn';
$hide_footer = true; // account pages have no footer (it stays on Home, Shop, the Marketplace, the Book page and Wishlist)
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/profile-flat.css', 'assets/css/market-flat.css'];
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

const LISTING_MAX_PRICE = 1000000; // Rs.; the same limit as Sell a Book

$user_id = (int) $_SESSION['user_id'];
k_book_columns($conn); // condition and the other details (created the first time, if they are missing)
$conditions = k_book_conditions();

// ---------- Changing a listing (price, copies, condition), marking it sold, or removing it ----------
// Every change is a POST with the CSRF token, and only ever touches a book whose seller_id is the signed-in reader.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    $do = $field('do');
    $book_id = (int) $field('book_id');
    $flash = ['type' => 'error', 'text' => 'Something went wrong, so nothing was changed. Please try again.', 'book' => $book_id];

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $flash['text'] = 'Your session expired, so nothing was changed. Please try again.';
    } else {
        $stmt = $conn->prepare('SELECT id, title, image FROM books WHERE id = ? AND seller_id = ?');
        $stmt->bind_param('ii', $book_id, $user_id);
        $stmt->execute();
        $own = $stmt->get_result()->fetch_assoc();

        if (!$own) {
            $flash['text'] = 'That listing was not found.';
        } elseif ($do === 'save') {
            $price_text = $field('price');
            $stock_text = $field('stock');
            $condition = $field('condition');
            if ($price_text === '' || !is_numeric($price_text) || (float) $price_text <= 0 || (float) $price_text > LISTING_MAX_PRICE) {
                $flash['text'] = 'Enter a price above zero, up to Rs. ' . number_format(LISTING_MAX_PRICE) . '.';
            } elseif (!preg_match('/^\d{1,4}$/', $stock_text)) {
                $flash['text'] = 'Enter how many copies you have, from 0 up to 9999.';
            } else {
                $price = round((float) $price_text, 2);
                $stock = (int) $stock_text;
                if (isset($conditions[$condition])) {
                    $stmt = $conn->prepare('UPDATE books SET price = ?, stock = ?, book_condition = ? WHERE id = ? AND seller_id = ?');
                    $stmt->bind_param('disii', $price, $stock, $condition, $book_id, $user_id);
                } else {
                    $stmt = $conn->prepare('UPDATE books SET price = ?, stock = ? WHERE id = ? AND seller_id = ?');
                    $stmt->bind_param('diii', $price, $stock, $book_id, $user_id);
                }
                if ($stmt->execute()) {
                    $flash = ['type' => 'success', 'book' => $book_id,
                        'text' => $stock === 0 ? '“' . $own['title'] . '” is saved as sold out and no longer shows in the Marketplace.' : '“' . $own['title'] . '” was updated.'];
                }
            }
        } elseif ($do === 'sold') {
            $stmt = $conn->prepare('UPDATE books SET stock = 0 WHERE id = ? AND seller_id = ?');
            $stmt->bind_param('ii', $book_id, $user_id);
            if ($stmt->execute()) {
                $flash = ['type' => 'success', 'book' => $book_id, 'text' => '“' . $own['title'] . '” is marked as sold and no longer shows in the Marketplace.'];
            }
        } elseif ($do === 'remove') {
            // A book that has been ordered stays: deleting it would also delete those order lines from the buyers' order history.
            $stmt = $conn->prepare('SELECT COUNT(*) FROM order_items WHERE book_id = ?');
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            if ((int) $stmt->get_result()->fetch_row()[0] > 0) {
                $flash['text'] = '“' . $own['title'] . '” has been ordered, so it cannot be removed. Mark it as sold instead.';
            } else {
                $stmt = $conn->prepare('DELETE FROM books WHERE id = ? AND seller_id = ?');
                $stmt->bind_param('ii', $book_id, $user_id);
                if ($stmt->execute()) {
                    k_delete_upload((string) $own['image'], 'books');
                    $flash = ['type' => 'success', 'book' => 0, 'text' => '“' . $own['title'] . '” was removed.'];
                }
            }
        }
    }
    $_SESSION['listing_flash'] = $flash;
    header('Location: my_listings.php' . ($flash['book'] ? '#book-' . (int) $flash['book'] : ''));
    exit();
}

$flash = $_SESSION['listing_flash'] ?? null;
unset($_SESSION['listing_flash']);

// ---------- The reader's listings, with how many copies have been ordered (cancelled orders do not count) ----------
$stmt = $conn->prepare(
    "SELECT b.*, COALESCE(oc.ordered, 0) AS ordered, COALESCE(oc.line_count, 0) AS order_lines
     FROM books b
     LEFT JOIN (
         SELECT oi.book_id, SUM(CASE WHEN o.status <> 'cancelled' THEN oi.quantity ELSE 0 END) AS ordered, COUNT(*) AS line_count
         FROM order_items oi JOIN orders o ON o.id = oi.order_id GROUP BY oi.book_id
     ) oc ON oc.book_id = b.id
     WHERE b.seller_id = ?
     ORDER BY b.created_at DESC, b.id DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$available = 0;
foreach ($listings as $row) {
    if ((int) $row['stock'] > 0) {
        $available++;
    }
}
$page_title = 'My listings';
require_once '../includes/header.php';
?>

<main class="fl">
    <div class="fl-wrap ml">
        <div class="ml-head">
            <div>
                <h1 class="fl-h1">My listings</h1>
                <p class="fl-sub"><?php echo $listings
                    ? $available . ' of ' . count($listings) . ($available === 1 && count($listings) === 1 ? ' book is' : ' books are') . ' in the Marketplace now.'
                    : 'Books you list for sale show up here.'; ?></p>
            </div>
            <div class="ml-head__actions">
                <a href="sell_book.php" class="k-btn k-btn--primary">Sell a book</a>
                <a href="marketplace.php" class="k-btn k-btn--outline">View Marketplace</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="k-alert k-alert--<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>" role="<?php echo $flash['type'] === 'success' ? 'status' : 'alert'; ?>">
                <?php echo htmlspecialchars($flash['text']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$listings): ?>
            <?php k_empty_state('cart', 'Nothing listed yet', 'List a book you have finished with and another reader can buy it. It takes a minute.', 'Sell a Book', 'sell_book.php'); ?>
        <?php else: ?>
            <ul class="ml-list">
                <?php foreach ($listings as $b): ?>
                    <?php
                    $id = (int) $b['id'];
                    $stock = (int) $b['stock'];
                    $cond_label = k_book_condition_label($b['book_condition'] ?? null);
                    $ordered = (int) $b['ordered'];
                    $has_orders = (int) $b['order_lines'] > 0;
                    $price_num = rtrim(rtrim(number_format((float) $b['price'], 2, '.', ''), '0'), '.');
                    ?>
                    <li class="ml-item fl-panel" id="book-<?php echo $id; ?>">
                        <a href="product.php?id=<?php echo $id; ?>" class="ml-item__cover" tabindex="-1" aria-hidden="true"><?php k_cover($b, 'ml-cover', false); ?></a>

                        <div class="ml-item__main">
                            <h2 class="ml-item__title"><a href="product.php?id=<?php echo $id; ?>"><?php echo htmlspecialchars($b['title']); ?></a></h2>
                            <p class="ml-item__meta">
                                <?php echo htmlspecialchars(implode(' · ', array_filter([$b['author'], $b['category']]))); ?>
                            </p>
                            <p class="ml-item__status">
                                <?php if ($stock > 0): ?>
                                    <span class="ml-badge ml-badge--live">In the Marketplace · <?php echo $stock; ?> <?php echo $stock === 1 ? 'copy' : 'copies'; ?></span>
                                <?php else: ?>
                                    <span class="ml-badge ml-badge--sold">Sold out</span>
                                <?php endif; ?>
                                <?php if ($ordered > 0): ?>
                                    <span class="ml-ordered"><?php echo $ordered; ?> ordered</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <form method="POST" action="my_listings.php" class="ml-item__edit" data-validate>
                            <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                            <input type="hidden" name="book_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="do" value="save">
                            <div class="k-field ml-field">
                                <label for="price-<?php echo $id; ?>">Price (Rs.)</label>
                                <input type="number" id="price-<?php echo $id; ?>" name="price" class="k-input" required min="0.01" max="<?php echo LISTING_MAX_PRICE; ?>" step="0.01"
                                    inputmode="decimal" value="<?php echo htmlspecialchars($price_num); ?>"
                                    data-msg-missing="Enter a price." data-msg-invalid="Enter a price above zero.">
                            </div>
                            <div class="k-field ml-field">
                                <label for="stock-<?php echo $id; ?>">Copies</label>
                                <input type="number" id="stock-<?php echo $id; ?>" name="stock" class="k-input" required min="0" max="9999" step="1" inputmode="numeric"
                                    value="<?php echo $stock; ?>" data-msg-missing="Enter how many copies you have." data-msg-invalid="Enter 0 or more copies.">
                            </div>
                            <div class="k-field ml-field ml-field--cond">
                                <label for="cond-<?php echo $id; ?>">Condition</label>
                                <select id="cond-<?php echo $id; ?>" name="condition" class="k-input">
                                    <?php if ($cond_label === ''): ?><option value="">Not set</option><?php endif; ?>
                                    <?php foreach ($conditions as $key => [$name]): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>"<?php echo ($b['book_condition'] ?? '') === $key ? ' selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="k-btn k-btn--primary">Save</button>
                        </form>

                        <div class="ml-item__more">
                            <?php if ($stock > 0): ?>
                                <form method="POST" action="my_listings.php" onsubmit="return confirm('Mark this book as sold? It will no longer show in the Marketplace.')">
                                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                    <input type="hidden" name="book_id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="do" value="sold">
                                    <button type="submit" class="k-btn k-btn--outline k-btn--sm">Mark as sold</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$has_orders): ?>
                                <form method="POST" action="my_listings.php" onsubmit="return confirm('Remove this listing? This can\'t be undone.')">
                                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                    <input type="hidden" name="book_id" value="<?php echo $id; ?>">
                                    <input type="hidden" name="do" value="remove">
                                    <button type="submit" class="k-btn k-btn--ghost k-btn--sm">Remove</button>
                                </form>
                            <?php else: ?>
                                <span class="ml-note">Has orders, so it stays on record.</span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
