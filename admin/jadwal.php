<?php
require_once '../config.php';
require_login();
require_admin();

$error = '';
$success = '';

// Proses tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'tambah') {
            $judul = clean_input($_POST['judul']);
            $tanggal = $_POST['tanggal'];
            $lokasi = clean_input($_POST['lokasi']);
            $deskripsi = clean_input($_POST['deskripsi']);
            $kapasitas = $_POST['kapasitas'];
            
            $sql = "INSERT INTO jadwal_penyuluhan (judul, tanggal, lokasi, deskripsi, kapasitas) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $judul, $tanggal, $lokasi, $deskripsi, $kapasitas);
            
            if ($stmt->execute()) {
                $success = "Jadwal berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan jadwal!";
            }
        } 
        elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $judul = clean_input($_POST['judul']);
            $tanggal = $_POST['tanggal'];
            $lokasi = clean_input($_POST['lokasi']);
            $deskripsi = clean_input($_POST['deskripsi']);
            $kapasitas = $_POST['kapasitas'];
            $status = $_POST['status'] ?? 'terbuka';
            
            $sql = "UPDATE jadwal_penyuluhan SET judul = ?, tanggal = ?, lokasi = ?, deskripsi = ?, kapasitas = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssisi", $judul, $tanggal, $lokasi, $deskripsi, $kapasitas, $status, $id);
            
            if ($stmt->execute()) {
                $success = "Jadwal berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate jadwal!";
            }
        }
        elseif ($_POST['action'] == 'hapus') {
            $id = $_POST['id'];
            $sql = "DELETE FROM jadwal_penyuluhan WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "Jadwal berhasil dihapus!";
            } else {
                $error = "Gagal menghapus jadwal!";
            }
        }
    }
}

// Ambil data admin
$sql_admin = "SELECT * FROM users WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $_SESSION['user_id']);
$stmt_admin->execute();
$admin_data = $stmt_admin->get_result()->fetch_assoc();

// Ambil data jadwal dengan info pendaftaran
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sql = "SELECT j.*, COUNT(p.id) as total_pendaftar
        FROM jadwal_penyuluhan j
        LEFT JOIN pendaftaran_penyuluhan p ON j.id = p.jadwal_id
        WHERE 1=1";
if ($search) {
    $sql .= " AND (j.judul LIKE ? OR j.lokasi LIKE ?)";
    $search_term = "%" . $search . "%";
}
if ($status_filter) {
    $sql .= " AND j.status = ?";
}
$sql .= " GROUP BY j.id ORDER BY j.tanggal DESC";

$stmt = $conn->prepare($sql);
if ($search && $status_filter) {
    $stmt->bind_param("sss", $search_term, $search_term, $status_filter);
} elseif ($search) {
    $stmt->bind_param("ss", $search_term, $search_term);
} elseif ($status_filter) {
    $stmt->bind_param("s", $status_filter);
}
$stmt->execute();
$result = $stmt->get_result();

// Mendapatkan statistik jadwal
$sql_stats = "SELECT 
    COUNT(*) as total_jadwal,
    SUM(CASE WHEN tanggal >= NOW() THEN 1 ELSE 0 END) as jadwal_upcoming,
    SUM(CASE WHEN status = 'terbuka' THEN 1 ELSE 0 END) as jadwal_terbuka
    FROM jadwal_penyuluhan";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// Total pendaftar
