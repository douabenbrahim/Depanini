<?php
require_once 'config.php';

$conn = getDB();
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$product_id = isset($_GET['id']) ? $_GET['id'] : null;

// If specific product ID is requested
if ($product_id) {
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
    exit;
}

// Otherwise get all products
$sql = "SELECT p.*, u.first_name, u.last_name, u.email as owner_email, u.id as owner_id 
        FROM products p 
        JOIN users u ON p.owner_id = u.id 
        WHERE p.status = 'available'";

if ($category !== 'all' && $category !== '') {
    $sql .= " AND p.category = '" . mysqli_real_escape_string($conn, $category) . "'";
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
              OR p.description LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

$sql .= " ORDER BY p.created_at DESC";

$result = $conn->query($sql);
$products = array();

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode(array('success' => true, 'products' => $products));

$conn->close();
?>