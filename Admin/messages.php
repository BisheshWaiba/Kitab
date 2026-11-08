<?php
// Messages sent through the Contact page. Handled before the header is output: deleting one answers with a redirect.
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/contact.php';
k_contact_table($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your session expired, so nothing was deleted. Please try again.'];
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'The message was deleted.'];
    }
    header("Location: " . BASE_URL . "admin/messages.php");
    exit();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$total = (int) $conn->query("SELECT COUNT(*) FROM contact_messages")->fetch_row()[0];
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC LIMIT 200")->fetch_all(MYSQLI_ASSOC);

// The unread ones are tagged "New" below; opening this page counts as reading them
$conn->query("UPDATE contact_messages SET is_read = 1 WHERE is_read = 0");

// header.php counts the unread messages for the sidebar, so it comes after the update above
include 'includes/header.php';
?>

<main class="k-admin__main">
    <div class="k-admin__bar">
        <h1 class="k-h1">Messages</h1>
        <span class="k-admin__welcome"><?php echo $total . ($total === 1 ? ' message' : ' messages'); ?> from the Contact page</span>
    </div>

    <?php if ($flash): ?>
        <div class="k-alert k-alert--<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>"
            role="<?php echo $flash['type'] === 'success' ? 'status' : 'alert'; ?>">
            <?php echo htmlspecialchars($flash['message']); ?></div>
    <?php endif; ?>

    <div class="k-tablecard">
        <table class="k-table k-table--xwide">
            <thead>
                <tr>
                    <th>From</th>
                    <th>About</th>
                    <th>Message</th>
                    <th>Received</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr>
                        <td>
                            <div class="k-admintitle"><?php echo htmlspecialchars($m['name']); ?>
                                <?php if (!$m['is_read']): ?><span class="k-tag">New</span><?php endif; ?></div>
                            <div class="k-muted"><?php echo htmlspecialchars($m['email']); ?></div>
                        </td>
                        <td><span class="k-tag"><?php echo htmlspecialchars(CONTACT_TOPICS[$m['topic']] ?? $m['topic']); ?></span></td>
                        <td class="k-msgtext"><?php echo htmlspecialchars($m['message']); ?></td>
                        <td class="k-muted k-nowrap"><?php echo date('M j, Y g:i A', strtotime($m['created_at'])); ?></td>
                        <td>
                            <div class="k-rowactions">
                                <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>?subject=<?php echo rawurlencode('Re: your message to ' . 'KITAB'); ?>"
                                    class="k-btn k-btn--outline k-btn--xs">Reply</a>
                                <form method="POST" onsubmit="return confirm('Delete this message?')">
                                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>">
                                    <button type="submit" class="k-btn k-btn--danger k-btn--xs"
                                        aria-label="Delete the message from <?php echo htmlspecialchars($m['name']); ?>">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$messages): ?>
                    <tr>
                        <td colspan="5" class="k-table__empty">No messages yet. They appear here when someone uses the Contact page.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>

</html>
