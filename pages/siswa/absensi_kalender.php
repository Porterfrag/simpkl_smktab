<?php

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') {
    die("Akses tidak sah!");
}
$id_siswa = $_SESSION['id_ref'];


$bulan_pilihan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$nama_bulan_ini = date('F Y', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));

$hari_kerja_perusahaan = []; 
try {
    $sql_info = "SELECT s.nama_lengkap, p.hari_kerja 
                 FROM siswa s
                 LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                 WHERE s.id_siswa = :id_siswa";
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute(['id_siswa' => $id_siswa]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    $nama_siswa = $info['nama_lengkap'];
    
    if (!empty($info['hari_kerja'])) {
        $hari_kerja_perusahaan = explode(',', $info['hari_kerja']);
    } else {
        $hari_kerja_perusahaan = [1, 2, 3, 4, 5];
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$data_absensi_bulan_ini = [];
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
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data absensi: " . $e->getMessage() . "</div>";
}


$jumlah_hari_di_bulan = date('t', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_pertama_minggu = date('N', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_ini = date('d');
$bulan_ini_sekarang = date('m');
$tahun_ini_sekarang = date('Y');

$pkl_start = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : '2020-01-01';
$pkl_end   = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : '2030-12-31';

$is_current_month = ($bulan_pilihan == $bulan_ini_sekarang && $tahun_pilihan == $tahun_ini_sekarang);
?>

<style>
    .kalender-wrap { 
        width: 100%; 
        margin-top: 20px; 
        overflow-x: auto; 
        -webkit-overflow-scrolling: touch; 
    }
    .kalender-wrap table { 
        width: 100%; 
        min-width: 700px; 
        border-collapse: collapse; 
        table-layout: fixed; 
    }
    .kalender-wrap th, .kalender-wrap td { 
        border: 1px solid #dee2e6; 
        height: 100px; 
        vertical-align: top; 
        padding: 8px; 
        background-color: white;
    }
    .kalender-wrap th { 
        height: 40px; 
        text-align: center; 
        background-color: #0d6efd; 
        color: white; 
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    .kalender-wrap .nomor-tanggal { 
        font-size: 1.1rem; 
        font-weight: 700; 
        margin-bottom: 8px; 
        display: block; 
        color: #495057;
    }
    .kalender-wrap .hari-ini { 
        background-color: #e7f1ff !important; 
        border: 2px solid #0d6efd; 
        position: relative;
        z-index: 1;
    }
    .kalender-wrap .hari-ini .nomor-tanggal { 
        color: #0d6efd; 
        text-decoration: underline;
    }
    .status-badge { 
        display: block; 
        padding: 4px 6px; 
        border-radius: 4px; 
        text-align: center; 
        color: white; 
        font-size: 0.75rem; 
        font-weight: 600;
        margin-top: 4px; 
        white-space: nowrap; 
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .status-hadir { background-color: #198754; }
    .status-izin { background-color: #0d6efd; }
    .status-sakit { background-color: #ffc107; color: #212529; }
    .status-alpha { background-color: #dc3545; }
    .status-libur { background-color: #f8f9fa; color: #adb5bd; border: 1px solid #dee2e6; }
    .status-alpha-auto { background-color: #f8d7da; color: #842029; border: 1px solid #f5c6cb; }
    .bukan-bulan-ini { background-color: #f8f9fa; }
    .form-ganti-bulan { 
        margin-bottom: 20px; 
        background: #fff; 
        padding: 15px; 
        border-radius: 8px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
</style>

<h2 class="mb-4">Kalender Absensi</h2>

<div class="form-ganti-bulan">
    <form action="index.php" method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="page" value="siswa/absensi_kalender">
        
        <div class="col-auto"><label class="fw-bold">Filter:</label></div>
        <div class="col-auto">
            <select name="bulan" class="form-select form-select-sm">
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    $sel = ($i == $bulan_pilihan) ? 'selected' : '';
                    echo "<option value='$i' $sel>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="tahun" class="form-select form-select-sm">
                <?php
                for ($i = date('Y')-1; $i <= date('Y')+1; $i++) {
                    $sel = ($i == $tahun_pilihan) ? 'selected' : '';
                    echo "<option value='$i' $sel>$i</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
        </div>
    </form>
</div>

<h4 class="mb-3 text-primary"><i class="fas fa-calendar-alt me-2"></i><?php echo $nama_bulan_ini; ?></h4>

<div class="kalender-wrap">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th><th>Min</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php
                    $hari_ke = 1; 
                    
                    for ($i = 1; $i < $hari_pertama_minggu; $i++) {
                        echo '<td class="bukan-bulan-ini"></td>';
                    }

                    while ($hari_ke <= $jumlah_hari_di_bulan) {
                        
                        $hari_minggu_ini = date('N', mktime(0, 0, 0, $bulan_pilihan, $hari_ke, $tahun_pilihan));
                        
                        $tanggal_loop = "$tahun_pilihan-" . str_pad($bulan_pilihan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($hari_ke, 2, '0', STR_PAD_LEFT);
                        $tanggal_hari_ini = date('Y-m-d');

                        $class_hari_ini = ($is_current_month && $hari_ke == $hari_ini) ? 'hari-ini' : '';
                        
                        $content = "<span class='nomor-tanggal'>$hari_ke</span>";
                        
                        if (isset($data_absensi_bulan_ini[$hari_ke])) {
                            $status = $data_absensi_bulan_ini[$hari_ke];
                            $badge_class = 'status-' . strtolower($status);
                            if($status == 'Libur') $badge_class = 'status-libur';
                            
                            $content .= "<span class='status-badge $badge_class'>$status</span>";
                        
                        } else {
                            
                            if (in_array($hari_minggu_ini, $hari_kerja_perusahaan)) {
                                
                                $is_within_period = ($tanggal_loop >= $pkl_start && $tanggal_loop <= $pkl_end);
                                
                                if ($tanggal_loop < $tanggal_hari_ini && $is_within_period) {
                                    $content .= "<span class='status-badge status-alpha-auto'>Tidak Hadir</span>";
                                } elseif (!$is_within_period) {
                                    $content .= "<span class='text-muted small d-block mt-1'>-</span>";
                                }
                            } else {
                                $content .= "<span class='status-badge status-libur'>Libur</span>";
                            }
                        }

                        echo "<td class='$class_hari_ini'>$content</td>";

                        if ($hari_minggu_ini == 7 && $hari_ke != $jumlah_hari_di_bulan) {
                            echo '</tr><tr>';
                        }
                        $hari_ke++;
                    }

                    $hari_terakhir_minggu = date('N', mktime(0, 0, 0, $bulan_pilihan, $jumlah_hari_di_bulan, $tahun_pilihan));
                    if ($hari_terakhir_minggu != 7) {
                        for ($i = $hari_terakhir_minggu; $i < 7; $i++) {
                            echo '<td class="bukan-bulan-ini"></td>';
                        }
                    }
                    ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 small text-muted">
    <i class="fas fa-info-circle me-1"></i> 
    <span class="badge status-hadir me-1">Hadir</span>
    <span class="badge status-izin me-1">Izin</span>
    <span class="badge status-sakit me-1">Sakit</span>
    <span class="badge status-alpha me-1">Alpha (Manual)</span>
    <span class="badge status-alpha-auto text-danger me-1">Tidak Hadir (Otomatis)</span>
    <span class="badge status-libur border">Libur Rutin</span>
</div>