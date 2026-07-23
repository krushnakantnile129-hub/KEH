<?php
$page_title = "Edit Profile";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = sanitize($_POST['name'] ?? '');
    $bio          = sanitize($_POST['bio'] ?? '');
    $college      = sanitize($_POST['college'] ?? '');
    $department   = sanitize($_POST['department'] ?? '');
    $github_url   = sanitize($_POST['github_url'] ?? '');
    $linkedin_url = sanitize($_POST['linkedin_url'] ?? '');

    if (empty($name) || empty($college) || empty($department)) {
        $error = "Name, College, and Department cannot be empty.";
    } else {
        $photo_filename = $current_user['profile_photo'];

        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['profile_photo']['tmp_name'];
            $file_name = $_FILES['profile_photo']['name'];
            $file_size = $_FILES['profile_photo']['size'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($file_ext, $allowed_exts)) {
                $error = "Only JPG, PNG, and WEBP profile photos are allowed.";
            } elseif ($file_size > 2 * 1024 * 1024) {
                $error = "Profile photo size must be less than 2MB.";
            } else {
                $upload_dir = __DIR__ . '/uploads/profile_photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_filename = 'user_' . $current_user['id'] . '_' . time() . '.' . $file_ext;
                if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                    $photo_filename = $new_filename;
                } else {
                    $error = "Failed to save profile photo upload.";
                }
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, bio = ?, college = ?, department = ?, github_url = ?, linkedin_url = ?, profile_photo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $bio, $college, $department, $github_url, $linkedin_url, $photo_filename, $current_user['id']]);
                
                $success = "Profile updated successfully!";
                
                // Refresh current user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$current_user['id']]);
                $current_user = $stmt->fetch();
            } catch (Exception $e) {
                $error = "Failed to update profile. " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="keh-card p-4 p-md-5 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1">Edit Profile</h3>
                        <p class="text-muted small mb-0">Update your student information, avatar, and social handles.</p>
                    </div>
                    <a href="profile.php" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fas fa-eye me-1"></i> View Profile</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= e($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="edit-profile.php" method="POST" enctype="multipart/form-data">
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <img src="/KEH/uploads/profile_photos/<?= e($current_user['profile_photo']) ?>" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($current_user['name']) ?>&background=6366f1&color=fff';"
                                 alt="Avatar" class="mentor-avatar shadow-sm mb-2" style="width: 100px; height: 100px;">
                        </div>
                        <div class="col-md-9">
                            <label for="profile_photo" class="form-label fw-semibold small">Upload Profile Photo</label>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            <span class="text-muted text-xs">Recommended JPG or PNG. Max size 2MB.</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label fw-semibold small">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?= e($current_user['name']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label fw-semibold small">Email Address (Read-only)</label>
                            <input type="email" class="form-control bg-light" id="email" value="<?= e($current_user['email']) ?>" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="college" class="form-label fw-semibold small">College / University</label>
                            <input type="text" class="form-control" id="college" name="college" required value="<?= e($current_user['college']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label fw-semibold small">Department / Major</label>
                            <input type="text" class="form-control" id="department" name="department" required value="<?= e($current_user['department']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label fw-semibold small">About Me (Bio)</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Describe your background, what you enjoy teaching, and what skills you want to pick up..."><?= e($current_user['bio']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="github_url" class="form-label fw-semibold small"><i class="fab fa-github me-1"></i> GitHub URL</label>
                            <input type="url" class="form-control" id="github_url" name="github_url" placeholder="https://github.com/username" value="<?= e($current_user['github_url']) ?>">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="linkedin_url" class="form-label fw-semibold small"><i class="fab fa-linkedin me-1 text-primary"></i> LinkedIn URL</label>
                            <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/username" value="<?= e($current_user['linkedin_url']) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="profile.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
