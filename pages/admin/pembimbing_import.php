<?php
// (Pastikan file ini hanya di-include oleh index.php)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$pesan_sukses = '';
$pesan_error = '';
$file_uploaded = false;
$total_sukses = 0;
$total_gagal = 0;
$gagal_details = []; 

// --- PROSES IMPORT ---
if (isset($_POST['submit_import']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        
        $file_tmp_path = $_FILES['csv_file']['tmp_name'];
        $mime_type = mime_content_type($file_tmp_path);
        $file_ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        
        // Validasi tipe file (CSV)
        if ($mime_type == 'text/csv' || $mime_type == 'text/plain' || $file_ext == 'csv') {
            
            $handle = fopen($file_tmp_path, "r");
            if (!$handle) {
                $pesan_error = "Gagal membaca file CSV.";
            } else {
                
                // Lewati baris header (baris 1)
                fgetcsv($handle);
                $row_number = 1;
                
                // Mulai Transaksi
                $pdo->beginTransaction();
                
                try {
                    // Query Cek Duplikat (NIP/ID Guru)
                    $sql_check = "SELECT COUNT(*) FROM pembimbing WHERE nip = ?";
                    $stmt_check = $pdo->prepare($sql_check);
                    
                    // Query Insert Pembimbing
                    $sql_pembimbing = "INSERT INTO pembimbing (nip, nama_guru, no_telp) VALUES (?, ?, ?)";
                    $stmt_pembimbing = $pdo->prepare($sql_pembimbing);

                    // Query Insert User
                    $sql_user = "INSERT INTO users (username, password, role, id_ref) VALUES (?, ?, 'pembimbing', ?)";
                    $stmt_user = $pdo->prepare($sql_user);
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $row_number++;
                        
                        // 1. Validasi Jumlah Kolom (Minimal 2: ID, Nama. Kolom 3 Telp Opsional)
                        if (count($data) < 2) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number: Data tidak lengkap.";
                            continue;
                        }

                        // Sanitize
                        $nip = trim($data[0]); // Ini ID Guru Custom Anda
                        $nama_guru = trim($data[1]);
                        $no_telp = isset($data[2]) ? trim($data[2]) : ''; // Telp opsional
                        
                        // 2. Validasi Kosong
                        if (empty($nip) || empty($nama_guru)) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number: ID Guru atau Nama kosong.";
                            continue;
                        }
                        
                        // 3. Cek Duplikat
                        $stmt_check->execute([$nip]);
                        if ($stmt_check->fetchColumn() > 0) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number ($nip): ID Guru sudah ada.";
                            continue;
                        }
                        
                        // --- PROSES INSERT ---
                        
                        // A. Insert ke tabel pembimbing
                        $stmt_pembimbing->execute([$nip, $nama_guru, $no_telp]);
                        $id_pembimbing_baru = $pdo->lastInsertId();
                        
                        // B. Insert ke tabel users (Username = ID, Pass = ID)
                        // Gunakan password_hash (Bcrypt) sesuai upgrade kita
                        $hashed_password = password_hash($nip, PASSWORD_DEFAULT);
                        
                        $stmt_user->execute([$nip, $hashed_password, $id_pembimbing_baru]);
                        
                        $total_sukses++;
                    }
                    
                    $pdo->commit();
                    $pesan_sukses = "Import selesai. Sukses: <b>$total_sukses</b>, Gagal: <b>$total_gagal</b>.";

                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $pesan_error = "Terjadi kesalahan database pada baris $row_number. Transaksi dibatalkan. Error: " . $e->getMessage();
                }
                
                fclose($handle);
            }
            
        } else {
            $pesan_error = "Format file salah. Harap unggah file .csv";
        }
    } else {
        $pesan_error = "Gagal mengunggah file.";
    }
}
?>

<h2 class="mb-4">Import Guru Pembimbing (Massal)</h2>

<div class="alert alert-info shadow-sm border-0">
    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Panduan Format CSV</h5>
    <p class="mb-0">Siapkan file Excel/CSV dengan urutan kolom sebagai berikut (tanpa judul kolom di baris 1 juga boleh, tapi sistem akan melewati baris pertama):</p>
    <code class="d-block mt-2 p-2 bg-white rounded border">ID_GURU, NAMA_LENGKAP, NO_TELEPON</code>
    <p class="mt-2 small mb-0">Contoh: <strong>GURU001, Budi Santoso, 08123456789</strong></p>
</div>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success role='alert'"><?php echo $pesan_sukses; ?></div>
    
    <?php if ($total_gagal > 0): ?>
        <div class="alert alert-warning role='alert'">
            <strong>Rincian Gagal:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($gagal_details as $detail): ?>
                    <li><?php echo htmlspecialchars($detail); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger role='alert'"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="index.php?page=admin/pembimbing_import" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="csv_file" class="form-label fw-bold">Pilih File CSV</label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="submit_import" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i> Mulai Import
                </button>
                <a href="index.php?page=admin/pembimbing_data" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>