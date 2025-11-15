<?php

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') {
    die("Akses tidak sah!");
}
$id_siswa = $_SESSION['id_ref'];

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $tanggal = $_POST['tanggal'];
    $kegiatan = $_POST['kegiatan']; 
    $nama_file_db = null;

    if (empty($tanggal) || empty($kegiatan)) {
        $pesan_error = "Tanggal dan Uraian Kegiatan wajib diisi!";
    } else {
        
        if (isset($_FILES['foto_kegiatan']) && $_FILES['foto_kegiatan']['error'] == UPLOAD_ERR_OK) {
            
            $file = $_FILES['foto_kegiatan'];
            $nama_file = $file['name'];
            $tmp_name = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext_arr = explode('.', $nama_file);
            $file_ext = strtolower(end($file_ext_arr));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2 * 1024 * 1024; 

            if (in_array($file_ext, $allowed_ext)) {
                if ($file_size <= $max_size) {
                    $nama_file_unik = uniqid('foto_', true) . '.' . $file_ext;
                    $target_dir = "assets/uploads/";
                    $target_path = $target_dir . $nama_file_unik;

                    if (compressAndUpload($tmp_name, $target_path, 60)) {
                        $nama_file_db = $nama_file_unik; 
                    } else {
                        $pesan_error = "Gagal memproses/mengompres gambar.";
                    }
                } else {
                    $pesan_error = "Gagal: Ukuran file foto tidak boleh lebih dari 2 MB.";
                }
            } else {
                $pesan_error = "Gagal: Format file foto harus JPG, JPEG, atau PNG.";
            }
        }

        if (empty($pesan_error)) {
            try {
                $sql = "INSERT INTO jurnal_harian (id_siswa, tanggal, kegiatan, foto_kegiatan, status_validasi) 
                        VALUES (:id_siswa, :tanggal, :kegiatan, :foto, 'Pending')";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':id_siswa', $id_siswa);
                $stmt->bindParam(':tanggal', $tanggal);
                $stmt->bindParam(':kegiatan', $kegiatan);
                $stmt->bindParam(':foto', $nama_file_db); 

                $stmt->execute();
                
                $_SESSION['pesan_sukses'] = "Jurnal harian berhasil disimpan dan menunggu validasi.";
                header("Location: index.php?page=siswa/jurnal_lihat");
                exit;
                
            } catch (PDOException $e) {
                $pesan_error = "Gagal menyimpan data jurnal: " . $e->getMessage();
            }
        }
    }
}
?>

<h2 class="mb-4">Isi Jurnal Harian</h2>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<form action="index.php?page=siswa/jurnal_isi" method="POST" enctype="multipart/form-data">
    
    <div class="mb-3">
        <label for="tanggal" class="form-label">Tanggal Kegiatan</label>
        <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="kegiatan" class="form-label">Uraian Kegiatan</label>
        <textarea class="form-control" id="kegiatan" name="kegiatan" rows="8" placeholder="Jelaskan kegiatan yang Anda lakukan hari ini..." required></textarea>
        <small class="form-text text-muted">Hanya isi jurnal jika Anda **hadir** pada hari yang bersangkutan.</small>
    </div>
    
    <div class="mb-3">
        <label for="foto_kegiatan" class="form-label">Foto Kegiatan (Opsional)</label>
        <input type="file" class="form-control" id="foto_kegiatan" name="foto_kegiatan" accept="image/jpeg, image/png">
        <small class="form-text text-muted">Ukuran maks 2MB. Format: JPG, PNG.</small>
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan Jurnal</button>
    <a href="index.php?page=siswa/jurnal_lihat" class="btn btn-secondary">Batal</a>
</form>
