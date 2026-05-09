<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();

// ── INNER JOIN: Organizer's events with registration counts ──
// Only events created by this organizer (intersection of events + users).
$evStmt = $db->prepare("
    SELECT
        e.id,
        e.title,
        e.event_date,
        e.status,
        e.slots,
        COUNT(r.id)                                        AS total_regs,
        SUM(CASE WHEN r.status = 'approved'  THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN r.status = 'pending'   THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) AS completed
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id        -- LEFT JOIN: include events with 0 registrations
    WHERE e.organizer_id = ?
    GROUP BY e.id
    ORDER BY e.event_date DESC
");
$evStmt->execute([$uid]);
$myEvents = $evStmt->fetchAll();

// ── Summary Stats ──────────────────────────────────────────
$totalEvents   = count($myEvents);
$totalVols     = array_sum(array_column($myEvents, 'total_regs'));
$totalApproved = array_sum(array_column($myEvents, 'approved'));
$openEvents    = count(array_filter($myEvents, fn($e) => $e['status'] === 'open'));