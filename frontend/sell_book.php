<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/profile-flat.css', 'assets/css/sell-flat.css'];
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

const SELL_MAX_PRICE = 1000000;     // Rs.; a bigger number is almost certainly a typo
const SELL_MAX_DESCRIPTION = 2000;  // characters

k_book_columns($conn); // condition, language, format and year published (created the first time, if they are missing)
$conditions = k_book_conditions();
$languages = k_book_languages();
$formats = k_book_formats();

$error = '';   // a problem with the whole form (the book could not be saved)
$errors = [];  // problems with one field, shown under it (field name => message)

// Categories (also used to check the submitted category). The books table keeps the category's id too, so keep both.
$categories = [];
$cat_result = $conn->query("SELECT name, MIN(id) AS id FROM categories GROUP BY name ORDER BY name ASC");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[$cat['name']] = (int) $cat['id'];
    }
}
if (empty($categories)) {
    $categories = ['Fiction' => 0, 'Non-Fiction' => 0]; // fallback if the table is missing
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $field = fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    $title = $field('title');
    $author = $field('author');
    $price_text = $field('price');
    $stock_text = $field('stock');
    $category = $field('category');
    $description = $field('description');
    $publisher = $field('publisher');
    $pages_text = $field('pages');
    $isbn = $field('isbn');
    $condition = $field('condition');
    $language = $field('language');
    $format = $field('format');
    $year_text = $field('year');
    $seller_id = (int) $_SESSION['user_id'];

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $error = "Your session expired, so nothing was saved. Please try again.";
    } else {
        if ($title === '') {
            $errors['title'] = "Enter the book's title.";
        } elseif (mb_strlen($title) > 255) {
            $errors['title'] = "Use 255 characters or fewer.";
        }
        if ($author === '') {
            $errors['author'] = "Enter the author's name.";
        } elseif (mb_strlen($author) > 255) {
            $errors['author'] = "Use 255 characters or fewer.";
        }
        if ($price_text === '') {
            $errors['price'] = "Enter a price.";
        } elseif (!is_numeric($price_text) || (float) $price_text <= 0) {
            $errors['price'] = "Enter a price above zero, like 499.";
        } elseif ((float) $price_text > SELL_MAX_PRICE) {
            $errors['price'] = "Enter a price up to Rs. " . number_format(SELL_MAX_PRICE) . ".";
        }
        if (!isset($categories[$category])) {
            $errors['category'] = "Choose a category.";
        }
        if (!preg_match('/^\d{1,4}$/', $stock_text) || (int) $stock_text < 1) {
            $errors['stock'] = "Enter how many copies you have, from 1 up.";
        }
        if (mb_strlen($description) > SELL_MAX_DESCRIPTION) {
            $errors['description'] = "Use " . number_format(SELL_MAX_DESCRIPTION) . " characters or fewer.";
        }
        if (mb_strlen($publisher) > 255) {
            $errors['publisher'] = "Use 255 characters or fewer.";
        }
        if ($pages_text !== '' && (!preg_match('/^\d{1,5}$/', $pages_text) || (int) $pages_text < 1)) {
            $errors['pages'] = "Enter the number of pages as a whole number, like 320.";
        }
        if ($isbn !== '' && !preg_match('/^(\d{9}[\dXx]|\d{13})$/', preg_replace('/[\s-]/', '', $isbn))) {
            $errors['isbn'] = "Enter the ISBN as 10 or 13 digits. Hyphens are fine.";
        }
        if (!isset($conditions[$condition])) {
            $errors['condition'] = "Choose the condition of your copy.";
        }
        if ($language !== '' && !in_array($language, $languages, true)) {
            $errors['language'] = "Choose a language from the list.";
        }
        if ($format !== '' && !in_array($format, $formats, true)) {
            $errors['format'] = "Choose a format from the list.";
        }
        if ($year_text !== '' && (!preg_match('/^\d{4}$/', $year_text) || (int) $year_text < 1450 || (int) $year_text > (int) date('Y') + 1)) {
            $errors['year'] = "Enter a four-digit year, like 2019.";
        }

        $image = ''; // no cover: pages show a titled placeholder
        if (!$errors) {
            // Image upload (optional); a problem with the file is shown under the picture box
            [$uploaded, $upload_error] = k_upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'books', 'book_' . $seller_id);
            if ($upload_error !== '') {
                $errors['image'] = $upload_error;
            } elseif ($uploaded !== null) {
                $image = $uploaded;
            }
        }

        if (!$errors) {
            $price = round((float) $price_text, 2);
            $pages = $pages_text === '' ? 0 : (int) $pages_text;
            $stock = (int) $stock_text;
            $category_id = $categories[$category];
            $language_value = $language === '' ? 'English' : $language;
            $format_value = $format === '' ? null : $format;
            $year = $year_text === '' ? null : (int) $year_text;
            $stmt = $conn->prepare(
                "INSERT INTO books (title, author, description, price, image, category, category_id, publisher, pages, isbn, seller_id, stock, book_condition, `language`, `format`, published_year, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param("sssdssisisiisssi", $title, $author, $description, $price, $image, $category, $category_id, $publisher, $pages, $isbn, $seller_id, $stock, $condition, $language_value, $format_value, $year);
            if ($stmt->execute()) {
                // Back to a fresh page with a message, so refreshing it cannot list the same book twice
                $_SESSION['sell_notice'] = ['id' => (int) $conn->insert_id, 'title' => $title];
                header("Location: sell_book.php");
                exit();
            }
            k_delete_upload($image, 'books'); // the book was not saved, so its cover is not needed
            $error = "Something went wrong listing your book. Please try again.";
        }
    }
    $old = ['title' => $title, 'author' => $author, 'price' => $price_text, 'stock' => $stock_text, 'category' => $category,
        'description' => $description, 'publisher' => $publisher, 'pages' => $pages_text, 'isbn' => $isbn,
        'condition' => $condition, 'language' => $language, 'format' => $format, 'year' => $year_text];
} else {
    $old = [];
}

