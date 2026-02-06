<?php
/**
 * PDF Helper untuk generate laporan PDF
 * ✅ FIXED: Penanganan font dan encoding yang lebih baik
 */

require_once __DIR__ . '/../libs/fpdf.php';

class PDFHelper extends FPDF
{
    function Header()
    {
        // HEADER LAPORAN - Gunakan Arial yang pasti ada
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'LAPORAN REWARD & PUNISHMENT', 0, 1, 'C');

        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 7, 'Periode Bulanan', 0, 1, 'C');

        $this->Ln(10);
    }

    function Footer()
    {
        // FOOTER LAPORAN
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }

    function CreateMonthlyReport($data)
    {
        $this->AddPage();
        $this->SetFont('Arial', '', 10);

        // =========================
        // RINGKASAN LAPORAN
        // =========================

        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 7, 'Ringkasan Laporan', 0, 1);

        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Bulan : ' . $this->safeText($data['summary']['bulan']), 0, 1);
        $this->Cell(0, 6, 'Total Reward : ' . $data['summary']['total_reward'], 0, 1);
        $this->Cell(0, 6, 'Total Punishment : ' . $data['summary']['total_punishment'], 0, 1);
        $this->Cell(0, 6, 'Rata-rata TOPSIS : ' . number_format($data['summary']['avg_topsis'], 2), 0, 1);
        $this->Ln(5);

        // =========================
        // DATA REWARD (TABEL)
        // =========================

        if (!empty($data['rewards'])) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 7, 'Data Reward', 0, 1);
            $this->Ln(2);

            // Header tabel
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(200, 220, 255);
            $this->Cell(10, 7, 'No', 1, 0, 'C', true);
            $this->Cell(30, 7, 'NIK', 1, 0, 'C', true);
            $this->Cell(60, 7, 'Nama Karyawan', 1, 0, 'C', true);
            $this->Cell(40, 7, 'Tanggal', 1, 0, 'C', true);
            $this->Cell(30, 7, 'TOPSIS', 1, 0, 'C', true);
            $this->Ln();

            // Data tabel
            $this->SetFont('Arial', '', 8);
            $no = 1;
            foreach ($data['rewards'] as $r) {
                $nama = isset($r['nama_karyawan']) ? $r['nama_karyawan'] : '-';
                $nik = isset($r['nik']) ? $r['nik'] : '-';
                $tanggal = isset($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '-';
                $topsis = isset($r['topsis_score']) ? number_format($r['topsis_score'], 4) : '0.0000';

                $this->Cell(10, 6, $no++, 1, 0, 'C');
                $this->Cell(30, 6, $this->safeText($nik), 1, 0, 'L');
                $this->Cell(60, 6, $this->safeText($nama), 1, 0, 'L');
                $this->Cell(40, 6, $tanggal, 1, 0, 'C');
                $this->Cell(30, 6, $topsis, 1, 0, 'C');
                $this->Ln();
            }
            
            $this->Ln(5);
        }

        // =========================
        // DATA PUNISHMENT (TABEL)
        // =========================

        if (!empty($data['punishments'])) {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 7, 'Data Punishment', 0, 1);
            $this->Ln(2);

            // Header tabel
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(255, 200, 200);
            $this->Cell(10, 7, 'No', 1, 0, 'C', true);
            $this->Cell(30, 7, 'NIK', 1, 0, 'C', true);
            $this->Cell(60, 7, 'Nama Karyawan', 1, 0, 'C', true);
            $this->Cell(40, 7, 'Tanggal', 1, 0, 'C', true);
            $this->Cell(30, 7, 'TOPSIS', 1, 0, 'C', true);
            $this->Ln();

            // Data tabel
            $this->SetFont('Arial', '', 8);
            $no = 1;
            foreach ($data['punishments'] as $p) {
                $nama = isset($p['nama_karyawan']) ? $p['nama_karyawan'] : '-';
                $nik = isset($p['nik']) ? $p['nik'] : '-';
                $tanggal = isset($p['tanggal']) ? date('d/m/Y', strtotime($p['tanggal'])) : '-';
                $topsis = isset($p['topsis_score']) ? number_format($p['topsis_score'], 4) : '0.0000';

                $this->Cell(10, 6, $no++, 1, 0, 'C');
                $this->Cell(30, 6, $this->safeText($nik), 1, 0, 'L');
                $this->Cell(60, 6, $this->safeText($nama), 1, 0, 'L');
                $this->Cell(40, 6, $tanggal, 1, 0, 'C');
                $this->Cell(30, 6, $topsis, 1, 0, 'C');
                $this->Ln();
            }
        }

        // Footer info
        $this->Ln(10);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, 'Dicetak pada: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
    }

    /**
     * ✅ FIXED: Konversi text yang lebih aman
     * Menangani karakter UTF-8 dengan lebih baik
     */
    public function safeText($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Convert to string first
        $text = (string)$text;
        
        // Remove special characters that might cause issues
        $text = str_replace(['�', '�', '�'], '', $text);
        
        // Try to convert encoding
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }
        
        // Fallback to mb_convert_encoding
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        }
        
        // Last resort: just return the text
        return $text;
    }
}