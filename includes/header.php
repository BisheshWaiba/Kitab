<?php
// Buffer output so pages can still redirect with header() after including this file.
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}

require_once __DIR__ . '/helpers.php';
$wish_count = count(k_wishlist_ids());

$current_page = basename($_SERVER['SCRIPT_NAME']);
$nav_home = $current_page === 'index.php';
$nav_wish = $current_page === 'wishlist.php';
$nav_shop = in_array($current_page, ['shop.php', 'product.php'], true);
$user_initial = isset($_SESSION['user_name']) ? strtoupper(mb_substr($_SESSION['user_name'], 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - KITAB' : 'KITAB - Premium Book Store'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/genre.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/kitab.css">
</head>

<body>

    <div class="k-announce">Free shipping on orders over Rs. 2,000</div>

    <header class="k-nav">
        <div class="k-nav__inner">
            <a href="<?php echo BASE_URL; ?>index.php" class="k-logo">KITAB<span>.</span></a>

            <form action="<?php echo BASE_URL; ?>frontend/shop.php" method="GET" class="k-search" role="search">
                <button type="submit" class="k-search__btn" aria-label="Search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </button>
                <input type="text" name="search" placeholder="Search books..." aria-label="Search books"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </form>

            <nav class="k-links" aria-label="Main">
                <a href="<?php echo BASE_URL; ?>index.php"
                    class="k-link<?php echo $nav_home ? ' is-active' : ''; ?>">Home</a>
                <a href="<?php echo BASE_URL; ?>frontend/shop.php"
                    class="k-link<?php echo $nav_shop ? ' is-active' : ''; ?>">Shop</a>
                <a href="<?php echo BASE_URL; ?>frontend/wishlist.php"
                    class="k-cart k-cart--wish<?php echo $nav_wish ? ' is-active' : ''; ?><?php echo isset($_SESSION['user_id']) ? '' : ' k-cart--guest'; ?>"
                    aria-label="Wishlist<?php echo $wish_count > 0 ? ', ' . $wish_count . ' saved' : ''; ?>"
                    <?php echo $nav_wish ? 'aria-current="page"' : ''; ?>>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z" />
                    </svg>
                    <?php if ($wish_count > 0): ?>
                        <span class="k-cart__badge"><?php echo $wish_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>frontend/cart.php" class="k-cart" aria-label="Cart<?php echo $cart_count > 0 ? ', ' . $cart_count . ' items' : ''; ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 4h2.2l2.1 10.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.2" />
                        <circle cx="9.5" cy="19.5" r="1.4" />
                        <circle cx="17" cy="19.5" r="1.4" />
                    </svg>
                    <?php if ($cart_count > 0): ?>
                        <span class="k-cart__badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo BASE_URL; ?>frontend/profile.php" class="k-avatar" aria-label="My account">
                        <?php echo htmlspecialchars($user_initial); ?>
                    </a>
                <?php else: ?>
                    <div class="k-auth">
                        <a href="<?php echo BASE_URL; ?>frontend/login.php" class="k-btn k-btn--outline k-btn--sm">Log In</a>
                        <a href="<?php echo BASE_URL; ?>frontend/register.php" class="k-btn k-btn--primary k-btn--sm">Sign Up</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>
