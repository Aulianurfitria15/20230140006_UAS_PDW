<?php
// asisten/kelola_modul.php

// Seluruh logika PHP dijalankan SEBELUM output HTML apapun.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

// 1. Validasi Akses dan Parameter URL
// Pastikan hanya asisten yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header('Location: ../login.php');
    exit;
}
// Ambil ID Praktikum dari URL, jika tidak ada, kembalikan
if (!isset($_GET['id_praktikum']) || empty($_GET['id_praktikum'])) {
    header('Location: kelola_matkul.php');
    exit;
}
$id_praktikum = $_GET['id_praktikum'];
$upload_dir = '../uploads/materi/';

// 2. Ambil data praktikum untuk judul halaman
$stmt_praktikum = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_praktikum->bind_param("i", $id_praktikum);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();
if ($result_praktikum->num_rows === 0) {
    header('Location: kelola_matkul.php');
    exit;
}
$praktikum = $result_praktikum->fetch_assoc();
$pageTitle = 'Kelola Modul: ' . htmlspecialchars($praktikum['nama_praktikum']);
$stmt_praktikum->close();

// Inisialisasi variabel
$pesan = '';
$is_edit = false;
$edit_data = ['id' => '', 'nama_modul' => '', 'file_materi' => ''];

// 3. Logika Proses Form (Create & Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_modul = $_POST['nama_modul'];

    // Proses UPDATE (Edit)
    if (isset($_POST['id_update'])) {
        $id_update = $_POST['id_update'];
        $stmt_get_old_file = $conn->prepare("SELECT file_materi FROM modul WHERE id = ?");
        $stmt_get_old_file->bind_param("i", $id_update);
        $stmt_get_old_file->execute();
        $old_file_name = $stmt_get_old_file->get_result()->fetch_assoc()['file_materi'];
        $stmt_get_old_file->close();
        
        $file_materi_name = $old_file_name;

        // Jika ada file baru diupload, ganti yang lama
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0) {
            // Hapus file lama jika ada
            if (!empty($old_file_name) && file_exists($upload_dir . $old_file_name)) {
                unlink($upload_dir . $old_file_name);
            }
            $file_materi = $_FILES['file_materi'];
            $file_materi_name = time() . '_' . basename($file_materi['name']);
            move_uploaded_file($file_materi['tmp_name'], $upload_dir . $file_materi_name);
        }
        
        $stmt = $conn->prepare("UPDATE modul SET nama_modul = ?, file_materi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama_modul, $file_materi_name, $id_update);
        if ($stmt->execute()) {
            $pesan = "<div class='bg-blue-100 text-blue-700 p-3 rounded-lg mb-4'>Modul berhasil diperbarui.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal memperbarui modul.</div>";
        }
        $stmt->close();
    }
    // Proses CREATE (Tambah)
    else {
        $file_materi_name = '';
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0) {
            $file_materi = $_FILES['file_materi'];
            $file_materi_name = time() . '_' . basename($file_materi['name']);
            move_uploaded_file($file_materi['tmp_name'], $upload_dir . $file_materi_name);
        }
        $stmt = $conn->prepare("INSERT INTO modul (id_praktikum, nama_modul, file_materi) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $id_praktikum, $nama_modul, $file_materi_name);
        if ($stmt->execute()) {
            $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Modul berhasil ditambahkan.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menambahkan modul.</div>";
        }
        $stmt->close();
    }
}

// 4. Logika untuk DELETE
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    $stmt_file = $conn->prepare("SELECT file_materi FROM modul WHERE id = ?");
    $stmt_file->bind_param("i", $id_hapus);
    $stmt_file->execute();
    $result_file = $stmt_file->get_result()->fetch_assoc();
    if ($result_file && !empty($result_file['file_materi']) && file_exists($upload_dir . $result_file['file_materi'])) {
        unlink($upload_dir . $result_file['file_materi']);
    }
    $stmt_file->close();

    $stmt_hapus = $conn->prepare("DELETE FROM modul WHERE id = ?");
    $stmt_hapus->bind_param("i", $id_hapus);
    if ($stmt_hapus->execute()) {
        $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Modul berhasil dihapus.</div>";
    } else {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menghapus modul.</div>";
    }
    $stmt_hapus->close();
}

// 5. Logika untuk mengambil data yang akan di-EDIT
if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $id_edit = $_GET['edit_id'];
    $stmt_edit = $conn->prepare("SELECT id, nama_modul, file_materi FROM modul WHERE id = ?");
    $stmt_edit->bind_param("i", $id_edit);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    if ($result_edit->num_rows > 0) {
        $edit_data = $result_edit->fetch_assoc();
    }
    $stmt_edit->close();
}

// 6. Logika untuk MEMBACA SEMUA DATA (READ)
$result_modul = $conn->query("SELECT * FROM modul WHERE id_praktikum = $id_praktikum ORDER BY created_at ASC");


// =======================================================================
// AKHIR DARI BLOK LOGIKA PHP
// MULAI BLOK TAMPILAN HTML
// =======================================================================

require_once '../templates/header.php';
?>

<?php if (!empty($pesan)) { echo $pesan; } ?>

<a href="kelola_matkul.php" class="mb-4 inline-block text-indigo-600 hover:text-indigo-900">&larr; Kembali ke Daftar Praktikum</a>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4"><?php echo $is_edit ? 'Edit Modul' : 'Tambah Modul Baru'; ?></h3>
    <form action="kelola_modul.php?id_praktikum=<?php echo $id_praktikum; ?>" method="POST" enctype="multipart/form-data">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id_update" value="<?php echo $edit_data['id']; ?>">
        <?php endif; ?>

        <div class="mb-4">
            <label for="nama_modul" class="block text-sm font-medium text-gray-700">Nama Modul</label>
            <input type="text" id="nama_modul" name="nama_modul" value="<?php echo htmlspecialchars($edit_data['nama_modul']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
        </div>
        <div class="mb-4">
            <label for="file_materi" class="block text-sm font-medium text-gray-700">File Materi (PDF, DOCX, dll)</label>
            <?php if ($is_edit && !empty($edit_data['file_materi'])): ?>
                <p class="text-sm text-gray-500 mt-1 mb-2">File saat ini: <a href="<?php echo $upload_dir . htmlspecialchars($edit_data['file_materi']); ?>" target="_blank" class="text-indigo-600"><?php echo htmlspecialchars($edit_data['file_materi']); ?></a></p>
                <label for="file_materi" class="block text-sm font-medium text-gray-700">Upload file baru untuk mengganti (opsional):</label>
            <?php endif; ?>
            <input type="file" id="file_materi" name="file_materi" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>
        
        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white <?php echo $is_edit ? 'bg-blue-600 hover:bg-blue-700' : 'bg-indigo-600 hover:bg-indigo-700'; ?>">
            <?php echo $is_edit ? 'Update Modul' : 'Simpan Modul'; ?>
        </button>
        <?php if ($is_edit): ?>
            <a href="kelola_modul.php?id_praktikum=<?php echo $id_praktikum; ?>" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Batal</a>
        <?php endif; ?>
    </form>
</div>

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
                                    Lihat File
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="kelola_modul.php?id_praktikum=<?php echo $id_praktikum; ?>&edit_id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            <a href="kelola_modul.php?id_praktikum=<?php echo $id_praktikum; ?>&hapus_id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini?');">Hapus</a>
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
require_once '../templates/footer.php';
$conn->close();
?>