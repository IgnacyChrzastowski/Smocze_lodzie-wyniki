<?php
require_once "config.php";
header('Content-Type: application/json; charset=utf-8');

// ─── POST: operator saves settings (session-protected) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Brak autoryzacji.']);
        exit;
    }

    $allowed = ['stream_zawody_id', 'stream_wyscig_id', 'stream_tryb_tabelki', 'stream_tryb_belki'];
    $stmt = $conn->prepare("INSERT INTO ustawienia (klucz, wartosc) VALUES (?, ?) ON DUPLICATE KEY UPDATE wartosc = VALUES(wartosc)");
    foreach ($allowed as $k) {
        if (isset($_POST[$k])) {
            $v = (string)$_POST[$k];
            $stmt->bind_param('ss', $k, $v);
            $stmt->execute();
        }
    }
    $stmt->close();

    $resp = ['success' => true];
    // Jeśli zmieniły się zawody — zwróć nową listę wyścigów do selecta
    if (isset($_POST['stream_zawody_id'])) {
        $zid = (int)$_POST['stream_zawody_id'];
        $list = [];
        $res = $conn->query("SELECT id, nazwa FROM wyscigi WHERE id_zawodow = " . $zid . " ORDER BY id ASC");
        if ($res) { while ($r = $res->fetch_assoc()) $list[] = $r; $res->free(); }
        $resp['wyscigi'] = $list;
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── GET: stream display — public ─────────────────────────────────────────

// Wczytaj ustawienia streamu
$s = [];
$res = $conn->query("SELECT klucz, wartosc FROM ustawienia WHERE klucz IN ('stream_wyscig_id','stream_tryb_tabelki')");
if ($res) { while ($r = $res->fetch_assoc()) $s[$r['klucz']] = $r['wartosc']; $res->free(); }

$wyscig_id    = (int)($s['stream_wyscig_id'] ?? 0);
$tryb_tabelki = $s['stream_tryb_tabelki'] ?? 'tory';

// stream-page zawsze pokazuje dokładnie wybrany wyścig (tryb belki nie ma tu zastosowania)

if ($wyscig_id <= 0) {
    echo json_encode(['html' => '', 'empty' => true, 'type' => $tryb_tabelki]);
    exit;
}

// Szczegóły wyścigu
$race = null;
$res = $conn->query("
    SELECT w.id, w.nazwa, w.opis,
           k.nazwa AS kat, d.nazwa AS dyst, f.nazwa AS faza
    FROM wyscigi w
    LEFT JOIN kategorie k ON w.id_kategorii = k.id
    LEFT JOIN dystanse  d ON w.id_dystansu  = d.id
    LEFT JOIN fazy      f ON w.id_fazy      = f.id
    WHERE w.id = $wyscig_id
");
if ($res && $res->num_rows > 0) { $race = $res->fetch_assoc(); $res->free(); }

if (!$race) {
    echo json_encode(['html' => '', 'empty' => true, 'type' => $tryb_tabelki]);
    exit;
}

// Buduj nagłówek: WYŚCIG ... KAT. ... DYSTANS FAZA
$header_parts = [mb_strtoupper($race['nazwa'])];
if ($race['kat'])  $header_parts[] = 'KAT. ' . mb_strtoupper($race['kat']);
if ($race['dyst']) $header_parts[] = mb_strtoupper($race['dyst']);
if ($race['faza']) $header_parts[] = mb_strtoupper($race['faza']);
$header = implode(' ', $header_parts);

// Drużyny w wyścigu
$teams = [];
$res = $conn->query("
    SELECT wn.miejsce,
           wn.tor AS tor_nr,
           wn.wynik,
           COALESCE(dg.nazwa, wn.nazwa) AS druzyna,
           COALESCE(t.nazwa, NULLIF(CONCAT('Tor ', wn.tor), 'Tor 0')) AS tor_nazwa
    FROM wyniki wn
    LEFT JOIN druzyny dg ON wn.id_druzyny = dg.id
    LEFT JOIN tory    t  ON wn.id_toru    = t.id
    WHERE wn.id_wyscigu = $wyscig_id
    ORDER BY wn.miejsce ASC, wn.id ASC
");
if ($res) { while ($r = $res->fetch_assoc()) $teams[] = $r; $res->free(); }

// Buduj HTML
$hdr = '<div class="race-header">'
    . '<div class="race-header-text">' . htmlspecialchars($header) . '</div>'
    . ($race['opis'] ? '<div class="race-header-opis">' . htmlspecialchars($race['opis']) . '</div>' : '')
    . '</div>';

if ($tryb_tabelki === 'tory') {
    // Sortuj po numerze toru
    usort($teams, fn($a, $b) => (int)$a['tor_nr'] <=> (int)$b['tor_nr']);
    $rows = '';
    foreach ($teams as $t) {
        $tor_label = $t['tor_nazwa'] ? mb_strtoupper(htmlspecialchars($t['tor_nazwa'])) : ('TOR ' . (int)$t['tor_nr']);
        $druzyna   = htmlspecialchars(mb_strtoupper($t['druzyna'] ?? ''));
        $rows .= '<div class="race-row">'
            . '<div class="col-label">' . $tor_label . '</div>'
            . '<div class="col-team">'  . $druzyna   . '</div>'
            . '</div>';
    }
    $logo_html = '<div class="tbl-brand"><img src="https://fromair.pl/wp-content/uploads/2025/02/Logo_bez_ta_Obszar_roboczy_1-120x120.png" alt="fromair.pl" class="tbl-logo"><span class="tbl-brand-name">fromair.pl</span></div>';
    $html = '<div class="tbl-tory">' . $logo_html . $hdr . $rows . '</div>';
} else {
    // Sortuj po miejscu
    usort($teams, fn($a, $b) => (int)$a['miejsce'] <=> (int)$b['miejsce']);
    $rows = '';
    foreach ($teams as $t) {
        // Pomijaj wiersze bez wyniku w trybie miejsc
        if ($t['wynik'] === null || $t['wynik'] === '') continue;
        $msc_label = (int)$t['miejsce'] . ' MIEJSCE';
        $druzyna   = htmlspecialchars(mb_strtoupper($t['druzyna'] ?? ''));
        $wynik     = htmlspecialchars($t['wynik'] ?? '');
        $rows .= '<div class="race-row">'
            . '<div class="col-place">'  . $msc_label . '</div>'
            . '<div class="col-team">'   . $druzyna   . '</div>'
            . '<div class="col-result">' . $wynik     . '</div>'
            . '</div>';
    }
    $logo_html2 = '<div class="tbl-brand"><img src="https://fromair.pl/wp-content/uploads/2025/02/Logo_bez_ta_Obszar_roboczy_1-120x120.png" alt="fromair.pl" class="tbl-logo"><span class="tbl-brand-name">fromair.pl</span></div>';
    $html = '<div class="tbl-miejsca">' . $logo_html2 . $hdr . $rows . '</div>';
}

echo json_encode(['html' => $html, 'empty' => false, 'type' => $tryb_tabelki], JSON_UNESCAPED_UNICODE);