<?php
require_once dirname(__DIR__) . '/config/db.php';

echo "Resetting categories to NULL...\n";

$conn->query("UPDATE books SET category = NULL WHERE category = 'General'");

echo "✓ Categories have been reset to NULL/empty.\n";
echo "Books will no longer show in category filters.\n";

$conn->close();
?>