<?php
// api/numbers.php — fetch newly-allocated numbers for a client+range
// Tracks already-served numbers in data/served/{hash}.json per (user+range)
// so the same numbers don't appear repeatedly across multiple allocations.
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../lamix.php';
header('Content-Type: application/json');
requireLogin();

@set_time_limit(60);
@ini_set('max_execution_time', '60');

$user          = getUser();
$rangeId       = trim($_GET['range_id'] ?? '');
$qty           = (int)($_GET['qty'] ?? 30);
$clientId      = $user['client_id'] ?? '';
$justAllocated = (int)($_GET['just_allocated'] ?? 0);

if (!$clientId || !$rangeId) {
    echo json_encode(['numbers' => [], 'error' => 'Missing client_id or range_id']);
    exit;
}

// ── Served-number tracking ─────────────────────────────────────────────
$servedDir = __DIR__ . '/../data/served';
if (!is_dir($servedDir)) @mkdir($servedDir, 0755, true);

$servedKey  = md5($user['username'] . '|' . $clientId . '|' . $rangeId);
$servedFile = $servedDir . '/' . $servedKey . '.json';

$servedNumbers = [];
$servedRecord  = [];
if (file_exists($servedFile)) {
    $raw = @file_get_contents($servedFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $cutoff = time() - (90 * 86400); // 90-day retention
        foreach ($decoded as $num => $ts) {
            if ((int)$ts >= $cutoff) {
                $servedRecord[$num] = (int)$ts;
                $servedNumbers[]    = (string)$num;
            }
        }
    }
}

// ── Wait for Lamix commit if allocate just happened ────────────────────
if ($justAllocated) {
    sleep(3);
}

// ── Retry fetch up to 4 times if not enough fresh numbers ──────────────
$numbers  = [];
$maxRetry = $justAllocated ? 4 : 1;

for ($attempt = 0; $attempt < $maxRetry; $attempt++) {
    $numbers = Lamix::getNumbers($clientId, $rangeId, $qty, $servedNumbers);
    if (count($numbers) >= $qty) break;
    if ($attempt < $maxRetry - 1) sleep(2 + $attempt);
}

// ── Save served numbers ────────────────────────────────────────────────
if (!empty($numbers)) {
    $now = time();
    foreach ($numbers as $num) {
        $servedRecord[(string)$num] = $now;
    }
    @file_put_contents($servedFile, json_encode($servedRecord), LOCK_EX);
}

echo json_encode([
    'numbers'   => $numbers,
    'count'     => count($numbers),
    'requested' => $qty,
    'partial'   => count($numbers) < $qty,
]);