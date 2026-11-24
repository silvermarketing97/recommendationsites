<?php
require dirname(__DIR__) . '/_auth.php';
require dirname(__DIR__) . '/_analytics.php';
rs_start_session();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (!rs_logged_in()) { http_response_code(401); echo json_encode(['error' => 'login']); exit; }
try {
    if (isset($_GET['realtime'])) { echo json_encode(rs_realtime()); exit; }
    echo json_encode(rs_report($_GET['range'] ?? '30d', $_GET['from'] ?? null, $_GET['to'] ?? null));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
