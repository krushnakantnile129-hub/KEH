<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);
$last_id = filter_input(INPUT_GET, 'last_id', FILTER_VALIDATE_INT) ?? 0;

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Request ID is required.']);
    exit();
}

// Verify authorization
$stmt = $pdo->prepare("SELECT * FROM learning_requests WHERE id = ? AND status = 'accepted'");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request || !in_array($user_id, [$request['learner_id'], $request['mentor_id']])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized chat session.']);
    exit();
}

// Fetch messages
$stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name, u.profile_photo as sender_photo 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.request_id = ? AND m.id > ?
    ORDER BY m.id ASC
");
$stmt->execute([$request_id, $last_id]);
$messages = $stmt->fetchAll();

// Mark messages sent to user as read
if (!empty($messages)) {
    $mark_stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND is_read = 0");
    $mark_stmt->execute([$request_id, $user_id]);
}

$formatted = [];
foreach ($messages as $msg) {
    $formatted[] = [
        'id'           => (int)$msg['id'],
        'sender_id'    => (int)$msg['sender_id'],
        'sender_name'  => e($msg['sender_name']),
        'sender_photo' => e($msg['sender_photo']),
        'message'      => e($msg['message']),
        'is_me'        => ((int)$msg['sender_id'] === (int)$user_id),
        'time'         => format_time($msg['created_at'])
    ];
}

echo json_encode(['success' => true, 'messages' => $formatted]);
?>
