<?php
// --- 1. PHP LOGIC ---
if (!isset($_SESSION['user_id'])) {
    die("Akses tidak sah!");
}

$user_id = $_SESSION['user_id'];
$id_ref = $_SESSION['id_ref']; 
$role = $_SESSION['role'];
$profil_data = []; 
$username = "";

// Modern Color Palette
$theme_color = '#6366f1'; // Indigo (Default)
$gradient = 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)';

if ($role == 'siswa') { 
    $theme_color = '#0ea5e9'; // Sky
    $gradient = 'linear-gradient(135deg, #0ea5e9 0%, #2dd4bf 100%)';
} elseif ($role == 'pembimbing') { 
    $theme_color = '#10b981'; // Emerald
    $gradient = 'linear-gradient(135deg, #10b981 0%, #3b82f6 100%)';
}

try {
    // Get Username
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $user['username'];

    // Get Role Data
    if ($role == 'siswa') {
        $stmt = $pdo->prepare("SELECT s.nis, s.nama_lengkap, s.jurusan, s.kelas, 
                       p.nama_perusahaan, p.alamat as alamat_perusahaan,
                       g.nama_guru, g.no_telp as telp_guru
                FROM siswa s
                LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                LEFT JOIN pembimbing g ON s.id_pembimbing = g.id_pembimbing
                WHERE s.id_siswa = :id");
        $stmt->execute(['id' => $id_ref]);
        $profil_data = $stmt->fetch(PDO::FETCH_ASSOC);

    } elseif ($role == 'pembimbing') {
        $stmt = $pdo->prepare("SELECT p.nip, p.nama_guru, p.no_telp,
                       (SELECT COUNT(*) FROM siswa WHERE id_pembimbing = p.id_pembimbing) as total_bimbingan,
                       (SELECT COUNT(DISTINCT id_perusahaan) FROM siswa WHERE id_pembimbing = p.id_pembimbing) as total_perusahaan
                FROM pembimbing p
                WHERE p.id_pembimbing = :id");
        $stmt->execute(['id' => $id_ref]);
        $profil_data = $stmt->fetch(PDO::FETCH_ASSOC);

    } elseif ($role == 'admin') {
        $profil_data = ['nama_lengkap' => 'Administrator'];
    }

} catch (PDOException $e) { }

// Display Name & Initials
$display_name = $username;
if ($role == 'siswa' && !empty($profil_data['nama_lengkap'])) $display_name = $profil_data['nama_lengkap'];
elseif ($role == 'pembimbing' && !empty($profil_data['nama_guru'])) $display_name = $profil_data['nama_guru'];
elseif ($role == 'admin') $display_name = "Administrator";

$initials = strtoupper(substr($display_name, 0, 1));
if (strpos($display_name, ' ') !== false) {
    $parts = explode(' ', $display_name);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}
?>

<style>
    :root {
        --profile-theme: <?php echo $theme_color; ?>;
        --profile-gradient: <?php echo $gradient; ?>;
    }

    .profile-wrapper {
        padding-bottom: 5rem;
    }

    /* Hero Section with Glassmorphism */
    .profile-hero {
        background: var(--profile-gradient);
        border-radius: 24px;
        padding: 3rem 1.5rem 5rem;
        position: relative;
        overflow: hidden;
        margin-bottom: -4rem;
        z-index: 1;
    }

    .profile-hero::before {
        content: '';
        position: absolute;
        top: -10%; right: -5%;
        width: 15rem; height: 15rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .profile-avatar-container {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto;
    }

    .profile-avatar-large {
        width: 100px;
        height: 100px;
        background: white;
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--profile-theme);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border: 4px solid rgba(255,255,255,0.3);
    }

    /* Content Card */
    .profile-content-card {
        background: #fff;
        border-radius: 30px;
        padding: 5rem 1.5rem 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative;
        z-index: 2;
    }

    .profile-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .profile-badge {
        display: inline-block;
        padding: 0.35rem 1rem;
        background: rgba(var(--profile-theme-rgb, 99, 102, 241), 0.1);
        color: var(--profile-theme);
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1.5rem;
    }

    /* Info Groups */
    .info-section {
        margin-bottom: 2rem;
    }

    .info-section-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
        padding-left: 0.5rem;
    }

    .info-item {
        background: #f8fafc;
        border-radius: 20px;
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        transition: transform 0.2s;
        border: 1px solid #f1f5f9;
    }

    .info-item:active {
        transform: scale(0.98);
    }

    .info-icon {
        width: 45px;
        height: 45px;
        background: #fff;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: var(--profile-theme);
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .info-label {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 0.1rem;
        display: block;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #334155;
        line-height: 1.2;
    }

    /* WhatsApp Button Fix */
    .btn-wa-sm {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 50% !important;
        padding: 0 !important;
    }

    /* Floating Quick Actions */
    .action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1rem;
    }

    .btn-profile-action {
        padding: 1rem;
        border-radius: 18px;
        font-weight: 700;
        font-size: 0.9rem;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .btn-profile-secondary {
        background: #f1f5f9;
        color: #475569;
    }

    .btn-profile-danger {
        background: #fef2f2;
        color: #ef4444;
    }

    .btn-profile-action i {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }

    @media (max-width: 576px) {
        .profile-content-card {
            margin-left: -1rem;
            margin-right: -1rem;
            border-radius: 30px 30px 0 0;
        }
    }
</style>

<div class="profile-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            
            <!-- Hero Header -->
            <div class="profile-hero shadow">
                <div class="profile-avatar-container">
                    <div class="profile-avatar-large">
                        <?php echo $initials; ?>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="profile-content-card text-center">
                <h2 class="profile-name"><?php echo htmlspecialchars($display_name); ?></h2>
                <div class="profile-badge">
                    <i class="fas fa-shield-alt me-1"></i> <?php echo ucfirst($role); ?>
                </div>

                <!-- Account Info -->
                <div class="info-section text-start">
                    <h6 class="info-section-title">Informasi Akun</h6>
                    
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-at"></i></div>
                        <div>
                            <span class="info-label">Username</span>
                            <span class="info-value"><?php echo htmlspecialchars($username); ?></span>
                        </div>
                    </div>

                    <?php if ($role == 'siswa'): ?>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-id-card"></i></div>
                            <div>
                                <span class="info-label">Nomor Induk Siswa</span>
                                <span class="info-value"><?php echo htmlspecialchars($profil_data['nis']); ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-graduation-cap"></i></div>
                            <div>
                                <span class="info-label">Kelas & Jurusan</span>
                                <span class="info-value"><?php echo htmlspecialchars($profil_data['kelas'] . " - " . $profil_data['jurusan']); ?></span>
                            </div>
                        </div>
                    <?php elseif ($role == 'pembimbing'): ?>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-id-badge"></i></div>
                            <div>
                                <span class="info-label">NIP</span>
                                <span class="info-value"><?php echo htmlspecialchars($profil_data['nip'] ?? '-'); ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                            <div>
                                <span class="info-label">Kontak</span>
                                <span class="info-value"><?php echo htmlspecialchars($profil_data['no_telp'] ?? '-'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Placement / Stats -->
                <?php if ($role == 'siswa'): ?>
                    <div class="info-section text-start">
                        <h6 class="info-section-title">Status Magang</h6>
                        
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-building"></i></div>
                            <div class="flex-grow-1">
                                <span class="info-label">Tempat Magang</span>
                                <span class="info-value <?php echo empty($profil_data['nama_perusahaan']) ? 'text-danger' : ''; ?>">
                                    <?php echo htmlspecialchars($profil_data['nama_perusahaan'] ?? 'Belum Penempatan'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="flex-grow-1">
                                <span class="info-label">Guru Pembimbing</span>
                                <span class="info-value <?php echo empty($profil_data['nama_guru']) ? 'text-danger' : ''; ?>">
                                    <?php echo htmlspecialchars($profil_data['nama_guru'] ?? 'Belum Ditentukan'); ?>
                                </span>
                            </div>
                            <?php if (!empty($profil_data['telp_guru'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $profil_data['telp_guru'])); ?>" 
                                   target="_blank" class="btn btn-success btn-wa-sm ms-2">
                                    <i class="fab fa-whatsapp fs-5"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($role == 'pembimbing'): ?>
                    <div class="info-section text-start">
                        <h6 class="info-section-title">Statistik Bimbingan</h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="info-item flex-column text-center align-items-center">
                                    <span class="info-label">Total Siswa</span>
                                    <span class="info-value fs-4"><?php echo $profil_data['total_bimbingan']; ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="info-item flex-column text-center align-items-center">
                                    <span class="info-label">Total DUDI</span>
                                    <span class="info-value fs-4"><?php echo $profil_data['total_perusahaan']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="action-grid">
                    <a href="index.php?page=ganti_password" class="btn-profile-action btn-profile-secondary">
                        <i class="fas fa-key"></i> Ganti Password
                    </a>
                    <a href="logout.php" class="btn-profile-action btn-profile-danger" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>