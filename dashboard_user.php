<?php
require_once 'config.php';
require_login();

// Jika role bukan user, redirect ke admin
if (is_admin()) {
    header("Location: dashboard_admin.php");
    exit();
}

// Ambil data user
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

// Statistik user
$sql_kuis_stats = "SELECT 
                    COUNT(DISTINCT q.id) as total_soal_dijawab,
                    COUNT(*) as total_jawaban,
                    SUM(j.benar) as total_benar
                   FROM jawaban_kuis j
                   JOIN kuis q ON j.kuis_id = q.id
                   WHERE j.user_id = ?";
$stmt_stats = $conn->prepare($sql_kuis_stats);
$stmt_stats->bind_param("i", $_SESSION['user_id']);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Pendaftaran penyuluhan user
$sql_pendaftaran = "SELECT p.*, j.judul, j.tanggal, j.lokasi
                    FROM pendaftaran_penyuluhan p
                    JOIN jadwal_penyuluhan j ON p.jadwal_id = j.id
                    WHERE p.user_id = ?
                    ORDER BY j.tanggal ASC
                    LIMIT 5";
$stmt_pendaftaran = $conn->prepare($sql_pendaftaran);
$stmt_pendaftaran->bind_param("i", $_SESSION['user_id']);
$stmt_pendaftaran->execute();
$result_pendaftaran = $stmt_pendaftaran->get_result();

// Materi terbaru
$sql_materi_terbaru = "SELECT * FROM materi ORDER BY tanggal_upload DESC LIMIT 3";
$result_materi_terbaru = $conn->query($sql_materi_terbaru);

// Jadwal penyuluhan mendatang
$sql_jadwal_terbaru = "SELECT j.*, 
                            COUNT(p.id) as total_pendaftar,
                            (SELECT COUNT(*) FROM pendaftaran_penyuluhan WHERE user_id = ? AND jadwal_id = j.id) as sudah_daftar
                       FROM jadwal_penyuluhan j
                       LEFT JOIN pendaftaran_penyuluhan p ON j.id = p.jadwal_id
                       WHERE j.tanggal >= NOW() AND j.status != 'dibatalkan'
                       GROUP BY j.id
                       ORDER BY j.tanggal ASC
                       LIMIT 3";
