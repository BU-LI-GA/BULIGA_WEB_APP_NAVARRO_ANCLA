<?php
// ============================================================
// organizer/join-demo.php – SQL JOIN Demonstration Page
// Required for IT26: Shows INNER JOIN only
// with live query results and annotated SQL comments.
// ============================================================
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

$pageTitle = 'SQL JOIN Demo';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
    <h1><i class="bi bi-database me-2"></i>SQL JOIN Demonstration</h1>
         <p>Live query results showing INNER JOIN</p>
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

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>