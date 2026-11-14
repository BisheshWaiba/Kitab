<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/reviews.php';
// The Book page is flat (white and light-grey surfaces, from the design canvas); only its buttons are glass.
// is-gbtn switches the glass buttons on, and these stylesheets are loaded by the redesigned pages only
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/product-flat.css'];
$legal = require '../includes/legal_pages.php'; // for the return period, so it is only ever written in one place
$return_days = (int) $legal['site']['return_days'];
// The book is looked up (and add-to-cart handled) before the header is output, so the tab title
// and the cart badge in the header are correct. That needs the session started first.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM books WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    http_response_code(404);
    $page_title = 'Book not found';
    require_once '../includes/header.php';
    echo "<div class='k-page'>";
    k_empty_state('cart', 'Book not found', 'It may have been removed, or the link is mistyped.', 'Browse Books', 'shop.php');
    echo "</div>";
    require_once '../includes/footer.php';
    exit();
}

$book = $result->fetch_assoc();

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $book_id = (int) $book['id'];
    $quantity = max(1, min(100, intval($_POST['quantity'] ?? 1)));

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Up to 100 copies of one book (the cart page's limit), and never more than are in stock
    $max = k_max_qty(k_stock_of($book));
    $in_cart = (int) ($_SESSION['cart'][$book_id] ?? 0);
    $added = min($quantity, max(0, $max - $in_cart));
    $note = '';
    if ($max <= 0) {
        $note = 'This book is sold out.';
    } elseif ($added <= 0) {
        $note = ($max === 1 ? 'The only copy is' : 'All ' . $max . ' available copies are') . ' already in your cart.';
    } elseif ($added < $quantity) {
        $note = 'Only ' . k_copies($max) . ' available, so we added ' . $added . '.';
    }
    if ($added > 0) {
        $_SESSION['cart'][$book_id] = $in_cart + $added;
    }

    // The page's script adds without leaving the book and shows a message; it asks for JSON
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => $added > 0,
            'count' => array_sum($_SESSION['cart']),
            'added' => $added,
            'inCart' => (int) ($_SESSION['cart'][$book_id] ?? 0),
            'note' => $note, // why fewer (or none) were added; empty when all went in
            'shipNote' => k_ship_note(k_cart_total($conn), (float) $book['price']), // the line under Add to Cart, up to date
        ]);
        exit();
    }

    // Without JavaScript: back to the book, where the same message is shown once
    $_SESSION['cart_toast'] = ['book' => $book_id, 'added' => $added, 'note' => $note];
    header("Location: product.php?id=" . $book_id);
    exit();
}

// The "added to your cart" message left by the step above
$cart_toast = $_SESSION['cart_toast'] ?? null;
unset($_SESSION['cart_toast']);
if ($cart_toast && (int) $cart_toast['book'] !== (int) $book['id']) {
    $cart_toast = null;
}

// Reviews
[$review_count, $review_avg, $review_dist] = k_review_summary($conn, (int) $book['id']);
$show_all_reviews = ($_GET['reviews'] ?? '') === 'all';
$viewer_id = (int) ($_SESSION['user_id'] ?? 0);
// How the reviews are shown: sorted (rsort) and/or only one rating (rstars), both from the bar above the list
$rsort = (is_string($_GET['rsort'] ?? null) && isset(REVIEW_SORTS[$_GET['rsort']])) ? $_GET['rsort'] : 'default';
$rstars = in_array($_GET['rstars'] ?? '', ['1', '2', '3', '4', '5'], true) ? (int) $_GET['rstars'] : 0;
$reviews = k_review_list($conn, (int) $book['id'], $show_all_reviews ? 100000 : REVIEW_PAGE_SIZE, $rsort, $rstars, $viewer_id);
$review_shown_total = k_review_count($conn, (int) $book['id'], $rstars);
// The address of this page with some of those choices changed ('' removes one), back at the reviews
$rlink = function (array $change = []) use ($book, $rsort, $rstars, $show_all_reviews): string {
    $q = array_merge([
        'id' => (int) $book['id'],
        'rsort' => $rsort !== 'default' ? $rsort : '',
        'rstars' => $rstars > 0 ? (string) $rstars : '',
        'reviews' => $show_all_reviews ? 'all' : '',
    ], $change);
    return 'product.php?' . htmlspecialchars(http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null)), ENT_QUOTES) . '#reviews';
};
$viewer_is_admin = ($_SESSION['user_role'] ?? '') === 'admin';
$my_review = $viewer_id > 0 ? k_review_of($conn, (int) $book['id'], $viewer_id) : null;
$viewer_is_seller = $viewer_id > 0 && (int) $book['seller_id'] === $viewer_id;
// One-time message from review.php (and what was typed, if the review was not accepted)
$review_flash = $_SESSION['review_flash'] ?? null;
unset($_SESSION['review_flash']);
if ($review_flash && (int) $review_flash['book'] !== (int) $book['id']) {
    $review_flash = null;
}
$form_rating = (int) ($review_flash['old']['rating'] ?? ($my_review['rating'] ?? 0));
$form_comment = (string) ($review_flash['old']['comment'] ?? ($my_review['comment'] ?? ''));

