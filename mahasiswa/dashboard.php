<?php
// mahasiswa/dashboard.php

$pageTitle = 'Dashboard';
require_once('templates/header_mahasiswa.php');
require_once '../config.php';

if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../asisten/dashboard.php');
    exit;
}

$id_mahasiswa = $_SESSION['user_id'];
$nama_mahasiswa = $_SESSION['nama'];

$stmt_prak = $conn->prepare("SELECT COUNT(id) as total FROM pendaftaran_praktikum WHERE id_mahasiswa = ?");
$stmt_prak->bind_param("i", $id_mahasiswa);
$stmt_prak->execute();
$total_praktikum = $stmt_prak->get_result()->fetch_assoc()['total'];
$stmt_prak->close();

$stmt_selesai = $conn->prepare("SELECT COUNT(id) as total FROM laporan WHERE id_mahasiswa = ? AND nilai IS NOT NULL");
$stmt_selesai->bind_param("i", $id_mahasiswa);
$stmt_selesai->execute();
$tugas_selesai = $stmt_selesai->get_result()->fetch_assoc()['total'];
$stmt_selesai->close();

$stmt_menunggu = $conn->prepare("SELECT COUNT(id) as total FROM laporan WHERE id_mahasiswa = ? AND nilai IS NULL");
$stmt_menunggu->bind_param("i", $id_mahasiswa);
$stmt_menunggu->execute();
$tugas_menunggu = $stmt_menunggu->get_result()->fetch_assoc()['total'];
$stmt_menunggu->close();

$notifikasi = [];
$sql_notif_nilai = "SELECT m.nama_modul, l.nilai FROM laporan l JOIN modul m ON l.id_modul = m.id WHERE l.id_mahasiswa = ? AND l.nilai IS NOT NULL ORDER BY l.tanggal_kumpul DESC LIMIT 2";
$stmt_notif_nilai = $conn->prepare($sql_notif_nilai);
$stmt_notif_nilai->bind_param("i", $id_mahasiswa);
$stmt_notif_nilai->execute();
$res_notif_nilai = $stmt_notif_nilai->get_result();
while($data = $res_notif_nilai->fetch_assoc()){
    $notifikasi[] = ['tipe' => 'nilai', 'teks' => 'Nilai untuk <strong>' . htmlspecialchars($data['nama_modul']) . '</strong> telah diberikan: <span class="nilai-notif">' . htmlspecialchars($data['nilai']) . '</span>'];
}
$stmt_notif_nilai->close();

$sql_notif_daftar = "SELECT mp.nama_praktikum FROM pendaftaran_praktikum pp JOIN mata_praktikum mp ON pp.id_praktikum = mp.id WHERE pp.id_mahasiswa = ? ORDER BY pp.tanggal_daftar DESC LIMIT 1";
$stmt_notif_daftar = $conn->prepare($sql_notif_daftar);
$stmt_notif_daftar->bind_param("i", $id_mahasiswa);
$stmt_notif_daftar->execute();
$res_notif_daftar = $stmt_notif_daftar->get_result();
if($res_notif_daftar->num_rows > 0){
    $data = $res_notif_daftar->fetch_assoc();
    $notifikasi[] = ['tipe' => 'sukses', 'teks' => 'Anda berhasil mendaftar pada praktikum <strong>' . htmlspecialchars($data['nama_praktikum']) . '</strong>.'];
}
$stmt_notif_daftar->close();

