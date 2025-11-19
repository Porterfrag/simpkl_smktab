<?php

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];
$hari_ini = date('Y-m-d');
$pesan_sukses = '';
$pesan_error = '';


if (isset($_GET['aksi']) && $_GET['aksi'] == 'tandai_alpha' && isset($_GET['id_siswa'])) {
    $id_siswa_alpha = $_GET['id_siswa'];
    
    $sql_cek = "SELECT COUNT(*) FROM siswa WHERE id_siswa = :id_siswa AND id_pembimbing = :id_pembimbing";
    $stmt_cek = $pdo->prepare($sql_cek);
    $stmt_cek->execute([':id_siswa' => $id_siswa_alpha, ':id_pembimbing' => $id_pembimbing]);
    $is_miliknya = $stmt_cek->fetchColumn();

    if ($is_miliknya) {
        try {
            $sql_insert_alpha = "INSERT INTO absensi (id_siswa, tanggal, status, dicatat_oleh, id_pembimbing)
                                 VALUES (:id_siswa, :tanggal, 'Alpha', 'Pembimbing', :id_pembimbing)";
            $stmt_insert = $pdo->prepare($sql_insert_alpha);
            $stmt_insert->execute([
                ':id_siswa' => $id_siswa_alpha,
                ':tanggal' => $hari_ini,
                ':id_pembimbing' => $id_pembimbing
            ]);
            $pesan_sukses = "Siswa berhasil ditandai sebagai Alpha.";
            
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { 
                $pesan_error = "Gagal: Siswa sudah melakukan absensi (Hadir/Izin/Sakit).";
            } else {
                $pesan_error = "Gagal menandai Alpha: " . $e->getMessage();
            }
        }
    } else {
        $pesan_error = "Aksi tidak sah!";
    }
}


