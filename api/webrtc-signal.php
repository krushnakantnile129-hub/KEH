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
$action = $_REQUEST['action'] ?? '';
$room_id = trim($_REQUEST['room_id'] ?? '');

if (empty($room_id)) {
    echo json_encode(['success' => false, 'error' => 'Room ID is required.']);
    exit();
}

// Verify session room access
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE room_id = ?");
$stmt->execute([$room_id]);
$session = $stmt->fetch();

if (!$session || !in_array($user_id, [$session['learner_id'], $session['mentor_id']])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized session access.']);
    exit();
}

if ($action === 'send') {
    $receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
    $signal_type = trim($_POST['signal_type'] ?? '');
    $signal_data = $_POST['signal_data'] ?? '';

    if (!$receiver_id || !in_array($signal_type, ['offer', 'answer', 'ice-candidate']) || empty($signal_data)) {
        echo json_encode(['success' => false, 'error' => 'Invalid signaling parameters.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO webrtc_signals (room_id, sender_id, receiver_id, signal_type, signal_data) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$room_id, $user_id, $receiver_id, $signal_type, $signal_data]);

        echo json_encode(['success' => true, 'signal_id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to save signal.']);
    }
} elseif ($action === 'poll') {
    $last_id = filter_input(INPUT_GET, 'last_id', FILTER_VALIDATE_INT) ?? 0;

    $stmt = $pdo->prepare("
        SELECT id, sender_id, signal_type, signal_data, created_at 
        FROM webrtc_signals 
        WHERE room_id = ? AND receiver_id = ? AND id > ?
        ORDER BY id ASC
    ");
    $stmt->execute([$room_id, $user_id, $last_id]);
    $signals = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'signals' => $signals
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
?>
