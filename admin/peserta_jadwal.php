<?php
require_once '../config.php';
require_login();
require_admin();

$jadwal_id = $_GET['id'] ?? 0;

// Ambil info jadwal
$sql_jadwal = "SELECT * FROM jadwal_penyuluhan WHERE id = ?";
$stmt_jadwal = $conn->prepare($sql_jadwal);
$stmt_jadwal->bind_param("i", $jadwal_id);
$stmt_jadwal->execute();
$result_jadwal = $stmt_jadwal->get_result();
$jadwal = $result_jadwal->fetch_assoc();

if (!$jadwal) {
    header("Location: jadwal.php");
    exit();
}

// Update status kehadiran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_kehadiran'])) {
    foreach ($_POST['status'] as $pendaftaran_id => $status) {
        $sql_update = "UPDATE pendaftaran_penyuluhan SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $status, $pendaftaran_id);
        $stmt_update->execute();
    }
    $success = "Status kehadiran berhasil diupdate!";
}

// Ambil daftar peserta
$sql_peserta = "SELECT p.*, u.name, u.email, u.phone, u.avatar
                FROM pendaftaran_penyuluhan p
                JOIN users u ON p.user_id = u.id
                WHERE p.jadwal_id = ?
                ORDER BY p.tanggal_daftar ASC";
$stmt_peserta = $conn->prepare($sql_peserta);
$stmt_peserta->bind_param("i", $jadwal_id);
$stmt_peserta->execute();
$result_peserta = $stmt_peserta->get_result();

// Statistik peserta
$total_peserta = $result_peserta->num_rows;
$status_counts = [
    'terdaftar' => 0,
    'hadir' => 0,
    'tidak_hadir' => 0
];

if($total_peserta > 0) {
    $result_peserta->data_seek(0);
    while($row = $result_peserta->fetch_assoc()) {
        if(isset($status_counts[$row['status']])) {
            $status_counts[$row['status']]++;
        }
    }
    $result_peserta->data_seek(0);
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
    <title>Daftar Peserta - Admin Panel</title>
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
            transform: translateY(-5px);
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
        
        .status-badge {
            @apply text-xs font-medium px-2.5 py-1 rounded-full;
        }
        
        .status-terdaftar {
            @apply bg-yellow-100 text-yellow-800;
        }
        
        .status-hadir {
            @apply bg-green-100 text-green-800;
        }
        
        .status-tidak_hadir {
            @apply bg-red-100 text-red-800;
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
            <!-- Header Section -->
            <section class="gradient-primary text-white py-10 px-8 rounded-2xl mb-8 relative overflow-hidden animate-fade-in">
                <div class="absolute inset-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute top-0 left-0 w-full opacity-10">
                        <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold mb-2">Daftar Peserta</h1>
                            <p class="text-indigo-100 text-lg"><?php echo $jadwal['judul']; ?></p>
                        </div>
                        <a href="jadwal.php" class="bg-white bg-opacity-20 text-white px-6 py-2 rounded-lg hover:bg-opacity-30 transition flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </section>

            <?php if(isset($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md animate-fade-in">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm"><?php echo $success; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 animate-slide-up">
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-blue rounded-lg text-white">
                            <i class="fas fa-calendar-day text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Tanggal & Waktu</h3>
                            <p class="text-lg font-bold text-gray-800"><?php echo tanggal_indo($jadwal['tanggal']); ?></p>
                            <p class="text-sm text-gray-600"><?php echo date('H:i', strtotime($jadwal['tanggal'])); ?> WIB</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-purple rounded-lg text-white">
                            <i class="fas fa-map-marker-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Lokasi</h3>
                            <p class="text-lg font-bold text-gray-800"><?php echo $jadwal['lokasi']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-green rounded-lg text-white">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Kapasitas</h3>
                            <p class="text-lg font-bold text-gray-800"><?php echo $total_peserta; ?>/<?php echo $jadwal['kapasitas']; ?> peserta</p>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo min(100, ($total_peserta/$jadwal['kapasitas'])*100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                    <div class="flex items-center">
                        <div class="p-3 gradient-yellow rounded-lg text-white">
                            <i class="fas fa-clipboard-check text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Status</h3>
                            <?php
                            $status_color = [
                                'terbuka' => 'green',
                                'penuh' => 'blue',
                                'selesai' => 'gray',
                                'dibatalkan' => 'red'
                            ][$jadwal['status']] ?? 'gray';
                            ?>
                            <p class="text-lg font-bold text-gray-800">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                    <?php echo ucfirst($jadwal['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Peserta Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-slide-up" style="animation-delay: 0.2s;">
                <div class="bg-white rounded-xl shadow-md p-6 card-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <i class="fas fa-user-clock text-yellow-700 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Terdaftar</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $status_counts['terdaftar']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i class="fas fa-user-check text-green-700 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Hadir</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $status_counts['hadir']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md p-6 card-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 rounded-lg">
                            <i class="fas fa-user-times text-red-700 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm text-gray-500 uppercase font-semibold">Tidak Hadir</h3>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $status_counts['tidak_hadir']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Peserta List -->
            <div class="bg-white rounded-xl shadow-md card-shadow animate-slide-up" style="animation-delay: 0.3s;">
                <form method="POST">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-users text-indigo-500 mr-2"></i>
                            Daftar Peserta Terdaftar
                        </h2>
                        <div class="flex gap-4">
                            <a href="export_peserta.php?jadwal_id=<?php echo $jadwal_id; ?>" 
                               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center text-sm">
                                <i class="fas fa-file-csv mr-2"></i>Export CSV
                            </a>
                            <button type="submit" name="update_kehadiran" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center text-sm">
                                <i class="fas fa-save mr-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peserta</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if($result_peserta->num_rows > 0): ?>
                                    <?php $no = 1; while($peserta = $result_peserta->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $no++; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if(isset($peserta['avatar']) && $peserta['avatar']): ?>
                                                        <img src="../<?php echo AVATAR_PATH . $peserta['avatar']; ?>" class="h-10 w-10 rounded-full mr-3 object-cover border-2 border-white border-opacity-40" alt="Avatar">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                                            <i class="fas fa-user text-indigo-500"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="font-medium text-gray-900"><?php echo $peserta['name']; ?></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $peserta['email']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $peserta['phone'] ?? '-'; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo tanggal_indo($peserta['tanggal_daftar']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <select name="status[<?php echo $peserta['id']; ?>]" 
                                                        class="block appearance-none bg-white border border-gray-300 rounded-lg py-2 px-4 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                                    <option value="terdaftar" <?php echo $peserta['status'] == 'terdaftar' ? 'selected' : ''; ?>>
                                                        Terdaftar
                                                    </option>
                                                    <option value="hadir" <?php echo $peserta['status'] == 'hadir' ? 'selected' : ''; ?>>
                                                        Hadir
                                                    </option>
                                                    <option value="tidak_hadir" <?php echo $peserta['status'] == 'tidak_hadir' ? 'selected' : ''; ?>>
                                                        Tidak Hadir
                                                    </option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mb-3">
                                                    <i class="fas fa-users-slash text-gray-400 text-xl"></i>
                                                </div>
                                                <p class="text-gray-500 mb-2">Belum ada peserta yang mendaftar</p>
                                                <a href="jadwal.php" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                                                    Kembali ke Jadwal
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
</body>
</html>