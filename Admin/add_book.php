<?php include 'includes/header.php'; ?>

<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $price = floatval($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);

    // Image Upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = "book_" . time() . "." . $ext;
            $upload_dir = "../uploads/books/";

            // Create directory if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                // Ensure the path assumes BASE_URL context or is root-relative correctly
                // Storing as relative path from root for consistency
                $image_path = "uploads/books/" . $new_filename;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, WEBP allowed.";
        }
    } else {
        $error = "Please upload a book cover image.";
    }

    if (empty($error)) {
        $sql = "INSERT INTO books (title, author, price, category, description, image) VALUES ('$title', '$author', $price, '$category', '$description', '$image_path')";
        if ($conn->query($sql)) {
            $success = "Book added successfully!";
            // Optional: Redirect after success
            // header("Location: books.php");
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}
?>

<main class="main-content">
    <header class="admin-header">
        <h1>Add New Book</h1>
        <a href="books.php" class="btn-admin btn-back">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </header>

    <div class="admin-card" style="max-width: 800px;">
        <?php if ($error): ?>
            <div class="alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">
                <?php echo $success; ?> <a href="books.php" class="alert-link">View Books</a>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Book
                        Title</label>
                    <input type="text" name="title" required class="form-control form-input"
                        placeholder="Enter book title">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Author</label>
                    <input type="text" name="author" required class="form-control form-input" placeholder="Author name">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Price
                        (Rs.)</label>
                    <input type="number" name="price" required min="0" class="form-control form-input"
                        placeholder="0.00">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label">Category</label>
                    <select name="category" required class="form-input">
                        <option value="Fiction">Fiction</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Business">Business</option>
                        <option value="Arts & Photography">Arts & Photography</option>
                        <option value="Travel">Travel</option>
                        <option value="Nature">Nature</option>
                        <option value="Science">Science</option>
                        <option value="History">History</option>
                        <option value="Biography">Biography</option>
                        <option value="Self-Help">Self-Help</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-input" placeholder="Book summary..."></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label class="form-label">Cover
                    Image</label>
                <input type="file" name="image" required accept="image/*" class="form-input form-input-white">
            </div>

            <button type="submit" class="btn-admin btn-admin-primary btn-save">
                <i class="fas fa-save"></i> Save Book
            </button>
        </form>
    </div>
</main>

</body>

</html>