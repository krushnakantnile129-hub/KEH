<?php
$page_title = "Learning Requests";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$user_id = $current_user['id'];
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Accept / Reject POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $action     = $_POST['action'] ?? '';

    if ($request_id && in_array($action, ['accept', 'reject', 'cancel'])) {
        // Verify mentor ownership for accept/reject or learner for cancel
        $stmt = $pdo->prepare("SELECT * FROM learning_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();

        if ($req) {
            if ($action === 'accept' && $req['mentor_id'] == $user_id) {
                $stmt = $pdo->prepare("UPDATE learning_requests SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$request_id]);
                $msg = "Request accepted! You can now chat and schedule a session.";
            } elseif ($action === 'reject' && $req['mentor_id'] == $user_id) {
                $stmt = $pdo->prepare("UPDATE learning_requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$request_id]);
                $msg = "Request rejected.";
            } elseif ($action === 'cancel' && $req['learner_id'] == $user_id) {
                $stmt = $pdo->prepare("UPDATE learning_requests SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$request_id]);
                $msg = "Request cancelled.";
            }
        }
    }
}

// Fetch Received Requests (User is Mentor)
$stmt = $pdo->prepare("
    SELECT lr.*, s.skill_name, u.name as learner_name, u.profile_photo as learner_photo, u.college as learner_college
    FROM learning_requests lr
    JOIN skills s ON lr.skill_id = s.id
    JOIN users u ON lr.learner_id = u.id
    WHERE lr.mentor_id = ?
    ORDER BY lr.created_at DESC
");
$stmt->execute([$user_id]);
$received_requests = $stmt->fetchAll();

// Fetch Sent Requests (User is Learner)
$stmt = $pdo->prepare("
    SELECT lr.*, s.skill_name, u.name as mentor_name, u.profile_photo as mentor_photo, u.college as mentor_college
    FROM learning_requests lr
    JOIN skills s ON lr.skill_id = s.id
    JOIN users u ON lr.mentor_id = u.id
    WHERE lr.learner_id = ?
    ORDER BY lr.created_at DESC
");
$stmt->execute([$user_id]);
$sent_requests = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Learning Requests</h3>
            <p class="text-muted small mb-0">Manage incoming peer requests and track your sent learning applications.</p>
        </div>
        <a href="find-mentor.php" class="btn btn-primary rounded-pill px-4"><i class="fas fa-search me-1"></i> Find More Mentors</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

<?php
$active_tab = $_GET['tab'] ?? 'received';
if (!in_array($active_tab, ['received', 'sent'])) $active_tab = 'received';
?>

    <!-- Prominent Section Tab Navigation -->
    <div class="keh-card p-2 mb-4 bg-white shadow-sm rounded-4">
        <div class="row g-2">
            <div class="col-6">
                <a href="requests.php?tab=received" class="btn btn-lg w-100 rounded-3 text-start py-3 <?= $active_tab === 'received' ? 'btn-primary shadow-sm' : 'btn-light text-dark' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-5 fw-bold d-block"><i class="fas fa-inbox me-2"></i> Received Requests</span>
                            <span class="small opacity-75">Incoming requests from students wanting to learn from you</span>
                        </div>
                        <span class="badge rounded-pill fs-6 px-3 py-2 <?= $active_tab === 'received' ? 'bg-white text-primary fw-bold' : 'bg-primary text-white' ?>"><?= count($received_requests) ?></span>
                    </div>
                </a>
            </div>
            <div class="col-6">
                <a href="requests.php?tab=sent" class="btn btn-lg w-100 rounded-3 text-start py-3 <?= $active_tab === 'sent' ? 'btn-primary shadow-sm' : 'btn-light text-dark' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fs-5 fw-bold d-block"><i class="fas fa-paper-plane me-2"></i> Sent Requests</span>
                            <span class="small opacity-75">Outgoing learning requests you submitted to mentors</span>
                        </div>
                        <span class="badge rounded-pill fs-6 px-3 py-2 <?= $active_tab === 'sent' ? 'bg-white text-primary fw-bold' : 'bg-primary text-white' ?>"><?= count($sent_requests) ?></span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="tab-content" id="requestTabsContent">
        <!-- RECEIVED REQUESTS TAB -->
        <?php if ($active_tab === 'received'): ?>
            <div class="tab-pane fade show active" id="received">
                <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-inbox text-primary me-2"></i> Incoming Received Requests</h5>
                    <span class="text-muted small">Accept requests to enable instant chat &amp; video calls</span>
                </div>
                <?php if (empty($received_requests)): ?>
                    <div class="keh-card p-5 text-center">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5>No Received Requests Yet</h5>
                        <p class="text-muted small">When other students request to learn a skill from you, their applications will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($received_requests as $req): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="keh-card p-4 h-100 d-flex flex-column border-primary-subtle">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="/KEH/uploads/profile_photos/<?= e($req['learner_photo']) ?>" 
                                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($req['learner_name']) ?>&background=6366f1&color=fff';"
                                                 alt="Learner" class="rounded-circle object-fit-cover shadow-xs" width="48" height="48">
                                            <div>
                                                <h6 class="fw-bold mb-0"><?= e($req['learner_name']) ?></h6>
                                                <span class="text-muted text-xs"><?= e($req['learner_college']) ?></span>
                                            </div>
                                        </div>
                                        <?php 
                                            $badge = 'bg-warning';
                                            if ($req['status'] === 'accepted') $badge = 'bg-success';
                                            if ($req['status'] === 'rejected') $badge = 'bg-danger';
                                            if ($req['status'] === 'completed') $badge = 'bg-purple';
                                        ?>
                                        <span class="badge <?= $badge ?> rounded-pill text-capitalize px-3 py-1.5"><?= e($req['status']) ?></span>
                                    </div>

                                    <div class="mb-3 bg-light p-3 rounded-3 flex-grow-1">
                                        <span class="fw-bold small text-primary d-block mb-1"><i class="fas fa-book-open me-1"></i> Wants to learn: <?= e($req['skill_name']) ?></span>
                                        <p class="text-secondary small mb-0">"<?= e($req['message']) ?>"</p>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between pt-2 mt-auto border-top">
                                        <span class="text-muted text-xs"><?= format_time($req['created_at']) ?></span>

                                        <?php if ($req['status'] === 'pending'): ?>
                                            <div class="d-flex gap-2">
                                                <form action="requests.php?tab=received" method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="action" value="accept">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-xs"><i class="fas fa-check me-1"></i> Accept</button>
                                                </form>
                                                <form action="requests.php?tab=received" method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="fas fa-times me-1"></i> Reject</button>
                                                </form>
                                            </div>
                                        <?php elseif ($req['status'] === 'accepted'): ?>
                                            <div class="d-flex gap-2">
                                                <button type="button" onclick="startInstantCall(<?= $req['id'] ?>)" class="btn btn-sm btn-success rounded-pill px-3 shadow-xs">
                                                    <i class="fas fa-video me-1"></i> Call
                                                </button>
                                                <a href="chat.php?request_id=<?= $req['id'] ?>" class="btn btn-sm btn-purple rounded-pill px-3"><i class="fas fa-comments me-1"></i> Chat</a>
                                                <a href="my-sessions.php?action=schedule&request_id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fas fa-calendar-plus me-1"></i> Schedule</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- SENT REQUESTS TAB -->
            <div class="tab-pane fade show active" id="sent">
                <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-paper-plane text-primary me-2"></i> Sent Learning Applications</h5>
                    <span class="text-muted small">Requests you submitted to mentors</span>
                </div>
                <?php if (empty($sent_requests)): ?>
                    <div class="keh-card p-5 text-center">
                        <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
                        <h5>No Sent Requests</h5>
                        <p class="text-muted small">You haven't sent any learning requests to mentors yet.</p>
                        <a href="find-mentor.php" class="btn btn-sm btn-primary rounded-pill">Search Mentors</a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($sent_requests as $req): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="keh-card p-4 h-100 d-flex flex-column">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="/KEH/uploads/profile_photos/<?= e($req['mentor_photo']) ?>" 
                                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($req['mentor_name']) ?>&background=6366f1&color=fff';"
                                                 alt="Mentor" class="rounded-circle object-fit-cover shadow-xs" width="48" height="48">
                                            <div>
                                                <h6 class="fw-bold mb-0"><?= e($req['mentor_name']) ?></h6>
                                                <span class="text-muted text-xs"><?= e($req['mentor_college']) ?></span>
                                            </div>
                                        </div>
                                        <?php 
                                            $badge = 'bg-warning';
                                            if ($req['status'] === 'accepted') $badge = 'bg-success';
                                            if ($req['status'] === 'rejected') $badge = 'bg-danger';
                                            if ($req['status'] === 'completed') $badge = 'bg-purple';
                                        ?>
                                        <span class="badge <?= $badge ?> rounded-pill text-capitalize px-3 py-1.5"><?= e($req['status']) ?></span>
                                    </div>

                                    <div class="mb-3 bg-light p-3 rounded-3 flex-grow-1">
                                        <span class="fw-bold small text-primary d-block mb-1"><i class="fas fa-graduation-cap me-1"></i> Requested Skill: <?= e($req['skill_name']) ?></span>
                                        <p class="text-secondary small mb-0">"<?= e($req['message']) ?>"</p>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between pt-2 mt-auto border-top">
                                        <span class="text-muted text-xs"><?= format_time($req['created_at']) ?></span>

                                        <?php if ($req['status'] === 'pending'): ?>
                                            <form action="requests.php?tab=sent" method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Cancel</button>
                                            </form>
                                        <?php elseif ($req['status'] === 'accepted'): ?>
                                            <div class="d-flex gap-2">
                                                <button type="button" onclick="startInstantCall(<?= $req['id'] ?>)" class="btn btn-sm btn-success rounded-pill px-3 shadow-xs">
                                                    <i class="fas fa-video me-1"></i> Call
                                                </button>
                                                <a href="chat.php?request_id=<?= $req['id'] ?>" class="btn btn-sm btn-purple rounded-pill px-3"><i class="fas fa-comments me-1"></i> Chat</a>
                                                <a href="my-sessions.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Sessions</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
    </div>
</div>

<script>
function startInstantCall(requestId) {
    const formData = new FormData();
    formData.append('request_id', requestId);

    fetch('/KEH/api/start-instant-call.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.room_id) {
            window.location.href = '/KEH/video-call.php?room=' + encodeURIComponent(data.room_id);
        } else {
            alert('Could not start video call: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => console.error('Start call error:', err));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
