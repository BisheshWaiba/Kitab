<?php
session_start();
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KITAB - Premium Book Store</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/genre.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <header class="navbar">
        <div class="container nav-container">
            <a href="<?php echo BASE_URL; ?>frontend/index.php" class="logo">KITAB.</a>

            <form action="<?php echo BASE_URL; ?>frontend/shop.php" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search books..."
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>

            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>frontend/index.php">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>frontend/shop.php">Shop</a></li>
                <li>
                    <a href="<?php echo BASE_URL; ?>frontend/cart.php" class="nav-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>frontend/profile.php" class="nav-cart" style="font-size: 1.3rem;">
                            <i class="fas fa-user-circle"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li style="display: flex; gap: 1rem; align-items: center;">
                        <a href="<?php echo BASE_URL; ?>frontend/login.php" class="btn btn-light"
                            style="padding: 0.6rem 1.5rem;">Log In</a>
                        <a href="<?php echo BASE_URL; ?>frontend/register.php" class="btn btn-primary"
                            style="padding: 0.6rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="far fa-user"></i> Sign Up
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </header>