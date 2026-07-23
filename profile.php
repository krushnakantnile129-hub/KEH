<?php
$page_title = "User Profile";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$profile_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? $current_user['id'];

// Fetch profile user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_id]);
$profile_user = $stmt->fetch();

if (!$profile_user || $profile_user['is_blocked']) {
    header("Location: dashboard.php?error=" . urlencode("User profile not found or unavailable."));
    exit();
}

$is_own_profile = ($profile_id === (int)$current_user['id']);

// Fetch rating details
$rating_info = get_user_rating($pdo, $profile_id);

// Fetch taught & learned skills
$stmt = $pdo->prepare("
    SELECT us.*, s.skill_name, c.category_name
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE us.user_id = ?
");
$stmt->execute([$profile_id]);
$user_skills = $stmt->fetchAll();

$teach_skills = array_filter($user_skills, fn($s) => $s['skill_type'] === 'teach');
$learn_skills = array_filter($user_skills, fn($s) => $s['skill_type'] === 'learn');

// Fetch reviews given to this user
$stmt = $pdo->prepare("
    SELECT r.*, u.name as reviewer_name, u.profile_photo as reviewer_photo
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewed_user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$profile_id]);
$user_reviews = $stmt->fetchAll();

// Fetch certificates
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE user_id = ? ORDER BY created_at DESC");
$user_certs = $stmt->fetchAll();

$review_error = '';
$review_success = '';

