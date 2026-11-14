<?php include 'includes/header.php'; ?>

<?php
require_once dirname(__DIR__) . '/includes/helpers.php';

$error = '';
$success = '';

// Categories come from the database (same list as the seller form); fall back to a basic set if that fails
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $stock_text = trim((string) ($_POST['stock'] ?? '1'));

    $image_path = '';
    if ($title === '' || $author === '') {
        $error = "Please enter a title and an author.";
    } elseif ($price <= 0) {
        $error = "Please enter a price greater than zero.";
    } elseif (!in_array($category, $categories, true)) {
        $error = "Please choose a category.";
    } elseif (!preg_match('/^\d{1,6}$/', $stock_text)) {
        $error = "Enter how many copies are in stock, as a whole number from 0 up (0 means sold out).";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please upload a book cover image.";
    } else {
        // Image Upload (checked by content, stored under uploads/books/)
        [$image_path, $error] = k_upload_image($_FILES['image'], 'books', 'book_admin');
    }

    if (empty($error)) {
        $title_sql = $conn->real_escape_string($title);
        $author_sql = $conn->real_escape_string($author);
        $category_sql = $conn->real_escape_string($category);
        $description_sql = $conn->real_escape_string($description);
        $image_sql = $conn->real_escape_string($image_path);

        $sql = "INSERT INTO books (title, author, price, category, description, image, stock) VALUES ('$title_sql', '$author_sql', $price, '$category_sql', '$description_sql', '$image_sql', " . (int) $stock_text . ")";
        if ($conn->query($sql)) {
            $success = "Book added successfully!";
        } else {
            $error = "Something went wrong saving the book. Please try again.";
        }
    }
}

// Keep what was typed when there is an error; start fresh after a successful save
$old = $success ? [] : $_POST;
$old_category = $old['category'] ?? '';
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Add New Book</h1>
        <a href="<?php echo BASE_URL; ?>admin/books.php" class="k-btn k-btn--outline">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5M11 6l-6 6 6 6" />
            </svg>
            Back to List
        </a>
    </div>

    <div class="k-formstack">
        <?php if ($error): ?>
            <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="k-alert k-alert--success" role="status">
                <?php echo htmlspecialchars($success); ?>
                <a href="<?php echo BASE_URL; ?>admin/books.php">View Books</a>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="k-card k-formcard">
            <div class="k-fields">
                <div class="k-field">
                    <label for="title">Book Title</label>
                    <input type="text" id="title" name="title" required class="k-input" placeholder="Enter book title"
                        value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>">
                </div>
                <div class="k-field">
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" required class="k-input" placeholder="Author name"
                        value="<?php echo htmlspecialchars($old['author'] ?? ''); ?>">
                </div>
            </div>

            <div class="k-fields">
                <div class="k-field">
                    <label for="price">Price (Rs.)</label>
                    <input type="number" id="price" name="price" required min="0.01" step="0.01" class="k-input"
                        placeholder="0.00" value="<?php echo htmlspecialchars($old['price'] ?? ''); ?>">
                </div>
                <div class="k-field">
                    <label for="category">Category</label>
                    <select id="category" name="category" required class="k-input">
                        <?php foreach ($categories as $name): ?>
                            <option value="<?php echo htmlspecialchars($name); ?>"
                                <?php echo $old_category === $name ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="k-field">
                <label for="stock">Copies in stock</label>
                <input type="number" id="stock" name="stock" required min="0" max="999999" step="1" class="k-input"
                    value="<?php echo htmlspecialchars($old['stock'] ?? '1'); ?>">
                <p class="k-field__hint">Checkout takes copies out of this. 0 means sold out.</p>
            </div>

            <div class="k-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" class="k-input k-textarea"
                    placeholder="Book summary..."><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
            </div>

            <div class="k-field">
                <span class="k-field__label" id="cover-label">Cover Image</span>
                <div class="k-dropzone" data-upload>
                    <div class="k-dropzone__preview" data-upload-preview>
                        <img alt="Cover preview">
                    </div>
                    <p class="k-dropzone__text">JPG, PNG, GIF or WebP, up to 2 MB.</p>
                    <button type="button" class="k-btn k-btn--outline k-btn--sm" data-upload-pick>Choose Image</button>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" hidden
                        aria-labelledby="cover-label">
                </div>
            </div>

            <button type="submit" class="k-btn k-btn--primary k-btn--block">Save Book</button>
        </form>
    </div>
</main>

<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>

</html>
