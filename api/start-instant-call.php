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

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Request ID is required.']);
    exit();
}

// Verify accepted request
$stmt = $pdo->prepare("
    SELECT lr.*, s.skill_name 
    FROM learning_requests lr 
    JOIN skills s ON lr.skill_id = s.id 
    WHERE lr.id = ? AND lr.status = 'accepted'
");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request || !in_array($user_id, [$request['learner_id'], $request['mentor_id']])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized or request not accepted.']);
    exit();
}

// Check if an existing active/scheduled session room exists
$stmt = $pdo->prepare("SELECT room_id FROM sessions WHERE request_id = ? AND status IN ('scheduled', 'ongoing') ORDER BY id DESC LIMIT 1");
$stmt->execute([$request_id]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode(['success' => true, 'room_id' => $existing['room_id']]);
    exit();
}

// Create new instant session room
$room_id = 'ROOM-KEH-' . $request['learner_id'] . '-' . $request['mentor_id'] . '-' . rand(1000, 9999);
$topic = '1-on-1 Instant Video Session: ' . $request['skill_name'];

try {
    $stmt = $pdo->prepare("
        INSERT INTO sessions (request_id, learner_id, mentor_id, session_date, session_time, session_topic, room_id, status)
        VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, 'ongoing')
    ");
    $stmt->execute([$request_id, $request['learner_id'], $request['mentor_id'], $topic, $room_id]);

    echo json_encode(['success' => true, 'room_id' => $room_id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Could not create instant video call session.']);
}
?>
