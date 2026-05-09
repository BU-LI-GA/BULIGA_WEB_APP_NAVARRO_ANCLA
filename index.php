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
