<?php
require __DIR__ . '/_auth.php';
rs_require_login();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$cfg = rs_config();
$session = ['backendName' => 'github', 'token' => $cfg['github_token']];
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin · Recommendation Sites</title>
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <script>
    // Signed in with username/password: open the editor directly (no GitHub prompt).
    try { localStorage.setItem('rs_notrack', '1'); } catch (e) {}
    try { localStorage.setItem('sveltia-cms.user', <?= json_encode(json_encode($session)) ?>); } catch (e) {}
  </script>
</head>
<body>
  <script src="https://unpkg.com/@sveltia/cms@0.271.2/dist/sveltia-cms.js"></script>
  <div style="position:fixed;right:18px;bottom:18px;z-index:99999;display:flex;gap:8px;font:700 14px system-ui,sans-serif">
    <a href="/admin/stats/" style="background:#0b1f1a;color:#ffc23d;text-decoration:none;padding:12px 16px;border-radius:999px;box-shadow:0 10px 24px rgba(0,0,0,.3)">📊 Stats</a>
    <a href="/admin/logout.php" style="background:#fff;color:#0b1f1a;text-decoration:none;padding:12px 16px;border-radius:999px;box-shadow:0 10px 24px rgba(0,0,0,.3)">Log out</a>
  </div>
</body>
</html>
