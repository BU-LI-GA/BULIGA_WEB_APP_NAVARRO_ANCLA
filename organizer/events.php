<?php

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