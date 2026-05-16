<?php
$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Twoje hasło MySQL
$db   = 'smoczelodziewyniki';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Błąd połączenia: " . $conn->connect_error);
}
?>