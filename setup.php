<?php
require_once 'configs/db.config.php';

// Drop tables if they exist to perform a clean setup
$conn->query("DROP TABLE IF EXISTS " . DB_PREFIX . "transactions");
$conn->query("DROP TABLE IF EXISTS " . DB_PREFIX . "settings");

$sql = "CREATE TABLE " . DB_PREFIX . "transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    category VARCHAR(100),
    date DATE NOT NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (isset($conn) && $conn->query($sql) === TRUE) {
    echo "Table transactions created successfully<br>";
} else {
    echo "Error creating transactions table: " . ($conn->error ?? 'No connection') . "<br>";
}

$sqlSettings = "CREATE TABLE " . DB_PREFIX . "settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
)";

if (isset($conn) && $conn->query($sqlSettings) === TRUE) {
    echo "Table settings created successfully<br>";
    // Insert default login code
    $conn->query("INSERT INTO " . DB_PREFIX . "settings (setting_key, setting_value) VALUES ('secret_code', '11+08+1993')");
} else {
    echo "Error creating settings table: " . ($conn->error ?? 'No connection') . "<br>";
}

$conn->close();
?>
