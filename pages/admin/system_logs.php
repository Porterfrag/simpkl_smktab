<?php
// (Pastikan file ini hanya di-include oleh index.php)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$log_file = 'logs/error_log.txt';

// --- FITUR: BERSIHKAN LOG ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'clear') {
    file_put_contents($log_file, ""); // Kosongkan file
    $_SESSION['swal_success'] = "Log error berhasil dibersihkan.";
    echo "<script>window.location.href='index.php?page=admin/system_logs';</script>";
    exit;
}

// --- BACA FILE LOG ---
$logs = [];
if (file_exists($log_file)) {
    // Baca file ke dalam array
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // Balik urutan agar error terbaru ada di atas
    $logs = array_reverse($lines);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-bug me-2 text-danger"></i>System Error Logs</h2>
    <a href="index.php?page=admin/system_logs&aksi=clear" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Yakin ingin menghapus semua riwayat error?')">
        <i class="fas fa-trash me-1"></i> Bersihkan Log
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                <p class="mb-0">Sistem bersih. Tidak ada error yang tercatat.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 datatable">
                    <thead class="table-dark">
                        <tr>
                            <th>Waktu & Pesan Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $line): ?>
                            <tr>
                                <td class="font-monospace small text-danger"><?php echo htmlspecialchars($line); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info mt-3 shadow-sm border-0">
    <i class="fas fa-info-circle me-2"></i> 
    Log ini mencatat error PHP (Syntax, Fatal) dan Database yang terjadi di latar belakang.
</div>