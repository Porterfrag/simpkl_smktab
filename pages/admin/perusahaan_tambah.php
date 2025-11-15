<?php

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_perusahaan = $_POST['nama_perusahaan'];
    $alamat = $_POST['alamat'];
    $kontak_person = $_POST['kontak_person'];
    $no_telp = $_POST['no_telp'];

    $hari_kerja_arr = isset($_POST['hari_kerja']) ? $_POST['hari_kerja'] : [];
    $hari_kerja_str = implode(',', $hari_kerja_arr);

    if (empty($nama_perusahaan) || empty($alamat)) {
        $pesan_error = "Nama Perusahaan dan Alamat wajib diisi!";
    } else {
        try {
            $sql = "INSERT INTO perusahaan (nama_perusahaan, alamat, kontak_person, no_telp, hari_kerja) 
                    VALUES (:nama, :alamat, :kontak, :telp, :hari_kerja)";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':nama' => $nama_perusahaan,
                ':alamat' => $alamat,
                ':kontak' => $kontak_person,
                ':telp' => $no_telp,
                ':hari_kerja' => $hari_kerja_str 
            ]);
            
            $_SESSION['pesan_sukses'] = "Data perusahaan berhasil ditambahkan!";
            header("Location: index.php?page=admin/perusahaan_data");
            exit;
            
        } catch (PDOException $e) {
            $pesan_error = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}
?>

<h2 class="mb-4">Tambah Data Perusahaan Baru</h2>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<form action="index.php?page=admin/perusahaan_tambah" method="POST">
    
    <div class="mb-3">
        <label for="nama_perusahaan" class="form-label">Nama Perusahaan</label>
        <input type="text" class="form-control" id="nama_perusahaan" name="nama_perusahaan" required 
               value="<?php echo htmlspecialchars(isset($_POST['nama_perusahaan']) ? $_POST['nama_perusahaan'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="alamat" class="form-label">Alamat</label>
        <textarea class="form-control" id="alamat" name="alamat" rows="4" required><?php echo htmlspecialchars(isset($_POST['alamat']) ? $_POST['alamat'] : ''); ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="kontak_person" class="form-label">Kontak Person</label>
        <input type="text" class="form-control" id="kontak_person" name="kontak_person" 
               value="<?php echo htmlspecialchars(isset($_POST['kontak_person']) ? $_POST['kontak_person'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="no_telp" class="form-label">No. Telepon</label>
        <input type="text" class="form-control" id="no_telp" name="no_telp" 
               value="<?php echo htmlspecialchars(isset($_POST['no_telp']) ? $_POST['no_telp'] : ''); ?>">
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
                        $checked = ($num <= 5) ? 'checked' : '';
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
    
    <button type="submit" class="btn btn-primary">Simpan Data</button>
    <a href="index.php?page=admin/perusahaan_data" class="btn btn-secondary">Kembali</a>
</form>