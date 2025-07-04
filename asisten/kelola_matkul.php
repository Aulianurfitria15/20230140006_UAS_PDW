<?php
// asisten/kelola_matkul.php

// 1. Set Judul Halaman
$pageTitle = 'Kelola Mata Praktikum';

// 2. Panggil Header dan Konfigurasi
// Pastikan header.php tidak memuat file CSS lain yang bisa konflik
require_once 'templates/header.php'; 
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
$alert_type = '';

// 4. Logika untuk DELETE
if (isset($_GET['hapus_id'])) {
    $id_hapus = $_GET['hapus_id'];
    $stmt = $conn->prepare("DELETE FROM mata_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id_hapus);
    if ($stmt->execute()) {
        $pesan = "Data berhasil dihapus.";
        $alert_type = 'success';
    } else {
        $pesan = "Gagal menghapus data. Kemungkinan terkait dengan data lain.";
        $alert_type = 'danger';
    }
    $stmt->close();
}

// 5. Logika untuk PROSES FORM (CREATE & UPDATE)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_praktikum = $_POST['nama_praktikum'];
    $deskripsi = $_POST['deskripsi'];

    if (isset($_POST['id_update'])) {
        $id_update = $_POST['id_update'];
        $stmt = $conn->prepare("UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $id_update);
        if ($stmt->execute()) {
            $pesan = "Data berhasil diperbarui.";
            $alert_type = 'success';
        } else {
            $pesan = "Gagal memperbarui data.";
            $alert_type = 'danger';
        }
    } 
    else {
        $id_asisten = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO mata_praktikum (nama_praktikum, deskripsi, id_asisten_pembuat) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $id_asisten);
        if ($stmt->execute()) {
            $pesan = "Praktikum berhasil ditambahkan.";
            $alert_type = 'success';
        } else {
            $pesan = "Gagal menambahkan praktikum.";
            $alert_type = 'danger';
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

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    :root {
        --primary-color: #4f46e5; --primary-hover: #4338ca;
        --secondary-color: #10b981; --secondary-hover: #059669;
        --danger-color: #ef4444; --danger-hover: #dc2626;
        --info-color: #3b82f6; --info-hover: #2563eb;
        --success-bg: #dcfce7; --success-text: #166534;
        --danger-bg: #fee2e2; --danger-text: #991b1b;
        --text-primary: #1f2937; --text-secondary: #6b7280;
        --border-color: #e5e7eb; --card-bg: #ffffff;
        --shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1);
        --border-radius: 0.5rem;
    }
    .main-content { font-family: 'Inter', sans-serif; } /* Ganti dengan class container utama Anda */
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
    .form-group { margin-bottom: 1.5rem; }
    .form-label {
        display: block; font-size: 0.875rem; font-weight: 500;
        color: var(--text-primary); margin-bottom: 0.5rem;
    }
    .form-input, .form-textarea {
        width: 100%; padding: 0.625rem 0.75rem;
        border: 1px solid var(--border-color); border-radius: 0.375rem;
        box-shadow: var(--shadow); transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-input:focus, .form-textarea:focus {
        outline: none; border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }
    .form-textarea { resize: vertical; min-height: 80px; }
    .btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 0.625rem 1.25rem; border-radius: 0.375rem;
        font-weight: 600; font-size: 0.875rem; border: 1px solid transparent;
        cursor: pointer; transition: background-color 0.2s; text-decoration: none;
    }
    .btn:hover { text-decoration: none; }
    .btn-primary { background-color: var(--primary-color); color: white; }
    .btn-primary:hover { background-color: var(--primary-hover); }
    .btn-secondary { background-color: var(--secondary-color); color: white; }
    .btn-secondary:hover { background-color: var(--secondary-hover); }
    .btn-info { background-color: var(--info-color); color: white; }
    .btn-info:hover { background-color: var(--info-hover); }
    .btn-danger { background-color: var(--danger-color); color: white; }
    .btn-danger:hover { background-color: var(--danger-hover); }
    .btn-outline { background-color: transparent; color: var(--text-secondary); border-color: var(--border-color); }
    .btn-outline:hover { background-color: #f9fafb; }
    .btn-group { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .table-wrapper { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; text-align: left; }
    .table thead { background-color: #f9fafb; border-bottom: 1px solid var(--border-color); }
    .table th {
        padding: 0.75rem 1.5rem; font-size: 0.75rem;
        text-transform: uppercase; letter-spacing: 0.05em;
        font-weight: 600; color: var(--text-secondary);
    }
    .table td {
        padding: 1rem 1.5rem; font-size: 0.875rem;
        color: var(--text-secondary); vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
    }
    .table tbody tr:last-child td { border-bottom: none; }
    .table td .font-semibold { font-weight: 600; color: var(--text-primary); }
    .table .text-center { text-align: center; }
</style>

<div class="main-content"> <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?php echo $alert_type; ?>"><?php echo htmlspecialchars($pesan); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 class="card-header">
            <?php echo $id_edit ? 'Edit Mata Praktikum' : 'Tambah Mata Praktikum Baru'; ?>
        </h3>
        <form action="kelola_matkul.php" method="POST">
            <?php if ($id_edit): ?>
                <input type="hidden" name="id_update" value="<?php echo $id_edit; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nama_praktikum" class="form-label">Nama Praktikum</label>
                <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($nama_edit); ?>" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="deskripsi" class="form-label">Deskripsi Singkat</label>
                <textarea id="deskripsi" name="deskripsi" class="form-textarea"><?php echo htmlspecialchars($deskripsi_edit); ?></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <?php echo $id_edit ? 'Update Praktikum' : 'Simpan Praktikum'; ?>
                </button>
                <?php if ($id_edit): ?>
                    <a href="kelola_matkul.php" class="btn btn-outline">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 class="card-header">Daftar Mata Praktikum</h3>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Praktikum</th>
                        <th>Deskripsi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="font-semibold"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 100)) . (strlen($row['deskripsi']) > 100 ? '...' : ''); ?></td>
                            <td class="text-center">
                                <div class="btn-group" style="justify-content: center;">
                                    <a href="kelola_modul.php?id_praktikum=<?php echo $row['id']; ?>" class="btn btn-secondary">Kelola Modul</a>
                                    <a href="kelola_matkul.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-info">Edit</a>
                                    <a href="kelola_matkul.php?hapus_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini? Semua modul terkait akan terhapus.');">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">Belum ada mata praktikum yang ditambahkan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// 8. Panggil Footer
require_once 'templates/footer.php';
$conn->close();
?>