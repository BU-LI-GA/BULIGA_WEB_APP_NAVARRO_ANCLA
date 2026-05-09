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
<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-database me-2"></i>SQL JOIN Demonstration</h1>
        <p>Live query results showing INNER, LEFT, RIGHT, and FULL OUTER JOIN — IT26 Requirement</p>
    </div>
</div>

<div class="container">

    <!-- Explanation Card -->
    <div class="buliga-card p-4 mb-5">
        <h5 class="fw-sora mb-3"><i class="bi bi-info-circle me-2 text-green"></i>About SQL JOINs</h5>
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded-3 bg-green-pale">
                    <div class="fw-sora text-green mb-1">INNER JOIN</div>
                    <div class="small text-muted">Only rows with matching values in <strong>both</strong> tables.</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded-3" style="background:#e8f0ff;">
                    <div class="fw-sora mb-1" style="color:#2c5cf7;">LEFT JOIN</div>
                    <div class="small text-muted">All rows from the <strong>left</strong> table + matched right rows (NULLs if no match).</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded-3" style="background:var(--amber-light);">
                    <div class="fw-sora mb-1" style="color:var(--soil);">RIGHT JOIN</div>
                    <div class="small text-muted">All rows from the <strong>right</strong> table + matched left rows (NULLs if no match).</div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="p-3 rounded-3" style="background:#fde8f8;">
                    <div class="fw-sora mb-1" style="color:#9b1da8;">FULL OUTER JOIN</div>
                    <div class="small text-muted">All rows from <strong>both</strong> tables, NULLs where no match exists.</div>
                </div>
            </div>
        </div>
    </div>

      <!-- 1. INNER JOIN -->
    <div class="mb-5">
        <div class="section-header">
            <h5 class="fw-sora">
                <span class="status-badge status-approved me-2">INNER JOIN</span>
                Registrations with Matching Student &amp; Event
            </h5>
        </div>
        <div class="p-3 rounded-3 mb-3" style="background:#1c2c1c;border-radius:var(--radius);">
            <pre class="mb-0" style="color:#5bbf85;font-size:.8rem;white-space:pre-wrap;">SELECT r.id, u.full_name AS student, e.title AS event, r.status
FROM registrations r
<span style="color:#f5a623;">INNER JOIN</span> users u  ON r.student_id = u.id  -- must match a user
<span style="color:#f5a623;">INNER JOIN</span> events e ON r.event_id   = e.id  -- must match an event
ORDER BY r.id LIMIT 8;</pre>
        </div>
        <div class="buliga-table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Reg ID</th>
                        <th data-sortable>Student</th>
                        <th data-sortable>Event</th>
                        <th data-sortable>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($innerResult as $row): ?>
                    <tr>
                        <td class="text-muted small">#<?= $row['reg_id'] ?></td>
                        <td><?= htmlspecialchars($row['student']) ?></td>
                        <td class="small"><?= htmlspecialchars($row['event']) ?></td>
                        <td><span class="status-badge status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="small text-muted mt-2">
            <i class="bi bi-lightbulb text-green me-1"></i>
            INNER JOIN returns only the <?= count($innerResult) ?> registrations where both a matching student AND a matching event exist.
            Orphaned rows (e.g., deleted users) would be excluded.
        </p>
    </div>