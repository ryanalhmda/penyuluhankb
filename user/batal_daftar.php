<?php
require_once '../config.php';
require_login();

$id = $_GET['id'] ?? 0;

// Hapus pendaftaran
$sql = "DELETE FROM pendaftaran_penyuluhan WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $_SESSION['user_id']);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    // Update status jadwal jika tidak penuh lagi
    $sql_update = "UPDATE jadwal_penyuluhan j
                   SET j.status = 'terbuka'
                   WHERE j.id IN (
                       SELECT jadwal_id FROM pendaftaran_penyuluhan WHERE id = ?
                   ) 
                   AND j.kapasitas > (
                       SELECT COUNT(*) FROM pendaftaran_penyuluhan 
                       WHERE jadwal_id = j.id
                   )";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $id);
    $stmt_update->execute();
    
    header("Location: jadwal.php?status=batal_success");
} else {
    header("Location: jadwal.php?status=batal_failed");
}
exit();
?>