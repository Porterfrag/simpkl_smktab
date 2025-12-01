<?php

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];

$id_absensi = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id_absensi) {
    echo "<div class='alert alert-danger'>Error: ID Absensi tidak ditemukan.</div>";
    exit;
}

$pesan_sukses = '';
$pesan_error = '';

try {
    $sql_cek = "SELECT s.id_siswa, s.nama_lengkap, a.tanggal 
                FROM absensi a 
                JOIN siswa s ON a.id_siswa = s.id_siswa
                WHERE a.id_absensi = :id AND s.id_pembimbing = :id_pembimbing";
    $stmt_cek = $pdo->prepare($sql_cek);
    $stmt_cek->execute([':id' => $id_absensi, ':id_pembimbing' => $id_pembimbing]);
    $data_cek = $stmt_cek->fetch(PDO::FETCH_ASSOC);

    if (!$data_cek) {
        die("<div class='alert alert-danger'>Akses Ditolak: Data tidak ditemukan atau siswa ini bukan bimbingan Anda.</div>");
    }
    $id_siswa_terkait = $data_cek['id_siswa']; 

} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status_baru = $_POST['status'];
    $jam_baru = $_POST['jam_absen'];
    $keterangan_baru = $_POST['keterangan'];

    try {
        $sql_update = "UPDATE absensi SET 
                        status = :status, 
                        jam_absen = :jam, 
                        keterangan = :ket,
                        dicatat_oleh = 'Pembimbing' 
                       WHERE id_absensi = :id";
        
        $stmt = $pdo->prepare($sql_update);
        $stmt->execute([
            ':status' => $status_baru,
            ':jam' => $jam_baru,
            ':ket' => $keterangan_baru,
            ':id' => $id_absensi
        ]);

        // --- SWEETALERT2 SUCCESS RESPONSE ---
        echo "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Proses Koreksi</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <style>body { font-family: sans-serif; background-color: #f8f9fa; }</style>
        </head>
        <body>
            <script>
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Koreksi absensi berhasil disimpan.',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0d6efd'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'index.php?page=pembimbing/rekap_absensi_siswa&id_siswa=$id_siswa_terkait';
                    }
                });
            </script>
        </body>
        </html>";
        exit; // Stop execution so the form below doesn't load again

    } catch (PDOException $e) {
        $pesan_error = "Gagal update: " . $e->getMessage();
    }
}

try {
    $sql = "SELECT * FROM absensi WHERE id_absensi = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_absensi]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<a href="index.php?page=pembimbing/rekap_absensi_siswa&id_siswa=<?php echo $id_siswa_terkait; ?>" class="btn btn-sm btn-secondary mb-3">&larr; Kembali</a>

<h2 class="mb-4">Koreksi Absensi: <?php echo htmlspecialchars($data_cek['nama_lengkap']); ?></h2>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i> Anda sedang mengoreksi absensi tanggal <strong><?php echo date('d F Y', strtotime($data['tanggal'])); ?></strong>.
        </div>

        <form action="" method="POST">
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
                    <label for="jam_absen" class="form-label fw-bold">Jam Masuk</label>
                    <input type="time" class="form-control" name="jam_absen" id="jam_absen" value="<?php echo htmlspecialchars($data['jam_absen']); ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="keterangan" class="form-label fw-bold">Keterangan / Alasan Koreksi</label>
                <textarea class="form-control" name="keterangan" id="keterangan" rows="3"><?php echo htmlspecialchars($data['keterangan']); ?></textarea>
            </div>

            <?php if (!empty($data['bukti_foto'])): ?>
                <div class="mb-4 p-3 bg-light rounded border text-center">
                    <label class="form-label text-muted small text-uppercase fw-bold d-block mb-2">Bukti Foto Tersimpan</label>
                    <img src="assets/uploads/<?php echo $data['bukti_foto']; ?>" alt="Bukti" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                </div>
            <?php endif; ?>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>