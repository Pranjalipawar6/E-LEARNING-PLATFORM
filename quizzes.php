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
        // Get quizzes for enrolled courses - fetch LATEST attempt
        $stmt = $pdo->prepare("
            SELECT q.*, c.title as course_title,
                (SELECT selected_answer FROM quiz_attempts WHERE quiz_id = q.id AND student_id = ? ORDER BY id DESC LIMIT 1) as user_answer,
                (SELECT is_correct FROM quiz_attempts WHERE quiz_id = q.id AND student_id = ? ORDER BY id DESC LIMIT 1) as is_correct
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            JOIN enrollments e ON e.course_id = c.id
            WHERE e.user_id = ?
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($quizzes as &$quiz) {
            $quiz['attempted'] = !is_null($quiz['user_answer']);
            $quiz['is_correct'] = (int)$quiz['is_correct']; // ✅ FIX: force integer
        }
        echo json_encode($quizzes);
    } else {
        // Teacher view
        $stmt = $pdo->prepare("
            SELECT q.*, c.title as course_title
            FROM quizzes q
            JOIN courses c ON q.course_id = c.id
            WHERE c.teacher_id = ?
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'create' && $_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("INSERT INTO quizzes (course_id, question, option1, option2, option3, option4, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['course_id'], $data['question'], $data['opt1'], $data['opt2'], $data['opt3'], $data['opt4'], $data['correct']]);
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'submit' && $_SESSION['role'] === 'student') {
        $quiz_id = (int)$data['quiz_id'];
        $selected = (int)$data['selected_answer'];
        $student_id = (int)$_SESSION['user_id'];

        // Check if already attempted
        $check = $pdo->prepare("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?");
        $check->execute([$quiz_id, $student_id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already attempted']);
            exit();
        }

        // Get correct answer as integer
        $stmt = $pdo->prepare("SELECT correct_answer FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        $correct = (int)$stmt->fetchColumn();

        // Strict comparison
        $is_correct = ($selected === $correct) ? 1 : 0;

        // Insert attempt
        $stmt = $pdo->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $student_id, $selected, $is_correct]);
        
        echo json_encode(['success' => true, 'is_correct' => $is_correct]);
    }
}
elseif ($method === 'DELETE') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $quiz_id = $data['quiz_id'] ?? 0;
    // Verify teacher owns the course of this quiz
    $stmt = $pdo->prepare("
        DELETE q FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        WHERE q.id = ? AND c.teacher_id = ?
    ");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    if ($stmt->rowCount()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not found or not yours']);
    }
}
?>