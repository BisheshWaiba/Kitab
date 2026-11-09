<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css'];
$page_title = 'About';
$legal = require '../includes/legal_pages.php'; // for the return period, so it is only ever written in one place
$return_days = (int) $legal['site']['return_days'];

// Numbers come straight from the catalogue, so they are always true
$facts = $conn->query(
    "SELECT COUNT(*) AS books,
            COUNT(DISTINCT NULLIF(TRIM(author), '')) AS authors,
            COUNT(DISTINCT NULLIF(TRIM(category), '')) AS collections
     FROM books"
)->fetch_assoc();
require_once '../includes/header.php';
?>

<section class="k-pagehead">
    <div class="k-pagehead__inner">
        <h1 class="k-h1">About KITAB</h1>
        <p>An online bookstore for readers</p>
    </div>
</section>

<div class="k-about">
    <p class="k-about__lead">
        KITAB is an online bookstore. Browse the shelves by category or by author, save the books you want to read next,
        and have them brought to your door.
    </p>

    <ul class="k-facts">
        <li class="k-fact">
            <span class="k-fact__n"><?php echo (int) $facts['books']; ?></span>
            <span class="k-fact__label"><?php echo (int) $facts['books'] === 1 ? 'Book' : 'Books'; ?> in the store</span>
        </li>
        <li class="k-fact">
            <span class="k-fact__n"><?php echo (int) $facts['authors']; ?></span>
            <span class="k-fact__label"><?php echo (int) $facts['authors'] === 1 ? 'Author' : 'Authors'; ?></span>
        </li>
        <li class="k-fact">
            <span class="k-fact__n"><?php echo (int) $facts['collections']; ?></span>
            <span class="k-fact__label"><?php echo (int) $facts['collections'] === 1 ? 'Collection' : 'Collections'; ?> to explore</span>
        </li>
    </ul>

    <section class="k-about__section" aria-labelledby="about-find">
        <h2 class="k-h2" id="about-find">What you'll find here</h2>
        <ul class="k-features">
            <li class="k-card k-feature">
                <h3>Browse your way</h3>
                <p>Search the catalogue, pick a collection, or open an author to see all of their books together.</p>
                <a href="collections.php">See the collections</a>
            </li>
            <li class="k-card k-feature">
                <h3>Save for later</h3>
                <p>Tap the heart on any book to add it to your wishlist, and come back to it when you are ready.</p>
                <a href="wishlist.php">Open your wishlist</a>
            </li>
            <li class="k-card k-feature">
                <h3>Hear from readers</h3>
                <p>Every book page has ratings and reviews from other readers, and you can add your own.</p>
                <a href="shop.php">Find a book to review</a>
            </li>
            <li class="k-card k-feature">
                <h3>Sell a book</h3>
                <p>Have a book to pass on? Members can list their own books for sale from their account.</p>
                <a href="sell_book.php">List a book</a>
            </li>
        </ul>
    </section>

    <section class="k-about__section" aria-labelledby="about-order">
        <h2 class="k-h2" id="about-order">How ordering works</h2>
        <ol class="k-how">
            <li>
                <h3>Pick your books</h3>
                <p>Add them to your cart. Orders over Rs. 2,000 ship free.</p>
            </li>
            <li>
                <h3>Check out</h3>
                <p>Tell us your name, email and delivery address. There is nothing to pay online.</p>
            </li>
            <li>
                <h3>Pay on delivery</h3>
                <p>You pay in cash when your order arrives at your door.</p>
            </li>
            <li>
                <h3>Not right? Send it back</h3>
                <p>Return a book within <?php echo $return_days; ?> days, or tell us if it arrives damaged. Our
                    <a href="legal.php?page=returns">Return policy</a> has the details.</p>
            </li>
        </ol>
    </section>

    <section class="k-cta">
        <h2 class="k-h2">Find your next read</h2>
        <p>Start with the whole catalogue, or write to us if you have a question.</p>
        <div class="k-cta__actions">
            <a href="shop.php" class="k-btn k-btn--primary">Browse Books</a>
            <a href="contact.php" class="k-btn k-btn--outline">Contact Us</a>
        </div>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>
