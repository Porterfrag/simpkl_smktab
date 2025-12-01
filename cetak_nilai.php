<?php
session_start();
require 'config/koneksi.php';
require 'core/fpdf/fpdf.php'; 

if (!isset($_SESSION['user_id'])) { die("Akses dilarang."); }
if (!isset($_GET['id_siswa'])) { die("ID Siswa tidak ditemukan."); }

$id_siswa = $_GET['id_siswa'];

try {
    $sql = "SELECT s.nama_lengkap, s.nis, s.kelas, s.jurusan,
                p.nama_perusahaan, p.kontak_person as pembimbing_dudi, 
                g.nama_guru, g.nip as nip_guru, n.* FROM siswa s
            LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
            LEFT JOIN pembimbing g ON s.id_pembimbing = g.id_pembimbing
            LEFT JOIN penilaian n ON s.id_siswa = n.id_siswa
            WHERE s.id_siswa = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_siswa]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data || empty($data['aspek_disiplin'])) {
        die("<script>alert('Data nilai belum lengkap!'); window.close();</script>");
    }

    // --- LOGIKA NOMOR SERTIFIKAT ---
    $no_urut = 0;
    if (!empty($data['no_sertifikat'])) {
        $no_urut = $data['no_sertifikat'];
    } else {
        $stmt_max = $pdo->query("SELECT MAX(no_sertifikat) as max_no FROM penilaian");
        $row_max = $stmt_max->fetch(PDO::FETCH_ASSOC);
        $last_no = $row_max['max_no'];
        $no_urut = ($last_no == 0 || $last_no == null) ? 316 : $last_no + 1;

        $stmt_update = $pdo->prepare("UPDATE penilaian SET no_sertifikat = :no WHERE id_penilaian = :id_p");
        $stmt_update->execute([':no' => $no_urut, ':id_p' => $data['id_penilaian']]);
    }
    $nomor_sertifikat_full = "NOMOR: " . $no_urut . "/SERT/SMKNST/" . date('Y');
    // --------------------------------

    $total = $data['aspek_disiplin'] + $data['aspek_kompetensi'] + $data['aspek_kerjasama'] + $data['aspek_inisiatif'];
    $rata_rata = $total / 4;
    
    $predikat = "Cukup";
    if ($rata_rata >= 90) $predikat = "Sangat Baik (Excellent)";
    elseif ($rata_rata >= 80) $predikat = "Baik (Good)";
    elseif ($rata_rata >= 70) $predikat = "Cukup (Fair)";

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

class PDF_Sertifikat extends FPDF {
    function Header() {
        $this->SetLineWidth(1);
        $this->Rect(5, 5, 320, 205); 
        $this->SetLineWidth(0.5);
        $this->Rect(8, 8, 314, 199); 
        
        if(file_exists('assets/images/logo-smk.png')) {
            $this->Image('assets/images/logo-smk.png', 155, 12, 20);
        }
        $this->Ln(25); 
    }
}

$pdf = new PDF_Sertifikat('L', 'mm', array(215, 330)); 
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(false); 
$pdf->AddPage();

// --- JUDUL ---
$pdf->SetFont('Times', 'B', 20); 
$pdf->Cell(0, 8, 'SERTIFIKAT PRAKTIK KERJA LAPANGAN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, $nomor_sertifikat_full, 0, 1, 'C');
$pdf->Ln(3);

// --- IDENTITAS ---
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Kepala SMK Negeri 1 Sungai Tabuk dengan ini menerangkan bahwa:', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('Times', 'BI', 24); 
$pdf->SetTextColor(0, 51, 102);
$pdf->Cell(0, 10, strtoupper($data['nama_lengkap']), 0, 1, 'C');

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'NIS: ' . $data['nis'] . '  |  Kelas: ' . $data['kelas'] . '  |  Jurusan: ' . $data['jurusan'], 0, 1, 'C');

