<?php
session_start();
require 'config/koneksi.php';
require 'core/fpdf/fpdf.php'; 

if (!isset($_SESSION['user_id'])) {
    die("Anda harus login untuk mengakses halaman ini.");
}
if (!isset($_GET['id_siswa'])) {
    die("Error: ID Siswa tidak ditemukan.");
}

$id_siswa = $_GET['id_siswa'];
$role = $_SESSION['role'];
$id_ref = $_SESSION['id_ref']; 

try {
    $sql_siswa = "SELECT siswa.nama_lengkap, siswa.nis, siswa.kelas, perusahaan.nama_perusahaan 
                  FROM siswa 
                  LEFT JOIN perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
                  WHERE siswa.id_siswa = :id_siswa";

    if ($role == 'pembimbing') {
        $sql_siswa .= " AND siswa.id_pembimbing = :id_pembimbing";
    }
    
    $stmt_siswa = $pdo->prepare($sql_siswa);
    $params_siswa = [':id_siswa' => $id_siswa];
    if ($role == 'pembimbing') {
        $params_siswa[':id_pembimbing'] = $id_ref;
    }
    
    $stmt_siswa->execute($params_siswa);
    $siswa = $stmt_siswa->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        die("Data siswa tidak ditemukan atau Anda tidak punya hak akses.");
    }

    $sql_jurnal = "SELECT * FROM jurnal_harian 
                   WHERE id_siswa = :id_siswa AND status_validasi = 'Disetujui' 
                   ORDER BY tanggal ASC";
    $stmt_jurnal = $pdo->prepare($sql_jurnal);
    $stmt_jurnal->execute([':id_siswa' => $id_siswa]);
    $jurnal_list = $stmt_jurnal->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error database: " . $e->getMessage());
}



class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 10, 'JURNAL HARIAN PRAKTIK KERJA LAPANGAN', 0, 1, 'C');
        $this->SetFont('Arial','B',12);
        $this->Cell(0, 10, 'SMKN 1 SUNGAI TABUK', 0, 1, 'C'); 
        $this->Ln(5); 
    }

    function Footer()
    {
        $this->SetY(-15); 
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF('P', 'mm', 'A4'); 
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'DATA SISWA', 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 7, 'Nama Siswa', 0, 0, 'L');
$pdf->Cell(5, 7, ':', 0, 0, 'C');
$pdf->Cell(0, 7, $siswa['nama_lengkap'], 0, 1, 'L');

$pdf->Cell(40, 7, 'NIS', 0, 0, 'L');
$pdf->Cell(5, 7, ':', 0, 0, 'C');
$pdf->Cell(0, 7, $siswa['nis'], 0, 1, 'L');

$pdf->Cell(40, 7, 'Kelas', 0, 0, 'L');
$pdf->Cell(5, 7, ':', 0, 0, 'C');
$pdf->Cell(0, 7, $siswa['kelas'], 0, 1, 'L');

$pdf->Cell(40, 7, 'Tempat PKL', 0, 0, 'L');
$pdf->Cell(5, 7, ':', 0, 0, 'C');
$nama_perusahaan = isset($siswa['nama_perusahaan']) ? $siswa['nama_perusahaan'] : '-';
$pdf->Cell(0, 7, $nama_perusahaan, 0, 1, 'L');

$pdf->Ln(10); 

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'RINCIAN JURNAL HARIAN (YANG DISETUJUI)', 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230); 
$pdf->Cell(10, 10, 'No', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell(150, 10, 'Uraian Kegiatan', 1, 1, 'C', true); 

$pdf->SetFont('Arial', '', 10);
$no = 1;
if (empty($jurnal_list)) {
    $pdf->Cell(190, 10, 'Tidak ada jurnal yang disetujui.', 1, 1, 'C');
} else {
    foreach ($jurnal_list as $jurnal) {
        $pdf->Cell(10, 10, $no++, 1, 0, 'C'); 
        $pdf->Cell(30, 10, date('d/m/Y', strtotime($jurnal['tanggal'])), 1, 0, 'C'); 
        
        $pdf->MultiCell(150, 10, $jurnal['kegiatan'], 1, 'L');
    }
}

$pdf->Output('I', 'Jurnal_' . $siswa['nis'] . '.pdf'); 
?>