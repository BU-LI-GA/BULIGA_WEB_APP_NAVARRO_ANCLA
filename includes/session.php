<?php
// ============================================================
// includes/session.php – Session management helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require a logged-in user; redirect to login if not.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Require a specific role. Redirect to dashboard if wrong role.
 */
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

/**
 * Set a one-time flash message.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message.
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if the current user is logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current user's role.
 */
function currentRole(): ?string {
    return $_SESSION['role'] ?? null;
}

/**
 * Current user id shorthand.
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}