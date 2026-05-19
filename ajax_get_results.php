<?php
require_once "config.php";

// Pobierz id zawodów z query string (opcjonalnie) LUB z cookies
$zawody_id = isset($_GET['zawody_id']) ? (int)$_GET['zawody_id'] : 0;

if ($zawody_id === 0 && isset($_COOKIE['zawody_prezentacyjne'])) {
    $zawody_id = (int)$_COOKIE['zawody_prezentacyjne'];
}

// zapytanie do bazy
if ($zawody_id > 0) {
    $res = $conn->query("
        SELECT w.id AS id, w.nazwa AS nazwa_w, w.id_zawodow, z.nazwa AS nazwa_z
        FROM wyscigi w
        LEFT JOIN zawody z ON w.id_zawodow = z.id
        WHERE w.id_zawodow = " . (int)$zawody_id . "
        ORDER BY w.id ASC
    ");
} else {
    $res = $conn->query("
        SELECT w.id AS id, w.nazwa AS nazwa_w, w.id_zawodow, z.nazwa AS nazwa_z
        FROM wyscigi w
        LEFT JOIN zawody z ON w.id_zawodow = z.id
        ORDER BY w.id ASC
    ");
}

$wyscigi = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $wyscigi[] = $r;
    }
    $res->free();
}

// pobierz drużyny pogrupowane po id_wyscigu (z polem tor)
$druzyny_by_wyscig = [];
$res3 = $conn->query("SELECT id, nazwa, wynik, tor, miejsce, id_wyscigu FROM druzyny ORDER BY id_wyscigu ASC, miejsce ASC");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $idw = (int)$row['id_wyscigu'];
        if (!isset($druzyny_by_wyscig[$idw])) {
            $druzyny_by_wyscig[$idw] = [];
        }
        $druzyny_by_wyscig[$idw][] = $row;
    }
    $res3->free();
}

// HTML output
if (count($wyscigi) === 0) {
    echo '<div class="card-body"><em>Brak wyścigów dla wybranego kryterium.</em></div>';
} else {
    foreach ($wyscigi as $w) {
        echo '<div class="card shadow-sm mb-3">';
        echo '<div class="card-header">';
        echo '<strong>' . htmlspecialchars($w['nazwa_w']) . '</strong>';
        echo ' <small class="text-muted">(' . htmlspecialchars(isset($w['nazwa_z']) ? $w['nazwa_z'] : '—') . ')</small>';
        echo '</div>';
        echo '<div class="card-body">';

        $teams = isset($druzyny_by_wyscig[(int)$w['id']]) ? $druzyny_by_wyscig[(int)$w['id']] : [];
        if (count($teams) === 0) {
            echo '<em>Brak drużyn w tym wyścigu.</em>';
        } else {
            echo '<table class="table table-striped mb-0">';
            echo '<thead>';
            echo '<tr><th style="width:80px">Miejsce</th><th>Nazwa drużyny</th><th style="width:60px">Tor</th><th style="width:120px">Wynik</th></tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($teams as $t) {
                $wynik = $t['wynik'];
                $tor = $t['tor'];
                $miejsce = (int)$t['miejsce'];
                $nazwa_t = htmlspecialchars($t['nazwa']);
                echo '<tr>';
                echo '<td><strong>' . $miejsce . '</strong></td>';
                echo '<td>' . $nazwa_t . '</td>';
                echo '<td>' . ($tor !== null && $tor !== '' ? htmlspecialchars($tor) : '—') . '</td>';
                echo '<td>' . ($wynik !== null && $wynik !== '' ? htmlspecialchars($wynik) : '—') . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
        echo '</div>';
        echo '</div>';
    }
}
?>