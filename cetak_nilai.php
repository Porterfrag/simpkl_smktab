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
    $sql_siswa = "SELECT 
                    siswa.nama_lengkap, siswa.nis, siswa.kelas, 
                    perusahaan.nama_perusahaan,
                    pembimbing.nama_guru
                  FROM siswa 
                  LEFT JOIN perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
                  LEFT JOIN pembimbing ON siswa.id_pembimbing = pembimbing.id_pembimbing
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
    
    $sql_nilai = "SELECT * FROM penilaian WHERE id_siswa = :id_siswa";
    $stmt_nilai = $pdo->prepare($sql_nilai);
    $stmt_nilai->execute(['id_siswa' => $id_siswa]);
    $nilai = $stmt_nilai->fetch(PDO::FETCH_ASSOC);

    if (!$nilai) {
        $nilai = [
            'aspek_disiplin' => 0,
            'aspek_kompetensi' => 0,
            'aspek_kerjasama' => 0,
            'aspek_inisiatif' => 0,
            'catatan_penilaian' => 'Siswa belum dinilai.'
        ];
    }
    
    $total_nilai = $nilai['aspek_disiplin'] + $nilai['aspek_kompetensi'] + $nilai['aspek_kerjasama'] + $nilai['aspek_inisiatif'];
    $rata_rata = $total_nilai / 4;


} catch (PDOException $e) {
    die("Error database: " . $e->getMessage());
}



class PDF_Nilai extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 10, 'SERTIFIKAT NILAI PRAKTIK KERJA LAPANGAN', 0, 1, 'C');
        $this->SetFont('Arial','B',12);
        $this->Cell(0, 10, 'SMKN 1 CODING', 0, 1, 'C'); 
        $this->SetLineWidth(1);
        $this->Line(10, 32, 200, 32); 
        $this->SetLineWidth(0.2);
        $this->Line(10, 33, 200, 33); 
        $this->Ln(10); 
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function TabelNilai($header, $data)
    {
        $w = array(10, 120, 50); 
        
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230);
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 10, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        $this->SetFont('Arial', '', 11);
        $this->SetFillColor(255);
        $no = 1;
        foreach($data as $aspek => $nilai_aspek)
        {
            $this->Cell($w[0], 10, $no++, 1, 0, 'C');
            $this->Cell($w[1], 10, $aspek, 1, 0, 'L');
            $this->Cell($w[2], 10, $nilai_aspek, 1, 1, 'C');
        }
    }
}

$pdf = new PDF_Nilai('P', 'mm', 'A4');
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
$pdf->Cell(0, 10, 'HASIL PENILAIAN PRAKTIK KERJA LAPANGAN', 0, 1, 'L');

$header_tabel = array('No', 'Aspek Penilaian', 'Nilai (0-100)');
$data_tabel = array(
    'Disiplin & Kehadiran' => $nilai['aspek_disiplin'],
    'Kompetensi Teknis / Keterampilan' => $nilai['aspek_kompetensi'],
    'Kerjasama (Teamwork)' => $nilai['aspek_kerjasama'],
    'Inisiatif & Kreativitas' => $nilai['aspek_inisiatif']
);

$pdf->TabelNilai($header_tabel, $data_tabel); 

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(130, 10, 'NILAI RATA-RATA', 1, 0, 'C');
$pdf->Cell(50, 10, number_format($rata_rata, 2), 1, 1, 'C'); 

$pdf->Ln(10); 

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'CATATAN PEMBIMBING', 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->SetFillColor(250, 250, 250);
$catatan = isset($nilai['catatan_penilaian']) ? $nilai['catatan_penilaian'] : '-';
$pdf->MultiCell(190, 7, $catatan, 1, 'L', true);

$pdf->Ln(15); 

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(130); 
$pdf->Cell(60, 7, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'C'); 
$pdf->Cell(130); 
$pdf->Cell(60, 7, 'Guru Pembimbing,', 0, 1, 'C');
$pdf->Ln(20); 

$pdf->Cell(130); 
$nama_pembimbing = isset($siswa['nama_guru']) ? $siswa['nama_guru'] : '(......................................)';
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 7, $nama_pembimbing, 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(130); 
$pdf->Cell(60, 7, 'NIP: ..............................', 0, 1, 'C');


$pdf->Output('I', 'Nilai_' . $siswa['nis'] . '.pdf');
?>