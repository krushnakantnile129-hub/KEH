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
$request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
$receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
$message_text = trim($_POST['message'] ?? '');

if (!$request_id || !$receiver_id || empty($message_text)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit();
}

// Verify learning request status and user participation
$stmt = $pdo->prepare("SELECT * FROM learning_requests WHERE id = ? AND status = 'accepted'");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    echo json_encode(['success' => false, 'error' => 'Accepted learning request not found.']);
    exit();
}

if (!in_array($user_id, [$request['learner_id'], $request['mentor_id']])) {
    echo json_encode(['success' => false, 'error' => 'You are not authorized for this chat.']);
    exit();
}

// Verify receiver is the other party
$expected_receiver = ($user_id == $request['learner_id']) ? $request['mentor_id'] : $request['learner_id'];
if ($receiver_id != $expected_receiver) {
    echo json_encode(['success' => false, 'error' => 'Invalid message recipient.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, request_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $receiver_id, $request_id, $message_text]);

    echo json_encode([
        'success' => true,
        'message_id' => $pdo->lastInsertId(),
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error sending message.']);
}
?>
