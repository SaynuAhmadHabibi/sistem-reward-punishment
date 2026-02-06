<?php
/**
 * Permission Test & Verification Page
 * Verify user roles and permissions after login
 */

require_once 'includes/functions.php';

// Redirect to login if not authenticated
if (!isLoggedIn()) {
    redirect('login.php');
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-shield-alt me-2"></i>Permission & Access Level Check
            </h1>
        </div>
    </div>

    <!-- User Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Current User Info
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Username:</strong></td>
                            <td><code><?php echo htmlspecialchars($_SESSION['user_username']); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td><?php echo htmlspecialchars($_SESSION['user_nama']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo htmlspecialchars($_SESSION['user_email']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Role:</strong></td>
                            <td>
                                <span class="badge <?php 
                                    if ($_SESSION['user_role'] === 'admin') echo 'bg-danger';
                                    elseif ($_SESSION['user_role'] === 'hrd_admin') echo 'bg-warning';
                                    elseif ($_SESSION['user_role'] === 'direktur') echo 'bg-info';
                                    else echo 'bg-secondary';
                                ?>">
                                    <?php echo getRoleDisplayName($_SESSION['user_role']); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>Access Capabilities
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Can Read:</strong></td>
                            <td>
                                <span class="badge <?php echo canRead() ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo canRead() ? '✓ YES' : '✗ NO'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Can Create:</strong></td>
                            <td>
                                <span class="badge <?php echo canCreate() ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo canCreate() ? '✓ YES' : '✗ NO'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Can Update:</strong></td>
                            <td>
                                <span class="badge <?php echo canUpdate() ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo canUpdate() ? '✓ YES' : '✗ NO'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Can Delete:</strong></td>
                            <td>
                                <span class="badge <?php echo canDelete() ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo canDelete() ? '✓ YES' : '✗ NO'; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Permission Matrix -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-th me-2"></i>Full Permission Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Permission</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $permissions = [
                                    'read', 'write', 'delete', 'manage_users', 'manage_departments',
                                    'manage_positions', 'manage_employees', 'manage_evaluations',
                                    'manage_rewards', 'manage_punishments', 'generate_reports', 'backup_data'
                                ];

                                foreach ($permissions as $permission) {
                                    $displayName = getPermissionDisplayName($permission);
                                    $hasPermission = hasPermission($permission);
                                    $badge = $hasPermission ? '<span class="badge bg-success">✓ Allowed</span>' : '<span class="badge bg-danger">✗ Denied</span>';
                                    echo "<tr>";
                                    echo "<td><code>" . htmlspecialchars($permission) . "</code></td>";
                                    echo "<td>$badge</td>";
                                    echo "<td>" . htmlspecialchars($displayName) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Results -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-vial me-2"></i>Test Results & Expected Behavior
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isAdmin()): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-crown me-2"></i>ADMIN Role Detected</h5>
                            <p>You have <strong>FULL ACCESS</strong> to all features:</p>
                            <ul>
                                <li>✅ Create, Read, Update, Delete all data</li>
                                <li>✅ Manage all modules (Karyawan, Penilaian, Reward, Punishment)</li>
                                <li>✅ Manage Users</li>
                                <li>✅ Backup Database</li>
                                <li>✅ View Reports</li>
                            </ul>
                        </div>
                    <?php elseif (isHRDAdmin()): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-briefcase me-2"></i>HRD ADMIN Role Detected</h5>
                            <p>You have <strong>CRUD ACCESS</strong> for HR modules:</p>
                            <ul>
                                <li>✅ Create, Read, Update, Delete (Karyawan, Penilaian, Reward, Punishment)</li>
                                <li>✅ View Reports</li>
                                <li>❌ Cannot manage Users</li>
                                <li>❌ Cannot backup database</li>
                            </ul>
                        </div>
                    <?php elseif (isDirektur()): ?>
                        <div class="alert alert-primary">
                            <h5><i class="fas fa-eye me-2"></i>DIREKTUR Role Detected</h5>
                            <p>You have <strong>READ ONLY ACCESS</strong>:</p>
                            <ul>
                                <li>✅ View Dashboard</li>
                                <li>✅ View Reports</li>
                                <li>✅ Read all data</li>
                                <li>❌ Cannot Create data</li>
                                <li>❌ Cannot Update data</li>
                                <li>❌ Cannot Delete data</li>
                                <li>❌ Cannot manage any modules</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary">
                            <h5>Unknown Role</h5>
                            <p>Role not recognized. Please contact administrator.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Information -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>How This Works
                    </h5>
                </div>
                <div class="card-body">
                    <h6>🔐 Permission System</h6>
                    <p>The system stores permissions in the database as JSON and loads them into the session on login.</p>
                    
                    <h6>📝 For Developers</h6>
                    <p>Use these functions in your code:</p>
                    <pre><code>// Check permissions
canCreate()      // Can user create?
canRead()        // Can user read?
canUpdate()      // Can user update?
canDelete()      // Can user delete?
hasPermission('permission_name')  // Check specific permission
isAdmin()        // Is user admin?
isHRDAdmin()     // Is user HRD admin?
isDirektur()     // Is user direktur?

// Restrict access
restrictWriteAccess()  // Redirect if can't write
restrictAdminAccess()  // Redirect if not admin</code></pre>

                    <h6>🎯 Use Cases</h6>
                    <ul>
                        <li>In create.php, edit.php, delete.php: Call <code>restrictWriteAccess()</code> at the top</li>
                        <li>In templates: Use <code>if (canCreate())</code> to show/hide buttons</li>
                        <li>In queries: Filter results based on user role</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="row mt-4 mb-4">
        <div class="col-12">
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
