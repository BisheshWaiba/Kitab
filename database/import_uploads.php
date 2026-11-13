<?php
// Copies the pictures in your local uploads/ folder (book covers, profile photos) into the database the site is online
// with, so the online site can show them. Run it once from your own computer, pointed at the ONLINE database:
//
//     set DB_HOST=... & set DB_PORT=... & set DB_USER=... & set DB_PASS=... & set DB_NAME=... & set DB_SSL=1
//     php database/import_uploads.php
//
// Safe to run again: a picture that is already there is skipped. Command line only.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$root = realpath(__DIR__ . '/../uploads');
if ($root === false) {
    exit("There is no uploads/ folder here.\n");
}

$types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
$added = $skipped = $ignored = 0;
$conn = k_upload_table();

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $full = $file->getPathname();
    $info = @getimagesize($full);
    if (!$info || !in_array($info[2], $types, true)) {
        $ignored++;
        continue;
    }
    // the address the site uses for it: uploads/books/name.jpg
    $path = 'uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', substr($full, strlen($root) + 1));
    $mime = image_type_to_mime_type($info[2]);
    $bytes = file_get_contents($full);
    $stmt = $conn->prepare("INSERT IGNORE INTO kitab_uploads (path, mime, data) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $path, $mime, $bytes);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $added++;
        echo "added    $path\n";
    } else {
        $skipped++;
        echo "skipped  $path\n";
    }
}
echo "\nDone: $added added, $skipped already there, $ignored not pictures.\n";
