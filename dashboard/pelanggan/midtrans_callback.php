<?php
include '../../db/koneksi.php';

// Include Midtrans Config
require_once '../../vendor/midtrans/midtrans-php/Midtrans.php';

// Midtrans Configuration
\Midtrans\Config::$serverKey = 'Mid-server-Cb96pXISJY2A3GnsGPcM-349'; // Ganti dengan Server Key Anda
\Midtrans\Config::$isProduction = false; // Set to true for production
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

try {
    $notif = new \Midtrans\Notification();

    $transaction = $notif->transaction_status;
    $type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;

    $status = '';
    $should_save_transaction = false;

    // Update status pembayaran berdasarkan response Midtrans
    if ($transaction == 'capture') {
        // For credit card transaction, we need to check whether transaction is challenge by FDS or not
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                // TODO set payment status in merchant's database to 'Challenge by FDS'
                $status = 'pending';
            } else {
                // TODO set payment status in merchant's database to 'Success'
                $status = 'paid';
                $should_save_transaction = true;
            }
        }
    } else if ($transaction == 'settlement') {
        // TODO set payment status in merchant's database to 'Success'
        $status = 'paid';
        $should_save_transaction = true;
    } else if ($transaction == 'pending') {
        // TODO set payment status in merchant's database to 'Pending'
        $status = 'pending';
    } else if ($transaction == 'deny') {
        // TODO set payment status in merchant's database to 'Denied'
        $status = 'failed';
    } else if ($transaction == 'expire') {
        // TODO set payment status in merchant's database to 'Expired'
        $status = 'failed';
    } else if ($transaction == 'cancel') {
        // TODO set payment status in merchant's database to 'Canceled'
        $status = 'cancelled';
    }

    // Jika pembayaran berhasil, simpan transaksi ke database
    if ($should_save_transaction) {
        saveTransactionToDatabase($order_id, $status, $pdo);
        updateStock($order_id, $pdo);
    } else {
        // Untuk status lain, hanya update status jika transaksi sudah ada di database
        updateTransactionStatus($order_id, $status, $pdo);
    }

    echo "OK";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

function saveTransactionToDatabase($order_id, $status, $pdo)
{
    try {
        // Cek apakah transaksi sudah ada di database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $exists = $stmt->fetchColumn();

        if ($exists == 0) {
            // Transaksi belum ada, perlu dicari dari session atau sumber lain
            // Untuk callback Midtrans, kita tidak bisa akses session, jadi perlu alternatif
            // Opsi 1: Simpan data pending ke tabel terpisah
            // Opsi 2: Decode dari order_id jika mengandung info
            // Opsi 3: Simpan ke temporary table saat create snap token

            // Untuk sementara, kita akan log error ini
            error_log("Transaction data not found for order_id: " . $order_id);
            return false;
        } else {
            // Update status jika sudah ada
            $stmt = $pdo->prepare("UPDATE tb_transaksi SET status_pembayaran = ?, updated_at = NOW() WHERE order_id = ?");
            $stmt->execute([$status, $order_id]);
        }

        return true;
    } catch (Exception $e) {
        error_log('Error saving transaction: ' . $e->getMessage());
        return false;
    }
}

function updateTransactionStatus($order_id, $status, $pdo)
{
    try {
        // Update status di database hanya jika transaksi sudah ada
        $stmt = $pdo->prepare("UPDATE tb_transaksi SET status_pembayaran = ?, updated_at = NOW() WHERE order_id = ?");
        $result = $stmt->execute([$status, $order_id]);

        if ($stmt->rowCount() == 0) {
            error_log("No transaction found to update for order_id: " . $order_id);
        }

        return $result;
    } catch (Exception $e) {
        error_log('Error updating transaction status: ' . $e->getMessage());
        return false;
    }
}

function updateStock($order_id, $pdo)
{
    try {
        // Ambil data transaksi
        $stmt = $pdo->prepare("SELECT id_barang, jumlah_beli FROM tb_transaksi WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $transaction = $stmt->fetch();

        if ($transaction) {
            // Update stok barang
            $stmt = $pdo->prepare("UPDATE tb_barang SET stok_barang = stok_barang - ? WHERE id_barang = ?");
            $stmt->execute([$transaction['jumlah_beli'], $transaction['id_barang']]);
        }
    } catch (Exception $e) {
        // Log error jika diperlukan
        error_log('Error updating stock: ' . $e->getMessage());
    }
}
?>