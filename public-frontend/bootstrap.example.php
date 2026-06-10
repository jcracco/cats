<?php
/**
 * bootstrap.example.php — Template for bootstrap.php
 * Copy to bootstrap.php and set PRIVATE_PATH to your private-backend/ directory.
 * bootstrap.php is gitignored and never committed.
 */
define('PRIVATE_PATH', '/path/to/your/private-backend/');
require_once PRIVATE_PATH . 'config.php';
require_once PRIVATE_PATH . 'db.php';
require_once PRIVATE_PATH . 'auth.php';