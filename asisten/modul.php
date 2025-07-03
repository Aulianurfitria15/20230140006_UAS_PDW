<?php
// asisten/modul.php

// Mulai sesi dan panggil konfigurasi terlebih dahulu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

// =======================================================================
// BAGIAN 1: SEMUA LOGIKA PHP DITARUH DI SINI
// =======================================================================

// 1. Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// 2. Ambil ID Praktikum dari URL, jika tidak ada, kembalikan ke halaman matkul
if (!isset($_GET['id_praktikum']) || empty($_GET['id_praktikum'])) {
    header('Location: matkul.php');
    exit;
}
$id_praktikum = $_GET['id_praktikum'];

// 3. Ambil nama praktikum untuk ditampilkan di judul
$stmt_praktikum = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_praktikum->bind_param("i", $id_praktikum);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();
if ($result_praktikum->num_rows === 0) {
    // Jika ID praktikum tidak valid, kembalikan
    header('Location: matkul.php');
    exit;
}
$praktikum = $result_praktikum->fetch_assoc();
// Variabel $pageTitle akan digunakan nanti di header.php
$pageTitle = 'Kelola Modul: ' . htmlspecialchars($praktikum['nama_praktikum']); 
$stmt_praktikum->close();


// Inisialisasi variabel untuk pesan dan path upload
$pesan = '';
$upload_dir = '../uploads/materi/'; // Pastikan folder ini ada

// 4. Logika untuk DELETE modul
if (isset($_GET['hapus_id'])) {
    // ... (Logika hapus tetap sama) ...
}

// 5. Logika untuk PROSES FORM (CREATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_modul'])) {
    // ... (Logika tambah modul tetap sama) ...
}


// 6. Logika untuk MEMBACA SEMUA DATA MODUL (READ)
$result_modul = $conn->query("SELECT * FROM modul WHERE id_praktikum = $id_praktikum ORDER BY created_at ASC");


// =======================================================================
// BAGIAN 2: TAMPILAN HTML DIMULAI DARI SINI
// Panggil header SETELAH semua logika di atas selesai
// =======================================================================
require_once '../templates/header.php';
?>

<?php if (!empty($pesan)) { echo $pesan; } ?>

<a href="matkul.php" class="mb-4 inline-block text-indigo-600 hover:text-indigo-900">&larr; Kembali ke Daftar Praktikum</a>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Tambah Modul Baru</h3>
    <form action="modul.php?id_praktikum=<?php echo $id_praktikum; ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label for="nama_modul" class="block text-sm font-medium text-gray-700">Nama Modul (Contoh: Modul 1 - Pengenalan HTML)</label>
            <input type="text" id="nama_modul" name="nama_modul" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
        </div>
        <div class="mb-4">
            <label for="file_materi" class="block text-sm font-medium text-gray-700">File Materi (PDF, DOCX, PPTX)</label>
            <input type="file" id="file_materi" name="file_materi" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>
        <button type="submit" name="tambah_modul" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            Simpan Modul
        </button>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    </div>

<?php
// Panggil Footer
require_once '../templates/footer.php';
$conn->close();
?>