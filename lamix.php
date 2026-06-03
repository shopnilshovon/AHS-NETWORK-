<?php
require_once __DIR__ . '/config.php';

class Lamix {

    private static function solveCaptcha($html) {
        if (preg_match('/What is\s+(\d+)\s*\+\s*(\d+)/i', $html, $m)) return (int)$m[1] + (int)$m[2];
        if (preg_match('/What is\s+(\d+)\s*-\s*(\d+)/i',  $html, $m)) return (int)$m[1] - (int)$m[2];
        if (preg_match('/What is\s+(\d+)\s*\*\s*(\d+)/i',  $html, $m)) return (int)$m[1] * (int)$m[2];
        return 0;
    }

    // ── Generic cURL request ─────────────────────────────────────────────────
    private static function req($url, $method='GET', $postData=null, $extraHeaders=[], $cookieFile=null) {
        $cf = $cookieFile ?: COOKIE_FILE;
        $ch = curl_init();
        $headers = array_merge([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
            'Accept-Language: en-US,en;q=0.9',
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_COOKIEJAR      => $cf,
            CURLOPT_COOKIEFILE     => $cf,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postData) curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $body   = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return ['body'=>$body, 'code'=>$code, 'url'=>$finUrl];
    }

    // ── Login a user (client or agent) ───────────────────────────────────────
    public static function login($username, $password, $cookieFile=null) {
        // Step 1: GET login page
        $r    = self::req(LAMIX_BASE . '/login', 'GET', null, [], $cookieFile);
        $capt = self::solveCaptcha($r['body']);

        // Step 2: POST credentials
        $post = http_build_query([
            'username' => $username,
            'password' => $password,
            'capt'     => $capt,
        ]);
        $r = self::req(
            LAMIX_BASE . '/signin', 'POST', $post,
            [
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: ' . LAMIX_BASE . '/login',
                'Origin: http://51.210.208.26',
            ],
            $cookieFile
        );

        $url  = $r['url'];
        $body = $r['body'];

        if (stripos($url, 'login') !== false && stripos($body, 'logout') === false)
            return ['success'=>false, 'error'=>'Invalid username or password'];

        $isClient = stripos($url, '/client/') !== false;
        $isAgent  = stripos($url, '/agent/')  !== false;
        return ['success'=>true, 'is_client'=>$isClient, 'is_agent'=>$isAgent];
    }

    // ── Check if agent cookie is still valid ─────────────────────────────────
    public static function agentIsLoggedIn() {
        if (!file_exists(COOKIE_FILE)) return false;
        $r    = self::req(LAMIX_BASE . '/agent/SMSDashboard', 'GET', null, ['X-Requested-With: XMLHttpRequest']);
        $url  = $r['url'];
        $body = $r['body'];
        if (stripos($url,  'login')    !== false) return false;
        if (strpos($body, 'name="username"') !== false) return false;
        return $r['code'] === 200;
    }

    // ── Ensure agent session (re-login if expired) ────────────────────────────
    public static function ensureAgent() {
        if (self::agentIsLoggedIn()) return true;
        $r = self::login(AGENT_USERNAME, AGENT_PASSWORD);
        return $r['success'];
    }

    // ── Fetch ranges ─────────────────────────────────────────────────────────
    public static function getRanges() {
        if (!self::ensureAgent()) return ['success'=>false, 'error'=>'Agent login failed'];
        $all  = [];
        $page = 1;
        while (true) {
            $r    = self::req(
                LAMIX_BASE . "/agent/res/aj_smsranges.php?max=100&page=$page",
                'GET', null,
                ['X-Requested-With: XMLHttpRequest', 'Referer: ' . LAMIX_BASE . '/agent/MySMSNumbers']
            );
            $data  = json_decode($r['body'], true);
            $items = (is_array($data) && isset($data['results'])) ? $data['results'] : (is_array($data) ? $data : []);
            if (empty($items)) break;
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                $id   = (string)($item['id'] ?? '');
                $name = trim(strip_tags((string)($item['title'] ?? $item['text'] ?? '')), '- ');
                if ($id && $name) {
                    $all[] = ['id'=>$id, 'name'=>$name, 'payout'=>getPayoutForRange($name)];
                }
            }
            if (count($items) < 100) break;
            $page++;
        }
        return ['success'=>true, 'ranges'=>$all];
    }

    // ── Get client_id from agent's client list by username ────────────────────
    public static function getClientId($username) {
        if (!self::ensureAgent()) return null;
        $r    = self::req(
            LAMIX_BASE . '/agent/res/data_clients.php?sEcho=1&iColumns=8&iDisplayStart=0&iDisplayLength=200&sSearch=',
            'GET', null, ['X-Requested-With: XMLHttpRequest']
        );
        $data = json_decode($r['body'], true);
        $rows = $data['aaData'] ?? [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $rawName = trim(strip_tags((string)($row[1] ?? '')));
            if (strcasecmp($rawName, $username) === 0) {
                // Try to extract ID from first column HTML
                preg_match('/value=[\'"](\d+)[\'"]/', (string)($row[0]??''), $m);
                if ($m) return $m[1];
                preg_match('/fclient=(\d+)/', (string)($row[0]??''), $m);
                if ($m) return $m[1];
            }
        }
        return null;
    }

