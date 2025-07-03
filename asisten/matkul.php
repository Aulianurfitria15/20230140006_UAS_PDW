<?php
// asisten/matkul.php

// 1. Set Judul Halaman
$pageTitle = 'Kelola Mata Praktikum';

// 2. Panggil Header dan Konfigurasi
require_once '../templates/header.php';
require_once '../config.php';

// 3. Pastikan hanya asisten yang bisa mengakses halaman ini
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// Inisialisasi variabel untuk form dan pesan
$id_edit = null;
$nama_edit = '';
$deskripsi_edit = '';
$pesan = '';

// 4. Logika untuk DELETE
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    $stmt = $conn->prepare("DELETE FROM mata_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id_hapus);
    if ($stmt->execute()) {
        $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Data berhasil dihapus.</div>";
    } else {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menghapus data.</div>";
    }
    $stmt->close();
}

// 5. Logika untuk PROSES FORM (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_praktikum = $_POST['nama_praktikum'];
    $deskripsi = $_POST['deskripsi'];

    // Jika ada id_update, berarti ini adalah proses UPDATE
    if (isset($_POST['id_update'])) {
        $id_update = $_POST['id_update'];
        $stmt = $conn->prepare("UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $id_update);
        if ($stmt->execute()) {
            $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Data berhasil diperbarui.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal memperbarui data.</div>";
        }
    } 
    // Jika tidak ada, berarti ini adalah proses CREATE
    else {
        $id_asisten = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO mata_praktikum (nama_praktikum, deskripsi, id_asisten_pembuat) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $id_asisten);
        if ($stmt->execute()) {
            $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Praktikum berhasil ditambahkan.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menambahkan praktikum.</div>";
        }
    }
    $stmt->close();
}

// 6. Logika untuk mengambil data yang akan di-EDIT
if (isset($_GET['edit_id'])) {
    $id_edit = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT nama_praktikum, deskripsi FROM mata_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    if ($result_edit->num_rows > 0) {
        $data_edit = $result_edit->fetch_assoc();
        $nama_edit = $data_edit['nama_praktikum'];
        $deskripsi_edit = $data_edit['deskripsi'];
    }
    $stmt->close();
}

// 7. Logika untuk MEMBACA SEMUA DATA (READ)
$result = $conn->query("SELECT * FROM mata_praktikum ORDER BY id DESC");

?>

<!-- Tampilkan pesan sukses/gagal jika ada -->
<?php if (!empty($pesan)) { echo $pesan; } ?>

<!-- Form untuk menambah/mengedit mata praktikum -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">
        <?php echo $id_edit ? 'Edit Mata Praktikum' : 'Tambah Mata Praktikum Baru'; ?>
    </h3>
    <form action="matkul.php" method="POST">
        <!-- Hidden input untuk ID saat update -->
        <?php if ($id_edit): ?>
            <input type="hidden" name="id_update" value="<?php echo $id_edit; ?>">
        <?php endif; ?>

        <div class="mb-4">
            <label for="nama_praktikum" class="block text-sm font-medium text-gray-700">Nama Praktikum</label>
            <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($nama_edit); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi Singkat</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($deskripsi_edit); ?></textarea>
        </div>
        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <?php echo $id_edit ? 'Update Praktikum' : 'Simpan Praktikum'; ?>
        </button>
        <?php if ($id_edit): ?>
            <a href="matkul.php" class="ml-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Batal</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabel untuk menampilkan data mata praktikum -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Daftar Mata Praktikum</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Praktikum</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <a href="modul.php?id_praktikum=<?php echo $row['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Kelola Modul</a>
                            <a href="matkul.php?edit_id=<?php echo $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <a href="matkul.php?hapus_id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?');">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada mata praktikum yang ditambahkan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// 8. Panggil Footer
require_once '../templates/footer.php';
$conn->close();
?>
