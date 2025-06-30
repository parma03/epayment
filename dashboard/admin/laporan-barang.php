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

// Query untuk mendapatkan data barang berdasarkan range tanggal
$sql = "SELECT * FROM tb_barang WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$barangs = $stmt->fetchAll();

// Statistik
$total_barang = count($barangs);
$total_stok = array_sum(array_column($barangs, 'stok_barang'));
$total_nilai = array_sum(array_map(function ($item) {
    return $item['harga_barang'] * $item['stok_barang'];
}, $barangs));

// Barang dengan stok rendah (<=5)
$barang_stok_rendah = array_filter($barangs, function ($item) {
    return $item['stok_barang'] <= 5;
});

// Barang kosong
$barang_kosong = array_filter($barangs, function ($item) {
    return $item['stok_barang'] == 0;
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Laporan Barang &mdash; Stisla</title>

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
                <h2><strong>LAPORAN DATA BARANG</strong></h2>
                <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> -
                    <?php echo date('d/m/Y', strtotime($end_date)); ?>
                </p>
                <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <section class="section">
                    <div class="section-header no-print">
                        <h1><i class="fas fa-chart-bar mr-3"></i>Laporan Barang</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item active"><a href="index.php"><i
                                        class="fas fa-home mr-1"></i>Dashboard</a></div>
                            <div class="breadcrumb-item"><i class="fas fa-chart-bar mr-1"></i>Laporan Barang</div>
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
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Total Barang</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo $total_barang; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="card card-statistic-1 stats-card">
                                    <div class="card-icon bg-success">
                                        <i class="fas fa-cubes"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Total Stok</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo number_format($total_stok); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="card card-statistic-1 stats-card">
                                    <div class="card-icon bg-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Stok Rendah</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo count($barang_stok_rendah); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <div class="card card-statistic-1 stats-card">
                                    <div class="card-icon bg-info">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="card-wrap">
                                        <div class="card-header">
                                            <h4>Total Nilai</h4>
                                        </div>
                                        <div class="card-body">
                                            Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?>
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
                                        <h4><i class="fas fa-table mr-2"></i>Data Barang
                                            <small
                                                class="text-muted">(<?php echo date('d/m/Y', strtotime($start_date)); ?>
                                                - <?php echo date('d/m/Y', strtotime($end_date)); ?>)</small>
                                        </h4>
                                        <div class="card-header-action">
                                            <span class="badge badge-primary badge-lg">
                                                <i class="fas fa-database mr-1"></i><?php echo $total_barang; ?> Data
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($barangs)): ?>
                                            <div class="text-center py-5">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Tidak ada data barang</h5>
                                                <p class="text-muted">Tidak ada barang yang terdaftar dalam periode tanggal
                                                    yang dipilih.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="table-1">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center"><i class="fas fa-hashtag"></i></th>
                                                            <th class="no-print"><i class="fas fa-image mr-1"></i>Photo</th>
                                                            <th><i class="fas fa-box mr-1"></i>Nama Barang</th>
                                                            <th><i class="fas fa-align-left mr-1"></i>Deskripsi</th>
                                                            <th><i class="fas fa-money-bill-wave mr-1"></i>Harga</th>
                                                            <th><i class="fas fa-cubes mr-1"></i>Stok</th>
                                                            <th><i class="fas fa-calculator mr-1"></i>Total Nilai</th>
                                                            <th><i class="fas fa-calendar-plus mr-1"></i>Tanggal Dibuat</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($barangs as $index => $barang): ?>
                                                            <tr
                                                                class="<?php echo $barang['stok_barang'] <= 5 ? ($barang['stok_barang'] == 0 ? 'table-danger' : 'table-warning') : ''; ?>">
                                                                <td class="text-center">
                                                                    <span
                                                                        class="badge badge-secondary"><?php echo $index + 1; ?></span>
                                                                </td>
                                                                <td class="no-print">
                                                                    <?php if ($barang['photo_barang']): ?>
                                                                        <img alt="product"
                                                                            src="../../assets/img/products/<?php echo $barang['photo_barang']; ?>"
                                                                            class="avatar-preview"
                                                                            title="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                                                                    <?php else: ?>
                                                                        <img alt="product"
                                                                            src="../../assets/img/products/default-product.png"
                                                                            class="avatar-preview"
                                                                            title="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <strong><?php echo htmlspecialchars($barang['nama_barang']); ?></strong>
                                                                    <?php if ($barang['stok_barang'] == 0): ?>
                                                                        <br><span class="badge badge-danger badge-sm">Stok
                                                                            Habis</span>
                                                                    <?php elseif ($barang['stok_barang'] <= 5): ?>
                                                                        <br><span class="badge badge-warning badge-sm">Stok
                                                                            Rendah</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">
                                                                        <?php echo strlen($barang['deskripsi_barang']) > 50 ?
                                                                            htmlspecialchars(substr($barang['deskripsi_barang'], 0, 50)) . '...' :
                                                                            htmlspecialchars($barang['deskripsi_barang']); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-success font-weight-bold">
                                                                        Rp
                                                                        <?php echo number_format($barang['harga_barang'], 0, ',', '.'); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span
                                                                        class="badge badge-<?php echo $barang['stok_barang'] > 10 ? 'success' : ($barang['stok_barang'] > 0 ? 'warning' : 'danger'); ?>">
                                                                        <i
                                                                            class="fas fa-cubes mr-1"></i><?php echo $barang['stok_barang']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-primary font-weight-bold">
                                                                        Rp
                                                                        <?php echo number_format($barang['harga_barang'] * $barang['stok_barang'], 0, ',', '.'); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-clock mr-1 text-muted"></i>
                                                                    <?php echo $barang['created_at'] ? date('d/m/Y H:i', strtotime($barang['created_at'])) : '-'; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr class="bg-light font-weight-bold">
                                                            <td colspan="4" class="text-right">
                                                                <strong>TOTAL:</strong>
                                                            </td>
                                                            <td>
                                                                <span class="text-success">
                                                                    Rp
                                                                    <?php echo number_format(array_sum(array_column($barangs, 'harga_barang')), 0, ',', '.'); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-info">
                                                                    <i
                                                                        class="fas fa-cubes mr-1"></i><?php echo $total_stok; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="text-primary">
                                                                    <strong>Rp
                                                                        <?php echo number_format($total_nilai, 0, ',', '.'); ?></strong>
                                                                </span>
                                                            </td>
                                                            <td></td>
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
                        <?php if (!empty($barangs)): ?>
                            <div class="row">
                                <?php if (!empty($barang_stok_rendah)): ?>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h4 class="text-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Barang
                                                    Stok Rendah (≤5)</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Nama Barang</th>
                                                                <th>Stok</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($barang_stok_rendah as $item): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                                                    <td>
                                                                        <span
                                                                            class="badge badge-<?php echo $item['stok_barang'] == 0 ? 'danger' : 'warning'; ?>">
                                                                            <?php echo $item['stok_barang']; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($item['stok_barang'] == 0): ?>
                                                                            <span class="text-danger">Habis</span>
                                                                        <?php else: ?>
                                                                            <span class="text-warning">Rendah</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="text-info"><i class="fas fa-chart-pie mr-2"></i>Ringkasan Laporan
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group list-group-flush">
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-box text-primary mr-2"></i>Total Jenis
                                                        Barang</span>
                                                    <span class="badge badge-primary"><?php echo $total_barang; ?></span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-cubes text-success mr-2"></i>Total Stok</span>
                                                    <span
                                                        class="badge badge-success"><?php echo number_format($total_stok); ?></span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Stok
                                                        Rendah</span>
                                                    <span
                                                        class="badge badge-warning"><?php echo count($barang_stok_rendah); ?></span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-times-circle text-danger mr-2"></i>Stok
                                                        Kosong</span>
                                                    <span
                                                        class="badge badge-danger"><?php echo count($barang_kosong); ?></span>
                                                </li>
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><i class="fas fa-money-bill-wave text-info mr-2"></i>Total Nilai
                                                        Inventori</span>
                                                    <span class="badge badge-info">Rp
                                                        <?php echo number_format($total_nilai, 0, ',', '.'); ?></span>
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
                <hr>
                <div class="row">
                    <div class="col-6">
                        <p><strong>Ringkasan:</strong></p>
                        <ul>
                            <li>Total Barang: <?php echo $total_barang; ?></li>
                            <li>Total Stok: <?php echo number_format($total_stok); ?></li>
                            <li>Stok Rendah: <?php echo count($barang_stok_rendah); ?></li>
                            <li>Total Nilai: Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?></li>
                        </ul>
                    </div>
                    <div class="col-6 text-right">
                        <p>Mengetahui,</p>
                        <br><br>
                        <p>_____________________<br>
                            <strong>Manager Gudang</strong>
                        </p>
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
        $(document).ready(function () {
            if ($.fn.DataTable.isDataTable('#table-1')) {
                $('#table-1').DataTable().destroy();
            }

            $("#table-1").DataTable({
                "columnDefs": [
                    { "orderable": false, "targets": [1] }, // Photo column
                    { "className": "text-center", "targets": [0] },
                    { "className": "text-right", "targets": [4, 6] }
                ],
                "responsive": true,
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                "order": [[7, "desc"]], // Sort by created_at descending
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
                    // Update footer
                    // Update footer totals
                    var api = this.api();

                    // Calculate totals for visible rows
                    var totalHarga = 0;
                    var totalStok = 0;
                    var totalNilai = 0;

                    api.rows({ page: 'current' }).data().each(function (row, index) {
                        // Assuming columns are: #, Photo, Nama, Desc, Harga, Stok, Total, Date
                        var harga = parseFloat(row[4].replace(/[^\d]/g, '')) || 0;
                        var stok = parseInt(row[5].replace(/[^\d]/g, '')) || 0;
                        var nilai = parseFloat(row[6].replace(/[^\d]/g, '')) || 0;

                        totalHarga += harga;
                        totalStok += stok;
                        totalNilai += nilai;
                    });

                    // Update footer if exists
                    if ($(api.table().footer()).length) {
                        $(api.column(4).footer()).html(
                            '<span class="text-success">Rp ' + totalHarga.toLocaleString('id-ID') + '</span>'
                        );
                        $(api.column(5).footer()).html(
                            '<span class="badge badge-info"><i class="fas fa-cubes mr-1"></i>' + totalStok.toLocaleString('id-ID') + '</span>'
                        );
                        $(api.column(6).footer()).html(
                            '<span class="text-primary"><strong>Rp ' + totalNilai.toLocaleString('id-ID') + '</strong></span>'
                        );
                    }
                }
            });

            // Add search delay for better performance
            $('#table-1_filter input').off().on('keyup', function (e) {
                var table = $('#table-1').DataTable();
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(function () {
                    table.search(e.target.value).draw();
                }, 500);
            });
        });

        // Ganti fungsi printReport() yang lama dengan yang ini
        function printReport() {
            // Simpan scroll position dan state
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const originalTitle = document.title;

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

            // Siapkan dokumen untuk print
            setTimeout(function () {
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }

                // Ubah title untuk print
                document.title = 'Laporan Barang - ' + new Date().toLocaleDateString('id-ID');

                // Sembunyikan DataTables controls sementara
                const dataTablesControls = document.querySelectorAll(
                    '.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dataTables_processing'
                );
                dataTablesControls.forEach(el => {
                    el.style.display = 'none';
                });

                // Pastikan tabel dalam keadaan normal (tidak ada filter aktif)
                if ($.fn.DataTable.isDataTable('#table-1')) {
                    const table = $('#table-1').DataTable();

                    // Reset search dan paging untuk menampilkan semua data
                    table.search('').draw();
                    table.page.len(-1).draw(); // Show all entries
                }

                // Scroll ke atas
                window.scrollTo(0, 0);

                // Tunggu sebentar untuk memastikan rendering selesai
                setTimeout(() => {
                    try {
                        // Eksekusi print
                        window.print();

                        // Cleanup setelah print
                        setTimeout(() => {
                            // Kembalikan title
                            document.title = originalTitle;

                            // Kembalikan scroll position
                            window.scrollTo(0, scrollTop);

                            // Tampilkan kembali DataTables controls
                            dataTablesControls.forEach(el => {
                                el.style.display = '';
                            });

                            // Reinitialize DataTable dengan pengaturan normal
                            if ($.fn.DataTable.isDataTable('#table-1')) {
                                const table = $('#table-1').DataTable();
                                table.page.len(25).draw(); // Kembali ke 25 entries per page
                            }
                        }, 1000);

                    } catch (error) {
                        console.error('Error saat mencetak:', error);

                        // Cleanup jika terjadi error
                        document.title = originalTitle;
                        window.scrollTo(0, scrollTop);
                        dataTablesControls.forEach(el => {
                            el.style.display = '';
                        });

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

        // Export to Excel function (optional enhancement)
        function exportToExcel() {
            // Get table data
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

            // Create CSV content
            var csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "No,Nama Barang,Deskripsi,Harga,Stok,Total Nilai,Tanggal Dibuat\n";

            data.forEach(function (row, index) {
                // Clean data and escape quotes
                var cleanRow = [
                    index + 1,
                    row[2].replace(/<[^>]*>/g, '').replace(/"/g, '""'), // Nama barang (remove HTML)
                    row[3].replace(/<[^>]*>/g, '').replace(/"/g, '""'), // Deskripsi (remove HTML)
                    row[4].replace(/[^\d]/g, ''), // Harga (numbers only)
                    row[5].replace(/[^\d]/g, ''), // Stok (numbers only)
                    row[6].replace(/[^\d]/g, ''), // Total nilai (numbers only)
                    row[7].replace(/<[^>]*>/g, '').replace(/"/g, '""')  // Tanggal (remove HTML)
                ];
                csvContent += '"' + cleanRow.join('","') + '"\n';
            });

            // Download file
            var encodedUri = encodeURI(csvContent);
            var link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "laporan_barang_" + new Date().toISOString().split('T')[0] + ".csv");
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
        /* Ganti CSS print yang ada dengan kode ini */
        @media print {

            /* Sembunyikan semua elemen yang tidak diperlukan */
            .no-print,
            .main-sidebar,
            .navbar-bg,
            .main-navbar,
            .section-header,
            .card-header.no-print,
            .btn,
            .modal,
            .alert-container,
            .breadcrumb,
            .section-header-breadcrumb,
            .card-header-action,
            .filter-card,
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate,
            .dataTables_wrapper .dataTables_processing,
            .pagination,
            .page-link,
            .page-item,
            .dropdown,
            .dropdown-menu,
            #table-1_wrapper .row:first-child,
            #table-1_wrapper .row:last-child,
            .dataTables_scrollHead,
            .sorting,
            .sorting_asc,
            .sorting_desc {
                display: none !important;
            }

            /* Reset layout untuk print */
            .main-sidebar,
            .navbar-bg,
            .main-navbar {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .section-body {
                padding: 0 !important;
            }

            .container-fluid,
            .row,
            .col-12 {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Tampilkan header dan footer print */
            .print-header {
                display: block !important;
                text-align: center;
                padding: 20px 0;
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
                page-break-after: avoid;
            }

            .print-footer {
                display: block !important;
                margin-top: 30px;
                page-break-inside: avoid;
                border-top: 1px solid #000;
                padding-top: 20px;
            }

            /* Styling untuk kartu statistik yang akan dicetak */
            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
                margin-bottom: 10px !important;
                page-break-inside: avoid;
            }

            .card-body {
                padding: 10px !important;
            }

            /* Styling untuk tabel */
            .table {
                font-size: 10px !important;
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .table th,
            .table td {
                border: 1px solid #000 !important;
                padding: 4px !important;
                text-align: left;
                vertical-align: top;
            }

            .table thead th {
                background-color: #f8f9fa !important;
                font-weight: bold !important;
                text-align: center !important;
            }

            .table tfoot td {
                background-color: #f8f9fa !important;
                font-weight: bold !important;
                border-top: 2px solid #000 !important;
            }

            /* Hilangkan kolom foto dari print */
            .table th:nth-child(2),
            .table td:nth-child(2) {
                display: none !important;
            }

            /* Styling untuk badge dan status */
            .badge {
                border: 1px solid #000 !important;
                background-color: transparent !important;
                color: #000 !important;
                padding: 2px 4px !important;
                font-size: 8px !important;
            }

            /* Reset warna untuk print */
            * {
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
                color: #000 !important;
            }

            .text-success,
            .text-primary,
            .text-warning,
            .text-danger,
            .text-info,
            .text-muted {
                color: #000 !important;
            }

            /* Styling untuk baris tabel dengan status */
            .table-warning {
                background-color: #fff3cd !important;
            }

            .table-danger {
                background-color: #f8d7da !important;
            }

            /* Pengaturan page break */
            .stats-card,
            .row {
                page-break-inside: avoid;
            }

            .table tbody tr {
                page-break-inside: avoid;
            }

            /* Pastikan konten tidak terpotong */
            body {
                margin: 0;
                padding: 10px;
                font-family: Arial, sans-serif;
            }

            /* Sembunyikan scrollbar */
            ::-webkit-scrollbar {
                display: none !important;
            }

            /* Ukuran font yang konsisten */
            h1,
            h2,
            h3,
            h4,
            h5,
            h6 {
                font-size: 14px !important;
                margin: 5px 0 !important;
            }

            p {
                font-size: 10px !important;
                margin: 2px 0 !important;
            }

            /* Pastikan tabel responsif untuk print */
            .table-responsive {
                overflow: visible !important;
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