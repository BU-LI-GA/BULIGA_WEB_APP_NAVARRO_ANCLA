<?php
// ============================================================
// organizer/send-announcement.php – Send Announcement
// Demonstrates: CRUD Create (INSERT into announcements)
//               LEFT JOIN to show prior announcements
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
if (!$event) { setFlash('error', 'Event not found.'); header('Location: /buliga/organizer/dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['ann_title'] ?? '');
    $body  = trim($_POST['ann_body'] ?? '');

    if (!$title || !$body) {
        setFlash('error', 'Title and message are required.');
    } else {
        // CRUD: Create – insert announcement
        $ins = $db->prepare(
            "INSERT INTO announcements (event_id, author_id, title, body) VALUES (?, ?, ?, ?)"
        );
        $ins->execute([$eid, $uid, $title, $body]);
        setFlash('success', 'Announcement sent to all registered volunteers!');
        header("Location: send-announcement.php?event_id=$eid");
        exit;
    }
}

// ── LEFT JOIN: Prior announcements × author ────────────────
// LEFT JOIN keeps announcements even if author user were deleted.
$annStmt = $db->prepare("
    SELECT
        a.*,
        u.full_name AS author
    FROM announcements a
    LEFT JOIN users u ON a.author_id = u.id     -- LEFT JOIN: show even if author deleted
    WHERE a.event_id = ?
    ORDER BY a.created_at DESC
");
$annStmt->execute([$eid]);
$prior = $annStmt->fetchAll();

$pageTitle = 'Send Announcement';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-megaphone me-2"></i>Send Announcement</h1>
        <p><?= htmlspecialchars($event['title']) ?></p>
    </div>
</div>

<div class="container">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="buliga-form-card">
                <h5 class="fw-sora mb-4"><i class="bi bi-send me-2 text-green"></i>New Announcement</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="ann_title" class="form-control"
                               value="<?= htmlspecialchars($_POST['ann_title'] ?? '') ?>"
                               placeholder="e.g. Reminder: Bring Gloves" required />
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="ann_body" class="form-control" rows="5"
                                  placeholder="Write your announcement here…"
                                  required><?= htmlspecialchars($_POST['ann_body'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-green px-4">
                        <i class="bi bi-send me-2"></i>Send Announcement
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <h5 class="fw-sora mb-3"><i class="bi bi-clock-history me-2 text-green"></i>Prior Announcements</h5>

            <?php if ($prior): ?>
                <?php foreach ($prior as $a): ?>
                <div class="announcement-card">
                    <div class="ann-title"><?= htmlspecialchars($a['title']) ?></div>
                    <div class="ann-meta">
                        <?= htmlspecialchars($a['author'] ?? 'Unknown') ?> ·
                        <?= date('M d, Y g:i A', strtotime($a['created_at'])) ?>
                    </div>
                    <p class="small mt-2 mb-0"><?= nl2br(htmlspecialchars($a['body'])) ?></p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-icon">📢</span>
                    <p>No announcements yet for this event.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>