$description = trim((string) ($book['description'] ?? ''));
$page_title = $book['title'];

// What a shared link to this book shows (see includes/share_meta.php): its own cover, and the author and price in the text
$meta_type = 'book';
$meta_image = trim((string) ($book['image'] ?? ''));
$meta_image_alt = 'Cover of ' . $book['title'];
$blurb = trim(preg_replace('/\s+/u', ' ', strip_tags($description)));
if (mb_strlen($blurb) > 180) {
    $blurb = preg_replace('/\s+\S*$/u', '', mb_substr($blurb, 0, 180)) . '…'; // ends on a whole word
}
$meta_description = 'By ' . $book['author'] . '. Rs. ' . number_format((float) $book['price']) . '. ' . ($blurb !== '' ? $blurb : 'Available at KITAB.');
require_once '../includes/header.php';
?>

<?php
$cover_img = trim((string) ($book['image'] ?? ''));
$cover_url = $cover_img === '' ? '' : (strpos($cover_img, 'http') === 0 ? $cover_img : BASE_URL . $cover_img);
$category = trim((string) ($book['category'] ?? ''));
$stock = k_stock_of($book);
$sold_out = $stock !== null && $stock <= 0;
$max_qty = max(1, k_max_qty($stock)); // the most the quantity box accepts
?>
<main class="fl">

