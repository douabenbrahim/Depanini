<?php
require_once '../api/config.php';

if (!isAdmin()) {
    die(json_encode(array('success' => false, 'error' => 'Unauthorized')));
}

$conn = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($action === 'list') {
    $sql = "SELECT p.*, u.email as owner_email, u.first_name, u.last_name 
            FROM products p
            JOIN users u ON p.owner_id = u.id
            ORDER BY p.created_at DESC";
    $result = $conn->query($sql);
    $products = array();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode(array('success' => true, 'products' => $products));
}
elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    echo json_encode(array('success' => $result));
    $stmt->close();
}
elseif ($action === 'get' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    echo json_encode(array('success' => true, 'product' => $product));
    $stmt->close();
}
elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    $name = $data['name'];
    $category = $data['category'];
    $description = $data['description'];
    $price = $data['price'];
    
    $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, description = ?, price = ? WHERE id = ?");
    $stmt->bind_param("sssdi", $name, $category, $description, $price, $id);
    $result = $stmt->execute();
    echo json_encode(array('success' => $result));
    $stmt->close();
}

$conn->close();
?>