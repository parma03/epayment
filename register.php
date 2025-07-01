<?php
session_start();
include 'db/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Administrator') {
        header("Location: dashboard/admin/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Driver') {
        header("Location: dashboard/driver/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Gudang') {
        header("Location: dashboard/gudang/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pelayan') {
        header("Location: dashboard/pelayan/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pelanggan') {
        header("Location: dashboard/pelanggan/index.php");
        exit();
    }
}

// Proses registrasi
if ($_POST) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password-confirm']);

    // Validasi input
    if (empty($email) || empty($password) || empty($password_confirm)) {
        $alert_message = 'Semua field harus diisi!';
        $alert_type = 'danger';
        $alert_title = 'Error!';
        $alert_icon = 'fas fa-exclamation-circle';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert_message = 'Format email tidak valid!';
        $alert_type = 'danger';
        $alert_title = 'Error!';
        $alert_icon = 'fas fa-exclamation-circle';
    } else if ($password !== $password_confirm) {
        $alert_message = 'Password dan konfirmasi password tidak sama!';
        $alert_type = 'danger';
        $alert_title = 'Error!';
        $alert_icon = 'fas fa-exclamation-circle';
    } else if (strlen($password) < 6) {
        $alert_message = 'Password minimal 6 karakter!';
        $alert_type = 'danger';
        $alert_title = 'Error!';
        $alert_icon = 'fas fa-exclamation-circle';
    } else {
        try {
            // Cek apakah email sudah terdaftar
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_user WHERE email = ?");
            $stmt->execute([$email]);
            $email_exists = $stmt->fetchColumn();

            if ($email_exists > 0) {
                $alert_message = 'Email sudah terdaftar! Gunakan email lain.';
                $alert_type = 'danger';
                $alert_title = 'Error!';
                $alert_icon = 'fas fa-exclamation-circle';
            } else {
                // Hash password untuk keamanan (opsional, sesuaikan dengan sistem existing)
                // $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert data ke database
                $stmt = $pdo->prepare("INSERT INTO tb_user (email, password, role, created_at) VALUES (?, ?, 'Pelanggan', NOW())");
                $result = $stmt->execute([$email, $password]); // Gunakan $hashed_password jika ingin hash

                if ($result) {
                    $_SESSION['alert_message'] = 'Registrasi berhasil! Silakan login dengan akun Anda.';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Berhasil!';
                    $_SESSION['alert_icon'] = 'fas fa-check-circle';

                    header("Location: index.php");
                    exit();
                } else {
                    $alert_message = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
                    $alert_type = 'danger';
                    $alert_title = 'Error!';
                    $alert_icon = 'fas fa-exclamation-circle';
                }
            }
        } catch (PDOException $e) {
            $alert_message = 'Terjadi kesalahan database: ' . $e->getMessage();
            $alert_type = 'danger';
            $alert_title = 'Error!';
            $alert_icon = 'fas fa-exclamation-circle';
        }
    }
}

// Ambil alert dari session dan hapus setelah digunakan
if (empty($alert_message)) {
    $alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : '';
    $alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : '';
    $alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : '';
    $alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : '';
}

// Hapus alert dari session setelah digunakan
unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Register Pelanggan &mdash; E-Payment</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="assets/modules/jquery-selectric/selectric.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">

    <style>
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div id="app">
        <!-- Alert Container di pojok kanan atas -->
        <?php if (!empty($alert_message)): ?>
            <div class="alert-container">
                <div class="alert alert-<?php echo $alert_type; ?> alert-has-icon alert-dismissible fade show fade-in"
                    role="alert">
                    <div class="alert-icon"><i class="<?php echo $alert_icon; ?>"></i></div>
                    <div class="alert-body">
                        <div class="alert-title"><?php echo $alert_title; ?></div>
                        <?php echo $alert_message; ?>
                    </div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <section class="section">
            <div class="container mt-5">
                <div class="row">
                    <div
                        class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                        <div class="login-brand">
                            <img src="assets/img/stisla-fill.svg" alt="logo" width="100"
                                class="shadow-light rounded-circle">
                        </div>

                        <div class="card card-primary">
                            <div class="card-header text-center">
                                <h4>Register Pelanggan</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" id="registerForm">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input id="email" type="email" class="form-control" name="email"
                                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                            autofocus required>
                                        <div class="invalid-feedback" id="email-feedback"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="password" class="d-block">Password</label>
                                        <input id="password" type="password" class="form-control pwstrength"
                                            data-indicator="pwindicator" name="password" required>
                                        <div id="pwindicator" class="pwindicator">
                                            <div class="bar"></div>
                                            <div class="label"></div>
                                        </div>
                                        <small class="text-muted">Minimal 6 karakter</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="password2" class="d-block">Konfirmasi Password</label>
                                        <input id="password2" type="password" class="form-control"
                                            name="password-confirm" required>
                                        <div class="invalid-feedback" id="password-confirm-feedback"></div>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="agree" class="custom-control-input" id="agree"
                                                required>
                                            <label class="custom-control-label" for="agree">
                                                Saya setuju dengan syarat dan ketentuan yang berlaku
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                                            <i class="fas fa-user-plus"></i> Daftar Sekarang
                                        </button>
                                    </div>

                                    <div class="text-center">
                                        <p class="mb-0">Sudah punya akun?
                                            <a href="index.php" class="text-primary font-weight-bold">Login di sini</a>
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="simple-footer">
                            Copyright &copy; Nesha Sadina 2025 <?php echo date('Y'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- General JS Scripts -->
    <script src="assets/modules/jquery.min.js"></script>
    <script src="assets/modules/popper.js"></script>
    <script src="assets/modules/tooltip.js"></script>
    <script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
    <script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
    <script src="assets/modules/moment.min.js"></script>
    <script src="assets/js/stisla.js"></script>

    <!-- JS Libraries -->
    <script src="assets/modules/jquery-pwstrength/jquery.pwstrength.min.js"></script>
    <script src="assets/modules/jquery-selectric/jquery.selectric.min.js"></script>

    <!-- Page Specific JS File -->
    <script src="assets/js/page/auth-register.js"></script>

    <!-- Template JS File -->
    <script src="assets/js/scripts.js"></script>
    <script src="assets/js/custom.js"></script>

    <!-- Custom Validation Script -->
    <script>
        $(document).ready(function () {
            // Real-time password confirmation validation
            $('#password2').on('keyup', function () {
                var password = $('#password').val();
                var confirmPassword = $(this).val();

                if (confirmPassword !== '') {
                    if (password !== confirmPassword) {
                        $(this).addClass('is-invalid');
                        $('#password-confirm-feedback').text('Password tidak sama');
                    } else {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                        $('#password-confirm-feedback').text('');
                    }
                }
            });

            // Form validation
            $('#registerForm').on('submit', function (e) {
                var isValid = true;
                var email = $('#email').val();
                var password = $('#password').val();
                var confirmPassword = $('#password2').val();
                var agree = $('#agree').is(':checked');

                // Reset validation states
                $('.form-control').removeClass('is-invalid');

                // Email validation
                if (!email || !isValidEmail(email)) {
                    $('#email').addClass('is-invalid');
                    $('#email-feedback').text('Email tidak valid');
                    isValid = false;
                }

                // Password validation
                if (!password || password.length < 6) {
                    $('#password').addClass('is-invalid');
                    isValid = false;
                }

                // Password confirmation validation
                if (password !== confirmPassword) {
                    $('#password2').addClass('is-invalid');
                    $('#password-confirm-feedback').text('Password tidak sama');
                    isValid = false;
                }

                // Agreement validation
                if (!agree) {
                    alert('Anda harus menyetujui syarat dan ketentuan');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            function isValidEmail(email) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Auto hide alerts after 5 seconds
            setTimeout(function () {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>

</html>