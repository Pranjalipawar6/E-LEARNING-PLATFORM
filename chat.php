<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// GET: fetch messages
if ($method === 'GET') {
    $course_id = $_GET['course_id'] ?? 0;
    $student_id = $_GET['student_id'] ?? null; // only for teacher view

    if (!$course_id) {
        echo json_encode(['error' => 'Course ID required']);
        exit();
    }

    // Verify course exists and get teacher_id
    $stmt = $pdo->prepare("SELECT teacher_id FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        echo json_encode(['error' => 'Course not found']);
        exit();
    }
    $teacher_id = $course['teacher_id'];

    // Student view: only their own conversation with teacher
    if ($role === 'student') {
        // Check enrollment
        $enrollStmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $enrollStmt->execute([$user_id, $course_id]);
        if (!$enrollStmt->fetch()) {
            echo json_encode(['error' => 'You are not enrolled in this course']);
            exit();
        }

        // Fetch messages between this student and the teacher for this course
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   u1.fullname as sender_name, 
                   u2.fullname as receiver_name
            FROM messages m
            JOIN users u1 ON m.sender_id = u1.id
            JOIN users u2 ON m.receiver_id = u2.id
            WHERE m.course_id = ? 
              AND ((m.sender_id = ? AND m.receiver_id = ?) 
                   OR (m.sender_id = ? AND m.receiver_id = ?))
            ORDER BY m.timestamp ASC
        ");
        $stmt->execute([$course_id, $user_id, $teacher_id, $teacher_id, $user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    // Teacher view: optionally filter by a specific student
    elseif ($role === 'teacher') {
        // Verify teacher owns this course
        if ($teacher_id != $user_id) {
            echo json_encode(['error' => 'You do not own this course']);
            exit();
        }

        if ($student_id) {
            // Fetch messages with a specific student
            $stmt = $pdo->prepare("
                SELECT m.*, 
                       u1.fullname as sender_name, 
                       u2.fullname as receiver_name
                FROM messages m
                JOIN users u1 ON m.sender_id = u1.id
                JOIN users u2 ON m.receiver_id = u2.id
                WHERE m.course_id = ? 
                  AND ((m.sender_id = ? AND m.receiver_id = ?) 
                       OR (m.sender_id = ? AND m.receiver_id = ?))
                ORDER BY m.timestamp ASC
            ");
            $stmt->execute([$course_id, $user_id, $student_id, $student_id, $user_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            // Get all students who have messaged in this course (grouped)
            $stmt = $pdo->prepare("
                SELECT DISTINCT 
                    u.id as student_id, 
                    u.fullname as student_name
                FROM messages m
                JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
                WHERE m.course_id = ? 
                  AND u.role = 'student'
                  AND u.id != ?
                ORDER BY u.fullname
            ");
            $stmt->execute([$course_id, $user_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } else {
        echo json_encode(['error' => 'Invalid role']);
    }
}

// POST: send a message
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = $data['course_id'] ?? 0;
    $message = trim($data['message'] ?? '');

    if (!$course_id || !$message) {
        echo json_encode(['error' => 'Course ID and message are required']);
        exit();
    }

    // Get course teacher
    $stmt = $pdo->prepare("SELECT teacher_id, title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        echo json_encode(['error' => 'Course not found']);
        exit();
    }
    $teacher_id = $course['teacher_id'];

    if ($role === 'student') {
        // Verify enrollment
        $enrollStmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $enrollStmt->execute([$user_id, $course_id]);
        if (!$enrollStmt->fetch()) {
            echo json_encode(['error' => 'You are not enrolled in this course']);
            exit();
        }

        // Student sends to teacher
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, course_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $teacher_id, $course_id, $message]);
        echo json_encode(['success' => true]);
    }
    elseif ($role === 'teacher') {
        // Teacher sends to a specific student
        $student_id = $data['student_id'] ?? 0;
        if (!$student_id) {
            echo json_encode(['error' => 'Student ID required for teacher reply']);
            exit();
        }

        // Verify teacher owns this course
        if ($teacher_id != $user_id) {
            echo json_encode(['error' => 'You do not own this course']);
            exit();
        }

        // Verify student is enrolled in this course
        $enrollStmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $enrollStmt->execute([$student_id, $course_id]);
        if (!$enrollStmt->fetch()) {
            echo json_encode(['error' => 'Student is not enrolled in this course']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, course_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $student_id, $course_id, $message]);
        echo json_encode(['success' => true]);
    }
    else {
        echo json_encode(['error' => 'Invalid role']);
    }
}
?>