<?php
// --- 1. PHP LOGIC & SECURITY ---
// session_start(); // Uncomment jika perlu
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
    $hari_kerja_perusahaan = !empty($info['hari_kerja']) ? explode(',', $info['hari_kerja']) : [1, 2, 3, 4, 5];

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }

$data_absensi_bulan_ini = [];
$summary = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];

try {
    $sql_absen = "SELECT DAY(tanggal) as hari, tanggal, status, bukti_foto, latitude, longitude, jam_absen, keterangan 
                  FROM absensi 
                  WHERE id_siswa = :id_siswa AND MONTH(tanggal) = :bulan AND YEAR(tanggal) = :tahun";
    $stmt_absen = $pdo->prepare($sql_absen);
    $stmt_absen->execute([':id_siswa' => $id_siswa, ':bulan' => $bulan_pilihan, ':tahun' => $tahun_pilihan]);
    
    while ($row = $stmt_absen->fetch(PDO::FETCH_ASSOC)) {
        $data_absensi_bulan_ini[$row['hari']] = [
            'status' => $row['status'],
            'foto'   => $row['bukti_foto'],
            'lat'    => $row['latitude'],
            'long'   => $row['longitude'],
            'jam'    => $row['jam_absen'],
            'ket'    => $row['keterangan'],
            'tgl'    => $row['tanggal']
        ];
        if(isset($summary[$row['status']])) $summary[$row['status']]++;
    }
} catch (PDOException $e) { echo "Error: " . $e->getMessage(); }

