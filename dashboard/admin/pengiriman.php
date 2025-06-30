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

// Handle konfirmasi penerimaan barang
if ($_POST && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'assign_driver') {
            $id_pengiriman = $_POST['id_pengiriman'];
            $id_driver = $_POST['id_driver'];

            // Update driver dan status menjadi 'dikirim'
            $stmt = $pdo->prepare("UPDATE tb_pengiriman SET id_driver = ?, status = 'dikirim', updated_at = NOW() WHERE id_pengiriman = ?");
            $result = $stmt->execute([$id_driver, $id_pengiriman]);

            if ($result) {
                $_SESSION['alert_message'] = 'Driver berhasil ditugaskan dan status diupdate menjadi dikirim!';
                $_SESSION['alert_type'] = 'success';
                $_SESSION['alert_title'] = 'Berhasil!';
                $_SESSION['alert_icon'] = 'fas fa-check-circle';
            } else {
                $_SESSION['alert_message'] = 'Gagal menugaskan driver!';
                $_SESSION['alert_type'] = 'danger';
                $_SESSION['alert_title'] = 'Error!';
                $_SESSION['alert_icon'] = 'fas fa-exclamation-circle';
            }
        } else if ($_POST['action'] === 'confirm_delivery') {
            $id_pengiriman = $_POST['id_pengiriman'];

            // Update status pengiriman menjadi 'selesai'
            $stmt = $pdo->prepare("UPDATE tb_pengiriman SET status = 'selesai', updated_at = NOW() WHERE id_pengiriman = ?");
            $result = $stmt->execute([$id_pengiriman]);

            if ($result) {
                $_SESSION['alert_message'] = 'Status pengiriman berhasil diupdate menjadi selesai!';
                $_SESSION['alert_type'] = 'success';
                $_SESSION['alert_title'] = 'Berhasil!';
                $_SESSION['alert_icon'] = 'fas fa-check-circle';
            } else {
                $_SESSION['alert_message'] = 'Gagal mengupdate status pengiriman!';
                $_SESSION['alert_type'] = 'danger';
                $_SESSION['alert_title'] = 'Error!';
                $_SESSION['alert_icon'] = 'fas fa-exclamation-circle';
            }
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        $_SESSION['alert_message'] = 'Error: ' . $e->getMessage();
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Error!';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-circle';

        header("Location: " . $_SERVER['PHP_SELF']);
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

// Fetch driver untuk dropdown
$stmt_drivers = $pdo->prepare("SELECT id_user, email FROM tb_user WHERE role = 'Driver'");
$stmt_drivers->execute();
$drivers = $stmt_drivers->fetchAll();

// Fetch transaksi yang memiliki status pengiriman dengan JOIN untuk mendapatkan data lengkap
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        b.nama_barang,
        b.harga_barang,
        b.photo_barang,
        u.email as email_user,
        p.id_pengiriman,
        p.id_driver,
        p.id_gudang,
        p.status as status_pengiriman,
        p.created_at as shipping_created_at,
        p.updated_at as shipping_updated_at,
        driver.email as driver_email,
        gudang.email as gudang_email
    FROM tb_transaksi t
    LEFT JOIN tb_barang b ON t.id_barang = b.id_barang
    LEFT JOIN tb_user u ON t.id_user = u.id_user
    INNER JOIN tb_pengiriman p ON t.id_transaksi = p.id_transaksi
    LEFT JOIN tb_user driver ON p.id_driver = driver.id_user
    LEFT JOIN tb_user gudang ON p.id_gudang = gudang.id_user
    WHERE p.status IN ('disiapkan', 'dikirim', 'terkirim')
    AND t.status_pembayaran = 'paid'
    ORDER BY p.updated_at DESC, t.created_at DESC
");
$stmt->execute();
$transactions = $stmt->fetchAll();

function getStatusDisplay($transaction)
{
    if ($transaction['id_gudang'] === null) {
        return [
            'text' => 'DI PROSES',
            'class' => '',
            'icon' => 'fas fa-clock'
        ];
    } else if ($transaction['status_pengiriman'] === 'disiapkan') {
        return [
            'text' => 'DISIAPKAN - ' . strtoupper($transaction['gudang_email']),
            'class' => '',
            'icon' => 'fas fa-box'
        ];
    } else if ($transaction['status_pengiriman'] === 'dikirim') {
        return [
            'text' => 'DIKIRIM',
            'class' => '',
            'icon' => 'fas fa-truck'
        ];
    } else if ($transaction['status_pengiriman'] === 'terkirim') {
        return [
            'text' => 'TERKIRIM',
            'class' => '',
            'icon' => 'fas fa-check'
        ];
    } else {
        return [
            'text' => strtoupper($transaction['status_pengiriman']),
            'class' => '',
            'icon' => 'fas fa-question'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Manajemen Transaksi & Konfirmasi &mdash; Stisla</title>

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
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .currency {
            font-weight: bold;
            color: #28a745;
        }

        . {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85em;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .invoice-print,
            .invoice-print * {
                visibility: visible;
            }

            .invoice-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
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
                        <h1><i class="fas fa-shipping-fast mr-3"></i>Manajemen Transaksi & Pengiriman</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item active"><a href="index.php"><i
                                        class="fas fa-home mr-1"></i>Dashboard</a></div>
                            <div class="breadcrumb-item"><i class="fas fa-truck mr-1"></i>Transaksi & Pengiriman</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4><i class="fas fa-table mr-2"></i>Transaksi Dalam Pengiriman</h4>
                                        <div class="card-header-action">
                                            <div class="badge badge-info badge-lg">
                                                <i class="fas fa-truck mr-1"></i>
                                                Total: <?php echo count($transactions); ?> transaksi dalam pengiriman
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($transactions)): ?>
                                            <div class="empty-state" data-height="400">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-truck"></i>
                                                </div>
                                                <h2>Tidak Ada Transaksi Dalam Pengiriman</h2>
                                                <p class="lead">Belum ada transaksi yang sedang dalam proses pengiriman atau
                                                    terkirim.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover" id="table-1">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center"><i class="fas fa-hashtag"></i></th>
                                                            <th><i class="fas fa-image mr-1"></i>Produk</th>
                                                            <th><i class="fas fa-receipt mr-1"></i>Order ID</th>
                                                            <th><i class="fas fa-user mr-1"></i>Pemesan</th>
                                                            <th><i class="fas fa-phone mr-1"></i>No. HP</th>
                                                            <th><i class="fas fa-map-marker-alt mr-1"></i>Alamat</th>
                                                            <th><i class="fas fa-shopping-cart mr-1"></i>Qty</th>
                                                            <th><i class="fas fa-money-bill-wave mr-1"></i>Total</th>
                                                            <th><i class="fas fa-shipping-fast mr-1"></i>Status Kirim</th>
                                                            <th><i class="fas fa-calendar mr-1"></i>Tanggal Kirim</th>
                                                            <th class="text-center"><i class="fas fa-cogs mr-1"></i>Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($transactions as $index => $trans): ?>
                                                            <tr>
                                                                <td class="text-center">
                                                                    <span
                                                                        class="badge badge-secondary"><?php echo $index + 1; ?></span>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if ($trans['photo_barang']): ?>
                                                                            <img src="../../assets/img/products/<?php echo $trans['photo_barang']; ?>"
                                                                                class="product-image mr-2" alt="Product">
                                                                        <?php else: ?>
                                                                            <div
                                                                                class="product-image mr-2 bg-light d-flex align-items-center justify-content-center">
                                                                                <i class="fas fa-image text-muted"></i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <strong><?php echo htmlspecialchars($trans['nama_barang'] ?? 'Produk Tidak Ditemukan'); ?></strong>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span
                                                                        class="order-id"><?php echo htmlspecialchars($trans['order_id']); ?></span>
                                                                </td>
                                                                <td>
                                                                    <strong><?php echo htmlspecialchars($trans['nama_pemesan']); ?></strong>
                                                                    <br>
                                                                    <small
                                                                        class="text-muted"><?php echo htmlspecialchars($trans['email_user'] ?? 'Email tidak tersedia'); ?></small>
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-phone text-success mr-1"></i>
                                                                    <?php echo htmlspecialchars($trans['nohp_pemesan']); ?>
                                                                </td>
                                                                <td>
                                                                    <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                                                        title="<?php echo htmlspecialchars($trans['alamat_pemesan']); ?>">
                                                                        <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                                                        <?php echo htmlspecialchars($trans['alamat_pemesan']); ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span><?php echo $trans['jumlah_beli']; ?> pcs</span>
                                                                </td>
                                                                <td>
                                                                    <span class="currency">
                                                                        Rp
                                                                        <?php echo number_format($trans['total_harga'], 0, ',', '.'); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $statusDisplay = getStatusDisplay($trans);
                                                                    ?>
                                                                    <span class=" <?php echo $statusDisplay['class']; ?>">
                                                                        <i
                                                                            class="<?php echo $statusDisplay['icon']; ?> mr-1"></i>
                                                                        <?php echo $statusDisplay['text']; ?>
                                                                    </span>
                                                                    <?php if ($trans['id_driver'] && $trans['status_pengiriman'] === 'dikirim'): ?>
                                                                        <br><small class="text-muted">Driver:
                                                                            <?php echo htmlspecialchars($trans['driver_email']); ?></small>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-clock mr-1 text-muted"></i>
                                                                    <?php echo date('d/m/Y H:i', strtotime($trans['shipping_updated_at'] ?? $trans['shipping_created_at'])); ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <div class="btn-group-vertical" role="group">
                                                                        <div class="btn-group mb-1" role="group">
                                                                            <button type="button" class="btn btn-info btn-sm"
                                                                                onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($trans)); ?>)"
                                                                                title="Detail Transaksi">
                                                                                <i class="fas fa-eye"></i>
                                                                            </button>
                                                                            <button type="button" class="btn btn-success btn-sm"
                                                                                onclick="printInvoice(<?php echo htmlspecialchars(json_encode($trans)); ?>)"
                                                                                title="Cetak Invoice">
                                                                                <i class="fas fa-print"></i>
                                                                            </button>
                                                                        </div>

                                                                        <?php if ($trans['id_gudang'] !== null && $trans['id_driver'] === null): ?>
                                                                            <!-- Tombol Assign Driver jika sudah disiapkan gudang tapi belum ada driver -->
                                                                            <button type="button"
                                                                                class="btn btn-warning btn-sm mb-1"
                                                                                onclick="assignDriver(<?php echo $trans['id_pengiriman']; ?>, '<?php echo htmlspecialchars($trans['order_id']); ?>')"
                                                                                title="Tugaskan Driver">
                                                                                <i class="fas fa-user-plus"></i> Tugaskan Driver
                                                                            </button>
                                                                        <?php endif; ?>

                                                                        <?php if ($trans['id_driver'] !== null): ?>
                                                                            <!-- Tombol Cetak Surat Jalan jika driver sudah ditugaskan -->
                                                                            <button type="button"
                                                                                class="btn btn-primary btn-sm mb-1"
                                                                                onclick="printSuratJalan(<?php echo htmlspecialchars(json_encode($trans)); ?>)"
                                                                                title="Cetak Surat Jalan">
                                                                                <i class="fas fa-file-alt"></i> Surat Jalan
                                                                            </button>
                                                                        <?php endif; ?>

                                                                        <?php if ($trans['status_pengiriman'] === 'terkirim'): ?>
                                                                            <!-- Tombol Konfirmasi Selesai -->
                                                                            <button type="button" class="btn btn-success btn-sm"
                                                                                onclick="confirmDelivery(<?php echo $trans['id_pengiriman']; ?>, '<?php echo htmlspecialchars($trans['order_id']); ?>')"
                                                                                title="Konfirmasi Selesai">
                                                                                <i class="fas fa-check-double"></i> Selesai
                                                                            </button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
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

    <!-- Modal Assign Driver -->
    <div class="modal fade" id="assignDriverModal" tabindex="-1" role="dialog" aria-labelledby="assignDriverModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="assignDriverModalLabel">
                        <i class="fas fa-user-plus mr-2"></i>Tugaskan Driver
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="alert alert-warning">
                            <i class="fas fa-truck mr-2"></i>
                            Pilih driver untuk mengirim Order ID <strong><span id="assignOrderId"></span></strong>
                        </div>
                    </div>

                    <form id="assignDriverForm" method="POST" action="">
                        <input type="hidden" name="action" value="assign_driver">
                        <input type="hidden" name="id_pengiriman" id="assignIdPengiriman">

                        <div class="form-group">
                            <label for="id_driver"><i class="fas fa-user mr-2"></i>Pilih Driver:</label>
                            <select name="id_driver" id="id_driver" class="form-control" required>
                                <option value="">-- Pilih Driver --</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <option value="<?php echo $driver['id_user']; ?>">
                                        <?php echo htmlspecialchars($driver['email']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="text-center">
                            <i class="fas fa-shipping-fast fa-3x text-warning mb-3"></i>
                            <p class="text-muted">Setelah driver ditugaskan, status akan berubah menjadi
                                <strong>DIKIRIM</strong>
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Batal
                    </button>
                    <button type="submit" form="assignDriverForm" class="btn btn-warning btn-lg">
                        <i class="fas fa-user-plus mr-1"></i>Tugaskan Driver
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="confirmModalLabel">
                        <i class="fas fa-check-double mr-2"></i>Konfirmasi Penerimaan Barang
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Apakah Anda yakin barang dengan Order ID <strong><span id="confirmOrderId"></span></strong>
                            sudah diterima oleh pelanggan?
                        </div>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-box-open fa-3x text-primary mb-3"></i>
                        <p class="text-muted">Status pengiriman akan diubah menjadi <strong>SELESAI</strong> dan tidak
                            dapat dibatalkan.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="confirmForm" method="POST" action="">
                        <input type="hidden" name="action" value="confirm_delivery">
                        <input type="hidden" name="id_pengiriman" id="confirmIdPengiriman">
                        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check-double mr-1"></i>Ya, Konfirmasi Diterima
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Transaction Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white" id="viewModalLabel">
                        <i class="fas fa-eye mr-2"></i>Detail Transaksi
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Product Information -->
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-box mr-2"></i>Informasi Produk</h6>
                                </div>
                                <div class="card-body text-center">
                                    <img id="viewProductImage" class="img-fluid rounded mb-3"
                                        style="max-height: 200px; object-fit: cover;" alt="Product">
                                    <h5 id="viewProductName" class="text-primary"></h5>
                                    <p class="text-muted mb-2">Harga Satuan:</p>
                                    <h6 id="viewProductPrice" class="currency"></h6>
                                </div>
                            </div>
                        </div>

                        <!-- Order Information -->
                        <div class="col-md-8">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-receipt mr-2"></i>Informasi Pesanan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-receipt text-info mr-2"></i>Order ID:</td>
                                                    <td><span id="viewOrderId" class="order-id"></span></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-user text-primary mr-2"></i>Pemesan:</td>
                                                    <td id="viewCustomerName"></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-envelope text-warning mr-2"></i>Email:</td>
                                                    <td id="viewCustomerEmail"></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-phone text-success mr-2"></i>No. HP:</td>
                                                    <td id="viewCustomerPhone"></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-shopping-cart text-info mr-2"></i>Jumlah:</td>
                                                    <td><span id="viewQuantity"
                                                            class="badge badge-info badge-lg"></span></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-money-bill-wave text-success mr-2"></i>Total:
                                                    </td>
                                                    <td><span id="viewTotalPrice" class="currency h5"></span></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-credit-card text-success mr-2"></i>Status:
                                                    </td>
                                                    <td><span id="viewPaymentStatus"
                                                            class="badge badge-success">PAID</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="font-weight-bold"><i
                                                            class="fas fa-calendar text-muted mr-2"></i>Tanggal:</td>
                                                    <td id="viewOrderDate"></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Address -->
                            <div class="card border-warning mt-3">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-map-marker-alt mr-2"></i>Alamat Pengiriman</h6>
                                </div>
                                <div class="card-body">
                                    <p id="viewShippingAddress" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Tutup
                    </button>
                    <button type="button" class="btn btn-success btn-lg" onclick="printFromModal()">
                        <i class="fas fa-print mr-1"></i>Cetak Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Surat Jalan Template for Printing -->
    <div id="suratJalanTemplate" style="display: none;">
        <div class="surat-jalan-print">
            <div style="padding: 20px; font-family: Arial, sans-serif; background: white; color: #333;">
                <!-- Header -->
                <div
                    style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px;">
                    <h1 style="color: #333; margin: 0; font-size: 28px;">SURAT JALAN</h1>
                    <p style="margin: 5px 0; color: #666; font-size: 14px;">E-Payment System</p>
                </div>

                <!-- Info Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                    <div>
                        <h3
                            style="color: #333; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                            Informasi Pengiriman</h3>
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="border: none; padding: 5px 0; width: 40%; font-weight: bold;">No. Surat
                                    Jalan:</td>
                                <td style="border: none; padding: 5px 0;" id="suratJalanNo">-</td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 5px 0; font-weight: bold;">Order ID:</td>
                                <td style="border: none; padding: 5px 0;" id="suratJalanOrderId">-</td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 5px 0; font-weight: bold;">Tanggal Kirim:</td>
                                <td style="border: none; padding: 5px 0;" id="suratJalanDate">-</td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 5px 0; font-weight: bold;">Driver:</td>
                                <td style="border: none; padding: 5px 0;" id="suratJalanDriver">-</td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <h3
                            style="color: #333; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                            Penerima</h3>
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="border: none; padding: 5px 0; width: 30%; font-weight: bold;">Nama:</td>
                                <td style="border: none; padding: 5px 0;" id="suratJalanCustomerName">-</td>
                            </tr>
                            <tr>
                                <td style="border: none; padding: 5px 0; font-weight: bold;">No. HP:</td>
                                <td style="border: none; padding: 5px 0;" id="suratJalanCustomerPhone">-</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Alamat Tujuan -->
                <div style="margin-bottom: 30px;">
                    <h3 style="color: #333; margin-bottom: 10px;">Alamat Tujuan</h3>
                    <div
                        style="background: #f8f9fa; padding: 15px; border-left: 4px solid #ffc107; border-radius: 4px;">
                        <p id="suratJalanAddress" style="margin: 0; font-size: 14px;">-</p>
                    </div>
                </div>

                <!-- Tabel Barang -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                    <thead>
                        <tr style="background: #333; color: white;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #333;">Nama Barang</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #333; width: 15%;">Jumlah
                            </th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #333; width: 25%;">
                                Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;" id="suratJalanProductName">-</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"
                                id="suratJalanQuantity">-</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">Barang dalam kondisi
                                baik</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Tanda Tangan -->
                <div style="display: flex; justify-content: space-between; margin-top: 50px;">
                    <div style="text-align: center; width: 250px;">
                        <p style="margin: 0; font-weight: bold;">Pengirim</p>
                        <div style="height: 80px;"></div>
                        <div style="border-top: 1px solid #333; padding-top: 5px;">
                            <p style="margin: 0; font-weight: bold;" id="suratJalanDriverSign">-</p>
                            <small style="color: #666;">Driver</small>
                        </div>
                    </div>
                    <div style="text-align: center; width: 250px;">
                        <p style="margin: 0; font-weight: bold;">Penerima</p>
                        <div style="height: 80px;"></div>
                        <div style="border-top: 1px solid #333; padding-top: 5px;">
                            <p style="margin: 0; font-weight: bold;" id="suratJalanReceiverSign">-</p>
                            <small style="color: #666;">Penerima</small>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p style="color: #666; margin: 0; font-size: 12px;">
                        Surat jalan ini dicetak pada: <span id="suratJalanPrintTime">-</span>
                    </p>
                    <p style="color: #666; margin: 5px 0 0 0; font-size: 11px;">
                        <em>Harap tanda tangan penerima sebagai bukti penerimaan barang</em>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Invoice Template for Printing -->
    <div id="invoiceTemplate" style="display: none;">
        <div class="invoice-print">
            <div style="padding: 20px; font-family: Arial, sans-serif;">
                <div
                    style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px;">
                    <h1 style="color: #333; margin: 0;">INVOICE</h1>
                    <p style="margin: 5px 0; color: #666;">E-Payment System</p>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                    <div>
                        <h3 style="color: #333; margin-bottom: 10px;">Detail Pesanan</h3>
                        <p><strong>Order ID:</strong> <span id="printOrderId"></span></p>
                        <p><strong>Tanggal:</strong> <span id="printOrderDate"></span></p>
                        <p><strong>Status:</strong> <span style="color: green; font-weight: bold;">PAID</span></p>
                    </div>
                    <div style="text-align: right;">
                        <h3 style="color: #333; margin-bottom: 10px;">Informasi Pelanggan</h3>
                        <p><strong>Nama:</strong> <span id="printCustomerName"></span></p>
                        <p><strong>Email:</strong> <span id="printCustomerEmail"></span></p>
                        <p><strong>No. HP:</strong> <span id="printCustomerPhone"></span></p>
                    </div>
                </div>

                <div style="margin-bottom: 30px;">
                    <h3 style="color: #333; margin-bottom: 10px;">Alamat Pengiriman</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff;">
                        <p id="printShippingAddress" style="margin: 0;"></p>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                    <thead>
                        <tr style="background: #333; color: white;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Produk</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Harga Satuan</th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;">Jumlah</th>
                            <th style="padding: 12px; text-align: right; border: 1px solid #ddd;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;" id="printProductName"></td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"
                                id="printProductPrice"></td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;" id="printQuantity">
                            </td>
                            <td style="padding: 12px; text-align: right; border: 1px solid #ddd; font-weight: bold;"
                                id="printSubtotal"></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa;">
                            <td colspan="3"
                                style="padding: 15px; font-weight: bold; text-align: right; border: 1px solid #ddd;">
                                TOTAL PEMBAYARAN:</td>
                            <td style="padding: 15px; font-weight: bold; font-size: 18px; color: #28a745; text-align: right; border: 1px solid #ddd;"
                                id="printTotalPrice"></td>
                        </tr>
                    </tfoot>
                </table>

                <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p style="color: #666; margin: 0;">Terima kasih atas kepercayaan Anda!</p>
                    <p style="color: #666; margin: 5px 0 0 0;">Invoice ini dicetak pada: <span
                            id="printDateTime"></span></p>
                </div>
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
    <script src="../../assets/modules/datatables/datatables.min.js"></script>
    <script src="../../assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/modules/datatables/Select-1.2.4/js/dataTables.select.min.js"></script>

    <!-- Template JS File -->
    <script src="../../assets/js/scripts.js"></script>
    <script src="../../assets/js/custom.js"></script>

    <script>
        let currentTransactionData = null;

        // Initialize DataTable
        $(document).ready(function () {
            if ($.fn.DataTable.isDataTable('#table-1')) {
                $('#table-1').DataTable().destroy();
            }

            $("#table-1").DataTable({
                "columnDefs": [
                    { "orderable": false, "targets": [1, 10] }, // Image and action columns
                    { "className": "text-center", "targets": [0, 10] },
                    { "width": "150px", "targets": [10] } // Action column width
                ],
                "responsive": true,
                "pageLength": 10,
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"]],
                "order": [[9, "desc"]], // Sort by shipping date descending
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
                }
            });

            $('.dataTables_filter input').addClass('form-control').attr('placeholder', 'Cari transaksi...');
            $('.dataTables_length select').addClass('form-control form-control-sm');
        });

        // Function to show confirmation modal
        function confirmDelivery(idPengiriman, orderId) {
            $('#confirmIdPengiriman').val(idPengiriman);
            $('#confirmOrderId').text(orderId);
            $('#confirmModal').modal('show');
        }

        // Function to show assign driver modal
        function assignDriver(idPengiriman, orderId) {
            $('#assignIdPengiriman').val(idPengiriman);
            $('#assignOrderId').text(orderId);
            $('#id_driver').val(''); // Reset dropdown
            $('#assignDriverModal').modal('show');
        }

        // Function to print surat jalan
        function printSuratJalan(transaction) {
            populateSuratJalanData(transaction);

            // Sembunyikan semua konten kecuali surat jalan template
            const originalDisplay = [];
            document.querySelectorAll('body > *:not(#suratJalanTemplate)').forEach((element, index) => {
                originalDisplay[index] = element.style.display;
                element.style.display = 'none';
            });

            // Tampilkan surat jalan template
            document.getElementById('suratJalanTemplate').style.display = 'block';

            // Print
            window.print();

            // Kembalikan tampilan semula setelah print
            setTimeout(() => {
                document.getElementById('suratJalanTemplate').style.display = 'none';
                document.querySelectorAll('body > *:not(#suratJalanTemplate)').forEach((element, index) => {
                    element.style.display = originalDisplay[index];
                });
            }, 100);
        }
        const suratJalanStyles = `
<style>
/* Surat Jalan Print Styles */
@media print {
    .surat-jalan-print {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    
    .surat-jalan-print * {
        visibility: visible !important;
    }
    
    .surat-jalan-print table {
        page-break-inside: avoid;
    }
    
    .surat-jalan-print .signature-section {
        page-break-inside: avoid;
    }
    
    /* Hide everything else when printing surat jalan */
    body.printing-surat-jalan > *:not(#suratJalanTemplate) {
        display: none !important;
    }
    
    body.printing-surat-jalan #suratJalanTemplate {
        display: block !important;
    }
}

/* Enhanced surat jalan template styles */
#suratJalanTemplate {
    display: none;
}

#suratJalanTemplate .surat-jalan-print {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    background: white;
    color: #333;
}

#suratJalanTemplate .signature-section {
    display: flex;
    justify-content: space-between;
    margin-top: 50px;
}

#suratJalanTemplate .signature-box {
    text-align: center;
    width: 200px;
}

#suratJalanTemplate .signature-line {
    border-top: 1px solid #333;
    padding-top: 5px;
    margin-top: 60px;
}
</style>
`;
        // Function to populate surat jalan data
        function populateSuratJalanData(transaction) {
            // Generate surat jalan number
            let suratJalanNo = 'SJ-' + transaction.order_id + '-' + new Date().getTime();

            $('#suratJalanNo').text(suratJalanNo);
            $('#suratJalanOrderId').text(transaction.order_id);
            $('#suratJalanDate').text(new Date().toLocaleDateString('id-ID'));
            $('#suratJalanDriver').text(transaction.driver_email || 'Driver tidak tersedia');
            $('#suratJalanCustomerName').text(transaction.nama_pemesan);
            $('#suratJalanCustomerPhone').text(transaction.nohp_pemesan);
            $('#suratJalanAddress').text(transaction.alamat_pemesan);
            $('#suratJalanProductName').text(transaction.nama_barang || 'Produk Tidak Ditemukan');
            $('#suratJalanQuantity').text(transaction.jumlah_beli + ' pcs');
            $('#suratJalanDriverSign').text(transaction.driver_email || 'Driver');
            $('#suratJalanReceiverSign').text(transaction.nama_pemesan);
            $('#suratJalanPrintTime').text(new Date().toLocaleString('id-ID'));
        }

        // Function to view transaction details
        function viewTransaction(transaction) {
            currentTransactionData = transaction;

            // Set product information
            if (transaction.photo_barang) {
                $('#viewProductImage').attr('src', '../../assets/img/products/' + transaction.photo_barang);
            } else {
                $('#viewProductImage').attr('src', '../../assets/img/no-image.png');
            }

            $('#viewProductName').text(transaction.nama_barang || 'Produk Tidak Ditemukan');
            $('#viewProductPrice').text('Rp ' + new Intl.NumberFormat('id-ID').format(transaction.harga_barang || 0));

            // Set order information
            $('#viewOrderId').text(transaction.order_id);
            $('#viewCustomerName').text(transaction.nama_pemesan);
            $('#viewCustomerEmail').text(transaction.email_user || 'Email tidak tersedia');
            $('#viewCustomerPhone').text(transaction.nohp_pemesan);
            $('#viewQuantity').text(transaction.jumlah_beli + ' pcs');
            $('#viewTotalPrice').text('Rp ' + new Intl.NumberFormat('id-ID').format(transaction.total_harga));
            $('#viewOrderDate').text(new Date(transaction.created_at).toLocaleString('id-ID'));
            $('#viewShippingAddress').text(transaction.alamat_pemesan);

            // Set shipping status
            let shippingStatus = transaction.status_pengiriman.toUpperCase();
            let statusClass = '';
            let statusIcon = '';

            switch (transaction.status_pengiriman) {
                case 'dikirim':
                    statusClass = 'badge-warning';
                    statusIcon = 'fas fa-truck';
                    break;
                case 'terkirim':
                    statusClass = 'badge-info';
                    statusIcon = 'fas fa-box';
                    break;
                default:
                    statusClass = 'badge-secondary';
                    statusIcon = 'fas fa-question';
            }

            $('#viewPaymentStatus').removeClass().addClass('badge ' + statusClass).html('<i class="' + statusIcon + ' mr-1"></i>' + shippingStatus);

            $('#viewModal').modal('show');
        }

        $('#confirmForm').on('submit', function (e) {
            let submitBtn = $(this).find('button[type="submit"]');
            let originalText = submitBtn.html();

            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...');

            // Allow form to submit naturally
            setTimeout(function () {
                if (submitBtn.length) {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalText);
                }
            }, 3000);
        });

        // Handle assign driver form submission
        $('#assignDriverForm').on('submit', function (e) {
            let submitBtn = $(this).find('button[type="submit"]');
            let originalText = submitBtn.html();

            // Validation
            if (!$('#id_driver').val()) {
                e.preventDefault();
                alert('Silakan pilih driver terlebih dahulu!');
                return false;
            }

            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Menugaskan...');

            // Allow form to submit naturally
            setTimeout(function () {
                if (submitBtn.length) {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalText);
                }
            }, 3000);
        });

        // Function to print invoice directly
        function printInvoice(transaction) {
            populateInvoiceData(transaction);
            window.print();
        }

        // Function to print from modal
        function printFromModal() {
            if (currentTransactionData) {
                populateInvoiceData(currentTransactionData);
                window.print();
            }
        }

        // Function to populate invoice data
        function populateInvoiceData(transaction) {
            $('#printOrderId').text(transaction.order_id);
            $('#printOrderDate').text(new Date(transaction.created_at).toLocaleString('id-ID'));
            $('#printCustomerName').text(transaction.nama_pemesan);
            $('#printCustomerEmail').text(transaction.email_user || 'Email tidak tersedia');
            $('#printCustomerPhone').text(transaction.nohp_pemesan);
            $('#printShippingAddress').text(transaction.alamat_pemesan);
            $('#printProductName').text(transaction.nama_barang || 'Produk Tidak Ditemukan');
            $('#printProductPrice').text('Rp ' + new Intl.NumberFormat('id-ID').format(transaction.harga_barang || 0));
            $('#printQuantity').text(transaction.jumlah_beli + ' pcs');
            $('#printSubtotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(transaction.total_harga));
            $('#printTotalPrice').text('Rp ' + new Intl.NumberFormat('id-ID').format(transaction.total_harga));
            $('#printDateTime').text(new Date().toLocaleString('id-ID'));
        }

        // Auto hide alert after 5 seconds
        setTimeout(function () {
            $('.alert-container .alert').fadeOut('slow');
        }, 5000);

        // Enhance table with hover effects
        $('#table-1 tbody').on('mouseenter', 'tr', function () {
            $(this).addClass('table-active');
        });

        $('#table-1 tbody').on('mouseleave', 'tr', function () {
            $(this).removeClass('table-active');
        });

        // Add tooltip for truncated addresses
        $('[title]').tooltip({
            placement: 'top',
            trigger: 'hover'
        });

        // Format currency on page load
        $('.currency').each(function () {
            let text = $(this).text();
            if (text.includes('Rp')) {
                // Already formatted
                return;
            }
            let number = parseFloat(text.replace(/[^\d.-]/g, ''));
            if (!isNaN(number)) {
                $(this).text('Rp ' + new Intl.NumberFormat('id-ID').format(number));
            }
        });

        // Refresh data every 30 seconds
        setInterval(function () {
            // Optional: Add auto-refresh functionality
            // location.reload();
        }, 30000);

        // Print styles enhancement
        window.addEventListener('beforeprint', function () {
            // Hide elements that shouldn't be printed
            $('.no-print').hide();
            // Show only invoice template
            $('#invoiceTemplate').show();
        });

        window.addEventListener('afterprint', function () {
            // Restore page elements
            $('.no-print').show();
            $('#invoiceTemplate').hide();
        });

        // Tambahkan styles ke head jika belum ada
        if (!document.getElementById('suratJalanStyles')) {
            const styleElement = document.createElement('div');
            styleElement.id = 'suratJalanStyles';
            styleElement.innerHTML = suratJalanStyles;
            document.head.appendChild(styleElement);
        }

        // Event listener untuk print events surat jalan
        window.addEventListener('beforeprint', function () {
            if (document.getElementById('suratJalanTemplate').style.display !== 'none') {
                document.body.classList.add('printing-surat-jalan');
            }
        });

        window.addEventListener('afterprint', function () {
            document.body.classList.remove('printing-surat-jalan');
        });

        // Fungsi untuk print surat jalan dari modal (jika diperlukan)
        function printSuratJalanFromModal() {
            if (currentTransactionData) {
                printSuratJalan(currentTransactionData);
            } else {
                alert('Data transaksi tidak tersedia untuk dicetak.');
            }
        }

        // Enhanced search functionality
        $('#table-1_filter input').on('keyup', function () {
            let searchTerm = $(this).val().toLowerCase();
            if (searchTerm.length > 2) {
                // Highlight search terms
                $('#table-1 tbody tr').each(function () {
                    let rowText = $(this).text().toLowerCase();
                    if (rowText.includes(searchTerm)) {
                        $(this).addClass('table-warning');
                    } else {
                        $(this).removeClass('table-warning');
                    }
                });
            } else {
                $('#table-1 tbody tr').removeClass('table-warning');
            }
        });

        // Modal enhancement
        $('#viewModal').on('shown.bs.modal', function () {
            // Focus on close button for accessibility
            $(this).find('[data-dismiss="modal"]').first().focus();
        });

        // Error handling for images
        $('.product-image').on('error', function () {
            $(this).attr('src', '../../assets/img/no-image.png');
            $(this).addClass('bg-light');
        });

        // Add loading state for actions
        $('button[onclick^="viewTransaction"], button[onclick^="printInvoice"]').on('click', function () {
            let $btn = $(this);
            let originalHtml = $btn.html();

            $btn.prop('disabled', true);
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');

            setTimeout(function () {
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }, 1000);
        });

        // Export functionality (optional)
        function exportToExcel() {
            // Implementation for Excel export if needed
            console.log('Export to Excel functionality can be added here');
        }

        function exportToPDF() {
            // Implementation for PDF export if needed
            console.log('Export to PDF functionality can be added here');
        }

        // Statistics counter animation (optional)
        function animateCounter(element, target) {
            let current = 0;
            let increment = target / 50;
            let timer = setInterval(function () {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $(element).text(Math.floor(current));
            }, 20);
        }

        // Initialize counter animation for total transactions
        let totalTransactions = <?php echo count($transactions); ?>;
        if (totalTransactions > 0) {
            animateCounter('.badge-info', totalTransactions);
        }

        // Keyboard shortcuts
        $(document).on('keydown', function (e) {
            // Ctrl + P for print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                if (currentTransactionData) {
                    printFromModal();
                }
            }

            // ESC to close modal
            if (e.key === 'Escape') {
                $('#viewModal').modal('hide');
            }
        });

        // Touch/mobile enhancements
        if ('ontouchstart' in window) {
            // Add touch-friendly interactions for mobile
            $('.btn-group .btn').addClass('btn-lg');
            $('.table-responsive').css('overflow-x', 'auto');
        }

        // Accessibility improvements
        $('button[title]').attr('aria-label', function () {
            return $(this).attr('title');
        });

        // Form validation helpers (if needed for future forms)
        function validateForm(formId) {
            let isValid = true;
            $(formId + ' [required]').each(function () {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            return isValid;
        }

        // Notification system enhancement
        function showNotification(title, message, type = 'info') {
            // Create dynamic notification
            let notification = `
                <div class="alert alert-${type} alert-has-icon alert-dismissible fade show" role="alert">
                    <div class="alert-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="alert-body">
                        <div class="alert-title">${title}</div>
                        ${message}
                    </div>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `;

            $('.alert-container').append(notification);

            // Auto remove after 5 seconds
            setTimeout(function () {
                $('.alert-container .alert').last().fadeOut('slow', function () {
                    $(this).remove();
                });
            }, 5000);
        }

        // Function to get status badge HTML
        function getStatusBadge(status, gudangEmail, driverEmail) {
            let badge = '';

            if (status === 'disiapkan' && gudangEmail) {
                badge = `<span class="badge badge-info badge-lg">
                    <i class="fas fa-box mr-1"></i>DISIAPKAN - ${gudangEmail.toUpperCase()}
                 </span>`;
            } else if (status === 'dikirim' && driverEmail) {
                badge = `<span class="badge badge-warning badge-lg">
                    <i class="fas fa-truck mr-1"></i>DIKIRIM
                 </span>
                 <br><small class="text-muted">Driver: ${driverEmail}</small>`;
            } else if (status === 'terkirim') {
                badge = `<span class="badge badge-success badge-lg">
                    <i class="fas fa-check mr-1"></i>TERKIRIM
                 </span>`;
            } else {
                badge = `<span class="badge badge-secondary badge-lg">
                    <i class="fas fa-clock mr-1"></i>DI PROSES
                 </span>`;
            }

            return badge;
        }

        // Enhanced notification for driver assignment
        function showDriverAssignedNotification(driverEmail, orderId) {
            showNotification(
                'Driver Ditugaskan!',
                `Driver ${driverEmail} telah ditugaskan untuk Order ID: ${orderId}`,
                'success'
            );
        }

        // Console info for debugging
        console.log('🚀 Transaction Management System Loaded');
        console.log('📊 Total Transactions Ready to Ship:', totalTransactions);
        console.log('🔧 DataTable initialized with responsive design');

    </script>

    <!-- Additional CSS for better mobile responsiveness -->
    <style>
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .product-image {
                width: 35px;
                height: 35px;
            }

            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .modal-xl {
                max-width: 95%;
            }
        }

        /* Enhanced print styles */
        @media print {
            .invoice-print {
                margin: 0;
                padding: 0;
            }

            .invoice-print table {
                page-break-inside: avoid;
            }

            .invoice-print h1,
            .invoice-print h3 {
                page-break-after: avoid;
            }
        }

        /* Loading animation */
        .btn .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Highlight search results */
        .table-warning {
            background-color: rgba(255, 193, 7, 0.2) !important;
        }

        /* Enhanced alert container */
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

</body>

</html>