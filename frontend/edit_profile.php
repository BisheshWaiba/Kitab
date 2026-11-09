<?php
mysqli_report(MYSQLI_REPORT_OFF); // a clash with another account's email is answered below, not with an exception
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/profile-flat.css'];
require_once '../includes/password_reset.php'; // RESET_MAX_PASSWORD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

const PW_TRIES = 5;      // wrong "current password" tries allowed ...
const PW_WINDOW = 600;   // ... in this many seconds (the same count the change-password page keeps)

$user_id = (int) $_SESSION['user_id'];
k_user_columns($conn);
$stmt = $conn->prepare("SELECT name, email, phone, address, city, date_of_birth, gender, profile_picture, favorite_genres, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
if (!$account) {
    header("Location: login.php");
    exit();
}

$genre_options = k_genre_options();
$saved_genres = array_values(array_intersect(array_keys($genre_options), explode(',', (string) ($account['favorite_genres'] ?? ''))));
$values = [
    'name' => trim((string) $account['name']),
    'email' => trim((string) $account['email']),
    'phone' => trim((string) ($account['phone'] ?? '')),
    'address' => trim((string) ($account['address'] ?? '')),
    'city' => trim((string) ($account['city'] ?? '')),
    'dob' => trim((string) ($account['date_of_birth'] ?? '')),
    'gender' => trim((string) ($account['gender'] ?? '')),
];
$gender_options = k_gender_options();
$old_picture = trim((string) ($account['profile_picture'] ?? ''));
$errors = [];      // problems with one field, shown under it
$error = '';       // a problem with the page as a whole
$ask_password = false;

$now = time();
$fails = array_values(array_filter((array) ($_SESSION['pw_fails'] ?? []), fn($t) => is_int($t) && $t > $now - PW_WINDOW));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = fn(string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $error = "Your session expired. Please try again.";
    } else {
        foreach (['name', 'email', 'phone', 'address', 'city', 'dob', 'gender'] as $key) {
            $values[$key] = trim($field($key));
        }
        $posted = isset($_POST['genres']) && is_array($_POST['genres']) ? $_POST['genres'] : [];
        $saved_genres = array_values(array_intersect(array_keys($genre_options), $posted)); // only the choices the page offers
        $remove_photo = $field('remove_photo') === '1';

        if ($values['name'] === '') {
            $errors['name'] = "Enter your full name.";
        } elseif (mb_strlen($values['name']) > 100) {
            $errors['name'] = "Use 100 characters or fewer.";
        }
        if ($values['email'] === '') {
            $errors['email'] = "Enter your email address.";
        } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 255) {
            $errors['email'] = "Enter a valid email address, like name@example.com.";
        }
        if ($values['phone'] !== '') {
            $digits = strlen(preg_replace('/\D/', '', $values['phone']));
            if (strlen($values['phone']) > 20 || !preg_match('/^[+0-9 ()\-]+$/', $values['phone']) || $digits < 7 || $digits > 15) {
                $errors['phone'] = "Enter a phone number with 7 to 15 digits, like +977 98XXXXXXXX.";
            }
        }
        if (mb_strlen($values['address']) > 500) {
            $errors['address'] = "Use 500 characters or fewer.";
        }
        if (mb_strlen($values['city']) > 100) {
            $errors['city'] = "Use 100 characters or fewer.";
        }
        if ($values['dob'] !== '') {
            $born = DateTime::createFromFormat('!Y-m-d', $values['dob']);
            if (!$born || $born->format('Y-m-d') !== $values['dob']) {
                $errors['dob'] = "Enter your date of birth as a real date, like 1998-03-24.";
            } elseif ($born > new DateTime('today')) {
                $errors['dob'] = "Your date of birth cannot be in the future.";
            } elseif ($born < new DateTime('1900-01-01')) {
                $errors['dob'] = "Enter a date of birth from the year 1900 onwards.";
            }
        }
        if ($values['gender'] !== '' && !isset($gender_options[$values['gender']])) {
            $errors['gender'] = "Choose one of the options.";
        }

        // A new email address changes how you sign in, so it needs the current password
        $email_changed = !isset($errors['email']) && strcasecmp($values['email'], (string) $account['email']) !== 0;
        if ($email_changed) {
            $ask_password = true;
            $current = $field('current');
            if (count($fails) >= PW_TRIES) {
                $errors['current'] = "Too many wrong tries. Please wait a few minutes and try again.";
            } elseif ($current === '') {
                $errors['current'] = "Enter your current password to change your email address.";
            } elseif (!password_verify($current, (string) $account['password'])) {
                $errors['current'] = "That is not your current password.";
                $fails[] = $now;
                $_SESSION['pw_fails'] = $fails;
            } elseif (!isset($errors['email'])) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
                $stmt->bind_param("si", $values['email'], $user_id);
                $stmt->execute();
                if ($stmt->get_result()->fetch_row()) {
                    $errors['email'] = "Another account already uses that email address.";
                }
            }
        }

        // The photo is stored last, so a form with problems never leaves a stray file behind
        $new_picture = null;
        if (!$errors) {
            [$new_picture, $photo_error] = k_upload_image($_FILES['profile_pic'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'profiles', 'user_' . $user_id);
            if ($photo_error !== '') {
                $errors['profile_pic'] = $photo_error;
            }
        }

        if (!$errors) {
            $picture = $new_picture ?? ($remove_photo ? null : ($old_picture !== '' ? $old_picture : null));
            $phone = $values['phone'] !== '' ? $values['phone'] : null;
            $address = $values['address'] !== '' ? $values['address'] : null;
            $city = $values['city'] !== '' ? $values['city'] : null;
            $dob = $values['dob'] !== '' ? $values['dob'] : null;
            $gender = $values['gender'] !== '' ? $values['gender'] : null;
            $genres = implode(',', $saved_genres);

            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ?, date_of_birth = ?, gender = ?, favorite_genres = ?, profile_picture = ? WHERE id = ?");
            $stmt->bind_param("sssssssssi", $values['name'], $values['email'], $phone, $address, $city, $dob, $gender, $genres, $picture, $user_id);
            if ($stmt->execute()) {
                if ($old_picture !== '' && $old_picture !== $picture) {
                    k_delete_upload($old_picture, 'profiles'); // the replaced or removed photo is no longer used
                }
                unset($_SESSION['pw_fails']);
                $_SESSION['user_name'] = $values['name'];
                $_SESSION['account_notice'] = "Your details were saved.";
                header("Location: profile.php");
                exit();
            }
            k_delete_upload($new_picture, 'profiles'); // the save failed, so the new photo is not needed
            if ($conn->errno === 1062) {
                $errors['email'] = "Another account already uses that email address.";
            } else {
                $error = "Something went wrong saving your details. Please try again.";
            }
        }
        $ask_password = $ask_password || isset($errors['current']);
    }
}

