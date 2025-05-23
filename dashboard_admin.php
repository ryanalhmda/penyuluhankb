<?php
require_once 'config.php';
require_login();
require_admin();

// Statistik keseluruhan
$sql_total_users = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$result_total_users = $conn->query($sql_total_users);
$total_users = $result_total_users->fetch_assoc()['total'];

$sql_total_materi = "SELECT COUNT(*) as total FROM materi";
$result_total_materi = $conn->query($sql_total_materi);
$total_materi = $result_total_materi->fetch_assoc()['total'];

$sql_total_kuis = "SELECT COUNT(*) as total FROM kuis";
$result_total_kuis = $conn->query($sql_total_kuis);
$total_kuis = $result_total_kuis->fetch_assoc()['total'];

$sql_total_jadwal = "SELECT COUNT(*) as total FROM jadwal_penyuluhan WHERE tanggal >= NOW()";
$result_total_jadwal = $conn->query($sql_total_jadwal);
$total_jadwal = $result_total_jadwal->fetch_assoc()['total'];

// Statistik views materi
$sql_views = "SELECT SUM(views) as total_views FROM materi";
$result_views = $conn->query($sql_views);
$total_views = $result_views->fetch_assoc()['total_views'] ?? 0;

// Jadwal terdekat
$sql_jadwal_terdekat = "SELECT j.*, COUNT(p.id) as terisi 
                        FROM jadwal_penyuluhan j
                        LEFT JOIN pendaftaran_penyuluhan p ON j.id = p.jadwal_id
                        WHERE j.tanggal >= NOW() 
                        GROUP BY j.id
                        ORDER BY j.tanggal ASC
                        LIMIT 5";
$result_jadwal_terdekat = $conn->query($sql_jadwal_terdekat);

// User terbaru
$sql_user_terbaru = "SELECT name, email, created_at, avatar
                     FROM users 
                     WHERE role = 'user' 
                     ORDER BY created_at DESC 
                     LIMIT 5";
$result_user_terbaru = $conn->query($sql_user_terbaru);

// Chart data - Pendaftaran per bulan
$sql_chart = "SELECT MONTH(tanggal_daftar) as bulan, COUNT(*) as jumlah
              FROM pendaftaran_penyuluhan
              WHERE YEAR(tanggal_daftar) = YEAR(NOW())
              GROUP BY MONTH(tanggal_daftar)
              ORDER BY bulan ASC";
$result_chart = $conn->query($sql_chart);
$chart_data = array_fill(1, 12, 0);
while($row = $result_chart->fetch_assoc()) {
    $chart_data[$row['bulan']] = $row['jumlah'];
}

