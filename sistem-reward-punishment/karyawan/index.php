<?php
require_once '../includes/auth_helper.php';
require_once '../includes/functions.php';

/* ===============================
   AUTH & AKSES (FIX HRD)
================================ */
requireLogin();

if (
    !hasPermission('karyawan_view') &&
    !isHRDAdmin() &&
    !isAdmin() &&
    !isManager() &&
    !isDirektur()
) {
    $_SESSION['error'] = 'Akses ditolak!';
    redirect('../dashboard.php');
}

$page_title = "Data Karyawan";

/* ===============================
   FILTER & SEARCH
================================ */
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$departemen = isset($_GET['departemen']) ? sanitize($_GET['departemen']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$jabatan = isset($_GET['jabatan']) ? sanitize($_GET['jabatan']) : '';

/* ===============================
   PAGINATION
================================ */
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

/* ===============================
   QUERY DATA
================================ */
$sql = "SELECT k.*, d.nama as departemen_nama, j.nama as jabatan_nama 
        FROM karyawan k 
        LEFT JOIN departemen d ON k.departemen_id = d.id 
        LEFT JOIN jabatan j ON k.jabatan_id = j.id 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (k.nama LIKE ? OR k.nik LIKE ? OR k.email LIKE ? OR k.telepon LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

if (!empty($departemen)) {
    $sql .= " AND k.departemen_id = ?";
    $params[] = $departemen;
}

if (!empty($jabatan)) {
    $sql .= " AND k.jabatan_id = ?";
    $params[] = $jabatan;
}

if (!empty($status) && in_array($status, ['aktif','non-aktif'])) {
    $sql .= " AND k.status = ?";
    $params[] = $status;
}

/* ===============================
   TOTAL DATA
================================ */
$count_sql = str_replace(
    "SELECT k.*, d.nama as departemen_nama, j.nama as jabatan_nama",
    "SELECT COUNT(*) as total",
    $sql
);

$total_rows = getSingleRow($count_sql, $params)['total'] ?? 0;
$total_pages = ceil($total_rows / $limit);

/* ===============================
   FINAL QUERY
================================ */
$sql .= " ORDER BY k.nama ASC LIMIT $limit OFFSET $offset";
$karyawan_list = getMultipleRows($sql, $params);

/* ===============================
   DATA PENDUKUNG
================================ */
$departemen_list = getMultipleRows("SELECT id, kode, nama FROM departemen ORDER BY nama ASC");
$jabatan_list = getMultipleRows("SELECT id, kode, nama FROM jabatan ORDER BY nama ASC");

$stats = getSingleRow("SELECT 
    COUNT(*) as total,
    SUM(status='aktif') as aktif,
    SUM(status='non-aktif') as non_aktif,
    COUNT(DISTINCT departemen_id) as departemen,
    COUNT(DISTINCT jabatan_id) as jabatan
    FROM karyawan");

$page_subtitle = "Manajemen Data Karyawan";
?>

<?php include '../includes/header.php'; ?>

<!-- ===============================
     HTML (ASLI, TIDAK DIUBAH)
================================ -->

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Data Karyawan
                    </h5>
                    <div>
                        <?php if (canCreate()): ?>
                        <a href="create.php" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-plus me-1"></i>Tambah Karyawan
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-light btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Total Karyawan</small>
                            <h4 class="mb-0 fw-bold text-primary"><?php echo $stats['total']; ?></h4>
                        </div>
                        <div class="text-primary opacity-25">
                            <i class="fas fa-users fa-3x"></i>
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
                            <small class="text-muted d-block">Aktif</small>
                            <h4 class="mb-0 fw-bold text-success"><?php echo $stats['aktif']; ?></h4>
                        </div>
                        <div class="text-success opacity-25">
                            <i class="fas fa-user-check fa-3x"></i>
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
                            <small class="text-muted d-block">Non-Aktif</small>
                            <h4 class="mb-0 fw-bold text-danger"><?php echo $stats['non_aktif']; ?></h4>
                        </div>
                        <div class="text-danger opacity-25">
                            <i class="fas fa-user-times fa-3x"></i>
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
                            <small class="text-muted d-block">Departemen</small>
                            <h4 class="mb-0 fw-bold text-info"><?php echo $stats['departemen']; ?></h4>
                        </div>
                        <div class="text-info opacity-25">
                            <i class="fas fa-building fa-3x"></i>
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
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Cari nama, NIK, email..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="departemen">
                                <option value="">Semua Departemen</option>
                                <?php foreach ($departemen_list as $dep): ?>
                                    <option value="<?php echo $dep['id']; ?>" <?php echo $departemen == $dep['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dep['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="jabatan">
                                <option value="">Semua Jabatan</option>
                                <?php foreach ($jabatan_list as $jab): ?>
                                    <option value="<?php echo $jab['id']; ?>" <?php echo $jabatan == $jab['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($jab['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?php echo $status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="non-aktif" <?php echo $status == 'non-aktif' ? 'selected' : ''; ?>>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Karyawan -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>Data Karyawan (<?php echo $total_rows; ?> records)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($karyawan_list)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>NIK</th>
                                        <th>Nama</th>
                                        <th>Departemen</th>
                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th>Email</th>
                                        <th>Telepon</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($karyawan_list as $index => $karyawan): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($karyawan['nik']); ?></td>
                                            <td><?php echo htmlspecialchars($karyawan['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($karyawan['departemen_nama'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($karyawan['jabatan_nama'] ?: '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $karyawan['status'] == 'aktif' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($karyawan['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($karyawan['email']); ?></td>
                                            <td><?php echo htmlspecialchars($karyawan['telepon']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="view.php?id=<?php echo $karyawan['id']; ?>" class="btn btn-sm btn-success" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (canUpdate()): ?>
                                                    <a href="edit.php?id=<?php echo $karyawan['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if (canDelete()): ?>
                                                    <a href="delete.php?id=<?php echo $karyawan['id']; ?>" class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Hapus karyawan ini secara permanen?')" title="Hapus">
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
                            <nav aria-label="Karyawan pagination" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&departemen=<?php echo urlencode($departemen); ?>&jabatan=<?php echo urlencode($jabatan); ?>&status=<?php echo urlencode($status); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&departemen=<?php echo urlencode($departemen); ?>&jabatan=<?php echo urlencode($jabatan); ?>&status=<?php echo urlencode($status); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&departemen=<?php echo urlencode($departemen); ?>&jabatan=<?php echo urlencode($jabatan); ?>&status=<?php echo urlencode($status); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4>Tidak ada data karyawan</h4>
                            <p class="text-muted">Belum ada karyawan yang terdaftar dalam sistem.</p>
                            <?php if (canCreate()): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Karyawan Pertama
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>