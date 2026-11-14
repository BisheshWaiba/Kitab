<?php
// Shared markup helpers for the redesigned pages. Needs BASE_URL (config/db.php).

// The delivery address of an order. Older orders kept it in shipping_address instead of address.
function k_order_address(array $order): string
{
    $address = trim((string) ($order['address'] ?? ''));
    return $address !== '' ? $address : trim((string) ($order['shipping_address'] ?? ''));
}

// The favourite genres a customer can pick (value stored in the database => label shown on the page).
// Used by the Preferences page, where they are chosen, and the Profile page, where they are shown.
function k_genre_options(): array
{
    return [
        'Arts' => 'Arts & Photography',
        'Business' => 'Business & Investing',
        'Fiction' => 'Fiction & Literature',
        'Languages' => 'Foreign Languages',
        'History' => 'History & Social Science',
        'Kids' => 'Kids & Teens',
        'Wellness' => 'Lifestyle & Wellness',
        'Graphic Novels' => 'Manga & Graphic Novels',
    ];
}

// The gender choices on the Edit profile page (value stored => label shown). Leaving it empty means "not added".
function k_gender_options(): array
{
    return ['female' => 'Female', 'male' => 'Male', 'other' => 'Other'];
}

// The optional account details added later (date of birth, gender, city). Created on first use, so an existing database
// needs no manual step; database/update_schema_profile_details.php does the same thing by hand.
function k_user_columns(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $columns = [
        'date_of_birth' => 'DATE NULL DEFAULT NULL',
        'gender' => 'VARCHAR(20) NULL DEFAULT NULL',
        'city' => 'VARCHAR(100) NULL DEFAULT NULL',
    ];
    try {
        foreach ($columns as $name => $definition) {
            $found = $conn->query("SHOW COLUMNS FROM users LIKE '$name'");
            if ($found && $found->num_rows === 0) {
                $conn->query("ALTER TABLE users ADD $name $definition");
            }
        }
    } catch (Throwable $e) {
        error_log('KITAB: could not add the account detail columns: ' . $e->getMessage());
    }
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

// Where uploaded pictures are kept: 'files' (the uploads/ folder, as on XAMPP) or 'db' (the database, for an online host
// whose disk is thrown away between visits; the Docker image sets UPLOAD_STORE=db). Either way a picture keeps the same
// address, uploads/<subdir>/<name>; with 'db' the page serve_upload.php hands it out (see .htaccess and router.php).
function k_upload_store(): string
{
    return getenv('UPLOAD_STORE') === 'db' ? 'db' : 'files';
}

// The table that holds the pictures when they are kept in the database (created the first time it is needed)
function k_upload_table(): mysqli
{
    static $ready = false;
    $conn = $GLOBALS['conn'] ?? kitab_db_connect();
    if (!$ready) {
        $ready = true;
        $conn->query("CREATE TABLE IF NOT EXISTS kitab_uploads (
            path VARCHAR(255) NOT NULL PRIMARY KEY,
            mime VARCHAR(50) NOT NULL,
            data MEDIUMBLOB NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    return $conn;
}

// Remove a picture that was uploaded to uploads/<subdir>/ (book covers in 'books', profile photos in 'profiles').
// Anything else (the bundled assets/ covers, outside paths) is left alone.
function k_delete_upload(?string $path, string $subdir = 'books'): void
{
    $path = (string) $path;
    $name = basename($path);
    if ($name === '' || $path !== 'uploads/' . $subdir . '/' . $name) {
        return;
    }
    $file = dirname(__DIR__) . '/uploads/' . $subdir . '/' . $name;
    if (is_file($file)) {
        @unlink($file);
    }
    if (k_upload_store() === 'db') {
        try {
            $stmt = k_upload_table()->prepare("DELETE FROM kitab_uploads WHERE path = ?");
            $stmt->bind_param("s", $path);
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('KITAB: could not delete an uploaded picture: ' . $e->getMessage());
        }
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

    $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $types[$info[2]];

    if (k_upload_store() === 'db') {
        $path = 'uploads/' . $subdir . '/' . $name;
        $mime = image_type_to_mime_type($info[2]);
        $bytes = is_uploaded_file($file['tmp_name']) ? file_get_contents($file['tmp_name']) : false;
        if ($bytes === false) {
            return [null, 'Sorry, the image could not be saved. Please try again.'];
        }
        try {
            $conn = k_upload_table();
            // A picture bigger than the database accepts in one go would make it drop the connection, so check first
            $limit = (int) ($conn->query("SELECT @@max_allowed_packet")->fetch_row()[0] ?? 0);
            if ($limit > 0 && strlen($bytes) + 4096 > $limit) {
                error_log('KITAB: a picture of ' . strlen($bytes) . ' bytes does not fit max_allowed_packet (' . $limit . ')');
                return [null, 'Sorry, the image could not be saved. Please try a smaller one.'];
            }
            $stmt = $conn->prepare("INSERT INTO kitab_uploads (path, mime, data) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $path, $mime, $bytes);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
        } catch (Throwable $e) {
            error_log('KITAB: could not store an uploaded picture: ' . $e->getMessage());
            return [null, 'Sorry, the image could not be saved. Please try again.'];
        }
        return [$path, ''];
    }

    $dir = dirname(__DIR__) . '/uploads/' . $subdir . '/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
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

// Book tile for the glass Home page: a frosted card tinted by the book's own cover (styles: assets/css/home-glass.css)
function k_glass_tile(array $b): void
{
    $id = (int) $b['id'];
    $href = BASE_URL . 'frontend/product.php?id=' . $id;
    $img = trim((string) ($b['image'] ?? ''));
    $src = $img === '' ? '' : (strpos($img, 'http') === 0 ? $img : BASE_URL . $img);
    $category = trim((string) ($b['category'] ?? ''));
    ?>
    <article class="hg-tile">
        <?php if ($src !== ''): ?>
            <img class="hg-tile__bg" src="<?php echo htmlspecialchars($src); ?>" alt="" loading="lazy">
        <?php endif; ?>
        <span class="hg-tile__frost"></span>
        <i class="hg-shine"></i>
        <a class="hg-tile__stage" href="<?php echo $href; ?>" tabindex="-1" aria-hidden="true">
            <?php k_cover($b, 'hg-tile__cover'); ?>
        </a>
        <?php if ($category !== ''): ?>
            <span class="hg-tile__tag"><?php echo htmlspecialchars($category); ?></span>
        <?php endif; ?>
        <?php k_wish_button($b); ?>
        <div class="hg-tile__meta">
            <a class="hg-tile__title" href="<?php echo $href; ?>"><?php echo htmlspecialchars($b['title']); ?></a>
            <span class="hg-tile__author">by <?php echo htmlspecialchars($b['author']); ?></span>
            <div class="hg-tile__foot">
                <span class="hg-tile__price">Rs. <?php echo number_format((float) $b['price']); ?></span>
                <a href="<?php echo $href; ?>" class="k-btn k-btn--primary k-btn--sm">Add to Cart</a>
            </div>
        </div>
    </article>
    <?php
}

// The one empty state, used wherever a page or a list has nothing to show: a round icon (a heart for the wishlist, a cart
// for everything else), a short friendly headline, one sentence of help and a single button. $level is the heading level
// (2 on a page, 3 inside a section). All text is escaped here. Styles: .k-empty in assets/css/kitab.css.
function k_empty_state(string $icon, string $title, string $hint, string $label, string $href, int $level = 2): void
{
    $shapes = [
        'cart' => '<path d="M3 4h2.2l2.1 10.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.2" /><circle cx="9.5" cy="19.5" r="1.4" /><circle cx="17" cy="19.5" r="1.4" />',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z" />',
    ];
    $tag = 'h' . max(2, min(6, $level));
    ?>
    <div class="k-empty">
        <span class="k-empty__badge" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><?php echo $shapes[$icon] ?? $shapes['cart']; ?></svg>
        </span>
        <<?php echo $tag; ?> class="k-empty__title"><?php echo htmlspecialchars($title); ?></<?php echo $tag; ?>>
        <p><?php echo htmlspecialchars($hint); ?></p>
        <a href="<?php echo htmlspecialchars($href); ?>" class="k-btn k-btn--primary"><?php echo htmlspecialchars($label); ?></a>
    </div>
    <?php
}

// A full address (https://...) for a path on this site. Uses the public address from config/db.php (SITE_URL) once the
// site is online, and the address the page was asked at until then.
function k_site_url(string $path = ''): string
{
    $base = SITE_URL !== '' ? rtrim(SITE_URL, '/') . '/' : BASE_URL;
    return $base . ltrim($path, '/');
}

// Form errors that belong to one field. $errors is ['field' => 'message']; a field's id is its name.
// k_field_error() prints the message right under the field, k_field_invalid() goes inside the input's tag.
// assets/js/main.js uses the same markup for the messages it shows before the form is sent.
function k_field_error(array $errors, string $field): void
{
    if (isset($errors[$field])) {
        echo '<p class="k-field__error" id="' . htmlspecialchars($field) . '-error">' . htmlspecialchars($errors[$field]) . '</p>';
    }
}

function k_field_invalid(array $errors, string $field): void
{
    if (isset($errors[$field])) {
        echo ' aria-invalid="true" aria-describedby="' . htmlspecialchars($field) . '-error"';
    }
}

// Thin "Cart > Details > Done" progress line shown on the Cart and Checkout pages: $current is 'cart', 'details' or 'done'.
// Steps before the current one are finished (and "Cart" links back while the visitor is on Details); styles: assets/css/glass.css
function k_checkout_steps(string $current): void
{
    $steps = ['cart' => 'Cart', 'details' => 'Details', 'done' => 'Done'];
    $at = array_search($current, array_keys($steps), true);
    $at = $at === false ? 0 : $at;
    $last = count($steps) - 1;
    $check = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>';
    ?>
    <nav class="gk-steps" aria-label="Checkout progress">
        <ol>
            <?php $i = 0; foreach ($steps as $key => $label): ?>
                <?php
                $is_current = $i === $at;
                $is_done = $i < $at || ($is_current && $i === $last); // arriving at the last step means everything is finished
                $classes = trim(($is_done ? 'is-done ' : '') . ($is_current ? 'is-current' : ''));
                $link = $key === 'cart' && $current === 'details';
                $dot = '<span class="gk-steps__dot" aria-hidden="true">' . ($is_done ? $check : $i + 1) . '</span>';
                ?>
                <li<?php echo $classes !== '' ? ' class="' . $classes . '"' : ''; ?><?php echo $is_current ? ' aria-current="step"' : ''; ?>>
                    <?php if ($link): ?><a href="cart.php" class="gk-steps__item"><?php else: ?><span class="gk-steps__item"><?php endif; ?>
                        <?php echo $dot; ?>
                        <span class="gk-steps__label"><?php echo $label; ?></span>
                        <?php if ($is_done && !$is_current): ?><span class="k-sr">(completed)</span><?php endif; ?>
                    <?php echo $link ? '</a>' : '</span>'; ?>
                </li>
                <?php $i++; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

// Published review count and average for every book that has any: [book_id => [count, average]]. One query per page, so a
// grid of cards does not ask the database once per card. Same rule as k_review_summary(): only 'approved' reviews count.
function k_card_ratings(): array
{
    static $ratings = null;
    if ($ratings !== null) {
        return $ratings;
    }
    $ratings = [];
    $conn = $GLOBALS['conn'] ?? null;
    if ($conn instanceof mysqli) {
        try {
            $rows = $conn->query("SELECT book_id, COUNT(*) AS n, AVG(rating) AS avg_rating FROM reviews WHERE status = 'approved' GROUP BY book_id");
            foreach ($rows ? $rows->fetch_all(MYSQLI_ASSOC) : [] as $r) {
                $ratings[(int) $r['book_id']] = [(int) $r['n'], (float) $r['avg_rating']];
            }
        } catch (Throwable $e) {
            error_log('KITAB: could not read the book ratings: ' . $e->getMessage());
        }
    }
    return $ratings;
}

// What a book's rating says in words, for screen readers: "5 out of 5 stars, 1 review" or "No reviews yet"
function k_rating_say(int $book_id): string
{
    [$count, $avg] = k_card_ratings()[$book_id] ?? [0, 0.0];
    return $count > 0
        ? rtrim(rtrim(number_format($avg, 1), '0'), '.') . ' out of 5 stars, ' . $count . ($count === 1 ? ' review' : ' reviews')
        : 'No reviews yet';
}

// The small star row shown under a book's cover on cards and in the Best Sellers row: five stars filled up to the
// average of the published reviews (grey while a book has none), then the number of reviews in brackets.
function k_rating_row(int $book_id): string
{
    [$count, $avg] = k_card_ratings()[$book_id] ?? [0, 0.0];
    $pct = round($avg / 5 * 100, 1);
    return '<span class="k-book__rating">'
        . '<span class="k-stars" style="--pct:' . $pct . '%" role="img" aria-label="' . k_rating_say($book_id) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</span>'
        . ($count > 0 ? '<span class="k-book__rating-n" aria-hidden="true">(' . $count . ')</span>' : '')
        . '</span>';
}

// A book card: a tall pale stage with the cover on it, then the star rating, the title and the price. The heart (wishlist)
// and Add to Cart sit on the stage and appear on hover or keyboard focus (always shown on touch screens, see flat.css).
// Add to Cart adds one copy without leaving the page (assets/js/main.js shows the "Added to your cart" message and updates
// the cart badge); without JavaScript the form is sent to the Book page, which adds it and shows the message.
// ($cta is kept for old callers; the button on the stage is always the amber one.)
function k_book_card(array $b, string $cta = 'outline'): void
{
    $href = BASE_URL . 'frontend/product.php?id=' . (int) $b['id'];
    $cover_img = trim((string) ($b['image'] ?? ''));
    $cover_url = $cover_img === '' ? '' : (strpos($cover_img, 'http') === 0 ? $cover_img : BASE_URL . $cover_img);
    ?>
    <article class="k-book">
        <div class="k-book__media">
            <a class="k-book__cover" href="<?php echo $href; ?>" tabindex="-1" aria-hidden="true">
                <?php k_cover($b); ?>
            </a>
            <?php k_wish_button($b); ?>
            <form method="POST" action="<?php echo $href; ?>" class="k-book__add" data-add-form
                data-title="<?php echo htmlspecialchars($b['title']); ?>" data-cover="<?php echo htmlspecialchars($cover_url); ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" name="add_to_cart" class="k-btn k-btn--primary"
                    aria-label="Add to Cart: <?php echo htmlspecialchars($b['title']); ?>"><span data-add-label>Add to Cart</span></button>
            </form>
        </div>
        <div class="k-book__info">
            <?php echo k_rating_row((int) $b['id']); ?>
            <a class="k-book__title" href="<?php echo $href; ?>"><?php echo htmlspecialchars($b['title']); ?><span class="k-sr"> by <?php echo htmlspecialchars($b['author']); ?></span></a>
            <span class="k-book__price">Rs. <?php echo number_format((float) $b['price']); ?></span>
        </div>
    </article>
    <?php
}
