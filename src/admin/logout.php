<?php
require __DIR__ . '/_auth.php';
rs_start_session();
rs_remember_forget();
$_SESSION = [];
session_destroy();
setcookie('rs_admin', '', time() - 3600, '/admin');
header('Cache-Control: no-store');
?><!doctype html><meta charset="utf-8"><meta name="robots" content="noindex">
<script>try{localStorage.removeItem('sveltia-cms.user');}catch(e){}location.replace('/admin/');</script>
