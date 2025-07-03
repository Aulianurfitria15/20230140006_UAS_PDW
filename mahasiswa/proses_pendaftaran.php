<?php
// mahasiswa/proses_pendaftaran.php

session_start();
require_once '../config.php';

// Keamanan: Pastikan pengguna sudah login sebagai mahasiswa dan ada ID praktikum
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa' || !isset($_GET['id_praktikum'])) {
    // Jika tidak, arahkan ke halaman login
    header("Location: ../login.php");
    exit();
}

$id_praktikum = $_GET['id_praktikum'];
$id_mahasiswa = $_SESSION['user_id'];

// Keamanan: Cek agar mahasiswa tidak bisa mendaftar di praktikum yang sama lebih dari sekali
$stmt_check = $conn->prepare("SELECT id FROM pendaftaran_praktikum WHERE id_mahasiswa = ? AND id_praktikum = ?");
$stmt_check->bind_param("ii", $id_mahasiswa, $id_praktikum);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

// Jika hasilnya 0 (belum terdaftar), maka proses pendaftaran
if ($result_check->num_rows === 0) {
    $stmt_insert = $conn->prepare("INSERT INTO pendaftaran_praktikum (id_mahasiswa, id_praktikum) VALUES (?, ?)");
    $stmt_insert->bind_param("ii", $id_mahasiswa, $id_praktikum);
    $stmt_insert->execute();
    $stmt_insert->close();
}
$stmt_check->close();

// Setelah selesai, arahkan pengguna ke halaman "Praktikum Saya" untuk melihat hasilnya
header("Location: praktikum_saya.php");
exit();

?>
