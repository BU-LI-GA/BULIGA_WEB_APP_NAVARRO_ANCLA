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
    <div class="hero-float-wrap">
        <span style="--i:0;top:10%;left:8%;">🌿</span>
        <span style="--i:1;top:20%;left:85%;">🤝</span>
        <span style="--i:2;top:40%;left:5%;">👥</span>
        <span style="--i:3;top:65%;left:90%;">🌱</span>
        <span style="--i:4;top:75%;left:12%;">💚</span>
        <span style="--i:5;top:30%;left:92%;">🙌</span>
        <span style="--i:6;top:80%;left:45%;">🤲</span>
        <span style="--i:7;top:15%;left:50%;">🏡</span>
    </div>
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
<section id="events" class="py-5" style="background:var(--green-pale);">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-4">
            <div>
                <span class="section-eyebrow">Open Now</span>
                <h2 class="fw-sora mb-0" style="font-size:2rem;">Upcoming Events</h2>
            </div>
            <a href="/auth/register.php" class="btn btn-green">
                Register to Join <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if ($featuredEvents): ?>
        <div class="row g-4">
            <?php foreach ($featuredEvents as $ev): ?>
            <div class="col-md-4">
                <div class="buliga-card h-100">
                    <?php if ($ev['image_url']): ?>
                        <img src="<?= htmlspecialchars($ev['image_url']) ?>"
                             class="card-img-top" alt="Event" />
                    <?php else: ?>
                        <div class="event-card-placeholder">🌿</div>
                    <?php endif; ?>
                    <div class="p-3 d-flex flex-column">
                        <span class="status-badge status-open mb-2" style="width:fit-content;">Open</span>
                        <h6 class="fw-sora mb-1"><?= htmlspecialchars($ev['title']) ?></h6>
                        <p class="small text-muted mb-2">
                            <?= htmlspecialchars(substr($ev['description'], 0, 90)) ?>…
                        </p>
                        <div class="small text-muted mb-1">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= date('M d, Y', strtotime($ev['event_date'])) ?>
                        </div>
                        <div class="small text-muted mb-3">
                            <i class="bi bi-geo-alt me-1"></i>
                            <?= htmlspecialchars($ev['location']) ?>
                        </div>
                        <div class="mt-auto">
                            <a href="/auth/register.php" class="btn btn-green btn-sm w-100">
                                <i class="bi bi-plus-circle me-1"></i>Register to Join
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-icon">📅</span>
                <p>No upcoming events right now. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section id="about" class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="section-eyebrow">About Buliga</span>
                <h2 class="fw-sora mb-3" style="font-size:2rem;">Volunteering Made Simple</h2>
                <p class="text-muted mb-3">
                    Buliga (Filipino for <em>"partner/ally"</em>) is a web-based platform built to address
                    the college's volunteer recruitment challenges. It replaces fragmented messaging threads
                    and paper-based tracking with a single, organized hub.
                </p>
                <p class="text-muted mb-4">
                    Whether you're a student looking to make an impact or an organizer coordinating
                    community events, Buliga gives you the tools to connect, collaborate, and serve.
                </p>
                <a href="/auth/register.php" class="btn btn-green px-4">
                    <i class="bi bi-heart me-2"></i>Start Volunteering Today
                </a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;margin-bottom:.5rem;">🎓</div>
                            <div class="fw-sora">For Students</div>
                            <div class="small text-muted mt-1">Find events, register, and track hours</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;margin-bottom:.5rem;">🧑‍💼</div>
                            <div class="fw-sora">For Organizers</div>
                            <div class="small text-muted mt-1">Create events and manage volunteers</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;margin-bottom:.5rem;">📊</div>
                            <div class="fw-sora">Data-Driven</div>
                            <div class="small text-muted mt-1">Visual dashboards and analytics</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="feature-card text-center">
                            <div style="font-size:2.5rem;margin-bottom:.5rem;">🔐</div>
                            <div class="fw-sora">Secure</div>
                            <div class="small text-muted mt-1">Role-based access with bcrypt auth</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="cta-section">
            <h2 class="fw-sora mb-3" style="font-size:2.2rem;">
                Ready to Make a Difference?
            </h2>
            <p class="mb-4" style="opacity:.85;max-width:480px;margin:0 auto 1.5rem;">
                Join hundreds of students already volunteering through Buliga and start
                building your community impact today.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/auth/register.php" class="btn btn-buliga btn-lg px-4">
                    <i class="bi bi-person-plus me-2"></i>Create Free Account
                </a>
                <a href="/auth/login.php" class="btn btn-lg px-4"
                   style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.4);border-radius:var(--radius-pill);">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                </a>
            </div>
        </div>
    </div>
</section>
<section class="py-5">
    <div class="container">
        <div class="cta-section">
            <h2 class="fw-sora mb-3" style="font-size:2.2rem;">
                Ready to Make a Difference?
            </h2>
            <p class="mb-4" style="opacity:.85;max-width:480px;margin:0 auto 1.5rem;">
                Join hundreds of students already volunteering through Buliga and start
                building your community impact today.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/auth/register.php" class="btn btn-buliga btn-lg px-4">
                    <i class="bi bi-person-plus me-2"></i>Create Free Account
                </a>
                <a href="/auth/login.php" class="btn btn-lg px-4"
                   style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.4);border-radius:var(--radius-pill);">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                </a>
            </div>
        </div>
    </div>
</section>
<footer class="buliga-footer py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="buliga-brand-sm">🌿 Buliga</div>
                <div class="small mt-1" style="opacity:.6;">Volunteer Management Platform</div>
            </div>
            <div class="col-md-4 text-md-center mb-3 mb-md-0">
                <div class="d-flex justify-content-center gap-3">
                    <a href="#how"    class="text-muted small text-decoration-none">How It Works</a>
                    <a href="#events" class="text-muted small text-decoration-none">Events</a>
                    <a href="#about"  class="text-muted small text-decoration-none">About</a>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="small" style="opacity:.5;">IT26 Final Project &copy; <?= date('Y') ?></div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

