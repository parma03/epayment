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
    } else if ($_SESSION['role'] === 'Pelanggan') {
        header("Location: ../dashboard/pelanggan/index.php");
        exit();
    }
}
// Process CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $nama_barang = $_POST['nama_barang'];
                    $deskripsi_barang = $_POST['deskripsi_barang'];
                    $stok_barang = $_POST['stok_barang'];
                    $harga_barang = str_replace(['Rp', '.', ' '], '', $_POST['harga_barang']); // Remove formatting
                    $photo_barang = null;

                    // Handle file upload
                    if (isset($_FILES['photo_barang']) && $_FILES['photo_barang']['error'] == 0) {
                        $upload_dir = '../../assets/img/products/';
                        $file_extension = pathinfo($_FILES['photo_barang']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['photo_barang']['tmp_name'], $upload_path)) {
                            $photo_barang = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO tb_barang (nama_barang, deskripsi_barang, stok_barang, harga_barang, photo_barang, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$nama_barang, $deskripsi_barang, $stok_barang, $harga_barang, $photo_barang]);
                    break;

                case 'update':
                    $id_barang = $_POST['id_barang'];
                    $nama_barang = $_POST['nama_barang'];
                    $deskripsi_barang = $_POST['deskripsi_barang'];
                    $stok_barang = $_POST['stok_barang'];
                    $harga_barang = str_replace(['Rp', '.', ' '], '', $_POST['harga_barang']); // Remove formatting
                    $old_photo = $_POST['old_photo'];
                    $photo_barang = $old_photo;

                    // Handle file upload
                    if (isset($_FILES['photo_barang']) && $_FILES['photo_barang']['error'] == 0) {
                        $upload_dir = '../../assets/img/products/';
                        $file_extension = pathinfo($_FILES['photo_barang']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['photo_barang']['tmp_name'], $upload_path)) {
                            // Delete old photo if exists
                            if ($old_photo && file_exists($upload_dir . $old_photo)) {
                                unlink($upload_dir . $old_photo);
                            }
                            $photo_barang = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE tb_barang SET nama_barang = ?, deskripsi_barang = ?, stok_barang = ?, harga_barang = ?, photo_barang = ?, updated_at = NOW() WHERE id_barang = ?");
                    $stmt->execute([$nama_barang, $deskripsi_barang, $stok_barang, $harga_barang, $photo_barang, $id_barang]);
                    break;

                case 'delete':
                    $id_barang = $_POST['id_barang'];

                    // Get photo filename before delete
                    $stmt = $pdo->prepare("SELECT photo_barang FROM tb_barang WHERE id_barang = ?");
                    $stmt->execute([$id_barang]);
                    $barang = $stmt->fetch();

                    // Delete barang
                    $stmt = $pdo->prepare("DELETE FROM tb_barang WHERE id_barang = ?");
                    $stmt->execute([$id_barang]);

                    // Delete photo file if exists
                    if ($barang['photo_barang'] && file_exists('../../assets/img/products/' . $barang['photo_barang'])) {
                        unlink('../../assets/img/products/' . $barang['photo_barang']);
                    }

                    $_SESSION['alert_message'] = 'Data Barang berhasil dihapus!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'fas fa-check-circle';
                    break;
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

        } catch (Exception $e) {
            $_SESSION['alert_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
            $_SESSION['alert_type'] = 'danger';
            $_SESSION['alert_title'] = 'Error!';
            $_SESSION['alert_icon'] = 'fas fa-exclamation-circle';
        }
    }
}

// Ambil alert dari session dan hapus setelah digunakan
$alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : '';
$alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : '';
$alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : '';
$alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : '';

// Hapus alert dari session setelah digunakan
unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);

