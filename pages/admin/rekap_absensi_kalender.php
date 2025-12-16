<?php
// --- 1. PHP LOGIC & SECURITY ---
if ($_SESSION['role'] != 'admin') { die("Akses dilarang!"); }

// Koneksi Database ($pdo) diasumsikan sudah ada
// include 'config.php'; 

// --- [FITUR BARU] LOGIKA SIMPAN KOREKSI (ADMIN) ---
if (isset($_POST['btn_simpan_koreksi'])) {
    $k_id_siswa = $_POST['k_id_siswa'];
    $k_tanggal  = $_POST['k_tanggal'];
    $k_status   = $_POST['k_status'];
    $k_catatan  = $_POST['k_catatan'];

    try {
        // Cek apakah data sudah ada
        $cek = $pdo->prepare("SELECT id_absensi FROM absensi WHERE id_siswa = ? AND tanggal = ?");
        $cek->execute([$k_id_siswa, $k_tanggal]);
        
        if ($cek->rowCount() > 0) {
            // UPDATE
            $sql_update = "UPDATE absensi SET status = :stat, keterangan = :ket WHERE id_siswa = :id AND tanggal = :tgl";
            $stmt = $pdo->prepare($sql_update);
            $stmt->execute([':stat'=>$k_status, ':ket'=>$k_catatan, ':id'=>$k_id_siswa, ':tgl'=>$k_tanggal]);
        } else {
            // INSERT (Admin mengisi data kosong)
            $sql_insert = "INSERT INTO absensi (id_siswa, tanggal, jam_absen, status, keterangan, latitude, longitude) 
                           VALUES (:id, :tgl, NOW(), :stat, :ket, NULL, NULL)";
            $stmt = $pdo->prepare($sql_insert);
            $stmt->execute([':stat'=>$k_status, ':ket'=>$k_catatan, ':id'=>$k_id_siswa, ':tgl'=>$k_tanggal]);
        }

        echo "<script>alert('Berhasil! Data absensi siswa telah diperbarui.'); window.location.href='index.php?page=admin/rekap_absensi_kalender&id_siswa=$k_id_siswa';</script>";
        exit;

    } catch (PDOException $e) {
        $err_msg = json_encode("Gagal update database: " . $e->getMessage());
        echo "<script>alert($err_msg); window.history.back();</script>";
        exit;
    }
}
// --- END LOGIKA KOREKSI ---

// Parameter Filter
$id_siswa = isset($_GET['id_siswa']) ? $_GET['id_siswa'] : '';
$bulan_pilihan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Variabel Default
$nama_siswa = 'Pilih Siswa Terlebih Dahulu';
$summary = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
$data_absensi_bulan_ini = [];
$hari_kerja_perusahaan = [1, 2, 3, 4, 5]; // Default Senin-Jumat
$siswa_list = [];

