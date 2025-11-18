<?php
ob_start(); 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config/koneksi.php';
require 'core/functions.php'; 

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
<body style="background-color: #f4f6f9;">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php?page=dashboard">
    <img src="assets/images/logo-smk.png" alt="Logo" width="40" height="40" class="d-inline-block align-text-top me-2">
    
    SIMPKL SMKTAB
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
                        $active_manajemen = in_array($page, ['admin/siswa_data', 'admin/siswa_tambah', 'admin/siswa_edit', 'admin/siswa_import', 'admin/perusahaan_data', 'admin/perusahaan_tambah', 'admin/perusahaan_edit', 'admin/pembimbing_data', 'admin/pembimbing_tambah', 'admin/pembimbing_edit']) ? 'active' : '';
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
                        $active_kegiatan = in_array($page, ['admin/plotting_data', 'admin/plotting_edit', 'admin/rekap_nilai', 'admin/rekap_absensi_harian']) ? 'active' : '';
                    ?>
                   <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Kegiatan PKL</a>
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
                        $active_pengumuman = in_array($page, ['admin/pengumuman_data', 'admin/pengumuman_tambah', 'admin/pengaturan_edit']) ? 'active' : '';
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
                        </ul>
                    </li>

               <?php elseif ($role == 'siswa'): ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAbsensiSiswa" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Absensi
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownAbsensiSiswa">
                            <li><a class="dropdown-item" href="index.php?page=siswa/absensi">Absensi Harian</a></li>
                            <li><a class="dropdown-item" href="index.php?page=siswa/absensi_kalender">Kalender Absensi</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownJurnalSiswa" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Jurnal Kegiatan
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownJurnalSiswa">
                            <li><a class="dropdown-item" href="index.php?page=siswa/jurnal_isi">Isi Jurnal</a></li>
                            <li><a class="dropdown-item" href="index.php?page=siswa/jurnal_lihat">Riwayat Jurnal</a></li>
                        </ul>
                    </li>
                
                <?php elseif ($role == 'pembimbing'): ?>
                    <?php 
                        $active_validasi = in_array($page, ['pembimbing/validasi_daftar_siswa', 'pembimbing/validasi_jurnal_siswa', 'admin/rekap_nilai']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_validasi; ?>" href="#" id="navbarDropdownValidasi" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Validasi & Nilai
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownValidasi">
                            <li><a class="dropdown-item" href="index.php?page=pembimbing/validasi_daftar_siswa">Daftar Siswa Bimbingan</a></li>
                        </ul>
                    </li>

                    <?php 
                        $active_absensi = in_array($page, ['pembimbing/rekap_absensi_harian', 'pembimbing/rekap_absensi_siswa', 'pembimbing/rekap_kalender_siswa']) ? 'active' : '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $active_absensi; ?>" href="#" id="navbarDropdownAbsensi" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Absensi Siswa
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownAbsensi">
                            <li><a class="dropdown-item" href="index.php?page=pembimbing/rekap_absensi_harian">Absensi Harian</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
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
                if ($role == 'admin' && strpos($page, 'admin/') === 0) {
                    include $file_path;
                } elseif ($role == 'siswa' && strpos($page, 'siswa/') === 0) {
                    include $file_path;
                } elseif ($role == 'pembimbing' && strpos($page, 'pembimbing/') === 0) {
                    include $file_path;
                } elseif (strpos($page, 'admin/') !== 0 && strpos($page, 'siswa/') !== 0 && strpos($page, 'pembimbing/') !== 0) {
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
    });
</script>

</body>
</html>