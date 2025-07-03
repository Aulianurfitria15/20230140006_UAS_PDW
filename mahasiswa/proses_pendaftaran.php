// mahasiswa/proses_pendaftaran.php
<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa' || !isset($_GET['id_praktikum'])) {
    header("Location: ../login.php");
    exit();
}

$id_praktikum = $_GET['id_praktikum'];
$id_mahasiswa = $_SESSION['user_id'];

// Cek agar tidak daftar double
$stmt_check = $conn->prepare("SELECT id FROM pendaftaran_praktikum WHERE id_mahasiswa = ? AND id_praktikum = ?");
$stmt_check->bind_param("ii", $id_mahasiswa, $id_praktikum);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO pendaftaran_praktikum (id_mahasiswa, id_praktikum) VALUES (?, ?)");
    $stmt->bind_param("ii", $id_mahasiswa, $id_praktikum);
    $stmt->execute();
}

header("Location: praktikum_saya.php");
exit();
?>