try {
    // A. Ambil Daftar Semua Siswa (Untuk Select2 Admin)
    $stmt_siswa = $pdo->query("SELECT id_siswa, nama_lengkap, kelas FROM siswa ORDER BY nama_lengkap ASC");
    $siswa_list = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

    // B. Jika Siswa Dipilih, Ambil Data Detailnya
    if ($id_siswa) {
        $sql_info = "SELECT s.nama_lengkap, p.hari_kerja 
                     FROM siswa s
                     LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                     WHERE s.id_siswa = :id_siswa";
        $stmt_info = $pdo->prepare($sql_info);
        $stmt_info->execute([':id_siswa' => $id_siswa]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            $nama_siswa = $info['nama_lengkap'];
            if (!empty($info['hari_kerja'])) {
                $hari_kerja_perusahaan = explode(',', $info['hari_kerja']);
            }
        }

        $sql_absen = "SELECT DAY(tanggal) as hari, tanggal, status, bukti_foto, latitude, longitude, jam_absen, keterangan 
                      FROM absensi 
                      WHERE id_siswa = :id_siswa 
                        AND MONTH(tanggal) = :bulan 
                        AND YEAR(tanggal) = :tahun";
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
        }
        
        // Hitung Summary (Loop array agar sinkron)
        foreach($data_absensi_bulan_ini as $d){
            if(isset($summary[$d['status']])) $summary[$d['status']]++;
        }
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

// Konfigurasi Kalender
$nama_bulan_ini = date('F Y', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$jumlah_hari_di_bulan = date('t', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_pertama_minggu = date('N', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_ini = date('d');
$is_current_month = ($bulan_pilihan == date('m') && $tahun_pilihan == date('Y'));
$tanggal_hari_ini = date('Y-m-d');

$pkl_start = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : '2020-01-01';
$pkl_end   = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : '2030-12-31';
?>

<style>
    .calendar-container { max-width: 100%; margin: 0 auto; }
    .summary-card { background: #fff; border-radius: 12px; padding: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 10px; border: 1px solid #eee; }
    .summary-count { font-size: 1.2rem; font-weight: 800; display: block; }
    .summary-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .calendar-header { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: 600; font-size: 0.8rem; color: #6c757d; padding-bottom: 10px; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
    .day-cell { aspect-ratio: 1 / 1; background-color: #fff; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; font-size: 0.9rem; font-weight: 500; color: #333; border: 1px solid #f0f0f0; transition: transform 0.1s; cursor: pointer; }
    .day-cell.empty { background: transparent; border: none; cursor: default; }
    .day-cell.status-hadir { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
    .day-cell.status-izin { background-color: #cfe2ff; color: #084298; border-color: #b6d4fe; }
    .day-cell.status-sakit { background-color: #fff3cd; color: #664d03; border-color: #ffecb5; }
    .day-cell.status-alpha { background-color: #f8d7da; color: #842029; border-color: #f5c6cb; }
    .day-cell.status-libur { background-color: #f8f9fa; color: #adb5bd; cursor: default; }
    .day-cell.status-missing { background-color: #fff; border: 2px dashed #dc3545; color: #dc3545; }
    .day-cell.today { box-shadow: 0 0 0 2px #0d6efd; z-index: 2; font-weight: bold; }
    .cell-status-text { font-size: 0.6rem; margin-top: 2px; font-weight: normal; display: none; }
    .day-cell:not(.empty):not(.status-libur):hover { transform: scale(1.05); z-index: 5; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    @media (min-width: 400px) { .cell-status-text { display: block; } }
    .filter-area { background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
</style>

<div class="mb-4">
    <h4 class="fw-bold text-dark mb-3"><i class="fas fa-calendar-alt me-2"></i> Rekap Absensi Kalender</h4>
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">
            <label for="siswaSelector" class="form-label small text-muted text-uppercase fw-bold">Pilih Siswa</label>
            <select id="siswaSelector" class="form-select select2" style="width: 100%;">
                <option value="">-- Cari Siswa --</option>
                <?php foreach ($siswa_list as $s): ?>
                    <option value="<?php echo $s['id_siswa']; ?>" <?php echo ($s['id_siswa'] == $id_siswa) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['nama_lengkap']) . ' (' . htmlspecialchars($s['kelas']) . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<?php if (!$id_siswa): ?>
    <div class="alert alert-info text-center py-4">
        <i class="fas fa-info-circle fa-2x mb-3"></i><br>
        Silakan pilih siswa pada kolom pencarian di atas untuk melihat kalender absensi.
    </div>
<?php else: ?>

    <div class="row g-2 mb-3">
        <div class="col-3"><div class="summary-card"><span class="summary-count text-success"><?php echo $summary['Hadir']; ?></span><span class="summary-label">Hadir</span></div></div>
        <div class="col-3"><div class="summary-card"><span class="summary-count text-primary"><?php echo $summary['Izin']; ?></span><span class="summary-label">Izin</span></div></div>
        <div class="col-3"><div class="summary-card"><span class="summary-count text-warning"><?php echo $summary['Sakit']; ?></span><span class="summary-label">Sakit</span></div></div>
        <div class="col-3"><div class="summary-card"><span class="summary-count text-danger"><?php echo $summary['Alpha']; ?></span><span class="summary-label">Alpha</span></div></div>
    </div>

    <div class="filter-area">
        <form action="index.php" method="GET" class="row g-2">
            <input type="hidden" name="page" value="admin/rekap_absensi_kalender">
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
                <h6 class="text-center mb-3 fw-bold text-uppercase text-primary"><?php echo $nama_bulan_ini; ?></h6>
                <div class="calendar-header">
                    <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div>
                </div>
                <div class="calendar-grid">
                    <?php
                    for ($i = 1; $i < $hari_pertama_minggu; $i++) echo '<div class="day-cell empty"></div>';

                    for ($hari_ke = 1; $hari_ke <= $jumlah_hari_di_bulan; $hari_ke++) {
                        $hari_minggu_ini = date('N', mktime(0, 0, 0, $bulan_pilihan, $hari_ke, $tahun_pilihan));
                        $tanggal_loop = "$tahun_pilihan-" . str_pad($bulan_pilihan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($hari_ke, 2, '0', STR_PAD_LEFT);
                        
                        $cell_classes = ['day-cell'];
                        $status_label = '';
                        $onclick_event = '';

                        if ($is_current_month && $hari_ke == $hari_ini) $cell_classes[] = 'today';

                        if (isset($data_absensi_bulan_ini[$hari_ke])) {
                            // DATA ADA DI DATABASE
                            $data = $data_absensi_bulan_ini[$hari_ke];
                            $status = $data['status'];
                            $cell_classes[] = 'status-' . strtolower($status);
                            $status_label = $status;
                            $json_data = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
                            $onclick_event = "onclick='showDetail($json_data)'";
                        } else {
                            // DATA KOSONG
                            if (in_array($hari_minggu_ini, $hari_kerja_perusahaan)) {
                                $is_within_period = ($tanggal_loop >= $pkl_start && $tanggal_loop <= $pkl_end);
                                if ($tanggal_loop < $tanggal_hari_ini && $is_within_period) {
                                    // Status Missing / Belum Absen
                                    $cell_classes[] = 'status-missing';
                                    $status_label = '!'; 
                                    // Buat dummy data agar bisa diklik untuk koreksi
                                    $dummy_data = [
                                        'status' => 'Belum Absen', 'tgl' => $tanggal_loop,
                                        'jam' => '-', 'ket' => 'Data belum masuk.',
                                        'foto' => '', 'lat' => '', 'long' => ''
                                    ];
                                    $json_data = htmlspecialchars(json_encode($dummy_data), ENT_QUOTES, 'UTF-8');
                                    $onclick_event = "onclick='showDetail($json_data)'";
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
<?php endif; ?>

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
                    <img id="modalFoto" src="" class="img-fluid rounded shadow-sm" style="max-height: 250px; display: none;" alt="Bukti">
                    <p id="noFotoText" class="text-muted small fst-italic mb-0" style="display: none;">Tidak ada bukti foto.</p>
                </div>

                <div id="mapContainer" style="display:none;" class="mb-3">
                    <h6 class="fw-bold text-start small mb-2"><i class="fas fa-map-marker-alt me-1"></i> Lokasi:</h6>
                    <div class="ratio ratio-16x9 border rounded overflow-hidden shadow-sm">
                        <iframe id="mapFrame" src="" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
                <p id="noMapText" class="text-muted small fst-italic mb-3" style="display: none;">
                    <i class="fas fa-map-slash me-1"></i> Lokasi tidak tersedia.
                </p>

                <div class="d-grid gap-2">
                    <button type="button" id="btnKoreksi" class="btn btn-warning rounded-pill btn-sm text-dark fw-bold" onclick="openKoreksiModal()">
                        <i class="fas fa-edit me-2"></i> Koreksi / Input Absensi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKoreksi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h6 class="modal-title fw-bold text-dark"><i class="fas fa-pen-square me-2"></i>Koreksi Data (Admin)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="k_id_siswa" id="k_id_siswa" value="<?php echo $id_siswa; ?>">
                    <input type="hidden" name="k_tanggal" id="k_tanggal">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal</label>
                        <input type="text" id="k_tgl_display" class="form-control form-control-sm bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status Kehadiran</label>
                        <select name="k_status" id="k_status" class="form-select form-select-sm" required>
                            <option value="Hadir">Hadir</option>
                            <option value="Izin">Izin</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Alpha">Alpha</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Catatan Admin</label>
                        <textarea name="k_catatan" id="k_catatan" class="form-control form-control-sm" rows="2" placeholder="Alasan perubahan data..."></textarea>
                    </div>
                </div>
                <div class="modal-footer p-1">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="backToDetail()">Batal</button>
                    <button type="submit" name="btn_simpan_koreksi" class="btn btn-sm btn-dark">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (jQuery().select2) {
        $('.select2').select2({ theme: 'bootstrap-5', placeholder: "Cari Siswa...", allowClear: true });
    }
    $('#siswaSelector').on('change', function() {
        var id_siswa = $(this).val();
        if (id_siswa) window.location.href = 'index.php?page=admin/rekap_absensi_kalender&id_siswa=' + id_siswa;
        else window.location.href = 'index.php?page=admin/rekap_absensi_kalender';
    });
});

let currentAbsenData = null;

function showDetail(data) {
    if (!data) return;
    currentAbsenData = data;

    const modalEl = document.getElementById('modalDetailAbsen');
    const modal = new bootstrap.Modal(modalEl);
    
    // Set UI Teks
    const dateObj = new Date(data.tgl);
    document.getElementById('modalDateTitle').textContent = dateObj.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('modalJam').textContent = data.jam ? data.jam : '-';
    document.getElementById('modalKet').textContent = data.ket ? '"' + data.ket + '"' : '';

    // Set Badge
    const badge = document.getElementById('modalStatusBadge');
    badge.textContent = data.status;
    badge.className = 'badge'; 
    if(data.status === 'Hadir') badge.classList.add('bg-success');
    else if(data.status === 'Izin') badge.classList.add('bg-primary');
    else if(data.status === 'Sakit') badge.classList.add('bg-warning', 'text-dark');
    else if(data.status === 'Belum Absen') badge.classList.add('bg-danger', 'border', 'border-white');
    else badge.classList.add('bg-danger');

    // Set Foto
    const img = document.getElementById('modalFoto');
    const noFoto = document.getElementById('noFotoText');
    if (data.foto) {
        img.src = 'assets/uploads/' + data.foto;
        img.style.display = 'block'; noFoto.style.display = 'none';
    } else {
        img.style.display = 'none'; noFoto.style.display = 'block';
    }

    // [BARU] LOGIKA MAPS EMBED
    const mapContainer = document.getElementById('mapContainer');
    const mapFrame = document.getElementById('mapFrame');
    const noMapText = document.getElementById('noMapText');

    if (data.lat && data.long) {
        mapContainer.style.display = 'block';
        noMapText.style.display = 'none';
        // URL Map Embed Standard
        const mapUrl = `http://googleusercontent.com/maps.google.com/maps?q=${data.lat},${data.long}&z=15&output=embed`;
        mapFrame.src = mapUrl;
    } else {
        mapContainer.style.display = 'none';
        mapFrame.src = "";
        noMapText.style.display = 'block';
    }

    modal.show();
}

// Reset iframe saat tutup modal
document.getElementById('modalDetailAbsen').addEventListener('hidden.bs.modal', function () {
    document.getElementById('mapFrame').src = "";
});

// Buka Modal Koreksi
function openKoreksiModal() {
    if(!currentAbsenData) return;
    const modalDetail = bootstrap.Modal.getInstance(document.getElementById('modalDetailAbsen'));
    modalDetail.hide();

    document.getElementById('k_tanggal').value = currentAbsenData.tgl;
    document.getElementById('k_tgl_display').value = currentAbsenData.tgl;
    
    let statusToSet = currentAbsenData.status;
    if(statusToSet === 'Belum Absen') statusToSet = 'Alpha';
    document.getElementById('k_status').value = statusToSet;
    document.getElementById('k_catatan').value = currentAbsenData.ket || '';

    new bootstrap.Modal(document.getElementById('modalKoreksi')).show();
}

function backToDetail() {
    const modalKoreksi = bootstrap.Modal.getInstance(document.getElementById('modalKoreksi'));
    modalKoreksi.hide();
    showDetail(currentAbsenData);
}
</script>