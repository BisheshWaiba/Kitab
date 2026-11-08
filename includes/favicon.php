<?php
// The site icon, shared by every page's <head> (storefront, profile setup, admin).
// favicon.ico for older browsers, the SVG for current ones (sizes="any" makes them choose it over the .ico),
// and the touch icon for iPhones/iPads when a page is added to the home screen.
?>
    <link rel="icon" href="<?php echo BASE_URL; ?>favicon.ico" sizes="32x32">
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon.svg" type="image/svg+xml" sizes="any">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/images/apple-touch-icon.png">
