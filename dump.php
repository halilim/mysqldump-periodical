<?php

require_once "config.php";

$dbh = new PDO(
    "mysql:host=$mysql_host;charset=utf8",
    $mysql_user,
    $mysql_pass,
    array(
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION
    )
);

$stmt = $dbh->query("SHOW DATABASES");
$dbs  = $stmt->fetchAll(PDO::FETCH_COLUMN);

$dbs = array_diff($dbs, $exclude_dbs);

$dir = $backup_dir . DIRECTORY_SEPARATOR . date("Y-m-d_H-i-s");

if (empty($dry_run)) {
    if (!mkdir($dir)) {
        throw new RuntimeException("Couldn't mkdir $dir");
    }
}

foreach ($dbs as $db_name) {
    echo "--> Dumping $db_name" . PHP_EOL;

    if (empty($dry_run)) {
        $sql_file = $dir . DIRECTORY_SEPARATOR . $db_name . ".sql";

        $dump_result = system(
            "\"$mysqldump\" -u$mysql_user -p$mysql_pass $db_name > \"$sql_file\""
        );
        if ($dump_result === false) {
            throw new RuntimeException("Couldn't dump $db_name to $sql_file");
        }

        if (!empty($compress_cmd)) {
            echo "--> Compressing $db_name" . PHP_EOL;

            $compress_result = system(
                strtr($compress_cmd, array(":archive_name" => $sql_file, ":file_name" => $sql_file))
            );

            if ($compress_result !== false && file_exists($sql_file)) {
                unlink($sql_file);
            }
        }
    }
}

if (empty($dry_run) && !empty($rotate_conf["enabled"])) {
    rotate(
        $backup_dir . DIRECTORY_SEPARATOR . "*",
        $rotate_conf["last_days"],
        $rotate_conf["max_days"],
        $rotate_conf["days_of_month"],
        $rotate_conf["dry_run"]
    );
}

/**
 * @param string $pattern Pattern or path, will be passed to glob()
 * @param int    $last_days
 * @param int    $max_days
 * @param array  $days_of_month
 * @param bool   $dry_run
 */
function rotate(
    $pattern,
    $last_days = 7,
    $max_days = 62,
    $days_of_month = array(7, 15, 21, 28),
    $dry_run = true
) {
    if (empty($pattern)) {
        throw new InvalidArgumentException("Empty pattern to rotate");
    }

    $last_days_ago = strtotime("-" . $last_days . " days");

    $max_days_ago = strtotime("-" . $max_days . " days");

    foreach (glob($pattern) as $v) {
        $m_time = filemtime($v);
        $m_day  = date("d", $m_time);
        if ($m_time < $max_days_ago
            || ($m_time < $last_days_ago && !in_array($m_day, $days_of_month))
        ) {
            if ($dry_run) {
                echo "To be deleted: $v" . PHP_EOL;
            } else {
                if (is_dir($v)) {
                    deleteDir($v);
                } else {
                    unlink($v);
                }
            }
        }
    }
}

/**
 * @param string $dirPath
 *
 * @link http://stackoverflow.com/a/3349792/372654
 */
function deleteDir($dirPath)
{
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}
