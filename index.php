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

// Logic untuk menghandle file action tanpa tampilan (redirects)
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <link rel="apple-touch-icon" href="assets/images/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes"> 
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"> 
    <meta name="apple-mobile-web-app-title" content="SIPKL">

    <style>
        /* --- CSS Tambahan untuk Mobile Bottom Nav --- */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            background-color: #fff;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            height: 70px;
        }

        .mobile-bottom-nav__item {
            flex-grow: 1;
            text-align: center;
            text-decoration: none;
            color: #6c757d;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-family: 'Poppins', sans-serif;
        }

        .mobile-bottom-nav__item i {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .mobile-bottom-nav__item.active {
            color: #0d6efd;
            font-weight: 600;
        }

        /* Padding body agar konten paling bawah tidak tertutup navbar di HP */
        body.has-bottom-nav {
            padding-bottom: 80px !important; 
        }

        /* Sembunyikan Bottom Nav di Desktop */
        @media (min-width: 768px) {
            .mobile-bottom-nav {
                display: none;
            }
            body.has-bottom-nav {
                padding-bottom: 0 !important;
            }
        }
    </style>

    <script>
        if ('serviceWorker' in navigator) {
          window.addEventListener('load', () => {
            navigator.serviceWorker.register('service-worker.js')
              .then(reg => console.log('PWA Service Worker registered!', reg))
              .catch(err => console.log('PWA Error:', err));
          });
        }
    </script>
</head>

<body style="background-color: #f4f6f9;" class="has-bottom-nav">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php?page=dashboard">
            <img src="assets/images/logo-smk.png" alt="Logo" width="35" height="35" class="d-inline-block align-text-top me-2">
            <span class="fw-bold">SIPKL SMKTAB</span>
        </a>

        <div class="collapse navbar-collapse d-none d-md-block" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'dashboard') ? 'active' : ''; ?>" href="index.php?page=dashboard">Home</a>
                </li>

                <?php if ($role == 'admin'): ?>
                    <?php
                        $active_manajemen = in_array($page, ['admin/siswa_data', 'admin/siswa_import', 'admin/perusahaan_data', 'admin/perusahaan_import', 'admin/pembimbing_data', 'admin/pembimbing_import']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_manajemen; ?>" href="#" role="button" data-bs-toggle="dropdown">Manajemen Data</a>
                        <ul class="dropdown-menu shadow">
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
                        $active_kegiatan = in_array($page, ['admin/plotting_data', 'admin/rekap_nilai', 'admin/rekap_absensi_harian', 'admin/jurnal_data', 'admin/absensi_data']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_kegiatan; ?>" href="#" role="button" data-bs-toggle="dropdown">Kegiatan PKL</a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="index.php?page=admin/plotting_data">Plotting Siswa</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/jurnal_data">Data Jurnal</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/absensi_data">Data Absensi</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/rekap_nilai">Rekap Nilai</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/rekap_absensi_harian">Rekap Absensi</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/generate_alpha">Generate Alpha</a></li>
                        </ul>
                    </li>

                    <?php
                        $active_pengumuman = in_array($page, ['admin/pengumuman_data', 'admin/pengumuman_tambah', 'admin/pengaturan_edit', 'admin/system_logs']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_pengumuman; ?>" href="#" role="button" data-bs-toggle="dropdown">Pengumuman & Sistem</a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="index.php?page=admin/pengumuman_tambah">Buat Pengumuman</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/pengumuman_data">Daftar Pengumuman</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/pengaturan_edit">Pengaturan Sistem</a></li>
                            <li><a class="dropdown-item" href="index.php?page=admin/system_logs">Logs</a></li>
                        </ul>
                    </li>

                <?php elseif ($role == 'siswa'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (strpos($page, 'siswa/absensi') !== false) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown">Absensi</a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="index.php?page=siswa/absensi">Absensi Harian</a></li>
                            <li><a class="dropdown-item" href="index.php?page=siswa/absensi_kalender">Kalender Absensi</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (strpos($page, 'siswa/jurnal') !== false) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown">Jurnal Kegiatan</a>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="index.php?page=siswa/jurnal_isi">Isi Jurnal</a></li>
                            <li><a class="dropdown-item" href="index.php?page=siswa/jurnal_lihat">Riwayat Jurnal</a></li>
                        </ul>
                    </li>

                <?php elseif ($role == 'pembimbing'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'pembimbing/validasi_daftar_siswa') ? 'active' : ''; ?>" href="index.php?page=pembimbing/validasi_daftar_siswa">Daftar Siswa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'pembimbing/rekap_absensi_harian') ? 'active' : ''; ?>" href="index.php?page=pembimbing/rekap_absensi_harian">Absensi Siswa</a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($page == 'profil') ? 'active' : ''; ?>" href="index.php?page=profil">Profil</a>
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

<nav class="mobile-bottom-nav d-md-none">
    
    <a href="index.php?page=dashboard" class="mobile-bottom-nav__item <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>

    <?php if ($role == 'admin'): ?>
        <a href="#" class="mobile-bottom-nav__item <?php echo (strpos($page, 'admin/siswa') !== false || strpos($page, 'admin/perusahaan') !== false) ? 'active' : ''; ?>" data-bs-toggle="offcanvas" data-bs-target="#offcanvasManajemen">
            <i class="fas fa-database"></i>
            <span>Data</span>
        </a>

        <a href="#" class="mobile-bottom-nav__item <?php echo (strpos($page, 'admin/jurnal') !== false || strpos($page, 'admin/absensi') !== false) ? 'active' : ''; ?>" data-bs-toggle="offcanvas" data-bs-target="#offcanvasKegiatan">
            <i class="fas fa-clipboard-list"></i>
            <span>Kegiatan</span>
        </a>

        <a href="#" class="mobile-bottom-nav__item <?php echo (strpos($page, 'pengumuman') !== false) ? 'active' : ''; ?>" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSistem">
            <i class="fas fa-cog"></i>
            <span>Sistem</span>
        </a>

    <?php elseif ($role == 'siswa'): ?>
        <a href="#" class="mobile-bottom-nav__item <?php echo (strpos($page, 'siswa/absensi') !== false) ? 'active' : ''; ?>" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAbsensiSiswa">
            <i class="fas fa-camera"></i>
            <span>Absen</span>
        </a>

        <a href="#" class="mobile-bottom-nav__item <?php echo (strpos($page, 'siswa/jurnal') !== false) ? 'active' : ''; ?>" data-bs-toggle="offcanvas" data-bs-target="#offcanvasJurnalSiswa">
            <i class="fas fa-book"></i>
            <span>Jurnal</span>
        </a>

    <?php elseif ($role == 'pembimbing'): ?>
        <a href="index.php?page=pembimbing/validasi_daftar_siswa" class="mobile-bottom-nav__item <?php echo ($page == 'pembimbing/validasi_daftar_siswa') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Siswa</span>
        </a>
        <a href="index.php?page=pembimbing/rekap_absensi_harian" class="mobile-bottom-nav__item <?php echo ($page == 'pembimbing/rekap_absensi_harian') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>Absensi</span>
        </a>
    <?php endif; ?>

    <a href="#" class="mobile-bottom-nav__item <?php echo ($page == 'profil') ? 'active' : ''; ?>" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAkun">
        <i class="fas fa-user"></i>
        <span>Akun</span>
    </a>
</nav>

<div class="offcanvas offcanvas-bottom h-auto" tabindex="-1" id="offcanvasManajemen">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title fw-bold">Manajemen Data</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body small pt-0">
    <div class="list-group list-group-flush">
        <a href="index.php?page=admin/siswa_data" class="list-group-item list-group-item-action"><i class="fas fa-user-graduate me-2 text-primary"></i> Data Siswa</a>
        <a href="index.php?page=admin/perusahaan_data" class="list-group-item list-group-item-action"><i class="fas fa-building me-2 text-primary"></i> Data Perusahaan</a>
        <a href="index.php?page=admin/pembimbing_data" class="list-group-item list-group-item-action"><i class="fas fa-chalkboard-teacher me-2 text-primary"></i> Data Pembimbing</a>
        <a href="index.php?page=admin/plotting_data" class="list-group-item list-group-item-action"><i class="fas fa-map-marked-alt me-2 text-primary"></i> Plotting Siswa</a>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-bottom h-auto" tabindex="-1" id="offcanvasKegiatan">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title fw-bold">Kegiatan PKL</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body small pt-0">
    <div class="row text-center g-3">
        <div class="col-4">
            <a href="index.php?page=admin/jurnal_data" class="text-decoration-none text-dark">
                <div class="p-3 bg-light rounded border mb-1"><i class="fas fa-book fa-lg text-primary"></i></div>
                <span>Jurnal</span>
            </a>
        </div>
        <div class="col-4">
            <a href="index.php?page=admin/absensi_data" class="text-decoration-none text-dark">
                <div class="p-3 bg-light rounded border mb-1"><i class="fas fa-clock fa-lg text-success"></i></div>
                <span>Absensi</span>
            </a>
        </div>
        <div class="col-4">
            <a href="index.php?page=admin/rekap_nilai" class="text-decoration-none text-dark">
                <div class="p-3 bg-light rounded border mb-1"><i class="fas fa-star fa-lg text-warning"></i></div>
                <span>Nilai</span>
            </a>
        </div>
        <div class="col-4">
            <a href="index.php?page=admin/rekap_absensi_harian" class="text-decoration-none text-dark">
                <div class="p-3 bg-light rounded border mb-1"><i class="fas fa-list-alt fa-lg text-info"></i></div>
                <span>Rekap</span>
            </a>
        </div>
        <div class="col-4">
            <a href="index.php?page=admin/generate_alpha" class="text-decoration-none text-dark">
                <div class="p-3 bg-light rounded border mb-1"><i class="fas fa-user-times fa-lg text-danger"></i></div>
                <span>Alpha</span>
            </a>
        </div>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-bottom h-auto" tabindex="-1" id="offcanvasSistem">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title fw-bold">Pengumuman & Sistem</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body small pt-0">
     <div class="list-group list-group-flush">
        <a href="index.php?page=admin/pengumuman_data" class="list-group-item list-group-item-action"><i class="fas fa-bullhorn me-2"></i> Daftar Pengumuman</a>
        <a href="index.php?page=admin/pengumuman_tambah" class="list-group-item list-group-item-action"><i class="fas fa-plus-circle me-2"></i> Buat Pengumuman</a>
        <a href="index.php?page=admin/pengaturan_edit" class="list-group-item list-group-item-action"><i class="fas fa-cogs me-2"></i> Pengaturan</a>
        <a href="index.php?page=admin/system_logs" class="list-group-item list-group-item-action"><i class="fas fa-terminal me-2"></i> Logs</a>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-bottom h-auto" tabindex="-1" id="offcanvasAbsensiSiswa">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menu Absensi</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body pt-0">
    <div class="d-grid gap-2">
        <a href="index.php?page=siswa/absensi" class="btn btn-primary btn-lg"><i class="fas fa-camera me-2"></i> Absen Hari Ini</a>
        <a href="index.php?page=siswa/absensi_kalender" class="btn btn-outline-secondary"><i class="fas fa-calendar-alt me-2"></i> Lihat Riwayat Absen</a>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-bottom h-auto" tabindex="-1" id="offcanvasJurnalSiswa">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menu Jurnal</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body pt-0">
    <div class="d-grid gap-2">
        <a href="index.php?page=siswa/jurnal_isi" class="btn btn-success btn-lg"><i class="fas fa-pen me-2"></i> Isi Jurnal Kegiatan</a>
        <a href="index.php?page=siswa/jurnal_lihat" class="btn btn-outline-secondary"><i class="fas fa-history me-2"></i> Lihat Riwayat Jurnal</a>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-bottom h-auto" tabindex="-1" id="offcanvasAkun">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menu Akun</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body pt-0">
    <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between align-items-center">
            Login sebagai
            <span class="badge bg-primary rounded-pill"><?php echo ucfirst($role); ?></span>
        </li>
        <a href="index.php?page=profil" class="list-group-item list-group-item-action"><i class="fas fa-user-circle me-2"></i> Profil Saya</a>
        <a href="logout.php" class="list-group-item list-group-item-action text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </ul>
  </div>
</div>

<footer class="d-none d-md-block">
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
        // DataTables & Select2
        $('.datatable').DataTable({ "columnDefs": [ { "targets": [-1], "orderable": false } ] });
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Pilih opsi...', allowClear: true });

        // SweetAlert Hapus
        $(document).on('click', '.btn-hapus', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            Swal.fire({
                title: 'Yakin hapus data ini?', text: "Data tidak bisa dikembalikan!", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) document.location.href = href; });
        });
        
        // SweetAlert Alpha
        $(document).on('click', '.btn-alpha', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            Swal.fire({
                title: 'Tandai Alpha?', text: "Siswa ini akan dianggap tidak hadir.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Ya, Alpha', cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) document.location.href = href; });
        });
    });

    // SweetAlert Flash Messages
    var pesanSukses = $('.alert-success').text();
    var pesanError = $('.alert-danger').text();
    if(pesanSukses){ Swal.fire({icon: 'success', title: 'Berhasil!', text: pesanSukses, timer: 3000, showConfirmButton: false}); $('.alert-success').hide(); }
    if(pesanError){ Swal.fire({icon: 'error', title: 'Gagal!', text: pesanError}); $('.alert-danger').hide(); }

    // iOS Prompt Logic
    const isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
    const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);
    if (isIos() && !isInStandaloneMode()) { document.getElementById('ios-install-prompt').style.display = 'block'; }
    function tutupPrompt() { document.getElementById('ios-install-prompt').style.display = 'none'; }
</script>
</body>
</html>