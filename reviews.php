<?php
$page_title = "Give Rating & Review";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$user_id = $current_user['id'];
$session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);

if (!$session_id) {
    header("Location: my-sessions.php");
    exit();
}

// Fetch session details
$stmt = $pdo->prepare("
    SELECT s.*, 
           u_mentor.name as mentor_name, u_mentor.profile_photo as mentor_photo
    FROM sessions s
    JOIN users u_mentor ON s.mentor_id = u_mentor.id
    WHERE s.id = ? AND s.learner_id = ? AND s.status = 'completed'
");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: my-sessions.php?error=" . urlencode("Completed session not found or review already submitted."));
    exit();
}

// Check if review already exists
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE session_id = ? AND reviewer_id = ?");
$stmt->execute([$session_id, $user_id]);
if ($stmt->fetch()) {
    header("Location: my-sessions.php?msg=" . urlencode("You have already reviewed this session. Thank you!"));
    exit();
}

$error = '';
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating      = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $review_text = sanitize($_POST['review_text'] ?? '');

    if (!$rating || $rating < 1 || $rating > 5 || empty($review_text)) {
        $error = "Please choose a rating from 1 to 5 stars and write a review.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (session_id, reviewer_id, reviewed_user_id, rating, review_text)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$session_id, $user_id, $session['mentor_id'], $rating, $review_text]);

            // If 5 stars, award +3 bonus points to mentor
            if ($rating == 5) {
                award_points($pdo, $session['mentor_id'], 3, "Received a 5-star rating for session: " . $session['session_topic']);
            }

            header("Location: profile.php?id=" . $session['mentor_id'] . "&msg=" . urlencode("Thank you for your rating & review!"));
            exit();
        } catch (Exception $e) {
            $error = "Failed to save review. " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="keh-card p-4 p-md-5 shadow-sm text-center">
                <img src="/KEH/uploads/profile_photos/<?= e($session['mentor_photo']) ?>" 
                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($session['mentor_name']) ?>&background=6366f1&color=fff';"
                     alt="Mentor" class="mentor-avatar shadow-sm mb-3" style="width: 80px; height: 80px;">
                
                <h4 class="fw-bold mb-1">Rate Your Session with <?= e($session['mentor_name']) ?></h4>
                <p class="text-muted small mb-4">Topic: <strong><?= e($session['session_topic']) ?></strong></p>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show text-start" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($msg): ?>
                    <div class="alert alert-success alert-dismissible fade show text-start" role="alert">
                        <i class="fas fa-check-circle me-1"></i> <?= e($msg) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="reviews.php?session_id=<?= $session_id ?>" method="POST" class="text-start">
                    <div class="mb-4 text-center">
                        <label class="form-label fw-semibold small d-block mb-2">Select Star Rating</label>
                        <div class="rating-select d-inline-flex gap-2 fs-3 text-warning">
                            <select name="rating" class="form-select text-center fw-bold fs-5 text-warning border-warning" required>
                                <option value="5" selected>⭐⭐⭐⭐⭐ (5/5 Excellent)</option>
                                <option value="4">⭐⭐⭐⭐ (4/5 Very Good)</option>
                                <option value="3">⭐⭐⭐ (3/5 Average)</option>
                                <option value="2">⭐⭐ (2/5 Below Average)</option>
                                <option value="1">⭐ (1/5 Poor)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="review_text" class="form-label fw-semibold small">Your Feedback &amp; Review</label>
                        <textarea class="form-control" id="review_text" name="review_text" rows="4" required placeholder="Write a few lines about how helpful the mentor was and what you learned..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold shadow-sm">
                        <i class="fas fa-star me-1"></i> Submit Review &amp; Award Points
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
