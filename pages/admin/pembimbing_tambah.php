<?php

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = $_POST['nip'];
    $nama_guru = $_POST['nama_guru'];
    $no_telp = $_POST['no_telp'];

    if (empty($nip) || empty($nama_guru)) {
        $pesan_error = "NIP dan Nama Guru wajib diisi!";
    } else {
        try {
            $pdo->beginTransaction();
            
            $sql_pembimbing = "INSERT INTO pembimbing (nip, nama_guru, no_telp) VALUES (:nip, :nama, :telp)";
            $stmt_pembimbing = $pdo->prepare($sql_pembimbing);
            $stmt_pembimbing->execute([
                ':nip' => $nip,
                ':nama' => $nama_guru,
                ':telp' => $no_telp
            ]);
            
            $id_pembimbing_baru = $pdo->lastInsertId();

            $username_pembimbing = $nip;
            $password_default = $nip;
            $hashed_password = password_hash($password_default, PASSWORD_DEFAULT);
            $role_pembimbing = 'pembimbing';

            $sql_user = "INSERT INTO users (username, password, role, id_ref) VALUES (:username, :password, :role, :id_ref)";
            $stmt_user = $pdo->prepare($sql_user);
            
            $stmt_user->execute([
                ':username' => $username_pembimbing,
                ':password' => $hashed_password,
                ':role' => $role_pembimbing,
                ':id_ref' => $id_pembimbing_baru
            ]);

            $pdo->commit();

            $_SESSION['pesan_sukses'] = "Data pembimbing dan akun login berhasil ditambahkan!";
            header("Location: index.php?page=admin/pembimbing_data");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $pesan_error = "Gagal: NIP '$nip' sudah terdaftar. Gunakan NIP lain.";
            } else {
                $pesan_error = "Gagal menyimpan data: " . $e->getMessage();
            }
        }
    }
}
?>

<h2 class="mb-4">Tambah Data Guru Pembimbing Baru</h2>
<p class="mb-3">Menambahkan guru pembimbing juga akan membuatkan akun login (username: NIP, password: NIP).</p>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<form action="index.php?page=admin/pembimbing_tambah" method="POST">
    
    <div class="mb-3">
        <label for="nip" class="form-label">NIP</label>
        <input type="text" class="form-control" id="nip" name="nip" required 
               value="<?php echo htmlspecialchars(isset($_POST['nip']) ? $_POST['nip'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="nama_guru" class="form-label">Nama Guru</label>
        <input type="text" class="form-control" id="nama_guru" name="nama_guru" required
               value="<?php echo htmlspecialchars(isset($_POST['nama_guru']) ? $_POST['nama_guru'] : ''); ?>">
    </div>
    
    <div class="mb-3">
        <label for="no_telp" class="form-label">No. Telepon</label>
        <input type="text" class="form-control" id="no_telp" name="no_telp"
               value="<?php echo htmlspecialchars(isset($_POST['no_telp']) ? $_POST['no_telp'] : ''); ?>">
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan Data</button>
    <a href="index.php?page=admin/pembimbing_data" class="btn btn-secondary">Kembali</a>
</form>