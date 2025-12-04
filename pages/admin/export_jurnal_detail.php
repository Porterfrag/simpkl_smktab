<?php
session_start();
require '../../config/koneksi.php';

// Cek Akses (Admin & Pembimbing boleh)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'pembimbing'])) {
    die("Akses dilarang!");
}

$id_siswa = $_GET['id_siswa'];

// Ambil Data Siswa
$stmt_s = $pdo->prepare("SELECT s.nama_lengkap, s.nis, s.kelas, p.nama_perusahaan 
                         FROM siswa s 
                         LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan 
                         WHERE id_siswa = ?");
$stmt_s->execute([$id_siswa]);
$siswa = $stmt_s->fetch(PDO::FETCH_ASSOC);

// Header Excel
$filename = "Jurnal_" . str_replace(' ', '_', $siswa['nama_lengkap']) . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil Jurnal
$stmt_j = $pdo->prepare("SELECT * FROM jurnal_harian WHERE id_siswa = ? ORDER BY tanggal DESC");
$stmt_j->execute([$id_siswa]);
$jurnals = $stmt_j->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>LAPORAN KEGIATAN (JURNAL) SISWA</h3>
<table>
    <tr><td>Nama</td><td>: <?php echo $siswa['nama_lengkap']; ?></td></tr>
    <tr><td>NIS</td><td>: <?php echo $siswa['nis']; ?></td></tr>
    <tr><td>Kelas</td><td>: <?php echo $siswa['kelas']; ?></td></tr>
    <tr><td>Tempat PKL</td><td>: <?php echo $siswa['nama_perusahaan']; ?></td></tr>
</table>
<br>

<table border="1">
    <thead>
        <tr style="background-color: #4CAF50; color: white;">
            <th>No</th>
            <th>Tanggal</th>
            <th>Kegiatan</th>
            <th>Status Validasi</th>
            <th>Catatan Pembimbing</th>
        </tr>
    </thead>
    <tbody>
        <?php $no=1; foreach($jurnals as $row): ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
            <td><?php echo nl2br(htmlspecialchars($row['kegiatan'])); ?></td>
            <td><?php echo $row['status_validasi']; ?></td>
            <td><?php echo $row['catatan_pembimbing']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>