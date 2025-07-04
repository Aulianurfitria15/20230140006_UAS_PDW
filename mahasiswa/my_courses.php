<?php
$pageTitle = 'Praktikum Saya';
require_once 'templates/header_mahasiswa.php';
require_once '../config.php';

if ($_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../asisten/dashboard.php');
    exit;
}

$id_mahasiswa = $_SESSION['user_id'];

$sql = "SELECT 
            mp.id, mp.nama_praktikum, mp.deskripsi,
            u.nama as nama_asisten
        FROM pendaftaran_praktikum pp
        JOIN mata_praktikum mp ON pp.id_praktikum = mp.id
        JOIN users u ON mp.id_asisten_pembuat = u.id
        WHERE pp.id_mahasiswa = ?
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
        --action-color: #4f46e5; --action-hover: #4338ca;
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
        background-color: var(--action-color);
        margin-top: auto; /* Aligns button to bottom */
    }
    .course-card .btn:hover { background-color: var(--action-hover); transform: scale(1.03); }
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
                
                <a href="detail_praktikum.php?id=<?php echo $row['id']; ?>" class="btn">
                    Lihat Detail & Tugas
                </a>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-courses">
                <p>Anda belum mendaftar di praktikum manapun. Silakan cari praktikum di halaman "Cari Praktikum".</p>
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