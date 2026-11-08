<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// The Shop page has the glass look (from the design canvas): a body class and a stylesheet only the redesigned pages load
$body_class = 'is-glass is-shop';
$extra_css = 'assets/css/glass.css';
$meta_description = 'Browse the full KITAB catalog and filter by category, author or price.';
require_once '../includes/header.php';

// ---------- What the visitor asked for (every value is checked before it goes near the database) ----------
$has_search = is_string($_GET['search'] ?? null); // "Search results for" shows once a search was made, even an empty one
$search_text = $has_search ? $_GET['search'] : '';
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

// Sort: only these three exist, anything else means "newest"
$sorts = [
    'newest' => ['Newest', 'created_at DESC, id DESC'],
    'price_asc' => ['Price: low to high', 'price ASC, id DESC'],
    'price_desc' => ['Price: high to low', 'price DESC, id DESC'],
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

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
$result = $conn->query("SELECT * FROM books $where_sql ORDER BY " . $sorts[$sort][1]);
$books = $result->fetch_all(MYSQLI_ASSOC);

// Category types shown above the books
$cat_result = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");

// For the result count and the hints in the price boxes
[$total_books, $price_lo, $price_hi] = $conn->query("SELECT COUNT(*), FLOOR(MIN(price)), CEIL(MAX(price)) FROM books")->fetch_row();
$total_books = (int) $total_books;
$filters_active = $where_clauses !== [];

// Address of this page with some of the choices changed (null removes one); sort and price travel along
$plain = fn(float $n): string => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
$money = fn(float $n): string => 'Rs. ' . number_format($n, $n == floor($n) ? 0 : 2);
$build = function (array $change = []) use ($search_text, $author_filter, $active_category, $sort, $min_price, $max_price, $plain): string {
    $q = [
        'search' => $search_text,
        'author' => $author_filter,
        'category' => $active_category,
        'sort' => $sort !== 'newest' ? $sort : '',
        'min' => $min_price !== null ? $plain($min_price) : '',
        'max' => $max_price !== null ? $plain($max_price) : '',
    ];
    foreach ($change as $key => $value) {
        $q[$key] = $value === null ? '' : $value;
    }
    $q = array_filter($q, fn($v) => $v !== '');
    return 'shop.php' . ($q ? '?' . http_build_query($q) : '');
};

// "3 of 18 books", then what is narrowing them down
$count = count($books);
$notes = [];
if ($active_category !== '') {
    $notes[] = $active_category;
}
if ($author_filter !== '') {
    $notes[] = 'by ' . $author_filter;
}
if ($search_text !== '') {
    $notes[] = '"' . $search_text . '"';
}
if ($min_price !== null && $max_price !== null) {
    $notes[] = $money($min_price) . ' to ' . $money($max_price);
} elseif ($min_price !== null) {
    $notes[] = $money($min_price) . ' and up';
} elseif ($max_price !== null) {
    $notes[] = 'up to ' . $money($max_price);
}

// Light from the books: the first two covers on the page glow softly behind the top of it
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
    <h1 class="hg-h1 gs-title">All Books</h1>
    <p class="hg-lede">Browse our complete catalog</p>
</section>

<div class="hg-wrap gs-main">
    <nav class="gs-filter hg-panel" aria-label="Book categories">
        <ul class="k-cats hg-chips">
            <?php $all_href = $build(['category' => null, 'search' => null, 'author' => null]); ?>
            <li><a href="<?php echo htmlspecialchars($all_href); ?>" class="k-genre<?php echo $active_category === '' ? ' is-active' : ''; ?>"
                    <?php echo $active_category === '' ? 'aria-current="page"' : ''; ?>>All</a></li>
            <?php while ($cat = $cat_result->fetch_assoc()): ?>
                <?php $is_active = ($active_category === $cat['category']); ?>
                <li><a href="<?php echo htmlspecialchars($build(['category' => $cat['category'], 'search' => null, 'author' => null])); ?>"
                        class="k-genre<?php echo $is_active ? ' is-active' : ''; ?>"
                        <?php echo $is_active ? 'aria-current="page"' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></a>
                </li>
            <?php endwhile; ?>
        </ul>
    </nav>

    <?php if ($author_filter !== ''): ?>
        <p class="k-results">Books by <strong><?php echo htmlspecialchars($author_filter); ?></strong>
            &middot; <a href="shop.php">Show all books</a></p>
    <?php endif; ?>

    <?php if ($has_search): ?>
        <p class="k-results">Search results for
            "<strong><?php echo htmlspecialchars($search_text); ?></strong>"</p>
    <?php endif; ?>

    <div class="gs-controls">
        <p class="gs-count" aria-live="polite">
            <strong><?php echo $count; ?></strong><?php echo $filters_active ? ' of ' . $total_books : ''; ?>
            <?php echo ($filters_active ? $total_books : $count) === 1 ? 'book' : 'books'; ?>
            <?php if ($notes): ?><span>&middot; <?php echo htmlspecialchars(implode(' · ', $notes)); ?></span><?php endif; ?>
        </p>

        <form method="GET" action="shop.php" class="gs-tools" data-shop-tools data-validate>
            <?php foreach (['search' => $search_text, 'author' => $author_filter, 'category' => $active_category] as $name => $value): ?>
                <?php if ($value !== ''): ?>
                    <input type="hidden" name="<?php echo $name; ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <label class="gs-sort">
                <span>Sort by</span>
                <select name="sort" class="gs-select" data-autosubmit>
                    <?php foreach ($sorts as $key => [$label]): ?>
                        <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

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
                <?php if ($min_price !== null || $max_price !== null): ?>
                    <a href="<?php echo htmlspecialchars($build(['min' => null, 'max' => null])); ?>" class="gs-clear">Clear price</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($books): ?>
        <div class="hg-grid">
            <?php foreach ($books as $row): ?>
                <?php k_glass_tile($row); ?>
            <?php endforeach; ?>
        </div>
    <?php elseif ($filters_active): ?>
        <?php k_empty_state('cart', 'No books found', 'Nothing matches your search or filters. Try something different, or start again.', 'Clear Filters', 'shop.php'); ?>
    <?php else: ?>
        <?php k_empty_state('cart', 'No books yet', 'The shelves are being filled. Please check back soon.', 'Back to Home', BASE_URL . 'index.php'); ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
