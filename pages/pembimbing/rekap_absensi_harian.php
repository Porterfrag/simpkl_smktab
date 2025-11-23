<?php
// --- BAGIAN LOGIKA PHP (SAMA DENGAN ASLINYA, DITAMBAH HITUNG STATISTIK) ---

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
// Variabel Statistik Harian
$stat_hadir = 0;
$stat_izin_sakit = 0;
$stat_alpha = 0;
$stat_belum = 0;

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
            $status = $absensi_hari_ini[$id_siswa]['status'];
            $siswa['status_absen'] = $status;
            $siswa['jam_absen'] = $absensi_hari_ini[$id_siswa]['jam_absen'];
            $siswa['keterangan'] = $absensi_hari_ini[$id_siswa]['keterangan'];
            $siswa['bukti_foto'] = $absensi_hari_ini[$id_siswa]['bukti_foto'];
            $siswa['latitude'] = $absensi_hari_ini[$id_siswa]['latitude'];
            $siswa['longitude'] = $absensi_hari_ini[$id_siswa]['longitude'];

            // Hitung Statistik
            if ($status == 'Hadir') $stat_hadir++;
            elseif ($status == 'Izin' || $status == 'Sakit') $stat_izin_sakit++;
            elseif ($status == 'Alpha') $stat_alpha++;

        } else {
            $siswa['status_absen'] = 'Belum Absen';
            $siswa['jam_absen'] = '-';
            $siswa['keterangan'] = '-';
            $siswa['bukti_foto'] = null;
            $siswa['latitude'] = null;
            $siswa['longitude'] = null;
            
            $stat_belum++;
        }
        $rekap_absensi[] = $siswa;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data: " . $e->getMessage() . "</div>";
}
?>

