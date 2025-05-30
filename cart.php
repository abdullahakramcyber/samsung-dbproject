<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "dbproj");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Database connection failed']);
    exit();
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Ensure cart_items table exists (independent)
$conn->query("CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    UNIQUE KEY (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Ensure order_items table exists (independent)
$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
)");

$method = $_SERVER['REQUEST_METHOD'];
$input = [];
if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_POST;
    }
    $action = $input['action'] ?? '';
    if (!$user_id) {
        echo json_encode(['success' => false, 'msg' => 'Please login']);
        $conn->close();
        exit();
    }

    // ADD TO CART
    if ($action === 'add') {
        $product_id = intval($input['product_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 1);
        if ($product_id < 1 || $quantity < 1) {
            echo json_encode(['success' => false, 'msg' => 'Invalid product or quantity']);
            $conn->close();
            exit();
        }
        // Insert or update quantity
        $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
        $conn->close();
        exit();
    }

    // REMOVE FROM CART
    if ($action === 'remove') {
        $product_id = intval($input['product_id'] ?? 0);
        if ($product_id < 1) {
            echo json_encode(['success' => false, 'msg' => 'Invalid product']);
            $conn->close();
            exit();
        }
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id=? AND product_id=?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
        $conn->close();
        exit();
    }

    // CLEAR CART
    if ($action === 'clear') {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
        $conn->close();
        exit();
    }

    // CHECKOUT (create order and clear cart)
    if ($action === 'checkout') {
        // Get cart items
        $cart = [];
        $result = $conn->query("SELECT product_id, quantity FROM cart_items WHERE user_id=$user_id");
        while ($row = $result->fetch_assoc()) {
            $cart[] = $row;
        }
        if (empty($cart)) {
            echo json_encode(['success' => false, 'msg' => 'Cart is empty']);
            $conn->close();
            exit();
        }
        $totalAmount = floatval($input['totalAmount'] ?? 0);
        $orderDate = date('Y-m-d');
        $stmt = $conn->prepare("INSERT INTO orders (orderUserId, orderDate, totalAmount) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $user_id, $orderDate, $totalAmount);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert order items
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
        foreach ($cart as $item) {
            $pid = intval($item['product_id']);
            $qty = intval($item['quantity']);
            $stmt_item->bind_param("iii", $order_id, $pid, $qty);
            $stmt_item->execute();
        }
        $stmt_item->close();

        // Clear cart
        $conn->query("DELETE FROM cart_items WHERE user_id=$user_id");
        echo json_encode(['success' => true, 'order_id' => $order_id]);
        $conn->close();
        exit();
    }

    // Unknown action
    echo json_encode(['success' => false, 'msg' => 'Invalid action']);
    $conn->close();
    exit();
}

// GET: Return cart items for user
if ($method === 'GET') {
    if (!$user_id) {
        echo json_encode(['success' => true, 'cart' => []]);
        $conn->close();
        exit();
    }
    $result = $conn->query("SELECT product_id, quantity FROM cart_items WHERE user_id=$user_id");
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = $row;
    }
    echo json_encode(['success' => true, 'cart' => $cart]);
    $conn->close();
    exit();
}

// Fallback
echo json_encode(['success' => false, 'msg' => 'Invalid request']);
$conn->close();
exit();
?>