// Fetch all Barang
$stmt = $pdo->prepare("SELECT * FROM tb_barang ORDER BY created_at DESC");
$stmt->execute();
$barangs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Barang Management &mdash; Stisla</title>

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
                        <h1><i class="fas fa-box mr-3"></i>Barang Management</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item active"><a href="index.php"><i
                                        class="fas fa-home mr-1"></i>Dashboard</a></div>
                            <div class="breadcrumb-item"><i class="fas fa-box mr-1"></i>Barang</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4><i class="fas fa-table mr-2"></i>Data Barang</h4>
                                        <div class="card-header-action">
                                            <button type="button" class="btn btn-primary btn-lg" data-toggle="modal"
                                                data-target="#addModal">
                                                <i class="fas fa-plus-circle mr-2"></i>Tambah Barang
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="table-1">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center"><i class="fas fa-hashtag"></i></th>
                                                        <th><i class="fas fa-image mr-1"></i>Photo</th>
                                                        <th><i class="fas fa-box mr-1"></i>Nama Barang</th>
                                                        <th><i class="fas fa-align-left mr-1"></i>Deskripsi</th>
                                                        <th><i class="fas fa-money-bill-wave mr-1"></i>Harga</th>
                                                        <th><i class="fas fa-cubes mr-1"></i>Stok</th>
                                                        <th><i class="fas fa-calendar-plus mr-1"></i>Created At</th>
                                                        <th><i class="fas fa-calendar-edit mr-1"></i>Updated At</th>
                                                        <th class="text-center"><i class="fas fa-cogs mr-1"></i>Action
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($barangs as $index => $barang): ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <span
                                                                    class="badge badge-secondary"><?php echo $index + 1; ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($barang['photo_barang']): ?>
                                                                    <img alt="product"
                                                                        src="../../assets/img/products/<?php echo $barang['photo_barang']; ?>"
                                                                        class="avatar-preview"
                                                                        title="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                                                                <?php else: ?>
                                                                    <img alt="product"
                                                                        src="../../assets/img/products/default-product.png"
                                                                        class="avatar-preview"
                                                                        title="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($barang['nama_barang']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted">
                                                                    <?php echo strlen($barang['deskripsi_barang']) > 50 ?
                                                                        htmlspecialchars(substr($barang['deskripsi_barang'], 0, 50)) . '...' :
                                                                        htmlspecialchars($barang['deskripsi_barang']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-success badge-lg">
                                                                    <i class="fas fa-money-bill-wave mr-1"></i>Rp
                                                                    <?php echo number_format($barang['harga_barang'], 0, ',', '.'); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="badge badge-<?php echo $barang['stok_barang'] > 10 ? 'success' : ($barang['stok_barang'] > 0 ? 'warning' : 'danger'); ?>">
                                                                    <i
                                                                        class="fas fa-cubes mr-1"></i><?php echo $barang['stok_barang']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-clock mr-1 text-muted"></i>
                                                                <?php echo $barang['created_at'] ? date('d/m/Y H:i', strtotime($barang['created_at'])) : '-'; ?>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-edit mr-1 text-muted"></i>
                                                                <?php echo $barang['updated_at'] ? date('d/m/Y H:i', strtotime($barang['updated_at'])) : '-'; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-warning btn-sm"
                                                                        onclick="editBarang(<?php echo htmlspecialchars(json_encode($barang)); ?>)"
                                                                        title="Edit Barang">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-primary btn-sm"
                                                                        onclick="viewBarang(<?php echo htmlspecialchars(json_encode($barang)); ?>)"
                                                                        title="Detail Barang">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-danger btn-sm"
                                                                        onclick="deleteBarang(<?php echo $barang['id_barang']; ?>, '<?php echo htmlspecialchars($barang['nama_barang']); ?>')"
                                                                        title="Hapus Barang">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>

                                                </tbody>
                                            </table>
                                        </div>
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

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="fas fa-box-plus mr-2"></i>Tambah Barang Baru
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-box mr-1"></i>Nama Barang</label>
                                    <input type="text" class="form-control" name="nama_barang" required
                                        placeholder="masukkan nama barang">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-cubes mr-1"></i>Stok Barang</label>
                                    <input type="number" class="form-control" name="stok_barang" required
                                        placeholder="masukkan jumlah stok" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave mr-1"></i>Harga Barang</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control currency-input" name="harga_barang" required
                                        placeholder="0" oninput="formatCurrency(this)">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left mr-1"></i>Deskripsi Barang</label>
                            <textarea class="form-control" name="deskripsi_barang" rows="4" required
                                placeholder="masukkan deskripsi barang"></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-camera mr-1"></i>Product Picture</label>
                            <div class="custom-file-upload">
                                <input type="file" class="form-control-file" name="photo_barang" accept="image/*"
                                    onchange="previewImage(this, 'addPreview')" id="addFileInput">
                                <div class="upload-text">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Klik untuk upload gambar atau drag & drop</p>
                                    <small class="text-muted">Format: JPG, PNG, GIF. Max: 2MB</small>
                                </div>
                            </div>
                            <img id="addPreview" class="avatar-modal" style="display: none;" alt="Preview">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-box-edit mr-2"></i>Edit Barang
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id_barang" id="edit_id_barang">
                        <input type="hidden" name="old_photo" id="edit_old_photo">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-box mr-1"></i>Nama Barang</label>
                                    <input type="text" class="form-control" name="nama_barang" id="edit_nama_barang"
                                        required placeholder="masukkan nama barang">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-cubes mr-1"></i>Stok Barang</label>
                                    <input type="number" class="form-control" name="stok_barang" id="edit_stok_barang"
                                        required placeholder="masukkan jumlah stok" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave mr-1"></i>Harga Barang</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control currency-input" name="harga_barang"
                                        id="edit_harga_barang" required placeholder="0" oninput="formatCurrency(this)">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left mr-1"></i>Deskripsi Barang</label>
                            <textarea class="form-control" name="deskripsi_barang" id="edit_deskripsi_barang" rows="4"
                                required placeholder="masukkan deskripsi barang"></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-camera mr-1"></i>Product Picture</label>
                            <div class="custom-file-upload">
                                <input type="file" class="form-control-file" name="photo_barang" accept="image/*"
                                    onchange="previewImage(this, 'editPreview')" id="editFileInput">
                                <div class="upload-text">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Klik untuk upload gambar atau drag & drop</p>
                                    <small class="text-muted">Format: JPG, PNG, GIF. Max: 2MB (Kosongkan jika tidak
                                        ingin mengubah)</small>
                                </div>
                            </div>
                            <img id="editPreview" class="avatar-modal" alt="Preview">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt mr-1"></i>Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Hapus Barang
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id_barang" id="delete_id_barang">

                        <div class="text-center mb-4">
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                                <h5 class="alert-heading">Peringatan!</h5>
                                <p class="mb-0">Anda akan menghapus Barang dengan nama:</p>
                                <strong class="text-danger h5" id="delete_nama_barang"></strong>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                        <h6 class="card-title text-danger">Tindakan Tidak Dapat Dibatalkan!</h6>
                                        <p class="card-text text-muted">
                                            Data Barang akan dihapus secara permanen dari sistem.
                                            Pastikan Anda benar-benar yakin dengan keputusan ini.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-trash-alt mr-1"></i>Ya, Hapus Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white" id="viewModalLabel">
                        <i class="fas fa-eye mr-2"></i>Detail Barang
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="viewProductImage" class="img-fluid rounded mb-3"
                                style="width: 200px; height: 200px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 8px 25px rgba(0,0,0,0.15);"
                                alt="Product Image">
                            <h5 id="viewNamaBarang" class="text-primary"></h5>
                            <span id="viewStokBadge" class="badge badge-primary badge-lg"></span>
                        </div>
                        <div class="col-md-8">
                            <div class="card border-0">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="fas fa-info-circle mr-2"></i>Informasi Detail
                                    </h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-box text-primary mr-2"></i>Nama Barang:</td>
                                            <td id="viewNamaBarangDetail"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-align-left text-success mr-2"></i>Deskripsi:</td>
                                            <td id="viewDeskripsiDetail"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-money-bill-wave text-success mr-2"></i>Harga:</td>
                                            <td id="viewHargaDetail"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-cubes text-warning mr-2"></i>Stok:</td>
                                            <td id="viewStokDetail"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-calendar-plus text-info mr-2"></i>Dibuat:</td>
                                            <td id="viewCreatedAt"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-calendar-edit text-secondary mr-2"></i>Diupdate:</td>
                                            <td id="viewUpdatedAt"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Tutup
                    </button>
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
        // Initialize DataTable with enhanced features
        $(document).ready(function () {
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#table-1')) {
                $('#table-1').DataTable().destroy();
            }

            // Initialize new DataTable with enhanced styling
            $("#table-1").DataTable({
                "columnDefs": [
                    { "orderable": false, "targets": [1, 8] }, // Photo and Action columns
                    { "className": "text-center", "targets": [0, 8] }
                ],
                "responsive": true,
                "pageLength": 10,
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"]],
                "order": [[5, "desc"]], // Sort by created_at descending
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
                },
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                "drawCallback": function (settings) {
                    // Add animation to table rows
                    $('.table tbody tr').addClass('animate__animated animate__fadeIn');
                }
            });

            // Add search enhancement
            $('.dataTables_filter input').addClass('form-control').attr('placeholder', 'Cari Barang...');
            $('.dataTables_length select').addClass('form-control form-control-sm');

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Enhanced preview image function with validation
        function previewImage(input, previewId) {
            const file = input.files[0];
            const preview = $('#' + previewId);

            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Format Tidak Valid!',
                        text: 'Silakan pilih file gambar dengan format JPG, PNG, atau GIF.',
                        confirmButtonColor: '#d33'
                    });
                    input.value = '';
                    preview.hide();
                    return;
                }

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ukuran File Terlalu Besar!',
                        text: 'Ukuran file maksimal 2MB.',
                        confirmButtonColor: '#d33'
                    });
                    input.value = '';
                    preview.hide();
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.attr('src', e.target.result).show().addClass('animate__animated animate__zoomIn');
                }
                reader.readAsDataURL(file);
            } else {
                preview.hide();
            }
        }

        // Enhanced edit barang function
        function editBarang(barang) {
            $('#edit_id_barang').val(barang.id_barang);
            $('#edit_nama_barang').val(barang.nama_barang);
            $('#edit_deskripsi_barang').val(barang.deskripsi_barang);
            $('#edit_stok_barang').val(barang.stok_barang);
            $('#edit_harga_barang').val(formatNumber(barang.harga_barang)); // Format harga
            $('#edit_old_photo').val(barang.photo_barang);

            // Show current photo with animation
            const editPreview = $('#editPreview');
            if (barang.photo_barang) {
                editPreview.attr('src', '../../assets/img/products/' + barang.photo_barang)
                    .show()
                    .addClass('animate__animated animate__fadeIn');
            } else {
                editPreview.attr('src', '../../assets/img/products/default-product.png')
                    .show()
                    .addClass('animate__animated animate__fadeIn');
            }

            // Show modal with animation
            $('#editModal').modal('show');
        }

        // Enhanced view barang function
        function viewBarang(barang) {
            $('#viewNamaBarang').text(barang.nama_barang);
            $('#viewNamaBarangDetail').text(barang.nama_barang);
            $('#viewDeskripsiDetail').text(barang.deskripsi_barang);
            $('#viewHargaDetail').html('<span class="badge badge-success badge-lg"><i class="fas fa-money-bill-wave mr-1"></i>Rp ' + formatNumber(barang.harga_barang) + '</span>');
            $('#viewStokDetail').text(barang.stok_barang + ' unit');

            // Set stock badge with color based on quantity
            let stockBadgeClass = 'badge-success';
            if (barang.stok_barang <= 0) {
                stockBadgeClass = 'badge-danger';
            } else if (barang.stok_barang <= 10) {
                stockBadgeClass = 'badge-warning';
            }
            $('#viewStokBadge').html('<i class="fas fa-cubes mr-1"></i>' + barang.stok_barang + ' unit')
                .removeClass('badge-success badge-warning badge-danger')
                .addClass(stockBadgeClass);

            // Format dates
            $('#viewCreatedAt').text(barang.created_at ? new Date(barang.created_at).toLocaleString('id-ID') : '-');
            $('#viewUpdatedAt').text(barang.updated_at ? new Date(barang.updated_at).toLocaleString('id-ID') : '-');

            // Set product image
            const viewProductImage = $('#viewProductImage');
            if (barang.photo_barang) {
                viewProductImage.attr('src', '../../assets/img/products/' + barang.photo_barang);
            } else {
                viewProductImage.attr('src', '../../assets/img/products/default-product.png');
            }

            $('#viewModal').modal('show');
        }

        // Enhanced delete barang function with SweetAlert2
        function deleteBarang(id, namaBarang) {
            Swal.fire({
                title: '⚠️ Konfirmasi Hapus',
                html: `Apakah Anda yakin ingin menghapus barang:<br><strong class="text-danger">${namaBarang}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '🗑️ Ya, Hapus!',
                cancelButtonText: '❌ Batal',
                reverseButtons: true,
                customClass: {
                    popup: 'animate__animated animate__zoomIn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete_id_barang').val(id);
                    $('#delete_nama_barang').text(namaBarang);
                    $('#deleteModal').modal('show');
                }
            });
        }

        // Form validation enhancement
        function validateForm(formId) {
            const form = $(formId);
            let isValid = true;

            form.find('input[required], select[required], textarea[required]').each(function () {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                }
            });

            return isValid;
        }

        // Auto hide alerts with enhanced animation
        setTimeout(function () {
            $('.alert').addClass('animate__animated animate__fadeOutRight');
            setTimeout(function () {
                $('.alert').remove();
            }, 1000);
        }, 5000);

        // Add hover effects to table rows
        $(document).on('mouseenter', '.table tbody tr', function () {
            $(this).addClass('table-active');
        }).on('mouseleave', '.table tbody tr', function () {
            $(this).removeClass('table-active');
        });

        // Enhanced file upload drag and drop
        $('.custom-file-upload').on('dragover', function (e) {
            e.preventDefault();
            $(this).addClass('border-primary bg-light');
        }).on('dragleave', function (e) {
            e.preventDefault();
            $(this).removeClass('border-primary bg-light');
        }).on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('border-primary bg-light');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const input = $(this).find('input[type=file]')[0];
                input.files = files;
                $(input).trigger('change');
            }
        });

        // Add success animation for form submissions
        $('form').on('submit', function () {
            const submitBtn = $(this).find('button[type=submit]');
            const originalText = submitBtn.html();

            if (validateForm(this)) {
                submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...')
                    .prop('disabled', true);
            }
        });

        // Add loading animation to buttons
        $('.btn').on('click', function () {
            if ($(this).attr('type') === 'submit') {
                const originalText = $(this).html();
                $(this).html('<i class="fas fa-spinner fa-spin mr-1"></i>Loading...');

                setTimeout(() => {
                    $(this).html(originalText);
                }, 2000);
            }
        });

        // Initialize animate.css classes
        $(document).ready(function () {
            $('.card').addClass('animate__animated animate__fadeInUp');
            $('.alert').addClass('animate__animated animate__slideInRight');
        });

        // Add keyboard shortcuts
        $(document).keydown(function (e) {
            // Ctrl + N for new barang
            if (e.ctrlKey && e.keyCode === 78) {
                e.preventDefault();
                $('#addModal').modal('show');
            }

            // ESC to close modals
            if (e.keyCode === 27) {
                $('.modal').modal('hide');
            }
        });

        // Add search highlight functionality
        $('#table-1').on('draw.dt', function () {
            const searchTerm = $('.dataTables_filter input').val();
            if (searchTerm) {
                $('.table tbody').highlight(searchTerm);
            }
        });

        // Reset form when modal is closed
        $('.modal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $(this).find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
            $(this).find('img[id$="Preview"]').hide();
        });

        // Reset add modal when opened
        $('#addModal').on('shown.bs.modal', function () {
            $('#addPreview').hide();
            $(this).find('input[name="nama_barang"]').focus();
        });

        // Reset edit modal when opened
        $('#editModal').on('shown.bs.modal', function () {
            $(this).find('input[name="nama_barang"]').focus();
        });

        // Add real-time validation
        $('input[required], textarea[required]').on('blur', function () {
            if ($(this).val()) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        });

        // Stock warning alert
        function checkStockLevels() {
            $('.table tbody tr').each(function () {
                const stockBadge = $(this).find('td:nth-child(6) .badge'); // Kolom stock (ke-5)

                if (stockBadge.length > 0) {
                    // Ambil text dan extract angka
                    const stockText = stockBadge.text();
                    const matches = stockText.match(/\d+/);
                    const stockValue = matches ? parseInt(matches[0]) : 0;

                    // Reset classes
                    $(this).removeClass('table-warning table-danger');

                    // Apply styling
                    if (stockValue <= 5 && stockValue > 0) {
                        $(this).addClass('table-warning');
                    } else if (stockValue === 0) {
                        $(this).addClass('table-danger');
                    }
                }
            });
        }

        // Call stock check on page load
        $(document).ready(function () {
            setTimeout(checkStockLevels, 1000);
        });

        // Success notification for successful operations
        function showSuccessNotification(message) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: message,
                timer: 2000,
                showConfirmButton: false,
                customClass: {
                    popup: 'animate__animated animate__bounceIn'
                }
            });
        }

        // Error notification for failed operations
        function showErrorNotification(message) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: message,
                confirmButtonColor: '#d33',
                customClass: {
                    popup: 'animate__animated animate__shakeX'
                }
            });
        }

        function formatNumber(num) {
            return parseInt(num).toLocaleString('id-ID');
        }

        function formatCurrency(input) {
            // Remove all non-digit characters
            let value = input.value.replace(/\D/g, '');

            // Add thousand separators
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            // Set the formatted value back to input
            input.value = value;
        }
    </script>

    <!-- SweetAlert2 for better alerts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.min.js"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.min.css">

    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <!-- jQuery Highlight plugin for search highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-highlight/3.5.0/jquery.highlight.min.js"></script>

</body>

</html>