<!-- --- TAMPILAN MOBILE FOCUSED --- -->
<div class="container-fluid px-0">
    
    <!-- Header -->
    <div class="d-flex align-items-center mb-3 bg-white p-3 shadow-sm rounded">
        <div class="flex-grow-1">
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-calendar-day me-2"></i>Absensi Harian</h5>
            <small class="text-muted"><?php echo date('l, d F Y', strtotime($hari_ini)); ?></small>
        </div>
        <div>
            <button class="btn btn-light btn-sm rounded-circle" onclick="window.location.reload();" title="Refresh Data">
                <i class="fas fa-sync-alt text-secondary"></i>
            </button>
        </div>
    </div>

    <!-- Dashboard Statistik Mini -->
    <div class="row g-2 mb-4">
        <div class="col-3">
            <div class="p-2 text-center bg-success bg-opacity-10 border border-success rounded">
                <h5 class="mb-0 fw-bold text-success"><?php echo $stat_hadir; ?></h5>
                <small class="text-muted" style="font-size: 0.7rem;">Hadir</small>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 text-center bg-primary bg-opacity-10 border border-primary rounded">
                <h5 class="mb-0 fw-bold text-primary"><?php echo $stat_izin_sakit; ?></h5>
                <small class="text-muted" style="font-size: 0.7rem;">Izin/Skt</small>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 text-center bg-danger bg-opacity-10 border border-danger rounded">
                <h5 class="mb-0 fw-bold text-danger"><?php echo $stat_alpha; ?></h5>
                <small class="text-muted" style="font-size: 0.7rem;">Alpha</small>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 text-center bg-secondary bg-opacity-10 border border-secondary rounded">
                <h5 class="mb-0 fw-bold text-secondary"><?php echo $stat_belum; ?></h5>
                <small class="text-muted" style="font-size: 0.7rem;">Belum</small>
            </div>
        </div>
    </div>

    <?php if(!empty($pesan_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $pesan_sukses; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(!empty($pesan_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <?php echo $pesan_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search Filter Sederhana -->
    <div class="input-group shadow-sm mb-3">
        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
        <input type="text" id="mobileSearch" class="form-control border-start-0" placeholder="Cari nama siswa...">
    </div>

    <!-- List Absensi Card -->
    <div id="absensiList">
        <?php foreach ($rekap_absensi as $rekap): ?>
            <?php
                $status = $rekap['status_absen'];
                
                // Logic Warna & Icon
                $borderClass = 'border-secondary';
                $badgeClass = 'bg-secondary';
                $bgCard = 'bg-white';
                $iconStatus = 'fa-minus';

                if ($status == 'Hadir') {
                    $borderClass = 'border-success';
                    $badgeClass = 'bg-success';
                    $bgCard = 'bg-success bg-opacity-10'; // Sedikit tint hijau
                    $iconStatus = 'fa-check';
                } elseif ($status == 'Izin') {
                    $borderClass = 'border-primary';
                    $badgeClass = 'bg-primary';
                    $iconStatus = 'fa-envelope';
                } elseif ($status == 'Sakit') {
                    $borderClass = 'border-warning';
                    $badgeClass = 'bg-warning text-dark';
                    $iconStatus = 'fa-medkit';
                } elseif ($status == 'Alpha') {
                    $borderClass = 'border-danger';
                    $badgeClass = 'bg-danger';
                    $iconStatus = 'fa-times';
                } elseif ($status == 'Belum Absen') {
                    $borderClass = 'border-light'; // Netral
                    $bgCard = 'bg-light'; 
                }
            ?>

            <div class="card mb-3 shadow-sm border-0 border-start border-4 <?php echo $borderClass; ?> student-item">
                <div class="card-body p-3">
                    <!-- Baris Atas: Nama & Badge Status -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0 student-name"><?php echo htmlspecialchars($rekap['nama_lengkap']); ?></h6>
                            <small class="text-muted">NIS: <?php echo htmlspecialchars($rekap['nis']); ?></small>
                        </div>
                        <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                            <i class="fas <?php echo $iconStatus; ?> me-1"></i> <?php echo $status; ?>
                        </span>
                    </div>

                    <!-- Baris Tengah: Informasi Absen -->
                    <div class="row g-2 mb-3 small text-secondary">
                        <div class="col-6">
                            <i class="far fa-clock me-1"></i> <?php echo htmlspecialchars($rekap['jam_absen']); ?>
                        </div>
                        <div class="col-6 text-truncate">
                            <i class="far fa-comment-dots me-1"></i> <?php echo htmlspecialchars($rekap['keterangan']); ?>
                        </div>
                    </div>

                    <!-- Baris Bawah: Tombol Aksi -->
                    <div class="d-flex gap-2">
                        <?php if ($status == 'Belum Absen'): ?>
                            <!-- Tombol Tandai Alpha -->
                            <a href="index.php?page=pembimbing/rekap_absensi_harian&aksi=tandai_alpha&id_siswa=<?php echo $rekap['id_siswa']; ?>" 
                               class="btn btn-outline-danger btn-sm w-100" 
                               onclick="return confirm('Yakin ingin menandai siswa ini sebagai Alpha?');">
                                <i class="fas fa-user-times me-1"></i> Tandai Alpha
                            </a>
                        <?php else: ?>
                            <!-- Tombol Lihat Foto & Lokasi -->
                            <?php if ($rekap['bukti_foto']): ?>
                                <button class="btn btn-primary btn-sm flex-grow-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#fotoModal" 
                                        data-foto="assets/uploads/<?php echo htmlspecialchars($rekap['bukti_foto']); ?>"
                                        data-nama="<?php echo htmlspecialchars($rekap['nama_lengkap']); ?>">
                                    <i class="fas fa-camera"></i> Foto
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm flex-grow-1" disabled><i class="fas fa-camera"></i> No Foto</button>
                            <?php endif; ?>

                            <?php if (!empty($rekap['latitude'])): ?>
                                <button class="btn btn-info btn-sm flex-grow-1 text-white"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#mapModal" 
                                        data-lat="<?php echo $rekap['latitude']; ?>" 
                                        data-lon="<?php echo $rekap['longitude']; ?>"
                                        data-nama="<?php echo htmlspecialchars($rekap['nama_lengkap']); ?>">
                                    <i class="fas fa-map-marker-alt"></i> Lokasi
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm flex-grow-1" disabled><i class="fas fa-map-marker-alt"></i> No Lokasi</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($rekap_absensi)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted">Belum ada siswa yang terdaftar.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- MODAL MAP -->
<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title text-truncate" id="namaSiswaMap">Lokasi Siswa</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 60vh;">
                <iframe id="mapFrame" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
            <div class="modal-footer py-1 bg-light">
                <small id="koordinatText" class="text-muted me-auto font-monospace"></small>
                <a id="gmapsLink" href="#" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i> Buka GMap
                </a>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FOTO -->
<div class="modal fade" id="fotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> 
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="namaSiswaFoto">Foto Bukti</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="imgPreview" src="" class="img-fluid rounded" alt="Bukti Foto" style="max-height: 75vh; width: 100%; object-fit: contain; background: #f8f9fa;">
            </div>
        </div>
    </div>
</div>


<script>
// Search Filter Script
document.getElementById('mobileSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const items = document.querySelectorAll('.student-item');
    
    items.forEach(item => {
        const name = item.querySelector('.student-name').textContent.toLowerCase();
        if (name.includes(filter)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// Logic Modal Map
var mapModal = document.getElementById('mapModal');
mapModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget; 
    var lat = button.getAttribute('data-lat');
    var lon = button.getAttribute('data-lon');
    var nama = button.getAttribute('data-nama');
    
    // Menggunakan Google Maps Embed API
    var mapUrl = "https://maps.google.com/maps?q=" + lat + "," + lon + "&z=17&ie=UTF8&iwloc=&output=embed";
    // Link untuk membuka aplikasi Google Maps langsung
    var gmapsUrl = "https://www.google.com/maps/search/?api=1&query=" + lat + "," + lon;

    document.getElementById('mapFrame').src = mapUrl;
    document.getElementById('namaSiswaMap').textContent = "Lokasi: " + nama;
    document.getElementById('koordinatText').textContent = lat + ", " + lon;
    document.getElementById('gmapsLink').href = gmapsUrl;
});

// Logic Modal Foto
var fotoModal = document.getElementById('fotoModal');
fotoModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var fotoUrl = button.getAttribute('data-foto');
    var nama = button.getAttribute('data-nama');

    document.getElementById('imgPreview').src = fotoUrl;
    document.getElementById('namaSiswaFoto').textContent = nama;
});

// Reset Source saat modal tutup
mapModal.addEventListener('hidden.bs.modal', function () { document.getElementById('mapFrame').src = ""; });
fotoModal.addEventListener('hidden.bs.modal', function () { document.getElementById('imgPreview').src = ""; });
</script>