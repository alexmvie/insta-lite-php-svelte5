<?php
// This script will inspect your MySQL foreign keys and print the SQL needed to add ON DELETE CASCADE where missing.
// It requires PDO and your existing database.php config.
require_once __DIR__ . '/database.php';

function getForeignKeys($pdo, $table) {
    $stmt = $pdo->prepare("SHOW CREATE TABLE `$table`");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return [];
    $create = array_values($row)[1];
    $fks = [];
    foreach (explode("\n", $create) as $line) {
        if (stripos($line, 'FOREIGN KEY') !== false) {
            $fks[] = trim($line, ", ");
        }
    }
    return $fks;
}

function showTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES;");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$db = new Database();
$pdo = $db->getConnection();
$tables = showTables($pdo);
echo "Tables found: " . implode(", ", $tables) . "\n";
foreach ($tables as $table) {
    echo "\n--- $table ---\n";
    $fks = getForeignKeys($pdo, $table);
    if ($fks) {
        echo "Foreign Keys:\n";
        foreach ($fks as $fk) {
            echo $fk . "\n";
        }
    } else {
        echo "No foreign keys found.\n";
    }
}
