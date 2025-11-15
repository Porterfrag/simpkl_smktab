<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$pesan_sukses = '';
$pesan_error = '';

$current_grading = isset($SETTINGS['grading_start_date']) ? $SETTINGS['grading_start_date'] : date('Y-m-d');
$current_start   = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : date('Y-m-01');
$current_end     = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : date('Y-m-t');


if (isset($_POST['simpan_pengaturan']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    $inputs = [
        'grading_start_date' => $_POST['grading_start_date'],
        'pkl_start_date'     => $_POST['pkl_start_date'],
        'pkl_end_date'       => $_POST['pkl_end_date']
    ];

    try {
        $pdo->beginTransaction();
        
        $sql = "UPDATE pengaturan SET value_setting = :val WHERE key_setting = :key";
        $stmt = $pdo->prepare($sql);

        foreach ($inputs as $key => $val) {
            $stmt->execute([':val' => $val, ':key' => $key]);
        }
        
        $pdo->commit();
        $pesan_sukses = "Semua pengaturan sistem berhasil diperbarui.";
        
        echo "<meta http-equiv='refresh' content='1;url=index.php?page=admin/pengaturan_edit'>";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $pesan_error = "Gagal menyimpan pengaturan: " . $e->getMessage();
    }
}
?>

<h2 class="mb-4">Pengaturan Sistem PKL</h2>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success" role="alert"><?php echo $pesan_sukses; ?></div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<form action="index.php?page=admin/pengaturan_edit" method="POST">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Periode Kegiatan PKL</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai PKL</label>
                        <input type="date" class="form-control" name="pkl_start_date" value="<?php echo htmlspecialchars($current_start); ?>" required>
                        <small class="text-muted">Siswa tidak bisa absen sebelum tanggal ini.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai PKL</label>
                        <input type="date" class="form-control" name="pkl_end_date" value="<?php echo htmlspecialchars($current_end); ?>" required>
                        <small class="text-muted">Siswa tidak bisa absen setelah tanggal ini.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Periode Penilaian</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Buka Input Nilai Mulai Tanggal</label>
                        <input type="date" class="form-control" name="grading_start_date" value="<?php echo htmlspecialchars($current_grading); ?>" required>
                        <small class="text-muted">Guru Pembimbing baru bisa memberi nilai akhir setelah tanggal ini.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <button type="submit" name="simpan_pengaturan" class="btn btn-lg btn-success w-100">
        <i class="fas fa-save me-2"></i> Simpan Semua Pengaturan
    </button>
</form>