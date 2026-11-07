<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Check if tables have necessary columns (ensure manual run first, but just in case)
// We assume fix_sell_columns.php was run.

// Categories (also used to check the submitted category)
$categories = [];
$cat_result = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat['name'];
    }
}
if (empty($categories)) {
    // Fallback if table issues
    $categories = ['Fiction', 'Non-Fiction'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');

    // Optional fields
    $publisher = trim($_POST['publisher'] ?? '');
    $pages = max(0, intval($_POST['pages'] ?? 0));
    $isbn = trim($_POST['isbn'] ?? '');

    $seller_id = (int) $_SESSION['user_id'];
    $image = ''; // no cover: pages show a titled placeholder

    if ($title === '' || $author === '') {
        $error = "Please enter a title and an author.";
    } elseif ($price <= 0) {
        $error = "Please enter a price greater than zero.";
    } elseif (!in_array($category, $categories, true)) {
        $error = "Please choose a category.";
    } else {
        // Image Upload (optional)
        [$uploaded, $error] = k_upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'books', 'book_' . $seller_id);
        if ($uploaded !== null) {
            $image = $uploaded;
        }
    }

    if (empty($error)) {
        $title_sql = $conn->real_escape_string($title);
        $author_sql = $conn->real_escape_string($author);
        $category_sql = $conn->real_escape_string($category);
        $description_sql = $conn->real_escape_string($description);
        $publisher_sql = $conn->real_escape_string($publisher);
        $isbn_sql = $conn->real_escape_string($isbn);
        $image_sql = $conn->real_escape_string($image);

        // Insert
        // Note: IF seller_id doesn't exist in DB schema, this will fail. ensure schema is updated.
        $sql = "INSERT INTO books (title, author, description, price, image, category, publisher, pages, isbn, seller_id, created_at)
                VALUES ('$title_sql', '$author_sql', '$description_sql', $price, '$image_sql', '$category_sql', '$publisher_sql', $pages, '$isbn_sql', $seller_id, NOW())";

        if ($conn->query($sql) === TRUE) {
            $success = "Book listed successfully! It is now visible in the shop.";
        } else {
            $error = "Something went wrong listing your book. Please try again.";
        }
    }
}

// Keep what was typed when there is an error; start fresh after a successful listing
$old = $success ? [] : $_POST;
$old_category = $old['category'] ?? '';
?>

<div class="k-page k-page--stack k-page--narrow">
    <div class="k-pagebar">
        <h1 class="k-h1">Sell a Book</h1>
        <a href="profile.php" class="k-btn k-btn--outline">Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="k-alert k-alert--success" role="status">
            <?php echo htmlspecialchars($success); ?>
            <a href="shop.php">View in Shop</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="k-card k-formcard">
        <div class="k-fields">
            <div class="k-field">
                <label for="title">Book Title *</label>
                <input type="text" id="title" name="title" class="k-input" required
                    value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>">
            </div>
            <div class="k-field">
                <label for="author">Author *</label>
                <input type="text" id="author" name="author" class="k-input" required
                    value="<?php echo htmlspecialchars($old['author'] ?? ''); ?>">
            </div>
        </div>

        <div class="k-fields">
            <div class="k-field">
                <label for="price">Price (NPR) *</label>
                <input type="number" step="0.01" min="0.01" id="price" name="price" class="k-input" required
                    value="<?php echo htmlspecialchars($old['price'] ?? ''); ?>">
            </div>
            <div class="k-field">
                <label for="category">Category *</label>
                <select id="category" name="category" class="k-input" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $name): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>"
                            <?php echo $old_category === $name ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="k-field">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="k-input k-textarea"
                rows="5"><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
        </div>

        <div class="k-field">
            <span class="k-field__label" id="cover-label">Cover Image (Optional)</span>
            <div class="k-dropzone" data-upload>
                <div class="k-dropzone__preview" data-upload-preview>
                    <img alt="Cover preview">
                </div>
                <p class="k-dropzone__text">JPG, PNG, GIF or WebP, up to 2 MB. Leave empty to use a placeholder.</p>
                <button type="button" class="k-btn k-btn--outline k-btn--sm" data-upload-pick>Choose Image</button>
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" hidden
                    aria-labelledby="cover-label">
            </div>
        </div>

        <h2 class="k-formcard__sub">Additional Details</h2>

        <div class="k-fields k-fields--3">
            <div class="k-field">
                <label for="publisher">Publisher</label>
                <input type="text" id="publisher" name="publisher" class="k-input"
                    value="<?php echo htmlspecialchars($old['publisher'] ?? ''); ?>">
            </div>
            <div class="k-field">
                <label for="pages">Pages</label>
                <input type="number" min="0" id="pages" name="pages" class="k-input"
                    value="<?php echo htmlspecialchars($old['pages'] ?? ''); ?>">
            </div>
            <div class="k-field">
                <label for="isbn">ISBN</label>
                <input type="text" maxlength="20" id="isbn" name="isbn" class="k-input"
                    value="<?php echo htmlspecialchars($old['isbn'] ?? ''); ?>">
            </div>
        </div>

        <button type="submit" class="k-btn k-btn--primary k-btn--block">List Book for Sale</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
