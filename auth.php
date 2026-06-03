<?php
require_once __DIR__ . '/config.php';

function startSess() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict']);
        session_start();
    }
}

function getUser() {
    startSess();
    if (empty($_SESSION['username'])) return null;
    if (!empty($_SESSION['last_act']) && time()-$_SESSION['last_act'] > SESSION_LIFE) {
        session_destroy(); return null;
    }
    $_SESSION['last_act'] = time();
    return [
        'username'  => $_SESSION['username'],
        'client_id' => $_SESSION['client_id'] ?? null,
    ];
}

function requireLogin() {
    if (!getUser()) { header('Location: /index.php'); exit; }
}

function setUser($username, $clientId) {
    startSess();
    session_regenerate_id(true);
    $_SESSION['username']  = $username;
    $_SESSION['client_id'] = $clientId;
    $_SESSION['last_act']  = time();
}

function doLogout() {
    startSess();
    session_destroy();
    header('Location: /index.php'); exit;
}
