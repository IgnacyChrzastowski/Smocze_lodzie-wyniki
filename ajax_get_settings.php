<?php

require_once "config.php";
header('Content-Type: application/json; charset=utf-8');

$zawody_id = 0;
$nazwa_zawodow = '';
$status_zawodow = '';

// Pobierz aktywne zawody z ustawień
$res = $conn->query("SELECT wartosc FROM ustawienia WHERE klucz = 'aktywne_zawody' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $zawody_id = (int)$row['wartosc'];
    $res->free();
}

// Sprawdź czy zawody o tym ID istnieją i są AKTYWNE
if ($zawody_id > 0) {
    $res = $conn->query("SELECT nazwa, status FROM zawody WHERE id = " . (int)$zawody_id . " AND status = 'aktywne'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nazwa_zawodow  = $row['nazwa'];
        $status_zawodow = $row['status'];
        $res->free();
    } else {
        // Zawody nie istnieją lub są archiwami — szukamy pierwszych aktywnych
        $zawody_id = 0;
    }
}

// Fallback: pierwsze aktywne zawody
if ($zawody_id === 0) {
    $res = $conn->query("SELECT id, nazwa, status FROM zawody WHERE status = 'aktywne' ORDER BY id ASC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $zawody_id = (int)$row['id'];
        $nazwa_zawodow = $row['nazwa'];
        $status_zawodow = $row['status'];
        $res->free();

        // Auto-update ustawienia na nowe aktywne zawody
        $stmt = $conn->prepare("INSERT INTO ustawienia (`klucz`, `wartosc`) VALUES (?, ?) ON DUPLICATE KEY UPDATE wartosc = VALUES(wartosc)");
        if ($stmt) {
            $klucz = 'aktywne_zawody';
            $wartosc = (string)$zawody_id;
            $stmt->bind_param("ss", $klucz, $wartosc);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Pobierz wszystkie zawody do listy rozwijalnej
// Kolejność: aktywne na górze, potem zarchiwizowane malejąco po id
$lista_zawodow = [];
$res_lista = $conn->query("
    SELECT id, nazwa, status
    FROM zawody
    ORDER BY
        CASE status WHEN 'aktywne' THEN 0 ELSE 1 END ASC,
        id DESC
");
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
    'zawody_id'      => $zawody_id,
    'nazwa_zawodow'  => $nazwa_zawodow,
    'status_zawodow' => $status_zawodow,
    'lista_zawodow'  => $lista_zawodow
], JSON_UNESCAPED_UNICODE);
?>