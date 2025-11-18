<?php
// (Pastikan file ini hanya di-include oleh index.php)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$id_absensi = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id_absensi) {
    echo "<div class='alert alert-danger'>ID Absensi tidak ditemukan.</div>";
    exit;
}

$pesan_sukses = '';
$pesan_error = '';

// --- PROSES UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status_baru = $_POST['status'];
    $jam_baru = $_POST['jam_absen'];
    $keterangan_baru = $_POST['keterangan'];

    try {
        // Update data dan ubah 'dicatat_oleh' menjadi 'Admin' sebagai jejak audit
        $sql_update = "UPDATE absensi SET 
                        status = :status, 
                        jam_absen = :jam, 
                        keterangan = :ket,
                        dicatat_oleh = 'Admin' 
                       WHERE id_absensi = :id";
        
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            ':status' => $status_baru,
            ':jam' => $jam_baru,
            ':ket' => $keterangan_baru,
            ':id' => $id_absensi
        ]);

        $pesan_sukses = "Data absensi berhasil diperbarui!";
        
        // Refresh agar data terbaru muncul
        echo "<meta http-equiv='refresh' content='1;url=index.php?page=admin/absensi_data'>";

    } catch (PDOException $e) {
        $pesan_error = "Gagal update: " . $e->getMessage();
    }
}

// --- AMBIL DATA LAMA ---
try {
    $sql = "SELECT a.*, s.nama_lengkap, s.nis, s.kelas 
            FROM absensi a 
            JOIN siswa s ON a.id_siswa = s.id_siswa 
            WHERE a.id_absensi = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_absensi]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("Data tidak ditemukan.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Data Absensi</h2>
    <a href="index.php?page=admin/absensi_data" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
</div>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success"><?php echo $pesan_sukses; ?></div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="index.php?page=admin/absensi_edit&id=<?php echo $id_absensi; ?>" method="POST">
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label text-muted small text-uppercase fw-bold">Nama Siswa</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['nama_lengkap']); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small text-uppercase fw-bold">Tanggal Absen</label>
                    <input type="text" class="form-control" value="<?php echo date('d F Y', strtotime($data['tanggal'])); ?>" disabled>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="status" class="form-label fw-bold">Status Kehadiran</label>
                    <select name="status" id="status" class="form-select">
                        <?php 
                        $statuses = ['Hadir', 'Izin', 'Sakit', 'Alpha', 'Libur'];
                        foreach ($statuses as $s) {
                            $selected = ($data['status'] == $s) ? 'selected' : '';
                            echo "<option value='$s' $selected>$s</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="jam_absen" class="form-label fw-bold">Jam Absen</label>
                    <input type="time" class="form-control" name="jam_absen" id="jam_absen" value="<?php echo htmlspecialchars($data['jam_absen']); ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="keterangan" class="form-label fw-bold">Keterangan</label>
                <textarea class="form-control" name="keterangan" id="keterangan" rows="3"><?php echo htmlspecialchars($data['keterangan']); ?></textarea>
            </div>

            <?php if (!empty($data['bukti_foto'])): ?>
                <div class="mb-4">
                    <label class="form-label text-muted small text-uppercase fw-bold">Bukti Foto Tersimpan</label>
                    <div class="d-block">
                        <a href="assets/uploads/<?php echo $data['bukti_foto']; ?>" target="_blank">
                            <img src="assets/uploads/<?php echo $data['bukti_foto']; ?>" alt="Bukti" class="img-thumbnail" style="max-height: 150px;">
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>