<?php
session_start();
require_once "config.php"; // musi utworzyć $conn jako mysqli

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim(isset($_POST["username"]) ? $_POST["username"] : "");
    $password = trim(isset($_POST["password"]) ? $_POST["password"] : "");

    if ($username === "" || $password === "") {
        echo "Proszę podać login i hasło.";
        exit;
    }

    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    if (!$stmt = $conn->prepare($sql)) {
        error_log("Prepare failed: " . $conn->error);
        echo "Wystąpił błąd serwera.";
        exit;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];

            header("Location: management.php");
            exit();
        } else {
            echo "Nieprawidłowy login lub hasło.";
        }
    } else {
        echo "Nieprawidłowy login lub hasło.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie</title>
</head>
<body>

<h2>Logowanie</h2>

<form method="POST">

    <input type="text" name="username" placeholder="Login" required><br><br>

    <input type="password" name="password" placeholder="Hasło" required><br><br>

    <button type="submit">Zaloguj</button>

</form>

<a href="register.php">Utwórz konto</a>

</body>
</html>