<?php
// asisten/modul.php

// 1. Panggil Header dan Konfigurasi
require_once '../templates/header.php';
require_once '../config.php';

// 2. Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// 3. Ambil ID Praktikum dari URL, jika tidak ada, kembalikan ke halaman sebelumnya
if (!isset($_GET['id_praktikum']) || empty($_GET['id_praktikum'])) {
    header('Location: matkul.php');
    exit;
}
$id_praktikum = $_GET['id_praktikum'];

// Ambil nama praktikum untuk ditampilkan di judul
$stmt_praktikum = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_praktikum->bind_param("i", $id_praktikum);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();
if ($result_praktikum->num_rows === 0) {
    header('Location: matkul.php');
    exit;
}
$praktikum = $result_praktikum->fetch_assoc();
$pageTitle = 'Kelola Modul: ' . htmlspecialchars($praktikum['nama_praktikum']);
$stmt_praktikum->close();


// Inisialisasi variabel
$pesan = '';
$upload_dir = '../uploads/materi/'; // Pastikan folder ini ada dan bisa ditulis (writable)

// 4. Logika untuk DELETE
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    
    // Ambil nama file yang akan dihapus dari server
    $stmt_file = $conn->prepare("SELECT file_materi FROM modul WHERE id = ?");
    $stmt_file->bind_param("i", $id_hapus);
    $stmt_file->execute();
    $result_file = $stmt_file->get_result()->fetch_assoc();
    if ($result_file && !empty($result_file['file_materi'])) {
        if (file_exists($upload_dir . $result_file['file_materi'])) {
            unlink($upload_dir . $result_file['file_materi']);
        }
    }
    $stmt_file->close();

    // Hapus data dari database
    $stmt_hapus = $conn->prepare("DELETE FROM modul WHERE id = ?");
    $stmt_hapus->bind_param("i", $id_hapus);
    if ($stmt_hapus->execute()) {
        $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Modul berhasil dihapus.</div>";
    } else {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menghapus modul.</div>";
    }
    $stmt_hapus->close();
}

// 5. Logika untuk PROSES FORM (CREATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_modul'])) {
    $nama_modul = $_POST['nama_modul'];
    $file_materi_name = '';

    // Proses upload file jika ada
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0) {
        $file_materi = $_FILES['file_materi'];
        $file_materi_name = time() . '_' . basename($file_materi['name']);
        
        if (!move_uploaded_file($file_materi['tmp_name'], $upload_dir . $file_materi_name)) {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal mengunggah file.</div>";
            $file_materi_name = ''; 
        }
    }

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO modul (id_praktikum, nama_modul, file_materi) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $id_praktikum, $nama_modul, $file_materi_name);
    if ($stmt->execute()) {
        $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Modul berhasil ditambahkan.</div>";
    } else {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menambahkan modul.</div>";
    }
    $stmt->close();
}


// 6. Logika untuk MEMBACA SEMUA DATA (READ)
$result_modul = $conn->query("SELECT * FROM modul WHERE id_praktikum = $id_praktikum ORDER BY created_at ASC");

?>

<!-- Tampilkan pesan sukses/gagal jika ada -->
<?php if (!empty($pesan)) { echo $pesan; } ?>

<a href="matkul.php" class="mb-4 inline-block text-indigo-600 hover:text-indigo-900">&larr; Kembali ke Daftar Praktikum</a>

<!-- Form untuk menambah modul -->
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

<!-- Tabel untuk menampilkan data modul -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Daftar Modul</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Modul</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File Materi</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result_modul && $result_modul->num_rows > 0): ?>
                    <?php while($row = $result_modul->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_modul']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if (!empty($row['file_materi'])): ?>
                                <a href="<?php echo $upload_dir . htmlspecialchars($row['file_materi']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                    <?php echo htmlspecialchars($row['file_materi']); ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="modul.php?id_praktikum=<?php echo $id_praktikum; ?>&hapus_id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini?');">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada modul yang ditambahkan untuk praktikum ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// 7. Panggil Footer
require_once '../templates/footer.php';
$conn->close();
?>
