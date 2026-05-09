<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();
// ── 1. INNER JOIN ─────────────────────────────────────────
// Returns ONLY rows that have matching values in BOTH tables.
// Here: registrations that have both a matching student AND a matching event.
$innerSQL = "
SELECT
    r.id          AS reg_id,
    u.full_name   AS student,
    e.title       AS event,
    r.status
FROM registrations r
INNER JOIN users u  ON r.student_id  = u.id   -- must match a user
INNER JOIN events e ON r.event_id    = e.id   -- must match an event
ORDER BY r.id
LIMIT 8";
$innerResult = $db->query($innerSQL)->fetchAll();

// ── 2. LEFT JOIN ──────────────────────────────────────────
// Returns ALL rows from the LEFT table (events), plus matching
// rows from the RIGHT table (registrations). NULLs if no match.
$leftSQL = "
SELECT
    e.id          AS event_id,
    e.title       AS event_title,
    e.status      AS event_status,
    COUNT(r.id)   AS registration_count
FROM events e
LEFT JOIN registrations r ON r.event_id = e.id  -- keep events with 0 registrations
GROUP BY e.id
ORDER BY registration_count DESC
LIMIT 8";
$leftResult = $db->query($leftSQL)->fetchAll();

// ── 3. RIGHT JOIN ─────────────────────────────────────────
// Returns ALL rows from the RIGHT table (users/students), plus
// matching rows from the LEFT table (registrations). NULLs if
// the student has no registrations at all.
$rightSQL = "
SELECT
    u.full_name   AS student_name,
    u.email,
    COUNT(r.id)   AS total_registrations,
    COALESCE(SUM(r.hours_rendered), 0) AS total_hours
FROM registrations r
RIGHT JOIN users u ON r.student_id = u.id   -- keep all students, even if unregistered
WHERE u.role = 'student'
GROUP BY u.id
ORDER BY total_registrations DESC
LIMIT 8";
$rightResult = $db->query($rightSQL)->fetchAll();

// ── 4. FULL OUTER JOIN (simulated with UNION) ─────────────
// MySQL has no native FULL OUTER JOIN, so we simulate with
// LEFT JOIN UNION RIGHT JOIN to get all rows from both tables.
$fullSQL = "
-- Full Outer Join: All events + all students (matched where possible)
SELECT
    e.title       AS event_title,
    u.full_name   AS student_name,
    r.status      AS reg_status
FROM events e
LEFT JOIN registrations r ON r.event_id  = e.id
LEFT JOIN users u         ON r.student_id = u.id

UNION

SELECT
    e2.title      AS event_title,
    u2.full_name  AS student_name,
    r2.status     AS reg_status
FROM users u2
LEFT JOIN registrations r2 ON r2.student_id = u2.id
LEFT JOIN events e2        ON r2.event_id   = e2.id
WHERE u2.role = 'student'
ORDER BY event_title
LIMIT 12";
$fullResult = $db->query($fullSQL)->fetchAll();

$pageTitle = 'SQL JOIN Demo';
require_once __DIR__ . '/../includes/header.php';
?>