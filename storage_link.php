<?php
/**
 * One-time storage symlink creator for cPanel hosts without SSH.
 *
 * INSTRUCTIONS:
 *   1. Upload this file to your public_html/ directory
 *   2. Visit https://yourdomain.com/storage_link.php in your browser
 *   3. DELETE THIS FILE immediately after — it is a security risk if left on the server
 */

$publicHtml = __DIR__;                          // e.g. /home/user/public_html
$link       = $publicHtml . '/storage';         // where the symlink will live

// Auto-detect the project root — look for artisan file going up from public_html
$projectRoot = null;
$search      = dirname($publicHtml);            // go one level up from public_html

foreach (['Stock', 'spare_part', 'app', 'project'] as $candidate) {
    $try = $search . '/' . $candidate;
    if (file_exists($try . '/artisan')) {
        $projectRoot = $try;
        break;
    }
}

// Also try the parent directly
if (!$projectRoot && file_exists($search . '/artisan')) {
    $projectRoot = $search;
}

// Manual fallback — edit this if auto-detect fails
if (!$projectRoot) {
    $projectRoot = dirname($publicHtml) . '/Stock'; // adjust folder name if needed
}

$target = $projectRoot . '/storage/app/public';

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
