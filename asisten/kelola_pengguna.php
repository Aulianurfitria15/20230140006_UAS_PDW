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
$alert_type = '';
$is_edit_mode = false;
$user_to_edit = ['id' => '', 'nama' => '', 'email' => '', 'role' => 'mahasiswa'];

// LOGIKA UPDATE & CREATE (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';

    // PROSES UPDATE
    if (isset($_POST['id_update'])) {
        $id_update = $_POST['id_update'];
        
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $id_update);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $pesan = "Email sudah digunakan oleh pengguna lain.";
            $alert_type = 'danger';
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
                $pesan = "Data pengguna berhasil diperbarui.";
                $alert_type = 'success';
            } else {
                $pesan = "Gagal memperbarui data.";
                $alert_type = 'danger';
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
    // PROSES CREATE
    else {
        // Cek email dulu
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();
        if($stmt_check->num_rows > 0) {
            $pesan = "Email sudah terdaftar. Gunakan email lain.";
            $alert_type = 'danger';
        } elseif (empty($password)) {
            $pesan = "Password wajib diisi untuk pengguna baru.";
            $alert_type = 'danger';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt_insert = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $nama, $email, $hashed_password, $role);
            if ($stmt_insert->execute()) {
                $pesan = "Pengguna baru berhasil ditambahkan.";
                $alert_type = 'success';
            } else {
                $pesan = "Gagal menambahkan pengguna.";
                $alert_type = 'danger';
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}


// LOGIKA DELETE (GET)
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    if ($id_hapus == $_SESSION['user_id']) {
        $pesan = "Anda tidak dapat menghapus akun Anda sendiri.";
        $alert_type = 'warning';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_hapus);
        $stmt->execute();
        $pesan = "Pengguna berhasil dihapus.";
        $alert_type = 'success';
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
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    :root {
        --primary-color: #4f46e5; --primary-hover: #4338ca;
        --danger-color: #ef4444; --danger-hover: #dc2626;
        --info-color: #3b82f6; --info-hover: #2563eb;
        --success-bg: #dcfce7; --success-text: #166534;
        --danger-bg: #fee2e2; --danger-text: #991b1b;
        --warning-bg: #fef9c3; --warning-text: #a16207;
        --text-primary: #1f2937; --text-secondary: #6b7280;
        --border-color: #e5e7eb; --card-bg: #ffffff;
        --shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1);
        --border-radius: 0.5rem;
    }
    .main-content { font-family: 'Inter', sans-serif; }
    .card {
        background-color: var(--card-bg); padding: 1.5rem;
        border-radius: var(--border-radius); box-shadow: var(--shadow);
        margin-bottom: 2rem;
    }
    .card-header {
        font-size: 1.25rem; font-weight: 700; color: var(--text-primary);
        margin-bottom: 1.5rem; padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .alert {
        padding: 1rem; margin-bottom: 1.5rem;
        border-radius: var(--border-radius); font-size: 0.875rem;
    }
    .alert-success { background-color: var(--success-bg); color: var(--success-text); }
    .alert-danger { background-color: var(--danger-bg); color: var(--danger-text); }
    .alert-warning { background-color: var(--warning-bg); color: var(--warning-text); }
    .form-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; }
    @media(min-width: 1024px) { .form-grid { grid-template-columns: repeat(3, 1fr); } }
    .form-group { display: flex; flex-direction: column; }
    .form-group.col-span-full { grid-column: 1 / -1; }
    .form-label {
        font-size: 0.875rem; font-weight: 500;
        color: var(--text-primary); margin-bottom: 0.5rem;
    }
    .form-input, .form-select {
        width: 100%; padding: 0.625rem 0.75rem;
        border: 1px solid var(--border-color); border-radius: 0.375rem;
        box-shadow: var(--shadow);
    }
    .form-input:focus, .form-select:focus {
        outline: none; border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }
    .btn {
        display: inline-flex; padding: 0.625rem 1.25rem; border-radius: 0.375rem;
        font-weight: 600; font-size: 0.875rem; border: 1px solid transparent;
        cursor: pointer; transition: background-color 0.2s; text-decoration: none;
    }
    .btn:hover { text-decoration: none; }
    .btn-primary { background-color: var(--primary-color); color: white; }
    .btn-primary:hover { background-color: var(--primary-hover); }
    .btn-info { background-color: var(--info-color); color: white; }
    .btn-info:hover { background-color: var(--info-hover); }
    .btn-danger { background-color: var(--danger-color); color: white; }
    .btn-danger:hover { background-color: var(--danger-hover); }
    .btn-outline { background-color: transparent; color: var(--text-secondary); border-color: var(--border-color); }
    .btn-outline:hover { background-color: #f9fafb; }
    .btn-group { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .table-wrapper { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; text-align: left; }
    .table th {
        padding: 0.75rem 1.5rem; font-size: 0.75rem; text-transform: uppercase; 
        font-weight: 600; color: var(--text-secondary); background-color: #f9fafb;
    }
    .table td {
        padding: 1rem 1.5rem; font-size: 0.875rem;
        color: var(--text-secondary); vertical-align: middle;
        border-top: 1px solid var(--border-color);
    }
    .table td .font-semibold { font-weight: 600; color: var(--text-primary); }
    .table .text-center { text-align: center; }
    .badge {
        display: inline-flex; padding: 0.25rem 0.75rem; font-size: 0.75rem;
        font-weight: 600; line-height: 1.25; border-radius: 9999px;
    }
    .badge-asisten { background-color: #dbeafe; color: #1e40af; }
    .badge-mahasiswa { background-color: #dcfce7; color: #166534; }
</style>

<div class="main-content">

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?php echo $alert_type; ?>"><?php echo htmlspecialchars($pesan); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 class="card-header">
            <?php echo $is_edit_mode ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru'; ?>
        </h3>
        <form action="kelola_pengguna.php" method="POST" class="form-grid" autocomplete="off">
            
            <?php if ($is_edit_mode): ?>
                <input type="hidden" name="id_update" value="<?php echo $user_to_edit['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nama" class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($user_to_edit['nama']); ?>" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-input" 
                       placeholder="<?php echo $is_edit_mode ? 'Kosongkan jika tidak ganti' : 'Wajib diisi'; ?>" 
                       autocomplete="new-password"
                       <?php echo !$is_edit_mode ? 'required' : ''; ?>>
            </div>
            <div class="form-group" style="grid-column: 1 / 2;"> <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-select">
                    <option value="mahasiswa" <?php echo ($user_to_edit['role'] == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo ($user_to_edit['role'] == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
            </div>
            <div class="form-group col-span-full" style="align-self: flex-end;">
                <div class="btn-group">
                    <button type="submit" class="btn <?php echo $is_edit_mode ? 'btn-info' : 'btn-primary'; ?>">
                        <?php echo $is_edit_mode ? 'Update Pengguna' : 'Tambah Pengguna'; ?>
                    </button>
                    <?php if ($is_edit_mode): ?>
                        <a href="kelola_pengguna.php" class="btn btn-outline">Batal</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 class="card-header">Daftar Semua Pengguna</h3>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_users && $result_users->num_rows > 0): ?>
                        <?php while($user = $result_users->fetch_assoc()): ?>
                        <tr>
                            <td class="font-semibold"><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] == 'asisten' ? 'badge-asisten' : 'badge-mahasiswa'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" style="justify-content: center;">
                                    <a href="kelola_pengguna.php?edit_id=<?php echo $user['id']; ?>" class="btn btn-info">Edit</a>
                                    <a href="kelola_pengguna.php?hapus_id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
require_once 'templates/footer.php';
$conn->close();
?>