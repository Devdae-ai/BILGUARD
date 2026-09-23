<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/auth.php';

redirect_if_logged_in();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = get_pdo()->prepare(
        'SELECT id, name, password_hash FROM users WHERE email = ?'
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        header('Location: dashboard.php');
        exit;
    }

    $error = "That email and password combination doesn't match our records.";
}
?>