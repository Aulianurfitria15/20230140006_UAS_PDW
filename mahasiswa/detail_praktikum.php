<?php
require_once 'templates/header_mahasiswa.php';
require_once '../config.php';

if ($_SESSION['role'] !== 'mahasiswa') { header('Location: ../asisten/dashboard.php'); exit; }
if (!isset($_GET['id']) || empty($_GET['id'])) { header('Location: my_courses.php'); exit; }

$id_praktikum = $_GET['id'];
$id_mahasiswa = $_SESSION['user_id'];
$pesan = ''; $pesan_tipe = '';
$upload_dir_materi = '../uploads/materi/';
$upload_dir_laporan = '../uploads/laporan/'; 
if (!is_dir($upload_dir_laporan)) { mkdir($upload_dir_laporan, 0755, true); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kumpul_laporan'])) {
    $id_modul = $_POST['id_modul'];
    if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == 0) {
        $file = $_FILES['file_laporan'];
        $file_name = time() . '_' . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $upload_dir_laporan . $file_name)) {
            $stmt_check = $conn->prepare("SELECT id FROM laporan WHERE id_modul = ? AND id_mahasiswa = ?");
            $stmt_check->bind_param("ii", $id_modul, $id_mahasiswa);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $stmt_update = $conn->prepare("UPDATE laporan SET file_laporan = ?, tanggal_kumpul = NOW(), nilai = NULL, feedback = NULL WHERE id_modul = ? AND id_mahasiswa = ?");
                $stmt_update->bind_param("sii", $file_name, $id_modul, $id_mahasiswa);
                $stmt_update->execute();
            } else {
                $stmt_insert = $conn->prepare("INSERT INTO laporan (id_modul, id_mahasiswa, file_laporan) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iis", $id_modul, $id_mahasiswa, $file_name);
                $stmt_insert->execute();
            }
            $pesan = "Laporan berhasil diunggah."; $pesan_tipe = "success";
        } else {
            $pesan = "Gagal memindahkan file yang diunggah."; $pesan_tipe = "error";
        }
    } else {
        $pesan = "Tidak ada file yang dipilih atau terjadi error saat upload."; $pesan_tipe = "error";
    }
}

$stmt_prak = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
$stmt_prak->bind_param("i", $id_praktikum);
$stmt_prak->execute();
$praktikum = $stmt_prak->get_result()->fetch_assoc();
$pageTitle = 'Detail: ' . htmlspecialchars($praktikum['nama_praktikum']);

