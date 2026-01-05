<?php
// --- BAGIAN LOGIKA PHP (TIDAK DIUBAH, SAMA PERSIS DENGAN ASLINYA) ---

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];

if (!isset($_GET['id_siswa'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Siswa tidak ditemukan.</div>";
    exit;
}
$id_siswa = $_GET['id_siswa'];

$pesan_sukses_nilai = '';
$pesan_error_nilai = '';
$pesan_sukses_jurnal = '';
$pesan_error_jurnal = '';

$grading_start_date = isset($SETTINGS['grading_start_date']) ? $SETTINGS['grading_start_date'] : '2025-01-01'; 
$is_grading_phase = (date('Y-m-d') >= $grading_start_date);

try {
    $sql_cek = "SELECT nama_lengkap FROM siswa WHERE id_siswa = :id_siswa AND id_pembimbing = :id_pembimbing";
    $stmt_cek = $pdo->prepare($sql_cek);
    $stmt_cek->bindParam(':id_siswa', $id_siswa);
    $stmt_cek->bindParam(':id_pembimbing', $id_pembimbing);
    $stmt_cek->execute();
    
    $siswa = $stmt_cek->fetch(PDO::FETCH_ASSOC);
    if (!$siswa) {
        die("Error 403: Anda tidak memiliki akses ke data siswa ini.");
    }
    $nama_siswa = $siswa['nama_lengkap'];
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}


if (isset($_POST['simpan_nilai'])) {
    $aspek_disiplin = $_POST['aspek_disiplin'];
    $aspek_kompetensi = $_POST['aspek_kompetensi'];
    $aspek_kerjasama = $_POST['aspek_kerjasama'];
    $aspek_inisiatif = $_POST['aspek_inisiatif'];
    $catatan_penilaian = $_POST['catatan_penilaian'];
    
    try {
        $sql_nilai = "INSERT INTO penilaian (id_siswa, id_pembimbing, aspek_disiplin, aspek_kompetensi, aspek_kerjasama, aspek_inisiatif, catatan_penilaian)
                      VALUES (:id_siswa, :id_pembimbing, :disiplin, :kompetensi, :kerjasama, :inisiatif, :catatan)
                      ON DUPLICATE KEY UPDATE
                          id_pembimbing = :id_pembimbing,
                          aspek_disiplin = :disiplin,
                          aspek_kompetensi = :kompetensi,
                          aspek_kerjasama = :kerjasama,
                          aspek_inisiatif = :inisiatif,
                          catatan_penilaian = :catatan";
                          
        $stmt_nilai = $pdo->prepare($sql_nilai);
        
        $stmt_nilai->execute([
            ':id_siswa' => $id_siswa,
            ':id_pembimbing' => $id_pembimbing,
            ':disiplin' => $aspek_disiplin,
            ':kompetensi' => $aspek_kompetensi,
            ':kerjasama' => $aspek_kerjasama,
            ':inisiatif' => $aspek_inisiatif,
            ':catatan' => $catatan_penilaian
        ]);
        
        $pesan_sukses_nilai = "Nilai akhir siswa berhasil disimpan/diperbarui!";
        
    } catch (PDOException $e) {
        $pesan_error_nilai = "Gagal menyimpan nilai: " . $e->getMessage();
    }
}


if (isset($_POST['status_validasi'])) {
    $id_jurnal = $_POST['id_jurnal'];
    $status_validasi = $_POST['status_validasi'];
    $catatan_pembimbing = $_POST['catatan_pembimbing'];

    if (empty($id_jurnal) || empty($status_validasi)) {
        $pesan_error_jurnal = "Terjadi kesalahan saat memproses data jurnal.";
    } else {
        try {
            $sql_update = "UPDATE jurnal_harian 
                           SET status_validasi = :status, catatan_pembimbing = :catatan 
                           WHERE id_jurnal = :id_jurnal AND id_siswa = :id_siswa";
            $stmt_update = $pdo->prepare($sql_update);
            
            $stmt_update->execute([
                ':status' => $status_validasi,
                ':catatan' => $catatan_pembimbing,
                ':id_jurnal' => $id_jurnal,
                ':id_siswa' => $id_siswa
            ]);

            // Notifikasi logic (disederhanakan sesuai snippet asli)
            $stmt_u = $pdo->prepare("SELECT id FROM users WHERE role='siswa' AND id_ref = ?");
            $stmt_u->execute([$id_siswa]);
            $id_user_siswa = $stmt_u->fetchColumn();

            if ($id_user_siswa && function_exists('kirim_notifikasi')) {
                $judul_notif = "Jurnal " . $status_validasi;
                $isi_notif = "Jurnal Anda tanggal " . date('d/m') . " telah " . strtolower($status_validasi) . " oleh pembimbing.";
                $link_notif = "index.php?page=siswa/jurnal_lihat";
                kirim_notifikasi($pdo, $id_user_siswa, $judul_notif, $isi_notif, $link_notif);
            }
            
            $pesan_sukses_jurnal = "Jurnal berhasil divalidasi!";
            
        } catch (PDOException $e) {
            $pesan_error_jurnal = "Gagal memvalidasi jurnal: " . $e->getMessage();
        }
    }
}


$nilai = null; 
try {
    $sql_get_nilai = "SELECT * FROM penilaian WHERE id_siswa = :id_siswa";
    $stmt_get_nilai = $pdo->prepare($sql_get_nilai);
    $stmt_get_nilai->execute(['id_siswa' => $id_siswa]);
    $nilai = $stmt_get_nilai->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pesan_error_nilai = "Gagal mengambil data nilai: " . $e->getMessage();
}

$jurnal_list = [];
try {
    $sql_jurnal = "SELECT * FROM jurnal_harian WHERE id_siswa = :id_siswa ORDER BY tanggal DESC";
    $stmt_jurnal = $pdo->prepare($sql_jurnal);
    $stmt_jurnal->bindParam(':id_siswa', $id_siswa);
    $stmt_jurnal->execute();
    $jurnal_list = $stmt_jurnal->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// --- LOGIKA PAGINASI PHP BARU ---
$data_per_halaman = 5; // Jumlah data per halaman
$total_jurnal = count($jurnal_list);
$total_halaman = ceil($total_jurnal / $data_per_halaman);
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
if ($halaman_aktif > $total_halaman) $halaman_aktif = $total_halaman;
$offset = ($halaman_aktif - 1) * $data_per_halaman;

// Di sini kita tidak perlu memotong array karena paginasi akan dilakukan oleh JS

?>

<div class="container-fluid px-0">
    
    <div class="d-flex align-items-center mb-3 bg-white p-3 shadow-sm rounded">
        <a href="index.php?page=pembimbing/validasi_daftar_siswa" class="btn btn-light btn-sm me-3 text-secondary rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <small class="text-muted d-block">Validasi Jurnal</small>
            <h5 class="mb-0 fw-bold text-dark text-truncate"><?php echo htmlspecialchars($nama_siswa); ?></h5>
        </div>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-6">
            <a href="cetak_jurnal.php?id_siswa=<?php echo $id_siswa; ?>" target="_blank" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center h-100 py-2">
                <i class="fas fa-file-pdf me-2"></i> Cetak Jurnal
            </a>
        </div>
    </div>


    <h6 class="mb-3 fw-bold text-secondary border-bottom pb-2">
        <i class="fas fa-book-reader me-2"></i>Riwayat Jurnal
    </h6>

    <?php if(!empty($pesan_sukses_jurnal)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?php echo $pesan_sukses_jurnal; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(!empty($pesan_error_jurnal)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <?php echo $pesan_error_jurnal; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div id="jurnalListContainer">
        <?php foreach ($jurnal_list as $index => $jurnal): ?>
            <?php 
                $status = $jurnal['status_validasi'];
                $isPending = ($status == 'Pending');
                
                // Warna Border Kiri berdasarkan status (Indikator visual cepat)
                $borderClass = 'border-secondary'; // Default
                if($status == 'Disetujui') $borderClass = 'border-success';
                if($status == 'Ditolak') $borderClass = 'border-danger';
                if($status == 'Pending') $borderClass = 'border-warning';
            ?>
            
            <div class="card mb-3 shadow-sm border-0 border-start border-4 <?php echo $borderClass; ?> jurnal-item" data-index="<?php echo $index; ?>">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom-0">
                    <span class="fw-bold text-dark">
                        <i class="far fa-calendar-alt me-1 text-muted"></i> <?php echo date('d M Y', strtotime($jurnal['tanggal'])); ?>
                    </span>
                    <?php if (!$isPending): ?>
                        <span class="badge rounded-pill <?php echo ($status == 'Disetujui') ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $status; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-warning text-dark">Menunggu</span>
                    <?php endif; ?>
                </div>

                <div class="card-body pt-0 pb-3">
                    <p class="card-text mb-3 text-dark" style="white-space: pre-line;">
                        <?php echo htmlspecialchars($jurnal['kegiatan']); ?>
                    </p>
                    
                    <?php if (!empty($jurnal['foto_kegiatan'])): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-3" 
                                data-bs-toggle="modal" 
                                data-bs-target="#fotoModal" 
                                data-foto="assets/uploads/<?php echo htmlspecialchars($jurnal['foto_kegiatan']); ?>"
                                data-nama="Jurnal: <?php echo date('d M Y', strtotime($jurnal['tanggal'])); ?>">
                            <i class="fas fa-image me-1"></i> Lihat Foto Kegiatan
                        </button>
                    <?php endif; ?>

                    <div class="bg-light rounded p-2 mt-2">
                        <?php if ($isPending): ?>
                            <form action="index.php?page=pembimbing/validasi_jurnal_siswa&id_siswa=<?php echo $id_siswa; ?>" method="POST">
                                <input type="hidden" name="id_jurnal" value="<?php echo $jurnal['id_jurnal']; ?>">
                                
                                <div class="mb-2">
                                    <textarea name="catatan_pembimbing" rows="2" class="form-control form-control-sm border-0 shadow-none" placeholder="Tulis catatan untuk siswa (opsional)..."></textarea>
                                </div>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="submit" name="status_validasi" value="Disetujui" class="btn btn-success btn-sm w-100 fw-bold">
                                            <i class="fas fa-check me-1"></i> Setujui
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="submit" name="status_validasi" value="Ditolak" class="btn btn-danger btn-sm w-100 fw-bold">
                                            <i class="fas fa-times me-1"></i> Tolak
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="small">
                                <span class="fw-bold text-secondary">Catatan Anda:</span>
                                <p class="mb-0 text-muted fst-italic">
                                    "<?php echo htmlspecialchars(empty($jurnal['catatan_pembimbing']) ? '-' : $jurnal['catatan_pembimbing']); ?>"
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($jurnal_list)): ?>
            <div class="text-center py-5 text-muted" id="emptyJurnalMessage">
                <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                <p>Siswa belum mengisi jurnal harian.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_halaman > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Jurnal Paginasi">
            <ul class="pagination pagination-sm" id="jurnalPagination">
                </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Bukti: <span id="namaSiswaFoto"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body text-center bg-light" style="max-height: 80vh; overflow-y: auto;">
                <img id="imgPreview" src="" class="img-fluid rounded shadow-sm" alt="Bukti Foto" style="max-height: 70vh; width: auto;">
            </div>
            
            <div class="modal-footer">
                <a id="downloadLink" href="" download class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Download</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk handle Modal Foto
document.addEventListener('DOMContentLoaded', function() {
    var fotoModal = document.getElementById('fotoModal');
    fotoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var fotoUrl = button.getAttribute('data-foto');
        var nama = button.getAttribute('data-nama');

        var img = document.getElementById('imgPreview');
        var downloadBtn = document.getElementById('downloadLink');
        var title = document.getElementById('namaSiswaFoto');
        
        img.src = fotoUrl;
        downloadBtn.href = fotoUrl;
        title.textContent = nama;
    });
    
    // Reset src saat tutup biar hemat memori
    fotoModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('imgPreview').src = "";
    });
    
    // --- SCRIPT PAGINASI JURNAL (MENGGUNAKAN JAVASCRIPT) ---
    
    const itemsPerPage = <?php echo $data_per_halaman; ?>;
    const jurnalItems = document.querySelectorAll('.jurnal-item');
    const paginationContainer = document.getElementById('jurnalPagination');
    const totalItems = jurnalItems.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    let currentPage = 1;

    function displayItems(page) {
        // Jika tidak ada jurnal, jangan lakukan apa-apa
        if (totalItems === 0) return;

        currentPage = page;
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        jurnalItems.forEach((item, index) => {
            if (index >= start && index < end) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function setupPagination() {
        if (totalPages <= 1) return;

        paginationContainer.innerHTML = ''; // Kosongkan
        
        // Tombol Previous
        const prevItem = document.createElement('li');
        prevItem.classList.add('page-item');
        if (currentPage === 1) prevItem.classList.add('disabled');
        const prevLink = document.createElement('a');
        prevLink.classList.add('page-link');
        prevLink.href = '#';
        prevLink.innerHTML = '&laquo;';
        prevLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                displayItems(currentPage - 1);
                setupPagination();
                document.getElementById('jurnalListContainer').scrollIntoView({ behavior: 'smooth' });
            }
        });
        prevItem.appendChild(prevLink);
        paginationContainer.appendChild(prevItem);

        // Nomor Halaman
        for (let i = 1; i <= totalPages; i++) {
            const pageItem = document.createElement('li');
            pageItem.classList.add('page-item');
            if (i === currentPage) pageItem.classList.add('active');

            const pageLink = document.createElement('a');
            pageLink.classList.add('page-link');
            pageLink.href = '#';
            pageLink.textContent = i;
            pageLink.addEventListener('click', (e) => {
                e.preventDefault();
                displayItems(i);
                setupPagination();
                document.getElementById('jurnalListContainer').scrollIntoView({ behavior: 'smooth' });
            });

            pageItem.appendChild(pageLink);
            paginationContainer.appendChild(pageItem);
        }
        
        // Tombol Next
        const nextItem = document.createElement('li');
        nextItem.classList.add('page-item');
        if (currentPage === totalPages) nextItem.classList.add('disabled');
        const nextLink = document.createElement('a');
        nextLink.classList.add('page-link');
        nextLink.href = '#';
        nextLink.innerHTML = '&raquo;';
        nextLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < totalPages) {
                displayItems(currentPage + 1);
                setupPagination();
                document.getElementById('jurnalListContainer').scrollIntoView({ behavior: 'smooth' });
            }
        });
        nextItem.appendChild(nextLink);
        paginationContainer.appendChild(nextItem);
    }
    
    // Inisialisasi tampilan
    if (totalItems > 0) {
        displayItems(1); // Tampilkan halaman pertama saat dimuat
        setupPagination();
    }
});
</script>