<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "dbproj");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Database connection failed']);
    exit();
}
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $name, $hashed_password);
    $stmt->fetch();
    if (password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        echo json_encode(['success' => true, 'name' => $name, 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Invalid password.']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'No user found with this email.']);
}
$stmt->close();
$conn->close();
?>
