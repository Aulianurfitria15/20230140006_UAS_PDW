<?php
// asisten/edit_pengguna.php

$pageTitle = 'Edit Pengguna';
require_once '../templates/header.php';
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// Cek apakah ID pengguna ada di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: kelola_pengguna.php');
    exit;
}
$user_id = $_GET['id'];

$pesan_error = '';

// Proses form saat disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    // Cek apakah email sudah digunakan oleh user lain
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check->bind_param("si", $email, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $pesan_error = "Email sudah digunakan oleh pengguna lain.";
    } else {
        // Jika password diisi, hash password baru. Jika tidak, jangan update password.
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nama, $email, $role, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nama, $email, $role, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['pesan_sukses'] = "Data pengguna berhasil diperbarui.";
            header("Location: kelola_pengguna.php");
            exit;
        } else {
            $pesan_error = "Gagal memperbarui data pengguna.";
        }
        $stmt->close();
    }
    $stmt_check->close();
}

// Ambil data pengguna yang akan diedit
$stmt_user = $conn->prepare("SELECT nama, email, role FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result = $stmt_user->get_result();
if ($result->num_rows === 0) {
    header('Location: kelola_pengguna.php');
    exit;
}
$user = $result->fetch_assoc();
$stmt_user->close();
?>

<?php if (!empty($pesan_error)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<a href="kelola_pengguna.php" class="mb-4 inline-block text-indigo-600 hover:text-indigo-900">&larr; Kembali ke Daftar Pengguna</a>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Edit Data Pengguna</h3>
    <form action="edit_pengguna.php?id=<?php echo $user_id; ?>" method="POST">
        <div class="mb-4">
            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
            <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">Password Baru (Opsional)</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Kosongkan jika tidak ingin ganti">
        </div>
        <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
            <select name="role" id="role" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="mahasiswa" <?php echo ($user['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($user['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>
        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Update Pengguna</button>
    </form>
</div>

<?php
require_once '../templates/footer.php';
$conn->close();
?>