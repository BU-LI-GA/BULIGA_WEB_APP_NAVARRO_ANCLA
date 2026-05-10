<?php
// ============================================================
// organizer/events.php – All Events by This Organizer
// Demonstrates: LEFT JOIN, search, sort, full CRUD controls
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db     = getDB();
$uid    = currentUserId();
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';

$params = [$uid];
$where  = ['e.organizer_id = ?'];

if ($search) {
    $where[]  = "(e.title LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (in_array($status, ['open','closed','cancelled'])) {
    $where[]  = "e.status = ?";
    $params[] = $status;
}

// ── LEFT JOIN: Events with registration count ──────────────
$stmt = $db->prepare("
    SELECT
        e.*,
        COUNT(r.id)                                              AS total_regs,
        SUM(CASE WHEN r.status='approved'  THEN 1 ELSE 0 END)   AS approved,
        SUM(CASE WHEN r.status='pending'   THEN 1 ELSE 0 END)   AS pending,
        SUM(CASE WHEN r.status='completed' THEN 1 ELSE 0 END)   AS completed
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id              -- LEFT JOIN: 0-reg events included
    WHERE " . implode(' AND ', $where) . "
    GROUP BY e.id
    ORDER BY e.event_date DESC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle = 'My Events';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-calendar-event me-2"></i>My Events</h1>
        <p>Create, edit, and manage all your volunteer events.</p>
    </div>
</div>

<div class="container">
    <!-- Controls -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <div class="search-bar">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="form-control"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Search events…" style="min-width:220px;" />
            </div>
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="all"      <?= $status==='all'      ?'selected':'' ?>>All Statuses</option>
                <option value="open"     <?= $status==='open'     ?'selected':'' ?>>Open</option>
                <option value="closed"   <?= $status==='closed'   ?'selected':'' ?>>Closed</option>
                <option value="cancelled"<?= $status==='cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($search || $status !== 'all'): ?>
                <a href="/organizer/events.php" class="btn btn-outline-buliga btn-sm">Clear</a>
            <?php endif; ?>
        </form>
        <div class="ms-auto">
            <a href="/organizer/create-event.php" class="btn btn-green">
                <i class="bi bi-plus me-1"></i>New Event
            </a>
        </div>
    </div>

    <?php if ($events): ?>
    <div class="buliga-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th data-sortable>Event</th>
                    <th data-sortable>Date</th>
                    <th data-sortable>Status</th>
                    <th data-sortable>Slots</th>
                    <th data-sortable>Registrations</th>
                    <th data-sortable>Pending</th>
                    <th data-sortable>Approved</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td>
                        <div class="fw-sora" style="font-size:.92rem">
                            <?= htmlspecialchars($ev['title']) ?>
                        </div>
                        <div class="small text-muted">
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ev['location']) ?>
                        </div>
                    </td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime($ev['event_date'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $ev['status'] ?>">
                            <?= ucfirst($ev['status']) ?>
                        </span>
                    </td>
                    <td><?= $ev['slots'] ?></td>
                    <td><?= $ev['total_regs'] ?></td>
                    <td>
                        <?php if ($ev['pending'] > 0): ?>
                            <span class="status-badge status-pending"><?= $ev['pending'] ?></span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $ev['approved'] ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="/organizer/manage-registrations.php?event_id=<?= $ev['id'] ?>"
                               class="btn btn-outline-buliga btn-sm">
                                <i class="bi bi-people"></i>
                            </a>
                            <a href="/organizer/edit-event.php?id=<?= $ev['id'] ?>"
                               class="btn btn-green btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/organizer/send-announcement.php?event_id=<?= $ev['id'] ?>"
                               class="btn btn-sm"
                               style="background:var(--amber-light);color:var(--soil);border:1px solid #f0d090;border-radius:var(--radius-pill);">
                                <i class="bi bi-megaphone"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary row -->
    <div class="mt-3 text-muted small">
        Showing <?= count($events) ?> event<?= count($events) !== 1 ? 's' : '' ?>.
        Total registrations: <strong><?= array_sum(array_column($events, 'total_regs')) ?></strong>
    </div>

    <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon">📅</span>
            <p>
                <?= $search ? 'No events match your search.' : "You haven't created any events yet." ?>
            </p>
            <a href="/organizer/create-event.php" class="btn btn-green">Create First Event</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>