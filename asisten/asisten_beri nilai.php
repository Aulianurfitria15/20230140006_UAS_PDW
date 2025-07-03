<?php
// asisten/beri_nilai.php

// 1. Panggil Header dan Konfigurasi
require_once '../templates/header.php';
require_once '../config.php';

// 2. Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// 3. Ambil ID Laporan dari URL, jika tidak ada, kembalikan
if (!isset($_GET['id_laporan']) || empty($_GET['id_laporan'])) {
    header('Location: kelola_laporan.php');
    exit;
}
$id_laporan = $_GET['id_laporan'];

$pesan = '';
$upload_dir = '../uploads/laporan/'; // Pastikan folder ini ada

// 4. Logika untuk PROSES FORM (UPDATE NILAI)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nilai = $_POST['nilai'];
    $feedback = $_POST['feedback'];

    $stmt = $conn->prepare("UPDATE laporan SET nilai = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("isi", $nilai, $feedback, $id_laporan);
    
    if ($stmt->execute()) {
        $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Nilai berhasil disimpan.</div>";
    } else {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menyimpan nilai.</div>";
    }
    $stmt->close();
}

// 5. Query untuk mengambil detail laporan yang akan dinilai
$sql = "SELECT 
            l.id, l.file_laporan, l.nilai, l.feedback,
            u.nama as nama_mahasiswa,
            m.nama_modul,
            mp.nama_praktikum
        FROM laporan l
        JOIN users u ON l.id_mahasiswa = u.id
        JOIN modul m ON l.id_modul = m.id
        JOIN mata_praktikum mp ON m.id_praktikum = mp.id
        WHERE l.id = ?";
$stmt_laporan = $conn->prepare($sql);
$stmt_laporan->bind_param("i", $id_laporan);
$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();

if ($result_laporan->num_rows === 0) {
    // Jika ID laporan tidak valid, kembalikan
    header('Location: kelola_laporan.php');
    exit;
}
$laporan = $result_laporan->fetch_assoc();
$pageTitle = 'Beri Nilai: ' . htmlspecialchars($laporan['nama_mahasiswa']);
$stmt_laporan->close();
?>

<!-- Tampilkan pesan sukses/gagal jika ada -->
<?php if (!empty($pesan)) { echo $pesan; } ?>

<a href="kelola_laporan.php" class="mb-4 inline-block text-indigo-600 hover:text-indigo-900">&larr; Kembali ke Daftar Laporan</a>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Kolom Informasi Laporan -->
    <div class="md:col-span-1 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Detail Laporan</h3>
        <div class="space-y-3">
            <div>
                <dt class="text-sm font-medium text-gray-500">Nama Mahasiswa</dt>
                <dd class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Mata Praktikum</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Modul</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($laporan['nama_modul']); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">File Laporan</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php if (!empty($laporan['file_laporan']) && file_exists($upload_dir . $laporan['file_laporan'])): ?>
                        <a href="<?php echo $upload_dir . htmlspecialchars($laporan['file_laporan']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-semibold underline">
                            Unduh Laporan
                        </a>
                    <?php else: ?>
                        File tidak ditemukan.
                    <?php endif; ?>
                </dd>
            </div>
        </div>
    </div>

    <!-- Kolom Form Penilaian -->
    <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Form Penilaian</h3>
        <form action="beri_nilai.php?id_laporan=<?php echo $id_laporan; ?>" method="POST">
            <div class="mb-4">
                <label for="nilai" class="block text-sm font-medium text-gray-700">Nilai (Angka)</label>
                <input type="number" id="nilai" name="nilai" min="0" max="100" value="<?php echo htmlspecialchars($laporan['nilai']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>
            <div class="mb-4">
                <label for="feedback" class="block text-sm font-medium text-gray-700">Feedback (Teks)</label>
                <textarea id="feedback" name="feedback" rows="5" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($laporan['feedback']); ?></textarea>
            </div>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Simpan Nilai
            </button>
        </form>
    </div>
</div>

<?php
// 6. Panggil Footer
require_once '../templates/footer.php';
$conn->close();
?>
