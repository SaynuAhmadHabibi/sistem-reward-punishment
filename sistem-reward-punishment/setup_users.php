<?php
/**
 * Setup Initial Users with Permission Levels
 * Admin & HRD: CRUD (Create, Read, Update, Delete)
 * Direktur: READ Only
 */

$conn = mysqli_connect('localhost', 'root', '', 'sistem_reward_punishment');

if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

echo "Setting up users with permission levels...\n\n";

mysqli_report(MYSQLI_REPORT_OFF);

// Define users with their roles and permissions
$users = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'nama' => 'Administrator',
        'email' => 'admin@company.com',
        'role' => 'admin',
        'permissions' => json_encode([
            'view_dashboard' => true,
            'manage_users' => true,
            'manage_karyawan' => true,
            'manage_penilaian' => true,
            'manage_reward' => true,
            'manage_punishment' => true,
            'view_laporan' => true,
            'backup_data' => true,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true
        ])
    ],
    [
        'username' => 'hrd_admin',
        'password' => 'hrd_admin123',
        'nama' => 'HRD Administrator',
        'email' => 'hrd@company.com',
        'role' => 'hrd_admin',
        'permissions' => json_encode([
            'view_dashboard' => true,
            'manage_users' => false,
            'manage_karyawan' => true,
            'manage_penilaian' => true,
            'manage_reward' => true,
            'manage_punishment' => true,
            'view_laporan' => true,
            'backup_data' => false,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true
        ])
    ],
    [
        'username' => 'direktur',
        'password' => 'direktur123',
        'nama' => 'Direktur Utama',
        'email' => 'direktur@company.com',
        'role' => 'direktur',
        'permissions' => json_encode([
            'view_dashboard' => true,
            'manage_users' => false,
            'manage_karyawan' => false,
            'manage_penilaian' => false,
            'manage_reward' => false,
            'manage_punishment' => false,
            'view_laporan' => true,
            'backup_data' => false,
            'can_create' => false,
            'can_read' => true,
            'can_update' => false,
            'can_delete' => false
        ])
    ]
];

// Insert users
foreach ($users as $user) {
    $username = $user['username'];
    $password = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $nama = $user['nama'];
    $email = $user['email'];
    $role = $user['role'];
    $permissions = $user['permissions'];
    
    // Check if user exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($check) > 0) {
        // Update existing user
        $sql = "UPDATE users SET password = ?, nama = ?, email = ?, role = ?, permissions = ? WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssss', $password, $nama, $email, $role, $permissions, $username);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✓ Updated: $username ($role)\n";
        } else {
            echo "✗ Error updating $username: " . mysqli_error($conn) . "\n";
        }
        mysqli_stmt_close($stmt);
    } else {
        // Create new user
        $sql = "INSERT INTO users (username, password, nama, email, role, permissions) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssss', $username, $password, $nama, $email, $role, $permissions);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✓ Created: $username ($role)\n";
        } else {
            echo "✗ Error creating $username: " . mysqli_error($conn) . "\n";
        }
        mysqli_stmt_close($stmt);
    }
}

echo "\n✅ User setup complete!\n\n";

echo "Available Users & Credentials:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. ADMIN - Full Access (CRUD all modules + System Admin)\n";
echo "   Username: admin\n";
echo "   Password: admin123\n";
echo "   Permissions: Full CRUD + Manage Users + Backup Data\n\n";

echo "2. HRD ADMIN - CRUD Access (HR & Performance Management)\n";
echo "   Username: hrd_admin\n";
echo "   Password: hrd_admin123\n";
echo "   Permissions: CRUD on Karyawan, Penilaian, Reward, Punishment\n";
echo "               (Cannot: Manage Users, Backup Data)\n\n";

echo "3. DIREKTUR - READ ONLY (View Reports & Dashboards)\n";
echo "   Username: direktur\n";
echo "   Password: direktur123\n";
echo "   Permissions: READ only - View Dashboard & Laporan\n";
echo "               (Cannot: Create, Update, Delete any data)\n\n";

echo "═══════════════════════════════════════════════════════════════\n";

// Display permission matrix
echo "\n📊 PERMISSION MATRIX:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$permission_matrix = [
    'can_create' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✗'],
    'can_read' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✓'],
    'can_update' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✗'],
    'can_delete' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✗'],
    'manage_users' => ['Admin' => '✓', 'HRD' => '✗', 'Direktur' => '✗'],
    'manage_karyawan' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✗'],
    'manage_penilaian' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✗'],
    'manage_reward' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✗'],
    'manage_punishment' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✗'],
    'view_laporan' => ['Admin' => '✓', 'HRD' => '✓', 'Direktur' => '✓'],
    'backup_data' => ['Admin' => '✓', 'HRD' => '✗', 'Direktur' => '✗'],
];

printf("%-20s | %-10s | %-10s | %-10s\n", 'Permission', 'Admin', 'HRD', 'Direktur');
echo str_repeat("─", 55) . "\n";

foreach ($permission_matrix as $permission => $roles) {
    printf("%-20s | %-10s | %-10s | %-10s\n", 
        str_replace('_', ' ', ucfirst($permission)),
        $roles['Admin'],
        $roles['HRD'],
        $roles['Direktur']
    );
}

echo "\n✅ Setup Complete! Users are ready to login.\n";

mysqli_close($conn);
?>
