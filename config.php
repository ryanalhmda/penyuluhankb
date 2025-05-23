<?php
session_start();

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kb_penyuluhan');

// Konfigurasi tambahan
define('SITE_URL', 'http://localhost/penyuluhan_kb');
define('UPLOAD_PATH', 'uploads/');
define('AVATAR_PATH', 'uploads/avatars/');

// Pastikan folder upload ada
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}
if (!file_exists(AVATAR_PATH)) {
    mkdir(AVATAR_PATH, 0777, true);
}

// Koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set karakter encoding
$conn->set_charset("utf8");

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Cek login
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Cek admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Redirect jika belum login
function require_login() {
    if (!is_logged_in()) {
        header("Location: auth.php?mode=login");
        exit();
    }
}

// Redirect jika bukan admin
function require_admin() {
    if (!is_admin()) {
        header("Location: index.php");
        exit();
    }
}

// Format tanggal Indonesia
function tanggal_indo($tanggal){
    $bulan = array (
        1 =>   'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// Alert function
function alert($message, $type = 'info') {
    $icon = '';
    $bg_color = '';
    $border_color = '';
    $text_color = '';
    
    switch($type) {
        case 'success':
            $icon = '✓';
            $bg_color = 'bg-green-100';
            $border_color = 'border-green-400';
            $text_color = 'text-green-700';
            break;
        case 'error':
            $icon = '✕';
            $bg_color = 'bg-red-100';
            $border_color = 'border-red-400';
            $text_color = 'text-red-700';
            break;
        case 'warning':
            $icon = '⚠';
            $bg_color = 'bg-yellow-100';
            $border_color = 'border-yellow-400';
            $text_color = 'text-yellow-700';
            break;
        default:
            $icon = 'ℹ';
            $bg_color = 'bg-blue-100';
            $border_color = 'border-blue-400';
            $text_color = 'text-blue-700';
            break;
    }
    
    return '<div class="' . $bg_color . ' border-2 ' . $border_color . ' ' . $text_color . ' px-4 py-3 rounded relative mb-4" role="alert">
              <span class="font-bold">' . $icon . '</span> ' . $message . '
            </div>';
}
?>