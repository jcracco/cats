#!/usr/bin/env php
<?php
/**
 * backup_db.php — Daily MySQL backup
 * Location: private/cats/backup_db.php
 * Scheduled via Plesk: Execute PHP file, daily at 03:00
 *
 * Email delivery is controlled by BACKUP_EMAIL_ENABLED in config.php.
 * Set to false to keep backups local only.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$backup_dir = __DIR__ . '/backups';
$date       = date('Ymd');
$file       = "$backup_dir/cats_$date.sql";

// ── Create backup ─────────────────────────────────────────────────────────────
$cmd    = sprintf('/usr/bin/mysqldump -u%s -p%s %s > %s 2>&1',
    escapeshellarg(DB_USER), escapeshellarg(DB_PASS),
    escapeshellarg(DB_NAME), escapeshellarg($file));
$output = shell_exec($cmd);

if ($output) {
    echo "[" . date('Y-m-d H:i:s') . "] mysqldump warning/error: $output\n";
} else {
    echo "[" . date('Y-m-d H:i:s') . "] Backup created: $file (" . round(filesize($file)/1024) . " KB)\n";
}

// ── Email delivery (optional) ─────────────────────────────────────────────────
if (defined('BACKUP_EMAIL_ENABLED') && BACKUP_EMAIL_ENABLED) {
    $sql      = file_get_contents($file);
    $encoded  = chunk_split(base64_encode($sql));
    $boundary = md5(time());
    $subject  = "CATS DB Backup - " . date('Y-m-d');

    $headers  = "From: " . BACKUP_EMAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $body  = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
    $body .= "CATS daily backup attached.\r\n";
    $body .= "Date: " . date('Y-m-d H:i:s') . "\r\n";
    $body .= "Size: " . round(filesize($file)/1024) . " KB\r\n\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: application/sql; name=\"cats_$date.sql\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"cats_$date.sql\"\r\n\r\n";
    $body .= $encoded . "\r\n";
    $body .= "--$boundary--";

    if (mail(BACKUP_EMAIL_TO, $subject, $body, $headers)) {
        echo "[" . date('Y-m-d H:i:s') . "] Backup emailed to " . BACKUP_EMAIL_TO . "\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Failed to send email\n";
    }
} else {
    echo "[" . date('Y-m-d H:i:s') . "] Email delivery disabled — backup kept locally only\n";
}

// ── Delete backups older than 30 days ─────────────────────────────────────────
foreach (glob("$backup_dir/*.sql") as $f) {
    if (filemtime($f) < strtotime('-30 days')) {
        unlink($f);
        echo "[" . date('Y-m-d H:i:s') . "] Deleted old backup: $f\n";
    }
}
