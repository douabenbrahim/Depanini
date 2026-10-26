<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    // Get user details from database to ensure fresh data
    $conn = getDB();
    $stmt = $conn->prepare("SELECT id, email, first_name, last_name, role FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user) {
            echo json_encode(array(
                'success' => true,
                'user' => array(
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                    'role' => $user['role']
                )
            ));
        } else {
            echo json_encode(array('success' => false));
        }
    } else {
        echo json_encode(array('success' => false, 'error' => 'Database error'));
    }
    $conn->close();
} else {
    echo json_encode(array('success' => false));
}
?>