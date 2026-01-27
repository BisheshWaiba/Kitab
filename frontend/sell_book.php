<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Check if tables have necessary columns (ensure manual run first, but just in case)
// We assume fix_sell_columns.php was run.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $price = floatval($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);

    // Optional fields
    $publisher = isset($_POST['publisher']) ? $conn->real_escape_string($_POST['publisher']) : '';
    $pages = isset($_POST['pages']) ? intval($_POST['pages']) : 0;
    $isbn = isset($_POST['isbn']) ? $conn->real_escape_string($_POST['isbn']) : '';

    $seller_id = $_SESSION['user_id'];

    // Image Upload
    $image = "https://placehold.co/400x600?text=" . urlencode($title); // Default

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/books/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "book_" . $seller_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename; // Relative to this script

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Store DB path as "uploads/books/filename.ext" relative to root
            $image = "uploads/books/" . $new_filename;
        } else {
            $error = "Failed to upload image.";
        }
    }

    if (empty($error)) {
        // Insert
        // Note: IF seller_id doesn't exist in DB schema, this will fail. ensure schema is updated.
        $sql = "INSERT INTO books (title, author, description, price, image, category, publisher, pages, isbn, seller_id, created_at) 
                VALUES ('$title', '$author', '$description', $price, '$image', '$category', '$publisher', $pages, '$isbn', $seller_id, NOW())";

        if ($conn->query($sql) === TRUE) {
            $success = "Book listed successfully! It is now visible in the shop.";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}
?>

<div class="container" style="padding: 4rem 2rem; max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Sell a Book</h1>
        <a href="profile.php" class="btn btn-outline">Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div style="background:#f0fdf4; color:#16a34a; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
            <?php echo $success; ?>
            <a href="shop.php" style="text-decoration:underline;">View in Shop</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Book Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Author *</label>
                    <input type="text" name="author" class="form-control" required>
                </div>
            </div>

            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Price (NPR) *</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php
                        // Fetch categories dynamically
                        $cat_sql = "SELECT DISTINCT name FROM categories ORDER BY name ASC";
                        $cat_result = $conn->query($cat_sql);
                        if ($cat_result) {
                            while ($cat = $cat_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($cat['name']) . '">' . htmlspecialchars($cat['name']) . '</option>';
                            }
                        } else {
                            // Fallback if table issues
                            echo '<option value="Fiction">Fiction</option>';
                            echo '<option value="Non-Fiction">Non-Fiction</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="5"></textarea>
            </div>

            <div class="form-group">
                <label>Cover Image (Optional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <small style="color:var(--text-light);">Leave empty to generate a placeholder.</small>
            </div>

            <h3
                style="margin-top:2rem; margin-bottom:1rem; font-size:1.2rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
                Additional Details
            </h3>

            <div class="grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Publisher</label>
                    <input type="text" name="publisher" class="form-control">
                </div>
                <div class="form-group">
                    <label>Pages</label>
                    <input type="number" name="pages" class="form-control">
                </div>
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:1rem;">List Book for Sale</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>