<?php

$pesan_sukses = '';
$pesan_error = '';
$perusahaan = null;

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Perusahaan tidak ditemukan.</div>";
    exit;
}
$id_perusahaan = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_perusahaan = $_POST['nama_perusahaan'];
    $alamat = $_POST['alamat'];
    $kontak_person = $_POST['kontak_person'];
    $no_telp = $_POST['no_telp'];

    $hari_kerja_arr = isset($_POST['hari_kerja']) ? $_POST['hari_kerja'] : [];
    $hari_kerja_str = implode(',', $hari_kerja_arr);

    try {
        $sql = "UPDATE perusahaan SET 
                    nama_perusahaan = :nama, 
                    alamat = :alamat, 
                    kontak_person = :kontak, 
                    no_telp = :telp,
                    hari_kerja = :hari_kerja 
                WHERE id_perusahaan = :id";
        
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nama' => $nama_perusahaan,
            ':alamat' => $alamat,
            ':kontak' => $kontak_person,
            ':telp' => $no_telp,
            ':hari_kerja' => $hari_kerja_str, 
            ':id' => $id_perusahaan
        ]);
        
        $pesan_sukses = "Data perusahaan berhasil diperbarui!";

    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
    }
}

try {
    $sql_get = "SELECT * FROM perusahaan WHERE id_perusahaan = :id";
    $stmt_get = $pdo->prepare($sql_get);
    $stmt_get->bindParam(':id', $id_perusahaan);
    $stmt_get->execute();
    
    $perusahaan = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$perusahaan) {
        echo "<div class='alert alert-danger' role='alert'>Error: Data perusahaan dengan ID $id_perusahaan tidak ditemukan.</div>";
        exit;
    }

    $hari_kerja_db = !empty($perusahaan['hari_kerja']) ? explode(',', $perusahaan['hari_kerja']) : [];

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>

<h2 class="mb-4">Edit Data Perusahaan</h2>

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

<?php if ($perusahaan): ?>
<form action="index.php?page=admin/perusahaan_edit&id=<?php echo $id_perusahaan; ?>" method="POST">
    
    <div class="mb-3">
        <label for="nama_perusahaan" class="form-label">Nama Perusahaan</label>
        <input type="text" class="form-control" id="nama_perusahaan" name="nama_perusahaan" value="<?php echo htmlspecialchars($perusahaan['nama_perusahaan']); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="alamat" class="form-label">Alamat</label>
        <textarea class="form-control" id="alamat" name="alamat" rows="4" required><?php echo htmlspecialchars($perusahaan['alamat']); ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="kontak_person" class="form-label">Kontak Person</label>
        <input type="text" class="form-control" id="kontak_person" name="kontak_person" value="<?php echo htmlspecialchars($perusahaan['kontak_person']); ?>">
    </div>
    
    <div class="mb-3">
        <label for="no_telp" class="form-label">No. Telepon</label>
        <input type="text" class="form-control" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($perusahaan['no_telp']); ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Hari Kerja</label>
        <div class="card p-3 bg-light border-0">
            <div class="d-flex flex-wrap gap-3">
                <?php
                $days = [1=>'Senin', 2=>'Selasa', 3=>'Rabu', 4=>'Kamis', 5=>'Jumat', 6=>'Sabtu', 7=>'Minggu'];
                foreach ($days as $num => $name) {
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        $checked = (isset($_POST['hari_kerja']) && in_array($num, $_POST['hari_kerja'])) ? 'checked' : '';
                    } else {
                        $checked = (in_array($num, $hari_kerja_db)) ? 'checked' : '';
                    }
                    
                    echo "
                    <div class='form-check'>
                        <input class='form-check-input' type='checkbox' name='hari_kerja[]' value='$num' id='day$num' $checked>
                        <label class='form-check-label' for='day$num'>$name</label>
                    </div>";
                }
                ?>
            </div>
            <small class="text-muted mt-2 d-block">* Hari yang tidak dicentang akan dianggap sebagai hari libur di kalender absensi siswa.</small>
        </div>
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="index.php?page=admin/perusahaan_data" class="btn btn-secondary">Batal</a>
</form>
<?php endif; ?>