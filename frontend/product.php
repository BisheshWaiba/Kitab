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
$reviews = k_review_list($conn, (int) $book['id'], $show_all_reviews ? 100000 : REVIEW_PAGE_SIZE);
$viewer_id = (int) ($_SESSION['user_id'] ?? 0);
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

<!-- Reviews -->
<section class="k-reviews" id="reviews" aria-labelledby="reviews-title">
    <h2 class="k-h2" id="reviews-title">Reviews</h2>

    <?php if ($review_flash): ?>
        <div class="k-alert k-alert--<?php echo $review_flash['type'] === 'success' ? 'success' : 'error'; ?>"
            role="<?php echo $review_flash['type'] === 'success' ? 'status' : 'alert'; ?>">
            <?php echo htmlspecialchars($review_flash['message']); ?></div>
    <?php endif; ?>

    <div class="k-reviews__grid">
        <div class="k-reviews__side">
            <?php if ($review_count > 0): ?>
                <div class="k-card k-ratingbox">
                    <div class="k-ratingbox__score">
                        <span class="k-ratingbox__num"><?php echo number_format($review_avg, 1); ?></span>
                        <?php echo k_stars($review_avg); ?>
                        <span class="k-ratingbox__count">Based on <?php echo $review_count . ($review_count === 1 ? ' review' : ' reviews'); ?></span>
                    </div>
                    <ul class="k-bars">
                        <?php foreach ($review_dist as $stars => $n): ?>
                            <li>
                                <span><?php echo $stars; ?> star<?php echo $stars === 1 ? '' : 's'; ?></span>
                                <span class="k-bars__track" aria-hidden="true"><span class="k-bars__fill"
                                        style="width: <?php echo round($n / $review_count * 100); ?>%"></span></span>
                                <span class="k-bars__n"><?php echo $n; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($viewer_id === 0): ?>
                <div class="k-card k-reviewform">
                    <h3 class="k-h3">Write a review</h3>
                    <p class="k-reviewform__note">Log in to share what you thought of this book.</p>
                    <a href="login.php" class="k-btn k-btn--outline">Log In</a>
                </div>
            <?php elseif ($viewer_is_seller): ?>
                <div class="k-card k-reviewform">
                    <h3 class="k-h3">Write a review</h3>
                    <p class="k-reviewform__note">You listed this book, so you can't review it.</p>
                </div>
            <?php elseif ($my_review && $my_review['status'] === 'rejected'): ?>
                <div class="k-card k-reviewform">
                    <h3 class="k-h3">Your review</h3>
                    <p class="k-reviewform__note">Your review was removed by our team and can't be changed.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="review.php" class="k-card k-reviewform" data-validate>
                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                    <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                    <input type="hidden" name="do" value="save">
                    <h3 class="k-h3"><?php echo $my_review ? 'Edit your review' : 'Write a review'; ?></h3>

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
                    </fieldset>

                    <div class="k-field">
                        <label for="comment">Your review <span class="k-field__hint">(optional)</span></label>
                        <textarea id="comment" name="comment" rows="4" maxlength="<?php echo REVIEW_MAX_COMMENT; ?>"
                            class="k-input k-textarea" placeholder="What did you think of it?"><?php echo htmlspecialchars($form_comment); ?></textarea>
                    </div>
                    <button type="submit" class="k-btn k-btn--primary"><?php echo $my_review ? 'Update Review' : 'Post Review'; ?></button>
                </form>
                <?php if ($my_review): ?>
                    <form method="POST" action="review.php" class="k-reviewdelete"
                        onsubmit="return confirm('Delete your review of this book?')">
                        <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                        <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                        <input type="hidden" name="do" value="delete">
                        <button type="submit" class="k-linkbtn">Delete my review</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="k-reviews__list">
            <?php if ($reviews): ?>
                <ul class="k-reviewlist">
                    <?php foreach ($reviews as $rv): ?>
                        <?php $mine = $viewer_id > 0 && (int) $rv['user_id'] === $viewer_id; ?>
                        <li class="k-review" id="review-<?php echo (int) $rv['id']; ?>">
                            <div class="k-review__head">
                                <span class="k-review__who"><?php echo htmlspecialchars(k_review_name((string) $rv['name'])); ?></span>
                                <?php if ($mine): ?><span class="k-tag">You</span><?php endif; ?>
                                <?php echo k_stars((float) $rv['rating']); ?>
                                <time class="k-review__date" datetime="<?php echo htmlspecialchars(date('Y-m-d', strtotime($rv['created_at']))); ?>"><?php echo date('F j, Y', strtotime($rv['created_at'])); ?></time>
                            </div>
                            <?php if (trim((string) $rv['comment']) !== ''): ?>
                                <p class="k-review__text"><?php echo nl2br(htmlspecialchars($rv['comment'])); ?></p>
                            <?php endif; ?>
                            <?php if ($viewer_is_admin): ?>
                                <form method="POST" action="review.php" class="k-review__admin"
                                    onsubmit="return confirm('Remove this review from the page?')">
                                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                    <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                    <input type="hidden" name="review_id" value="<?php echo (int) $rv['id']; ?>">
                                    <input type="hidden" name="do" value="remove">
                                    <button type="submit" class="k-linkbtn k-linkbtn--danger">Remove review</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($review_count > count($reviews)): ?>
                    <a href="product.php?id=<?php echo (int) $book['id']; ?>&amp;reviews=all#reviews" class="k-btn k-btn--outline">Show all <?php echo $review_count; ?> reviews</a>
                <?php endif; ?>
            <?php else: ?>
                <div class="k-empty">
                    <p>No reviews yet. Be the first to say what you thought.</p>
                </div>
            <?php endif; ?>
        </div>
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
