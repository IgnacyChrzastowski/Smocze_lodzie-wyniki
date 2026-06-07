<?php
$host = '127.0.0.1';
$user = 'u926842490_lodzie';
$pass = 'WqaqW2&1'; // Twoje hasło MySQL
$db   = 'u926842490_lodzie';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Błąd połączenia: " . $conn->connect_error);
}
?>