<?php

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

$filter_mode = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$where_clause = "";
if ($filter_mode == 'unplotted') {
    $where_clause = "WHERE siswa.id_perusahaan IS NULL OR siswa.id_pembimbing IS NULL";
}

try {
    $sql = "SELECT 
                siswa.id_siswa, siswa.nis, siswa.nama_lengkap, siswa.jurusan, siswa.kelas,
                perusahaan.nama_perusahaan, pembimbing.nama_guru
            FROM siswa
            LEFT JOIN perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
            LEFT JOIN pembimbing ON siswa.id_pembimbing = pembimbing.id_pembimbing
            $where_clause
            ORDER BY siswa.nama_lengkap ASC";
    $stmt = $pdo->query($sql);
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dudi_list = $pdo->query("SELECT id_perusahaan, nama_perusahaan FROM perusahaan ORDER BY nama_perusahaan ASC")->fetchAll(PDO::FETCH_ASSOC);

    $guru_list = $pdo->query("SELECT id_pembimbing, nama_guru FROM pembimbing ORDER BY nama_guru ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $siswa_list = [];
}
?>

<h2 class="mb-4">Plotting Penempatan Siswa</h2>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success role='alert'"><?php echo $pesan_sukses; ?></div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger role='alert'"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<div class="mb-4">
    <div class="btn-group" role="group">
        <a href="index.php?page=admin/plotting_data&filter=all" class="btn btn-outline-primary <?php echo ($filter_mode == 'all') ? 'active' : ''; ?>">Semua Siswa</a>
        <a href="index.php?page=admin/plotting_data&filter=unplotted" class="btn btn-outline-danger <?php echo ($filter_mode == 'unplotted') ? 'active' : ''; ?>">Belum Di-Plotting</a>
    </div>
</div>

<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Plotting Massal</h5>
    </div>
    <div class="card-body bg-light">
        <form action="index.php?page=admin/plotting_bulk" method="POST" id="bulkForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Pilih Perusahaan (DUDI)</label>
                   <select name="id_perusahaan" class="form-select select2">
    <option value="">-- Pilih Perusahaan --</option>
                        <?php foreach ($dudi_list as $d): ?>
                            <option value="<?php echo $d['id_perusahaan']; ?>"><?php echo htmlspecialchars($d['nama_perusahaan']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Pilih Guru Pembimbing</label>
                <select name="id_pembimbing" class="form-select select2">
    <option value="">-- Pilih Guru Pembimbing --</option>
                        <?php foreach ($guru_list as $g): ?>
                            <option value="<?php echo $g['id_pembimbing']; ?>"><?php echo htmlspecialchars($g['nama_guru']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Yakin ingin menerapkan plotting ke siswa yang dipilih?')">
                        <i class="fas fa-check-double me-2"></i> Terapkan ke Siswa Terpilih
                    </button>
                </div>
            </div>
            
            <div class="table-responsive mt-4">
                <table class="table table-striped table-hover table-bordered datatable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" id="selectAll" class="form-check-input border-dark">
                            </th>
                            <th>Nama Siswa</th>
                            <th>Kelas / Jurusan</th>
                            <th>Perusahaan Saat Ini</th>
                            <th>Pembimbing Saat Ini</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siswa_list as $siswa): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" name="id_siswa[]" value="<?php echo $siswa['id_siswa']; ?>" class="form-check-input student-checkbox border-secondary">
                                </td>
                                <td class="text-start fw-bold"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                                <td class="text-start"><?php echo htmlspecialchars($siswa['kelas']); ?> - <?php echo htmlspecialchars($siswa['jurusan']); ?></td>
                                
                                <td class="text-start">
                                    <?php if (!empty($siswa['nama_perusahaan'])): ?>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($siswa['nama_perusahaan']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-start">
                                    <?php if (!empty($siswa['nama_guru'])): ?>
                                        <span class="text-primary"><?php echo htmlspecialchars($siswa['nama_guru']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum Ada</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                        <div class="btn-group" role="group">
                            <a href="index.php?page=admin/plotting_edit&id_siswa=<?php echo $siswa['id_siswa']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Plotting">
                                <i class="fas fa-edit"></i>
                            </a>

                            <?php if (!empty($siswa['nama_perusahaan'])): ?>
                                <a href="cetak_surat_pengantar.php?id_siswa=<?php echo $siswa['id_siswa']; ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Surat Pengantar">
                                    <i class="fas fa-print"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form> </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    });
});
</script>