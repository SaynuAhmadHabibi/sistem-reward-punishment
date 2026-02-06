<?php
require_once '../includes/functions.php';
require_once '../includes/auth_helper.php';

// Redirect ke login jika belum login
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Admin, HRD Admin, dan Direktur bisa akses (Direktur read-only)
if (!isAdmin() && !isHRDAdmin() && !isDirektur()) {
    $_SESSION['error'] = 'Akses ditolak! Anda tidak memiliki permission untuk mengakses halaman ini.';
    redirect('../dashboard.php');
}

$page_title = "Manajemen Penilaian";

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $conn = connectDB();
    $stmt = mysqli_prepare($conn, "DELETE FROM penilaian WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);

    if (mysqli_stmt_execute($stmt)) {
        logActivity('PENILAIAN_MANAGEMENT', "Menghapus data penilaian ID: $id");
        redirect('index.php', 'Data penilaian berhasil dihapus');
    } else {
        $_SESSION['error'] = 'Gagal menghapus data penilaian: ' . mysqli_error($conn);
        redirect('index.php');
    }
}

// Search dan pagination
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Query untuk mendapatkan data penilaian
$sql_count = "SELECT COUNT(*) as total FROM penilaian p
              LEFT JOIN karyawan k ON p.karyawan_id = k.id
              LEFT JOIN users u ON p.penilai_id = u.id
              WHERE 1=1";

$sql = "SELECT p.*, k.nama as karyawan_nama, k.nik, u.nama as penilai_nama
        FROM penilaian p
        LEFT JOIN karyawan k ON p.karyawan_id = k.id
        LEFT JOIN users u ON p.penilai_id = u.id
        WHERE 1=1";

$params = [];
$count_params = [];

if (!empty($search)) {
    $search_condition = " AND (k.nama LIKE ? OR k.nik LIKE ? OR u.nama LIKE ? OR p.keterangan LIKE ?)";
    $sql_count .= $search_condition;
    $sql .= $search_condition;
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $count_params = $params;
}

$sql .= " ORDER BY p.tanggal_penilaian DESC, p.created_at DESC LIMIT ? OFFSET ?";
$params = array_merge($params, [$limit, $offset]);

$total_penilaian = getSingleRow($sql_count, $count_params)['total'];
$total_pages = ceil($total_penilaian / $limit);
$penilaian_list = getMultipleRows($sql, $params);
// Statistik
$stats = getSingleRow("
    SELECT
        COUNT(*) as total_penilaian,
        AVG(kinerja) as rata_rata_nilai,
        MAX(kinerja) as nilai_tertinggi,
        MIN(kinerja) as nilai_terendah
    FROM penilaian
    WHERE MONTH(tanggal_penilaian) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_penilaian) = YEAR(CURRENT_DATE())
");
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>Manajemen Penilaian Karyawan
                    </h5>
                    <div>
                        <a href="create.php" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-plus me-1"></i>Tambah Penilaian
                        </a>
                        <a href="../laporan/index.php" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-chart-line me-1"></i>Laporan
                        </a>
                        <a href="../reward/index.php" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-trophy me-1"></i>Reward
                        </a>
                        <a href="../punishment/index.php" class="btn btn-light btn-sm">
                            <i class="fas fa-exclamation-triangle me-1"></i>Punishment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Bulan Ini -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Total Penilaian</small>
                            <h4 class="mb-0 fw-bold text-success"><?php echo $stats['total_penilaian']; ?></h4>
                        </div>
                        <div class="text-success opacity-25">
                            <i class="fas fa-chart-bar fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Rata-rata Nilai</small>
                            <h4 class="mb-0 fw-bold text-info"><?php echo number_format($stats['rata_rata_nilai'] ?? 0, 2); ?></h4>
                        </div>
                        <div class="text-info opacity-25">
                            <i class="fas fa-calculator fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Nilai Tertinggi</small>
                            <h4 class="mb-0 fw-bold text-primary"><?php echo number_format($stats['nilai_tertinggi'] ?? 0, 2); ?></h4>
                        </div>
                        <div class="text-primary opacity-25">
                            <i class="fas fa-arrow-up fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Nilai Terendah</small>
                            <h4 class="mb-0 fw-bold text-warning"><?php echo number_format($stats['nilai_terendah'] ?? 0, 2); ?></h4>
                        </div>
                        <div class="text-warning opacity-25">
                            <i class="fas fa-arrow-down fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i>Cari & Filter
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="search" placeholder="Cari nama karyawan, NIK, penilai..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Penilaian -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>Data Penilaian (<?php echo $total_penilaian; ?> records)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($penilaian_list)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Karyawan</th>
                                        <th>NIK</th>
                                        <th>Penilai</th>
                                        <th>Tanggal</th>
                                        <th>Nilai Kinerja</th>
                                        <th>Status</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($penilaian_list as $index => $penilaian): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($penilaian['karyawan_nama']); ?></td>
                                            <td><?php echo htmlspecialchars($penilaian['nik']); ?></td>
                                            <td><?php echo htmlspecialchars($penilaian['penilai_nama']); ?></td>
                                            <td><?php echo formatDate($penilaian['tanggal']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $penilaian['nilai_akhir'] >= 85 ? 'success' :
                                                         ($penilaian['nilai_akhir'] >= 70 ? 'warning' : 'danger');
                                                ?>">
                                                    <?php echo $penilaian['nilai_akhir']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $penilaian['status'] == 'aktif' ? 'success' :
                                                         ($penilaian['status'] == 'draft' ? 'secondary' : 'danger');
                                                ?>">
                                                    <?php echo ucfirst($penilaian['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="view.php?id=<?php echo $penilaian['id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (canUpdate()): ?>
                                                    <a href="edit.php?id=<?php echo $penilaian['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if (canDelete()): ?>
                                                    <a href="?action=delete&id=<?php echo $penilaian['id']; ?>" class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Hapus penilaian ini secara permanen?')" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Penilaian pagination" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                            <h4>Tidak ada data penilaian</h4>
                            <p class="text-muted">Belum ada penilaian karyawan yang tercatat dalam sistem.</p>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Penilaian Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh setiap 5 menit untuk data real-time
setTimeout(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 300000);

// Highlight search terms
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = '<?php echo htmlspecialchars($search); ?>';
    if (searchTerm) {
        highlightText(searchTerm);
    }
});

function highlightText(searchTerm) {
    const elements = document.querySelectorAll('td');
    elements.forEach(element => {
        if (element.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
            element.innerHTML = element.textContent.replace(
                new RegExp(searchTerm, 'gi'),
                '<mark>$&</mark>'
            );
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>