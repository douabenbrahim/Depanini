<?php
require_once '../api/config.php';

if (!isAdmin()) {
    die(json_encode(array('success' => false, 'error' => 'Unauthorized')));
}

$conn = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if ($action === 'list') {
    $sql = "SELECT r.*, p.name as product_name, u1.email as renter_email, u2.email as owner_email 
            FROM rentals r
            JOIN products p ON r.product_id = p.id
            JOIN users u1 ON r.renter_id = u1.id
            JOIN users u2 ON r.owner_id = u2.id
            ORDER BY r.created_at DESC";
    $result = $conn->query($sql);
    $rentals = array();
    
    while ($row = $result->fetch_assoc()) {
        $rentals[] = $row;
    }
    
    echo json_encode(array('success' => true, 'rentals' => $rentals));
}
elseif ($action === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    $stmt = $conn->prepare("UPDATE rentals SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $result = $stmt->execute();
    echo json_encode(array('success' => $result));
    $stmt->close();
}

$conn->close();
?>