<?php
// (Pastikan file ini hanya di-include oleh index.php)
// session_start(); // (Sudah dimulai di index.php)

// Ambil id_siswa dari session
if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') {
    die("Akses tidak sah!");
}
$id_siswa = $_SESSION['id_ref'];

// (Asumsi $pdo dan $SETTINGS sudah ada dari index.php)

// ==========================================================
// --- LOGIKA PENGAMBILAN BULAN & TAHUN ---
// ==========================================================
$bulan_pilihan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$nama_bulan_ini = date('F Y', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
// ==========================================================

// --- 1. AMBIL NAMA SISWA & HARI KERJA PERUSAHAAN ---
$hari_kerja_perusahaan = []; // Default kosong
try {
    $sql_info = "SELECT s.nama_lengkap, p.hari_kerja 
                 FROM siswa s
                 LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                 WHERE s.id_siswa = :id_siswa";
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute(['id_siswa' => $id_siswa]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    $nama_siswa = $info['nama_lengkap'];
    
    // Ubah string "1,2,3,4,5" menjadi array [1, 2, 3, 4, 5]
    if (!empty($info['hari_kerja'])) {
        $hari_kerja_perusahaan = explode(',', $info['hari_kerja']);
    } else {
        // Default Senin-Jumat jika belum diset
        $hari_kerja_perusahaan = [1, 2, 3, 4, 5];
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// --- 2. AMBIL DATA ABSENSI SESUAI BULAN PILIHAN ---
$data_absensi_bulan_ini = [];
// Variabel untuk Dashboard Ringkasan
$summary = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];

try {
    $sql_absen = "SELECT DAY(tanggal) as hari, status 
                  FROM absensi 
                  WHERE id_siswa = :id_siswa 
                    AND MONTH(tanggal) = :bulan 
                    AND YEAR(tanggal) = :tahun";
    $stmt_absen = $pdo->prepare($sql_absen);
    $stmt_absen->execute([
        ':id_siswa' => $id_siswa,
        ':bulan' => $bulan_pilihan,
        ':tahun' => $tahun_pilihan
    ]);
    
    while ($row = $stmt_absen->fetch(PDO::FETCH_ASSOC)) {
        $data_absensi_bulan_ini[$row['hari']] = $row['status'];
        
        // Hitung ringkasan untuk dashboard
        if(isset($summary[$row['status']])) {
            $summary[$row['status']]++;
        }
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data absensi: " . $e->getMessage() . "</div>";
}


// --- LOGIKA KALENDER ---
$jumlah_hari_di_bulan = date('t', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_pertama_minggu = date('N', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_ini = date('d');
$bulan_ini_sekarang = date('m');
$tahun_ini_sekarang = date('Y');

// [LOGIKA BARU] AMBIL BATAS TANGGAL DARI SETTINGS
$pkl_start = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : '2020-01-01';
$pkl_end   = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : '2030-12-31';

// Cek apakah kalender yang dibuka adalah bulan ini
$is_current_month = ($bulan_pilihan == $bulan_ini_sekarang && $tahun_pilihan == $tahun_ini_sekarang);
?>

<style>
    /* Wrapper responsive */
    .calendar-container {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Summary Cards (Dashboard Mini) */
    .summary-card {
        background: #fff;
        border-radius: 12px;
        padding: 10px 5px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
        height: 100%;
    }
    .summary-count { font-size: 1.2rem; font-weight: 800; display: block; line-height: 1.2; }
    .summary-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Grid Header (Mon, Tue, etc) */
    .calendar-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        font-weight: 600;
        font-size: 0.8rem;
        color: #6c757d;
        padding-bottom: 8px;
        margin-top: 15px;
    }
    
    /* Grid Body */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px; /* Jarak antar kotak */
    }

    /* Individual Day Cell */
    .day-cell {
        aspect-ratio: 1 / 1; /* Kotak selalu persegi */
        background-color: #fff;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        font-size: 0.95rem;
        font-weight: 500;
        color: #444;
        border: 1px solid #eaeaea;
        transition: all 0.2s;
    }
    
    .day-cell.empty { background: transparent; border: none; }

    /* Status Colors (Backgrounds) */
    .day-cell.status-hadir { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
    .day-cell.status-izin { background-color: #cfe2ff; color: #084298; border-color: #b6d4fe; }
    .day-cell.status-sakit { background-color: #fff3cd; color: #664d03; border-color: #ffecb5; }
    .day-cell.status-alpha { background-color: #f8d7da; color: #842029; border-color: #f5c6cb; }
    .day-cell.status-libur { background-color: #f8f9fa; color: #adb5bd; }
    
    /* Auto Alpha (Dashed Red) */
    .day-cell.status-missing { 
        background-color: #fff; 
        border: 2px dashed #dc3545; 
        color: #dc3545;
    }

    /* Hari Ini Highlight */
    .day-cell.today {
        box-shadow: 0 0 0 2px #0d6efd; /* Ring luar */
        z-index: 2;
        font-weight: 800;
    }

    /* Text Status inside cell (Hidden on very small screens) */
    .cell-status-text {
        font-size: 0.6rem;
        margin-top: 1px;
        font-weight: normal;
        display: block;
    }
    
    /* Filter Area styling */
    .filter-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold text-dark">Absensi Saya</h4>
</div>

<div class="row g-2 mb-4">
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-success"><?php echo $summary['Hadir']; ?></span>
            <span class="summary-label">Hadir</span>
        </div>
    </div>
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-primary"><?php echo $summary['Izin']; ?></span>
            <span class="summary-label">Izin</span>
        </div>
    </div>
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-warning"><?php echo $summary['Sakit']; ?></span>
            <span class="summary-label">Sakit</span>
        </div>
    </div>
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-danger"><?php echo $summary['Alpha']; ?></span>
            <span class="summary-label">Alpha</span>
        </div>
    </div>
</div>

<div class="filter-card">
    <form action="index.php" method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="siswa/absensi_kalender">
        
        <div class="col-5">
            <label class="small text-muted mb-1">Bulan</label>
            <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    $sel = ($i == $bulan_pilihan) ? 'selected' : '';
                    echo "<option value='$i' $sel>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-4">
            <label class="small text-muted mb-1">Tahun</label>
            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php
                for ($i = date('Y')-1; $i <= date('Y')+1; $i++) {
                    $sel = ($i == $tahun_pilihan) ? 'selected' : '';
                    echo "<option value='$i' $sel>$i</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-3 d-grid">
             <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i></button>
        </div>
    </form>
</div>

<div class="calendar-container">
    <h6 class="text-center mb-0 fw-bold text-uppercase text-primary">
        <?php echo $nama_bulan_ini; ?>
    </h6>

    <div class="calendar-header">
        <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div>
    </div>

    <div class="calendar-grid">
        <?php
        // 1. Kotak kosong awal bulan
        for ($i = 1; $i < $hari_pertama_minggu; $i++) {
            echo '<div class="day-cell empty"></div>';
        }

        // 2. Loop tanggal
        for ($hari_ke = 1; $hari_ke <= $jumlah_hari_di_bulan; $hari_ke++) {
            $hari_minggu_ini = date('N', mktime(0, 0, 0, $bulan_pilihan, $hari_ke, $tahun_pilihan));
            $tanggal_loop = "$tahun_pilihan-" . str_pad($bulan_pilihan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($hari_ke, 2, '0', STR_PAD_LEFT);
            $tanggal_hari_ini = date('Y-m-d');
            
            // CSS Classes setup
            $classes = ['day-cell'];
            $status_text = ''; // Text kecil di bawah angka

            // Highlight Hari Ini
            if ($is_current_month && $hari_ke == $hari_ini) {
                $classes[] = 'today';
            }

            if (isset($data_absensi_bulan_ini[$hari_ke])) {
                // === KASUS A: Ada Data Absensi ===
                $status = $data_absensi_bulan_ini[$hari_ke];
                $classes[] = 'status-' . strtolower($status);
                $status_text = $status;
            } else {
                // === KASUS B: Tidak Ada Data ===
                
                // Cek apakah Hari Kerja Perusahaan?
                if (in_array($hari_minggu_ini, $hari_kerja_perusahaan)) {
                    
                    // Cek apakah dalam periode PKL?
                    $is_within_period = ($tanggal_loop >= $pkl_start && $tanggal_loop <= $pkl_end);
                    
                    if ($tanggal_loop < $tanggal_hari_ini && $is_within_period) {
                        // Tidak hadir (Alpha Otomatis)
                        $classes[] = 'status-missing';
                        $status_text = '!';
                    } elseif (!$is_within_period) {
                        // Diluar periode PKL
                        $status_text = '-';
                    }
                } else {
                    // Hari Libur Rutin
                    $classes[] = 'status-libur';
                }
            }

            // Render HTML Cell
            echo '<div class="' . implode(' ', $classes) . '">';
            echo '<span>' . $hari_ke . '</span>';
            if ($status_text) {
                // Potong teks jika terlalu panjang (misal "Sakit" jadi "Skt" optional, tapi CSS handle overflow)
                echo '<span class="cell-status-text">' . substr($status_text, 0, 5) . '</span>';
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<div class="mt-4 pt-3 border-top">
    <small class="text-muted d-block mb-2 fw-bold">Keterangan:</small>
    <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-success bg-opacity-25 text-success border border-success">Hadir</span>
        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary">Izin</span>
        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning">Sakit</span>
        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">Alpha</span>
    </div>
</div>