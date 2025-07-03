<?php
// mahasiswa/dashboard.php

// 1. Panggil Header dan Konfigurasi
$pageTitle = 'Dashboard';
require_once('templates/header_mahasiswa.php');
require_once '../config.php';

// 2. Pastikan hanya mahasiswa yang bisa mengakses
if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../asisten/dashboard.php');
    exit;
}

$id_mahasiswa = $_SESSION['user_id'];
$nama_mahasiswa = $_SESSION['nama'];

// 3. Query untuk mengambil data dinamis
// Hitung jumlah praktikum yang diikuti
$stmt_prak = $conn->prepare("SELECT COUNT(id) as total FROM pendaftaran_praktikum WHERE id_mahasiswa = ?");
$stmt_prak->bind_param("i", $id_mahasiswa);
$stmt_prak->execute();
$total_praktikum = $stmt_prak->get_result()->fetch_assoc()['total'];
$stmt_prak->close();

// Hitung tugas yang sudah dinilai
$stmt_selesai = $conn->prepare("SELECT COUNT(id) as total FROM laporan WHERE id_mahasiswa = ? AND nilai IS NOT NULL");
$stmt_selesai->bind_param("i", $id_mahasiswa);
$stmt_selesai->execute();
$tugas_selesai = $stmt_selesai->get_result()->fetch_assoc()['total'];
$stmt_selesai->close();

// Hitung tugas yang menunggu penilaian
$stmt_menunggu = $conn->prepare("SELECT COUNT(id) as total FROM laporan WHERE id_mahasiswa = ? AND nilai IS NULL");
$stmt_menunggu->bind_param("i", $id_mahasiswa);
$stmt_menunggu->execute();
$tugas_menunggu = $stmt_menunggu->get_result()->fetch_assoc()['total'];
$stmt_menunggu->close();

// Ambil 3 notifikasi terbaru (contoh: nilai baru, pendaftaran baru)
// Ini adalah contoh sederhana, bisa dikembangkan lebih lanjut
$notifikasi = [];
// Notif nilai baru
$sql_notif_nilai = "SELECT m.nama_modul, l.nilai FROM laporan l JOIN modul m ON l.id_modul = m.id WHERE l.id_mahasiswa = ? AND l.nilai IS NOT NULL ORDER BY l.tanggal_kumpul DESC LIMIT 1";
$stmt_notif_nilai = $conn->prepare($sql_notif_nilai);
$stmt_notif_nilai->bind_param("i", $id_mahasiswa);
$stmt_notif_nilai->execute();
$res_notif_nilai = $stmt_notif_nilai->get_result();
if($res_notif_nilai->num_rows > 0){
    $data = $res_notif_nilai->fetch_assoc();
    $notifikasi[] = ['tipe' => 'nilai', 'teks' => 'Nilai untuk <strong>' . htmlspecialchars($data['nama_modul']) . '</strong> telah diberikan.'];
}
$stmt_notif_nilai->close();

// Notif pendaftaran baru
$sql_notif_daftar = "SELECT mp.nama_praktikum FROM pendaftaran_praktikum pp JOIN mata_praktikum mp ON pp.id_praktikum = mp.id WHERE pp.id_mahasiswa = ? ORDER BY pp.tanggal_daftar DESC LIMIT 1";
$stmt_notif_daftar = $conn->prepare($sql_notif_daftar);
$stmt_notif_daftar->bind_param("i", $id_mahasiswa);
$stmt_notif_daftar->execute();
$res_notif_daftar = $stmt_notif_daftar->get_result();
if($res_notif_daftar->num_rows > 0){
    $data = $res_notif_daftar->fetch_assoc();
    $notifikasi[] = ['tipe' => 'sukses', 'teks' => 'Anda berhasil mendaftar pada mata praktikum <strong>' . htmlspecialchars($data['nama_praktikum']) . '</strong>.'];
}
$stmt_notif_daftar->close();

?>

<!-- Banner Selamat Datang -->
<div class="p-6 rounded-lg shadow-lg text-white" style="background: linear-gradient(90deg, #3B82F6 0%, #10B981 100%);">
    <h2 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars(explode(' ', $nama_mahasiswa)[0]); ?>!</h2>
    <p class="mt-2">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<!-- Kartu Statistik -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <div class="text-4xl font-bold text-blue-600"><?php echo $total_praktikum; ?></div>
        <p class="text-gray-500 mt-2">Praktikum Diikuti</p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <div class="text-4xl font-bold text-green-600"><?php echo $tugas_selesai; ?></div>
        <p class="text-gray-500 mt-2">Tugas Selesai</p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <div class="text-4xl font-bold text-yellow-600"><?php echo $tugas_menunggu; ?></div>
        <p class="text-gray-500 mt-2">Tugas Menunggu</p>
    </div>
</div>

<!-- Notifikasi Terbaru -->
<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Notifikasi Terbaru</h3>
    <div class="space-y-4">
        <?php if (empty($notifikasi)): ?>
            <p class="text-gray-500">Tidak ada notifikasi baru.</p>
        <?php else: ?>
            <?php foreach ($notifikasi as $notif): ?>
                <div class="flex items-start">
                    <?php if ($notif['tipe'] == 'nilai'): ?>
                        <div class="flex-shrink-0 w-6 h-6 text-yellow-500"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg></div>
                    <?php elseif ($notif['tipe'] == 'sukses'): ?>
                        <div class="flex-shrink-0 w-6 h-6 text-green-500"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    <?php endif; ?>
                    <div class="ml-3">
                        <p class="text-sm text-gray-700"><?php echo $notif['teks']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<?php
// 6. Panggil Footer
require_once '../templates/footer.php';
$conn->close();
?>
