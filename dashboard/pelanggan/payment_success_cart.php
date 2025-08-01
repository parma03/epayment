<?php
// payment_success_cart.php - MODIFIED VERSION
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
    if (!isset($_SESSION['pending_cart_transaction']) || $_SESSION['pending_cart_transaction']['order_id'] !== $order_id) {
        throw new Exception('Transaction data not found in session');
    }

    $transaction_data = $_SESSION['pending_cart_transaction'];

    // Proses jika pembayaran berhasil
    if ($transaction_status === 'success') {
        try {
            $pdo->beginTransaction();

            // Validasi ulang stok untuk setiap item sebelum menyimpan
            foreach ($transaction_data['cart_items'] as $item) {
                $stmt = $pdo->prepare("SELECT stok_barang FROM tb_barang WHERE id_barang = ?");
                $stmt->execute([$item['id_barang']]);
                $current_stock = $stmt->fetchColumn();

                if ($current_stock < $item['jumlah']) {
                    throw new Exception('Stok ' . $item['nama_barang'] . ' tidak mencukupi');
                }
            }

            // MODIFIKASI: Simpan SATU transaksi utama untuk keseluruhan keranjang
            $stmt = $pdo->prepare("
                INSERT INTO tb_transaksi 
                (id_user, nama_pemesan, nohp_pemesan, alamat_pemesan, 
                 total_harga, status_pembayaran, order_id, snap_token, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $transaction_data['user_id'],
                $transaction_data['nama_pemesan'],
                $transaction_data['nohp_pemesan'],
                $transaction_data['alamat_pemesan'],
                $transaction_data['total_amount'], // Total keseluruhan
                'paid',
                $transaction_data['order_id'],
                $transaction_data['snap_token']
            ]);

            // Dapatkan ID transaksi yang baru dibuat
            $id_transaksi = $pdo->lastInsertId();

            // MODIFIKASI: Simpan detail setiap item ke tabel tb_transaksi_detail
            foreach ($transaction_data['cart_items'] as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO tb_transaksi_detail 
                    (id_transaksi, id_barang, nama_barang, harga_barang, jumlah_beli, subtotal, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    $id_transaksi,
                    $item['id_barang'],
                    $item['nama_barang'],
                    $item['harga_barang'],
                    $item['jumlah'],
                    $item['subtotal']
                ]);

                // Update stok barang
                $stmt = $pdo->prepare("UPDATE tb_barang SET stok_barang = stok_barang - ? WHERE id_barang = ?");
                $stmt->execute([$item['jumlah'], $item['id_barang']]);
            }

            // Kosongkan keranjang setelah pembayaran berhasil
            $stmt = $pdo->prepare("DELETE FROM tb_keranjang WHERE id_user = ?");
            $stmt->execute([$transaction_data['user_id']]);

            $pdo->commit();

            // Hapus data transaksi dari session
            unset($_SESSION['pending_cart_transaction']);

            echo json_encode([
                'status' => 'success',
                'message' => 'Transaction saved successfully',
                'transaction_id' => $id_transaksi
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } else {
        // Untuk status pending, error, atau cancel, hapus data dari session
        unset($_SESSION['pending_cart_transaction']);

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
