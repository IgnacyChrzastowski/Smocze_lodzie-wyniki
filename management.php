<?php
session_start();
require_once "config.php";

$err = "";

// Dodawanie nowego wyścigu (z możliwością dodania nowych zawodów)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_wyscig') {
    $nazwa_w = trim(isset($_POST['nazwa_wyscigu']) ? $_POST['nazwa_wyscigu'] : '');
    $id_z = (int)(isset($_POST['id_zawodow']) ? $_POST['id_zawodow'] : 0);
    $nowe_zawody = trim(isset($_POST['nowe_zawody']) ? $_POST['nowe_zawody'] : '');

    // Jeśli użytkownik wpisał nowe zawody
    if ($nowe_zawody !== '') {
        $ins_z = $conn->prepare("INSERT INTO zawody (nazwa) VALUES (?)");
        if ($ins_z) {
            $ins_z->bind_param("s", $nowe_zawody);
            if ($ins_z->execute()) {
                $id_z = $conn->insert_id; // pobierz ID nowo dodanych zawodów
                $ins_z->close();
            } else {
                $err = "Błąd przy dodawaniu zawodów: " . $conn->error;
                $ins_z->close();
            }
        }
    }

    // Teraz dodaj wyścig (jeśli mamy id zawodów)
    if ($err === '' && $nazwa_w !== '' && $id_z > 0) {
        $ins_w = $conn->prepare("INSERT INTO wyscigi (id_zawodow, nazwa) VALUES (?, ?)");
        if ($ins_w) {
            $ins_w->bind_param("is", $id_z, $nazwa_w);
            if ($ins_w->execute()) {
                $ins_w->close();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $err = "Błąd przy dodawaniu wyścigu: " . $conn->error;
            }
            $ins_w->close();
        }
    } elseif ($err === '') {
        $err = "Podaj nazwę wyścigu i zawody (wybierz istniejące lub dodaj nowe).";
    }
}

// Pobierz zawody do select
$zawody = [];
$res = $conn->query("SELECT id, nazwa FROM zawody ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $zawody[] = $row;
    }
    $res->free();
}

// Pobierz wyścigi
$wyscigi = [];
$res2 = $conn->query("
    SELECT w.id, w.nazwa AS nazwa_w, z.nazwa AS nazwa_z
    FROM wyscigi w
    LEFT JOIN zawody z ON w.id_zawodow = z.id
    ORDER BY w.id ASC
");
if ($res2) {
    while ($r = $res2->fetch_assoc()) {
        $wyscigi[] = $r;
    }
    $res2->free();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Dodaj wyścig</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 20px; }
        .err { color: red; margin: 10px 0; }
        label { display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 5px; }
        button { margin-top: 10px; padding: 8px 15px; }
        table { margin-top: 20px; border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    </style>
</head>
<body>

<h2>Dodaj nowy wyścig</h2>

<?php if ($err): ?>
    <div class="err"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="action" value="add_wyscig">

    <label>Wybierz istniejące zawody:
        <select name="id_zawodow">
            <option value="">-- brak --</option>
            <?php foreach ($zawody as $z): ?>
                <option value="<?php echo $z['id']; ?>">
                    <?php echo htmlspecialchars($z['nazwa']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>... LUB dodaj nowe zawody:
        <input type="text" name="nowe_zawody" placeholder="Nazwa nowych zawodów">
    </label>

    <label>Nazwa wyścigu:
        <input type="text" name="nazwa_wyscigu" required>
    </label>

    <button type="submit">Dodaj wyścig</button>
</form>

<h2>Lista wyścigów</h2>
<?php if (count($wyscigi) === 0): ?>
    <p>Brak wyścigów.</p>
<?php else: ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Wyścig</th>
            <th>Zawody</th>
        </tr>
        <?php foreach ($wyscigi as $w): ?>
            <tr>
                <td><?php echo $w['id']; ?></td>
                <td><?php echo htmlspecialchars($w['nazwa_w']); ?></td>
                <td><?php echo htmlspecialchars(isset($w['nazwa_z']) ? $w['nazwa_z'] : '—'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>