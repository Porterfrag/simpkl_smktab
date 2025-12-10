<?php
// --- 1. PHP LOGIC & SECURITY ---
if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];

if (!isset($_GET['id_siswa'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Siswa tidak ditemukan.</div>";
    exit;
}
$id_siswa = $_GET['id_siswa'];

$bulan_pilihan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$nama_bulan_ini = date('F Y', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));

$hari_kerja_perusahaan = []; 
$nama_siswa = "";

try {
    // Check if student belongs to this mentor
    $sql_info = "SELECT s.nama_lengkap, p.hari_kerja 
                 FROM siswa s
                 LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                 WHERE s.id_siswa = :id_siswa AND s.id_pembimbing = :id_pembimbing";
                 
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([':id_siswa' => $id_siswa, ':id_pembimbing' => $id_pembimbing]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) {
        die("Error: Data siswa tidak ditemukan atau bukan bimbingan Anda.");
    }
    
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
// Variables for Summary Dashboard
$summary = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];

try {
    // [UPDATE] Fetch complete attendance details
    $sql_absen = "SELECT DAY(tanggal) as hari, tanggal, status, bukti_foto, latitude, longitude, jam_absen, keterangan 
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
        // Store full details for modal
        $data_absensi_bulan_ini[$row['hari']] = [
            'status' => $row['status'],
            'foto'   => $row['bukti_foto'],
            'lat'    => $row['latitude'],
            'long'   => $row['longitude'],
            'jam'    => $row['jam_absen'],
            'ket'    => $row['keterangan'],
            'tgl'    => $row['tanggal']
        ];

        // Calculate Summary
        if(isset($summary[$row['status']])) {
            $summary[$row['status']]++;
        }
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data absensi: " . $e->getMessage() . "</div>";
}

$jumlah_hari_di_bulan = date('t', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_pertama_minggu = date('N', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_ini = date('d');
$is_current_month = ($bulan_pilihan == date('m') && $tahun_pilihan == date('Y'));

// Settings fallback
$pkl_start = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : '2020-01-01';
$pkl_end   = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : '2030-12-31';
?>

<style>
    /* Mobile First Grid Calendar */
    .calendar-container { max-width: 100%; margin: 0 auto; }
    
    /* Summary Cards */
    .summary-card { background: #fff; border-radius: 12px; padding: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 10px; border: 1px solid #eee; }
    .summary-count { font-size: 1.2rem; font-weight: 800; display: block; }
    .summary-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }

    /* The Grid */
    .calendar-header { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: 600; font-size: 0.8rem; color: #6c757d; padding-bottom: 10px; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }

    .day-cell {
        aspect-ratio: 1 / 1; background-color: #fff; border-radius: 8px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        position: relative; font-size: 0.9rem; font-weight: 500; color: #333;
        border: 1px solid #f0f0f0; transition: transform 0.1s;
    }

    .day-cell.empty { background: transparent; border: none; }

    /* Status Styling */
    .day-cell.status-hadir { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; cursor: pointer; }
    .day-cell.status-izin { background-color: #cfe2ff; color: #084298; border-color: #b6d4fe; cursor: pointer; }
    .day-cell.status-sakit { background-color: #fff3cd; color: #664d03; border-color: #ffecb5; cursor: pointer; }
    .day-cell.status-alpha { background-color: #f8d7da; color: #842029; border-color: #f5c6cb; cursor: pointer; }
    .day-cell.status-libur { background-color: #f8f9fa; color: #adb5bd; }
    .day-cell.status-missing { background-color: #fff; border: 2px dashed #dc3545; color: #dc3545; }

    .day-cell.today { box-shadow: 0 0 0 2px #0d6efd; z-index: 2; font-weight: bold; }

    .cell-status-text { font-size: 0.6rem; margin-top: 2px; font-weight: normal; display: none; }
    
    /* Hover Effect */
    .day-cell:not(.empty):not(.status-libur):not(.status-missing):hover {
        transform: scale(1.05); z-index: 5; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    @media (min-width: 400px) { .cell-status-text { display: block; } }
    .filter-area { background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="index.php?page=pembimbing/validasi_daftar_siswa" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <h5 class="m-0 fw-bold text-end"><?php echo htmlspecialchars($nama_siswa); ?></h5>
</div>

<div class="row g-2 mb-3">
    <div class="col-3"><div class="summary-card"><span class="summary-count text-success"><?php echo $summary['Hadir']; ?></span><span class="summary-label">Hadir</span></div></div>
    <div class="col-3"><div class="summary-card"><span class="summary-count text-primary"><?php echo $summary['Izin']; ?></span><span class="summary-label">Izin</span></div></div>
    <div class="col-3"><div class="summary-card"><span class="summary-count text-warning"><?php echo $summary['Sakit']; ?></span><span class="summary-label">Sakit</span></div></div>
    <div class="col-3"><div class="summary-card"><span class="summary-count text-danger"><?php echo $summary['Alpha']; ?></span><span class="summary-label">Alpha</span></div></div>
</div>

<div class="filter-area">
    <form action="index.php" method="GET" class="row g-2">
        <input type="hidden" name="page" value="pembimbing/rekap_kalender_siswa">
        <input type="hidden" name="id_siswa" value="<?php echo $id_siswa; ?>">
        
        <div class="col-6">
            <label class="small text-muted mb-1">Bulan</label>
            <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $bulan_pilihan) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6">
            <label class="small text-muted mb-1">Tahun</label>
            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for ($i = date('Y')-1; $i <= date('Y')+1; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $tahun_pilihan) ? 'selected' : ''; ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-3">
        <div class="calendar-container">
            <h6 class="text-center mb-3 fw-bold text-uppercase text-primary">
                <?php echo $nama_bulan_ini; ?>
            </h6>

            <div class="calendar-header">
                <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div>
            </div>

            <div class="calendar-grid">
                <?php
                // Empty cells
                for ($i = 1; $i < $hari_pertama_minggu; $i++) {
                    echo '<div class="day-cell empty"></div>';
                }

                // Date Loop
                for ($hari_ke = 1; $hari_ke <= $jumlah_hari_di_bulan; $hari_ke++) {
                    $hari_minggu_ini = date('N', mktime(0, 0, 0, $bulan_pilihan, $hari_ke, $tahun_pilihan));
                    $tanggal_loop = "$tahun_pilihan-" . str_pad($bulan_pilihan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($hari_ke, 2, '0', STR_PAD_LEFT);
                    $tanggal_hari_ini = date('Y-m-d');
                    
                    $cell_classes = ['day-cell'];
                    $status_label = '';
                    $onclick_event = '';

                    if ($is_current_month && $hari_ke == $hari_ini) $cell_classes[] = 'today';

                    if (isset($data_absensi_bulan_ini[$hari_ke])) {
                        // Data exists
                        $data = $data_absensi_bulan_ini[$hari_ke];
                        $status = $data['status'];
                        $cell_classes[] = 'status-' . strtolower($status);
                        $status_label = $status;

                        // [UPDATE] Encode data for JS click handler
                        $json_data = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
                        $onclick_event = "onclick='showDetail($json_data)'";

                    } else {
                        // Empty/Missing data logic
                        if (in_array($hari_minggu_ini, $hari_kerja_perusahaan)) {
                            $is_within_period = ($tanggal_loop >= $pkl_start && $tanggal_loop <= $pkl_end);
                            if ($tanggal_loop < $tanggal_hari_ini && $is_within_period) {
                                $cell_classes[] = 'status-missing';
                                $status_label = '!'; 
                            }
                        } else {
                            $cell_classes[] = 'status-libur';
                        }
                    }

                    echo '<div class="' . implode(' ', $cell_classes) . '" ' . $onclick_event . '>';
                    echo '<span>' . $hari_ke . '</span>';
                    if($status_label) echo '<span class="cell-status-text">' . substr($status_label, 0, 3) . '</span>';
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
                <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary">Libur</span>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailAbsen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalDateTitle">Detail Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <h4 class="mb-3"><span id="modalStatusBadge" class="badge bg-secondary">Status</span></h4>
                
                <p class="mb-1 text-muted small"><i class="far fa-clock me-1"></i> Waktu: <span id="modalJam" class="fw-bold text-dark">-</span></p>
                <p class="mb-3 text-secondary small fst-italic" id="modalKet"></p>

                <div class="mb-3 bg-light rounded p-2 border d-flex justify-content-center">
                    <img id="modalFoto" src="" class="img-fluid rounded shadow-sm" style="max-height: 300px; display: none;" alt="Bukti">
                    <p id="noFotoText" class="text-muted small fst-italic mb-0" style="display: none;">Tidak ada bukti foto.</p>
                </div>

                <div class="d-grid">
                    <a id="btnMaps" href="#" target="_blank" class="btn btn-outline-primary rounded-pill btn-sm">
                        <i class="fas fa-map-marker-alt me-2"></i> Lihat Lokasi (Gmaps)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to show modal detail
function showDetail(data) {
    if (!data) return;

    const modalEl = document.getElementById('modalDetailAbsen');
    const modal = new bootstrap.Modal(modalEl);
    
    // Set Date Title
    const dateObj = new Date(data.tgl);
    document.getElementById('modalDateTitle').textContent = dateObj.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Set Status Badge
    const badge = document.getElementById('modalStatusBadge');
    badge.textContent = data.status;
    badge.className = 'badge'; 
    if(data.status === 'Hadir') badge.classList.add('bg-success');
    else if(data.status === 'Izin') badge.classList.add('bg-primary');
    else if(data.status === 'Sakit') badge.classList.add('bg-warning', 'text-dark');
    else badge.classList.add('bg-danger');

    // Set Info
    document.getElementById('modalJam').textContent = data.jam ? data.jam : '-';
    document.getElementById('modalKet').textContent = data.ket ? '"' + data.ket + '"' : '';

    // Set Photo
    const img = document.getElementById('modalFoto');
    const noFoto = document.getElementById('noFotoText');
    if (data.foto) {
        img.src = 'assets/uploads/' + data.foto;
        img.style.display = 'block';
        noFoto.style.display = 'none';
    } else {
        img.style.display = 'none';
        noFoto.style.display = 'block';
    }

    // Set Map Button
    const btnMaps = document.getElementById('btnMaps');
    if (data.lat && data.long) {
        btnMaps.href = `http://googleusercontent.com/maps.google.com/?q=${data.lat},${data.long}`;
        btnMaps.classList.remove('disabled', 'btn-secondary');
        btnMaps.classList.add('btn-outline-primary');
        btnMaps.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i> Lihat Lokasi (Gmaps)';
    } else {
        btnMaps.href = '#';
        btnMaps.classList.remove('btn-outline-primary');
        btnMaps.classList.add('disabled', 'btn-secondary');
        btnMaps.innerHTML = '<i class="fas fa-map-slash me-2"></i> Lokasi Tidak Tersedia';
    }

    modal.show();
}
</script>