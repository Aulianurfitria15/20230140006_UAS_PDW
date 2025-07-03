<?php
// mahasiswa/courses.php

// 1. Panggil Header dan Konfigurasi
$pageTitle = 'Cari Praktikum';
$activePage = 'courses'; // Untuk menandai link aktif di navigasi
require_once 'templates/header_mahasiswa.php';
require_once '../config.php';

// Pastikan hanya mahasiswa yang bisa mengakses
if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../asisten/dashboard.php');
    exit;
}

$id_mahasiswa = $_SESSION['user_id'];

// 2. Query untuk mengambil semua mata praktikum yang tersedia
// Kita juga menggunakan LEFT JOIN untuk mengecek apakah mahasiswa sudah terdaftar di praktikum tersebut
$sql = "SELECT 
            mp.id,
            mp.nama_praktikum,
            mp.deskripsi,
            u.nama as nama_asisten,
            (SELECT COUNT(*) FROM pendaftaran_praktikum pp WHERE pp.id_praktikum = mp.id AND pp.id_mahasiswa = ?) as sudah_terdaftar
        FROM mata_praktikum mp
        JOIN users u ON mp.id_asisten_pembuat = u.id
        ORDER BY mp.nama_praktikum ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_mahasiswa);
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
        <div class="bg-white p-6 rounded-lg shadow-md flex flex-col">
            <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
            <p class="text-gray-600 mb-4 flex-grow"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
            <p class="text-sm text-gray-500 mb-4">Asisten: <?php echo htmlspecialchars($row['nama_asisten']); ?></p>
            
            <div class="mt-auto">
                <?php if ($row['sudah_terdaftar'] > 0): ?>
                    <button class="w-full text-center bg-gray-400 text-white px-4 py-2 rounded-lg cursor-not-allowed" disabled>
                        Sudah Terdaftar
                    </button>
                <?php else: ?>
                    <a href="proses_pendaftaran.php?id_praktikum=<?php echo $row['id']; ?>" class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700" onclick="return confirm('Apakah Anda yakin ingin mendaftar di praktikum ini?');">
                        Daftar Praktikum
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full text-center p-6 bg-white rounded-lg shadow-md">
            <p class="text-gray-500">Saat ini belum ada praktikum yang tersedia.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$conn->close();
require_once 'templates/footer_mahasiswa.php';
?>