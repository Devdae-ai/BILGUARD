<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function require_login(): void {
    if (!current_user_id()) {
        header('Location: login.php');
        exit;
    }
}

function redirect_if_logged_in(): void {
    if (current_user_id()) {
        header('Location: dashboard.php');
        exit;
    }
}

function current_user(): ?array {
    $id = current_user_id();
    if (!$id) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = get_pdo()->prepare('SELECT id, name, email, avatar, currency, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function flash_set(string $key, string $msg): void {
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}