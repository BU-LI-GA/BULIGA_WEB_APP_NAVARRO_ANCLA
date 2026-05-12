<?php
// includes/header.php – Top navigation bar (included on every page)
// Expects $pageTitle to be set before including this file.
if (!isset($pageTitle)) $pageTitle = 'Buliga';
$role = currentRole();
$dashLink = $role === 'organizer' ? '/buliga/organizer/dashboard.php' : '/buliga/student/dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/svg+xml" href="/buliga/assets/icons/favicon.svg" />
    <title><?= htmlspecialchars($pageTitle) ?> · Buliga</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet" />
    <!-- Buliga CSS -->
    <link href="/buliga/assets/css/buliga.css" rel="stylesheet" />
</head>
<body>

<nav class="navbar navbar-expand-lg buliga-navbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand buliga-brand" href="<?= isLoggedIn() ? $dashLink : '/buliga/' ?>">
            <span class="brand-icon">🌿</span> Buliga
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="bi bi-list text-white fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php if ($role === 'student'): ?>
                    <li class="nav-item"><a class="nav-link" href="/buliga/student/dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/buliga/student/events.php"><i class="bi bi-calendar-event"></i> Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="/buliga/student/my-registrations.php"><i class="bi bi-bookmark-check"></i> My Registrations</a></li>
                <?php elseif ($role === 'organizer'): ?>
                    <li class="nav-item"><a class="nav-link" href="/buliga/organizer/dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/buliga/organizer/events.php"><i class="bi bi-calendar-event"></i> My Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="/buliga/organizer/create-event.php"><i class="bi bi-plus-circle"></i> Create Event</a></li>
                    <li class="nav-item"><a class="nav-link" href="/buliga/organizer/join-demo.php"><i class="bi bi-database"></i> JOIN Demo</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/buliga/student/events.php"><i class="bi bi-calendar-event"></i> Browse Events</a></li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item me-2">
                        <span class="badge role-badge"><?= ucfirst(htmlspecialchars($role)) ?></span>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-nav" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end buliga-dropdown">
                            <li><a class="dropdown-item" href="/buliga/<?= $role ?>/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/buliga/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/buliga/auth/login.php">Log In</a></li>
                    <li class="nav-item"><a class="btn btn-buliga ms-2" href="/buliga/auth/register.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Message -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show buliga-alert" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<main class="py-4">
<div class="page" id="page">