$jumlah_hari_di_bulan = date('t', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_pertama_minggu = date('N', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_ini = date('d');
$is_current_month = ($bulan_pilihan == date('m') && $tahun_pilihan == date('Y'));
$pkl_start = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : '2020-01-01';
$pkl_end   = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : '2030-12-31';
?>

<style>
    .calendar-container { max-width: 100%; margin: 0 auto; }
    .summary-card { background: #fff; border-radius: 12px; padding: 10px 5px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; height: 100%; }
    .summary-count { font-size: 1.2rem; font-weight: 800; display: block; line-height: 1.2; }
    .summary-label { font-size: 0.7rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .calendar-header { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: 600; font-size: 0.8rem; color: #6c757d; padding-bottom: 8px; margin-top: 15px; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
    .day-cell { aspect-ratio: 1 / 1; background-color: #fff; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; font-size: 0.95rem; font-weight: 500; color: #444; border: 1px solid #eaeaea; transition: all 0.2s; cursor: default; }
    .day-cell.empty { background: transparent; border: none; }
    .day-cell.status-hadir { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; cursor: pointer; }
    .day-cell.status-izin { background-color: #cfe2ff; color: #084298; border-color: #b6d4fe; cursor: pointer; }
    .day-cell.status-sakit { background-color: #fff3cd; color: #664d03; border-color: #ffecb5; cursor: pointer; }
    .day-cell.status-alpha { background-color: #f8d7da; color: #842029; border-color: #f5c6cb; cursor: pointer; }
    .day-cell.status-libur { background-color: #f8f9fa; color: #adb5bd; }
    .day-cell.status-missing { background-color: #fff; border: 2px dashed #dc3545; color: #dc3545; }
    .day-cell.today { box-shadow: 0 0 0 2px #0d6efd; z-index: 2; font-weight: 800; }
    .cell-status-text { font-size: 0.6rem; margin-top: 1px; font-weight: normal; display: block; }
    .filter-card { background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e9ecef; }
    .day-cell:not(.empty):not(.status-libur):not(.status-missing):hover { transform: scale(1.05); z-index: 5; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0 fw-bold text-dark">Absensi Saya</h4>
</div>

<div class="row g-2 mb-4">
    <div class="col-3"><div class="summary-card"><span class="summary-count text-success"><?php echo $summary['Hadir']; ?></span><span class="summary-label">Hadir</span></div></div>
    <div class="col-3"><div class="summary-card"><span class="summary-count text-primary"><?php echo $summary['Izin']; ?></span><span class="summary-label">Izin</span></div></div>
    <div class="col-3"><div class="summary-card"><span class="summary-count text-warning"><?php echo $summary['Sakit']; ?></span><span class="summary-label">Sakit</span></div></div>
    <div class="col-3"><div class="summary-card"><span class="summary-count text-danger"><?php echo $summary['Alpha']; ?></span><span class="summary-label">Alpha</span></div></div>
</div>

<div class="filter-card">
    <form action="index.php" method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="siswa/absensi_kalender">
        <div class="col-5">
            <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for ($i = 1; $i <= 12; $i++) { $sel = ($i == $bulan_pilihan) ? 'selected' : ''; echo "<option value='$i' $sel>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>"; } ?>
            </select>
        </div>
        <div class="col-4">
            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for ($i = date('Y')-1; $i <= date('Y')+1; $i++) { $sel = ($i == $tahun_pilihan) ? 'selected' : ''; echo "<option value='$i' $sel>$i</option>"; } ?>
            </select>
        </div>
        <div class="col-3 d-grid"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync"></i></button></div>
    </form>
</div>

<div class="calendar-container">
    <h6 class="text-center mb-0 fw-bold text-uppercase text-primary"><?php echo $nama_bulan_ini; ?></h6>
    <div class="calendar-header"><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div></div>
    <div class="calendar-grid">
        <?php
        for ($i = 1; $i < $hari_pertama_minggu; $i++) echo '<div class="day-cell empty"></div>';

        for ($hari_ke = 1; $hari_ke <= $jumlah_hari_di_bulan; $hari_ke++) {
            $hari_minggu_ini = date('N', mktime(0, 0, 0, $bulan_pilihan, $hari_ke, $tahun_pilihan));
            $tanggal_loop = "$tahun_pilihan-" . str_pad($bulan_pilihan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($hari_ke, 2, '0', STR_PAD_LEFT);
            $tanggal_hari_ini = date('Y-m-d');
            
            $classes = ['day-cell'];
            $status_text = ''; 
            $onclick_event = '';

            if ($is_current_month && $hari_ke == $hari_ini) $classes[] = 'today';

            if (isset($data_absensi_bulan_ini[$hari_ke])) {
                $data = $data_absensi_bulan_ini[$hari_ke];
                $status = $data['status'];
                $classes[] = 'status-' . strtolower($status);
                $status_text = $status;
                $json_data = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
                $onclick_event = "onclick='showDetail($json_data)'";
            } else {
                if (in_array($hari_minggu_ini, $hari_kerja_perusahaan)) {
                    $is_within_period = ($tanggal_loop >= $pkl_start && $tanggal_loop <= $pkl_end);
                    if ($tanggal_loop < $tanggal_hari_ini && $is_within_period) {
                        $classes[] = 'status-missing'; $status_text = '!';
                    } elseif (!$is_within_period) { $status_text = '-'; }
                } else { $classes[] = 'status-libur'; }
            }

            echo '<div class="' . implode(' ', $classes) . '" ' . $onclick_event . '>';
            echo '<span>' . $hari_ke . '</span>';
            if ($status_text) echo '<span class="cell-status-text">' . substr($status_text, 0, 5) . '</span>';
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
                    <img id="modalFoto" src="" class="img-fluid rounded shadow-sm" style="max-height: 200px; display: none;">
                    <p id="noFotoText" class="text-muted small fst-italic mb-0" style="display: none;">Tidak ada bukti foto.</p>
                </div>

                <div id="mapContainer" style="display:none;" class="mt-3">
                    <h6 class="fw-bold text-start small mb-2"><i class="fas fa-map-marker-alt me-1"></i> Lokasi:</h6>
                    <div class="ratio ratio-16x9 border rounded overflow-hidden">
                        <iframe id="mapFrame" src="" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
                
                <p id="noMapText" class="text-muted small fst-italic mt-3" style="display: none;">
                    <i class="fas fa-map-slash me-1"></i> Lokasi tidak tersedia.
                </p>

            </div>
        </div>
    </div>
</div>

<script>
function showDetail(data) {
    if (!data) return;

    const modalEl = document.getElementById('modalDetailAbsen');
    const modal = new bootstrap.Modal(modalEl);
    
    // 1. Set Info Teks
    const dateObj = new Date(data.tgl);
    document.getElementById('modalDateTitle').textContent = dateObj.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('modalJam').textContent = data.jam ? data.jam : '-';
    document.getElementById('modalKet').textContent = data.ket ? '"' + data.ket + '"' : '';

    // 2. Set Status Badge
    const badge = document.getElementById('modalStatusBadge');
    badge.textContent = data.status;
    badge.className = 'badge'; 
    if(data.status === 'Hadir') badge.classList.add('bg-success');
    else if(data.status === 'Izin') badge.classList.add('bg-primary');
    else if(data.status === 'Sakit') badge.classList.add('bg-warning', 'text-dark');
    else badge.classList.add('bg-danger');

    // 3. Set Foto
    const img = document.getElementById('modalFoto');
    const noFoto = document.getElementById('noFotoText');
    if (data.foto) {
        img.src = 'assets/uploads/' + data.foto;
        img.style.display = 'block'; noFoto.style.display = 'none';
    } else {
        img.style.display = 'none'; noFoto.style.display = 'block';
    }

    // 4. [UPDATE] LOGIKA MAPS EMBED
    const mapContainer = document.getElementById('mapContainer');
    const mapFrame = document.getElementById('mapFrame');
    const noMapText = document.getElementById('noMapText');

    if (data.lat && data.long) {
        // Tampilkan Container Map
        mapContainer.style.display = 'block';
        noMapText.style.display = 'none';
        
        // Buat URL Embed (Tanpa API Key, menggunakan mode Embed dasar)
        // Format: https://maps.google.com/maps?q=[LAT],[LONG]&z=15&output=embed
        const mapUrl = `https://maps.google.com/maps?q=${data.lat},${data.long}&z=15&output=embed`;
        
        mapFrame.src = mapUrl;
    } else {
        // Sembunyikan Map jika tidak ada koordinat
        mapContainer.style.display = 'none';
        mapFrame.src = ""; // Reset src biar video/map stop loading
        noMapText.style.display = 'block';
    }

    modal.show();
}

// Reset iframe saat modal ditutup agar tidak membebani memori
document.getElementById('modalDetailAbsen').addEventListener('hidden.bs.modal', function () {
    document.getElementById('mapFrame').src = "";
});
</script>