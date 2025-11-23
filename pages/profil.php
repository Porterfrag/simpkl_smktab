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

// Dynamic Colors based on Role
$theme_color = 'primary'; 
$bg_gradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; // Default/Admin (Deep Purple)

if ($role == 'siswa') { 
    $theme_color = 'primary';
    $bg_gradient = 'linear-gradient(135deg, #0093E9 0%, #80D0C7 100%)'; // Siswa (Cyan/Blue)
} elseif ($role == 'pembimbing') { 
    $theme_color = 'success'; 
    $bg_gradient = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)'; // Pembimbing (Green/Teal)
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

} catch (PDOException $e) { echo "Error: " . $e->getMessage(); }

// Display Name Helper
$display_name = $username;
if ($role == 'siswa' && !empty($profil_data['nama_lengkap'])) $display_name = $profil_data['nama_lengkap'];
elseif ($role == 'pembimbing' && !empty($profil_data['nama_guru'])) $display_name = $profil_data['nama_guru'];
elseif ($role == 'admin') $display_name = "Administrator";
?>

<style>
    body {
        background-color: #f0f2f5;
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    
    /* Card Container */
    .profile-card {
        background: white;
        border-radius: 24px;
        border: none;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        position: relative;
        margin-bottom: 30px;
    }

    /* Header with Gradient */
    .profile-cover {
        height: 150px;
        background: <?php echo $bg_gradient; ?>;
        position: relative;
    }

    /* Avatar Floating over Header */
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 5px solid white;
        background: white;
        position: absolute;
        bottom: -60px;
        left: 50%;
        transform: translateX(-50%);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    /* Text Styling */
    .profile-info {
        padding-top: 70px; /* Space for avatar */
        text-align: center;
        padding-bottom: 25px;
    }
    .user-name { font-size: 1.5rem; font-weight: 800; color: #2d3748; margin-bottom: 5px; }
    .user-role { 
        text-transform: uppercase; 
        font-size: 0.75rem; 
        font-weight: 700; 
        letter-spacing: 1.5px;
        color: #718096;
    }

    /* Information Grids */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        padding: 0 20px 25px 20px;
    }
    
    /* Individual Data Box */
    .data-box {
        background: #f8fafc;
        border-radius: 16px;
        padding: 20px 15px;
        text-align: center;
        border: 1px solid #edf2f7;
    }
    .data-box i { font-size: 1.5rem; margin-bottom: 10px; opacity: 0.8; }
    .data-label { font-size: 0.75rem; color: #a0aec0; font-weight: 600; text-transform: uppercase; display: block; }
    .data-value { font-size: 1rem; color: #2d3748; font-weight: 700; line-height: 1.2; margin-top: 5px; }

    /* Wide Box (Full Width) Configuration */
    .data-box.wide { 
        grid-column: span 2; 
        display: flex; 
        align-items: center; 
        justify-content: space-between; /* Pushes content to edges */
        text-align: left; 
        padding: 20px;
    }
    .data-box.wide i.main-icon { 
        margin-bottom: 0; 
        margin-right: 15px; 
        font-size: 2rem; 
    }
    .data-box.wide .content-left {
        display: flex;
        align-items: center;
        flex-grow: 1; /* Takes up available space */
    }
    
    /* Action Buttons Area */
    .action-area {
        background: #fafbfc;
        padding: 20px;
        border-top: 1px solid #edf2f7;
    }
    .btn-custom-outline {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        color: #4a5568;
        font-weight: 600;
        padding: 10px;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        text-decoration: none;
    }
    .btn-custom-outline:hover { background: #edf2f7; color: #2d3748; }
    
    .btn-custom-danger {
        background: #fff5f5;
        border: 2px solid #fed7d7;
        color: #c53030;
        border-radius: 12px;
        font-weight: 600;
        padding: 10px;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        text-decoration: none;
    }
    .btn-custom-danger:hover { background: #ffe3e3; }
</style>

<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8 col-sm-12">
        
        <div class="profile-card">
            <div class="profile-cover">
                <div class="profile-avatar">
                    <i class="fas fa-user fa-4x text-<?php echo $theme_color; ?>"></i>
                </div>
            </div>

            <div class="profile-info">
                <h2 class="user-name"><?php echo htmlspecialchars($display_name); ?></h2>
                <div class="user-role"><?php echo ucfirst($role); ?></div>
                
                <?php if ($role == 'siswa'): ?>
                    <div class="mt-2 badge bg-light text-dark border px-3 py-2 rounded-pill">
                        <?php echo htmlspecialchars($profil_data['kelas'] . " • " . $profil_data['jurusan']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($role == 'siswa'): ?>
            <div class="info-grid">
                
                <div class="data-box">
                    <i class="fas fa-id-card text-primary"></i>
                    <span class="data-label">NIS</span>
                    <div class="data-value"><?php echo htmlspecialchars($profil_data['nis']); ?></div>
                </div>

                <div class="data-box">
                    <i class="fas fa-user-circle text-info"></i>
                    <span class="data-label">Username</span>
                    <div class="data-value"><?php echo htmlspecialchars($username); ?></div>
                </div>

                <div class="data-box wide">
                    <div class="content-left">
                        <i class="fas fa-building text-warning main-icon"></i>
                        <div>
                            <span class="data-label">Tempat Magang</span>
                            <?php if (!empty($profil_data['nama_perusahaan'])): ?>
                                <div class="data-value"><?php echo htmlspecialchars($profil_data['nama_perusahaan']); ?></div>
                                <small class="text-muted d-block mt-1" style="font-size: 11px; line-height: 1.2;">
                                    <?php echo htmlspecialchars($profil_data['alamat_perusahaan']); ?>
                                </small>
                            <?php else: ?>
                                <div class="text-danger fw-bold small">Belum Penempatan</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="data-box wide">
                    <div class="content-left">
                        <i class="fas fa-chalkboard-teacher text-success main-icon"></i>
                        <div>
                            <span class="data-label">Pembimbing</span>
                            <?php if (!empty($profil_data['nama_guru'])): ?>
                                <div class="data-value"><?php echo htmlspecialchars($profil_data['nama_guru']); ?></div>
                            <?php else: ?>
                                <div class="text-danger fw-bold small">Belum Ditentukan</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($profil_data['nama_guru']) && !empty($profil_data['telp_guru'])): ?>
                        <div class="ms-2">
                            <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $profil_data['telp_guru'])); ?>" 
                               target="_blank" 
                               class="btn btn-success rounded-pill d-flex align-items-center shadow-sm px-3 py-2">
                                <i class="fab fa-whatsapp fa-lg me-1 mt-2"></i> Chat
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
            <?php endif; ?>


            <?php if ($role == 'pembimbing'): ?>
            <div class="info-grid">
                <div class="data-box">
                    <i class="fas fa-user-graduate text-primary"></i>
                    <span class="data-label">Siswa</span>
                    <div class="data-value fs-3"><?php echo $profil_data['total_bimbingan']; ?></div>
                </div>

                <div class="data-box">
                    <i class="fas fa-industry text-warning"></i>
                    <span class="data-label">DUDI</span>
                    <div class="data-value fs-3"><?php echo $profil_data['total_perusahaan']; ?></div>
                </div>

                <div class="data-box wide">
                    <div class="content-left">
                        <i class="fas fa-id-badge text-secondary main-icon"></i>
                        <div>
                            <span class="data-label">Nomor Induk Pegawai</span>
                            <div class="data-value"><?php echo htmlspecialchars($profil_data['nip']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>


            <div class="action-area">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="index.php?page=ganti_password" class="btn btn-custom-outline">
                            <i class="fas fa-key me-2"></i> Ganti Password
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="logout.php" class="btn btn-custom-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>