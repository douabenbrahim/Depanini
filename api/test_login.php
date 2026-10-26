<?php
require_once 'config.php';

header('Content-Type: text/html');

// First, check if user is logged in
if (!isLoggedIn()) {
    echo "<h2 style='color:red'>❌ You are NOT logged in!</h2>";
    echo "<a href='/depanini/interface1.html'>Please login first</a>";
    exit;
}

$conn = getDB();
$renter_id = getCurrentUserId();

// Get user info
$userStmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$userStmt->bind_param("i", $renter_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

echo "<h1>Payment Test Page</h1>";
echo "<h2>Logged in as: " . $user['first_name'] . " " . $user['last_name'] . " (ID: $renter_id)</h2>";
echo "<hr>";

// Get all available products
$result = $conn->query("SELECT id, name, price, status, owner_id FROM products WHERE status = 'available'");

echo "<h3>Available Products to Rent:</h3>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Name</th><th>Price/day</th><th>Owner ID</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['price']} DT</td>";
    echo "<td>{$row['owner_id']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Test Rental Form</h3>";
echo "<form method='post' action=''>";
echo "<label>Product:</label><br>";
echo "<select name='product_id' style='padding:8px; margin:5px; width:200px;'>";
$result = $conn->query("SELECT id, name, price FROM products WHERE status = 'available'");
while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['name']} - {$row['price']} DT/day</option>";
}
echo "</select><br><br>";

echo "<label>Start Date:</label><br>";
echo "<input type='date' name='start_date' value='" . date('Y-m-d') . "' style='padding:8px; margin:5px;'><br><br>";

echo "<label>End Date:</label><br>";
echo "<input type='date' name='end_date' value='" . date('Y-m-d', strtotime('+3 days')) . "' style='padding:8px; margin:5px;'><br><br>";

echo "<button type='submit' name='test_rent' style='padding:10px 20px; background:#e67e22; color:white; border:none; border-radius:5px; cursor:pointer;'>🔧 TEST RENT NOW</button>";
echo "</form>";

// Handle form submission
if (isset($_POST['test_rent'])) {
    $product_id = (int)$_POST['product_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    echo "<hr>";
    echo "<h3>Test Result:</h3>";
    
    // Get product details
    $productStmt = $conn->prepare("SELECT owner_id, name, price FROM products WHERE id = ?");
    $productStmt->bind_param("i", $product_id);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    $product = $productResult->fetch_assoc();
    
    if (!$product) {
        echo "<p style='color:red'>❌ Product not found!</p>";
        $conn->close();
        exit;
    }
    
    // Calculate days and total
    $days = ceil((strtotime($end_date) - strtotime($start_date)) / 86400);
    if ($days <= 0) $days = 1;
    $total_price = $days * $product['price'];
    
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Product</td><td>{$product['name']}</td></tr>";
    echo "<tr><td>Owner ID</td><td>{$product['owner_id']}</td></tr>";
    echo "<tr><td>Renter ID</td><td>$renter_id</td></tr>";
    echo "<tr><td>Start Date</td><td>$start_date</td></tr>";
    echo "<tr><td>End Date</td><td>$end_date</td></tr>";
    echo "<tr><td>Days</td><td>$days</td></tr>";
    echo "<tr><td>Total Price</td><td>$total_price DT</td></tr>";
    echo "</table>";
    
    // Check if user is trying to rent their own item
    if ($product['owner_id'] == $renter_id) {
        echo "<p style='color:red'>❌ You cannot rent your own item!</p>";
        $conn->close();
        exit;
    }
    
    // Insert rental
    $status = 'pending';
    $payment_method = 'inperson';
    
    $insertStmt = $conn->prepare("INSERT INTO rentals (product_id, renter_id, owner_id, start_date, end_date, total_price, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("iiisssss", $product_id, $renter_id, $product['owner_id'], $start_date, $end_date, $total_price, $payment_method, $status);
    
    if ($insertStmt->execute()) {
        $rental_id = $conn->insert_id;
        echo "<p style='color:green; font-weight:bold;'>✅ RENTAL CREATED SUCCESSFULLY!</p>";
        echo "<p>Rental ID: $rental_id</p>";
        
        // Update product status
        $updateStmt = $conn->prepare("UPDATE products SET status = 'rented' WHERE id = ?");
        $updateStmt->bind_param("i", $product_id);
        if ($updateStmt->execute()) {
            echo "<p style='color:green'>✅ Product status updated to 'rented'</p>";
        }
        $updateStmt->close();
        
        echo "<p><a href='/depanini/interface2.html'>Go back to marketplace</a></p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create rental!</p>";
        echo "<p>Error: " . $insertStmt->error . "</p>";
    }
    $insertStmt->close();
}

$conn->close();
?>