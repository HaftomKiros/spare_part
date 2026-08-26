<?php
/**
 * One-time storage symlink creator for cPanel hosts without SSH.
 *
 * INSTRUCTIONS:
 *   1. Upload this file to your public_html/ directory
 *   2. Visit https://yourdomain.com/storage_link.php in your browser
 *   3. DELETE THIS FILE immediately after — it is a security risk if left on the server
 *
 * What it does: creates public_html/storage → ../Stock/storage/app/public symlink
 * (equivalent to running `php artisan storage:link` via SSH)
 */

// Adjust this path if your project folder is named differently
$target = __DIR__ . '/../Stock/storage/app/public';
$link   = __DIR__ . '/storage';

if (is_link($link)) {
    echo '<p style="color:orange">⚠️  Symlink already exists at: ' . $link . '</p>';
} elseif (file_exists($link)) {
    echo '<p style="color:red">❌ A file or folder named "storage" already exists in public_html. Remove it first, then re-run this script.</p>';
} elseif (symlink($target, $link)) {
    echo '<p style="color:green">✅ Storage symlink created successfully!</p>';
    echo '<p>Target: ' . $target . '</p>';
    echo '<p>Link:   ' . $link . '</p>';
} else {
    echo '<p style="color:red">❌ Failed to create symlink. Your host may not allow symlinks via PHP.</p>';
    echo '<p>Contact your host and ask them to run: <code>php artisan storage:link</code> from your project root.</p>';
}

echo '<p><strong>⚠️ DELETE THIS FILE NOW from public_html!</strong></p>';
