<?php

require_once __DIR__ . '/includes/fungsi.php';

// Logout hanya diproses melalui tombol Keluar dengan metode POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Gunakan tombol Keluar.');
}

// Memeriksa token keamanan formulir.
cek_token();

// Mengosongkan data session.
$_SESSION = [];

// Menghapus cookie session dari browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]);
}

// Menghapus session pada server.
session_destroy();

// Mengalihkan pengguna ke halaman login.
pergi('login.php');