<?php
$page_title = "Register Student Account";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = sanitize($_POST['name'] ?? '');
    $email      = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password   = $_POST['password'] ?? '';
    $college    = sanitize($_POST['college'] ?? '');
    $department = sanitize($_POST['department'] ?? '');

    if (empty($name) || !$email || empty($password) || empty($college) || empty($department)) {
        $error = "Please fill in all required fields with a valid email.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check existing email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "An account with this email already exists.";
        } else {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, college, department, points, profile_photo) 
                    VALUES (?, ?, ?, ?, ?, 5, 'default_avatar.png')
                ");
                $stmt->execute([$name, $email, $hashed_pass, $college, $department]);
                $new_user_id = $pdo->lastInsertId();

                // Log points
                award_points($pdo, $new_user_id, 0, "Registration Profile bonus");

                // Auto login
                $_SESSION['user_id'] = $new_user_id;
                header("Location: dashboard.php?msg=" . urlencode("Welcome to Knowledge Exchange Hub! Start by adding your skills."));
                exit();
            } catch (Exception $e) {
                $error = "Registration failed. Please try again. " . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="keh-card p-4 p-md-5 shadow-sm">
                <div class="text-center mb-4">
                    <div class="brand-icon mx-auto mb-2" style="width: 48px; height: 48px; font-size: 1.4rem;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="fw-bold">Join Knowledge Exchange Hub</h3>
                    <p class="text-muted small">Teach your skills and learn from fellow students for free.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold small">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" id="name" name="name" required placeholder="e.g. Alex Johnson" value="<?= e($_POST['name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Student Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" required placeholder="alex@college.edu" value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="At least 6 characters">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="college" class="form-label fw-semibold small">College / University</label>
                            <input type="text" class="form-control" id="college" name="college" required placeholder="State Tech University" value="<?= e($_POST['college'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label fw-semibold small">Department</label>
                            <input type="text" class="form-control" id="department" name="department" required placeholder="Computer Science" value="<?= e($_POST['department'] ?? '') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold shadow-sm mb-3">
                        Create Free Account
                    </button>

                    <div class="text-center">
                        <span class="text-muted small">Already registered? </span>
                        <a href="login.php" class="fw-semibold text-primary text-decoration-none small">Log In here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
