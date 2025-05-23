<?php
require_once '../config.php';
require_login();

// Jika view materi, update view count
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $materi_id = $_GET['view'];
    $sql_view = "UPDATE materi SET views = views + 1 WHERE id = ?";
    $stmt_view = $conn->prepare($sql_view);
    $stmt_view->bind_param("i", $materi_id);
    $stmt_view->execute();
    
    // Ambil informasi file materi
    $sql_file = "SELECT file_materi FROM materi WHERE id = ?";
    $stmt_file = $conn->prepare($sql_file);
    $stmt_file->bind_param("i", $materi_id);
    $stmt_file->execute();
    $result_file = $stmt_file->get_result();
    
    if ($file_data = $result_file->fetch_assoc()) {
        if (!empty($file_data['file_materi'])) {
            // Redirect ke file materi
            header("Location: ../" . UPLOAD_PATH . $file_data['file_materi']);
            exit;
        }
    }
}

// Filter kategori
$kategori_filter = $_GET['kategori'] ?? '';
$search = $_GET['search'] ?? '';

// Ambil data materi dengan filter
$sql = "SELECT * FROM materi WHERE 1=1";
$params = [];
$types = "";

if ($kategori_filter) {
    $sql .= " AND kategori = ?";
    $params[] = $kategori_filter;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (judul LIKE ? OR deskripsi LIKE ?)";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql .= " ORDER BY tanggal_upload DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Ambil kategori unik untuk filter
$sql_kategori = "SELECT DISTINCT kategori FROM materi WHERE kategori IS NOT NULL ORDER BY kategori";
$result_kategori = $conn->query($sql_kategori);

// Ambil data user untuk avatar
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

// Function to determine color scheme based on category
function getColorScheme($kategori) {
    switch ($kategori) {
        case 'Edukasi':
            return [
                'gradient' => 'gradient-purple',
                'bg' => 'bg-purple-100',
                'text' => 'text-purple-600',
                'hover-bg' => 'hover:bg-purple-100',
                'btn-bg' => 'bg-purple-600',
                'btn-hover' => 'hover:bg-purple-700',
                'light-bg' => 'bg-purple-100',
                'light-text' => 'text-purple-600',
                'light-hover' => 'hover:bg-purple-200',
                'icon' => 'fa-book'
            ];
        case 'Kontrasepsi':
            return [
                'gradient' => 'gradient-blue',
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-600',
                'hover-bg' => 'hover:bg-blue-100',
                'btn-bg' => 'bg-blue-600',
                'btn-hover' => 'hover:bg-blue-700',
                'light-bg' => 'bg-blue-100',
                'light-text' => 'text-blue-600',
                'light-hover' => 'hover:bg-blue-200',
                'icon' => 'fa-pills'
            ];
        case 'Kesehatan':
            return [
                'gradient' => 'gradient-pink',
                'bg' => 'bg-pink-100',
                'text' => 'text-pink-600',
                'hover-bg' => 'hover:bg-pink-100',
                'btn-bg' => 'bg-pink-600',
                'btn-hover' => 'hover:bg-pink-700',
                'light-bg' => 'bg-pink-100',
                'light-text' => 'text-pink-600',
                'light-hover' => 'hover:bg-pink-200',
                'icon' => 'fa-heartbeat'
            ];
        case 'Perencanaan':
            return [
                'gradient' => 'gradient-green',
                'bg' => 'bg-green-100',
                'text' => 'text-green-600',
                'hover-bg' => 'hover:bg-green-100',
                'btn-bg' => 'bg-green-600',
                'btn-hover' => 'hover:bg-green-700',
                'light-bg' => 'bg-green-100',
                'light-text' => 'text-green-600',
                'light-hover' => 'hover:bg-green-200',
                'icon' => 'fa-calendar-alt'
            ];
        default:
            return [
                'gradient' => 'gradient-gray',
                'bg' => 'bg-gray-100',
                'text' => 'text-gray-600',
                'hover-bg' => 'hover:bg-gray-100',
                'btn-bg' => 'bg-gray-600',
                'btn-hover' => 'hover:bg-gray-700',
                'light-bg' => 'bg-gray-100',
                'light-text' => 'text-gray-600',
                'light-hover' => 'hover:bg-gray-200',
                'icon' => 'fa-file-alt'
            ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi Penyuluhan - Penyuluhan KB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        
        .gradient-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .gradient-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .gradient-pink {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
        }
        
        .gradient-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .gradient-gray {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
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
        
        .tag-cloud span {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            margin: 0.25rem;
            background: #f3f4f6;
            border-radius: 9999px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .tag-cloud span:hover {
            background: #e0e7ff;
            color: #4f46e5;
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
    </style>
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
        <div class="container mx-auto px-4 z-10 relative">
            <div class="animate-fade-in">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Materi Penyuluhan KB</h1>
                <p class="text-xl opacity-90">Akses berbagai materi edukasi untuk pengetahuan KB Anda</p>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="container mx-auto px-4 -mt-8 relative z-20">
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-up">
            <form method="GET" class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Cari materi..." 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>
                <div class="min-w-[200px]">
                    <select name="kategori" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">Semua Kategori</option>
                        <?php while($kategori = $result_kategori->fetch_assoc()): ?>
                            <option value="<?php echo $kategori['kategori']; ?>" <?php echo $kategori_filter == $kategori['kategori'] ? 'selected' : ''; ?>>
                                <?php echo $kategori['kategori']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="gradient-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition shadow-md flex items-center">
                    <i class="fas fa-search mr-2"></i>Cari
                </button>
                <?php if($kategori_filter || $search): ?>
                    <a href="materi.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition shadow-md flex items-center">
                        <i class="fas fa-times mr-2"></i>Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- Materi Grid -->
    <section class="container mx-auto px-4 py-4">
        <?php if($result->num_rows > 0): ?>
            <div class="grid md:grid-cols-3 gap-8">
                <?php $counter = 0; while($materi = $result->fetch_assoc()): $counter++; 
                    $colorScheme = getColorScheme($materi['kategori']);
                ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow animate-slide-up" style="animation-delay: <?php echo 0.1 * ($counter % 3); ?>s;">
                        <div class="h-2 <?php echo $colorScheme['gradient']; ?>"></div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <div class="p-3 rounded-full <?php echo $colorScheme['bg'] . ' ' . $colorScheme['text']; ?>">
                                    <i class="fas <?php echo $colorScheme['icon']; ?>"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-bold text-lg text-gray-800"><?php echo $materi['judul']; ?></h3>
                                    <?php if($materi['kategori']): ?>
                                        <span class="inline-block text-xs px-2 py-1 rounded-full mt-1 <?php echo $colorScheme['bg'] . ' ' . $colorScheme['text']; ?>">
                                            <?php echo $materi['kategori']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p class="text-gray-600 mb-4 line-clamp-3">
                                <?php echo substr($materi['deskripsi'], 0, 150) . (strlen($materi['deskripsi']) > 150 ? '...' : ''); ?>
                            </p>
                            
                            <div class="flex items-center text-sm text-gray-500 mb-4">
                                <div class="flex items-center mr-6">
                                    <i class="fas fa-calendar mr-2 <?php echo $colorScheme['text']; ?>"></i>
                                    <span><?php echo tanggal_indo($materi['tanggal_upload']); ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-eye mr-2 <?php echo $colorScheme['text']; ?>"></i>
                                    <span><?php echo $materi['views']; ?> kali dilihat</span>
                                </div>
                            </div>
                            
                            <?php if($materi['tags']): ?>
                                <div class="tag-cloud mb-4">
                                    <?php foreach(explode(',', $materi['tags']) as $tag): ?>
                                        <span class="<?php echo $colorScheme['text'] . ' ' . $colorScheme['hover-bg']; ?> transition">
                                            #<?php echo trim($tag); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($materi['file_materi']): ?>
                                <div class="flex gap-2">
                                    <a href="materi.php?view=<?php echo $materi['id']; ?>" 
                                       target="_blank"
                                       class="flex-1 <?php echo $colorScheme['btn-bg'] . ' ' . $colorScheme['btn-hover']; ?> text-white py-2 px-4 rounded-lg transition text-center shadow-md flex items-center justify-center group">
                                        <i class="fas fa-eye mr-2 group-hover:animate-pulse"></i>Lihat Materi
                                    </a>
                                    <a href="../<?php echo UPLOAD_PATH . $materi['file_materi']; ?>" download
                                       class="<?php echo $colorScheme['light-bg'] . ' ' . $colorScheme['light-text'] . ' ' . $colorScheme['light-hover']; ?> py-2 px-4 rounded-lg transition shadow-md">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <button class="w-full bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center" disabled>
                                    <i class="fas fa-file-alt mr-2"></i>File Belum Tersedia
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-lg p-8 text-center animate-fade-in">
                <div class="w-20 h-20 bg-gray-100 rounded-full mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-folder-open text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Tidak Ada Materi</h3>
                <p class="text-gray-600 mb-6">Belum ada materi yang sesuai dengan kriteria pencarian Anda.</p>
                <a href="materi.php" class="gradient-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition shadow-md inline-block">
                    <i class="fas fa-sync-alt mr-2"></i>Lihat Semua Materi
                </a>
            </div>
        <?php endif; ?>
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

    <!-- JavaScript -->
    <script>
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
        });
    </script>
</body>
</html>