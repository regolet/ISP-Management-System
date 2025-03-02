<?php
$sql = "CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(50) NOT NULL,
    `module` varchar(50) NOT NULL,
    `record_id` int(11) DEFAULT NULL,
    `old_values` json DEFAULT NULL,
    `new_values` json DEFAULT NULL,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `module` (`module`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$mysqli = new mysqli('localhost', 'root', '', 'isp');

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

if ($mysqli->query($sql)) {
    echo "Activity logs table created successfully\n";
} else {
    echo "Error creating table: " . $mysqli->error . "\n";
}

$mysqli->close();
