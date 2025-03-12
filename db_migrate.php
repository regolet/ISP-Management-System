<?php

$db_file = 'database/isp-management.sqlite';
$backup_file = 'database/isp-management.sqlite.bak';

try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $backup_db = new PDO('sqlite:' . $backup_file);
    $backup_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get list of tables from backup database
    $tables_query = $backup_db->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "Migrating table: " . $table . "\n";

        // Get data from backup table
        $data_query = $backup_db->query("SELECT * FROM " . $table);
        $data = $data_query->fetchAll(PDO::FETCH_ASSOC);

        // Prepare insert statement
        $columns = array_keys($data[0]);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $insert_query = "INSERT INTO " . $table . " (" . implode(',', $columns) . ") VALUES (" . $placeholders . ")";
        $insert_stmt = $db->prepare($insert_query);

        // Insert data into new table
        foreach ($data as $row) {
            $insert_stmt->execute(array_values($row));
        }

        echo "Table " . $table . " migrated successfully!\n";
    }

    echo "Data migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Error migrating data: " . $e->getMessage() . "\n";
}

?>