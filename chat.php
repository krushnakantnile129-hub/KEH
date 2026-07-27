<?php
$page_title = "Skill Chat";
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/functions.php';

$user_id =  $current_user['id'];

// Fetch all accepted requests for conversation list
$stmt = $pdo->prepare("
    SELECT lr.*, s.skill_name,
           u_other.id as partner_id, u_other.name as partner_name, u_other.profile_photo as partner_photo, u_other.college as partner_college
    FROM learning_requests lr
    JOIN skills s ON lr.skill_id = s.id
    JOIN users u_other ON (CASE WHEN lr.learner_id = ? THEN lr.mentor_id ELSE lr.learner_id END) = u_other.id
    WHERE (lr.learner_id = ? OR lr.mentor_id = ?) AND lr.status = 'accepted'
    ORDER BY lr.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

// Select current active conversation
$active_request_id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
if (!$active_request_id && !empty($conversations)) {
    $active_request_id = $conversations[0]['id'];
}

$active_conv = null;
$messages = [];

if ($active_request_id) {
    foreach ($conversations as $c) {
        if ($c['id'] == $active_request_id) {
            $active_conv = $c;
            break;
        }
    }

    if ($active_conv) {
        // Fetch message history
        $stmt = $pdo->prepare("
            SELECT m.*, u.name as sender_name, u.profile_photo as sender_photo
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.request_id = ?
            ORDER BY m.id ASC
        ");
        $stmt->execute([$active_request_id]);
        $messages = $stmt->fetchAll();

        // Mark unread messages as read
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->execute([$active_request_id, $user_id]);
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
    

    .keh-card {
        transition: box-shadow 0.3s ease, transform 0.3s ease;
    }

    /* Sidebar heading gets a subtle gradient text treatment */
    .col-md-4 .keh-card h5.text-purple,
    .col-lg-3 .keh-card h5.text-purple {
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #6366f1);
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: gradientShift 6s ease infinite;
    }

    @keyframes gradientShift {
        0%   { background-position: 0% center; }
        50%  { background-position: 100% center; }
        100% { background-position: 0% center; }
    }

    /* Conversation list items */
    .list-group-item-action {
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.3s ease;
    }

    .list-group-item-action:hover {
        transform: translateX(4px) scale(1.01);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.18);
    }

    .list-group-item-action.bg-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35);
    }

    .list-group-item-action img,
    .keh-card-header img {
        transition: transform 0.25s ease;
    }

    .list-group-item-action:hover img {
        transform: scale(1.08) rotate(-2deg);
    }

    /* Chat header bar */
    .chat-container .keh-card-header {
        background: linear-gradient(120deg, rgba(99,102,241,0.08), rgba(139,92,246,0.08));
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(99, 102, 241, 0.12);
        
  
    }

    .keh-card-header img {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
    }

    /* Video call button — soft pulsing gradient */
    .btn-success.rounded-pill {
        border: none;
        background: linear-gradient(270deg, #22c55e, #16a34a, #22c55e);
        background-size: 200% 200%;
        animation: pulseGradient 3s ease infinite;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-success.rounded-pill:hover {
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 6px 16px rgba(34, 197, 94, 0.35);
    }

    @keyframes pulseGradient {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .btn-outline-primary.rounded-pill {
        transition: all 0.25s ease;
    }

    .btn-outline-primary.rounded-pill:hover {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border-color: transparent;
        color: #fff;
        transform: translateY(-2px);
    }

    /* Chat bubbles fade + slide in */
    .chat-messages .d-flex.mb-3 {
          scrollbar-width: none;
        animation: bubbleIn 0.35s ease both;
    }

    @keyframes bubbleIn {
        from {
            opacity: 0;
            transform: translateY(10px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .chat-bubble {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .chat-bubble:hover {
        transform: translateY(-1px);
    }

    .chat-bubble-sent {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        box-shadow: 0 3px 10px rgba(99, 102, 241, 0.25);
    }

    .chat-bubble-received {
        background: #f1f2f6;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    /* Message input focus glow */
    #messageInput {
        transition: box-shadow 0.25s ease, border-color 0.25s ease;
    }

    #messageInput:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.18);
    }

    /* Send button */
    #chatForm .btn-primary.rounded-circle {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    #chatForm .btn-primary.rounded-circle:hover {
        transform: scale(1.1) rotate(8deg);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
    }

    /* Empty state icon gentle float */
    .fa-comments.fa-4x {
        animation: floatIcon 3s ease-in-out infinite;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    @keyframes floatIcon {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-8px); }
    }
</style>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar Conversation List -->
        <div class="col-md-4 col-lg-3">
            <div class="keh-card p-3 shadow-sm h-100">
                <h5 class="fw-bold mb-3 px-2"><i class="fas fa-comments text-purple me-2"></i> Conversations</h5>
                
                <?php if (empty($conversations)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted small mb-0">No active request chats.</p>
                        <a href="find-mentor.php" class="btn btn-sm btn-outline-primary rounded-pill mt-2">Find Mentors</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conv): ?>
                            <?php 
                                $is_selected = ($conv['id'] == $active_request_id);
                                $unread = get_unread_message_count($pdo, $user_id);
                            ?>
                            <a href="chat.php?request_id=<?= $conv['id'] ?>" class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-1 d-flex align-items-center gap-3 <?= $is_selected ? 'bg-primary text-white' : 'bg-light' ?>">
                                <img src="/KEH/uploads/profile_photos/<?= e($conv['partner_photo']) ?>" 
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($conv['partner_name']) ?>&background=6366f1&color=fff';"
                                     alt="Partner" class="rounded-circle object-fit-cover" width="42" height="42">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h6 class="fw-bold mb-0 text-truncate <?= $is_selected ? 'text-white' : 'text-dark' ?>"><?= e($conv['partner_name']) ?></h6>
                                    <span class="small opacity-75 d-block text-truncate"><?= e($conv['skill_name']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Chat Box Area -->
        <div class="col-md-8 col-lg-9">
            <?php if (!$active_conv): ?>
                <div class="keh-card p-5 text-center shadow-sm h-100 d-flex flex-column justify-content-center align-items-center">
                    <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                    <h4>Select a Conversation</h4>
                    <p class="text-muted small">Choose a mentor or student from the sidebar to start chatting.</p>
                </div>
            <?php else: ?>
                <div class="keh-card shadow-sm chat-container">
                    <!-- Chat Header -->
                    <div class="keh-card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <img src="/KEH/uploads/profile_photos/<?= e($active_conv['partner_photo']) ?>" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($active_conv['partner_name']) ?>&background=6366f1&color=fff';"
                                 alt="Partner" class="rounded-circle object-fit-cover" width="45" height="45">
                            <div>
                                <h6 class="fw-bold mb-0"><?= e($active_conv['partner_name']) ?></h6>
                                <span class="text-muted small"><i class="fas fa-book me-1 text-primary"></i> Skill: <?= e($active_conv['skill_name']) ?></span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" onclick="startInstantCall(<?= $active_conv['id'] ?>)" class="btn btn-success btn-sm rounded-pill shadow-xs">
                                <i class="fas fa-video me-1"></i> Start Video Call
                            </button>
                            <a href="my-sessions.php?action=schedule&request_id=<?= $active_conv['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                <i class="fas fa-calendar-plus me-1"></i> Schedule Session
                            </a>
                        </div>
                    </div>

                    <!-- Chat Messages Body -->
                    <div class="chat-messages" id="chatMessages">
                        <?php foreach ($messages as $i => $msg): ?>
                            <?php $is_me = ($msg['sender_id'] == $user_id); ?>
                            <div class="d-flex mb-3 <?= $is_me ? 'justify-content-end' : 'justify-content-start' ?>" style="animation-delay: <?= min($i * 0.03, 0.6) ?>s;">
                                <div class="chat-bubble <?= $is_me ? 'chat-bubble-sent' : 'chat-bubble-received' ?>">
                                    <div><?= nl2br(e($msg['message'])) ?></div>
                                    <span class="chat-time"><?= format_time($msg['created_at']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Chat Input Footer -->
                    <div class="p-3 bg-white border-top">
                        <form id="chatForm" data-request-id="<?= $active_conv['id'] ?>" data-receiver-id="<?= $active_conv['partner_id'] ?>" class="d-flex gap-2">
                            <input type="text" id="messageInput" class="form-control rounded-pill px-3" placeholder="Type your message..." required autocomplete="off">
                            <button type="submit" class="btn btn-primary rounded-circle px-3"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Real-time Chat JS Poller -->
<script src="/KEH/assets/js/chat.js"></script>
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

// Auto-scroll to latest message with a smooth animation on load
document.addEventListener('DOMContentLoaded', function () {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTo({ top: chatMessages.scrollHeight, behavior: 'smooth' });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>