<?php

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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid    = (int)($_POST['reg_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $hours  = (float)($_POST['hours'] ?? 0);
    $statusFilter = $_POST['status'] ?? 'all';
    $search = $_POST['search'] ?? '';

    $allowed = ['approved', 'rejected', 'completed'];
    if ($rid && in_array($action, $allowed)) {
        if ($action === 'completed') {
            
            $upd = $db->prepare(
                "UPDATE registrations SET status='completed', hours_rendered=?, updated_at=NOW()
                 WHERE id=? AND event_id=?"
            );
            $upd->execute([$hours, $rid, $eid]);
        } else {
           
            $upd = $db->prepare(
                "UPDATE registrations SET status=?, updated_at=NOW() WHERE id=? AND event_id=?"
            );
            $upd->execute([$action, $rid, $eid]);
        }
        setFlash('success', 'Registration status updated.');
    }
   
    $query = http_build_query(['event_id' => $eid, 'status' => $statusFilter, 'search' => $search]);
    header("Location: /organizer/manage-registrations.php?$query");
    exit;
}


$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');

$params = [$eid];
$where  = ['r.event_id = ?'];

if ($statusFilter !== 'all') {
    $where[] = 'r.status = ?';
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);


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
    INNER JOIN users u  ON r.student_id = u.id    -- JOIN 1: Get student details
    INNER JOIN events e ON r.event_id   = e.id    -- JOIN 2: Validate event exists
    WHERE $whereClause
    ORDER BY 
        CASE r.status 
            WHEN 'pending'   THEN 1
            WHEN 'approved'  THEN 2
            WHEN 'completed' THEN 3
            ELSE 4
        END,
        r.registered_at ASC
");
$regStmt->execute($params);
$registrations = $regStmt->fetchAll();


$counts = ['total' => count($registrations)];
foreach (['pending', 'approved', 'rejected', 'completed'] as $s) {
    $counts[$s] = count(array_filter($registrations, fn($r) => $r['status'] === $s));
}

$pageTitle = 'Manage Volunteers';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-people me-2"></i>Manage Volunteers</h1>
        <p><?= htmlspecialchars($event['title']) ?> · <?= date('M d, Y', strtotime($event['event_date'])) ?></p>
    </div>
</div>

<div class="container">
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <a href="/organizer/edit-event.php?id=<?= $eid ?>" class="btn btn-outline-buliga btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Event
        </a>
        <a href="/organizer/send-announcement.php?event_id=<?= $eid ?>" class="btn btn-green btn-sm">
            <i class="bi bi-megaphone me-1"></i>Send Announcement
        </a>
        <div class="ms-auto d-flex gap-2 flex-wrap">
            <a href="?event_id=<?= $eid ?>&status=pending" class="status-badge status-pending" style="text-decoration:none;cursor:pointer;">
                <?= $counts['pending'] ?> Pending
            </a>
            <a href="?event_id=<?= $eid ?>&status=approved" class="status-badge status-approved" style="text-decoration:none;cursor:pointer;">
                <?= $counts['approved'] ?> Approved
            </a>
            <a href="?event_id=<?= $eid ?>&status=completed" class="status-badge status-completed" style="text-decoration:none;cursor:pointer;">
                <?= $counts['completed'] ?> Completed
            </a>
            <a href="?event_id=<?= $eid ?>&status=rejected" class="status-badge status-rejected" style="text-decoration:none;cursor:pointer;">
                <?= $counts['rejected'] ?> Rejected
            </a>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="buliga-card p-3 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="event_id" value="<?= $eid ?>">
            
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Status Filter</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All Statuses (<?= $counts['total'] ?>)</option>
                    <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>⏳ Pending (<?= $counts['pending'] ?>)</option>
                    <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>✅ Approved (<?= $counts['approved'] ?>)</option>
                    <option value="rejected" <?= $statusFilter==='rejected'?'selected':'' ?>>❌ Rejected (<?= $counts['rejected'] ?>)</option>
                    <option value="completed" <?= $statusFilter==='completed'?'selected':'' ?>>🏁 Completed (<?= $counts['completed'] ?>)</option>
                </select>
            </div>
            
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted">Search Volunteers</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search by name or email…" />
                    <button class="btn btn-green" type="submit">Filter</button>
                </div>
            </div>
            
            <div class="col-md-3 text-end">
                <a href="?event_id=<?= $eid ?>" class="btn btn-outline-buliga">
                    <i class="bi bi-x-circle"></i> Clear All
                </a>
            </div>
        </form>
        
        <!-- Active Filters Display -->
        <?php if ($statusFilter !== 'all' || $search): ?>
        <div class="mt-3 small">
            <strong class="text-muted">Active filters:</strong>
            <?php if ($statusFilter !== 'all'): ?>
                <span class="badge bg-warning text-dark ms-2">Status: <?= ucfirst($statusFilter) ?></span>
            <?php endif; ?>
            <?php if ($search): ?>
                <span class="badge bg-info text-dark ms-2">Search: "<?= htmlspecialchars($search) ?>"</span>
            <?php endif; ?>
            <span class="text-muted ms-2">→ Showing <?= count($registrations) ?> volunteer<?= count($registrations)===1?'':'s' ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Volunteers Table -->
    <?php if ($registrations): ?>
    <div class="buliga-table">
        <table class="table mb-0" id="volTable">
            <thead>
                <tr>
                    <th data-sortable>Student Name</th>
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
                    <td class="fw-sora" style="font-size:1rem;"><?= number_format($r['hours_rendered'], 1) ?>h</td>
                    <td>
                        <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="reg_id" value="<?= $r['reg_id'] ?>">
                                <input type="hidden" name="action" value="approved">
                                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-sm btn-green me-1">Approve</button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="reg_id" value="<?= $r['reg_id'] ?>">
                                <input type="hidden" name="action" value="rejected">
                                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
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
                                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
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
        <div class="empty-state buliga-card">
            <span class="empty-icon">👥</span>
            <p>No volunteers match the current filters.</p>
            <a href="?event_id=<?= $eid ?>" class="btn btn-green btn-sm">Clear Filters</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
    