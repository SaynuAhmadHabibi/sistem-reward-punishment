<?php
/**
 * Konfigurasi TOPSIS - Sistem Reward & Punishment
 * 
 * File ini berisi konfigurasi kriteria, bobot, dan threshold untuk TOPSIS
 */

// =============================================
// DEFINISI KRITERIA TOPSIS
// =============================================

define('TOPSIS_CRITERIA', [
    'kinerja' => [
        'name' => 'Kinerja',
        'type' => 'benefit',          // benefit atau cost
        'weight' => 0.35,             // Bobot 35%
        'min' => 0,
        'max' => 100,
        'description' => 'Penilaian kualitas dan kuantitas pekerjaan'
    ],
    'kedisiplinan' => [
        'name' => 'Kedisiplinan',
        'type' => 'benefit',          // benefit (semakin tinggi semakin baik)
        'weight' => 0.25,             // Bobot 25%
        'min' => 0,
        'max' => 100,
        'description' => 'Ketepatan waktu dan kehadiran'
    ],
    'kerjasama' => [
        'name' => 'Kerjasama',
        'type' => 'benefit',          // benefit
        'weight' => 0.20,             // Bobot 20%
        'min' => 0,
        'max' => 100,
        'description' => 'Kemampuan bekerja sama dalam tim'
    ],
    'absensi' => [
        'name' => 'Absensi',
        'type' => 'cost',             // cost (semakin rendah semakin baik)
        'weight' => 0.20,             // Bobot 20%
        'min' => 0,
        'max' => 30,
        'description' => 'Jumlah hari libur/tidak masuk (dalam hari)'
    ]
]);

// =============================================
// KATEGORI REWARD & PUNISHMENT
// =============================================

define('TOPSIS_CATEGORIES', [
    'reward' => [
        'name' => 'Reward',
        'min_value' => 0.7,           // Nilai preferensi >= 0.7
        'max_value' => 1.0,
        'levels' => [
            'sangat_baik' => [
                'min' => 0.8,
                'max' => 1.0,
                'label' => 'Sangat Baik',
                'description' => 'Performa luar biasa'
            ],
            'baik' => [
                'min' => 0.7,
                'max' => 0.8,
                'label' => 'Baik',
                'description' => 'Performa di atas rata-rata'
            ]
        ]
    ],
    'normal' => [
        'name' => 'Normal',
        'min_value' => 0.3,           // 0.3 <= nilai < 0.7
        'max_value' => 0.7,
        'levels' => [
            'normal' => [
                'min' => 0.3,
                'max' => 0.7,
                'label' => 'Normal',
                'description' => 'Performa sesuai standar'
            ]
        ]
    ],
    'punishment' => [
        'name' => 'Punishment',
        'min_value' => 0.0,           // Nilai preferensi < 0.3
        'max_value' => 0.3,
        'levels' => [
            'berat' => [
                'min' => 0.0,
                'max' => 0.1,
                'label' => 'Berat',
                'description' => 'Performa sangat rendah, perlu tindakan serius'
            ],
            'sedang' => [
                'min' => 0.1,
                'max' => 0.2,
                'label' => 'Sedang',
                'description' => 'Performa rendah, perlu perbaikan'
            ],
            'ringan' => [
                'min' => 0.2,
                'max' => 0.3,
                'label' => 'Ringan',
                'description' => 'Performa di bawah standar, perlu diperbaiki'
            ]
        ]
    ]
]);

// =============================================
// REKOMENDASI TINDAKAN
// =============================================

