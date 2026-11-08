<?php
// Shared markup helpers for the redesigned pages. Needs BASE_URL (config/db.php).

// The delivery address of an order. Older orders kept it in shipping_address instead of address.
function k_order_address(array $order): string
{
    $address = trim((string) ($order['address'] ?? ''));
    return $address !== '' ? $address : trim((string) ($order['shipping_address'] ?? ''));
}

// Readable payment method (cash_on_delivery -> Cash on Delivery); empty means the default
function k_payment_label(?string $method): string
{
    $method = (string) $method;
    if ($method === '' || $method === 'cash_on_delivery') {
        return 'Cash on Delivery';
    }
    return ucwords(str_replace('_', ' ', $method));
}

// Plain-text email through PHP's mail(). Returns false when the server cannot send mail (as on a plain XAMPP).
// $reply_to, when given, is where a reply goes (used for contact messages, so replying answers the visitor).
function k_send_mail(string $to, string $subject, string $body, ?string $reply_to = null): bool
{
    $one_line = fn(string $text): string => str_replace(["\r", "\n"], '', $text);
    if (preg_match('/[^\x20-\x7E]/', $subject)) {
        $subject = '=?UTF-8?B?' . base64_encode($one_line($subject)) . '?='; // names and accents in a subject
    }
    $headers = "From: " . MAIL_FROM . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
    if ($reply_to !== null && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $headers .= "\r\nReply-To: " . $one_line($reply_to);
    }
    return @mail($one_line($to), $one_line($subject), $body, $headers);
}

// One-time token for forms that change data (needs an active session). Pair with k_csrf_valid() on the POST side.
function k_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function k_csrf_valid($token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// Remove a cover that was uploaded to uploads/books/. Anything else (the bundled assets/ covers, outside paths) is left alone.
function k_delete_upload(?string $path): void
{
    $path = (string) $path;
    $name = basename($path);
    if ($name === '' || $path !== 'uploads/books/' . $name) {
        return;
    }
    $file = dirname(__DIR__) . '/uploads/books/' . $name;
    if (is_file($file)) {
        @unlink($file);
    }
}

// Validate and store an uploaded image under uploads/<subdir>/.
// Returns [relative path or null, error message or ''].
// Only real JPG/PNG/GIF/WebP files are accepted (checked by content, not by name); the saved extension
// comes from the detected type, so a script can never be stored under an executable extension.
function k_upload_image(array $file, string $subdir, string $prefix, int $max_bytes = 2097152): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) == UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }

    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    if ($file['error'] == UPLOAD_ERR_INI_SIZE || $file['error'] == UPLOAD_ERR_FORM_SIZE || $file['size'] > $max_bytes) {
        return [null, 'That image is too large. Please choose one under ' . round($max_bytes / 1048576) . ' MB.'];
    }

    $info = $file['error'] == UPLOAD_ERR_OK ? @getimagesize($file['tmp_name']) : false;
    if (!$info || !isset($types[$info[2]])) {
        return [null, 'Please upload a JPG, PNG, GIF or WebP image.'];
    }

    $dir = dirname(__DIR__) . '/uploads/' . $subdir . '/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return [null, 'Sorry, the image could not be saved. Please try again.'];
    }

    return ['uploads/' . $subdir . '/' . $name, ''];
}

// Cover image, or a titled placeholder when a book has none.
function k_cover(array $b, string $class = 'k-cover', bool $lazy = true): void
{
    $img = trim((string) ($b['image'] ?? ''));
    if ($img === '') {
        echo '<div class="' . $class . ' k-cover--empty">' . htmlspecialchars($b['title']) . '</div>';
        return;
    }
    $src = strpos($img, 'http') === 0 ? $img : BASE_URL . $img;
    echo '<img class="' . $class . '" src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($b['title']) . '"'
        . ($lazy ? ' loading="lazy"' : '') . '>';
}

// Decorative covers beside the login/register forms. $offset chooses which books.
function k_auth_art(mysqli $conn, int $offset = 0, bool $flip = false): void
{
    $rows = $conn->query("SELECT id, title, image FROM books WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC LIMIT 4 OFFSET " . $offset);
    $books = $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];
    if (!$books) {
        return;
    }
    ?>
    <div class="k-auth-art<?php echo $flip ? ' k-auth-art--flip' : ''; ?>" aria-hidden="true">
        <div class="k-auth-art__cols">
            <?php foreach (array_chunk($books, 2) as $column): ?>
                <div class="k-auth-art__col">
                    <?php foreach ($column as $b) {
                        k_cover($b, 'k-cover k-auth-cover', false);
                    } ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// Ids of the books on the signed-in user's wishlist, as [id => true]. One query per request; empty for guests.
