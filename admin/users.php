<?php
require_once '../config.php';
require_login();
require_admin();

// Proses hapus user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        
        // Hapus user (kecuali admin)
        if ($id != $_SESSION['user_id']) {
            $sql_delete = "DELETE FROM users WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $id);
            
            if ($stmt_delete->execute()) {
                $success = "User berhasil dihapus!";
            } else {
                $error = "Gagal menghapus user!";
            }
        } else {
            $error = "Tidak bisa menghapus akun sendiri!";
        }
    }
}

// Ambil daftar user
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $search_term = "%" . $search . "%";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($search) {
    $stmt->bind_param("ss", $search_term, $search_term);
}
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Kelola User - Admin Panel</title>
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
                    <a href="jadwal.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-calendar w-5 mr-3"></i>
                        <span>Kelola Jadwal</span>
                    </a>
                    <a href="users.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 text-white">
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
                            <img src="../<?php echo AVATAR_PATH . $admin_data['avatar']; ?>" class="h-10 w-10 rounded-full mr-3 object-cover border-2 border-white border-opacity-40" alt="Avatar">
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
            <!-- Page Header -->
            <section class="gradient-primary text-white py-6 px-8 rounded-2xl mb-8 relative overflow-hidden animate-fade-in">
                <div class="absolute inset-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute top-0 left-0 w-full opacity-10">
                        <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold mb-2">Kelola User</h1>
                            <p class="text-indigo-100">Kelola data pengguna aplikasi Penyuluhan KB</p>
                        </div>
                        <div id="currentDateTime" class="text-sm text-indigo-100 bg-white bg-opacity-10 px-4 py-2 rounded-lg">
                            <?php echo date('l, d F Y - H:i'); ?>
                        </div>
                    </div>
                </div>
            </section>

            <?php if(isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                        <p><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-green-500"></i>
                        <p><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up">
                <form method="GET" class="flex flex-wrap gap-4 items-center">
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Cari berdasarkan nama atau email..." 
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                    </div>
                    <button type="submit" class="gradient-primary text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-md flex items-center">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                    <?php if($search): ?>
                        <a href="users.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition shadow-md flex items-center">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden animate-slide-up" style="animation-delay: 0.1s;">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-users text-indigo-500 mr-2"></i>
                        Daftar Pengguna
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $number = 1; ?>
                            <?php while($user = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $number++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if($user['avatar']): ?>
                                                <img src="../<?php echo AVATAR_PATH . $user['avatar']; ?>" class="h-10 w-10 rounded-full mr-3 object-cover border border-gray-200" alt="Avatar">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                                    <i class="fas fa-user text-indigo-500"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="font-medium text-gray-900"><?php echo $user['name']; ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $user['email']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                              <?php echo $user['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        <?php echo tanggal_indo($user['created_at']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        <?php echo $user['last_login'] ? tanggal_indo($user['last_login']) : 'Belum pernah'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" onclick="return confirm('Yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.')" 
                                                        class="text-red-600 hover:text-red-900 transition flex items-center">
                                                    <i class="fas fa-trash mr-1"></i> Hapus
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 flex items-center">
                                                <i class="fas fa-lock mr-1"></i> Akun sendiri
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if($result->num_rows == 0): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                        <i class="fas fa-users text-gray-400 text-4xl mb-3"></i>
                                        <p>Tidak ada pengguna yang ditemukan.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
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
    </script>
</body>
</html>