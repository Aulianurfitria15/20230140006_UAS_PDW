<?php
// asisten/dashboard.php

// 1. Definisi Variabel dan Panggil Konfigurasi
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once '../config.php'; // Panggil config untuk koneksi db

// Pastikan sesi dimulai SEBELUM memanggil header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Logika untuk mengambil data dinamis
// Hitung total modul dari semua praktikum
$result_modul = $conn->query("SELECT COUNT(id) as total FROM modul");
$total_modul = $result_modul->fetch_assoc()['total'];

// Hitung total laporan yang masuk
$result_laporan = $conn->query("SELECT COUNT(id) as total FROM laporan");
$total_laporan_masuk = $result_laporan->fetch_assoc()['total'];

// Hitung laporan yang belum dinilai
$result_belum_dinilai = $conn->query("SELECT COUNT(id) as total FROM laporan WHERE nilai IS NULL");
$laporan_belum_dinilai = $result_belum_dinilai->fetch_assoc()['total'];

// Ambil 3 aktivitas laporan terbaru
$sql_aktivitas = "SELECT 
                    u.nama as nama_mahasiswa, 
                    m.nama_modul, 
                    l.tanggal_kumpul 
                  FROM laporan l
                  JOIN users u ON l.id_mahasiswa = u.id
                  JOIN modul m ON l.id_modul = m.id
                  ORDER BY l.tanggal_kumpul DESC
                  LIMIT 3";
$result_aktivitas = $conn->query($sql_aktivitas);


// 3. Panggil Header SETELAH semua logika selesai
require_once 'templates/header.php'; 
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    :root {
        --text-primary: #1f2937;
        --text-secondary: #6b7280;
        --card-bg: #ffffff;
        --border-color: #e5e7eb;
        --shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1);
        --border-radius: 0.75rem; /* Slightly larger radius */
    }
    .main-content { 
        font-family: 'Inter', sans-serif; 
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) { 
        .stats-grid { 
            grid-template-columns: repeat(3, 1fr); 
        } 
    }

    .stat-card {
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        border: 1px solid var(--border-color);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
    }

    .stat-card .icon-wrapper {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 9999px;
    }
    .stat-card .icon-wrapper svg {
        width: 1.75rem;
        height: 1.75rem;
    }
    .icon-blue { background-color: #dbeafe; color: #3b82f6; }
    .icon-green { background-color: #dcfce7; color: #22c55e; }
    .icon-yellow { background-color: #fef9c3; color: #eab308; }

    .stat-card .info .title {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-secondary);
        margin: 0;
    }
    .stat-card .info .value {
        font-size: 2.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0.25rem 0 0 0;
        line-height: 1.2;
    }

    .activity-card {
        background-color: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 1.5rem;
        border: 1px solid var(--border-color);
    }
    .activity-card-header {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 1.5rem 0;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .activity-feed .item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .activity-feed .item:not(:last-child) {
        padding-bottom: 1.25rem;
        margin-bottom: 1.25rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .activity-feed .avatar {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 9999px;
        background-color: #f3f4f6;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        flex-shrink: 0;
        text-transform: uppercase;
        border: 2px solid white;
        box-shadow: 0 0 0 1px var(--border-color);
    }
    .activity-feed .details p { margin: 0; line-height: 1.5; }
    .activity-feed .details .text-content { color: var(--text-primary); }
    .activity-feed .details .timestamp { font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.25rem; }
</style>

<div class="main-content">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon-wrapper icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
            </div>
            <div class="info">
                <p class="title">Total Modul Dibuat</p>
                <p class="value"><?php echo $total_modul; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="icon-wrapper icon-green">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="info">
                <p class="title">Total Laporan Masuk</p>
                <p class="value"><?php echo $total_laporan_masuk; ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="icon-wrapper icon-yellow">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div class="info">
                <p class="title">Laporan Belum Dinilai</p>
                <p class="value"><?php echo $laporan_belum_dinilai; ?></p>
            </div>
        </div>
    </div>

    <div class="activity-card">
        <h3 class="activity-card-header">Aktivitas Laporan Terbaru</h3>
        <div class="activity-feed">
            <?php if ($result_aktivitas && $result_aktivitas->num_rows > 0): ?>
                <?php while($aktivitas = $result_aktivitas->fetch_assoc()): ?>
                <div class="item">
                    <div class="avatar">
                        <?php 
                            $words = explode(" ", $aktivitas['nama_mahasiswa']);
                            $initials = "";
                            foreach ($words as $w) {
                                if (!empty($w)) { // pastikan kata tidak kosong
                                    $initials .= mb_substr($w, 0, 1);
                                }
                            }
                            echo strtoupper(substr($initials, 0, 2));
                        ?>
                    </div>
                    <div class="details">
                        <p class="text-content"><strong><?php echo htmlspecialchars($aktivitas['nama_mahasiswa']); ?></strong> mengumpulkan laporan untuk modul <strong><?php echo htmlspecialchars($aktivitas['nama_modul']); ?></strong>.</p>
                        <p class="timestamp"><?php echo date('d F Y, H:i', strtotime($aktivitas['tanggal_kumpul'])); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-secondary" style="text-align: center; padding: 1rem 0;">Belum ada aktivitas laporan.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Panggil Footer
require_once 'templates/footer.php';
$conn->close();
?>