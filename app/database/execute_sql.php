<?php
require_once __DIR__ . '/../config/database.php';

$config = require __DIR__ . '/../config/database.php';
$dbConfig = $config['connections'][$config['default']];

$mysqli = new mysqli(
    $dbConfig['host'],
    $dbConfig['username'],
    $dbConfig['password'],
    $dbConfig['database'],
    $dbConfig['port'] ?? 3306
);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$sql = file_get_contents(__DIR__ . '/migrations/activity_logs.sql');

if ($mysqli->multi_query($sql)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    echo "Activity logs table created successfully\n";
} else {
    echo "Error creating table: " . $mysqli->error . "\n";
}

$mysqli->close();
