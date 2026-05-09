<?php

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';


if (isLoggedIn()) {
    header('Location: /' . currentRole() . '/dashboard.php');
    exit;
}

$db = getDB();

$evStmt = $db->prepare("
    SELECT e.id, e.title, e.description, e.location, e.event_date,
           e.slots, e.image_url, e.status,
           u.full_name AS organizer_name,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status != 'rejected') AS slots_taken
    FROM events e
    INNER JOIN users u ON e.organizer_id = u.id
    WHERE e.status = 'open' AND e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
    LIMIT 3
");
$evStmt->execute();
$featuredEvents = $evStmt->fetchAll();
$statsStmt = $db->query("
    SELECT
        (SELECT COUNT(*) FROM events)                                   AS total_events,
        (SELECT COUNT(*) FROM users WHERE role='student')               AS total_volunteers,
        (SELECT COALESCE(SUM(hours_rendered),0) FROM registrations
         WHERE status='completed')                                       AS total_hours
");
$siteStats = $statsStmt->fetch();
?>