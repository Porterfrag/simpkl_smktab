<?php
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config/koneksi.php';
require 'core/functions.php';

// HITUNG NOTIFIKASI BELUM DIBACA (Untuk user yang login)
$notif_count = 0;
$notif_list = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt_n = $pdo->prepare("SELECT * FROM notifikasi WHERE id_user = ? AND status = 'unread' ORDER BY tanggal DESC");
    $stmt_n->execute([$uid]);
    $notif_list = $stmt_n->fetchAll(PDO::FETCH_ASSOC);
    $notif_count = count($notif_list);
}

$role = $_SESSION['role'];

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$action_pages = [
    'admin/siswa_hapus',
    'admin/siswa_reset_password',
    'admin/perusahaan_hapus',
    'admin/pembimbing_hapus',
    'admin/pembimbing_reset_password',
    'siswa/jurnal_hapus',
    'admin/pengumuman_hapus'
];

if (in_array($page, $action_pages)) {
    $file_path = "pages/" . str_replace('../', '', $page) . '.php';
    if (file_exists($file_path)) {
        include $file_path;
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi PKL</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0d6efd">
<link rel="apple-touch-icon" href="assets/images/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes"> <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"> <meta name="apple-mobile-web-app-title" content="SIPKL">

<script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js')
          .then(reg => console.log('PWA Service Worker registered!', reg))
          .catch(err => console.log('PWA Error:', err));
      });
    }
</script>
<body style="background-color: #f4f6f9;">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php?page=dashboard">
    <img src="assets/images/logo-smk.png" alt="Logo" width="40" height="40" class="d-inline-block align-text-top me-2">

    SIPKL SMKTAB
</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'dashboard') ? 'active' : ''; ?>" href="index.php?page=dashboard">Home</a>
                </li>

                <?php if ($role == 'admin'): ?>
                    <?php
                        $active_manajemen = in_array($page, ['admin/siswa_data', 'admin/siswa_tambah', 'admin/siswa_edit', 'admin/siswa_import', 'admin/perusahaan_data', 'admin/perusahaan_tambah', 'admin/perusahaan_edit', 'admin/pembimbing_data', 'admin/pembimbing_tambah', 'admin/pembimbing_edit', 'admin/perusahaan_import', 'admin/pembimbing_import']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_manajemen; ?>" href="#" id="navbarDropdownManajemen" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Manajemen Data
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownManajemen">
                            <li><a class="dropdown-item" href="index.php?page=admin/siswa_data">Data Siswa</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/siswa_import">Import Siswa (CSV)</a></li>
                              <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/perusahaan_data">Data Perusahaan</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/perusahaan_import">Import Perusahaan (CSV)</a></li>
                              <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/pembimbing_data">Data Pembimbing</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/pembimbing_import">Import Pembimbing (CSV)</a></li>
                        </ul>
                    </li>

                    <?php
                        $active_kegiatan = in_array($page, ['admin/plotting_data', 'admin/plotting_edit', 'admin/rekap_nilai', 'admin/rekap_absensi_harian', 'admin/jurnal_data', 'admin/absensi_data']) ? 'active' : '';
                    ?>
                   <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_kegiatan; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Kegiatan PKL</a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="index.php?page=admin/plotting_data">Plotting Siswa</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/jurnal_data">Data Jurnal (Semua)</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/absensi_data">Data Absensi (Semua)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/rekap_nilai">Rekap Nilai Akhir</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/rekap_absensi_harian">Rekap Absensi Harian</a></li>
                        </ul>
                    </li>

                    <?php
                        $active_pengumuman = in_array($page, ['admin/pengumuman_data', 'admin/pengumuman_tambah', 'admin/pengaturan_edit', 'admin/system_logs']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_pengumuman; ?>" href="#" id="navbarDropdownPengumuman" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Pengumuman & Sistem
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownPengumuman">
                            <li><a class="dropdown-item" href="index.php?page=admin/pengumuman_tambah">Buat Pengumuman</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/pengumuman_data">Daftar Pengumuman</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/pengaturan_edit">Pengaturan Sistem</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/system_logs">Logs</a></li>
                        </ul>
                    </li>

               <?php elseif ($role == 'siswa'): ?>

                    <?php
                        $active_absensi_siswa = in_array($page, ['siswa/absensi', 'siswa/absensi_kalender']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_absensi_siswa; ?>" href="#" id="navbarDropdownAbsensiSiswa" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Absensi
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownAbsensiSiswa">
                            <li><a class="dropdown-item" href="index.php?page=siswa/absensi">Absensi Harian</a></li>
                            <li><a class="dropdown-item" href="index.php?page=siswa/absensi_kalender">Kalender Absensi</a></li>
                        </ul>
                    </li>

                    <?php
                        $active_jurnal_siswa = in_array($page, ['siswa/jurnal_isi', 'siswa/jurnal_lihat']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_jurnal_siswa; ?>" href="#" id="navbarDropdownJurnalSiswa" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Jurnal Kegiatan
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownJurnalSiswa">
                            <li><a class="dropdown-item" href="index.php?page=siswa/jurnal_isi">Isi Jurnal</a></li>
                            <li><a class="dropdown-item" href="index.php?page=siswa/jurnal_lihat">Riwayat Jurnal</a></li>
                        </ul>
                    </li>

                <?php elseif ($role == 'pembimbing'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'pembimbing/validasi_daftar_siswa') ? 'active' : ''; ?>" href="index.php?page=pembimbing/validasi_daftar_siswa">Daftar Siswa Bimbingan</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'pembimbing/rekap_absensi_harian') ? 'active' : ''; ?>" href="index.php?page=pembimbing/rekap_absensi_harian">Absensi Harian Siswa</a>
                    </li>
                <?php endif; ?>
            </ul>

           <ul class="navbar-nav ms-auto mb-2 mb-lg-0 d-flex align-items-start">
               <?php if($notif_count > 0): ?>
                    <li class="nav-item me-lg-2">
                        <a class="nav-link text-warning fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#notifikasiModal">
                            <i class="fas fa-bell"></i>
                            <span class="badge rounded-pill bg-danger"><?php echo $notif_count; ?></span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'profil') ? 'active' : ''; ?>" href="index.php?page=profil">Profil Saya</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container mt-4 mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <?php
            $page_file = str_replace('../', '', $page) . '.php';
            $file_path = "pages/" . $page_file;

            if (file_exists($file_path)) {
                // Periksa akses berdasarkan peran (Role-Based Access Control)
                $access_granted = false;
                if ($role == 'admin' && (strpos($page, 'admin/') === 0 || $page == 'dashboard' || $page == 'profil')) {
                    $access_granted = true;
                } elseif ($role == 'siswa' && (strpos($page, 'siswa/') === 0 || $page == 'dashboard' || $page == 'profil')) {
                    $access_granted = true;
                } elseif ($role == 'pembimbing' && (strpos($page, 'pembimbing/') === 0 || $page == 'dashboard' || $page == 'profil')) {
                    $access_granted = true;
                } elseif (strpos($page, 'admin/') !== 0 && strpos($page, 'siswa/') !== 0 && strpos($page, 'pembimbing/') !== 0) {
                     // Halaman umum (dashboard, profil, dll. tanpa prefix role)
                    $access_granted = true;
                }

                if ($access_granted) {
                    include $file_path;
                } else {
                    echo "<div class='alert alert-danger'>Error 403: Akses Dilarang.</div>";
                }
            } else {
                echo "<div class='alert alert-warning'>Error 404: Halaman tidak ditemukan.</div>";
            }
            ?>
        </div>
    </div>
</main>

<footer>
    <p class="text-center text-muted mb-4 small">&copy; <?php echo date("Y"); ?> SMKN 1 Sungai Tabuk. All rights reserved.</p>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // 1. Inisiasi DataTables Global
        $('.datatable').DataTable({
            "columnDefs": [ { "targets": [-1], "orderable": false } ]
        });

        // 2. Inisiasi Select2 Global
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Pilih opsi...',
            allowClear: true
        });
        // 3. LOGIKA SWEETALERT UNTUK TOMBOL HAPUS
        $(document).on('click', '.btn-hapus', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.location.href = href;
                }
            });
        });

        // 3. LOGIKA SWEETALERT UNTUK TOMBOL ALPHA
        $(document).on('click', '.btn-alpha', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Tandai siswa ini alpha ?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, tandai alpha!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.location.href = href;
                }
            });
        });
    });

    // 4. SWEETALERT UNTUK PESAN PHP (FLASH MESSAGE)
        var pesanSukses = $('.alert-success').text();
        var pesanError = $('.alert-danger').text();

        if(pesanSukses){
             Swal.fire({
                 icon: 'success',
                 title: 'Berhasil!',
                 text: pesanSukses,
                 timer: 3000,
                 showConfirmButton: false
             });
             $('.alert-success').hide();
        }

        if(pesanError){
             Swal.fire({
                 icon: 'error',
                 title: 'Gagal!',
                 text: pesanError
             });
             $('.alert-danger').hide();
        }
