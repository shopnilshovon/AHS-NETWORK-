<?php
// api/allocate.php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../lamix.php';
header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit; }

$user     = getUser();
$rangeId  = trim($_POST['range_id'] ?? '');
$payterm  = trim($_POST['payterm']  ?? '2');
$payout   = trim($_POST['payout']   ?? DEFAULT_PAYOUT);
$qty      = (int)($_POST['qty']     ?? 1);
$clientId = $user['client_id'] ?? '';

if (!$clientId) { echo json_encode(['success'=>false,'error'=>'Client ID not found. Please re-login.']); exit; }
if (!$rangeId)  { echo json_encode(['success'=>false,'error'=>'Range not selected']); exit; }
if ($qty < 1 || $qty > 30) { echo json_encode(['success'=>false,'error'=>'Invalid quantity (1-30)']); exit; }

$r = Lamix::allocate($rangeId, $clientId, $payterm, $payout, $qty);
echo json_encode($r);
