<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to index page
header('Location: /Classroom/index.php');
exit();
?>
