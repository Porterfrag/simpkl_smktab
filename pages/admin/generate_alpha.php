<?php
// --- DEBUG MODE (Hapus jika sudah fix) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 1. SECURITY & CONFIG ---
// Pastikan session sudah start dari index.php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    die("Akses dilarang! Silakan login sebagai Admin."); 
}

// Default Tanggal: Kemarin (H-1)
$default_tgl = date('Y-m-d', strtotime("-1 days"));
$tanggal_pilih = isset($_POST['tgl_proses']) ? $_POST['tgl_proses'] : $default_tgl;

// Pesan Hasil
$pesan = "";

// --- 2. LOGIKA PROSES ---
if (isset($_POST['btn_proses'])) {
    try {
        // A. Ambil semua siswa aktif
        // Pastikan kolom 'status' ada di tabel siswa. Jika tidak ada, hapus "WHERE status = 'aktif'"
        $sql_siswa = "SELECT id_siswa, nama_lengkap FROM siswa"; 
        $stmt_s = $pdo->query($sql_siswa);
        $siswa_all = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

        $jumlah_berhasil = 0;
        $jumlah_skip = 0;

        $pdo->beginTransaction(); // Mulai Transaksi

        foreach ($siswa_all as $s) {
            $id = $s['id_siswa'];

            // B. Cek apakah siswa ini SUDAH ada absen di tanggal tsb?
            $cek = $pdo->prepare("SELECT id_absensi FROM absensi WHERE id_siswa = ? AND tanggal = ?");
            $cek->execute([$id, $tanggal_pilih]);

            if ($cek->rowCount() == 0) {
                // C. JIKA BELUM ADA -> INSERT ALPHA
                // [FIX]: Menghapus kolom 'lokasi_masuk' agar sesuai tabel kamu
                $sql_insert = "INSERT INTO absensi (id_siswa, tanggal, jam_absen, status, keterangan, latitude, longitude) 
                               VALUES (:id, :tgl, '23:59:00', 'Alpha', 'Otomatis by System', NULL, NULL)";
                
                $ins = $pdo->prepare($sql_insert);
                $ins->execute([
                    ':id' => $id,
                    ':tgl' => $tanggal_pilih
                ]);
                $jumlah_berhasil++;
            } else {
                $jumlah_skip++;
            }
        }

        $pdo->commit(); // Simpan perubahan
        
        $pesan = "
        <div class='alert alert-success alert-dismissible fade show'>
            <i class='fas fa-check-circle me-2'></i>Proses Selesai!<br>
            <strong>$jumlah_berhasil</strong> siswa ditandai Alpha.<br>
            <strong>$jumlah_skip</strong> siswa sudah absen sebelumnya.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";

    } catch (PDOException $e) {
        $pdo->rollBack(); // Batalkan jika error
        $pesan = "<div class='alert alert-danger'>Error Database: " . $e->getMessage() . "</div>";
    } catch (Exception $e) {
        $pesan = "<div class='alert alert-danger'>Error System: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="container-fluid px-4">
    <h3 class="mt-4 fw-bold text-dark"><i class="fas fa-magic me-2"></i>Generate Alpha Otomatis</h3>
    <p class="text-muted">Fitur ini digunakan untuk mengisi status <strong>Alpha</strong> secara massal bagi siswa yang tidak melakukan absensi pada tanggal yang dipilih.</p>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0"><i class="fas fa-cog me-2"></i>Konfigurasi Proses</h6>
                </div>
                <div class="card-body">
                    <?php echo $pesan; ?>

                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memproses Alpha untuk tanggal ini? Data yang sudah ada tidak akan ditimpa.');">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Pilih Tanggal</label>
                            <input type="date" name="tgl_proses" class="form-control form-control-lg" value="<?php echo $tanggal_pilih; ?>" required>
                            <div class="form-text text-danger">
                                * Hati-hati memilih tanggal. Jangan pilih Hari Minggu atau Hari Libur Nasional.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="btn_proses" class="btn btn-danger btn-lg">
                                <i class="fas fa-robot me-2"></i> Proses Alpha Massal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body bg-light">
                    <h6 class="fw-bold text-dark mb-3">Cara Kerja:</h6>
                    <ul class="small text-muted mb-0">
                        <li class="mb-2">Sistem akan mengecek seluruh data <strong>Siswa</strong>.</li>
                        <li class="mb-2">Sistem mengecek apakah siswa tersebut sudah absen pada tanggal yang dipilih.</li>
                        <li class="mb-2">Jika data absensi <strong>KOSONG</strong>, sistem akan otomatis membuat data baru dengan status <strong>Alpha</strong>.</li>
                        <li class="mb-2">Jika data absensi <strong>SUDAH ADA</strong> (Hadir/Izin/Sakit), data tersebut <strong>TIDAK</strong> akan diubah.</li>
                        <li>Disarankan dijalankan setiap pagi untuk memproses data hari sebelumnya (H-1).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>