<?php
// Stand-alone export script
session_start();
require '../../config/koneksi.php';

// Cek Akses (Hanya Pembimbing)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses dilarang!");
}
$id_pembimbing = $_SESSION['id_ref'];

// Cek ID Siswa
if (!isset($_GET['id_siswa'])) {
    die("ID Siswa tidak ditemukan.");
}
$id_siswa = $_GET['id_siswa'];

// Validasi Kepemilikan (Apakah siswa ini bimbingan pembimbing yang login?)
try {
    $stmt_cek = $pdo->prepare("SELECT nama_lengkap, nis, kelas FROM siswa WHERE id_siswa = :id AND id_pembimbing = :pmb");
    $stmt_cek->execute([':id' => $id_siswa, ':pmb' => $id_pembimbing]);
    $siswa = $stmt_cek->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        die("Data siswa tidak ditemukan atau bukan bimbingan Anda.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Set Filename
$filename = "Rekap_Absensi_" . str_replace(' ', '_', $siswa['nama_lengkap']) . "_" . date('Ymd') . ".xls";

// Header Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil Data Absensi Detail
try {
    $sql = "SELECT tanggal, status, jam_absen, keterangan, dicatat_oleh 
            FROM absensi 
            WHERE id_siswa = :id 
            ORDER BY tanggal ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_siswa]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung Ringkasan
    $rekap = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
    foreach ($data as $row) {
        if (isset($rekap[$row['status']])) {
            $rekap[$row['status']]++;
        }
    }
    
} catch (PDOException $e) {
    echo "Error Data: " . $e->getMessage();
    exit;
}
?>

<h3>REKAPITULASI ABSENSI SISWA PKL</h3>
<table>
    <tr>
        <td><strong>Nama Siswa</strong></td>
        <td>: <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
    </tr>
    <tr>
        <td><strong>NIS</strong></td>
        <td>: <?php echo htmlspecialchars($siswa['nis']); ?></td>
    </tr>
    <tr>
        <td><strong>Kelas</strong></td>
        <td>: <?php echo htmlspecialchars($siswa['kelas']); ?></td>
    </tr>
</table>
<br>

<table border="1">
    <tr style="background-color: #eeeeee;">
        <th colspan="2">Ringkasan Kehadiran</th>
    </tr>
    <tr>
        <td>Hadir</td>
        <td><?php echo $rekap['Hadir']; ?></td>
    </tr>
    <tr>
        <td>Izin</td>
        <td><?php echo $rekap['Izin']; ?></td>
    </tr>
    <tr>
        <td>Sakit</td>
        <td><?php echo $rekap['Sakit']; ?></td>
    </tr>
    <tr>
        <td>Alpha</td>
        <td style="color: red; font-weight: bold;"><?php echo $rekap['Alpha']; ?></td>
    </tr>
</table>
<br>

<table border="1">
    <thead>
        <tr style="background-color: #4CAF50; color: white;">
            <th>No</th>
            <th>Tanggal</th>
            <th>Hari</th>
            <th>Status</th>
            <th>Jam Masuk</th>
            <th>Keterangan</th>
            <th>Dicatat Oleh</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; ?>
        <?php foreach ($data as $row): ?>
            <?php 
                $timestamp = strtotime($row['tanggal']);
                $hari = date('l', $timestamp);
                // Translate Hari (Opsional, Excel biasanya bisa baca format date, tapi manual string lebih aman)
                $hari_indo = [
                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                ];
                $hari_tampil = isset($hari_indo[$hari]) ? $hari_indo[$hari] : $hari;
                
                $color = ($row['status'] == 'Alpha') ? 'background-color: #ffcccc;' : '';
            ?>
            <tr style="<?php echo $color; ?>">
                <td><?php echo $no++; ?></td>
                <td><?php echo date('d/m/Y', $timestamp); ?></td>
                <td><?php echo $hari_tampil; ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['jam_absen'] ? $row['jam_absen'] : '-'); ?></td>
                <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                <td><?php echo htmlspecialchars($row['dicatat_oleh']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>