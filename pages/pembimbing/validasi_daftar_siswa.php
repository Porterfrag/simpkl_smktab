<?php
// --- Bagian PHP (Logika Backend Tetap Sama, Tidak Ada Perubahan) ---

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];

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
    $sql = "SELECT 
                siswa.id_siswa, 
                siswa.nis, 
                siswa.nama_lengkap, 
                siswa.kelas, 
                perusahaan.nama_perusahaan,
                (SELECT COUNT(*) FROM jurnal_harian WHERE id_siswa = siswa.id_siswa AND status_validasi = 'Pending') as jumlah_pending
            FROM 
                siswa
            LEFT JOIN 
                perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
            WHERE 
                siswa.id_pembimbing = :id_pembimbing
            ORDER BY 
                siswa.nama_lengkap ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_pembimbing', $id_pembimbing);
    $stmt->execute();
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Error: " . $e->getMessage() . "</div>";
}
?>

<!-- --- Tampilan Menggunakan Murni Bootstrap 5 (Tanpa Custom CSS) --- -->

<div class="container-fluid px-0">
    <!-- Header & Search -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <h4 class="fw-bold text-primary mb-1"><i class="fas fa-user-graduate me-2"></i>Siswa Bimbingan</h4>
            <p class="text-muted mb-0 small">Kelola jurnal dan validasi absensi siswa.</p>
        </div>
        <div class="col-md-6">
            <!-- Search Box menggunakan Input Group Bootstrap 5 -->
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="bsSearchInput" class="form-control border-start-0 py-2" placeholder="Cari siswa, kelas, atau perusahaan...">
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(!empty($pesan_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $pesan_sukses; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($pesan_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $pesan_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Daftar Siswa (Card Layout) -->
    <div id="studentListContainer">
        <?php foreach ($siswa_list as $siswa): ?>
            <?php 
                $inisial = strtoupper(substr($siswa['nama_lengkap'], 0, 2));
                // Logic badge status
                if ($siswa['jumlah_pending'] > 0) {
                    $badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger';
                    $badgeIcon = 'fa-clock';
                    $badgeText = $siswa['jumlah_pending'] . ' Jurnal Pending';
                } else {
                    $badgeClass = 'bg-success bg-opacity-10 text-success border border-success';
                    $badgeIcon = 'fa-check-circle';
                    $badgeText = 'Semua Valid';
                }
                $nama_perusahaan = isset($siswa['nama_perusahaan']) ? $siswa['nama_perusahaan'] : 'Belum diatur';
            ?>
            
            <!-- Item Card Siswa (Class 'student-item' untuk target pencarian JS) -->
            <div class="card mb-3 border-0 shadow-sm student-item">
                <div class="card-body p-3">
                    <div class="row align-items-center gy-3">
                        
                        <!-- Kolom 1: Identitas Siswa (Avatar + Nama) -->
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center">
                                <!-- Avatar Lingkaran Bootstrap -->
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-light text-primary fw-bold me-3" style="width: 50px; height: 50px; font-size: 1.1rem;">
                                    <?php echo $inisial; ?>
                                </div>
                                <div>
                                    <h6 class="card-title fw-bold mb-0 text-dark student-name"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></h6>
                                    <small class="text-muted">
                                        NIS: <?php echo htmlspecialchars($siswa['nis']); ?> &bull; 
                                        <span class="student-class"><?php echo htmlspecialchars($siswa['kelas']); ?></span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 2: Info PKL & Status (Grid Nested) -->
                        <div class="col-12 col-md-5">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Tempat PKL</small>
                                    <div class="fw-medium text-truncate student-company">
                                        <i class="fas fa-building text-secondary me-1 small"></i> 
                                        <?php echo htmlspecialchars($nama_perusahaan); ?>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Status</small>
                                    <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                                        <i class="fas <?php echo $badgeIcon; ?> me-1"></i> <?php echo $badgeText; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom 3: Tombol Aksi -->
                        <div class="col-12 col-md-3">
                            <!-- d-grid di mobile (full width), d-md-flex di desktop (auto width) -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php?page=pembimbing/validasi_jurnal_siswa&id_siswa=<?php echo $siswa['id_siswa']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    Validasi
                                </a>
                                <div class="btn-group" role="group">
                                    <a href="index.php?page=pembimbing/rekap_absensi_siswa&id_siswa=<?php echo $siswa['id_siswa']; ?>" 
                                       class="btn btn-outline-secondary btn-sm" title="Rekap Absen">
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                    <a href="index.php?page=pembimbing/rekap_kalender_siswa&id_siswa=<?php echo $siswa['id_siswa']; ?>" 
                                       class="btn btn-outline-info btn-sm" title="Kalender">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Tampilan Kosong -->
        <?php if (empty($siswa_list)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users-slash fa-3x mb-3 opacity-50"></i>
                <p>Anda belum memiliki siswa bimbingan.</p>
            </div>
        <?php endif; ?>
        
        <!-- Pesan "Tidak Ditemukan" untuk Search (Hidden by default) -->
        <div id="noResultsMessage" class="text-center py-5 d-none">
            <i class="fas fa-search fa-3x mb-3 text-muted opacity-25"></i>
            <p class="text-muted">Siswa tidak ditemukan.</p>
        </div>
    </div>
</div>

<!-- Javascript Search Filter (Updated for Cards) -->
<script>
document.getElementById('bsSearchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const items = document.querySelectorAll('.student-item');
    let visibleCount = 0;

    items.forEach(item => {
        // Ambil teks dari elemen spesifik di dalam kartu
        const name = item.querySelector('.student-name').textContent.toLowerCase();
        const company = item.querySelector('.student-company').textContent.toLowerCase();
        const kelas = item.querySelector('.student-class').textContent.toLowerCase();

        if (name.includes(filter) || company.includes(filter) || kelas.includes(filter)) {
            item.classList.remove('d-none');
            visibleCount++;
        } else {
            item.classList.add('d-none');
        }
    });

    // Tampilkan pesan jika tidak ada hasil
    const noResults = document.getElementById('noResultsMessage');
    if (visibleCount === 0 && items.length > 0) {
        noResults.classList.remove('d-none');
    } else {
        noResults.classList.add('d-none');
    }
});
</script>