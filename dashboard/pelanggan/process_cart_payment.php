<?php
// process_cart_payment.php - FILE BARU
session_start();
include '../../db/koneksi.php';
require_once '../../config/midtrans_config.php';
require_once '../../vendor/midtrans/midtrans-php/Midtrans.php';

header('Content-Type: application/json');

try {
    MidtransConfig::init();

    // Validasi input
    if (!isset($_POST['nama_pemesan'], $_POST['nohp_pemesan'], $_POST['alamat_pemesan'])) {
        throw new Exception('Data tidak lengkap');
    }

    // Validasi user login
    if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
        $_SESSION['alert_message'] = 'Anda harus login terlebih dahulu untuk melakukan pembelian!';
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Login Diperlukan';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
        throw new Exception('Anda harus login terlebih dahulu untuk melakukan pembelian!');
    }

    $user_id = $_SESSION['id_user'];
    $nama_pemesan = trim($_POST['nama_pemesan']);
    $nohp_pemesan = trim($_POST['nohp_pemesan']);
    $alamat_pemesan = trim($_POST['alamat_pemesan']);

    // Ambil data keranjang user
    $stmt = $pdo->prepare("
        SELECT k.*, b.nama_barang, b.harga_barang, b.stok_barang,
               (k.jumlah * b.harga_barang) as subtotal
        FROM tb_keranjang k
        JOIN tb_barang b ON k.id_barang = b.id_barang
        WHERE k.id_user = ?
        ORDER BY k.created_at ASC
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    if (empty($cart_items)) {
        $_SESSION['alert_message'] = 'Keranjang kosong! Silakan tambahkan produk terlebih dahulu.';
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Keranjang Kosong';
        $_SESSION['alert_icon'] = 'fas fa-shopping-cart';
        throw new Exception('Keranjang kosong');
    }

    // Validasi stok untuk setiap item
    $total_amount = 0;
    $item_details = [];
    $out_of_stock = [];

    foreach ($cart_items as $item) {
        if ($item['stok_barang'] < $item['jumlah']) {
            $out_of_stock[] = $item['nama_barang'] . ' (tersedia: ' . $item['stok_barang'] . ', diminta: ' . $item['jumlah'] . ')';
            continue;
        }

        $total_amount += $item['subtotal'];
        $item_details[] = [
            'id' => $item['id_barang'],
            'price' => (int) $item['harga_barang'],
            'quantity' => $item['jumlah'],
            'name' => substr($item['nama_barang'], 0, 50) // Midtrans limit nama produk
        ];
    }

    if (!empty($out_of_stock)) {
        $_SESSION['alert_message'] = 'Stok tidak mencukupi untuk: ' . implode(', ', $out_of_stock);
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Stok Tidak Mencukupi';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
        throw new Exception('Stok tidak mencukupi');
    }

    if ($total_amount <= 0) {
        throw new Exception('Total harga tidak valid');
    }

    // Generate order ID
    $order_id = 'CART-' . time() . '-' . rand(1000, 9999);

    // Prepare transaction data untuk Midtrans
    $transaction_details = [
        'order_id' => $order_id,
        'gross_amount' => (int) $total_amount,
    ];

    $customer_details = [
        'first_name' => $nama_pemesan,
        'phone' => $nohp_pemesan,
        'shipping_address' => [
            'address' => $alamat_pemesan,
            'phone' => $nohp_pemesan,
        ]
    ];

    $transaction = [
        'transaction_details' => $transaction_details,
        'customer_details' => $customer_details,
        'item_details' => $item_details
    ];

    // Get Snap Token
    $snapToken = \Midtrans\Snap::getSnapToken($transaction);

    // Simpan data transaksi ke session
    $_SESSION['pending_cart_transaction'] = [
        'user_id' => $user_id,
        'nama_pemesan' => $nama_pemesan,
        'nohp_pemesan' => $nohp_pemesan,
        'alamat_pemesan' => $alamat_pemesan,
        'cart_items' => $cart_items,
        'total_amount' => $total_amount,
        'order_id' => $order_id,
        'snap_token' => $snapToken
    ];

    // Set alert sukses
    $_SESSION['alert_message'] = 'Transaksi berhasil dibuat! Silakan lanjutkan pembayaran.';
    $_SESSION['alert_type'] = 'success';
    $_SESSION['alert_title'] = 'Transaksi Berhasil';
    $_SESSION['alert_icon'] = 'fas fa-check-circle';

    echo json_encode([
        'status' => 'success',
        'message' => 'Transaction created successfully',
        'snap_token' => $snapToken,
        'order_id' => $order_id
    ]);
} catch (Exception $e) {
    if (!isset($_SESSION['alert_message'])) {
        $_SESSION['alert_message'] = $e->getMessage();
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Error';
        $_SESSION['alert_icon'] = 'fas fa-times-circle';
    }

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'redirect' => true
    ]);
}
