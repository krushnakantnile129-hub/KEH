<?php
$page_title = "Mentor Details";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$mentor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$mentor_id) {
    header("Location: find-mentor.php");
    exit();
}

// Fetch mentor user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_blocked = 0 AND is_admin = 0");
$stmt->execute([$mentor_id]);
$mentor = $stmt->fetch();

if (!$mentor) {
    header("Location: find-mentor.php?error=" . urlencode("Mentor profile not found."));
    exit();
}

$is_self = ($mentor_id === (int)$current_user['id']);

// Fetch mentor taught skills
$stmt = $pdo->prepare("
    SELECT us.*, s.id as skill_id, s.skill_name, c.category_name
    FROM user_skills us
    JOIN skills s ON us.skill_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE us.user_id = ? AND us.skill_type = 'teach'
");
$stmt->execute([$mentor_id]);
$mentor_skills = $stmt->fetchAll();

// Fetch rating & reviews
$rating_info = get_user_rating($pdo, $mentor_id);

$stmt = $pdo->prepare("
    SELECT r.*, u.name as reviewer_name, u.profile_photo as reviewer_photo
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewed_user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$mentor_id]);
$reviews = $stmt->fetchAll();

// Handle Form Submissions
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_self) {
    $action = $_POST['action'] ?? 'send_request';

    if ($action === 'submit_manual_review') {
        $rating      = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $review_text = sanitize($_POST['review_text'] ?? '');

        if (!$rating || $rating < 1 || $rating > 5 || empty($review_text)) {
            $error = "Please select a star rating (1-5) and write your review.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO reviews (session_id, reviewer_id, reviewed_user_id, rating, review_text)
                    VALUES (NULL, ?, ?, ?, ?)
                ");
                $stmt->execute([$current_user['id'], $mentor_id, $rating, $review_text]);

                if ($rating == 5) {
                    award_points($pdo, $mentor_id, 3, "Received a 5-star review from " . $current_user['name']);
                }

                $success = "Thank you! Your rating & review for " . e($mentor['name']) . " has been posted.";

                // Refresh rating & reviews
                $rating_info = get_user_rating($pdo, $mentor_id);
                $stmt = $pdo->prepare("
                    SELECT r.*, u.name as reviewer_name, u.profile_photo as reviewer_photo
                    FROM reviews r
                    JOIN users u ON r.reviewer_id = u.id
                    WHERE r.reviewed_user_id = ?
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute([$mentor_id]);
                $reviews = $stmt->fetchAll();
            } catch (Exception $e) {
                $error = "Failed to post review: " . $e->getMessage();
            }
        }
    } else {
        $selected_skill_id = filter_input(INPUT_POST, 'skill_id', FILTER_VALIDATE_INT);
        $req_message       = sanitize($_POST['message'] ?? '');

        if (!$selected_skill_id || empty($req_message)) {
            $error = "Please select a skill and write a short request message.";
        } else {
            // Check if there is already a pending or accepted request
            $stmt = $pdo->prepare("SELECT id, status FROM learning_requests WHERE learner_id = ? AND mentor_id = ? AND skill_id = ? AND status IN ('pending', 'accepted')");
            $stmt->execute([$current_user['id'], $mentor_id, $selected_skill_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $error = "You already have an active or pending request with this mentor for this skill.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO learning_requests (learner_id, mentor_id, skill_id, message, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$current_user['id'], $mentor_id, $selected_skill_id, $req_message]);

                header("Location: requests.php?msg=" . urlencode("Learning request sent successfully to " . $mentor['name'] . "!"));
                exit();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Mentor Overview Left -->
        <div class="col-lg-7">
            <div class="keh-card p-4 shadow-sm mb-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="/KEH/uploads/profile_photos/<?= e($mentor['profile_photo']) ?>" 
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($mentor['name']) ?>&background=6366f1&color=fff';"
                         alt="<?= e($mentor['name']) ?>" class="mentor-avatar shadow" style="width: 90px; height: 90px;">
                    <div>
                        <h3 class="fw-bold mb-1"><?= e($mentor['name']) ?></h3>
                        <p class="text-muted small mb-1">
                            <i class="fas fa-university text-dark me-1"></i> <?= e($mentor['college'] ?? 'College') ?>
                            <span class="mx-1">•</span> <?= e($mentor['department'] ?? 'Department') ?>
                        </p>
                        <div class="d-flex align-items-center gap-2">
                            <?= render_stars($rating_info['avg']) ?>
                            <span class="fw-bold small"><?= $rating_info['avg'] > 0 ? $rating_info['avg'] . '/5' : 'New Mentor' ?></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-muted small text-uppercase">About Mentor</h6>
                    <p class="text-secondary mb-0"><?= !empty($mentor['bio']) ? nl2br(e($mentor['bio'])) : 'No detailed bio provided.' ?></p>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-muted small text-uppercase mb-2">Skills Taught by <?= e($mentor['name']) ?></h6>
                    <?php if (empty($mentor_skills)): ?>
                        <p class="text-muted small mb-0">No active skills listed for teaching.</p>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($mentor_skills as $ms): ?>
                                <?php 
                                    $badgeClass = 'badge-prof-intermediate';
                                    if ($ms['proficiency_level'] === 'Beginner') $badgeClass = 'badge-prof-beginner';
                                    if ($ms['proficiency_level'] === 'Advanced') $badgeClass = 'badge-prof-advanced';
                                    if ($ms['proficiency_level'] === 'Expert') $badgeClass = 'badge-prof-expert';
                                ?>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?= e($ms['skill_name']) ?></h6>
                                            <span class="text-muted small"><?= e($ms['category_name']) ?></span>
                                        </div>
                                        <span class="skill-chip <?= $badgeClass ?>"><?= e($ms['proficiency_level']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Reviews Card -->
            <div class="keh-card p-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-star text-warning me-2"></i> Reviews &amp; Ratings</h5>
                    <?php if (!$is_self): ?>
                        <button class="btn btn-sm btn-outline-warning rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#mentorReviewForm">
                            <i class="fas fa-star me-1"></i> Write Review
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= e($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!$is_self): ?>
                    <div class="collapse mb-4" id="mentorReviewForm">
                        <div class="p-3 bg-white border border-warning rounded-3 shadow-xs">
                            <h6 class="fw-bold mb-2 text-dark">Leave a Manual Rating &amp; Review for <?= e($mentor['name']) ?></h6>
                            <form action="mentor-details.php?id=<?= $mentor_id ?>" method="POST">
                                <input type="hidden" name="action" value="submit_manual_review">
                                
                                <div class="mb-2">
                                    <label class="form-label small text-muted fw-bold mb-1">Select Rating Stars:</label>
                                    <select name="rating" class="form-select form-select-sm text-warning fw-bold border-warning" required>
                                        <option value="5" selected>⭐⭐⭐⭐⭐ (5/5 Excellent)</option>
                                        <option value="4">⭐⭐⭐⭐ (4/5 Very Good)</option>
                                        <option value="3">⭐⭐⭐ (3/5 Average)</option>
                                        <option value="2">⭐⭐ (2/5 Below Average)</option>
                                        <option value="1">⭐ (1/5 Poor)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold mb-1">Review Comments:</label>
                                    <textarea name="review_text" class="form-control form-control-sm" rows="3" required placeholder="Write your honest review and feedback for <?= e($mentor['name']) ?>..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-warning btn-sm rounded-pill w-100 fw-bold">
                                    <i class="fas fa-star me-1"></i> Submit Review &amp; Award Points
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (empty($reviews)): ?>
                    <p class="text-muted small mb-0">No reviews yet for this mentor.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="/KEH/uploads/profile_photos/<?= e($rev['reviewer_photo']) ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($rev['reviewer_name']) ?>&background=6366f1&color=fff';"
                                         alt="Reviewer" class="rounded-circle" width="30" height="30">
                                    <span class="fw-semibold small"><?= e($rev['reviewer_name']) ?></span>
                                </div>
                                <div><?= render_stars($rev['rating']) ?></div>
                            </div>
                            <p class="text-secondary small mb-0">"<?= e($rev['review_text']) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Request Form Right Column -->
        <div class="col-lg-5">
            <div class="keh-card p-4 shadow-sm sticky-top" style="top: 90px;">
                <h4 class="fw-bold mb-3"><i class="fas fa-paper-plane text-dark me-2"></i> Send Learning Request</h4>

                <?php if ($is_self): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i> This is your own mentor profile.
                    </div>
                <?php elseif (empty($mentor_skills)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i> This mentor has not listed any teaching skills yet.
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="mentor-details.php?id=<?= $mentor_id ?>" method="POST">
                        <div class="mb-3">
                            <label for="skill_id" class="form-label fw-semibold small">Skill You Want to Learn</label>
                            <select class="form-select" id="skill_id" name="skill_id" required>
                                <option value="">-- Choose Skill --</option>
                                <?php foreach ($mentor_skills as $ms): ?>
                                    <option value="<?= $ms['skill_id'] ?>"><?= e($ms['skill_name']) ?> (Proficiency: <?= e($ms['proficiency_level']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label fw-semibold small">Introductory Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Hi <?= e($mentor['name']) ?>, I would like to learn this skill from you. In return, I can help teach Java / Graphic Design!"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold shadow-sm">
                            <i class="fas fa-paper-plane me-1"></i> Submit Learning Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>