$stmt_jadwal_terbaru = $conn->prepare($sql_jadwal_terbaru);
$stmt_jadwal_terbaru->bind_param("i", $_SESSION['user_id']);
$stmt_jadwal_terbaru->execute();
$result_jadwal_terbaru = $stmt_jadwal_terbaru->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Penyuluhan KB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
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
        
        .card-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c7d2fe;
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a5b4fc;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .bg-glass {
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .progress-ring-container {
            position: relative;
            width: 80px;
            height: 80px;
        }
        
        .progress-ring-circle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            stroke-dasharray: 283;
            transition: stroke-dashoffset 0.5s ease;
        }
        
        .badge-overlap {
            position: absolute;
            top: -8px;
            right: -8px;
            z-index: 10;
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        .event-card-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        
        .card-glow:hover {
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        secondary: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        },
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gray-50 custom-scrollbar">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 bg-white shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-1">
                    <div class="h-10 w-10 rounded-full gradient-primary flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-gray-800">Penyuluhan KB</span>
                </div>
                <div class="hidden md:flex items-center space-x-10">
                    <a href="dashboard_user.php" class="flex items-center font-medium text-indigo-600 transition border-b-2 border-indigo-600">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="user/materi.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                        <i class="fas fa-book mr-2"></i>Materi
                    </a>
                    <a href="user/kuis.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                        <i class="fas fa-question-circle mr-2"></i>Kuis
                    </a>
                    <a href="user/jadwal.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                        <i class="fas fa-calendar-alt mr-2"></i>Jadwal
                    </a>
                </div>
                <div class="flex items-center">
                    <div class="relative" id="userDropdown">
                        <button id="userDropdownButton" class="flex items-center p-2 rounded-full hover:bg-gray-100 transition">
                            <?php if($user_data['avatar']): ?>
                                <img src="<?php echo AVATAR_PATH . $user_data['avatar']; ?>" class="h-9 w-9 rounded-full mr-2 object-cover border-2 border-indigo-200" alt="Avatar">
                            <?php else: ?>
                                <div class="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center mr-2">
                                    <i class="fas fa-user text-indigo-600"></i>
                                </div>
                            <?php endif; ?>
                            <span class="hidden md:block text-gray-700 font-medium"><?php echo $user_data['name']; ?></span>
                            <i class="fas fa-chevron-down ml-2 text-gray-500 text-xs"></i>
                        </button>
                        <div id="userDropdownMenu" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 hidden animate-fade-in">
                            <div class="p-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-700"><?php echo $user_data['name']; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $user_data['email']; ?></p>
                            </div>
                            <a href="user/profile.php" class="flex items-center px-4 py-3 text-sm text-gray-600 hover:bg-gray-50 hover:text-indigo-600 transition">
                                <i class="fas fa-user-circle text-gray-400 w-5 mr-2"></i>Profil Saya
                            </a>
                            <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-gray-600 hover:bg-gray-50 hover:text-red-600 transition rounded-b-lg">
                                <i class="fas fa-sign-out-alt text-gray-400 w-5 mr-2"></i>Keluar
                            </a>
                        </div>
                    </div>
                    <!-- Mobile menu button -->
                    <div class="md:hidden ml-3">
                        <button id="mobile-menu-button" class="p-2 rounded-md hover:bg-gray-100 focus:outline-none">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg animate-fade-in">
            <a href="dashboard_user.php" class="flex items-center px-4 py-3 text-indigo-600 border-l-4 border-indigo-600 bg-indigo-50">
                <i class="fas fa-home w-6 mr-2"></i>Dashboard
            </a>
            <a href="user/materi.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                <i class="fas fa-book w-6 mr-2"></i>Materi
            </a>
            <a href="user/kuis.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                <i class="fas fa-question-circle w-6 mr-2"></i>Kuis
            </a>
            <a href="user/jadwal.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                <i class="fas fa-calendar-alt w-6 mr-2"></i>Jadwal
            </a>
            <a href="user/profile.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                <i class="fas fa-user-circle w-6 mr-2"></i>Profil
            </a>
            <a href="logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-red-600 hover:border-l-4 hover:border-red-600">
                <i class="fas fa-sign-out-alt w-6 mr-2"></i>Keluar
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-primary text-white py-12 relative overflow-hidden">
        <div class="absolute inset-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full opacity-10">
                <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
        <div class="container mx-auto px-4 z-10 relative animate-fade-in">
            <div class="flex items-center">
                <div class="flex-1">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">Selamat Datang, <span class="text-indigo-100"><?php echo $user_data['name']; ?></span>!</h1>
                    <p class="text-xl opacity-90 mb-6">Mari belajar lebih banyak tentang Keluarga Berencana</p>
                    <div class="flex space-x-4">
                        <a href="user/materi.php" class="bg-white text-indigo-600 hover:bg-indigo-50 font-medium px-6 py-2 rounded-full shadow-lg hover:shadow-xl transition flex items-center">
                            <i class="fas fa-book-open mr-2"></i> Jelajahi Materi
                        </a>
                        <a href="user/kuis.php" class="bg-indigo-700 text-white hover:bg-indigo-800 font-medium px-6 py-2 rounded-full shadow-lg hover:shadow-xl transition flex items-center">
                            <i class="fas fa-question-circle mr-2"></i> Ikuti Kuis
                        </a>
                    </div>
                </div>
                <?php if($user_data['avatar']): ?>
                    <div class="hidden lg:block">
                        <div class="relative">
                            <div class="absolute -inset-4 bg-indigo-200 rounded-full opacity-50 animate-pulse"></div>
                            <img src="<?php echo AVATAR_PATH . $user_data['avatar']; ?>" class="h-36 w-36 rounded-full border-4 border-white object-cover shadow-lg relative" alt="Avatar">
                        </div>
                    </div>
                <?php else: ?>
                    <div class="hidden lg:block">
                        <div class="relative">
                            <div class="absolute -inset-4 bg-indigo-200 rounded-full opacity-50 animate-pulse"></div>
                            <div class="h-36 w-36 rounded-full border-4 border-white bg-indigo-100 flex items-center justify-center shadow-lg relative">
                                <i class="fas fa-user-circle text-indigo-600 text-6xl"></i>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container mx-auto px-4 -mt-10 relative z-20">
        <div class="grid md:grid-cols-3 gap-6 mb-12 animate-slide-up">
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-trophy text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase font-semibold">Skor Kuis</p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="progress-ring-container">
                        <?php 
                        if ($stats['total_jawaban'] > 0) {
                            $percentage = round($stats['total_benar']/$stats['total_jawaban']*100, 1);
                        } else {
                            $percentage = 0;
                        }
                        $circumference = 2 * 3.14159 * 45;
                        $offset = $circumference - ($percentage / 100) * $circumference;
                        ?>
                        <svg class="w-full h-full" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" stroke="#dbeafe" stroke-width="8" fill="none" />
                            <circle class="progress-ring-circle" cx="50" cy="50" r="45" stroke="#3b82f6" stroke-width="8" fill="none" stroke-dasharray="<?php echo $circumference; ?>" stroke-dashoffset="<?php echo $offset; ?>" />
                            <text x="50" y="55" text-anchor="middle" font-size="18" font-weight="bold" fill="#1e40af"><?php echo $percentage; ?>%</text>
                        </svg>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm mb-1">Jawaban Benar</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_benar'] ?? 0; ?> <span class="text-sm text-gray-500">/ <?php echo $stats['total_jawaban'] ?? 0; ?></span></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-book text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase font-semibold">Total Soal</p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-4xl font-bold text-green-600"><?php echo $stats['total_jawaban'] ?? 0; ?></p>
                    <div class="flex items-center mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 rounded-full h-2" style="width: <?php echo min(100, $stats['total_jawaban'] ?? 0); ?>%"></div>
                        </div>
                        <span class="ml-2 text-xs text-gray-500">Soal</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-calendar-check text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase font-semibold">Event Terdaftar</p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-4xl font-bold text-purple-600"><?php echo $result_pendaftaran->num_rows; ?></p>
                    <div class="flex items-center mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 rounded-full h-2" style="width: <?php echo min(100, $result_pendaftaran->num_rows * 20); ?>%"></div>
                        </div>
                        <span class="ml-2 text-xs text-gray-500">Event</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="container mx-auto px-4 py-6">
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Quick Access -->
            <div class="md:col-span-2">
                <!-- Materi Terbaru -->
                <div class="mb-10 animate-slide-up" style="animation-delay: 0.2s;">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Materi Terbaru</h2>
                            <p class="text-gray-500 mt-1">Pelajari informasi terbaru seputar Keluarga Berencana</p>
                        </div>
                        <a href="user/materi.php" class="flex items-center text-indigo-600 hover:text-indigo-700 font-medium transition group">
                            <span>Lihat Semua</span> 
                            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                    <div class="grid md:grid-cols-3 gap-6">
                        <?php $counter = 0; while($materi = $result_materi_terbaru->fetch_assoc()): $counter++; ?>
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow" style="animation-delay: <?php echo 0.1 * $counter; ?>s;">
                                <div class="h-3 <?php if($counter == 1): ?>bg-blue-500<?php elseif($counter == 2): ?>bg-purple-500<?php else: ?>bg-pink-500<?php endif; ?>"></div>
                                <div class="p-6">
                                    <div class="flex items-center mb-3">
                                        <div class="p-2 rounded-full <?php if($counter == 1): ?>bg-blue-100 text-blue-600<?php elseif($counter == 2): ?>bg-purple-100 text-purple-600<?php else: ?>bg-pink-100 text-pink-600<?php endif; ?>">
                                            <i class="fas <?php if($counter == 1): ?>fa-book-medical<?php elseif($counter == 2): ?>fa-heartbeat<?php else: ?>fa-chart-pie<?php endif; ?>"></i>
                                        </div>
                                        <span class="text-xs text-gray-500 ml-auto">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            <?php echo tanggal_indo($materi['tanggal_upload']); ?>
                                        </span>
                                    </div>
                                    <h3 class="font-bold text-lg mb-2 text-gray-800"><?php echo $materi['judul']; ?></h3>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                        <?php echo substr($materi['deskripsi'], 0, 120) . (strlen($materi['deskripsi']) > 120 ? '...' : ''); ?>
                                    </p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs px-2 py-1 rounded-full <?php if($counter == 1): ?>bg-blue-100 text-blue-600<?php elseif($counter == 2): ?>bg-purple-100 text-purple-600<?php else: ?>bg-pink-100 text-pink-600<?php endif; ?>">Materi Terbaru</span>
                                        <?php if($materi['file_materi']): ?>
                                            <a href="<?php echo UPLOAD_PATH . $materi['file_materi']; ?>" target="_blank" 
                                               class="flex items-center text-sm font-medium <?php if($counter == 1): ?>text-blue-600 hover:text-blue-700<?php elseif($counter == 2): ?>text-purple-600 hover:text-purple-700<?php else: ?>text-pink-600 hover:text-pink-700<?php endif; ?> transition">
                                                <span>Download</span>
                                                <i class="fas fa-download ml-1"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Jadwal Terbuka -->
                <div class="mb-10 animate-slide-up" style="animation-delay: 0.4s;">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Jadwal Penyuluhan</h2>
                            <p class="text-gray-500 mt-1">Daftar jadwal penyuluhan yang tersedia</p>
                        </div>
                        <a href="user/jadwal.php" class="flex items-center text-indigo-600 hover:text-indigo-700 font-medium transition group">
                            <span>Lihat Semua</span> 
                            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                    <div class="grid md:grid-cols-3 gap-6">
                        <?php $counter = 0; while($jadwal = $result_jadwal_terbaru->fetch_assoc()): $counter++; ?>
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow card-glow relative" style="animation-delay: <?php echo 0.1 * $counter; ?>s;">
                                <div class="relative">
                                    <div class="h-16 event-card-gradient flex items-center p-4">
                                        <div class="flex-1 pr-8">
                                            <h3 class="font-bold text-lg text-white"><?php echo $jadwal['judul']; ?></h3>
                                        </div>
                                        
                                        <?php if($jadwal['sudah_daftar'] > 0): ?>
                                            <div class="absolute right-4 top-1/2 transform -translate-y-1/2 flex items-center justify-center">
                                                <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow-md">
                                                    <i class="fas fa-check mr-1"></i> Terdaftar
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="space-y-3 mb-6">
                                        <div class="flex items-center text-gray-600">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-calendar text-indigo-600"></i>
                                            </div>
                                            <span><?php echo tanggal_indo($jadwal['tanggal']); ?></span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-clock text-blue-600"></i>
                                            </div>
                                            <span><?php echo date('H:i', strtotime($jadwal['tanggal'])); ?> WIB</span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-map-marker-alt text-purple-600"></i>
                                            </div>
                                            <span><?php echo $jadwal['lokasi']; ?></span>
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                                <i class="fas fa-users text-yellow-600"></i>
                                            </div>
                                            <div class="flex-1">
                                                <span><?php echo $jadwal['total_pendaftar']; ?>/<?php echo $jadwal['kapasitas']; ?> peserta</span>
                                                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                                    <div class="<?php if($jadwal['total_pendaftar'] >= $jadwal['kapasitas']): ?>bg-red-500<?php else: ?>bg-green-500<?php endif; ?> h-2 rounded-full transition-all duration-500" style="width: <?php echo min(100, ($jadwal['total_pendaftar'] / $jadwal['kapasitas']) * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if($jadwal['sudah_daftar'] > 0): ?>
                                        <button class="w-full bg-green-100 text-green-700 font-medium py-3 px-4 rounded-xl cursor-not-allowed flex items-center justify-center" disabled>
                                            <i class="fas fa-check-circle mr-2"></i>Anda Sudah Terdaftar
                                        </button>
                                    <?php elseif($jadwal['status'] == 'penuh'): ?>
                                        <button class="w-full bg-gray-100 text-gray-500 font-medium py-3 px-4 rounded-xl cursor-not-allowed flex items-center justify-center" disabled>
                                            <i class="fas fa-ban mr-2"></i>Acara Penuh
                                        </button>
                                    <?php else: ?>
                                        <a href="user/jadwal.php?daftar=<?php echo $jadwal['id']; ?>" 
                                           class="block w-full gradient-primary text-white font-medium py-3 px-4 rounded-xl hover:opacity-90 transition text-center group">
                                            <span class="flex items-center justify-center">
                                                <i class="fas fa-sign-in-alt mr-2 group-hover:animate-pulse"></i>Daftar Sekarang
                                            </span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Profile Card -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-shadow animate-slide-up" style="animation-delay: 0.3s;">
                    <div class="gradient-primary h-24 relative">
                        <div class="absolute w-full text-center" style="bottom: -2rem;">
                            <?php if($user_data['avatar']): ?>
                                <img src="<?php echo AVATAR_PATH . $user_data['avatar']; ?>" class="h-20 w-20 rounded-full mx-auto object-cover border-4 border-white shadow-lg">
                            <?php else: ?>
                                <div class="h-20 w-20 rounded-full bg-indigo-100 flex items-center justify-center mx-auto border-4 border-white shadow-lg">
                                    <i class="fas fa-user-circle text-4xl text-indigo-600"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pt-12 p-6 text-center">
                        <h3 class="font-bold text-xl text-gray-800"><?php echo $user_data['name']; ?></h3>
                        <p class="text-gray-500 text-sm mb-4"><?php echo $user_data['email']; ?></p>
                        
                        <div class="space-y-3 border-t border-gray-100 pt-4 text-left">
                            <div class="flex items-center text-gray-600 px-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-phone text-blue-600 text-sm"></i>
                                </div>
                                <span class="text-sm"><?php echo $user_data['phone'] ?? 'Belum diisi'; ?></span>
                            </div>
                            <div class="flex items-center text-gray-600 px-2">
                                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-map-marker-alt text-purple-600 text-sm"></i>
                                </div>
                                <span class="text-sm"><?php echo $user_data['address'] ? substr($user_data['address'], 0, 30) . (strlen($user_data['address']) > 30 ? '...' : '') : 'Belum diisi'; ?></span>
                            </div>
                            <div class="flex items-center text-gray-600 px-2">
                                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-clock text-green-600 text-sm"></i>
                                </div>
                                <span class="text-sm">Login: <?php echo $user_data['last_login'] ? date('d/m/Y', strtotime($user_data['last_login'])) : 'Belum pernah'; ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <a href="user/profile.php" class="block w-full gradient-primary text-white py-3 rounded-lg text-center hover:opacity-90 transition group">
                                <span class="flex items-center justify-center">
                                    <i class="fas fa-user-edit mr-2 group-hover:animate-pulse"></i>Edit Profil
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-shadow card-hover animate-slide-up" style="animation-delay: 0.4s;">
                    <div class="p-6">
                        <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-link text-indigo-500 mr-2"></i>Link Cepat
                        </h3>
                        <div class="space-y-2">
                            <a href="user/materi.php" class="flex items-center p-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg transition">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-book text-blue-600"></i>
                                </div>
                                <div>
                                    <span class="font-medium">Semua Materi</span>
                                    <p class="text-xs text-gray-500">Akses semua materi pembelajaran</p>
                                </div>
                                <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                            </a>
                            <a href="user/kuis.php" class="flex items-center p-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg transition">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-question-circle text-green-600"></i>
                                </div>
                                <div>
                                    <span class="font-medium">Ikuti Kuis</span>
                                    <p class="text-xs text-gray-500">Uji pengetahuan Anda</p>
                                </div>
                                <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                            </a>
                            <a href="user/jadwal.php" class="flex items-center p-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-lg transition">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-calendar-alt text-purple-600"></i>
                                </div>
                                <div>
                                    <span class="font-medium">Jadwal Acara</span>
                                    <p class="text-xs text-gray-500">Lihat dan daftar acara penyuluhan</p>
                                </div>
                                <i class="fas fa-chevron-right ml-auto text-gray-400"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Event Terdaftar -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-shadow card-hover animate-slide-up" style="animation-delay: 0.5s;">
                    <div class="p-6">
                        <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-check text-indigo-500 mr-2"></i>Event Terdaftar
                        </h3>
                        <?php if($result_pendaftaran->num_rows > 0): ?>
                            <div class="space-y-4 max-h-80 overflow-y-auto custom-scrollbar">
                                <?php while($pendaftaran = $result_pendaftaran->fetch_assoc()): ?>
                                    <div class="border border-gray-100 rounded-lg p-4 hover:bg-gray-50 transition">
                                        <h4 class="font-medium text-gray-800"><?php echo $pendaftaran['judul']; ?></h4>
                                        <div class="flex items-center text-sm text-gray-500 mt-2">
                                            <div class="flex items-center mr-4">
                                                <i class="fas fa-calendar-day mr-1 text-indigo-500"></i>
                                                <?php echo tanggal_indo($pendaftaran['tanggal']); ?>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-map-marker-alt mr-1 text-pink-500"></i>
                                                <?php echo substr($pendaftaran['lokasi'], 0, 15) . (strlen($pendaftaran['lokasi']) > 15 ? '...' : ''); ?>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <span class="inline-block px-3 py-1 text-xs rounded-full font-medium
                                                <?php 
                                                if($pendaftaran['status'] == 'terdaftar') echo 'bg-blue-100 text-blue-700';
                                                elseif($pendaftaran['status'] == 'hadir') echo 'bg-green-100 text-green-700';
                                                else echo 'bg-red-100 text-red-700';
                                                ?>">
                                                <i class="fas <?php 
                                                    if($pendaftaran['status'] == 'terdaftar') echo 'fa-clipboard-check';
                                                    elseif($pendaftaran['status'] == 'hadir') echo 'fa-check-circle';
                                                    else echo 'fa-times-circle';
                                                ?> mr-1"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $pendaftaran['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="user/jadwal.php" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                    Lihat Semua Event <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-6 bg-gray-50 rounded-lg">
                                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-calendar-times text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-gray-600 mb-3">Belum ada event yang terdaftar</p>
                                <a href="user/jadwal.php" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                    Lihat Jadwal <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-10 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 rounded-full gradient-primary flex items-center justify-center mr-2">
                            <i class="fas fa-heartbeat text-white"></i>
                        </div>
                        <span class="font-bold text-xl">Penyuluhan KB</span>
                    </div>
                    <p class="text-gray-400 mb-4 pr-4">Memberikan edukasi dan informasi tentang Keluarga Berencana untuk masyarakat Indonesia.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-indigo-600 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-indigo-600 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-indigo-600 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Link Cepat</h3>
                    <ul class="space-y-2">
                        <li><a href="dashboard_user.php" class="text-gray-400 hover:text-white transition">Dashboard</a></li>
                        <li><a href="user/materi.php" class="text-gray-400 hover:text-white transition">Materi</a></li>
                        <li><a href="user/kuis.php" class="text-gray-400 hover:text-white transition">Kuis</a></li>
                        <li><a href="user/jadwal.php" class="text-gray-400 hover:text-white transition">Jadwal</a></li>
                        <li><a href="user/profile.php" class="text-gray-400 hover:text-white transition">Profil</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Kontak</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-indigo-400 mt-1 mr-3"></i>
                            <span class="text-gray-400">Jl. Khatib Sulaiman No. 105, Padang</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone text-indigo-400 mt-1 mr-3"></i>
                            <span class="text-gray-400">(0751) 7052357</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope text-indigo-400 mt-1 mr-3"></i>
                            <span class="text-gray-400">prov.sumbar@bkkbn.go.id</span>
                        </li>
                    </ul>
                </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle untuk dropdown user
            const userDropdownButton = document.getElementById('userDropdownButton');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userDropdownButton && userDropdownMenu) {
                userDropdownButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    userDropdownMenu.classList.toggle('hidden');
                });
                
                // Tutup dropdown jika user mengklik di luar dropdown
                document.addEventListener('click', function(e) {
                    if (!userDropdownButton.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                        userDropdownMenu.classList.add('hidden');
                    }
                });
            }
            
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
            
            // Animasi untuk progress ring
            const animateProgressRings = () => {
                document.querySelectorAll('.progress-ring-circle').forEach(ring => {
                    const circumference = ring.getAttribute('stroke-dasharray');
                    const offset = ring.getAttribute('stroke-dashoffset');
                    
                    ring.style.strokeDashoffset = circumference;
                    
                    setTimeout(() => {
                        ring.style.strokeDashoffset = offset;
                    }, 300);
                });
            };
            
            animateProgressRings();
        });
    </script>
</body>
</html>