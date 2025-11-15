<?php

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nis = $_POST['nis'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jurusan = $_POST['jurusan'];
    $kelas = $_POST['kelas'];

    if (empty($nis) || empty($nama_lengkap) || empty($jurusan) || empty($kelas)) {
        $pesan_error = "Semua kolom wajib diisi!";
    } else {
        try {
            
            $sql_siswa = "INSERT INTO siswa (nis, nama_lengkap, jurusan, kelas) VALUES (:nis, :nama_lengkap, :jurusan, :kelas)";
            $stmt_siswa = $pdo->prepare($sql_siswa);
            $stmt_siswa->execute([
                ':nis' => $nis,
                ':nama_lengkap' => $nama_lengkap,
                ':jurusan' => $jurusan,
                ':kelas' => $kelas
            ]);
            
            $id_siswa_baru = $pdo->lastInsertId();

            $username_siswa = $nis;
            $password_siswa_default = $nis;
           $hashed_password = password_hash($password_siswa_default, PASSWORD_DEFAULT);
            $role_siswa = 'siswa'; 

            $sql_user = "INSERT INTO users (username, password, role, id_ref) VALUES (:username, :password, :role, :id_ref)";
            $stmt_user = $pdo->prepare($sql_user);
            
            $stmt_user->execute([
                ':username' => $username_siswa,
                ':password' => $hashed_password,
                ':role' => $role_siswa,
                ':id_ref' => $id_siswa_baru
            ]);

            $pesan_sukses = "Data siswa dan akun login berhasil ditambahkan!";
            
            $_POST = array(); 
            
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $pesan_error = "Gagal: NIS '$nis' sudah terdaftar. Gunakan NIS lain.";
            } else {
                $pesan_error = "Gagal menyimpan data: " . $e->getMessage();
            }
        }
    }
}
?>

<h2 class="mb-4">Tambah Data Siswa Baru</h2>
<p class="mb-3">Menambahkan siswa baru juga akan otomatis membuatkan akun login (username: NIS, password: NIS).</p>

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

<form action="index.php?page=admin/siswa_tambah" method="POST">
    
    <div class="mb-3">
        <label for="nis" class="form-label">NIS</label>
        <input type="text" class="form-control" id="nis" name="nis" required 
               value="<?php echo htmlspecialchars(isset($_POST['nis']) ? $_POST['nis'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required
               value="<?php echo htmlspecialchars(isset($_POST['nama_lengkap']) ? $_POST['nama_lengkap'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="jurusan" class="form-label">Jurusan</label>
        <input type="text" class="form-control" id="jurusan" name="jurusan" placeholder="Contoh: Rekayasa Perangkat Lunak" required
               value="<?php echo htmlspecialchars(isset($_POST['jurusan']) ? $_POST['jurusan'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="kelas" class="form-label">Kelas</label>
        <input type="text" class="form-control" id="kelas" name="kelas" placeholder="Contoh: XII RPL 1" required
               value="<?php echo htmlspecialchars(isset($_POST['kelas']) ? $_POST['kelas'] : ''); ?>">
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan Data</button>
    <a href="index.php?page=admin/siswa_data" class="btn btn-secondary">Kembali</a>
</form>