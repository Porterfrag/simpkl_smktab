<?php
// (Pastikan file ini hanya di-include oleh index.php)

$pesan_sukses = '';
$pesan_error = '';

if (isset($_SESSION['pesan_sukses'])) {
    $pesan_sukses = $_SESSION['pesan_sukses'];
    unset($_SESSION['pesan_sukses']); 
}
if (isset($_SESSION['pesan_error'])) {
    $pesan_error = $_SESSION['pesan_error'];
    unset($_SESSION['pesan_error']); 
}

// --- Ambil Data Siswa ---
try {
    $stmt = $pdo->query("SELECT * FROM siswa ORDER BY nama_lengkap ASC");
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $siswa_list = []; 
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Manajemen Siswa</h3>
        <p class="text-muted small mb-0">Kelola data siswa, akun, dan laporan.</p>
    </div>
    <a href="index.php?page=admin/siswa_tambah" class="btn btn-primary rounded-pill shadow-sm fw-bold px-4">
        <i class="fas fa-plus me-2"></i>Tambah Siswa
    </a>
</div>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success rounded-3 shadow-sm border-0 mb-4">
        <i class="fas fa-check-circle me-2"></i> <?php echo $pesan_sukses; ?>
    </div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger rounded-3 shadow-sm border-0 mb-4">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive" style="min-height: 400px;"> <table class="table table-hover align-middle mb-0 datatable w-100">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center py-3" width="5%">No</th>
                        <th class="text-start py-3">NIS</th>
                        <th class="text-start py-3">Nama Lengkap</th>
                        <th class="text-start py-3">Jurusan</th>
                        <th class="text-start py-3">Kelas</th>
                        <th class="text-center py-3" width="10%">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($siswa_list as $siswa): ?>
                        <tr>
                            <td class="text-center text-muted"><?php echo $no++; ?></td>
                            <td class="text-start fw-bold text-secondary"><?php echo htmlspecialchars($siswa['nis']); ?></td>
                            <td class="text-start fw-bold text-dark"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                            <td class="text-start"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($siswa['jurusan']); ?></span></td>
                            <td class="text-start"><?php echo htmlspecialchars($siswa['kelas']); ?></td>
                            
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport">
                                        <i class="fas fa-ellipsis-v text-secondary"></i>
                                    </button>
                                    
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><h6 class="dropdown-header small text-uppercase">Aksi Data</h6></li>
                                        
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=admin/siswa_edit&id=<?php echo $siswa['id_siswa']; ?>">
                                                <i class="fas fa-edit text-warning me-2"></i> Edit Data
                                            </a>
                                        </li>
                                        
                                        <li>
                                            <a class="dropdown-item" href="index.php?page=admin/siswa_reset_password&id=<?php echo $siswa['id_siswa']; ?>" onclick="return confirm('Reset password siswa ini kembali ke NIS?')">
                                                <i class="fas fa-key text-info me-2"></i> Reset Password
                                            </a>
                                        </li>

                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header small text-uppercase">Laporan</h6></li>

                                        <li>
                                            <a class="dropdown-item" href="pages/pembimbing/export_absensi_detail.php?id_siswa=<?php echo $siswa['id_siswa']; ?>" target="_blank">
                                                <i class="fas fa-file-excel text-success me-2"></i> Rekap Absensi
                                            </a>
                                        </li>
                                        
                                        <li>
                                            <a class="dropdown-item" href="pages/admin/export_jurnal_detail.php?id_siswa=<?php echo $siswa['id_siswa']; ?>" target="_blank">
                                                <i class="fas fa-file-alt text-primary me-2"></i> Rekap Jurnal
                                            </a>
                                        </li>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <li>
                                            <a class="dropdown-item text-danger btn-hapus" href="index.php?page=admin/siswa_hapus&id=<?php echo $siswa['id_siswa']; ?>">
                                                <i class="fas fa-trash-alt me-2"></i> Hapus Siswa
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($siswa_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i>
                                <p>Belum ada data siswa.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>