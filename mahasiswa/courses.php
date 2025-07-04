<?php
$pageTitle = 'Cari Praktikum';
$activePage = 'courses'; 
require_once 'templates/header_mahasiswa.php';
require_once '../config.php';

if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../asisten/dashboard.php');
    exit;
}

$id_mahasiswa = $_SESSION['user_id'];

$sql = "SELECT 
            mp.id, mp.nama_praktikum, mp.deskripsi,
            u.nama as nama_asisten,
            (SELECT COUNT(*) FROM pendaftaran_praktikum pp WHERE pp.id_praktikum = mp.id AND pp.id_mahasiswa = ?) as sudah_terdaftar
        FROM mata_praktikum mp
        JOIN users u ON mp.id_asisten_pembuat = u.id
        ORDER BY mp.nama_praktikum ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_mahasiswa);
$stmt->execute();
$result = $stmt->get_result();
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    :root {
        --text-primary: #1f2937; --text-secondary: #6b7280;
        --card-bg: #ffffff; --border-color: #e5e7eb;
        --action-color: #3B82F6; --action-hover: #2563EB;
        --disabled-bg: #E5E7EB; --disabled-text: #9CA3AF;
        --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        --border-radius: 0.75rem;
    }
    .main-content { font-family: 'Inter', sans-serif; }
    .course-grid {
        display: grid; grid-template-columns: repeat(1, 1fr);
        gap: 1.5rem;
    }
    @media (min-width: 768px) { .course-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .course-grid { grid-template-columns: repeat(3, 1fr); } }
    
    @keyframes popIn {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .course-card {
        background-color: var(--card-bg); padding: 1.5rem;
        border-radius: var(--border-radius); box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        display: flex; flex-direction: column;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        opacity: 0; /* Start hidden for animation */
        animation: popIn 0.5s ease-out forwards;
    }
    .course-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }

    .course-card h3 {
        font-size: 1.25rem; font-weight: 700; color: var(--text-primary);
        margin: 0 0 0.5rem 0;
    }
    .course-card .description {
        color: var(--text-secondary); margin-bottom: 1rem;
        flex-grow: 1; line-height: 1.6;
    }
    .course-card .asisten {
        font-size: 0.875rem; color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }
    .course-card .btn {
        display: block; width: 100%; text-align: center;
        padding: 0.75rem 1rem; border-radius: 0.5rem;
        font-weight: 600; color: white; text-decoration: none;
        transition: background-color 0.2s, transform 0.2s;
        margin-top: auto; /* Aligns button to bottom */
    }
    .btn-register { background-color: var(--action-color); }
    .btn-register:hover { background-color: var(--action-hover); transform: scale(1.03); }
    .btn-disabled {
        background-color: var(--disabled-bg); color: var(--disabled-text);
        cursor: not-allowed;
    }
    .no-courses {
        grid-column: 1 / -1; text-align: center; padding: 2rem;
        background-color: var(--card-bg); border-radius: var(--border-radius);
    }
</style>

<div class="main-content">
    <div class="course-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <div class="course-card">
                <h3><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
                <p class="description"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                <p class="asisten">Asisten: <strong><?php echo htmlspecialchars($row['nama_asisten']); ?></strong></p>
                
                <?php if ($row['sudah_terdaftar'] > 0): ?>
                    <button class="btn btn-disabled" disabled>Sudah Terdaftar</button>
                <?php else: ?>
                    <a href="proses_pendaftaran.php?id_praktikum=<?php echo $row['id']; ?>" class="btn btn-register" onclick="return confirm('Apakah Anda yakin ingin mendaftar di praktikum ini?');">
                        Daftar Praktikum
                    </a>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-courses">
                <p>Saat ini belum ada praktikum yang tersedia.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const cards = document.querySelectorAll('.course-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 100}ms`;
    });
});
</script>

<?php
$stmt->close();
$conn->close();
require_once 'templates/footer_mahasiswa.php';
?>