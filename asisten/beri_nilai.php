<?php
// asisten/beri_nilai.php

// Panggil Header dan Konfigurasi
require_once 'templates/header.php';
require_once '../config.php';

// Pastikan hanya asisten yang bisa mengakses
if ($_SESSION['role'] !== 'asisten') {
    header('Location: ../mahasiswa/dashboard.php');
    exit;
}

// Ambil ID Laporan dari URL
if (!isset($_GET['id_laporan']) || empty($_GET['id_laporan'])) {
    header('Location: kelola_laporan.php');
    exit;
}
$id_laporan = $_GET['id_laporan'];
$pesan = '';
$upload_dir = '../uploads/laporan/';

// Logika untuk PROSES FORM (UPDATE NILAI)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nilai = $_POST['nilai'];
    $feedback = $_POST['feedback'];

    $stmt = $conn->prepare("UPDATE laporan SET nilai = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("isi", $nilai, $feedback, $id_laporan);
    
    if ($stmt->execute()) {
        $pesan = "Nilai berhasil disimpan.";
    } else {
        $pesan = "Gagal menyimpan nilai."; // Nanti bisa ganti jenis alert
    }
    $stmt->close();
}

// Query untuk mengambil detail laporan yang akan dinilai
$sql = "SELECT 
            l.id, l.file_laporan, l.nilai, l.feedback,
            u.nama as nama_mahasiswa,
            m.nama_modul,
            mp.nama_praktikum
        FROM laporan l
        JOIN users u ON l.id_mahasiswa = u.id
        JOIN modul m ON l.id_modul = m.id
        JOIN mata_praktikum mp ON m.id_praktikum = mp.id
        WHERE l.id = ?";
$stmt_laporan = $conn->prepare($sql);
$stmt_laporan->bind_param("i", $id_laporan);
$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();

if ($result_laporan->num_rows === 0) {
    header('Location: kelola_laporan.php');
    exit;
}
$laporan = $result_laporan->fetch_assoc();
$pageTitle = 'Beri Nilai: ' . htmlspecialchars($laporan['nama_mahasiswa']);
$stmt_laporan->close();
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    :root {
        --primary-color: #4f46e5; --primary-hover: #4338ca;
        --success-bg: #dcfce7; --success-text: #166534;
        --text-primary: #1f2937; --text-secondary: #6b7280;
        --border-color: #e5e7eb; --card-bg: #ffffff;
        --shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1);
        --border-radius: 0.5rem;
    }
    .main-content { font-family: 'Inter', sans-serif; }
    .link-back {
        display: inline-block; margin-bottom: 1.5rem; color: var(--primary-color);
        font-weight: 500; text-decoration: none;
    }
    .link-back:hover { text-decoration: underline; }
    .alert {
        padding: 1rem; margin-bottom: 1.5rem; border-radius: var(--border-radius);
        background-color: var(--success-bg); color: var(--success-text); font-size: 0.875rem;
    }
    .main-grid { display: grid; grid-template-columns: 1fr; gap: 2rem; }
    @media(min-width: 768px) { .main-grid { grid-template-columns: 1fr 2fr; } }
    .card {
        background-color: var(--card-bg); padding: 1.5rem;
        border-radius: var(--border-radius); box-shadow: var(--shadow);
    }
    .card-header {
        font-size: 1.25rem; font-weight: 700; color: var(--text-primary);
        margin-bottom: 1.5rem; padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .details-list dt {
        font-size: 0.875rem; font-weight: 500;
        color: var(--text-secondary); margin-bottom: 0.25rem;
    }
    .details-list dd {
        font-size: 1rem; color: var(--text-primary);
        font-weight: 500; margin: 0 0 1.25rem 0;
    }
    .details-list dd a { color: var(--primary-color); }
    .form-group { margin-bottom: 1.5rem; }
    .form-label {
        display: block; font-size: 0.875rem; font-weight: 500;
        color: var(--text-primary); margin-bottom: 0.5rem;
    }
    .form-input, .form-textarea {
        width: 100%; padding: 0.625rem 0.75rem;
        border: 1px solid var(--border-color); border-radius: 0.375rem;
        box-shadow: var(--shadow);
    }
    .form-input:focus, .form-textarea:focus {
        outline: none; border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }
    .form-textarea { resize: vertical; min-height: 120px; }
    .btn {
        display: inline-flex; padding: 0.625rem 1.5rem; border-radius: 0.375rem;
        font-weight: 600; font-size: 0.875rem; border: 1px solid transparent;
        cursor: pointer; transition: background-color 0.2s; text-decoration: none;
    }
    .btn-primary { background-color: var(--primary-color); color: white; }
    .btn-primary:hover { background-color: var(--primary-hover); }
</style>

<div class="main-content">
    <a href="kelola_laporan.php" class="link-back">&larr; Kembali ke Daftar Laporan</a>

    <?php if (!empty($pesan)) : ?>
        <div class="alert"><?php echo $pesan; ?></div>
    <?php endif; ?>

    <div class="main-grid">
        <div class="card">
            <h3 class="card-header">Detail Laporan</h3>
            <dl class="details-list">
                <div>
                    <dt>Nama Mahasiswa</dt>
                    <dd><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?></dd>
                </div>
                <div>
                    <dt>Mata Praktikum</dt>
                    <dd><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></dd>
                </div>
                <div>
                    <dt>Modul</dt>
                    <dd><?php echo htmlspecialchars($laporan['nama_modul']); ?></dd>
                </div>
                <div>
                    <dt>File Laporan</dt>
                    <dd>
                        <?php if (!empty($laporan['file_laporan']) && file_exists($upload_dir . $laporan['file_laporan'])): ?>
                            <a href="<?php echo $upload_dir . htmlspecialchars($laporan['file_laporan']); ?>" target="_blank">
                                Unduh & Lihat Laporan
                            </a>
                        <?php else: ?>
                            File tidak ditemukan.
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <h3 class="card-header">Form Penilaian</h3>
            <form action="beri_nilai.php?id_laporan=<?php echo $id_laporan; ?>" method="POST">
                <div class="form-group">
                    <label for="nilai" class="form-label">Nilai (Angka 0-100)</label>
                    <input type="number" id="nilai" name="nilai" min="0" max="100" value="<?php echo htmlspecialchars($laporan['nilai']); ?>" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="feedback" class="form-label">Feedback (Opsional)</label>
                    <textarea id="feedback" name="feedback" rows="5" class="form-textarea"><?php echo htmlspecialchars($laporan['feedback']); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    Simpan Nilai
                </button>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
$conn->close();
?>