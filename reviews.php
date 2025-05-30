<?php
session_start();
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "dbproj");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept both JSON and form POST
    $input = [];
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_POST;
    }
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => 'Not logged in']);
        exit();
    }
    $reviewUserId = $_SESSION['user_id'];
    $rating = $input['rating'] ?? '';
    $comment = $input['comment'] ?? '';
    if (!$rating || !$comment) {
        echo json_encode(['success' => false, 'msg' => 'All fields required']);
        exit();
    }
    $stmt = $conn->prepare("INSERT INTO reviews (reviewUserId, rating, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $reviewUserId, $rating, $comment);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'msg' => 'Review submitted']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT r.rating, r.comment, r.created_at, u.name FROM reviews r JOIN users u ON r.reviewUserId = u.id ORDER BY r.created_at DESC");
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    echo json_encode(['success' => true, 'reviews' => $reviews]);
    $conn->close();
    exit();
}
?>
