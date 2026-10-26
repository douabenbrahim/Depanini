<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(array('success' => false, 'error' => 'Not logged in'));
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(array('success' => false, 'error' => 'Invalid data'));
    exit;
}

$required = array('product_id', 'start_date', 'end_date', 'total_price');
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(array('success' => false, 'error' => "$field required"));
        exit;
    }
}

$conn = getDB();
$product_id = (int)$data['product_id'];
$renter_id = getCurrentUserId();
$start_date = $data['start_date'];
$end_date = $data['end_date'];
$total_price = (float)$data['total_price'];
$payment_method = isset($data['payment_method']) ? $data['payment_method'] : 'inperson';

// Fix payment_method values - ensure they match database expectations
if ($payment_method == 'cash') {
    $payment_method = 'inperson';
} elseif ($payment_method == 'card') {
    $payment_method = 'visa';
}

// Get product details
$stmt = $conn->prepare("SELECT owner_id, name, price, status FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(array('success' => false, 'error' => 'Product not found'));
    $conn->close();
    exit;
}

if ($product['status'] !== 'available') {
    echo json_encode(array('success' => false, 'error' => 'Product is not available'));
    $conn->close();
    exit;
}

if ($product['owner_id'] == $renter_id) {
    echo json_encode(array('success' => false, 'error' => 'Cannot rent your own item'));
    $conn->close();
    exit;
}

// Create rental
$status = 'pending';
$stmt = $conn->prepare("INSERT INTO rentals (product_id, renter_id, owner_id, start_date, end_date, total_price, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiisssss", $product_id, $renter_id, $product['owner_id'], $start_date, $end_date, $total_price, $payment_method, $status);
$result = $stmt->execute();

if ($result) {
    $rental_id = $conn->insert_id;
    
    // Update product status
    $updateStmt = $conn->prepare("UPDATE products SET status = 'rented' WHERE id = ?");
    $updateStmt->bind_param("i", $product_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Create chat message
    $message = "I've booked your item \"{$product['name']}\" from {$start_date} to {$end_date}. Total: {$total_price} DT. Payment: " . ($payment_method == 'inperson' ? 'Cash on pickup' : 'Card');
    $chatStmt = $conn->prepare("INSERT INTO chats (sender_id, receiver_id, message, rental_id) VALUES (?, ?, ?, ?)");
    $chatStmt->bind_param("iisi", $renter_id, $product['owner_id'], $message, $rental_id);
    $chatStmt->execute();
    $chatStmt->close();
    
    echo json_encode(array('success' => true, 'rental_id' => $rental_id));
} else {
    echo json_encode(array('success' => false, 'error' => 'Database error: ' . $stmt->error));
}

$stmt->close();
$conn->close();
?>