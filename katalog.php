<?php
// katalog.php (Halaman Publik)

// Mulai sesi untuk memeriksa status login pengguna
session_start();
require_once 'config.php';

// Cek apakah pengguna sudah login dan apa perannya
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? '';

// Query untuk mengambil semua mata praktikum yang tersedia
// Kita JOIN dengan tabel users untuk mendapatkan nama asisten
$sql = "SELECT mp.id, mp.nama_praktikum, mp.deskripsi, u.nama as nama_asisten 
        FROM mata_praktikum mp 
        JOIN users u ON mp.id_asisten_pembuat = u.id 
        ORDER BY mp.nama_praktikum ASC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Praktikum - SIMPRAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full">
<div class="min-h-full">
  <!-- Navigasi Publik -->
  <nav class="bg-gray-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <div class="flex items-center">
            <h1 class="text-white font-bold text-xl">SIMPRAK</h1>
        </div>
        <div>
            <?php if ($is_logged_in): ?>
                <a href="<?php echo $user_role == 'asisten' ? 'asisten/dashboard.php' : 'mahasiswa/dashboard.php'; ?>" class="bg-indigo-600 text-white hover:bg-indigo-700 rounded-md px-3 py-2 text-sm font-medium">Ke Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="bg-indigo-600 text-white hover:bg-indigo-700 rounded-md px-3 py-2 text-sm font-medium">Login</a>
            <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <header class="bg-white shadow">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900">Katalog Mata Praktikum</h1>
      <p class="mt-2 text-gray-600">Temukan dan daftar untuk praktikum yang Anda minati.</p>
    </div>
  </header>
  <main>
    <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
        <!-- Grid untuk menampilkan kartu praktikum -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            if ($result && $result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
            <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col transform hover:-translate-y-1 transition-transform duration-300">
                <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
                <p class="text-sm text-gray-500 mb-4">Oleh: <?php echo htmlspecialchars($row['nama_asisten']); ?></p>
                <p class="text-gray-700 mb-6 flex-grow"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                
                <!-- Tombol ini hanya muncul jika pengguna adalah mahasiswa yang sudah login -->
                <?php if($is_logged_in && $user_role == 'mahasiswa'): ?>
                    <a href="mahasiswa/proses_pendaftaran.php?id_praktikum=<?php echo $row['id']; ?>" class="mt-auto w-full text-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 font-semibold">
                        Daftar Praktikum Ini
                    </a>
                <?php endif; ?>
            </div>
            <?php 
                endwhile;
            else:
                // Pesan jika tidak ada praktikum yang tersedia
                echo "<p class='col-span-full text-center text-gray-500'>Belum ada mata praktikum yang tersedia saat ini.</p>";
            endif;
            ?>
        </div>
    </div>
  </main>
</div>
</body>
</html>
<?php $conn->close(); ?>
