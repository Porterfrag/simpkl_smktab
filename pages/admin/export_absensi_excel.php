<?php
session_start();
require '../../config/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$filename = "Rekap_Total_Absensi_PKL_" . date('Ymd') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

try {
    $sql = "SELECT 
                s.nis, 
                s.nama_lengkap, 
                s.kelas,
                p.nama_perusahaan,
                SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) AS total_hadir,
                SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) AS total_izin,
                SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS total_sakit,
                SUM(CASE WHEN a.status = 'Alpha' THEN 1 ELSE 0 END) AS total_alpha
            FROM siswa s
            LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
            LEFT JOIN absensi a ON s.id_siswa = a.id_siswa
            GROUP BY s.id_siswa
            ORDER BY s.kelas ASC, s.nama_lengkap ASC";
            
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<table border="1">
    <thead>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th>No</th>
            <th>NIS</th>
            <th>Nama Siswa</th>
            <th>Kelas</th>
            <th>Tempat PKL</th>
            <th>Total Hadir</th>
            <th>Total Izin</th>
            <th>Total Sakit</th>
            <th>Total Alpha</th>
            <th>Persentase Kehadiran</th> </tr>
    </thead>
    <tbody>
        <?php $no = 1; ?>
        <?php foreach ($data as $row): ?>
            <?php 
                $total_hari = $row['total_hadir'] + $row['total_izin'] + $row['total_sakit'] + $row['total_alpha'];
                if ($total_hari > 0) {
                    $persentase = round(($row['total_hadir'] / $total_hari) * 100) . '%';
                } else {
                    $persentase = '0%';
                }
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td>'<?php echo htmlspecialchars($row['nis']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_perusahaan'] ?? '-'); ?></td>
                <td><?php echo $row['total_hadir']; ?></td>
                <td><?php echo $row['total_izin']; ?></td>
                <td><?php echo $row['total_sakit']; ?></td>
                
                <?php if ($row['total_alpha'] > 0): ?>
                    <td style="background-color: #ffcccc; color: red; font-weight: bold;"><?php echo $row['total_alpha']; ?></td>
                <?php else: ?>
                    <td>0</td>
                <?php endif; ?>

                <td><?php echo $persentase; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>