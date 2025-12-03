<?php
// (Pastikan file ini hanya di-include oleh index.php)
// session_start(); // (Sudah dimulai di index.php)

$role = $_SESSION['role'];
$id_ref = $_SESSION['id_ref']; 
$username = $_SESSION['username'];

$nama_display = $username;
$info_cards = []; 
$siswa_belum_absen = [];
$dudi_bimbingan_list = [];
$pengumuman_list = [];

// [PERBAIKAN] Inisialisasi variabel agar tidak error jika data kosong
$tempat_pkl = '-';
$nama_pembimbing = '-';

try {
    $hari_ini = date('Y-m-d');
    
    // 1. AMBIL PENGUMUMAN (Global)
    $stmt = $pdo->query("SELECT * FROM pengumuman ORDER BY tanggal_post DESC LIMIT 3");
    $pengumuman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==========================================
    // TAMPILAN ADMIN
    // ==========================================
    if ($role == 'admin') {
        $nama_display = 'Administrator';
        
        $c_siswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
        $c_dudi = $pdo->query("SELECT COUNT(*) FROM perusahaan")->fetchColumn();
        $c_guru = $pdo->query("SELECT COUNT(*) FROM pembimbing")->fetchColumn();
        $c_pending = $pdo->query("SELECT COUNT(*) FROM jurnal_harian WHERE status_validasi = 'Pending'")->fetchColumn();
        $c_unplot = $pdo->query("SELECT COUNT(*) FROM siswa WHERE id_perusahaan IS NULL")->fetchColumn();

        $stmt_ba = $pdo->prepare("SELECT s.nama_lengkap, s.kelas, p.nama_perusahaan FROM siswa s LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan WHERE s.id_siswa NOT IN (SELECT id_siswa FROM absensi WHERE tanggal = ?) ORDER BY s.kelas ASC");
        $stmt_ba->execute([$hari_ini]);
        $siswa_belum_absen = $stmt_ba->fetchAll(PDO::FETCH_ASSOC);
        $c_belum_absen = count($siswa_belum_absen);

        $info_cards = [
            ['title' => 'Total Siswa', 'value' => $c_siswa, 'icon' => 'fa-users', 'color' => 'primary', 'link' => 'index.php?page=admin/siswa_data'],
            ['title' => 'Mitra DUDI', 'value' => $c_dudi, 'icon' => 'fa-building', 'color' => 'info', 'link' => 'index.php?page=admin/perusahaan_data'],
            ['title' => 'Pembimbing', 'value' => $c_guru, 'icon' => 'fa-chalkboard-user', 'color' => 'success', 'link' => 'index.php?page=admin/pembimbing_data'],
            ['title' => 'Jurnal Pending', 'value' => $c_pending, 'icon' => 'fa-clock', 'color' => 'warning', 'link' => 'index.php?page=admin/jurnal_data'],
            ['title' => 'Belum Plotting', 'value' => $c_unplot, 'icon' => 'fa-user-slash', 'color' => 'secondary', 'link' => 'index.php?page=admin/plotting_data'],
            ['title' => 'Belum Absen', 'value' => $c_belum_absen, 'icon' => 'fa-user-clock', 'color' => 'danger', 'is_modal' => true, 'target' => '#modalBelumAbsen']
        ];

    // ==========================================
    // TAMPILAN PEMBIMBING
    // ==========================================
    } elseif ($role == 'pembimbing') {
        // Nama Guru
        $stmt = $pdo->prepare("SELECT nama_guru FROM pembimbing WHERE id_pembimbing = ?");
        $stmt->execute([$id_ref]);
        $nama_display = $stmt->fetchColumn();

        // Hitung Siswa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE id_pembimbing = ?");
        $stmt->execute([$id_ref]);
        $c_siswa = $stmt->fetchColumn();

        // Hitung Jurnal Pending
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jurnal_harian j JOIN siswa s ON j.id_siswa = s.id_siswa WHERE s.id_pembimbing = ? AND j.status_validasi = 'Pending'");
        $stmt->execute([$id_ref]);
        $c_pending = $stmt->fetchColumn();

        // Hitung Siswa Belum Absen
        $stmt_ba = $pdo->prepare("SELECT s.nama_lengkap, s.kelas, p.nama_perusahaan FROM siswa s LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan WHERE s.id_pembimbing = ? AND s.id_siswa NOT IN (SELECT id_siswa FROM absensi WHERE tanggal = ?) ORDER BY s.nama_lengkap ASC");
        $stmt_ba->execute([$id_ref, $hari_ini]);
        $siswa_belum_absen = $stmt_ba->fetchAll(PDO::FETCH_ASSOC);
        $c_belum_absen = count($siswa_belum_absen);

        // Hitung & Ambil Data DUDI Binaan
        $sql_dudi = "SELECT DISTINCT p.* FROM siswa s 
                     JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan 
                     WHERE s.id_pembimbing = ?";
        $stmt_dudi = $pdo->prepare($sql_dudi);
        $stmt_dudi->execute([$id_ref]);
        $dudi_bimbingan_list = $stmt_dudi->fetchAll(PDO::FETCH_ASSOC);
        $c_dudi_binaan = count($dudi_bimbingan_list);

        $info_cards = [
            ['title' => 'Siswa Bimbingan', 'value' => $c_siswa, 'icon' => 'fa-user-graduate', 'color' => 'primary', 'link' => 'index.php?page=pembimbing/validasi_daftar_siswa'],
            ['title' => 'Jurnal Pending', 'value' => $c_pending, 'icon' => 'fa-file-signature', 'color' => 'warning', 'link' => 'index.php?page=pembimbing/validasi_daftar_siswa'],
            ['title' => 'Belum Absen', 'value' => $c_belum_absen, 'icon' => 'fa-user-clock', 'color' => 'danger', 'is_modal' => true, 'target' => '#modalBelumAbsen'],
            ['title' => 'Mitra DUDI', 'value' => $c_dudi_binaan, 'icon' => 'fa-building', 'color' => 'info', 'is_modal' => true, 'target' => '#modalDudiBimbingan']
        ];

    // ==========================================
    // TAMPILAN SISWA
    // ==========================================
    } elseif ($role == 'siswa') {
        $stmt = $pdo->prepare("SELECT s.nama_lengkap, p.nama_perusahaan, g.nama_guru FROM siswa s LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan LEFT JOIN pembimbing g ON s.id_pembimbing = g.id_pembimbing WHERE s.id_siswa = ?");
        $stmt->execute([$id_ref]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $nama_display = $data['nama_lengkap'];
            $tempat_pkl = !empty($data['nama_perusahaan']) ? $data['nama_perusahaan'] : 'Belum Ditempatkan';
            $nama_pembimbing = !empty($data['nama_guru']) ? $data['nama_guru'] : 'Belum Diatur';
        }
        // Jika data kosong, variabel $nama_pembimbing tetap '-' (default di atas), jadi tidak error
    }

} catch (PDOException $e) { }
?>

