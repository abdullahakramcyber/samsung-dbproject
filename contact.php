<?php
header('Content-Type: application/json');

// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dbproj";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'msg' => 'Database connection failed']);
    exit();
}

// Accept both JSON and form POST
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
    $input = $_POST;
}
$name    = trim($input['name'] ?? '');
$email   = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode(['success' => false, 'msg' => 'All fields required.']);
    $conn->close();
    exit();
}

$name    = $conn->real_escape_string($name);
$email   = $conn->real_escape_string($email);
$message = $conn->real_escape_string($message);

// Insert data into contact table
$sql = "INSERT INTO contact(name, email, message) VALUES ('$name', '$email', '$message')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'msg' => 'Message sent successfully.']);
} else {
    echo json_encode(['success' => false, 'msg' => 'Error: ' . $conn->error]);
}

$conn->close();
exit();
?>