$stmt_modul = $conn->prepare("SELECT m.*, l.file_laporan, l.nilai, l.feedback, l.tanggal_kumpul FROM modul m LEFT JOIN laporan l ON m.id = l.id_modul AND l.id_mahasiswa = ? WHERE m.id_praktikum = ? ORDER BY m.created_at ASC");
$stmt_modul->bind_param("ii", $id_mahasiswa, $id_praktikum);
$stmt_modul->execute();
$result_modul = $stmt_modul->get_result();
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    :root {
        /* Palet Warna Baru */
        --action-color: #0d9488; /* Teal-600 */
        --action-hover: #0f766e;  /* Teal-700 */

        --text-primary: #1f2937; 
        --text-secondary: #64748b;
        --card-bg: #ffffff; 
        --border-color: #e2e8f0;
        --success-bg: #f0fdf4; 
        --success-border: #bbf7d0; 
        --success-text: #166534;
        --error-bg: #fef2f2; 
        --error-border: #fecaca; 
        --error-text: #991b1b;
        --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.07);
        --border-radius: 0.75rem;
    }
    .main-content { font-family: 'Inter', sans-serif; }
    .link-back {
        display: inline-block; margin-bottom: 1.5rem; font-weight: 600;
        color: var(--action-color); text-decoration: none; font-size: 1rem;
    }
    .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-weight: 500; }
    .alert-success { background-color: var(--success-bg); border: 1px solid var(--success-border); color: var(--success-text); }
    .alert-error { background-color: var(--error-bg); border: 1px solid var(--error-border); color: var(--error-text); }

    .accordion-item {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }
    .accordion-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 1.25rem 1.5rem; cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .accordion-header:hover { background-color: #f8fafc; }
    .accordion-header h3 { font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-primary); }
    .accordion-header .status-icon {
        width: 1.5rem; height: 1.5rem;
        color: #16a34a; /* Green for completed */
    }
    .accordion-header .status-icon.pending { color: #f59e0b; } /* Amber for pending */
    .accordion-arrow {
        transition: transform 0.3s ease;
        color: var(--text-secondary);
    }
    .accordion-item.active .accordion-arrow { transform: rotate(180deg); }
    .accordion-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-out, padding 0.5s ease-out;
        padding: 0 1.5rem;
    }
    .accordion-content-inner {
        padding: 1.5rem 0;
        border-top: 1px solid var(--border-color);
        display: grid; grid-template-columns: 1fr; gap: 2rem;
    }
    @media (min-width: 768px) { .accordion-content-inner { grid-template-columns: 1fr 1.5fr; } }
    
    .section-title { font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; }
    .download-link { color: var(--action-color); font-weight: 500; }
    .nilai-wrapper .nilai { font-size: 2.5rem; font-weight: 800; color: #16a34a; }
    .nilai-wrapper .feedback { font-size: 0.9rem; margin-top: 0.5rem; }
    .text-muted { font-size: 0.9rem; color: var(--text-secondary); }
    .upload-status { background-color: #F0F9FF; color: #075985; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; }
    .upload-form .form-label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
    .upload-form .input-group { display: flex; gap: 0.75rem; align-items: center; }
    .upload-form input[type="file"] {
        flex-grow: 1; border: 1px solid var(--border-color);
        border-radius: 0.5rem; font-size: 0.875rem;
    }
    .upload-form input[type="file"]::file-selector-button {
        background-color: #F0FDFA; color: var(--action-color);
        border: none; border-right: 1px solid var(--border-color);
        padding: 0.6rem 1rem; font-weight: 600; cursor: pointer;
    }
    .btn-upload {
        background-color: var(--action-color); color: white; border: none;
        padding: 0.6rem 1.25rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-upload:hover { background-color: var(--action-hover); }
</style>

<div class="main-content">
    <?php if (!empty($pesan)) { echo "<div class='alert alert-{$pesan_tipe}'>{$pesan}</div>"; } ?>

    <a href="my_courses.php" class="link-back">&larr; Kembali ke Praktikum Saya</a>

    <h2 style="font-size: 1.875rem; font-weight: 800; margin-bottom: 1.5rem;"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h2>

    <div class="accordion">
        <?php if ($result_modul && $result_modul->num_rows > 0): $modul_index = 0; ?>
            <?php while($modul = $result_modul->fetch_assoc()): ?>
            <div class="accordion-item <?php echo $modul_index === 0 ? 'active' : '' ?>">
                <div class="accordion-header">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php if(!is_null($modul['nilai'])): ?>
                             <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        <?php else: ?>
                            <svg class="status-icon pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($modul['nama_modul']); ?></h3>
                    </div>
                    <svg class="accordion-arrow" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
                <div class="accordion-content">
                    <div class="accordion-content-inner">
                        <div class="space-y-6">
                            <div>
                                <h4 class="section-title">Materi Praktikum</h4>
                                <?php if(!empty($modul['file_materi'])): ?>
                                    <a href="<?php echo $upload_dir_materi . htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="download-link">Unduh Materi Di Sini</a>
                                <?php else: ?>
                                    <p class="text-muted">Materi belum tersedia.</p>
                                <?php endif; ?>
                            </div>
                            <div class="nilai-wrapper">
                                <h4 class="section-title">Nilai & Feedback</h4>
                                <?php if(!is_null($modul['nilai'])): ?>
                                    <p class="nilai"><?php echo htmlspecialchars($modul['nilai']); ?></p>
                                    <p class="feedback"><strong>Feedback:</strong> <?php echo !empty($modul['feedback']) ? htmlspecialchars($modul['feedback']) : 'Tidak ada feedback.'; ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Laporan Anda belum dinilai.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <h4 class="section-title">Pengumpulan Laporan</h4>
                            <?php if(!empty($modul['file_laporan'])): ?>
                                <div class="upload-status">
                                    <p>&#10004; Terakhir diunggah pada <?php echo date('d M Y, H:i', strtotime($modul['tanggal_kumpul'])); ?>.</p>
                                </div>
                            <?php endif; ?>
                            <form action="detail_praktikum.php?id=<?php echo $id_praktikum; ?>" method="POST" enctype="multipart/form-data" class="upload-form" style="margin-top: 1rem;">
                                <input type="hidden" name="id_modul" value="<?php echo $modul['id']; ?>">
                                <label for="file_laporan_<?php echo $modul['id']; ?>" class="form-label">
                                    <?php echo !empty($modul['file_laporan']) ? 'Kumpul Ulang (Ganti File):' : 'Pilih File Laporan:'; ?>
                                </label>
                                <div class="input-group">
                                    <input type="file" name="file_laporan" id="file_laporan_<?php echo $modul['id']; ?>" required>
                                    <button type="submit" name="kumpul_laporan" class="btn-upload">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php $modul_index++; endwhile; ?>
        <?php else: ?>
            <p class="text-center text-muted">Belum ada modul untuk praktikum ini.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const accordionItems = document.querySelectorAll(".accordion-item");

    function openFirstAccordion() {
        if (accordionItems.length > 0) {
            const firstItem = accordionItems[0];
            firstItem.classList.add('active');
            const content = firstItem.querySelector(".accordion-content");
            content.style.maxHeight = content.scrollHeight + "px";
        }
    }

    accordionItems.forEach(item => {
        const header = item.querySelector(".accordion-header");
        const content = item.querySelector(".accordion-content");

        header.addEventListener("click", () => {
            const isActive = item.classList.contains("active");

            accordionItems.forEach(i => {
                i.classList.remove("active");
                i.querySelector(".accordion-content").style.maxHeight = null;
            });

            if (!isActive) {
                item.classList.add("active");
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });

    openFirstAccordion();
});
</script>

<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>