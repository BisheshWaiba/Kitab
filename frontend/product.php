<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/reviews.php';
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
    echo "<div class='k-page'>
    <div class='k-empty'>
        <p>Book not found.</p>
        <a href='shop.php' class='k-btn k-btn--outline'>Back to Shop</a>
    </div>
</div>";
    require_once '../includes/footer.php';
    exit();
}

$book = $result->fetch_assoc();

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $book_id = $book['id'];
    $quantity = max(1, min(100, intval($_POST['quantity'])));

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id] += $quantity;
    } else {
        $_SESSION['cart'][$book_id] = $quantity;
    }

    header("Location: cart.php");
    exit();
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
require_once '../includes/header.php';
?>

<section class="k-product">
    <div class="k-product__media">
        <?php k_cover($book, 'k-product__cover', false); ?>
    </div>

    <div class="k-product__info">
        <span class="k-product__cat"><?php echo htmlspecialchars($book['category']); ?></span>
        <h1 class="k-product__title"><?php echo htmlspecialchars($book['title']); ?></h1>
        <p class="k-product__author">by <a href="shop.php?author=<?php echo urlencode(trim($book['author'])); ?>"><?php echo htmlspecialchars($book['author']); ?></a></p>
        <p class="k-rating">
            <?php if ($review_count > 0): ?>
                <a href="#reviews"><?php echo k_stars($review_avg); ?>
                    <span><?php echo number_format($review_avg, 1); ?> &middot; <?php echo $review_count . ($review_count === 1 ? ' review' : ' reviews'); ?></span></a>
            <?php else: ?>
                <a href="#reviews" class="k-rating__none">No reviews yet</a>
            <?php endif; ?>
        </p>
        <p class="k-product__price">Rs. <?php echo htmlspecialchars(number_format($book['price'])); ?></p>

        <?php if ($description !== ''): ?>
            <p class="k-product__desc"><?php echo nl2br(htmlspecialchars($description)); ?></p>
        <?php endif; ?>

        <div class="k-specs">
            <h2 class="k-specs__title">Product Details</h2>
            <dl>
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
        </div>

        <form method="POST" class="k-buy">
            <div class="k-qty">
                <label for="quantity">Quantity</label>
                <div class="k-qty__box" data-qty>
                    <button type="button" class="k-qty__btn" data-step="-1" aria-label="Decrease quantity">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M5 12h14" />
                        </svg>
                    </button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="100">
                    <button type="button" class="k-qty__btn" data-step="1" aria-label="Increase quantity">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" name="add_to_cart" class="k-btn k-btn--primary">Add to Cart</button>
        </form>
        <?php k_wish_button($book, 'button'); ?>
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
                <form method="POST" action="review.php" class="k-card k-reviewform">
                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                    <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                    <input type="hidden" name="do" value="save">
                    <h3 class="k-h3"><?php echo $my_review ? 'Edit your review' : 'Write a review'; ?></h3>

                    <fieldset class="k-rate">
                        <legend class="k-field__label">Your rating</legend>
                        <div class="k-rate__stars">
                            <?php for ($v = 5; $v >= 1; $v--): ?>
                                <input type="radio" name="rating" id="rate<?php echo $v; ?>" value="<?php echo $v; ?>" required
                                    <?php echo $form_rating === $v ? 'checked' : ''; ?>>
                                <label for="rate<?php echo $v; ?>"><span class="k-sr"><?php echo $v; ?> star<?php echo $v === 1 ? '' : 's'; ?></span><span aria-hidden="true">&#9733;</span></label>
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
// Same-category books first; when there are fewer than three, fill up with other books
$current_id = (int) $book['id'];
$cat = $conn->real_escape_string((string) $book['category']);
$sql_related = "SELECT * FROM books WHERE id != $current_id ORDER BY (category = '$cat') DESC, RAND() LIMIT 3";
$result_related = $conn->query($sql_related);

if ($result_related->num_rows > 0):
    ?>
    <section class="k-related">
        <h2 class="k-h2">You May Also Like</h2>
        <div class="k-grid">
            <?php while ($row = $result_related->fetch_assoc()): ?>
                <?php k_book_card($row); ?>
            <?php endwhile; ?>
        </div>
    </section>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
