<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(array('success' => false, 'error' => 'Not logged in'));
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(array('success' => false, 'error' => 'Invalid JSON data'));
    exit;
}

if (empty($data['receiver_id']) || empty($data['message'])) {
    echo json_encode(array('success' => false, 'error' => 'Missing receiver_id or message'));
    exit;
}

$conn = getDB();
$sender_id = getCurrentUserId();
$receiver_id = (int)$data['receiver_id'];
$message = trim($data['message']);
$rental_id = isset($data['rental_id']) ? (int)$data['rental_id'] : null;

// Validate receiver exists
$checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
if (!$checkStmt) {
    echo json_encode(array('success' => false, 'error' => 'Database prepare failed: ' . $conn->error));
    $conn->close();
    exit;
}

$checkStmt->bind_param("i", $receiver_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows === 0) {
    echo json_encode(array('success' => false, 'error' => 'Receiver not found'));
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// Insert message
$stmt = $conn->prepare("INSERT INTO chats (sender_id, receiver_id, message, rental_id, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
if (!$stmt) {
    echo json_encode(array('success' => false, 'error' => 'Database prepare failed: ' . $conn->error));
    $conn->close();
    exit;
}

$stmt->bind_param("iisi", $sender_id, $receiver_id, $message, $rental_id);
$result = $stmt->execute();

if ($result) {
    echo json_encode(array('success' => true, 'chat_id' => $conn->insert_id));
} else {
    echo json_encode(array('success' => false, 'error' => 'Database error: ' . $stmt->error));
}

$stmt->close();
$conn->close();
?>