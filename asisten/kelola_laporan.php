<?php
// asisten/kelola_laporan.php

// Panggil Header dan Konfigurasi
$pageTitle = 'Laporan Masuk';
// BENAR
require_once 'templates/header.php';
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// Logika untuk filter laporan
$where_clauses = [];
$filter_praktikum = $_GET['filter_praktikum'] ?? '';
$filter_mahasiswa = $_GET['filter_mahasiswa'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

if (!empty($filter_praktikum)) {
    $where_clauses[] = "mp.id = " . intval($filter_praktikum);
}
if (!empty($filter_mahasiswa)) {
    $where_clauses[] = "u.id = " . intval($filter_mahasiswa);
}
if ($filter_status === 'dinilai') {
    $where_clauses[] = "l.nilai IS NOT NULL";
}
if ($filter_status === 'belum_dinilai') {
    $where_clauses[] = "l.nilai IS NULL";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Query utama untuk mengambil semua data laporan
$sql = "SELECT 
            l.id as id_laporan,
            l.tanggal_kumpul,
            l.nilai,
            u.nama as nama_mahasiswa,
            m.nama_modul,
            mp.nama_praktikum
        FROM laporan l
        JOIN users u ON l.id_mahasiswa = u.id
        JOIN modul m ON l.id_modul = m.id
        JOIN mata_praktikum mp ON m.id_praktikum = mp.id
        $where_sql
        ORDER BY l.tanggal_kumpul DESC";

$result_laporan = $conn->query($sql);

// Ambil data untuk dropdown filter
$result_praktikum_filter = $conn->query("SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC");
$result_mahasiswa_filter = $conn->query("SELECT id, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC");
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    :root {
        --primary-color: #4f46e5; --primary-hover: #4338ca;
        --info-color: #3b82f6; --info-hover: #2563eb;
        --success-bg: #dcfce7; --success-text: #166534;
        --pending-bg: #fef3c7; --pending-text: #92400e;
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
    .filter-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; align-items: end; }
    @media(min-width: 768px) { .filter-grid { grid-template-columns: repeat(4, 1fr); } }
    .form-group { display: flex; flex-direction: column; }
    .form-label {
        font-size: 0.875rem; font-weight: 500;
        color: var(--text-primary); margin-bottom: 0.5rem;
    }
    .form-select {
        width: 100%; padding: 0.625rem 0.75rem;
        border: 1px solid var(--border-color); border-radius: 0.375rem;
        box-shadow: var(--shadow);
    }
    .btn {
        display: inline-flex; width: 100%; justify-content: center;
        padding: 0.625rem 1.25rem; border-radius: 0.375rem;
        font-weight: 600; font-size: 0.875rem; border: 1px solid transparent;
        cursor: pointer; transition: background-color 0.2s; text-decoration: none;
    }
    .btn-primary { background-color: var(--primary-color); color: white; }
    .btn-primary:hover { background-color: var(--primary-hover); }
    .btn-info { background-color: var(--info-color); color: white; width: auto; }
    .btn-info:hover { background-color: var(--info-hover); }
    .table-wrapper { overflow-x: auto; }
    .table { width: 100%; border-collapse: collapse; text-align: left; }
    .table th {
        padding: 0.75rem 1.5rem; font-size: 0.75rem;
        text-transform: uppercase; letter-spacing: 0.05em;
        font-weight: 600; color: var(--text-secondary);
        background-color: #f9fafb;
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
    .badge-success { background-color: var(--success-bg); color: var(--success-text); }
    .badge-pending { background-color: var(--pending-bg); color: var(--pending-text); }
</style>

<div class="main-content">
    <div class="card">
        <h3 class="card-header">Filter Laporan</h3>
        <form action="kelola_laporan.php" method="GET" class="filter-grid">
            <div class="form-group">
                <label for="filter_praktikum" class="form-label">Praktikum</label>
                <select name="filter_praktikum" id="filter_praktikum" class="form-select">
                    <option value="">Semua Praktikum</option>
                    <?php mysqli_data_seek($result_praktikum_filter, 0); while($p = $result_praktikum_filter->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>" <?php if($filter_praktikum == $p['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($p['nama_praktikum']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="filter_mahasiswa" class="form-label">Mahasiswa</label>
                <select name="filter_mahasiswa" id="filter_mahasiswa" class="form-select">
                    <option value="">Semua Mahasiswa</option>
                    <?php mysqli_data_seek($result_mahasiswa_filter, 0); while($m = $result_mahasiswa_filter->fetch_assoc()): ?>
                    <option value="<?php echo $m['id']; ?>" <?php if($filter_mahasiswa == $m['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($m['nama']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="filter_status" class="form-label">Status</label>
                <select name="filter_status" id="filter_status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="dinilai" <?php if($filter_status == 'dinilai') echo 'selected'; ?>>Sudah Dinilai</option>
                    <option value="belum_dinilai" <?php if($filter_status == 'belum_dinilai') echo 'selected'; ?>>Belum Dinilai</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 class="card-header">Daftar Laporan Masuk</h3>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Mahasiswa</th>
                        <th>Praktikum & Modul</th>
                        <th>Tgl Kumpul</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_laporan && $result_laporan->num_rows > 0): ?>
                        <?php while($row = $result_laporan->fetch_assoc()): ?>
                        <tr>
                            <td class="font-semibold"><?php echo htmlspecialchars($row['nama_mahasiswa']); ?></td>
                            <td>
                                <div class="font-semibold"><?php echo htmlspecialchars($row['nama_praktikum']); ?></div>
                                <div><?php echo htmlspecialchars($row['nama_modul']); ?></div>
                            </td>
                            <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_kumpul'])); ?></td>
                            <td class="text-center">
                                <?php if (is_null($row['nilai'])): ?>
                                    <span class="badge badge-pending">Belum Dinilai</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Dinilai (<?php echo $row['nilai']; ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="beri_nilai.php?id_laporan=<?php echo $row['id_laporan']; ?>" class="btn btn-info">
                                    <?php echo is_null($row['nilai']) ? 'Beri Nilai' : 'Lihat/Ubah'; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 2rem;">Tidak ada laporan yang ditemukan.</td>
                        </tr>
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