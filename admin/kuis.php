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
            $pertanyaan = clean_input($_POST['pertanyaan']);
            $opsi_a = clean_input($_POST['opsi_a']);
            $opsi_b = clean_input($_POST['opsi_b']);
            $opsi_c = clean_input($_POST['opsi_c']);
            $opsi_d = clean_input($_POST['opsi_d'] ?? '');
            $jawaban_benar = $_POST['jawaban_benar'];
            
            // Handle kategori selection or custom input
            if (isset($_POST['custom_kategori']) && trim($_POST['custom_kategori']) != '' && $_POST['kategori'] == 'other') {
                $kategori = clean_input($_POST['custom_kategori']);
            } else {
                $kategori = clean_input($_POST['kategori']);
            }
            
            $tingkat_kesulitan = $_POST['tingkat_kesulitan'];
            
            $sql = "INSERT INTO kuis (pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar, kategori, tingkat_kesulitan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban_benar, $kategori, $tingkat_kesulitan);
            
            if ($stmt->execute()) {
                $success = "Soal berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan soal!";
            }
        } 
        elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $pertanyaan = clean_input($_POST['pertanyaan']);
            $opsi_a = clean_input($_POST['opsi_a']);
            $opsi_b = clean_input($_POST['opsi_b']);
            $opsi_c = clean_input($_POST['opsi_c']);
            $opsi_d = clean_input($_POST['opsi_d'] ?? '');
            $jawaban_benar = $_POST['jawaban_benar'];
            
            // Handle kategori selection or custom input
            if (isset($_POST['custom_kategori']) && trim($_POST['custom_kategori']) != '' && $_POST['kategori'] == 'other') {
                $kategori = clean_input($_POST['custom_kategori']);
            } else {
                $kategori = clean_input($_POST['kategori']);
            }
            
            $tingkat_kesulitan = $_POST['tingkat_kesulitan'];
            
            $sql = "UPDATE kuis SET pertanyaan = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?, jawaban_benar = ?, kategori = ?, tingkat_kesulitan = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $jawaban_benar, $kategori, $tingkat_kesulitan, $id);
            
            if ($stmt->execute()) {
                $success = "Soal berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate soal!";
            }
        }
        elseif ($_POST['action'] == 'hapus') {
            $id = $_POST['id'];
            $sql = "DELETE FROM kuis WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "Soal berhasil dihapus!";
            } else {
                $error = "Gagal menghapus soal!";
            }
        }
    }
}

// Ambil data kuis
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
$sql = "SELECT * FROM kuis WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND pertanyaan LIKE ?";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $types .= "s";
}

