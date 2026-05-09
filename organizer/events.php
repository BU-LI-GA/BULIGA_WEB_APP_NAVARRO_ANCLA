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