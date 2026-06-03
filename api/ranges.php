<?php
// api/ranges.php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../lamix.php';
header('Content-Type: application/json');
requireLogin();
$r = Lamix::getRanges();
echo json_encode($r['success'] ? ['ranges'=>$r['ranges']] : ['ranges'=>[], 'error'=>$r['error']]);
