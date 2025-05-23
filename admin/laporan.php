<?php
require_once '../config.php';
require_login();
require_admin();

// Get date range from query parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Laporan Kuis
$sql_kuis = "SELECT 
    u.name as user_name,
    COUNT(DISTINCT k.id) as total_soal,
    COUNT(*) as total_jawaban,
    SUM(j.benar) as benar,
    AVG(j.benar) * 100 as skor_rata
FROM jawaban_kuis j
JOIN users u ON j.user_id = u.id
JOIN kuis k ON j.kuis_id = k.id
WHERE DATE(j.tanggal_jawab) BETWEEN ? AND ?
GROUP BY j.user_id
ORDER BY skor_rata DESC";

$stmt_kuis = $conn->prepare($sql_kuis);
$stmt_kuis->bind_param("ss", $start_date, $end_date);
$stmt_kuis->execute();
$result_kuis = $stmt_kuis->get_result();

// Laporan Materi Populer
$sql_materi = "SELECT 
    judul,
    kategori,
    views,
    tanggal_upload
FROM materi
ORDER BY views DESC
LIMIT 10";
$result_materi = $conn->query($sql_materi);

// Laporan Pendaftaran Penyuluhan
$sql_pendaftaran = "SELECT 
    j.judul,
    j.tanggal,
    j.lokasi,
    j.kapasitas,
    COUNT(p.id) as total_pendaftar,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'tidak_hadir' THEN 1 ELSE 0 END) as tidak_hadir
FROM jadwal_penyuluhan j
LEFT JOIN pendaftaran_penyuluhan p ON j.id = p.jadwal_id
WHERE DATE(j.tanggal) BETWEEN ? AND ?
GROUP BY j.id
ORDER BY j.tanggal ASC";

$stmt_pendaftaran = $conn->prepare($sql_pendaftaran);
$stmt_pendaftaran->bind_param("ss", $start_date, $end_date);
$stmt_pendaftaran->execute();
$result_pendaftaran = $stmt_pendaftaran->get_result();

// Chart Data - Aktivitas per kategori
$sql_aktivitas = "SELECT 
    'Materi' as jenis,
    kategori,
    COUNT(*) as jumlah
FROM materi
WHERE kategori IS NOT NULL
GROUP BY kategori
UNION ALL
SELECT 
    'Kuis' as jenis,
    kategori,
    COUNT(*) as jumlah
FROM kuis
WHERE kategori IS NOT NULL
GROUP BY kategori
ORDER BY jenis, jumlah DESC";

