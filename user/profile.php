<?php
require_once '../config.php';
require_login();

$error = '';
$success = '';

// Ambil data user
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $_SESSION['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $phone = clean_input($_POST['phone'] ?? '');
        $address = clean_input($_POST['address'] ?? '');
        $birth_date = $_POST['birth_date'] ?? null;
        
        // Cek apakah ada perubahan data
        $data_changed = false;
        if ($name != $user_data['name'] || $email != $user_data['email'] || 
            $phone != ($user_data['phone'] ?? '') || $address != ($user_data['address'] ?? '') || 
            $birth_date != $user_data['birth_date']) {
            $data_changed = true;
        }
        
        // Jika tidak ada perubahan data
        if (!$data_changed) {
            $error = 'Tidak ada perubahan data yang dilakukan!';
        } else {
            // Cek apakah email sudah digunakan user lain
            $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("si", $email, $_SESSION['user_id']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $error = 'Email sudah digunakan oleh user lain!';
            } else {
                // Update data
                $sql_update = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, birth_date = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("sssssi", $name, $email, $phone, $address, $birth_date, $_SESSION['user_id']);
                
                if ($stmt_update->execute()) {
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $success = 'Profile berhasil diupdate!';
                    $user_data = array_merge($user_data, compact('name', 'email', 'phone', 'address', 'birth_date'));
                } else {
                    $error = 'Terjadi kesalahan saat mengupdate profile!';
                }
            }
        }
    }
    
    // Proses upload avatar
    if (isset($_POST['update_avatar']) && $_FILES['avatar']['name']) {
        $target_dir = "../" . AVATAR_PATH;
        $file_extension = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
        $new_filename = $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Validasi file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            $error = 'Format file tidak diizinkan! Gunakan JPG, PNG atau GIF.';
        } elseif ($_FILES["avatar"]["size"] > 2000000) { // 2MB max
            $error = 'File terlalu besar! Maksimum 2MB.';
        } else {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                // Hapus foto lama jika ada
                if ($user_data['avatar'] && file_exists($target_dir . $user_data['avatar'])) {
                    unlink($target_dir . $user_data['avatar']);
                }
                
                // Update database
                $sql_avatar = "UPDATE users SET avatar = ? WHERE id = ?";
                $stmt_avatar = $conn->prepare($sql_avatar);
                $stmt_avatar->bind_param("si", $new_filename, $_SESSION['user_id']);
                
                if ($stmt_avatar->execute()) {
                    $_SESSION['avatar'] = $new_filename;
                    $user_data['avatar'] = $new_filename;
                    $success = 'Avatar berhasil diupdate!';
                } else {
                    $error = 'Terjadi kesalahan saat menyimpan avatar!';
                }
            } else {
                $error = 'Terjadi kesalahan saat mengupload file!';
            }
        }
    }
    
    // Proses ganti password
    if (isset($_POST['update_password'])) {
        $current_password = md5($_POST['current_password']);
        $new_password = md5($_POST['new_password']);
        $confirm_password = md5($_POST['confirm_password']);
        
        // Validasi password lama
        $sql_check_password = "SELECT password FROM users WHERE id = ?";
        $stmt_check_password = $conn->prepare($sql_check_password);
        $stmt_check_password->bind_param("i", $_SESSION['user_id']);
        $stmt_check_password->execute();
        $result_password = $stmt_check_password->get_result();
        $user_password = $result_password->fetch_assoc();
        
        if ($current_password !== $user_password['password']) {
            $error = 'Password lama salah!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Password baru tidak cocok!';
        } elseif ($current_password === $new_password) {
            $error = 'Password baru tidak boleh sama dengan password lama!';
        } else {
            // Update password
            $sql_password = "UPDATE users SET password = ? WHERE id = ?";
            $stmt_password = $conn->prepare($sql_password);
            $stmt_password->bind_param("si", $new_password, $_SESSION['user_id']);
            
            if ($stmt_password->execute()) {
                $success = 'Password berhasil diubah!';
            } else {
                $error = 'Terjadi kesalahan saat mengubah password!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Penyuluhan KB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }
        
        .header-gradient {
            background: linear-gradient(120deg, #6366f1 0%, #8b5cf6 100%);
            position: relative;
            overflow: hidden;
        }
        
        .header-gradient::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='smallGrid' width='20' height='20' patternUnits='userSpaceOnUse'%3E%3Cpath d='M 20 0 L 0 0 0 20' fill='none' stroke='rgba(255, 255, 255, 0.05)' stroke-width='1'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='100%25' height='100%25' fill='url(%23smallGrid)'/%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .header-waves {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23f8fafc' fill-opacity='1' d='M0,96L48,106.7C96,117,192,139,288,138.7C384,139,480,117,576,96C672,75,768,53,864,69.3C960,85,1056,139,1152,176C1248,213,1344,235,1392,245.3L1440,256L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-repeat: no-repeat;
        }
        
        .card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .tab-active {
            border-bottom: 3px solid #6366f1;
            color: #6366f1;
            font-weight: 600;
        }
        
        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }
        
        .file-upload input[type=file] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }
        
        .form-input {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            background-color: #f9fafb;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6366f1;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        
        .btn-primary {
            background-color: #6366f1;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #4f46e5;
        }
        
        .btn-secondary {
            background-color: #e5e7eb;
            color: #4b5563;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        
        .avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
            margin-top: -60px;
            z-index: 10;
        }
        
        .avatar-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 2.5rem;
        }
        
        .avatar-label {
            display: block;
            text-align: center;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .avatar-email {
            display: block;
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }
        
        .alert-icon {
            margin-right: 0.75rem;
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .fade-out {
            animation: fadeOut 0.5s ease forwards;
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            color: #6366f1;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: #4b5563;
        }
        
        .info-item i {
            color: #6366f1;
            margin-right: 0.75rem;
            width: 16px;
            text-align: center;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <!-- Perbaikan Header untuk Profile Page -->
<nav class="sticky top-0 z-50 bg-white shadow-md">
    <div class="container mx-auto px-6">
        <div class="flex justify-between items-center h-16">
            <!-- Logo Penyuluhan KB -->
            <div class="flex items-center">
                <a href="../dashboard_user.php" class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white"></i>
                    </div>
                    <span class="font-bold text-xl text-gray-800 ml-2">Penyuluhan KB</span>
                </a>
            </div>
            
            <!-- Main Navigation -->
            <div class="hidden md:flex items-center space-x-10">
                <a href="../dashboard_user.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                    <i class="fas fa-home mr-2"></i>Dashboard
                </a>
                <a href="materi.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                    <i class="fas fa-book mr-2"></i>Materi
                </a>
                <a href="kuis.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                    <i class="fas fa-question-circle mr-2"></i>Kuis
                </a>
                <a href="jadwal.php" class="flex items-center font-medium text-gray-600 hover:text-indigo-600 transition hover:border-b-2 hover:border-indigo-600">
                    <i class="fas fa-calendar-alt mr-2"></i>Jadwal
                </a>
            </div>
            
            <!-- User Menu -->
            <div class="flex items-center">
                <div class="relative" id="userDropdown">
                    <button id="userDropdownButton" class="flex items-center">
                        <div class="relative h-9 w-9 rounded-full overflow-hidden border-2 border-gray-200">
                            <?php if(isset($user_data['avatar']) && $user_data['avatar']): ?>
                                <img src="../<?php echo AVATAR_PATH . $user_data['avatar']; ?>" class="h-full w-full object-cover" alt="Avatar">
                            <?php else: ?>
                                <img src="../assets/img/avatar-placeholder.png" class="h-full w-full object-cover" alt="Avatar">
                            <?php endif; ?>
                        </div>
                        <span class="hidden md:block text-gray-700 font-medium ml-2">Ryan</span>
                        <i class="fas fa-chevron-down ml-2 text-gray-500 text-xs"></i>
                    </button>
                    <div id="userDropdownMenu" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 hidden animate-fade-in z-50">
                        <div class="p-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-700">Ryan</p>
                            <p class="text-xs text-gray-500"><?php echo $user_data['email'] ?? 'ryanalhmda@gmail.com'; ?></p>
                        </div>
                        <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-600 hover:bg-gray-50 hover:text-indigo-600 transition">
                            <i class="fas fa-user-circle text-gray-400 w-5 mr-2"></i>Profil Saya
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
        <a href="materi.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
            <i class="fas fa-book w-6 mr-2"></i>Materi
        </a>
        <a href="kuis.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
            <i class="fas fa-question-circle w-6 mr-2"></i>Kuis
        </a>
        <a href="jadwal.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
            <i class="fas fa-calendar-alt w-6 mr-2"></i>Jadwal
        </a>
        <a href="profile.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 hover:border-l-4 hover:border-indigo-600">
            <i class="fas fa-user-circle w-6 mr-2"></i>Profil
        </a>
        <a href="../logout.php" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 hover:text-red-600 hover:border-l-4 hover:border-red-600">
            <i class="fas fa-sign-out-alt w-6 mr-2"></i>Keluar
        </a>
    </div>
</nav>

<!-- CSS Tambahan untuk Header -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    body {
        font-family: 'Poppins', sans-serif;
        scroll-behavior: smooth;
    }
    
    #userDropdownButton {
        padding: 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }
    
    #userDropdownButton:hover {
        background-color: #f3f4f6;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
</style>

    <!-- Header -->
    <header class="header-gradient py-12 relative">
        <div class="container mx-auto px-4 relative z-10">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white">Profil Pengguna</h1>
                    <p class="text-indigo-100 mt-2">Kelola informasi pribadi dan pengaturan akun Anda</p>
                </div>
                
                <div class="hidden md:flex text-white">
                    <a href="../dashboard_user.php" class="hover:text-indigo-200">Dashboard</a>
                    <span class="mx-2">›</span>
                    <span>Profil</span>
                </div>
            </div>
        </div>
        <div class="header-waves"></div>
    </header>

    <!-- Content -->
    <div class="container mx-auto px-4 pb-12">
        <!-- Notification Alerts -->
        <?php if($error): ?>
            <div class="alert alert-danger my-4 animate-fade-in" id="errorAlert">
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <span><?php echo $error; ?></span>
                <button type="button" class="ml-auto text-red-700 hover:text-red-900" onclick="closeAlert('errorAlert')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success my-4 animate-fade-in" id="successAlert">
                <i class="fas fa-check-circle alert-icon"></i>
                <span><?php echo $success; ?></span>
                <button type="button" class="ml-auto text-green-700 hover:text-green-900" onclick="closeAlert('successAlert')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mt-6">
            <!-- Profile Sidebar -->
            <div class="md:col-span-4">
                <div class="card mb-6 pt-10">
                    <div class="avatar-container">
                        <div class="avatar-circle">
                            <?php if($user_data['avatar']): ?>
                                <img src="../<?php echo AVATAR_PATH . $user_data['avatar']; ?>" class="avatar-img" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-body text-center">
                        <span class="avatar-label"><?php echo $user_data['name']; ?></span>
                        <span class="avatar-email"><?php echo $user_data['email']; ?></span>
                        
                        <div class="mt-8">
                            <h3 class="section-title">
                                <i class="fas fa-camera"></i> Foto Profil
                            </h3>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <div class="file-upload mb-4">
                                    <button type="button" class="btn-secondary w-full">
                                        <i class="fas fa-upload mr-2"></i> Pilih Foto Baru
                                    </button>
                                    <input type="file" name="avatar" accept="image/*">
                                </div>
                                <p class="text-xs text-gray-500 mb-4">
                                    <i class="fas fa-info-circle mr-1"></i> Maksimum 2MB. Format: JPG, PNG, GIF
                                </p>
                                <button type="submit" name="update_avatar" class="btn-primary w-full">
                                    <i class="fas fa-sync-alt mr-2"></i> Update Foto Profil
                                </button>
                            </form>
                        </div>
                        
                        <div class="mt-8 border-t pt-6">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i> Info Login
                            </h3>
                            
                            <div class="mt-4 space-y-3">
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Login Terakhir: <?php echo $user_data['last_login'] ? date('d/m/Y H:i', strtotime($user_data['last_login'])) : 'Belum ada'; ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Bergabung: <?php echo $user_data['created_at'] ? date('d/m/Y', strtotime($user_data['created_at'])) : '-'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Tabs and Forms -->
            <div class="md:col-span-8">
                <div class="card">
                    <div class="border-b px-6">
                        <div class="flex -mb-px">
                            <button class="tab-nav py-4 px-6 tab-active" data-tab="profile-info">
                                <i class="fas fa-user-edit mr-2"></i> Informasi Profil
                            </button>
                            <button class="tab-nav py-4 px-6" data-tab="password">
                                <i class="fas fa-lock mr-2"></i> Ganti Password
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body tab-content" id="profile-info">
                        <form method="POST" action="">
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="name">
                                    Nama Lengkap
                                </label>
                                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="email">
                                    Email
                                </label>
                                <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="phone">
                                    Nomor Telepon
                                </label>
                                <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="birth_date">
                                    Tanggal Lahir
                                </label>
                                <input type="date" id="birth_date" name="birth_date" class="form-input" value="<?php echo $user_data['birth_date']; ?>">
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="address">
                                    Alamat
                                </label>
                                <textarea id="address" name="address" class="form-input" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" name="update_profile" class="btn-primary">
                                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card-body tab-content hidden" id="password">
                        <form method="POST" action="">
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="current_password">
                                    Password Lama
                                </label>
                                <div class="relative">
                                    <input type="password" id="current_password" name="current_password" class="form-input" required>
                                    <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 password-toggle" data-target="current_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="new_password">
                                    Password Baru
                                </label>
                                <div class="relative">
                                    <input type="password" id="new_password" name="new_password" class="form-input" required>
                                    <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 password-toggle" data-target="new_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-medium mb-2" for="confirm_password">
                                    Konfirmasi Password Baru
                                </label>
                                <div class="relative">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                                    <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 password-toggle" data-target="confirm_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm">Untuk keamanan, pastikan password Anda:</p>
                                        <ul class="mt-2 text-xs space-y-1 list-disc list-inside text-blue-700">
                                            <li>Memiliki minimal 8 karakter</li>
                                            <li>Mengandung huruf besar dan kecil</li>
                                            <li>Mengandung angka atau simbol</li>
                                            <li>Tidak sama dengan password lama</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" name="update_password" class="btn-primary" style="background-color: #10b981;">
                                    <i class="fas fa-key mr-2"></i> Ganti Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="bg-indigo-500 h-10 w-10 rounded-full flex items-center justify-center mr-2">
                            <i class="fas fa-heartbeat text-white"></i>
                        </div>
                        <span class="font-bold text-xl">Penyuluhan KB</span>
                    </div>
                    <p class="text-gray-400 mb-4">Memberikan edukasi dan informasi tentang Keluarga Berencana untuk masyarakat Indonesia.</p>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Link Cepat</h3>
                    <ul class="space-y-2">
                        <li><a href="../dashboard_user.php" class="text-gray-400 hover:text-white transition">Dashboard</a></li>
                        <li><a href="materi.php" class="text-gray-400 hover:text-white transition">Materi</a></li>
                        <li><a href="kuis.php" class="text-gray-400 hover:text-white transition">Kuis</a></li>
                        <li><a href="jadwal.php" class="text-gray-400 hover:text-white transition">Jadwal</a></li>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle dropdown user
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
            
            // Tab switching
            const tabNavs = document.querySelectorAll('.tab-nav');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabNavs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabNavs.forEach(t => t.classList.remove('tab-active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('tab-active');
                    
                    // Hide all tab contents
                    tabContents.forEach(content => content.classList.add('hidden'));
                    
                    // Show selected tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.remove('hidden');
                });
            });
            
            // File input display
            const fileInput = document.querySelector('input[type="file"]');
            const fileButton = document.querySelector('.file-upload button');
            
            if (fileInput && fileButton) {
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const fileName = this.files[0].name;
                        fileButton.innerHTML = '<i class="fas fa-check mr-2"></i> ' + fileName;
                    } else {
                        fileButton.innerHTML = '<i class="fas fa-upload mr-2"></i> Pilih Foto Baru';
                    }
                });
            }
            
            // Password visibility toggle
            const passwordToggles = document.querySelectorAll('.password-toggle');
            
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    fadeOut(alert);
                });
            }, 5000);
        });
        
        // Function to close alerts
        function closeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                fadeOut(alert);
            }
        }
        
        // Function to fade out an element
        function fadeOut(element) {
            let opacity = 1;
            const timer = setInterval(function() {
                if (opacity <= 0.1) {
                    clearInterval(timer);
                    element.style.display = 'none';
                }
                element.style.opacity = opacity;
                opacity -= 0.1;
            }, 50);
        }
    </script>
</body>
</html>