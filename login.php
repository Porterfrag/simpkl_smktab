<?php
session_start();
require 'config/koneksi.php';
require 'core/auto_alpha.php'; 

if (isset($_COOKIE['id']) && isset($_COOKIE['key'])) {
    $id = $_COOKIE['id'];
    $key = $_COOKIE['key'];

    try {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $key === hash('sha256', $user['username'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['id_ref'] = $user['id_ref'];

            if ($user['role'] != 'siswa') {
                jalankan_auto_alpha($pdo);
            }
        }
    } catch (PDOException $e) {
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); 

    try {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['id_ref'] = $user['id_ref']; 

            if ($user['role'] != 'siswa') {
                jalankan_auto_alpha($pdo);
            }

            if ($remember) {
                setcookie('id', $user['id'], time() + (60 * 60 * 24 * 7), '/');
                setcookie('key', hash('sha256', $user['username']), time() + (60 * 60 * 24 * 7), '/');
            }

            header("Location: index.php?page=dashboard");
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem PKL</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .form-control {
            background-color: #f3f4f6;
            border: none;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            background-color: #fff;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }
        .btn-login {
            background: linear-gradient(to right, #2563eb, #1e40af);
            border: none;
            padding: 0.75rem;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(to right, #1d4ed8, #1e3a8a);
        }
        .right-side-bg {
            background: linear-gradient(135deg, #2563eb 0%, #3730a3 100%);
        }
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>

    <div class="min-vh-100 d-flex align-items-center justify-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card login-card">
                        <div class="row g-0">
                            
                            <div class="col-md-6 bg-white p-5">
                                <div class="text-center mb-4">
                                    <img src="assets/images/logo-smk.png" alt="Logo Sekolah" style="width: 80px; height: auto;" class="mx-auto d-block">
                                </div>
                                <h2 class="h3 fw-bold text-dark text-center mb-5">SIMPKL SMKTAB</h2>

                                <form action="login.php" method="POST">
                                    <?php if(!empty($error)): ?>
                                        <div class="alert alert-danger border-danger text-danger fade show" role="alert">
                                            <?php echo htmlspecialchars($error); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-4">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control rounded-3" id="username" name="username" placeholder="Username" required>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="Password" required>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember-me" name="remember">
                                            <label class="form-check-label small text-secondary" for="remember-me">
                                                Ingat Saya
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-login text-white fw-bold rounded-3 shadow">
                                            Login
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-md-6 right-side-bg d-flex flex-column align-items-center justify-content-center p-5 text-white text-center">
                                <h2 class="display-6 fw-bold mb-3">Selamat Datang!</h2>
                                <p class="lead fs-6">Silakan login untuk mengakses SIMPKL SMKTAB.</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>