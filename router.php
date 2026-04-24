<?php
/**
 * Router for PHP built-in server (Railway preview).
 * Mirrors the .htaccess rewrite/redirect logic used on one.com production.
 *
 * Only invoked when the requested path is NOT a real file on disk
 * (PHP's built-in server serves existing files directly).
 */

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

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------
function serve_file($path) {
    if (!is_file($path)) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
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
    readfile($path);
    return true;
}

function redirect($to, $code = 301) {
    header("Location: {$to}", true, $code);
    exit;
}

// -----------------------------------------------------------------------------
// Handle shared assets + contact.php routing
// -----------------------------------------------------------------------------
// Contact form POSTs — route to the per-site contact.php
if (($path === '/contact.php' || $path === '/kontakt.php') && is_file("{$site}/contact.php")) {
    chdir(__DIR__ . '/' . $site);
    require __DIR__ . "/{$site}/contact.php";
    exit;
}

// sitemap.xml at root → per-site
if ($path === '/sitemap.xml' && is_file("{$site}/sitemap.xml")) {
    if (serve_file("{$site}/sitemap.xml")) exit;
}

// robots.txt at root (shared)
if ($path === '/robots.txt') {
    if (is_file(__DIR__ . '/robots.txt') && serve_file(__DIR__ . '/robots.txt')) exit;
    // Fallback
    header('Content-Type: text/plain');
    echo "User-agent: *\nAllow: /\n";
    exit;
}

// -----------------------------------------------------------------------------
// Root
// -----------------------------------------------------------------------------
if ($path === '/' || $path === '') {
    if (serve_file("{$site}/index.html")) exit;
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
        if (serve_file("{$sub}/index.html")) exit;
    }
    $candidate = $sub . $rest;
    if (is_file($candidate)) {
        serve_file($candidate);
        exit;
    }
    // Strip prefix and continue resolution for this subsite
    $site = $sub;
    $path = $rest ?: '/';
}

// -----------------------------------------------------------------------------
// Shared assets — serve from repo root (matches href="../shared/...")
// -----------------------------------------------------------------------------
if (strpos($path, '/shared/') === 0) {
    $f = ltrim($path, '/');
    if (is_file($f)) {
        serve_file($f);
        exit;
    }
}

// -----------------------------------------------------------------------------
// Canonical enforcement: /foo.html → /foo/  (301)
// -----------------------------------------------------------------------------
if (preg_match('#^/([^/]+)\.html$#', $path, $m)) {
    $slug = $m[1];
    if (is_file("{$site}/{$slug}.html") && $slug !== 'index') {
        // Don't redirect if the request already came from the pretty URL internally
        redirect("/{$slug}/");
    }
}

// -----------------------------------------------------------------------------
// Taxonomy + legacy WP paths → 301 to blog index
// -----------------------------------------------------------------------------
if (preg_match('#^/(category|tag|author|kategorie|autor)/#', $path)) {
    $blog = ($site === 'de') ? '/blog/msc-headhunting-blog/' : '/blog/';
    redirect($blog);
}
if (preg_match('#^/wp-(content|admin|includes|json)/#', $path)) {
    http_response_code(410);
    exit;
}
if ($path === '/feed/' || $path === '/feed') {
    redirect(($site === 'de') ? '/blog/msc-headhunting-blog/' : '/blog/');
}

// -----------------------------------------------------------------------------
// Pretty URL resolution
// -----------------------------------------------------------------------------
$trimmed = rtrim($path, '/');
$segments = array_values(array_filter(explode('/', $trimmed), 'strlen'));

// 1. Exact file: /com/foo.html style path
if (is_file("{$site}{$path}") && substr($path, -1) !== '/') {
    serve_file("{$site}{$path}");
    exit;
}

// 2. /foo/ → {site}/foo.html
if (count($segments) === 1) {
    $f = "{$site}/{$segments[0]}.html";
    if (is_file($f)) { serve_file($f); exit; }
}

// 3. /foo/bar/ → {site}/foo/bar.html (subdir file, e.g. testimonials/, case-studies/)
if (count($segments) === 2) {
    $f = "{$site}/{$segments[0]}/{$segments[1]}.html";
    if (is_file($f)) { serve_file($f); exit; }

    // Flat-prefix files (DE testimonials + case studies migrated as prefix-slug.html)
    $prefixes = [
        'testimonials'       => 'testimonial',
        'unsere-referenzen'  => 'testimonial',
        'case-studies'       => 'case-study',
    ];
    if (isset($prefixes[$segments[0]])) {
        $flat = "{$site}/{$prefixes[$segments[0]]}-{$segments[1]}.html";
        if (is_file($flat)) { serve_file($flat); exit; }
    }

    // Sector pattern: /industries-and-sectors/foo/ → {site}/foo.html (root-level)
    $sector_root = "{$site}/{$segments[1]}.html";
    if (is_file($sector_root)) { serve_file($sector_root); exit; }

    // WP attachment pattern: /parent/child/ where parent itself is a real page
    if (is_file("{$site}/{$segments[0]}.html")) {
        redirect("/{$segments[0]}/");
    }
}

// 4. Deeper paths — try last segment at root
if (count($segments) >= 3) {
    $last = end($segments);
    if (is_file("{$site}/{$last}.html")) {
        serve_file("{$site}/{$last}.html");
        exit;
    }
    // Attachment: redirect to first segment as parent
    if (is_file("{$site}/{$segments[0]}.html")) {
        redirect("/{$segments[0]}/");
    }
}

// -----------------------------------------------------------------------------
// 404
// -----------------------------------------------------------------------------
http_response_code(404);
$site_404 = "{$site}/404.html";
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
