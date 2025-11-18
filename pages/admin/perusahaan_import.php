<?php
// (Pastikan file ini hanya di-include oleh index.php)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$pesan_sukses = '';
$pesan_error = '';
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
                
                // Lewati baris header
                fgetcsv($handle);
                $row_number = 1;
                
                // Mulai Transaksi
                $pdo->beginTransaction();
                
                try {
                    // Query Insert
                    $sql = "INSERT INTO perusahaan (nama_perusahaan, alamat, kontak_person, no_telp, hari_kerja) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $row_number++;
                        
                        // 1. Validasi Jumlah Kolom (Minimal 2: Nama, Alamat)
                        if (count($data) < 2) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number: Data tidak lengkap (Nama/Alamat kurang).";
                            continue;
                        }

                        // Ambil Data
                        $nama = trim($data[0]);
                        $alamat = trim($data[1]);
                        $kontak = isset($data[2]) ? trim($data[2]) : '';
                        $telp = isset($data[3]) ? trim($data[3]) : '';
                        
                        // Hari kerja (Jika kosong di CSV, default 1,2,3,4,5 / Senin-Jumat)
                        $hari_kerja = (isset($data[4]) && !empty(trim($data[4]))) ? trim($data[4]) : '1,2,3,4,5';
                        
                        // 2. Validasi Wajib
                        if (empty($nama) || empty($alamat)) {
                            $total_gagal++;
                            $gagal_details[] = "Baris $row_number: Nama Perusahaan atau Alamat kosong.";
                            continue;
                        }
                        
                        // Eksekusi
                        $stmt->execute([$nama, $alamat, $kontak, $telp, $hari_kerja]);
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

<h2 class="mb-4">Import Data Perusahaan (Massal)</h2>

<div class="alert alert-info shadow-sm border-0">
    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Panduan Format CSV</h5>
    <p class="mb-2">Siapkan file Excel/CSV dengan urutan kolom sebagai berikut:</p>
    <code class="d-block p-2 bg-white rounded border mb-2">NAMA_PT, ALAMAT, KONTAK_PERSON, TELEPON, HARI_KERJA(Opsional)</code>
    <ul class="small mb-0 text-muted">
        <li><strong>Hari Kerja:</strong> Isi dengan angka 1-7 dipisah koma (1=Senin, 7=Minggu).</li>
        <li>Jika Hari Kerja dikosongkan, otomatis diisi <strong>1,2,3,4,5</strong> (Senin-Jumat).</li>
        <li>Contoh: <strong>PT. Maju Jaya, Jl. Merdeka No 1, Pak Budi, 081234, 1,2,3,4,5,6</strong></li>
    </ul>
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
        <form action="index.php?page=admin/perusahaan_import" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="csv_file" class="form-label fw-bold">Pilih File CSV</label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" name="submit_import" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i> Mulai Import
                </button>
                <a href="index.php?page=admin/perusahaan_data" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>