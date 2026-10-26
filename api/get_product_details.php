<?php
require_once 'config.php';

$product_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$product_id) {
    die(json_encode(array('success' => false, 'error' => 'Product ID required')));
}

$conn = getDB();

$stmt = $conn->prepare("SELECT p.*, u.first_name, u.last_name, u.email as owner_email, u.id as owner_id 
                        FROM products p 
                        JOIN users u ON p.owner_id = u.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if ($product) {
    echo json_encode(array('success' => true, 'product' => $product));
} else {
    echo json_encode(array('success' => false, 'error' => 'Product not found'));
}

$stmt->close();
$conn->close();
?>