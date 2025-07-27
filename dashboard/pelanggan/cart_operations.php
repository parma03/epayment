<?php
// cart_operations.php - FILE BARU
session_start();
include '../../db/koneksi.php';

header('Content-Type: application/json');

try {
    // Validasi user login
    if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
        throw new Exception('Anda harus login terlebih dahulu');
    }

    $user_id = $_SESSION['id_user'];
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']) ?: 1;

            // Validasi produk
            $stmt = $pdo->prepare("SELECT * FROM tb_barang WHERE id_barang = ? AND stok_barang > 0");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception('Produk tidak ditemukan atau stok habis');
            }

            // Cek apakah produk sudah ada di keranjang
            $stmt = $pdo->prepare("SELECT * FROM tb_keranjang WHERE id_user = ? AND id_barang = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing_item = $stmt->fetch();

            if ($existing_item) {
                // Update quantity
                $new_quantity = $existing_item['jumlah'] + $quantity;

                // Validasi stok
                if ($new_quantity > $product['stok_barang']) {
                    throw new Exception('Jumlah melebihi stok yang tersedia. Stok: ' . $product['stok_barang']);
                }

                $stmt = $pdo->prepare("UPDATE tb_keranjang SET jumlah = ?, updated_at = NOW() WHERE id_keranjang = ?");
                $stmt->execute([$new_quantity, $existing_item['id_keranjang']]);
            } else {
                // Validasi stok
                if ($quantity > $product['stok_barang']) {
                    throw new Exception('Jumlah melebihi stok yang tersedia. Stok: ' . $product['stok_barang']);
                }

                // Insert new item
                $stmt = $pdo->prepare("INSERT INTO tb_keranjang (id_user, id_barang, jumlah, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }

            // Get cart count
            $stmt = $pdo->prepare("SELECT SUM(jumlah) as total FROM tb_keranjang WHERE id_user = ?");
            $stmt->execute([$user_id]);
            $cart_count = $stmt->fetchColumn() ?: 0;

            echo json_encode([
                'status' => 'success',
                'message' => 'Produk berhasil ditambahkan ke keranjang',
                'cart_count' => $cart_count
            ]);
            break;

        case 'update':
            $cart_id = intval($_POST['cart_id']);
            $quantity = intval($_POST['quantity']);

            if ($quantity <= 0) {
                throw new Exception('Jumlah harus lebih dari 0');
            }

            // Validasi ownership dan get product info
            $stmt = $pdo->prepare("
                SELECT k.*, b.stok_barang 
                FROM tb_keranjang k 
                JOIN tb_barang b ON k.id_barang = b.id_barang 
                WHERE k.id_keranjang = ? AND k.id_user = ?
            ");
            $stmt->execute([$cart_id, $user_id]);
            $cart_item = $stmt->fetch();

            if (!$cart_item) {
                throw new Exception('Item keranjang tidak ditemukan');
            }

            if ($quantity > $cart_item['stok_barang']) {
                throw new Exception('Jumlah melebihi stok yang tersedia. Stok: ' . $cart_item['stok_barang']);
            }

            $stmt = $pdo->prepare("UPDATE tb_keranjang SET jumlah = ?, updated_at = NOW() WHERE id_keranjang = ?");
            $stmt->execute([$quantity, $cart_id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Keranjang berhasil diupdate'
            ]);
            break;

        case 'remove':
            $cart_id = intval($_POST['cart_id']);

            // Validasi ownership
            $stmt = $pdo->prepare("SELECT id_keranjang FROM tb_keranjang WHERE id_keranjang = ? AND id_user = ?");
            $stmt->execute([$cart_id, $user_id]);

            if (!$stmt->fetch()) {
                throw new Exception('Item keranjang tidak ditemukan');
            }

            $stmt = $pdo->prepare("DELETE FROM tb_keranjang WHERE id_keranjang = ?");
            $stmt->execute([$cart_id]);

            // Get updated cart count
            $stmt = $pdo->prepare("SELECT SUM(jumlah) as total FROM tb_keranjang WHERE id_user = ?");
            $stmt->execute([$user_id]);
            $cart_count = $stmt->fetchColumn() ?: 0;

            echo json_encode([
                'status' => 'success',
                'message' => 'Item berhasil dihapus dari keranjang',
                'cart_count' => $cart_count
            ]);
            break;

        case 'get':
            $stmt = $pdo->prepare("
                SELECT k.*, b.nama_barang, b.harga_barang, b.photo_barang, b.stok_barang,
                       (k.jumlah * b.harga_barang) as subtotal
                FROM tb_keranjang k
                JOIN tb_barang b ON k.id_barang = b.id_barang
                WHERE k.id_user = ?
                ORDER BY k.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $cart_items = $stmt->fetchAll();

            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item['subtotal'];
            }

            echo json_encode([
                'status' => 'success',
                'items' => $cart_items,
                'total_amount' => $total_amount,
                'total_items' => count($cart_items)
            ]);
            break;

        case 'clear':
            $stmt = $pdo->prepare("DELETE FROM tb_keranjang WHERE id_user = ?");
            $stmt->execute([$user_id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Keranjang berhasil dikosongkan',
                'cart_count' => 0
            ]);
            break;

        default:
            throw new Exception('Action tidak valid');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
