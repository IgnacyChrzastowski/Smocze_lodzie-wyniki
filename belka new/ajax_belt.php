<?php
// Belka informacyjna — zwraca dane o aktualnym lub następnym wyścigu.
// Tryb belki (aktualny/nastepny/poprzedni) jest tu obsługiwany.
require_once "config.php";
header('Content-Type: application/json; charset=utf-8');

// Wczytaj ustawienia
$s = [];
$res = $conn->query("SELECT klucz, wartosc FROM ustawienia WHERE klucz IN ('stream_zawody_id','stream_wyscig_id','stream_tryb_belki')");
if ($res) { while ($r = $res->fetch_assoc()) $s[$r['klucz']] = $r['wartosc']; $res->free(); }

$zawody_id  = (int)($s['stream_zawody_id'] ?? 0);
$base_id    = (int)($s['stream_wyscig_id'] ?? 0);
$tryb_belki = $s['stream_tryb_belki'] ?? 'aktualny';

// Wyznacz wyścig dla belki na podstawie trybu
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
    echo json_encode(['html' => '', 'empty' => true]);
    exit;
}

// Szczegóły wyścigu
$race = null;
$res = $conn->query("
    SELECT w.nazwa, w.opis,
           k.nazwa AS kat, d.nazwa AS dyst, f.nazwa AS faza
    FROM wyscigi w
    LEFT JOIN kategorie k ON w.id_kategorii = k.id
    LEFT JOIN dystanse  d ON w.id_dystansu  = d.id
    LEFT JOIN fazy      f ON w.id_fazy      = f.id
    WHERE w.id = $wyscig_id
");
if ($res && $res->num_rows > 0) { $race = $res->fetch_assoc(); $res->free(); }

if (!$race) {
    echo json_encode(['html' => '', 'empty' => true]);
    exit;
}

// Etykieta trybu
$labels = ['aktualny' => 'AKTUALNY', 'nastepny' => 'NASTĘPNY', 'poprzedni' => 'POPRZEDNI'];
$label  = $labels[$tryb_belki] ?? mb_strtoupper($tryb_belki);

// Buduj HTML belki
$race_name = htmlspecialchars('WYŚCIG ' . mb_strtoupper($race['nazwa']));
$kat  = $race['kat']  ? '<span class="belt-tag">' . htmlspecialchars(mb_strtoupper($race['kat']))  . '</span>' : '';
$dyst = $race['dyst'] ? '<span class="belt-tag">' . htmlspecialchars(mb_strtoupper($race['dyst'])) . '</span>' : '';
$faza = $race['faza'] ? '<span class="belt-tag">' . htmlspecialchars(mb_strtoupper($race['faza'])) . '</span>' : '';

$html = '<div class="belt belt--' . htmlspecialchars($tryb_belki) . '">'
    . '<div class="belt-label">' . htmlspecialchars($label) . '</div>'
    . '<div class="belt-body">'
    .   '<span class="belt-name">' . $race_name . '</span>'
    .   ($race['opis'] ? '<span class="belt-opis">' . htmlspecialchars($race['opis']) . '</span>' : '')
    .   '<span class="belt-sep"></span>'
    .   '<span class="belt-tags">' . $kat . $dyst . $faza . '</span>'
    . '</div>'
    . '<a href="https://fromair.pl/" target="_blank" class="belt-brand">'
    .   '<img src="https://fromair.pl/wp-content/uploads/2025/02/Logo_bez_ta_Obszar_roboczy_1-120x120.png" alt="fromair.pl">'
    .   '<span>fromair.pl</span>'
    . '</a>'
    . '</div>';

echo json_encode(['html' => $html, 'empty' => false, 'tryb' => $tryb_belki], JSON_UNESCAPED_UNICODE);