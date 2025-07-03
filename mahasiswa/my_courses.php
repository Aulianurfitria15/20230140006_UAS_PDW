<?php
// mahasiswa/praktikum_saya.php

// 1. Panggil Header dan Konfigurasi
$pageTitle = 'Praktikum Saya';
require_once 'templates/header_mahasiswa.php';
require_once '../config.php';

// 2. Pastikan hanya mahasiswa yang bisa mengakses
if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../asisten/dashboard.php');
    exit;
}

$id_mahasiswa = $_SESSION['user_id'];

// 3. Query untuk mengambil praktikum yang diikuti oleh mahasiswa yang sedang login
$sql = "SELECT 
            mp.id,
            mp.nama_praktikum,
            mp.deskripsi,
            u.nama as nama_asisten
        FROM pendaftaran_praktikum pp
        JOIN mata_praktikum mp ON pp.id_praktikum = mp.id
        JOIN users u ON mp.id_asisten_pembuat = u.id
        WHERE pp.id_mahasiswa = ?
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
            
            <a href="detail_praktikum.php?id=<?php echo $row['id']; ?>" class="mt-auto w-full text-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                Lihat Detail & Tugas
            </a>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full text-center p-6 bg-white rounded-lg shadow-md">
            <p class="text-gray-500">Anda belum mendaftar di praktikum manapun.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$conn->close();
require_once 'templates/footer_mahasiswa.php';
?>
