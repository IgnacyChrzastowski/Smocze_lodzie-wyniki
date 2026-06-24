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
$res = $conn->query("SELECT klucz, wartosc FROM ustawienia WHERE klucz IN ('stream_zawody_id','stream_wyscig_id','stream_tryb_tabelki','stream_tryb_belki')");
if ($res) { while ($r = $res->fetch_assoc()) $s[$r['klucz']] = $r['wartosc']; $res->free(); }

$zawody_id    = (int)($s['stream_zawody_id'] ?? 0);
$base_id      = (int)($s['stream_wyscig_id'] ?? 0);
$tryb_tabelki = $s['stream_tryb_tabelki'] ?? 'tory';
$tryb_belki   = $s['stream_tryb_belki'] ?? 'aktualny';

// Wyznacz faktyczny wyścig na podstawie trybu belki
$wyscig_id = $base_id;
if ($base_id > 0 && $zawody_id > 0) {
    if ($tryb_belki === 'poprzedni') {
        $r = $conn->query("SELECT id FROM wyscigi WHERE id_zawodow=$zawody_id AND id<$base_id ORDER BY id DESC LIMIT 1");
        if ($r && $r->num_rows > 0) { $wyscig_id = (int)$r->fetch_assoc()['id']; $r->free(); }
    } elseif ($tryb_belki === 'nastepny') {
        $r = $conn->query("SELECT id FROM wyscigi WHERE id_zawodow=$zawody_id AND id>$base_id ORDER BY id ASC LIMIT 1");
        if ($r && $r->num_rows > 0) { $wyscig_id = (int)$r->fetch_assoc()['id']; $r->free(); }
    }
}

if ($wyscig_id <= 0) {
    echo json_encode(['html' => '', 'empty' => true, 'type' => $tryb_tabelki]);
    exit;
}

// Szczegóły wyścigu
$race = null;
$res = $conn->query("
    SELECT w.id, w.nazwa,
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
$hdr = '<div class="race-header">' . htmlspecialchars($header) . '</div>';

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
    $html = '<div class="tbl-tory">' . $hdr . $rows . '</div>';
} else {
    // Sortuj po miejscu
    usort($teams, fn($a, $b) => (int)$a['miejsce'] <=> (int)$b['miejsce']);
    $rows = '';
    foreach ($teams as $t) {
        $msc_label = (int)$t['miejsce'] . ' MIEJSCE';
        $druzyna   = htmlspecialchars(mb_strtoupper($t['druzyna'] ?? ''));
        $wynik     = htmlspecialchars($t['wynik'] ?? '');
        $rows .= '<div class="race-row">'
            . '<div class="col-place">'  . $msc_label . '</div>'
            . '<div class="col-team">'   . $druzyna   . '</div>'
            . '<div class="col-result">' . $wynik     . '</div>'
            . '</div>';
    }
    $html = '<div class="tbl-miejsca">' . $hdr . $rows . '</div>';
}

echo json_encode(['html' => $html, 'empty' => false, 'type' => $tryb_tabelki], JSON_UNESCAPED_UNICODE);