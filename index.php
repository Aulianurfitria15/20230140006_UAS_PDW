<?php
// Mulai session untuk memeriksa status login
session_start();

// Cek apakah pengguna sudah memiliki session (sudah login)
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    
    // Jika sudah login, arahkan berdasarkan peran (role)
    if ($_SESSION['role'] == 'asisten') {
        // Arahkan ke dashboard asisten
        header("Location: asisten/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        // Arahkan ke dashboard mahasiswa
        header("Location: mahasiswa/dashboard.php");
        exit();
    }
    
}

// Jika tidak ada session (belum login), arahkan ke halaman login
header("Location: login.php");
exit();

?>