?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    :root {
        --text-primary: #1f2937; --text-secondary: #6b7280;
        --card-bg: #ffffff; --border-color: #e5e7eb;
        --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        --border-radius: 0.75rem;
    }
    .main-content { 
        font-family: 'Inter', sans-serif;
        animation: fadeIn 0.5s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .welcome-banner {
        padding: 2.5rem 2rem; border-radius: var(--border-radius);
        color: white; box-shadow: var(--shadow-lg);
        background: linear-gradient(135deg, #3B82F6 0%, #1E3A8A 100%);
        position: relative;
        overflow: hidden;
    }
    .welcome-banner::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-4v-1h4v-9h-4v-1h4v-9h-4v-1h4v-9h-4v-1h4v-9h-4v-1h4v-9h-4v-1h4v-9h-4v-1h4v-9h-4v-1h4v-4h1v4h9v-4h1v4h9v-4h1v4h9v-4h1v4h9v-4h1v4h9v-4h1v4h9v-4h1v4h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9z'/%3E%3Cpath d='M6 5V0h1v5h5v1H6v5H5V6H0V5h5z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }
    .welcome-banner h2 { font-size: 2rem; font-weight: 800; }
    .welcome-banner p { margin-top: 0.5rem; opacity: 0.9; font-size: 1.125rem; }
    .stats-grid {
        display: grid; grid-template-columns: repeat(1, 1fr);
        gap: 1.5rem; margin-top: 2rem;
    }
    @media (min-width: 768px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
    .stat-card {
        background-color: var(--card-bg); padding: 1.5rem;
        border-radius: var(--border-radius); box-shadow: var(--shadow-md);
        text-align: center; border: 1px solid var(--border-color);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
    .stat-card .value { font-size: 3rem; font-weight: 800; line-height: 1; }
    .stat-card .title { color: var(--text-secondary); margin-top: 0.75rem; font-weight: 500;}
    .value-blue { color: #2563EB; }
    .value-green { color: #16A34A; }
    .value-yellow { color: #D97706; }

    .notification-card {
        background-color: var(--card-bg); padding: 1.5rem;
        border-radius: var(--border-radius); box-shadow: var(--shadow-md);
        margin-top: 2rem; border: 1px solid var(--border-color);
    }
    .notification-card h3 {
        font-size: 1.25rem; font-weight: 700; color: var(--text-primary);
        margin: 0 0 1rem 0; padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .notification-item { 
        display: flex; align-items: flex-start; gap: 1rem;
        padding: 0.75rem; border-radius: 0.5rem;
        transition: background-color 0.2s ease;
    }
    .notification-item:hover { background-color: #f9fafb; }
    .notification-item:not(:last-child) { margin-bottom: 0.5rem; }
    .notification-icon { flex-shrink: 0; width: 1.75rem; height: 1.75rem; }
    .icon-nilai { color: #F59E0B; }
    .icon-sukses { color: #10B981; }
    .notification-text { font-size: 0.9rem; color: var(--text-primary); line-height: 1.6; }
    .nilai-notif { background-color: #FEF3C7; color: #92400E; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
</style>

<div class="main-content">
    <div class="welcome-banner">
        <h2>Selamat Datang, <?php echo htmlspecialchars(explode(' ', $nama_mahasiswa)[0]); ?>!</h2>
        <p>Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="value value-blue" id="stat-praktikum"><?php echo $total_praktikum; ?></div>
            <p class="title">Praktikum Diikuti</p>
        </div>
        <div class="stat-card">
            <div class="value value-green" id="stat-selesai"><?php echo $tugas_selesai; ?></div>
            <p class="title">Laporan Dinilai</p>
        </div>
        <div class="stat-card">
            <div class="value value-yellow" id="stat-menunggu"><?php echo $tugas_menunggu; ?></div>
            <p class="title">Menunggu Penilaian</p>
        </div>
    </div>

    <div class="notification-card">
        <h3>Notifikasi Terbaru</h3>
        <div class="space-y-2">
            <?php if (empty($notifikasi)): ?>
                <p class="text-secondary" style="padding: 1rem 0; text-align: center;">Tidak ada notifikasi baru untuk Anda.</p>
            <?php else: ?>
                <?php foreach ($notifikasi as $notif): ?>
                    <div class="notification-item">
                        <?php if ($notif['tipe'] == 'nilai'): ?>
                            <svg class="notification-icon icon-nilai" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.166 2.072A.75.75 0 016 .25h12a.75.75 0 01.834 1.822l-3.34 16.698a.75.75 0 01-1.497-.3l3.34-16.696H6.633L3.293 18.47a.75.75 0 01-1.497-.3L5.166 2.072zM12 7.5a.75.75 0 01.75.75v6a.75.75 0 01-1.5 0v-6A.75.75 0 0112 7.5z" clip-rule="evenodd" /></svg>
                        <?php elseif ($notif['tipe'] == 'sukses'): ?>
                           <svg class="notification-icon icon-sukses" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                        <?php endif; ?>
                        <p class="notification-text"><?php echo $notif['teks']; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    const statPraktikum = document.getElementById('stat-praktikum');
    const statSelesai = document.getElementById('stat-selesai');
    const statMenunggu = document.getElementById('stat-menunggu');

    if (statPraktikum) {
        animateValue(statPraktikum, 0, <?php echo $total_praktikum; ?>, 1500);
    }
    if (statSelesai) {
        animateValue(statSelesai, 0, <?php echo $tugas_selesai; ?>, 1500);
    }
    if (statMenunggu) {
        animateValue(statMenunggu, 0, <?php echo $tugas_menunggu; ?>, 1500);
    }
});
</script>


<?php
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>