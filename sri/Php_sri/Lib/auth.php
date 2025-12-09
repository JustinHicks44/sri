<?php
// Database-backed authentication for MyTickets
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

function auth_signup($username, $email, $password) {
    global $pdo;
    if (!$username || !$email || !$password) {
        return ['ok' => false, 'error' => 'All fields required'];
    }
    // Check for existing username/email
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Users WHERE Username = ? OR Email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        return ['ok' => false, 'error' => 'Username or email already exists'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'Customer';
    $stmt = $pdo->prepare('INSERT INTO Users (Username, Email, PasswordHash, Role, IsVerified) VALUES (?, ?, ?, ?, 1)');
    $ok = $stmt->execute([$username, $email, $hash, $role]);
    if (!$ok) {
        return ['ok' => false, 'error' => 'Signup failed'];
    }
    $user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    return ['ok' => true, 'user' => [
        'UserID' => $user_id,
        'Username' => $username,
        'Email' => $email,
        'Role' => $role
    ]];
}

function auth_signin($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM Users WHERE Username = ? OR Email = ?');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['PasswordHash'])) {
        return ['ok' => false, 'error' => 'Invalid credentials'];
    }
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['role'] = $user['Role'];
    return ['ok' => true, 'user' => $user];
}

function auth_signout() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_unset();
    session_destroy();
}

function auth_current_user() {
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare('SELECT * FROM Users WHERE UserID = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function auth_require_role($roles) {
    $user = auth_current_user();
    if (!$user || !in_array($user['Role'], (array)$roles, true)) {
        header('Location: /sri/Admin/login.php');
        exit;
    }
}
