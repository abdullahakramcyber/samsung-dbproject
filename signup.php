<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "dbproj");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Database connection failed']);
    exit();
}
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'msg' => 'All fields required']);
    exit();
}
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'msg' => 'Registration successful!']);
} else {
    // Check for duplicate email error (MySQL error code 1062)
    if ($stmt->errno == 1062 || strpos($stmt->error, 'Duplicate') !== false) {
        echo json_encode(['success' => false, 'msg' => 'Email is already registered.']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error: ' . $stmt->error]);
    }
}
$stmt->close();
$conn->close();
?>
