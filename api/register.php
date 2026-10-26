<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

$required = array('firstName', 'lastName', 'email', 'password');
foreach ($required as $field) {
    if (empty($data[$field])) {
        die(json_encode(array('success' => false, 'error' => "$field is required")));
    }
}

$email = trim($data['email']);
$password = password_hash($data['password'], PASSWORD_DEFAULT);
$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$dob = isset($data['dob']) ? $data['dob'] : null;
$gender = isset($data['gender']) ? $data['gender'] : null;

$conn = getDB();

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    die(json_encode(array('success' => false, 'error' => 'Email already registered')));
}
$stmt->close();

// Insert user
$stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, dob, gender, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
$stmt->bind_param("ssssss", $email, $password, $firstName, $lastName, $dob, $gender);
$result = $stmt->execute();

if ($result) {
    echo json_encode(array('success' => true, 'message' => 'Registration successful'));
} else {
    echo json_encode(array('success' => false, 'error' => 'Registration failed: ' . $stmt->error));
}

$stmt->close();
$conn->close();
?>