<?php
// ============================================================
// student/dashboard.php – Student Volunteer Dashboard
// Demonstrates: INNER JOIN, LEFT JOIN, aggregation, Chart.js
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('student');

$db  = getDB();
$uid = currentUserId();

// ============================================================
// QUERY 1: My Recent Registrations
// JOIN TYPE: INNER JOIN (registrations × events)
// PURPOSE:  Show only registrations where the student has a
//           valid event. Excludes orphaned registrations (should
//           be impossible due to FK, but INNER JOIN guarantees data integrity).
// RESULT:   Rows exist ONLY when BOTH registration AND event match.
// ============================================================
$regStmt = $db->prepare("
    SELECT
        r.id              AS reg_id,
        r.status,
        r.hours_rendered,
        r.registered_at,
        e.title,
        e.event_date,
        e.location,
        e.status          AS event_status
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id           -- INNER JOIN: both tables must match
    WHERE r.student_id = ?                             -- Filter to THIS student
    ORDER BY e.event_date DESC
    LIMIT 5
");
$regStmt->execute([$uid]);
$recentRegs = $regStmt->fetchAll();

// ============================================================
// QUERY 2: Upcoming Open Events with My Registration Status
// JOIN TYPE: LEFT JOIN (events ← registrations) + INNER JOIN (events → users)
// PURPOSE:  Show ALL open, future events plus whether THIS student
//           has registered. LEFT JOIN preserves events with no
//           registration; reg_status will be NULL if not registered.
// PATTERN:  LEFT JOIN with additional filter on the ON clause
//           (r.student_id = ?) ensures we only get THIS student's
//           registration if it exists.
// ============================================================
$upcomingStmt = $db->prepare("
    SELECT
        e.id,
        e.title,
        e.event_date,
        e.location,
        e.slots,
        e.status          AS event_status,
        r.status          AS reg_status,              -- NULL if student hasn't registered
        u.full_name       AS organizer_name
    FROM events e
    LEFT JOIN registrations r                          -- LEFT JOIN: keep ALL events
        ON r.event_id = e.id AND r.student_id = ?      -- only my registration (if any)
    INNER JOIN users u ON e.organizer_id = u.id        -- INNER JOIN: must have organizer
    WHERE e.status = 'open'
      AND e.event_date >= CURDATE()                   -- Only future events
    ORDER BY e.event_date ASC
    LIMIT 4
");
$upcomingStmt->execute([$uid]);
$upcomingEvents = $upcomingStmt->fetchAll();

// ============================================================
// QUERY 3: Student Registration Statistics
// JOIN TYPE: None (single table)
// PURPOSE:  Aggregate statistics from registrations table only.
// ============================================================
$statsStmt = $db->prepare("
    SELECT
        COUNT(*)                                              AS total_regs,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'approved'  THEN 1 ELSE 0 END) AS approved,
        COALESCE(SUM(hours_rendered), 0)                      AS total_hours
    FROM registrations
    WHERE student_id = ?
");
$statsStmt->execute([$uid]);
$stats = $statsStmt->fetch();

// Chart data preparation
$chartLabels = ['Pending', 'Approved', 'Completed', 'Rejected'];
$chartData   = [
    (int)$stats['pending'],
    (int)$stats['approved'],
    (int)$stats['completed'],
    0   // rejected – could fetch separately if needed
];

// ============================================================
// QUERY 4: My Announcements (Multi-table INNER JOIN)
// JOIN TYPE: INNER JOIN × 3 tables
// PURPOSE:  Show announcements ONLY for events THIS student has
//           registered for. Requires announcement → event link,
//           event → registration link, and announcement → author link.
// RESULT:   Only announcements from registered events appear.
// ============================================================
$annStmt = $db->prepare("
    SELECT
        a.title        AS ann_title,
        a.body,
        a.created_at,
        e.title        AS event_title,
        u.full_name    AS author
    FROM announcements a
    INNER JOIN events e        ON a.event_id  = e.id     -- Event must exist
    INNER JOIN registrations r ON r.event_id  = e.id     -- Student must be registered
    INNER JOIN users u         ON a.author_id = u.id    -- Author must exist
    WHERE r.student_id = ?                              -- Only MY registrations
    ORDER BY a.created_at DESC
    LIMIT 5
");
$annStmt->execute([$uid]);
$announcements = $annStmt->fetchAll();

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-grid-1x2 me-2"></i>My Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>! Here's your volunteer overview.</p>
    </div>
</div>

<div class="container">

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_regs'] ?></div>
                <div class="stat-label">Total Registered</div>
                <i class="bi bi-calendar-check stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['approved'] ?></div>
                <div class="stat-label">Approved</div>
                <i class="bi bi-check-circle stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['completed'] ?></div>
                <div class="stat-label">Completed</div>
                <i class="bi bi-trophy stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_hours'], 1) ?>h</div>
                <div class="stat-label">Hours Rendered</div>
                <i class="bi bi-clock stat-icon"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Left: Upcoming Events + Recent Registrations -->
        <div class="col-lg-8">

            <!-- Upcoming Events -->
            <div class="section-header">
                <h5><i class="bi bi-calendar-event me-2 text-green"></i>Open Events Near You</h5>
                <a href="/student/events.php" class="btn btn-outline-buliga btn-sm">Browse All</a>
            </div>

            <?php if ($upcomingEvents): ?>
                <div class="row g-3 mb-4">
                    <?php foreach ($upcomingEvents as $ev): ?>
                    <div class="col-sm-6">
                        <div class="buliga-card p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="status-badge status-<?= $ev['event_status'] ?>">
                                    <?= ucfirst($ev['event_status']) ?>
                                </span>
                                <?php if ($ev['reg_status']): ?>
                                    <span class="status-badge status-<?= $ev['reg_status'] ?>">
                                        <?= ucfirst($ev['reg_status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h6 class="fw-sora mb-1" style="font-size:.95rem">
                                <?= htmlspecialchars($ev['title']) ?>
                            </h6>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('M d, Y', strtotime($ev['event_date'])) ?>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?= htmlspecialchars($ev['location']) ?>
                            </div>
                            <?php if (!$ev['reg_status']): ?>
                                <a href="/student/event-detail.php?id=<?= $ev['id'] ?>"
                                   class="btn btn-green btn-sm w-100">
                                    <i class="bi bi-plus-circle me-1"></i>Register
                                </a>
                            <?php else: ?>
                                <a href="/student/event-detail.php?id=<?= $ev['id'] ?>"
                                   class="btn btn-outline-buliga btn-sm w-100">View Details</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state buliga-card mb-4">
                    <span class="empty-icon">📅</span>
                    <p>No upcoming events available at the moment.</p>
                    <a href="/student/events.php" class="btn btn-green btn-sm">Browse All Events</a>
                </div>
            <?php endif; ?>

            <!-- Recent Registrations Table -->
            <div class="section-header">
                <h5><i class="bi bi-bookmark-check me-2 text-green"></i>My Recent Registrations</h5>
                <a href="/student/my-registrations.php" class="btn btn-outline-buliga btn-sm">View All</a>
            </div>

            <?php if ($recentRegs): ?>
            <div class="buliga-table mb-4">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th data-sortable>Event</th>
                            <th data-sortable>Date</th>
                            <th data-sortable>Status</th>
                            <th data-sortable>Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentRegs as $r): ?>
                        <tr>
                            <td class="fw-sora" style="font-size:.9rem">
                                <?= htmlspecialchars($r['title']) ?>
                            </td>
                            <td class="small text-muted">
                                <?= date('M d, Y', strtotime($r['event_date'])) ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $r['status'] ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($r['hours_rendered'], 1) ?>h</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state buliga-card">
                    <span class="empty-icon">🌱</span>
                    <p>You haven't registered for any events yet.</p>
                    <a href="/student/events.php" class="btn btn-green btn-sm">Find Events</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Charts + Announcements -->
        <div class="col-lg-4">

            <!-- Doughnut Chart: Registration Status -->
            <div class="chart-container mb-4">
                <div class="chart-title"><i class="bi bi-pie-chart me-2"></i>Registrations by Status</div>
                <canvas id="statusChart" height="220"></canvas>
            </div>

            <!-- Announcements -->
            <div class="section-header">
                <h5><i class="bi bi-megaphone me-2 text-green"></i>Announcements</h5>
            </div>

            <?php if ($announcements): ?>
                <?php foreach ($announcements as $a): ?>
                <div class="announcement-card">
                    <div class="ann-title"><?= htmlspecialchars($a['ann_title']) ?></div>
                    <div class="ann-meta">
                        <?= htmlspecialchars($a['event_title']) ?> · <?= date('M d', strtotime($a['created_at'])) ?>
                    </div>
                    <p class="small mb-0 mt-1"><?= nl2br(htmlspecialchars($a['body'])) ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-icon">📢</span>
                    <p class="small">No announcements yet. Register for events to see updates.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
makeDoughnut('statusChart',
    <?= json_encode($chartLabels) ?>,
    <?= json_encode($chartData) ?>,
    ['#f5a623', '#2d9b5a', '#2c5cf7', '#e74c3c']
);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>