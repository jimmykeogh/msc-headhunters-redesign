<?php
/**
 * Router for PHP built-in server (Railway preview).
 * Mirrors the .htaccess rewrite/redirect logic used on one.com production.
 *
 * Invoked for EVERY request by `php -S ... router.php`. If this script
 * returns FALSE, PHP serves the on-disk file as-is; otherwise the
 * script's output is sent.
 */

$ROOT = __DIR__;

// Let PHP serve existing static files directly (CSS, JS, images, fonts, etc.)
// — only intercept when the request is NOT a real file on disk.
$uri_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($uri_path !== '/' && $uri_path !== '' && is_file($ROOT . $uri_path)) {
    return false;
}

// -----------------------------------------------------------------------------
// Site selection (host-based; defaults to com on unknown hosts)
// -----------------------------------------------------------------------------
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
if (strpos($host, 'msc-headhunters.de') !== false || strpos($host, '.de') !== false) {
    $site = 'de';
} elseif (strpos($host, 'msc-headhunters.nl') !== false || strpos($host, '.nl') !== false) {
    $site = 'nl';
} else {
    $site = 'com';
}

$path   = $uri_path;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------
function serve_file($abs) {
    if (!is_file($abs)) return false;
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $mimes = [
        'html' => 'text/html; charset=UTF-8',
        'htm'  => 'text/html; charset=UTF-8',
        'css'  => 'text/css; charset=UTF-8',
        'js'   => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'xml'  => 'application/xml; charset=UTF-8',
        'txt'  => 'text/plain; charset=UTF-8',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'otf'  => 'font/otf',
        'pdf'  => 'application/pdf',
    ];
    if (isset($mimes[$ext])) header('Content-Type: ' . $mimes[$ext]);
    readfile($abs);
    return true;
}

function redirect($to, $code = 301) {
    header("Location: {$to}", true, $code);
    exit;
}

function dbg($tag) {
    header('X-Router-Decision: ' . $tag);
}

function load_rewrites($root, $site) {
    static $cache = [];
    if (!isset($cache[$site])) {
        $f = "$root/$site/rewrites.json";
        if (is_file($f)) {
            $cache[$site] = json_decode(file_get_contents($f), true) ?: ['rewrites'=>[], 'redirects'=>[]];
        } else {
            $cache[$site] = ['rewrites'=>[], 'redirects'=>[]];
        }
    }
    return $cache[$site];
}

// -----------------------------------------------------------------------------
// Special routes
// -----------------------------------------------------------------------------
// Contact form — route to the per-site contact.php
if (($path === '/contact.php' || $path === '/kontakt.php') && is_file("$ROOT/$site/contact.php")) {
    dbg("contact:$site");
    chdir("$ROOT/$site");
    require "$ROOT/$site/contact.php";
    exit;
}

// sitemap.xml at root → per-site
if ($path === '/sitemap.xml' && is_file("$ROOT/$site/sitemap.xml")) {
    dbg("sitemap:$site");
    serve_file("$ROOT/$site/sitemap.xml");
    exit;
}

// robots.txt at root (shared)
if ($path === '/robots.txt') {
    if (is_file("$ROOT/robots.txt")) { dbg('robots'); serve_file("$ROOT/robots.txt"); exit; }
    dbg('robots-fallback');
    header('Content-Type: text/plain');
    echo "User-agent: *\nAllow: /\n";
    exit;
}

// -----------------------------------------------------------------------------
// Root
// -----------------------------------------------------------------------------
if ($path === '/' || $path === '') {
    dbg("root:$site");
    if (serve_file("$ROOT/$site/index.html")) exit;
    http_response_code(404);
    exit;
}

// -----------------------------------------------------------------------------
// Direct file access (com/de/nl prefixed paths, e.g. /com/foo.html)
// -----------------------------------------------------------------------------
if (preg_match('#^/(com|de|nl)(/.*)?$#', $path, $m)) {
    $sub = $m[1];
    $rest = $m[2] ?? '';
    if ($rest === '' || $rest === '/') {
        dbg("prefix-root:$sub");
        if (serve_file("$ROOT/$sub/index.html")) exit;
    }
    $candidate = "$ROOT/$sub$rest";
    if (is_file($candidate)) {
        dbg("prefix-direct:$sub$rest");
        serve_file($candidate);
        exit;
    }
    // Strip prefix and continue resolution for this subsite
    $site = $sub;
    $path = $rest ?: '/';
}

