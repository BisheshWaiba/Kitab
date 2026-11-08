<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
$page_title = 'Authors';
require_once '../includes/header.php';

// One entry per author, built from the books themselves
$result = $conn->query(
    "SELECT MIN(TRIM(author)) AS name, COUNT(*) AS books, GROUP_CONCAT(title ORDER BY title SEPARATOR ' · ') AS titles
     FROM books WHERE TRIM(author) <> '' GROUP BY TRIM(author) ORDER BY name ASC"
);
$authors = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total = count($authors);

// "E. H. Gombrich" -> "EG"
function k_initials(string $name): string
{
    $words = preg_split('/\s+/u', trim($name));
    $last = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';
    return mb_strtoupper(mb_substr($words[0], 0, 1) . $last);
}
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">Authors</h1>
        <p><?php echo $total === 0 ? 'No authors yet' : $total . ($total === 1 ? ' writer' : ' writers') . ' on our shelves'; ?></p>
    </div>
</section>

<div class="k-shop">
    <main class="k-shop__main">
        <?php if ($total > 0): ?>
            <ul class="k-authors">
                <?php foreach ($authors as $a): ?>
                    <li>
                        <a class="k-author" href="shop.php?author=<?php echo urlencode($a['name']); ?>">
                            <span class="k-author__mono" aria-hidden="true"><?php echo htmlspecialchars(k_initials($a['name'])); ?></span>
                            <span class="k-author__body">
                                <span class="k-author__name"><?php echo htmlspecialchars($a['name']); ?></span>
                                <span class="k-author__count"><?php echo (int) $a['books'] . ((int) $a['books'] === 1 ? ' book' : ' books'); ?></span>
                                <span class="k-author__titles"><?php echo htmlspecialchars($a['titles']); ?></span>
                            </span>
                            <svg class="k-author__arrow" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="k-empty">
                <p>There are no books in the store yet.</p>
                <a href="shop.php" class="k-btn k-btn--outline">Go to Shop</a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
