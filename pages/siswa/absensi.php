<?php
if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') { die("Akses tidak sah!"); }
$id_siswa = $_SESSION['id_ref'];

$pesan_sukses = ''; $pesan_error = '';
$hari_ini = date('Y-m-d');
$jam_sekarang = date('H:i:s');
$sudah_absen = false;

$pkl_start = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : '2020-01-01';
$pkl_end   = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : '2030-12-31';

if ($hari_ini < $pkl_start) {
    echo "<div class='alert alert-warning shadow-sm rounded-3'><i class='fas fa-clock me-2'></i>Masa PKL belum dimulai.</div>"; return; 
}
if ($hari_ini > $pkl_end) {
    echo "<div class='alert alert-info shadow-sm rounded-3'><i class='fas fa-check-circle me-2'></i>Masa PKL telah berakhir.</div>"; return; 
}

try {
    $stmt = $pdo->prepare("SELECT * FROM absensi WHERE id_siswa = ? AND tanggal = ?");
    $stmt->execute([$id_siswa, $hari_ini]);
    $data_absen = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data_absen) { $sudah_absen = true; $pesan_sukses = "Absensi hari ini terekam: " . $data_absen['status']; }
} catch (PDOException $e) { $pesan_error = "Error DB"; }

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$sudah_absen) {
    $status = $_POST['status_kehadiran'];
    $ket = $_POST['keterangan'];
    $img = $_POST['image_data']; 
    $lat = $_POST['latitude'];     
    $lon = $_POST['longitude'];   

    if (empty($status)) { $pesan_error = "Pilih status kehadiran."; } 
    elseif ($status == 'Hadir' && empty($img)) { $pesan_error = "Foto wajib diambil."; } 
    elseif (($status == 'Izin' || $status == 'Sakit') && empty($ket)) { $pesan_error = "Isi keterangan."; } 
    else {
        $file_db = null;
        if ($status == 'Hadir' && !empty($img)) {
            $data = base64_decode(substr($img, strpos($img, ',') + 1));
            $file_db = 'absen_' . $id_siswa . '_' . time() . '.jpeg';
            file_put_contents('assets/uploads/' . $file_db, $data);
        }

        if (empty($pesan_error)) {
            try {
                $sql = "INSERT INTO absensi (id_siswa, tanggal, status, dicatat_oleh, jam_absen, keterangan, bukti_foto, latitude, longitude) VALUES (?, ?, ?, 'Siswa', ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$id_siswa, $hari_ini, $status, $jam_sekarang, $ket, $file_db, $lat, $lon]);
                $pesan_sukses = "Absensi Berhasil!"; $sudah_absen = true;
            } catch (PDOException $e) { $pesan_error = "Gagal simpan."; }
        }
    }
}
?>

