<?php
$page_title = "Certificates & Portfolio";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$user_id = $current_user['id'];
$error = '';
$success = '';

// Handle Delete Certificate Action
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $cert_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($cert_id) {
        $stmt = $pdo->prepare("SELECT certificate_file FROM certificates WHERE id = ? AND user_id = ?");
        $stmt->execute([$cert_id, $user_id]);
        $cert = $stmt->fetch();

        if ($cert) {
            @unlink(__DIR__ . '/uploads/certificates/' . $cert['certificate_file']);
            $stmt = $pdo->prepare("DELETE FROM certificates WHERE id = ? AND user_id = ?");
            $stmt->execute([$cert_id, $user_id]);
            $success = "Certificate removed successfully.";
        }
    }
}

// Handle Upload Certificate Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cert_name = sanitize($_POST['certificate_name'] ?? '');

    if (empty($cert_name) || !isset($_FILES['certificate_file']) || $_FILES['certificate_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please provide a certificate title and select a valid file.";
    } else {
        $file_tmp  = $_FILES['certificate_file']['tmp_name'];
        $file_name = $_FILES['certificate_file']['name'];
        $file_size = $_FILES['certificate_file']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed_exts)) {
            $error = "Only PDF, JPG, and PNG certificate files are allowed.";
        } elseif ($file_size > 5 * 1024 * 1024) {
            $error = "Certificate file size must be less than 5MB.";
        } else {
            $upload_dir = __DIR__ . '/uploads/certificates/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_filename = 'cert_' . $user_id . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                $stmt = $pdo->prepare("INSERT INTO certificates (user_id, certificate_name, certificate_file) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $cert_name, $new_filename]);
                $success = "Certificate uploaded and attached to your profile!";
            } else {
                $error = "Failed to save uploaded certificate file.";
            }
        }
    }
}

// Fetch user's certificates
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$certificates = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Upload Certificate Form Left -->
        <div class="col-lg-5">
            <div class="keh-card p-4 shadow-sm">
                <h4 class="fw-bold mb-3"><i class="fas fa-file-upload text-purple me-2"></i> Upload Certificate</h4>
                
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

                <form action="certificates.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="certificate_name" class="form-label fw-semibold small">Certificate Title</label>
                        <input type="text" class="form-control" id="certificate_name" name="certificate_name" required placeholder="e.g. AWS Certified Practitioner, Java Mastery">
                    </div>

                    <div class="mb-4">
                        <label for="certificate_file" class="form-label fw-semibold small">Certificate File (PDF or Image)</label>
                        <input type="file" class="form-control" id="certificate_file" name="certificate_file" accept=".pdf,image/*" required>
                        <span class="text-muted text-xs">PDF, JPG, PNG up to 5MB.</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold shadow-sm">
                        <i class="fas fa-upload me-1"></i> Upload Certificate
                    </button>
                </form>
            </div>
        </div>

        <!-- My Certificates List Right -->
        <div class="col-lg-7">
            <div class="keh-card p-4 shadow-sm">
                <h4 class="fw-bold mb-3"><i class="fas fa-certificate text-warning me-2"></i> My Uploaded Certificates</h4>

                <?php if (empty($certificates)): ?>
                    <div class="text-center py-4 bg-light rounded-3">
                        <p class="text-muted small mb-0">No certificates uploaded yet. Add proof of your skills to boost your mentor rating!</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($certificates as $cert): ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="fas fa-award text-warning fs-4"></i>
                                        <h6 class="fw-bold mb-0 text-dark"><?= e($cert['certificate_name']) ?></h6>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                        <a href="/KEH/uploads/certificates/<?= e($cert['certificate_file']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="fas fa-external-link-alt me-1"></i> View File
                                        </a>
                                        <a href="certificates.php?action=delete&id=<?= $cert['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Delete this certificate?');" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
