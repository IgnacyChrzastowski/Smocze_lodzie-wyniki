<?php
// Klasyfikacja generalna: zwraca wszystkie drużyny i ich wyniki ze wszystkich wyścigów
// w danej kategorii, na danym dystansie, w danej fazie — posortowane czasami rosnąco,
// z przypisanymi miejscami. (Realizacja polecenia z dokumentu specyfikacji.)

session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak autoryzacji.']);
    exit;
}

require_once "config.php";

// Konwertuje wynik MM:SS,mmm na milisekundy (do sortowania) — ta sama logika co w management.php
function wynik_na_ms_klasyfikacja(string $wynik): int {
    if (!preg_match('/^(\d{1,2}):(\d{2}),(\d{3})$/', $wynik, $m)) return PHP_INT_MAX;
    return ((int)$m[1] * 60000) + ((int)$m[2] * 1000) + (int)$m[3];
}

$id_kategorii = isset($_GET['id_kategorii']) ? (int)$_GET['id_kategorii'] : 0;
$id_dystansu  = isset($_GET['id_dystansu'])  ? (int)$_GET['id_dystansu']  : 0;
$id_fazy      = isset($_GET['id_fazy'])      ? (int)$_GET['id_fazy']      : 0;

if ($id_kategorii <= 0 || $id_dystansu <= 0 || $id_fazy <= 0) {
    echo json_encode(['error' => 'Wybierz kategorię, dystans i fazę.']);
    exit;
}

// WYŚWIETLA WSZYSTKIE DRUŻYNY ORAZ ICH WYNIKI ZE WSZYSTKICH WYŚCIGÓW
// W DANEJ KATEGORII NA DANYM DYSTANSIE W DANEJ FAZIE
$stmt = $conn->prepare("
    SELECT COALESCE(dg.nazwa, wn.nazwa) AS nazwa_druzyny, wn.wynik, r.nazwa AS nazwa_wyscigu
    FROM wyniki wn
    JOIN wyscigi r ON wn.id_wyscigu = r.id
    LEFT JOIN druzyny dg ON wn.id_druzyny = dg.id
    WHERE r.id_kategorii = ? AND r.id_dystansu = ? AND r.id_fazy = ?
      AND wn.wynik IS NOT NULL AND wn.wynik <> ''
");

if (!$stmt) {
    echo json_encode(['error' => 'Błąd przygotowania zapytania: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iii", $id_kategorii, $id_dystansu, $id_fazy);
$stmt->execute();
$result = $stmt->get_result();

$wiersze = [];
while ($row = $result->fetch_assoc()) {
    $row['wynik_ms'] = wynik_na_ms_klasyfikacja($row['wynik']);
    $wiersze[] = $row;
}
$stmt->close();

// SORTUJE JE CZASAMI OD NAJKRÓTSZEGO DO NAJDŁUŻSZEGO
usort($wiersze, function ($a, $b) {
    return $a['wynik_ms'] <=> $b['wynik_ms'];
});

// I PRZYPISUJE MIEJSCA
$ranking = [];
$miejsce = 1;
foreach ($wiersze as $w) {
    $ranking[] = [
        'miejsce'       => $miejsce,
        'nazwa_druzyny' => $w['nazwa_druzyny'],
        'wynik'         => $w['wynik'],
        'nazwa_wyscigu' => $w['nazwa_wyscigu'],
    ];
    $miejsce++;
}

echo json_encode(['ranking' => $ranking], JSON_UNESCAPED_UNICODE);