// Ambil data admin
$sql_admin = "SELECT * FROM users WHERE id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("i", $_SESSION['user_id']);
$stmt_admin->execute();
$admin_data = $stmt_admin->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Penyuluhan KB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="dashboard_admin.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 text-white">
                        <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin/materi.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-book w-5 mr-3"></i>
                        <span>Kelola Materi</span>
                    </a>
                    <a href="admin/kuis.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-question-circle w-5 mr-3"></i>
                        <span>Kelola Kuis</span>
                    </a>
                    <a href="admin/jadwal.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Kelola Jadwal</span>
                    </a>
                    <a href="admin/users.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola User</span>
                    </a>
                    <a href="admin/laporan.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
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
                <a href="logout.php" class="flex items-center justify-center px-4 py-3 bg-red-500 hover:bg-red-600 rounded-lg text-white transition">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <!-- Hero Section -->
            <section class="gradient-primary text-white py-12 px-8 rounded-2xl mb-10 relative overflow-hidden animate-fade-in">
                <div class="absolute inset-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute top-0 left-0 w-full opacity-10">
                        <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold mb-2">Selamat Datang, Admin!</h1>
                            <p class="text-indigo-100">Panel administrasi untuk penyuluhan KB</p>
                        </div>
                        <div class="text-sm text-indigo-100 bg-white bg-opacity-10 px-4 py-2 rounded-lg">
                            <i class="fas fa-clock mr-2"></i>
                            <span id="currentDateTime"><?php echo date('l, d F Y - H:i'); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Cards -->
            <div class="grid md:grid-cols-4 gap-6 mb-10 animate-slide-up">
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-blue rounded-lg text-white">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Total User</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_users); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-100 h-1 rounded-full">
                            <div class="bg-blue-500 h-1 rounded-full" style="width: <?php echo min(100, ($total_users/100)*100); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-green rounded-lg text-white">
                            <i class="fas fa-book text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Total Materi</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_materi); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-100 h-1 rounded-full">
                            <div class="bg-green-500 h-1 rounded-full" style="width: <?php echo min(100, ($total_materi/30)*100); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-purple rounded-lg text-white">
                            <i class="fas fa-question-circle text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Total Soal</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_kuis); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-100 h-1 rounded-full">
                            <div class="bg-purple-500 h-1 rounded-full" style="width: <?php echo min(100, ($total_kuis/50)*100); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-yellow rounded-lg text-white">
                            <i class="fas fa-eye text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Total Views</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_views); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-100 h-1 rounded-full">
                            <div class="bg-yellow-500 h-1 rounded-full" style="width: <?php echo min(100, ($total_views/1000)*100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Lists Row -->
            <div class="grid md:grid-cols-3 gap-8 mb-10">
                <!-- Chart -->
                <div class="md:col-span-2 bg-white rounded-xl shadow-md p-6 card-shadow animate-slide-up" style="animation-delay: 0.1s;">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-chart-line text-indigo-500 mr-2"></i>
                        Pendaftaran Per Bulan (<?php echo date('Y'); ?>)
                    </h2>
                    <canvas id="pendaftaranChart" class="w-full" style="height: 300px;"></canvas>
                </div>

                <!-- Jadwal Terdekat -->
                <div class="bg-white rounded-xl shadow-md p-6 card-shadow animate-slide-up" style="animation-delay: 0.2s;">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-calendar-alt text-indigo-500 mr-2"></i>
                        Jadwal Terdekat
                    </h2>
                    
                    <?php if($result_jadwal_terdekat->num_rows > 0): ?>
                        <div class="space-y-4 max-h-80 overflow-y-auto custom-scrollbar">
                            <?php while($jadwal = $result_jadwal_terdekat->fetch_assoc()): ?>
                                <div class="border-l-4 border-indigo-500 pl-4 py-2 hover:bg-gray-50 transition rounded-r">
                                    <h3 class="font-medium text-gray-800"><?php echo $jadwal['judul']; ?></h3>
                                    <div class="flex items-center text-sm text-gray-600 mt-1">
                                        <i class="fas fa-calendar mr-2 text-indigo-500"></i>
                                        <?php echo tanggal_indo($jadwal['tanggal']); ?>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600 mt-1">
                                        <i class="fas fa-map-marker-alt mr-2 text-pink-500"></i>
                                        <?php echo $jadwal['lokasi']; ?>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-xs text-gray-500">
                                            Terisi: <?php echo $jadwal['terisi']; ?>/<?php echo $jadwal['kapasitas']; ?>
                                        </span>
                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                            <div class="bg-indigo-500 h-2 rounded-full" style="width: <?php echo ($jadwal['terisi']/$jadwal['kapasitas'])*100; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="admin/jadwal.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium inline-flex items-center">
                                <span>Kelola Jadwal</span>
                                <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center py-6 bg-gray-50 rounded-lg text-center">
                            <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mb-3">
                                <i class="fas fa-calendar-times text-gray-400 text-xl"></i>
                            </div>
                            <p class="text-gray-600 mb-3">Tidak ada jadwal mendatang</p>
                            <a href="admin/jadwal.php" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                                Tambah Jadwal Baru
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center animate-slide-up" style="animation-delay: 0.3s;">
                <i class="fas fa-bolt text-indigo-500 mr-2"></i>
                Aksi Cepat
            </h2>
            
            <div class="grid md:grid-cols-3 gap-6 mb-10 animate-slide-up" style="animation-delay: 0.4s;">
                <a href="admin/materi.php" class="gradient-blue text-white p-6 rounded-xl shadow-md card-hover">
                    <i class="fas fa-book text-3xl mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Kelola Materi</h3>
                    <p class="text-blue-100">Tambah & edit materi penyuluhan</p>
                    <div class="mt-4 bg-white bg-opacity-20 px-4 py-2 rounded-lg inline-flex items-center">
                        <span>Buka</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>
                
                <a href="admin/kuis.php" class="gradient-green text-white p-6 rounded-xl shadow-md card-hover">
                    <i class="fas fa-question-circle text-3xl mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Kelola Kuis</h3>
                    <p class="text-green-100">Tambah & edit soal kuis</p>
                    <div class="mt-4 bg-white bg-opacity-20 px-4 py-2 rounded-lg inline-flex items-center">
                        <span>Buka</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>
                
                <a href="admin/jadwal.php" class="gradient-purple text-white p-6 rounded-xl shadow-md card-hover">
                    <i class="fas fa-calendar text-3xl mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Kelola Jadwal</h3>
                    <p class="text-purple-100">Atur jadwal penyuluhan</p>
                    <div class="mt-4 bg-white bg-opacity-20 px-4 py-2 rounded-lg inline-flex items-center">
                        <span>Buka</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>
            </div>

            <!-- Recent Users -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up" style="animation-delay: 0.5s;">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-users text-indigo-500 mr-2"></i>
                        User Terbaru
                    </h2>
                    <a href="admin/users.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium inline-flex items-center">
                        <span>Lihat Semua</span>
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <?php if($result_user_terbaru->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($user = $result_user_terbaru->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if(isset($user['avatar']) && $user['avatar']): ?>
                                                    <img src="<?php echo AVATAR_PATH . $user['avatar']; ?>" class="h-10 w-10 rounded-full mr-3 object-cover border border-gray-200" alt="Avatar">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                                        <i class="fas fa-user text-indigo-500"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="font-medium text-gray-900"><?php echo $user['name']; ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $user['email']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo tanggal_indo($user['created_at']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="admin/users.php" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 bg-gray-50 rounded-lg">
                        <p class="text-gray-600">Belum ada user baru</p>
                    </div>
                <?php endif; ?>
                </div>
    <script>
        // Real-time clock update
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
        
        // Chart configuration
        const ctx = document.getElementById('pendaftaranChart').getContext('2d');
        const pendaftaranChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Pendaftaran',
                    data: <?php echo json_encode(array_values($chart_data)); ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.5)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            maxTicksLimit: 5
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>