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
    if ($_SESSION['role'] === 'Gudang') {
        header("Location: ../dashboard/gudang/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pelanggan') {
        header("Location: ../dashboard/pelanggan/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pelayan') {
        header("Location: ../dashboard/pelayan/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Administrator') {
        header("Location: ../dashboard/admin/index.php");
        exit();
    }
}

// Handle konfirmasi penerimaan barang
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'confirm_delivery') {
    try {
        $id_pengiriman = $_POST['id_pengiriman'];

        // Update status pengiriman menjadi 'terkirim'
        $stmt = $pdo->prepare("UPDATE tb_pengiriman SET id_driver = ?, status = 'terkirim', updated_at = NOW() WHERE id_pengiriman = ?");
        $result = $stmt->execute([$_SESSION['id_user'], $id_pengiriman]);

        if ($result) {
            $_SESSION['alert_message'] = 'Status pengiriman berhasil diupdate menjadi terkirim!';
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_title'] = 'Berhasil!';
            $_SESSION['alert_icon'] = 'fas fa-check-circle';
        } else {
            $_SESSION['alert_message'] = 'Gagal mengupdate status pengiriman!';
            $_SESSION['alert_type'] = 'danger';
            $_SESSION['alert_title'] = 'Error!';
            $_SESSION['alert_icon'] = 'fas fa-exclamation-circle';
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

// Query untuk mengambil data transaksi dengan detail lengkap seperti pada order.php
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        td.nama_barang,
        td.harga_barang,
        td.jumlah_beli,
        td.subtotal,
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
    LEFT JOIN tb_transaksi_detail td ON t.id_transaksi = td.id_transaksi
    LEFT JOIN tb_barang b ON td.id_barang = b.id_barang
    LEFT JOIN tb_user u ON t.id_user = u.id_user
    INNER JOIN tb_pengiriman p ON t.id_transaksi = p.id_transaksi
    LEFT JOIN tb_user driver ON p.id_driver = driver.id_user
    LEFT JOIN tb_user gudang ON p.id_gudang = gudang.id_user
    WHERE p.status = 'dikirim'
    AND t.status_pembayaran = 'paid'
    AND p.id_driver = :id_user
    ORDER BY p.updated_at DESC, t.created_at DESC
");
$stmt->bindParam(':id_user', $_SESSION['id_user'], PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

// Group transactions by id_transaksi untuk menggabungkan detail transaksi yang sama
$grouped_transactions = [];
foreach ($transactions as $trans) {
    $trans_id = $trans['id_transaksi'];
    if (!isset($grouped_transactions[$trans_id])) {
        $grouped_transactions[$trans_id] = $trans;
        $grouped_transactions[$trans_id]['items'] = [];
    }
    if ($trans['nama_barang']) { // Pastikan ada item
        $grouped_transactions[$trans_id]['items'][] = [
            'nama_barang' => $trans['nama_barang'],
            'harga_barang' => $trans['harga_barang'],
            'jumlah_beli' => $trans['jumlah_beli'],
            'subtotal' => $trans['subtotal'],
            'photo_barang' => $trans['photo_barang']
        ];
    }
}

function getStatusDisplay($transaction)
{
    if ($transaction['id_gudang'] === null) {
        return [
            'text' => 'DI PROSES',
            'class' => 'badge-secondary',
            'icon' => 'fas fa-clock'
        ];
    } else if ($transaction['status_pengiriman'] === 'disiapkan') {
        return [
            'text' => 'DISIAPKAN - ' . strtoupper($transaction['gudang_email']),
            'class' => 'badge-info',
            'icon' => 'fas fa-box'
        ];
    } else if ($transaction['status_pengiriman'] === 'dikirim') {
        return [
            'text' => 'DIKIRIM',
            'class' => 'badge-warning',
            'icon' => 'fas fa-truck'
        ];
    } else if ($transaction['status_pengiriman'] === 'terkirim') {
        return [
            'text' => 'TERKIRIM',
            'class' => 'badge-success',
            'icon' => 'fas fa-check'
        ];
    } else {
        return [
            'text' => strtoupper($transaction['status_pengiriman']),
            'class' => 'badge-secondary',
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

        .order-id {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85em;
        }

        .items-list {
            max-height: 120px;
            overflow-y: auto;
        }

        .item-row {
            border-bottom: 1px solid #f0f0f0;
            padding: 8px 0;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
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
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Enhanced scrollbar for items list */
        .items-list::-webkit-scrollbar {
            width: 4px;
        }

        .items-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .items-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 2px;
        }

        .items-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Item row hover effects */
        .item-row {
            transition: background-color 0.2s ease;
        }

        .item-row:hover {
            background-color: rgba(0, 123, 255, 0.05);
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
                                                Total: <?php echo count($grouped_transactions); ?> transaksi dalam pengiriman
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($grouped_transactions)): ?>
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
                                                            <th><i class="fas fa-shopping-cart mr-1"></i>Items</th>
                                                            <th><i class="fas fa-money-bill-wave mr-1"></i>Total</th>
                                                            <th><i class="fas fa-shipping-fast mr-1"></i>Status Kirim</th>
                                                            <th><i class="fas fa-calendar mr-1"></i>Tanggal Kirim</th>
                                                            <th class="text-center"><i class="fas fa-cogs mr-1"></i>Action
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php $index = 0;
                                                        foreach ($grouped_transactions as $trans): $index++; ?>
                                                            <tr>
                                                                <td class="text-center">
                                                                    <span
                                                                        class="badge badge-secondary"><?php echo $index; ?></span>
                                                                </td>
                                                                <td>
                                                                    <div class="items-list">
                                                                        <?php if (!empty($trans['items'])): ?>
                                                                            <?php foreach ($trans['items'] as $item): ?>
                                                                                <div class="item-row d-flex align-items-center">
                                                                                    <?php if ($item['photo_barang']): ?>
                                                                                        <img src="../../assets/img/products/<?php echo $item['photo_barang']; ?>"
                                                                                            class="item-image mr-2" alt="Product">
                                                                                    <?php else: ?>
                                                                                        <div
                                                                                            class="item-image mr-2 bg-light d-flex align-items-center justify-content-center">
                                                                                            <i class="fas fa-image text-muted"></i>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                    <div>
                                                                                        <small><strong><?php echo htmlspecialchars($item['nama_barang'] ?? 'Produk Tidak Ditemukan'); ?></strong></small>
                                                                                        <br>
                                                                                        <small class="text-muted"><?php echo $item['jumlah_beli']; ?> x Rp <?php echo number_format($item['harga_barang'], 0, ',', '.'); ?></small>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
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
                                                                    <span class="badge badge-primary">
                                                                        <?php echo count($trans['items']); ?> item(s)
                                                                    </span>
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
                                                                    <span class="badge <?php echo $statusDisplay['class']; ?>">
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
                                                                        </div>

                                                                        <!-- Tombol Cetak Surat Jalan jika driver sudah ditugaskan -->
                                                                        <button type="button"
                                                                            class="btn btn-primary btn-sm mb-1"
                                                                            onclick="printSuratJalan(<?php echo htmlspecialchars(json_encode($trans)); ?>)"
                                                                            title="Cetak Surat Jalan">
                                                                            <i class="fas fa-file-alt"></i> Surat Jalan
                                                                        </button>

                                                                        <?php if ($trans['status_pengiriman'] === 'dikirim'): ?>
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
                        <p class="text-muted">Status pengiriman akan diubah menjadi <strong>TERKIRIM</strong> dan tidak
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
                                <div class="card-body" id="viewProductsList">
                                    <!-- Products will be populated here -->
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
                                                            class="fas fa-shopping-cart text-info mr-2"></i>Items:</td>
                                                    <td><span id="viewItemCount"
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
                    <tbody id="suratJalanItemsTable">
                        <!-- Items will be populated here -->
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
                    <tbody id="printProductsTable">
                        <!-- Products will be populated here -->
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
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#table-1')) {
                $('#table-1').DataTable().destroy();
            }

            $("#table-1").DataTable({
                "columnDefs": [{
                        "orderable": false,
                        "targets": [1, 10]
                    }, // Image and action columns
                    {
                        "className": "text-center",
                        "targets": [0, 10]
                    },
                    {
                        "width": "150px",
                        "targets": [10]
                    } // Action column width
                ],
                "responsive": true,
                "pageLength": 10,
                "lengthMenu": [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "Semua"]
                ],
                "order": [
                    [9, "desc"]
                ], // Sort by shipping date descending
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

        // Function to print surat jalan
        function printSuratJalan(transaction) {
            // Populate data surat jalan terlebih dahulu
            populateSuratJalanData(transaction);

            // Buat window baru untuk pencetakan
            let printWindow = window.open('', '_blank', 'width=800,height=600');

            // Ambil konten surat jalan
            let suratJalanContent = document.getElementById('suratJalanTemplate').innerHTML;

            // Buat HTML lengkap untuk window baru
            let printHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Surat Jalan - ${transaction.order_id}</title>
            <style>
                body { 
                    margin: 0; 
                    padding: 20px; 
                    font-family: Arial, sans-serif; 
                    background: white; 
                    color: #333; 
                }
                
                @media print {
                    body { margin: 0; padding: 15px; }
                    .no-print { display: none !important; }
                }
                
                table { 
                    border-collapse: collapse; 
                    width: 100%; 
                }
                
                table td, table th { 
                    border: 1px solid #ddd; 
                    padding: 8px; 
                    text-align: left; 
                }
                
                table th { 
                    background-color: #333; 
                    color: white; 
                }
                
                .text-center { text-align: center; }
                .font-weight-bold { font-weight: bold; }
                
                /* Print button style */
                .print-btn {
                    position: fixed;
                    top: 10px;
                    right: 10px;
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 10px 15px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                    z-index: 1000;
                }
                
                .print-btn:hover {
                    background: #0056b3;
                }
                
                @media print {
                    .print-btn { display: none; }
                }
            </style>
        </head>
        <body>
            <button class="print-btn no-print" onclick="window.print()">🖨️ Cetak</button>
            ${suratJalanContent}
        </body>
        </html>
    `;

            // Tulis HTML ke window baru
            printWindow.document.write(printHTML);
            printWindow.document.close();

            // Tunggu sebentar lalu fokus ke window baru
            setTimeout(() => {
                printWindow.focus();

                // Auto print setelah 500ms
                setTimeout(() => {
                    printWindow.print();
                }, 500);
            }, 100);
        }

        // Function to populate surat jalan data
        function populateSuratJalanData(transaction) {
            try {
                // Generate surat jalan number
                let now = new Date();
                let suratJalanNo = 'SJ-' + transaction.order_id + '-' + now.getFullYear() +
                    (now.getMonth() + 1).toString().padStart(2, '0') +
                    now.getDate().toString().padStart(2, '0');

                // Populate data dengan pengecekan null/undefined
                document.getElementById('suratJalanNo').textContent = suratJalanNo;
                document.getElementById('suratJalanOrderId').textContent = transaction.order_id || 'N/A';
                document.getElementById('suratJalanDate').textContent = now.toLocaleDateString('id-ID');
                document.getElementById('suratJalanDriver').textContent = transaction.driver_email || 'Driver tidak tersedia';
                document.getElementById('suratJalanCustomerName').textContent = transaction.nama_pemesan || 'N/A';
                document.getElementById('suratJalanCustomerPhone').textContent = transaction.nohp_pemesan || 'N/A';
                document.getElementById('suratJalanAddress').textContent = transaction.alamat_pemesan || 'Alamat tidak tersedia';
                document.getElementById('suratJalanDriverSign').textContent = transaction.driver_email || 'Driver';
                document.getElementById('suratJalanReceiverSign').textContent = transaction.nama_pemesan || 'Penerima';
                document.getElementById('suratJalanPrintTime').textContent = now.toLocaleString('id-ID');

                // Populate items table untuk surat jalan
                let itemsTableHtml = '';
                if (transaction.items && Array.isArray(transaction.items) && transaction.items.length > 0) {
                    transaction.items.forEach(function(item, index) {
                        itemsTableHtml += `
                    <tr>
                        <td style="padding: 12px; border: 1px solid #ddd;">
                            ${item.nama_barang || 'Produk Tidak Ditemukan'}
                        </td>
                        <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">
                            ${item.jumlah_beli || 0} pcs
                        </td>
                        <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">
                            Barang dalam kondisi baik
                        </td>
                    </tr>
                `;
                    });
                } else {
                    itemsTableHtml = `
                <tr>
                    <td colspan="3" style="padding: 12px; text-align: center; border: 1px solid #ddd;">
                        Tidak ada item ditemukan
                    </td>
                </tr>
            `;
                }

                document.getElementById('suratJalanItemsTable').innerHTML = itemsTableHtml;

                console.log('Surat jalan data populated successfully:', {
                    orderId: transaction.order_id,
                    itemsCount: transaction.items ? transaction.items.length : 0
                });

            } catch (error) {
                console.error('Error populating surat jalan data:', error);
                alert('Terjadi kesalahan saat mempersiapkan data surat jalan: ' + error.message);
            }
        }
        // Function to view transaction details
        function viewTransaction(transaction) {
            currentTransactionData = transaction;

            // Set product information
            let productsHtml = '';
            if (transaction.items && transaction.items.length > 0) {
                transaction.items.forEach(function(item) {
                    let imageHtml = '';
                    if (item.photo_barang) {
                        imageHtml = `<img src="../../assets/img/products/${item.photo_barang}" class="img-fluid rounded mb-2" style="max-height: 100px; object-fit: cover;" alt="Product">`;
                    } else {
                        imageHtml = '<div class="bg-light p-3 rounded mb-2 text-center"><i class="fas fa-image text-muted"></i></div>';
                    }

                    productsHtml += `
                        <div class="mb-3 pb-3 border-bottom">
                            ${imageHtml}
                            <h6 class="text-primary">${item.nama_barang || 'Produk Tidak Ditemukan'}</h6>
                            <p class="text-muted mb-1">Harga: Rp ${new Intl.NumberFormat('id-ID').format(item.harga_barang || 0)}</p>
                            <p class="text-muted mb-1">Jumlah: ${item.jumlah_beli} pcs</p>
                            <p class="font-weight-bold text-success">Subtotal: Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal || 0)}</p>
                        </div>
                    `;
                });
            }
            $('#viewProductsList').html(productsHtml);

            // Set order information
            $('#viewOrderId').text(transaction.order_id);
            $('#viewCustomerName').text(transaction.nama_pemesan);
            $('#viewCustomerEmail').text(transaction.email_user || 'Email tidak tersedia');
            $('#viewCustomerPhone').text(transaction.nohp_pemesan);
            $('#viewItemCount').text((transaction.items ? transaction.items.length : 0) + ' item(s)');
            $('#viewTotalPrice').text('Rp ' + new Intl.NumberFormat('id-ID').format(transaction.total_harga));
            $('#viewOrderDate').text(new Date(transaction.created_at).toLocaleString('id-ID'));
            $('#viewShippingAddress').text(transaction.alamat_pemesan);

            $('#viewModal').modal('show');
        }

        $('#confirmForm').on('submit', function(e) {
            let submitBtn = $(this).find('button[type="submit"]');
            let originalText = submitBtn.html();

            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...');

            // Allow form to submit naturally
            setTimeout(function() {
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

            // Populate products table
            let productsTableHtml = '';
            if (transaction.items && transaction.items.length > 0) {
                transaction.items.forEach(function(item) {
                    productsTableHtml += `
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">${item.nama_barang || 'Produk Tidak Ditemukan'}</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">Rp ${new Intl.NumberFormat('id-ID').format(item.harga_barang || 0)}</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">${item.jumlah_beli} pcs</td>
                            <td style="padding: 12px; text-align: right; border: 1px solid #ddd; font-weight: bold;">Rp ${new Intl.NumberFormat('id-ID').format(item.subtotal || 0)}</td>
                        </tr>
                    `;
                });
            }
            $('#printProductsTable').html(productsTableHtml);

            $('#printTotalPrice').text('Rp ' + new Intl.NumberFormat('id-ID').format(transaction.total_harga));
            $('#printDateTime').text(new Date().toLocaleString('id-ID'));
        }

        // Auto hide alert after 5 seconds
        setTimeout(function() {
            $('.alert-container .alert').fadeOut('slow');
        }, 5000);

        // Enhance table with hover effects
        $('#table-1 tbody').on('mouseenter', 'tr', function() {
            $(this).addClass('table-active');
        });

        $('#table-1 tbody').on('mouseleave', 'tr', function() {
            $(this).removeClass('table-active');
        });

        // Add tooltip for truncated addresses
        $('[title]').tooltip({
            placement: 'top',
            trigger: 'hover'
        });

        // Format currency on page load
        $('.currency').each(function() {
            let text = $(this).text();
            if (text.includes('Rp')) {
                return;
            }
            let number = parseFloat(text.replace(/[^\d.-]/g, ''));
            if (!isNaN(number)) {
                $(this).text('Rp ' + new Intl.NumberFormat('id-ID').format(number));
            }
        });

        // Print styles enhancement
        window.addEventListener('beforeprint', function() {
            $('.no-print').hide();
            $('#invoiceTemplate').show();
        });

        window.addEventListener('afterprint', function() {
            $('.no-print').show();
            $('#invoiceTemplate').hide();
        });

        // Initialize counter for total transactions
        let totalTransactions = <?php echo count($grouped_transactions); ?>;

        // Console info for debugging
        console.log('🚀 Pengiriman Management System Loaded');
        console.log('📊 Total Transactions in Delivery:', totalTransactions);
        console.log('🔧 DataTable initialized with responsive design');
    </script>

    <!-- Additional CSS for better mobile responsiveness -->
    <style>
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .product-image,
            .item-image {
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

            .items-list {
                max-height: 80px;
            }
        }

        /* Enhanced print styles */
        @media print {

            .invoice-print,
            .surat-jalan-print {
                margin: 0;
                padding: 0;
            }

            .invoice-print table,
            .surat-jalan-print table {
                page-break-inside: avoid;
            }

            .invoice-print h1,
            .invoice-print h3,
            .surat-jalan-print h1,
            .surat-jalan-print h3 {
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

        /* Status badge animations */
        .badge {
            transition: all 0.3s ease;
        }

        .badge:hover {
            transform: scale(1.05);
        }

        /* Button hover effects */
        .btn-group .btn {
            transition: all 0.2s ease;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>

</body>

</html>