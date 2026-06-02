<?php

require_once "config.php";
header('Content-Type: application/json; charset=utf-8');

$zawody_id = 0;
$nazwa_zawodow = '';

$res = $conn->query("SELECT wartosc FROM ustawienia WHERE klucz = 'aktywne_zawody' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $zawody_id = (int)$row['wartosc'];
    $res->free();
}

if ($zawody_id === 0) {
    $res = $conn->query("SELECT id FROM zawody WHERE status = 'aktywne' ORDER BY id ASC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $zawody_id = (int)$row['id'];
        $res->free();
    }
}

if ($zawody_id > 0) {
    $res = $conn->query("SELECT nazwa FROM zawody WHERE id = " . (int)$zawody_id);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nazwa_zawodow = $row['nazwa'];
        $res->free();
    }
}

// Pobierz wszystkie zawody do listy rozwijalnej
$lista_zawodow = [];
$res_lista = $conn->query("SELECT id, nazwa, status FROM zawody ORDER BY status ASC, id DESC");
// status ASC: 'aktywne' przed 'zakończone' (a < z)
if ($res_lista) {
    while ($row = $res_lista->fetch_assoc()) {
        $lista_zawodow[] = [
            'id'     => (int)$row['id'],
            'nazwa'  => $row['nazwa'],
            'status' => $row['status']
        ];
    }
    $res_lista->free();
}

echo json_encode([
    'zawody_id'     => $zawody_id,
    'nazwa_zawodow' => $nazwa_zawodow,
    'lista_zawodow' => $lista_zawodow
], JSON_UNESCAPED_UNICODE);
?>
