<?php
// ============================================================
// organizer/manage-registrations.php – Manage Volunteers
// Demonstrates: INNER JOIN across 3 tables, CRUD Update status
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();
$eid = (int)($_GET['event_id'] ?? 0);

// Verify event ownership
$evStmt = $db->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$evStmt->execute([$eid, $uid]);
$event = $evStmt->fetch();
if (!$event) { setFlash('error', 'Event not found.'); header('Location: /organizer/dashboard.php'); exit; }

// Handle status update (approve / reject / complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid    = (int)($_POST['reg_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $hours  = (float)($_POST['hours'] ?? 0);

    $allowed = ['approved', 'rejected', 'completed'];
    if ($rid && in_array($action, $allowed)) {
        if ($action === 'completed') {
            // CRUD: Update – mark completed with hours
            $upd = $db->prepare(
                "UPDATE registrations SET status='completed', hours_rendered=?, updated_at=NOW()
                 WHERE id=? AND event_id=?"
            );
            $upd->execute([$hours, $rid, $eid]);
        } else {
            // CRUD: Update – approve or reject
            $upd = $db->prepare(
                "UPDATE registrations SET status=?, updated_at=NOW() WHERE id=? AND event_id=?"
            );
            $upd->execute([$action, $rid, $eid]);
        }
        setFlash('success', 'Registration status updated.');
    }
    header("Location: /organizer/manage-registrations.php?event_id=$eid");
    exit;
}

// ── INNER JOIN across 3 tables ─────────────────────────────
// Join registrations × users (students) × events to get
// full volunteer info. All three tables must match (INNER JOIN).
$regStmt = $db->prepare("
    SELECT
        r.id            AS reg_id,
        r.status,
        r.hours_rendered,
        r.registered_at,
        u.id            AS student_id,
        u.full_name,
        u.email
    FROM registrations r
    INNER JOIN users u  ON r.student_id = u.id    -- INNER JOIN: get student info
    INNER JOIN events e ON r.event_id   = e.id    -- INNER JOIN: confirm event
    WHERE r.event_id = ?
    ORDER BY r.registered_at ASC
");
$regStmt->execute([$eid]);
$registrations = $regStmt->fetchAll();

$counts = ['total' => count($registrations)];
foreach (['pending', 'approved', 'rejected', 'completed'] as $s) {
    $counts[$s] = count(array_filter($registrations, fn($r) => $r['status'] === $s));
}

$pageTitle = 'Manage Registrations';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-people me-2"></i>Manage Volunteers</h1>
        <p><?= htmlspecialchars($event['title']) ?> · <?= date('M d, Y', strtotime($event['event_date'])) ?></p>
    </div>
</div>

<div class="container">
    <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
        <a href="/organizer/edit-event.php?id=<?= $eid ?>" class="btn btn-outline-buliga btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Event
        </a>
        <a href="/organizer/send-announcement.php?event_id=<?= $eid ?>" class="btn btn-green btn-sm">
            <i class="bi bi-megaphone me-1"></i>Send Announcement
        </a>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <span class="status-badge status-pending"><?= $counts['pending'] ?> Pending</span>
            <span class="status-badge status-approved"><?= $counts['approved'] ?> Approved</span>
            <span class="status-badge status-completed"><?= $counts['completed'] ?> Completed</span>
            <span class="status-badge status-rejected"><?= $counts['rejected'] ?> Rejected</span>
        </div>
    </div>

    <!-- Search -->
    <div class="mb-3 search-bar" style="max-width:360px;">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control"
               data-search-table="#volTable"
               placeholder="Search volunteers…" />
    </div>

    <?php if ($registrations): ?>
    <div class="buliga-table">
        <table class="table mb-0" id="volTable">
            <thead>
                <tr>
                    <th data-sortable>Student</th>
                    <th data-sortable>Email</th>
                    <th data-sortable>Registered</th>
                    <th data-sortable>Status</th>
                    <th data-sortable>Hours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($registrations as $r): ?>
                <tr>
                    <td class="fw-sora" style="font-size:.9rem">
                        <?= htmlspecialchars($r['full_name']) ?>
                    </td>
                    <td class="small text-muted"><?= htmlspecialchars($r['email']) ?></td>
                    <td class="small text-muted"><?= date('M d, Y', strtotime($r['registered_at'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $r['status'] ?>">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td><?= number_format($r['hours_rendered'], 1) ?>h</td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="reg_id" value="<?= $r['reg_id'] ?>">
                                <input type="hidden" name="action" value="approved">
                                <button type="submit" class="btn btn-sm btn-green me-1">Approve</button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="reg_id" value="<?= $r['reg_id'] ?>">
                                <input type="hidden" name="action" value="rejected">
                                <button type="submit" class="btn btn-sm"
                                        style="background:#fde8e8;color:#c0392b;border:1.5px solid #f5c6c6;border-radius:var(--radius-pill);">
                                    Reject
                                </button>
                            </form>
                        <?php elseif ($r['status'] === 'approved'): ?>
                            <!-- Mark Complete with hours -->
                            <form method="POST" class="d-inline d-flex align-items-center gap-1">
                                <input type="hidden" name="reg_id" value="<?= $r['reg_id'] ?>">
                                <input type="hidden" name="action" value="completed">
                                <input type="number" name="hours" step="0.5" min="0" max="24"
                                       value="<?= $r['hours_rendered'] ?>"
                                       class="form-control form-control-sm" style="width:70px;"
                                       placeholder="hrs" />
                                <button type="submit" class="btn btn-sm"
                                        style="background:#e8f0ff;color:#2c5cf7;border:1.5px solid #c5d4fb;border-radius:var(--radius-pill);">
                                    ✓ Complete
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="small text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon">👥</span>
            <p>No volunteers have registered for this event yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>