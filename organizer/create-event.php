<?php
// ============================================================
// organizer/create-event.php – Create New Event
// Demonstrates: CRUD Create (INSERT)
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    } elseif ($slots < 1 || $slots > 500) {
        setFlash('error', 'Slots must be between 1 and 500.');
    } else {
        // Handle image upload
        $image_url = null;
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = $_FILES['image']['type'];
            if (in_array($mime, $allowed) && $_FILES['image']['size'] < 3_000_000) {
                $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename  = uniqid('ev_', true) . '.' . $ext;
                $dest      = __DIR__ . '/../uploads/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $image_url = '/buliga/uploads/' . $filename;
                }
            } else {
                setFlash('error', 'Image must be JPG/PNG/GIF/WEBP under 3MB.');
            }
        }

        // CRUD: Create – INSERT new event
        $ins = $db->prepare("
            INSERT INTO events
                (organizer_id, title, description, location, event_date,
                 start_time, end_time, slots, image_url, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $uid, $title, $description, $location,
            $event_date, $start_time, $end_time,
            $slots, $image_url, $status
        ]);

        $newId = $db->lastInsertId();
        setFlash('success', 'Event "' . $title . '" created successfully! 🎉');
        header('Location: events.php');
        exit;
    }
}

$pageTitle = 'Create Event';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-plus-circle me-2"></i>Create New Event</h1>
        <p>Fill in the details to publish a new volunteer opportunity.</p>
    </div>
</div>

<div class="container" style="max-width:760px;">
    <div class="buliga-form-card">
        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Event Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                       placeholder="e.g. Coastal Clean-Up Drive" required />
            </div>

            <div class="mb-3">
                <label class="form-label">Description <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control" rows="4"
                          placeholder="Describe the event, its goals, and what volunteers will do…"
                          required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Location <span class="text-danger">*</span></label>
                <input type="text" name="location" class="form-control"
                       value="<?= htmlspecialchars($_POST['location'] ?? '') ?>"
                       placeholder="e.g. Macajalar Bay, CDO" required />
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label">Event Date <span class="text-danger">*</span></label>
                    <input type="date" name="event_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>"
                           min="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control"
                           value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required />
                </div>
                <div class="col-sm-4">
                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" class="form-control"
                           value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>" required />
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label">Volunteer Slots <span class="text-danger">*</span></label>
                    <input type="number" name="slots" class="form-control" min="1" max="500"
                           value="<?= (int)($_POST['slots'] ?? 20) ?>" required />
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="open"   <?= (($_POST['status'] ?? 'open') === 'open'  ) ? 'selected' : '' ?>>Open</option>
                        <option value="closed" <?= (($_POST['status'] ?? '') === 'closed') ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Event Image <span class="text-muted small">(optional, max 3MB)</span></label>
                <input type="file" name="image" id="event_image" class="form-control"
                       accept="image/jpeg,image/png,image/gif,image/webp" />
                <img id="image_preview" src="" alt="Preview"
                     style="display:none;max-height:200px;border-radius:var(--radius);margin-top:.75rem;" />
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-green px-4 py-2">
                    <i class="bi bi-check2 me-2"></i>Publish Event
                </button>
                <a href="dashboard.php" class="btn btn-outline-buliga">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>