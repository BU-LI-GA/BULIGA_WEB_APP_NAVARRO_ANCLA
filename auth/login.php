<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';


if (isLoggedIn()) {
    header('Location: /' . currentRole() . '/dashboard.php');
    exit;
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $db   = getDB();
        // SQL: simple SELECT to find user by email
        $stmt = $db->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
            header('Location: /' . $user['role'] . '/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Log In · Buliga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet" />
    <link href="/assets/css/buliga.css" rel="stylesheet" />
</head>
<body>
<div class="auth-wrapper">
    <!-- Floating volunteer-themed background elements -->
    <div class="auth-dots" style="display:none;">
        <span style="--i:0;top:15%;left:10%;">🤝</span>
        <span style="--i:1;top:25%;left:80%;">🌿</span>
        <span style="--i:2;top:60%;left:5%;">👥</span>
        <span style="--i:3;top:70%;left:75%;">🤲</span>
        <span style="--i:4;top:40%;left:90%;">🌱</span>
        <span style="--i:5;top:85%;left:20%;">💚</span>
        <span style="--i:6;top:10%;left:50%;">🙌</span>
        <span style="--i:7;top:55%;left:95%;">🏡</span>
    </div>
    <div class="auth-card">
        <div class="auth-logo">Buliga</div>
        <p class="auth-tagline">Sign in to continue volunteering</p>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 py-2 mb-3 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@buliga.edu" autofocus autocomplete="off" />
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" autocomplete="current-password" />
            </div>
            <button type="submit" class="btn btn-green w-100 py-2 fw-sora">
                <i class="bi bi-box-arrow-in-right me-2"></i>Log In
            </button>
        </form>

        <hr class="my-3" />
        <p class="text-center small text-muted mb-0">
            Don't have an account?
            <a href="/auth/register.php" class="fw-sora text-green">Sign up</a>
        </p>
        <p class="text-center mt-3 mb-0">
            <a href="/" class="small text-muted"><i class="bi bi-arrow-left"></i> Back to home</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>