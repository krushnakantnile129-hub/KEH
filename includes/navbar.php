<?php
require_once __DIR__ . '/functions.php';
$is_logged_in = isset($_SESSION['user_id']);
$user = null;
$unread_count = 0;
$pending_req_count = 0;

if ($is_logged_in && isset($pdo)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $unread_count = get_unread_message_count($pdo, $user['id']);
        $pending_req_count = get_pending_request_count($pdo, $user['id']);
    }
}
?>
<nav class="navbar navbar-expand-lg sticky-top custom-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/KEH/index.php">
            <div class="brand-icon shadow-sm">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span class="brand-text fw-bold">Knowledge <span class="text-primary-gradient">Exchange Hub</span></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link px-3" href="/KEH/find-mentor.php"><i class="fas fa-search me-1 text-primary"></i> Find Mentors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/KEH/leaderboard.php"><i class="fas fa-trophy me-1 text-warning"></i> Leaderboard</a>
                </li>
                <?php if ($is_logged_in): ?>
                <li class="nav-item">
                    <a class="nav-link px-3 position-relative" href="/KEH/requests.php">
                        <i class="fas fa-paper-plane me-1 text-info"></i> Requests
                        <?php if ($pending_req_count > 0): ?>
                            <span class="badge rounded-pill bg-danger ms-1"><?=$pending_req_count?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3" href="/KEH/my-sessions.php"><i class="fas fa-calendar-alt me-1 text-success"></i> Sessions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 position-relative" href="/KEH/chat.php">
                        <i class="fas fa-comments me-1 text-purple"></i> Chat
                        <?php if ($unread_count > 0): ?>
                            <span class="badge rounded-pill bg-primary ms-1"><?=$unread_count?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if ($is_logged_in && $user): ?>
                    <!-- Points badge -->
                    <div class="points-pill bg-warning-subtle text-warning-emphasis fw-bold px-3 py-1 rounded-pill shadow-xs d-flex align-items-center gap-1" title="Your Total Points">
                        <i class="fas fa-coins text-warning"></i> <?= (int)$user['points'] ?> pts
                    </div>

                    <?php if (!empty($user['is_admin'])): ?>
                        <a href="/KEH/admin/dashboard.php" class="btn btn-outline-danger btn-sm rounded-pill font-weight-bold">
                            <i class="fas fa-user-shield me-1"></i> Admin
                        </a>
                    <?php endif; ?>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="/KEH/uploads/profile_photos/<?= e($user['profile_photo'] ?? 'default_avatar.png') ?>" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=6366f1&color=fff';"
                                 alt="Profile" class="avatar-sm rounded-circle me-2 object-fit-cover shadow-xs" width="38" height="38">
                            <span class="fw-semibold small d-none d-md-inline-block"><?= e($user['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 rounded-3">
                            <li><a class="dropdown-item py-2" href="/KEH/dashboard.php"><i class="fas fa-chart-pie me-2 text-primary"></i> Dashboard</a></li>
                            <li><a class="dropdown-item py-2" href="/KEH/profile.php"><i class="fas fa-user me-2 text-success"></i> My Profile</a></li>
                            <li><a class="dropdown-item py-2" href="/KEH/edit-profile.php"><i class="fas fa-user-edit me-2 text-info"></i> Edit Profile</a></li>
                            <li><a class="dropdown-item py-2" href="/KEH/add-skills.php"><i class="fas fa-tools me-2 text-warning"></i> Manage Skills</a></li>
                            <li><a class="dropdown-item py-2" href="/KEH/certificates.php"><i class="fas fa-certificate me-2 text-purple"></i> Certificates</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="/KEH/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/KEH/login.php" class="btn btn-light text-primary fw-semibold px-4 rounded-pill">Log In</a>
                    <a href="/KEH/register.php" class="btn btn-primary fw-semibold px-4 rounded-pill shadow-sm">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