$result_aktivitas = $conn->query($sql_aktivitas);
$aktivitas_data = [];
while($row = $result_aktivitas->fetch_assoc()) {
    $aktivitas_data[] = $row;
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
    <title>Laporan - Admin Panel</title>
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
        
       @media print {
    /* Hide non-printable elements */
    .no-print {
        display: none !important;
    }
    
    /* Basic reset for printing */
    body {
        width: 100%;
        margin: 0;
        padding: 0;
        background-color: #FFFFFF;
        font-size: 10px; /* Reduce font size for printing */
    }
    
    /* Remove sidebar margin */
    main {
        margin-left: 0 !important;
        padding: 10px !important;
    }
    
    /* Remove all page breaks */
    .print-break {
        page-break-before: auto;
    }
    
    /* Remove spacing and styling that takes up space */
    .shadow-md, .shadow-lg, .shadow-sm {
        box-shadow: none !important;
    }
    .rounded-xl, .rounded-2xl, .rounded-lg {
        border-radius: 0 !important;
    }
    
    /* Make all sections more compact */
    .p-6 {
        padding: 8px !important;
    }
    .mb-8 {
        margin-bottom: 10px !important;
    }
    
    /* Adjust card grid layouts */
    .grid {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    .grid > div {
        flex: 1 1 150px !important;
    }
    
    /* Reduce chart height */
    .h-96 {
        height: 200px !important;
    }
    canvas {
        max-height: 200px !important;
    }
    
    /* Table formatting */
    table {
        font-size: 9px !important;
        width: 100% !important;
        border-collapse: collapse !important;
    }
    th, td {
        padding: 2px 4px !important;
    }
    
    /* Compact table rows */
    td.py-4, th.py-3 {
        padding-top: 2px !important;
        padding-bottom: 2px !important;
    }
    
    /* Ensure everything stays on one page */
    html, body {
        height: auto !important;
    }
    
    /* Scale to fit */
    @page {
        size: auto;
        margin: 5mm;
    }
}
    </style>
</head>
<body class="bg-gray-50 custom-scrollbar">
    <!-- Sidebar and Main Content Wrapper -->
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 gradient-primary text-white min-h-screen fixed shadow-lg z-10 no-print">
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
                    <a href="users.php" class="flex items-center px-4 py-3 rounded-lg text-white hover:bg-white hover:bg-opacity-10 transition">
                        <i class="fas fa-users w-5 mr-3"></i>
                        <span>Kelola User</span>
                    </a>
                    <a href="laporan.php" class="flex items-center px-4 py-3 rounded-lg bg-white bg-opacity-10 text-white">
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
            <section class="gradient-primary text-white py-6 px-8 rounded-2xl mb-8 relative overflow-hidden animate-fade-in no-print">
                <div class="absolute inset-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="absolute top-0 left-0 w-full opacity-10">
                        <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,144C1248,107,1344,85,1392,74.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold mb-2">Laporan</h1>
                            <p class="text-indigo-100">Laporan statistik dan data penyuluhan KB</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div id="currentDateTime" class="text-sm text-indigo-100 bg-white bg-opacity-10 px-4 py-2 rounded-lg">
                                <?php echo date('l, d F Y - H:i'); ?>
                            </div>
                            <button onclick="window.print()" class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-opacity-90 transition shadow-md flex items-center">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Print Header (Only visible when printing) -->
            <div class="hidden print:block mb-6">
                <div class="flex items-center justify-center">
                    <div class="text-center">
                        <h1 class="text-2xl font-bold">Laporan Penyuluhan KB</h1>
                        <p class="text-gray-600">Periode: <?php echo tanggal_indo($start_date); ?> - <?php echo tanggal_indo($end_date); ?></p>
                    </div>
                </div>
                <hr class="my-4 border-gray-300">
            </div>

            <!-- Date Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 no-print animate-slide-up">
                <h2 class="text-lg font-bold mb-4 flex items-center text-gray-800">
                    <i class="fas fa-calendar-alt text-indigo-500 mr-2"></i>
                    Filter Periode
                </h2>
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                               class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                               class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div class="self-end">
                        <button type="submit" class="gradient-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition shadow-md flex items-center">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-8 animate-slide-up" style="animation-delay: 0.1s;">
                <?php
                $sql_summary = [
                    'total_users' => "SELECT COUNT(*) as total FROM users WHERE role = 'user'",
                    'total_materi' => "SELECT COUNT(*) as total FROM materi",
                    'total_kuis' => "SELECT COUNT(*) as total FROM kuis"
                ];
                
                $summary_data = [];
                foreach($sql_summary as $key => $sql) {
                    $result = $conn->query($sql);
                    $summary_data[$key] = $result->fetch_assoc()['total'];
                }
                
                $card_data = [
                    [
                        'title' => 'Total User',
                        'value' => $summary_data['total_users'],
                        'icon' => 'fa-users',
                        'color' => 'gradient-blue'
                    ],
                    [
                        'title' => 'Total Materi',
                        'value' => $summary_data['total_materi'],
                        'icon' => 'fa-book',
                        'color' => 'gradient-purple'
                    ],
                    [
                        'title' => 'Total Soal Kuis',
                        'value' => $summary_data['total_kuis'],
                        'icon' => 'fa-question-circle',
                        'color' => 'gradient-green'
                    ]
                ];
                
                foreach($card_data as $card):
                ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden card-hover">
                    <div class="h-2 <?php echo $card['color']; ?>"></div>
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full <?php 
                                if($card['color'] == 'gradient-blue') echo 'bg-blue-100 text-blue-600';
                                elseif($card['color'] == 'gradient-purple') echo 'bg-purple-100 text-purple-600';
                                else echo 'bg-green-100 text-green-600';
                            ?>">
                                <i class="fas <?php echo $card['icon']; ?> text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm text-gray-500 uppercase font-semibold"><?php echo $card['title']; ?></h3>
                                <p class="text-3xl font-bold <?php 
                                    if($card['color'] == 'gradient-blue') echo 'text-blue-600';
                                    elseif($card['color'] == 'gradient-purple') echo 'text-purple-600';
                                    else echo 'text-green-600';
                                ?>"><?php echo $card['value']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Laporan Kuis -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up" style="animation-delay: 0.2s;">
                <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center">
                    <i class="fas fa-chart-line text-indigo-500 mr-2"></i>
                    Laporan Kuis User
                </h2>
                <p class="text-sm text-gray-600 mb-6">Periode: <?php echo tanggal_indo($start_date); ?> - <?php echo tanggal_indo($end_date); ?></p>
                
                <?php if($result_kuis->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Soal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jawaban Benar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skor (%)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($row = $result_kuis->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo $row['user_name']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $row['total_soal']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $row['benar']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php 
                                                $score = round($row['skor_rata'], 1);
                                                $color = $score >= 70 ? 'bg-green-500' : ($score >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                                                ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full text-white <?php echo $color; ?>">
                                                    <?php echo $score; ?>%
                                                </span>
                                                <div class="ml-4 w-24 bg-gray-200 rounded-full h-2">
                                                    <div class="<?php echo $color; ?> h-2 rounded-full" style="width: <?php echo $score; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <i class="fas fa-info-circle text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600">Tidak ada data kuis untuk periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="print-break"></div>

            <!-- Materi Populer -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up" style="animation-delay: 0.3s;">
                <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center">
                    <i class="fas fa-fire text-indigo-500 mr-2"></i>
                    Top 10 Materi Populer
                </h2>
                
                <?php if($result_materi->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Upload</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                $counter = 0;
                                while($row = $result_materi->fetch_assoc()): 
                                    $counter++;
                                    $bg_class = $counter <= 3 ? 'bg-yellow-50' : '';
                                ?>
                                    <tr class="hover:bg-gray-50 transition <?php echo $bg_class; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if($counter <= 3): ?>
                                                    <span class="flex-shrink-0 h-6 w-6 rounded-full bg-yellow-400 text-white flex items-center justify-center mr-3 text-xs font-bold">
                                                        <?php echo $counter; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <div class="font-medium text-gray-900"><?php echo $row['judul']; ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if($row['kategori']): ?>
                                                <?php
                                                $kategori_colors = [
                                                    'Edukasi' => 'bg-purple-100 text-purple-800',
                                                    'Kontrasepsi' => 'bg-blue-100 text-blue-800',
                                                    'Kesehatan' => 'bg-pink-100 text-pink-800',
                                                    'Perencanaan' => 'bg-green-100 text-green-800'
                                                ];
                                                $color_class = $kategori_colors[$row['kategori']] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color_class; ?>">
                                                    <?php echo $row['kategori']; ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-eye mr-2 text-indigo-400"></i>
                                                <span class="font-medium"><?php echo $row['views']; ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo tanggal_indo($row['tanggal_upload']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <i class="fas fa-book text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600">Tidak ada data materi.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="print-break"></div>

            <!-- Laporan Pendaftaran -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up" style="animation-delay: 0.4s;">
                <h2 class="text-xl font-bold mb-4 text-gray-800 flex items-center">
                    <i class="fas fa-calendar-check text-indigo-500 mr-2"></i>
                    Laporan Pendaftaran Penyuluhan
                </h2>
                <p class="text-sm text-gray-600 mb-6">Periode: <?php echo tanggal_indo($start_date); ?> - <?php echo tanggal_indo($end_date); ?></p>
                
                <?php if($result_pendaftaran->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acara</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kapasitas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendaftar</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hadir</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tidak Hadir</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($row = $result_pendaftaran->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo $row['judul']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo tanggal_indo($row['tanggal']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $row['lokasi']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $row['kapasitas']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $row['total_pendaftar']; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo $row['hadir']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                <?php echo $row['tidak_hadir']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <i class="fas fa-calendar-times text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600">Tidak ada data pendaftaran untuk periode ini.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chart -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-8 animate-slide-up" style="animation-delay: 0.5s;">
                <h2 class="text-xl font-bold mb-6 text-gray-800 flex items-center">
                    <i class="fas fa-chart-pie text-indigo-500 mr-2"></i>
                    Distribusi Konten per Kategori
                </h2>
                <div class="h-96">
                    <canvas id="aktivitasChart"></canvas>
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
        
    // Prepare data for chart
    const aktivitasData = <?php echo json_encode($aktivitas_data); ?>;
    const categories = [...new Set(aktivitasData.map(item => item.kategori))];
    const materiData = [];
    const kuisData = [];
    
    categories.forEach(cat => {
        const materiItem = aktivitasData.find(item => item.jenis === 'Materi' && item.kategori === cat);
        const kuisItem = aktivitasData.find(item => item.jenis === 'Kuis' && item.kategori === cat);
        
        materiData.push(materiItem ? materiItem.jumlah : 0);
        kuisData.push(kuisItem ? kuisItem.jumlah : 0);
    });

    // Create chart
    const ctx = document.getElementById('aktivitasChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [
                {
                    label: 'Materi',
                    data: materiData,
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Kuis',
                    data: kuisData,
                    backgroundColor: 'rgba(236, 72, 153, 0.7)',
                    borderColor: 'rgba(236, 72, 153, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
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
    <!-- Add this JavaScript function to your laporan.php file, just before the closing </body> tag -->

<script>
// Improved print functionality
document.querySelector('button[onclick="window.print()"]').onclick = function(e) {
    e.preventDefault();
    
    // Prepare for printing
    document.querySelectorAll('.animate-fade-in, .animate-slide-up').forEach(el => {
        el.style.animation = 'none';
    });
    
    // Add a small delay to ensure charts are fully rendered
    setTimeout(function() {
        window.print();
    }, 500);
};

// Ensure charts resize correctly on print
window.addEventListener('beforeprint', function() {
    if (window.Chart && Chart.instances) {
        for (let id in Chart.instances) {
            if (Chart.instances.hasOwnProperty(id)) {
                Chart.instances[id].resize();
            }
        }
    }
});
</script>
</body>
</html>