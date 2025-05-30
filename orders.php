<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "dbproj");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Database connection failed']);
    exit();
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Not logged in']);
    exit();
}
$orderUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT order_id, orderDate, totalAmount FROM orders WHERE orderUserId=$orderUserId ORDER BY orderDate DESC, order_id DESC");
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Get items for this order
        $order_id = intval($row['order_id']);
        $items_result = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id=$order_id");
        $items_arr = [];
        while ($item_row = $items_result->fetch_assoc()) {
            $items_arr[] = "Product #" . $item_row['product_id'] . " (x" . $item_row['quantity'] . ")";
        }
        $row['items'] = implode(', ', $items_arr);
        $orders[] = $row;
    }
    echo json_encode(['success' => true, 'orders' => $orders]);
    $conn->close();
    exit();
}

$orderDate = $_POST['orderDate'] ?? '';
$totalAmount = $_POST['totalAmount'] ?? '';
if (!$orderDate || !$totalAmount) {
    echo json_encode(['success' => false, 'msg' => 'All fields required']);
    exit();
}
$stmt = $conn->prepare("INSERT INTO orders (orderUserId, orderDate, totalAmount) VALUES (?, ?, ?)");
$stmt->bind_param("isd", $orderUserId, $orderDate, $totalAmount);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'msg' => 'Order placed successfully.']);
} else {
    echo json_encode(['success' => false, 'msg' => 'Error: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>
