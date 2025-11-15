<?php


session_start();
require '../../config/koneksi.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$filename = "Rekap_Nilai_PKL_" . date('Ymd') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

try {
    $sql = "SELECT 
                siswa.nis, 
                siswa.nama_lengkap, 
                siswa.kelas,
                siswa.jurusan,
                perusahaan.nama_perusahaan,
                penilaian.aspek_disiplin,
                penilaian.aspek_kompetensi,
                penilaian.aspek_kerjasama,
                penilaian.aspek_inisiatif,
                pembimbing.nama_guru
            FROM siswa
            LEFT JOIN perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
            LEFT JOIN penilaian ON siswa.id_siswa = penilaian.id_siswa
            LEFT JOIN pembimbing ON penilaian.id_pembimbing = pembimbing.id_pembimbing
            ORDER BY siswa.kelas ASC, siswa.nama_lengkap ASC";
            
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
            <th>Jurusan</th>
            <th>Tempat PKL</th>
            <th>Disiplin</th>
            <th>Kompetensi</th>
            <th>Kerjasama</th>
            <th>Inisiatif</th>
            <th>Nilai Rata-rata</th>
            <th>Penilai</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; ?>
        <?php foreach ($data as $row): ?>
            <?php 
                if (isset($row['aspek_disiplin'])) {
                    $total = $row['aspek_disiplin'] + $row['aspek_kompetensi'] + $row['aspek_kerjasama'] + $row['aspek_inisiatif'];
                    $rata = number_format($total / 4, 2);
                } else {
                    $rata = "Belum Dinilai";
                }
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td>'<?php echo htmlspecialchars($row['nis']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                <td><?php echo htmlspecialchars($row['jurusan']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_perusahaan']); ?></td>
                <td><?php echo $row['aspek_disiplin'] ?? '-'; ?></td>
                <td><?php echo $row['aspek_kompetensi'] ?? '-'; ?></td>
                <td><?php echo $row['aspek_kerjasama'] ?? '-'; ?></td>
                <td><?php echo $row['aspek_inisiatif'] ?? '-'; ?></td>
                <td><?php echo $rata; ?></td>
                <td><?php echo htmlspecialchars($row['nama_guru'] ?? '-'); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>