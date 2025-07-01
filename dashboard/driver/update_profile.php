<?php
// File: _component/navbar.php
session_start();
include '../../db/koneksi.php';

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id_user = $_SESSION['id_user'];
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $current_password = trim($_POST['current_password']);

        // Validasi input
        if (empty($email)) {
            throw new Exception('Email tidak boleh kosong');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid');
        }

        // Cek apakah email sudah digunakan user lain
        $stmt = $pdo->prepare("SELECT id_user FROM tb_user WHERE email = ? AND id_user != ?");
        $stmt->execute([$email, $id_user]);
        if ($stmt->fetch()) {
            throw new Exception('Email sudah digunakan oleh user lain');
        }

        // Ambil data user saat ini
        $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
        $stmt->execute([$id_user]);
        $current_user = $stmt->fetch();

        if (!$current_user) {
            throw new Exception('User tidak ditemukan');
        }

        // Jika password lama diisi, validasi password lama
        if (!empty($current_password)) {
            if ($current_user['password'] !== $current_password) {
                throw new Exception('Password lama tidak sesuai');
            }
        }

        // Persiapan query update
        $update_fields = [];
        $update_values = [];

        // Update email
        $update_fields[] = "email = ?";
        $update_values[] = $email;

        // Update password jika diisi
        if (!empty($password)) {
            if (empty($current_password)) {
                throw new Exception('Password lama harus diisi untuk mengubah password');
            }
            $update_fields[] = "password = ?";
            $update_values[] = $password;
        }

        // Handle upload foto profile
        $photo_filename = $current_user['photo_profile']; // Keep current photo by default

        if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/img/avatar/';

            // Validasi file
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['photo_profile']['type'];

            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF');
            }

            if ($_FILES['photo_profile']['size'] > 2 * 1024 * 1024) { // 2MB
                throw new Exception('Ukuran file terlalu besar. Maksimal 2MB');
            }

            // Generate filename unik
            $file_extension = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
            $photo_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $photo_filename;

            // Buat direktori jika belum ada
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Upload file
            if (!move_uploaded_file($_FILES['photo_profile']['tmp_name'], $upload_path)) {
                throw new Exception('Gagal mengupload foto profile');
            }

            // Hapus foto lama jika ada dan bukan default
            if (
                $current_user['photo_profile'] &&
                $current_user['photo_profile'] !== 'avatar-1.png' &&
                file_exists($upload_dir . $current_user['photo_profile'])
            ) {
                unlink($upload_dir . $current_user['photo_profile']);
            }
        }

        // Update photo_profile jika ada perubahan
        if ($photo_filename !== $current_user['photo_profile']) {
            $update_fields[] = "photo_profile = ?";
            $update_values[] = $photo_filename;
        }

        // Update timestamp
        $update_fields[] = "update_at = NOW()";
        $update_values[] = $id_user; // untuk WHERE clause

        // Execute update
        $sql = "UPDATE tb_user SET " . implode(', ', $update_fields) . " WHERE id_user = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($update_values);

        // Update session data
        $_SESSION['email'] = $email;
        $_SESSION['photo_profile'] = $photo_filename;

        // Set success message
        $_SESSION['alert_message'] = 'Profile berhasil diperbarui';
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_title'] = 'Berhasil!';
        $_SESSION['alert_icon'] = 'fas fa-check-circle';

        // Redirect ke halaman yang sama untuk mencegah resubmit
        $current_page = $_SERVER['REQUEST_URI'];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();

    } catch (Exception $e) {
        // Set error message
        $_SESSION['alert_message'] = $e->getMessage();
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Error!';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';

        // Redirect ke halaman yang sama
        $current_page = $_SERVER['REQUEST_URI'];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}


?>