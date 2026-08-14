<?php

/**
 * One-time database setup script.
 * Visit /setup.php after deploying to create the schema and seed users.
 * Safe to re-run: it skips existing tables.
 */

require_once __DIR__ . '/config/database.php';

$sql = file_get_contents(__DIR__ . '/database/schema.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$sql = preg_replace('/DROP DATABASE[^;]*;/i', '', $sql);
$sql = preg_replace('/CREATE DATABASE[^;]*;/i', '', $sql);
$sql = preg_replace('/USE[^;]*;/i', '', $sql);

try {
    $db->exec($sql);
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
}

?>