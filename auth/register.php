<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    header('Location: /' . currentRole() . '/dashboard.php');
    exit;
}
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? 'student';

    if (!$full_name || !$email || !$password || !$confirm) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student', 'organizer'])) {
        $error = 'Invalid role selected.';
    } else {
        $db   = getDB();
        // Check if email already exists
        $chk  = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'This email is already registered. Try logging in.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare(
                "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
            );
            $ins->execute([$full_name, $email, $hash, $role]);
            setFlash('success', 'Account created! Please log in.');
            header('Location: /auth/login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up · Buliga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet" />
    <link href="/assets/css/buliga.css" rel="stylesheet" />
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:500px;">
        <div class="auth-logo">🌿 Buliga</div>
        <p class="auth-tagline">Create your volunteer account</p>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 py-2 mb-3 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                       placeholder="e.g. Juan Dela Cruz" required />
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@buliga.edu" required />
            </div>
            <div class="mb-3">
                <label class="form-label">I am a… <span class="text-danger">*</span></label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" value="student"
                               id="role_student"
                               <?= (($_POST['role'] ?? 'student') === 'student') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_student">🎓 Student Volunteer</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="role" value="organizer"
                               id="role_organizer"
                               <?= (($_POST['role'] ?? '') === 'organizer') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_organizer">🧑‍💼 Event Organizer</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control"
                       placeholder="Min. 6 characters" required />
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" class="form-control"
                       placeholder="Repeat password" required />
            </div>
            <button type="submit" class="btn btn-green w-100 py-2 fw-sora">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>

        <hr class="my-3" />
        <p class="text-center small text-muted mb-0">
            Already have an account?
            <a href="/auth/login.php" class="fw-sora text-green">Log in</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>