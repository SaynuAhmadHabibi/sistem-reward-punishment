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

$page_title = "Manajemen Users";

// Handle actions
$message = '';
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    // Cek jangan hapus diri sendiri
    if ($action === 'delete' && $id == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Tidak dapat menghapus akun sendiri!';
        redirect('index.php');
    }

    $conn = connectDB();

    try {
        switch ($action) {
            case 'delete':
                $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                $message = 'User berhasil dihapus!';
                logActivity('USER_MANAGEMENT', 'Menghapus user ID: ' . $id);
                break;

            default:
                $_SESSION['error'] = 'Action tidak valid!';
                redirect('index.php');
        }

        redirect('index.php', $message);
    } catch (Exception $e) {
        $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        redirect('index.php');
    }
}

// Search dan pagination
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Query untuk data users
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (nama LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$users = getMultipleRows($sql, $params);

// Hitung total users untuk pagination
$sql_count = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$count_params = [];

if (!empty($search)) {
    $sql_count .= " AND (nama LIKE ? OR username LIKE ? OR email LIKE ?)";
    $count_params = [$search_term, $search_term, $search_term];
}

$total_users = getSingleRow($sql_count, $count_params)['total'];
$total_pages = ceil($total_users / $limit);

// Statistik users
$stats = getSingleRow("SELECT
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role = 'hrd_admin' THEN 1 ELSE 0 END) as hrd_admin_count,
    SUM(CASE WHEN role = 'direktur' THEN 1 ELSE 0 END) as direktur_count,
    SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as manager_count,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count
    FROM users");
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-shield me-2"></i>Manajemen Users
                    </h5>
                    <a href="create.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Tambah User
                    </a>
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
                            <small class="text-muted d-block">Total Users</small>
                            <h4 class="mb-0 fw-bold text-danger"><?php echo $stats['total_users']; ?></h4>
                        </div>
                        <div class="text-danger opacity-25">
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
                            <small class="text-muted d-block">Admin</small>
                            <h4 class="mb-0 fw-bold text-primary"><?php echo $stats['admin_count']; ?></h4>
                        </div>
                        <div class="text-primary opacity-25">
                            <i class="fas fa-crown fa-3x"></i>
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
                            <small class="text-muted d-block">HRD Admin</small>
                            <h4 class="mb-0 fw-bold text-success"><?php echo $stats['hrd_admin_count']; ?></h4>
                        </div>
                        <div class="text-success opacity-25">
                            <i class="fas fa-user-shield fa-3x"></i>
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
                            <small class="text-muted d-block">Direktur</small>
                            <h4 class="mb-0 fw-bold text-warning"><?php echo $stats['direktur_count']; ?></h4>
                        </div>
                        <div class="text-warning opacity-25">
                            <i class="fas fa-user-tie fa-3x"></i>
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
                            <input type="text" class="form-control" name="search" placeholder="Cari nama, username, atau email..."
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

    <!-- Tabel Users -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>Data Users (<?php echo $total_users; ?> records)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th style="width: 100px;">Role</th>
                                        <th>Terakhir Login</th>
                                        <th>Dibuat</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'manager' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['last_login'] ? formatDate($user['last_login'], 'd/m/Y H:i') : '-'; ?></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (canUpdate()): ?>
                                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if (canDelete() && $user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Hapus user ini secara permanen?')" title="Hapus">
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
                            <nav aria-label="User pagination" class="mt-3">
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
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4>Tidak ada data user</h4>
                            <p class="text-muted">Belum ada user yang terdaftar dalam sistem.</p>
                            <?php if (canCreate()): ?>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah User Pertama
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