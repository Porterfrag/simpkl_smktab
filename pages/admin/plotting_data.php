<?php
// --- 1. LOGIKA PHP & QUERY DATABASE ---

$pesan_sukses = '';
$pesan_error = '';

// Cek Flash Message Session
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_sukses = $_SESSION['pesan_sukses'];
    unset($_SESSION['pesan_sukses']); 
}
if (isset($_SESSION['pesan_error'])) {
    $pesan_error = $_SESSION['pesan_error'];
    unset($_SESSION['pesan_error']); 
}

// Tangkap Parameter URL
$filter_mode = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$search_input = isset($_GET['search']) ? $_GET['search'] : '';

// Persiapan Query Dinamis
$where_conditions = [];
$params = [];

// A. Logika Filter (Unplotted)
if ($filter_mode == 'unplotted') {
    $where_conditions[] = "(siswa.id_perusahaan IS NULL OR siswa.id_pembimbing IS NULL)";
}

// B. Logika Multi-Search (Pencarian Banyak dengan Koma)
if (!empty($search_input)) {
    // Pecah string berdasarkan koma menjadi array
    $keywords = explode(',', $search_input);
    $search_clauses = [];
    
    foreach ($keywords as $word) {
        $word = trim($word); // Hilangkan spasi berlebih
        if (!empty($word)) {
            // Cari di Nama ATAU NIS
            $search_clauses[] = "(siswa.nama_lengkap LIKE ? OR siswa.nis LIKE ?)";
            $params[] = "%$word%";
            $params[] = "%$word%";
        }
    }
    
    // Gabungkan clause pencarian dengan OR (agar salah satu cocok, data tampil)
    if (!empty($search_clauses)) {
        $where_conditions[] = "(" . implode(' OR ', $search_clauses) . ")";
    }
}

// C. Rakit WHERE Clause Akhir
$sql_where = "";
if (!empty($where_conditions)) {
    $sql_where = "WHERE " . implode(' AND ', $where_conditions);
}

try {
    // Query Utama Siswa
    $sql = "SELECT 
                siswa.id_siswa, siswa.nis, siswa.nama_lengkap, siswa.jurusan, siswa.kelas,
                perusahaan.nama_perusahaan, pembimbing.nama_guru
            FROM siswa
            LEFT JOIN perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
            LEFT JOIN pembimbing ON siswa.id_pembimbing = pembimbing.id_pembimbing
            $sql_where
            ORDER BY siswa.nama_lengkap ASC";

    // Gunakan Prepare Statement (Penting karena ada input user dari pencarian)
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query Data Pendukung untuk Dropdown
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

<div class="card mb-4 border-light shadow-sm">
    <div class="card-body">
        <form action="index.php" method="GET" class="row g-3 align-items-center">
            <input type="hidden" name="page" value="admin/plotting_data">
            
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari banyak siswa? Contoh: Ahmad, Budi, 12345" 
                           value="<?php echo htmlspecialchars($search_input); ?>">
                    <button class="btn btn-primary" type="submit">Cari</button>
                    <?php if(!empty($search_input)): ?>
                        <a href="index.php?page=admin/plotting_data" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
                <small class="text-muted fst-italic ms-2">Pisahkan dengan koma (,) untuk mencari lebih dari satu orang.</small>
            </div>

            <div class="col-md-6 text-md-end">
                <div class="btn-group" role="group">
                    <a href="index.php?page=admin/plotting_data&filter=all<?php echo $search_input ? '&search='.$search_input : ''; ?>" 
                       class="btn btn-outline-primary <?php echo ($filter_mode == 'all') ? 'active' : ''; ?>">
                       Semua Siswa
                    </a>
                    <a href="index.php?page=admin/plotting_data&filter=unplotted<?php echo $search_input ? '&search='.$search_input : ''; ?>" 
                       class="btn btn-outline-danger <?php echo ($filter_mode == 'unplotted') ? 'active' : ''; ?>">
                       Belum Di-Plotting
                    </a>
                </div>
            </div>
        </form>
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
                        <?php if (count($siswa_list) > 0): ?>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="id_siswa[]" value="<?php echo $siswa['id_siswa']; ?>" class="form-check-input student-checkbox border-secondary">
                                    </td>
                                    <td class="text-start fw-bold">
                                        <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($siswa['nis']); ?></small>
                                    </td>
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
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-1"></i> Data siswa tidak ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form> 
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');

    if(selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});
</script>