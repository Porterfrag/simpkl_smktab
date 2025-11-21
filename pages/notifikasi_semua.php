<?php
// (Pastikan file ini hanya di-include oleh index.php)
if (!isset($_SESSION['user_id'])) {
    die("Akses tidak sah!");
}
$uid = $_SESSION['user_id'];

// --- FITUR: TANDAI SEMUA SUDAH DIBACA ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'tandai_semua_baca') {
    $stmt = $pdo->prepare("UPDATE notifikasi SET status = 'read' WHERE id_user = ?");
    $stmt->execute([$uid]);
    echo "<script>window.location.href='index.php?page=notifikasi_semua';</script>";
    exit;
}

// --- AMBIL SEMUA NOTIFIKASI ---
try {
    $sql = "SELECT * FROM notifikasi WHERE id_user = :uid ORDER BY tanggal DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $uid]);
    $notifikasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Notifikasi Saya</h2>
    <?php if (!empty($notifikasi_list)): ?>
        <a href="index.php?page=notifikasi_semua&aksi=tandai_semua_baca" class="btn btn-outline-primary btn-sm rounded-pill">
            <i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 rounded-4">
    <div class="list-group list-group-flush rounded-4 overflow-hidden">
        
        <?php if (empty($notifikasi_list)): ?>
            <div class="text-center py-5 text-muted">
                <i class="far fa-bell-slash fa-3x mb-3 opacity-50"></i>
                <p>Tidak ada notifikasi.</p>
            </div>
        <?php else: ?>
            
            <?php foreach ($notifikasi_list as $notif): ?>
                <?php 
                    // Style untuk notifikasi belum dibaca (bg-blue-50)
                    $bg_class = ($notif['status'] == 'unread') ? 'bg-aliceblue' : 'bg-white';
                    $icon_color = ($notif['status'] == 'unread') ? 'text-primary' : 'text-secondary';
                    $fw_class = ($notif['status'] == 'unread') ? 'fw-bold' : 'fw-normal';
                    
                    // Link direct via baca_notif.php agar status berubah jadi read saat diklik
                    $link_url = "baca_notif.php?id=" . $notif['id_notif'] . "&link=" . urlencode($notif['link']);
                ?>
                
                <a href="<?php echo $link_url; ?>" class="list-group-item list-group-item-action p-4 border-bottom <?php echo $bg_class; ?>">
                    <div class="d-flex w-100 justify-content-between mb-1">
                        <h6 class="mb-1 <?php echo $fw_class; ?> text-dark">
                            <i class="fas fa-circle small me-2 <?php echo $icon_color; ?>" style="font-size: 8px; vertical-align: middle;"></i>
                            <?php echo htmlspecialchars($notif['judul']); ?>
                        </h6>
                        <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($notif['tanggal'])); ?></small>
                    </div>
                    <p class="mb-1 text-secondary small ps-3 ms-1" style="line-height: 1.5;">
                        <?php echo htmlspecialchars($notif['pesan']); ?>
                    </p>
                </a>
            <?php endforeach; ?>

        <?php endif; ?>
        
    </div>
</div>

<style>
    .bg-aliceblue { background-color: #f0f8ff; }
    .list-group-item-action:hover { background-color: #f8f9fa !important; }
</style>