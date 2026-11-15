<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/reviews.php'; // k_stars(), for the stars on the reviews panel
// My account: a dashboard (recent orders, selling, saved books, reviews, how complete the profile is), then the account details.
// A flat page (white and light-grey surfaces); only its buttons are glass. The Terracotta & Sand colours come from includes/header.php.
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/profile-flat.css', 'assets/css/account-dashboard.css'];
$page_title = 'My account';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// A message left by the page that changed something (shown once)
$notice = (string) ($_SESSION['account_notice'] ?? '');
unset($_SESSION['account_notice']);

// The account details, read fresh so a new photo or genre choice shows at once
k_user_columns($conn);
$stmt = $conn->prepare("SELECT name, email, phone, address, city, date_of_birth, gender, profile_picture, favorite_genres, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?: [];

$name = trim((string) ($user['name'] ?? $_SESSION['user_name'] ?? ''));
$first_name = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY)[0] ?? '';
$email = trim((string) ($user['email'] ?? ''));
$phone = trim((string) ($user['phone'] ?? ''));
$address = trim((string) ($user['address'] ?? ''));
$city = trim((string) ($user['city'] ?? ''));
$gender = k_gender_options()[(string) ($user['gender'] ?? '')] ?? '';
$born = trim((string) ($user['date_of_birth'] ?? ''));
$birthday = $born !== '' && strtotime($born) ? date('F j, Y', strtotime($born)) : '';
$initial = strtoupper(mb_substr($name, 0, 1));
$member_since = !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '';

$avatar_path = trim((string) ($user['profile_picture'] ?? ''));
$avatar_url = $avatar_path === '' ? '' : (strpos($avatar_path, 'http') === 0 ? $avatar_path : BASE_URL . $avatar_path);

// Favorite genres, as the labels the Preferences page shows
$genre_labels = k_genre_options();
$genres = [];
foreach (array_filter(explode(',', (string) ($user['favorite_genres'] ?? ''))) as $value) {
    if (isset($genre_labels[$value])) {
        $genres[] = $genre_labels[$value];
    }
}