// Handle manual direct review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_manual_review' && !$is_own_profile) {
    $rating      = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $review_text = sanitize($_POST['review_text'] ?? '');

    if (!$rating || $rating < 1 || $rating > 5 || empty($review_text)) {
        $review_error = "Please select a star rating (1-5) and write your review.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (session_id, reviewer_id, reviewed_user_id, rating, review_text)
                VALUES (NULL, ?, ?, ?, ?)
            ");
            $stmt->execute([$current_user['id'], $profile_id, $rating, $review_text]);

            // If 5 stars, award +3 bonus points to reviewed user
            if ($rating == 5) {
                award_points($pdo, $profile_id, 3, "Received a 5-star review from " . $current_user['name']);
            }

            $review_success = "Your rating & review have been submitted successfully!";

            // Refresh rating info and reviews list
            $rating_info = get_user_rating($pdo, $profile_id);
            $stmt = $pdo->prepare("
                SELECT r.*, u.name as reviewer_name, u.profile_photo as reviewer_photo
                FROM reviews r
                JOIN users u ON r.reviewer_id = u.id
                WHERE r.reviewed_user_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$profile_id]);
            $user_reviews = $stmt->fetchAll();
        } catch (Exception $e) {
            $review_error = "Failed to submit review: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Header Banner Card -->
    <div class="keh-card p-4 mb-4 shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <img src="/KEH/uploads/profile_photos/<?= e($profile_user['profile_photo']) ?>" 
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($profile_user['name']) ?>&background=6366f1&color=fff';"
                     alt="<?= e($profile_user['name']) ?>" class="mentor-avatar shadow" style="width: 120px; height: 120px;">
            </div>
            <div class="col-md-6 text-center text-md-start">
                <h2 class="fw-bold mb-1"><?= e($profile_user['name']) ?></h2>
                <p class="text-muted mb-2">
                    <i class="fas fa-university text-primary me-1"></i> <?= e($profile_user['college'] ?? 'Student') ?>
                    <span class="mx-1">•</span> <?= e($profile_user['department'] ?? 'Department') ?>
                </p>
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-3">
                    <?= render_stars($rating_info['avg']) ?>
                    <span class="fw-bold ms-1"><?= $rating_info['avg'] > 0 ? $rating_info['avg'] . '/5' : 'New Mentor' ?></span>
                    <span class="text-muted small">(<?= $rating_info['count'] ?> reviews)</span>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                    <?php if (!empty($profile_user['github_url'])): ?>
                        <a href="<?= e($profile_user['github_url']) ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill">
                            <i class="fab fa-github me-1"></i> GitHub
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($profile_user['linkedin_url'])): ?>
                        <a href="<?= e($profile_user['linkedin_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                            <i class="fab fa-linkedin me-1"></i> LinkedIn
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3 text-center text-md-end mt-3 mt-md-0">
                <div class="points-pill bg-warning-subtle text-warning-emphasis fw-bold px-3 py-2 rounded-pill d-inline-block mb-3">
                    <i class="fas fa-coins text-warning me-1"></i> <?= (int)$profile_user['points'] ?> Points
                </div>
                <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-2">
                    <?php if ($is_own_profile): ?>
                        <a href="edit-profile.php" class="btn btn-primary rounded-pill px-3"><i class="fas fa-user-edit me-1"></i> Edit Profile</a>
                    <?php else: ?>
                        <a href="mentor-details.php?id=<?= $profile_user['id'] ?>" class="btn btn-primary rounded-pill px-3"><i class="fas fa-paper-plane me-1"></i> Send Request</a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-dark rounded-pill px-3" onclick="copyProfileLink('<?= $profile_user['id'] ?>')">
                        <i class="fas fa-link me-1"></i> Share Profile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyProfileLink(userId) {
        const link = window.location.origin + '/KEH/profile.php?id=' + userId;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link).then(() => {
                alert('Success! Unique profile link copied to clipboard:\n\n' + link);
            });
        } else {
            prompt('Copy your unique profile link below:', link);
        }
    }
    </script>

    <div class="row g-4">
        <!-- Bio & Skills Left Column -->
        <div class="col-lg-7">
            <!-- Bio Card -->
            <div class="keh-card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-user-circle text-primary me-2"></i> About Me</h5>
                <p class="text-secondary mb-0">
                    <?= !empty($profile_user['bio']) ? nl2br(e($profile_user['bio'])) : '<em>No bio information provided yet.</em>' ?>
                </p>
            </div>

            <!-- Skills Taught -->
            <div class="keh-card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-chalkboard-teacher text-success me-2"></i> Skills I Can Teach</h5>
                <?php if (empty($teach_skills)): ?>
                    <p class="text-muted small mb-0">No teaching skills listed.</p>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($teach_skills as $ts): ?>
                            <?php 
                                $badgeClass = 'badge-prof-intermediate';
                                if ($ts['proficiency_level'] === 'Beginner') $badgeClass = 'badge-prof-beginner';
                                if ($ts['proficiency_level'] === 'Advanced') $badgeClass = 'badge-prof-advanced';
                                if ($ts['proficiency_level'] === 'Expert') $badgeClass = 'badge-prof-expert';
                            ?>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?= e($ts['skill_name']) ?></h6>
                                        <span class="text-muted small"><?= e($ts['category_name']) ?></span>
                                    </div>
                                    <span class="skill-chip <?= $badgeClass ?>"><?= e($ts['proficiency_level']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Skills to Learn -->
            <div class="keh-card p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-book-reader text-info me-2"></i> Skills I Want to Learn</h5>
                <?php if (empty($learn_skills)): ?>
                    <p class="text-muted small mb-0">No learning goals listed.</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($learn_skills as $ls): ?>
                            <span class="skill-chip badge-prof-beginner px-3 py-2 fs-6">
                                <i class="fas fa-seedling me-1"></i> <?= e($ls['skill_name']) ?> (<?= e($ls['proficiency_level']) ?>)
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Certificates & Reviews Right Column -->
        <div class="col-lg-5">
            <!-- Certificates Gallery -->
            <div class="keh-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-certificate text-purple me-2"></i> Certificates</h5>
                    <?php if ($is_own_profile): ?>
                        <a href="certificates.php" class="small text-purple text-decoration-none fw-semibold">Upload New</a>
                    <?php endif; ?>
                </div>

                <?php if (empty($user_certs)): ?>
                    <p class="text-muted small mb-0">No certificates uploaded.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($user_certs as $cert): ?>
                            <div class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between border-0">
                                <span class="small fw-semibold"><i class="fas fa-file-pdf me-2 text-danger"></i> <?= e($cert['certificate_name']) ?></span>
                                <a href="/KEH/uploads/certificates/<?= e($cert['certificate_file']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill">View</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Student Reviews Card -->
            <div class="keh-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-star text-warning me-2"></i> Reviews &amp; Feedback</h5>
                    <?php if (!$is_own_profile): ?>
                        <button class="btn btn-sm btn-outline-warning rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#reviewFormCollapse">
                            <i class="fas fa-edit me-1"></i> Write Review
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($review_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show text-start" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= e($review_error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($review_success): ?>
                    <div class="alert alert-success alert-dismissible fade show text-start" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= e($review_success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Manual Direct Review Form -->
                <?php if (!$is_own_profile): ?>
                    <div class="collapse <?= ($review_error || $review_success) ? 'show' : '' ?> mb-3" id="reviewFormCollapse">
                        <div class="p-3 bg-white border border-warning rounded-3 shadow-xs">
                            <h6 class="fw-bold mb-2 text-dark">Review <?= e($profile_user['name']) ?></h6>
                            <form action="profile.php?id=<?= $profile_id ?>" method="POST">
                                <input type="hidden" name="action" value="submit_manual_review">
                                
                                <div class="mb-2">
                                    <label class="form-label small text-muted fw-bold mb-1">Your Rating:</label>
                                    <select name="rating" class="form-select form-select-sm text-warning fw-bold border-warning" required>
                                        <option value="5" selected>⭐⭐⭐⭐⭐ (5/5 Excellent)</option>
                                        <option value="4">⭐⭐⭐⭐ (4/5 Very Good)</option>
                                        <option value="3">⭐⭐⭐ (3/5 Average)</option>
                                        <option value="2">⭐⭐ (2/5 Below Average)</option>
                                        <option value="1">⭐ (1/5 Poor)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold mb-1">Your Review Feedback:</label>
                                    <textarea name="review_text" class="form-control form-control-sm" rows="3" required placeholder="Write a few words about your learning experience with this peer..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-warning btn-sm rounded-pill w-100 fw-bold">
                                    <i class="fas fa-star me-1"></i> Post Review &amp; Award Points
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (empty($user_reviews)): ?>
                    <p class="text-muted small mb-0">No reviews received yet.</p>
                <?php else: ?>
                    <?php foreach ($user_reviews as $rev): ?>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="/KEH/uploads/profile_photos/<?= e($rev['reviewer_photo']) ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($rev['reviewer_name']) ?>&background=6366f1&color=fff';"
                                         alt="Reviewer" class="rounded-circle object-fit-cover" width="30" height="30">
                                    <span class="fw-semibold small"><?= e($rev['reviewer_name']) ?></span>
                                </div>
                                <div><?= render_stars($rev['rating']) ?></div>
                            </div>
                            <p class="text-secondary small mb-1">"<?= e($rev['review_text']) ?>"</p>
                            <span class="text-muted text-xs d-block text-end"><?= format_time($rev['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
