<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}
$id_admin_user = $_SESSION['user_id']; 

$pesan_sukses = '';
$pesan_error = '';
$file_uploaded = false;
$total_sukses = 0;
$total_gagal = 0;
$gagal_details = []; 

if (isset($_POST['submit_import']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        
        $file_tmp_path = $_FILES['csv_file']['tmp_name'];
        $mime_type = mime_content_type($file_tmp_path);
        
        if ($mime_type == 'text/csv' || $mime_type == 'text/plain' || strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)) == 'csv') {
            
            $file_uploaded = true; 
            
            $handle = fopen($file_tmp_path, "r");
            if (!$handle) {
                $pesan_error = "Gagal membaca file CSV yang diunggah.";
            } else {
                
                fgetcsv($handle);
                
                $row_number = 1;
                
                $pdo->beginTransaction();
                
                try {
                    $sql_check = "SELECT COUNT(*) FROM siswa WHERE nis = ?";
                    $stmt_check = $pdo->prepare($sql_check);
                    
                    $sql_siswa = "INSERT INTO siswa (nis, nama_lengkap, jurusan, kelas) VALUES (?, ?, ?, ?)";
                    $stmt_siswa = $pdo->prepare($sql_siswa);

                    $sql_user = "INSERT INTO users (username, password, role, id_ref) VALUES (?, ?, 'siswa', ?)";
                    $stmt_user = $pdo->prepare($sql_user);
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $row_number++; 
                        
                        if (count($data) != 4) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number: Data tidak lengkap (Ditemukan " . count($data) . " kolom, seharusnya 4).";
                            continue;
                        }

                        $nis = trim($data[0]);
                        $nama_lengkap = trim($data[1]);
                        $jurusan = trim($data[2]);
                        $kelas = trim($data[3]);
                        
                        if (empty($nis) || empty($nama_lengkap)) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number: NIS atau Nama Lengkap kosong.";
                            continue;
                        }
                        
                        $stmt_check->execute([$nis]);
                        if ($stmt_check->fetchColumn() > 0) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number ($nis): NIS sudah terdaftar di database.";
                            continue;
                        }
                        
                        
                        $stmt_siswa->execute([$nis, $nama_lengkap, $jurusan, $kelas]);
                        $id_siswa_baru = $pdo->lastInsertId();
                        
                       $hashed_password = password_hash($nis, PASSWORD_DEFAULT);
                        $stmt_user->execute([$nis, $hashed_password, $id_siswa_baru]);
                        
                        $total_sukses++;
                    }
                    
                    $pdo->commit();
                    
                    $pesan_sukses = "Proses import selesai. Total sukses: $total_sukses, Total gagal: $total_gagal.";

                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $pesan_error = "FATAL ERROR DATABASE pada baris $row_number. Transaksi dibatalkan. Pesan: " . $e->getMessage();
                }
                
                fclose($handle);
            }
            
        } else {
            $pesan_error = "Jenis file tidak didukung. Harap unggah file CSV ('.csv').";
        }
    } else {
        $pesan_error = "Gagal mengunggah file. Silakan coba lagi.";
    }
}
?>

<h2 class="mb-4">Import Data Siswa (Massal)</h2>
<p class="mb-3">
    Silakan unggah file CSV yang berisi data siswa baru. Struktur file CSV **wajib** berurutan seperti ini:
    <br><code>NIS, NAMA_LENGKAP, JURUSAN, KELAS</code>
</p>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success" role="alert"><?php echo $pesan_sukses; ?></div>
    
    <?php if ($total_gagal > 0): ?>
        <div class="alert alert-warning" role="alert">
            <h5 class="alert-heading">Rincian Kegagalan (Total <?php echo $total_gagal; ?>):</h5>
            <ul>
                <?php foreach ($gagal_details as $detail): ?>
                    <li><?php echo htmlspecialchars($detail); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Siswa yang sudah berhasil diimport dipertahankan (karena proses berjalan di dalam transaksi). Silakan perbaiki file CSV Anda dan coba import kembali baris yang gagal.</p>
        </div>
    <?php endif; ?>

<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<div class="card p-4">
    <h5 class="card-title">Upload File CSV</h5>
    <form action="index.php?page=admin/siswa_import" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="csv_file" class="form-label">Pilih File CSV</label>
            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
            <small class="form-text text-muted">Maksimal 1000 baris data per import.</small>
        </div>
        
        <button type="submit" name="submit_import" class="btn btn-primary">Mulai Import</button>
    </form>
</div>