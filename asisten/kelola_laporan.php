<?php
// asisten/kelola_laporan.php

// Panggil Header dan Konfigurasi
$pageTitle = 'Laporan Masuk';
require_once '../templates/header.php';
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

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Filter Laporan</h3>
    <form action="kelola_laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Daftar Laporan Masuk</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Mahasiswa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Praktikum & Modul</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Kumpul</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($result_laporan && $result_laporan->num_rows > 0): ?>
                    <?php while($row = $result_laporan->fetch_assoc()): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['nama_mahasiswa']); ?></td>
                        <td class="px-6 py-4">
                            <div class="font-semibold"><?php echo htmlspecialchars($row['nama_praktikum']); ?></div>
                            <div><?php echo htmlspecialchars($row['nama_modul']); ?></div>
                        </td>
                        <td class="px-6 py-4"><?php echo date('d M Y, H:i', strtotime($row['tanggal_kumpul'])); ?></td>
                        <td class="px-6 py-4 text-center">
                            <?php if (is_null($row['nilai'])): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Belum Dinilai
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Sudah Dinilai (<?php echo $row['nilai']; ?>)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="beri_nilai.php?id_laporan=<?php echo $row['id_laporan']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                <?php echo is_null($row['nilai']) ? 'Beri Nilai' : 'Lihat/Ubah Nilai'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center p-4">Tidak ada laporan yang ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once '../templates/footer.php';
$conn->close();
?>