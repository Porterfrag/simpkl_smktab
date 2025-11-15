<?php

$role = $_SESSION['role'];
$id_ref = $_SESSION['id_ref']; 
$username = $_SESSION['username'];

$nama_display = $username;
$info_boxes = [];
$pengumuman_list = []; 
$siswa_belum_absen = []; 

echo "
<style>
    .info-card {
        text-align: center;
        border-left: 5px solid;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        min-height: 150px; 
        display: flex; 
        align-items: center;
        transition: transform 0.2s;
    }
    .info-card:hover { transform: translateY(-5px); }
    .info-card h3 { font-size: 2.5em; font-weight: 700; margin-bottom: 5px; }
    
    .card-success { border-left-color: #198754; }
    .card-primary { border-left-color: #0d6efd; }
    .card-danger { border-left-color: #dc3545; }
    .card-warning { border-left-color: #ffc107; }
    
    .pengumuman-box {
        background: #fff8e1; border: 1px solid #ffe082;
        border-left: 5px solid #ffc107; padding: 15px 20px; margin-bottom: 15px; border-radius: 5px;
    }
    .clickable-card { cursor: pointer; }
</style>
";

try {
    $hari_ini = date('Y-m-d');
    
    $sql_pengumuman = "SELECT * FROM pengumuman ORDER BY tanggal_post DESC LIMIT 3";
    $stmt_pengumuman = $pdo->query($sql_pengumuman);
    $pengumuman_list = $stmt_pengumuman->fetchAll(PDO::FETCH_ASSOC);

    if ($role == 'admin') {
        $nama_display = 'Administrator';
        
        $total_siswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
        $total_perusahaan = $pdo->query("SELECT COUNT(*) FROM perusahaan")->fetchColumn();
        $total_pembimbing = $pdo->query("SELECT COUNT(*) FROM pembimbing")->fetchColumn();
        $total_pending = $pdo->query("SELECT COUNT(*) FROM jurnal_harian WHERE status_validasi = 'Pending'")->fetchColumn();
        $total_belum_plotting = $pdo->query("SELECT COUNT(*) FROM siswa WHERE id_perusahaan IS NULL OR id_pembimbing IS NULL")->fetchColumn();

        $sql_belum = "SELECT s.nama_lengkap, s.kelas, p.nama_perusahaan 
                      FROM siswa s
                      LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                      WHERE s.id_siswa NOT IN (SELECT id_siswa FROM absensi WHERE tanggal = :hari_ini)
                      ORDER BY s.kelas ASC, s.nama_lengkap ASC";
        $stmt_belum = $pdo->prepare($sql_belum);
        $stmt_belum->execute([':hari_ini' => $hari_ini]);
        $siswa_belum_absen = $stmt_belum->fetchAll(PDO::FETCH_ASSOC);
        $total_belum_absen = count($siswa_belum_absen);

        $info_boxes = [
            ['total' => $total_siswa, 'label' => 'Total Siswa', 'class' => 'success', 'icon' => '<i class="fas fa-users"></i>'],
            ['total' => $total_perusahaan, 'label' => 'Total Perusahaan', 'class' => 'primary', 'icon' => '<i class="fas fa-building"></i>'],
            ['total' => $total_pembimbing, 'label' => 'Total Pembimbing', 'class' => 'primary', 'icon' => '<i class="fas fa-chalkboard-teacher"></i>'],
            
            ['total' => $total_pending, 'label' => 'Jurnal Menunggu Validasi', 'class' => 'warning', 'icon' => '<i class="fas fa-clock"></i>'],
            ['total' => $total_belum_plotting, 'label' => 'Siswa Belum Di-Plotting', 'class' => 'danger', 'icon' => '<i class="fas fa-user-times"></i>'],
            
            [
                'total' => $total_belum_absen, 
                'label' => 'Belum Absen Hari Ini', 
                'class' => 'danger', 
                'icon' => '<i class="fas fa-user-slash"></i>',
                'is_modal' => true, 
                'target' => '#modalBelumAbsen'
            ],
        ];

    } elseif ($role == 'siswa') {
        $id_siswa = $id_ref;
        $stmt = $pdo->prepare("SELECT siswa.nama_lengkap, perusahaan.nama_perusahaan, pembimbing.nama_guru FROM siswa LEFT JOIN perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan LEFT JOIN pembimbing ON siswa.id_pembimbing = pembimbing.id_pembimbing WHERE siswa.id_siswa = :id_siswa");
        $stmt->execute(['id_siswa' => $id_siswa]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $nama_display = $data['nama_lengkap'];
        $tempat_pkl = !empty($data['nama_perusahaan']) ? $data['nama_perusahaan'] : 'Belum Ditempatkan';
        $nama_pembimbing = !empty($data['nama_guru']) ? $data['nama_guru'] : 'Belum Diatur';

    } elseif ($role == 'pembimbing') {
        $id_pembimbing = $id_ref;
        $stmt_nama = $pdo->prepare("SELECT nama_guru FROM pembimbing WHERE id_pembimbing = :id");
        $stmt_nama->execute(['id' => $id_pembimbing]);
        $nama_display = $stmt_nama->fetchColumn();

        $stmt_siswa = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE id_pembimbing = :id");
        $stmt_siswa->execute(['id' => $id_pembimbing]);
        $total_siswa = $stmt_siswa->fetchColumn();
        
        $sql_jurnal = "SELECT COUNT(*) FROM jurnal_harian JOIN siswa ON jurnal_harian.id_siswa = siswa.id_siswa WHERE siswa.id_pembimbing = :id_pembimbing AND jurnal_harian.status_validasi = 'Pending'";
        $stmt_jurnal = $pdo->prepare($sql_jurnal);
        $stmt_jurnal->execute(['id_pembimbing' => $id_pembimbing]);
        $total_jurnal_pending = $stmt_jurnal->fetchColumn();

        $sql_belum = "SELECT s.nama_lengkap, s.kelas, p.nama_perusahaan 
                      FROM siswa s
                      LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                      WHERE s.id_pembimbing = :id_pembimbing 
                      AND s.id_siswa NOT IN (SELECT id_siswa FROM absensi WHERE tanggal = :hari_ini)
                      ORDER BY s.nama_lengkap ASC";
        $stmt_belum = $pdo->prepare($sql_belum);
        $stmt_belum->execute([':hari_ini' => $hari_ini, ':id_pembimbing' => $id_pembimbing]);
        $siswa_belum_absen = $stmt_belum->fetchAll(PDO::FETCH_ASSOC);
        $total_belum_absen = count($siswa_belum_absen);

        $info_boxes = [
            ['total' => $total_siswa, 'label' => 'Siswa Bimbingan', 'class' => 'primary', 'icon' => '<i class="fas fa-users"></i>'],
            ['total' => $total_jurnal_pending, 'label' => 'Jurnal Pending', 'class' => 'warning', 'icon' => '<i class="fas fa-exclamation-circle"></i>', 'link' => 'index.php?page=pembimbing/validasi_daftar_siswa'],
            [
                'total' => $total_belum_absen, 
                'label' => 'Belum Absen Hari Ini', 
                'class' => 'danger', 
                'icon' => '<i class="fas fa-user-slash"></i>',
                'is_modal' => true,
                'target' => '#modalBelumAbsen'
            ],
        ];
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<div class="mb-4">
    <h2 class="fw-bold text-dark">Selamat Datang, <?php echo htmlspecialchars($nama_display); ?>!</h2>
    <p class="text-muted">Anda login sebagai: <strong><?php echo ucfirst($role); ?></strong></p>
</div>

<?php if (!empty($info_boxes)): ?>
    <h4 class="mb-3">Monitoring Statistik</h4>
    
    <?php if ($role == 'admin'): ?>
        <div class="row align-items-stretch mb-4">
            <?php for ($i = 0; $i < 3; $i++): $box = $info_boxes[$i]; ?>
                <div class="col-lg-4 col-md-6 mb-3 d-flex">
                    <?php 
                        if (isset($box['is_modal']) && $box['is_modal']) {
                            $tag_awal = '<div class="h-100 w-100 clickable-card" data-bs-toggle="modal" data-bs-target="' . $box['target'] . '">';
                            $tag_akhir = '</div>';
                        } else {
                            $link = isset($box['link']) ? $box['link'] : '#';
                            $tag_awal = '<a href="' . $link . '" style="text-decoration:none;" class="h-100 w-100">';
                            $tag_akhir = '</a>';
                        }
                    ?>
                    <?php echo $tag_awal; ?>
                    <div class="card p-3 info-card card-<?php echo $box['class']; ?> d-flex flex-column justify-content-center h-100 w-100 position-relative overflow-hidden">
                        <div class="row align-items-center position-relative" style="z-index: 2;">
                            <div class="col-4 display-6 text-<?php echo $box['class']; ?>">
                                <?php echo $box['icon']; ?>
                            </div>
                            <div class="col-8 text-end">
                                <h3 class="text-<?php echo $box['class']; ?>"><?php echo $box['total']; ?></h3>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($box['label']); ?></p>
                            </div>
                        </div>
                        <div class="icon-bg text-<?php echo $box['class']; ?> opacity-25" style="position:absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1;">
                            <?php echo $box['icon']; ?>
                        </div>
                    </div>
                    <?php echo $tag_akhir; ?>
                </div>
            <?php endfor; ?>
        </div>
        <div class="row align-items-stretch justify-content-center">
            <?php for ($i = 3; $i < 6; $i++): $box = $info_boxes[$i]; ?>
                <div class="col-lg-4 col-md-6 mb-3 d-flex">
                    <?php 
                        if (isset($box['is_modal']) && $box['is_modal']) {
                            $tag_awal = '<div class="h-100 w-100 clickable-card" data-bs-toggle="modal" data-bs-target="' . $box['target'] . '">';
                            $tag_akhir = '</div>';
                        } else {
                            $link = isset($box['link']) ? $box['link'] : '#';
                            $tag_awal = '<a href="' . $link . '" style="text-decoration:none;" class="h-100 w-100">';
                            $tag_akhir = '</a>';
                        }
                    ?>
                    <?php echo $tag_awal; ?>
                    <div class="card p-3 info-card card-<?php echo $box['class']; ?> d-flex flex-column justify-content-center h-100 w-100 position-relative overflow-hidden">
                        <div class="row align-items-center position-relative" style="z-index: 2;">
                            <div class="col-4 display-6 text-<?php echo $box['class']; ?>">
                                <?php echo $box['icon']; ?>
                            </div>
                            <div class="col-8 text-end">
                                <h3 class="text-<?php echo $box['class']; ?>"><?php echo $box['total']; ?></h3>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($box['label']); ?></p>
                            </div>
                        </div>
                        <div class="icon-bg text-<?php echo $box['class']; ?> opacity-25" style="position:absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1;">
                            <?php echo $box['icon']; ?>
                        </div>
                    </div>
                    <?php echo $tag_akhir; ?>
                </div>
            <?php endfor; ?>
        </div>

    <?php elseif ($role == 'pembimbing'): ?>
        <div class="row align-items-stretch">
            <?php foreach ($info_boxes as $box): ?>
                <div class="col-lg-4 col-md-6 mb-3 d-flex">
                    <?php 
                        if (isset($box['is_modal']) && $box['is_modal']) {
                            $tag_awal = '<div class="h-100 w-100 clickable-card" data-bs-toggle="modal" data-bs-target="' . $box['target'] . '">';
                            $tag_akhir = '</div>';
                        } else {
                            $link = isset($box['link']) ? $box['link'] : '#';
                            $tag_awal = '<a href="' . $link . '" style="text-decoration:none;" class="h-100 w-100">';
                            $tag_akhir = '</a>';
                        }
                    ?>
                    <?php echo $tag_awal; ?>
                    <div class="card p-3 info-card card-<?php echo $box['class']; ?> d-flex flex-column justify-content-center h-100 w-100 position-relative overflow-hidden">
                        <div class="row align-items-center position-relative" style="z-index: 2;">
                            <div class="col-4 display-6 text-<?php echo $box['class']; ?>">
                                <?php echo $box['icon']; ?>
                            </div>
                            <div class="col-8 text-end">
                                <h3 class="text-<?php echo $box['class']; ?>"><?php echo $box['total']; ?></h3>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($box['label']); ?></p>
                            </div>
                        </div>
                        <div class="icon-bg text-<?php echo $box['class']; ?> opacity-25" style="position:absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.1;">
                            <?php echo $box['icon']; ?>
                        </div>
                    </div>
                    <?php echo $tag_akhir; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <hr class="mt-4">
<?php endif; ?>

<?php if ($role == 'admin' || $role == 'pembimbing'): ?>
<div class="modal fade" id="modalBelumAbsen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-user-clock me-2"></i>Siswa Belum Absen Hari Ini</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($siswa_belum_absen)): ?>
                    <div class="p-4 text-center text-success">
                        <h4><i class="fas fa-check-circle fa-2x mb-2"></i></h4>
                        <p class="mb-0 fw-bold">Luar Biasa! Semua siswa sudah melakukan absensi.</p>
                    </div>
                <?php else: ?>
                    <table id="tableBelumAbsen" class="table table-hover table-striped mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Tempat PKL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($siswa_belum_absen as $mhs): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td class="fw-bold text-danger"><?php echo htmlspecialchars($mhs['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($mhs['kelas']); ?></td>
                                    <td>
                                        <?php echo !empty($mhs['nama_perusahaan']) ? htmlspecialchars($mhs['nama_perusahaan']) : '<span class="badge bg-secondary">Belum Plotting</span>'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if ($role == 'siswa'): ?>
    <div class="alert alert-info border-0 shadow-sm">
        <h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Informasi PKL Anda</h4>
        <hr>
        <p class="mb-1"><strong>Tempat PKL:</strong> <?php echo htmlspecialchars($tempat_pkl); ?></p>
        <p class="mb-0"><strong>Guru Pembimbing:</strong> <?php echo htmlspecialchars($nama_pembimbing); ?></p>
    </div>
    <hr class="mt-4">
<?php endif; ?>

<div style="margin-top: 30px;">
    <h4 class="mb-3"><i class="fas fa-bullhorn me-2 text-warning"></i>Pengumuman Terbaru</h4>
    <?php if (empty($pengumuman_list)): ?>
        <div class="alert alert-light text-center text-muted">Belum ada pengumuman terbaru saat ini.</div>
    <?php else: ?>
        <?php foreach ($pengumuman_list as $pengumuman): ?>
            <div class="pengumuman-box">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($pengumuman['judul']); ?></h5>
                    <span class="badge bg-light text-dark border"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y, H:i', strtotime($pengumuman['tanggal_post'])); ?></span>
                </div>
                <hr style="border: 0; border-top: 1px solid #ffe082; margin: 10px 0;">
                <p class="mb-0 text-secondary"><?php echo nl2br($pengumuman['isi']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($role == 'admin'): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3"><h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Ringkasan Data</h5></div>
                <div class="card-body"><canvas id="adminChart" height="100"></canvas></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. CHART JS
    <?php if ($role == 'admin'): ?>
    const ctx = document.getElementById('adminChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Siswa', 'Perusahaan', 'Pembimbing', 'Jurnal Pending'],
            datasets: [{
                label: 'Jumlah Data',
                data: [<?php echo $total_siswa; ?>, <?php echo $total_perusahaan; ?>, <?php echo $total_pembimbing; ?>, <?php echo $total_pending; ?>],
                backgroundColor: ['rgba(25, 135, 84, 0.6)', 'rgba(13, 110, 253, 0.6)', 'rgba(13, 202, 240, 0.6)', 'rgba(255, 193, 7, 0.6)'],
                borderColor: ['rgba(25, 135, 84, 1)', 'rgba(13, 110, 253, 1)', 'rgba(13, 202, 240, 1)', 'rgba(255, 193, 7, 1)'],
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
    <?php endif; ?>

    // 2. DATATABLES MODAL
    $(document).ready(function() {
        $('#modalBelumAbsen').on('shown.bs.modal', function () {
            if (!$.fn.DataTable.isDataTable('#tableBelumAbsen')) {
                $('#tableBelumAbsen').DataTable({
                    "pageLength": 10,
                    "lengthChange": false,
                    "searching": true,
                    "info": true,
                    "responsive": true
                });
            }
        });
    });
</script>