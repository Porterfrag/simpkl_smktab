<?php
// (Pastikan file ini hanya di-include oleh index.php)
if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') {
    die("Akses tidak sah!");
}
$id_siswa = $_SESSION['id_ref'];

$pesan_sukses = '';
$pesan_error = '';

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $tanggal = $_POST['tanggal'];
    $kegiatan = $_POST['kegiatan'];
    $nama_file_db = null;

    if (empty($tanggal) || empty($kegiatan)) {
        $pesan_error = "Tanggal dan Uraian Kegiatan wajib diisi!";
    } else {
        
        // --- PROSES UPLOAD FOTO ---
        if (isset($_FILES['foto_kegiatan']) && $_FILES['foto_kegiatan']['error'] == UPLOAD_ERR_OK) {
            $file = $_FILES['foto_kegiatan'];
            $tmp_name = $file['tmp_name'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Kita izinkan file besar masuk dulu, nanti dikompres
            if (in_array($file_ext, $allowed_ext)) {
                $nama_file_unik = uniqid('jurnal_', true) . '.' . $file_ext;
                $target_dir = "assets/uploads/";
                $target_path = $target_dir . $nama_file_unik;

                // Gunakan fungsi kompresi kita
                if (compressAndUpload($tmp_name, $target_path, 60)) {
                    $nama_file_db = $nama_file_unik; 
                } else {
                    $pesan_error = "Gagal memproses gambar.";
                }
            } else {
                $pesan_error = "Format file harus JPG atau PNG.";
            }
        }

        if (empty($pesan_error)) {
            try {
                $sql = "INSERT INTO jurnal_harian (id_siswa, tanggal, kegiatan, foto_kegiatan, status_validasi) 
                        VALUES (:id_siswa, :tanggal, :kegiatan, :foto, 'Pending')";
                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':id_siswa' => $id_siswa,
                    ':tanggal' => $tanggal,
                    ':kegiatan' => $kegiatan,
                    ':foto' => $nama_file_db
                ]);
                
                $_SESSION['pesan_sukses'] = "Jurnal berhasil disimpan!";
                header("Location: index.php?page=siswa/jurnal_lihat");
                exit;
                
            } catch (PDOException $e) {
                $pesan_error = "Gagal menyimpan: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
    /* Area Upload Kustom */
    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 15px;
        background-color: #f8f9fa;
        text-align: center;
        padding: 30px 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    .upload-area:hover, .upload-area.active {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    .upload-icon {
        font-size: 2.5rem;
        color: #adb5bd;
        margin-bottom: 10px;
    }
    /* Preview Gambar */
    #imagePreview {
        display: none;
        width: 100%;
        border-radius: 10px;
        margin-top: 0;
        object-fit: cover;
        max-height: 300px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    /* Sembunyikan input file asli */
    #foto_kegiatan { display: none; }
    
    /* Loading Overlay */
    #loadingOverlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255, 255, 255, 0.9);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.3s;
    }
    #loadingOverlay.show {
        visibility: visible;
        opacity: 1;
    }
</style>

<div id="loadingOverlay">
    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
    <h5 class="fw-bold text-dark">Menyimpan Jurnal...</h5>
    <p class="text-muted small">Sedang mengompres foto, mohon tunggu.</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Tulis Jurnal</h4>
        <p class="text-muted small mb-0">Ceritakan aktivitasmu hari ini</p>
    </div>
    <a href="index.php?page=siswa/jurnal_lihat" class="btn btn-light btn-sm rounded-circle shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-times"></i>
    </a>
</div>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger rounded-3 shadow-sm border-0 mb-4 animate__animated animate__shakeX">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<form action="index.php?page=siswa/jurnal_isi" method="POST" enctype="multipart/form-data" id="formJurnal">
    
    <div class="card shadow-sm border-0 rounded-4 mb-3">
        <div class="card-body p-4">
            
            <div class="mb-4">
                <label class="form-label fw-bold text-secondary small text-uppercase ls-1">Tanggal</label>
                <input type="date" class="form-control form-control-lg bg-light border-0" id="tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold text-secondary small text-uppercase ls-1">Aktivitas</label>
                <textarea class="form-control bg-light border-0" id="kegiatan" name="kegiatan" rows="5" placeholder="Apa yang kamu kerjakan hari ini?" required style="resize: none;"></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label fw-bold text-secondary small text-uppercase ls-1">Dokumentasi</label>
                
                <label for="foto_kegiatan" class="upload-area w-100" id="dropZone">
                    <div id="uploadContent">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <h6 class="fw-bold text-dark mb-1">Upload Foto</h6>
                    </div>
                    <img id="imagePreview" alt="Preview">
                </label>
                
                <input type="file" name="foto_kegiatan" id="foto_kegiatan" accept="image/*">
                
                <button type="button" id="btnRemovePhoto" class="btn btn-outline-danger btn-sm w-100 mt-2 rounded-pill" style="display: none;">
                    <i class="fas fa-trash me-1"></i> Hapus Foto
                </button>
            </div>

        </div>
    </div>

    <div class="d-grid mb-5">
        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow py-3">
            <i class="fas fa-paper-plane me-2"></i> Kirim Laporan
        </button>
    </div>

</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const fileInput = document.getElementById('foto_kegiatan');
    const preview = document.getElementById('imagePreview');
    const uploadContent = document.getElementById('uploadContent');
    const dropZone = document.getElementById('dropZone');
    const btnRemove = document.getElementById('btnRemovePhoto');
    const form = document.getElementById('formJurnal');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // 1. Logic Preview Gambar
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadContent.style.display = 'none'; // Sembunyikan teks "Upload Foto"
                dropZone.style.padding = '0'; // Hilangkan padding agar gambar full
                dropZone.style.borderStyle = 'solid';
                btnRemove.style.display = 'block'; // Munculkan tombol hapus
            }
            reader.readAsDataURL(file);
        }
    });

    // 2. Logic Hapus Foto
    btnRemove.addEventListener('click', function() {
        fileInput.value = ''; // Reset input
        preview.style.display = 'none';
        preview.src = '';
        uploadContent.style.display = 'block'; // Munculkan lagi teks
        dropZone.style.padding = '30px 20px';
        dropZone.style.borderStyle = 'dashed';
        this.style.display = 'none';
    });

    // 3. Logic Loading Animation saat Submit
    form.addEventListener('submit', function(e) {
        // Validasi sederhana
        if (!document.getElementById('tanggal').value || !document.getElementById('kegiatan').value) {
            e.preventDefault();
            alert("Mohon lengkapi data tanggal dan kegiatan.");
            return;
        }

        // Tampilkan Loading Overlay
        loadingOverlay.classList.add('show');
        
        // Disable tombol submit agar tidak double post
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
    });
});
</script>