$rekap_absensi = [];
try {
    $sql_siswa = "SELECT 
                    siswa.id_siswa, siswa.nama_lengkap, siswa.nis,
                    pembimbing.nama_guru 
                  FROM siswa 
                  LEFT JOIN pembimbing ON siswa.id_pembimbing = pembimbing.id_pembimbing
                  WHERE siswa.id_pembimbing = :id_pembimbing 
                  ORDER BY siswa.nama_lengkap ASC";
    $stmt_siswa = $pdo->prepare($sql_siswa);
    $stmt_siswa->execute(['id_pembimbing' => $id_pembimbing]);
    $siswa_list = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_absensi = "SELECT id_siswa, status, jam_absen, keterangan, bukti_foto, latitude, longitude
                    FROM absensi 
                    WHERE tanggal = :tanggal";
    $stmt_absensi = $pdo->prepare($sql_absensi);
    $stmt_absensi->execute([':tanggal' => $hari_ini]);
    
    $absensi_hari_ini = [];
    while ($row = $stmt_absensi->fetch(PDO::FETCH_ASSOC)) {
        $absensi_hari_ini[$row['id_siswa']] = $row;
    }
    
    foreach ($siswa_list as $siswa) {
        $id_siswa = $siswa['id_siswa'];
        if (isset($absensi_hari_ini[$id_siswa])) {
            $siswa['status_absen'] = $absensi_hari_ini[$id_siswa]['status'];
            $siswa['jam_absen'] = $absensi_hari_ini[$id_siswa]['jam_absen'];
            $siswa['keterangan'] = $absensi_hari_ini[$id_siswa]['keterangan'];
            $siswa['bukti_foto'] = $absensi_hari_ini[$id_siswa]['bukti_foto'];
            $siswa['latitude'] = $absensi_hari_ini[$id_siswa]['latitude'];
            $siswa['longitude'] = $absensi_hari_ini[$id_siswa]['longitude'];
        } else {
            $siswa['status_absen'] = 'Belum Absen';
            $siswa['jam_absen'] = '-';
            $siswa['keterangan'] = '-';
            $siswa['bukti_foto'] = null;
            $siswa['latitude'] = null;
            $siswa['longitude'] = null;
        }
        $rekap_absensi[] = $siswa;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data: " . $e->getMessage() . "</div>";
}
?>

<h2 class="mb-4">Rekap Absensi Harian</h2>
<p class="mb-3">Halaman ini menampilkan status kehadiran siswa bimbingan Anda untuk hari ini, <strong><?php echo date('d F Y', strtotime($hari_ini)); ?></strong>.</p>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success" role="alert"><?php echo $pesan_sukses; ?></div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert"><?php echo $pesan_error; ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered <?php echo (!empty($rekap_absensi) ? 'datatable' : ''); ?>">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Siswa (NIS)</th>
                <th>Status Hari Ini</th>
                <th>Jam Absen</th>
                <th>Keterangan</th>
                <th>Bukti Foto</th> 
                <th>Lokasi GPS</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($rekap_absensi as $rekap): ?>
                <tr>
                    <td class="text-start"><?php echo $no++; ?></td>
                    <td class="text-start" style="min-width: 150px;">
                        <?php echo htmlspecialchars($rekap['nama_lengkap']); ?>
                        <br><small>(NIS: <?php echo htmlspecialchars($rekap['nis']); ?>)</small>
                    </td>
                    
                    <td class="text-start">
                        <?php
                            $status = $rekap['status_absen'];
                            $class_badge = 'text-black';
                            if ($status == 'Izin') $class_badge = 'bg-primary text-white';
                            if ($status == 'Sakit') $class_badge = 'bg-warning text-dark';
                            if ($status == 'Alpha') $class_badge = 'bg-danger text-white';
                            if ($status == 'Belum Absen') $class_badge = 'bg-secondary text-white';
                            if ($status == 'Hadir') $class_badge = 'bg-success text-white';
                        ?>
                        <span class="badge <?php echo $class_badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </td>

                    <td class="text-start"><?php echo htmlspecialchars($rekap['jam_absen']); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($rekap['keterangan']); ?></td>
                    
                    <td class="text-start" style="min-width: 80px;">
                        <?php if ($rekap['bukti_foto']): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#fotoModal" 
                                    data-foto="assets/uploads/<?php echo htmlspecialchars($rekap['bukti_foto']); ?>"
                                    data-nama="<?php echo htmlspecialchars($rekap['nama_lengkap']); ?>">
                                <i class="fas fa-image"></i> Lihat
                            </button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    
                    <td class="text-start" style="min-width: 80px;">
                        <?php if (!empty($rekap['latitude']) && !empty($rekap['longitude'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-info" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#mapModal" 
                                    data-lat="<?php echo $rekap['latitude']; ?>" 
                                    data-lon="<?php echo $rekap['longitude']; ?>"
                                    data-nama="<?php echo htmlspecialchars($rekap['nama_lengkap']); ?>">
                                <i class="fas fa-map-marker-alt"></i> Map
                            </button>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td class="text-start" style="min-width: 100px;">
                        <?php if ($rekap['status_absen'] == 'Belum Absen'): ?>
                            <a href="index.php?page=pembimbing/rekap_absensi_harian&aksi=tandai_alpha&id_siswa=<?php echo $rekap['id_siswa']; ?>" 
                               class="btn btn-sm btn-danger btn-alpha">
                               Tandai Alpha
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">(Sudah Terekam)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($rekap_absensi)): ?>
                <tr>
                    <td colspan="8" class="text-center">Anda tidak memiliki siswa bimbingan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lokasi: <span id="namaSiswaMap"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 60vh;">
                <iframe id="mapFrame" width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src=""></iframe>
            </div>
            <div class="modal-footer">
                <small id="koordinatText" class="me-auto text-muted"></small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Bukti: <span id="namaSiswaFoto"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-light">
                <img id="imgPreview" src="" class="img-fluid rounded shadow-sm" alt="Bukti Foto">
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
    
    // --- LOGIKA MODAL PETA ---
    var mapModal = document.getElementById('mapModal');
    mapModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; 
        var lat = button.getAttribute('data-lat');
        var lon = button.getAttribute('data-lon');
        var nama = button.getAttribute('data-nama');
        var mapUrl = "https://maps.google.com/maps?q=" + lat + "," + lon + "&z=17&ie=UTF8&iwloc=&output=embed";

        document.getElementById('mapFrame').src = mapUrl;
        document.getElementById('namaSiswaMap').textContent = nama;
        document.getElementById('koordinatText').textContent = "Koordinat: " + lat + ", " + lon;
    });
    mapModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('mapFrame').src = ""; // Reset iframe agar stop loading
    });

    // --- LOGIKA MODAL FOTO ---
    var fotoModal = document.getElementById('fotoModal');
    fotoModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var fotoUrl = button.getAttribute('data-foto'); // Ambil URL dari tombol
        var nama = button.getAttribute('data-nama');

        var img = document.getElementById('imgPreview');
        var downloadBtn = document.getElementById('downloadLink');
        
        // Set source gambar dan link download
        img.src = fotoUrl;
        downloadBtn.href = fotoUrl;
        
        document.getElementById('namaSiswaFoto').textContent = nama;
    });
    fotoModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('imgPreview').src = ""; // Reset gambar
    });

});
</script>