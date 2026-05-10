<?php
// ============================================================
// organizer/dashboard.php – Organizer Dashboard
// Demonstrates: INNER JOIN, LEFT JOIN, RIGHT JOIN, aggregation,
//               Chart.js bar + doughnut + line charts
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();

// ── INNER JOIN: Organizer's events with registration counts ──
// Only events created by this organizer (intersection of events + users).
$evStmt = $db->prepare("
    SELECT
        e.id,
        e.title,
        e.event_date,
        e.status,
        e.slots,
        COUNT(r.id)                                        AS total_regs,
        SUM(CASE WHEN r.status = 'approved'  THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN r.status = 'pending'   THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) AS completed
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id        -- LEFT JOIN: include events with 0 registrations
    WHERE e.organizer_id = ?
    GROUP BY e.id
    ORDER BY e.event_date DESC
");
$evStmt->execute([$uid]);
$myEvents = $evStmt->fetchAll();

// ── Summary Stats ──────────────────────────────────────────
$totalEvents   = count($myEvents);
$totalVols     = array_sum(array_column($myEvents, 'total_regs'));
$totalApproved = array_sum(array_column($myEvents, 'approved'));
$openEvents    = count(array_filter($myEvents, fn($e) => $e['status'] === 'open'));

// ── RIGHT JOIN demo: All registrations → Students ──────────
// RIGHT JOIN: show all registrations even if a student record
// were somehow missing (defensive; also demonstrates RIGHT JOIN).
$volStmt = $db->prepare("
    SELECT
        u.full_name,
        u.email,
        e.title        AS event_title,
        r.status       AS reg_status,
        r.hours_rendered,
        r.registered_at
    FROM events e
    INNER JOIN registrations r  ON r.event_id  = e.id     -- INNER JOIN: only real events
    RIGHT JOIN users u          ON r.student_id = u.id     -- RIGHT JOIN: keep all students even if no reg
    WHERE e.organizer_id = ?
      AND u.role = 'student'
    ORDER BY r.registered_at DESC
    LIMIT 8
");
$volStmt->execute([$uid]);
$recentVols = $volStmt->fetchAll();

// ── Chart 1: Registrations per event (bar) ─────────────────
$barLabels = array_map(fn($e) => substr($e['title'], 0, 20), $myEvents);
$barData   = array_map(fn($e) => (int)$e['total_regs'], $myEvents);

// ── Chart 2: Event status distribution (doughnut) ──────────
$statusOpen      = count(array_filter($myEvents, fn($e) => $e['status'] === 'open'));
$statusClosed    = count(array_filter($myEvents, fn($e) => $e['status'] === 'closed'));
$statusCancelled = count(array_filter($myEvents, fn($e) => $e['status'] === 'cancelled'));

// ── Chart 3: Monthly registrations (line) ──────────────────
$monthlyStmt = $db->prepare("
    SELECT
        DATE_FORMAT(r.registered_at, '%b %Y') AS month,
        COUNT(r.id)                            AS reg_count
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id
    WHERE e.organizer_id = ?
    GROUP BY YEAR(r.registered_at), MONTH(r.registered_at)
    ORDER BY YEAR(r.registered_at), MONTH(r.registered_at)
    LIMIT 6
");
$monthlyStmt->execute([$uid]);
$monthly = $monthlyStmt->fetchAll();
$lineLabels = array_column($monthly, 'month');
$lineData   = array_column($monthly, 'reg_count');

$pageTitle = 'Organizer Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-grid-1x2 me-2"></i>Organizer Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>! Manage your events and volunteers.</p>
    </div>
</div>

<div class="container">

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $totalEvents ?></div>
                <div class="stat-label">Total Events</div>
                <i class="bi bi-calendar-event stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $openEvents ?></div>
                <div class="stat-label">Open Events</div>
                <i class="bi bi-calendar-check stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $totalVols ?></div>
                <div class="stat-label">Registrations</div>
                <i class="bi bi-people stat-icon"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?= $totalApproved ?></div>
                <div class="stat-label">Approved Vols.</div>
                <i class="bi bi-person-check stat-icon"></i>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Bar Chart: Registrations per Event -->
        <div class="col-lg-7">
            <div class="chart-container">
                <div class="chart-title"><i class="bi bi-bar-chart me-2"></i>Registrations per Event</div>
                <canvas id="barChart" height="200"></canvas>
            </div>
        </div>

        <!-- Doughnut: Event Status -->
        <div class="col-lg-5">
            <div class="chart-container">
                <div class="chart-title"><i class="bi bi-pie-chart me-2"></i>Event Status Overview</div>
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Line Chart: Monthly Registrations -->
        <div class="col-lg-6">
            <div class="chart-container">
                <div class="chart-title"><i class="bi bi-graph-up me-2"></i>Monthly Registrations</div>
                <canvas id="lineChart" height="180"></canvas>
            </div>
        </div>

        <!-- Recent Volunteers Table -->
        <div class="col-lg-6">
            <div class="section-header">
                <h5><i class="bi bi-person-lines-fill me-2 text-green"></i>Recent Volunteers</h5>
            </div>
            <?php if ($recentVols): ?>
            <div class="buliga-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th data-sortable>Student</th>
                            <th data-sortable>Event</th>
                            <th data-sortable>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentVols as $v): ?>
                        <tr>
                            <td>
                                <div class="fw-sora" style="font-size:.88rem">
                                    <?= htmlspecialchars($v['full_name']) ?>
                                </div>
                                <div class="small text-muted"><?= htmlspecialchars($v['email']) ?></div>
                            </td>
                            <td class="small text-muted">
                                <?= htmlspecialchars(substr($v['event_title'], 0, 25)) ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $v['reg_status'] ?>">
                                    <?= ucfirst($v['reg_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-icon">👥</span>
                    <p>No volunteers registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Events Summary Table -->
    <div class="section-header">
        <h5><i class="bi bi-table me-2 text-green"></i>My Events Overview</h5>
    </div>

    <?php if ($myEvents): ?>
    <div class="buliga-table mb-4">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th data-sortable>Title</th>
                    <th data-sortable>Date</th>
                    <th data-sortable>Status</th>
                    <th data-sortable>Registrations</th>
                    <th data-sortable>Approved</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($myEvents as $e): ?>
                <tr>
                    <td class="fw-sora" style="font-size:.9rem">
                        <?= htmlspecialchars($e['title']) ?>
                    </td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
                    <td><span class="status-badge status-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span></td>
                    <td><?= $e['total_regs'] ?> / <?= $e['slots'] ?></td>
                    <td><?= $e['approved'] ?></td>
                    <td>
                        <a href="/organizer/manage-registrations.php?event_id=<?= $e['id'] ?>"
                           class="btn btn-outline-buliga btn-sm me-1">Volunteers</a>
                        <a href="/organizer/edit-event.php?id=<?= $e['id'] ?>"
                           class="btn btn-green btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon">📅</span>
            <p>You haven't created any events yet.</p>
            <a href="/organizer/create-event.php" class="btn btn-green">Create Your First Event</a>
        </div>
    <?php endif; ?>
</div>

<script>
makeBar('barChart',
    <?= json_encode(array_values($barLabels)) ?>,
    <?= json_encode(array_values($barData)) ?>,
    'Volunteers Registered'
);

makeDoughnut('statusChart',
    ['Open', 'Closed', 'Cancelled'],
    [<?= $statusOpen ?>, <?= $statusClosed ?>, <?= $statusCancelled ?>],
    ['#2d9b5a', '#f5a623', '#e74c3c']
);

makeLine('lineChart',
    <?= json_encode(array_values($lineLabels)) ?>,
    <?= json_encode(array_values($lineData)) ?>,
    'Registrations'
);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>