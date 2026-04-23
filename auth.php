<?php
require_once '../config.php';  // changed from '../config.php'

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'register') {
        $fullname = trim($data['fullname']);
        $email = trim($data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'];

        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$fullname, $email, $password, $role]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['role'] = $role;
            $_SESSION['fullname'] = $fullname;
            echo json_encode(['success' => true, 'role' => $role]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
        }
    }
    elseif ($action === 'login') {
        $email = trim($data['email']);
        $password = $data['password'];
        $role = $data['role'];
        $captcha = trim($data['captcha'] ?? '');

        // Validate CAPTCHA
        if (!isset($_SESSION['captcha_code'])) {
            echo json_encode(['success' => false, 'message' => 'Valid CAPTCHA .']);
            exit();
        }
        if (strcasecmp($captcha, $_SESSION['captcha_code']) !== 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid CAPTCHA code']);
            exit();
        }
        // CAPTCHA is correct, clear it
        unset($_SESSION['captcha_code']);

        // Continue with login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['fullname'] = $user['fullname'];
            echo json_encode(['success' => true, 'role' => $user['role'], 'user_id' => $user['id'], 'fullname' => $user['fullname']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }
    elseif ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
    }
}
elseif ($method === 'GET') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'logged_in' => true,
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'fullname' => $_SESSION['fullname']
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}
?>