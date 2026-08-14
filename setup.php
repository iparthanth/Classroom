<?php

/**
 * One-time database setup script.
 * Visit /setup.php after deploying to create the schema and seed users.
 * Safe to re-run: it drops and recreates the tables.
 */

require_once __DIR__ . '/config/database.php';

$prelude = "
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS course_materials;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS whiteboard_sessions;
DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS assignments;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;
";

$sql = file_get_contents(__DIR__ . '/database/schema.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);

$statements = array_filter(array_map('trim', explode(';', $prelude . $sql)));

try {
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        if (preg_match('/^\s*(DROP DATABASE|CREATE DATABASE|USE)\b/i', $statement)) {
            continue;
        }
        $db->exec($statement);
    }
    echo "<h2>Database initialized successfully!</h2>";
    echo "<p>Default accounts:</p><ul>";
    echo "<li><strong>Admin:</strong> admin / password</li>";
    echo "<li><strong>Teacher:</strong> teacher1 / password</li>";
    echo "<li><strong>Student:</strong> student1 / password</li>";
    echo "</ul>";
    echo '<p><a href="/index.php">Go to login</a></p>';
} catch (PDOException $e) {
    echo "<h2>Setup failed</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Statement: " . htmlspecialchars($statement ?? '') . "</p>";
}

?>