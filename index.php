<?php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';


if (isLoggedIn()) {
    header('Location: /' . currentRole() . '/dashboard.php');
    exit;
}

$db = getDB();

$evStmt = $db->prepare("
    SELECT e.id, e.title, e.description, e.location, e.event_date,
           e.slots, e.image_url, e.status,
           u.full_name AS organizer_name,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status != 'rejected') AS slots_taken
    FROM events e
    INNER JOIN users u ON e.organizer_id = u.id
    WHERE e.status = 'open' AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
    LIMIT 3
");
$evStmt->execute();
$featuredEvents = $evStmt->fetchAll();
$statsStmt = $db->query("
    SELECT
        (SELECT COUNT(*) FROM events)                                   AS total_events,
        (SELECT COUNT(*) FROM users WHERE role='student')               AS total_volunteers,
        (SELECT COALESCE(SUM(hours_rendered),0) FROM registrations
         WHERE status='completed')                                       AS total_hours
");
$siteStats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buliga – Volunteer Management Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet" />
    <link href="/assets/css/buliga.css" rel="stylesheet" />
    <style>
        /* Landing-page only extras */
        .how-step {
            text-align: center;
            padding: 2rem 1rem;
        }
        .how-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: var(--green-pale);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            border: 2px solid var(--green-light);
        }
        .how-step h5 { font-family:'Sora',sans-serif; font-weight:700; }

        .feature-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.75rem;
            height: 100%;
            transition: box-shadow .2s, transform .2s;
        }
        .feature-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }
        .feature-icon {
            font-size: 2.2rem;
            margin-bottom: .75rem;
            display: block;
        }
        .feature-card h6 { font-family:'Sora',sans-serif; font-weight:700; }

        .stat-pill {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: var(--radius);
            padding: .9rem 1.75rem;
            backdrop-filter: blur(8px);
        }
        .stat-pill .num {
            font-family:'Sora',sans-serif;
            font-weight:800;
            font-size:1.8rem;
            line-height:1;
            color:#fff;
        }
        .stat-pill .lbl {
            font-size:.78rem;
            color:rgba(255,255,255,.8);
            margin-top:4px;
        }

        .section-eyebrow {
            font-family:'Sora',sans-serif;
            font-size:.78rem;
            font-weight:700;
            letter-spacing:2px;
            text-transform:uppercase;
            color:var(--green-mid);
            display:block;
            margin-bottom:.5rem;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--green-deep) 0%, #1e7d42 100%);
            border-radius: 24px;
            padding: 3.5rem 2rem;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .cta-section::before {
            content:'🌿';
            position:absolute; right:3%; bottom:-10px;
            font-size:8rem; opacity:.07; pointer-events:none;
        }

        /* Animated underline on hero CTA */
        .hero-cta-wrap { display:flex; gap:1rem; flex-wrap:wrap; }

        /* Divider leaf */
        .leaf-divider {
            text-align:center;
            font-size:1.8rem;
            opacity:.25;
            margin:0.5rem 0;
            user-select:none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg buliga-navbar">
    <div class="container">
        <a class="navbar-brand buliga-brand" href="/">
            <span class="brand-icon">🌿</span> Buliga
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="bi bi-list text-white fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="#how">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="#events">Browse Events</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="/auth/login.php">Log In</a></li>
                <li class="nav-item ms-2">
                    <a class="btn btn-buliga" href="/auth/register.php">Get Started</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<section class="landing-hero">
    <div class="container position-relative" style="z-index:2;">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="section-eyebrow" style="color:rgba(255,255,255,.65);">
                    Community in Action
                </span>
                <h1 class="display-3 fw-bold mb-3" style="letter-spacing:-1.5px;">
                    Volunteer.<br/>Connect.<br/>
                    <span style="color:var(--amber);">Make Impact.</span>
                </h1>
                <p class="lead mb-4" style="max-width:500px;">
                    Buliga is your campus hub for finding volunteer opportunities, managing registrations,
                    and tracking your community service journey — all in one place.
                </p>
                <div class="hero-cta-wrap mb-5">
                    <a href="/auth/register.php" class="btn btn-buliga btn-lg px-4 py-2">
                        <i class="bi bi-person-plus me-2"></i>Join as Volunteer
                    </a>
                    <a href="#events" class="btn btn-lg px-4 py-2"
                       style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.4);border-radius:var(--radius-pill);">
                        <i class="bi bi-search me-2"></i>Browse Events
                    </a>
                </div>

                <!-- Live Stats -->
                <div class="d-flex flex-wrap gap-3">
                    <div class="stat-pill">
                        <span class="num"><?= number_format($siteStats['total_events']) ?></span>
                        <span class="lbl">Events Posted</span>
                    </div>
                    <div class="stat-pill">
                        <span class="num"><?= number_format($siteStats['total_volunteers']) ?></span>
                        <span class="lbl">Volunteers</span>
                    </div>
                    <div class="stat-pill">
                        <span class="num"><?= number_format($siteStats['total_hours']) ?>h</span>
                        <span class="lbl">Hours Rendered</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-center align-items-center">
                <div style="font-size:14rem;opacity:.18;user-select:none;line-height:1;">🌿</div>
            </div>
        </div>
    </div>
</section>
<section id="how" class="py-5" style="background:var(--green-pale);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow">Simple Process</span>
            <h2 class="fw-sora" style="font-size:2rem;">How Buliga Works</h2>
            <p class="text-muted">Three easy steps to start making a difference</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="how-step">
                    <div class="how-icon">📝</div>
                    <h5>1. Create an Account</h5>
                    <p class="text-muted">Sign up as a Student Volunteer or Event Organizer in under a minute.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="how-step">
                    <div class="how-icon">🔍</div>
                    <h5>2. Find Your Cause</h5>
                    <p class="text-muted">Browse and filter volunteer opportunities that match your passion and schedule.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="how-step">
                    <div class="how-icon">🤝</div>
                    <h5>3. Show Up & Serve</h5>
                    <p class="text-muted">Register, get approved, attend the event, and track your volunteer hours.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-eyebrow">Platform Features</span>
            <h2 class="fw-sora" style="font-size:2rem;">Everything You Need</h2>
            <p class="text-muted">Built for students and organizers alike</p>
        </div>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-4">
                <div class="feature-card">
                    <span class="feature-icon">🗂️</span>
                    <h6>Event Management</h6>
                    <p class="text-muted small mb-0">Organizers can create, edit, and manage events with image uploads and slot tracking.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="feature-card">
                    <span class="feature-icon">📋</span>
                    <h6>Volunteer Registration</h6>
                    <p class="text-muted small mb-0">Students register in one click and track their status from pending to completed.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="feature-card">
                    <span class="feature-icon">📢</span>
                    <h6>Announcements</h6>
                    <p class="text-muted small mb-0">Organizers broadcast updates and reminders directly to registered volunteers.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="feature-card">
                    <span class="feature-icon">📊</span>
                    <h6>Live Dashboards</h6>
                    <p class="text-muted small mb-0">Visual charts and stats give both students and organizers instant insights.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="feature-card">
                    <span class="feature-icon">🔍</span>
                    <h6>Search & Filter</h6>
                    <p class="text-muted small mb-0">Find events by title, location, or organizer with real-time search.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="feature-card">
                    <span class="feature-icon">🕐</span>
                    <h6>Hours Tracking</h6>
                    <p class="text-muted small mb-0">Log and monitor volunteer service hours for each completed event.</p>
                </div>
            </div>
        </div>
    </div>
</section>