$picture_url = $old_picture === '' ? '' : (strpos($old_picture, 'http') === 0 ? $old_picture : BASE_URL . $old_picture);
$page_title = 'Edit profile';
require_once '../includes/header.php';
?>

<main class="fl">
    <div class="fl-wrap pf">
        <h1 class="fl-h1">Edit profile</h1>

        <form method="POST" enctype="multipart/form-data" class="pf-form pf-form--edit fl-panel" data-validate>
            <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">

            <?php if ($error): ?>
                <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Photo -->
            <div class="pf-group pf-group--photo">
                <div class="k-avatar-upload" data-upload>
                    <div class="k-avatar-upload__preview<?php echo $picture_url !== '' ? ' has-image' : ''; ?>" data-upload-preview>
                        <img class="k-avatar-upload__img" alt="Your profile photo"
                            <?php echo $picture_url !== '' ? 'src="' . htmlspecialchars($picture_url) . '"' : ''; ?>>
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 8h3l1.6-2.4h6.8L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z" />
                            <circle cx="12" cy="13" r="3.6" />
                        </svg>
                    </div>
                    <button type="button" class="k-btn k-btn--outline k-btn--sm" data-upload-pick><?php echo $picture_url !== '' ? 'Change Photo' : 'Upload Photo'; ?></button>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
                    <?php if ($picture_url !== ''): ?>
                        <label class="pf-check"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
                    <?php endif; ?>
                    <?php k_field_error($errors, 'profile_pic'); ?>
                </div>
            </div>

            <!-- Details -->
            <div class="pf-group">
                <div class="k-fields">
                    <div class="k-field">
                        <label for="name">Full name</label>
                        <input type="text" id="name" name="name" class="k-input" required autocomplete="name" maxlength="100"
                            data-msg-missing="Enter your full name."
                            value="<?php echo htmlspecialchars($values['name']); ?>" <?php k_field_invalid($errors, 'name'); ?>>
                        <?php k_field_error($errors, 'name'); ?>
                    </div>
                    <div class="k-field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" class="k-input" required autocomplete="email" maxlength="255"
                            data-msg-missing="Enter your email address."
                            value="<?php echo htmlspecialchars($values['email']); ?>" <?php k_field_invalid($errors, 'email'); ?>>
                        <?php k_field_error($errors, 'email'); ?>
                    </div>
                </div>

                <div class="k-field pf-confirm" data-confirm<?php echo $ask_password ? ' data-keep' : ' hidden'; ?>>
                    <label for="current">Current password <span class="k-field__hint">(needed to change your email address)</span></label>
                    <input type="password" id="current" name="current" class="k-input" autocomplete="current-password"
                        maxlength="<?php echo RESET_MAX_PASSWORD; ?>" data-msg-missing="Enter your current password to change your email address."
                        <?php k_field_invalid($errors, 'current'); ?>>
                    <?php k_field_error($errors, 'current'); ?>
                </div>

                <div class="k-fields">
                    <div class="k-field">
                        <label for="phone">Phone number <span class="k-field__hint">(optional)</span></label>
                        <input type="tel" id="phone" name="phone" class="k-input" autocomplete="tel" maxlength="20"
                            placeholder="+977 98XXXXXXXX" pattern="[+0-9 \(\)\-]{7,20}"
                            data-msg-invalid="Enter a phone number with 7 to 15 digits, like +977 98XXXXXXXX."
                            value="<?php echo htmlspecialchars($values['phone']); ?>" <?php k_field_invalid($errors, 'phone'); ?>>
                        <?php k_field_error($errors, 'phone'); ?>
                    </div>
                    <div class="k-field">
                        <label for="city">City <span class="k-field__hint">(optional)</span></label>
                        <input type="text" id="city" name="city" class="k-input" autocomplete="address-level2" maxlength="100"
                            placeholder="Kathmandu"
                            value="<?php echo htmlspecialchars($values['city']); ?>" <?php k_field_invalid($errors, 'city'); ?>>
                        <?php k_field_error($errors, 'city'); ?>
                    </div>
                </div>
                <div class="k-fields">
                    <div class="k-field">
                        <label for="dob">Date of birth <span class="k-field__hint">(optional)</span></label>
                        <input type="date" id="dob" name="dob" class="k-input" autocomplete="bday" min="1900-01-01" max="<?php echo date('Y-m-d'); ?>"
                            data-msg-invalid="Enter a real date of birth, not one in the future."
                            value="<?php echo htmlspecialchars($values['dob']); ?>" <?php k_field_invalid($errors, 'dob'); ?>>
                        <?php k_field_error($errors, 'dob'); ?>
                    </div>
                    <div class="k-field">
                        <label for="gender">Gender <span class="k-field__hint">(optional)</span></label>
                        <select id="gender" name="gender" class="k-input" autocomplete="sex" <?php k_field_invalid($errors, 'gender'); ?>>
                            <option value="">Not specified</option>
                            <?php foreach ($gender_options as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>"<?php echo $values['gender'] === $key ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php k_field_error($errors, 'gender'); ?>
                    </div>
                </div>
                <div class="k-field">
                    <label for="address">Address <span class="k-field__hint">(optional)</span></label>
                    <textarea id="address" name="address" class="k-input k-textarea" rows="3" maxlength="500"
                        autocomplete="street-address" placeholder="Maitidevi, Kathmandu"
                        <?php k_field_invalid($errors, 'address'); ?>><?php echo htmlspecialchars($values['address']); ?></textarea>
                    <?php k_field_error($errors, 'address'); ?>
                </div>
            </div>

            <!-- Favorite genres -->
            <fieldset class="pf-group pf-genres">
                <legend>Favorite genres</legend>
                <div class="k-tiles">
                    <?php foreach ($genre_options as $value => $label): ?>
                        <label class="k-tile">
                            <input type="checkbox" name="genres[]" value="<?php echo htmlspecialchars($value); ?>"
                                <?php echo in_array($value, $saved_genres, true) ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="pf-form__actions">
                <button type="submit" class="k-btn k-btn--primary">Save Changes</button>
                <a href="profile.php" class="k-btn k-btn--outline">Cancel</a>
            </div>
        </form>
        <noscript><style>.pf-confirm[hidden] { display: flex; }</style></noscript>
    </div>
</main>

<script>
// The current-password box shows up only when the email address is being changed
(function () {
    var email = document.getElementById('email');
    var box = document.querySelector('[data-confirm]');
    var pass = document.getElementById('current');
    if (!email || !box || !pass) { return; }
    var start = <?php echo json_encode(strtolower((string) $account['email'])); ?>;
    function sync() {
        var changed = email.value.trim().toLowerCase() !== start;
        box.hidden = !changed && !box.hasAttribute('data-keep');
        pass.required = !box.hidden;
    }
    email.addEventListener('input', sync);
    sync();
})();
</script>

<?php require_once '../includes/footer.php'; ?>
