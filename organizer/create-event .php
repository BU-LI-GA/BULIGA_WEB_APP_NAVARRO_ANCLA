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
                    $image_url = '/uploads/' . $filename;
                }
            } else {
                setFlash('error', 'Image must be JPG/PNG/GIF/WEBP under 3MB.');
            }
        }