$pdf->Ln(4);
$pdf->MultiCell(0, 5, "Telah melaksanakan dan menyelesaikan Praktik Kerja Lapangan (PKL) dengan hasil yang memuaskan di:", 0, 'C');

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, strtoupper($data['nama_perusahaan']), 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, "Dengan rincian nilai sebagai berikut:", 0, 1, 'C');
$pdf->Ln(3);

// --- TABEL NILAI ---
$w_label = 110;
$w_nilai = 30;
$start_x = (330 - ($w_label + $w_nilai)) / 2; 
$h_row = 7; 

$pdf->SetX($start_x);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell($w_label, 8, 'ASPEK PENILAIAN', 1, 0, 'C', true);
$pdf->Cell($w_nilai, 8, 'NILAI', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);

$aspek = [
    '1. Disiplin & Kehadiran' => $data['aspek_disiplin'],
    '2. Kompetensi Teknis / Keterampilan' => $data['aspek_kompetensi'],
    '3. Kerjasama (Teamwork)' => $data['aspek_kerjasama'],
    '4. Inisiatif & Kreativitas' => $data['aspek_inisiatif']
];

foreach($aspek as $label => $nilai) {
    $pdf->SetX($start_x);
    $pdf->Cell($w_label, $h_row, $label, 1, 0);
    $pdf->Cell($w_nilai, $h_row, $nilai, 1, 1, 'C');
}

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetX($start_x);
$pdf->Cell($w_label, $h_row, 'NILAI RATA-RATA', 1, 0, 'R');
$pdf->Cell($w_nilai, $h_row, number_format($rata_rata, 2), 1, 1, 'C', true);

$pdf->Ln(3);
$pdf->SetFont('Arial', 'I', 11);
$pdf->Cell(0, 6, "Predikat: " . $predikat, 0, 1, 'C');


// --- TANDA TANGAN (POSISI BARU) ---
$pdf->SetFont('Arial', '', 11);
$y_base = 165; 
$y_kanan = 165; 

$x_kiri = 40;
$x_kanan = 230; 

// KIRI: KEPALA SEKOLAH
$pdf->SetXY($x_kiri, $y_base);
$pdf->Cell(70, 5, 'Mengetahui,', 0, 1, 'C');
$pdf->SetXY($x_kiri, $y_base + 5);
$pdf->Cell(70, 5, 'Kepala Sekolah', 0, 1, 'C');

$pdf->SetXY($x_kiri, $y_base + 30);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(70, 5, '( Nama Kepala Sekolah )', 0, 1, 'C'); // Ganti dengan nama asli jika mau statis
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY($x_kiri, $y_base + 35);
$pdf->Cell(70, 5, 'NIP. ...................................', 0, 1, 'C');


// KANAN: PEMBIMBING DU/DI (PIHAK PERUSAHAAN)
$pdf->SetFont('Arial', '', 11);
$pdf->SetXY($x_kanan, $y_kanan); 
$pdf->Cell(80, 5, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'C');
$pdf->SetXY($x_kanan, $y_kanan + 5);
$pdf->Cell(80, 5, 'Pembimbing DU/DI,', 0, 1, 'C');

$pdf->SetXY($x_kanan, $y_kanan + 30);
$pdf->SetFont('Arial', 'B', 11);
// Menggunakan data kontak_person dari tabel perusahaan sebagai nama pembimbing
$nama_pembimbing_dudi = !empty($data['pembimbing_dudi']) ? $data['pembimbing_dudi'] : '( ................................... )';
$pdf->Cell(80, 5, $nama_pembimbing_dudi, 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY($x_kanan, $y_kanan + 35);
// Pembimbing DUDI biasanya tidak punya NIP, bisa dikosongkan atau diganti Jabatan
$pdf->Cell(80, 5, 'Jabatan: ..............................', 0, 1, 'C');

$pdf->Output('I', 'Sertifikat_' . $data['nis'] . '.pdf');
?>