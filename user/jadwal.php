<?php
require_once '../config.php';
require_login();

$message = '';
$message_type = '';

// Proses pendaftaran dari dashboard
if (isset($_GET['daftar']) && is_numeric($_GET['daftar'])) {
    $jadwal_id = $_GET['daftar'];
    
    // Cek apakah jadwal sudah penuh
    $sql_check = "SELECT j.kapasitas, j.status, COUNT(p.id) as terisi 
                  FROM jadwal_penyuluhan j
                  LEFT JOIN pendaftaran_penyuluhan p ON j.id = p.jadwal_id
                  WHERE j.id = ?
                  GROUP BY j.id";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $jadwal_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();
    $data = $check_result->fetch_assoc();
    
    if ($data['status'] != 'terbuka') {
        $message = "Pendaftaran untuk acara ini sudah ditutup!";
        $message_type = 'error';
    } elseif ($data['terisi'] >= $data['kapasitas']) {
        $message = "Maaf, acara ini sudah penuh!";
        $message_type = 'error';
    } else {
        // Daftarkan user
        $sql_daftar = "INSERT INTO pendaftaran_penyuluhan (user_id, jadwal_id) VALUES (?, ?)";
        $stmt_daftar = $conn->prepare($sql_daftar);
        $stmt_daftar->bind_param("ii", $_SESSION['user_id'], $jadwal_id);
        
        if ($stmt_daftar->execute()) {
            $message = "Pendaftaran berhasil!";
            $message_type = 'success';
            
            // Update status jadwal jika sudah penuh
            if (($data['terisi'] + 1) >= $data['kapasitas']) {
                $sql_update = "UPDATE jadwal_penyuluhan SET status = 'penuh' WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $jadwal_id);
                $stmt_update->execute();
            }
        } else {
            if ($conn->errno == 1062) { // Duplicate entry
                $message = "Anda sudah terdaftar untuk acara ini!";
                $message_type = 'warning';
            } else {
                $message = "Terjadi kesalahan saat mendaftar!";
                $message_type = 'error';
            }
        }
    }
}

