<?php
/**
 * One-time storage symlink creator for cPanel hosts without SSH.
 *
 * This script is for setups where the ENTIRE Laravel project lives
 * inside public_html (not a separate Stock folder).
 *
 * Correct paths:
 *   link   → /home/user/public_html/public/storage
 *   target → /home/user/public_html/storage/app/public
 *
 * INSTRUCTIONS:
 *   1. Upload this file to your public_html/public/ directory
 *   2. Visit https://yourdomain.com/storage_link.php in your browser
 *   3. DELETE THIS FILE immediately after — it is a security risk if left on the server
 */

$publicHtml  = __DIR__;                              // /home/abushspq/public_html
$link        = $publicHtml . '/storage';             // where the symlink will live

// Laravel is deployed directly INTO public_html
// so storage/app/public is INSIDE public_html
$projectRoot = $publicHtml;                          // /home/abushspq/public_html
$target      = $projectRoot . '/storage/app/public'; // /home/abushspq/public_html/storage/app/public

echo '<pre>';
echo 'public_html : ' . $publicHtml  . PHP_EOL;
echo 'project root: ' . $projectRoot . PHP_EOL;
echo 'target      : ' . $target      . PHP_EOL;
echo 'link        : ' . $link        . PHP_EOL;
echo '</pre>';

if (!file_exists($target)) {
    echo '<p style="color:red">❌ Target path does not exist: <code>' . $target . '</code><br>';
    echo 'Edit the $projectRoot variable in this file to the correct path.</p>';
} elseif (is_link($link)) {
    echo '<p style="color:orange">⚠️ Symlink already exists → ' . readlink($link) . '</p>';
    echo '<p>If it points to the wrong place, delete <code>public_html/storage</code> and re-run.</p>';
} elseif (file_exists($link)) {
    echo '<p style="color:red">❌ A real folder named <code>storage</code> already exists in public_html.<br>';
    echo 'Delete it via File Manager, then re-run this script.</p>';
} elseif (symlink($target, $link)) {
    echo '<p style="color:green;font-size:1.2em">✅ Symlink created successfully!</p>';
    echo '<p><code>' . $link . '</code> → <code>' . $target . '</code></p>';
    echo '<p>Company logos and user avatars will now display correctly.</p>';
} else {
    echo '<p style="color:red">❌ symlink() failed — your host may not allow PHP symlinks.<br>';
    echo 'Ask your host to run via SSH: <code>cd ' . $projectRoot . ' && php artisan storage:link</code></p>';
}

echo '<hr><p><strong style="color:red">⚠️ DELETE THIS FILE from public_html NOW!</strong></p>';
