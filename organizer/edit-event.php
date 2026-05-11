<?php
// ============================================================
// organizer/edit-event.php – Edit & Delete Event
// Demonstrates: CRUD Update (UPDATE) and Delete (DELETE)
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();
$eid = (int)($_GET['id'] ?? 0);

if (!$eid) { header('Location: dashboard.php'); exit; }

// Fetch event (must belong to this organizer)
$stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$eid, $uid]);
$event = $stmt->fetch();
if (!$event) { setFlash('error', 'Event not found.'); header('Location: dashboard.php'); exit; }

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // CRUD: Delete event (cascades to registrations + announcements via FK)
    $del = $db->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
    $del->execute([$eid, $uid]);
    setFlash('success', 'Event deleted.');
    header('Location: dashboard.php');
    exit;
}

// Handle EDIT (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $start_time  = $_POST['start_time'] ?? '';
    $end_time    = $_POST['end_time'] ?? '';
    $slots       = (int)($_POST['slots'] ?? 20);
    $status      = $_POST['status'] ?? 'open';

    if (!$title || !$description || !$location || !$event_date || !$start_time || !$end_time) {
        setFlash('error', 'Please fill in all required fields.');
    } else {
        $image_url = $event['image_url'];

        // New image uploaded?
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = $_FILES['image']['type'];
            if (in_array($mime, $allowed) && $_FILES['image']['size'] < 3_000_000) {
                $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('ev_', true) . '.' . $ext;
                $dest     = __DIR__ . '/../uploads/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $image_url = '/buliga/uploads/' . $filename;
                }
            }
        }

        // CRUD: Update event
        $upd = $db->prepare("
            UPDATE events
            SET title=?, description=?, location=?, event_date=?,
                start_time=?, end_time=?, slots=?, status=?, image_url=?,
                updated_at=NOW()
            WHERE id=? AND organizer_id=?
        ");
        $upd->execute([
            $title, $description, $location, $event_date,
            $start_time, $end_time, $slots, $status, $image_url,
            $eid, $uid
        ]);

        setFlash('success', 'Event updated successfully!');
        header('Location: events.php');
        exit;
    }
}

$pageTitle = 'Edit Event';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-pencil-square me-2"></i>Edit Event</h1>
        <p><?= htmlspecialchars($event['title']) ?></p>
    </div>
</div>

<div class="container" style="max-width:760px;">
    <div class="buliga-form-card">
        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Event Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= htmlspecialchars($event['title']) ?>" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Description <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="4"
                          required><?= htmlspecialchars($event['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Location <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control"
                       value="<?= htmlspecialchars($event['location']) ?>" required />
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label">Event Date</label>
                    <input type="date" name="event_date" class="form-control"
                           value="<?= $event['event_date'] ?>" required />
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="start_time" class="form-control"
                           value="<?= $event['start_time'] ?>" required />
                </div>
                <div class="col-sm-4">
                    <label class="form-label">End Time</label>
                    <input type="time" name="end_time" class="form-control"
                           value="<?= $event['end_time'] ?>" required />
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Volunteer Slots</label>
                    <input type="number" name="slots" class="form-control" min="1" max="500"
                           value="<?= $event['slots'] ?>" required />
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="open"      <?= $event['status'] === 'open'      ? 'selected' : '' ?>>Open</option>
                        <option value="closed"    <?= $event['status'] === 'closed'    ? 'selected' : '' ?>>Closed</option>
                        <option value="cancelled" <?= $event['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Replace Image <span class="text-muted small">(optional)</span></label>
                <?php if ($event['image_url']): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars(str_starts_with($event['image_url'], '/uploads/') ? '/buliga' . $event['image_url'] : $event['image_url']) ?>"
                             style="max-height:120px;border-radius:var(--radius-sm);" />
                    </div>
                <?php endif; ?>
                <input type="file" name="image" id="event_image" class="form-control"
                       accept="image/jpeg,image/png,image/gif,image/webp" />
                <img id="image_preview" src="" alt="" style="display:none;max-height:160px;border-radius:var(--radius);margin-top:.75rem;" />
            </div>

            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="submit" name="action" value="edit" class="btn btn-green px-4 py-2">
                    <i class="bi bi-check2 me-2"></i>Save Changes
                </button>
                <a href="manage-registrations.php?event_id=<?= $eid ?>"
                   class="btn btn-outline-buliga">Manage Volunteers</a>
                <a href="send-announcement.php?event_id=<?= $eid ?>"
                   class="btn btn-outline-buliga">
                   <i class="bi bi-megaphone me-1"></i>Announce
               </a>
            </div>
        </form>

        <!-- DELETE form placed OUTSIDE the edit form to prevent nesting -->
        <div class="mt-3">
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Permanently delete this event and all its registrations?')">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-sm"
                        style="background:#fde8e8;color:#c0392b;border:1.5px solid #f5c6c6;border-radius:var(--radius-pill);">
                    <i class="bi bi-trash me-1"></i>Delete Event
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>