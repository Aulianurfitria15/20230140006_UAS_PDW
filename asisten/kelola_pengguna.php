<?php
// asisten/kelola_pengguna.php

// 1. Panggil Header dan Konfigurasi
$pageTitle = 'Kelola Akun Pengguna';
require_once 'templates/header.php';
require_once '../config.php';

// 2. Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// Inisialisasi variabel
$pesan = '';

// 3. Logika untuk DELETE
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    // Melindungi agar tidak bisa menghapus diri sendiri
    if ($id_hapus == $_SESSION['user_id']) {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Anda tidak dapat menghapus akun Anda sendiri.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_hapus);
        if ($stmt->execute()) {
            $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Pengguna berhasil dihapus.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menghapus pengguna.</div>";
        }
        $stmt->close();
    }
}

// 4. Logika untuk PROSES FORM (CREATE)
// Untuk kesederhanaan, halaman ini hanya untuk Create, Read, Delete. Update bisa jadi sangat kompleks (ganti password, dll).
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_pengguna'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Cek apakah email sudah ada
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Email sudah terdaftar.</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt_insert = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);
        if ($stmt_insert->execute()) {
            $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Pengguna baru berhasil ditambahkan.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal menambahkan pengguna.</div>";
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
}

// 5. Logika untuk MEMBACA SEMUA DATA (READ)
$result_users = $conn->query("SELECT id, nama, email, role FROM users ORDER BY role, nama ASC");

?>

<!-- Tampilkan pesan sukses/gagal jika ada -->
<?php if (!empty($pesan)) { echo $pesan; } ?>

<!-- Form untuk menambah pengguna -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Tambah Pengguna Baru</h3>
    <form action="kelola_pengguna.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="nama" id="nama" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div class="self-end">
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role" id="role" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="mahasiswa">Mahasiswa</option>
                <option value="asisten">Asisten</option>
            </select>
        </div>
        <div class="col-span-full">
            <button type="submit" name="tambah_pengguna" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Tambah Pengguna</button>
        </div>
    </form>
</div>

<!-- Tabel untuk menampilkan data pengguna -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Daftar Semua Pengguna</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result_users && $result_users->num_rows > 0): ?>
                    <?php while($user = $result_users->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nama']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] == 'asisten' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <!-- Tombol Edit bisa ditambahkan di sini -->
                            <a href="kelola_pengguna.php?hapus_id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// 6. Panggil Footer
require_once 'templates/footer.php';
$conn->close();
?>
