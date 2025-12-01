<?php
session_start();
require 'config/koneksi.php';
require 'core/fpdf/fpdf.php'; 

// 1. Cek Akses & ID
if (!isset($_SESSION['user_id'])) { die("Akses dilarang."); }
if (!isset($_GET['id_siswa'])) { die("ID Siswa tidak ditemukan."); }

$id_siswa = $_GET['id_siswa'];

// 2. Ambil Data Lengkap
try {
    $sql = "SELECT 
                s.nama_lengkap, s.nis, s.kelas, s.jurusan,
                p.nama_perusahaan, p.alamat
            FROM siswa s
            LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
            WHERE s.id_siswa = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_siswa]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data || empty($data['nama_perusahaan'])) {
        die("Data tidak lengkap atau siswa belum di-plotting.");
    }

    // --- LOGIKA NOMOR SURAT (SAMA SEPERTI SEBELUMNYA) ---
    $stmt_cek_surat = $pdo->prepare("SELECT * FROM surat_keluar WHERE id_siswa = :id");
    $stmt_cek_surat->execute([':id' => $id_siswa]);
    $surat_exist = $stmt_cek_surat->fetch(PDO::FETCH_ASSOC);

    if ($surat_exist) {
        $nomor_surat_full = $surat_exist['nomor_surat_full'];
    } else {
        $tahun_ini = date('Y');
        $sql_last = "SELECT MAX(nomor_urut) as last_no FROM surat_keluar WHERE YEAR(tanggal_surat) = :thn";
        $stmt_last = $pdo->prepare($sql_last);
        $stmt_last->execute([':thn' => $tahun_ini]);
        $row_last = $stmt_last->fetch(PDO::FETCH_ASSOC);
        
        $next_no = ($row_last['last_no']) ? $row_last['last_no'] + 1 : 1;
        $nomor_surat_full = "421.5/" . str_pad($next_no, 3, '0', STR_PAD_LEFT) . "/PKL/SMK/" . $tahun_ini;
        $tgl_surat_db = date('Y-m-d');

        $sql_insert = "INSERT INTO surat_keluar (id_siswa, nomor_urut, nomor_surat_full, tanggal_surat) VALUES (:id, :urut, :full, :tgl)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([':id' => $id_siswa, ':urut' => $next_no, ':full' => $nomor_surat_full, ':tgl' => $tgl_surat_db]);
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// ==========================================
// PDF CLASS CUSTOM
// ==========================================
class PDF_Surat extends FPDF {
    function Header() {
        if(file_exists('assets/images/logo-smk.png')) {
            $this->Image('assets/images/logo-smk.png', 20, 10, 25);
        }
        
        // KOP SURAT (Teks Tengah)
        $this->SetFont('Times', 'B', 14);
        $this->Cell(0, 6, 'PEMERINTAH PROVINSI KALIMANTAN SELATAN', 0, 1, 'C');
        $this->SetFont('Times', 'B', 16);
        $this->Cell(0, 6, 'DINAS PENDIDIKAN DAN KEBUDAYAAN', 0, 1, 'C');
        $this->SetFont('Times', 'B', 18);
        $this->Cell(0, 7, 'SMK NEGERI 1 SUNGAI TABUK', 0, 1, 'C');
        $this->SetFont('Times', '', 10);
        $this->Cell(0, 10, 'Jl. Pematang Panjang Km. 3, Kec. Sungai Tabuk, Kab. Banjar', 0, 1, 'C');
        $this->Cell(0, 5, 'Website: smkn1sungaitabuk.sch.id | Email: info@smkn1sungaitabuk.sch.id', 0, 1, 'C');
        
        $this->SetLineWidth(1);
        $this->Line(20, 40, 190, 40);
        $this->SetLineWidth(0.2);
        $this->Line(20, 41, 190, 41);
        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Times', 'I', 8);
        $this->Cell(0, 10, 'Dokumen ini dicetak otomatis oleh SIMPKL pada ' . date('d/m/Y H:i'), 0, 0, 'C');
    }
}

// --- MULAI CETAK ---
$pdf = new PDF_Surat('P', 'mm', 'A4');
$pdf->SetMargins(25, 20, 25); // Kiri, Atas, Kanan
$pdf->AddPage();
$pdf->SetFont('Times', '', 12);

// Tanggal Surat
$tgl_cetak = date('d F Y');
$pdf->Cell(0, 5, 'Banjarmasin, ' . $tgl_cetak, 0, 1, 'R');
$pdf->Ln(5);

// --- BAGIAN NOMOR/LAMPIRAN (LURUS) ---
// Trik: Gunakan Cell dengan lebar tetap untuk Label
$w_label = 25; // Lebar kolom "Nomor", "Lampiran"
$w_titik = 5;  // Lebar kolom ":"
// Sisa lebar otomatis

$pdf->Cell($w_label, 5, 'Nomor', 0, 0);
$pdf->Cell($w_titik, 5, ':', 0, 0);
$pdf->Cell(0, 5, $nomor_surat_full, 0, 1);

$pdf->Cell($w_label, 5, 'Lampiran', 0, 0);
$pdf->Cell($w_titik, 5, ':', 0, 0);
$pdf->Cell(0, 5, '-', 0, 1);

$pdf->Cell($w_label, 5, 'Perihal', 0, 0);
$pdf->Cell($w_titik, 5, ':', 0, 0);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 5, 'Permohonan Praktik Kerja Lapangan (PKL)', 0, 1);
$pdf->SetFont('Times', '', 12);