// Proses pendaftaran dari form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['daftar'])) {
    $jadwal_id = $_POST['jadwal_id'];
    
    // Cek apakah jadwal sudah penuh
    $sql_check = "SELECT j.kapasitas, j.status, COUNT(p.id) as terisi 
                  FROM jadwal_penyuluhan j
                  LEFT JOIN pendaftaran_penyuluhan p ON j.id = p.jadwal_id
                  WHERE j.id = ?
                  GROUP BY j.id";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $jadwal_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();
    $data = $check_result->fetch_assoc();
    
    if ($data['status'] != 'terbuka') {
        $message = "Pendaftaran untuk acara ini sudah ditutup!";
        $message_type = 'error';
    } elseif ($data['terisi'] >= $data['kapasitas']) {
        $message = "Maaf, acara ini sudah penuh!";
        $message_type = 'error';
    } else {
        // Daftarkan user
        $sql_daftar = "INSERT INTO pendaftaran_penyuluhan (user_id, jadwal_id, status) VALUES (?, ?, 'terdaftar')";
        $stmt_daftar = $conn->prepare($sql_daftar);
        $stmt_daftar->bind_param("ii", $_SESSION['user_id'], $jadwal_id);
        
        if ($stmt_daftar->execute()) {
            $message = "Pendaftaran berhasil!";
            $message_type = 'success';
            
            // Update status jadwal jika sudah penuh
            if (($data['terisi'] + 1) >= $data['kapasitas']) {
                $sql_update = "UPDATE jadwal_penyuluhan SET status = 'penuh' WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $jadwal_id);
                $stmt_update->execute();
            }
        } else {
            if ($conn->errno == 1062) { // Duplicate entry
                $message = "Anda sudah terdaftar untuk acara ini!";
                $message_type = 'warning';
            } else {
                $message = "Terjadi kesalahan saat mendaftar: " . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// Ambil jadwal penyuluhan yang tersedia
$sql_jadwal = "SELECT j.*, 
                      COUNT(p.id) as terisi,
                      (SELECT COUNT(*) FROM pendaftaran_penyuluhan WHERE user_id = ? AND jadwal_id = j.id) as sudah_daftar
               FROM jadwal_penyuluhan j
               LEFT JOIN pendaftaran_penyuluhan p ON j.id = p.jadwal_id
               WHERE j.tanggal >= NOW() AND j.status != 'dibatalkan'
               GROUP BY j.id
               ORDER BY j.tanggal ASC";
$stmt_jadwal = $conn->prepare($sql_jadwal);
$stmt_jadwal->bind_param("i", $_SESSION['user_id']);
$stmt_jadwal->execute();
$result_jadwal = $stmt_jadwal->get_result();

// Ambil history pendaftaran
$sql_history = "SELECT p.*, j.judul, j.tanggal, j.lokasi, j.status as jadwal_status
                FROM pendaftaran_penyuluhan p
                JOIN jadwal_penyuluhan j ON p.jadwal_id = j.id
                WHERE p.user_id = ?
                ORDER BY j.tanggal DESC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $_SESSION['user_id']);
$stmt_history->execute();
$result_history = $stmt_history->get_result();

// Ambil data user
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
    <title>Jadwal Penyuluhan - Penyuluhan KB</title>
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

    <!-- Hero Section -->
    <section class="gradient-primary text-white py-12 relative overflow-hidden">
        <div class="absolute inset-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full opacity-10">
                <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
        <div class="container mx-auto px-4 z-10 relative animate-fade-in">
            <h1 class="text-4xl font-bold mb-4">Jadwal Penyuluhan KB</h1>
            <p class="text-xl opacity-90">Daftar dan ikuti acara penyuluhan KB kami</p>
        </div>
    </section>

    <!-- Alert Messages -->
    <?php if($message): ?>
        <div class="container mx-auto px-4 py-3 mt-4">
            <div class="rounded-lg p-4 mb-4 <?php 
                if($message_type == 'success') echo 'bg-green-100 text-green-800 border-l-4 border-green-500';
                elseif($message_type == 'warning') echo 'bg-yellow-100 text-yellow-800 border-l-4 border-yellow-500';
                else echo 'bg-red-100 text-red-800 border-l-4 border-red-500';
            ?> animate-fade-in shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas <?php 
                            if($message_type == 'success') echo 'fa-check-circle text-green-600';
                            elseif($message_type == 'warning') echo 'fa-exclamation-triangle text-yellow-600';
                            else echo 'fa-times-circle text-red-600';
                        ?> text-xl mr-3"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium"><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <section class="container mx-auto px-4 py-6 mt-4">
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Jadwal Tersedia -->
            <div class="md:col-span-2 animate-slide-up" style="animation-delay: 0.1s;">
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Jadwal Penyuluhan Tersedia</h2>
                            <p class="text-gray-500 mt-1">Pilih dan daftar pada acara yang Anda minati</p>
                        </div>
                    </div>
                    
                    <?php if($result_jadwal->num_rows > 0): ?>
                        <div class="grid md:grid-cols-2 gap-6">
                            <?php $counter = 0; while($jadwal = $result_jadwal->fetch_assoc()): $counter++; ?>
                                <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover card-shadow card-glow relative" style="animation-delay: <?php echo 0.1 * $counter; ?>s;">
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
                                            <?php
                                            $date_now = new DateTime();
                                            $date_event = new DateTime($jadwal['tanggal']);
                                            $interval = $date_now->diff($date_event);
                                            
                                            $countdown_text = '';
                                            if ($interval->days > 0) {
                                                $countdown_text = 'Dalam ' . $interval->days . ' hari';
                                            } else {
                                                $countdown_text = 'Hari ini!';
                                            }
                                            ?>
                                            
                                            <div class="flex justify-between mb-2">
                                                <span class="inline-block bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full font-medium">
                                                    <?php echo $countdown_text; ?>
                                                </span>
                                                <span class="inline-block <?php echo $jadwal['status'] == 'terbuka' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> text-xs px-2 py-1 rounded-full font-medium">
                                                    <?php echo ucfirst($jadwal['status']); ?>
                                                </span>
                                            </div>
                                            
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
                                                    <span><?php echo $jadwal['terisi']; ?>/<?php echo $jadwal['kapasitas']; ?> peserta</span>
                                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                                        <div class="<?php if($jadwal['terisi'] >= $jadwal['kapasitas']): ?>bg-red-500<?php else: ?>bg-green-500<?php endif; ?> h-2 rounded-full transition-all duration-500" style="width: <?php echo min(100, ($jadwal['terisi'] / $jadwal['kapasitas']) * 100); ?>%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if($jadwal['deskripsi']): ?>
                                            <p class="text-gray-600 mb-4 text-sm"><?php echo substr($jadwal['deskripsi'], 0, 100) . (strlen($jadwal['deskripsi']) > 100 ? '...' : ''); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if($jadwal['sudah_daftar'] > 0): ?>
                                            <button class="w-full bg-green-100 text-green-700 font-medium py-3 px-4 rounded-xl cursor-not-allowed flex items-center justify-center" disabled>
                                                <i class="fas fa-check-circle mr-2"></i>Anda Sudah Terdaftar
                                            </button>
                                        <?php elseif($jadwal['status'] == 'penuh'): ?>
                                            <button class="w-full bg-gray-100 text-gray-500 font-medium py-3 px-4 rounded-xl cursor-not-allowed flex items-center justify-center" disabled>
                                                <i class="fas fa-ban mr-2"></i>Acara Penuh
                                            </button>
                                        <?php else: ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="jadwal_id" value="<?php echo $jadwal['id']; ?>">
                                                <button type="submit" name="daftar" 
                                                        class="w-full gradient-primary text-white font-medium py-3 px-4 rounded-xl hover:opacity-90 transition text-center group">
                                                    <span class="flex items-center justify-center">
                                                        <i class="fas fa-sign-in-alt mr-2 group-hover:animate-pulse"></i>Daftar Sekarang
                                                    </span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                            <i class="fas fa-calendar-times text-6xl text-gray-400 mb-4"></i>
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">Tidak Ada Jadwal</h3>
                            <p class="text-gray-600">Belum ada jadwal penyuluhan yang tersedia saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- History Pendaftaran -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden card-shadow card-hover animate-slide-up" style="animation-delay: 0.3s;">
                    <div class="p-6">
                        <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-history text-indigo-500 mr-2"></i>Riwayat Pendaftaran Anda
                        </h3>
                        
                        <?php if($result_history->num_rows > 0): ?>
                            <div class="space-y-4 max-h-96 overflow-y-auto custom-scrollbar">
                                <?php while($history = $result_history->fetch_assoc()): ?>
                                    <div class="border border-gray-100 rounded-lg p-4 hover:bg-gray-50 transition">
                                        <div class="flex justify-between">
                                            <h4 class="font-medium text-gray-800"><?php echo $history['judul']; ?></h4>
                                            <?php
                                            $status = $history['status'];
                                            $badge_color = $status == 'hadir' ? 'green' : ($status == 'tidak_hadir' ? 'red' : 'blue');
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $badge_color; ?>-100 text-<?php echo $badge_color; ?>-800">
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-500 mt-2">
                                            <div class="flex items-center mr-4">
                                                <i class="fas fa-calendar-day mr-1 text-indigo-500"></i>
                                                <?php echo tanggal_indo($history['tanggal']); ?>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-map-marker-alt mr-1 text-pink-500"></i>
                                                <?php echo substr($history['lokasi'], 0, 15) . (strlen($history['lokasi']) > 15 ? '...' : ''); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if($history['status'] == 'terdaftar' && strtotime($history['tanggal']) > time()): ?>
                                            <div class="mt-3 text-right">
                                                <a href="batal_daftar.php?id=<?php echo $history['id']; ?>" 
                                                   onclick="return confirm('Yakin ingin membatalkan pendaftaran?')"
                                                   class="text-red-600 hover:text-red-800 text-sm font-medium inline-flex items-center">
                                                    <i class="fas fa-times-circle mr-1"></i> Batal Daftar
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-6 bg-gray-50 rounded-lg">
                                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                    <i class="fas fa-history text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-gray-600 mb-3">Belum ada acara yang terdaftar</p>
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
            
            // Animasi untuk progress bar
            const animateProgress = () => {
                document.querySelectorAll('.bg-green-500, .bg-red-500').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 300);
                });
            };
            
            animateProgress();
        });
    </script>
</body>
</html>