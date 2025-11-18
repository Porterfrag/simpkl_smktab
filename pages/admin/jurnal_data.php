<?php
// Cek Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { die("Akses dilarang!"); }

$pesan_sukses = ''; 
$pesan_error = '';

// --- PROSES 1: VALIDASI JURNAL (ADMIN OVERRIDE) ---
// (PERBAIKAN: Kita cek apakah ada 'status' dan 'id_jurnal' di POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status']) && isset($_POST['id_jurnal'])) {
    
    $id_jurnal = $_POST['id_jurnal'];
    $status = $_POST['status'];
    
    try {
        $sql = "UPDATE jurnal_harian SET status_validasi = :status WHERE id_jurnal = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':status' => $status, ':id' => $id_jurnal]);
        
        $pesan_sukses = "Status jurnal berhasil diubah menjadi: <strong>$status</strong>.";
    } catch (PDOException $e) {
        $pesan_error = "Error: " . $e->getMessage();
    }
}

// --- PROSES 2: HAPUS JURNAL ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id_jurnal = $_GET['id'];
    try {
        // Hapus file foto dulu
        $stmt_cek = $pdo->prepare("SELECT foto_kegiatan FROM jurnal_harian WHERE id_jurnal = :id");
        $stmt_cek->execute([':id' => $id_jurnal]);
        $foto = $stmt_cek->fetchColumn();
        
        if($foto && file_exists("assets/uploads/$foto")) {
            unlink("assets/uploads/$foto");
        }

        // Hapus record
        $stmt = $pdo->prepare("DELETE FROM jurnal_harian WHERE id_jurnal = :id");
        $stmt->execute([':id' => $id_jurnal]);
        
        $pesan_sukses = "Jurnal berhasil dihapus permanen.";
    } catch (PDOException $e) {
        $pesan_error = "Error: " . $e->getMessage();
    }
}

// --- AMBIL SEMUA DATA JURNAL ---
try {
    $sql = "SELECT j.*, s.nama_lengkap, s.kelas, p.nama_perusahaan 
            FROM jurnal_harian j
            JOIN siswa s ON j.id_siswa = s.id_siswa
            LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
            ORDER BY j.tanggal DESC, j.id_jurnal DESC LIMIT 1000"; 
    $jurnal_list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { 
    $jurnal_list = []; 
}
?>

<h2 class="mb-4">Manajemen Seluruh Jurnal Siswa</h2>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success role='alert'"><?php echo $pesan_sukses; ?></div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger role='alert'"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th>Tgl</th>
                <th>Siswa</th>
                <th>Kegiatan</th>
                <th>Foto</th>
                <th>Status</th>
                <th style="min-width: 120px;">Aksi Admin</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jurnal_list as $row): ?>
                <tr>
                    <td class="text-start"><?php echo date('d/m/y', strtotime($row['tanggal'])); ?></td>
                    <td class="text-start">
                        <strong><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($row['kelas']); ?></small>
                    </td>
                    <td class="text-start"><?php echo nl2br(htmlspecialchars(substr($row['kegiatan'], 0, 100))) . (strlen($row['kegiatan']) > 100 ? '...' : ''); ?></td>
                    <td class="text-start">
                        <?php if ($row['foto_kegiatan']): ?>
                            <a href="assets/uploads/<?php echo $row['foto_kegiatan']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-image"></i></a>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                    <td class="text-start">
                        <?php 
                            $status = $row['status_validasi'];
                            $badge = ($status == 'Disetujui') ? 'bg-success' : (($status == 'Ditolak') ? 'bg-danger' : 'bg-warning text-dark');
                        ?>
                        <span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span>
                    </td>
                    
                    <td class="text-start">
                        <form action="index.php?page=admin/jurnal_data" method="POST" class="d-inline-block">
                            <input type="hidden" name="id_jurnal" value="<?php echo $row['id_jurnal']; ?>">
                            
                            <?php if($row['status_validasi'] != 'Disetujui'): ?>
                                <button type="submit" name="status" value="Disetujui" class="btn btn-sm btn-success" title="Setujui Jurnal" onclick="return confirm('Setujui jurnal ini?')">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if($row['status_validasi'] != 'Ditolak'): ?>
                                <button type="submit" name="status" value="Ditolak" class="btn btn-sm btn-warning" title="Tolak Jurnal" onclick="return confirm('Tolak jurnal ini?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </form>

                        <a href="index.php?page=admin/jurnal_data&aksi=hapus&id=<?php echo $row['id_jurnal']; ?>" 
                           class="btn btn-sm btn-danger ms-1" 
                           onclick="return confirm('Hapus jurnal ini secara permanen?')" 
                           title="Hapus Permanen">
                           <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>