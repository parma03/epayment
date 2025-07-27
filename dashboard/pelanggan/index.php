<?php
session_start();
include '../../db/koneksi.php';
require_once '../../config/midtrans_config.php';

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
                                                    <div class="btn-group w-100">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            onclick="viewProduct(<?php echo $item['id_barang']; ?>)">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </button>
                                                        <button type="button" class="btn btn-success btn-sm"
                                                            onclick="addToCart(<?php echo $item['id_barang']; ?>)">
                                                            <i class="fas fa-cart-plus"></i> Keranjang
                                                        </button>
                                                    </div>
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

    <!-- GANTI dengan: Quantity Modal untuk Add to Cart -->
    <div class="modal fade" id="addToCartModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah ke Keranjang</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="addToCartForm">
                    <div class="modal-body">
                        <input type="hidden" id="add_product_id" name="product_id">

                        <div class="text-center mb-3">
                            <img id="add_product_image" src="" alt="Product" class="img-fluid rounded" style="max-height: 100px;">
                            <h6 id="add_product_name" class="mt-2"></h6>
                            <p id="add_product_price" class="text-primary font-weight-bold"></p>
                        </div>

                        <div class="form-group">
                            <label>Jumlah</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <button class="btn btn-outline-secondary" type="button" onclick="decreaseQty()">-</button>
                                </div>
                                <input type="number" class="form-control text-center" id="add_quantity" name="quantity" value="1" min="1" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="increaseQty()">+</button>
                                </div>
                            </div>
                            <small class="text-muted">Stok tersedia: <span id="add_stock_info"></span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- GANTI: Checkout Modal (untuk proses pembayaran dari keranjang) -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-credit-card mr-2"></i>Checkout
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="checkoutForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Informasi Pengiriman</h6>
                                <div class="form-group">
                                    <label>Nama Pemesan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="checkout_nama_pemesan" name="nama_pemesan" required>
                                </div>
                                <div class="form-group">
                                    <label>No. HP <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="checkout_nohp_pemesan" name="nohp_pemesan" required>
                                </div>
                                <div class="form-group">
                                    <label>Alamat Pengiriman <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="checkout_alamat_pemesan" name="alamat_pemesan" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Ringkasan Pesanan</h6>
                                <div id="checkoutSummary">
                                    <!-- Order summary will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" id="btnCheckout">
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
        data-client-key="<?php echo MidtransConfig::getClientKey(); ?>"></script>

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
                    alert('Error loading product detail');
                }
            });
        }

        // Buy product
        function addToCart(id) {
            // Check login status first
            $.ajax({
                url: 'check_login_status.php',
                method: 'GET',
                dataType: 'json',
                success: function(loginResponse) {
                    if (!loginResponse.logged_in) {
                        showAlert('Anda harus login terlebih dahulu!', 'warning', 'Login Diperlukan', 'fas fa-exclamation-triangle');
                        return;
                    }

                    // Get product details
                    $.ajax({
                        url: 'get_product_detail.php',
                        method: 'GET',
                        data: {
                            id: id,
                            action: 'add_to_cart'
                        },
                        dataType: 'json',
                        success: function(data) {
                            $('#add_product_id').val(data.id_barang);
                            $('#add_product_name').text(data.nama_barang);
                            $('#add_product_price').text('Rp ' + new Intl.NumberFormat('id-ID').format(data.harga_barang));
                            $('#add_stock_info').text(data.stok_barang);
                            $('#add_quantity').attr('max', data.stok_barang).val(1);

                            // Set product image
                            let imageSrc = data.photo_barang ?
                                '../../assets/img/products/' + data.photo_barang :
                                '../../assets/img/products/product-1.png';
                            $('#add_product_image').attr('src', imageSrc);

                            $('#addToCartModal').modal('show');
                        },
                        error: function() {
                            showAlert('Error loading product data', 'danger', 'Error', 'fas fa-times-circle');
                        }
                    });
                },
                error: function() {
                    showAlert('Error checking login status', 'danger', 'Error', 'fas fa-times-circle');
                }
            });
        }

        function increaseQty() {
            let qtyInput = $('#add_quantity');
            let currentQty = parseInt(qtyInput.val());
            let maxQty = parseInt(qtyInput.attr('max'));

            if (currentQty < maxQty) {
                qtyInput.val(currentQty + 1);
            }
        }

        function decreaseQty() {
            let qtyInput = $('#add_quantity');
            let currentQty = parseInt(qtyInput.val());

            if (currentQty > 1) {
                qtyInput.val(currentQty - 1);
            }
        }

        // TAMBAH: Handle add to cart form
        $('#addToCartForm').on('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);
            formData.append('action', 'add');

            $.ajax({
                url: 'cart_operations.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#addToCartModal').modal('hide');
                        showAlert(response.message, 'success', 'Berhasil', 'fas fa-check-circle');
                        updateCartCount(response.cart_count);
                    } else {
                        showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                    }
                },
                error: function() {
                    showAlert('Terjadi kesalahan saat menambahkan ke keranjang', 'danger', 'Error', 'fas fa-times-circle');
                }
            });
        });

        // TAMBAH: Fungsi untuk menampilkan keranjang
        function showCart() {
            $.ajax({
                url: 'cart_operations.php',
                method: 'POST',
                data: {
                    action: 'get'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        displayCartItems(response.items, response.total_amount);
                        $('#cartModal').modal('show');
                    } else {
                        showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                    }
                },
                error: function() {
                    showAlert('Error loading cart', 'danger', 'Error', 'fas fa-times-circle');
                }
            });
        }

        // TAMBAH: Fungsi untuk menampilkan items di keranjang
        function displayCartItems(items, totalAmount) {
            let html = '';

            if (items.length === 0) {
                html = `
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5>Keranjang Kosong</h5>
                <p class="text-muted">Belum ada produk di keranjang Anda</p>
            </div>
        `;
                $('#checkoutBtn').prop('disabled', true);
            } else {
                html = '<div class="table-responsive"><table class="table table-bordered">';
                html += '<thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th>Aksi</th></tr></thead><tbody>';

                items.forEach(function(item) {
                    let imageSrc = item.photo_barang ?
                        '../../assets/img/products/' + item.photo_barang :
                        '../../assets/img/products/product-1.png';

                    html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${imageSrc}" alt="${item.nama_barang}" class="img-thumbnail mr-2" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <strong>${item.nama_barang}</strong><br>
                                <small class="text-muted">Stok: ${item.stok_barang}</small>
                            </div>
                        </div>
                    </td>
                    <td>Rp ${new Intl.NumberFormat('id-ID').format(item.harga_barang)}</td>
                    <td>
                        <div class="input-group" style="width: 120px;">
                            <div class="input-group-prepend">
                                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="updateCartQuantity(${item.id_keranjang}, ${item.jumlah - 1}, ${item.stok_barang})">-</button>
                            </div>
                            <input type="number" class="form-control form-control-sm text-center" value="${item.jumlah}" min="1" max="${item.stok_barang}" onchange="updateCartQuantity(${item.id_keranjang}, this.value, ${item.stok_barang})">
                            <div class="input-group-append">
                                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="updateCartQuantity(${item.id_keranjang}, ${item.jumlah + 1}, ${item.stok_barang})">+</button>
                            </div>
                        </div>
                    </td>
                    <td>Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id_keranjang})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
                });

                html += '</tbody></table></div>';
                html += `
            <div class="border-top pt-3">
                <div class="row">
                    <div class="col-6">
                        <button class="btn btn-warning btn-sm" onclick="clearCart()">
                            <i class="fas fa-trash"></i> Kosongkan Keranjang
                        </button>
                    </div>
                    <div class="col-6 text-right">
                        <h5>Total: Rp ${new Intl.NumberFormat('id-ID').format(totalAmount)}</h5>
                    </div>
                </div>
            </div>
        `;
                $('#checkoutBtn').prop('disabled', false);
            }

            $('#cartModalBody').html(html);
        }

        // TAMBAH: Fungsi untuk update quantity di keranjang
        function updateCartQuantity(cartId, newQuantity, maxStock) {
            if (newQuantity < 1 || newQuantity > maxStock) return;

            $.ajax({
                url: 'cart_operations.php',
                method: 'POST',
                data: {
                    action: 'update',
                    cart_id: cartId,
                    quantity: newQuantity
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showCart(); // Refresh cart display
                        updateCartCount(); // Update cart count in navbar
                    } else {
                        showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                    }
                },
                error: function() {
                    showAlert('Error updating cart', 'danger', 'Error', 'fas fa-times-circle');
                }
            });
        }

        // TAMBAH: Fungsi untuk hapus item dari keranjang
        function removeFromCart(cartId) {
            if (confirm('Apakah Anda yakin ingin menghapus item ini dari keranjang?')) {
                $.ajax({
                    url: 'cart_operations.php',
                    method: 'POST',
                    data: {
                        action: 'remove',
                        cart_id: cartId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert(response.message, 'success', 'Berhasil', 'fas fa-check-circle');
                            showCart(); // Refresh cart display
                            updateCartCount(response.cart_count);
                        } else {
                            showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                        }
                    },
                    error: function() {
                        showAlert('Error removing item', 'danger', 'Error', 'fas fa-times-circle');
                    }
                });
            }
        }

        // TAMBAH: Fungsi untuk kosongkan keranjang
        function clearCart() {
            if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
                $.ajax({
                    url: 'cart_operations.php',
                    method: 'POST',
                    data: {
                        action: 'clear'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert(response.message, 'success', 'Berhasil', 'fas fa-check-circle');
                            showCart(); // Refresh cart display
                            updateCartCount(0);
                        } else {
                            showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                        }
                    },
                    error: function() {
                        showAlert('Error clearing cart', 'danger', 'Error', 'fas fa-times-circle');
                    }
                });
            }
        }

        // TAMBAH: Fungsi untuk kosongkan keranjang
        function clearCart() {
            if (confirm('Apakah Anda yakin ingin mengosongkan keranjang?')) {
                $.ajax({
                    url: 'cart_operations.php',
                    method: 'POST',
                    data: {
                        action: 'clear'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert(response.message, 'success', 'Berhasil', 'fas fa-check-circle');
                            showCart(); // Refresh cart display
                            updateCartCount(0);
                        } else {
                            showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                        }
                    },
                    error: function() {
                        showAlert('Error clearing cart', 'danger', 'Error', 'fas fa-times-circle');
                    }
                });
            }
        }

        // TAMBAH: Fungsi untuk update cart count di navbar
        function updateCartCount(count) {
            if (count === undefined) {
                // Get current count from server
                $.ajax({
                    url: 'cart_operations.php',
                    method: 'POST',
                    data: {
                        action: 'get'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            let totalItems = response.items.reduce((sum, item) => sum + parseInt(item.jumlah), 0);
                            updateCartBadge(totalItems);
                        }
                    }
                });
            } else {
                updateCartBadge(count);
            }
        }

        function updateCartBadge(count) {
            let cartBadge = $('#cartCount');
            if (count > 0) {
                if (cartBadge.length) {
                    cartBadge.text(count);
                } else {
                    $('.fa-shopping-cart').parent().append(`<span class="badge badge-danger badge-pill cart-count" id="cartCount">${count}</span>`);
                }
            } else {
                cartBadge.remove();
            }
        }

        // TAMBAH: Fungsi untuk proceed to checkout
        function proceedToCheckout() {
            $('#cartModal').modal('hide');

            // Load cart items for checkout
            $.ajax({
                url: 'cart_operations.php',
                method: 'POST',
                data: {
                    action: 'get'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.items.length > 0) {
                        displayCheckoutSummary(response.items, response.total_amount);
                        $('#checkoutModal').modal('show');
                    } else {
                        showAlert('Keranjang kosong', 'warning', 'Peringatan', 'fas fa-exclamation-triangle');
                    }
                },
                error: function() {
                    showAlert('Error loading checkout data', 'danger', 'Error', 'fas fa-times-circle');
                }
            });
        }

        // TAMBAH: Fungsi untuk menampilkan summary checkout
        function displayCheckoutSummary(items, totalAmount) {
            let html = '<div class="card">';
            html += '<div class="card-body">';

            items.forEach(function(item) {
                html += `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>${item.nama_barang}</strong><br>
                    <small class="text-muted">${item.jumlah} x Rp ${new Intl.NumberFormat('id-ID').format(item.harga_barang)}</small>
                </div>
                <span>Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal)}</span>
            </div>
        `;
            });

            html += '<hr>';
            html += `<div class="d-flex justify-content-between"><strong>Total: Rp ${new Intl.NumberFormat('id-ID').format(totalAmount)}</strong></div>`;
            html += '</div></div>';

            $('#checkoutSummary').html(html);
        }

        // Calculate total price
        $('#jumlah_beli').on('input', function() {
            var qty = parseInt($(this).val()) || 1;
            var harga = parseInt($('#harga_satuan').val());
            var total = qty * harga;

            $('#total_harga').val(total);
            $('#total_harga_display').val('Rp ' + new Intl.NumberFormat('id-ID').format(total));
        });

        // Handle purchase form submission
        $('#checkoutForm').on('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);
            $('#btnCheckout').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            $.ajax({
                url: 'process_cart_payment.php', // File baru untuk process payment dari cart
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#checkoutModal').modal('hide');

                        // Trigger Midtrans Snap
                        snap.pay(response.snap_token, {
                            onSuccess: function(result) {
                                $.ajax({
                                    url: 'payment_success_cart.php', // File baru untuk handle success dari cart
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'success',
                                        transaction_result: JSON.stringify(result)
                                    },
                                    dataType: 'json',
                                    success: function(saveResponse) {
                                        sessionStorage.setItem('alert_message', 'Pembayaran berhasil! Terima kasih atas pembelian Anda.');
                                        sessionStorage.setItem('alert_type', 'success');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Berhasil');
                                        sessionStorage.setItem('alert_icon', 'fas fa-check-circle');
                                        location.reload();
                                    },
                                    error: function() {
                                        sessionStorage.setItem('alert_message', 'Pembayaran berhasil tetapi ada error saat menyimpan data. Silakan hubungi customer service.');
                                        sessionStorage.setItem('alert_type', 'warning');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Berhasil');
                                        sessionStorage.setItem('alert_icon', 'fas fa-exclamation-triangle');
                                        location.reload();
                                    }
                                });
                            },
                            onPending: function(result) {
                                $.ajax({
                                    url: 'payment_success_cart.php',
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'pending',
                                        transaction_result: JSON.stringify(result)
                                    },
                                    dataType: 'json',
                                    complete: function() {
                                        sessionStorage.setItem('alert_message', 'Pembayaran sedang diproses. Silakan selesaikan pembayaran Anda.');
                                        sessionStorage.setItem('alert_type', 'info');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Pending');
                                        sessionStorage.setItem('alert_icon', 'fas fa-clock');
                                        location.reload();
                                    }
                                });
                            },
                            onError: function(result) {
                                $.ajax({
                                    url: 'payment_success_cart.php',
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'error',
                                        transaction_result: JSON.stringify(result)
                                    },
                                    dataType: 'json',
                                    complete: function() {
                                        sessionStorage.setItem('alert_message', 'Pembayaran gagal! Silakan coba lagi.');
                                        sessionStorage.setItem('alert_type', 'danger');
                                        sessionStorage.setItem('alert_title', 'Pembayaran Gagal');
                                        sessionStorage.setItem('alert_icon', 'fas fa-times-circle');
                                        location.reload();
                                    }
                                });
                            },
                            onClose: function() {
                                $.ajax({
                                    url: 'payment_success_cart.php',
                                    method: 'POST',
                                    data: {
                                        order_id: response.order_id,
                                        transaction_status: 'cancel'
                                    },
                                    dataType: 'json',
                                    complete: function() {
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
                        if (response.redirect) {
                            location.reload();
                        } else {
                            showAlert(response.message, 'danger', 'Error', 'fas fa-times-circle');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showAlert('Terjadi kesalahan dalam memproses pembayaran. Silakan coba lagi.', 'danger', 'Error', 'fas fa-times-circle');
                },
                complete: function() {
                    $('#btnCheckout').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Bayar Sekarang');
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
            setTimeout(function() {
                $('.alert-container').fadeOut();
            }, 5000);
        }

        $(document).ready(function() {
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