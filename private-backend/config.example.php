<?php
/**
 * config.example.php — Template for config.php
 * Location: private/cats/config.example.php
 *
 * Setup:
 *   1. Copy this file to config.php
 *   2. Fill in all values marked with ← replace
 *   3. Never commit config.php (it is gitignored)
 *
 * Generate password hash:
 *   Create a temp PHP file containing:
 *   <?php echo password_hash('your_password', PASSWORD_DEFAULT); ?>
 *   Run it once, copy the output into AUTH_PASS, delete the file.
 */

// ── Private path ──────────────────────────────────────────────────────────────
// PRIVATE_PATH is defined in public-frontend/bootstrap.php (not in this file)

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cats_db');
define('DB_USER', 'cats_user');
define('DB_PASS', 'your_db_password');                  // ← replace

// ── Admin auth ────────────────────────────────────────────────────────────────
define('AUTH_USER', 'your_username');                   // ← replace
define('AUTH_PASS', '$2y$12$...');                      // ← replace with hashed password

// ── Session ───────────────────────────────────────────────────────────────────
define('SESSION_COOKIE',   'cats_session');
define('SESSION_DURATION', 60 * 60 * 24 * 30); // 30 days

// ── Backup ───────────────────────────────────────────────────────────────────
define('BACKUP_EMAIL_ENABLED', false);               // ← set to true to enable emailing backup file
define('BACKUP_EMAIL_TO',      'your@email.com');   // ← replace
define('BACKUP_EMAIL_FROM',    'backup@yourdomain.com'); // ← replace

// ── Analytics ─────────────────────────────────────────────────────────────────
define('UMAMI_ENABLED', false);                    // ← set to true to enable
define('UMAMI_WEBSITE_ID', 'your-website-id');     // ← replace with your Umami ID

// ── Demo detection ────────────────────────────────────────────────────────────
define('IS_DEMO_DOMAIN', 'demo.url.tld');             // ← replace with your demo domain