$sql_total_pendaftar = "SELECT COUNT(*) as total FROM pendaftaran_penyuluhan";
$result_total_pendaftar = $conn->query($sql_total_pendaftar);
$total_pendaftar = $result_total_pendaftar->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - Admin Panel</title>
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
        
        .gradient-yellow {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .gradient-red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        .status-badge-terbuka {
            @apply px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800;
        }
        
        .status-badge-penuh {
            @apply px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800;
        }
        
        .status-badge-selesai {
            @apply px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800;
        }
        
        .status-badge-dibatalkan {
            @apply px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800;
        }
        
        /* Add tooltip styles */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }

        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 custom-scrollbar">
    <!-- Sidebar and Main Content Wrapper -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 gradient-primary text-white min-h-screen fixed shadow-lg z-10">
            <div class="p-6">
                <div class="flex items-center mb-8">
                    <div class="h-10 w-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white text-xl"></i>
                    </div>
                    <div class="ml-2">
                        <h2 class="text-xl font-bold">Admin Panel</h2>
                        <p class="text-sm opacity-80">Penyuluhan KB</p>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <a href="../dashboard_admin.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="materi.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Kelola Materi</span>
                    </a>
                    <a href="kuis.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-question-circle w-5 mr-3"></i>
                        <span>Kelola Kuis</span>
                    </a>
                    <a href="jadwal.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 text-white">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Kelola Jadwal</span>
                    </a>
                    <a href="users.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola User</span>
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-chart-bar w-5 mr-3"></i>
                        <span>Laporan</span>
                    </a>
                </nav>
            </div>
            
            <div class="absolute bottom-0 w-64 p-6">
                <div class="bg-white bg-opacity-10 p-4 rounded-lg mb-4">
                    <div class="flex items-center">
                        <?php if(isset($admin_data['avatar']) && $admin_data['avatar']): ?>
                            <img src="<?php echo AVATAR_PATH . $admin_data['avatar']; ?>" class="h-10 w-10 rounded-full mr-3 object-cover border-2 border-white border-opacity-40" alt="Avatar">
                        <?php else: ?>
                            <div class="h-10 w-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-3">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-semibold text-white"><?php echo $_SESSION['name']; ?></p>
                            <p class="text-xs text-white text-opacity-80">Administrator</p>
                        </div>
                    </div>
                </div>
                <a href="../logout.php" class="flex items-center justify-center px-4 py-3 bg-red-500 hover:bg-red-600 rounded-lg text-white transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <!-- Hero Section -->
            <section class="gradient-primary text-white py-10 px-8 rounded-2xl mb-8 relative overflow-hidden animate-fade-in">
                <div class="absolute inset-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute top-0 left-0 w-full opacity-10">
                        <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-2">Kelola Jadwal Penyuluhan</h1>
                            <p class="text-indigo-100">Kelola dan atur jadwal acara penyuluhan KB</p>
                        </div>
                        <div class="text-sm text-indigo-100 bg-white bg-opacity-10 px-4 py-2 rounded-lg">
                            <i class="fas fa-clock mr-2"></i>
                            <span id="currentDateTime"><?php echo date('l, d F Y - H:i'); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <?php if($error || $success): ?>
                <div class="mb-6 animate-slide-up">
                    <?php if($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-3"></i>
                                <p><?php echo $error; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-3"></i>
                                <p><?php echo $success; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid md:grid-cols-4 gap-6 mb-8 animate-slide-up">
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-blue rounded-lg text-white">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Total Jadwal</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_jadwal'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-green rounded-lg text-white">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Jadwal Mendatang</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['jadwal_upcoming'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-purple rounded-lg text-white">
                            <i class="fas fa-door-open text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Jadwal Terbuka</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['jadwal_terbuka'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-yellow rounded-lg text-white">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Total Pendaftar</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_pendaftar ?? 0); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tambah Jadwal & Search -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- Tambah Jadwal Form -->
                <div class="md:col-span-2 bg-white rounded-xl shadow-md p-6 animate-slide-up" style="animation-delay: 0.1s;">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-plus-circle text-indigo-500 mr-2"></i>
                        Tambah Jadwal Baru
                    </h2>
                    <form method="POST" action="" class="grid md:grid-cols-2 gap-6">
                        <input type="hidden" name="action" value="tambah">
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="judul">
                                Judul Acara
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                                   id="judul" type="text" name="judul" placeholder="Masukkan judul acara" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="tanggal">
                                Tanggal & Waktu
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                                   id="tanggal" type="datetime-local" name="tanggal" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="lokasi">
                                Lokasi
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                                   id="lokasi" type="text" name="lokasi" placeholder="Masukkan lokasi acara" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="kapasitas">
                                Kapasitas Peserta
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                                   id="kapasitas" type="number" name="kapasitas" min="1" value="50" required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="deskripsi">
                                Deskripsi Acara
                            </label>
                            <textarea class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                                      id="deskripsi" name="deskripsi" rows="3" placeholder="Masukkan deskripsi acara..."></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <button class="gradient-blue text-white font-bold py-3 px-6 rounded-lg hover:opacity-90 focus:outline-none focus:shadow-outline transition flex items-center" 
                                    type="submit">
                                <i class="fas fa-plus mr-2"></i>Tambah Jadwal
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Search Box -->
                <div class="bg-white rounded-xl shadow-md p-6 animate-slide-up" style="animation-delay: 0.2s;">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-search text-indigo-500 mr-2"></i>
                        Cari Jadwal
                    </h2>
                    <form method="GET" class="space-y-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2" for="search">
                                Kata Kunci
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <i class="fas fa-search text-gray-400"></i>
                                </span>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                    id="search" placeholder="Cari berdasarkan judul atau lokasi..." 
                                    class="pl-10 w-full px-4 py-3 bg-gray-50 text-gray-700 border border-gray-300 rounded-lg focus:outline-none focus:bg-white focus:border-indigo-500 transition">
                            </div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button type="submit" class="flex-1 gradient-blue text-white px-4 py-3 rounded-lg hover:opacity-90 transition flex items-center justify-center">
                                <i class="fas fa-search mr-2"></i>Cari
                            </button>
                            <?php if($search): ?>
                                <a href="jadwal.php" class="flex-1 bg-gray-500 text-white px-4 py-3 rounded-lg hover:bg-gray-600 transition flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <h3 class="text-gray-700 font-semibold mb-4">Filter Status</h3>
                        <div class="flex flex-wrap gap-2">
                            <a href="jadwal.php?status=terbuka" class="px-3 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition">
                                <i class="fas fa-door-open mr-1"></i> Terbuka
                            </a>
                            <a href="jadwal.php?status=penuh" class="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition">
                                <i class="fas fa-users mr-1"></i> Penuh
                            </a>
                            <a href="jadwal.php?status=selesai" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-check-circle mr-1"></i> Selesai
                            </a>
                            <a href="jadwal.php?status=dibatalkan" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                                <i class="fas fa-ban mr-1"></i> Dibatalkan
                            </a>
                            <a href="jadwal.php" class="px-3 py-2 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition">
                                <i class="fas fa-list mr-1"></i> Semua
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jadwal List -->
            <div class="bg-white rounded-xl shadow-md animate-slide-up" style="animation-delay: 0.3s;">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-list text-indigo-500 mr-2"></i>
                        Daftar Jadwal
                        <?php if($search): ?>
                            <span class="ml-2 text-sm font-normal text-gray-500">
                                - Hasil pencarian untuk "<?php echo htmlspecialchars($search); ?>"
                            </span>
                        <?php elseif($status_filter): ?>
                            <span class="ml-2 text-sm font-normal text-gray-500">
                                - Filter: Status "<?php echo ucfirst(htmlspecialchars($status_filter)); ?>"
                            </span>
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendaftar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo $row['judul']; ?></div>
                                            <?php if($row['deskripsi']): ?>
                                                <div class="text-sm text-gray-500"><?php echo substr($row['deskripsi'], 0, 50) . (strlen($row['deskripsi']) > 50 ? '...' : ''); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar-day mr-2 text-indigo-400"></i>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo tanggal_indo($row['tanggal']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($row['tanggal'])); ?> WIB</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-map-marker-alt mr-2 text-pink-500"></i>
                                                <span class="text-gray-700"><?php echo $row['lokasi']; ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-users mr-2 text-blue-500"></i>
                                                <span class="text-gray-700"><?php echo $row['total_pendaftar']; ?>/<?php echo $row['kapasitas']; ?></span>
                                            </div>
                                            <div class="mt-2">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <?php 
                                                    $percentage = ($row['total_pendaftar']/$row['kapasitas'])*100;
                                                    $barColor = "bg-blue-500";
                                                    if ($percentage >= 90) {
                                                        $barColor = "bg-red-500";
                                                    } elseif ($percentage >= 70) {
                                                        $barColor = "bg-yellow-500";
                                                    } elseif ($percentage >= 50) {
                                                        $barColor = "bg-green-500";
                                                    }
                                                    ?>
                                                    <div class="<?php echo $barColor; ?> h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status = $row['status'];
                                            $statusColor = '';
                                            $statusIcon = '';
                                            
                                            switch($status) {
                                                case 'terbuka':
                                                    $statusColor = 'status-badge-terbuka';
                                                    $statusIcon = 'fa-door-open';
                                                    break;
                                                case 'penuh':
                                                    $statusColor = 'status-badge-penuh';
                                                    $statusIcon = 'fa-users';
                                                    break;
                                                case 'selesai':
                                                    $statusColor = 'status-badge-selesai';
                                                    $statusIcon = 'fa-check-circle';
                                                    break;
                                                case 'dibatalkan':
                                                    $statusColor = 'status-badge-dibatalkan';
                                                    $statusIcon = 'fa-ban';
                                                    break;
                                                default:
                                                    $statusColor = 'status-badge-selesai';
                                                    $statusIcon = 'fa-question-circle';
                                            }
                                            ?>
                                            <span class="<?php echo $statusColor; ?> inline-flex items-center">
                                                <i class="fas <?php echo $statusIcon; ?> mr-1"></i>
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="editJadwal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['judul']); ?>', '<?php echo date('Y-m-d\TH:i', strtotime($row['tanggal'])); ?>', '<?php echo addslashes($row['lokasi']); ?>', '<?php echo $row['kapasitas']; ?>', '<?php echo addslashes($row['deskripsi']); ?>', '<?php echo $row['status']; ?>')" 
                                                        class="tooltip text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50 transition">
                                                    <span class="tooltiptext">Edit</span>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="peserta_jadwal.php?id=<?php echo $row['id']; ?>" 
                                                class="tooltip text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50 transition">
                                                    <span class="tooltiptext">Lihat Peserta</span>
                                                    <i class="fas fa-list-ul"></i>
                                                </a>
                                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?');">
                                                    <input type="hidden" name="action" value="hapus">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" class="tooltip text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition">
                                                        <span class="tooltiptext">Hapus</span>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="text-5xl text-gray-300 mb-4">
                                                <i class="fas fa-calendar-times"></i>
                                            </div>
                                            <h3 class="text-xl font-medium text-gray-500 mb-1">Tidak Ada Jadwal</h3>
                                            <?php if($search): ?>
                                                <p class="text-gray-400">Tidak ada jadwal yang cocok dengan pencarian "<?php echo htmlspecialchars($search); ?>"</p>
                                                <a href="jadwal.php" class="mt-3 inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-800 rounded-lg hover:bg-indigo-200 transition">
                                                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Lengkap
                                                </a>
                                            <?php elseif($status_filter): ?>
                                                <p class="text-gray-400">Tidak ada jadwal dengan status "<?php echo ucfirst(htmlspecialchars($status_filter)); ?>"</p>
                                                <a href="jadwal.php" class="mt-3 inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-800 rounded-lg hover:bg-indigo-200 transition">
                                                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Lengkap
                                                </a>
                                            <?php else: ?>
                                                <p class="text-gray-400">Belum ada jadwal yang ditambahkan</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($result->num_rows > 10): ?>
                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Menampilkan <?php echo $result->num_rows; ?> jadwal
                    </div>
                    <div class="flex space-x-1">
                        <a href="#" class="px-3 py-1 bg-white border border-gray-300 rounded text-gray-600 hover:bg-gray-50">Sebelumnya</a>
                        <a href="#" class="px-3 py-1 bg-indigo-500 border border-indigo-500 rounded text-white">1</a>
                        <a href="#" class="px-3 py-1 bg-white border border-gray-300 rounded text-gray-600 hover:bg-gray-50">Selanjutnya</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl shadow-xl w-11/12 md:w-3/4 lg:w-1/2 max-h-screen overflow-y-auto animate-slide-up">
            <div class="gradient-primary text-white py-4 px-6 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Jadwal
                </h3>
                <button type="button" onclick="closeEditModal()" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" class="p-6 space-y-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_judul">
                            Judul Acara
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                               id="edit_judul" type="text" name="judul" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_tanggal">
                            Tanggal & Waktu
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                               id="edit_tanggal" type="datetime-local" name="tanggal" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_lokasi">
                            Lokasi
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                               id="edit_lokasi" type="text" name="lokasi" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_kapasitas">
                            Kapasitas
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                               id="edit_kapasitas" type="number" name="kapasitas" min="1" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_status">
                            Status
                        </label>
                        <div class="relative">
                            <select class="block appearance-none w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                                    id="edit_status" name="status" required>
                                <option value="terbuka">Terbuka</option>
                                <option value="penuh">Penuh</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="edit_deskripsi">
                            Deskripsi
                        </label>
                        <textarea class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-indigo-500 transition" 
                                  id="edit_deskripsi" name="deskripsi" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-between pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                    <button type="submit" class="gradient-blue text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Update clock function
    function updateClock() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        };
        document.getElementById('currentDateTime').textContent = now.toLocaleDateString('id-ID', options).replace(',', ' -');
        setTimeout(updateClock, 1000);
    }
    
    // Start the clock when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        updateClock();
    });
    
    function editJadwal(id, judul, tanggal, lokasi, kapasitas, deskripsi, status) {
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_judul').value = judul;
        document.getElementById('edit_tanggal').value = tanggal;
        document.getElementById('edit_lokasi').value = lokasi;
        document.getElementById('edit_kapasitas').value = kapasitas;
        document.getElementById('edit_deskripsi').value = deskripsi;
        document.getElementById('edit_status').value = status;
        
        // Add fade-in animation
        document.querySelector('#editModal > div').classList.add('animate-fade-in');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
    </script>
</body>
</html>