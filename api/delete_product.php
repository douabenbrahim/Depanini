<?php
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(array('success' => false, 'error' => 'Not logged in'));
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    echo json_encode(array('success' => false, 'error' => 'Product ID required'));
    exit;
}

$conn = getDB();
$product_id = $data['id'];
$user_id = getCurrentUserId();
$is_admin = isAdmin();

// Check if user owns the product or is admin
$checkStmt = $conn->prepare("SELECT owner_id FROM products WHERE id = ?");
$checkStmt->bind_param("i", $product_id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$product = $result->fetch_assoc();

if (!$product || ($product['owner_id'] != $user_id && !$is_admin)) {
    $conn->close();
    echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
    exit;
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$result = $stmt->execute();

if ($result) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('success' => false, 'error' => 'Failed to delete: ' . $conn->error));
}

$stmt->close();
$conn->close();
?>