<section class="gp-product fl-wrap">
    <div class="gp-stage">
        <?php k_cover($book, 'gp-stage__cover', false); ?>
        <?php if ($category !== ''): ?>
            <span class="gp-tag"><?php echo htmlspecialchars($category); ?></span>
        <?php endif; ?>
    </div>

    <div class="gp-buy">
        <h1 class="fl-h1 gp-title"><?php echo htmlspecialchars($book['title']); ?></h1>
        <p class="gp-by">by <a href="shop.php?author=<?php echo urlencode(trim($book['author'])); ?>"><?php echo htmlspecialchars($book['author']); ?></a></p>
        <p class="gp-rating">
            <?php if ($review_count > 0): ?>
                <a href="#reviews"><?php echo k_stars($review_avg); ?>
                    <span><?php echo number_format($review_avg, 1); ?> &middot; <?php echo $review_count . ($review_count === 1 ? ' review' : ' reviews'); ?></span></a>
            <?php else: ?>
                <a href="#reviews" class="gp-rating__none">No reviews yet</a>
            <?php endif; ?>
        </p>
        <p class="gp-price">Rs. <?php echo htmlspecialchars(number_format($book['price'])); ?></p>
        <?php if ($stock !== null): ?>
            <p class="gp-stock gp-stock--<?php echo $sold_out ? 'out' : ($stock <= 5 ? 'low' : 'ok'); ?>">
                <span class="gp-stock__dot" aria-hidden="true"></span>
                <?php echo $sold_out ? 'Out of stock' : ($stock <= 5 ? 'Only ' . $stock . ' left' : 'In stock'); ?>
            </p>
        <?php endif; ?>

        <?php if ($description !== ''): ?>
            <p class="gp-desc"><?php echo nl2br(htmlspecialchars($description)); ?></p>
        <?php endif; ?>

        <dl class="gp-specs">
            <div>
                <dt>Publisher</dt>
                <dd><?php echo htmlspecialchars($book['publisher'] ?: 'N/A'); ?></dd>
            </div>
            <div>
                <dt>Language</dt>
                <dd><?php echo htmlspecialchars($book['language'] ?? 'English'); ?></dd>
            </div>
            <div>
                <dt>Pages</dt>
                <dd><?php echo htmlspecialchars($book['pages'] ?: 'N/A'); ?></dd>
            </div>
            <div>
                <dt>ISBN</dt>
                <dd><?php echo htmlspecialchars($book['isbn'] ?: 'N/A'); ?></dd>
            </div>
        </dl>

        <div class="gp-actions">
            <?php if ($sold_out): ?>
                <div class="gp-buyform">
                    <button type="button" class="k-btn k-btn--primary k-btn--lg" disabled>Out of stock</button>
                </div>
            <?php else: ?>
            <form method="POST" class="gp-buyform" data-add-form data-title="<?php echo htmlspecialchars($book['title']); ?>"
                data-cover="<?php echo htmlspecialchars($cover_url); ?>">
                <label class="k-sr" for="quantity">Quantity</label>
                <div class="gp-qty" data-qty>
                    <button type="button" class="gp-qty__btn" data-step="-1" aria-label="Decrease quantity">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M5 12h14" />
                        </svg>
                    </button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $max_qty; ?>">
                    <button type="button" class="gp-qty__btn" data-step="1" aria-label="Increase quantity">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>
                <button type="submit" name="add_to_cart" class="k-btn k-btn--primary k-btn--lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 4h2.2l2.1 10.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.2" />
                        <circle cx="9.5" cy="19.5" r="1.4" />
                        <circle cx="17" cy="19.5" r="1.4" />
                    </svg>
                    <span data-add-label>Add to Cart</span>
                </button>
            </form>
            <?php endif; ?>
            <?php k_wish_button($book, 'button'); ?>
        </div>

        <?php // where the cart stands against the free-shipping promise (main.js refreshes it after each Add to Cart) ?>
        <p class="gp-ship" data-ship-note>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 7h11v9H3z" />
                <path d="M14 10h4l3 3v3h-7z" />
                <circle cx="7" cy="18" r="1.6" />
                <circle cx="17" cy="18" r="1.6" />
            </svg>
            <span data-ship-text><?php echo htmlspecialchars(k_ship_note(k_cart_total($conn), (float) $book['price'])); ?></span>
        </p>

        <ul class="gp-trust">
            <li>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="6" width="18" height="12" rx="2" />
                    <circle cx="12" cy="12" r="2.6" />
                </svg>
                Pay when it arrives
            </li>
            <li>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 8h10a5 5 0 0 1 0 10H8" />
                    <path d="M8 4 4 8l4 4" />
                </svg>
                <a href="legal.php?page=returns">Return within <?php echo $return_days; ?> days</a>
            </li>
        </ul>
    </div>
</section>

