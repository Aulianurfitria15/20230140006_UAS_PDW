<?php
// mahasiswa/detail_praktikum.php

// 1. Panggil Header dan Konfigurasi
require_once 'templates/header_mahasiswa.php';
require_once '../config.php';

// 2. Validasi akses
if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../asisten/dashboard.php');
    exit;
}
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: praktikum_saya.php');
    exit;
}
$id_praktikum = $_GET['id'];
$id_mahasiswa = $_SESSION['user_id'];
$pesan = '';
$upload_dir_materi = '../uploads/materi/';
$upload_dir_laporan = '../uploads/laporan/'; // Pastikan folder ini ada

// 3. Proses upload laporan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kumpul_laporan'])) {
    $id_modul = $_POST['id_modul'];
    
    if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == 0) {
        $file = $_FILES['file_laporan'];
        $file_name = time() . '_' . basename($file['name']);
        
        if (move_uploaded_file($file['tmp_name'], $upload_dir_laporan . $file_name)) {
            // Cek apakah sudah pernah upload, jika ya, UPDATE, jika tidak, INSERT
            $stmt_check = $conn->prepare("SELECT id FROM laporan WHERE id_modul = ? AND id_mahasiswa = ?");
            $stmt_check->bind_param("ii", $id_modul, $id_mahasiswa);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // UPDATE
                $stmt_update = $conn->prepare("UPDATE laporan SET file_laporan = ?, tanggal_kumpul = NOW(), nilai = NULL, feedback = NULL WHERE id_modul = ? AND id_mahasiswa = ?");
                $stmt_update->bind_param("sii", $file_name, $id_modul, $id_mahasiswa);
                $stmt_update->execute();
            } else {
                // INSERT
                $stmt_insert = $conn->prepare("INSERT INTO laporan (id_modul, id_mahasiswa, file_laporan) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iis", $id_modul, $id_mahasiswa, $file_name);
                $stmt_insert->execute();
            }
            $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Laporan berhasil diunggah.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal mengunggah file.</div>";
        }
    } else {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Tidak ada file yang dipilih atau terjadi error.</div>";
    }
}

// 4. Ambil data praktikum dan modul
$stmt_prak = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_prak->bind_param("i", $id_praktikum);
$stmt_prak->execute();
$praktikum = $stmt_prak->get_result()->fetch_assoc();
$pageTitle = 'Detail: ' . htmlspecialchars($praktikum['nama_praktikum']);

$stmt_modul = $conn->prepare("SELECT m.*, l.file_laporan, l.nilai, l.feedback, l.tanggal_kumpul FROM modul m LEFT JOIN laporan l ON m.id = l.id_modul AND l.id_mahasiswa = ? WHERE m.id_praktikum = ? ORDER BY m.created_at ASC");
$stmt_modul->bind_param("ii", $id_mahasiswa, $id_praktikum);
$stmt_modul->execute();
$result_modul = $stmt_modul->get_result();
?>

<?php if (!empty($pesan)) { echo $pesan; } ?>
<a href="praktikum_saya.php" class="mb-4 inline-block text-indigo-600 hover:text-indigo-900">&larr; Kembali ke Daftar Praktikum</a>

<div class="space-y-6">
    <?php if ($result_modul && $result_modul->num_rows > 0): ?>
        <?php while($modul = $result_modul->fetch_assoc()): ?>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold text-gray-900 border-b pb-2 mb-4"><?php echo htmlspecialchars($modul['nama_modul']); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Kolom Materi & Nilai -->
                <div class="md:col-span-1 space-y-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Materi Praktikum</h4>
                        <?php if(!empty($modul['file_materi'])): ?>
                            <a href="<?php echo $upload_dir_materi . htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="inline-flex items-center mt-2 text-indigo-600 hover:underline">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                Unduh Materi
                            </a>
                        <?php else: ?>
                            <p class="text-sm text-gray-500 mt-2">Materi belum tersedia.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Nilai & Feedback</h4>
                        <?php if(!is_null($modul['nilai'])): ?>
                            <p class="text-2xl font-bold text-green-600 mt-2"><?php echo htmlspecialchars($modul['nilai']); ?></p>
                            <p class="text-sm text-gray-600 mt-1"><strong>Feedback:</strong> <?php echo !empty($modul['feedback']) ? htmlspecialchars($modul['feedback']) : '-'; ?></p>
                        <?php else: ?>
                            <p class="text-sm text-gray-500 mt-2">Laporan Anda belum dinilai.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Kolom Pengumpulan Laporan -->
                <div class="md:col-span-2">
                    <h4 class="font-semibold text-gray-700">Pengumpulan Laporan</h4>
                    <?php if(!empty($modul['file_laporan'])): ?>
                        <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
                            <p class="text-sm text-green-800">Anda telah mengumpulkan laporan pada: <?php echo date('d M Y, H:i', strtotime($modul['tanggal_kumpul'])); ?></p>
                            <p class="text-sm text-green-800">File: <?php echo htmlspecialchars(substr($modul['file_laporan'], 11)); // Menghilangkan timestamp ?></p>
                        </div>
                    <?php endif; ?>
                    <form action="detail_praktikum.php?id=<?php echo $id_praktikum; ?>" method="POST" enctype="multipart/form-data" class="mt-4">
                        <input type="hidden" name="id_modul" value="<?php echo $modul['id']; ?>">
                        <label for="file_laporan_<?php echo $modul['id']; ?>" class="block text-sm font-medium text-gray-700"><?php echo !empty($modul['file_laporan']) ? 'Kumpul Ulang (File Lama Akan Diganti)' : 'Pilih File Laporan'; ?></label>
                        <div class="mt-1 flex items-center">
                            <input type="file" name="file_laporan" id="file_laporan_<?php echo $modul['id']; ?>" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
                            <button type="submit" name="kumpul_laporan" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-center text-gray-500">Belum ada modul untuk praktikum ini.</p>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer.php';
$conn->close();
?>
