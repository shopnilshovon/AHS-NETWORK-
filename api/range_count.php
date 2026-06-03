<?php
// api/range_count.php — return available number count for a range
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../lamix.php';
header('Content-Type: application/json');
requireLogin();

@set_time_limit(60);

$rangeId = trim($_GET['range_id'] ?? '');
if (!$rangeId) {
    echo json_encode(['success'=>false, 'error'=>'Missing range_id']);
    exit;
}

$r = Lamix::getRangeAvailableCount($rangeId);
echo json_encode($r);