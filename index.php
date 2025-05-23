    <?php
    require_once 'config.php';

    // Jika sudah login, redirect ke dashboard
    if (is_logged_in()) {
        if (is_admin()) {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard_user.php");
        }
        exit();
    }

    // Ambil statistik untuk homepage
    $sql_total_users = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $result_total_users = $conn->query($sql_total_users);
    $total_users = $result_total_users->fetch_assoc()['total'] ?? 0;

    $sql_total_materi = "SELECT COUNT(*) as total FROM materi";
    $result_total_materi = $conn->query($sql_total_materi);
    $total_materi = $result_total_materi->fetch_assoc()['total'] ?? 0;

    $sql_total_jadwal = "SELECT COUNT(*) as total FROM jadwal_penyuluhan WHERE tanggal >= NOW()";
    $result_total_jadwal = $conn->query($sql_total_jadwal);
    $total_jadwal = $result_total_jadwal->fetch_assoc()['total'] ?? 0;

    // Ambil jadwal terdekat
    $sql_jadwal_terdekat = "SELECT * FROM jadwal_penyuluhan WHERE tanggal >= NOW() ORDER BY tanggal ASC LIMIT 3";
    $result_jadwal_terdekat = $conn->query($sql_jadwal_terdekat);

    // Ambil list materi untuk modal popup
    $sql_list_materi = "SELECT id, judul, deskripsi, kategori, tags, tanggal_upload, views FROM materi ORDER BY tanggal_upload DESC LIMIT 6";
    $result_list_materi = $conn->query($sql_list_materi);

    // Ambil kategori unik untuk filter
    $sql_kategori = "SELECT DISTINCT kategori FROM materi WHERE kategori IS NOT NULL ORDER BY kategori";
    $result_kategori = $conn->query($sql_kategori);
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Penyuluhan Keluarga Berencana</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
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
            
            .hero-section {
                position: relative;
                background-image: url('logo.jpg');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
            }
            
            .hero-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.85) 0%, rgba(139, 92, 246, 0.85) 100%);
            }
            
            .card-shadow {
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            }
            
            .card-hover {
                transition: all 0.3s ease;
            }
            
            .card-hover:hover {
                transform: translateY(-8px);
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
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
            
            .animate-fade-in {
                animation: fadeIn 0.6s ease-in-out;
            }
            
            .animate-slide-up {
                animation: slideUp 0.5s ease-out;
            }
            
            .counter-animation {
                display: inline-block;
                opacity: 0;
                transform: translateY(10px);
                animation: counterAnim 0.7s ease forwards;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideUp {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            @keyframes counterAnim {
                to { 
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                transition: all 0.3s ease;
            }
            
            .btn-primary:hover {
                opacity: 0.9;
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
            }
            
            .btn-secondary {
                transition: all 0.3s ease;
            }
            
            .btn-secondary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }
            
            .section-heading {
                position: relative;
                display: inline-block;
            }
            
            .section-heading::after {
                content: '';
                position: absolute;
                width: 50%;
                height: 4px;
                bottom: -10px;
                left: 25%;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                border-radius: 4px;
            }
            
            .scroll-indicator {
                animation: bounce 2s infinite;
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-15px);
                }
                60% {
                    transform: translateY(-7px);
                }
            }
            
            .nav-item {
                position: relative;
            }
            
            .nav-item::after {
                content: '';
                position: absolute;
                width: 0;
                height: 2px;
                bottom: -3px;
                left: 0;
                background-color: white;
                transition: width 0.3s ease;
            }
            
            .nav-item:hover::after {
                width: 100%;
            }
            
            .mobile-menu-animation {
                transition: all 0.3s ease-in-out;
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
                        <a href="#tentang" class="nav-item font-medium text-gray-600 hover:text-indigo-600 transition">
                            Tentang
                        </a>
                        <a href="#program" class="nav-item font-medium text-gray-600 hover:text-indigo-600 transition">
                            Program
                        </a>
                        <a href="#statistik" class="nav-item font-medium text-gray-600 hover:text-indigo-600 transition">
                            Statistik
                        </a>
                        <a href="auth.php?mode=login" class="flex items-center text-gray-700 hover:text-indigo-600 transition border border-gray-300 rounded-lg px-4 py-2 bg-white shadow-sm hover:shadow btn-secondary">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                        <a href="auth.php?mode=register" class="flex items-center text-white gradient-primary rounded-lg px-4 py-2 shadow-md hover:shadow-lg btn-primary">
                            <i class="fas fa-user-plus mr-2"></i>Daftar
                        </a>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button id="mobile-menu-button" class="p-2 rounded-md hover:bg-gray-100 focus:outline-none">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg animate-fade-in mobile-menu-animation">
                <a href="#tentang" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                    <i class="fas fa-info-circle w-6 mr-2"></i>Tentang
                </a>
                <a href="#program" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                    <i class="fas fa-list-alt w-6 mr-2"></i>Program
                </a>
                <a href="#jadwal" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                    <i class="fas fa-calendar-alt w-6 mr-2"></i>Jadwal
                </a>
                <a href="#statistik" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
                    <i class="fas fa-chart-bar w-6 mr-2"></i>Statistik
                </a>
                <div class="grid grid-cols-2 gap-2 p-4 border-t border-gray-100">
                    <a href="auth.php?mode=login" class="flex items-center justify-center text-gray-700 border border-gray-300 rounded-lg px-3 py-2 hover:bg-gray-50 hover:text-indigo-600">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="auth.php?mode=register" class="flex items-center justify-center text-white gradient-primary rounded-lg px-3 py-2">
                        <i class="fas fa-user-plus mr-2"></i>Daftar
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section id="hero" class="hero-section min-h-screen flex items-center relative">
            <div class="hero-overlay"></div>
            <div class="container mx-auto px-4 py-20 z-10 animate-fade-in">
                <div class="max-w-3xl mx-auto">
                    <div class="text-center mb-12">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6 text-white">
                            Penyuluhan Keluarga Berencana
                        </h1>
                        <p class="text-xl mb-10 text-white opacity-90">
                            Program komprehensif untuk mendukung para Kader KB di Sumatera Barat dalam memberdayakan keluarga melalui pendidikan dan akses layanan KB yang berkualitas.
                        </p>
                        <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6 justify-center">
                            <a href="auth.php?mode=register" class="bg-white text-indigo-600 px-8 py-3 rounded-lg text-lg font-semibold shadow-lg hover:shadow-xl btn-secondary">
                                <i class="fas fa-user-plus mr-2"></i>Bergabung Sekarang
                            </a>
                            <a href="#tentang" class="border-2 border-white text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-white hover:text-indigo-600 transition btn-secondary">
                                <i class="fas fa-info-circle mr-2"></i>Pelajari Lebih Lanjut
                            </a>
                        </div>
                    </div>
                    
                    <!-- Scroll indicator -->
                    <div class="flex justify-center mt-16">
                        <a href="#tentang" class="text-white scroll-indicator">
                            <i class="fas fa-chevron-down text-2xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tentang Section -->
        <section id="tentang" class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold section-heading">Tentang Program KB</h2>
                    </div>
                    
                    <div class="mb-12">
                        <p class="text-lg text-gray-700 mb-8 leading-relaxed animate-slide-up">
                            Program Keluarga Berencana (KB) adalah usaha untuk mengontrol jumlah anak dan jarak kelahiran dengan menggunakan kontrasepsi. Program ini membantu pasangan merencanakan keluarga mereka sesuai dengan kemampuan ekonomi dan kesehatan.
                        </p>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow animate-slide-up" style="animation-delay: 0.1s;">
                            <div class="h-14 w-14 rounded-full bg-indigo-100 flex items-center justify-center mb-4">
                                <i class="fas fa-graduation-cap text-2xl text-indigo-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-gray-800">Edukasi</h3>
                            <p class="text-gray-600">Penyediaan informasi dan pengetahuan yang akurat tentang KB untuk membantu masyarakat membuat keputusan yang tepat.</p>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow animate-slide-up" style="animation-delay: 0.2s;">
                            <div class="h-14 w-14 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                                <i class="fas fa-hands-helping text-2xl text-blue-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-gray-800">Konsultasi</h3>
                            <p class="text-gray-600">Layanan konsultasi dan bimbingan dari ahli kesehatan reproduksi untuk membantu keluarga merencanakan masa depan.</p>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-lg p-6 card-hover card-shadow animate-slide-up" style="animation-delay: 0.3s;">
                            <div class="h-14 w-14 rounded-full bg-purple-100 flex items-center justify-center mb-4">
                                <i class="fas fa-shield-alt text-2xl text-purple-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-gray-800">Layanan</h3>
                            <p class="text-gray-600">Akses ke berbagai metode kontrasepsi yang aman dan dapat diandalkan melalui petugas kesehatan terlatih.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Program Section -->
        <section id="program" class="py-20 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="max-w-5xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold section-heading">Program Kami</h2>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-8 mt-16">
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow animate-slide-up" style="animation-delay: 0.1s;">
                            <div class="h-3 bg-indigo-500"></div>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mr-4">
                                        <i class="fas fa-chalkboard-teacher text-indigo-600 text-xl"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800">Penyuluhan Rutin</h3>
                                </div>
                                <p class="text-gray-600 mb-6">Penyuluhan berkala tentang metode KB dan kesehatan reproduksi untuk meningkatkan pemahaman masyarakat.</p>
                                <a href="#jadwal" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                                    <span>Lihat Jadwal</span>
                                    <i class="fas fa-arrow-right ml-2 transition-transform duration-300 transform group-hover:translate-x-1"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow animate-slide-up" style="animation-delay: 0.2s;">
                            <div class="h-3 bg-blue-500"></div>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                                        <i class="fas fa-laptop text-blue-600 text-xl"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800">Materi Online</h3>
                                </div>
                                <p class="text-gray-600 mb-6">Akses ke berbagai materi pembelajaran digital yang dapat diakses kapan saja dan di mana saja.</p>
                                <a href="auth.php?mode=login" class="flex items-center text-blue-600 hover:text-blue-800 font-medium">
                                    <span>Belajar Online</span>
                                    <i class="fas fa-arrow-right ml-2 transition-transform duration-300 transform group-hover:translate-x-1"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover card-shadow animate-slide-up" style="animation-delay: 0.3s;">
                            <div class="h-3 bg-purple-500"></div>
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                                        <i class="fas fa-clipboard-check text-purple-600 text-xl"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800">Evaluasi & Kuis</h3>
                                </div>
                                <p class="text-gray-600 mb-6">Test dan evaluasi pengetahuan tentang KB untuk memastikan pemahaman yang tepat dan menyeluruh.</p>
                                <a href="auth.php?mode=login" class="flex items-center text-purple-600 hover:text-purple-800 font-medium">
                                    <span>Ikuti Kuis</span>
                                    <i class="fas fa-arrow-right ml-2 transition-transform duration-300 transform group-hover:translate-x-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>



        <!-- Statistik Section -->
        <section id="statistik" class="py-20 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="max-w-5xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold section-heading">Statistik Kami</h2>
                    </div>
                    
                    <div class="grid md:grid-cols-3 gap-8 mt-16">
                        <div class="bg-white rounded-xl shadow-lg p-8 text-center card-hover card-shadow">
                            <div class="mx-auto w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-users text-3xl text-indigo-600"></i>
                            </div>
                            <h3 class="text-4xl font-bold text-indigo-600 mb-2 counter" data-target="<?php echo $total_users; ?>">0</h3>
                            <p class="text-gray-600 text-lg">Total Peserta</p>
                        </div>
                        
                        <div id="materiCard" class="bg-white rounded-xl shadow-lg p-8 text-center card-hover card-shadow cursor-pointer" onclick="openMateriModal()">
                            <div class="mx-auto w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-book text-3xl text-blue-600"></i>
                            </div>
                            <h3 class="text-4xl font-bold text-blue-600 mb-2 counter" data-target="<?php echo $total_materi; ?>">0</h3>
                            <p class="text-gray-600 text-lg">Materi Edukasi</p>
                            <span class="text-sm text-blue-600 mt-2 inline-block">Klik untuk melihat daftar materi <i class="fas fa-arrow-right ml-1"></i></span>
                        </div>
                        
                        <div id="jadwalCard" class="bg-white rounded-xl shadow-lg p-8 text-center card-hover card-shadow cursor-pointer" onclick="openJadwalModal()">
                            <div class="mx-auto w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-calendar text-3xl text-purple-600"></i>
                            </div>
                            <h3 class="text-4xl font-bold text-purple-600 mb-2 counter" data-target="<?php echo $total_jadwal; ?>">0</h3>
                            <p class="text-gray-600 text-lg">Jadwal Mendatang</p>
                            <span class="text-sm text-purple-600 mt-2 inline-block">Klik untuk melihat jadwal <i class="fas fa-arrow-right ml-1"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Modal Materi -->
        <div id="materiModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden overflow-y-auto py-5">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 flex flex-col animate-fade-in" style="max-height: 90vh;">
                <div class="gradient-primary p-6 flex items-center justify-between text-white">
                    <div class="flex items-center">
                        <i class="fas fa-book-open text-2xl mr-3"></i>
                        <h3 class="text-xl font-bold">Daftar Materi Edukasi</h3>
                    </div>
                    <button onclick="closeMateriModal()" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-full transition">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Filter Materi -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div class="flex-1 min-w-[200px]">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                <input type="text" id="searchMateri" placeholder="Cari materi..." 
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            </div>
                        </div>
                        <div class="min-w-[200px]">
                            <select id="kategoriMateri" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                <option value="">Semua Kategori</option>
                                <?php 
                                // Reset the result pointer to the beginning
                                if($result_kategori && $result_kategori->num_rows > 0) {
                                    $result_kategori->data_seek(0);
                                    while($kategori = $result_kategori->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $kategori['kategori']; ?>">
                                        <?php echo $kategori['kategori']; ?>
                                    </option>
                                <?php 
                                    endwhile; 
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-y-auto flex-grow" style="min-height: 200px;">
                    <div class="p-6 grid md:grid-cols-2 gap-6">
                        <?php if($result_list_materi && $result_list_materi->num_rows > 0): ?>
                            <?php $counter = 0; while($materi = $result_list_materi->fetch_assoc()): $counter++; ?>
                                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200 materi-item" data-kategori="<?php echo $materi['kategori']; ?>">
                                    <div class="h-2 <?php 
                                        if($counter % 3 == 1) echo 'bg-blue-500';
                                        elseif($counter % 3 == 2) echo 'bg-purple-500';
                                        else echo 'bg-pink-500';
                                    ?>"></div>
                                    <div class="p-4">
                                        <div class="flex items-center mb-3">
                                            <div class="p-2 rounded-full <?php 
                                                if($counter % 3 == 1) echo 'bg-blue-100 text-blue-600';
                                                elseif($counter % 3 == 2) echo 'bg-purple-100 text-purple-600';
                                                else echo 'bg-pink-100 text-pink-600';
                                            ?>">
                                                <i class="fas <?php 
                                                    if($counter % 3 == 1) echo 'fa-book-medical';
                                                    elseif($counter % 3 == 2) echo 'fa-heartbeat';
                                                    else echo 'fa-chart-pie';
                                                ?> text-sm"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="font-bold text-gray-800"><?php echo $materi['judul']; ?></h3>
                                                <?php if($materi['kategori']): ?>
                                                    <span class="inline-block text-xs px-2 py-1 rounded-full <?php 
                                                        if($counter % 3 == 1) echo 'bg-blue-100 text-blue-600';
                                                        elseif($counter % 3 == 2) echo 'bg-purple-100 text-purple-600';
                                                        else echo 'bg-pink-100 text-pink-600';
                                                    ?>">
                                                        <?php echo $materi['kategori']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <p class="text-gray-600 mb-3 text-sm line-clamp-2">
                                            <?php echo substr($materi['deskripsi'], 0, 120) . (strlen($materi['deskripsi']) > 120 ? '...' : ''); ?>
                                        </p>
                                        
                                        <div class="flex items-center text-xs text-gray-500 mb-3">
                                            <div class="flex items-center mr-4">
                                                <i class="fas fa-calendar mr-1 <?php 
                                                    if($counter % 3 == 1) echo 'text-blue-500';
                                                    elseif($counter % 3 == 2) echo 'text-purple-500';
                                                    else echo 'text-pink-500';
                                                ?>"></i>
                                                <span><?php echo tanggal_indo($materi['tanggal_upload']); ?></span>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-eye mr-1 <?php 
                                                    if($counter % 3 == 1) echo 'text-blue-500';
                                                    elseif($counter % 3 == 2) echo 'text-purple-500';
                                                    else echo 'text-pink-500';
                                                ?>"></i>
                                                <span><?php echo $materi['views']; ?></span>
                                            </div>
                                        </div>
                                        
                                        <a href="auth.php?mode=login" 
                                        class="block w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition text-center text-sm">
                                            <i class="fas fa-sign-in-alt mr-2"></i>Login untuk Mengakses
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-span-2 text-center p-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto flex items-center justify-center mb-4">
                                    <i class="fas fa-folder-open text-2xl text-gray-400"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Tidak Ada Materi</h3>
                                <p class="text-gray-600">Belum ada materi yang tersedia saat ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 bg-gray-50 flex flex-col md:flex-row gap-4 justify-between items-center">
                    <p class="text-gray-600 text-center md:text-left"><i class="fas fa-info-circle mr-2"></i>Login untuk mengakses dan mengunduh materi</p>
                    <a href="auth.php?mode=login" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition shadow-md flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login Sekarang
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Modal Jadwal -->
        <div id="jadwalModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden overflow-y-auto py-5">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl mx-4 flex flex-col animate-fade-in" style="max-height: 90vh;">
                <div class="bg-purple-600 p-6 flex items-center justify-between text-white">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-alt text-2xl mr-3"></i>
                        <h3 class="text-xl font-bold">Jadwal Penyuluhan Terbaru</h3>
                    </div>
                    <button onclick="closeJadwalModal()" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-full transition">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div class="overflow-y-auto flex-grow" style="min-height: 200px;">
                    <div class="p-6 grid md:grid-cols-2 gap-6">
                        <?php if($result_jadwal_terdekat && $result_jadwal_terdekat->num_rows > 0): ?>
                            <?php $delay = 0.1; while($jadwal = $result_jadwal_terdekat->fetch_assoc()): ?>
                                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                                    <div class="h-3 bg-purple-500"></div>
                                    <div class="p-4">
                                        <h3 class="font-bold text-lg text-gray-800 mb-2"><?php echo $jadwal['judul']; ?></h3>
                                        
                                        <div class="space-y-2 mb-4">
                                            <div class="flex items-center text-gray-600">
                                                <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center mr-2">
                                                    <i class="fas fa-calendar text-indigo-600 text-xs"></i>
                                                </div>
                                                <span class="text-sm"><?php echo tanggal_indo($jadwal['tanggal']); ?></span>
                                            </div>
                                            <div class="flex items-center text-gray-600">
                                                <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                                    <i class="fas fa-clock text-blue-600 text-xs"></i>
                                                </div>
                                                <span class="text-sm"><?php echo date('H:i', strtotime($jadwal['tanggal'])); ?> WIB</span>
                                            </div>
                                            <div class="flex items-center text-gray-600">
                                                <div class="w-6 h-6 rounded-full bg-purple-100 flex items-center justify-center mr-2">
                                                    <i class="fas fa-map-marker-alt text-purple-600 text-xs"></i>
                                                </div>
                                                <span class="text-sm"><?php echo $jadwal['lokasi']; ?></span>
                                            </div>
                                            <div class="flex items-center text-gray-600">
                                                <div class="w-6 h-6 rounded-full bg-yellow-100 flex items-center justify-center mr-2">
                                                    <i class="fas fa-users text-yellow-600 text-xs"></i>
                                                </div>
                                                <span class="text-sm">Kapasitas: <?php echo $jadwal['kapasitas']; ?> orang</span>
                                            </div>
                                        </div>
                                        
                                        <?php if($jadwal['deskripsi']): ?>
                                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                                <?php echo substr($jadwal['deskripsi'], 0, 100) . (strlen($jadwal['deskripsi']) > 100 ? '...' : ''); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <a href="auth.php?mode=login" 
                                        class="block w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition text-center text-sm">
                                            <i class="fas fa-sign-in-alt mr-2"></i>Login untuk Mendaftar
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-span-2 text-center p-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto flex items-center justify-center mb-4">
                                    <i class="fas fa-calendar-times text-2xl text-gray-400"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Tidak Ada Jadwal</h3>
                                <p class="text-gray-600">Belum ada jadwal penyuluhan terbaru saat ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 bg-gray-50 flex flex-col md:flex-row gap-4 justify-between items-center">
                    <p class="text-gray-600 text-center md:text-left"><i class="fas fa-info-circle mr-2"></i>Login untuk mendaftar ke jadwal penyuluhan</p>
                    <a href="auth.php?mode=login" class="w-full md:w-auto bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition shadow-md flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login Sekarang
                    </a>
                </div>
            </div>
        </div>

        <!-- Alur Aplikasi Section -->
        <section id="alur" class="py-20 bg-white">
            <div class="container mx-auto px-4">
                <div class="max-w-5xl mx-auto">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold section-heading">Alur Penggunaan Aplikasi</h2>
                    </div>
                    
                    <div class="grid md:grid-cols-4 gap-6 mt-16 relative">
                        <!-- Line connector - desktop -->
                        <div class="hidden md:block absolute top-1/3 left-0 w-full h-2 bg-indigo-100 z-0"></div>
                        
                        <!-- Step 1 -->
                        <div class="relative z-10 flex flex-col items-center animate-slide-up" style="animation-delay: 0.1s;">
                            <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-lg mb-6 border-4 border-indigo-100">
                                <div class="w-16 h-16 rounded-full gradient-primary flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold">1</span>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2 text-center">Registrasi</h3>
                            <p class="text-gray-600 text-center">Buat akun untuk mulai menggunakan layanan</p>
                        </div>
                        
                        <!-- Step 2 -->
                        <div class="relative z-10 flex flex-col items-center animate-slide-up" style="animation-delay: 0.2s;">
                            <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-lg mb-6 border-4 border-indigo-100">
                                <div class="w-16 h-16 rounded-full gradient-primary flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold">2</span>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2 text-center">Akses Materi</h3>
                            <p class="text-gray-600 text-center">Pelajari materi KB dari berbagai sumber</p>
                        </div>
                        
                        <!-- Step 3 -->
                        <div class="relative z-10 flex flex-col items-center animate-slide-up" style="animation-delay: 0.3s;">
                            <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-lg mb-6 border-4 border-indigo-100">
                                <div class="w-16 h-16 rounded-full gradient-primary flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold">3</span>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2 text-center">Daftar Penyuluhan</h3>
                            <p class="text-gray-600 text-center">Ikuti jadwal penyuluhan yang tersedia</p>
                        </div>
                        
                        <!-- Step 4 -->
                        <div class="relative z-10 flex flex-col items-center animate-slide-up" style="animation-delay: 0.4s;">
                            <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center shadow-lg mb-6 border-4 border-indigo-100">
                                <div class="w-16 h-16 rounded-full gradient-primary flex items-center justify-center">
                                    <span class="text-white text-2xl font-bold">4</span>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2 text-center">Evaluasi</h3>
                            <p class="text-gray-600 text-center">Ikuti kuis dan dapatkan sertifikat</p>
                        </div>
                    </div>
                    
                    <div class="mt-16 text-center">
                        <a href="auth.php?mode=register" class="inline-block gradient-primary text-white text-lg font-medium py-3 px-8 rounded-lg shadow-lg hover:shadow-xl btn-primary">
                            <i class="fas fa-rocket mr-2"></i>Mulai Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-16 bg-indigo-600">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto text-center">
                    <h2 class="text-3xl font-bold text-white mb-6">Siap untuk Bergabung?</h2>
                    <p class="text-xl text-white opacity-90 mb-10">
                        Dapatkan akses ke semua materi, jadwal penyuluhan, dan fitur lainnya dengan mendaftar sekarang.
                    </p>
                    <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6 justify-center">
                        <a href="auth.php?mode=register" class="bg-white text-indigo-600 px-8 py-3 rounded-lg text-lg font-semibold shadow-lg hover:shadow-xl btn-secondary">
                            <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                        </a>
                        <a href="auth.php?mode=login" class="border-2 border-white text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-white hover:text-indigo-600 transition btn-secondary">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-10">
            <div class="container mx-auto px-4">
                <div class="grid md:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center mb-4">
                            <div class="h-10 w-10 rounded-full gradient-primary flex items-center justify-center mr-2">
                                <i class="fas fa-heartbeat text-white"></i>
                            </div>
                            <span class="font-bold text-xl">Penyuluhan KB</span>
                        </div>
                        <p class="text-gray-400 mb-4 pr-4">Memberikan edukasi dan informasi tentang Keluarga Berencana untuk masyarakat Indonesia.</p>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold mb-4">Menu</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#tentang" class="hover:text-white transition">Tentang</a></li>
                            <li><a href="#program" class="hover:text-white transition">Program</a></li>
                            <li><a href="#statistik" class="hover:text-white transition">Statistik</a></li>
                            <li><a href="#alur" class="hover:text-white transition">Alur Aplikasi</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold mb-4">Kontak</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li class="flex items-start">
                                <i class="fas fa-phone text-indigo-400 mt-1 mr-3"></i>
                                <span>(0751) 7052357</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-envelope text-indigo-400 mt-1 mr-3"></i>
                                <span>prov.sumbar@bkkbn.go.id</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-map-marker-alt text-indigo-400 mt-1 mr-3"></i>
                                <span>Jl. Khatib Sulaiman No. 105, Padang</span>
                            </li>
                        </ul>
                    </div>
                    <div>
    <h4 class="text-lg font-bold mb-4">Follow Kami</h4>
    <div class="flex space-x-4">
        <a href="https://www.facebook.com/kemendukbangga.bkkbn.sumbar" target="_blank" class="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-indigo-600 transition">
            <i class="fab fa-facebook-f"></i>
        </a>
        <a href="https://www.instagram.com/kemendukbangga.bkkbnsumbar" target="_blank" class="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-indigo-600 transition">
            <i class="fab fa-instagram"></i>
        </a>
        <a href="https://www.youtube.com/@Kemendukbangga.BKKBN_Sumbar" target="_blank" class="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-indigo-600 transition">
            <i class="fab fa-youtube"></i>
        </a>
        <a href="https://www.tiktok.com/@kemendukbangga_sumbar" target="_blank" class="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center text-gray-400 hover:text-white hover:bg-indigo-600 transition">
            <i class="fab fa-tiktok"></i>
        </a>
    </div>
</div>
            </div>
        </footer>

        <script>
            // Modal functions
            function openMateriModal() {
                document.getElementById('materiModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
            }
            
            function closeMateriModal() {
                document.getElementById('materiModal').classList.add('hidden');
                document.body.style.overflow = 'auto'; // Restore scrolling
            }
            
            function openJadwalModal() {
                document.getElementById('jadwalModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
            }
            
            function closeJadwalModal() {
                document.getElementById('jadwalModal').classList.add('hidden');
                document.body.style.overflow = 'auto'; // Restore scrolling
            }
            
            // Mobile menu toggle
            document.addEventListener('DOMContentLoaded', function() {
                const mobileMenuButton = document.getElementById('mobile-menu-button');
                const mobileMenu = document.getElementById('mobile-menu');
                
                if (mobileMenuButton && mobileMenu) {
                    mobileMenuButton.addEventListener('click', function() {
                        mobileMenu.classList.toggle('hidden');
                    });
                }
                
                // Smooth scroll for anchor links
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            window.scrollTo({
                                top: target.offsetTop - 80, // Adjust for fixed header
                                behavior: 'smooth'
                            });
                        }
                    });
                });
                
                // Intersection Observer for animations
                const observerOptions = {
                    root: null,
                    rootMargin: '0px',
                    threshold: 0.1
                };
                
                // Animate slide-up elements when they come into view
                const slideUpElements = document.querySelectorAll('.animate-slide-up');
                const slideUpObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);
                
                slideUpElements.forEach(element => {
                    element.style.opacity = '0';
                    element.style.transform = 'translateY(20px)';
                    element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    if (element.style.animationDelay) {
                        element.style.transitionDelay = element.style.animationDelay;
                    }
                    slideUpObserver.observe(element);
                });
                
                // Counter animation
                function animateCounter(counterElement) {
                    const target = parseInt(counterElement.getAttribute('data-target'));
                    const duration = 2000; // 2 seconds
                    const steps = 50;
                    const stepValue = target / steps;
                    let current = 0;
                    let step = 0;
                    
                    const timer = setInterval(() => {
                        step++;
                        current = Math.ceil(stepValue * step);
                        
                        if (current > target) {
                            current = target;
                            clearInterval(timer);
                        }
                        
                        counterElement.textContent = current;
                        
                        if (step >= steps) {
                            clearInterval(timer);
                        }
                    }, duration / steps);
                }
                
                // Start counter animation when counter section is in view
                const counterElements = document.querySelectorAll('.counter');
                const counterObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animateCounter(entry.target);
                            entry.target.classList.add('counter-animation');
                            observer.unobserve(entry.target);
                        }
                    });
                }, observerOptions);
                
                counterElements.forEach(counter => {
                    counterObserver.observe(counter);
                });
                
                // Materi Modal Search and Filter
                const searchMateri = document.getElementById('searchMateri');
                const kategoriMateri = document.getElementById('kategoriMateri');
                const materiItems = document.querySelectorAll('.materi-item');
                
                function filterMateri() {
                    const searchValue = searchMateri.value.toLowerCase();
                    const kategoriValue = kategoriMateri.value;
                    
                    materiItems.forEach(item => {
                        const title = item.querySelector('h3').textContent.toLowerCase();
                        const description = item.querySelector('p').textContent.toLowerCase();
                        const itemKategori = item.dataset.kategori;
                        
                        const matchesSearch = title.includes(searchValue) || description.includes(searchValue);
                        const matchesKategori = kategoriValue === '' || itemKategori === kategoriValue;
                        
                        if (matchesSearch && matchesKategori) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                }
                
                if (searchMateri) {
                    searchMateri.addEventListener('input', filterMateri);
                }
                
                if (kategoriMateri) {
                    kategoriMateri.addEventListener('change', filterMateri);
                }
                
                // Close modals when clicking outside
                window.addEventListener('click', function(e) {
                    const materiModal = document.getElementById('materiModal');
                    const jadwalModal = document.getElementById('jadwalModal');
                    
                    if (e.target === materiModal) {
                        closeMateriModal();
                    }
                    
                    if (e.target === jadwalModal) {
                        closeJadwalModal();
                    }
                });
                
                // Escape key to close modals
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeMateriModal();
                        closeJadwalModal();
                    }
                });
            });
        </script>
        <!-- Add this script at the bottom of your file, just before the closing </body> tag -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Back to top functionality
        const backToTopLink = document.getElementById('back-to-top');
        if (backToTopLink) {
            backToTopLink.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
        
        // Modal functions
        function openMateriModal() {
            document.getElementById('materiModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        }
        
        function closeMateriModal() {
            document.getElementById('materiModal').classList.add('hidden');
            document.body.style.overflow = 'auto'; // Restore scrolling
        }
        
        function openJadwalModal() {
            document.getElementById('jadwalModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        }
        
        function closeJadwalModal() {
            document.getElementById('jadwalModal').classList.add('hidden');
            document.body.style.overflow = 'auto'; // Restore scrolling
        }
        
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80, // Adjust for fixed header
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Intersection Observer for animations
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        // Animate slide-up elements when they come into view
        const slideUpElements = document.querySelectorAll('.animate-slide-up');
        const slideUpObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        slideUpElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            if (element.style.animationDelay) {
                element.style.transitionDelay = element.style.animationDelay;
            }
            slideUpObserver.observe(element);
        });
        
        // Counter animation
        function animateCounter(counterElement) {
            const target = parseInt(counterElement.getAttribute('data-target'));
            const duration = 2000; // 2 seconds
            const steps = 50;
            const stepValue = target / steps;
            let current = 0;
            let step = 0;
            
            const timer = setInterval(() => {
                step++;
                current = Math.ceil(stepValue * step);
                
                if (current > target) {
                    current = target;
                    clearInterval(timer);
                }
                
                counterElement.textContent = current;
                
                if (step >= steps) {
                    clearInterval(timer);
                }
            }, duration / steps);
        }
        
        // Start counter animation when counter section is in view
        const counterElements = document.querySelectorAll('.counter');
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    entry.target.classList.add('counter-animation');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        counterElements.forEach(counter => {
            counterObserver.observe(counter);
        });
        
        // Materi Modal Search and Filter
        const searchMateri = document.getElementById('searchMateri');
        const kategoriMateri = document.getElementById('kategoriMateri');
        const materiItems = document.querySelectorAll('.materi-item');
        
        function filterMateri() {
            const searchValue = searchMateri.value.toLowerCase();
            const kategoriValue = kategoriMateri.value;
            
            materiItems.forEach(item => {
                const title = item.querySelector('h3').textContent.toLowerCase();
                const description = item.querySelector('p').textContent.toLowerCase();
                const itemKategori = item.dataset.kategori;
                
                const matchesSearch = title.includes(searchValue) || description.includes(searchValue);
                const matchesKategori = kategoriValue === '' || itemKategori === kategoriValue;
                
                if (matchesSearch && matchesKategori) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        if (searchMateri) {
            searchMateri.addEventListener('input', filterMateri);
        }
        
        if (kategoriMateri) {
            kategoriMateri.addEventListener('change', filterMateri);
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const materiModal = document.getElementById('materiModal');
            const jadwalModal = document.getElementById('jadwalModal');
            
            if (e.target === materiModal) {
                closeMateriModal();
            }
            
            if (e.target === jadwalModal) {
                closeJadwalModal();
            }
        });
        
        // Escape key to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMateriModal();
                closeJadwalModal();
            }
        });
        
        // Footer back to top link
        document.querySelectorAll('.back-to-top').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    });
</script>
    </body>
    </html>