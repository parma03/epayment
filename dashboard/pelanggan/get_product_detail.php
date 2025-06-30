<?php
include '../../db/koneksi.php';

if (!isset($_GET['id'])) {
    die('Product ID not provided');
}

$id = $_GET['id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

try {
    $stmt = $pdo->prepare("SELECT * FROM tb_barang WHERE id_barang = ? AND stok_barang > 0");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        die('Product not found or out of stock');
    }

    if ($action === 'buy') {
        // Return JSON for buy action
        header('Content-Type: application/json');
        echo json_encode($product);
        exit;
    }

    // Return HTML for view action
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="product-image-detail">
                <?php if (!empty($product['photo_barang'])): ?>
                    <img src="../../uploads/<?php echo $product['photo_barang']; ?>"
                        alt="<?php echo htmlspecialchars($product['nama_barang']); ?>" class="img-fluid rounded">
                <?php else: ?>
                    <img src="../../assets/img/products/product-default.png" alt="No Image" class="img-fluid rounded">
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <h3><?php echo htmlspecialchars($product['nama_barang']); ?></h3>
            <div class="mb-3">
                <span class="badge badge-primary">Stok: <?php echo $product['stok_barang']; ?></span>
            </div>
            <div class="price-large mb-3">
                <h4 class="text-primary">Rp <?php echo number_format($product['harga_barang'], 0, ',', '.'); ?></h4>
            </div>
            <div class="product-description">
                <h5>Deskripsi Produk</h5>
                <p><?php echo nl2br(htmlspecialchars($product['deskripsi_barang'])); ?></p>
            </div>
            <div class="product-actions mt-4">
                <button type="button" class="btn btn-primary btn-lg btn-block"
                    onclick="$('#productModal').modal('hide'); buyProduct(<?php echo $product['id_barang']; ?>);">
                    <i class="fas fa-shopping-cart"></i> Beli Sekarang
                </button>
            </div>
        </div>
    </div>

    <style>
        .product-image-detail img {
            max-height: 400px;
            width: 100%;
            object-fit: cover;
        }

        .price-large {
            border-left: 4px solid #6777ef;
            padding-left: 15px;
        }

        .product-description {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
    <?php

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>