<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// The Shop page is flat (white and light-grey surfaces, from the design canvas); only its buttons are glass.
// is-gbtn switches the glass buttons on, and these stylesheets are loaded by the redesigned pages only
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/shop-flat.css'];
$meta_description = 'Browse the full KITAB catalog and filter by category, author or price.';
require_once '../includes/header.php';

// ---------- What the visitor asked for (every value is checked before it goes near the database) ----------
$search_text = is_string($_GET['search'] ?? null) ? $_GET['search'] : '';
$author_filter = is_string($_GET['author'] ?? null) ? trim($_GET['author']) : '';   // linked from the Authors page and the product page
$active_category = is_string($_GET['category'] ?? null) ? $_GET['category'] : '';

// Price range: each end is a number or nothing. If they come the wrong way round, they are swapped.
$price_number = function ($value): ?float {
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') {
        return null;
    }
    $n = filter_var($value, FILTER_VALIDATE_FLOAT);
    return ($n === false || $n < 0 || $n > 10000000) ? null : (float) $n;
};
$min_price = $price_number($_GET['min'] ?? null);
$max_price = $price_number($_GET['max'] ?? null);
if ($min_price !== null && $min_price == 0) {
    $min_price = null; // "from 0" is the same as no minimum
}
if ($min_price !== null && $max_price !== null && $min_price > $max_price) {
    [$min_price, $max_price] = [$max_price, $min_price];
}

// "4 stars and up": the only rating filter there is (rating=4); anything else means no filter
$rating_on = ($_GET['rating'] ?? null) === '4';

// The average and number of published (approved) reviews for each book, joined to the books so they can be sorted and filtered on.
// A book with no reviews has NULL here: it never passes the rating filter and comes last in "Top rated".
$rating_join = "LEFT JOIN (SELECT book_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE status = 'approved' GROUP BY book_id) rt ON rt.book_id = books.id";
$top_rated_order = 'rt.avg_rating IS NULL, rt.avg_rating DESC, rt.review_count DESC, books.id DESC';

// Sort: only these four exist, anything else means "newest"
$sorts = [
    'newest' => ['Newest', 'books.created_at DESC, books.id DESC'],
    'rating' => ['Top rated', $top_rated_order],
    'price_asc' => ['Price: low to high', 'books.price ASC, books.id DESC'],
    'price_desc' => ['Price: high to low', 'books.price DESC, books.id DESC'],
];
$sort = (is_string($_GET['sort'] ?? null) && isset($sorts[$_GET['sort']])) ? $_GET['sort'] : 'newest';

