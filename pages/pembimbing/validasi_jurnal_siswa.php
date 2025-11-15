<?php

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
?>

<a href="index.php?page=pembimbing/validasi_daftar_siswa" class="btn btn-sm btn-secondary mb-3">&larr; Kembali ke Daftar Siswa</a>
<h2 class="mb-4">Data Siswa: <?php echo htmlspecialchars($nama_siswa); ?></h2>

<div class="d-flex flex-wrap mb-4">
    <a href="cetak_jurnal.php?id_siswa=<?php echo $id_siswa; ?>" 
       target="_blank" 
       class="btn btn-danger me-2">
       Cetak Jurnal ke PDF
    </a>
    <a href="cetak_nilai.php?id_siswa=<?php echo $id_siswa; ?>" 
       target="_blank" 
       class="btn btn-primary">
       Cetak Rekap Nilai
    </a>
</div>

<hr>
<h3 class="mb-3">Input Nilai Akhir PKL</h3>

<?php if(!empty($pesan_sukses_nilai)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $pesan_sukses_nilai; ?>
    </div>
<?php endif; ?>
<?php if(!empty($pesan_error_nilai)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error_nilai; ?>
    </div>
<?php endif; ?>

<?php if ($is_grading_phase): ?>
    
    <form action="index.php?page=pembimbing/validasi_jurnal_siswa&id_siswa=<?php echo $id_siswa; ?>" method="POST">
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="aspek_disiplin" class="form-label">1. Disiplin & Kehadiran (0-100)</label>
                <input type="number" class="form-control" id="aspek_disiplin" name="aspek_disiplin" min="0" max="100" 
                       value="<?php echo htmlspecialchars(isset($nilai['aspek_disiplin']) ? $nilai['aspek_disiplin'] : 0); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="aspek_kompetensi" class="form-label">2. Kompetensi Teknis (0-100)</label>
                <input type="number" class="form-control" id="aspek_kompetensi" name="aspek_kompetensi" min="0" max="100" 
                       value="<?php echo htmlspecialchars(isset($nilai['aspek_kompetensi']) ? $nilai['aspek_kompetensi'] : 0); ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="aspek_kerjasama" class="form-label">3. Kerjasama (Teamwork) (0-100)</label>
                <input type="number" class="form-control" id="aspek_kerjasama" name="aspek_kerjasama" min="0" max="100" 
                       value="<?php echo htmlspecialchars(isset($nilai['aspek_kerjasama']) ? $nilai['aspek_kerjasama'] : 0); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="aspek_inisiatif" class="form-label">4. Inisiatif & Kreativitas (0-100)</label>
                <input type="number" class="form-control" id="aspek_inisiatif" name="aspek_inisiatif" min="0" max="100" 
                       value="<?php echo htmlspecialchars(isset($nilai['aspek_inisiatif']) ? $nilai['aspek_inisiatif'] : 0); ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label for="catatan_penilaian" class="form-label">Catatan Tambahan</label>
            <textarea class="form-control" id="catatan_penilaian" name="catatan_penilaian" rows="4"><?php echo htmlspecialchars(isset($nilai['catatan_penilaian']) ? $nilai['catatan_penilaian'] : ''); ?></textarea>
        </div>
        
        <button type="submit" name="simpan_nilai" class="btn btn-success">Simpan Nilai Akhir</button>
    </form>

<?php else: ?>
    <div class="alert alert-info" role="alert">
        <p class="mb-0"><strong>Fase Penilaian Belum Dibuka.</strong> Modul input nilai akan muncul pada tanggal <strong><?php echo date('d F Y', strtotime($grading_start_date)); ?></strong>.</p>
    </div>
<?php endif; ?>

<hr style="margin-top: 30px;">

<h3 class="mb-3">Validasi Jurnal Harian</h3>

<?php if(!empty($pesan_sukses_jurnal)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $pesan_sukses_jurnal; ?>
    </div>
<?php endif; ?>
<?php if(!empty($pesan_error_jurnal)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error_jurnal; ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <table id="jurnalTable" class="table table-striped table-hover table-bordered <?php echo (!empty($jurnal_list) ? 'datatable' : ''); ?>">
        <thead class="table-light">
            <tr>
                <th>Tanggal</th>
                <th>Kegiatan</th>
                <th>Foto</th>
                <th style="min-width: 150px;">Status / Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jurnal_list as $jurnal): ?>
                <tr>
                    <td class="text-start"><?php echo date('d M Y', strtotime($jurnal['tanggal'])); ?></td>

                    <td class="text-start"><?php echo nl2br(htmlspecialchars($jurnal['kegiatan'])); ?></td>
                    
                    <td class="text-start">
                        <?php if (!empty($jurnal['foto_kegiatan'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#fotoModal" 
                                    data-foto="assets/uploads/<?php echo htmlspecialchars($jurnal['foto_kegiatan']); ?>"
                                    data-nama="Bukti Kegiatan: <?php echo htmlspecialchars($jurnal['tanggal']); ?>">
                                <i class="fas fa-image"></i> Lihat
                            </button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    
                    <td>
                        <?php if ($jurnal['status_validasi'] == 'Pending'): ?>
                            <form action="index.php?page=pembimbing/validasi_jurnal_siswa&id_siswa=<?php echo $id_siswa; ?>" method="POST" class="d-flex flex-column">
                                <input type="hidden" name="id_jurnal" value="<?php echo $jurnal['id_jurnal']; ?>">
                                <textarea name="catatan_pembimbing" rows="2" class="form-control mb-2" placeholder="Beri catatan..."></textarea>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="status_validasi" value="Disetujui" class="btn btn-sm btn-success w-50 me-1">Setujui</button>
                                    <button type="submit" name="status_validasi" value="Ditolak" class="btn btn-sm btn-danger w-50">Tolak</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <?php 
                                $status = $jurnal['status_validasi'];
                                $class_badge = ($status == 'Disetujui') ? 'bg-success' : 'bg-danger';
                            ?>
                            <span class="badge <?php echo $class_badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                            <p class="mt-2" style="font-size: 0.9em; border-left: 3px solid #ccc; padding-left: 5px;">
                                <strong>Catatan:</strong> <?php echo nl2br(htmlspecialchars(isset($jurnal['catatan_pembimbing']) ? $jurnal['catatan_pembimbing'] : 'Tidak ada catatan.')); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($jurnal_list)): ?>
                <tr>
                    <td colspan="4" class="text-center">Siswa ini belum mengisi jurnal harian.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="namaSiswaFoto">Detail Foto</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-light">
                <img id="imgPreview" src="" class="img-fluid rounded shadow-sm" alt="Bukti Foto" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
                <a id="downloadLink" href="" download class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Download</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
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
    
    fotoModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('imgPreview').src = "";
    });
});
</script>