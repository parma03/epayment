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
    } else if ($_SESSION['role'] === 'Pelayan') {
        header("Location: ../dashboard/pelayan/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pelanggan') {
        header("Location: ../dashboard/pelanggan/index.php");
        exit();
    }
}

// Set default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validasi tanggal
if (strtotime($start_date) > strtotime($end_date)) {
    $alert_message = 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir!';
    $alert_type = 'warning';
    $alert_title = 'Peringatan!';
    $alert_icon = 'fas fa-exclamation-triangle';

    // Reset ke default
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Query untuk mendapatkan data transaksi dengan JOIN ke tabel barang dan user
$sql = "SELECT t.*, b.nama_barang, b.harga_barang, b.photo_barang, u.email as email_user
        FROM tb_transaksi t
        LEFT JOIN tb_barang b ON t.id_barang = b.id_barang
        LEFT JOIN tb_user u ON t.id_user = u.id_user
        WHERE DATE(t.created_at) BETWEEN ? AND ?
        ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$transaksis = $stmt->fetchAll();

// Statistik
$total_transaksi = count($transaksis);
$total_pendapatan = array_sum(array_column($transaksis, 'total_harga'));
$total_barang_terjual = array_sum(array_column($transaksis, 'jumlah_beli'));

// Transaksi berdasarkan status
$transaksi_pending = array_filter($transaksis, function ($item) {
    return $item['status_pembayaran'] == 'pending';
});

$transaksi_paid = array_filter($transaksis, function ($item) {
    return $item['status_pembayaran'] == 'paid';
});

$transaksi_failed = array_filter($transaksis, function ($item) {
    return $item['status_pembayaran'] == 'failed';
});

$transaksi_cancelled = array_filter($transaksis, function ($item) {
    return $item['status_pembayaran'] == 'cancelled';
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Laporan Transaksi &mdash; Stisla</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="../../assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="../../assets/modules/datatables/datatables.min.css">
    <link rel="stylesheet" href="../../assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../../assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/css/customnew.css">

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .main-sidebar,
            .navbar-bg,
            .main-navbar,
            .section-header,
            .card-header {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .section-body {
                padding: 0 !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .table {
                font-size: 12px !important;
            }

            .print-header {
                display: block !important;
                text-align: center;
                padding: 20px 0;
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
            }

            .print-footer {
                display: block !important;
                margin-top: 30px;
                page-break-inside: avoid;
            }

            .btn,
            .modal {
                display: none !important;
            }
        }

        .print-header {
            display: none;
        }

        .print-footer {
            display: none;
        }

        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stats-card {
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <div id="app">
        <!-- Alert Container di pojok kanan atas -->
        <?php if (!empty($alert_message)): ?>
            <div class="alert-container no-print">
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
            <div class="navbar-bg no-print"></div>
            <?php include '_component/navbar.php'; ?>
            <div class="main-sidebar sidebar-style-2 no-print">
                <?php include '_component/sidebar.php'; ?>
            </div>

            <!-- Print Header (Hidden on screen, visible on print) -->
            <div class="print-header">
                <h2><strong>LAPORAN DATA TRANSAKSI</strong></h2>
                <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> -
                    <?php echo date('d/m/Y', strtotime($end_date)); ?>
                </p>
                <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <section class="section">
                    <div class="section-header no-print">
                        <h1><i class="fas fa-chart-line mr-3"></i>Laporan Data Transaksi</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item active"><a href="index.php"><i
                                        class="fas fa-home mr-1"></i>Dashboard</a></div>
                            <div class="breadcrumb-item"><i class="fas fa-chart-line mr-1"></i>Laporan Transaksi</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <!-- Filter Card -->
                        <div class="row no-print">
                            <div class="col-12">
                                <div class="card filter-card">
                                    <div class="card-header">
                                        <h4><i class="fas fa-filter mr-2"></i>Filter Laporan</h4>
                                    </div>
                                    <div class="card-body">
                                        <form method="GET" class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="text-white"><i
                                                            class="fas fa-calendar-alt mr-1"></i>Tanggal Mulai</label>
                                                    <input type="date" class="form-control" name="start_date"
                                                        value="<?php echo $start_date; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="text-white"><i
                                                            class="fas fa-calendar-alt mr-1"></i>Tanggal Akhir</label>
                                                    <input type="date" class="form-control" name="end_date"
                                                        value="<?php echo $end_date; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="text-white">&nbsp;</label>
                                                    <div class="d-flex">
                                                        <button type="submit" class="btn btn-light btn-lg mr-2">
                                                            <i class="fas fa-search mr-1"></i>Filter
                                                        </button>
                                                        <button type="button" class="btn btn-success btn-lg"
                                                            onclick="printReport()">
                                                            <i class="fas fa-print mr-1"></i>Cetak
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="card card-statistic-1 stats-card">
                                    <div class="card-icon bg-primary">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Total Transaksi</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo $total_transaksi; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="card card-statistic-1 stats-card">
                                    <div class="card-icon bg-success">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Total Pendapatan</h4>
                                        </div>
                                        <div class="card-body">
                                            Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="card card-statistic-1 stats-card">
                                    <div class="card-icon bg-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Transaksi Pending</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo count($transaksi_pending); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="card card-statistic-1 stats-card">
                                    <div class="card-icon bg-info">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Barang Terjual</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo number_format($total_barang_terjual); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header no-print">
                                        <h4><i class="fas fa-table mr-2"></i>Data Transaksi
                                            <small
                                                class="text-muted">(<?php echo date('d/m/Y', strtotime($start_date)); ?>
                                                - <?php echo date('d/m/Y', strtotime($end_date)); ?>)</small>
                                        </h4>
                                        <div class="card-header-action">
                                            <span class="badge badge-primary badge-lg">
                                                <i class="fas fa-database mr-1"></i><?php echo $total_transaksi; ?> Data
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($transaksis)): ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Tidak ada data transaksi</h5>
                                                <p class="text-muted">Tidak ada transaksi yang ditemukan dalam periode
                                                    tanggal
                                                    yang dipilih.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="table-1">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center"><i class="fas fa-hashtag"></i></th>
                                                            <th class="no-print"><i class="fas fa-image mr-1"></i>Produk
                                                            </th>
                                                            <th><i class="fas fa-shopping-cart mr-1"></i>Order ID</th>
                                                            <th><i class="fas fa-user mr-1"></i>Pelanggan</th>
                                                            <th><i class="fas fa-box mr-1"></i>Barang</th>
                                                            <th><i class="fas fa-sort-numeric-up mr-1"></i>Jumlah</th>
                                                            <th><i class="fas fa-money-bill-wave mr-1"></i>Total Harga</th>
                                                            <th><i class="fas fa-info-circle mr-1"></i>Status</th>
                                                            <th><i class="fas fa-calendar-plus mr-1"></i>Tanggal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($transaksis as $index => $transaksi): ?>
                                                            <tr>
                                                                <td class="text-center">
                                                                    <span
                                                                        class="badge badge-secondary"><?php echo $index + 1; ?></span>
                                                                </td>
                                                                <td class="no-print">
                                                                    <?php if ($transaksi['photo_barang']): ?>
                                                                        <img alt="product"
                                                                            src="../../assets/img/products/<?php echo $transaksi['photo_barang']; ?>"
                                                                            class="avatar-preview"
                                                                            title="<?php echo htmlspecialchars($transaksi['nama_barang']); ?>">
                                                                    <?php else: ?>
                                                                        <img alt="product"
                                                                            src="../../assets/img/products/default-product.png"
                                                                            class="avatar-preview"
                                                                            title="<?php echo htmlspecialchars($transaksi['nama_barang']); ?>">
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <strong><?php echo htmlspecialchars($transaksi['order_id']); ?></strong>
                                                                </td>
                                                                <td>
                                                                    <strong><?php echo htmlspecialchars($transaksi['nama_pemesan']); ?></strong>
                                                                    <br><small
                                                                        class="text-muted"><?php echo htmlspecialchars($transaksi['nohp_pemesan']); ?></small>
                                                                    <br><small
                                                                        class="text-muted"><?php echo htmlspecialchars($transaksi['email_user']); ?></small>
                                                                </td>
                                                                <td>
                                                                    <strong><?php echo htmlspecialchars($transaksi['nama_barang']); ?></strong>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-info">
                                                                        <i
                                                                            class="fas fa-cubes mr-1"></i><?php echo $transaksi['jumlah_beli']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-success font-weight-bold">
                                                                        Rp
                                                                        <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $status_class = '';
                                                                    $status_icon = '';
                                                                    switch ($transaksi['status_pembayaran']) {
                                                                        case 'pending':
                                                                            $status_class = 'warning';
                                                                            $status_icon = 'fas fa-clock';
                                                                            break;
                                                                        case 'paid':
                                                                            $status_class = 'success';
                                                                            $status_icon = 'fas fa-check-circle';
                                                                            break;
                                                                        case 'failed':
                                                                            $status_class = 'danger';
                                                                            $status_icon = 'fas fa-times-circle';
                                                                            break;
                                                                        case 'cancelled':
                                                                            $status_class = 'secondary';
                                                                            $status_icon = 'fas fa-ban';
                                                                            break;
                                                                    }
                                                                    ?>
                                                                    <span class="badge badge-<?php echo $status_class; ?>">
                                                                        <i class="<?php echo $status_icon; ?> mr-1"></i>
                                                                        <?php echo ucfirst($transaksi['status_pembayaran']); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-clock mr-1 text-muted"></i>
                                                                    <?php echo date('d/m/Y H:i', strtotime($transaksi['created_at'])); ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="bg-light font-weight-bold">
                                                            <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                                                            <td class="text-right">
                                                                <span class="badge badge-info">
                                                                    <?php echo $total_barang_terjual; ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-right">
                                                                <span class="text-success">
                                                                    <strong>Rp
                                                                        <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></strong>
                                                                </span>
                                                            </td>
                                                            <td colspan="2"></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <?php if (!empty($transaksis)): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="text-info"><i class="fas fa-chart-pie mr-2"></i>Status Transaksi</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Status</th>
                                                            <th>Jumlah</th>
                                                            <th>Persentase</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><span class="badge badge-success">Paid</span></td>
                                                            <td><?php echo count($transaksi_paid); ?></td>
                                                            <td><?php echo $total_transaksi > 0 ? round((count($transaksi_paid) / $total_transaksi) * 100, 1) : 0; ?>%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="badge badge-warning">Pending</span></td>
                                                            <td><?php echo count($transaksi_pending); ?></td>
                                                            <td><?php echo $total_transaksi > 0 ? round((count($transaksi_pending) / $total_transaksi) * 100, 1) : 0; ?>%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="badge badge-danger">Failed</span></td>
                                                            <td><?php echo count($transaksi_failed); ?></td>
                                                            <td><?php echo $total_transaksi > 0 ? round((count($transaksi_failed) / $total_transaksi) * 100, 1) : 0; ?>%
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="badge badge-secondary">Cancelled</span></td>
                                                            <td><?php echo count($transaksi_cancelled); ?></td>
                                                            <td><?php echo $total_transaksi > 0 ? round((count($transaksi_cancelled) / $total_transaksi) * 100, 1) : 0; ?>%
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="text-success"><i class="fas fa-chart-line mr-2"></i>Ringkasan Laporan
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-shopping-cart text-primary mr-2"></i>Total
                                                        Transaksi</span>
                                                    <span class="badge badge-primary"><?php echo $total_transaksi; ?></span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-money-bill-wave text-success mr-2"></i>Total
                                                        Pendapatan</span>
                                                    <span class="badge badge-success">Rp
                                                        <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-boxes text-info mr-2"></i>Total Barang
                                                        Terjual</span>
                                                    <span
                                                        class="badge badge-info"><?php echo number_format($total_barang_terjual); ?></span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-calculator text-warning mr-2"></i>Rata-rata per
                                                        Transaksi</span>
                                                    <span class="badge badge-warning">
                                                        Rp
                                                        <?php echo $total_transaksi > 0 ? number_format($total_pendapatan / $total_transaksi, 0, ',', '.') : 0; ?>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Print Footer (Hidden on screen, visible on print) -->
            <div class="print-footer">
                <div class="row">
                    <div class="col-6">
                        <p><strong>Ringkasan:</strong></p>
                        <ul>
                            <li>Total Transaksi: <?php echo $total_transaksi; ?></li>
                            <li>Total Pendapatan: Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></li>
                            <li>Barang Terjual: <?php echo number_format($total_barang_terjual); ?></li>
                            <li>Transaksi Berhasil: <?php echo count($transaksi_paid); ?></li>
                        </ul>
                    </div>
                    <div class="col-6 text-right">
                        <p>Mengetahui,</p>
                        <br><br>
                        <p>_____________________<br><strong>Manager</strong></p>
                    </div>
                </div>
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
    <script src="../../assets/modules/datatables/datatables.min.js"></script>
    <script src="../../assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/modules/datatables/Select-1.2.4/js/dataTables.select.min.js"></script>

    <!-- Template JS File -->
    <script src="../../assets/js/scripts.js"></script>
    <script src="../../assets/js/custom.js"></script>

    <script>
        // Initialize DataTable with enhanced features
        // Ganti bagian footerCallback di DataTables initialization dengan kode ini:

        $("#table-1").DataTable({
            "columnDefs": [
                { "orderable": false, "targets": [1] }, // Photo column
                { "className": "text-center", "targets": [0] },
                { "className": "text-right", "targets": [5, 6] } // Jumlah dan Total Harga columns
            ],
            "responsive": true,
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
            "order": [[8, "desc"]], // Sort by created_at descending (kolom ke-8)
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Data tidak ditemukan",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_ (_TOTAL_ total data)",
                "infoEmpty": "Tidak ada data yang tersedia",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "search": "🔍 Cari:",
                "searchPlaceholder": "Ketik untuk mencari...",
                "paginate": {
                    "first": "⏮️ Pertama",
                    "last": "⏭️ Terakhir",
                    "next": "▶️ Selanjutnya",
                    "previous": "◀️ Sebelumnya"
                },
                "processing": "⏳ Memproses...",
                "loadingRecords": "⏳ Memuat data..."
            },
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            "footerCallback": function (row, data, start, end, display) {
                var api = this.api();

                // Helper function to convert formatted number to integer
                var intVal = function (i) {
                    if (typeof i === 'string') {
                        // Remove HTML tags and non-numeric characters, keep only digits
                        var cleanStr = i.replace(/<[^>]*>/g, '').replace(/[^\d]/g, '');
                        return parseInt(cleanStr) || 0;
                    }
                    return typeof i === 'number' ? i : 0;
                };

                // Calculate totals for current page
                var totalJumlahPage = 0;
                var totalHargaPage = 0;

                // Calculate totals for all data (filtered)
                var totalJumlahAll = 0;
                var totalHargaAll = 0;

                // Current page calculation
                api.rows({ page: 'current' }).data().each(function (row) {
                    // Struktur kolom: #, Photo, Order ID, Pelanggan, Barang, Jumlah, Total Harga, Status, Tanggal
                    // Index:           0    1      2        3          4       5        6            7       8
                    totalJumlahPage += intVal(row[5]); // Kolom Jumlah
                    totalHargaPage += intVal(row[6]);  // Kolom Total Harga
                });

                // All data calculation (filtered)
                api.rows({ search: 'applied' }).data().each(function (row) {
                    totalJumlahAll += intVal(row[5]); // Kolom Jumlah
                    totalHargaAll += intVal(row[6]);  // Kolom Total Harga
                });

                // Update footer - pastikan tfoot ada di HTML
                var $footer = $(api.table().footer());
                if ($footer.length) {
                    // Update kolom Jumlah (kolom ke-5)
                    $(api.column(5).footer()).html(
                        '<span class="badge badge-info">' +
                        '<i class="fas fa-cubes mr-1"></i>' +
                        totalJumlahAll.toLocaleString('id-ID') +
                        '</span>' +
                        (totalJumlahPage !== totalJumlahAll ?
                            '<br><small class="text-muted">Halaman: ' + totalJumlahPage.toLocaleString('id-ID') + '</small>' :
                            '')
                    );

                    // Update kolom Total Harga (kolom ke-6)
                    $(api.column(6).footer()).html(
                        '<span class="text-success">' +
                        '<strong>Rp ' + totalHargaAll.toLocaleString('id-ID') + '</strong>' +
                        '</span>' +
                        (totalHargaPage !== totalHargaAll ?
                            '<br><small class="text-muted">Halaman: Rp ' + totalHargaPage.toLocaleString('id-ID') + '</small>' :
                            '')
                    );
                }
            }
        });


        // Ganti fungsi printReport() yang lama dengan yang ini
        function printReport() {
            // Simpan state asli
            const originalTitle = document.title;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            // Tampilkan loading
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Mempersiapkan Laporan...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            // Prepare untuk print
            setTimeout(function () {
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }

                // Ubah title untuk print
                document.title = 'Laporan Transaksi - ' + new Date().toLocaleDateString('id-ID');

                // Sembunyikan elemen yang tidak perlu dicetak dengan menambah class
                const hideElements = [
                    '.main-sidebar',
                    '.navbar-bg',
                    '.main-navbar',
                    '.section-header',
                    '.filter-card',
                    '.btn',
                    '.modal',
                    '.alert-container',
                    '.dataTables_wrapper .dataTables_length',
                    '.dataTables_wrapper .dataTables_filter',
                    '.dataTables_wrapper .dataTables_info',
                    '.dataTables_wrapper .dataTables_paginate',
                    'form',
                    '.card-header-action'
                ];

                hideElements.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => {
                        if (!el.classList.contains('no-print')) {
                            el.classList.add('no-print');
                        }
                    });
                });

                // Tambahkan class print-mode ke body
                document.body.classList.add('print-mode');

                // Scroll ke atas
                window.scrollTo(0, 0);

                // Tunggu sebentar untuk memastikan styling sudah applied
                setTimeout(() => {
                    try {
                        window.print();

                        // Cleanup setelah print
                        setTimeout(() => {
                            document.title = originalTitle;
                            document.body.classList.remove('print-mode');
                            window.scrollTo(0, scrollTop);
                        }, 1000);

                    } catch (error) {
                        console.error('Error saat mencetak:', error);

                        // Cleanup jika error
                        document.title = originalTitle;
                        document.body.classList.remove('print-mode');
                        window.scrollTo(0, scrollTop);

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Mencetak',
                                text: 'Terjadi kesalahan saat mencetak laporan.',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            alert('Terjadi kesalahan saat mencetak laporan.');
                        }
                    }
                }, 500);

            }, 1000);
        }

        // Tambahkan event listener untuk after print cleanup
        window.addEventListener('afterprint', function () {
            // Cleanup setelah print selesai
            document.body.classList.remove('print-mode');

            // Remove temporary no-print classes
            const tempNoPrintElements = document.querySelectorAll('.no-print:not([class*="no-print"])');
            tempNoPrintElements.forEach(el => {
                el.classList.remove('no-print');
            });
        });

        // Auto-submit form when date changes (optional)
        $('input[name="start_date"], input[name="end_date"]').on('change', function () {
            // Auto-submit after both dates are selected
            var startDate = $('input[name="start_date"]').val();
            var endDate = $('input[name="end_date"]').val();

            if (startDate && endDate) {
                // Validate dates
                if (new Date(startDate) > new Date(endDate)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan!',
                        text: 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir!',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Show loading and auto-submit
                Swal.fire({
                    title: 'Memfilter Data...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit form after short delay
                setTimeout(function () {
                    $('form').submit();
                }, 500);
            }
        });

        // Quick date range buttons
        function setDateRange(days) {
            var endDate = new Date();
            var startDate = new Date();
            startDate.setDate(startDate.getDate() - days);

            $('input[name="start_date"]').val(startDate.toISOString().split('T')[0]);
            $('input[name="end_date"]').val(endDate.toISOString().split('T')[0]);
        }

        // Add quick date buttons after DOM is ready
        $(document).ready(function () {
            // Add quick date range buttons
            var quickDateButtons = `
                <div class="btn-group mb-3" role="group" aria-label="Quick Date Range">
                    <button type="button" class="btn btn-outline-primary btn-sm text-success" onclick="setDateRange(7)">
                        <i class="fas fa-calendar-week mr-1"></i>7 Hari
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm text-success" onclick="setDateRange(30)">
                        <i class="fas fa-calendar-alt mr-1"></i>30 Hari
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm text-success" onclick="setDateRange(90)">
                        <i class="fas fa-calendar mr-1"></i>90 Hari
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm text-success" onclick="setDateRange(365)">
                        <i class="fas fa-calendar-plus mr-1"></i>1 Tahun
                    </button>
                </div>
            `;

            $('.filter-card .card-body form').prepend(quickDateButtons);
        });

        function exportToExcel() {
            var table = $('#table-1').DataTable();
            var data = table.rows().data().toArray();

            if (data.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Ada Data',
                    text: 'Tidak ada data untuk diekspor!',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Create CSV content untuk transaksi
            var csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "No,Order ID,Pelanggan,No HP,Barang,Jumlah,Total Harga,Status,Tanggal\n";

            data.forEach(function (row, index) {
                var cleanRow = [
                    index + 1,
                    row[2].replace(/<[^>]*>/g, '').replace(/"/g, '""'), // Order ID
                    row[3].replace(/<[^>]*>/g, '').replace(/"/g, '""'), // Pelanggan
                    row[4].replace(/<[^>]*>/g, '').replace(/"/g, '""'), // Barang
                    row[5].replace(/[^\d]/g, ''), // Jumlah
                    row[6].replace(/[^\d]/g, ''), // Total Harga
                    row[7].replace(/<[^>]*>/g, '').replace(/"/g, '""'), // Status
                    row[8].replace(/<[^>]*>/g, '').replace(/"/g, '""')  // Tanggal
                ];
                csvContent += '"' + cleanRow.join('","') + '"\n';
            });

            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "laporan_transaksi_" + new Date().toISOString().split('T')[0] + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data berhasil diekspor ke Excel!',
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Add export button
        $(document).ready(function () {
            // Add export button next to print button
            var exportButton = `
                <button type="button" class="btn btn-info btn-lg ml-2" onclick="exportToExcel()">
                    <i class="fas fa-file-excel mr-1"></i>Export Excel
                </button>
            `;

            $('.btn-success:contains("Cetak")').after(exportButton);
        });

        // Tooltip initialization
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Smooth scroll for statistics cards
        $(document).ready(function () {
            $('.stats-card').hover(
                function () {
                    $(this).addClass('shadow-lg');
                },
                function () {
                    $(this).removeClass('shadow-lg');
                }
            );
        });

        // Auto-refresh data every 5 minutes (optional)
        var autoRefresh = false; // Set to true to enable auto-refresh

        if (autoRefresh) {
            setInterval(function () {
                if (!document.hidden) { // Only refresh if page is visible
                    Swal.fire({
                        title: 'Memperbarui Data...',
                        text: 'Mengambil data terbaru',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    }).then(() => {
                        location.reload();
                    });
                }
            }, 300000); // 5 minutes
        }

        // Print preview function
        function printPreview() {
            // Create a new window for print preview
            var printWindow = window.open('', '_blank', 'width=800,height=600');

            // Get the HTML content to print
            var printContent = document.documentElement.outerHTML;

            // Write content to new window
            printWindow.document.write(printContent);
            printWindow.document.close();

            // Add print styles to the new window
            printWindow.onload = function () {
                // Add print-specific styles
                var style = printWindow.document.createElement('style');
                style.textContent = `
                    @media screen {
                        .no-print { display: none !important; }
                        .print-header { display: block !important; }
                        .print-footer { display: block !important; }
                    }
                `;
                printWindow.document.head.appendChild(style);

                // Show print dialog
                setTimeout(function () {
                    printWindow.print();
                    printWindow.close();
                }, 1000);
            };
        }

        // Handle print media query changes
        if (window.matchMedia) {
            var mediaQueryList = window.matchMedia('print');
            mediaQueryList.addListener(function (mql) {
                if (mql.matches) {
                    // Before print
                    console.log('Printing...');
                } else {
                    // After print
                    console.log('Print dialog closed');
                }
            });
        }

        // SweetAlert2 for alerts (make sure SweetAlert2 is included)
        $(document).ready(function () {
            // Check if SweetAlert2 is available
            if (typeof Swal === 'undefined') {
                console.warn('SweetAlert2 is not loaded. Using regular alerts.');
                window.Swal = {
                    fire: function (options) {
                        if (typeof options === 'string') {
                            alert(options);
                        } else if (options.text) {
                            alert(options.text);
                        }
                    },
                    showLoading: function () { },
                    close: function () { }
                };
            }
        });

        // Keyboard shortcuts
        $(document).keydown(function (e) {
            // Ctrl+P for print
            if (e.ctrlKey && e.keyCode === 80) {
                e.preventDefault();
                printReport();
            }

            // Ctrl+E for export
            if (e.ctrlKey && e.keyCode === 69) {
                e.preventDefault();
                exportToExcel();
            }

            // F5 for refresh (allow default behavior)
            if (e.keyCode === 116) {
                // Allow default F5 behavior
                return true;
            }
        });

        // Add keyboard shortcut hints
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();

            // Add tooltips for buttons
            $('.btn:contains("Cetak")').attr('title', 'Cetak Laporan (Ctrl+P)').attr('data-toggle', 'tooltip');
            $('.btn:contains("Export")').attr('title', 'Export ke Excel (Ctrl+E)').attr('data-toggle', 'tooltip');
        });
    </script>

    <!-- Add SweetAlert2 CDN if not already included -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Additional CSS for better print styling -->
    <style>
        /* Additional print styles */
        /* Ganti CSS di dalam tag <style> dengan kode ini */
        @media print {

            /* Reset dan hide semua elemen yang tidak perlu */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Hide semua elemen yang tidak perlu dicetak */
            .no-print,
            .main-sidebar,
            .navbar-bg,
            .main-navbar,
            .section-header,
            .card-header,
            .btn,
            .modal,
            .alert,
            .alert-container,
            .breadcrumb,
            .section-header-breadcrumb,
            .card-header-action,
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate,
            .dataTables_wrapper .dataTables_processing,
            .filter-card,
            .quick-date-buttons,
            form {
                display: none !important;
            }

            /* Reset layout untuk print */
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                font-size: 11px !important;
                line-height: 1.3 !important;
            }

            .main-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .section-body {
                padding: 0 !important;
                margin: 0 !important;
            }

            .container-fluid {
                padding: 0 !important;
            }

            .row {
                margin: 0 !important;
            }

            .col-12 {
                padding: 0 !important;
            }

            /* Card styling untuk print */
            .card {
                border: none !important;
                box-shadow: none !important;
                margin-bottom: 0 !important;
                background: white !important;
            }

            .card-body {
                padding: 0 !important;
            }

            /* Print header styling */
            .print-header {
                display: block !important;
                text-align: center;
                padding: 15px 0 !important;
                border-bottom: 2px solid #000;
                margin-bottom: 15px !important;
                page-break-inside: avoid;
            }

            .print-header h2 {
                font-size: 18px !important;
                margin: 0 0 10px 0 !important;
                font-weight: bold !important;
            }

            .print-header p {
                font-size: 12px !important;
                margin: 5px 0 !important;
            }

            /* Statistics cards untuk print */
            .stats-card {
                display: block !important;
                border: 1px solid #ddd !important;
                margin-bottom: 10px !important;
                padding: 8px !important;
                page-break-inside: avoid;
            }

            .stats-card .card-icon {
                display: none !important;
            }

            .stats-card .card-wrap {
                padding: 0 !important;
            }

            .stats-card .card-header h4 {
                font-size: 11px !important;
                margin: 0 0 5px 0 !important;
                font-weight: bold !important;
            }

            .stats-card .card-body {
                font-size: 14px !important;
                font-weight: bold !important;
                margin: 0 !important;
            }

            /* Table styling untuk print */
            .table-responsive {
                overflow: visible !important;
            }

            .table {
                font-size: 9px !important;
                border-collapse: collapse !important;
                width: 100% !important;
                margin: 0 !important;
                page-break-inside: auto;
            }

            .table th,
            .table td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                text-align: left !important;
                vertical-align: top !important;
                line-height: 1.2 !important;
            }

            .table th {
                background-color: #f8f9fa !important;
                font-weight: bold !important;
                font-size: 8px !important;
                text-align: center !important;
            }

            .table thead {
                display: table-header-group !important;
            }

            .table tfoot {
                display: table-footer-group !important;
                background-color: #f8f9fa !important;
                font-weight: bold !important;
            }

            .table tbody tr {
                page-break-inside: avoid !important;
            }

            /* Badge styling untuk print */
            .badge {
                border: 1px solid #000 !important;
                background-color: #fff !important;
                color: #000 !important;
                padding: 2px 4px !important;
                font-size: 7px !important;
                font-weight: normal !important;
            }

            /* Text colors untuk print */
            .text-success,
            .text-primary,
            .text-warning,
            .text-danger,
            .text-info {
                color: #000 !important;
            }

            /* Hide images di kolom foto */
            .avatar-preview {
                display: none !important;
            }

            /* Summary cards untuk print */
            .list-group-item {
                border: 1px solid #ddd !important;
                padding: 5px !important;
                font-size: 9px !important;
            }

            /* Print footer styling */
            .print-footer {
                display: block !important;
                margin-top: 20px !important;
                padding-top: 15px !important;
                border-top: 2px solid #000 !important;
                page-break-inside: avoid;
                font-size: 10px !important;
            }

            .print-footer hr {
                display: none !important;
            }

            .print-footer ul {
                list-style: none !important;
                padding-left: 0 !important;
                margin: 0 !important;
            }

            .print-footer li {
                margin-bottom: 3px !important;
            }

            /* Khusus untuk status transaksi dan ringkasan */
            .row:has(.col-md-6) .card {
                border: 1px solid #ddd !important;
                margin-bottom: 10px !important;
            }

            .row:has(.col-md-6) .card-header {
                display: block !important;
                background-color: #f8f9fa !important;
                padding: 8px !important;
                border-bottom: 1px solid #ddd !important;
            }

            .row:has(.col-md-6) .card-header h4 {
                font-size: 11px !important;
                margin: 0 !important;
                font-weight: bold !important;
            }

            .row:has(.col-md-6) .card-body {
                padding: 8px !important;
            }

            /* Tabel status transaksi */
            .table-sm th,
            .table-sm td {
                padding: 3px !important;
                font-size: 8px !important;
            }

            /* Responsif untuk kertas A4 */
            @page {
                size: A4;
                margin: 1cm;
            }

            /* Pastikan tidak ada overflow */
            body * {
                max-width: 100% !important;
                box-sizing: border-box !important;
            }

            /* Force break untuk section besar */
            .section {
                page-break-inside: avoid;
            }
        }

        /* Screen styles for better UX */
        @media screen {
            .alert-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                width: 400px;
            }

            .fade-in {
                animation: fadeIn 0.5s;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            .avatar-preview {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 5px;
                cursor: pointer;
                transition: transform 0.2s;
            }

            .avatar-preview:hover {
                transform: scale(1.1);
            }

            /* Loading animation for statistics cards */
            .stats-card .card-body {
                font-size: 2rem;
                font-weight: bold;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .alert-container {
                    width: 90%;
                    right: 5%;
                }

                .table-responsive {
                    font-size: 0.875rem;
                }

                .stats-card .card-body {
                    font-size: 1.5rem;
                }
            }
        }
    </style>

    <!-- Add this before closing body tag -->
    <script>
        // Final initialization
        $(document).ready(function () {
            // Add loading animation to statistics cards
            $('.stats-card .card-body').each(function () {
                var $this = $(this);
                var targetValue = parseInt($this.text().replace(/[^\d]/g, ''));
                var currentValue = 0;
                var increment = Math.ceil(targetValue / 50);

                var timer = setInterval(function () {
                    currentValue += increment;
                    if (currentValue >= targetValue) {
                        currentValue = targetValue;
                        clearInterval(timer);
                    }

                    if ($this.text().includes('Rp')) {
                        $this.text('Rp ' + currentValue.toLocaleString('id-ID'));
                    } else {
                        $this.text(currentValue.toLocaleString('id-ID'));
                    }
                }, 30);
            });

            // Add success message if data loaded
            <?php if (!empty($barangs)): ?>
                setTimeout(function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Data Berhasil Dimuat',
                            text: 'Menampilkan <?php echo $total_barang; ?> barang',
                            timer: 2000,
                            showConfirmButton: false,
                            position: 'top-end',
                            toast: true
                        });
                    }
                }, 1000);
            <?php endif; ?>
        });
    </script>
</body>

</html>