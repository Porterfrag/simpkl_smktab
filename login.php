<?php
session_start();
require 'config/koneksi.php';
require 'core/auto_alpha.php'; 

// --- 1. LOGIKA CEK COOKIE (AUTO LOGIN) ---
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
    } catch (PDOException $e) {}
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

// --- 2. LOGIKA LOGIN SUBMIT ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); 

    try {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // VERIFIKASI PASSWORD (BCRYPT)
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            /* --- NEW: Background Pattern --- */
            background-color: #f5f7fa; 
            background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="w-full max-w-4xl p-4">
        <div class="bg-white rounded-2xl shadow-xl flex overflow-hidden">

            <div class="w-full md:w-1/2 p-8 md:p-12">
                <div class="text-center mb-6">
                    <img src="assets/images/logo-smk.png" alt="Logo Sekolah" class="w-20 h-auto mx-auto mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Login SIPKL SMKTAB</h2>
                    <p class="text-gray-500 text-sm mt-1">Masukkan kredensial akun Anda</p>
                </div>

                <form action="login.php" method="POST">
                    <?php if(!empty($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded mb-6 text-sm" role="alert">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="space-y-5">
                        <div>
                            <label for="username" class="text-sm font-medium text-gray-700 block mb-1">Username</label>
                            <input id="username" name="username" type="text" placeholder="Username" required 
                                class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:bg-white focus:ring-0 transition duration-200">
                        </div>
                        <div>
                            <label for="password" class="text-sm font-medium text-gray-700 block mb-1">Password</label>
                            <input id="password" name="password" type="password" placeholder="••••••••" required 
                                class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-200 focus:border-blue-500 focus:bg-white focus:ring-0 transition duration-200">
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-6">
                        <label class="flex items-center cursor-pointer">
                            <input id="remember-me" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-600">Ingat Saya</span>
                        </label>
                        </div>
                    
                    <button type="submit" class="w-full mt-8 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition duration-300 transform hover:-translate-y-0.5">
                        Masuk Aplikasi
                    </button>
                </form>
                
                <p class="text-center text-gray-400 text-xs mt-8">&copy; <?php echo date("Y"); ?> SMKN 1 Sungai Tabuk</p>
            </div>

            <div class="hidden md:flex w-1/2 bg-gradient-to-br from-blue-600 to-indigo-900 items-center justify-center p-12 text-white text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                     <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full blur-2xl"></div>
                     <div class="absolute bottom-10 right-10 w-32 h-32 bg-white rounded-full blur-3xl"></div>
                </div>

                <div class="relative z-10">
                    <h2 class="text-4xl font-extrabold mb-4">Selamat Datang!</h2>
                    <p class="text-blue-100 text-lg leading-relaxed">SIPKL<br>SMK Negeri 1 Sungai Tabuk.</p>
                    <div class="mt-8">
                         <span class="inline-block px-4 py-2 border border-white/30 rounded-full text-sm backdrop-blur-sm">v1.0 Release</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>