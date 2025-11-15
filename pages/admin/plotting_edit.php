<?php

$pesan_sukses = '';
$pesan_error = '';
$siswa = null;
$perusahaan_list = [];
$pembimbing_list = [];

if (!isset($_GET['id_siswa'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Siswa tidak ditemukan.</div>";
    exit;
}
$id_siswa = $_GET['id_siswa'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_perusahaan = $_POST['id_perusahaan'] ?: null;
    $id_pembimbing = $_POST['id_pembimbing'] ?: null;

    try {
        $sql = "UPDATE siswa SET id_perusahaan = :id_perusahaan, id_pembimbing = :id_pembimbing WHERE id_siswa = :id_siswa";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':id_perusahaan' => $id_perusahaan,
            ':id_pembimbing' => $id_pembimbing,
            ':id_siswa' => $id_siswa
        ]);
        
        $_SESSION['pesan_sukses'] = "Data penempatan siswa berhasil diperbarui!";
        header("Location: index.php?page=admin/plotting_data");
        exit;

    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
    }
}

try {
    $sql_siswa = "SELECT * FROM siswa WHERE id_siswa = :id_siswa";
    $stmt_siswa = $pdo->prepare($sql_siswa);
    $stmt_siswa->bindParam(':id_siswa', $id_siswa);
    $stmt_siswa->execute();
    $siswa = $stmt_siswa->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        echo "<div class='alert alert-danger' role='alert'>Error: Data siswa tidak ditemukan.</div>";
        exit;
    }

    $stmt_perusahaan = $pdo->query("SELECT id_perusahaan, nama_perusahaan FROM perusahaan ORDER BY nama_perusahaan ASC");
    $perusahaan_list = $stmt_perusahaan->fetchAll(PDO::FETCH_ASSOC);

    $stmt_pembimbing = $pdo->query("SELECT id_pembimbing, nama_guru FROM pembimbing ORDER BY nama_guru ASC");
    $pembimbing_list = $stmt_pembimbing->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>

<h2 class="mb-4">Atur Penempatan Siswa</h2>

<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<?php if ($siswa): ?>
<form action="index.php?page=admin/plotting_edit&id_siswa=<?php echo $id_siswa; ?>" method="POST">
    
    <div class="mb-3 p-3 bg-light rounded">
        <h5>Siswa yang diatur:</h5>
        <p class="mb-1"><strong>NIS:</strong> <?php echo htmlspecialchars($siswa['nis']); ?></p>
        <p class="mb-1"><strong>Nama:</strong> <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></p>
        <p class="mb-0"><strong>Kelas/Jurusan:</strong> <?php echo htmlspecialchars($siswa['kelas']); ?> / <?php echo htmlspecialchars($siswa['jurusan']); ?></p>
    </div>

    <div class="mb-3">
        <label for="id_perusahaan" class="form-label">Tempat PKL (DUDI)</label>
        <select name="id_perusahaan" id="id_perusahaan" class="form-select">
            <option value="">-- Kosongkan / Belum Ditempatkan --</option>
            <?php foreach ($perusahaan_list as $perusahaan): ?>
                <option value="<?php echo $perusahaan['id_perusahaan']; ?>" 
                    <?php echo ($perusahaan['id_perusahaan'] == $siswa['id_perusahaan']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($perusahaan['nama_perusahaan']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="mb-3">
        <label for="id_pembimbing" class="form-label">Guru Pembimbing</label>
        <select name="id_pembimbing" id="id_pembimbing" class="form-select">
            <option value="">-- Kosongkan / Belum Diatur --</option>
            <?php foreach ($pembimbing_list as $pembimbing): ?>
                <option value="<?php echo $pembimbing['id_pembimbing']; ?>"
                    <?php echo ($pembimbing['id_pembimbing'] == $siswa['id_pembimbing']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($pembimbing['nama_guru']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan Penempatan</button>
    <a href="index.php?page=admin/plotting_data" class="btn btn-secondary">Batal</a>
</form>
<?php endif; ?>