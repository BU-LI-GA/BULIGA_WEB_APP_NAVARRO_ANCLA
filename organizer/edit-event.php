<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();
$eid = (int)($_GET['id'] ?? 0);

if (!$eid) { header('Location: /organizer/dashboard.php'); exit; }

// Fetch event (must belong to this organizer)
$stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$eid, $uid]);
$event = $stmt->fetch();
if (!$event) { setFlash('error', 'Event not found.'); header('Location: /organizer/dashboard.php'); exit; }

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // CRUD: Delete event (cascades to registrations + announcements via FK)
    $del = $db->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
    $del->execute([$eid, $uid]);
    setFlash('success', 'Event deleted.');
    header('Location: /organizer/dashboard.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete') {
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

        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = $_FILES['image']['type'];
            if (in_array($mime, $allowed) && $_FILES['image']['size'] < 3_000_000) {
                $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('ev_', true) . '.' . $ext;
                $dest     = __DIR__ . '/../uploads/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $image_url = '/uploads/' . $filename;
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
        header("Location: /organizer/edit-event.php?id=$eid");
        exit;
    }
}