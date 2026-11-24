<?php
/**
 * Serves the static pages of the site and records visits from robots
 * (Googlebot, Bing, AI crawlers, SEO tools, scripts) for the stats dashboard.
 * Human visitors are counted by the JavaScript tracker instead.
 */
$root = realpath(dirname(__DIR__));
$uri = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
if ($uri === '' || $uri[0] !== '/' || strpos($uri, "\0") !== false) $uri = '/';

$file = substr($uri, -1) === '/' ? $root . $uri . 'index.html' : $root . $uri;
$real = realpath($file);
$status = 200;
if ($real === false || strpos($real, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)
    || !preg_match('/\.(html|xml|txt)$/', $real) || preg_match('#/(admin|api)/#', substr($real, strlen($root)))) {
    $status = 404;
    $real = $root . '/404.html';
}

// Record robots (never let analytics break the page)
try {
    require $root . '/admin/_analytics.php';
    rs_log_bot($uri, $status);
} catch (Throwable $e) { /* ignore */ }

$types = ['html' => 'text/html; charset=utf-8', 'xml' => 'application/xml; charset=utf-8', 'txt' => 'text/plain; charset=utf-8'];
$ext = pathinfo($real, PATHINFO_EXTENSION);
if (substr($real, -8) === 'feed.xml') $types['xml'] = 'application/rss+xml; charset=utf-8';

http_response_code($status);
header('Content-Type: ' . ($types[$ext] ?? 'text/plain; charset=utf-8'));
header('X-Content-Type-Options: nosniff');
if ($ext === 'html') header('Cache-Control: no-cache');

$mtime = @filemtime($real);
if ($status === 200 && $mtime) {
    $etag = '"' . dechex($mtime) . '-' . dechex(filesize($real)) . '"';
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    header('ETag: ' . $etag);
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
}
header('Content-Length: ' . filesize($real));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') readfile($real);