</script>

<div class="modal fade" id="notifikasiModal" tabindex="-1" aria-labelledby="notifikasiModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notifikasiModalLabel">Notifikasi (<?php echo $notif_count; ?> Belum Dibaca)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if ($notif_count > 0): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($notif_list as $notif): ?>
                    <li class="list-group-item list-group-item-action list-group-item-primary">
                        <small class="text-muted float-end"><?php echo date('d M H:i', strtotime($notif['tanggal'])); ?></small>
                        <p class="mb-1 fw-bold"><?php echo htmlspecialchars($notif['judul']); ?></p>
                        <p class="mb-1 small"><?php echo htmlspecialchars($notif['pesan']); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="alert alert-info text-center">Tidak ada notifikasi baru.</div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <a href="index.php?page=notifikasi_mark_all_read" class="btn btn-sm btn-outline-primary"><i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
<div id="ios-install-prompt" class="fixed-bottom p-3 m-3 bg-white rounded shadow-lg border" style="display: none; z-index: 9999;">
    <div class="d-flex align-items-start">
        <img src="assets/images/icon-192.png" width="50" class="rounded me-3">
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1">Install Aplikasi SIPKL</h6>
            <p class="small text-muted mb-0">
                Untuk menginstall di iPhone: <br>
                1. Tekan tombol <strong>Share</strong> <i class="fas fa-share-square text-primary"></i> di bawah browser Safari.<br>
                2. Pilih <strong>"Add to Home Screen"</strong> (Tambah ke Layar Utama) <i class="fas fa-plus-square"></i>.
            </p>
        </div>
        <button type="button" class="btn-close ms-2" onclick="tutupPrompt()"></button>
    </div>
</div>

<script>
    // Deteksi apakah device adalah iOS (iPhone/iPad)
    const isIos = () => {
        const userAgent = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(userAgent);
    }

    // Deteksi apakah sudah diinstall (Standalone mode)
    const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

    // Tampilkan pesan hanya jika: Ini iOS DAN Belum diinstall
    if (isIos() && !isInStandaloneMode()) {
        document.getElementById('ios-install-prompt').style.display = 'block';
    }

    function tutupPrompt() {
        document.getElementById('ios-install-prompt').style.display = 'none';
    }
</script>
</body>
</html>