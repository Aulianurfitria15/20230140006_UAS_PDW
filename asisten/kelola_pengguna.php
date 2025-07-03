<?php
// asisten/kelola_pengguna.php

// Panggil Header dan Konfigurasi
$pageTitle = 'Kelola Akun Pengguna';
require_once 'templates/header.php';
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// Inisialisasi variabel
$pesan = '';
$is_edit_mode = false;
$user_to_edit = ['id' => '', 'nama' => '', 'email' => '', 'role' => 'mahasiswa'];

// LOGIKA UPDATE & CREATE (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // PROSES UPDATE
    if (isset($_POST['id_update'])) {
        $id_update = $_POST['id_update'];
        $password = $_POST['password'];

        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $id_update);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Email sudah digunakan oleh pengguna lain.</div>";
            // Isi kembali data yang sedang diedit jika gagal
            $user_to_edit = ['id' => $id_update, 'nama' => $nama, 'email' => $email, 'role' => $role];
            $is_edit_mode = true;
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nama, $email, $role, $hashed_password, $id_update);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nama, $email, $role, $id_update);
            }
            if ($stmt->execute()) {
                $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Data pengguna berhasil diperbarui.</div>";
            } else {
                $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4'>Gagal memperbarui data.</div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
    // PROSES CREATE
    else {
        $password = $_POST['password'];
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
}

// LOGIKA DELETE (GET)
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    if ($id_hapus == $_SESSION['user_id']) {
        $pesan = "<div class='bg-yellow-100 text-yellow-700 p-3 rounded-lg mb-4'>Anda tidak dapat menghapus akun Anda sendiri.</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_hapus);
        $stmt->execute();
        $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4'>Pengguna berhasil dihapus.</div>";
        $stmt->close();
    }
}

// LOGIKA UNTUK MASUK MODE EDIT (GET)
if (isset($_GET['edit_id'])) {
    $is_edit_mode = true;
    $id_edit = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT id, nama, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// MEMBACA SEMUA DATA PENGGUNA (READ)
$result_users = $conn->query("SELECT id, nama, email, role FROM users ORDER BY role, nama ASC");
?>

<?php if (!empty($pesan)) { echo $pesan; } ?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">
        <?php echo $is_edit_mode ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru'; ?>
    </h3>
    <form action="kelola_pengguna.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
        
        <?php if ($is_edit_mode): ?>
            <input type="hidden" name="id_update" value="<?php echo $user_to_edit['id']; ?>">
        <?php endif; ?>

        <div>
            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($user_to_edit['nama']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" 
                   placeholder="<?php echo $is_edit_mode ? 'Kosongkan jika tidak ganti' : ''; ?>" 
                   <?php echo !$is_edit_mode ? 'required' : ''; ?>>
        </div>
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role" id="role" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="mahasiswa" <?php echo ($user_to_edit['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($user_to_edit['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>
        <div class="col-span-full flex items-center">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border shadow-sm text-sm font-medium rounded-md text-white <?php echo $is_edit_mode ? 'bg-blue-600 hover:bg-blue-700' : 'bg-indigo-600 hover:bg-indigo-700'; ?>">
                <?php echo $is_edit_mode ? 'Update Pengguna' : 'Tambah Pengguna'; ?>
            </button>
            <?php if ($is_edit_mode): ?>
                <a href="kelola_pengguna.php" class="ml-3 text-sm text-gray-600 hover:text-gray-900">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

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
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['nama']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] == 'asisten' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-medium">
                            <a href="kelola_pengguna.php?edit_id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
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
require_once 'templates/footer.php';
$conn->close();
?>