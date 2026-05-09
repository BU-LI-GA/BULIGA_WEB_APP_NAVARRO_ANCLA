<?php
// ============================================================
// organizer/join-demo.php – SQL JOIN Demonstration Page
// Required for IT26: Shows INNER, LEFT, RIGHT, FULL OUTER JOIN
// with live query results and annotated SQL comments.
// ============================================================
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
requireRole('organizer');

$db  = getDB();
$uid = currentUserId();
