<?php
// Analytics collector for Recommendation Sites (self-hosted, no cookies).
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(204); exit; }
try {
    require dirname(__DIR__) . '/admin/_analytics.php';
    rs_collect();
} catch (Throwable $e) {
    http_response_code(204);
}
