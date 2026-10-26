<?php
require_once '../api/config.php';

if (!isAdmin()) {
    die(json_encode(array('success' => false, 'error' => 'Unauthorized')));
}

$conn = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($action === 'list') {
    $result = $conn->query("SELECT id, email, first_name, last_name, role, is_banned, created_at FROM users ORDER BY created_at DESC");
    $users = array();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(array('success' => true, 'users' => $users));
} 
elseif ($action === 'ban' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("UPDATE users SET is_banned = 1 WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    echo json_encode(array('success' => $result));
    $stmt->close();
}
elseif ($action === 'unban' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    echo json_encode(array('success' => $result));
    $stmt->close();
}
elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    echo json_encode(array('success' => $result));
    $stmt->close();
}

$conn->close();
?>