<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

$pesan_sukses = '';
$pesan_error = '';

if (isset($_SESSION['pesan_sukses'])) {
    $pesan_sukses = $_SESSION['pesan_sukses'];
    unset($_SESSION['pesan_sukses']); 
}
if (isset($_SESSION['pesan_error'])) {
    $pesan_error = $_SESSION['pesan_error'];
    unset($_SESSION['pesan_error']); 
}

try {
    $sql = "SELECT pengumuman.*, users.username 
            FROM pengumuman 
            LEFT JOIN users ON pengumuman.id_admin = users.id
            ORDER BY tanggal_post DESC";
    $stmt = $pdo->query($sql);
    $pengumuman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data pengumuman: " . $e->getMessage() . "</div>";
    $pengumuman_list = [];
}
?>

<h2 class="mb-4">Manajemen Data Pengumuman</h2>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $pesan_sukses; ?>
    </div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<a href="index.php?page=admin/pengumuman_tambah" class="btn btn-success mb-3">Buat Pengumuman Baru</a>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Tanggal Posting</th>
                <th>Judul</th>
                <th>Isi Pengumuman</th>
                <th>Di-posting Oleh</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($pengumuman_list as $pengumuman): ?>
                <tr>
                    <td width="5%"><?php echo $no++; ?></td>
                    
                    <td width="15%"><?php echo date('d M Y, H:i', strtotime($pengumuman['tanggal_post'])); ?></td>
                    
                    <td width="20%"><?php echo htmlspecialchars($pengumuman['judul']); ?></td>
                    <td width="35%"><?php echo nl2br($pengumuman['isi']); ?></td>
                    <td width="10%">
                        <?php echo htmlspecialchars(isset($pengumuman['username']) ? $pengumuman['username'] : 'N/A'); ?>
                    </td>
                    <td width="10%">
                        <a href="index.php?page=admin/pengumuman_hapus&id=<?php echo $pengumuman['id_pengumuman']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Yakin ingin menghapus pengumuman ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($pengumuman_list)): ?>
                <tr>
                    <td colspan="6" class="text-center">Belum ada pengumuman yang dibuat.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>