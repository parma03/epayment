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
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    $role = "Administrator";
                    $photo_profile = null;

                    // Handle file upload
                    if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] == 0) {
                        $upload_dir = '../../assets/img/avatar/';
                        $file_extension = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['photo_profile']['tmp_name'], $upload_path)) {
                            $photo_profile = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO tb_user (email, password, role, photo_profile, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$email, $password, $role, $photo_profile]);

                    $_SESSION['alert_message'] = 'Data administrator berhasil ditambahkan!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'fas fa-check-circle';
                    break;

                case 'update':
                    $id_user = $_POST['id_user'];
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    $role = "Administrator";
                    $old_photo = $_POST['old_photo'];
                    $photo_profile = $old_photo;

                    // Handle file upload
                    if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] == 0) {
                        $upload_dir = '../../assets/img/avatar/';
                        $file_extension = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['photo_profile']['tmp_name'], $upload_path)) {
                            // Delete old photo if exists
                            if ($old_photo && file_exists($upload_dir . $old_photo)) {
                                unlink($upload_dir . $old_photo);
                            }
                            $photo_profile = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE tb_user SET email = ?, password = ?, role = ?, photo_profile = ?, update_at = NOW() WHERE id_user = ?");
                    $stmt->execute([$email, $password, $role, $photo_profile, $id_user]);

                    $_SESSION['alert_message'] = 'Data administrator berhasil diupdate!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'fas fa-check-circle';
                    break;

                case 'delete':
                    $id_user = $_POST['id_user'];

                    // Get photo filename before delete
                    $stmt = $pdo->prepare("SELECT photo_profile FROM tb_user WHERE id_user = ?");
                    $stmt->execute([$id_user]);
                    $user = $stmt->fetch();

                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM tb_user WHERE id_user = ?");
                    $stmt->execute([$id_user]);

                    // Delete photo file if exists
                    if ($user['photo_profile'] && file_exists('../../assets/img/avatar/' . $user['photo_profile'])) {
                        unlink('../../assets/img/avatar/' . $user['photo_profile']);
                    }

                    $_SESSION['alert_message'] = 'Data administrator berhasil dihapus!';
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

// Fetch all administrators
$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE role = 'Administrator' ORDER BY created_at DESC");
$stmt->execute();
$administrators = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Administrator Management &mdash; Stisla</title>

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

    <style>
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
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

        /* Enhanced Card Styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 20px 25px;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.3rem;
        }

        .card-body {
            padding: 25px;
        }

        /* Enhanced Table Styling */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 18px 15px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            border: none;
        }

        .table tbody tr:hover {
            background-color: #f8f9ff;
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
        }

        /* Avatar Styling */
        .avatar-preview,
        .avatar-modal {
            border: 3px solid #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .avatar-preview {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-preview:hover {
            transform: scale(1.1);
        }

        .avatar-modal {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 15px auto;
            display: block;
        }

        /* Enhanced Badge Styling */
        .badge {
            padding: 8px 12px;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        /* Enhanced Button Styling */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(240, 147, 251, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #721c24;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 154, 158, 0.4);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
            margin-right: 5px;
        }

        /* Enhanced Modal Styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.2rem;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border: none;
            padding: 20px 25px;
            background-color: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }

        /* Enhanced Form Styling */
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-control-file {
            padding: 10px 0;
        }

        /* Enhanced Alert Styling */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #721c24;
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        /* DataTables Custom Styling */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 6px 12px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px;
            margin: 0 2px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-color: #667eea !important;
            color: white !important;
        }

        .breadcrumb-item a {
            color: rgba(0, 0, 0, 0.8);
        }

        .breadcrumb-item.active {
            color: black;
        }

        /* File Upload Enhancement */
        .custom-file-upload {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .custom-file-upload:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-color: #764ba2;
        }

        /* Responsive Table Actions */
        @media (max-width: 768px) {
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.7rem;
            }

            .table-responsive {
                font-size: 0.9rem;
            }

            .avatar-preview {
                width: 40px;
                height: 40px;
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
                        <h1><i class="fas fa-users-cog mr-3"></i>Administrator Management</h1>
                        <div class="section-header-breadcrumb">
                            <div class="breadcrumb-item active"><a href="index.php"><i
                                        class="fas fa-home mr-1"></i>Dashboard</a></div>
                            <div class="breadcrumb-item"><i class="fas fa-user-shield mr-1"></i>Administrator</div>
                        </div>
                    </div>

                    <div class="section-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4><i class="fas fa-table mr-2"></i>Data Administrator</h4>
                                        <div class="card-header-action">
                                            <button type="button" class="btn btn-primary btn-lg" data-toggle="modal"
                                                data-target="#addModal">
                                                <i class="fas fa-plus-circle mr-2"></i>Tambah Administrator
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
                                                        <th><i class="fas fa-envelope mr-1"></i>Email</th>
                                                        <th><i class="fas fa-user-tag mr-1"></i>Role</th>
                                                        <th><i class="fas fa-calendar-plus mr-1"></i>Created At</th>
                                                        <th><i class="fas fa-calendar-edit mr-1"></i>Updated At</th>
                                                        <th class="text-center"><i class="fas fa-cogs mr-1"></i>Action
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($administrators as $index => $admin): ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <span
                                                                    class="badge badge-secondary"><?php echo $index + 1; ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($admin['photo_profile']): ?>
                                                                    <img alt="avatar"
                                                                        src="../../assets/img/avatar/<?php echo $admin['photo_profile']; ?>"
                                                                        class="avatar-preview"
                                                                        title="<?php echo htmlspecialchars($admin['email']); ?>">
                                                                <?php else: ?>
                                                                    <img alt="avatar" src="../../assets/img/avatar/avatar-1.png"
                                                                        class="avatar-preview"
                                                                        title="<?php echo htmlspecialchars($admin['email']); ?>">
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($admin['email']); ?></strong>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-primary">
                                                                    <i
                                                                        class="fas fa-user-shield mr-1"></i><?php echo $admin['role']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-clock mr-1 text-muted"></i>
                                                                <?php echo $admin['created_at'] ? date('d/m/Y H:i', strtotime($admin['created_at'])) : '-'; ?>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-edit mr-1 text-muted"></i>
                                                                <?php echo $admin['update_at'] ? date('d/m/Y H:i', strtotime($admin['update_at'])) : '-'; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-warning btn-sm"
                                                                        onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)"
                                                                        title="Edit Administrator">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-primary btn-sm"
                                                                        onclick="viewAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)"
                                                                        title="Detail Administrator">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-danger btn-sm"
                                                                        onclick="deleteAdmin(<?php echo $admin['id_user']; ?>, '<?php echo htmlspecialchars($admin['email']); ?>')"
                                                                        title="Hapus Administrator">
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
                        <i class="fas fa-user-plus mr-2"></i>Tambah Administrator Baru
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
                                    <label><i class="fas fa-envelope mr-1"></i>Email Address</label>
                                    <input type="email" class="form-control" name="email" required
                                        placeholder="masukkan email administrator">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-lock mr-1"></i>Password</label>
                                    <input type="password" class="form-control" name="password" required
                                        placeholder="masukkan password">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-camera mr-1"></i>Profile Picture</label>
                            <div class="custom-file-upload">
                                <input type="file" class="form-control-file" name="photo_profile" accept="image/*"
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
                        <i class="fas fa-user-edit mr-2"></i>Edit Administrator
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id_user" id="edit_id_user">
                        <input type="hidden" name="old_photo" id="edit_old_photo">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-envelope mr-1"></i>Email Address</label>
                                    <input type="email" class="form-control" name="email" id="edit_email" required
                                        placeholder="masukkan email administrator">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-lock mr-1"></i>Password</label>
                                    <input type="password" class="form-control" name="password" id="edit_password"
                                        required placeholder="masukkan password baru">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-camera mr-1"></i>Profile Picture</label>
                            <div class="custom-file-upload">
                                <input type="file" class="form-control-file" name="photo_profile" accept="image/*"
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
                        <i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Hapus Administrator
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id_user" id="delete_id_user">

                        <div class="text-center mb-4">
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                                <h5 class="alert-heading">Peringatan!</h5>
                                <p class="mb-0">Anda akan menghapus administrator dengan email:</p>
                                <strong class="text-danger h5" id="delete_email"></strong>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                        <h6 class="card-title text-danger">Tindakan Tidak Dapat Dibatalkan!</h6>
                                        <p class="card-text text-muted">
                                            Data administrator akan dihapus secara permanen dari sistem.
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
                        <i class="fas fa-eye mr-2"></i>Detail Administrator
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="viewAvatar" class="img-fluid rounded-circle mb-3"
                                style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 8px 25px rgba(0,0,0,0.15);"
                                alt="Avatar">
                            <h5 id="viewEmail" class="text-primary"></h5>
                            <span id="viewRole" class="badge badge-primary badge-lg"></span>
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
                                                    class="fas fa-envelope text-primary mr-2"></i>Email:</td>
                                            <td id="viewEmailDetail"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-user-tag text-success mr-2"></i>Role:</td>
                                            <td id="viewRoleDetail"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-calendar-plus text-info mr-2"></i>Dibuat:</td>
                                            <td id="viewCreatedAt"></td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold"><i
                                                    class="fas fa-calendar-edit text-warning mr-2"></i>Diupdate:</td>
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
                    { "orderable": false, "targets": [1, 6] },
                    { "className": "text-center", "targets": [0, 6] }
                ],
                "responsive": true,
                "pageLength": 10,
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"]],
                "order": [[4, "desc"]], // Sort by created_at descending
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
            $('.dataTables_filter input').addClass('form-control').attr('placeholder', 'Cari administrator...');
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

        // Enhanced edit administrator function
        function editAdmin(admin) {
            $('#edit_id_user').val(admin.id_user);
            $('#edit_email').val(admin.email);
            $('#edit_password').val(admin.password);
            $('#edit_old_photo').val(admin.photo_profile);

            // Show current photo with animation
            const editPreview = $('#editPreview');
            if (admin.photo_profile) {
                editPreview.attr('src', '../../assets/img/avatar/' + admin.photo_profile)
                    .show()
                    .addClass('animate__animated animate__fadeIn');
            } else {
                editPreview.attr('src', '../../assets/img/avatar/avatar-1.png')
                    .show()
                    .addClass('animate__animated animate__fadeIn');
            }

            // Show modal with animation
            $('#editModal').modal('show');
        }

        // Enhanced view administrator function
        function viewAdmin(admin) {
            $('#viewEmail').text(admin.email);
            $('#viewRole').text(admin.role);
            $('#viewEmailDetail').text(admin.email);
            $('#viewRoleDetail').html('<span class="badge badge-primary">' + admin.role + '</span>');
            $('#viewCreatedAt').text(admin.created_at ? new Date(admin.created_at).toLocaleString('id-ID') : '-');
            $('#viewUpdatedAt').text(admin.update_at ? new Date(admin.update_at).toLocaleString('id-ID') : '-');

            // Set avatar
            const viewAvatar = $('#viewAvatar');
            if (admin.photo_profile) {
                viewAvatar.attr('src', '../../assets/img/avatar/' + admin.photo_profile);
            } else {
                viewAvatar.attr('src', '../../assets/img/avatar/avatar-1.png');
            }

            $('#viewModal').modal('show');
        }

        // Enhanced delete administrator function with SweetAlert2
        function deleteAdmin(id, email) {
            Swal.fire({
                title: '⚠️ Konfirmasi Hapus',
                html: `Apakah Anda yakin ingin menghapus administrator:<br><strong class="text-danger">${email}</strong>?`,
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
                    $('#delete_id_user').val(id);
                    $('#delete_email').text(email);
                    $('#deleteModal').modal('show');
                }
            });
        }

        // Form validation enhancement
        function validateForm(formId) {
            const form = $(formId);
            let isValid = true;

            form.find('input[required], select[required]').each(function () {
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
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...')
                .prop('disabled', true);
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
            // Ctrl + N for new administrator
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