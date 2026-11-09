<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css'];
require_once '../includes/contact.php';
// Handled before the header is output: a sent message answers with a redirect back to this page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$site = require '../includes/site.php';

// Logged-in visitors get their name and email filled in
$name = '';
$email = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    [$name, $email] = array_map('strval', $stmt->get_result()->fetch_row() ?: ['', '']);
}
$topic = 'order';
$message = '';
$error = '';   // a problem with the whole form (expired session, too many messages), shown at the top of it
$errors = [];  // problems with one field, shown under it

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    $name = $field('name');
    $email = $field('email');
    $topic = $field('topic');
    $message = str_replace("\r\n", "\n", $field('message'));

    $recent = array_filter($_SESSION['contact_times'] ?? [], fn($t) => $t > time() - CONTACT_WINDOW);

    // Every field is checked, so all the problems show at once, each under its own field
    if ($name === '') {
        $errors['name'] = "Enter your name.";
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = "Use 100 characters or fewer.";
    }
    if ($email === '') {
        $errors['email'] = "Enter your email address so we can reply.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors['email'] = "Enter a valid email address, like name@example.com.";
    }
    if (!isset(CONTACT_TOPICS[$topic])) {
        $errors['topic'] = "Choose what your message is about.";
    }
    if (mb_strlen($message) < CONTACT_MIN_MESSAGE) {
        $errors['message'] = "Write a little more, at least " . CONTACT_MIN_MESSAGE . " characters (you wrote " . mb_strlen($message) . ").";
    } elseif (mb_strlen($message) > CONTACT_MAX_MESSAGE) {
        $errors['message'] = "Keep your message under " . CONTACT_MAX_MESSAGE . " characters.";
    }

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $error = "Your session expired, so nothing was sent. Please try again.";
    } elseif ($field('website') !== '') {
        // A hidden field only a robot fills in: act as if it worked, send nothing
        $_SESSION['contact_sent'] = true;
        header("Location: contact.php");
        exit();
    } elseif ($errors) {
        // the messages show under the fields
    } elseif (count($recent) >= CONTACT_PER_SESSION) {
        $error = "You have sent a few messages already. Please wait a few minutes before sending another.";
    } else {
        k_contact_table($conn);
        $hour = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE created_at > NOW() - INTERVAL 1 HOUR")->fetch_row()[0];
        if ((int) $hour >= CONTACT_PER_HOUR) {
            $error = "We are getting a lot of messages right now. Please email us directly at " . $site['email'] . ".";
        } else {
            $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, name, email, topic, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $name, $email, $topic, $message);
            $stmt->execute();

            // Tell the shop by email as well (it does not work on a computer without a mail server; the message is saved anyway)
            $body = $name . " <" . $email . "> wrote about: " . CONTACT_TOPICS[$topic] . "\r\n\r\n" . $message
                . "\r\n\r\n-- Sent from the Contact page. Reply to this email to answer.\r\n";
            if (!k_send_mail($site['email'], 'KITAB contact: ' . CONTACT_TOPICS[$topic], $body, $email)) {
                error_log('KITAB: a contact message was saved but the notification email could not be sent');
            }

            $recent[] = time();
            $_SESSION['contact_times'] = array_values($recent);
            $_SESSION['contact_sent'] = true;
            header("Location: contact.php");
            exit();
        }
    }
}

$sent = !empty($_SESSION['contact_sent']);
unset($_SESSION['contact_sent']);

$page_title = 'Contact';
require_once '../includes/header.php';
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">Contact Us</h1>
        <p>A question about an order, a return or selling a book? Write to us.</p>
    </div>
</section>

<div class="k-shop">
    <main class="k-shop__main">
        <div class="k-contact">
            <div class="k-contact__info">
                <div class="k-contact__item">
                    <h2>Email</h2>
                    <p><a href="mailto:<?php echo htmlspecialchars($site['email']); ?>"><?php echo htmlspecialchars($site['email']); ?></a></p>
                </div>
                <?php if ($site['phone'] !== ''): ?>
                    <div class="k-contact__item">
                        <h2>Phone</h2>
                        <p><?php echo htmlspecialchars($site['phone']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="k-contact__item">
                    <h2>Where we are</h2>
                    <p><?php echo htmlspecialchars($site['name']); ?><br><?php echo htmlspecialchars($site['address']); ?></p>
                </div>

                <div class="k-contact__help">
                    <h2>Before you write</h2>
                    <ul>
                        <li>About an order? Add its number. You can find it under Order History in <a href="profile.php">your account</a>.</li>
                        <li>Returning a book? Our <a href="legal.php?page=returns">Return policy</a> may answer it.</li>
                        <li>Want to sell a book? You can <a href="sell_book.php">list it yourself</a>.</li>
                    </ul>
                </div>
            </div>

            <div class="k-contact__form">
                <?php if ($sent): ?>
                    <div class="k-alert k-alert--success" role="status">Thank you, your message was sent. We will reply by email.</div>
                <?php endif; ?>

                <form method="POST" class="k-card k-formcard" data-validate>
                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                    <h2 class="k-h3">Send a message</h2>

                    <?php if ($error): ?>
                        <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="k-fields">
                        <div class="k-field">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" class="k-input" required maxlength="100"
                                autocomplete="name" data-msg-missing="Enter your name."
                                value="<?php echo htmlspecialchars($name); ?>" <?php k_field_invalid($errors, 'name'); ?>>
                            <?php k_field_error($errors, 'name'); ?>
                        </div>
                        <div class="k-field">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="k-input" required maxlength="255"
                                autocomplete="email" data-msg-missing="Enter your email address so we can reply."
                                value="<?php echo htmlspecialchars($email); ?>" <?php k_field_invalid($errors, 'email'); ?>>
                            <?php k_field_error($errors, 'email'); ?>
                        </div>
                    </div>

                    <div class="k-field">
                        <label for="topic">What is it about?</label>
                        <select id="topic" name="topic" class="k-input" required data-msg-missing="Choose what your message is about."
                            <?php k_field_invalid($errors, 'topic'); ?>>
                            <?php foreach (CONTACT_TOPICS as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $topic === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php k_field_error($errors, 'topic'); ?>
                    </div>

                    <div class="k-field">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="6" class="k-input k-textarea" required
                            minlength="<?php echo CONTACT_MIN_MESSAGE; ?>" maxlength="<?php echo CONTACT_MAX_MESSAGE; ?>"
                            data-msg-missing="Tell us how we can help." placeholder="How can we help?"
                            <?php k_field_invalid($errors, 'message'); ?>><?php echo htmlspecialchars($message); ?></textarea>
                        <?php k_field_error($errors, 'message'); ?>
                    </div>

                    <div class="k-hp" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <button type="submit" class="k-btn k-btn--primary k-btn--block">Send Message</button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
