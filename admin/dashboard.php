<?php
require_once '../api/config.php';

if (!isAdmin()) {
    die(json_encode(array('success' => false, 'error' => 'Unauthorized')));
}

$conn = getDB();
$stats = array();

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$row = $result->fetch_assoc();
$stats['total_users'] = $row['count'];

// Banned users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_banned = 1");
$row = $result->fetch_assoc();
$stats['banned_users'] = $row['count'];

// Total products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$row = $result->fetch_assoc();
$stats['total_products'] = $row['count'];

// Active rentals
$result = $conn->query("SELECT COUNT(*) as count FROM rentals WHERE status IN ('pending', 'confirmed')");
$row = $result->fetch_assoc();
$stats['active_rentals'] = $row['count'];

// Total revenue
$result = $conn->query("SELECT SUM(total_price) as total FROM rentals WHERE status = 'confirmed'");
$row = $result->fetch_assoc();
$stats['total_revenue'] = $row['total'] ? $row['total'] : 0;

$conn->close();
echo json_encode(array('success' => true, 'stats' => $stats));
?>