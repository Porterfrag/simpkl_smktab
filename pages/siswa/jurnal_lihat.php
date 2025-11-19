<?php
// (Pastikan file ini hanya di-include oleh index.php)
if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') { die("Akses tidak sah!"); }
$id_siswa = $_SESSION['id_ref'];

// Ambil data jurnal
try {
    $sql = "SELECT * FROM jurnal_harian WHERE id_siswa = :id_siswa ORDER BY tanggal DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_siswa' => $id_siswa]);
    $jurnal_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { echo "Error: " . $e->getMessage(); }

// Helper function untuk badge status
function getStatusBadge($status) {
    if ($status == 'Disetujui') return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Disetujui</span>';
    if ($status == 'Ditolak') return '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Ditolak</span>';
    return '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Riwayat Jurnal</h4>
    <a href="index.php?page=siswa/jurnal_isi" class="btn btn-success btn-sm rounded-pill shadow-sm">
        <i class="fas fa-plus me-1"></i> Tulis Jurnal
    </a>
</div>

<?php if (empty($jurnal_list)): ?>
    <div class="alert alert-light text-center border-0 shadow-sm py-5">
        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
        <p class="text-muted">Belum ada jurnal kegiatan yang diisi.</p>
        <a href="index.php?page=siswa/jurnal_isi" class="btn btn-primary btn-sm mt-2">Mulai Mengisi</a>
    </div>
<?php else: ?>

    <div class="d-none d-md-block table-responsive shadow-sm rounded-3 bg-white">
        <table class="table table-hover align-middle mb-0 datatable">
            <thead class="bg-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Kegiatan</th>
                    <th>Foto</th>
                    <th>Status</th>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jurnal_list as $jurnal): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?php echo date('d M Y', strtotime($jurnal['tanggal'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($jurnal['kegiatan'], 0, 50))) . (strlen($jurnal['kegiatan']) > 50 ? '...' : ''); ?></td>
                        <td>
                            <?php if ($jurnal['foto_kegiatan']): ?>
                                <button class="btn btn-sm btn-outline-primary rounded-pill" 
                                        data-bs-toggle="modal" data-bs-target="#fotoModal" 
                                        data-foto="assets/uploads/<?php echo $jurnal['foto_kegiatan']; ?>"
                                        data-ket="<?php echo htmlspecialchars($jurnal['kegiatan']); ?>">
                                    <i class="fas fa-image"></i>
                                </button>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td><?php echo getStatusBadge($jurnal['status_validasi']); ?></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($jurnal['catatan_pembimbing'] ?? '-'); ?></td>
                        <td>
                            <?php if ($jurnal['status_validasi'] == 'Pending'): ?>
                                <a href="index.php?page=siswa/jurnal_hapus&id=<?php echo $jurnal['id_jurnal']; ?>" 
   class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-hapus"> <i class="fas fa-trash me-1"></i> Hapus
</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-block d-md-none" id="mobileListContainer">
        <?php foreach ($jurnal_list as $jurnal): ?>
            <div class="card border-0 shadow-sm mb-3 rounded-3 mobile-item">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted small"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d F Y', strtotime($jurnal['tanggal'])); ?></span>
                        </div>
                        <div><?php echo getStatusBadge($jurnal['status_validasi']); ?></div>
                    </div>
                    
                    <p class="card-text text-dark mb-2" style="font-size: 0.95rem;">
                        <?php echo nl2br(htmlspecialchars($jurnal['kegiatan'])); ?>
                    </p>
                    
                    <?php if (!empty($jurnal['catatan_pembimbing'])): ?>
                        <div class="bg-light p-2 rounded border-start border-3 border-info mb-2">
                            <small class="d-block text-info fw-bold">Catatan Pembimbing:</small>
                            <small class="text-muted"><?php echo htmlspecialchars($jurnal['catatan_pembimbing']); ?></small>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <div>
                            <?php if ($jurnal['foto_kegiatan']): ?>
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" 
                                        data-bs-toggle="modal" data-bs-target="#fotoModal" 
                                        data-foto="assets/uploads/<?php echo $jurnal['foto_kegiatan']; ?>"
                                        data-ket="<?php echo htmlspecialchars($jurnal['kegiatan']); ?>">
                                    <i class="fas fa-image me-1"></i> Foto
                                </button>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($jurnal['status_validasi'] == 'Pending'): ?>
                               <a href="index.php?page=siswa/jurnal_hapus&id=<?php echo $jurnal['id_jurnal']; ?>" 
   class="btn btn-sm btn-danger rounded-circle btn-hapus" title="Hapus">
   <i class="fas fa-trash"></i>
</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <nav aria-label="Mobile pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="mobilePagination">
                </ul>
        </nav>
    </div>

<?php endif; ?>

<div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-truncate" id="modalTitle" style="max-width: 80%;">Detail Kegiatan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0 bg-light">
                <img id="imgPreview" src="" class="img-fluid" alt="Bukti Foto" style="max-height: 80vh;">
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. MODAL FOTO LOGIC
    var fotoModal = document.getElementById('fotoModal');
    fotoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var fotoUrl = button.getAttribute('data-foto');
        var ket = button.getAttribute('data-ket');
        
        document.getElementById('imgPreview').src = fotoUrl;
        document.getElementById('modalTitle').textContent = ket.length > 30 ? ket.substring(0, 30) + '...' : ket;
    });

    // 2. PAGINASI MOBILE LOGIC (Simple JS Pagination)
    const itemsPerPage = 5; // Tampilkan 5 kartu per halaman
    const items = document.querySelectorAll('.mobile-item');
    const paginationContainer = document.getElementById('mobilePagination');
    
    if(items.length > 0 && paginationContainer) {
        const totalPages = Math.ceil(items.length / itemsPerPage);
        let currentPage = 1;

        function showPage(page) {
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;

            items.forEach((item, index) => {
                item.style.display = (index >= start && index < end) ? 'block' : 'none';
            });
            renderPagination();
        }

        function renderPagination() {
            paginationContainer.innerHTML = '';
            
            // Prev Button
            const prevClass = currentPage === 1 ? 'disabled' : '';
            paginationContainer.innerHTML += `
                <li class="page-item ${prevClass}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">&laquo;</a>
                </li>`;

            // Page Numbers
            for (let i = 1; i <= totalPages; i++) {
                const activeClass = i === currentPage ? 'active' : '';
                paginationContainer.innerHTML += `
                    <li class="page-item ${activeClass}">
                        <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                    </li>`;
            }

            // Next Button
            const nextClass = currentPage === totalPages ? 'disabled' : '';
            paginationContainer.innerHTML += `
                <li class="page-item ${nextClass}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">&raquo;</a>
                </li>`;
        }

        // Expose function to global scope to be called by onclick
        window.changePage = function(page) {
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            showPage(page);
            // Scroll ke atas list sedikit agar nyaman
            document.getElementById('mobileListContainer').scrollIntoView({ behavior: 'smooth' });
        }

        // Init
        showPage(1);
    }
});
</script>