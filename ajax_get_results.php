<?php

require_once "config.php";

// Pobierz zawody_id z GET
$zawody_id = isset($_GET['zawody_id']) ? (int)$_GET['zawody_id'] : 0;

// Fallback: aktywne zawody z ustawień
if ($zawody_id === 0) {
    $res = $conn->query("SELECT wartosc FROM ustawienia WHERE klucz = 'aktywne_zawody' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $zawody_id = (int)$row['wartosc'];
        $res->free();
    }
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

// Pobierz wyniki (dawniej tabela druzyny) wraz z nazwą drużyny globalnej i nazwą toru, jeśli przypisane
$druzyny_by_wyscig = [];
$res3 = $conn->query("
    SELECT wn.id,
           COALESCE(dg.nazwa, wn.nazwa) AS nazwa,
           wn.wynik,
           wn.tor AS tor_legacy,
           t.nazwa AS tor_nazwa,
           wn.miejsce,
           wn.id_wyscigu
    FROM wyniki wn
    LEFT JOIN druzyny dg ON wn.id_druzyny = dg.id
    LEFT JOIN tory t ON wn.id_toru = t.id
    ORDER BY wn.id_wyscigu ASC, wn.miejsce ASC
");
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
                $nazwa_t = htmlspecialchars($t['nazwa']);

                // Etykieta toru: priorytet ma nazwa z tabeli `tory`, fallback do starej kolumny liczbowej
                $tor_label = '';
                if ($t['tor_nazwa'] !== null && $t['tor_nazwa'] !== '') {
                    $tor_label = $t['tor_nazwa'];
                } elseif ($t['tor_legacy'] !== null && (int)$t['tor_legacy'] > 0) {
                    $tor_label = (string)(int)$t['tor_legacy'];
                }

                // Miejsce i medal — TYLKO gdy drużyna ma wpisany wynik
                $has_wynik = ($wynik !== null && $wynik !== '');
                $medal = '';
                if ($has_wynik) {
                    if ($miejsce === 1) $medal = 'gold';
                    elseif ($miejsce === 2) $medal = 'silver';
                    elseif ($miejsce === 3) $medal = 'bronze';
                }

                echo '<tr>';
                echo '<td style="text-align:center">';
                if ($has_wynik) {
                    echo '<span class="place-badge ' . $medal . '">' . $miejsce . '</span>';
                } else {
                    echo '<span style="color:#ccc">—</span>';
                }
                echo '</td>';
                echo '<td><span class="team-name">' . $nazwa_t . '</span></td>';
                echo '<td style="text-align:center">';
                echo ($tor_label !== '')
                    ? '<span class="lane-badge">' . htmlspecialchars($tor_label) . '</span>'
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