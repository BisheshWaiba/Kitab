<?php
// Page description and the preview shown when a link is shared (WhatsApp, Messenger, Facebook, X, Slack, iMessage ...).
// A page can set these before it includes the header; whatever it leaves out falls back to the shop's own picture and text:
//   $meta_description  one or two plain sentences about the page
//   $meta_image        a cover or picture for it (a full address, or a path from the site root); the shop picture when empty
//   $meta_image_alt    what that picture shows
// Every address in these tags has to be a full one, because the crawler that reads them is not on the page: k_site_url() builds them.
$share_shop = (require __DIR__ . '/site.php')['name'];
$share_title = isset($page_title) ? $page_title . ' - ' . $share_shop : $share_shop . ' - Premium Book Store';
$share_text = !empty($meta_description)
    ? $meta_description
    : $share_shop . ' is a premium book store. Browse our books, get free shipping on orders over Rs. 2,000 and pay cash on delivery.';
$share_own_image = !empty($meta_image);
$share_image = $share_own_image ? (string) $meta_image : 'assets/images/share.jpg';
$share_image = preg_match('#^https?://#i', $share_image) ? $share_image : k_site_url($share_image);
$share_image_alt = $share_own_image ? ($meta_image_alt ?? $share_title) : $share_shop . ', a premium book store, with a fan of book covers';
?>
    <meta name="description" content="<?php echo htmlspecialchars($share_text); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($share_shop); ?>">
    <meta property="og:type" content="<?php echo htmlspecialchars($meta_type ?? 'website'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($share_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($share_text); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($share_image); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($share_image_alt); ?>">
<?php if (!$share_own_image): // the shop picture has a known size, which lets the big preview show at once ?>
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
<?php endif; ?>
    <meta name="twitter:card" content="<?php echo $share_own_image ? 'summary' : 'summary_large_image'; ?>">
