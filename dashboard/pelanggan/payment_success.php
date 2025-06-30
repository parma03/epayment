<?php
session_start();
include '../../db/koneksi.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['order_id']) || !isset($_POST['transaction_status'])) {
        throw new Exception('Missing required parameters');
    }

    $order_id = $_POST['order_id'];
    $transaction_status = $_POST['transaction_status'];

    // Validasi bahwa user sudah login
    if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
        throw new Exception('User not logged in');
    }

    // Ambil data transaksi dari session
    if (!isset($_SESSION['pending_transaction']) || $_SESSION['pending_transaction']['order_id'] !== $order_id) {
        throw new Exception('Transaction data not found in session');
    }

    $transaction_data = $_SESSION['pending_transaction'];

    // Validasi ulang bahwa stok masih tersedia
    $stmt = $pdo->prepare("SELECT stok_barang FROM tb_barang WHERE id_barang = ?");
    $stmt->execute([$transaction_data['product_id']]);
    $current_stock = $stmt->fetchColumn();

    if ($current_stock < $transaction_data['jumlah_beli']) {
        throw new Exception('Insufficient stock available');
    }

    // Simpan transaksi ke database setelah pembayaran berhasil
    if ($transaction_status === 'success') {
        $stmt = $pdo->prepare("
            INSERT INTO tb_transaksi 
            (id_barang, id_user, nama_pemesan, nohp_pemesan, alamat_pemesan, 
             jumlah_beli, total_harga, status_pembayaran, order_id, snap_token, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $transaction_data['product_id'],
            $transaction_data['user_id'],
            $transaction_data['nama_pemesan'],
            $transaction_data['nohp_pemesan'],
            $transaction_data['alamat_pemesan'],
            $transaction_data['jumlah_beli'],
            $transaction_data['total_harga'],
            'paid',
            $transaction_data['order_id'],
            $transaction_data['snap_token']
        ]);

        // Update stok barang
        $stmt = $pdo->prepare("UPDATE tb_barang SET stok_barang = stok_barang - ? WHERE id_barang = ?");
        $stmt->execute([$transaction_data['jumlah_beli'], $transaction_data['product_id']]);

        // Hapus data transaksi dari session
        unset($_SESSION['pending_transaction']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Transaction saved successfully'
        ]);
    } else {
        // Untuk status pending, error, atau cancel, hapus data dari session
        unset($_SESSION['pending_transaction']);

        echo json_encode([
            'status' => 'info',
            'message' => 'Transaction status: ' . $transaction_status
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>