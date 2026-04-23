<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $teacher_id = $_GET['teacher_id'] ?? null;
    if ($teacher_id) {
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(e.id) as students_count
            FROM courses c
            LEFT JOIN enrollments e ON e.course_id = c.id
            WHERE c.teacher_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$teacher_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        $stmt = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $title = $data['title'];
    $instructor = $data['instructor'];
    $price = $data['price'];
    $image = $data['image'] ?? '';
    $category = $data['category'] ?? 'General';
    $description = $data['description'] ?? '';
    $teacher_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO courses (title, instructor, price, image_url, category, description, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $instructor, $price, $image, $category, $description, $teacher_id]);
    echo json_encode(['success' => true, 'course_id' => $pdo->lastInsertId()]);
}

elseif ($method === 'DELETE') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = $data['course_id'] ?? 0;
    // Verify teacher owns this course
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Not your course']);
        exit();
    }
    // Delete course (cascade should handle assignments, quizzes, videos, enrollments if foreign keys set)
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    echo json_encode(['success' => true]);
}
?>