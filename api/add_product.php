<?php
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(array('success' => false, 'error' => 'Not logged in'));
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['name']) || empty($data['category']) || empty($data['price'])) {
    echo json_encode(array('success' => false, 'error' => 'Missing required fields'));
    exit;
}

$conn = getDB();

// Handle image upload
$imagePath = null;

if (!empty($data['image']) && strpos($data['image'], 'data:image') === 0) {
    $imageData = $data['image'];
    $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageBinary = base64_decode($imageData);
    
    $filename = uniqid() . '.jpg';
    $uploadDir = '../uploads/products/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    file_put_contents($uploadDir . $filename, $imageBinary);
    $imagePath = 'uploads/products/' . $filename;
}

$stmt = $conn->prepare("INSERT INTO products (name, category, subcategory, description, price, image, owner_id, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'available', NOW())");
$subcategory = isset($data['subcategory']) ? $data['subcategory'] : null;
$description = isset($data['description']) ? $data['description'] : '';
$owner_id = getCurrentUserId();

$stmt->bind_param("ssssdsi", $data['name'], $data['category'], $subcategory, $description, $data['price'], $imagePath, $owner_id);
$result = $stmt->execute();

if ($result) {
    echo json_encode(array('success' => true, 'product_id' => $conn->insert_id));
} else {
    echo json_encode(array('success' => false, 'error' => 'Failed to add product: ' . $conn->error));
}

$stmt->close();
$conn->close();
?>