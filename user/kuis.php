<?php
require_once '../config.php';
require_login();

// Ambil parameter mode
$mode = $_GET['mode'] ?? 'index';
$kategori = $_GET['kategori'] ?? '';
$tingkat = $_GET['tingkat'] ?? '';

// Proses submit jawaban
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kuis'])) {
    $benar = 0;
    $total = count($_POST['jawaban']);
    
    foreach ($_POST['jawaban'] as $kuis_id => $jawaban) {
        // Cek jawaban benar atau salah
        $sql_check = "SELECT jawaban_benar FROM kuis WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $kuis_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $kuis = $result_check->fetch_assoc();
        
        $is_benar = ($jawaban == $kuis['jawaban_benar']) ? 1 : 0;
        if ($is_benar) $benar++;
        
        // Simpan jawaban
        $sql_save = "INSERT INTO jawaban_kuis (user_id, kuis_id, jawaban_user, benar) VALUES (?, ?, ?, ?)";
        $stmt_save = $conn->prepare($sql_save);
        $stmt_save->bind_param("iisi", $_SESSION['user_id'], $kuis_id, $jawaban, $is_benar);
        $stmt_save->execute();
    }
    
    // Redirect ke hasil
    header("Location: kuis.php?mode=hasil&score=$benar&total=$total");
    exit();
}

// Ambil soal jika mode mulai
if ($mode == 'mulai') {
    $sql = "SELECT * FROM kuis WHERE 1=1";
    $params = [];
    $types = "";
    
    if ($kategori) {
        $sql .= " AND kategori = ?";
        $params[] = $kategori;
        $types .= "s";
    }
    
    if ($tingkat) {
        $sql .= " AND tingkat_kesulitan = ?";
        $params[] = $tingkat;
        $types .= "s";
    }
    
    $sql .= " ORDER BY RAND() LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result_soal = $stmt->get_result();
}

// Ambil statistik user
$sql_stats = "SELECT 
                COUNT(*) as total_jawaban,
                SUM(benar) as total_benar,
                AVG(benar) * 100 as rata_skor
              FROM jawaban_kuis 
              WHERE user_id = ?";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $_SESSION['user_id']);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Ambil kategori dan tingkat kesulitan untuk filter
$sql_kategori = "SELECT DISTINCT kategori FROM kuis WHERE kategori IS NOT NULL ORDER BY kategori";
$result_kategori = $conn->query($sql_kategori);

$sql_tingkat = "SELECT DISTINCT tingkat_kesulitan FROM kuis ORDER BY CASE tingkat_kesulitan WHEN 'mudah' THEN 1 WHEN 'sedang' THEN 2 WHEN 'sulit' THEN 3 END";
$result_tingkat = $conn->query($sql_tingkat);

// Progress data
$progress_data = [];
$sql_progress = "SELECT k.kategori, COUNT(*) as total, SUM(j.benar) as benar
                 FROM jawaban_kuis j
                 JOIN kuis k ON j.kuis_id = k.id
                 WHERE j.user_id = ? AND k.kategori IS NOT NULL
                 GROUP BY k.kategori";
$stmt_progress = $conn->prepare($sql_progress);
$stmt_progress->bind_param("i", $_SESSION['user_id']);
$stmt_progress->execute();
$result_progress = $stmt_progress->get_result();
while($row = $result_progress->fetch_assoc()) {
    $progress_data[$row['kategori']] = round(($row['benar'] / $row['total']) * 100, 1);
}

