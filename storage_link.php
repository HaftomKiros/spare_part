<?php
/**
 * One-time storage setup + symlink creator for cPanel hosts without SSH.
 *
 * INSTRUCTIONS:
 *   1. Upload this file to public_html/ (root of your Laravel project)
 *   2. Visit https://yourdomain.com/storage_link.php in your browser
 *   3. DELETE THIS FILE immediately after — security risk if left on server
 */

// ── Hardcoded paths — works regardless of where script is placed ──────
$root   = __DIR__;                              // /home/abushspq/public_html
$target = $root . '/storage/app/public';        // target for symlink
$link   = $root . '/public/storage';            // where Laravel expects symlink

echo '<pre>';
echo 'Project root : ' . $root   . PHP_EOL;
echo 'Symlink link : ' . $link   . PHP_EOL;
echo 'Symlink target: ' . $target . PHP_EOL;
echo '</pre>';

// ── Step 1: Create required folders if missing ────────────────────────
$folders = [
    $root . '/storage/app/public',
    $root . '/storage/app/public/avatars',
    $root . '/storage/app/public/logos',
    $root . '/storage/framework/cache/data',
    $root . '/storage/framework/sessions',
    $root . '/storage/framework/views',
    $root . '/storage/logs',
    $root . '/bootstrap/cache',
];

$created = [];
$failed  = [];
foreach ($folders as $folder) {
    if (! is_dir($folder)) {
        if (mkdir($folder, 0775, true)) {
            $created[] = $folder;
        } else {
            $failed[] = $folder;
        }
    }
}

if ($created) {
    echo '<p style="color:green">✅ Created folders:<br>' . implode('<br>', $created) . '</p>';
}
if ($failed) {
    echo '<p style="color:red">❌ Could not create:<br>' . implode('<br>', $failed) . '</p>';
}

// ── Step 2: Create the symlink ────────────────────────────────────────
if (is_link($link)) {
    echo '<p style="color:orange">⚠️ Symlink already exists → ' . readlink($link) . '</p>';
    echo '<p>If it points to the wrong place, delete <code>public/storage</code> and re-run.</p>';
} elseif (file_exists($link)) {
    echo '<p style="color:red">❌ A real folder named <code>public/storage</code> already exists.<br>
    Delete it via File Manager, then re-run this script.</p>';
} elseif (symlink($target, $link)) {
    echo '<p style="color:green;font-size:1.2em">✅ Symlink created successfully!</p>';
    echo '<p><code>' . $link . '</code><br>&nbsp;&nbsp;&nbsp;→ <code>' . $target . '</code></p>';
    echo '<p style="color:green">Uploads (avatars, logos) will now display correctly.</p>';
} else {
    echo '<p style="color:red">❌ symlink() failed.<br>
    Run via SSH instead: <code>cd ' . $root . ' && php artisan storage:link</code></p>';
}

echo '<hr><p><strong style="color:red">⚠️ DELETE THIS FILE (storage_link.php) NOW!</strong></p>';
