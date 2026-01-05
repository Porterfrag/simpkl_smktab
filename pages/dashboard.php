<?php
// (Pastikan file ini hanya di-include oleh index.php)
$role = $_SESSION['role'];
$id_ref = $_SESSION['id_ref']; 
$username = $_SESSION['username'];

$nama_display = $username;
$info_cards = []; 
$siswa_belum_absen = [];
$dudi_bimbingan_list = [];
$pengumuman_list = [];
$absensi_stat = ['Hadir'=>0, 'Izin'=>0, 'Sakit'=>0, 'Alpha'=>0];
$live_feed = [];
$top_alpha = [];

// Inisialisasi variabel default
$tempat_pkl = '-';
$nama_pembimbing = '-';

try {
    $hari_ini = date('Y-m-d');
    
    // 1. AMBIL PENGUMUMAN
    $stmt = $pdo->query("SELECT * FROM pengumuman ORDER BY tanggal_post DESC LIMIT 3");
    $pengumuman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. LOGIKA ADMIN
    if ($role == 'admin') {
        $nama_display = 'Administrator';
        
        $c_siswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
        $c_dudi = $pdo->query("SELECT COUNT(*) FROM perusahaan")->fetchColumn();
        $c_guru = $pdo->query("SELECT COUNT(*) FROM pembimbing")->fetchColumn();
        $c_pending = $pdo->query("SELECT COUNT(*) FROM jurnal_harian WHERE status_validasi = 'Pending'")->fetchColumn();
        $c_unplot = $pdo->query("SELECT COUNT(*) FROM siswa WHERE id_perusahaan IS NULL")->fetchColumn();

        // --- STATISTIK ABSENSI HARI INI ---
        $stmt_stat = $pdo->prepare("SELECT status, COUNT(*) as jumlah FROM absensi WHERE tanggal = ? GROUP BY status");
        $stmt_stat->execute([$hari_ini]);
        while($row = $stmt_stat->fetch(PDO::FETCH_ASSOC)) {
            $absensi_stat[$row['status']] = $row['jumlah'];
        }

        // --- LIVE FEED (5 ABSENSI TERAKHIR) ---
        $stmt_live = $pdo->query("SELECT a.jam_absen, s.nama_lengkap, a.status, s.kelas 
                                  FROM absensi a 
                                  JOIN siswa s ON a.id_siswa = s.id_siswa 
                                  WHERE a.tanggal = '$hari_ini' 
                                  ORDER BY a.jam_absen DESC LIMIT 5");
        $live_feed = $stmt_live->fetchAll(PDO::FETCH_ASSOC);

        // --- TOP 5 SISWA ALPHA TERBANYAK ---
        $stmt_alpha = $pdo->query("SELECT s.nama_lengkap, s.kelas, COUNT(a.id_absensi) as total_alpha 
                                   FROM absensi a 
                                   JOIN siswa s ON a.id_siswa = s.id_siswa 
                                   WHERE a.status = 'Alpha' 
                                   GROUP BY s.id_siswa 
                                   ORDER BY total_alpha DESC LIMIT 5");
        $top_alpha = $stmt_alpha->fetchAll(PDO::FETCH_ASSOC);


        $stmt_ba = $pdo->prepare("SELECT s.nama_lengkap, s.kelas, p.nama_perusahaan FROM siswa s LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan WHERE s.id_siswa NOT IN (SELECT id_siswa FROM absensi WHERE tanggal = ?) ORDER BY s.kelas ASC");
        $stmt_ba->execute([$hari_ini]);
        $siswa_belum_absen = $stmt_ba->fetchAll(PDO::FETCH_ASSOC);
        $c_belum_absen = count($siswa_belum_absen);

        $info_cards = [
            ['title' => 'Total Siswa', 'value' => $c_siswa, 'icon' => 'fa-users', 'color' => 'primary', 'link' => 'index.php?page=admin/siswa_data'],
            ['title' => 'Mitra DUDI', 'value' => $c_dudi, 'icon' => 'fa-building', 'color' => 'info', 'link' => 'index.php?page=admin/perusahaan_data'],
            ['title' => 'Pembimbing', 'value' => $c_guru, 'icon' => 'fa-chalkboard-user', 'color' => 'success', 'link' => 'index.php?page=admin/pembimbing_data'],
            ['title' => 'Jurnal Pending', 'value' => $c_pending, 'icon' => 'fa-clock', 'color' => 'warning', 'link' => 'index.php?page=admin/jurnal_data'],
            ['title' => 'Belum Plotting', 'value' => $c_unplot, 'icon' => 'fa-user-slash', 'color' => 'secondary', 'link' => 'index.php?page=admin/plotting_data']
        ];

    // 3. LOGIKA PEMBIMBING
    } elseif ($role == 'pembimbing') {
        // ... (Kode pembimbing tetap sama seperti sebelumnya) ...
        $stmt = $pdo->prepare("SELECT nama_guru FROM pembimbing WHERE id_pembimbing = ?");
        $stmt->execute([$id_ref]);
        $nama_display = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE id_pembimbing = ?");
        $stmt->execute([$id_ref]);
        $c_siswa = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM jurnal_harian j JOIN siswa s ON j.id_siswa = s.id_siswa WHERE s.id_pembimbing = ? AND j.status_validasi = 'Pending'");
        $stmt->execute([$id_ref]);
        $c_pending = $stmt->fetchColumn();

        $stmt_ba = $pdo->prepare("SELECT s.nama_lengkap, s.kelas, p.nama_perusahaan FROM siswa s LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan WHERE s.id_pembimbing = ? AND s.id_siswa NOT IN (SELECT id_siswa FROM absensi WHERE tanggal = ?) ORDER BY s.nama_lengkap ASC");
        $stmt_ba->execute([$id_ref, $hari_ini]);
        $siswa_belum_absen = $stmt_ba->fetchAll(PDO::FETCH_ASSOC);
        $c_belum_absen = count($siswa_belum_absen);

        $sql_dudi = "SELECT DISTINCT p.*, p.id_perusahaan FROM siswa s JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan WHERE s.id_pembimbing = ?";
        $stmt_dudi = $pdo->prepare($sql_dudi);
        $stmt_dudi->execute([$id_ref]);
        $dudi_bimbingan_list = $stmt_dudi->fetchAll(PDO::FETCH_ASSOC);
        $c_dudi_binaan = count($dudi_bimbingan_list);

        $info_cards = [
            ['title' => 'Siswa Bimbingan', 'value' => $c_siswa, 'icon' => 'fa-user-graduate', 'color' => 'primary', 'link' => 'index.php?page=pembimbing/validasi_daftar_siswa'],
            ['title' => 'Jurnal Pending', 'value' => $c_pending, 'icon' => 'fa-file-signature', 'color' => 'warning', 'link' => 'index.php?page=pembimbing/validasi_daftar_siswa'],
            ['title' => 'Mitra DUDI', 'value' => $c_dudi_binaan, 'icon' => 'fa-building', 'color' => 'info', 'is_modal' => true, 'target' => '#modalDudiBimbingan']
        ];

    // 4. LOGIKA SISWA
    } elseif ($role == 'siswa') {
        // ... (Kode siswa tetap sama) ...
        $stmt = $pdo->prepare("SELECT s.nama_lengkap, p.nama_perusahaan, g.nama_guru FROM siswa s LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan LEFT JOIN pembimbing g ON s.id_pembimbing = g.id_pembimbing WHERE s.id_siswa = ?");
        $stmt->execute([$id_ref]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $nama_display = $data['nama_lengkap'];
            $tempat_pkl = $data['nama_perusahaan'] ?? 'Belum Ditempatkan';
            $guru_pkl = $data['nama_guru'] ?? 'Belum Diatur';
        } else {
            $nama_display = $username;
            $tempat_pkl = '-'; $guru_pkl = '-';
        }
    }

} catch (PDOException $e) { }
?>

<style>
    body { background-color: #f5f7fa; background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px); background-size: 24px 24px; }
    .stat-card { transition: all 0.3s ease; border: none; border-radius: 12px; background: #fff; position: relative; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; }
    .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.5rem; }
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .bg-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .bg-soft-warning { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
    .bg-soft-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }
    .welcome-banner { background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; position: relative; z-index: 1; }
    
    /* Tabel Kecil */
    .table-sm td, .table-sm th { font-size: 0.85rem; padding: 0.5rem; }
</style>

<div class="welcome-banner shadow">
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

<div id="installContainer" class="alert alert-primary shadow-sm border-0 d-flex align-items-center justify-content-between mb-4" role="alert" style="display: none;">
    <div>
        <i class="fas fa-mobile-alt fa-lg me-2"></i>
        <strong>Install Aplikasi?</strong> <span class="small">Akses lebih cepat tanpa browser.</span>
    </div>
    <button id="btnInstall" class="btn btn-sm btn-light text-primary fw-bold rounded-pill px-3">Install</button>
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
                        echo '<div class="card stat-card h-100 shadow-sm" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="'.$card['target'].'">';
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
                <?php if (isset($card['is_modal'])) echo '</div>'; else echo '</div></a>'; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($role == 'pembimbing'): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalBelumAbsen">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small mb-1 fw-bold text-uppercase text-danger">Belum Absen Hari Ini</p>
                            <h2 class="fw-bold mb-0 text-danger"><?php echo $c_belum_absen; ?></h2>
                        </div>
                        <div class="icon-box bg-soft-danger">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($role == 'siswa'): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="icon-box bg-soft-primary me-3 rounded-circle" style="width:60px; height:60px;"><i class="fas fa-building fa-lg"></i></div>
                    <div><small class="text-muted text-uppercase fw-bold">Tempat Magang</small><h5 class="mb-0 fw-bold text-primary"><?php echo htmlspecialchars($tempat_pkl); ?></h5></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="icon-box bg-soft-success me-3 rounded-circle" style="width:60px; height:60px;"><i class="fas fa-chalkboard-teacher fa-lg"></i></div>
                    <div><small class="text-muted text-uppercase fw-bold">Guru Pembimbing</small><h5 class="mb-0 fw-bold text-success"><?php echo htmlspecialchars($guru_pkl); ?></h5></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <?php if ($role == 'admin'): ?>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-pie me-2"></i> Absensi Hari Ini</h6>
                </div>
                <div class="card-body">
                    <canvas id="absensiChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0 fw-bold text-success small"><i class="fas fa-rss me-2"></i> Live Feed (Absen Terakhir)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <tbody>
                                <?php if(empty($live_feed)): ?>
                                    <tr><td class="text-center text-muted p-3">Belum ada yang absen hari ini.</td></tr>
                                <?php else: ?>
                                    <?php foreach($live_feed as $feed): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary" style="width: 50px;"><?php echo date('H:i', strtotime($feed['jam_absen'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($feed['nama_lengkap']); ?>
                                            <span class="d-block text-muted" style="font-size: 0.7rem;"><?php echo $feed['kelas']; ?></span>
                                        </td>
                                        <td class="text-end"><span class="badge bg-<?php echo ($feed['status']=='Hadir'?'success':($feed['status']=='Izin'?'primary':($feed['status']=='Sakit'?'warning':'danger'))); ?>"><?php echo $feed['status']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0 fw-bold text-danger small"><i class="fas fa-exclamation-triangle me-2"></i> Top 5 Siswa Sering Alpha</h6>
                </div>
                <div class="card-body p-0">
                     <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <tbody>
                                <?php if(empty($top_alpha)): ?>
                                    <tr><td class="text-center text-muted p-3">Tidak ada data alpha. Bagus!</td></tr>
                                <?php else: ?>
                                    <?php foreach($top_alpha as $ta): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ta['nama_lengkap']); ?></td>
                                        <td class="text-end"><span class="badge bg-danger rounded-pill"><?php echo $ta['total_alpha']; ?>x</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                    <div class="text-center text-muted py-4"><i class="fas fa-bell-slash fa-2x mb-2"></i><p class="small mb-0">Tidak ada pengumuman.</p></div>
                <?php else: ?>
                    <?php foreach ($pengumuman_list as $info): ?>
                        <div class="pengumuman-box mb-3">
                            <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($info['judul']); ?></h6>
                            <small class="text-muted d-block mb-2"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($info['tanggal_post'])); ?></small>
                            <p class="mb-0 text-secondary small"><?php echo nl2br(substr($info['isi'], 0, 80)) . (strlen($info['isi']) > 80 ? '...' : ''); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($role == 'admin'): ?>
                    <div class="text-center mt-3"><a href="index.php?page=admin/pengumuman_data" class="btn btn-sm btn-outline-primary rounded-pill">Kelola Pengumuman</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (($role == 'admin' || $role == 'pembimbing') && !empty($siswa_belum_absen)): ?>
<div class="modal fade" id="modalBelumAbsen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-user-clock me-2"></i>Siswa Belum Absen Hari Ini</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0">
                <table id="tableBelumAbsen" class="table table-hover table-striped mb-0 w-100">
                    <thead class="table-light"><tr><th>No</th><th>Nama Siswa</th><th>Kelas</th><th>Tempat PKL</th></tr></thead>
                    <tbody>
                        <?php $no=1; foreach ($siswa_belum_absen as $mhs): ?>
                            <tr><td><?php echo $no++; ?></td><td class="fw-bold text-danger"><?php echo htmlspecialchars($mhs['nama_lengkap']); ?></td><td><?php echo htmlspecialchars($mhs['kelas']); ?></td><td><?php echo !empty($mhs['nama_perusahaan']) ? htmlspecialchars($mhs['nama_perusahaan']) : '-'; ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($role == 'pembimbing' && !empty($dudi_bimbingan_list)): ?>
<div class="modal fade" id="modalDudiBimbingan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-building me-2"></i>Daftar Mitra DUDI Bimbingan</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-0">
                <table id="tableDudiBimbingan" class="table table-hover table-striped mb-0 w-100">
                    <thead class="table-light"><tr><th>No</th><th>Nama Perusahaan</th><th>Alamat</th><th>Kontak</th></tr></thead>
                    <tbody>
                        <?php $no=1; foreach ($dudi_bimbingan_list as $d): ?>
                            <tr><td><?php echo $no++; ?></td><td class="fw-bold text-primary"><?php echo htmlspecialchars($d['nama_perusahaan']); ?></td><td><?php echo htmlspecialchars($d['alamat']); ?></td><td><?php echo htmlspecialchars($d['kontak_person']); ?><br><small><?php echo htmlspecialchars($d['no_telp']); ?></small></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
<?php endif; ?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- 1. PWA LOGIC (Tetap sama, Vanilla JS aman) ---
    let deferredPrompt;
    const installContainer = document.getElementById('installContainer');
    const btnInstall = document.getElementById('btnInstall');

    function hideInstallBanner() {
        if (installContainer) { installContainer.style.setProperty('display', 'none', 'important'); }
    }

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const isAlreadyInstalled = localStorage.getItem('pwa_installed') === 'true';

    if (isStandalone || isAlreadyInstalled) { hideInstallBanner(); }

    window.addEventListener('beforeinstallprompt', (e) => {
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) return;
        if (localStorage.getItem('pwa_installed') === 'true') return;
        e.preventDefault();
        deferredPrompt = e;
        if (installContainer) installContainer.style.display = 'flex';
    });

    if (btnInstall) {
        btnInstall.addEventListener('click', async () => {
            if (deferredPrompt) {
                hideInstallBanner();
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') localStorage.setItem('pwa_installed', 'true');
                deferredPrompt = null;
            }
        });
    }

    window.addEventListener('appinstalled', () => {
        hideInstallBanner();
        localStorage.setItem('pwa_installed', 'true');
        deferredPrompt = null;
    });


    // --- 2. LOGIC CHART & DATATABLES ---
    // Gunakan 'DOMContentLoaded' pengganti $(document).ready
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- LOGIC MODAL & DATATABLES (Cek jika jQuery ada baru jalankan) ---
        if (typeof jQuery !== 'undefined') {
            $('#modalBelumAbsen').on('shown.bs.modal', function () {
                if (!$.fn.DataTable.isDataTable('#tableBelumAbsen')) {
                    $('#tableBelumAbsen').DataTable({ "pageLength": 5, "lengthChange": false, "searching": true, "info": false });
                }
            });
            $('#modalDudiBimbingan').on('shown.bs.modal', function () {
                if (!$.fn.DataTable.isDataTable('#tableDudiBimbingan')) {
                    $('#tableDudiBimbingan').DataTable({ "pageLength": 5, "lengthChange": false, "searching": true, "info": false });
                }
            });
        }

        // --- CHART JS (Admin - Absensi Hari Ini) ---
        <?php if ($role == 'admin'): ?>
            const chartCanvas = document.getElementById('absensiChart');
            
            // Cek apakah elemen canvas benar-benar ada
            if (chartCanvas) {
                const ctx = chartCanvas.getContext('2d');
                
                // Siapkan Data
                const dataHadir = <?php echo $absensi_stat['Hadir']; ?>;
                const dataIzin  = <?php echo $absensi_stat['Izin']; ?>;
                const dataSakit = <?php echo $absensi_stat['Sakit']; ?>;
                const dataAlpha = <?php echo $absensi_stat['Alpha']; ?>;
                const totalData = dataHadir + dataIzin + dataSakit + dataAlpha;

                // Cek jika Data Kosong (0 semua)
                if (totalData === 0) {
                    // Tampilkan pesan teks jika data kosong agar user tidak bingung
                    const parent = chartCanvas.parentElement;
                    parent.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i><p>Belum ada data absensi masuk hari ini.</p></div>';
                } else {
                    // Render Chart jika ada data
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                            datasets: [{
                                data: [dataHadir, dataIzin, dataSakit, dataAlpha],
                                backgroundColor: ['#198754', '#0d6efd', '#ffc107', '#dc3545'],
                                borderWidth: 1,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false, 
                            plugins: { 
                                legend: { position: 'bottom' } // Pindah legend ke bawah agar lebih rapi
                            } 
                        }
                    });
                }
            }
        <?php endif; ?>
    });
</script>