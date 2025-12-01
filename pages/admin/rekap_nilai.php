<?php
// (Pastikan file ini hanya di-include oleh index.php)
// Cek jika bukan admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

// (Asumsi $pdo sudah ada dari index.php)
try {
    // Query ini mengambil SEMUA siswa, dan MENGGABUNGKAN data nilai jika ada
    $sql = "SELECT 
                siswa.id_siswa,
                siswa.nama_lengkap, 
                siswa.nis, 
                siswa.kelas,
                penilaian.aspek_disiplin,
                penilaian.aspek_kompetensi,
                penilaian.aspek_kerjasama,
                penilaian.aspek_inisiatif,
                pembimbing.nama_guru AS penilai 
            FROM 
                siswa
            LEFT JOIN 
                penilaian ON siswa.id_siswa = penilaian.id_siswa
            LEFT JOIN 
                pembimbing ON penilaian.id_pembimbing = pembimbing.id_pembimbing
            ORDER BY 
                siswa.nama_lengkap ASC";
            
    $stmt = $pdo->query($sql);
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Error: " . $e->getMessage() . "</div>";
}
?>

<h2 class="mb-4">Rekap Nilai Akhir Siswa PKL</h2>
<p class="mb-3">Halaman ini menampilkan rekapitulasi nilai akhir semua siswa.</p>

<a href="pages/admin/export_nilai_excel.php" target="_blank" class="btn btn-success mb-3">
    <i class="fas fa-file-excel me-2"></i> Export Data ke Excel
</a>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>NIS / Kelas</th>
                <th>Nilai Rata-Rata</th>
                <th>Status</th>
                <th>Dinilai Oleh</th>
                <th style="min-width: 100px;">Aksi</th> 
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($siswa_list as $siswa): ?>
                <tr>
                    <td class="text-start"><?php echo $no++; ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($siswa['nis']); ?> / <?php echo htmlspecialchars($siswa['kelas']); ?></td>
                    
                    <?php
                    // Cek apakah siswa sudah dinilai
                    $status_penilaian = isset($siswa['aspek_disiplin']);
                    
                    if ($status_penilaian) {
                        $total_nilai = $siswa['aspek_disiplin'] + $siswa['aspek_kompetensi'] + $siswa['aspek_kerjasama'] + $siswa['aspek_inisiatif'];
                        $rata_rata = $total_nilai / 4;
                        
                        $display_nilai = number_format($rata_rata, 2);
                        $display_status = '<span class="badge bg-success">Sudah Dinilai</span>';
                        $display_penilai = htmlspecialchars(isset($siswa['penilai']) ? $siswa['penilai'] : 'N/A');
                    } else {
                        $display_nilai = '-';
                        $display_status = '<span class="badge bg-secondary">Belum Dinilai</span>';
                        $display_penilai = '-';
                    }
                    ?>
                    
                    <td class="text-center" style="font-weight: bold;"><?php echo $display_nilai; ?></td>
                    <td class="text-start"><?php echo $display_status; ?></td>
                    <td class="text-start"><?php echo $display_penilai; ?></td>
                    
                    <td class="text-center">
                        <?php if ($status_penilaian): ?>
                            <a href="cetak_nilai.php?id_siswa=<?php echo $siswa['id_siswa']; ?>" target="_blank" class="btn btn-sm btn-primary" title="Cetak Sertifikat">
                                <i class="fas fa-print me-1"></i> Sertifikat
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($siswa_list)): ?>
                <tr>
                    <td colspan="7" class="text-center">Data siswa masih kosong.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>