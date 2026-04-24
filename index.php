<?php
// Host-based site selection; mirrors router.php for Apache environments
// where the router script isn't used (e.g. if this file is invoked directly).
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
if (strpos($host, '.de') !== false) {
    $target = '/de/index.html';
} elseif (strpos($host, '.nl') !== false) {
    $target = '/nl/index.html';
} else {
    $target = '/com/index.html';
}
header('Location: ' . $target, true, 302);
exit;
