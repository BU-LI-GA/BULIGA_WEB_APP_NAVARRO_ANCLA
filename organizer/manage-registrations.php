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