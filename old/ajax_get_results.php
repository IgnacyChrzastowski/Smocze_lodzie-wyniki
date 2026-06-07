<?php
require_once "config.php";

// Pobierz zawody_id z GET lub ustawień
$zawody_id = isset($_GET['zawody_id']) ? (int)$_GET['zawody_id'] : 0;

if ($zawody_id === 0 && isset($_COOKIE['zawody_prezentacyjne'])) {
    $zawody_id = (int)$_COOKIE['zawody_prezentacyjne'];
}

// Zapytanie do bazy
if ($zawody_id > 0) {
    $res = $conn->query("
        SELECT w.id AS id, w.nazwa AS nazwa_w, w.id_zawodow, z.nazwa AS nazwa_z
        FROM wyscigi w
        LEFT JOIN zawody z ON w.id_zawodow = z.id
        WHERE w.id_zawodow = " . (int)$zawody_id . "
        ORDER BY w.id DESC
    ");
} else {
    $res = $conn->query("
        SELECT w.id AS id, w.nazwa AS nazwa_w, w.id_zawodow, z.nazwa AS nazwa_z
        FROM wyscigi w
        LEFT JOIN zawody z ON w.id_zawodow = z.id
        ORDER BY w.id DESC
    ");
}

$wyscigi = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $wyscigi[] = $r;
    }
    $res->free();
}

// Pobierz drużyny
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
    echo '<div class="empty-state"><div>Brak wyścigów do wyświetlenia.</div></div>';
} else {
    foreach ($wyscigi as $w) {
        echo '<div class="race-card">';
        echo '<div class="race-title">' . htmlspecialchars($w['nazwa_w']) . '</div>';

        $teams = isset($druzyny_by_wyscig[(int)$w['id']]) ? $druzyny_by_wyscig[(int)$w['id']] : [];
        if (count($teams) === 0) {
            echo '<div class="px-3 py-3 text-muted" style="font-size:.95rem"><em>Brak drużyn.</em></div>';
        } else {
            echo '<table class="results-table">';
            echo '<thead><tr>';
            echo '<th style="width:56px;text-align:center">#</th>';
            echo '<th>Drużyna</th>';
            echo '<th style="width:60px;text-align:center">Tor</th>';
            echo '<th style="text-align:right">Wynik</th>';
            echo '</tr></thead><tbody>';
            foreach ($teams as $t) {
                $miejsce = (int)$t['miejsce'];
                $wynik   = $t['wynik'];
                $tor     = $t['tor'];
                $nazwa_t = htmlspecialchars($t['nazwa']);

                // klasa medalu
                $medal = '';
                if ($miejsce === 1) $medal = 'gold';
                elseif ($miejsce === 2) $medal = 'silver';
                elseif ($miejsce === 3) $medal = 'bronze';

                echo '<tr>';
                echo '<td style="text-align:center"><span class="place-badge ' . $medal . '">' . $miejsce . '</span></td>';
                echo '<td><span class="team-name">' . $nazwa_t . '</span></td>';
                echo '<td style="text-align:center">';
                echo ($tor !== null && $tor !== '' && (int)$tor > 0)
                    ? '<span class="lane-badge">' . (int)$tor . '</span>'
                    : '<span style="color:#ccc">—</span>';
                echo '</td>';
                echo '<td style="text-align:right">';
                echo ($wynik !== null && $wynik !== '')
                    ? '<span class="result-time">' . htmlspecialchars($wynik) . '</span>'
                    : '<span class="result-time empty">—</span>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
    }
}
?>