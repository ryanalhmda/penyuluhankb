<?php
require_once 'config.php';

// Hancurkan semua session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: index.php");
exit();
?>