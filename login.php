<?php
require 'includes/fungsi.php';
if (!empty($_SESSION['user_id'])) pergi('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_token();
    // Query dibatasi dua baris agar username duplikat pada database lama ditolak.
    $users = query('SELECT * FROM tb_petugas WHERE username = ? LIMIT 2', [trim($_POST['username'] ?? '')])->fetchAll();
    if (count($users) === 1 && hash_equals($users[0]['password'], md5($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $users[0]['id_petugas'];
        pergi('index.php');
    }
    $error = 'Username atau password salah, atau username tidak unik.';
}
?><!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login SPP</title>
<link rel="stylesheet" href="assets/vendor/bootstrap.min.css"><link rel="stylesheet" href="assets/style.css"></head><body>
<main class="panel login"><h1 class="h3">SPP Sekolah</h1><p class="text-secondary">Masuk untuk mengelola pembayaran SPP.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= token() ?><div class="mb-3"><label for="username" class="form-label">Username</label><input id="username" name="username" class="form-control" maxlength="25" required autocomplete="username"></div>
<div class="mb-4"><label for="password" class="form-label">Password</label><input id="password" type="password" name="password" class="form-control" required autocomplete="current-password"></div><button class="btn btn-primary w-100">Masuk</button></form></main></body></html>
