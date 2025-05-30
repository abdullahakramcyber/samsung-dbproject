<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "dbproj");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Database connection failed']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => 'Not logged in']);
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $email, $phone);
    $stmt->fetch();
    $stmt->close();
    echo json_encode(['success' => true, 'id' => $user_id, 'name' => $name, 'email' => $email, 'phone' => $phone]);
    $conn->close();
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => 'Not logged in']);
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name && $email) {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['user_name'] = $name;
        echo json_encode(['success' => true, 'msg' => 'Profile updated']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Name and Email required']);
    }
    $conn->close();
    exit();
}
?>
