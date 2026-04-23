<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['courses' => []]);
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("
        SELECT c.*, e.progress 
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.user_id = ?
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([$user_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['courses' => $courses]);
}

elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = $data['course_id'];
    $user_id = $_SESSION['user_id'];

    // Check if already enrolled
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already enrolled']);
        exit();
    }

    // Insert enrollment
    $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, progress) VALUES (?, ?, 0)");
    $stmt->execute([$user_id, $course_id]);
    echo json_encode(['success' => true]);
}

elseif ($method === 'PUT') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = $data['course_id'];
    $progress = $data['progress'];
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$progress, $user_id, $course_id]);
    echo json_encode(['success' => true]);
}

elseif ($method === 'DELETE') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = $data['course_id'] ?? 0;
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    echo json_encode(['success' => true]);
}
?>