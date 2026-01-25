mysqli_report(MYSQLI_REPORT_OFF);
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. SELF-HEALING: Check and create missing columns automatically
    $columns_needed = [
        'publisher' => 'VARCHAR(255) DEFAULT NULL',
        'pages' => 'INT DEFAULT NULL',
        'isbn' => 'VARCHAR(50) DEFAULT NULL',
        'seller_id' => 'INT DEFAULT NULL'
    ];

    foreach ($columns_needed as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM books LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE books ADD $col $def");
        }
    }

    // 2. Process Form Data
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $price = floatval($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $publisher = $conn->real_escape_string($_POST['publisher']);
    $pages = intval($_POST['pages']);
    $isbn = $conn->real_escape_string($_POST['isbn']);
    $seller_id = $_SESSION['user_id'];

    // 3. Handle Helper Image Upload
    $image = "https://placehold.co/400x600?text=" . urlencode($title); // Default placeholder

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/books/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "book_" . $seller_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = $target_file;
        }
    }

    // 4. Insert into Database
    $sql = "INSERT INTO books (title, author, description, price, image, category, publisher, pages, isbn, seller_id) 
            VALUES ('$title', '$author', '$description', $price, '$image', '$category', '$publisher', $pages, '$isbn', $seller_id)";

    if ($conn->query($sql) === TRUE) {
        $success = "Book listed successfully!";
    } else {
        $error = "Error: " . $conn->error;
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
                        <option value="Fiction">Fiction</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Science Fiction">Science Fiction</option>
                        <option value="Adventure">Adventure</option>
                        <option value="Self-Help">Self-Help</option>
                        <option value="History">History</option>
                        <option value="Children">Children</option>
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
                Additional Details</h3>

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

<?php require_once 'includes/footer.php'; ?>