function k_wishlist_ids(): array
{
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    $ids = [];
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $conn = $GLOBALS['conn'] ?? null;
    if ($user_id > 0 && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT book_id FROM wishlist WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_NUM) as [$book_id]) {
            $ids[(int) $book_id] = true;
        }
    }
    return $ids;
}

// Heart that saves a book to the wishlist: a round icon on book cards, or a text button on the product page.
// It is a plain form (works without JavaScript); main.js upgrades it to update in place.
// Guests get a link to the login page instead.
function k_wish_button(array $b, string $variant = 'icon'): void
{
    $id = (int) $b['id'];
    $title = (string) $b['title'];
    $on = isset(k_wishlist_ids()[$id]);
    $as_button = $variant === 'button';
    $heart = '<svg width="' . ($as_button ? 18 : 20) . '" height="' . ($as_button ? 18 : 20) . '" viewBox="0 0 24 24" stroke="currentColor"'
        . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
    $button_class = $as_button ? 'k-btn k-btn--outline k-wishbtn' : 'k-wish__btn';

    if (!isset($_SESSION['user_id'])) {
        $label = 'Log in to save "' . $title . '" to your wishlist';
        $link = '<a href="' . BASE_URL . 'frontend/login.php" class="' . $button_class . '" aria-label="' . htmlspecialchars($label) . '"'
            . ($as_button ? '' : ' title="Log in to save this book"') . '>' . $heart
            . ($as_button ? '<span>Add to Wishlist</span>' : '') . '</a>';
        echo $as_button ? $link : '<span class="k-wish">' . $link . '</span>';
        return;
    }
    ?>
    <form method="POST" action="<?php echo BASE_URL; ?>frontend/wishlist.php"
        class="<?php echo $as_button ? 'k-wishrow' : 'k-wish'; ?>" data-wish data-title="<?php echo htmlspecialchars($title); ?>">
        <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
        <input type="hidden" name="book_id" value="<?php echo $id; ?>">
        <input type="hidden" name="do" value="<?php echo $on ? 'remove' : 'add'; ?>">
        <input type="hidden" name="back" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? ''); ?>">
        <?php if ($as_button): ?>
            <button type="submit" class="<?php echo $button_class . ($on ? ' is-on' : ''); ?>" data-wish-btn>
                <?php echo $heart; ?>
                <span data-wish-text data-on="Saved to Wishlist" data-off="Add to Wishlist"><?php echo $on ? 'Saved to Wishlist' : 'Add to Wishlist'; ?></span>
            </button>
        <?php else: ?>
            <button type="submit" class="<?php echo $button_class . ($on ? ' is-on' : ''); ?>" data-wish-btn
                aria-pressed="<?php echo $on ? 'true' : 'false'; ?>"
                aria-label="<?php echo htmlspecialchars('Save "' . $title . '" to wishlist'); ?>"><?php echo $heart; ?></button>
        <?php endif; ?>
    </form>
    <?php
}

function k_book_card(array $b): void
{
    $href = BASE_URL . 'frontend/product.php?id=' . (int) $b['id'];
    ?>
    <article class="k-book">
        <div class="k-book__media">
            <a class="k-book__cover" href="<?php echo $href; ?>" tabindex="-1" aria-hidden="true">
                <?php k_cover($b); ?>
            </a>
            <?php k_wish_button($b); ?>
        </div>
        <div class="k-book__info">
            <span class="k-book__cat"><?php echo htmlspecialchars($b['category']); ?></span>
            <a class="k-book__title" href="<?php echo $href; ?>"><?php echo htmlspecialchars($b['title']); ?></a>
            <span class="k-book__author">by <?php echo htmlspecialchars($b['author']); ?></span>
        </div>
        <div class="k-book__foot">
            <span class="k-book__price">Rs. <?php echo number_format((float) $b['price']); ?></span>
            <a href="<?php echo $href; ?>" class="k-btn k-btn--outline">Add to Cart</a>
        </div>
    </article>
    <?php
}