define('TOPSIS_RECOMMENDATIONS', [
    'reward' => [
        'sangat_baik' => [
            'Berikan apresiasi dan pengakuan khusus atas pencapaian luar biasa',
            'Pertimbangkan promosi atau kenaikan gaji',
            'Kirim mengikuti program leadership development',
            'Jadikan sebagai mentor untuk karyawan lain',
            'Pertimbangkan bonus insentif tambahan',
            'Berikan kesempatan proyek khusus'
        ],
        'baik' => [
            'Berikan pengakuan dan apresiasi',
            'Pertimbangkan bonus atau insentif',
            'Kirim ke program pengembangan karir',
            'Pertahankan motivasi karyawan',
            'Berikan tanggung jawab tambahan'
        ]
    ],
    'normal' => [
        'normal' => [
            'Pertahankan performa saat ini',
            'Berikan penghargaan rutin (bonus normal)',
            'Tawarkan program pengembangan untuk meningkatkan performa',
            'Pantau perkembangan secara berkala',
            'Berikan feedback konstruktif untuk peningkatan'
        ]
    ],
    'punishment' => [
        'ringan' => [
            'Adakan diskusi untuk memahami hambatan',
            'Berikan coaching dan mentoring',
            'Buat rencana perbaikan bersama',
            'Monitor progress mingguan',
            'Pertimbangkan peringatan lisan',
            'Tawarkan pelatihan tambahan'
        ],
        'sedang' => [
            'Adakan meeting formal dengan surat peringatan I',
            'Buat written performance improvement plan (PIP)',
            'Berikan coaching intensif',
            'Monitor progress bi-weekly',
            'Pertimbangkan penalti atau potongan tunjangan',
            'Jika tidak ada perbaikan, escalate ke tahap berikutnya'
        ],
        'berat' => [
            'Berikan surat peringatan II',
            'Tentukan periode PIP yang ketat (30-60 hari)',
            'Monitor progress intensif',
            'Konsultasikan dengan divisi HR',
            'Siapkan dokumentasi lengkap',
            'Jika masih tidak ada perbaikan, pertimbangkan pemberhentian'
        ]
    ]
]);

// =============================================
// FUNGSI HELPER
// =============================================

/**
 * Dapatkan kategori berdasarkan nilai preferensi
 */
function getTOPSISCategory($preference_value) {
    $categories = TOPSIS_CATEGORIES;
    
    foreach ($categories as $cat => $data) {
        if ($preference_value >= $data['min_value'] && $preference_value <= $data['max_value']) {
            return $cat;
        }
    }
    
    return 'normal';
}

/**
 * Dapatkan level berdasarkan kategori dan nilai preferensi
 */
function getTOPSISLevel($category, $preference_value) {
    $categories = TOPSIS_CATEGORIES;
    
    if (!isset($categories[$category])) {
        return 'normal';
    }
    
    foreach ($categories[$category]['levels'] as $level => $data) {
        if ($preference_value >= $data['min'] && $preference_value <= $data['max']) {
            return $level;
        }
    }
    
    return 'normal';
}

/**
 * Dapatkan rekomendasi berdasarkan kategori dan level
 */
function getTOPSISRecommendations($category, $level = null) {
    $recommendations = TOPSIS_RECOMMENDATIONS;
    
    if (!isset($recommendations[$category])) {
        return [];
    }
    
    if ($level && isset($recommendations[$category][$level])) {
        return $recommendations[$category][$level];
    }
    
    // Return rekomendasi pertama jika level tidak ditemukan
    return reset($recommendations[$category]);
}

/**
 * Validasi nilai kriteria
 */
function validateTOPSISValue($criteria, $value) {
    $criteria_list = TOPSIS_CRITERIA;
    
    if (!isset($criteria_list[$criteria])) {
        return [
            'valid' => false,
            'message' => 'Kriteria tidak dikenal'
        ];
    }
    
    $c = $criteria_list[$criteria];
    
    if ($value < $c['min'] || $value > $c['max']) {
        return [
            'valid' => false,
            'message' => "Nilai {$criteria} harus antara {$c['min']} dan {$c['max']}"
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Nilai valid'
    ];
}

/**
 * Format kategori dan level menjadi string yang readable
 */
function formatTOPSISResult($category, $level) {
    $categories = TOPSIS_CATEGORIES;
    
    if (isset($categories[$category]['levels'][$level])) {
        return $categories[$category]['levels'][$level]['label'];
    }
    
    return ucfirst(str_replace('_', ' ', $level));
}

?>