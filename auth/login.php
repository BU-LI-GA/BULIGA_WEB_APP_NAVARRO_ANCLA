<?php
// ============================================================
// auth/login.php – Login Page
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

// Already logged in? Redirect.
if (isLoggedIn()) {
     header('Location: /buliga/' . currentRole() . '/dashboard.php');
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

            setFlash('success', 'Welcome back, ' . $user['full_name'] . '! 🌿');
            header('Location: /buliga/' . $user['role'] . '/dashboard.php');
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
    <style>
        body{margin:0;background:#1a6b3c;font-family:'DM Sans',sans-serif}
        .auth-wrapper{min-height:100vh;background:linear-gradient(135deg,#1a6b3c 0%,#1e7d42 50%,#2d9b5a 100%);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
        .auth-card{background:#fff;border-radius:20px;padding:2.5rem;width:100%;max-width:440px;box-shadow:0 24px 60px rgba(0,0,0,.25)}
        .auth-logo{font-family:'Sora',sans-serif;font-weight:800;font-size:2rem;color:#1a6b3c;text-align:center;margin-bottom:.25rem}
        .auth-tagline{text-align:center;color:#6b7b6b;font-size:.88rem;margin-bottom:2rem}
        .btn-green{background:#2d9b5a;color:#fff;border:none;border-radius:999px;padding:.5rem 1.4rem;font-family:'Sora',sans-serif;font-weight:600}
        .btn-green:hover{background:#1a6b3c;color:#fff}
        .text-green{color:#2d9b5a!important}
        .fw-sora{font-family:'Sora',sans-serif}
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">🌿 Buliga</div>
        <p class="auth-tagline">Sign in to continue volunteering</p>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 py-2 mb-3 small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@buliga.edu" required autofocus />
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" required />
            </div>
            <button type="submit" class="btn btn-green w-100 py-2 fw-sora">
                <i class="bi bi-box-arrow-in-right me-2"></i>Log In
            </button>
        </form>

<hr class="my-3" />
        <p class="text-center small text-muted mb-0">
            Don't have an account?
            <a href="register.php" class="fw-sora text-green">Sign up</a>
        </p>
        <p class="text-center mt-3 mb-0">
            <a href="../" class="small text-muted"><i class="bi bi-arrow-left"></i> Back to home</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>