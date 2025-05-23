<?php
require_once 'config.php';

// Mode parameter
$mode = $_GET['mode'] ?? 'login';

// Jika sudah login, redirect
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_user.php");
    }
    exit();
}

// Set very strict cache control headers to prevent back button navigation
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: -1");

$error = '';
$success = '';

// Process login/register
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'login') {
        $email = clean_input($_POST['email']);
        $password = md5($_POST['password']);
        
        $sql = "SELECT id, name, email, role, avatar FROM users WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'];
            
            // Update last login
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            if ($user['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_user.php");
            }
            exit();
        } else {
            $error = 'Email atau password salah!';
        }
    } elseif ($mode == 'register') {
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $password = md5($_POST['password']);
        $confirm_password = md5($_POST['confirm_password']);
        $phone = clean_input($_POST['phone'] ?? '');
        $address = clean_input($_POST['address'] ?? '');
        $birth_date = $_POST['birth_date'] ?? null;
        
        if ($password !== $confirm_password) {
            $error = 'Password tidak cocok!';
        } else {
            // Cek email sudah terdaftar
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Simpan user baru
                $sql = "INSERT INTO users (name, email, password, role, phone, address, birth_date) VALUES (?, ?, ?, 'user', ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $name, $email, $password, $phone, $address, $birth_date);
                
                if ($stmt->execute()) {
                    $success = 'Registrasi berhasil! Silakan login.';
                    $mode = 'login';
                } else {
                    $error = 'Terjadi kesalahan saat registrasi.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo $mode == 'login' ? 'Login' : 'Registrasi'; ?> - Penyuluhan KB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }
        
        .gradient-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .gradient-secondary {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
        }

        .auth-background {
            background-color: #f5f7ff;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23bbc1ff' fill-opacity='0.15' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
        
        .card-shadow {
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .form-input:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .tab-active {
            background-color: white;
            color: #4f46e5;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .tab-link {
            transition: all 0.3s ease;
        }

        .tab-link:hover:not(.tab-active) {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 1rem;
            color: #a5b4fc;
        }

        .link-hover {
            position: relative;
            display: inline-block;
        }
        
        .link-hover::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #4f46e5;
            transition: width 0.3s ease;
        }
        
        .link-hover:hover::after {
            width: 100%;
        }
    </style>
</head>

<body class="auth-background min-h-screen flex flex-col">
    <!-- Header/Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-10">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-1">
                    <div class="h-10 w-10 rounded-full gradient-primary flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-gray-800">Penyuluhan KB</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-600 hover:text-indigo-600 transition">
                        <i class="fas fa-home mr-1"></i>
                        <span class="hidden md:inline">Beranda</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 animate-fade-in">
            <!-- Page Title -->
            <div class="text-center">
                <h2 class="text-4xl font-bold text-indigo-600 mb-2">
                    <?php echo $mode == 'login' ? 'Selamat Datang Kembali' : 'Bergabung Bersama Kami'; ?>
                </h2>
                <p class="text-gray-600">
                    <?php echo $mode == 'login' ? 'Masuk untuk akses ke materi dan jadwal penyuluhan' : 'Buat akun untuk akses penuh ke platform Penyuluhan KB'; ?>
                </p>
            </div>

            <!-- Auth Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-slide-up card-shadow">
                <!-- Tabs -->
                <div class="flex bg-indigo-50 p-1 rounded-t-xl">
                    <a href="auth.php?mode=login" class="flex-1 py-3 px-4 rounded-xl text-center font-medium text-gray-700 tab-link <?php echo $mode == 'login' ? 'tab-active' : ''; ?>">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="auth.php?mode=register" class="flex-1 py-3 px-4 rounded-xl text-center font-medium text-gray-700 tab-link <?php echo $mode == 'register' ? 'tab-active' : ''; ?>">
                        <i class="fas fa-user-plus mr-2"></i>Registrasi
                    </a>
                </div>

                <!-- Form Content -->
                <div class="p-8">
                    <?php if($error): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md animate-fade-in">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700"><?php echo $error; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-md animate-fade-in">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700"><?php echo $success; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <?php if($mode == 'register'): ?>
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="name">
                                    Nama Lengkap
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input class="appearance-none block w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-xl py-3 pl-10 pr-4 form-input transition focus:ring-2 focus:ring-indigo-200 focus:outline-none" 
                                        id="name" type="text" name="name" placeholder="Masukkan nama lengkap" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="phone">
                                    Nomor Telepon (Opsional)
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input class="appearance-none block w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-xl py-3 pl-10 pr-4 form-input transition focus:ring-2 focus:ring-indigo-200 focus:outline-none" 
                                        id="phone" type="tel" name="phone" placeholder="08xxxxxxxxxx">
                                </div>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="birth_date">
                                    Tanggal Lahir (Opsional)
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-calendar-alt input-icon"></i>
                                    <input class="appearance-none block w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-xl py-3 pl-10 pr-4 form-input transition focus:ring-2 focus:ring-indigo-200 focus:outline-none" 
                                        id="birth_date" type="date" name="birth_date">
                                </div>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="address">
                                    Alamat (Opsional)
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-map-marker-alt input-icon" style="top: 25%"></i>
                                    <textarea class="appearance-none block w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-xl py-3 pl-10 pr-4 form-input transition focus:ring-2 focus:ring-indigo-200 focus:outline-none" 
                                        id="address" name="address" rows="2" placeholder="Masukkan alamat"></textarea>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="email">
                                Email
                            </label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input class="appearance-none block w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-xl py-3 pl-10 pr-4 form-input transition focus:ring-2 focus:ring-indigo-200 focus:outline-none" 
                                    id="email" type="email" name="email" placeholder="email@contoh.com" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="password">
                                Password
                            </label>
                            <div class="input-icon-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input class="appearance-none block w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-xl py-3 pl-10 pr-4 form-input transition focus:ring-2 focus:ring-indigo-200 focus:outline-none" 
                                    id="password" type="password" name="password" placeholder="••••••••" required>
                            </div>
                        </div>

                        <?php if($mode == 'register'): ?>
                            <div>
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="confirm_password">
                                    Konfirmasi Password
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input class="appearance-none block w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-xl py-3 pl-10 pr-4 form-input transition focus:ring-2 focus:ring-indigo-200 focus:outline-none" 
                                        id="confirm_password" type="password" name="confirm_password" placeholder="••••••••" required>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <button class="w-full btn-primary text-white font-bold py-3 px-4 rounded-xl focus:outline-none focus:shadow-outline hover:opacity-90 transition" 
                                    type="submit">
                                <?php if($mode == 'login'): ?>
                                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                                <?php else: ?>
                                    <i class="fas fa-user-plus mr-2"></i>Daftar
                                <?php endif; ?>
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 text-center">
                        <?php if($mode == 'login'): ?>
                            <p class="text-gray-600">
                                Belum punya akun? 
                                <a href="auth.php?mode=register" class="text-indigo-600 hover:text-indigo-800 font-semibold link-hover">Daftar sekarang</a>
                            </p>
                        <?php else: ?>
                            <p class="text-gray-600">
                                Sudah punya akun? 
                                <a href="auth.php?mode=login" class="text-indigo-600 hover:text-indigo-800 font-semibold link-hover">Login sekarang</a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Back to Home -->
            <div class="text-center">
                <a href="index.php" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script>
        // Add focus & blur effects for input fields
        document.addEventListener('DOMContentLoaded', function() {
            const inputFields = document.querySelectorAll('.form-input');
            
            inputFields.forEach(input => {
                // Add focus effect
                input.addEventListener('focus', function() {
                    this.parentNode.querySelector('.input-icon').classList.add('text-indigo-500');
                    this.parentNode.querySelector('.input-icon').classList.remove('text-gray-400');
                });
                
                // Remove focus effect
                input.addEventListener('blur', function() {
                    this.parentNode.querySelector('.input-icon').classList.remove('text-indigo-500');
                    this.parentNode.querySelector('.input-icon').classList.add('text-gray-400');
                });
            });
            
            // Completely prevent back button navigation
            (function() {
                // 1. Block the initial navigation attempt
                window.history.pushState(null, null, window.location.href);
                
                // 2. Handle any subsequent attempts
                window.addEventListener('popstate', function(event) {
                    // Push another state to prevent navigation
                    window.history.pushState(null, null, window.location.href);
                    
                    // This is critical - immediately reload the current page to stay at auth.php
                    window.location.reload();
                });
                
                // 3. Additional aggressive prevention
                window.addEventListener('beforeunload', function(e) {
                    // This helps prevent back navigation in some browsers
                    document.body.style.display = 'none';
                    
                    // The following lines help force a page reload, if navigation does happen
                    const key = 'reload-prevention-' + Date.now();
                    localStorage.setItem(key, '1');
                    localStorage.removeItem(key);
                });
                
                // 4. Monitor page visibility changes which can happen during back/forward navigation
                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'visible') {
                        // If the page becomes visible again (e.g., after back navigation)
                        window.location.reload();
                    }
                });
                
                // 5. Handle browser-specific cache issues
                window.addEventListener('pageshow', function(event) {
                    if (event.persisted) {
                        // Page was loaded from bfcache (back-forward cache)
                        window.location.reload();
                    }
                });
                
                // 6. Disable bfcache
                window.addEventListener('unload', function() {});
                
                // 7. Force check on load
                if (performance && performance.navigation) {
                    if (performance.navigation.type === 2) { // 2 = TYPE_BACK_FORWARD
                        window.location.reload();
                    }
                }
                
                // 8. Clear page history entirely (more extreme but effective)
                function unloadPage() {
                    try {
                        // Try to replace the URL state without changing it visibly
                        window.history.replaceState({}, document.title, window.location.href);
                    } catch (e) {
                        console.error('History API error:', e);
                    }
                }
                
                // Called when leaving the page
                window.addEventListener('unload', unloadPage);
                
                // The most aggressive measure - reload immediately if we suspect back navigation
                if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
                    window.location.replace(window.location.href);
                }
            })();
        });
    </script>
</body>
</html>