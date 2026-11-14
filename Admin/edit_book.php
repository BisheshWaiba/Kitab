<?php
// Looked up before the header is output so a missing book can answer with a 404.
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

// Categories come from the database (same list as the add form); keep the book's own category even if it is not listed
$categories = [];
$cat_result = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat['name'];
    }
}
if (empty($categories)) {
    $categories = ['Fiction', 'Non-Fiction', 'Business', 'Arts & Photography', 'Travel', 'Nature', 'Science', 'History', 'Biography', 'Self-Help'];
}
$current_category = trim((string) ($book['category'] ?? ''));
if ($current_category !== '' && !in_array($current_category, $categories, true)) {
    $categories[] = $current_category;
}

if ($book && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $field = fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    $title = $field('title');
    $author = $field('author');
    $price_text = $field('price');
    $price = floatval($price_text);
    $category = $field('category');
    $description = $field('description');
    $stock_text = $field('stock');
    $stock = (int) $stock_text;
    $new_image = null;

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $error = "Your session expired, so nothing was saved. Please try again.";
    } elseif ($title === '' || $author === '') {
        $error = "Please enter a title and an author.";
    } elseif (mb_strlen($title) > 255 || mb_strlen($author) > 255) {
        $error = "The title and author must be 255 characters or fewer.";
    } elseif (!is_finite($price) || $price <= 0 || $price >= 100000000) {
        $error = "Please enter a price greater than zero.";
    } elseif (!in_array($category, $categories, true)) {
        $error = "Please choose a category.";
    } elseif (!preg_match('/^\d{1,6}$/', $stock_text)) {
        $error = "Enter how many copies are in stock, as a whole number from 0 up (0 means sold out).";
    } else {
        // Optional new cover (checked by content, stored under uploads/books/)
        [$new_image, $error] = k_upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'books', 'book_admin');
    }

    if ($error === '') {
        $image_path = $new_image ?? $book['image'];
        $stmt = $conn->prepare("UPDATE books SET title = ?, author = ?, price = ?, category = ?, description = ?, image = ?, stock = ? WHERE id = ?");
        $stmt->bind_param("ssdsssii", $title, $author, $price, $category, $description, $image_path, $stock, $id);
        if ($stmt->execute()) {
            if ($new_image !== null) {
                k_delete_upload($book['image']); // the replaced cover is no longer used
            }
            $book = array_merge($book, [
                'title' => $title, 'author' => $author, 'price' => $price, 'category' => $category,
                'description' => $description, 'image' => $image_path, 'stock' => $stock,
            ]);
            $success = "Book updated.";
        } else {
            if ($new_image !== null) {
                k_delete_upload($new_image); // the save failed, so the new cover is not needed either
            }
            $error = "Something went wrong saving the book. Please try again.";
        }
    }
}

if (!$book) {
    http_response_code(404);
}
include 'includes/header.php';

// Show what was typed when there is an error; otherwise the saved values
$form = ($book && $error !== '') ? [
    'title' => $title, 'author' => $author, 'price' => $price_text, 'category' => $category, 'description' => $description, 'stock' => $stock_text,
] : ($book ?: []);
$cover = trim((string) ($book['image'] ?? ''));
$cover_url = $cover === '' ? '' : (strpos($cover, 'http') === 0 ? $cover : BASE_URL . $cover);
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Edit Book</h1>
        <a href="<?php echo BASE_URL; ?>admin/books.php" class="k-btn k-btn--outline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 6l-6 6 6 6" />
            </svg>
            Back to List
        </a>
    </div>

    <?php if (!$book): ?>
        <div class="k-empty">
            <p>We couldn't find that book. It may have been deleted.</p>
            <a href="<?php echo BASE_URL; ?>admin/books.php" class="k-btn k-btn--outline">Back to Books</a>
        </div>
    <?php else: ?>
        <div class="k-formstack">
            <?php if ($error): ?>
                <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="k-alert k-alert--success" role="status">
                    <?php echo htmlspecialchars($success); ?>
                    <a href="<?php echo BASE_URL; ?>admin/books.php">Back to Books</a>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="k-card k-formcard">
                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">

                <div class="k-fields">
                    <div class="k-field">
                        <label for="title">Book Title</label>
                        <input type="text" id="title" name="title" required maxlength="255" class="k-input"
                            placeholder="Enter book title" value="<?php echo htmlspecialchars($form['title']); ?>">
                    </div>
                    <div class="k-field">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" required maxlength="255" class="k-input"
                            placeholder="Author name" value="<?php echo htmlspecialchars($form['author']); ?>">
                    </div>
                </div>

                <div class="k-fields">
                    <div class="k-field">
                        <label for="price">Price (Rs.)</label>
                        <input type="number" id="price" name="price" required min="0.01" step="0.01" class="k-input"
                            placeholder="0.00" value="<?php echo htmlspecialchars($form['price']); ?>">
                    </div>
                    <div class="k-field">
                        <label for="category">Category</label>
                        <select id="category" name="category" required class="k-input">
                            <?php if (trim((string) $form['category']) === ''): ?>
                                <option value="" selected disabled>Choose a category</option>
                            <?php endif; ?>
                            <?php foreach ($categories as $name): ?>
                                <option value="<?php echo htmlspecialchars($name); ?>"
                                    <?php echo $form['category'] === $name ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="k-field">
                    <label for="stock">Copies in stock</label>
                    <input type="number" id="stock" name="stock" required min="0" max="999999" step="1" class="k-input"
                        placeholder="0" value="<?php echo htmlspecialchars((string) ($form['stock'] ?? 0)); ?>">
                    <p class="k-field__hint">Checkout takes copies out of this. 0 means sold out.</p>
                </div>

                <div class="k-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" class="k-input k-textarea"
                        placeholder="Book summary..."><?php echo htmlspecialchars((string) $form['description']); ?></textarea>
                </div>

                <div class="k-field">
                    <span class="k-field__label" id="cover-label">Cover Image</span>
                    <div class="k-dropzone" data-upload>
                        <div class="k-dropzone__preview<?php echo $cover_url !== '' ? ' has-image' : ''; ?>" data-upload-preview>
                            <img alt="Cover preview" <?php echo $cover_url !== '' ? 'src="' . htmlspecialchars($cover_url) . '"' : ''; ?>>
                        </div>
                        <p class="k-dropzone__text">Keep the current cover, or choose a new one to replace it. JPG, PNG, GIF or WebP, up to 2 MB.</p>
                        <button type="button" class="k-btn k-btn--outline k-btn--sm" data-upload-pick>Replace Image</button>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" hidden
                            aria-labelledby="cover-label">
                    </div>
                </div>

                <button type="submit" class="k-btn k-btn--primary k-btn--block">Save Changes</button>
            </form>
        </div>
    <?php endif; ?>
</main>


</body>

</html>
