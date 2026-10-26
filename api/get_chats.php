<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(array('success' => false, 'error' => 'Not logged in'));
    exit;
}

$conn = getDB();
$user_id = getCurrentUserId();
$otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($otherUserId) {
    // Get conversation with specific user
    $sql = "SELECT c.id, c.message, 
                   CASE WHEN c.sender_id = ? THEN 'sent' ELSE 'received' END as type,
                   DATE_FORMAT(c.created_at, '%H:%i') as time
            FROM chats c
            WHERE (c.sender_id = ? AND c.receiver_id = ?) 
               OR (c.sender_id = ? AND c.receiver_id = ?)
            ORDER BY c.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(array('success' => false, 'error' => 'Database prepare failed: ' . $conn->error));
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("iiiii", $user_id, $user_id, $otherUserId, $otherUserId, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = array();
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = array(
            'id' => $row['id'],
            'message' => htmlspecialchars($row['message']),
            'type' => $row['type'],
            'time' => $row['time']
        );
    }
    $stmt->close();
    
    // Mark messages as read
    $updateStmt = $conn->prepare("UPDATE chats SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    if ($updateStmt) {
        $updateStmt->bind_param("ii", $user_id, $otherUserId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    echo json_encode(array('success' => true, 'chats' => $messages));
    
} else {
    // Get all conversations with last message and unread count
    $sql = "SELECT 
                CASE WHEN c.sender_id = ? THEN c.receiver_id ELSE c.sender_id END as other_user_id,
                u.first_name, 
                u.last_name,
                (SELECT message FROM chats c2 
                 WHERE (c2.sender_id = other_user_id AND c2.receiver_id = ?) 
                    OR (c2.sender_id = ? AND c2.receiver_id = other_user_id) 
                 ORDER BY c2.created_at DESC LIMIT 1) as last_message,
                (SELECT DATE_FORMAT(created_at, '%H:%i') FROM chats c2 
                 WHERE (c2.sender_id = other_user_id AND c2.receiver_id = ?) 
                    OR (c2.sender_id = ? AND c2.receiver_id = other_user_id) 
                 ORDER BY c2.created_at DESC LIMIT 1) as last_time,
                SUM(CASE WHEN c.receiver_id = ? AND c.is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM chats c
            JOIN users u ON u.id = CASE WHEN c.sender_id = ? THEN c.receiver_id ELSE c.sender_id END
            WHERE c.sender_id = ? OR c.receiver_id = ?
            GROUP BY other_user_id, u.first_name, u.last_name
            ORDER BY MAX(c.created_at) DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(array('success' => false, 'error' => 'Database prepare failed: ' . $conn->error));
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("iiiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $conversations = array();
    
    while ($row = $result->fetch_assoc()) {
        $last_message = isset($row['last_message']) ? $row['last_message'] : '';
        $last_time = isset($row['last_time']) ? $row['last_time'] : '';
        $unread_count = isset($row['unread_count']) ? (int)$row['unread_count'] : 0;
        
        $conversations[] = array(
            'other_user_id' => $row['other_user_id'],
            'first_name' => htmlspecialchars($row['first_name']),
            'last_name' => htmlspecialchars($row['last_name']),
            'last_message' => htmlspecialchars($last_message),
            'last_time' => $last_time,
            'unread_count' => $unread_count
        );
    }
    $stmt->close();
    
    echo json_encode(array('success' => true, 'conversations' => $conversations));
}

$conn->close();
?>