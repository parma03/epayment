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

// Ambil data barang dari database
try {
    $stmt = $pdo->prepare("SELECT * FROM tb_barang WHERE stok_barang > 0 ORDER BY created_at DESC");
    $stmt->execute();
    $barang = $stmt->fetchAll();
} catch (PDOException $e) {
    $alert_message = "Error: " . $e->getMessage();
    $alert_type = "danger";
    $alert_title = "Database Error";
    $alert_icon = "fas fa-exclamation-triangle";
}

// Ambil alert dari session dan hapus setelah digunakan
$alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : $alert_message;
$alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : $alert_type;
$alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : $alert_title;
$alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : $alert_icon;

// Hapus alert dari session setelah digunakan
unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Katalog Produk &mdash; Syania furniture</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="assets/modules/owlcarousel2/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/modules/owlcarousel2/dist/assets/owl.theme.default.min.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">

    <!-- Custom CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            height: 200px;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .price-tag {
            font-size: 1.2em;
            font-weight: bold;
            color: #6777ef;
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
            width: 100%;
        }

        .search-box {
            max-width: 500px;
            margin: 0 auto;
        }

        /* Fixed Navbar Styling */
        .navbar-custom {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%) !important;
            padding: 1rem 0;
            width: 100vw !important;
            margin: 0 !important;
            left: 0 !important;
            right: 0 !important;
            position: fixed !important;
            top: 0 !important;
            z-index: 1030 !important;
        }

        .navbar-custom .container {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 15px;
            padding-right: 15px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: white !important;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #f1c40f !important;
        }

        .btn-login {
            background-color: #f39c12;
            border-color: #f39c12;
            color: white !important;
        }

        .btn-login:hover {
            background-color: #e67e22;
            border-color: #e67e22;
        }

        /* Remove white space/box */
        .navbar-toggler {
            border: none;
            background: rgba(255, 255, 255, 0.2);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=utf8,%3csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Ensure full width */
        .navbar-expand-lg .navbar-collapse {
            width: 100%;
        }

        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        /* Fix container spacing */
        .container-fluid {
            padding: 0 !important;
        }

        /* Adjust body padding for fixed navbar */
        body {
            padding-top: 70px;
        }

        /* Remove any default Bootstrap margins/padding that might cause white space */
        .row {
            margin-left: 0;
            margin-right: 0;
        }

        .col-12,
        .col-md-6,
        .col-lg-3,
        .col-md-4,
        .col-sm-6 {
            padding-left: 15px;
            padding-right: 15px;
        }
    </style>
</head>

<body>
    <!-- Alert Container -->
    <?php if (!empty($alert_message)): ?>
        <div class="alert-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;">
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

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store"></i> Syania furniture
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link btn btn-login px-3 ml-2" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-4 mb-4">Selamat Datang di Toko Syania furniture</h1>
                    <p class="lead mb-4">Temukan produk berkualitas dengan harga terbaik</p>

                    <!-- Search Box -->
                    <div class="search-box">
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg" id="searchProduct"
                                placeholder="Cari produk...">
                            <div class="input-group-append">
                                <button class="btn btn-light btn-lg" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="py-5">
        <div class="container">
            <!-- Filter Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">Katalog Produk</h5>
                                    <small class="text-muted">Menampilkan <?php echo count($barang); ?> produk</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <select class="form-control" id="sortBy">
                                                <option value="newest">Terbaru</option>
                                                <option value="price_low">Harga Terendah</option>
                                                <option value="price_high">Harga Tertinggi</option>
                                                <option value="name">Nama A-Z</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-control" id="priceRange">
                                                <option value="all">Semua Harga</option>
                                                <option value="0-500000">
                                                    < Rp 500.000</option>
                                                <option value="500000-1000000">Rp 500.000 - 1.000.000</option>
                                                <option value="1000000-2000000">Rp 1.000.000 - 2.000.000</option>
                                                <option value="2000000-999999999">> Rp 2.000.000</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row" id="productsGrid">
                <?php if (empty($barang)): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h4>Belum Ada Produk</h4>
                                <p class="text-muted">Produk akan segera tersedia</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($barang as $item): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4 product-item"
                            data-name="<?php echo strtolower($item['nama_barang']); ?>"
                            data-price="<?php echo $item['harga_barang']; ?>">
                            <div class="card product-card">
                                <div class="position-relative">
                                    <div class="product-image">
                                        <?php if (!empty($item['photo_barang'])): ?>
                                            <img src="assets/img/products/<?php echo $item['photo_barang']; ?>"
                                                alt="<?php echo htmlspecialchars($item['nama_barang']); ?>">
                                        <?php else: ?>
                                            <img src="assets/img/products/product-1.png" alt="No Image">
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge badge-primary stock-badge">
                                        Stok: <?php echo $item['stok_barang']; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title mb-2">
                                        <?php echo htmlspecialchars($item['nama_barang']); ?>
                                    </h5>
                                    <p class="card-text text-muted small mb-3">
                                        <?php echo htmlspecialchars(substr($item['deskripsi_barang'], 0, 80)); ?>
                                        <?php if (strlen($item['deskripsi_barang']) > 80): ?>...<?php endif; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="price-tag">Rp
                                            <?php echo number_format($item['harga_barang'], 0, ',', '.'); ?></span>
                                        <small class="text-muted">
                                            <i class="fas fa-box"></i> Tersedia
                                        </small>
                                    </div>
                                    <div class="btn-group w-100">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="viewProduct(<?php echo $item['id_barang']; ?>)">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm"
                                            onclick="redirectToLogin()">
                                            <i class="fas fa-shopping-cart"></i> Beli
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="mb-4">Tentang Kami</h2>
                    <p class="lead">
                        Syania furniture, <br>
                        Kami adalah toko online terpercaya yang menyediakan berbagai produk berkualitas
                        dengan harga yang kompetitif. Kepuasan pelanggan adalah prioritas utama kami.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="mb-4">Kontak Kami</h2>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                            <h5>Telepon</h5>
                            <p>+62 123-456-789</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                            <h5>Email</h5>
                            <p>info@tokoonline.com</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                            <h5>Alamat</h5>
                            <p>Jakarta, Indonesia</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Syania furniture</h5>
                    <p>Platform e-commerce terpercaya untuk semua kebutuhan Anda.</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p>&copy; <?php echo date('Y'); ?> Syania furniture. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Product Detail Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Detail Produk</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="productModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="redirectToLogin()">
                        <i class="fas fa-sign-in-alt"></i> Login untuk Membeli
                    </button>
                </div>
            </div>
        </div>
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
    <script src="assets/modules/owlcarousel2/dist/owl.carousel.min.js"></script>

    <!-- Template JS File -->
    <script src="assets/js/scripts.js"></script>
    <script src="assets/js/custom.js"></script>

    <script>
        // Search functionality
        $('#searchProduct').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.product-item').filter(function() {
                $(this).toggle($(this).data('name').indexOf(value) > -1)
            });
        });

        // Sort functionality
        $('#sortBy').on('change', function() {
            var sortBy = $(this).val();
            var $products = $('.product-item');
            var $container = $('#productsGrid');

            $products.sort(function(a, b) {
                switch (sortBy) {
                    case 'price_low':
                        return $(a).data('price') - $(b).data('price');
                    case 'price_high':
                        return $(b).data('price') - $(a).data('price');
                    case 'name':
                        return $(a).data('name').localeCompare($(b).data('name'));
                    default:
                        return 0;
                }
            });

            $container.html($products);
        });

        // Price range filter
        $('#priceRange').on('change', function() {
            var range = $(this).val();
            if (range === 'all') {
                $('.product-item').show();
                return;
            }

            var [min, max] = range.split('-').map(Number);
            $('.product-item').each(function() {
                var price = $(this).data('price');
                $(this).toggle(price >= min && price <= max);
            });
        });

        // View product detail
        function viewProduct(id) {
            $.ajax({
                url: 'get_product_detail.php',
                method: 'GET',
                data: {
                    id: id
                },
                success: function(response) {
                    $('#productModalBody').html(response);
                    $('#productModal').modal('show');
                },
                error: function() {
                    showAlert('Error loading product detail', 'danger', 'Error', 'fas fa-times-circle');
                }
            });
        }

        // Redirect to login page
        function redirectToLogin() {
            showAlert('Silakan login terlebih dahulu untuk melakukan pembelian', 'info', 'Login Diperlukan', 'fas fa-info-circle');
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 2000);
        }

        // Smooth scrolling for navigation links
        $('a[href^="#"]').on('click', function(event) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80
                }, 1000);
            }
        });

        // Show alert function
        function showAlert(message, type, title, icon) {
            // Remove existing alerts
            $('.alert-container').remove();

            var alertHtml = `
                <div class="alert-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;">
                    <div class="alert alert-${type} alert-has-icon alert-dismissible fade show" role="alert">
                        <div class="alert-icon"><i class="${icon}"></i></div>
                        <div class="alert-body">
                            <div class="alert-title">${title}</div>
                            ${message}
                        </div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            `;

            $('body').prepend(alertHtml);

            // Auto hide after 5 seconds
            setTimeout(function() {
                $('.alert-container').fadeOut();
            }, 5000);
        }

        // Auto hide alerts on page load
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        });
    </script>
</body>

</html>