    // ── Allocate numbers ─────────────────────────────────────────────────────
    public static function allocate($rangeId, $clientId, $payterm, $payout, $qty) {
        if (!self::ensureAgent()) return ['success'=>false, 'error'=>'Agent login failed'];
        $post = http_build_query([
            'action'    => 'allocate',
            'ntype'     => '-2',
            'range[]'   => $rangeId,
            'client[]'  => $clientId,
            'payterm'   => $payterm,
            'payout'    => $payout,
            'qty'       => $qty,
        ]);
        $r = self::req(
            LAMIX_BASE . '/agent/SMSBulkAllocations', 'POST', $post,
            ['Referer: ' . LAMIX_BASE . '/agent/SMSBulkAllocations']
        );
        if ($r['code'] === 200) {
            if (stripos($r['body'], 'no numbers available') !== false)
                return ['success'=>false, 'error'=>'No numbers available in this range'];
            return ['success'=>true];
        }
        return ['success'=>false, 'error'=>'HTTP ' . $r['code']];
    }

    // ── Fetch allocated numbers ───────────────────────────────────────────────
    public static function getNumbers($clientId, $rangeId, $qty, $excludeNumbers = []) {
        if (!self::ensureAgent()) return [];

        // Fetch generously (qty * 10, capped at 500) to maximise chance newest
        // allocations are visible. Matches the working Python bot approach.
        $fetchLimit = min(max($qty * 10, 200), 500);

        // CRITICAL: include sColumns parameter (7 commas for 8 columns).
        // Without this, Lamix DataTable does NOT honor sort and returns rows in
        // raw insertion order — causing the "always same numbers" bug.
        $r = self::req(
            LAMIX_BASE . "/agent/res/data_smsnumbers.php"
                . "?frange=" . urlencode($rangeId)
                . "&fclient=" . urlencode($clientId)
                . "&sEcho=1&iColumns=8"
                . "&sColumns=" . urlencode(',,,,,,,')      // ← KEY parameter
                . "&iDisplayStart=0&iDisplayLength=" . $fetchLimit
                . "&sSearch=&bRegex=false"
                . "&iSortCol_0=0&sSortDir_0=desc&iSortingCols=1",
            'GET', null, [
                'X-Requested-With: XMLHttpRequest',
                'Referer: ' . LAMIX_BASE . '/agent/MySMSNumbers',
            ]
        );

        $data = json_decode($r['body'], true);
        $rows = $data['aaData'] ?? [];

        $excludeSet = array_flip(array_map('strval', $excludeNumbers));
        $numbers = [];

        // Search ALL columns for a phone number — Lamix layout sometimes shifts
        // (e.g. when Date column is enabled), so don't trust fixed indices.
        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            foreach ($row as $cell) {
                $raw = trim(strip_tags(preg_replace('/<[^>]+>/', '', (string)$cell)));
                $raw = ltrim($raw, '+');
                if (ctype_digit($raw) && strlen($raw) >= 7 && strlen($raw) <= 15) {
                    if (!isset($excludeSet[$raw]) && !in_array($raw, $numbers, true)) {
                        $numbers[] = $raw;
                    }
                    break; // one phone number per row
                }
            }

            if (count($numbers) >= $qty) break;
        }

        return array_slice($numbers, 0, $qty);
    }

    // ── Get available number count for a specific range ──────────────────────
    // Counts unallocated numbers (those with #allocatem link in client column).
    public static function getRangeAvailableCount($rangeId) {
        if (!self::ensureAgent()) {
            return ['success'=>false, 'error'=>'Agent login failed'];
        }

        $available = 0;
        $total     = 0;
        $start     = 0;
        $page      = 500;

        while (true) {
            $r = self::req(
                LAMIX_BASE . "/agent/res/data_smsnumbers.php"
                    . "?frange=" . urlencode($rangeId)
                    . "&fclient="
                    . "&sEcho=1&iColumns=8"
                    . "&sColumns=" . urlencode(',,,,,,,')
                    . "&iDisplayStart=" . $start
                    . "&iDisplayLength=" . $page
                    . "&sSearch=&bRegex=false"
                    . "&iSortCol_0=0&sSortDir_0=desc&iSortingCols=1",
                'GET', null, [
                    'X-Requested-With: XMLHttpRequest',
                    'Referer: ' . LAMIX_BASE . '/agent/MySMSNumbers',
                ]
            );

            $data  = json_decode($r['body'], true);
            $rows  = $data['aaData'] ?? [];
            $iTotalRecords = isset($data['iTotalRecords']) ? (int)$data['iTotalRecords'] : 0;
            if ($total === 0) $total = $iTotalRecords;

            if (empty($rows)) break;

            foreach ($rows as $row) {
                if (!is_array($row)) continue;
                // Available rows have '#allocatem' link in their client column (col 5)
                $clientCol = (string)($row[5] ?? '');
                if (stripos($clientCol, '#allocatem') !== false) {
                    $available++;
                }
            }

            if (count($rows) < $page) break;
            $start += $page;
            if ($start > 20000) break; // safety cap
        }

        return [
            'success'   => true,
            'available' => $available,
            'total'     => $total,
        ];
    }
}