// ---------- The dashboard (every query is for the signed-in reader only) ----------
$plural = fn(int $n, string $one, string $many): string => $n . ' ' . ($n === 1 ? $one : $many);
$money = fn(float $n): string => 'Rs. ' . number_format($n);
$status_words = ['pending' => 'Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];

// Orders: how many, what they came to (cancelled ones do not count), how many are still on their way, and the latest three
$stmt = $conn->prepare("SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$order_count = count($orders);
$spent = 0.0;
$on_the_way = 0;
foreach ($orders as $o) {
    if ($o['status'] !== 'cancelled') {
        $spent += (float) $o['total'];
    }
    if (in_array($o['status'], ['pending', 'processing', 'shipped'], true)) {
        $on_the_way++;
    }
}
$recent_orders = array_slice($orders, 0, 3);
$order_books = [];
if ($recent_orders) {
    $ids = implode(',', array_map(fn($o) => (int) $o['id'], $recent_orders));
    $rows = $conn->query(
        "SELECT oi.order_id, b.id AS book_id, b.title, b.image
         FROM order_items oi LEFT JOIN books b ON b.id = oi.book_id
         WHERE oi.order_id IN ($ids) ORDER BY oi.id ASC"
    );
    foreach ($rows ? $rows->fetch_all(MYSQLI_ASSOC) : [] as $r) {
        $r['title'] = $r['title'] ?? 'Book no longer available';
        $order_books[(int) $r['order_id']][] = $r;
    }
}

// Selling: the books this reader has listed, and the latest orders that include them (the buyer's details are not shown)
$stmt = $conn->prepare("SELECT COUNT(*) AS listed, COALESCE(SUM(stock > 0), 0) AS available FROM books WHERE seller_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$selling = $stmt->get_result()->fetch_assoc() ?: ['listed' => 0, 'available' => 0];
$listed_count = (int) $selling['listed'];
$available_count = (int) $selling['available'];
$sales = [];
$copies_ordered = 0;
if ($listed_count > 0) {
    $stmt = $conn->prepare(
        "SELECT o.id AS order_id, o.status, o.created_at, oi.quantity, b.id AS book_id, b.title
         FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN books b ON b.id = oi.book_id
         WHERE b.seller_id = ? ORDER BY o.created_at DESC, o.id DESC LIMIT 3"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(oi.quantity), 0)
         FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN books b ON b.id = oi.book_id
         WHERE b.seller_id = ? AND o.status <> 'cancelled'"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $copies_ordered = (int) $stmt->get_result()->fetch_row()[0];
}

// Saved books (the wishlist)
$stmt = $conn->prepare("SELECT b.id, b.title, b.image FROM wishlist w JOIN books b ON b.id = w.book_id WHERE w.user_id = ? ORDER BY w.created_at DESC, w.id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$saved = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$saved_count = count($saved);

// Reviews written (one the team removed is listed but does not count towards the average)
$stmt = $conn->prepare(
    "SELECT r.rating, r.status, r.created_at, b.id AS book_id, b.title
     FROM reviews r JOIN books b ON b.id = r.book_id WHERE r.user_id = ? ORDER BY r.created_at DESC, r.id DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$review_count = count($my_reviews);
$review_given = array_values(array_filter($my_reviews, fn($r) => $r['status'] !== 'rejected'));
$review_avg = $review_given ? array_sum(array_column($review_given, 'rating')) / count($review_given) : 0.0;

// How complete the profile is: seven optional things
$checks = [
    'Photo' => $avatar_path !== '',
    'Phone number' => $phone !== '',
    'Date of birth' => $birthday !== '',
    'Gender' => $gender !== '',
    'City' => $city !== '',
    'Address' => $address !== '',
    'Favorite genres' => $genres !== [],
];
$checks_done = count(array_filter($checks));
$checks_total = count($checks);
$missing = array_keys(array_filter($checks, fn($ok) => !$ok));
$when = fn(string $stamp): string => date('M j, Y', strtotime($stamp));
?>

<main class="fl">
    <div class="fl-wrap pf ad">

        <header class="ad-head">
            <div class="ad-head__id">
                <?php if ($avatar_url !== ''): ?>
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="" class="pf-avatar">
                <?php else: ?>
                    <div class="pf-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></div>
                <?php endif; ?>
                <div class="ad-head__text">
                    <h1 class="fl-h1">My account</h1>
                    <p class="ad-head__sub">Welcome back<?php echo $first_name !== '' ? ', ' . htmlspecialchars($first_name) : ''; ?><?php echo $member_since !== '' ? ' · Member since ' . htmlspecialchars($member_since) : ''; ?></p>
                </div>
            </div>
            <div class="ad-head__actions">
                <a href="edit_profile.php" class="k-btn k-btn--outline">Edit profile</a>
                <a href="logout.php" class="k-btn k-btn--danger-light">Logout</a>
            </div>
        </header>

        <?php if ($notice !== ''): ?>
            <div class="k-alert k-alert--success pf-notice" role="status"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <div class="ad-grid">

            <div class="ad-col">
            <!-- Recent orders -->
            <section class="ad-panel fl-panel ad-area-orders" aria-labelledby="ad-orders-h">
                <div class="ad-panel__head">
                    <h2 class="ad-panel__title" id="ad-orders-h">Recent orders</h2>
                    <?php if ($order_count > 0): ?><a href="orders.php" class="ad-link">View all</a><?php endif; ?>
                </div>
                <?php if ($order_count > 0): ?>
                    <p class="ad-panel__sub"><?php echo $plural($order_count, 'order', 'orders'); ?> · <?php echo $money($spent); ?> in total<?php echo $on_the_way > 0 ? ' · ' . $on_the_way . ' on the way' : ''; ?></p>
                    <ul class="ad-rows">
                        <?php foreach ($recent_orders as $o): ?>
                            <?php
                            $oid = (int) $o['id'];
                            $items = $order_books[$oid] ?? [];
                            $first = $items[0] ?? ['title' => 'Book no longer available', 'image' => '', 'book_id' => 0];
                            $more = count($items) - 1;
                            ?>
                            <li class="ad-row">
                                <a href="order.php?id=<?php echo $oid; ?>" class="ad-row__link">
                                    <?php k_cover($first, 'ad-cover', false); ?>
                                    <span class="ad-row__text">
                                        <span class="ad-row__title"><?php echo htmlspecialchars($first['title']); ?><?php echo $more > 0 ? ' + ' . $more . ' more' : ''; ?></span>
                                        <span class="ad-row__meta">Order #<?php echo $oid; ?> · <?php echo htmlspecialchars($when($o['created_at'])); ?></span>
                                    </span>
                                    <span class="ad-row__end">
                                        <span class="ad-row__amount"><?php echo $money((float) $o['total']); ?></span>
                                        <span class="ad-badge ad-badge--<?php echo htmlspecialchars($o['status']); ?>"><?php echo htmlspecialchars($status_words[$o['status']] ?? ucfirst($o['status'])); ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="ad-empty">You haven't ordered anything yet. Your orders and where they are will show here.</p>
                    <div class="ad-panel__actions"><a href="shop.php" class="k-btn k-btn--primary">Browse books</a></div>
                <?php endif; ?>
            </section>

            <!-- Reviews -->

            <section class="ad-panel fl-panel ad-area-profile" aria-labelledby="ad-profile-h">
                <div class="ad-panel__head">
                    <h2 class="ad-panel__title" id="ad-profile-h">Your profile</h2>
                </div>
                <p class="ad-panel__sub"><?php echo $checks_done; ?> of <?php echo $checks_total; ?> details added</p>
                <div class="ad-meter" role="progressbar" aria-label="Profile details added" aria-valuemin="0" aria-valuemax="<?php echo $checks_total; ?>" aria-valuenow="<?php echo $checks_done; ?>">
                    <span style="width: <?php echo round($checks_done / $checks_total * 100); ?>%"></span>
                </div>
                <?php if ($missing): ?>
                    <p class="ad-empty">Still to add: <?php echo htmlspecialchars(implode(', ', array_map('mb_strtolower', $missing))); ?>.</p>
                    <div class="ad-panel__actions"><a href="edit_profile.php" class="k-btn k-btn--outline k-btn--sm">Add details</a></div>
                <?php else: ?>
                    <p class="ad-empty">Your profile is complete.</p>
                <?php endif; ?>
            </section>
            </div>

            <div class="ad-col">
            <!-- Selling -->
            <section class="ad-panel fl-panel ad-area-selling" aria-labelledby="ad-selling-h">
                <div class="ad-panel__head">
                    <h2 class="ad-panel__title" id="ad-selling-h">Selling</h2>
                    <?php if ($listed_count > 0): ?><a href="my_listings.php" class="ad-link">My listings</a><?php endif; ?>
                </div>
                <?php if ($listed_count > 0): ?>
                    <p class="ad-panel__sub"><?php echo $plural($listed_count, 'book', 'books'); ?> listed · <?php echo $available_count; ?> in the Marketplace now<?php echo $copies_ordered > 0 ? ' · ' . $plural($copies_ordered, 'copy', 'copies') . ' ordered' : ''; ?></p>
                    <?php if ($sales): ?>
                        <ul class="ad-rows">
                            <?php foreach ($sales as $s): ?>
                                <li class="ad-row">
                                    <a href="product.php?id=<?php echo (int) $s['book_id']; ?>" class="ad-row__link">
                                        <span class="ad-row__text">
                                            <span class="ad-row__title"><?php echo htmlspecialchars($s['title']); ?> × <?php echo (int) $s['quantity']; ?></span>
                                            <span class="ad-row__meta">Order #<?php echo (int) $s['order_id']; ?> · <?php echo htmlspecialchars($when($s['created_at'])); ?></span>
                                        </span>
                                        <span class="ad-badge ad-badge--<?php echo htmlspecialchars($s['status']); ?>"><?php echo htmlspecialchars($status_words[$s['status']] ?? ucfirst($s['status'])); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="ad-empty">No one has ordered your books yet. Orders for them will show here.</p>
                    <?php endif; ?>
                    <div class="ad-panel__actions">
                        <a href="sell_book.php" class="k-btn k-btn--primary k-btn--sm">Sell a book</a>
                        <a href="marketplace.php" class="k-btn k-btn--outline k-btn--sm">Marketplace</a>
                    </div>
                <?php else: ?>
                    <p class="ad-empty">Finished with a book? List it in the Marketplace and another reader can buy it.</p>
                    <div class="ad-panel__actions">
                        <a href="sell_book.php" class="k-btn k-btn--primary k-btn--sm">Sell a book</a>
                        <a href="marketplace.php" class="k-btn k-btn--outline k-btn--sm">Marketplace</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Saved books -->
            <section class="ad-panel fl-panel ad-area-saved" aria-labelledby="ad-saved-h">
                <div class="ad-panel__head">
                    <h2 class="ad-panel__title" id="ad-saved-h">Saved books</h2>
                    <?php if ($saved_count > 0): ?><a href="wishlist.php" class="ad-link">View all</a><?php endif; ?>
                </div>
                <?php if ($saved_count > 0): ?>
                    <p class="ad-panel__sub"><?php echo $plural($saved_count, 'book', 'books'); ?> saved for later</p>
                    <ul class="ad-shelf">
                        <?php foreach (array_slice($saved, 0, 4) as $b): ?>
                            <li><a href="product.php?id=<?php echo (int) $b['id']; ?>" aria-label="<?php echo htmlspecialchars($b['title']); ?>"><?php k_cover($b, 'ad-shelf__cover', false); ?></a></li>
                        <?php endforeach; ?>
                        <?php if ($saved_count > 4): ?><li class="ad-shelf__more">+<?php echo $saved_count - 4; ?> more</li><?php endif; ?>
                    </ul>
                <?php else: ?>
                    <p class="ad-empty">Tap the heart on a book to keep it here for later.</p>
                    <div class="ad-panel__actions"><a href="shop.php" class="k-btn k-btn--outline k-btn--sm">Browse books</a></div>
                <?php endif; ?>
            </section>

            <!-- Profile completeness -->
            <section class="ad-panel fl-panel ad-area-reviews" aria-labelledby="ad-reviews-h">
                <div class="ad-panel__head">
                    <h2 class="ad-panel__title" id="ad-reviews-h">Your reviews</h2>
                </div>
                <?php if ($review_count > 0): ?>
                    <p class="ad-panel__sub"><?php echo $plural($review_count, 'review', 'reviews'); ?><?php echo $review_given ? ' · you give ' . rtrim(rtrim(number_format($review_avg, 1), '0'), '.') . ' stars on average' : ''; ?></p>
                    <ul class="ad-rows">
                        <?php foreach (array_slice($my_reviews, 0, 3) as $r): ?>
                            <li class="ad-row">
                                <a href="product.php?id=<?php echo (int) $r['book_id']; ?>#reviews" class="ad-row__link">
                                    <span class="ad-row__text">
                                        <span class="ad-row__title"><?php echo htmlspecialchars($r['title']); ?></span>
                                        <span class="ad-row__meta"><?php echo k_stars((float) $r['rating']); ?> · <?php echo htmlspecialchars($when($r['created_at'])); ?></span>
                                    </span>
                                    <?php if ($r['status'] === 'rejected'): ?><span class="ad-badge ad-badge--cancelled">Removed</span><?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="ad-empty">You haven't reviewed a book yet. A few honest words help other readers choose.</p>
                    <div class="ad-panel__actions"><a href="shop.php" class="k-btn k-btn--outline k-btn--sm">Find a book to review</a></div>
                <?php endif; ?>
            </section>
            </div>
        </div>

        <!-- Account details -->
        <section class="ad-details fl-panel" id="details" aria-labelledby="ad-details-h">
            <div class="ad-panel__head">
                <h2 class="ad-panel__title" id="ad-details-h">Account details</h2>
                <a href="edit_profile.php" class="ad-link">Edit</a>
            </div>
            <dl class="pf-list">
                <div class="pf-row">
                    <dt>Email</dt>
                    <dd><?php echo htmlspecialchars($email); ?></dd>
                </div>
                <div class="pf-row">
                    <dt>Phone</dt>
                    <dd><?php echo $phone !== '' ? htmlspecialchars($phone) : '<span class="pf-empty">Not added yet</span>'; ?></dd>
                </div>
                <div class="pf-row">
                    <dt>Date of birth</dt>
                    <dd><?php echo $birthday !== '' ? htmlspecialchars($birthday) : '<span class="pf-empty">Not added yet</span>'; ?></dd>
                </div>
                <div class="pf-row">
                    <dt>Gender</dt>
                    <dd><?php echo $gender !== '' ? htmlspecialchars($gender) : '<span class="pf-empty">Not added yet</span>'; ?></dd>
                </div>
                <div class="pf-row">
                    <dt>City</dt>
                    <dd><?php echo $city !== '' ? htmlspecialchars($city) : '<span class="pf-empty">Not added yet</span>'; ?></dd>
                </div>
                <div class="pf-row">
                    <dt>Address</dt>
                    <dd><?php echo $address !== '' ? nl2br(htmlspecialchars($address)) : '<span class="pf-empty">Not added yet</span>'; ?></dd>
                </div>
                <div class="pf-row pf-row--wide">
                    <dt>Favorite genres</dt>
                    <dd>
                        <?php if ($genres): ?>
                            <ul class="pf-tags">
                                <?php foreach ($genres as $label): ?>
                                    <li class="k-tag"><?php echo htmlspecialchars($label); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="pf-empty">None chosen yet</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="pf-row pf-row--wide">
                    <dt>Password</dt>
                    <dd>
                        <span class="pf-secret" aria-hidden="true">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span><span class="k-sr">Hidden</span>
                        <div class="pf-row__actions">
                            <a href="change_password.php" class="k-btn k-btn--outline k-btn--sm">Change password</a>
                            <form method="POST" action="change_password.php">
                                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                <input type="hidden" name="do" value="reset">
                                <button type="submit" class="k-btn k-btn--ghost k-btn--sm">Email me a reset link</button>
                            </form>
                        </div>
                    </dd>
                </div>
            </dl>
        </section>
    </div>
</main>

<?php
$hide_footer = true; // the account page ends with its own content; the site script still loads
require_once '../includes/footer.php';
?>
