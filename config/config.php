<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'abarhail_db');
define('DB_USER', 'root');      // Default user for XAMPP
define('DB_PASS', '');          // Empty password for XAMPP
define('BASE_URL', 'http://localhost/abarhail-api/');

// Error Reporting - Enable for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Riyadh'); // You can change this if needed
