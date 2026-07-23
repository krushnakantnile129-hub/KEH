<?php
$page_title = "Learning Sessions";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$user_id = $current_user['id'];
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Create/Schedule Session Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_session') {
    $request_id    = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $session_date  = $_POST['session_date'] ?? '';
    $session_time  = $_POST['session_time'] ?? '';
    $session_topic = sanitize($_POST['session_topic'] ?? '');

    if (!$request_id || empty($session_date) || empty($session_time) || empty($session_topic)) {
        $error = "Please fill in all session scheduling details.";
    } else {
        // Verify user participation in request
        $stmt = $pdo->prepare("SELECT * FROM learning_requests WHERE id = ? AND status = 'accepted'");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();

        if (!$req || !in_array($user_id, [$req['learner_id'], $req['mentor_id']])) {
            $error = "Unauthorized to schedule for this request.";
        } else {
            $room_id = 'ROOM-KEH-' . $req['learner_id'] . '-' . $req['mentor_id'] . '-' . rand(100, 999);
            
            $stmt = $pdo->prepare("
                INSERT INTO sessions (request_id, learner_id, mentor_id, session_date, session_time, session_topic, room_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')
            ");
            $stmt->execute([$request_id, $req['learner_id'], $req['mentor_id'], $session_date, $session_time, $session_topic, $room_id]);
            
            $msg = "Session scheduled successfully! Room generated: " . $room_id;
        }
    }
}

// Handle Session Completion Confirmation Action
if (isset($_GET['action']) && $_GET['action'] === 'confirm') {
    $session_id = filter_input(INPUT_GET, 'session_id', FILTER_VALIDATE_INT);
    if ($session_id) {
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $sess = $stmt->fetch();

        if ($sess && in_array($user_id, [$sess['learner_id'], $sess['mentor_id']])) {
            $is_learner = ($user_id == $sess['learner_id']);
            
            if ($is_learner) {
                $stmt = $pdo->prepare("UPDATE sessions SET learner_confirmed = 1 WHERE id = ?");
                $stmt->execute([$session_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE sessions SET mentor_confirmed = 1 WHERE id = ?");
                $stmt->execute([$session_id]);
            }

            // Check if BOTH confirmed now
            $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            $updated_sess = $stmt->fetch();

            if ($updated_sess['learner_confirmed'] && $updated_sess['mentor_confirmed'] && $updated_sess['status'] !== 'completed') {
                // Mark completed & award points
                $stmt = $pdo->prepare("UPDATE sessions SET status = 'completed' WHERE id = ?");
                $stmt->execute([$session_id]);

                // Also update request status
                $stmt = $pdo->prepare("UPDATE learning_requests SET status = 'completed' WHERE id = ?");
                $stmt->execute([$updated_sess['request_id']]);

                // Award +10 points to mentor for teaching session
                award_points($pdo, $updated_sess['mentor_id'], 10, "Taught learning session: " . $updated_sess['session_topic']);
                // Award +5 points to learner for completing learning session
                award_points($pdo, $updated_sess['learner_id'], 5, "Completed learning session: " . $updated_sess['session_topic']);

                if ($is_learner) {
                    header("Location: reviews.php?session_id=" . $session_id . "&msg=" . urlencode("Session completed! Points awarded. Please leave a review for your mentor."));
                    exit();
                } else {
                    $msg = "Both participants confirmed! Session marked completed. +10 Points awarded!";
                }
            } else {
                $msg = "You confirmed completion! Waiting for the other participant to confirm.";
            }
        }
    }
}

// Fetch all user sessions
$stmt = $pdo->prepare("
    SELECT s.*, 
           u_learner.name as learner_name, u_learner.profile_photo as learner_photo,
           u_mentor.name as mentor_name, u_mentor.profile_photo as mentor_photo,
           (SELECT COUNT(*) FROM reviews WHERE session_id = s.id AND reviewer_id = ?) as review_submitted
    FROM sessions s
    JOIN users u_learner ON s.learner_id = u_learner.id
    JOIN users u_mentor ON s.mentor_id = u_mentor.id
    WHERE s.learner_id = ? OR s.mentor_id = ?
    ORDER BY s.session_date DESC, s.session_time DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$all_sessions = $stmt->fetchAll();

// Fetch accepted requests for scheduling form
$stmt = $pdo->prepare("
    SELECT lr.*, s.skill_name, u_other.name as partner_name
    FROM learning_requests lr
    JOIN skills s ON lr.skill_id = s.id
    JOIN users u_other ON (CASE WHEN lr.learner_id = ? THEN lr.mentor_id ELSE lr.learner_id END) = u_other.id
    WHERE (lr.learner_id = ? OR lr.mentor_id = ?) AND lr.status = 'accepted'
");
$stmt->execute([$user_id, $user_id, $user_id]);
$accepted_requests = $stmt->fetchAll();

$preselected_request_id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Learning Sessions</h3>
            <p class="text-muted small mb-0">Schedule, join video calls, and confirm completed peer sessions.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="collapse" data-bs-target="#scheduleCollapse">
            <i class="fas fa-calendar-plus me-1"></i> Schedule New Session
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Schedule Session Collapse Form -->
    <div class="collapse <?= ($preselected_request_id || isset($_GET['action']) && $_GET['action'] === 'schedule') ? 'show' : '' ?> mb-4" id="scheduleCollapse">
        <div class="keh-card p-4 bg-white shadow-sm border-primary">
            <h5 class="fw-bold mb-3"><i class="fas fa-clock text-primary me-2"></i> Schedule a Peer Learning Session</h5>
            
            <?php if (empty($accepted_requests)): ?>
                <p class="text-muted small mb-0">You don't have any accepted learning requests yet. Find a mentor and get your request accepted to schedule a session!</p>
            <?php else: ?>
                <form action="my-sessions.php" method="POST" class="row g-3">
                    <input type="hidden" name="action" value="create_session">
                    
                    <div class="col-md-4">
                        <label for="request_id" class="form-label fw-semibold small">Accepted Request / Partner</label>
                        <select class="form-select" id="request_id" name="request_id" required>
                            <option value="">-- Choose Connection --</option>
                            <?php foreach ($accepted_requests as $ar): ?>
                                <option value="<?= $ar['id'] ?>" <?= ($preselected_request_id == $ar['id']) ? 'selected' : '' ?>>
                                    <?= e($ar['partner_name']) ?> - <?= e($ar['skill_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="session_topic" class="form-label fw-semibold small">Session Topic / Goal</label>
                        <input type="text" class="form-control" id="session_topic" name="session_topic" placeholder="e.g. Core OOP Principles &amp; Examples" required>
                    </div>

                    <div class="col-md-2">
                        <label for="session_date" class="form-label fw-semibold small">Date</label>
                        <input type="date" class="form-control" id="session_date" name="session_date" min="<?= date('Y-m-d') ?>" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="session_time" class="form-label fw-semibold small">Time</label>
                        <input type="time" class="form-control" id="session_time" name="session_time" required value="16:00">
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fas fa-check me-1"></i> Save &amp; Create Session</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sessions List -->
    <?php if (empty($all_sessions)): ?>
        <div class="keh-card p-5 text-center">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <h5>No Sessions Found</h5>
            <p class="text-muted small">Schedule a session with your peer mentor to start your video call.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($all_sessions as $sess): ?>
                <?php 
                    $is_mentor = ($sess['mentor_id'] == $user_id);
                    $partner_name = $is_mentor ? $sess['learner_name'] : $sess['mentor_name'];
                    $partner_photo = $is_mentor ? $sess['learner_photo'] : $sess['mentor_photo'];
                    $my_confirmed = $is_mentor ? $sess['mentor_confirmed'] : $sess['learner_confirmed'];
                    $other_confirmed = $is_mentor ? $sess['learner_confirmed'] : $sess['mentor_confirmed'];
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="keh-card p-4 h-100 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-light text-dark border"><i class="fas fa-calendar me-1"></i> <?= date('M j, Y', strtotime($sess['session_date'])) ?> at <?= date('g:i A', strtotime($sess['session_time'])) ?></span>
                            <?php 
                                $statusBadge = 'bg-primary';
                                if ($sess['status'] === 'completed') $statusBadge = 'bg-success';
                                if ($sess['status'] === 'cancelled') $statusBadge = 'bg-danger';
                            ?>
                            <span class="badge <?= $statusBadge ?> rounded-pill text-capitalize"><?= e($sess['status']) ?></span>
                        </div>

                        <h5 class="fw-bold mb-2"><?= e($sess['session_topic']) ?></h5>
                        
                        <div class="d-flex align-items-center gap-2 mb-3 bg-light p-2 rounded-3">
                            <img src="/KEH/uploads/profile_photos/<?= e($partner_photo) ?>" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($partner_name) ?>&background=6366f1&color=fff';"
                                 alt="Partner" class="rounded-circle object-fit-cover" width="38" height="38">
                            <div>
                                <span class="fw-semibold small d-block"><?= e($partner_name) ?></span>
                                <span class="text-muted text-xs"><?= $is_mentor ? 'Learner Student' : 'Mentor Student' ?></span>
                            </div>
                        </div>

                        <div class="mt-auto border-top pt-3">
                            <?php if ($sess['status'] !== 'completed' && $sess['status'] !== 'cancelled'): ?>
                                <div class="d-flex gap-2 mb-2">
                                    <a href="video-call.php?room=<?= urlencode($sess['room_id']) ?>" class="btn btn-success btn-sm w-100 rounded-pill">
                                        <i class="fas fa-video me-1"></i> Join Video Call
                                    </a>
                                </div>
                                <?php if (!$my_confirmed): ?>
                                    <a href="my-sessions.php?action=confirm&session_id=<?= $sess['id'] ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill" onclick="return confirm('Confirm that this learning session took place successfully?');">
                                        <i class="fas fa-check-circle me-1"></i> Confirm Session Completed
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-light btn-sm w-100 rounded-pill text-success disabled"><i class="fas fa-check me-1"></i> You Confirmed</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-success small fw-bold d-block text-center mb-2"><i class="fas fa-check-double me-1"></i> Session Completed &amp; Points Logged!</span>
                                <?php if (!$is_mentor && !$sess['review_submitted']): ?>
                                    <a href="reviews.php?session_id=<?= $sess['id'] ?>" class="btn btn-warning btn-sm w-100 rounded-pill font-weight-bold">
                                        <i class="fas fa-star me-1"></i> Leave Review &amp; Rating
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
