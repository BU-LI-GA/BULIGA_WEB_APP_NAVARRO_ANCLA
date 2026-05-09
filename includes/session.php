<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}


function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        $redirect = $_SESSION['role'] === 'organizer'
            ? '/organizer/dashboard.php'
            : '/student/dashboard.php';
        header("Location: $redirect");
        exit;
    }
}


function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}


function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}


 
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}


function currentRole(): ?string {
    return $_SESSION['role'] ?? null;
}


function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}