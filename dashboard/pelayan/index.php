<?php
session_start();
include '../../db/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Driver') {
        header("Location: ../dashboard/driver/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Gudang') {
        header("Location: ../dashboard/gudang/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Administrator') {
        header("Location: ../dashboard/admin/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pelanggan') {
        header("Location: ../dashboard/pelanggan/index.php");
        exit();
    }
}

// Ambil alert dari session dan hapus setelah digunakan
$alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : '';
$alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : '';
$alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : '';
$alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : '';

// Hapus alert dari session setelah digunakan
unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);

// Query untuk statistik dashboard
try {
    // Total Barang
    $query_barang = "SELECT COUNT(*) as total_barang, SUM(stok_barang) as total_stok FROM tb_barang";
    $stmt_barang = $pdo->prepare($query_barang);
    $stmt_barang->execute();
    $data_barang = $stmt_barang->fetch();

    // Total Users berdasarkan role
    $query_users = "SELECT role, COUNT(*) as total FROM tb_user GROUP BY role";
    $stmt_users = $pdo->prepare($query_users);
    $stmt_users->execute();
    $users_by_role = [];
    while ($row = $stmt_users->fetch()) {
        $users_by_role[$row['role']] = $row['total'];
    }

    // Statistik Transaksi
    $query_transaksi = "SELECT 
        COUNT(*) as total_transaksi,
        SUM(CASE WHEN status_pembayaran = 'paid' THEN total_harga ELSE 0 END) as total_revenue,
        COUNT(CASE WHEN status_pembayaran = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status_pembayaran = 'paid' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status_pembayaran = 'failed' THEN 1 END) as failed_orders,
        COUNT(CASE WHEN status_pembayaran = 'cancelled' THEN 1 END) as cancelled_orders
        FROM tb_transaksi";
    $stmt_transaksi = $pdo->prepare($query_transaksi);
    $stmt_transaksi->execute();
    $data_transaksi = $stmt_transaksi->fetch();

    // Status Pengiriman
    $query_pengiriman = "SELECT status, COUNT(*) as total FROM tb_pengiriman GROUP BY status";
    $stmt_pengiriman = $pdo->prepare($query_pengiriman);
    $stmt_pengiriman->execute();
    $pengiriman_status = [];
    while ($row = $stmt_pengiriman->fetch()) {
        $pengiriman_status[$row['status']] = $row['total'];
    }

    // Barang dengan stok rendah (< 10)
    $query_stok_rendah = "SELECT nama_barang, stok_barang FROM tb_barang WHERE stok_barang < 10 ORDER BY stok_barang ASC LIMIT 5";
    $stmt_stok_rendah = $pdo->prepare($query_stok_rendah);
    $stmt_stok_rendah->execute();
    $result_stok_rendah = $stmt_stok_rendah->fetchAll();

    // Transaksi terbaru
    $query_transaksi_terbaru = "SELECT t.*, b.nama_barang, u.email as customer_email 
        FROM tb_transaksi t 
        JOIN tb_barang b ON t.id_barang = b.id_barang 
        JOIN tb_user u ON t.id_user = u.id_user 
        ORDER BY t.created_at DESC LIMIT 5";
    $stmt_transaksi_terbaru = $pdo->prepare($query_transaksi_terbaru);
    $stmt_transaksi_terbaru->execute();
    $result_transaksi_terbaru = $stmt_transaksi_terbaru->fetchAll();

    // Data untuk chart - Transaksi per bulan (6 bulan terakhir)
    $query_chart = "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_orders,
        SUM(CASE WHEN status_pembayaran = 'paid' THEN total_harga ELSE 0 END) as revenue
        FROM tb_transaksi 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month";
    $stmt_chart = $pdo->prepare($query_chart);
    $stmt_chart->execute();
    $chart_data = $stmt_chart->fetchAll();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Admin Dashboard &mdash; E-Payment System</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="../../assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="../../assets/modules/jqvmap/dist/jqvmap.min.css">
    <link rel="stylesheet" href="../../assets/modules/summernote/summernote-bs4.css">
    <link rel="stylesheet" href="../../assets/modules/owlcarousel2/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="../../assets/modules/owlcarousel2/dist/assets/owl.theme.default.min.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/components.css">

    <style>
        .stats-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

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
                transform: translateX(100%);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .gradient-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .gradient-card-2 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .gradient-card-3 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .gradient-card-4 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .low-stock-item {
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
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

        <div class="main-wrapper main-wrapper-1">
            <div class="navbar-bg"></div>
            <?php include '_component/navbar.php'; ?>
            <div class="main-sidebar sidebar-style-2">
                <?php include '_component/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Dashboard Admin</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                            <div class="breadcrumb-item">Admin Panel</div>
                        </div>
                    </div>

                    <!-- Statistics Cards Row 1 -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1 stats-card">
                                <div class="card-icon gradient-card">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Total Barang</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php echo number_format($data_barang['total_barang'] ?? 0); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1 stats-card">
                                <div class="card-icon gradient-card-2">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Total Stok</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php echo number_format($data_barang['total_stok'] ?? 0); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1 stats-card">
                                <div class="card-icon gradient-card-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Total Transaksi</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php echo number_format($data_transaksi['total_transaksi'] ?? 0); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1 stats-card">
                                <div class="card-icon gradient-card-4">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Total Revenue</h4>
                                    </div>
                                    <div class="card-body">
                                        Rp
                                        <?php echo number_format($data_transaksi['total_revenue'] ?? 0, 0, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards Row 2 - Order Status -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-2 stats-card">
                                <div class="card-stats">
                                    <div class="card-stats-title">Status Pesanan</div>
                                    <div class="card-stats-items">
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $data_transaksi['pending_orders'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Pending</div>
                                        </div>
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $data_transaksi['completed_orders'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Completed</div>
                                        </div>
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $data_transaksi['failed_orders'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Failed</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-icon shadow-primary bg-primary">
                                    <i class="fas fa-archive"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Order Management</h4>
                                    </div>
                                    <div class="card-body">
                                        <a href="order.php" class="btn btn-primary btn-sm">Kelola Order</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-2 stats-card">
                                <div class="card-stats">
                                    <div class="card-stats-title">User Management</div>
                                    <div class="card-stats-items">
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $users_by_role['Pelanggan'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Customers</div>
                                        </div>
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $users_by_role['Driver'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Drivers</div>
                                        </div>
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $users_by_role['Pelayan'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Pelayan</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-icon shadow-success bg-success">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Total Users</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php echo array_sum($users_by_role); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-2 stats-card">
                                <div class="card-stats">
                                    <div class="card-stats-title">Status Pengiriman</div>
                                    <div class="card-stats-items">
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $pengiriman_status['disiapkan'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Preparing</div>
                                        </div>
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $pengiriman_status['dikirim'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Shipping</div>
                                        </div>
                                        <div class="card-stats-item">
                                            <div class="card-stats-item-count">
                                                <?php echo $pengiriman_status['selesai'] ?? 0; ?>
                                            </div>
                                            <div class="card-stats-item-label">Delivered</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-icon shadow-warning bg-warning">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Shipping Status</h4>
                                    </div>
                                    <div class="card-body">
                                        <a href="pengiriman.php" class="btn btn-warning btn-sm">Kelola Pengiriman</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1 stats-card">
                                <div class="card-icon bg-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Stok Rendah</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        $low_stock_count = count($result_stok_rendah);
                                        echo $low_stock_count;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Tables Row -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Revenue & Orders Chart</h4>
                                    <div class="card-header-action">
                                        <div class="btn-group">
                                            <a href="laporan-transaksi.php" class="btn btn-primary">View Report</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart1" height="158"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Stok Barang Rendah</h4>
                                    <div class="card-header-action">
                                        <a href="barang.php" class="btn btn-primary btn-sm">Kelola Barang</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (count($result_stok_rendah) > 0): ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($result_stok_rendah as $barang): ?>
                                                <div
                                                    class="list-group-item low-stock-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($barang['nama_barang']); ?>
                                                        </h6>
                                                        <small class="text-muted">Stok tersisa</small>
                                                    </div>
                                                    <span
                                                        class="badge badge-danger badge-pill"><?php echo $barang['stok_barang']; ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <p class="text-muted">Semua barang memiliki stok yang cukup</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Transaksi Terbaru</h4>
                                    <div class="card-header-action">
                                        <a href="order.php" class="btn btn-primary">Lihat Semua <i
                                                class="fas fa-chevron-right"></i></a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Customer</th>
                                                    <th>Barang</th>
                                                    <th>Jumlah</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Tanggal</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($result_transaksi_terbaru) > 0): ?>
                                                    <?php foreach ($result_transaksi_terbaru as $transaksi): ?>
                                                        <tr>
                                                            <td><a href="#"
                                                                    class="font-weight-600"><?php echo htmlspecialchars($transaksi['order_id']); ?></a>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($transaksi['nama_pemesan']); ?></td>
                                                            <td><?php echo htmlspecialchars($transaksi['nama_barang']); ?></td>
                                                            <td><?php echo $transaksi['jumlah_beli']; ?></td>
                                                            <td>Rp
                                                                <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $status_class = '';
                                                                $status_text = '';
                                                                switch ($transaksi['status_pembayaran']) {
                                                                    case 'paid':
                                                                        $status_class = 'badge-success';
                                                                        $status_text = 'Paid';
                                                                        break;
                                                                    case 'pending':
                                                                        $status_class = 'badge-warning';
                                                                        $status_text = 'Pending';
                                                                        break;
                                                                    case 'failed':
                                                                        $status_class = 'badge-danger';
                                                                        $status_text = 'Failed';
                                                                        break;
                                                                    case 'cancelled':
                                                                        $status_class = 'badge-secondary';
                                                                        $status_text = 'Cancelled';
                                                                        break;
                                                                }
                                                                ?>
                                                                <div class="badge <?php echo $status_class; ?>">
                                                                    <?php echo $status_text; ?>
                                                                </div>
                                                            </td>
                                                            <td><?php echo date('d M Y', strtotime($transaksi['created_at'])); ?>
                                                            </td>
                                                            <td>
                                                                <a href="order-detail.php?id=<?php echo $transaksi['id_transaksi']; ?>"
                                                                    class="btn btn-primary btn-sm">Detail</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center">Belum ada transaksi</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                                            <a href="barang.php" class="btn btn-outline-primary btn-block">
                                                <i class="fas fa-plus"></i> Tambah Barang
                                            </a>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                                            <a href="pelanggan.php" class="btn btn-outline-success btn-block">
                                                <i class="fas fa-user-plus"></i> Tambah Customer
                                            </a>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                                            <a href="order.php" class="btn btn-outline-info btn-block">
                                                <i class="fas fa-eye"></i> Lihat Orders
                                            </a>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                                            <a href="pengiriman.php" class="btn btn-outline-warning btn-block">
                                                <i class="fas fa-truck"></i> Kelola Pengiriman
                                            </a>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                                            <a href="laporan-transaksi.php" class="btn btn-outline-secondary btn-block">
                                                <i class="fas fa-chart-bar"></i> Laporan
                                            </a>
                                        </div>
                                        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                                            <a href="admin.php" class="btn btn-outline-dark btn-block">
                                                <i class="fas fa-cog"></i> Settings
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php include '_component/footer.php'; ?>
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
    <script src="../../assets/modules/chart.min.js"></script>
    <script src="../../assets/modules/owlcarousel2/dist/owl.carousel.min.js"></script>
    <script src="../../assets/modules/summernote/summernote-bs4.js"></script>

    <!-- Template JS File -->
    <script src="../../assets/js/scripts.js"></script>
    <script src="../../assets/js/custom.js"></script>

    <!-- Custom Chart Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Revenue Chart
            var canvas = document.getElementById("revenueChart1");

            // Cek apakah canvas element ada
            if (!canvas) {
                console.error('Canvas element with ID "revenueChart1" not found');
                return;
            }

            var ctx = canvas.getContext('2d');

            // Data dari PHP - pastikan data tidak null
            var chartData = <?php echo json_encode($chart_data ?? []); ?>;

            console.log('Chart Data:', chartData); // Debug: cek data

            var labels = [];
            var revenueData = [];
            var orderData = [];

            // Cek apakah chartData ada dan tidak kosong
            if (chartData && chartData.length > 0) {
                chartData.forEach(function (item) {
                    var date = new Date(item.month + '-01');
                    labels.push(date.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }));
                    revenueData.push(parseInt(item.revenue) || 0);
                    orderData.push(parseInt(item.total_orders) || 0);
                });
            } else {
                // Data dummy jika tidak ada data
                labels = ['Jan 2024', 'Feb 2024', 'Mar 2024', 'Apr 2024', 'May 2024', 'Jun 2024'];
                revenueData = [0, 0, 0, 0, 0, 0];
                orderData = [0, 0, 0, 0, 0, 0];

                console.warn('No chart data available, using dummy data');
            }

            try {
                var myChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue (Rp)',
                            data: revenueData,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }, {
                            label: 'Total Orders',
                            data: orderData,
                            borderColor: '#f093fb',
                            backgroundColor: 'rgba(240, 147, 251, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#f093fb',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                            // Hapus yAxisID: 'y1' - ini yang menyebabkan error
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#667eea',
                                borderWidth: 1,
                                cornerRadius: 8,
                                displayColors: true,
                                callbacks: {
                                    label: function (context) {
                                        if (context.datasetIndex === 0) {
                                            return 'Revenue: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                        } else {
                                            return 'Orders: ' + context.parsed.y + ' pesanan';
                                        }
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Periode'
                                },
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Revenue (Rp) / Orders'
                                },
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    callback: function (value) {
                                        return value.toLocaleString('id-ID');
                                    }
                                }
                            }
                            // Hapus y1 axis - ini yang menyebabkan error
                        }
                    }
                });

                console.log('Chart created successfully');

            } catch (error) {
                console.error('Error creating chart:', error);

                // Tampilkan pesan error di canvas
                ctx.fillStyle = '#666';
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('Error loading chart', canvas.width / 2, canvas.height / 2);
            }
        });
        // Auto dismiss alerts after 5 seconds
        setTimeout(function () {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Add loading animation to buttons
        $(document).on('click', '.btn', function () {
            var btn = $(this);
            if (!btn.hasClass('no-loading')) {
                btn.prop('disabled', true);
                var originalText = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');

                setTimeout(function () {
                    btn.prop('disabled', false);
                    btn.html(originalText);
                }, 1000);
            }
        });

        // Animate counters on page load
        function animateCounters() {
            $('.card-body').each(function () {
                var $this = $(this);
                var countTo = $this.text().replace(/[^0-9]/g, '');
                if (countTo && !isNaN(countTo)) {
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 2000,
                        easing: 'linear',
                        step: function () {
                            $this.text(Math.floor(this.countNum).toLocaleString('id-ID'));
                        },
                        complete: function () {
                            $this.text(parseInt(countTo).toLocaleString('id-ID'));
                        }
                    });
                }
            });
        }

        // Initialize animations when page loads
        $(document).ready(function () {
            // Add entrance animations
            $('.stats-card').each(function (index) {
                $(this).css({
                    'opacity': '0',
                    'transform': 'translateY(30px)'
                });

                setTimeout(() => {
                    $(this).animate({
                        'opacity': 1
                    }, 500).css('transform', 'translateY(0)');
                }, index * 100);
            });

            // Add hover effects for quick action buttons
            $('.btn-outline-primary, .btn-outline-success, .btn-outline-info, .btn-outline-warning, .btn-outline-secondary, .btn-outline-dark').hover(
                function () {
                    $(this).addClass('shadow-lg');
                },
                function () {
                    $(this).removeClass('shadow-lg');
                }
            );

            // Add pulse animation for low stock items
            $('.low-stock-item').addClass('pulse-animation');
        });

        // Real-time clock
        function updateClock() {
            var now = new Date();
            var timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            var dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            // Update clock if element exists
            if ($('#current-time').length) {
                $('#current-time').text(timeString);
            }
            if ($('#current-date').length) {
                $('#current-date').text(dateString);
            }
        }

        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial call

        // Add smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function (event) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });

        // Add refresh functionality
        function refreshDashboard() {
            location.reload();
        }

        // Auto refresh every 5 minutes
        setInterval(refreshDashboard, 300000);
    </script>

    <!-- Additional Custom Styles for Enhanced UI -->
    <style>
        /* Enhanced card animations */
        .stats-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        /* Gradient backgrounds for icons */
        .gradient-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .gradient-card-2 {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 10px 20px rgba(240, 147, 251, 0.3);
        }

        .gradient-card-3 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 10px 20px rgba(79, 172, 254, 0.3);
        }

        .gradient-card-4 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            box-shadow: 0 10px 20px rgba(67, 233, 123, 0.3);
        }

        /* Enhanced table styling */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9ff;
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        /* Enhanced badge styles */
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);
        }

        .badge-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
        }

        .badge-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        /* Enhanced low stock styling */
        .low-stock-item {
            border-left: 4px solid #ff6b6b;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe0e0 100%);
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .low-stock-item:hover {
            background: linear-gradient(135deg, #ffe0e0 0%, #ffcccb 100%);
            transform: translateX(5px);
        }

        /* Pulse animation for critical items */
        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(255, 107, 107, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 107, 107, 0);
            }
        }

        /* Enhanced quick action buttons */
        .btn-outline-primary:hover,
        .btn-outline-success:hover,
        .btn-outline-info:hover,
        .btn-outline-warning:hover,
        .btn-outline-secondary:hover,
        .btn-outline-dark:hover {
            transform: translateY(-3px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Card header enhancements */
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        .card-header h4 {
            color: #495057;
            margin: 0;
            font-size: 1.1rem;
        }

        /* Responsive enhancements */
        @media (max-width: 768px) {
            .stats-card:hover {
                transform: translateY(-4px) scale(1.01);
            }

            .card-stats-item {
                text-align: center;
                margin-bottom: 10px;
            }
        }

        /* Loading animation */
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Alert enhancements */
        .alert-container .alert {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        /* Chart container styling */
        #revenueChart1 {
            border-radius: 10px;
        }
    </style>

</body>

</html>