// ---------- Build the query ----------
$where_clauses = [];
if ($search_text !== '') {
    $search = $conn->real_escape_string($search_text);
    $where_clauses[] = "(title LIKE '%$search%' OR author LIKE '%$search%')";
}
if ($author_filter !== '') {
    $author = $conn->real_escape_string($author_filter);
    $where_clauses[] = "TRIM(author) = '$author'";
}
if ($active_category !== '') {
    $category = $conn->real_escape_string($active_category);
    $where_clauses[] = "category = '$category'";
}
if ($min_price !== null) {
    $where_clauses[] = 'price >= ' . number_format($min_price, 2, '.', '');
}
if ($max_price !== null) {
    $where_clauses[] = 'price <= ' . number_format($max_price, 2, '.', '');
}
if ($rating_on) {
    $where_clauses[] = 'rt.avg_rating >= 4';
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
$result = $conn->query("SELECT books.* FROM books $rating_join $where_sql ORDER BY " . $sorts[$sort][1]);
$books = $result->fetch_all(MYSQLI_ASSOC);

// When nothing matches: the best-rated books, so the page is never a dead end
$popular = [];
if (!$books && $where_clauses) {
    $popular = $conn->query("SELECT books.* FROM books $rating_join ORDER BY $top_rated_order LIMIT 4")->fetch_all(MYSQLI_ASSOC);
}

// Category types shown above the books
$cat_result = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");

// For the result count and the hints in the price boxes
[$total_books, $price_lo, $price_hi] = $conn->query("SELECT COUNT(*), FLOOR(MIN(price)), CEIL(MAX(price)) FROM books")->fetch_row();
$total_books = (int) $total_books;
$filters_active = $where_clauses !== [];

// Address of this page with some of the choices changed (null removes one); sort and price travel along
$plain = fn(float $n): string => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
$money = fn(float $n): string => 'Rs. ' . number_format($n, $n == floor($n) ? 0 : 2);
$build = function (array $change = []) use ($search_text, $author_filter, $active_category, $sort, $min_price, $max_price, $rating_on, $plain): string {
    $q = [
        'search' => $search_text,
        'author' => $author_filter,
        'category' => $active_category,
        'sort' => $sort !== 'newest' ? $sort : '',
        'min' => $min_price !== null ? $plain($min_price) : '',
        'max' => $max_price !== null ? $plain($max_price) : '',
        'rating' => $rating_on ? '4' : '',
    ];
    foreach ($change as $key => $value) {
        $q[$key] = $value === null ? '' : $value;
    }
    $q = array_filter($q, fn($v) => $v !== '');
    return 'shop.php' . ($q ? '?' . http_build_query($q) : '');
};

// "3 of 18 books", and under it one removable tag for each thing that is narrowing them down: [words, address without it]
$count = count($books);
$price_active = $min_price !== null || $max_price !== null;
$tags = [];
if ($active_category !== '') {
    $tags[] = [$active_category, $build(['category' => null])];
}
if ($author_filter !== '') {
    $tags[] = ['by ' . $author_filter, $build(['author' => null])];
}
if ($search_text !== '') {
    $tags[] = ['"' . $search_text . '"', $build(['search' => null])];
}
if ($min_price !== null && $max_price !== null) {
    $tags[] = [$money($min_price) . ' to ' . $money($max_price), $build(['min' => null, 'max' => null])];
} elseif ($min_price !== null) {
    $tags[] = [$money($min_price) . ' and up', $build(['min' => null])];
} elseif ($max_price !== null) {
    $tags[] = ['up to ' . $money($max_price), $build(['max' => null])];
}
if ($rating_on) {
    $tags[] = ['4 stars and up', $build(['rating' => null])];
}
$clear_all = $build(['search' => null, 'author' => null, 'category' => null, 'min' => null, 'max' => null, 'rating' => null]);
?>

<main class="fl">

<section class="gs-head fl-wrap">
    <h1 class="fl-h1">All Books</h1>
    <p class="fl-sub">Browse our complete catalog</p>
</section>

<div class="fl-wrap gs-main gs-layout">
    <?php // The categories stand in a column on the left, as they used to. Below 900px the same list turns into a row of chips (shop-flat.css). ?>
    <aside class="gs-side">
    <h2 class="gs-side__title">Categories</h2>
    <nav class="gs-filter" aria-label="Book categories">
        <ul class="k-cats fl-chips gs-side__list">
            <?php $all_href = $build(['category' => null, 'search' => null, 'author' => null]); ?>
            <li><a href="<?php echo htmlspecialchars($all_href); ?>" class="k-genre gs-side__link<?php echo $active_category === '' ? ' is-active' : ''; ?>"
                    <?php echo $active_category === '' ? 'aria-current="page"' : ''; ?>><span class="gs-side__all">All Categories</span><span class="gs-side__chip-all">All</span></a></li>
            <?php while ($cat = $cat_result->fetch_assoc()): ?>
                <?php $is_active = ($active_category === $cat['category']); ?>
                <li><a href="<?php echo htmlspecialchars($build(['category' => $cat['category'], 'search' => null, 'author' => null])); ?>"
                        class="k-genre gs-side__link<?php echo $is_active ? ' is-active' : ''; ?>"
                        <?php echo $is_active ? 'aria-current="page"' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></a>
                </li>
            <?php endwhile; ?>
        </ul>
    </nav>
    </aside>

    <div class="gs-results">
    <div class="gs-controls">
        <p class="gs-count" aria-live="polite">
            <strong><?php echo $count; ?></strong><?php echo $filters_active ? ' of ' . $total_books : ''; ?>
            <?php echo ($filters_active ? $total_books : $count) === 1 ? 'book' : 'books'; ?>
        </p>

        <?php $filter_applied = $price_active || $rating_on; ?>
        <?php // On phones the rating and price filters sit behind a "Filters" button (shown only with scripts on); they are open from the start while one is applied. ?>
        <form method="GET" action="shop.php" class="gs-tools<?php echo $filter_applied ? ' is-filters-open' : ''; ?>" data-shop-tools data-validate>
            <?php foreach (['search' => $search_text, 'author' => $author_filter, 'category' => $active_category, 'rating' => $rating_on ? '4' : ''] as $name => $value): ?>
                <?php if ($value !== ''): ?>
                    <input type="hidden" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <label class="gs-sort">
                <span class="gs-sort__text" aria-hidden="true"><span class="gs-sort__lead">Sort by: </span><span class="gs-sort__short">Sort: </span><?php echo htmlspecialchars(str_replace(': ', ', ', $sorts[$sort][0])); ?></span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                <select name="sort" class="gs-select" aria-label="Sort by" data-autosubmit>
                    <?php foreach ($sorts as $key => [$label]): ?>
                        <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="button" class="k-btn k-btn--outline gs-filterbtn" data-filter-toggle aria-expanded="<?php echo $filter_applied ? 'true' : 'false'; ?>" aria-controls="gs-filters">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10" /><circle cx="16" cy="7" r="2" /><circle cx="8" cy="17" r="2" /></svg>
                <span>Filters</span>
                <?php if ($filter_applied): ?><span class="gs-filterbtn__dot" aria-hidden="true"></span><span class="k-sr">(applied)</span><?php endif; ?>
            </button>

            <div class="gs-filters" id="gs-filters">
                <?php // a link, so it works without scripts: it adds the filter, or takes it off when it is already on ?>
                <div class="gs-rate-group">
                    <span class="gs-price__label gs-rate-group__label">Rating</span>
                    <a href="<?php echo htmlspecialchars($build(['rating' => $rating_on ? null : '4'])); ?>" class="k-genre gs-rate<?php echo $rating_on ? ' is-active' : ''; ?>"
                        aria-label="<?php echo $rating_on ? 'Rated 4 stars and up: on. Select to turn it off' : 'Show only books rated 4 stars and up'; ?>"
                        <?php echo $rating_on ? 'aria-current="true"' : ''; ?>>
                        <span class="gs-rate__star" aria-hidden="true">&#9733;</span> 4 &amp; up
                    </a>
                </div>

                <div class="gs-price" role="group" aria-labelledby="gs-price-label">
                    <span id="gs-price-label" class="gs-price__label">Price (Rs.)</span>
                    <input type="number" name="min" class="k-input gs-num" min="0" step="any" inputmode="numeric"
                        placeholder="<?php echo (int) $price_lo; ?>" aria-label="Lowest price" data-msg-invalid="Enter a lowest price of 0 or more."
                        value="<?php echo $min_price !== null ? $plain($min_price) : ''; ?>">
                    <span aria-hidden="true">to</span>
                    <input type="number" name="max" class="k-input gs-num" min="0" step="any" inputmode="numeric"
                        placeholder="<?php echo (int) $price_hi; ?>" aria-label="Highest price" data-msg-invalid="Enter a highest price of 0 or more."
                        value="<?php echo $max_price !== null ? $plain($max_price) : ''; ?>">
                    <button type="submit" class="k-btn k-btn--outline k-btn--sm">Apply</button>
                    <?php if ($price_active): ?>
                        <a href="<?php echo htmlspecialchars($build(['min' => null, 'max' => null])); ?>" class="gs-clear">Clear price</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if ($tags): ?>
        <ul class="gs-tags" aria-label="Filters in use">
            <?php foreach ($tags as [$words, $without]): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($without); ?>" class="gs-tag" aria-label="Remove filter: <?php echo htmlspecialchars($words); ?>">
                        <span><?php echo htmlspecialchars($words); ?></span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"
                            aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if (count($tags) > 1): ?>
                <li><a href="<?php echo htmlspecialchars($clear_all); ?>" class="gs-clear">Clear all</a></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <?php if ($books): ?>
        <div class="fl-grid">
            <?php foreach ($books as $row): ?>
                <?php k_book_card($row, 'primary'); ?>
            <?php endforeach; ?>
        </div>
    <?php elseif ($filters_active): ?>
        <?php k_empty_state('cart', 'No books found', count($tags) > 1 ? 'Nothing matches all of the filters above. Take one off, or clear them all.' : 'Nothing matches ' . $tags[0][0] . '. Take it off to see every book, or try something else.', 'Clear Filters', $clear_all); ?>
        <?php if ($popular): ?>
            <section class="gs-popular" aria-labelledby="gs-popular-title">
                <h2 class="fl-h2" id="gs-popular-title">Popular books to try</h2>
                <div class="fl-grid">
                    <?php foreach ($popular as $row): ?>
                        <?php k_book_card($row, 'primary'); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php else: ?>
        <?php k_empty_state('cart', 'No books yet', 'The shelves are being filled. Please check back soon.', 'Back to Home', BASE_URL . 'index.php'); ?>
    <?php endif; ?>
    </div>
</div>

</main>

<?php require_once '../includes/footer.php'; ?>
