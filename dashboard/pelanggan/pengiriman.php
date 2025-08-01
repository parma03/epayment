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
    } else if ($_SESSION['role'] === 'Administrator') {
        header("Location: ../dashboard/admin/index.php");
        exit();
    }
}

// Handle proses pengiriman selesai
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'process_shipping') {
    try {
        $id_transaksi = $_POST['id_transaksi'];

        // Insert ke tb_pengiriman dengan status 'selesai'
        $stmt = $pdo->prepare("INSERT INTO tb_pengiriman (id_transaksi, status, created_at, updated_at) VALUES (?, 'selesai', NOW(), NOW())");
        $result = $stmt->execute([$id_transaksi]);

        if ($result) {
            $_SESSION['alert_message'] = 'Transaksi berhasil diselesaikan!';
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_title'] = 'Berhasil!';
            $_SESSION['alert_icon'] = 'fas fa-check-circle';
        } else {
            $_SESSION['alert_message'] = 'Gagal memproses transaksi!';
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

// Handle konfirmasi diterima (update status dari 'terkirim' ke 'selesai')
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'confirm_delivery') {
    try {
        $id_pengiriman = $_POST['id_pengiriman'];

        // Update status pengiriman dari 'terkirim' ke 'selesai'
        $stmt = $pdo->prepare("UPDATE tb_pengiriman SET status = 'selesai', updated_at = NOW() WHERE id_pengiriman = ? AND status = 'terkirim'");
        $result = $stmt->execute([$id_pengiriman]);

        if ($result && $stmt->rowCount() > 0) {
            $_SESSION['alert_message'] = 'Konfirmasi diterima berhasil! Pengiriman telah diselesaikan.';
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_title'] = 'Berhasil!';
            $_SESSION['alert_icon'] = 'fas fa-check-circle';
        } else {
            $_SESSION['alert_message'] = 'Gagal mengkonfirmasi pengiriman!';
            $_SESSION['alert_type'] = 'warning';
            $_SESSION['alert_title'] = 'Peringatan!';
            $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
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

// Pastikan session id_user ada
if (!isset($_SESSION['id_user'])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Query gabungan untuk mengambil data transaksi yang belum ada di tb_pengiriman atau yang statusnya 'dikirim'/'terkirim'
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        td.nama_barang,
        td.harga_barang,
        td.jumlah_beli,
        td.subtotal,
        b.photo_barang,
        u.email as email_user,
        p.status as status_pengiriman,
        p.id_pengiriman
    FROM tb_transaksi t
    LEFT JOIN tb_transaksi_detail td ON t.id_transaksi = td.id_transaksi
    LEFT JOIN tb_barang b ON td.id_barang = b.id_barang
    LEFT JOIN tb_user u ON t.id_user = u.id_user
    LEFT JOIN tb_pengiriman p ON t.id_transaksi = p.id_transaksi
    WHERE (p.id_transaksi IS NULL 
           OR p.status IN ('dikirim', 'terkirim'))
    AND t.status_pembayaran = 'paid'
    AND t.id_user = :id_user
    ORDER BY t.created_at DESC
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
    $grouped_transactions[$trans_id]['items'][] = [
        'nama_barang' => $trans['nama_barang'],
        'harga_barang' => $trans['harga_barang'],
        'jumlah_beli' => $trans['jumlah_beli'],
        'subtotal' => $trans['subtotal'],
        'photo_barang' => $trans['photo_barang']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Manajemen Transaksi & Pengiriman &mdash; Stisla</title>

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

        .status-badge {
            font-size: 0.75em;
            padding: 0.25em 0.5em;
        }

        .status-pending {
            background-color: #ffc107;
            color: #856404;
        }

        .status-dikirim {
            background-color: #007bff;
            color: white;
        }

        .status-terkirim {
            background-color: #fd7e14;
            color: white;
        }

        .status-selesai {
            background-color: #28a745;
            color: white;
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
                            <div class="breadcrumb-item"><i class="fas fa-box mr-1"></i>Transaksi & Pengiriman</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4><i class="fas fa-table mr-2"></i>Transaksi & Status Pengiriman</h4>
                                        <div class="card-header-action">
                                            <div class="badge badge-info badge-lg">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Total: <?php echo count($grouped_transactions); ?> transaksi aktif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($grouped_transactions)): ?>
                                            <div class="empty-state" data-height="400">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-box-open"></i>
                                                </div>
                                                <h2>Tidak Ada Transaksi</h2>
                                                <p class="lead">Belum ada transaksi yang perlu diproses atau dikonfirmasi.</p>
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
                                                            <th><i class="fas fa-truck mr-1"></i>Status</th>
                                                            <th><i class="fas fa-calendar mr-1"></i>Tanggal</th>
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
                                                                    $status = $trans['status_pengiriman'] ?? 'pending';
                                                                    $status_class = '';
                                                                    $status_text = '';
                                                                    $status_icon = '';

                                                                    switch ($status) {
                                                                        case 'dikirim':
                                                                            $status_class = 'status-dikirim';
                                                                            $status_text = 'DIKIRIM';
                                                                            $status_icon = 'fas fa-shipping-fast';
                                                                            break;
                                                                        case 'terkirim':
                                                                            $status_class = 'status-terkirim';
                                                                            $status_text = 'TERKIRIM';
                                                                            $status_icon = 'fas fa-truck';
                                                                            break;
                                                                        case 'selesai':
                                                                            $status_class = 'status-selesai';
                                                                            $status_text = 'SELESAI';
                                                                            $status_icon = 'fas fa-check-circle';
                                                                            break;
                                                                        default:
                                                                            $status_class = 'status-pending';
                                                                            $status_text = 'BELUM DIPROSES';
                                                                            $status_icon = 'fas fa-clock';
                                                                    }
                                                                    ?>
                                                                    <span class="badge status-badge <?php echo $status_class; ?>">
                                                                        <i class="<?php echo $status_icon; ?> mr-1"></i>
                                                                        <?php echo $status_text; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <i class="fas fa-clock mr-1 text-muted"></i>
                                                                    <?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?>
                                                                </td>
                                                                <td class="text-center">
                                                                    <div class="btn-group" role="group">
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

                                                                        <?php if (empty($trans['status_pengiriman'])): ?>
                                                                            <!-- Tombol untuk memproses pengiriman (status: pending -> selesai) -->
                                                                            <button type="button" class="btn btn-warning btn-sm"
                                                                                onclick="processShipping(<?php echo $trans['id_transaksi']; ?>, '<?php echo htmlspecialchars($trans['order_id']); ?>')"
                                                                                title="Proses Pengiriman">
                                                                                <i class="fas fa-shipping-fast"></i>
                                                                            </button>
                                                                        <?php elseif ($trans['status_pengiriman'] === 'terkirim'): ?>
                                                                            <!-- Tombol untuk konfirmasi diterima (status: terkirim -> selesai) -->
                                                                            <button type="button" class="btn btn-primary btn-sm"
                                                                                onclick="confirmDelivery(<?php echo $trans['id_pengiriman']; ?>, '<?php echo htmlspecialchars($trans['order_id']); ?>')"
                                                                                title="Konfirmasi Diterima">
                                                                                <i class="fas fa-check-double"></i>
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

    <!-- Process Shipping Modal -->
    <div class="modal fade" id="shippingModal" tabindex="-1" role="dialog" aria-labelledby="shippingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="shippingModalLabel">
                        <i class="fas fa-shipping-fast mr-2"></i>Proses Pengiriman
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Apakah Anda yakin ingin menyelesaikan pengiriman untuk Order ID <strong><span id="shippingOrderId"></span></strong>?
                        </div>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-truck fa-3x text-warning mb-3"></i>
                        <p class="text-muted">Transaksi akan dipindahkan ke status <strong>SELESAI</strong> dan masuk ke daftar histori.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="shippingForm" method="POST" action="">
                        <input type="hidden" name="action" value="process_shipping">
                        <input type="hidden" name="id_transaksi" id="shippingIdTransaksi">
                        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-shipping-fast mr-1"></i>Ya, Selesaikan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Delivery Modal -->
    <div class="modal fade" id="confirmDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeliveryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="confirmDeliveryModalLabel">
                        <i class="fas fa-check-double mr-2"></i>Konfirmasi Diterima
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            Apakah Anda sudah menerima pesanan untuk Order ID <strong><span id="confirmOrderId"></span></strong>?
                        </div>
                    </div>
                    <div class="text-center">
                        <i class="fas fa-box-open fa-3x text-primary mb-3"></i>
                        <p class="text-muted">Dengan mengkonfirmasi, Anda menyatakan bahwa pesanan telah <strong>DITERIMA DENGAN BAIK</strong> dan transaksi akan diselesaikan.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="confirmDeliveryForm" method="POST" action="">
                        <input type="hidden" name="action" value="confirm_delivery">
                        <input type="hidden" name="id_pengiriman" id="confirmIdPengiriman">
                        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check-double mr-1"></i>Ya, Sudah Diterima
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
                    },
                    {
                        "className": "text-center",
                        "targets": [0, 10]
                    }
                ],
                "responsive": true,
                "pageLength": 10,
                "lengthMenu": [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "Semua"]
                ],
                "order": [
                    [9, "desc"]
                ], // Sort by date descending
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

        // Function to show shipping confirmation modal
        function processShipping(idTransaksi, orderId) {
            $('#shippingIdTransaksi').val(idTransaksi);
            $('#shippingOrderId').text(orderId);
            $('#shippingModal').modal('show');
        }

        // Function to show delivery confirmation modal
        function confirmDelivery(idPengiriman, orderId) {
            $('#confirmIdPengiriman').val(idPengiriman);
            $('#confirmOrderId').text(orderId);
            $('#confirmDeliveryModal').modal('show');
        }

        // Handle shipping form submission
        $('#shippingForm').on('submit', function(e) {
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

        // Handle confirm delivery form submission
        $('#confirmDeliveryForm').on('submit', function(e) {
            let submitBtn = $(this).find('button[type="submit"]');
            let originalText = submitBtn.html();

            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengkonfirmasi...');

            // Allow form to submit naturally
            setTimeout(function() {
                if (submitBtn.length) {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalText);
                }
            }, 3000);
        });

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

        // Enhanced search functionality
        $('#table-1_filter input').on('keyup', function() {
            let searchTerm = $(this).val().toLowerCase();
            if (searchTerm.length > 2) {
                $('#table-1 tbody tr').each(function() {
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
        $('#viewModal, #shippingModal, #confirmDeliveryModal').on('shown.bs.modal', function() {
            $(this).find('[data-dismiss="modal"]').first().focus();
        });

        // Error handling for images
        $('.product-image, .item-image').on('error', function() {
            $(this).attr('src', '../../assets/img/no-image.png');
            $(this).addClass('bg-light');
        });

        // Add loading state for actions
        $('button[onclick^="viewTransaction"], button[onclick^="printInvoice"], button[onclick^="processShipping"], button[onclick^="confirmDelivery"]').on('click', function() {
            let $btn = $(this);
            let originalHtml = $btn.html();

            $btn.prop('disabled', true);
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');

            setTimeout(function() {
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }, 1000);
        });

        // Statistics counter animation
        function animateCounter(element, target) {
            let current = 0;
            let increment = target / 50;
            let timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                $(element).text(Math.floor(current));
            }, 20);
        }

        // Initialize counter animation for total transactions
        let totalTransactions = <?php echo count($grouped_transactions); ?>;
        if (totalTransactions > 0) {
            animateCounter('.badge-info', totalTransactions);
        }

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
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
                $('#shippingModal').modal('hide');
                $('#confirmDeliveryModal').modal('hide');
            }
        });

        // Touch/mobile enhancements
        if ('ontouchstart' in window) {
            $('.btn-group .btn').addClass('btn-lg');
            $('.table-responsive').css('overflow-x', 'auto');
        }

        // Accessibility improvements
        $('button[title]').attr('aria-label', function() {
            return $(this).attr('title');
        });

        // Notification system enhancement
        function showNotification(title, message, type = 'info') {
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

            setTimeout(function() {
                $('.alert-container .alert').last().fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Refresh page after successful form submission
        <?php if (isset($_SESSION['alert_type']) && $_SESSION['alert_type'] === 'success'): ?>
            setTimeout(function() {
                location.reload();
            }, 2000);
        <?php endif; ?>

        // Console info for debugging
        console.log('🚀 Enhanced Transaction Management System Loaded');
        console.log('📊 Total Active Transactions:', totalTransactions);
        console.log('🔧 DataTable initialized with responsive design');
        console.log('📦 Product details and enhanced shipping actions integrated successfully');
        console.log('✅ Delivery confirmation system active');
    </script>

    <!-- Additional CSS for enhanced mobile responsiveness -->
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

            .status-badge {
                font-size: 0.65em;
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

        /* Status badge animations */
        .badge,
        .status-badge {
            transition: all 0.3s ease;
        }

        .badge:hover,
        .status-badge:hover {
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

        /* Card hover effects */
        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Item row hover effects */
        .item-row {
            transition: background-color 0.2s ease;
        }

        .item-row:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        /* Enhanced status badge styling */
        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffc107, #ffcd39);
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
        }

        .status-dikirim {
            background: linear-gradient(135deg, #007bff, #0056b3);
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        }

        .status-terkirim {
            background: linear-gradient(135deg, #fd7e14, #e55a00);
            box-shadow: 0 2px 4px rgba(253, 126, 20, 0.3);
        }

        .status-selesai {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
        }

        /* Modal improvements */
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: none;
            border-radius: 15px 15px 0 0;
        }

        .modal-footer {
            border-top: none;
            border-radius: 0 0 15px 15px;
        }

        /* Button group improvements */
        .btn-group .btn:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .btn-group .btn:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
    </style>

</body>

</html>