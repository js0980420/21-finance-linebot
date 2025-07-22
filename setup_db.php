<?php

try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents('create_tables.sql');
    $pdo->exec($sql);
    
    echo "Database tables created successfully!\n";
    
    // Check tables
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "Created tables:\n";
    foreach ($result as $row) {
        echo "- " . $row['name'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}