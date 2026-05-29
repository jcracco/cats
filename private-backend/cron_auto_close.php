#!/usr/bin/env php
<?php
/**
 * cron_auto_close.php — Auto-close stale applications
 *
 * Recommended location: above web root, e.g.:
 *   /path/to/private/cats/cron_auto_close.php
 *
 * Set up in your hosting control panel:
 *   Command: /usr/bin/php /path/to/private/cats/cron_auto_close.php
 *   Schedule: Daily at 02:00
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$pdo = db();

// Mark ONLY "Applied" applications with no response after 60 days as No Answer
// This will never touch Interviewing, Offer, Ghosted, Rejected, or any other status
$stmt = $pdo->query("
    UPDATE applications
    SET status = 'No Answer'
    WHERE status = 'Applied'
    AND date_applied < DATE_SUB(CURDATE(), INTERVAL 60 DAY)
");
$count = $stmt->rowCount();

// Sync timeline entries — only for applications we JUST changed to No Answer
// Extra safety: also check status = 'No Answer' AND date_applied condition to be certain
if ($count > 0) {
    $pdo->query("
        UPDATE timeline_entries t
        JOIN applications a ON t.id = a.timeline_id
        SET t.pending = 0
        WHERE a.status = 'No Answer'
        AND a.date_applied < DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        AND t.pending = 1
        AND t.date_rejected IS NULL
    ");
}

$timestamp = date('Y-m-d H:i:s');
echo "[$timestamp] Auto-closed $count stale applications (60+ days, no response).\n";
