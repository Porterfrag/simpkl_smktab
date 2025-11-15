<?php
if (!isset($_SESSION['user_id'])) {
    die("Akses tidak sah!");
}

$user_id = $_SESSION['user_id'];
$id_ref = $_SESSION['id_ref']; 
$role = $_SESSION['role'];

$profil_data = []; 
$judul_role = ucfirst($role);

try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $user['username'];

    if ($role == 'siswa') {
        $sql = "SELECT s.nis, s.nama_lengkap, s.jurusan, s.kelas, 
                       p.nama_perusahaan, p.alamat as alamat_perusahaan,
                       g.nama_guru, g.no_telp as telp_guru
                FROM siswa s
                LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                LEFT JOIN pembimbing g ON s.id_pembimbing = g.id_pembimbing
                WHERE s.id_siswa = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id_ref]);
        $profil_data = $stmt->fetch(PDO::FETCH_ASSOC);

    } elseif ($role == 'pembimbing') {
        $sql = "SELECT p.nip, p.nama_guru, p.no_telp,
                       (SELECT COUNT(*) FROM siswa WHERE id_pembimbing = p.id_pembimbing) as total_bimbingan,
                       (SELECT COUNT(DISTINCT id_perusahaan) FROM siswa WHERE id_pembimbing = p.id_pembimbing) as total_perusahaan
                FROM pembimbing p
                WHERE p.id_pembimbing = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id_ref]);
        $profil_data = $stmt->fetch(PDO::FETCH_ASSOC);

    } elseif ($role == 'admin') {
        $profil_data = ['nama_lengkap' => 'Administrator Sistem'];
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            
            <div class="card-header bg-gradient text-white text-center py-5" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);">
                <div class="mb-3">
                    <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 100px; height: 100px;">
                        <i class="fas fa-user fa-3x text-primary"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1">
                    <?php 
                        if ($role == 'siswa') echo htmlspecialchars($profil_data['nama_lengkap']);
                        elseif ($role == 'pembimbing') echo htmlspecialchars($profil_data['nama_guru']);
                        else echo "Administrator";
                    ?>
                </h2>
                <span class="badge bg-white text-primary rounded-pill px-3 py-2 text-uppercase fw-bold shadow-sm">
                    <?php echo $judul_role; ?>
                </span>
            </div>

            <div class="card-body p-4 p-md-5">
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <h5 class="text-primary fw-bold mb-3 border-bottom pb-2"><i class="fas fa-id-card me-2"></i>Data Akun</h5>
                        
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase fw-bold">Username Login</label>
                            <div class="fs-5 fw-medium text-dark"><?php echo htmlspecialchars($username); ?></div>
                        </div>

                        <?php if ($role == 'siswa'): ?>
                            <div class="mb-3">
                                <label class="text-muted small text-uppercase fw-bold">NIS</label>
                                <div class="fs-5 text-dark"><?php echo htmlspecialchars($profil_data['nis']); ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small text-uppercase fw-bold">Kelas / Jurusan</label>
                                <div class="fs-5 text-dark"><?php echo htmlspecialchars($profil_data['kelas'] . " - " . $profil_data['jurusan']); ?></div>
                            </div>
                        
                        <?php elseif ($role == 'pembimbing'): ?>
                            <div class="mb-3">
                                <label class="text-muted small text-uppercase fw-bold">NIP</label>
                                <div class="fs-5 text-dark"><?php echo htmlspecialchars($profil_data['nip']); ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small text-uppercase fw-bold">No. Telepon</label>
                                <div class="fs-5 text-dark"><?php echo htmlspecialchars($profil_data['no_telp']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        
                        <?php if ($role == 'siswa'): ?>
                            <h5 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="fas fa-building me-2"></i>Informasi Penempatan</h5>
                            
                            <div class="mb-3">
                                <label class="text-muted small text-uppercase fw-bold">Tempat Magang (DUDI)</label>
                                <?php if (!empty($profil_data['nama_perusahaan'])): ?>
                                    <div class="fs-5 fw-bold text-dark"><?php echo htmlspecialchars($profil_data['nama_perusahaan']); ?></div>
                                    <small class="text-secondary"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($profil_data['alamat_perusahaan']); ?></small>
                                <?php else: ?>
                                    <div class="text-danger fst-italic">Belum ditempatkan</div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="text-muted small text-uppercase fw-bold">Guru Pembimbing</label>
                                <?php if (!empty($profil_data['nama_guru'])): ?>
                                    <div class="fs-5 fw-medium text-dark"><?php echo htmlspecialchars($profil_data['nama_guru']); ?></div>
                                    <small class="text-success"><i class="fab fa-whatsapp me-1"></i> <?php echo htmlspecialchars($profil_data['telp_guru']); ?></small>
                                <?php else: ?>
                                    <div class="text-danger fst-italic">Belum diatur</div>
                                <?php endif; ?>
                            </div>

                        <?php elseif ($role == 'pembimbing'): ?>
                            <h5 class="text-success fw-bold mb-3 border-bottom pb-2"><i class="fas fa-chart-pie me-2"></i>Statistik Bimbingan</h5>
                            
                            <div class="card bg-light border-0 p-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user-graduate fa-2x text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0 text-muted">Siswa Bimbingan</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo $profil_data['total_bimbingan']; ?></h3>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-light border-0 p-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-building fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0 text-muted">Perusahaan (DUDI)</h6>
                                        <h3 class="mb-0 fw-bold"><?php echo $profil_data['total_perusahaan']; ?></h3>
                                    </div>
                                </div>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-5 pt-3 border-top">
                    <a href="index.php?page=ganti_password" class="btn btn-warning px-4 py-2 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-key me-2"></i> Ganti Password
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger px-4 py-2 rounded-pill fw-bold">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>

            </div>
        </div>
        
    </div>
</div>