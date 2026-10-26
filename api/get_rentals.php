<?php
require_once 'config.php';

if (!isLoggedIn()) {
    die(json_encode(array('success' => false, 'error' => 'Not logged in')));
}

$conn = getDB();
$user_id = getCurrentUserId();

$sql = "SELECT r.*, p.name as product_name, p.image, u.first_name, u.last_name, u.email as owner_email
        FROM rentals r
        JOIN products p ON r.product_id = p.id
        JOIN users u ON r.owner_id = u.id
        WHERE r.renter_id = ?
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rentals = array();

while ($row = $result->fetch_assoc()) {
    $rentals[] = $row;
}

echo json_encode(array('success' => true, 'rentals' => $rentals));

$stmt->close();
$conn->close();
?>