<style>
    .stat-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 12px;
        background: #fff;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
    .icon-box {
        width: 50px; height: 50px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
    }
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .bg-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .bg-soft-warning { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
    .bg-soft-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }

    .welcome-banner {
        background: linear-gradient(45deg, #0d6efd, #0dcaf0);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
</style>

<div class="welcome-banner shadow-sm">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1">Halo, <?php echo htmlspecialchars($nama_display); ?>! 👋</h2>
            <p class="mb-0 opacity-75">Selamat datang di Dashboard Sistem Informasi PKL.</p>
        </div>
        <div class="d-none d-md-block">
            <i class="fas fa-layer-group fa-4x opacity-25"></i>
        </div>
    </div>
</div>

<?php if (!empty($info_cards)): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-muted fw-bold text-uppercase small ls-1 mb-0">Ringkasan Data</h5>
    </div>
    
    <div class="row g-3 mb-4">
        <?php foreach ($info_cards as $card): ?>
            <div class="col-12 col-sm-6 col-xl-3"> 
                <?php 
                    if (isset($card['is_modal'])) {
                        echo '<div class="card stat-card h-100 shadow-sm cursor-pointer" data-bs-toggle="modal" data-bs-target="'.$card['target'].'" style="cursor:pointer">';
                    } else {
                        echo '<a href="'.$card['link'].'" class="text-decoration-none"><div class="card stat-card h-100 shadow-sm">';
                    }
                ?>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase"><?php echo $card['title']; ?></p>
                                <h2 class="fw-bold mb-0 text-dark"><?php echo $card['value']; ?></h2>
                            </div>
                            <div class="icon-box bg-soft-<?php echo $card['color']; ?>">
                                <i class="fas <?php echo $card['icon']; ?>"></i>
                            </div>
                        </div>
                    </div>
                <?php 
                    if (isset($card['is_modal'])) echo '</div>'; else echo '</div></a>'; 
                ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($role == 'siswa'): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="icon-box bg-soft-primary me-3 rounded-circle" style="width:60px; height:60px;">
                        <i class="fas fa-building fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Tempat Magang</small>
                        <h5 class="mb-0 fw-bold text-primary"><?php echo htmlspecialchars($tempat_pkl); ?></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="icon-box bg-soft-success me-3 rounded-circle" style="width:60px; height:60px;">
                        <i class="fas fa-chalkboard-teacher fa-lg"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Guru Pembimbing</small>
                        <h5 class="mb-0 fw-bold text-success"><?php echo htmlspecialchars($nama_pembimbing); ?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <?php if ($role == 'admin'): ?>
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-pie me-2"></i> Statistik Sistem</h6>
                </div>
                <div class="card-body">
                    <canvas id="adminChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?php echo ($role == 'admin') ? 'col-md-4' : 'col-12'; ?> mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-warning"><i class="fas fa-bullhorn me-2"></i> Pengumuman Terbaru</h6>
            </div>
            <div class="card-body">
                <?php if (empty($pengumuman_list)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                        <p class="small mb-0">Tidak ada pengumuman.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pengumuman_list as $info): ?>
                        <div class="pengumuman-box mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($info['judul']); ?></h6>
                            </div>
                            <small class="text-muted d-block mb-2"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($info['tanggal_post'])); ?></small>
                            <p class="mb-0 text-secondary small">
                                <?php echo nl2br(substr($info['isi'], 0, 80)) . (strlen($info['isi']) > 80 ? '...' : ''); ?>
                            </p>
                            <?php if(strlen($info['isi']) > 80): ?>
                                <a href="#" class="small text-decoration-none mt-1 d-block" data-bs-toggle="modal" data-bs-target="#modalPengumuman<?php echo $info['id_pengumuman']; ?>">Baca selengkapnya</a>
                            <?php endif; ?>
                        </div>

                        <div class="modal fade" id="modalPengumuman<?php echo $info['id_pengumuman']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title fw-bold"><?php echo htmlspecialchars($info['judul']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-secondary">
                                        <?php echo nl2br($info['isi']); ?>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($role == 'admin'): ?>
                    <div class="text-center mt-3">
                        <a href="index.php?page=admin/pengumuman_data" class="btn btn-sm btn-outline-primary rounded-pill">Kelola Pengumuman</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (($role == 'admin' || $role == 'pembimbing') && !empty($siswa_belum_absen)): ?>
<div class="modal fade" id="modalBelumAbsen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-user-clock me-2"></i>Siswa Belum Absen Hari Ini</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table id="tableBelumAbsen" class="table table-hover table-striped mb-0 w-100">
                    <thead class="table-light">
                        <tr><th>No</th><th>Nama Siswa</th><th>Kelas</th><th>Tempat PKL</th></tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach ($siswa_belum_absen as $mhs): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td class="fw-bold text-danger"><?php echo htmlspecialchars($mhs['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($mhs['kelas']); ?></td>
                                <td><?php echo !empty($mhs['nama_perusahaan']) ? htmlspecialchars($mhs['nama_perusahaan']) : '<span class="badge bg-secondary">Belum Plotting</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($role == 'pembimbing' && !empty($dudi_bimbingan_list)): ?>
<div class="modal fade" id="modalDudiBimbingan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-building me-2"></i>Daftar Mitra DUDI Bimbingan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table id="tableDudiBimbingan" class="table table-hover table-striped mb-0 w-100">
                    <thead class="table-light">
                        <tr><th>No</th><th>Nama Perusahaan</th><th>Alamat</th><th>Kontak</th></tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach ($dudi_bimbingan_list as $d): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($d['nama_perusahaan']); ?></td>
                                <td><?php echo htmlspecialchars($d['alamat']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($d['kontak_person']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($d['no_telp']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Pastikan kode ini berjalan setelah jQuery ($) sudah dimuat di atas
    $(document).ready(function() {
        
        // --- KODE DATATABLES ---
        $('#modalBelumAbsen').on('shown.bs.modal', function () {
            if (!$.fn.DataTable.isDataTable('#tableBelumAbsen')) {
                $('#tableBelumAbsen').DataTable({
                    "pageLength": 5,
                    "lengthChange": false,
                    "searching": false,
                    "info": false,
                    // Tambahan bahasa indonesia agar lebih rapi
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
                    }
                });
            }
        });

        $('#modalDudiBimbingan').on('shown.bs.modal', function () {
             if (!$.fn.DataTable.isDataTable('#tableDudiBimbingan')) {
                $('#tableDudiBimbingan').DataTable({
                    "pageLength": 5,
                    "lengthChange": false
                });
            }
        });

        // --- KODE CHART.JS (ADMIN ONLY) ---
        <?php if ($role == 'admin'): ?>
        const ctx = document.getElementById('adminChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Siswa', 'DUDI', 'Guru', 'Jurnal Pending'],
                datasets: [{
                    data: [<?php echo $c_siswa; ?>, <?php echo $c_dudi; ?>, <?php echo $c_guru; ?>, <?php echo $c_pending; ?>],
                    backgroundColor: ['#198754', '#0dcaf0', '#198754', '#ffc107'],
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        <?php endif; ?>
    });
</script>