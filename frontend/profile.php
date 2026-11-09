<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// The Profile page is flat (white and light-grey surfaces, like the Shop); only its buttons are glass.
// is-gbtn switches the glass buttons on, and these stylesheets are loaded by the redesigned pages only
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/profile-flat.css'];
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Orders, newest first
$orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC, id DESC")->fetch_all(MYSQLI_ASSOC);

// The books in those orders (for the little stack of covers on each order)
$order_books = [];
if ($orders) {
    $order_ids = implode(',', array_map(fn($o) => (int) $o['id'], $orders));
    $rows = $conn->query(
        "SELECT oi.order_id, oi.quantity, b.id, b.title, b.image
         FROM order_items oi JOIN books b ON b.id = oi.book_id
         WHERE oi.order_id IN ($order_ids) ORDER BY oi.id ASC"
    );
    foreach ($rows ? $rows->fetch_all(MYSQLI_ASSOC) : [] as $r) {
        $order_books[(int) $r['order_id']][] = $r;
    }
}

// The books this customer has listed for sale
// Check if column exists first to avoid error if script not run yet
$check_col = $conn->query("SHOW COLUMNS FROM books LIKE 'seller_id'");
$my_books = [];
if ($check_col->num_rows > 0) {
    $result_my_books = $conn->query("SELECT * FROM books WHERE seller_id = $user_id ORDER BY created_at DESC");
    if ($result_my_books) {
        $my_books = $result_my_books->fetch_all(MYSQLI_ASSOC);
    }
}

// Saved for later: the five most recently saved books (the full list is on the Wishlist page)
$saved_total = count(k_wishlist_ids());
$saved = [];
if ($saved_total > 0) {
    $saved = $conn->query(
        "SELECT b.id, b.title, b.image FROM wishlist w JOIN books b ON b.id = w.book_id
         WHERE w.user_id = $user_id ORDER BY w.created_at DESC, w.id DESC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC);
}

// Force reload user data to get latest profile pic
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$avatar_path = trim((string) ($user['profile_picture'] ?? ''));
$avatar_url = $avatar_path === '' ? '' : (strpos($avatar_path, 'http') === 0 ? $avatar_path : BASE_URL . $avatar_path);
$initial = strtoupper(mb_substr($user_name, 0, 1));
$member_since = !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '';

// Favorite genres, as the labels the Preferences page shows
$genre_labels = k_genre_options();
$genres = [];
foreach (array_filter(explode(',', (string) ($user['favorite_genres'] ?? ''))) as $value) {
    if (isset($genre_labels[$value])) {
        $genres[] = $genre_labels[$value];
    }
}

// One line under the greeting: what is happening with the latest order
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$phrases = [
    'pending' => 'has been received',
    'processing' => 'is being prepared',
    'shipped' => 'is on its way',
    'delivered' => 'was delivered',
    'cancelled' => 'was cancelled',
];
$latest = $orders[0] ?? null;
$latest_status = $latest ? strtolower((string) $latest['status']) : '';
if ($latest && !in_array($latest_status, $statuses, true)) {
    $latest_status = 'pending';
}

$money = fn($n): string => 'Rs. ' . number_format((float) $n);
$plural = fn(int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);
?>

