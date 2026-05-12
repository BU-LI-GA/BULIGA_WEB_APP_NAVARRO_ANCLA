<?php
require_once __DIR__ . '/config/db.php';
try {
    $db = getDB();
    echo "Database connection OK<br>";

    // Check users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "users table exists<br>";
        $count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "Users count: $count<br>";
        if ($count > 0) {
            $users = $db->query("SELECT id, full_name, email, role FROM users")->fetchAll();
            foreach ($users as $u) {
                echo "User: {$u['id']} | {$u['full_name']} | {$u['email']} | {$u['role']}<br>";
            }
        }
    } else {
        echo "users table does NOT exist<br>";
    }
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}