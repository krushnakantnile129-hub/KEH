<?php
$page_title = "One-to-One Video Call";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$room_id = trim($_GET['room'] ?? '');
if (empty($room_id)) {
    header("Location: my-sessions.php?error=" . urlencode("Room ID missing."));
    exit();
}

// Fetch session and verify user participation
$stmt = $pdo->prepare("
    SELECT s.*, 
           u_learner.name as learner_name, u_learner.profile_photo as learner_photo,
           u_mentor.name as mentor_name, u_mentor.profile_photo as mentor_photo
    FROM sessions s
    JOIN users u_learner ON s.learner_id = u_learner.id
    JOIN users u_mentor ON s.mentor_id = u_mentor.id
    WHERE s.room_id = ?
");
$stmt->execute([$room_id]);
$session = $stmt->fetch();

$user_id = (int)$current_user['id'];

if (!$session || !in_array($user_id, [(int)$session['learner_id'], (int)$session['mentor_id']])) {
    header("Location: my-sessions.php?error=" . urlencode("Unauthorized to access this video room."));
    exit();
}

$is_caller = ($user_id === (int)$session['mentor_id']); // Mentor acts as initial offerer
$peer_id   = $is_caller ? (int)$session['learner_id'] : (int)$session['mentor_id'];
$peer_name = $is_caller ? $session['learner_name'] : $session['mentor_name'];
$peer_photo = $is_caller ? $session['learner_photo'] : $session['mentor_photo'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Video Room Header Card -->
    <div class="keh-card p-3 mb-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <img src="/KEH/uploads/profile_photos/<?= e($peer_photo) ?>" 
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($peer_name) ?>&background=6366f1&color=fff';"
                 alt="Peer" class="rounded-circle object-fit-cover" width="45" height="45">
            <div>
                <h5 class="fw-bold mb-0"><?= e($session['session_topic']) ?></h5>
                <span class="text-muted small me-2">Call with: <strong><?= e($peer_name) ?></strong></span>
                <span id="callStatusText" class="small font-weight-bold text-warning">Initializing WebRTC Media...</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="callTimer" class="badge bg-dark bg-opacity-75 rounded-pill px-3 py-2 text-white font-monospace"><i class="fas fa-clock text-warning me-1"></i> 00:00</span>
            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-circle me-1 text-white"></i> LIVE P2P</span>
        </div>
    </div>

    <!-- WebRTC Video Container -->
    <div id="webrtcCallContainer" 
         data-room-id="<?= e($room_id) ?>" 
         data-user-id="<?= $user_id ?>" 
         data-peer-id="<?= $peer_id ?>" 
         data-is-caller="<?= $is_caller ? 'true' : 'false' ?>">
        
        <div class="video-grid shadow-lg" id="videoGridElement">
            <!-- Remote Peer Video -->
            <video id="remoteVideo" autoplay playsinline class="bg-dark"></video>
            
            <!-- Local User Video Overlay -->
            <video id="localVideo" autoplay playsinline muted></video>

            <!-- Video Call Control Toolbar -->
            <div class="video-controls">
                <button type="button" id="toggleMic" class="btn-call-control btn-dark" title="Mute/Unmute Mic">
                    <i class="fas fa-microphone"></i>
                </button>
                <button type="button" id="toggleCam" class="btn-call-control btn-dark" title="Camera On/Off">
                    <i class="fas fa-video"></i>
                </button>
                <button type="button" id="toggleScreen" class="btn-call-control btn-dark" title="Share Screen">
                    <i class="fas fa-desktop"></i>
                </button>
                <button type="button" id="toggleFullscreen" class="btn-call-control btn-dark" title="Full Screen">
                    <i class="fas fa-expand"></i>
                </button>
                <button type="button" id="endCall" class="btn-call-control bg-danger" title="End Call">
                    <i class="fas fa-phone-slash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- WebRTC & Signaling Script -->
<script src="/KEH/assets/js/webrtc.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
