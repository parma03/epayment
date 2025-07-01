<?php
session_start();
include '../../db/koneksi.php';
require_once '../../config/midtrans_config.php';

// Include Midtrans Config
require_once '../../vendor/midtrans/midtrans-php/Midtrans.php';


header('Content-Type: application/json');

try {
    MidtransConfig::init();
    // Validasi input
    if (
        !isset(
        $_POST['product_id'],
        $_POST['nama_pemesan'],
        $_POST['nohp_pemesan'],
        $_POST['alamat_pemesan'],
        $_POST['jumlah_beli'],
        $_POST['total_harga']
    )
    ) {
        throw new Exception('Data tidak lengkap');
    }

    // VALIDASI ID_USER MANDATORY - HARUS LOGIN
    if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user']) || $_SESSION['id_user'] == 0) {
        // Set session alert untuk ditampilkan di halaman utama
        $_SESSION['alert_message'] = 'Anda harus login terlebih dahulu untuk melakukan pembelian!';
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Login Diperlukan';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';

        throw new Exception('Anda harus login terlebih dahulu untuk melakukan pembelian!');
    }

    $product_id = intval($_POST['product_id']);
    $nama_pemesan = trim($_POST['nama_pemesan']);
    $nohp_pemesan = trim($_POST['nohp_pemesan']);
    $alamat_pemesan = trim($_POST['alamat_pemesan']);
    $jumlah_beli = intval($_POST['jumlah_beli']);
    $total_harga = floatval($_POST['total_harga']);
    $user_id = $_SESSION['id_user']; // Ambil dari session yang sudah divalidasi

    // Validasi produk dan stok
    $stmt = $pdo->prepare("SELECT * FROM tb_barang WHERE id_barang = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['alert_message'] = 'Produk yang dipilih tidak ditemukan!';
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Produk Tidak Ditemukan';
        $_SESSION['alert_icon'] = 'fas fa-times-circle';

        throw new Exception('Produk tidak ditemukan');
    }

    if ($product['stok_barang'] < $jumlah_beli) {
        $_SESSION['alert_message'] = 'Stok produk tidak mencukupi! Stok tersedia: ' . $product['stok_barang'];
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Stok Tidak Mencukupi';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';

        throw new Exception('Stok tidak mencukupi');
    }

    // Validasi harga
    $expected_total = $product['harga_barang'] * $jumlah_beli;
    if (abs($total_harga - $expected_total) > 0.01) {
        $_SESSION['alert_message'] = 'Total harga tidak sesuai dengan perhitungan sistem!';
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Error Perhitungan';
        $_SESSION['alert_icon'] = 'fas fa-calculator';

        throw new Exception('Total harga tidak sesuai');
    }

    // Generate order ID
    $order_id = 'ORDER-' . time() . '-' . rand(1000, 9999);

    // Prepare transaction data untuk Midtrans
    $transaction_details = array(
        'order_id' => $order_id,
        'gross_amount' => (int) $total_harga,
    );

    $item_details = array(
        array(
            'id' => $product['id_barang'],
            'price' => (int) $product['harga_barang'],
            'quantity' => $jumlah_beli,
            'name' => $product['nama_barang']
        )
    );

    $customer_details = array(
        'first_name' => $nama_pemesan,
        'phone' => $nohp_pemesan,
        'shipping_address' => array(
            'address' => $alamat_pemesan,
            'phone' => $nohp_pemesan,
        )
    );

    $transaction = array(
        'transaction_details' => $transaction_details,
        'customer_details' => $customer_details,
        'item_details' => $item_details
    );

    // Get Snap Token
    $snapToken = \Midtrans\Snap::getSnapToken($transaction);

    // SIMPAN DATA TRANSAKSI SEMENTARA KE SESSION (bukan ke database)
    $_SESSION['pending_transaction'] = [
        'product_id' => $product_id,
        'user_id' => $user_id,
        'nama_pemesan' => $nama_pemesan,
        'nohp_pemesan' => $nohp_pemesan,
        'alamat_pemesan' => $alamat_pemesan,
        'jumlah_beli' => $jumlah_beli,
        'total_harga' => $total_harga,
        'order_id' => $order_id,
        'snap_token' => $snapToken
    ];

    // Set alert sukses
    $_SESSION['alert_message'] = 'Transaksi berhasil dibuat! Silakan lanjutkan pembayaran.';
    $_SESSION['alert_type'] = 'success';
    $_SESSION['alert_title'] = 'Transaksi Berhasil';
    $_SESSION['alert_icon'] = 'fas fa-check-circle';

    // Response sukses
    echo json_encode([
        'status' => 'success',
        'message' => 'Transaction created successfully',
        'snap_token' => $snapToken,
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Jika belum ada alert session yang diset, set alert umum
    if (!isset($_SESSION['alert_message'])) {
        $_SESSION['alert_message'] = $e->getMessage();
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Error';
        $_SESSION['alert_icon'] = 'fas fa-times-circle';
    }

    // Response error
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'redirect' => true // Tambahkan flag untuk redirect
    ]);
}
?>