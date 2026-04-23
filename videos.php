<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $course_id = $_GET['course_id'] ?? 0;
    if ($course_id) {
        $stmt = $pdo->prepare("SELECT * FROM videos WHERE course_id = ? ORDER BY created_at ASC");
        $stmt->execute([$course_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo json_encode([]);
    }
}
elseif ($method === 'POST') {
    // Teacher upload video
    if ($_SESSION['role'] !== 'teacher') {
        http_response_code(403);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("INSERT INTO videos (course_id, title, youtube_url) VALUES (?, ?, ?)");
    $stmt->execute([$data['course_id'], $data['title'], $data['youtube_url']]);
    echo json_encode(['success' => true]);
}
?>