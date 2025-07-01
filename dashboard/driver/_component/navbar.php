<?php
// Ambil data user untuk form
if (isset($_SESSION['id_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
    $stmt->execute([$_SESSION['id_user']]);
    $user_data = $stmt->fetch();
}
?>
<nav class="navbar navbar-expand-lg main-navbar">
    <form class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
        </ul>
    </form>
    <ul class="navbar-nav navbar-right">
        <li class="dropdown"><a href="#" data-toggle="dropdown"
                class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <img alt="image"
                    src="<?php echo !empty($_SESSION['photo_profile']) ? '../../assets/img/avatar/' . $_SESSION['photo_profile'] : '../../assets/img/avatar/avatar-1.png'; ?>"
                    class="rounded-circle mr-1">
                <div class="d-sm-none d-lg-inline-block">Hi,
                    <?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : '-'; ?>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <!-- Updated Profile Link with Modal Trigger -->
                <a href="#" class="dropdown-item has-icon" data-toggle="modal" data-target="#editProfileModal">
                    <i class="far fa-user"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item has-icon text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="editProfileModalLabel">
                    <i class="fas fa-user-edit mr-2"></i>Edit Profile
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- PERBAIKAN: Form action mengarah ke halaman yang sama (self-submit) -->
            <form action="update_profile.php" method="POST" enctype="multipart/form-data" id="editProfileForm">
                <div class="modal-body">
                    <div class="row">
                        <!-- Current Profile Photo -->
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-camera mr-2"></i>Foto Profile</h6>
                                </div>
                                <div class="card-body text-center">
                                    <img id="currentProfilePhoto"
                                        src="<?php echo !empty($user_data['photo_profile']) ? '../../assets/img/avatar/' . $user_data['photo_profile'] : '../../assets/img/avatar/avatar-1.png'; ?>"
                                        class="img-fluid rounded-circle mb-3"
                                        style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #007bff;"
                                        alt="Profile Photo">

                                    <div class="form-group">
                                        <label for="photo_profile" class="form-label">
                                            <i class="fas fa-upload mr-1"></i>Upload Foto Baru
                                        </label>
                                        <input type="file" class="form-control-file" id="photo_profile"
                                            name="photo_profile" accept="image/jpeg,image/jpg,image/png,image/gif"
                                            onchange="previewImage(this)">
                                        <small class="form-text text-muted">
                                            Format: JPG, PNG, GIF. Maksimal 2MB.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="col-md-8">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Informasi Profile</h6>
                                </div>
                                <div class="card-body">
                                    <!-- ID User (Hidden) -->
                                    <input type="hidden" name="id_user" value="<?php echo $user_data['id_user']; ?>">

                                    <!-- Email -->
                                    <div class="form-group">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope text-primary mr-1"></i>Email Address
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                        <div class="invalid-feedback">
                                            Email harus diisi dengan format yang valid
                                        </div>
                                    </div>

                                    <!-- Role (Display Only) -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-user-tag text-info mr-1"></i>Role
                                        </label>
                                        <input type="text" class="form-control"
                                            value="<?php echo htmlspecialchars($user_data['role']); ?>" readonly>
                                        <small class="form-text text-muted">Role tidak dapat diubah</small>
                                    </div>

                                    <!-- Current Password (Required for password change) -->
                                    <div class="form-group">
                                        <label for="current_password" class="form-label">
                                            <i class="fas fa-key text-warning mr-1"></i>Password Lama
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password"
                                                name="current_password"
                                                placeholder="Masukkan password lama (wajib jika ingin ubah password)">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('current_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Wajib diisi jika ingin mengubah
                                            password</small>
                                    </div>

                                    <!-- New Password -->
                                    <div class="form-group">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock text-success mr-1"></i>Password Baru
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password"
                                                placeholder="Kosongkan jika tidak ingin mengubah password">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah
                                            password</small>
                                    </div>

                                    <!-- Account Info -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-calendar-plus text-info mr-1"></i>Dibuat Pada
                                                </label>
                                                <input type="text" class="form-control"
                                                    value="<?php echo date('d/m/Y H:i', strtotime($user_data['created_at'])); ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-calendar-check text-success mr-1"></i>Terakhir
                                                    Update
                                                </label>
                                                <input type="text" class="form-control"
                                                    value="<?php echo $user_data['update_at'] ? date('d/m/Y H:i', strtotime($user_data['update_at'])) : 'Belum pernah'; ?>"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Batal
                    </button>
                    <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                        <i class="fas fa-save mr-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // Preview image before upload
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('currentProfilePhoto').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Toggle password visibility
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling.querySelector('button i');

        if (field.type === 'password') {
            field.type = 'text';
            button.classList.remove('fa-eye');
            button.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            button.classList.remove('fa-eye-slash');
            button.classList.add('fa-eye');
        }
    }

    // Form validation
    document.getElementById('editProfileForm').addEventListener('submit', function (e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const currentPassword = document.getElementById('current_password').value;

        // Email validation
        if (!email) {
            e.preventDefault();
            alert('Email harus diisi');
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Format email tidak valid');
            return;
        }

        // Password validation
        if (password && !currentPassword) {
            e.preventDefault();
            alert('Password lama harus diisi untuk mengubah password');
            return;
        }

        // File size validation
        const fileInput = document.getElementById('photo_profile');
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const maxSize = 2 * 1024 * 1024; // 2MB

            if (file.size > maxSize) {
                e.preventDefault();
                alert('Ukuran file terlalu besar. Maksimal 2MB');
                return;
            }
        }

        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...';
        submitBtn.disabled = true;

        // Re-enable button after 3 seconds (in case of error)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    });

    // Reset form when modal is closed
    $('#editProfileModal').on('hidden.bs.modal', function () {
        const form = document.getElementById('editProfileForm');
        form.reset();
        // Reset image preview
        document.getElementById('currentProfilePhoto').src = '<?php echo !empty($user_data['photo_profile']) ? '../../assets/img/avatar/' . $user_data['photo_profile'] : '../../assets/img/avatar/avatar-1.png'; ?>';
    });
</script>

<style>
    /* Additional styles for the edit profile modal */
    .modal-dialog {
        max-width: 800px;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    .btn-group .btn {
        border-radius: 0.375rem;
    }

    .input-group-append .btn {
        border-color: #ced4da;
    }

    .input-group-append .btn:hover {
        background-color: #e9ecef;
    }

    /* Loading animation */
    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .modal-dialog {
            max-width: 95%;
            margin: 1rem auto;
        }

        .row>.col-md-4,
        .row>.col-md-8 {
            margin-bottom: 1rem;
        }
    }
</style>