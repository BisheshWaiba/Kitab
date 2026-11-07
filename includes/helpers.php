<?php
// Shared markup helpers for the redesigned pages. Needs BASE_URL (config/db.php).

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
function k_auth_art(mysqli $conn, int $offset = 0): void
{
    $rows = $conn->query("SELECT id, title, image FROM books WHERE image IS NOT NULL AND image <> '' ORDER BY id ASC LIMIT 4 OFFSET " . $offset);
    $books = $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];
    if (!$books) {
        return;
    }
    ?>
    <div class="k-auth-art" aria-hidden="true">
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
