<?php
// 简易MySQL导出脚本：将当前库导出到项目根目录 upgrade.sql
// 注意：使用直连配置，如需调整，请修改下方连接信息。

$host = 'localhost';
$port = 3306;
$user = 'root';
$pass = '123456';
$dbname = '10-27'; // 数据库名包含短横线，连接时无需转义
$charset = 'utf8';

$outputFile = __DIR__ . DIRECTORY_SEPARATOR . 'upgrade.sql';

// 建立连接
$mysqli = @new mysqli($host, $user, $pass, $dbname, $port);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "[ERROR] MySQL连接失败: " . $mysqli->connect_error . PHP_EOL);
    exit(1);
}
$mysqli->set_charset($charset);

// 打开写入文件
$fh = @fopen($outputFile, 'w');
if (!$fh) {
    fwrite(STDERR, "[ERROR] 无法写入文件: {$outputFile}" . PHP_EOL);
    exit(1);
}

// 写入头信息
$header = "-- Database Export\n-- Host: {$host}\n-- Database: {$dbname}\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
$header .= "SET NAMES '{$charset}';\n";
$header .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
$header .= "SET time_zone = '+00:00';\n\n";
fwrite($fh, $header);

// 获取所有表
$tables = [];
$res = $mysqli->query('SHOW TABLES');
if ($res) {
    while ($row = $res->fetch_array(MYSQLI_NUM)) {
        $tables[] = $row[0];
    }
    $res->free();
}

// 导出每个表
foreach ($tables as $table) {
    fwrite($fh, "--\n-- Table structure for `{$table}`\n--\n\n");
    fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
    $createRes = $mysqli->query("SHOW CREATE TABLE `{$table}`");
    if ($createRes) {
        $createRow = $createRes->fetch_assoc();
        $createSql = isset($createRow['Create Table']) ? $createRow['Create Table'] : array_values($createRow)[1];
        fwrite($fh, $createSql . ";\n\n");
        $createRes->free();
    }

    fwrite($fh, "--\n-- Dumping data for table `{$table}`\n--\n\n");

    // 统计行数并分批导出
    $countRes = $mysqli->query("SELECT COUNT(*) AS cnt FROM `{$table}`");
    $total = 0;
    if ($countRes) {
        $countRow = $countRes->fetch_assoc();
        $total = (int)$countRow['cnt'];
        $countRes->free();
    }

    $batchSize = 1000;
    for ($offset = 0; $offset < $total; $offset += $batchSize) {
        $dataRes = $mysqli->query("SELECT * FROM `{$table}` LIMIT {$offset}, {$batchSize}");
        if (!$dataRes) continue;

        $rows = [];
        while ($row = $dataRes->fetch_array(MYSQLI_NUM)) {
            $values = [];
            foreach ($row as $val) {
                if (is_null($val)) {
                    $values[] = 'NULL';
                } else {
                    $escaped = $mysqli->real_escape_string($val);
                    $escaped = str_replace(["\n", "\r"], ['\\n', '\\r'], $escaped);
                    $values[] = "'{$escaped}'";
                }
            }
            $rows[] = '(' . implode(',', $values) . ')';
        }
        if (!empty($rows)) {
            $insertSql = "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $rows) . ";\n";
            fwrite($fh, $insertSql);
        }
        $dataRes->free();
    }

    fwrite($fh, "\n");
}

fclose($fh);
$mysqli->close();

echo "Export completed: {$outputFile}" . PHP_EOL;
exit(0);