<?php
mysqli_report(MYSQLI_REPORT_OFF); // Disable auto-exceptions
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. ENSURE COLUMNS EXIST (Self-Healing)
    $columns = [
        'favorite_genres' => 'TEXT',
        'profile_picture' => 'VARCHAR(255) DEFAULT NULL'
    ];

    foreach ($columns as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            // Column missing, try to add it
            $conn->query("ALTER TABLE users ADD $col $def");
        }
    }

    // 2. PROCESS UPLOAD
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            $conn->query("UPDATE users SET profile_picture = '$target_file' WHERE id = $user_id");
        }
    }

    // 3. PROCESS GENRES
    $genres = isset($_POST['genres']) ? implode(',', $_POST['genres']) : '';
    $genres = $conn->real_escape_string($genres);

    $sql = "UPDATE users SET favorite_genres = '$genres' WHERE id = $user_id";

    if ($conn->query($sql) === TRUE) {
        header("Location: index.php");
        exit();
    } else {
        $error_message = "Database Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalize Your Experience - KITAB</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pref-container {
            width: 100%;
            max-width: 1000px;
            background: #ffffff;
            border-radius: 24px;
            padding: 3rem 3rem 4rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
            /* Soft drop shadow */
            margin: 2rem;
        }

        .pref-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .pref-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        /* Highlight effect like in the image */
        .highlight {
            background: linear-gradient(120deg, #dbeafe 0%, #dbeafe 100%);
            background-repeat: no-repeat;
            background-size: 100% 40%;
            background-position: 0 80%;
        }

        .pref-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .avatar-upload {
            position: relative;
            max-width: 150px;
            margin: 0 auto 3rem;
            text-align: center;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #f1f5f9;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-size: cover;
            background-position: center;
            background-image: url('https://ui-avatars.com/api/?name=User&background=eff6ff&color=3b82f6');
            margin: 0 auto;
        }

        .upload-btn-wrapper {
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .btn-upload {
            border: 1px solid #cbd5e1;
            color: var(--text-dark);
            background-color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }

        .upload-btn-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .genre-grid-select {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .genre-option {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2rem 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.25rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .genre-option:hover {
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .genre-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .genre-option.selected {
            border-color: var(--accent);
            background: #eff6ff;
            /* light blue bg */
            box-shadow: 0 0 0 2px var(--accent) inset;
        }

        .icon-box {
            font-size: 2rem;
            color: #94a3b8;
            transition: color 0.2s;
        }

        .genre-option.selected .icon-box {
            color: var(--accent);
        }

        .genre-label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: block;
            margin: 2rem auto 0;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-submit:hover {
            background: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }

        .error-banner {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="pref-container">
        <div class="pref-header">
            <h2>Setup your <span class="highlight">Profile</span></h2>
            <p>Add a photo and select your favorite genres</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-banner">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="avatar-upload">
                <div class="avatar-preview" id="imagePreview"></div>
                <div class="upload-btn-wrapper">
                    <button type="button" class="btn-upload" onclick="document.getElementById('profile_pic').click()"><i
                            class="fas fa-camera"></i> Upload Photo</button>
                    <input type="file" id="profile_pic" name="profile_pic" onchange="readURL(this);">
                </div>
            </div>

            <div class="genre-grid-select">
                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="Arts">
                    <div class="icon-box"><i class="fas fa-palette"></i></div>
                    <span class="genre-label">Arts & Photography</span>
                </label>

                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="Business">
                    <div class="icon-box"><i class="fas fa-briefcase"></i></div>
                    <span class="genre-label">Business & Investing</span>
                </label>

                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="Fiction">
                    <div class="icon-box"><i class="fas fa-theater-masks"></i></div>
                    <span class="genre-label">Fiction & Literature</span>
                </label>

                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="Languages">
                    <div class="icon-box"><i class="fas fa-language"></i></div>
                    <span class="genre-label">Foreign Languages</span>
                </label>

                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="History">
                    <div class="icon-box"><i class="fas fa-landmark"></i></div>
                    <span class="genre-label">History & Social Science</span>
                </label>

                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="Kids">
                    <div class="icon-box"><i class="fas fa-child"></i></div>
                    <span class="genre-label">Kids & Teens</span>
                </label>

                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="Wellness">
                    <div class="icon-box"><i class="fas fa-running"></i></div>
                    <span class="genre-label">Lifestyle & Wellness</span>
                </label>

                <label class="genre-option">
                    <input type="checkbox" name="genres[]" value="Graphic Novels">
                    <div class="icon-box"><i class="fas fa-book-reader"></i></div>
                    <span class="genre-label">Manga & Graphic Novels</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">Continue</button>
        </form>
    </div>

    <script>
        document.querySelectorAll('.genre-option input').forEach(input => {
            input.addEventListener('change', function () {
                if (this.checked) {
                    this.parentElement.classList.add('selected');
                } else {
                    this.parentElement.classList.remove('selected');
                }
            });
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('imagePreview').style.backgroundImage = 'url(' + e.target.result + ')';
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>

</html>