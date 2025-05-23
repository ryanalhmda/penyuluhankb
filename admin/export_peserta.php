<?php
require_once '../config.php';
require_login();
require_admin();

$jadwal_id = $_GET['jadwal_id'] ?? 0;

// Ambil info jadwal
$sql_jadwal = "SELECT judul FROM jadwal_penyuluhan WHERE id = ?";
$stmt_jadwal = $conn->prepare($sql_jadwal);
$stmt_jadwal->bind_param("i", $jadwal_id);
$stmt_jadwal->execute();
$result_jadwal = $stmt_jadwal->get_result();
$jadwal = $result_jadwal->fetch_assoc();

if (!$jadwal) {
    die("Jadwal tidak ditemukan");
}

// Ambil data peserta
$sql_peserta = "SELECT p.*, u.name, u.email, u.phone
                FROM pendaftaran_penyuluhan p
                JOIN users u ON p.user_id = u.id
                WHERE p.jadwal_id = ?
                ORDER BY p.tanggal_daftar ASC";
$stmt_peserta = $conn->prepare($sql_peserta);
$stmt_peserta->bind_param("i", $jadwal_id);
$stmt_peserta->execute();
$result_peserta = $stmt_peserta->get_result();

// Headers untuk download CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="peserta_' . str_replace(' ', '_', $jadwal['judul']) . '_' . date('YmdHis') . '.csv"');

// Output file
$output = fopen('php://output', 'w');

// Tulis header CSV
fputcsv($output, ['No', 'Nama', 'Email', 'Telepon', 'Tanggal Daftar', 'Status']);

// Tulis data
$no = 1;
while($peserta = $result_peserta->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $peserta['name'],
        $peserta['email'],
        $peserta['phone'] ?? '-',
        tanggal_indo($peserta['tanggal_daftar']),
        ucfirst(str_replace('_', ' ', $peserta['status']))
    ]);
}

fclose($output);
?>