<!-- Reviews: the summary (the average, how many gave each rating), a bar to sort and filter, then the reviews as plain rows -->
<section class="k-reviews gr" id="reviews" aria-labelledby="reviews-title">
    <?php if ($review_flash): ?>
        <div class="k-alert k-alert--<?php echo $review_flash['type'] === 'success' ? 'success' : 'error'; ?>"
            role="<?php echo $review_flash['type'] === 'success' ? 'status' : 'alert'; ?>">
            <?php echo htmlspecialchars($review_flash['message']); ?></div>
    <?php endif; ?>

    <div class="gr-summary">
        <div class="gr-score">
            <?php if ($review_count > 0): ?>
                <p class="gr-score__num"><?php echo number_format($review_avg, 1); ?><small>/5</small></p>
                <?php echo k_stars($review_avg); ?>
                <p class="gr-score__n"><?php echo $review_count . ($review_count === 1 ? ' Rating' : ' Ratings'); ?></p>
            <?php else: ?>
                <p class="gr-score__none">No ratings yet</p>
            <?php endif; ?>
            <a href="#write-review" class="gr-score__link"><?php echo $my_review ? 'Edit your review' : 'Write a review'; ?></a>
        </div>
        <?php if ($review_count > 0): ?>
            <ul class="gr-bars" aria-label="Ratings by number of stars">
                <?php foreach ($review_dist as $stars => $n): ?>
                    <li>
                        <a href="<?php echo $rlink(['rstars' => $stars, 'reviews' => '']); ?>" class="gr-bars__row"
                            aria-label="<?php echo $n . ($n === 1 ? ' rating' : ' ratings') . ' of ' . $stars . ($stars === 1 ? ' star' : ' stars'); ?>: show them">
                            <?php echo k_stars((float) $stars); ?>
                            <span class="gr-track" aria-hidden="true"><span class="gr-fill" style="width: <?php echo round($n / $review_count * 100, 1); ?>%"></span></span>
                            <span class="gr-n"><?php echo $n; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="gr-bar">
        <h2 class="gr-bar__title" id="reviews-title">Product Reviews</h2>
        <form method="GET" action="product.php#reviews" class="gr-tools" data-review-tools>
            <input type="hidden" name="id" value="<?php echo (int) $book['id']; ?>">
            <?php if ($show_all_reviews): ?><input type="hidden" name="reviews" value="all"><?php endif; ?>
            <label class="gr-tool">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 20V5M4 9l4-4 4 4M16 4v15M12 15l4 4 4-4" /></svg>
                <span class="gr-tool__k">Sort:</span> <strong><?php echo REVIEW_SORTS[$rsort]; ?></strong>
                <select name="rsort" class="gr-select" aria-label="Sort the reviews" data-autosubmit>
                    <?php foreach (REVIEW_SORTS as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $rsort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="gr-tool">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18l-7 8.5V20l-4-2v-4.5L3 5z" /></svg>
                <span class="gr-tool__k">Filter:</span> <strong><?php echo $rstars > 0 ? $rstars . ' star' : 'All star'; ?></strong>
                <select name="rstars" class="gr-select" aria-label="Show only reviews with this many stars" data-autosubmit>
                    <option value="">All star</option>
                    <?php for ($v = 5; $v >= 1; $v--): ?>
                        <option value="<?php echo $v; ?>" <?php echo $rstars === $v ? 'selected' : ''; ?>><?php echo $v; ?> star</option>
                    <?php endfor; ?>
                </select>
            </label>
            <noscript><button type="submit" class="k-btn k-btn--outline k-btn--sm">Apply</button></noscript>
        </form>
    </div>

    <?php if ($reviews): ?>
        <ul class="gr-list">
            <?php foreach ($reviews as $rv): ?>
                <?php
                $mine = $viewer_id > 0 && (int) $rv['user_id'] === $viewer_id;
                $rv_name = k_review_name((string) $rv['name']);
                $rv_text = trim((string) $rv['comment']);
                $helpful_n = (int) $rv['helpful'];
                ?>
                <li class="gr-item" id="review-<?php echo (int) $rv['id']; ?>">
                    <div class="gr-item__top">
                        <?php echo k_stars((float) $rv['rating']); ?>
                        <time class="gr-item__date" datetime="<?php echo htmlspecialchars(date('Y-m-d', strtotime($rv['created_at']))); ?>"><?php echo date('j M Y', strtotime($rv['created_at'])); ?></time>
                    </div>
                    <p class="gr-item__who">
                        <span><?php echo htmlspecialchars($rv_name); ?></span>
                        <?php if ($mine): ?><span class="gr-item__you">You</span><?php endif; ?>
                        <?php if (!empty($rv['verified'])): ?>
                            <span class="gr-item__verified">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5" /></svg>
                                Verified purchase
                            </span>
                        <?php endif; ?>
                    </p>
                    <?php if ($rv_text !== ''): ?>
                        <p class="gr-item__text"><?php echo nl2br(htmlspecialchars($rv['comment'])); ?></p>
                    <?php endif; ?>
                    <div class="gr-item__foot">
                        <?php if ($mine): ?>
                            <span class="gr-helpful gr-helpful--static" title="People who found your review helpful">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2 10.5h4V21H2zM22 11.2c0-1-.8-1.9-1.9-1.9h-5.5l.8-3.9v-.3c0-.4-.2-.8-.4-1.1L14 3 7.6 9.4c-.4.4-.6.9-.6 1.4V19c0 1.1.9 2 2 2h8.2c.8 0 1.5-.5 1.8-1.2l2.9-6.7c.1-.3.1-.6.1-.8v-1.1z" /></svg>
                                <span class="gr-helpful__n"><?php echo $helpful_n; ?></span>
                                <span class="k-sr">people found your review helpful</span>
                            </span>
                        <?php else: ?>
                            <form method="POST" action="review.php" class="gr-helpful-form" data-helpful>
                                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                <input type="hidden" name="review_id" value="<?php echo (int) $rv['id']; ?>">
                                <input type="hidden" name="do" value="helpful">
                                <button type="submit" class="gr-helpful<?php echo !empty($rv['voted']) ? ' is-on' : ''; ?>" aria-pressed="<?php echo !empty($rv['voted']) ? 'true' : 'false'; ?>"
                                    aria-label="Mark this review as helpful (<?php echo $helpful_n; ?> so far)">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2 10.5h4V21H2zM22 11.2c0-1-.8-1.9-1.9-1.9h-5.5l.8-3.9v-.3c0-.4-.2-.8-.4-1.1L14 3 7.6 9.4c-.4.4-.6.9-.6 1.4V19c0 1.1.9 2 2 2h8.2c.8 0 1.5-.5 1.8-1.2l2.9-6.7c.1-.3.1-.6.1-.8v-1.1z" /></svg>
                                    <span class="gr-helpful__n"><?php echo $helpful_n; ?></span>
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($mine || $viewer_is_admin): ?>
                            <details class="gr-menu">
                                <summary aria-label="Options for this review">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8" /><circle cx="12" cy="12" r="1.8" /><circle cx="12" cy="19" r="1.8" /></svg>
                                </summary>
                                <div class="gr-menu__panel">
                                    <?php if ($mine): ?>
                                        <a href="#write-review" class="gr-menu__item">Edit review</a>
                                        <form method="POST" action="review.php" onsubmit="return confirm('Delete your review of this book?')">
                                            <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                            <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                            <input type="hidden" name="do" value="delete">
                                            <button type="submit" class="gr-menu__item gr-menu__item--danger">Delete review</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($viewer_is_admin && !$mine): ?>
                                        <form method="POST" action="review.php" onsubmit="return confirm('Remove this review from the page?')">
                                            <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                            <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                            <input type="hidden" name="review_id" value="<?php echo (int) $rv['id']; ?>">
                                            <input type="hidden" name="do" value="remove">
                                            <button type="submit" class="gr-menu__item gr-menu__item--danger">Remove review</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($review_shown_total > count($reviews)): ?>
            <a href="<?php echo $rlink(['reviews' => 'all']); ?>" class="k-btn k-btn--outline gr-more">Show all <?php echo $review_shown_total; ?> reviews</a>
        <?php endif; ?>
    <?php elseif ($rstars > 0): ?>
        <p class="gr-empty">No <?php echo $rstars; ?>-star reviews yet. <a href="<?php echo $rlink(['rstars' => '', 'reviews' => '']); ?>">Show all reviews</a></p>
    <?php else: ?>
        <p class="gr-empty">No reviews yet. Be the first to say what you thought.</p>
    <?php endif; ?>

    <?php // Writing a review: the same form as before, under the list ?>
    <div class="gr-write" id="write-review">
        <?php if ($viewer_id === 0): ?>
            <h3 class="gr-write__title">Write a review</h3>
            <p class="gr-write__note">Log in to share what you thought of this book.</p>
            <a href="login.php" class="k-btn k-btn--outline">Log In</a>
        <?php elseif ($viewer_is_seller): ?>
            <h3 class="gr-write__title">Write a review</h3>
            <p class="gr-write__note">You listed this book, so you can't review it.</p>
        <?php elseif ($my_review && $my_review['status'] === 'rejected'): ?>
            <h3 class="gr-write__title">Your review</h3>
            <p class="gr-write__note">Your review was removed by our team and can't be changed.</p>
        <?php else: ?>
            <form method="POST" action="review.php" class="gr-write__form" data-validate>
                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                <input type="hidden" name="do" value="save">
                <h3 class="gr-write__title"><?php echo $my_review ? 'Edit your review' : 'Write a review'; ?></h3>

                <fieldset class="k-rate">
                    <legend class="k-field__label">Your rating</legend>
                    <div class="k-rate__stars">
                        <?php for ($v = 5; $v >= 1; $v--): ?>
                            <input type="radio" name="rating" id="rate<?php echo $v; ?>" value="<?php echo $v; ?>" required
                                data-msg-missing="Choose a rating from 1 to 5 stars."
                                <?php echo $form_rating === $v ? 'checked' : ''; ?>>
                            <label for="rate<?php echo $v; ?>"><span class="k-sr"><?php echo $v; ?> star<?php echo $v === 1 ? '' : 's'; ?></span><svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.8l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.7 6.1 20.8l1.2-6.5L2.5 9.7l6.6-.9z" /></svg></label>
                        <?php endfor; ?>
                    </div>
                    <?php // the word for the stars chosen; which one shows is CSS (:has), see product-flat.css ?>
                    <p class="k-rate__word" aria-hidden="true"><span data-v="1">Poor</span><span data-v="2">Fair</span><span data-v="3">Good</span><span data-v="4">Very good</span><span data-v="5">Excellent</span></p>
                </fieldset>

                <div class="k-field">
                    <label for="comment">Your review <span class="k-field__hint">(optional)</span></label>
                    <textarea id="comment" name="comment" rows="4" maxlength="<?php echo REVIEW_MAX_COMMENT; ?>"
                        class="k-input k-textarea" placeholder="What did you think of it?"><?php echo htmlspecialchars($form_comment); ?></textarea>
                </div>
                <div class="gr-write__actions">
                    <button type="submit" class="k-btn k-btn--primary"><?php echo $my_review ? 'Update Review' : 'Post Review'; ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<!-- Related Products -->
<?php
// Same-category books first; when there are fewer than four, fill up with other books
$current_id = (int) $book['id'];
$cat = $conn->real_escape_string((string) $book['category']);
$sql_related = "SELECT * FROM books WHERE id != $current_id ORDER BY (category = '$cat') DESC, RAND() LIMIT 4";
$result_related = $conn->query($sql_related);

if ($result_related->num_rows > 0):
    ?>
    <section class="fl-wrap gp-related">
        <h2 class="fl-h2">You May Also Like</h2>
        <div class="fl-grid">
            <?php while ($row = $result_related->fetch_assoc()): ?>
                <?php k_book_card($row, 'primary'); ?>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>

</main>

<!-- "Added to your cart" message (main.js shows the same one without reloading the page) -->
<div class="gt-toasts" aria-live="polite">
    <?php if ($cart_toast): ?>
        <?php $toast_added = (int) $cart_toast['added']; $toast_note = (string) ($cart_toast['note'] ?? ''); ?>
        <div class="gt-toast" role="status"<?php echo $toast_added > 0 ? ' data-bump' : ''; ?>>
            <?php if ($cover_url !== ''): ?>
                <img class="gt-toast__img" src="<?php echo htmlspecialchars($cover_url); ?>" alt="">
            <?php endif; ?>
            <div class="gt-toast__text">
                <?php if ($toast_added > 0): ?>
                    <strong>Added to your cart</strong>
                    <span><?php echo htmlspecialchars($book['title']); ?> &times; <?php echo $toast_added; ?></span>
                <?php else: ?>
                    <strong>Couldn't add it</strong>
                <?php endif; ?>
                <?php if ($toast_note !== ''): ?><span class="gt-toast__note"><?php echo htmlspecialchars($toast_note); ?></span><?php endif; ?>
            </div>
            <?php if ($toast_added > 0): ?><a href="cart.php" class="k-btn k-btn--primary k-btn--sm">View cart</a><?php endif; ?>
            <button type="button" class="gt-toast__close" aria-label="Dismiss">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>
    <?php endif; ?>
</div>

<?php // Phones and tablets: while the real Add to Cart is off screen, this bar sticks to the bottom (main.js shows it; the click is passed to the real button) ?>
<?php if (!$sold_out): ?>
<div class="gp-bar" data-buybar>
    <div class="gp-bar__info">
        <strong class="gp-bar__price">Rs. <?php echo htmlspecialchars(number_format($book['price'])); ?></strong>
        <span class="gp-bar__title"><?php echo htmlspecialchars($book['title']); ?></span>
    </div>
    <button type="button" class="k-btn k-btn--primary" data-buybar-add aria-label="Add to Cart: <?php echo htmlspecialchars($book['title']); ?>">
        <span data-buybar-label>Add to Cart</span>
    </button>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
