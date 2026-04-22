<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$host = 'localhost';
$dbname = 'ihale';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

$ayarlar_sorgu = $db->query("SELECT setting_key, setting_value FROM settings");
$ayar = [];
while ($row = $ayarlar_sorgu->fetch()) {
    $ayar[$row['setting_key']] = $row['setting_value'];
}
?>