// The message left by a book that was just listed (shown once)
$listed = $_SESSION['sell_notice'] ?? null;
unset($_SESSION['sell_notice']);

$old_category = $old['category'] ?? '';
$page_title = 'Sell a book';
require_once '../includes/header.php';
?>

<main class="fl">
    <div class="fl-wrap pf pf--sell">
        <h1 class="fl-h1">Sell a book</h1>
        <p class="fl-lede sb-lede">List a book and it shows up in the <a href="marketplace.php">Marketplace</a> right away. Honest details help a copy sell, so tell buyers what they are getting. Already listed some? <a href="my_listings.php">See your listings</a>.</p>

        <?php if (is_array($listed)): ?>
            <div class="k-alert k-alert--success pf-notice" role="status">
                &ldquo;<?php echo htmlspecialchars($listed['title']); ?>&rdquo; is listed and now in the Marketplace.
                <a href="product.php?id=<?php echo (int) $listed['id']; ?>">View it</a> or <a href="my_listings.php">manage your listings</a>.
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="pf-form pf-form--edit fl-panel sb-form" data-validate>
            <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">

            <?php if ($error): ?>
                <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- The book -->
            <div class="pf-group">
                <h2 class="sb-h">The book</h2>
                <div class="k-fields">
                    <div class="k-field">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" class="k-input" required maxlength="255"
                            data-msg-missing="Enter the book's title."
                            value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>" <?php k_field_invalid($errors, 'title'); ?>>
                        <?php k_field_error($errors, 'title'); ?>
                    </div>
                    <div class="k-field">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" class="k-input" required maxlength="255"
                            data-msg-missing="Enter the author's name."
                            value="<?php echo htmlspecialchars($old['author'] ?? ''); ?>" <?php k_field_invalid($errors, 'author'); ?>>
                        <?php k_field_error($errors, 'author'); ?>
                    </div>
                </div>

                <div class="sb-trio">
                    <div class="k-field">
                        <label for="price">Price (Rs.)</label>
                        <input type="number" step="0.01" min="0.01" max="<?php echo SELL_MAX_PRICE; ?>" id="price" name="price" class="k-input" required
                            inputmode="decimal" placeholder="499"
                            data-msg-missing="Enter a price." data-msg-invalid="Enter a price above zero, like 499."
                            value="<?php echo htmlspecialchars($old['price'] ?? ''); ?>" <?php k_field_invalid($errors, 'price'); ?>>
                        <?php k_field_error($errors, 'price'); ?>
                        <p class="sb-help">For one copy, in rupees.</p>
                    </div>
                    <div class="k-field">
                        <label for="stock">Copies available</label>
                        <input type="number" id="stock" name="stock" class="k-input" required min="1" max="9999" step="1" inputmode="numeric"
                            data-msg-missing="Enter how many copies you have." data-msg-invalid="Enter how many copies you have, from 1 up."
                            value="<?php echo htmlspecialchars($old['stock'] ?? '1'); ?>" <?php k_field_invalid($errors, 'stock'); ?>>
                        <?php k_field_error($errors, 'stock'); ?>
                    </div>
                    <div class="k-field sb-trio__wide">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="k-input" required data-msg-missing="Choose a category."
                            <?php k_field_invalid($errors, 'category'); ?>>
                            <option value="">Select a category</option>
                            <?php foreach (array_keys($categories) as $name): ?>
                                <option value="<?php echo htmlspecialchars($name); ?>"<?php echo $old_category === $name ? ' selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php k_field_error($errors, 'category'); ?>
                    </div>
                </div>

                <div class="k-field">
                    <label for="description">Description <span class="k-field__hint">(optional)</span></label>
                    <textarea id="description" name="description" class="k-input k-textarea" rows="5" maxlength="<?php echo SELL_MAX_DESCRIPTION; ?>"
                        placeholder="What is it about, and what condition is your copy in?"
                        <?php k_field_invalid($errors, 'description'); ?>><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                    <?php k_field_error($errors, 'description'); ?>
                    <p class="sb-help">Say what the book is about and mention anything a buyer should know, such as a missing dust jacket or a name written inside.</p>
                </div>
            </div>

            <!-- Condition -->
            <fieldset class="pf-group sb-condition" <?php k_field_invalid($errors, 'condition'); ?>>
                <legend class="sb-h">Condition</legend>
                <p class="sb-help sb-help--lead">Pick the one that fits your copy best. Buyers see it on the book's page.</p>
                <div class="k-tiles sb-cond">
                    <?php foreach ($conditions as $key => [$name, $meaning]): ?>
                        <label class="k-tile sb-cond__tile">
                            <input type="radio" name="condition" value="<?php echo htmlspecialchars($key); ?>" required
                                data-msg-missing="Choose the condition of your copy."
                                <?php echo ($old['condition'] ?? '') === $key ? 'checked' : ''; ?>>
                            <span class="sb-cond__text"><strong><?php echo htmlspecialchars($name); ?></strong><small><?php echo htmlspecialchars($meaning); ?></small></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php k_field_error($errors, 'condition'); ?>
            </fieldset>

            <!-- Cover -->
            <div class="pf-group">
                <h2 class="sb-h" id="cover-label">Cover <span class="k-field__hint">(optional)</span></h2>
                <div class="sb-cover" data-upload>
                    <div class="sb-cover__preview" data-upload-preview>
                        <img alt="Cover preview">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H19v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z" />
                            <path d="M4 18.5A2.5 2.5 0 0 1 6.5 16H19" />
                        </svg>
                    </div>
                    <div class="sb-cover__side">
                        <p class="sb-cover__text">JPG, PNG, GIF or WebP, up to 2 MB. Without a cover the book shows a placeholder with its title.</p>
                        <button type="button" class="k-btn k-btn--outline k-btn--sm" data-upload-pick>Choose Image</button>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" hidden aria-labelledby="cover-label">
                        <?php k_field_error($errors, 'image'); ?>
                    </div>
                </div>
            </div>

            <!-- More details -->
            <div class="pf-group">
                <h2 class="sb-h">More details <span class="k-field__hint">(optional)</span></h2>
                <div class="k-fields k-fields--3">
                    <div class="k-field">
                        <label for="publisher">Publisher</label>
                        <input type="text" id="publisher" name="publisher" class="k-input" maxlength="255"
                            value="<?php echo htmlspecialchars($old['publisher'] ?? ''); ?>" <?php k_field_invalid($errors, 'publisher'); ?>>
                        <?php k_field_error($errors, 'publisher'); ?>
                    </div>
                    <div class="k-field">
                        <label for="pages">Pages</label>
                        <input type="number" min="1" max="99999" step="1" id="pages" name="pages" class="k-input" inputmode="numeric"
                            placeholder="320" data-msg-invalid="Enter the number of pages as a whole number, like 320."
                            value="<?php echo htmlspecialchars($old['pages'] ?? ''); ?>" <?php k_field_invalid($errors, 'pages'); ?>>
                        <?php k_field_error($errors, 'pages'); ?>
                    </div>
                    <div class="k-field">
                        <label for="isbn">ISBN</label>
                        <input type="text" maxlength="20" id="isbn" name="isbn" class="k-input" placeholder="978-0-00-000000-0"
                            pattern="[0-9Xx\- ]{10,20}" data-msg-invalid="Enter the ISBN as 10 or 13 digits. Hyphens are fine."
                            value="<?php echo htmlspecialchars($old['isbn'] ?? ''); ?>" <?php k_field_invalid($errors, 'isbn'); ?>>
                        <?php k_field_error($errors, 'isbn'); ?>
                        <p class="sb-help">On the back cover, above the barcode.</p>
                    </div>
                    <div class="k-field">
                        <label for="language">Language</label>
                        <select id="language" name="language" class="k-input" <?php k_field_invalid($errors, 'language'); ?>>
                            <?php $old_language = $old['language'] ?? 'English'; ?>
                            <?php foreach ($languages as $name): ?>
                                <option value="<?php echo htmlspecialchars($name); ?>"<?php echo $old_language === $name ? ' selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php k_field_error($errors, 'language'); ?>
                    </div>
                    <div class="k-field">
                        <label for="format">Format</label>
                        <select id="format" name="format" class="k-input" <?php k_field_invalid($errors, 'format'); ?>>
                            <option value="">Not sure</option>
                            <?php foreach ($formats as $name): ?>
                                <option value="<?php echo htmlspecialchars($name); ?>"<?php echo ($old['format'] ?? '') === $name ? ' selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php k_field_error($errors, 'format'); ?>
                    </div>
                    <div class="k-field">
                        <label for="year">Year published</label>
                        <input type="number" min="1450" max="<?php echo (int) date('Y') + 1; ?>" step="1" id="year" name="year" class="k-input" inputmode="numeric"
                            placeholder="<?php echo (int) date('Y') - 6; ?>" data-msg-invalid="Enter a four-digit year, like 2019."
                            value="<?php echo htmlspecialchars($old['year'] ?? ''); ?>" <?php k_field_invalid($errors, 'year'); ?>>
                        <?php k_field_error($errors, 'year'); ?>
                        <p class="sb-help">On the copyright page, near the front.</p>
                    </div>
                </div>
            </div>

            <div class="pf-form__actions">
                <button type="submit" class="k-btn k-btn--primary">List Book for Sale</button>
                <a href="profile.php" class="k-btn k-btn--outline">Cancel</a>
            </div>
        </form>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
