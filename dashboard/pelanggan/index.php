<?php
session_start();
include '../../db/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Pengecekan session untuk redirect jika sudah login admin
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Driver') {
        header("Location: ../dashboard/driver/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Gudang') {
        header("Location: ../dashboard/gudang/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pelayan') {
        header("Location: ../dashboard/pelayan/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Administrator') {
        header("Location: ../dashboard/admin/index.php");
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
    <title>Toko Online &mdash; Stisla</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="../../assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="../../assets/modules/owlcarousel2/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../../assets/modules/owlcarousel2/dist/assets/owl.theme.default.min.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/components.css">

    <!-- Custom CSS -->
    <style>
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
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
        }

        .search-box {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div id="app">
        <!-- Alert Container -->
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

        <div class="main-wrapper main-wrapper-1">
            <div class="navbar-bg"></div>
            <?php include '_component/navbar.php'; ?>
            <div class="main-sidebar sidebar-style-2">
                <?php include '_component/sidebar.php'; ?>
            </div>

            <!-- Hero Section -->
            <div class="hero-section">
                <div class="container">
                    <div class="row">
                        <div class="col-12 text-center">
                            <h1 class="display-4 mb-4">Selamat Datang di Toko Online Kami</h1>
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
            </div>

            <!-- Main Content -->
            <div class="main-content" style="margin-top: 0; padding-top: 0;">
                <section class="section">
                    <div class="container">
                        <!-- Filter Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h5 class="mb-0">Katalog Produk</h5>
                                                <small class="text-muted">Menampilkan <?php echo count($barang); ?>
                                                    produk</small>
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
                                                            <option value="500000-1000000">Rp 500.000 - 1.000.000
                                                            </option>
                                                            <option value="1000000-2000000">Rp 1.000.000 - 2.000.000
                                                            </option>
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
                                                        <img src="../../assets/img/products/<?php echo $item['photo_barang']; ?>"
                                                            alt="<?php echo htmlspecialchars($item['nama_barang']); ?>">
                                                    <?php else: ?>
                                                        <img src="../../assets/img/products/product-1.png" alt="No Image">
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
                                                        onclick="buyProduct(<?php echo $item['id_barang']; ?>)">
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
            </div>

            <?php include '_component/footer.php'; ?>
        </div>
    </div>

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
            </div>
        </div>
    </div>

    <!-- Purchase Modal -->
    <div class="modal fade" id="purchaseModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Pembelian</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="purchaseForm">
                    <div class="modal-body">
                        <input type="hidden" id="product_id" name="product_id">

                        <div class="form-group">
                            <label>Nama Pemesan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_pemesan" name="nama_pemesan" required>
                        </div>

                        <div class="form-group">
                            <label>No. HP <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="nohp_pemesan" name="nohp_pemesan" required>
                        </div>

                        <div class="form-group">
                            <label>Alamat Pengiriman <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="alamat_pemesan" name="alamat_pemesan" rows="3"
                                required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Jumlah <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="jumlah_beli" name="jumlah_beli" min="1"
                                value="1" required>
                        </div>

                        <div class="form-group">
                            <label>Total Harga</label>
                            <input type="text" class="form-control" id="total_harga_display" readonly>
                            <input type="hidden" id="total_harga" name="total_harga">
                            <input type="hidden" id="harga_satuan" name="harga_satuan">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnPurchase">
                            <i class="fas fa-credit-card"></i> Bayar Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- General JS Scripts -->
    <script src="../../assets/modules/jquery.min.js"></script>
    <script src="../../assets/modules/popper.js"></script>
    <script src="../../assets/modules/tooltip.js"></script>
    <script src="../../assets/modules/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
    <script src="../../assets/modules/moment.min.js"></script>
    <script src="../../assets/js/stisla.js"></script>

    <!-- JS Libraries -->
    <script src="../../assets/modules/owlcarousel2/dist/owl.carousel.min.js"></script>

    <!-- Template JS File -->
    <script src="../../assets/js/scripts.js"></script>
    <script src="../../assets/js/custom.js"></script>

    <!-- Midtrans Snap JS -->
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="Mid-client-i_gMpoalNpsZFVjf"></script>

    <script>
        // Search functionality
        $('#searchProduct').on('keyup', function () {
            var value = $(this).val().toLowerCase();
            $('.product-item').filter(function () {
                $(this).toggle($(this).data('name').indexOf(value) > -1)
            });
        });

        // Sort functionality
        $('#sortBy').on('change', function () {
            var sortBy = $(this).val();
            var $products = $('.product-item');
            var $container = $('#productsGrid');

            $products.sort(function (a, b) {
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
        $('#priceRange').on('change', function () {
            var range = $(this).val();
            if (range === 'all') {
                $('.product-item').show();
                return;
            }

            var [min, max] = range.split('-').map(Number);
            $('.product-item').each(function () {
                var price = $(this).data('price');
                $(this).toggle(price >= min && price <= max);
            });
        });

        // View product detail
        function viewProduct(id) {
            $.ajax({
                url: 'get_product_detail.php',
                method: 'GET',
                data: { id: id },
                success: function (response) {
                    $('#productModalBody').html(response);
                    $('#productModal').modal('show');
                },
                error: function () {
                    alert('Error loading product detail');
                }
            });
        }

        // Buy product
        function buyProduct(id) {
            // First check if user is logged in by making a simple request
            $.ajax({
                url: 'check_login_status.php',
                method: 'GET',
                dataType: 'json',
                success: function (loginResponse) {
                    if (!loginResponse.logged_in) {
                        showAlert('Anda harus login terlebih dahulu untuk melakukan pembelian!', 'warning', 'Login Diperlukan', 'fas fa-exclamation-triangle');
                        return;
                    }

                    // If logged in, proceed with getting product details
                    $.ajax({
                        url: 'get_product_detail.php',
                        method: 'GET',
                        data: { id: id, action: 'buy' },
                        dataType: 'json',
                        success: function (data) {
                            $('#product_id').val(data.id_barang);
                            $('#harga_satuan').val(data.harga_barang);
                            $('#total_harga').val(data.harga_barang);
                            $('#total_harga_display').val('Rp ' + new Intl.NumberFormat('id-ID').format(data.harga_barang));
                            $('#purchaseModal').modal('show');
                        },
                        error: function () {
                            showAlert('Error loading product data', 'danger', 'Error', 'fas fa-times-circle');
                        }
                    });
                },
                error: function () {
                    showAlert('Error checking login status', 'danger', 'Error', 'fas fa-times-circle');
                }
            });
        }

        // Calculate total price
        $('#jumlah_beli').on('input', function () {
            var qty = parseInt($(this).val()) || 1;
            var harga = parseInt($('#harga_satuan').val());
            var total = qty * harga;

            $('#total_harga').val(total);
            $('#total_harga_display').val('Rp ' + new Intl.NumberFormat('id-ID').format(total));
        });

        // Handle purchase form submission
        $('#purchaseForm').on('submit', function (e) {
            e.preventDefault();

            var formData = new FormData(this);
            $('#btnPurchase').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: 'process_payment.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        $('#purchaseModal').modal('hide');

                        // Trigger Midtrans Snap
                        snap.pay(response.snap_token, {
                            onSuccess: function (result) {
                                // Kirim data ke server untuk menyimpan transaksi
                                $.ajax({
                                    url: 'payment_success.php',
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'success',
                                        transaction_result: JSON.stringify(result)
                                    },
                                    dataType: 'json',
                                    success: function (saveResponse) {
                                        // Set success alert
                                        sessionStorage.setItem('alert_message', 'Pembayaran berhasil! Terima kasih atas pembelian Anda.');
                                        sessionStorage.setItem('alert_type', 'success');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Berhasil');
                                        sessionStorage.setItem('alert_icon', 'fas fa-check-circle');
                                        location.reload();
                                    },
                                    error: function () {
                                        // Meskipun pembayaran berhasil, ada error saat menyimpan
                                        sessionStorage.setItem('alert_message', 'Pembayaran berhasil tetapi ada error saat menyimpan data. Silakan hubungi customer service.');
                                        sessionStorage.setItem('alert_type', 'warning');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Berhasil');
                                        sessionStorage.setItem('alert_icon', 'fas fa-exclamation-triangle');
                                        location.reload();
                                    }
                                });
                            },
                            onPending: function (result) {
                                // Kirim data pending ke server
                                $.ajax({
                                    url: 'payment_success.php',
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'pending',
                                        transaction_result: JSON.stringify(result)
                                    },
                                    dataType: 'json',
                                    complete: function () {
                                        // Set pending alert
                                        sessionStorage.setItem('alert_message', 'Pembayaran sedang diproses. Silakan selesaikan pembayaran Anda.');
                                        sessionStorage.setItem('alert_type', 'info');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Pending');
                                        sessionStorage.setItem('alert_icon', 'fas fa-clock');
                                        location.reload();
                                    }
                                });
                            },
                            onError: function (result) {
                                // Kirim data error ke server
                                $.ajax({
                                    url: 'payment_success.php',
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'error',
                                        transaction_result: JSON.stringify(result)
                                    },
                                    dataType: 'json',
                                    complete: function () {
                                        // Set error alert
                                        sessionStorage.setItem('alert_message', 'Pembayaran gagal! Silakan coba lagi.');
                                        sessionStorage.setItem('alert_type', 'danger');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Gagal');
                                        sessionStorage.setItem('alert_icon', 'fas fa-times-circle');
                                        location.reload();
                                    }
                                });
                            },
                            onClose: function () {
                                // Kirim data cancel ke server
                                $.ajax({
                                    url: 'payment_success.php',
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'cancel'
                                    },
                                    dataType: 'json',
                                    complete: function () {
                                        // Set close alert
                                        sessionStorage.setItem('alert_message', 'Anda menutup popup pembayaran. Transaksi dibatalkan.');
                                        sessionStorage.setItem('alert_type', 'warning');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Dibatalkan');
                                        sessionStorage.setItem('alert_icon', 'fas fa-exclamation-triangle');
                                        location.reload();
                                    }
                                });
                            }
                        });
                    } else {
                        // Handle error response
                        if (response.redirect) {
                            // Jika perlu redirect (seperti harus login), reload halaman untuk menampilkan alert
                            location.reload();
                        } else {
                            // Tampilkan alert error langsung
                            showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showAlert('Terjadi kesalahan dalam memproses pembayaran. Silakan coba lagi.', 'danger', 'Error', 'fas fa-times-circle');
                },
                complete: function () {
                    $('#btnPurchase').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Bayar Sekarang');
                }
            });
        });

        function showAlert(message, type, title, icon) {
            // Remove existing alerts
            $('.alert-container').remove();

            var alertHtml = `
        <div class="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
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
            setTimeout(function () {
                $('.alert-container').fadeOut();
            }, 5000);
        }

        $(document).ready(function () {
            // Check sessionStorage for alert messages
            var alertMessage = sessionStorage.getItem('alert_message');
            var alertType = sessionStorage.getItem('alert_type');
            var alertTitle = sessionStorage.getItem('alert_title');
            var alertIcon = sessionStorage.getItem('alert_icon');

            if (alertMessage) {
                showAlert(alertMessage, alertType, alertTitle, alertIcon);

                // Clear the stored alert
                sessionStorage.removeItem('alert_message');
                sessionStorage.removeItem('alert_type');
                sessionStorage.removeItem('alert_title');
                sessionStorage.removeItem('alert_icon');
            }
        });
    </script>
</body>

</html>