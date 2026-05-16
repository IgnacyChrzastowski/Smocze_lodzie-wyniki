<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel użytkownika</title>
</head>
<body>

<h1>Witaj <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>

<p>Jesteś zalogowany.</p>

<a href="logout.php">Wyloguj</a>

</body>
</html>