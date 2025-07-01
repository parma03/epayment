<?php
include '../../db/koneksi.php';
require_once '../../config/midtrans_config.php';

// Include Midtrans Library
require_once '../../vendor/midtrans/midtrans-php/Midtrans.php';

// Log all incoming data for debugging
error_log("Midtrans Notification received: " . file_get_contents('php://input'));

try {
    // Initialize Midtrans configuration
    MidtransConfig::init();

    $notif = new \Midtrans\Notification();

    $transaction = $notif->transaction_status;
    $type = $notif->payment_type;
    $order_id = $notif->order_id;
    $fraud = $notif->fraud_status;

    // Log notification details
    error_log("Processing notification - Order ID: $order_id, Status: $transaction, Type: $type, Fraud: $fraud");

    $status = '';
    $should_update_stock = false;

    // Update status pembayaran berdasarkan response Midtrans
    if ($transaction == 'capture') {
        // For credit card transaction, we need to check whether transaction is challenge by FDS or not
        if ($type == 'credit_card') {
            if ($fraud == 'challenge') {
                $status = 'pending';
            } else {
                $status = 'paid';
                $should_update_stock = true;
            }
        }
    } else if ($transaction == 'settlement') {
        $status = 'paid';
        $should_update_stock = true;
    } else if ($transaction == 'pending') {
        $status = 'pending';
    } else if ($transaction == 'deny') {
        $status = 'failed';
    } else if ($transaction == 'expire') {
        $status = 'expired';
    } else if ($transaction == 'cancel') {
        $status = 'cancelled';
    }

    // Update transaction status in database
    $updated = updateTransactionStatus($order_id, $status, $pdo);

    if ($updated && $should_update_stock) {
        updateStock($order_id, $pdo);
        error_log("Stock updated for order: $order_id");
    }

    error_log("Transaction status updated - Order ID: $order_id, New Status: $status");
    echo "OK";

} catch (Exception $e) {
    error_log('Notification Handler Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}

function updateTransactionStatus($order_id, $status, $pdo)
{
    try {
        // Update status di database
        $stmt = $pdo->prepare("UPDATE tb_transaksi SET status_pembayaran = ?, updated_at = NOW() WHERE order_id = ?");
        $result = $stmt->execute([$status, $order_id]);

        if ($stmt->rowCount() == 0) {
            error_log("No transaction found to update for order_id: " . $order_id);
            return false;
        }

        error_log("Transaction status updated successfully for order_id: " . $order_id . " to status: " . $status);
        return true;
    } catch (Exception $e) {
        error_log('Error updating transaction status: ' . $e->getMessage());
        return false;
    }
}

function updateStock($order_id, $pdo)
{
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Ambil data transaksi
        $stmt = $pdo->prepare("SELECT id_barang, jumlah_beli FROM tb_transaksi WHERE order_id = ? AND status_pembayaran = 'paid'");
        $stmt->execute([$order_id]);
        $transaction = $stmt->fetch();

        if ($transaction) {
            // Check current stock
            $stmt = $pdo->prepare("SELECT stok_barang FROM tb_barang WHERE id_barang = ?");
            $stmt->execute([$transaction['id_barang']]);
            $current_stock = $stmt->fetchColumn();

            if ($current_stock >= $transaction['jumlah_beli']) {
                // Update stok barang
                $stmt = $pdo->prepare("UPDATE tb_barang SET stok_barang = stok_barang - ? WHERE id_barang = ?");
                $stmt->execute([$transaction['jumlah_beli'], $transaction['id_barang']]);

                error_log("Stock updated for product ID: " . $transaction['id_barang'] . ", reduced by: " . $transaction['jumlah_beli']);
            } else {
                error_log("Insufficient stock for product ID: " . $transaction['id_barang']);
            }
        }

        // Commit transaction
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        error_log('Error updating stock: ' . $e->getMessage());
        return false;
    }
}
?>