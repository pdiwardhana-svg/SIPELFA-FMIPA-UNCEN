<?php
require_once __DIR__ . '/includes/auth.php';

// Keluar hanya lewat tombol "Keluar" (POST + token CSRF).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    keluar_sesi();
    mulai_session();
    flash('success', 'Anda sudah keluar dari akun.');
    redirect(url('login.php'));
}

$user = user_saat_ini();
redirect($user ? url_dashboard($user['role']) : url('login.php'));