// Ambil data user untuk navbar
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuis KB - Penyuluhan KB</title>
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
        
        .event-card-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
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
        
        .card-glow:hover {
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
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
        
        .option-hover {
            transition: all 0.2s ease;
        }
        
        .option-hover:hover {
            background-color: #f0f4ff;
            border-color: #6366f1;
        }
        
        .option-selected {
            background-color: #eff6ff;
            border-color: #3b82f6;
            border-width: 2px;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f0f;
            position: absolute;
            left: 50%;
            animation: confetti 5s ease-in-out -2s infinite;
            transform-origin: left top;
            z-index: 1000;
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
        
        @keyframes confetti {
            0% { transform: rotateZ(15deg) rotateY(0deg) translateY(0); }
            25% { transform: rotateZ(5deg) rotateY(360deg) translateY(-300px); }
            50% { transform: rotateZ(15deg) rotateY(720deg) translateY(-600px); }
            75% { transform: rotateZ(5deg) rotateY(1080deg) translateY(-900px); }
            100% { transform: rotateZ(15deg) rotateY(1440deg) translateY(-1200px); }
        }
        
        .bg-glass {
            background-color: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
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
                <a href="../dashboard_user.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="materi.php" class="flex items-center font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'materi.php' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600 hover:border-b-2 hover:border-indigo-600'; ?>">
                    <i class="fas fa-book mr-2"></i>Materi
                </a>
                <a href="kuis.php" class="flex items-center font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'kuis.php' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600 hover:border-b-2 hover:border-indigo-600'; ?>">
                    <i class="fas fa-question-circle mr-2"></i>Kuis
                </a>
                <a href="jadwal.php" class="flex items-center font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'jadwal.php' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-600 hover:text-indigo-600 hover:border-b-2 hover:border-indigo-600'; ?>">
                    <i class="fas fa-calendar-alt mr-2"></i>Jadwal
                </a>
            </div>
            <div class="flex items-center">
                <div class="relative" id="userDropdown">
                    <button id="userDropdownButton" class="flex items-center p-2 rounded-full hover:bg-gray-100 transition">
                        <?php if(isset($user_data['avatar']) && $user_data['avatar']): ?>
                            <img src="../<?php echo AVATAR_PATH . $user_data['avatar']; ?>" class="h-9 w-9 rounded-full mr-2 object-cover border-2 border-indigo-200" alt="Avatar">
                        <?php else: ?>
                            <div class="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center mr-2">
                                <i class="fas fa-user text-indigo-600"></i>
                            </div>
                        <?php endif; ?>
                        <span class="hidden md:block text-gray-700 font-medium"><?php echo isset($user_data['name']) ? $user_data['name'] : 'Ryan'; ?></span>
                        <i class="fas fa-chevron-down ml-2 text-gray-500 text-xs"></i>
                    </button>
                    <div id="userDropdownMenu" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 hidden animate-fade-in">
                        <div class="p-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-700"><?php echo isset($user_data['name']) ? $user_data['name'] : 'Ryan'; ?></p>
                            <p class="text-xs text-gray-500"><?php echo isset($user_data['email']) ? $user_data['email'] : 'ryanalhmda@gmail.com'; ?></p>
                        </div>
                        <a href="profile.php" class="flex items-center px-4 py-3 text-sm <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-indigo-600 bg-indigo-50 border-l-4 border-indigo-600' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600'; ?> transition">
                            <i class="fas fa-user-circle <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-indigo-600' : 'text-gray-400'; ?> w-5 mr-2"></i>Profil Saya
                        </a>
                        <a href="../logout.php" class="flex items-center px-4 py-3 text-sm text-gray-600 hover:bg-gray-50 hover:text-red-600 transition rounded-b-lg">
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
        <a href="../dashboard_user.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
            <i class="fas fa-home w-6 mr-2"></i>Dashboard
        </a>
        <a href="materi.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'materi.php' ? 'text-indigo-600 border-l-4 border-indigo-600 bg-indigo-50' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600'; ?>">
            <i class="fas fa-book w-6 mr-2"></i>Materi
        </a>
        <a href="kuis.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'kuis.php' ? 'text-indigo-600 border-l-4 border-indigo-600 bg-indigo-50' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600'; ?>">
            <i class="fas fa-question-circle w-6 mr-2"></i>Kuis
        </a>
        <a href="jadwal.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'jadwal.php' ? 'text-indigo-600 border-l-4 border-indigo-600 bg-indigo-50' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600'; ?>">
            <i class="fas fa-calendar-alt w-6 mr-2"></i>Jadwal
        </a>
        <a href="profile.php" class="flex items-center px-4 py-3 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-indigo-600 border-l-4 border-indigo-600 bg-indigo-50' : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600'; ?>">
            <i class="fas fa-user-circle w-6 mr-2"></i>Profil
        </a>
        <a href="../logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-red-600 hover:border-l-4 hover:border-red-600">
            <i class="fas fa-sign-out-alt w-6 mr-2"></i>Keluar
        </a>
    </div>
</nav>
    <?php if($mode == 'index'): ?>
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
                        <h1 class="text-4xl md:text-5xl font-bold mb-4">Kuis Penyuluhan KB</h1>
                        <p class="text-xl opacity-90 mb-6">Uji pengetahuan Anda tentang Keluarga Berencana</p>
                        <div class="flex space-x-4">
                            <a href="#quiz-section" class="bg-white text-indigo-600 hover:bg-indigo-50 font-medium px-6 py-2 rounded-full shadow-lg hover:shadow-xl transition flex items-center">
                                <i class="fas fa-play mr-2"></i> Mulai Kuis
                            </a>
                            <a href="materi.php" class="bg-indigo-700 text-white hover:bg-indigo-800 font-medium px-6 py-2 rounded-full shadow-lg hover:shadow-xl transition flex items-center">
                                <i class="fas fa-book-open mr-2"></i> Pelajari Materi
                            </a>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="relative">
                            <div class="absolute -inset-4 bg-indigo-200 rounded-full opacity-50 animate-pulse"></div>
                            <div class="p-4 bg-white rounded-full shadow-xl relative">
                                <div class="h-28 w-28 gradient-primary rounded-full flex items-center justify-center">
                                    <i class="fas fa-question-circle text-white text-5xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="container mx-auto px-4 -mt-10 relative z-20">
            <div class="grid md:grid-cols-3 gap-6 mb-12 animate-slide-up">
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500 uppercase font-semibold">Total Jawaban</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-4xl font-bold text-blue-600"><?php echo $stats['total_jawaban'] ?? 0; ?></p>
                        <div class="flex items-center mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 rounded-full h-2" style="width: <?php echo min(100, $stats['total_jawaban'] ?? 0); ?>%"></div>
                            </div>
                            <span class="ml-2 text-xs text-gray-500">Soal</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-check text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500 uppercase font-semibold">Jawaban Benar</p>
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
                                <circle cx="50" cy="50" r="45" stroke="#d1fae5" stroke-width="8" fill="none" />
                                <circle class="progress-ring-circle" cx="50" cy="50" r="45" stroke="#10b981" stroke-width="8" fill="none" stroke-dasharray="<?php echo $circumference; ?>" stroke-dashoffset="<?php echo $offset; ?>" />
                                <text x="50" y="55" text-anchor="middle" font-size="18" font-weight="bold" fill="#047857"><?php echo $percentage; ?>%</text>
                            </svg>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-500 text-sm mb-1">Jawaban Benar</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $stats['total_benar'] ?? 0; ?> <span class="text-sm text-gray-500">/ <?php echo $stats['total_jawaban'] ?? 0; ?></span></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fas fa-trophy text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500 uppercase font-semibold">Rata-rata Skor</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-4xl font-bold text-purple-600"><?php echo round($stats['rata_skor'] ?? 0, 1); ?>%</p>
                        <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full gradient-primary rounded-full" style="width: <?php echo round($stats['rata_skor'] ?? 0, 1); ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>0%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Progress per Kategori -->
        <?php if(!empty($progress_data)): ?>
        <section class="container mx-auto px-4 mb-12 animate-slide-up" style="animation-delay: 0.2s;">
            <div class="bg-white rounded-xl shadow-lg p-8 card-shadow">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Progress Pembelajaran</h2>
                        <p class="text-gray-500 mt-1">Perkembangan pengetahuan Anda per kategori materi</p>
                    </div>
                    <div class="p-2 bg-indigo-100 rounded-full">
                        <i class="fas fa-chart-line text-indigo-600 text-xl"></i>
                    </div>
                </div>
                <div class="space-y-6">
                    <?php foreach($progress_data as $kategori => $persen): ?>
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="font-medium text-gray-700"><?php echo $kategori; ?></span>
                                <div class="flex items-center">
                                    <span class="font-semibold text-indigo-600"><?php echo $persen; ?>%</span>
                                    <?php if($persen >= 80): ?>
                                        <span class="ml-2 text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Sangat Baik</span>
                                    <?php elseif($persen >= 60): ?>
                                        <span class="ml-2 text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Baik</span>
                                    <?php elseif($persen >= 40): ?>
                                        <span class="ml-2 text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">Cukup</span>
                                    <?php else: ?>
                                        <span class="ml-2 text-xs px-2 py-1 bg-red-100 text-red-800 rounded-full">Perlu Diperbaiki</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 
                                    <?php if($persen >= 80): ?>
                                        bg-green-500
                                    <?php elseif($persen >= 60): ?>
                                        bg-blue-500
                                    <?php elseif($persen >= 40): ?>
                                        bg-yellow-500
                                    <?php else: ?>
                                        bg-red-500
                                    <?php endif; ?>" 
                                    style="width: <?php echo $persen; ?>%">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Quiz Selection -->
        <section id="quiz-section" class="container mx-auto px-4 mb-12 animate-slide-up" style="animation-delay: 0.3s;">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden card-shadow">
                <div class="gradient-primary p-8 text-white">
                    <h2 class="text-2xl font-bold mb-3">Mulai Kuis Baru</h2>
                    <p class="opacity-90">Pilih kategori dan tingkat kesulitan yang ingin Anda ikuti</p>
                </div>
                <form action="kuis.php" method="GET" class="p-8">
                    <input type="hidden" name="mode" value="mulai">
                    <div class="grid md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <label class="block text-gray-700 font-bold">Kategori</label>
                            <div class="relative">
                                <select name="kategori" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 appearance-none">
                                    <option value="">Semua Kategori</option>
                                    <?php while($row = $result_kategori->fetch_assoc()): ?>
                                        <option value="<?php echo $row['kategori']; ?>"><?php echo $row['kategori']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="block text-gray-700 font-bold">Tingkat Kesulitan</label>
                            <div class="relative">
                                <select name="tingkat" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 appearance-none">
                                    <option value="">Semua Tingkat</option>
                                    <?php while($row = $result_tingkat->fetch_assoc()): ?>
                                        <option value="<?php echo $row['tingkat_kesulitan']; ?>">
                                            <?php echo ucfirst($row['tingkat_kesulitan']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 text-center">
                        <button type="submit" class="gradient-primary text-white px-8 py-3 rounded-lg text-lg font-semibold hover:opacity-90 transition shadow-lg hover:shadow-xl">
                            <i class="fas fa-play-circle mr-2"></i>Mulai Kuis Sekarang
                        </button>
                        <p class="text-sm text-gray-500 mt-3">Kuis terdiri dari 5 soal acak sesuai pilihan Anda</p>
                    </div>
                </form>
            </div>
        </section>

        <!-- Tips Section -->
        <section class="container mx-auto px-4 mb-12 animate-slide-up" style="animation-delay: 0.4s;">
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow">
                    <div class="h-3 bg-blue-500"></div>
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                            <i class="fas fa-lightbulb text-blue-600 text-xl"></i>
                        </div>
                        <h3 class="font-bold text-lg mb-3 text-gray-800">Tips Mengerjakan Kuis</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                                <span>Baca pertanyaan dengan teliti sebelum menjawab</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                                <span>Perhatikan detail dan kata kunci dalam pertanyaan</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-blue-500 mt-1 mr-2"></i>
                                <span>Jangan terburu-buru dalam memilih jawaban</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow">
                    <div class="h-3 bg-purple-500"></div>
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mb-4">
                            <i class="fas fa-book text-purple-600 text-xl"></i>
                        </div>
                        <h3 class="font-bold text-lg mb-3 text-gray-800">Persiapan Sebelum Kuis</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-purple-500 mt-1 mr-2"></i>
                                <span>Pelajari materi terkait di menu Materi</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-purple-500 mt-1 mr-2"></i>
                                <span>Catat informasi penting dari setiap materi</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-purple-500 mt-1 mr-2"></i>
                                <span>Pahami konsep dasar Keluarga Berencana</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow">
                    <div class="h-3 bg-pink-500"></div>
                    <div class="p-6">
                        <div class="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center mb-4">
                            <i class="fas fa-chart-pie text-pink-600 text-xl"></i>
                        </div>
                        <h3 class="font-bold text-lg mb-3 text-gray-800">Manfaat Mengikuti Kuis</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pink-500 mt-1 mr-2"></i>
                                <span>Mengevaluasi pemahaman tentang KB</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pink-500 mt-1 mr-2"></i>
                                <span>Memperkuat konsep yang sudah dipelajari</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-pink-500 mt-1 mr-2"></i>
                                <span>Mengidentifikasi area yang perlu dipelajari lagi</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

    <?php elseif($mode == 'mulai'): ?>
        <!-- Quiz Form -->
        <section class="gradient-primary text-white py-8 relative overflow-hidden">
            <div class="absolute inset-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full opacity-10">
                    <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>
            <div class="container mx-auto px-4 z-10 relative">
                <h1 class="text-3xl font-bold mb-2">Kuis Penyuluhan KB</h1>
                <p class="text-indigo-100">Jawab semua pertanyaan dengan benar untuk mendapatkan skor terbaik</p>
            </div>
        </section>
        
        <section class="container mx-auto px-4 -mt-6 relative z-20 mb-12">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex flex-wrap items-center justify-between">
                        <div class="flex items-center mb-4 md:mb-0">
                            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                                <i class="fas fa-question-circle text-indigo-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-xl text-gray-800">Pertanyaan Kuis</h2>
                                <p class="text-gray-500 text-sm">
                                    <?php 
                                    if($kategori) echo 'Kategori: ' . $kategori; 
                                    if($kategori && $tingkat) echo ' | ';
                                    if($tingkat) echo 'Tingkat: ' . ucfirst($tingkat);
                                    if(!$kategori && !$tingkat) echo 'Semua kategori dan tingkat kesulitan';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="text-center mr-6">
                                <p class="text-sm text-gray-500 mb-1">Total Soal</p>
                                <p class="font-bold text-indigo-600 text-xl"><?php echo $result_soal->num_rows; ?></p>
                            </div>
                            <a href="kuis.php" class="bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium px-4 py-2 rounded-lg transition flex items-center">
                                <i class="fas fa-times mr-2"></i>Batal
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="mb-6 bg-gray-100 rounded-full h-3 overflow-hidden">
                        <div class="gradient-primary h-3 rounded-full progress-bar" style="width: 0%"></div>
                    </div>
                    
                    <form method="POST" action="" id="quizForm">
                        <?php if($result_soal->num_rows > 0): ?>
                            <div class="space-y-8">
                                <?php $no = 1; while($soal = $result_soal->fetch_assoc()): ?>
                                    <div class="p-6 border border-gray-200 rounded-xl hover:shadow-md transition quiz-question">
                                        <div class="flex flex-wrap justify-between items-start mb-4">
                                            <h3 class="font-bold text-lg text-gray-800 mb-2 md:mb-0 pr-4 flex-1">
                                                <span class="w-8 h-8 inline-flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-full mr-3"><?php echo $no; ?></span>
                                                <?php echo $soal['pertanyaan']; ?>
                                            </h3>
                                            <span class="px-3 py-1 text-xs rounded-full font-medium inline-flex items-center
                                                <?php 
                                                $tingkat_color = $soal['tingkat_kesulitan'] == 'mudah' ? 'bg-green-100 text-green-700' : 
                                                            ($soal['tingkat_kesulitan'] == 'sedang' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                                                echo $tingkat_color;
                                                ?>">
                                                <i class="fas <?php 
                                                    if($soal['tingkat_kesulitan'] == 'mudah') echo 'fa-smile';
                                                    elseif($soal['tingkat_kesulitan'] == 'sedang') echo 'fa-meh';
                                                    else echo 'fa-frown';
                                                ?> mr-1"></i>
                                                <?php echo ucfirst($soal['tingkat_kesulitan']); ?>
                                            </span>
                                        </div>
                                        
                                        <?php if(isset($soal['kategori']) && $soal['kategori']): ?>
                                            <div class="mb-4">
                                                <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full">
                                                    <?php echo $soal['kategori']; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="grid gap-3">
                                            <label class="flex items-center p-3 border border-gray-200 rounded-lg option-hover cursor-pointer option-label">
                                                <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" value="a" required class="mr-3 h-5 w-5 text-indigo-600 focus:ring-indigo-500 cursor-pointer option-input">
                                                <div class="flex items-center">
                                                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-medium mr-3">A</span>
                                                    <span><?php echo $soal['opsi_a']; ?></span>
                                                </div>
                                            </label>
                                            <label class="flex items-center p-3 border border-gray-200 rounded-lg option-hover cursor-pointer option-label">
                                                <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" value="b" required class="mr-3 h-5 w-5 text-indigo-600 focus:ring-indigo-500 cursor-pointer option-input">
                                                <div class="flex items-center">
                                                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-medium mr-3">B</span>
                                                    <span><?php echo $soal['opsi_b']; ?></span>
                                                </div>
                                            </label>
                                            <label class="flex items-center p-3 border border-gray-200 rounded-lg option-hover cursor-pointer option-label">
                                                <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" value="c" required class="mr-3 h-5 w-5 text-indigo-600 focus:ring-indigo-500 cursor-pointer option-input">
                                                <div class="flex items-center">
                                                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-medium mr-3">C</span>
                                                    <span><?php echo $soal['opsi_c']; ?></span>
                                                </div>
                                            </label>
                                            <?php if($soal['opsi_d']): ?>
                                                <label class="flex items-center p-3 border border-gray-200 rounded-lg option-hover cursor-pointer option-label">
                                                    <input type="radio" name="jawaban[<?php echo $soal['id']; ?>]" value="d" required class="mr-3 h-5 w-5 text-indigo-600 focus:ring-indigo-500 cursor-pointer option-input">
                                                    <div class="flex items-center">
                                                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-medium mr-3">D</span>
                                                        <span><?php echo $soal['opsi_d']; ?></span>
                                                    </div>
                                                </label>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php $no++; endwhile; ?>
                            </div>
                            
                            <div class="flex items-center justify-between mt-10">
                                <a href="kuis.php" class="text-gray-700 hover:text-indigo-600 font-medium transition flex items-center">
                                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar Kuis
                                </a>
                                <button type="submit" name="submit_kuis" class="gradient-primary text-white px-8 py-3 rounded-lg hover:opacity-90 transition shadow-lg hover:shadow-xl flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i>Submit Jawaban
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12 bg-gray-50 rounded-lg">
                                <div class="w-20 h-20 mx-auto bg-gray-200 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-exclamation-circle text-gray-400 text-3xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-700 mb-2">Tidak Ada Soal Tersedia</h3>
                                <p class="text-gray-600 mb-6">Tidak ada soal untuk kategori/tingkat yang dipilih.</p>
                                <a href="kuis.php" class="gradient-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition inline-flex items-center">
                                    <i class="fas fa-arrow-left mr-2"></i>Kembali ke Daftar Kuis
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </section>

    <?php elseif($mode == 'hasil'): ?>
        <!-- Quiz Result -->
        <section class="gradient-primary text-white py-8 relative overflow-hidden">
            <div class="absolute inset-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full opacity-10">
                    <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>
            <div class="container mx-auto px-4 z-10 relative">
                <h1 class="text-3xl font-bold mb-2">Hasil Kuis</h1>
                <p class="text-indigo-100">Skor yang Anda dapatkan dari kuis yang baru saja diselesaikan</p>
            </div>
        </section>
        
        <section class="container mx-auto px-4 -mt-6 relative z-20 mb-12">
            <div class="bg-white rounded-xl shadow-lg p-8 text-center animate-slide-up">
                <?php
                $score = $_GET['score'] ?? 0;
                $total = $_GET['total'] ?? 0;
                $percentage = $total > 0 ? round(($score / $total) * 100, 1) : 0;
                ?>
                
                <div class="max-w-lg mx-auto">
                    <div class="mb-8">
                        <?php if($percentage >= 80): ?>
                            <div id="confetti-container"></div>
                            <div class="p-4 bg-green-100 text-green-700 rounded-full inline-block mb-4">
                                <i class="fas fa-trophy text-5xl"></i>
                            </div>
                            <h2 class="text-4xl font-bold text-green-700 mb-2">Excellent!</h2>
                            <p class="text-gray-600">Pengetahuan Anda tentang KB sangat baik. Teruskan!</p>
                        <?php elseif($percentage >= 60): ?>
                            <div class="p-4 bg-blue-100 text-blue-700 rounded-full inline-block mb-4">
                                <i class="fas fa-thumbs-up text-5xl"></i>
                            </div>
                            <h2 class="text-4xl font-bold text-blue-700 mb-2">Good Job!</h2>
                            <p class="text-gray-600">Pengetahuan Anda cukup baik. Terus tingkatkan!</p>
                        <?php else: ?>
                            <div class="p-4 bg-orange-100 text-orange-700 rounded-full inline-block mb-4">
                                <i class="fas fa-book-open text-5xl"></i>
                            </div>
                            <h2 class="text-4xl font-bold text-orange-700 mb-2">Keep Learning!</h2>
                            <p class="text-gray-600">Belajar lebih giat lagi. Anda pasti bisa!</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-8 flex justify-center">
                        <div class="progress-ring-container" style="width: 150px; height: 150px;">
                            <?php 
                            $circumference = 2 * 3.14159 * 65;
                            $offset = $circumference - ($percentage / 100) * $circumference;
                            ?>
                            <svg class="w-full h-full" viewBox="0 0 150 150">
                                <circle cx="75" cy="75" r="65" stroke="#e5e7eb" stroke-width="14" fill="none" />
                                <circle class="progress-ring-circle" cx="75" cy="75" r="65" 
                                    <?php if($percentage >= 80): ?>
                                        stroke="#10b981"
                                    <?php elseif($percentage >= 60): ?>
                                        stroke="#3b82f6"
                                    <?php else: ?>
                                        stroke="#f97316"
                                    <?php endif; ?>
                                    stroke-width="14" fill="none" 
                                    stroke-dasharray="<?php echo $circumference; ?>" 
                                    stroke-dashoffset="<?php echo $offset; ?>" />
                                <text x="75" y="75" text-anchor="middle" font-size="24" font-weight="bold" 
                                    <?php if($percentage >= 80): ?>
                                        fill="#10b981"
                                    <?php elseif($percentage >= 60): ?>
                                        fill="#3b82f6"
                                    <?php else: ?>
                                        fill="#f97316"
                                    <?php endif; ?>
                                    alignment-baseline="middle"><?php echo $percentage; ?>%</text>
                                <text x="75" y="100" text-anchor="middle" font-size="14" fill="#6b7280" alignment-baseline="middle">Skor Anda</text>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="flex justify-center mb-8">
                        <div class="p-3 px-6 rounded-xl bg-indigo-50 flex items-center text-indigo-800">
                            <i class="fas fa-check-circle mr-2 text-indigo-600"></i>
                            <span class="font-medium"><?php echo $score; ?> jawaban benar dari <?php echo $total; ?> soal</span>
                        </div>
                    </div>
                    
                    <!-- Grade Meter -->
                    <div class="mb-10">
                        <div class="relative h-4 bg-gray-200 rounded-full overflow-hidden">
                            <div class="absolute inset-0 flex">
                                <div class="flex-1 bg-red-500"></div>
                                <div class="flex-1 bg-yellow-500"></div>
                                <div class="flex-1 bg-green-500"></div>
                            </div>
                            <div class="absolute top-0 bottom-0 w-1 bg-white" style="left: <?php echo $percentage; ?>%; transform: translateX(-50%);">
                                <div class="absolute -top-6 left-1/2 transform -translate-x-1/2">
                                    <div class="w-4 h-4 bg-white border-2 border-gray-800 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between text-sm mt-2">
                            <span class="text-gray-700">Perlu Belajar</span>
                            <span class="text-gray-700">Cukup</span>
                            <span class="text-gray-700">Sangat Baik</span>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="kuis.php" class="gradient-primary text-white px-8 py-3 rounded-lg hover:opacity-90 transition shadow-lg hover:shadow-xl flex items-center">
                            <i class="fas fa-redo mr-2"></i>Coba Kuis Lain
                        </a>
                        <a href="materi.php" class="bg-indigo-100 text-indigo-700 px-8 py-3 rounded-lg hover:bg-indigo-200 transition flex items-center">
                            <i class="fas fa-book-open mr-2"></i>Belajar Materi
                        </a>
                        <a href="../dashboard_user.php" class="bg-gray-200 text-gray-700 px-8 py-3 rounded-lg hover:bg-gray-300 transition flex items-center">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

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
                        <li><a href="../dashboard_user.php" class="text-gray-400 hover:text-white transition">Dashboard</a></li>
                        <li><a href="materi.php" class="text-gray-400 hover:text-white transition">Materi</a></li>
                        <li><a href="kuis.php" class="text-gray-400 hover:text-white transition">Kuis</a></li>
                        <li><a href="jadwal.php" class="text-gray-400 hover:text-white transition">Jadwal</a></li>
                        <li><a href="profile.php" class="text-gray-400 hover:text-white transition">Profil</a></li>
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
            
            // Handling quiz progress bar
            const progressBar = document.querySelector('.progress-bar');
            if(progressBar && document.querySelectorAll('.quiz-question').length > 0) {
                const questions = document.querySelectorAll('[name^="jawaban"]');
                const totalQuestions = document.querySelectorAll('.quiz-question').length;
                let answered = 0;
                
                // Update progress when option selected
                questions.forEach(question => {
                    question.addEventListener('change', function() {
                        const parentQuestion = this.closest('.quiz-question');
                        const selectedOption = parentQuestion.querySelector('.option-selected');
                        
                        // Remove previously selected option highlight
                        if (selectedOption) {
                            selectedOption.classList.remove('option-selected');
                        }
                        
                        // Highlight selected option
                        this.closest('.option-label').classList.add('option-selected');
                        
                        // Count answered questions and update progress
                        answered = document.querySelectorAll('input[name^="jawaban"]:checked').length;
                        const percentage = (answered / totalQuestions) * 100;
                        progressBar.style.width = percentage + '%';
                    });
                });
            }
            
            // Add confetti animation for high scores
            <?php if($mode == 'hasil' && $percentage >= 80): ?>
            function createConfetti() {
                const container = document.getElementById('confetti-container') || document.body;
                for(let i = 0; i < 150; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    
                    // Random colors for confetti
                    const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#10b981', '#f97316', '#eab308'];
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    
                    // Random position
                    confetti.style.left = Math.random() * 100 + 'vw';
                    
                    // Random animation settings
                    confetti.style.animationDelay = Math.random() * 5 + 's';
                    confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    
                    // Random size
                    const size = Math.random() * 8 + 6;
                    confetti.style.width = size + 'px';
                    confetti.style.height = size + 'px';
                    
                    // Random shape
                    const shape = Math.floor(Math.random() * 3);
                    if (shape === 1) {
                        confetti.style.borderRadius = '50%';
                    } else if (shape === 2) {
                        confetti.style.width = 0;
                        confetti.style.height = 0;
                        confetti.style.borderLeft = size/2 + 'px solid transparent';
                        confetti.style.borderRight = size/2 + 'px solid transparent';
                        confetti.style.borderBottom = size + 'px solid ' + colors[Math.floor(Math.random() * colors.length)];
                        confetti.style.backgroundColor = 'transparent';
                    }
                    
                    container.appendChild(confetti);
                    
                    setTimeout(() => {
                        confetti.remove();
                    }, 8000);
                }
            }
            
            // Create confetti with slight delay for better effect
            setTimeout(createConfetti, 500);
            <?php endif; ?>
        });
    </script>
</body>
</html>