if ($kategori_filter) {
    $sql .= " AND kategori = ?";
    $params[] = $kategori_filter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Ambil kategori untuk filter
$sql_kategori = "SELECT DISTINCT kategori FROM kuis WHERE kategori IS NOT NULL ORDER BY kategori";
$result_kategori = $conn->query($sql_kategori);

// Copy kategori data untuk form input
$kategori_options = [];
while($row_kategori = $result_kategori->fetch_assoc()) {
    $kategori_options[] = $row_kategori['kategori'];
}
// Reset result pointer
$result_kategori->data_seek(0);

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
    <title>Kelola Kuis - Admin Panel</title>
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
                    <a href="kuis.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 text-white">
                        <i class="fas fa-question-circle w-5 mr-3"></i>
                        <span>Kelola Kuis</span>
                    </a>
                    <a href="jadwal.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
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
                            <h1 class="text-2xl md:text-3xl font-bold mb-2">Kelola Kuis</h1>
                            <p class="text-indigo-100">Kelola soal kuis penyuluhan KB</p>
                        </div>
                        <div id="currentDateTime" class="text-sm text-indigo-100 bg-white bg-opacity-10 px-4 py-2 rounded-lg">
                            <?php echo date('l, d F Y - H:i'); ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-fade-in" style="animation-delay: 0.1s;">
                <?php
                // Count total questions
                $sql_total = "SELECT COUNT(*) as total FROM kuis";
                $result_total = $conn->query($sql_total);
                $total_questions = $result_total->fetch_assoc()['total'];
                
                // Count by difficulty
                $sql_difficulty = "SELECT tingkat_kesulitan, COUNT(*) as count FROM kuis GROUP BY tingkat_kesulitan";
                $result_difficulty = $conn->query($sql_difficulty);
                $difficulty_counts = [
                    'mudah' => 0,
                    'sedang' => 0,
                    'sulit' => 0,
                ];
                
                while($row = $result_difficulty->fetch_assoc()) {
                    $difficulty_counts[$row['tingkat_kesulitan']] = $row['count'];
                }
                
                // Count categories
                $sql_categories = "SELECT COUNT(DISTINCT kategori) as total_categories FROM kuis WHERE kategori IS NOT NULL";
                $result_categories = $conn->query($sql_categories);
                $total_categories = $result_categories->fetch_assoc()['total_categories'];
                ?>
                
                <div class="bg-white rounded-xl p-6 shadow-md card-hover">
                    <div class="flex items-center">
                        <div class="rounded-full p-3 gradient-blue text-white mr-4">
                            <i class="fas fa-question-circle text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Soal</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_questions; ?></h3>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Berbagai tingkat kesulitan</span>
                            <span class="text-indigo-500 font-medium"><?php echo $total_questions; ?> soal</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-md card-hover">
                    <div class="flex items-center">
                        <div class="rounded-full p-3 gradient-purple text-white mr-4">
                            <i class="fas fa-tags text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Kategori Soal</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_categories; ?></h3>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Kategori tersedia</span>
                            <span class="text-indigo-500 font-medium"><?php echo $total_categories; ?> kategori</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-md card-hover">
                    <div class="flex items-center">
                        <div class="rounded-full p-3 gradient-green text-white mr-4">
                            <i class="fas fa-layer-group text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Tingkat Kesulitan</p>
                            <h3 class="text-2xl font-bold text-gray-800">3 level</h3>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div class="text-center">
                                <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs font-medium">Mudah</span>
                                <p class="mt-1 font-medium"><?php echo $difficulty_counts['mudah']; ?></p>
                            </div>
                            <div class="text-center">
                                <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs font-medium">Sedang</span>
                                <p class="mt-1 font-medium"><?php echo $difficulty_counts['sedang']; ?></p>
                            </div>
                            <div class="text-center">
                                <span class="px-2 py-1 rounded-full bg-red-100 text-red-800 text-xs font-medium">Sulit</span>
                                <p class="mt-1 font-medium"><?php echo $difficulty_counts['sulit']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                        <p><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-green-500"></i>
                        <p><?php echo $success; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add Kuis Form -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up">
                <h2 class="text-xl font-bold mb-6 flex items-center text-gray-800">
                    <i class="fas fa-plus-circle text-indigo-500 mr-2"></i>
                    Tambah Soal Kuis Baru
                </h2>
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="tambah">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="pertanyaan">
                            Pertanyaan
                        </label>
                        <textarea class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                  id="pertanyaan" name="pertanyaan" rows="3" placeholder="Masukkan pertanyaan..." required></textarea>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="opsi_a">
                                Opsi A
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                   id="opsi_a" type="text" name="opsi_a" placeholder="Opsi A" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="opsi_b">
                                Opsi B
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                   id="opsi_b" type="text" name="opsi_b" placeholder="Opsi B" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="opsi_c">
                                Opsi C
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                   id="opsi_c" type="text" name="opsi_c" placeholder="Opsi C" required>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="opsi_d">
                                Opsi D (Opsional)
                            </label>
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                   id="opsi_d" type="text" name="opsi_d" placeholder="Opsi D">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="jawaban_benar">
                                Jawaban Benar
                            </label>
                            <select class="w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                    id="jawaban_benar" name="jawaban_benar" required>
                                <option value="a">A</option>
                                <option value="b">B</option>
                                <option value="c">C</option>
                                <option value="d">D</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="kategori">
                                Kategori
                            </label>
                            <select class="w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                    id="kategori" name="kategori" onchange="toggleCustomKategori(this, 'custom_kategori_container')" required>
                                <option value="Kontrasepsi">Kontrasepsi</option>
                                <option value="Perencanaan">Perencanaan</option>
                                <option value="Umum">Umum</option>
                                
                                <?php foreach ($kategori_options as $option): ?>
                                    <?php if (!in_array($option, ['Kontrasepsi', 'Perencanaan', 'Umum'])): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <option value="other">Kategori Lainnya (Custom)</option>
                            </select>
                            
                            <div id="custom_kategori_container" class="hidden mt-2">
                                <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                       id="custom_kategori" name="custom_kategori" type="text" placeholder="Masukkan kategori baru">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2" for="tingkat_kesulitan">
                                Tingkat Kesulitan
                            </label>
                            <select class="w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                    id="tingkat_kesulitan" name="tingkat_kesulitan" required>
                                <option value="mudah">Mudah</option>
                                <option value="sedang">Sedang</option>
                                <option value="sulit">Sulit</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <button class="gradient-blue text-white font-medium py-3 px-6 rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition shadow-md flex items-center" 
                                type="submit">
                            <i class="fas fa-plus mr-2"></i>Tambah Soal
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filter Section -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up" style="animation-delay: 0.1s;">
                <form method="GET" class="flex flex-wrap gap-4 items-center">
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Cari soal berdasarkan pertanyaan..." 
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                    </div>
                    <div class="min-w-[200px]">
                        <select name="kategori" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="">Semua Kategori</option>
                            <?php while($kategori = $result_kategori->fetch_assoc()): ?>
                                <option value="<?php echo $kategori['kategori']; ?>" <?php echo $kategori_filter == $kategori['kategori'] ? 'selected' : ''; ?>>
                                    <?php echo $kategori['kategori']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="gradient-primary text-white px-6 py-3 rounded-lg hover:opacity-90 transition shadow-md flex items-center">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                    <?php if($search || $kategori_filter): ?>
                        <a href="kuis.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition shadow-md flex items-center">
                            <i class="fas fa-times mr-2"></i>Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Kuis List -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden animate-slide-up" style="animation-delay: 0.2s;">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center">
                        <i class="fas fa-list-ul text-indigo-500 mr-2"></i>
                        Daftar Soal Kuis
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tingkat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawaban</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">    
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo substr($row['pertanyaan'], 0, 100) . (strlen($row['pertanyaan']) > 100 ? '...' : ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if($row['kategori']): ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                <?php echo $row['kategori']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $tingkat_color = 'green';
                                        if($row['tingkat_kesulitan'] == 'sedang') {
                                            $tingkat_color = 'yellow';
                                        } elseif($row['tingkat_kesulitan'] == 'sulit') {
                                            $tingkat_color = 'red';
                                        }
                                        ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $tingkat_color; ?>-100 text-<?php echo $tingkat_color; ?>-800">
                                            <?php echo ucfirst($row['tingkat_kesulitan']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                            <?php echo strtoupper($row['jawaban_benar']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editKuis(<?php echo $row['id']; ?>, '<?php echo addslashes($row['pertanyaan']); ?>', '<?php echo addslashes($row['opsi_a']); ?>', '<?php echo addslashes($row['opsi_b']); ?>', '<?php echo addslashes($row['opsi_c']); ?>', '<?php echo addslashes($row['opsi_d']); ?>', '<?php echo $row['jawaban_benar']; ?>', '<?php echo addslashes($row['kategori']); ?>', '<?php echo $row['tingkat_kesulitan']; ?>')" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-3 transition">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" onclick="return confirm('Yakin ingin hapus soal ini?')" 
                                                    class="text-red-600 hover:text-red-900 transition">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if($result->num_rows == 0): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                        <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                                        <p>Belum ada soal kuis yang tersedia.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-xl bg-white animate-fade-in">
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <h3 class="text-xl font-bold mb-4 text-gray-800 flex items-center">
                    <i class="fas fa-edit text-indigo-500 mr-2"></i>
                    Edit Soal Kuis
                </h3>
                
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_pertanyaan">
                        Pertanyaan
                    </label>
                    <textarea class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                              id="edit_pertanyaan" name="pertanyaan" rows="3" required></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_opsi_a">
                            Opsi A
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                               id="edit_opsi_a" type="text" name="opsi_a" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_opsi_b">
                            Opsi B
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                               id="edit_opsi_b" type="text" name="opsi_b" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_opsi_c">
                            Opsi C
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                               id="edit_opsi_c" type="text" name="opsi_c" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_opsi_d">
                            Opsi D
                        </label>
                        <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                               id="edit_opsi_d" type="text" name="opsi_d">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_jawaban_benar">
                            Jawaban Benar
                        </label>
                        <select class="w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                id="edit_jawaban_benar" name="jawaban_benar" required>
                            <option value="a">A</option>
                            <option value="b">B</option>
                            <option value="c">C</option>
                            <option value="d">D</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_kategori">
                            Kategori
                        </label>
                        <select class="w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                id="edit_kategori" name="kategori" onchange="toggleCustomKategori(this, 'edit_custom_kategori_container')" required>
                            <option value="Kontrasepsi">Kontrasepsi</option>
                            <option value="Perencanaan">Perencanaan</option>
                            <option value="Umum">Umum</option>
                            
                            <?php foreach ($kategori_options as $option): ?>
                                <?php if (!in_array($option, ['Kontrasepsi', 'Perencanaan', 'Umum'])): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <option value="other">Kategori Lainnya (Custom)</option>
                        </select>
                        
                        <div id="edit_custom_kategori_container" class="hidden mt-2">
                            <input class="w-full bg-gray-50 text-gray-700 border border-gray-300 rounded-lg py-3 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                   id="edit_custom_kategori" name="custom_kategori" type="text" placeholder="Masukkan kategori baru">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2" for="edit_tingkat_kesulitan">
                            Tingkat Kesulitan
                        </label>
                        <select class="w-full bg-gray-50 border border-gray-300 text-gray-700 py-3 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" 
                                id="edit_tingkat_kesulitan" name="tingkat_kesulitan" required>
                            <option value="mudah">Mudah</option>
                            <option value="sedang">Sedang</option>
                            <option value="sulit">Sulit</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-4">
                    <button class="gradient-blue text-white font-medium py-2.5 px-5 rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition flex items-center" 
                            type="submit">
                        <i class="fas fa-save mr-2"></i>Update
                    </button>
                    <button class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2.5 px-5 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition flex items-center" 
                            type="button" onclick="closeEditModal()">
                        <i class="fas fa-times mr-2"></i>Batal
                    </button>
                </div>
            </form>
        </div>
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
    
    // Toggle the custom kategori input field
    function toggleCustomKategori(selectElement, containerId) {
        const container = document.getElementById(containerId);
        if (selectElement.value === 'other') {
            container.classList.remove('hidden');
            container.querySelector('input').setAttribute('required', 'required');
        } else {
            container.classList.add('hidden');
            container.querySelector('input').removeAttribute('required');
        }
    }
    
    function editKuis(id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar, kategori, tingkat_kesulitan) {
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_pertanyaan').value = pertanyaan;
        document.getElementById('edit_opsi_a').value = opsi_a;
        document.getElementById('edit_opsi_b').value = opsi_b;
        document.getElementById('edit_opsi_c').value = opsi_c;
        document.getElementById('edit_opsi_d').value = opsi_d;
        document.getElementById('edit_jawaban_benar').value = jawaban_benar;
        
        // Handle kategori selection or show custom input
        const kategoriSelect = document.getElementById('edit_kategori');
        let kategoriFound = false;
        
        // Try to find the kategori in the dropdown options
        for (let i = 0; i < kategoriSelect.options.length; i++) {
            if (kategoriSelect.options[i].value === kategori) {
                kategoriSelect.selectedIndex = i;
                kategoriFound = true;
                break;
            }
        }
        
        // If not found, select "other" and fill the custom input
        if (!kategoriFound) {
            // Find the "other" option
            for (let i = 0; i < kategoriSelect.options.length; i++) {
                if (kategoriSelect.options[i].value === 'other') {
                    kategoriSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Show and fill the custom input
            document.getElementById('edit_custom_kategori_container').classList.remove('hidden');
            document.getElementById('edit_custom_kategori').value = kategori;
            document.getElementById('edit_custom_kategori').setAttribute('required', 'required');
        } else {
            // Hide the custom input
            document.getElementById('edit_custom_kategori_container').classList.add('hidden');
            document.getElementById('edit_custom_kategori').removeAttribute('required');
        }
        
        document.getElementById('edit_tingkat_kesulitan').value = tingkat_kesulitan;
        
        // Add body class to prevent scrolling when modal is open
        document.body.classList.add('overflow-hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        // Remove body class to re-enable scrolling
        document.body.classList.remove('overflow-hidden');
    }
    </script>
</body>
</html>