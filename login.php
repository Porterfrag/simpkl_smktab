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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-4xl w-full bg-white rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden">

            <div class="w-full md:w-1/2 p-8 md:p-12">
                <div class="text-center mb-4">
                    <img src="assets/images/logo-smk.png" alt="Logo Sekolah" style="width: 80px; height: auto;" class="mx-auto">
                </div>
                <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">Sistem Prakerin SMKTAB</h2>

                <form action="login.php" method="POST">
                    <?php if(!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="space-y-6">
                        <div>
                            <label for="username" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Username</label>
                            <input id="username" name="username" type="text" placeholder="Username" required class="w-full p-3 mt-1 bg-gray-100 rounded-lg border-none focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                        </div>
                        <div>
                            <label for="password" class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Password</label>
                            <input id="password" name="password" type="password" placeholder="Password" required class="w-full p-3 mt-1 bg-gray-100 rounded-lg border-none focus:outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-6">
                        <div class="flex items-center">
                            <input 
                                id="remember-me" 
                                name="remember" 
                                type="checkbox" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer"
                            >
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700 cursor-pointer">Ingat Saya</label>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" class="w-full py-3 px-4 text-white font-bold rounded-lg bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5">
                            Login
                        </button>
                    </div>
                </form>
            </div>

            <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col items-center justify-center bg-gradient-to-br from-blue-600 to-indigo-800 text-white text-center min-h-[300px] md:min-h-0">
                <h2 class="text-3xl font-bold mb-4">Selamat Datang!</h2>
                <p class="text-lg">Silakan login untuk mengakses Sistem Informasi Prakerin SMKTAB.</p>
            </div>

        </div>
    </div>
</body>
</html>