<style>
    .status-btn-group .btn-check:checked + .btn {
        border-width: 2px;
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .btn-check:checked + .btn-outline-success { border-color: #198754; color: #198754; background-color: #e8f5e9; }
    .btn-check:checked + .btn-outline-primary { border-color: #0d6efd; color: #0d6efd; background-color: #e7f1ff; }
    .btn-check:checked + .btn-outline-warning { border-color: #ffc107; color: #856404; background-color: #fff3cd; }

    .btn-outline-success, .btn-outline-primary, .btn-outline-warning {
        border: 1px solid #dee2e6;
        color: #6c757d;
        background-color: white;
    }

    .camera-frame {
        border-radius: 12px;
        overflow: hidden;
        background: #000;
        position: relative;
        aspect-ratio: 4/3; 
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }
    
    video#webcam { 
        width: 100%; height: 100%; object-fit: cover; 
        transform: scaleX(-1); 
    }
    
    img#photo { 
        width: 100%; height: 100%; object-fit: cover; 
    }
    
    .gps-badge {
        font-size: 0.75rem;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 50px;
        padding: 4px 12px;
        display: inline-block;
        margin-bottom: 10px;
    }

    /* Animasi sederhana saat muncul */
    #keteranganSection { transition: all 0.3s ease; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0 text-secondary">Absensi Harian</h5>
    <span class="badge bg-white text-dark border shadow-sm">
        <i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y'); ?>
    </span>
</div>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success rounded-3 shadow-sm border-0 d-flex align-items-center">
        <i class="fas fa-check-circle fa-2x me-3"></i>
        <div><strong>Berhasil!</strong><br><?php echo $pesan_sukses; ?></div>
    </div>
<?php endif; ?>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger rounded-3 shadow-sm border-0">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<?php if (!$sudah_absen): ?>
<form action="index.php?page=siswa/absensi" method="POST" id="attendanceForm">
    <input type="hidden" name="image_data" id="image_data">
    <input type="hidden" name="latitude" id="latitude">
    <input type="hidden" name="longitude" id="longitude">

    <div class="card shadow-sm border-0 mb-3 rounded-3">
        <div class="card-body p-3">
            <p class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; margin-bottom: 10px;">Status Kehadiran</p>
            
            <div class="row g-2 status-btn-group">
                <div class="col-4">
                    <input type="radio" class="btn-check" name="status_kehadiran" id="hadir" value="Hadir" checked>
                    <label class="btn btn-outline-success w-100 py-2 rounded-3 h-100 d-flex flex-column align-items-center justify-content-center" for="hadir">
                        <i class="fas fa-map-marker-alt fa-lg mb-1 mt-2"></i>
                        <span class="small fw-bold mt-1">Hadir</span>
                    </label>
                </div>
                <div class="col-4">
                    <input type="radio" class="btn-check" name="status_kehadiran" id="izin" value="Izin">
                    <label class="btn btn-outline-primary w-100 py-2 rounded-3 h-100 d-flex flex-column align-items-center justify-content-center" for="izin">
                        <i class="fas fa-envelope-open-text fa-lg mb-1 mt-2"></i>
                        <span class="small fw-bold mt-1">Izin</span>
                    </label>
                </div>
                <div class="col-4">
                    <input type="radio" class="btn-check" name="status_kehadiran" id="sakit" value="Sakit">
                    <label class="btn btn-outline-warning w-100 py-2 rounded-3 h-100 d-flex flex-column align-items-center justify-content-center" for="sakit">
                        <i class="fas fa-procedures fa-lg mb-1 mt-2"></i>
                        <span class="small fw-bold mt-1">Sakit</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3 rounded-3" id="cameraSection">
        <div class="card-body text-center p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem;">Foto Selfie</span>
                <div id="gpsStatus" class="gps-badge text-muted">
                    <i class="fas fa-spinner fa-spin"></i> GPS...
                </div>
            </div>

            <div class="camera-frame mb-3">
                <video id="webcam" autoplay playsinline></video>
                <canvas id="canvas" style="display:none;"></canvas>
                <img id="photo" src="" style="display:none;">
            </div>

            <div class="d-grid gap-2">
                <button type="button" id="startCam" class="btn btn-light border text-dark fw-bold rounded-pill btn-sm py-2">
                    <i class="fas fa-camera me-1"></i> Aktifkan Kamera
                </button>
                <button type="button" id="captureBtn" class="btn btn-primary fw-bold rounded-pill btn-sm py-2" style="display:none;">
                    <i class="fas fa-circle me-1"></i> Ambil Foto
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 rounded-3" id="keteranganSection" style="display:none;">
        <div class="card-body p-3">
            <p class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; margin-bottom: 5px;">Keterangan Tambahan</p>
            <textarea name="keterangan" id="keterangan" rows="2" class="form-control bg-light border-0" placeholder="Contoh: Izin acara keluarga / Sakit demam..."></textarea>
        </div>
    </div>

    <div class="d-grid mb-5">
        <button type="submit" id="submitBtn" class="btn btn-success btn-lg fw-bold rounded-pill shadow-sm">
            Kirim Absensi <i class="fas fa-paper-plane ms-2"></i>
        </button>
    </div>
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const photo = document.getElementById('photo');
    const captureBtn = document.getElementById('captureBtn');
    const startCamBtn = document.getElementById('startCam');
    const submitBtn = document.getElementById('submitBtn');
    const imgInput = document.getElementById('image_data');
    const latInput = document.getElementById('latitude'); 
    const lonInput = document.getElementById('longitude'); 
    const camSection = document.getElementById('cameraSection');
    const ketSection = document.getElementById('keteranganSection');
    const gpsStat = document.getElementById('gpsStatus');
    const radios = document.querySelectorAll('input[name="status_kehadiran"]');
    
    let stream = null;
    let locReady = false; 

    function updateUI() {
        let status = document.querySelector('input[name="status_kehadiran"]:checked').value;
        
        if (status === 'Hadir') {
            // Tampilkan Kamera, Sembunyikan Keterangan
            camSection.style.display = 'block';
            ketSection.style.display = 'none';
            
            // Logika tombol kirim: Jika status hadir, wajib foto dulu
            if (!imgInput.value) {
                submitBtn.disabled = true;
            }
            
            // Auto start camera if not already active
            if (!stream) {
                // Memberi sedikit delay agar UI render selesai
                setTimeout(() => { startCamBtn.click(); }, 100);
            }
        } else {
            // Sembunyikan Kamera, Tampilkan Keterangan
            camSection.style.display = 'none';
            ketSection.style.display = 'block';
            submitBtn.disabled = false; 

            // Matikan kamera untuk menghemat daya/privasi
            if (stream) {
                stream.getTracks().forEach(t => t.stop());
                stream = null;
                video.srcObject = null;
                startCamBtn.style.display = 'block';
                captureBtn.style.display = 'none';
            }
        }
    }

    radios.forEach(r => r.addEventListener('change', updateUI));

    // --- GPS LOGIC ---
    function getLoc() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    latInput.value = pos.coords.latitude.toFixed(6);
                    lonInput.value = pos.coords.longitude.toFixed(6);
                    locReady = true;
                    gpsStat.innerHTML = `<i class="fas fa-check text-success"></i> Lokasi OK`;
                    gpsStat.className = "gps-badge text-success border-success bg-white";
                    if (stream) captureBtn.disabled = false;
                },
                (err) => { 
                    locReady = false; 
                    gpsStat.innerHTML = `<i class="fas fa-times text-danger"></i> GPS Error`;
                },
                { enableHighAccuracy: false, timeout: 15000, maximumAge: 0 }
            );
        }
    }
    getLoc();

    // --- CAMERA LOGIC ---
    startCamBtn.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } } 
            });
            video.srcObject = stream;
            photo.style.display = 'none';
            video.style.display = 'block';
            startCamBtn.style.display = 'none';
            captureBtn.style.display = 'block';
            captureBtn.disabled = !locReady; 
        } catch (e) { 
            alert("Gagal mengakses kamera. Pastikan izin kamera diberikan."); 
        }
    });

    captureBtn.addEventListener('click', () => {
        if (!stream) return;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0); 
        
        const url = canvas.toDataURL('image/jpeg', 0.8);
        photo.src = url;
        
        video.style.display = 'none';
        photo.style.display = 'block';
        imgInput.value = url;
        
        captureBtn.style.display = 'none';
        startCamBtn.innerHTML = "<i class='fas fa-redo me-1'></i> Foto Ulang";
        startCamBtn.style.display = 'block';
        
        // Matikan stream setelah foto diambil
        stream.getTracks().forEach(t => t.stop());
        stream = null;
        
        submitBtn.disabled = false;
    });

    // Jalankan pertama kali saat load
    updateUI();
});
</script>