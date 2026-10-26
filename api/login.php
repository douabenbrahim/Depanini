<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data) || empty($data['email']) || empty($data['password'])) {
    echo json_encode(array('success' => false, 'error' => 'Email and password required'));
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

$conn = getDB();

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // Check if user is banned
    if (isset($user['is_banned']) && $user['is_banned'] == 1) {
        echo json_encode(array('success' => false, 'error' => 'Your account has been banned. Please contact admin.'));
        $conn->close();
        exit;
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['user_role'] = $user['role'];
    
    // Return user data - frontend will handle redirect
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
    echo json_encode(array('success' => false, 'error' => 'Invalid email or password'));
}

$stmt->close();
$conn->close();
?>