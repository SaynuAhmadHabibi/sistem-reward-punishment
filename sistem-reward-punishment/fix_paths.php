<?php
/**
 * Fix require_once paths in subdirectories
 * Convert ../../includes to ../includes
 */

$files_to_fix = [
    // Reward folder
    'c:\xampp\htdocs\sistem-reward-punishment\reward\cetak_piagam.php',
    
    // Punishment folder  
    'c:\xampp\htdocs\sistem-reward-punishment\punishment\cetak_sp.php',
    
    // Penilaian folder
    'c:\xampp\htdocs\sistem-reward-punishment\penilaian\topsis.php',
    'c:\xampp\htdocs\sistem-reward-punishment\penilaian\edit.php',
    'c:\xampp\htdocs\sistem-reward-punishment\penilaian\create.php',
    'c:\xampp\htdocs\sistem-reward-punishment\penilaian\delete.php',
    
    // Karyawan folder
    'c:\xampp\htdocs\sistem-reward-punishment\karyawan\view.php',
    'c:\xampp\htdocs\sistem-reward-punishment\karyawan\index.php',
    'c:\xampp\htdocs\sistem-reward-punishment\karyawan\edit.php',
    'c:\xampp\htdocs\sistem-reward-punishment\karyawan\delete.php',
    'c:\xampp\htdocs\sistem-reward-punishment\karyawan\create.php',
    
    // Backup folder
    'c:\xampp\htdocs\sistem-reward-punishment\backup\index.php',
    'c:\xampp\htdocs\sistem-reward-punishment\backup\backup_info.php',
];

$count = 0;
foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Replace ../../includes with ../includes
        $new_content = str_replace("require_once '../../includes/", "require_once '../includes/", $content);
        $new_content = str_replace('require_once "../../includes/', 'require_once "../includes/', $new_content);
        
        // Replace ../../ in redirect paths
        $new_content = str_replace("redirect('../../", "redirect('../", $new_content);
        $new_content = str_replace('redirect("../../', 'redirect("../', $new_content);
        
        if ($new_content !== $content) {
            file_put_contents($file, $new_content);
            echo "✓ Fixed: " . basename($file) . "\n";
            $count++;
        }
    } else {
        echo "✗ File not found: $file\n";
    }
}

echo "\n✅ Fixed $count files!\n";
?>