// -----------------------------------------------------------------------------
// Canonical enforcement: /foo.html → /foo/  (301)
// -----------------------------------------------------------------------------
if (preg_match('#^/([^/]+)\.html$#', $path, $m)) {
    $slug = $m[1];
    if (is_file("$ROOT/$site/{$slug}.html") && $slug !== 'index') {
        dbg('canonical-301');
        redirect("/{$slug}/");
    }
}

// -----------------------------------------------------------------------------
// Taxonomy + legacy WP paths → 301 to blog index (or 410)
// -----------------------------------------------------------------------------
if (preg_match('#^/(category|tag|author|kategorie|autor)/#', $path)) {
    $blog = ($site === 'de') ? '/blog/msc-headhunting-blog/' : '/blog/';
    dbg('tax-301');
    redirect($blog);
}
if (preg_match('#^/wp-(content|admin|includes|json)/#', $path)) {
    dbg('wp-410');
    http_response_code(410);
    exit;
}
if ($path === '/feed/' || $path === '/feed') {
    dbg('feed-301');
    redirect(($site === 'de') ? '/blog/msc-headhunting-blog/' : '/blog/');
}

// -----------------------------------------------------------------------------
// Explicit .htaccess-derived rewrites / redirects
// -----------------------------------------------------------------------------
$norm = '/' . trim($path, '/') . '/';
$rw = load_rewrites($ROOT, $site);
if (isset($rw['redirects'][$norm])) {
    dbg('map-301');
    redirect($rw['redirects'][$norm]);
}
if (isset($rw['rewrites'][$norm])) {
    $tgt = $rw['rewrites'][$norm];
    $abs = (substr($tgt, 0, 1) === '/') ? "$ROOT$tgt" : "$ROOT/$site/$tgt";
    if (is_file($abs)) {
        dbg('map-rewrite');
        serve_file($abs);
        exit;
    }
}

// -----------------------------------------------------------------------------
// Pretty URL resolution
// -----------------------------------------------------------------------------
$trimmed = rtrim($path, '/');
$segments = array_values(array_filter(explode('/', $trimmed), 'strlen'));

// 1. Exact file at /site/path
if (substr($path, -1) !== '/' && is_file("$ROOT/$site$path")) {
    dbg('exact');
    serve_file("$ROOT/$site$path");
    exit;
}

// 2. /foo/ → {site}/foo.html
if (count($segments) === 1) {
    $f = "$ROOT/$site/{$segments[0]}.html";
    if (is_file($f)) { dbg('1seg'); serve_file($f); exit; }
}

// 3. /foo/bar/ → {site}/foo/bar.html
if (count($segments) === 2) {
    $f = "$ROOT/$site/{$segments[0]}/{$segments[1]}.html";
    if (is_file($f)) { dbg('2seg-subdir'); serve_file($f); exit; }

    // Flat-prefix files (migrated testimonials + case studies)
    $prefixes = [
        'testimonials'       => 'testimonial',
        'unsere-referenzen'  => 'testimonial',
        'case-studies'       => 'case-study',
    ];
    if (isset($prefixes[$segments[0]])) {
        $flat = "$ROOT/$site/{$prefixes[$segments[0]]}-{$segments[1]}.html";
        if (is_file($flat)) { dbg('2seg-flat'); serve_file($flat); exit; }
    }

    // Sector pattern: last seg at root
    $sector_root = "$ROOT/$site/{$segments[1]}.html";
    if (is_file($sector_root)) { dbg('2seg-sector'); serve_file($sector_root); exit; }

    // WP attachment pattern: /parent/child/ → /parent/
    if (is_file("$ROOT/$site/{$segments[0]}.html")) {
        dbg('2seg-attach');
        redirect("/{$segments[0]}/");
    }
}

// 4. Deeper paths
if (count($segments) >= 3) {
    $last = end($segments);
    if (is_file("$ROOT/$site/{$last}.html")) {
        dbg('deep-last');
        serve_file("$ROOT/$site/{$last}.html");
        exit;
    }
    if (is_file("$ROOT/$site/{$segments[0]}.html")) {
        dbg('deep-attach');
        redirect("/{$segments[0]}/");
    }
}

// -----------------------------------------------------------------------------
// 404
// -----------------------------------------------------------------------------
dbg('404');
http_response_code(404);
$site_404 = "$ROOT/$site/404.html";
if (is_file($site_404)) {
    serve_file($site_404);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>404 Not Found</title><style>body{font-family:sans-serif;max-width:600px;margin:4rem auto;padding:2rem;text-align:center}</style></head>
<body>
  <h1>404 — Not Found</h1>
  <p>The page <code><?= htmlspecialchars($path) ?></code> doesn't exist on the <strong><?= htmlspecialchars($site) ?></strong> site.</p>
  <p><a href="/">Back to home</a></p>
</body>
</html>