<main class="fl">
    <div class="fl-wrap pf">

        <!-- Who you are, and what you can do -->
        <aside class="pf-side" aria-label="Your account">
            <div class="pf-id">
                <?php if ($avatar_url !== ''): ?>
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="" class="pf-avatar">
                <?php else: ?>
                    <div class="pf-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></div>
                <?php endif; ?>
                <div class="pf-id__text">
                    <p class="pf-name"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="pf-meta"><?php echo htmlspecialchars((string) ($user['email'] ?? '')); ?></p>
                    <?php if ($member_since !== ''): ?>
                        <p class="pf-meta">Member since <?php echo htmlspecialchars($member_since); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($genres): ?>
                <ul class="pf-tags" aria-label="Your favorite genres">
                    <?php foreach ($genres as $label): ?>
                        <li class="k-tag"><?php echo htmlspecialchars($label); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="pf-hint">Tell us what you like to read and we will point you to it. <a href="preferences.php">Pick favorite genres</a></p>
            <?php endif; ?>

            <div class="pf-actions">
                <a href="sell_book.php" class="k-btn k-btn--primary k-btn--block">Sell a Book</a>
                <a href="preferences.php" class="k-btn k-btn--outline k-btn--block">Edit Profile</a>
                <a href="logout.php" class="k-btn k-btn--danger-light k-btn--block">Logout</a>
            </div>
        </aside>

        <div class="pf-main">

            <header class="pf-hello">
                <h1 class="fl-h1">Hello, <?php echo htmlspecialchars($user_name); ?></h1>
                <?php if ($latest): ?>
                    <p class="fl-lede">Your latest order, <a href="order.php?id=<?php echo (int) $latest['id']; ?>">#<?php echo (int) $latest['id']; ?></a>,
                        <?php echo $phrases[$latest_status]; ?>.</p>
                <?php else: ?>
                    <p class="fl-lede">Your orders, saved books and listings will show up here.</p>
                <?php endif; ?>
            </header>

            <!-- Orders -->
            <section class="pf-sec" aria-labelledby="pf-orders">
                <div class="fl-head">
                    <div>
                        <h2 class="fl-h2" id="pf-orders">Orders</h2>
                        <?php if ($orders): ?>
                            <p class="fl-sub"><?php echo $plural(count($orders), 'order', 'orders'); ?>, newest first</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($orders): ?>
                    <ul class="pf-orders fl-panel">
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $oid = (int) $order['id'];
                            $status = strtolower((string) $order['status']);
                            if (!in_array($status, $statuses, true)) {
                                $status = 'pending';
                            }
                            $books_in = $order_books[$oid] ?? [];
                            $book_count = array_sum(array_map(fn($b) => (int) $b['quantity'], $books_in));
                            $shown = array_slice($books_in, 0, 3);
                            $more = count($books_in) - count($shown);
                            $step = array_search($status, ['pending', 'processing', 'shipped', 'delivered'], true);
                            $address = k_order_address($order);
                            ?>
                            <li class="pf-order">
                                <a class="pf-stack<?php echo $shown ? '' : ' pf-stack--empty'; ?>" href="order.php?id=<?php echo $oid; ?>" tabindex="-1" aria-hidden="true">
                                    <?php if ($shown): ?>
                                        <?php foreach ($shown as $i => $b): ?>
                                            <span class="pf-stack__book" style="--i: <?php echo $i; ?>"><?php k_cover($b, 'pf-stack__img', false); ?></span>
                                        <?php endforeach; ?>
                                        <?php if ($more > 0): ?><span class="pf-stack__more">+<?php echo $more; ?></span><?php endif; ?>
                                    <?php else: ?>
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" />
                                            <path d="M9 8h6M9 12h6" />
                                        </svg>
                                    <?php endif; ?>
                                </a>

                                <div class="pf-order__main">
                                    <a class="pf-order__id" href="order.php?id=<?php echo $oid; ?>">Order #<?php echo $oid; ?></a>
                                    <p class="pf-order__meta"><?php echo date('F j, Y', strtotime($order['created_at'])); ?><?php echo $book_count > 0 ? ' · ' . $plural($book_count, 'book', 'books') : ''; ?></p>
                                    <?php if ($address !== ''): ?>
                                        <p class="pf-order__ship">To <?php echo htmlspecialchars($address); ?></p>
                                    <?php endif; ?>
                                    <?php if ($step !== false): ?>
                                        <div class="pf-track" role="img" aria-label="Progress: <?php echo $status; ?>, step <?php echo $step + 1; ?> of 4">
                                            <?php for ($n = 0; $n < 4; $n++): ?><i<?php echo $n <= $step ? ' class="is-on"' : ''; ?>></i><?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="pf-order__side">
                                    <span class="pf-order__total"><?php echo $money($order['total']); ?></span>
                                    <span class="k-pill k-pill--<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                                    <a href="order.php?id=<?php echo $oid; ?>" class="k-btn k-btn--ghost k-btn--sm"
                                        aria-label="View details of order #<?php echo $oid; ?>">View details</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <?php k_empty_state('cart', 'No orders yet', 'When you place an order, you can follow it here.', 'Start Shopping', 'shop.php', 3); ?>
                <?php endif; ?>
            </section>

            <!-- Saved for later -->
            <?php if ($saved): ?>
                <section class="pf-sec" aria-labelledby="pf-saved">
                    <div class="fl-head">
                        <div>
                            <h2 class="fl-h2" id="pf-saved">Saved for later</h2>
                            <p class="fl-sub"><?php echo $plural($saved_total, 'book', 'books'); ?> on your wishlist</p>
                        </div>
                        <a href="wishlist.php" class="k-more">View wishlist
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </a>
                    </div>
                    <ul class="pf-saved">
                        <?php foreach ($saved as $b): ?>
                            <li>
                                <a href="product.php?id=<?php echo (int) $b['id']; ?>" aria-label="<?php echo htmlspecialchars($b['title']); ?>">
                                    <?php k_cover($b, 'pf-saved__img'); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <!-- Listings -->
            <section class="pf-sec" aria-labelledby="pf-listings">
                <div class="fl-head">
                    <div>
                        <h2 class="fl-h2" id="pf-listings">Your listings</h2>
                        <?php if ($my_books): ?>
                            <p class="fl-sub"><?php echo $plural(count($my_books), 'book', 'books'); ?> for sale</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($my_books): ?>
                        <a href="sell_book.php" class="k-btn k-btn--primary k-btn--sm">Add New</a>
                    <?php endif; ?>
                </div>

                <?php if ($my_books): ?>
                    <div class="fl-grid">
                        <?php foreach ($my_books as $book): ?>
                            <?php $book_href = 'product.php?id=' . (int) $book['id']; $stock = (int) ($book['stock'] ?? 0); ?>
                            <article class="k-book">
                                <a class="k-book__cover" href="<?php echo $book_href; ?>" tabindex="-1" aria-hidden="true">
                                    <?php k_cover($book); ?>
                                </a>
                                <div class="k-book__info">
                                    <span class="k-book__cat"><?php echo htmlspecialchars((string) $book['category']); ?></span>
                                    <a class="k-book__title" href="<?php echo $book_href; ?>"><?php echo htmlspecialchars($book['title']); ?></a>
                                    <span class="k-book__author"><?php echo $stock > 0 ? $stock . ' in stock' : 'Out of stock'; ?></span>
                                </div>
                                <div class="k-book__foot">
                                    <span class="k-book__price"><?php echo $money($book['price']); ?></span>
                                    <a href="<?php echo $book_href; ?>" class="k-btn k-btn--outline k-btn--sm">View</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php k_empty_state('cart', 'No books listed yet', 'Books you list for sale will show up here.', 'List Your First Book', 'sell_book.php', 3); ?>
                <?php endif; ?>
            </section>

        </div>
    </div>
</main>

<?php
$hide_footer = true; // the account page ends with its own content; the site script still loads
require_once '../includes/footer.php';
?>
