<?php
// Cek Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { die("Akses dilarang!"); }

$pesan_sukses = '';

// --- PROSES HAPUS ABSENSI ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id_absensi = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM absensi WHERE id_absensi = :id");
        $stmt->execute([':id' => $id_absensi]);
        $pesan_sukses = "Data absensi berhasil dihapus.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// --- AMBIL DATA ABSENSI (LIMIT 1000 TERBARU) ---
try {
    $sql = "SELECT a.*, s.nama_lengkap, s.kelas 
            FROM absensi a
            JOIN siswa s ON a.id_siswa = s.id_siswa
            ORDER BY a.tanggal DESC, a.jam_absen DESC LIMIT 1000";
    $absensi_list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $absensi_list = []; }
?>

<h2 class="mb-4">Data Master Absensi (History)</h2>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success role='alert'"><?php echo $pesan_sukses; ?></div>
<?php endif; ?>

<div class="alert alert-info border-0 shadow-sm">
    <i class="fas fa-info-circle me-2"></i> Halaman ini menampilkan 1000 data absensi terakhir. Gunakan fitur <strong>Search</strong> di tabel untuk mencari data spesifik.
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Status</th>
                <th>Jam</th>
                <th>Ket / Bukti</th>
                <th>Oleh</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($absensi_list as $row): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($row['kelas']); ?></small>
                    </td>
                    <td>
                        <?php 
                            $badge = ($row['status'] == 'Hadir') ? 'bg-success' : (($row['status'] == 'Alpha') ? 'bg-danger' : 'bg-warning text-dark');
                        ?>
                        <span class="badge <?php echo $badge; ?>"><?php echo $row['status']; ?></span>
                    </td>
                    <td><?php echo $row['jam_absen'] ? $row['jam_absen'] : '-'; ?></td>
                    <td>
                        <?php if($row['bukti_foto']): ?>
                            <a href="assets/uploads/<?php echo $row['bukti_foto']; ?>" target="_blank" class="text-decoration-none"><i class="fas fa-camera"></i> Foto</a>
                        <?php endif; ?>
                        <?php echo $row['keterangan'] ? '<br><small>'.htmlspecialchars($row['keterangan']).'</small>' : ''; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['dicatat_oleh']); ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="index.php?page=admin/absensi_edit&id=<?php echo $row['id_absensi']; ?>" class="btn btn-sm btn-warning text-dark" title="Edit Data">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <a href="index.php?page=admin/absensi_data&aksi=hapus&id=<?php echo $row['id_absensi']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data absensi ini? Siswa akan dianggap belum absen pada tanggal tersebut.')" title="Hapus Permanen">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>