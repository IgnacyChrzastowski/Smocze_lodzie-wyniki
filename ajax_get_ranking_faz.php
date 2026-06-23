<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak autoryzacji.']);
    exit;
}

require_once "config.php";

function wynik_na_ms_rf(string $wynik): int {
    if (!preg_match('/^(\d{1,2}):(\d{2}),(\d{3})$/', $wynik, $m)) return PHP_INT_MAX;
    return ((int)$m[1] * 60000) + ((int)$m[2] * 1000) + (int)$m[3];
}

$id_zawodow   = isset($_GET['id_zawodow'])   ? (int)$_GET['id_zawodow']   : 0;
$id_kategorii = isset($_GET['id_kategorii']) ? (int)$_GET['id_kategorii'] : 0;
$id_dystansu  = isset($_GET['id_dystansu'])  ? (int)$_GET['id_dystansu']  : 0;

// Parsuj listę ID faz przekazaną z checkboxów/selecta
$fazy_ids = [];
if (!empty($_GET['fazy_ids'])) {
    $fazy_ids = array_values(array_filter(array_map('intval', explode(',', $_GET['fazy_ids']))));
}

// Fallback: max_kolejnosc (wsteczna kompatybilność)
if (empty($fazy_ids) && isset($_GET['max_kolejnosc'])) {
    $max = (int)$_GET['max_kolejnosc'];
    if ($max > 1) {
        $res = $conn->query("SELECT id FROM fazy WHERE kolejnosc < " . $max . " AND kolejnosc IS NOT NULL");
        if ($res) {
            while ($r = $res->fetch_assoc()) $fazy_ids[] = (int)$r['id'];
            $res->free();
        }
    }
}

if ($id_zawodow <= 0 || empty($fazy_ids)) {
    echo json_encode(['ranking' => [], 'debug' => 'brak zawodow lub faz']);
    exit;
}

// ─── Buduj WHERE i params dynamicznie ─────────────────────────────────────
$where_parts = [
    'r.id_zawodow = ?',
    'r.id_fazy IN (' . implode(',', array_fill(0, count($fazy_ids), '?')) . ')',
    'wn.wynik IS NOT NULL',
    "wn.wynik <> ''",
];
$params = array_merge([$id_zawodow], $fazy_ids);

if ($id_kategorii > 0) {
    $where_parts[] = 'r.id_kategorii = ?';
    $params[]      = $id_kategorii;
}
if ($id_dystansu > 0) {
    $where_parts[] = 'r.id_dystansu = ?';
    $params[]      = $id_dystansu;
}

$sql = "
    SELECT COALESCE(dg.nazwa, wn.nazwa) AS nazwa_druzyny,
           wn.wynik,
           r.nazwa AS nazwa_wyscigu,
           f.nazwa AS nazwa_fazy,
           f.kolejnosc
    FROM wyniki wn
    JOIN  wyscigi r ON wn.id_wyscigu  = r.id
    JOIN  fazy    f ON r.id_fazy      = f.id
    LEFT JOIN druzyny dg ON wn.id_druzyny = dg.id
    WHERE " . implode(' AND ', $where_parts);

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Błąd prepare: ' . $conn->error]);
    exit;
}

// PHP 8.1+ — execute() z tablicą wartości, eliminuje problemy bind_param ze spreadem
if (!$stmt->execute($params)) {
    echo json_encode(['error' => 'Błąd execute: ' . $stmt->error]);
    $stmt->close();
    exit;
}

$result = $stmt->get_result();
if ($result === false) {
    echo json_encode(['error' => 'Błąd get_result: ' . $stmt->error]);
    $stmt->close();
    exit;
}

$wiersze = [];
while ($row = $result->fetch_assoc()) {
    $row['wynik_ms'] = wynik_na_ms_rf($row['wynik']);
    $wiersze[] = $row;
}
$stmt->close();

// Sortuj od najkrótszego czasu
usort($wiersze, function ($a, $b) { return $a['wynik_ms'] <=> $b['wynik_ms']; });

$ranking = [];
$miejsce = 1;
foreach ($wiersze as $w) {
    $ranking[] = [
        'miejsce'       => $miejsce++,
        'nazwa_druzyny' => $w['nazwa_druzyny'],
        'wynik'         => $w['wynik'],
        'nazwa_wyscigu' => $w['nazwa_wyscigu'],
        'nazwa_fazy'    => $w['nazwa_fazy'],
    ];
}

echo json_encode(['ranking' => $ranking], JSON_UNESCAPED_UNICODE);