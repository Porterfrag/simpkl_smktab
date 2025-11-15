<?php

if (!isset($_SESSION['user_id'])) {
    die("Akses tidak sah! Silakan login terlebih dahulu.");
}
$user_id = $_SESSION['user_id'];

$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $password_lama = filter_input(INPUT_POST, 'password_lama', FILTER_SANITIZE_STRING);
    $password_baru = filter_input(INPUT_POST, 'password_baru', FILTER_SANITIZE_STRING);
    $konfirmasi_password = filter_input(INPUT_POST, 'konfirmasi_password', FILTER_SANITIZE_STRING);

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $pesan_error = "Semua kolom wajib diisi!";
    } elseif ($password_baru != $konfirmasi_password) {
        $pesan_error = "Password Baru dan Konfirmasi Password tidak cocok!";

    } elseif ($password_baru == $password_lama) {
        $pesan_error = "Password Baru tidak boleh sama dengan Password Lama!";

    } elseif (strlen($password_baru) < 6) {
        $pesan_error = "Password baru minimal harus 6 karakter!";

    } else {
        try {
            $sql_cek = "SELECT password FROM users WHERE id = :id";
            $stmt_cek = $pdo->prepare($sql_cek);
            $stmt_cek->execute(['id' => $user_id]);
            $user = $stmt_cek->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $pesan_error = "Data pengguna tidak ditemukan.";
            } else {
                $password_lama_db = $user['password'];
                
                if (password_verify($password_lama, $password_lama_db)) {
                    
                    $password_baru_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                    
                    $sql_update = "UPDATE users SET password = :password_baru WHERE id = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->execute(['password_baru' => $password_baru_hash, 'id' => $user_id]);
                    
                    $pesan_sukses = "Password Anda telah **berhasil diperbarui!** (Menggunakan standar keamanan baru).";
                    
                } else {
                    $pesan_error = "Verifikasi Gagal: Password Lama yang Anda masukkan salah!";
                }
            }
            
        } catch (PDOException $e) {
            $pesan_error = "Terjadi kesalahan database. Silakan coba lagi nanti.";
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">⚙️ Ganti Kata Sandi</h5>
                </div>
                <div class="card-body p-4">
                    
                    <?php if(!empty($pesan_sukses)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Berhasil!</strong> <?php echo $pesan_sukses; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($pesan_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Gagal!</strong> <?php echo $pesan_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?page=ganti_password" method="POST" class="needs-validation" novalidate>
                        
                        <div class="mb-3">
                            <label for="password_lama" class="form-label">Password Lama</label>
                            <input type="password" class="form-control" id="password_lama" name="password_lama" required autocomplete="current-password">
                            <div class="invalid-feedback">
                                Password lama wajib diisi.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password_baru" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="password_baru" name="password_baru" required minlength="6" autocomplete="new-password">
                            <div class="form-text text-muted">Minimal 6 karakter.</div>
                            <div class="invalid-feedback">
                                Password baru harus diisi dan minimal 6 karakter.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required minlength="6" autocomplete="new-password">
                            <div class="invalid-feedback">
                                Konfirmasi password wajib diisi.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-key me-2"></i> Update Password
                        </button>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>