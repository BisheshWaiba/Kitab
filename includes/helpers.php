<?php
// Shared markup helpers for the redesigned pages. Needs BASE_URL (config/db.php).

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

function k_book_card(array $b): void
{
    $href = BASE_URL . 'frontend/product.php?id=' . (int) $b['id'];
    ?>
    <article class="k-book">
        <a class="k-book__cover" href="<?php echo $href; ?>" tabindex="-1" aria-hidden="true">
            <?php k_cover($b); ?>
        </a>
        <div class="k-book__info">
            <a class="k-book__title" href="<?php echo $href; ?>"><?php echo htmlspecialchars($b['title']); ?></a>
            <span class="k-book__author">by <?php echo htmlspecialchars($b['author']); ?><?php if ((string) $b['category'] !== ''): ?><span class="k-book__sep" aria-hidden="true">&middot;</span><span class="k-book__cat"><?php echo htmlspecialchars($b['category']); ?></span><?php endif; ?></span>
        </div>
        <div class="k-book__foot">
            <span class="k-book__price">Rs. <?php echo number_format((float) $b['price']); ?></span>
            <a href="<?php echo $href; ?>" class="k-btn k-btn--outline">Add to Cart</a>
        </div>
    </article>
    <?php
}
