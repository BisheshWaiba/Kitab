<?php
mysqli_report(MYSQLI_REPORT_OFF); // Disable auto-exceptions
require_once '../config/db.php';
require_once '../includes/helpers.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$error_message = '';   // a problem with saving (shown at the top)
$errors = [];          // a problem with the photo, shown under it

// value stored in the database => label shown on the page
$genre_options = [
    'Arts' => 'Arts & Photography',
    'Business' => 'Business & Investing',
    'Fiction' => 'Fiction & Literature',
    'Languages' => 'Foreign Languages',
    'History' => 'History & Social Science',
    'Kids' => 'Kids & Teens',
    'Wellness' => 'Lifestyle & Wellness',
    'Graphic Novels' => 'Manga & Graphic Novels',
];

// Current settings, so this page also works as "Settings" without wiping what is already saved
$current = $conn->query("SELECT profile_picture, favorite_genres FROM users WHERE id = $user_id");
$current = $current ? $current->fetch_assoc() : null;
$saved_genres = $current && !empty($current['favorite_genres']) ? explode(',', $current['favorite_genres']) : [];
$saved_picture = $current['profile_picture'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Genres: only accept the options this page offers
    $posted_genres = isset($_POST['genres']) && is_array($_POST['genres']) ? $_POST['genres'] : [];
    $genres_list = array_values(array_intersect(array_keys($genre_options), $posted_genres));
    $saved_genres = $genres_list; // keep the choices on screen if something below fails

    // 1. VALIDATE + SAVE UPLOAD (before anything else is saved)
    [$new_picture, $photo_error] = k_upload_image($_FILES['profile_pic'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'profiles', 'user_' . $user_id);
    if ($photo_error !== '') {
        $errors['profile_pic'] = $photo_error;
    }

    if (!$errors) {
        // 3. ENSURE COLUMNS EXIST (Self-Healing)
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

        if ($new_picture !== null) {
            $conn->query("UPDATE users SET profile_picture = '" . $conn->real_escape_string($new_picture) . "' WHERE id = $user_id");
            $saved_picture = $new_picture;
        }

        // 4. PROCESS GENRES
        $genres = $conn->real_escape_string(implode(',', $genres_list));
        $sql = "UPDATE users SET favorite_genres = '$genres' WHERE id = $user_id";

        if ($conn->query($sql) === TRUE) {
            header("Location: ../index.php");
            exit();
        } else {
            $error_message = 'Something went wrong saving your profile. Please try again.';
        }
    }
}

$picture_url = '';
if ($saved_picture !== '') {
    $picture_url = strpos($saved_picture, 'http') === 0 ? $saved_picture : BASE_URL . $saved_picture;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/../includes/theme.php'; ?>
    <title>Personalize Your Experience - KITAB</title>
<?php require __DIR__ . '/../includes/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/kitab.css">
</head>

<body>

    <div class="k-setup">
        <a href="<?php echo BASE_URL; ?>index.php" class="k-logo">KITAB<span>.</span></a>

        <form method="POST" enctype="multipart/form-data" class="k-card k-setup__card">
            <div class="k-setup__head">
                <h1 class="k-h1">Setup your <em>Profile</em></h1>
                <p>Add a photo and select your favorite genres</p>
            </div>

            <?php if ($error_message): ?>
                <div class="k-alert k-alert--error k-setup__alert" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="k-avatar-upload" data-upload>
                <div class="k-avatar-upload__preview<?php echo $picture_url !== '' ? ' has-image' : ''; ?>"
                    data-upload-preview>
                    <img class="k-avatar-upload__img" alt="Your profile photo"
                        <?php echo $picture_url !== '' ? 'src="' . htmlspecialchars($picture_url) . '"' : ''; ?>>
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 8h3l1.6-2.4h6.8L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z" />
                        <circle cx="12" cy="13" r="3.6" />
                    </svg>
                </div>
                <button type="button" class="k-btn k-btn--outline k-btn--sm" data-upload-pick>Upload Photo</button>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/jpeg,image/png,image/gif,image/webp"
                    hidden>
                <?php k_field_error($errors, 'profile_pic'); ?>
            </div>

            <div class="k-tiles">
                <?php foreach ($genre_options as $value => $label): ?>
                    <label class="k-tile">
                        <input type="checkbox" name="genres[]" value="<?php echo htmlspecialchars($value); ?>"
                            <?php echo in_array($value, $saved_genres, true) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="k-btn k-btn--primary k-btn--block">Continue</button>
        </form>
    </div>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>

</html>