$pdf->Ln(8);

// --- TUJUAN ---
$pdf->Cell(0, 5, 'Kepada Yth.', 0, 1);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 5, 'Pimpinan ' . $data['nama_perusahaan'], 0, 1);
$pdf->SetFont('Times', '', 12);
// Gunakan MultiCell agar alamat panjang turun baris rapi
$pdf->MultiCell(0, 5, 'di - ' . "\n" . ($data['alamat'] ? $data['alamat'] : 'Tempat'));

$pdf->Ln(8);

// --- ISI PARAGRAF 1 ---
$pdf->MultiCell(0, 6, "Dengan hormat,\n\nSehubungan dengan pelaksanaan program kurikulum Sekolah Menengah Kejuruan (SMK), bersama ini kami mengajukan permohonan kepada Bapak/Ibu untuk dapat menerima siswa kami melaksanakan Praktik Kerja Lapangan (PKL) di perusahaan/instansi yang Bapak/Ibu pimpin.", 0, 'J');

$pdf->Ln(4);
$pdf->Cell(0, 6, 'Adapun data siswa tersebut adalah sebagai berikut:', 0, 1);
$pdf->Ln(2);

// --- TABEL DATA SISWA (RAPID & LURUS) ---
// Kita gunakan indentasi (SetX) dan lebar label yang konsisten
$indent = 35; // Jarak dari kiri kertas
$w_label_siswa = 40; // Lebar label "Nama", "NIS"
$w_titik_siswa = 5;

$pdf->SetX($indent);
$pdf->Cell($w_label_siswa, 6, 'Nama', 0, 0);
$pdf->Cell($w_titik_siswa, 6, ':', 0, 0);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 6, $data['nama_lengkap'], 0, 1);

$pdf->SetFont('Times', '', 12);
$pdf->SetX($indent);
$pdf->Cell($w_label_siswa, 6, 'NIS', 0, 0);
$pdf->Cell($w_titik_siswa, 6, ':', 0, 0);
$pdf->Cell(0, 6, $data['nis'], 0, 1);

$pdf->SetX($indent);
$pdf->Cell($w_label_siswa, 6, 'Kelas / Jurusan', 0, 0);
$pdf->Cell($w_titik_siswa, 6, ':', 0, 0);
$pdf->Cell(0, 6, $data['kelas'] . ' / ' . $data['jurusan'], 0, 1);

$pdf->Ln(4);

// --- PENUTUP ---
$pdf->MultiCell(0, 6, "Demikian surat permohonan ini kami sampaikan. Besar harapan kami agar permohonan ini dapat dikabulkan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.", 0, 'J');

$pdf->Ln(15);

// --- TANDA TANGAN ---
// Posisikan di kanan halaman (sekitar 120mm dari kiri)
$posisi_ttd = 120; 

$pdf->SetX($posisi_ttd);
$pdf->Cell(60, 5, 'Hormat kami,', 0, 1, 'C');
$pdf->SetX($posisi_ttd);
$pdf->Cell(60, 5, 'Kepala Sekolah,', 0, 1, 'C');

$pdf->Ln(25); // Ruang TTD

$pdf->SetX($posisi_ttd);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(60, 5, '( Nama Kepala Sekolah )', 0, 1, 'C');

$pdf->SetX($posisi_ttd);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(60, 5, 'NIP. ...................................', 0, 1, 'C');

$pdf->Output('I', 'Surat_Pengantar_' . $data['nis'] . '.pdf');
?>