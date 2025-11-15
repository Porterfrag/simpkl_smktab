<?php

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}
$id_admin = $_SESSION['user_id'];

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];

    if (empty($judul) || empty($isi)) {
        $pesan_error = "Judul dan Isi Pengumuman wajib diisi!";
    } else {
        try {
            $sql = "INSERT INTO pengumuman (judul, isi, id_admin) VALUES (:judul, :isi, :id_admin)";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':judul' => $judul,
                ':isi' => $isi,
                ':id_admin' => $id_admin
            ]);
            
            $pesan_sukses = "Pengumuman berhasil diposting!";
            $_POST = array(); 
            
        } catch (PDOException $e) {
            $pesan_error = "Gagal memposting pengumuman: " . $e->getMessage();
        }
    }
}
?>

<h2 class="mb-4">Buat Pengumuman Baru</h2>
<p class="mb-3">Pengumuman ini akan muncul di halaman dashboard Siswa dan Pembimbing.</p>

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

<form action="index.php?page=admin/pengumuman_tambah" method="POST">
    
    <div class="mb-3">
        <label for="judul" class="form-label">Judul Pengumuman</label>
        <input type="text" class="form-control" id="judul" name="judul" required 
               value="<?php echo htmlspecialchars(isset($_POST['judul']) ? $_POST['judul'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="isi" class="form-label">Isi Pengumuman</label>
        <textarea class="form-control" id="isi" name="isi" rows="8" required><?php echo htmlspecialchars(isset($_POST['isi']) ? $_POST['isi'] : ''); ?></textarea>
        <small class="form-text text-muted">Anda bisa menggunakan tag HTML sederhana jika perlu (cth: &lt;strong&gt;, &lt;em&gt;).</small>
    </div>
    
    <button type="submit" class="btn btn-primary">Posting Pengumuman</button>
    <a href="index.php?page=admin/pengumuman_data" class="btn btn-secondary">Lihat Daftar Pengumuman</a>
</form>