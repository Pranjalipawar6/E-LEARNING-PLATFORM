<?php
require_once '../config.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([]);
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($role === 'student') {
        // Get assignments for courses the student is enrolled in
        $stmt = $pdo->prepare("
            SELECT a.*, c.title as course_title,
                (SELECT status FROM submissions WHERE assignment_id = a.id AND student_id = ?) as submission_status
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            JOIN enrollments e ON e.course_id = c.id
            WHERE e.user_id = ?
            ORDER BY a.due_date ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        // Teacher view
        $stmt = $pdo->prepare("
            SELECT a.*, c.title as course_title
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            WHERE c.teacher_id = ?
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
elseif ($method === 'POST') {
    // ---------- NEW: Handle file upload for assignment submission (multipart/form-data) ----------
    if (isset($_POST['action']) && $_POST['action'] === 'submit' && $_SESSION['role'] === 'student') {
        $assignment_id = $_POST['assignment_id'];
        $student_id = $_SESSION['user_id'];

        // Check if already submitted
        $check = $pdo->prepare("SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ?");
        $check->execute([$assignment_id, $student_id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already submitted']);
            exit();
        }

        // Handle file upload
        $uploadDir = '../uploads/assignments/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['assignment_file']['name']);
        $filePath = $uploadDir . $fileName;
        $dbPath = 'uploads/assignments/' . $fileName;

        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $filePath)) {
            $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, status) VALUES (?, ?, ?, 'submitted')");
            $stmt->execute([$assignment_id, $student_id, $dbPath]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
        }
        exit();
    }

    // ---------- Existing JSON logic for creating assignments (teacher) ----------
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    if ($action === 'create' && $_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['course_id'], $data['title'], $data['description'], $data['due_date']]);
        echo json_encode(['success' => true]);
    }
}

elseif ($method === 'DELETE') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $assignment_id = $data['assignment_id'] ?? 0;
    // Verify teacher owns the course of this assignment
    $stmt = $pdo->prepare("
        DELETE a FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$assignment_id, $_SESSION['user_id']]);
    if ($stmt->rowCount()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not found or not yours']);
    }
}


?>