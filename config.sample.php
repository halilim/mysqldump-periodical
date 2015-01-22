<?php

$mysql_host   = "127.0.0.1";
$mysql_user   = "root";
$mysql_pass   = "password";
$exclude_dbs  = array("information_schema", "mysql", "performance_schema");
$mysqldump    = '/usr/bin/mysqldump';
$backup_dir   = '/backup/mysql';
$compress_cmd = "gzip :file_name"; // Set to empty string to disable compression

$dry_run = false; // Just list databases to be backed up

/**
 * 1. Set enabled => true in order to enable it
 * 2. Test it to make sure that it doesn't delete something else by accident
 * 3. Set dry_run => false in order to actually enable it
 */
$rotate_conf = array(
    "enabled"       => false,
    "last_days"     => 7, // Keep last 7 days
    "max_days"      => 62, // Don't keep if older than 62 days
    "days_of_month" => array(7, 15, 21, 28), // Also keep 7th, 15th, 21st and 28th days of month
    "dry_run"       => true
);
