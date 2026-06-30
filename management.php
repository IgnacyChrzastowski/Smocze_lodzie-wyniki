<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

$alert = "";

// ---------------------- HANDLERY POST ----------------------------

// Dodaj nowe zawody
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_zawody') {
    $nazwa    = trim(isset($_POST['nazwa_zawodow']) ? $_POST['nazwa_zawodow'] : '');
    $is_ajax  = !empty($_POST['ajax']);
    if ($is_ajax) header('Content-Type: application/json; charset=utf-8');
    if ($nazwa === '') {
        if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Podaj nazwę zawodów.']); exit; }
        $alert = "Podaj nazwę zawodów.";
    } else {
        $stmt = $conn->prepare("INSERT INTO zawody (nazwa, status) VALUES (?, 'aktywne')");
        if ($stmt) {
            $stmt->bind_param("s", $nazwa);
            if ($stmt->execute()) {
                $new_id = (int)$conn->insert_id;
                $stmt->close();
                if ($is_ajax) { echo json_encode(['success' => true, 'id' => $new_id, 'nazwa' => $nazwa]); exit; }
                header("Location: " . $_SERVER['PHP_SELF']); exit;
            } else {
                $db_err = $conn->error; $stmt->close();
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Błąd zapisu: ' . $db_err]); exit; }
                $alert = "Błąd zapisu zawodów: " . $db_err;
            }
        } else {
            if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Błąd przygotowania zapytania.']); exit; }
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Edytuj zawody
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_zawody') {
    $id = (int)(isset($_POST['id_zawody']) ? $_POST['id_zawody'] : 0);
    $nazwa = trim(isset($_POST['nazwa_zawodow_edit']) ? $_POST['nazwa_zawodow_edit'] : '');
    if ($id <= 0 || $nazwa === '') {
        $alert = "Niepoprawne dane przy edycji zawodów.";
    } else {
        $stmt = $conn->prepare("UPDATE zawody SET nazwa = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $nazwa, $id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd aktualizacji zawodów: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Archiwizuj/Aktywuj zawody
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_archive_zawody') {
    $id = (int)(isset($_POST['id_zawody']) ? $_POST['id_zawody'] : 0);
    if ($id <= 0) {
        $alert = "Niepoprawne id zawodów.";
    } else {
        // Pobierz obecny status
        $res = $conn->query("SELECT status FROM zawody WHERE id = " . $id . " LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $current_status = $row['status'];
            $res->free();

            // Przełącz status
            $new_status = ($current_status === 'aktywne') ? 'zarchiwizowane' : 'aktywne';

            $stmt = $conn->prepare("UPDATE zawody SET status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $new_status, $id);
                if ($stmt->execute()) {
                    // Jeśli archiwizujesz zawody, które były ustawione jako prezentacyjne, przełącz na nowe aktywne
                    if ($new_status === 'zarchiwizowane') {
                        $res_check = $conn->query("SELECT wartosc FROM ustawienia WHERE klucz = 'aktywne_zawody' LIMIT 1");
                        if ($res_check && $res_check->num_rows > 0) {
                            $row_check = $res_check->fetch_assoc();
                            if ((int)$row_check['wartosc'] === $id) {
                                // Te zawody TO zawody prezentacyjne, szukamy nowych aktywnych
                                $res_nowe = $conn->query("SELECT id FROM zawody WHERE status = 'aktywne' AND id != " . $id . " ORDER BY id ASC LIMIT 1");
                                if ($res_nowe && $res_nowe->num_rows > 0) {
                                    $row_nowe = $res_nowe->fetch_assoc();
                                    $new_zawody_id = (int)$row_nowe['id'];
                                    $res_nowe->free();

                                    $stmt_upd = $conn->prepare("UPDATE ustawienia SET wartosc = ? WHERE klucz = 'aktywne_zawody'");
                                    if ($stmt_upd) {
                                        $val = (string)$new_zawody_id;
                                        $stmt_upd->bind_param("s", $val);
                                        $stmt_upd->execute();
                                        $stmt_upd->close();
                                    }
                                }
                            }
                            $res_check->free();
                        }
                    }

                    $alert = "Status zawodów zmieniony na: " . $new_status;
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $alert = "Błąd aktualizacji statusu: " . $conn->error;
                }
                $stmt->close();
            } else {
                $alert = "Błąd przygotowania zapytania: " . $conn->error;
            }
        } else {
            $alert = "Nie znaleziono zawodów o podanym ID.";
        }
    }
}

// Usuń zawody
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_zawody') {
    $id = (int)(isset($_POST['id_zawody_del']) ? $_POST['id_zawody_del'] : 0);
    if ($id <= 0) {
        $alert = "Niepoprawne id przy usuwaniu zawodów.";
    } else {
        $stmt = $conn->prepare("DELETE FROM zawody WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd usuwania zawodów: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// ---------------------- HANDLERY: SŁOWNIKI (kategorie, dystanse, fazy, tory, globalna lista drużyn) ----------------------------
// Wszystkie 5 tabel mają kształt id+nazwa (dystans ma dodatkowo metry, faza dodatkowo kolejnosc),
// więc obsługujemy je jednym generycznym zestawem handlerów rozróżnianych przez pole `typ`.
$slowniki_tabele = [
        'kategoria' => 'kategorie',
        'dystans'   => 'dystanse',
        'faza'      => 'fazy',
        'tor'       => 'tory',
        'druzyna'   => 'druzyny',
];

// Dodaj pozycję słownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_slownik') {
    $typ    = isset($_POST['typ'])   ? $_POST['typ']             : '';
    $nazwa  = trim(isset($_POST['nazwa']) ? $_POST['nazwa']      : '');
    $is_ajax = !empty($_POST['ajax']);

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
    }

    if (!isset($slowniki_tabele[$typ]) || $nazwa === '') {
        if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Podaj nazwę.']); exit; }
        $alert = "Podaj nazwę.";
    } else {
        $stmt = null;
        if ($typ === 'dystans') {
            $metry = (isset($_POST['metry']) && $_POST['metry'] !== '') ? (int)$_POST['metry'] : null;
            $stmt = $conn->prepare("INSERT INTO dystanse (nazwa, metry) VALUES (?, ?)");
            if ($stmt) $stmt->bind_param("si", $nazwa, $metry);
        } elseif ($typ === 'faza') {
            $kolejnosc = (isset($_POST['kolejnosc']) && $_POST['kolejnosc'] !== '') ? (int)$_POST['kolejnosc'] : null;
            $stmt = $conn->prepare("INSERT INTO fazy (nazwa, kolejnosc) VALUES (?, ?)");
            if ($stmt) $stmt->bind_param("si", $nazwa, $kolejnosc);
        } else {
            $tabela = $slowniki_tabele[$typ];
            $stmt = $conn->prepare("INSERT INTO `$tabela` (nazwa) VALUES (?)");
            if ($stmt) $stmt->bind_param("s", $nazwa);
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $new_id = (int)$conn->insert_id;
                $stmt->close();
                if ($is_ajax) {
                    echo json_encode(['success' => true, 'id' => $new_id, 'nazwa' => $nazwa]);
                    exit;
                }
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $db_err = $conn->error;
                $stmt->close();
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Błąd zapisu: ' . $db_err]); exit; }
                $alert = "Błąd zapisu: " . $db_err;
            }
        } else {
            if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Błąd przygotowania zapytania.']); exit; }
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Edytuj pozycję słownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_slownik') {
    $typ = isset($_POST['typ']) ? $_POST['typ'] : '';
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $nazwa = trim(isset($_POST['nazwa']) ? $_POST['nazwa'] : '');

    if (!isset($slowniki_tabele[$typ]) || $id <= 0 || $nazwa === '') {
        $alert = "Niepoprawne dane przy edycji.";
    } else {
        $stmt = null;
        if ($typ === 'dystans') {
            $metry = (isset($_POST['metry']) && $_POST['metry'] !== '') ? (int)$_POST['metry'] : null;
            $stmt = $conn->prepare("UPDATE dystanse SET nazwa = ?, metry = ? WHERE id = ?");
            if ($stmt) $stmt->bind_param("sii", $nazwa, $metry, $id);
        } elseif ($typ === 'faza') {
            $kolejnosc = (isset($_POST['kolejnosc']) && $_POST['kolejnosc'] !== '') ? (int)$_POST['kolejnosc'] : null;
            $stmt = $conn->prepare("UPDATE fazy SET nazwa = ?, kolejnosc = ? WHERE id = ?");
            if ($stmt) $stmt->bind_param("sii", $nazwa, $kolejnosc, $id);
        } else {
            $tabela = $slowniki_tabele[$typ];
            $stmt = $conn->prepare("UPDATE `$tabela` SET nazwa = ? WHERE id = ?");
            if ($stmt) $stmt->bind_param("si", $nazwa, $id);
        }

        if ($stmt) {
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd aktualizacji: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Usuń pozycję słownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_slownik') {
    $typ = isset($_POST['typ']) ? $_POST['typ'] : '';
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);

    if (!isset($slowniki_tabele[$typ]) || $id <= 0) {
        $alert = "Niepoprawne dane przy usuwaniu.";
    } else {
        $tabela = $slowniki_tabele[$typ];
        $stmt = $conn->prepare("DELETE FROM `$tabela` WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd usuwania (sprawdź, czy pozycja nie jest nigdzie użyta): " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Dodaj wyścig
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_wyscig') {
    $nazwa_w = 'Wyścig ' . trim(isset($_POST['nazwa_wyscigu']) ? $_POST['nazwa_wyscigu'] : '');
    $id_z = (int)(isset($_POST['id_zawodow']) ? $_POST['id_zawodow'] : 0);
    $id_kategorii   = (isset($_POST['id_kategorii'])   && $_POST['id_kategorii']   !== '') ? (int)$_POST['id_kategorii']   : null;
    $id_dystansu    = (isset($_POST['id_dystansu'])    && $_POST['id_dystansu']    !== '') ? (int)$_POST['id_dystansu']    : null;
    $id_fazy        = (isset($_POST['id_fazy'])        && $_POST['id_fazy']        !== '') ? (int)$_POST['id_fazy']        : null;
    $opis_w  = trim(isset($_POST['opis_wyscigu']) ? $_POST['opis_wyscigu'] : '');

    if ($id_z <= 0) {
        $alert = "Musisz wybrać zawody przed dodaniem wyścigu.";
    } elseif ($nazwa_w === '') {
        $alert = "Podaj nazwę wyścigu.";
    } else {
        $insw = $conn->prepare("INSERT INTO wyscigi (id_zawodow, nazwa, id_kategorii, id_dystansu, id_fazy, opis) VALUES (?, ?, ?, ?, ?, ?)");
        if ($insw) {
            $insw->bind_param("isiiis", $id_z, $nazwa_w, $id_kategorii, $id_dystansu, $id_fazy, $opis_w);
            if ($insw->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd dodawania wyścigu: " . $conn->error;
            }
            $insw->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Edytuj wyścig
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_wyscig') {
    $id = (int)(isset($_POST['id_wyscigu_edit']) ? $_POST['id_wyscigu_edit'] : 0);
    $nazwa = trim(isset($_POST['nazwa_wyscigu_edit']) ? $_POST['nazwa_wyscigu_edit'] : '');
    $id_z = (int)(isset($_POST['id_zawodow_edit']) ? $_POST['id_zawodow_edit'] : 0);
    $id_kategorii   = (isset($_POST['id_kategorii_edit'])   && $_POST['id_kategorii_edit']   !== '') ? (int)$_POST['id_kategorii_edit']   : null;
    $id_dystansu    = (isset($_POST['id_dystansu_edit'])    && $_POST['id_dystansu_edit']    !== '') ? (int)$_POST['id_dystansu_edit']    : null;
    $id_fazy        = (isset($_POST['id_fazy_edit'])        && $_POST['id_fazy_edit']        !== '') ? (int)$_POST['id_fazy_edit']        : null;
    $opis_w  = trim(isset($_POST['opis_wyscigu_edit']) ? $_POST['opis_wyscigu_edit'] : '');
    if ($id <= 0 || $nazwa === '' || $id_z <= 0) {
        $alert = "Niepoprawne dane przy edycji wyścigu.";
    } else {
        $stmt = $conn->prepare("UPDATE wyscigi SET id_zawodow = ?, nazwa = ?, id_kategorii = ?, id_dystansu = ?, id_fazy = ?, opis = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("isiiisi", $id_z, $nazwa, $id_kategorii, $id_dystansu, $id_fazy, $opis_w, $id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd aktualizacji wyścigu: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Usuń wyścig
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_wyscig') {
    $id = (int)(isset($_POST['id_wyscigu_del']) ? $_POST['id_wyscigu_del'] : 0);
    if ($id <= 0) {
        $alert = "Niepoprawne id przy usuwaniu wyścigu.";
    } else {
        $stmt = $conn->prepare("DELETE FROM wyscigi WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd usuwania wyścigu: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// ---------------------- HELPER: przelicz miejsca w wyścigu ----------------------------
// Konwertuje wynik MM:SS,mmm na milisekundy (do sortowania)
function wynik_na_ms(string $wynik): int {
    if (!preg_match('/^(\d{1,2}):(\d{2}),(\d{3})$/', $wynik, $m)) return PHP_INT_MAX;
    return ((int)$m[1] * 60000) + ((int)$m[2] * 1000) + (int)$m[3];
}

// Po każdej zmianie drużyny przeliczamy miejsca dla całego wyścigu:
// - drużyny z wynikiem sortowane rosnąco po czasie (1, 2, 3, ...)
// - drużyny bez wyniku dostają miejsca za nimi (kolejność wg ID)
function przelicz_miejsca(mysqli $conn, int $id_wyscigu): void {
    $res = $conn->query("SELECT id, wynik FROM wyniki WHERE id_wyscigu = " . $id_wyscigu . " ORDER BY id ASC");
    if (!$res) return;

    $z_wynikiem = [];
    $bez_wyniku = [];
    while ($r = $res->fetch_assoc()) {
        if ($r['wynik'] !== null && $r['wynik'] !== '') {
            $z_wynikiem[] = $r;
        } else {
            $bez_wyniku[] = $r;
        }
    }
    $res->free();

    usort($z_wynikiem, function($a, $b) {
        return wynik_na_ms($a['wynik']) <=> wynik_na_ms($b['wynik']);
    });

    $miejsce = 1;
    $upd = $conn->prepare("UPDATE wyniki SET miejsce = ? WHERE id = ?");
    if (!$upd) return;

    foreach ($z_wynikiem as $d) {
        $upd->bind_param("ii", $miejsce, $d['id']);
        $upd->execute();
        $miejsce++;
    }
    foreach ($bez_wyniku as $d) {
        $upd->bind_param("ii", $miejsce, $d['id']);
        $upd->execute();
        $miejsce++;
    }
    $upd->close();
}

// Pomocnicza: znajdź drużynę w globalnym spisie po nazwie, a jeśli nie istnieje — utwórz ją (szybkie dopisanie)
function znajdz_lub_utworz_druzyne(mysqli $conn, string $nazwa): ?int {
    if ($nazwa === '') return null;
    $stmt = $conn->prepare("SELECT id FROM druzyny WHERE nazwa = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param("s", $nazwa);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $id = (int)$res->fetch_assoc()['id'];
        $stmt->close();
        return $id;
    }
    $stmt->close();

    $ins = $conn->prepare("INSERT INTO druzyny (nazwa) VALUES (?)");
    if (!$ins) return null;
    $ins->bind_param("s", $nazwa);
    $ins->execute();
    $nowe_id = $conn->insert_id;
    $ins->close();
    return $nowe_id > 0 ? $nowe_id : null;
}

// Dodaj wynik drużyny w wyścigu (miejsce obliczane automatycznie)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_druzyna') {
    $id_wyscigu = (int)(isset($_POST['id_wyscigu']) ? $_POST['id_wyscigu'] : 0);
    $nazwa = trim(isset($_POST['nazwa_druzyny']) ? $_POST['nazwa_druzyny'] : '');
    $wynik = trim(isset($_POST['wynik_druzyny']) ? $_POST['wynik_druzyny'] : '');
    $id_toru = (isset($_POST['id_toru']) && $_POST['id_toru'] !== '') ? (int)$_POST['id_toru'] : null;

    if ($id_wyscigu <= 0 || $nazwa === '') {
        $alert = "Podaj nazwę drużyny i wybierz wyścig.";
    } elseif ($wynik !== '' && !preg_match('/^\d{1,2}:\d{2},\d{3}$/', $wynik)) {
        $alert = "Wynik musi być w formacie MM:SS,mmm (np. 1:23,456).";
    } else {
        $id_druzyny = znajdz_lub_utworz_druzyne($conn, $nazwa);
        $wynik_param = ($wynik === '') ? null : $wynik;
        $miejsce_tmp = 0;
        $tor_legacy  = 0; // kolumna historyczna, nowe wpisy korzystają z id_toru

        $stmt = $conn->prepare("INSERT INTO wyniki (nazwa, id_druzyny, wynik, miejsce, tor, id_toru, id_wyscigu) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sisiiii", $nazwa, $id_druzyny, $wynik_param, $miejsce_tmp, $tor_legacy, $id_toru, $id_wyscigu);
            if ($stmt->execute()) {
                przelicz_miejsca($conn, $id_wyscigu);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd zapisu wyniku: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Edytuj wynik drużyny (miejsce obliczane automatycznie)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_druzyna') {
    $id = (int)(isset($_POST['id_druzyny_edit']) ? $_POST['id_druzyny_edit'] : 0);
    $nazwa = trim(isset($_POST['nazwa_druzyny_edit']) ? $_POST['nazwa_druzyny_edit'] : '');
    $wynik = trim(isset($_POST['wynik_druzyny_edit']) ? $_POST['wynik_druzyny_edit'] : '');
    $id_toru = (isset($_POST['id_toru_edit']) && $_POST['id_toru_edit'] !== '') ? (int)$_POST['id_toru_edit'] : null;

    if ($id <= 0 || $nazwa === '') {
        $alert = "Niepoprawne dane przy edycji wyniku.";
    } elseif ($wynik !== '' && !preg_match('/^\d{1,2}:\d{2},\d{3}$/', $wynik)) {
        $alert = "Wynik musi być w formacie MM:SS,mmm (np. 1:23,456).";
    } else {
        // Pobierz id_wyscigu tego wpisu
        $res_id = $conn->query("SELECT id_wyscigu FROM wyniki WHERE id = " . $id . " LIMIT 1");
        $id_wyscigu_tej = 0;
        if ($res_id && $r_id = $res_id->fetch_assoc()) {
            $id_wyscigu_tej = (int)$r_id['id_wyscigu'];
            $res_id->free();
        }

        $id_druzyny = znajdz_lub_utworz_druzyne($conn, $nazwa);
        $wynik_param = ($wynik === '') ? null : $wynik;

        $stmt = $conn->prepare("UPDATE wyniki SET nazwa = ?, id_druzyny = ?, wynik = ?, id_toru = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sisii", $nazwa, $id_druzyny, $wynik_param, $id_toru, $id);
            if ($stmt->execute()) {
                if ($id_wyscigu_tej > 0) przelicz_miejsca($conn, $id_wyscigu_tej);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd aktualizacji wyniku: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Usuń wynik drużyny
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_druzyna') {
    $id = (int)(isset($_POST['id_druzyny_del']) ? $_POST['id_druzyny_del'] : 0);
    if ($id <= 0) {
        $alert = "Niepoprawne id przy usuwaniu wyniku.";
    } else {
        $stmt = $conn->prepare("DELETE FROM wyniki WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd usuwania wyniku: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// ---------------------- Pobranie danych ----------------------------

function fetch_all(mysqli $conn, string $sql): array {
    $out = [];
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $res->free();
    }
    return $out;
}

$zawody = [];
$res = $conn->query("SELECT id, nazwa, status FROM zawody ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $zawody[] = $r;
    $res->free();
}

// Słowniki — kategorie, dystanse, fazy, tory, globalna lista drużyn
$kategorie        = fetch_all($conn, "SELECT id, nazwa FROM kategorie ORDER BY nazwa ASC");
$dystanse         = fetch_all($conn, "SELECT id, nazwa, metry FROM dystanse ORDER BY metry ASC, nazwa ASC");
$fazy             = fetch_all($conn, "SELECT id, nazwa, kolejnosc FROM fazy ORDER BY kolejnosc ASC, nazwa ASC");
$tory             = fetch_all($conn, "SELECT id, nazwa FROM tory ORDER BY nazwa ASC");
$druzyny_globalne = fetch_all($conn, "SELECT id, nazwa FROM druzyny ORDER BY nazwa ASC");

// Wyniki (dawniej tabela `druzyny`) wraz z rozwiązaną nazwą z globalnego spisu i nazwą toru
$druzyny_by_wyscig = [];
$res3 = $conn->query("
    SELECT wn.id, wn.nazwa, wn.id_druzyny, dg.nazwa AS nazwa_globalna,
           wn.wynik, wn.tor AS tor_legacy, wn.id_toru, t.nazwa AS tor_nazwa,
           wn.miejsce, wn.id_wyscigu
    FROM wyniki wn
    LEFT JOIN druzyny dg ON wn.id_druzyny = dg.id
    LEFT JOIN tory t ON wn.id_toru = t.id
    ORDER BY wn.id_wyscigu ASC, wn.miejsce ASC
");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $idw = (int)$row['id_wyscigu'];
        if (!isset($druzyny_by_wyscig[$idw])) $druzyny_by_wyscig[$idw] = [];
        $druzyny_by_wyscig[$idw][] = $row;
    }
    $res3->free();
}

// pobierz wartość cookie używaną do filtrowania formularza (management)
$selected_zawody_formularz = isset($_COOKIE['zawody_formularz']) ? (int)$_COOKIE['zawody_formularz'] : 0;

// Nazwa zaznaczonych zawodów — używana w modalu Klasyfikacja generalna
$selected_zawody_nazwa = '';
if ($selected_zawody_formularz > 0) {
    foreach ($zawody as $z) {
        if ((int)$z['id'] === $selected_zawody_formularz) {
            $selected_zawody_nazwa = $z['nazwa'];
            break;
        }
    }
}

// Pobierz wyścigi — FILTRUJEMY jeśli istnieje cookie $selected_zawody_formularz
$wyscigi = [];
$where = '';
if (!empty($selected_zawody_formularz) && (int)$selected_zawody_formularz > 0) {
    $where = ' WHERE w.id_zawodow = ' . (int)$selected_zawody_formularz;
}
$res2 = $conn->query("
    SELECT w.id AS id, w.nazwa AS nazwa_w, w.id_zawodow, z.nazwa AS nazwa_z,
           w.id_kategorii, k.nazwa AS nazwa_kategorii,
           w.id_dystansu, d.nazwa AS nazwa_dystansu,
           w.id_fazy, f.nazwa AS nazwa_fazy,
           f.kolejnosc AS faza_kolejnosc, w.opis
    FROM wyscigi w
    LEFT JOIN zawody z ON w.id_zawodow = z.id
    LEFT JOIN kategorie k ON w.id_kategorii = k.id
    LEFT JOIN dystanse d ON w.id_dystansu = d.id
    LEFT JOIN fazy f ON w.id_fazy = f.id
    $where
    ORDER BY w.id DESC
");
if ($res2) {
    while ($r = $res2->fetch_assoc()) $wyscigi[] = $r;
    $res2->free();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Zarządzanie zawodami i wyścigami</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        .zawody-item { cursor: pointer; }
        .list-group-flush .list-group-item.active { background-color: #0d6efd; color: #fff; }
        .teams-row table { margin-bottom: 0; }
        .team-editable { cursor: pointer; }
        .archive-badge { font-size: 0.75rem; }
        .archive-item { opacity: 0.7; }
        .wyscig-meta-badge { font-size: 0.7rem; }
        /* Tom Select nad modalem Bootstrap (modal ma z-index 1055) */
        .ts-dropdown { z-index: 2000 !important; }
        .ts-wrapper.single .ts-control { cursor: pointer; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Panel zarządzania</a>
        <div class="ms-auto">
            <a href="index.php" class="btn btn-outline-light btn-sm me-2" target="_blank">Strona prezentacyjna</a>
            <a href="operator-page.php" class="btn btn-outline-light btn-sm me-2" target="_blank">Operator</a>
            <a href="stream-page.php" class="btn btn-danger btn-sm me-2" target="_blank">▶ Stream</a>
            <a href="info-belt.php" class="btn btn-warning btn-sm me-2" target="_blank" style="color:#111">ℹ Belka</a>
            <form method="post" action="logout.php" class="d-inline">
                <button class="btn btn-outline-light btn-sm" type="submit">Wyloguj</button>
            </form>
        </div>
    </div>
</nav>

<div class="container">
    <?php if ($alert): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($alert); ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" id="mainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-zawody-btn" data-bs-toggle="tab" data-bs-target="#tab-zawody" type="button" role="tab" aria-controls="tab-zawody" aria-selected="true">Zawody i wyścigi</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-slowniki-btn" data-bs-toggle="tab" data-bs-target="#tab-slowniki" type="button" role="tab" aria-controls="tab-slowniki" aria-selected="false">Ustawienia</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-druzyny-btn" data-bs-toggle="tab" data-bs-target="#tab-druzyny" type="button" role="tab" aria-controls="tab-druzyny" aria-selected="false">Drużyny (globalnie)</button>
        </li>
    </ul>

    <div class="tab-content" id="mainTabsContent">
        <div class="tab-pane fade show active" id="tab-zawody" role="tabpanel" aria-labelledby="tab-zawody-btn">

            <div class="row gy-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header"><strong>Dodaj nowe zawody</strong></div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="action" value="add_zawody">
                                <div class="mb-3">
                                    <label class="form-label">Nazwa zawodów</label>
                                    <input type="text" name="nazwa_zawodow" class="form-control" required>
                                </div>
                                <button class="btn btn-primary">Dodaj zawody</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-3">
                        <div class="card-header"><strong>Lista zawodów (kliknij, aby zapamiętać wybór)</strong></div>
                        <div class="card-body p-0">
                            <?php if (empty($zawody)): ?>
                                <div class="p-3">Brak zawodów.</div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($zawody as $z):
                                        $isActive = ($selected_zawody_formularz === (int)$z['id']);
                                        $isArchived = ($z['status'] === 'zarchiwizowane');
                                        $archiveClass = $isArchived ? ' archive-item' : '';
                                        $archiveButtonLabel = $isArchived ? '🔓 Aktywuj' : '🔒 Archiwizuj';
                                        ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center zawody-item<?php echo $isActive ? ' active' : ''; ?><?php echo $archiveClass; ?>"
                                             data-id="<?php echo (int)$z['id']; ?>">
                                            <div class="zawody-nazwa d-flex align-items-center">
                                                <?php echo htmlspecialchars($z['nazwa']); ?>
                                                <?php if ($isArchived): ?>
                                                    <span class="badge bg-secondary archive-badge ms-2">ARCHIWUM</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary edit-zawody-btn"
                                                        data-id="<?php echo (int)$z['id']; ?>"
                                                        data-nazwa="<?php echo htmlspecialchars($z['nazwa'], ENT_QUOTES); ?>">Edytuj</button>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_archive_zawody">
                                                    <input type="hidden" name="id_zawody" value="<?php echo (int)$z['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-warning" type="submit"><?php echo $archiveButtonLabel; ?></button>
                                                </form>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć zawody?');">
                                                    <input type="hidden" name="action" value="delete_zawody">
                                                    <input type="hidden" name="id_zawody_del" value="<?php echo (int)$z['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Usuń</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center gap-2">
                            <strong>Dodaj wyścig</strong>
                            <?php if ($selected_zawody_formularz > 0): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#klasyfikacjaModal">
                                    📊 Klasyfikacja generalna
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                        title="Zaznacz zawody z listy po lewej, aby wygenerować klasyfikację">
                                    📊 Klasyfikacja generalna
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="action" value="add_wyscig">
                                <div class="mb-3">
                                    <label class="form-label">Wybierz zawody</label>
                                    <select name="id_zawodow" class="form-select" id="selectIdZawodow" required>
                                        <option value="">-- wybierz --</option>
                                        <?php foreach ($zawody as $z): ?>
                                            <?php if ($z['status'] === 'aktywne'): ?>
                                                <option value="<?php echo (int)$z['id']; ?>" <?php echo ($selected_zawody_formularz === (int)$z['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($z['nazwa']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Kliknij zawody po lewej, aby ustawić domyślny wybór formularza. (Pokazane tylko aktywne zawody)</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nazwa wyścigu</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Wyścig</span>
                                        <input type="text" name="nazwa_wyscigu" id="addWyscigNazwa" class="form-control" value="<?php echo count($wyscigi) + 1; ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Opis wyścigu <span class="text-muted">(opcjonalnie)</span></label>
                                    <textarea name="opis_wyscigu" class="form-control" rows="2" placeholder="Krótka notatka do wyścigu…"></textarea>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Kategoria</label>
                                        <select name="id_kategorii" id="addWyscigKategoria" class="form-select">
                                            <option value="">— brak —</option>
                                            <?php foreach ($kategorie as $k): ?>
                                                <option value="<?php echo (int)$k['id']; ?>"><?php echo htmlspecialchars($k['nazwa']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Dystans</label>
                                        <select name="id_dystansu" id="addWyscigDystans" class="form-select">
                                            <option value="">— brak —</option>
                                            <?php foreach ($dystanse as $d): ?>
                                                <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['nazwa']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Faza</label>
                                        <select name="id_fazy" id="addWyscigFaza" class="form-select">
                                            <option value="">— brak —</option>
                                            <?php foreach ($fazy as $f): ?>
                                                <option value="<?php echo (int)$f['id']; ?>"><?php echo htmlspecialchars($f['nazwa']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-text">Kategorie, dystanse i fazy uzupełnisz w zakładce „Ustawienia”.</div>
                                </div>
                                <button class="btn btn-success">Dodaj wyścig</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-3">
                        <div class="card-header"><strong>Lista wyścigów</strong></div>
                        <div class="card-body p-0" style="max-height:520px; overflow-y:auto;">
                            <?php if (empty($wyscigi)): ?>
                                <div class="p-3">Brak wyścigów.</div>
                            <?php else: ?>
                                <table class="table mb-0">
                                    <thead>
                                    <tr><th style="width:45px">#</th><th>Wyścig</th><th>Zawody</th><th>Kategoria / dystans / faza</th><th>Akcje</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($wyscigi as $w): ?>
                                        <tr class="wyscig-row" data-id="<?php echo (int)$w['id']; ?>" data-zawody="<?php echo (int)$w['id_zawodow']; ?>">
                                            <td class="text-muted small"><?php echo (int)$w['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($w['nazwa_w']); ?>
                                                <?php if (!empty($w['opis'])): ?><div class="text-muted" style="font-size:.75rem;margin-top:2px;"><?php echo htmlspecialchars($w['opis']); ?></div><?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(isset($w['nazwa_z']) ? $w['nazwa_z'] : '—'); ?></td>
                                            <td>
                                                <?php if (!empty($w['nazwa_kategorii'])): ?><span class="badge bg-primary-subtle text-primary-emphasis wyscig-meta-badge"><?php echo htmlspecialchars($w['nazwa_kategorii']); ?></span><?php endif; ?>
                                                <?php if (!empty($w['nazwa_dystansu'])): ?><span class="badge bg-info-subtle text-info-emphasis wyscig-meta-badge"><?php echo htmlspecialchars($w['nazwa_dystansu']); ?></span><?php endif; ?>
                                                <?php if (!empty($w['nazwa_fazy'])): ?><span class="badge bg-secondary-subtle text-secondary-emphasis wyscig-meta-badge"><?php echo htmlspecialchars($w['nazwa_fazy']); ?></span><?php endif; ?>
                                                <?php if (empty($w['nazwa_kategorii']) && empty($w['nazwa_dystansu']) && empty($w['nazwa_fazy'])): ?><span class="text-muted small">—</span><?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addDruzynaModal"
                                                            data-id="<?php echo (int)$w['id']; ?>"
                                                            data-wyscignazwa="<?php echo htmlspecialchars($w['nazwa_w'], ENT_QUOTES); ?>"
                                                            data-id-zawodow="<?php echo (int)$w['id_zawodow']; ?>"
                                                            data-id-kategorii="<?php echo $w['id_kategorii'] !== null ? (int)$w['id_kategorii'] : ''; ?>"
                                                            data-id-dystansu="<?php echo $w['id_dystansu']  !== null ? (int)$w['id_dystansu']  : ''; ?>"
                                                            data-faza-kolejnosc="<?php echo $w['faza_kolejnosc'] !== null ? (int)$w['faza_kolejnosc'] : 0; ?>"
                                                            data-poprzednie-fazy="<?php
                                                            $prev = array_values(array_filter($fazy, function($f) use ($w) {
                                                                return $f['kolejnosc'] !== null
                                                                        && $w['faza_kolejnosc'] !== null
                                                                        && (int)$f['kolejnosc'] < (int)$w['faza_kolejnosc'];
                                                            }));
                                                            echo htmlspecialchars(json_encode(array_map(function($f){
                                                                return ['id' => (int)$f['id'], 'nazwa' => $f['nazwa']];
                                                            }, $prev), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                                            ?>">Dodaj drużynę</button>
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editWyscigModal"
                                                            data-id="<?php echo (int)$w['id']; ?>" data-nazwa="<?php echo htmlspecialchars($w['nazwa_w'], ENT_QUOTES); ?>" data-idz="<?php echo (int)$w['id_zawodow']; ?>"
                                                            data-idkategorii="<?php echo $w['id_kategorii'] !== null ? (int)$w['id_kategorii'] : ''; ?>"
                                                            data-iddystansu="<?php echo $w['id_dystansu'] !== null ? (int)$w['id_dystansu'] : ''; ?>"
                                                            data-idfazy="<?php echo $w['id_fazy'] !== null ? (int)$w['id_fazy'] : ''; ?>"
                                                            data-opis="<?php echo htmlspecialchars($w['opis'] ?? '', ENT_QUOTES); ?>">Edytuj</button>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć wyścig?');">
                                                        <input type="hidden" name="action" value="delete_wyscig">
                                                        <input type="hidden" name="id_wyscigu_del" value="<?php echo (int)$w['id']; ?>">
                                                        <button class="btn btn-outline-danger" type="submit">Usuń</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr class="teams-row" data-wyscig="<?php echo (int)$w['id']; ?>">
                                            <td colspan="5" class="small text-muted">
                                                <?php
                                                $teams = isset($druzyny_by_wyscig[(int)$w['id']]) ? $druzyny_by_wyscig[(int)$w['id']] : [];
                                                if (empty($teams)) {
                                                    echo '<em>Brak drużyn.</em>';
                                                } else {
                                                    echo '<table class="table table-sm mb-0">';
                                                    echo '<thead><tr><th style="width:60px">Miejsce</th><th>Nazwa</th><th style="width:80px">Tor</th><th style="width:120px">Wynik</th></tr></thead><tbody>';
                                                    foreach ($teams as $t) {
                                                        $team_id = (int)$t['id'];
                                                        $wynik = $t['wynik'];
                                                        $miejsce = (int)$t['miejsce'];
                                                        $nazwa_wyswietlana = !empty($t['nazwa_globalna']) ? $t['nazwa_globalna'] : $t['nazwa'];
                                                        $nazwa_t = htmlspecialchars($nazwa_wyswietlana);

                                                        $tor_label = '';
                                                        if (!empty($t['tor_nazwa'])) {
                                                            $tor_label = $t['tor_nazwa'];
                                                        } elseif ($t['tor_legacy'] !== null && (int)$t['tor_legacy'] > 0) {
                                                            $tor_label = (string)(int)$t['tor_legacy'];
                                                        }

                                                        echo '<tr class="team-editable" data-team-id="' . $team_id . '" data-team-name="' . $nazwa_t . '" data-team-wynik="' . htmlspecialchars($wynik) . '" data-team-idtoru="' . (int)($t['id_toru'] ?? 0) . '" data-team-miejsce="' . $miejsce . '" data-team-wyscig="' . (int)$t['id_wyscigu'] . '">';
                                                        echo '<td>' . $miejsce . '</td>';
                                                        echo '<td>' . $nazwa_t . '</td>';
                                                        echo '<td>' . ($tor_label !== '' ? htmlspecialchars($tor_label) : '—') . '</td>';
                                                        echo '<td>' . ($wynik !== null && $wynik !== '' ? htmlspecialchars($wynik) : '—') . '</td>';
                                                        echo '</tr>';
                                                    }
                                                    echo '</tbody></table>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /tab-zawody -->

        <div class="tab-pane fade" id="tab-slowniki" role="tabpanel" aria-labelledby="tab-slowniki-btn">
            <div class="row gy-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header"><strong>Kategorie</strong></div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2 mb-3">
                                <input type="hidden" name="action" value="add_slownik">
                                <input type="hidden" name="typ" value="kategoria">
                                <input type="text" name="nazwa" class="form-control form-control-sm" placeholder="np. Senior Open" required>
                                <button class="btn btn-sm btn-primary text-nowrap">Dodaj</button>
                            </form>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($kategorie)): ?>
                                    <li class="list-group-item text-muted small">Brak kategorii.</li>
                                <?php endif; ?>
                                <?php foreach ($kategorie as $k): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($k['nazwa']); ?></span>
                                        <span class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary edit-slownik-btn"
                                                data-typ="kategoria" data-id="<?php echo (int)$k['id']; ?>"
                                                data-nazwa="<?php echo htmlspecialchars($k['nazwa'], ENT_QUOTES); ?>"
                                                data-etykieta="kategorię">Edytuj</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć kategorię?');">
                                            <input type="hidden" name="action" value="delete_slownik">
                                            <input type="hidden" name="typ" value="kategoria">
                                            <input type="hidden" name="id" value="<?php echo (int)$k['id']; ?>">
                                            <button class="btn btn-outline-danger" type="submit">Usuń</button>
                                        </form>
                                    </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-3">
                        <div class="card-header"><strong>Dystanse</strong></div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2 mb-3">
                                <input type="hidden" name="action" value="add_slownik">
                                <input type="hidden" name="typ" value="dystans">
                                <input type="text" name="nazwa" class="form-control form-control-sm" placeholder="np. 200m" required>
                                <input type="number" name="metry" class="form-control form-control-sm" placeholder="metry" style="max-width:90px" min="1">
                                <button class="btn btn-sm btn-primary text-nowrap">Dodaj</button>
                            </form>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($dystanse)): ?>
                                    <li class="list-group-item text-muted small">Brak dystansów.</li>
                                <?php endif; ?>
                                <?php foreach ($dystanse as $d): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($d['nazwa']); ?><?php if ($d['metry'] !== null): ?><span class="text-muted small"> — <?php echo (int)$d['metry']; ?> m</span><?php endif; ?></span>
                                        <span class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary edit-slownik-btn"
                                                data-typ="dystans" data-id="<?php echo (int)$d['id']; ?>"
                                                data-nazwa="<?php echo htmlspecialchars($d['nazwa'], ENT_QUOTES); ?>"
                                                data-metry="<?php echo $d['metry'] !== null ? (int)$d['metry'] : ''; ?>"
                                                data-etykieta="dystans">Edytuj</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć dystans?');">
                                            <input type="hidden" name="action" value="delete_slownik">
                                            <input type="hidden" name="typ" value="dystans">
                                            <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                                            <button class="btn btn-outline-danger" type="submit">Usuń</button>
                                        </form>
                                    </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header"><strong>Fazy</strong></div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2 mb-3">
                                <input type="hidden" name="action" value="add_slownik">
                                <input type="hidden" name="typ" value="faza">
                                <input type="text" name="nazwa" class="form-control form-control-sm" placeholder="np. Eliminacje" required>
                                <input type="number" name="kolejnosc" class="form-control form-control-sm" placeholder="kolejność" style="max-width:110px">
                                <button class="btn btn-sm btn-primary text-nowrap">Dodaj</button>
                            </form>
                            <div class="form-text mb-2 mt-n2">Pole „kolejność” decyduje o kolejności wyświetlania faz (np. Eliminacje=1, Repasaż=2, Ćwierćfinał=3, Półfinał=4, Finał=5).</div>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($fazy)): ?>
                                    <li class="list-group-item text-muted small">Brak faz.</li>
                                <?php endif; ?>
                                <?php foreach ($fazy as $f): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($f['nazwa']); ?><?php if ($f['kolejnosc'] !== null): ?><span class="text-muted small"> (kolejność: <?php echo (int)$f['kolejnosc']; ?>)</span><?php endif; ?></span>
                                        <span class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary edit-slownik-btn"
                                                data-typ="faza" data-id="<?php echo (int)$f['id']; ?>"
                                                data-nazwa="<?php echo htmlspecialchars($f['nazwa'], ENT_QUOTES); ?>"
                                                data-kolejnosc="<?php echo $f['kolejnosc'] !== null ? (int)$f['kolejnosc'] : ''; ?>"
                                                data-etykieta="fazę">Edytuj</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć fazę?');">
                                            <input type="hidden" name="action" value="delete_slownik">
                                            <input type="hidden" name="typ" value="faza">
                                            <input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>">
                                            <button class="btn btn-outline-danger" type="submit">Usuń</button>
                                        </form>
                                    </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-3">
                        <div class="card-header"><strong>Tory</strong></div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2 mb-3">
                                <input type="hidden" name="action" value="add_slownik">
                                <input type="hidden" name="typ" value="tor">
                                <input type="text" name="nazwa" class="form-control form-control-sm" placeholder="np. Tor 1" required>
                                <button class="btn btn-sm btn-primary text-nowrap">Dodaj</button>
                            </form>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($tory)): ?>
                                    <li class="list-group-item text-muted small">Brak torów.</li>
                                <?php endif; ?>
                                <?php foreach ($tory as $t): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($t['nazwa']); ?></span>
                                        <span class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary edit-slownik-btn"
                                                data-typ="tor" data-id="<?php echo (int)$t['id']; ?>"
                                                data-nazwa="<?php echo htmlspecialchars($t['nazwa'], ENT_QUOTES); ?>"
                                                data-etykieta="tor">Edytuj</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć tor?');">
                                            <input type="hidden" name="action" value="delete_slownik">
                                            <input type="hidden" name="typ" value="tor">
                                            <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                            <button class="btn btn-outline-danger" type="submit">Usuń</button>
                                        </form>
                                    </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /tab-slowniki -->

        <div class="tab-pane fade" id="tab-druzyny" role="tabpanel" aria-labelledby="tab-druzyny-btn">
            <div class="row gy-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header"><strong>Dodaj drużynę do globalnego spisu</strong></div>
                        <div class="card-body">
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="action" value="add_slownik">
                                <input type="hidden" name="typ" value="druzyna">
                                <input type="text" name="nazwa" class="form-control" placeholder="Nazwa drużyny" required>
                                <button class="btn btn-primary text-nowrap">Dodaj</button>
                            </form>
                            <div class="form-text mt-2">Drużyny dodane tutaj będą dostępne do wyboru (z podpowiadaniem) przy wpisywaniu wyników w zakładce „Zawody i wyścigi”. Nowe nazwy wpisane bezpośrednio przy wyniku też trafią automatycznie do tego spisu.</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header"><strong>Globalny spis drużyn (<?php echo count($druzyny_globalne); ?>)</strong></div>
                        <div class="card-body p-0" style="max-height:520px; overflow-y:auto;">
                            <ul class="list-group list-group-flush">
                                <?php if (empty($druzyny_globalne)): ?>
                                    <li class="list-group-item text-muted small">Brak drużyn w spisie.</li>
                                <?php endif; ?>
                                <?php foreach ($druzyny_globalne as $dg): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo htmlspecialchars($dg['nazwa']); ?></span>
                                        <span class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary edit-slownik-btn"
                                                data-typ="druzyna" data-id="<?php echo (int)$dg['id']; ?>"
                                                data-nazwa="<?php echo htmlspecialchars($dg['nazwa'], ENT_QUOTES); ?>"
                                                data-etykieta="drużynę">Edytuj</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć drużynę z globalnego spisu? Wyniki, w których była użyta, zostaną zachowane pod swoją starą nazwą tekstową.');">
                                            <input type="hidden" name="action" value="delete_slownik">
                                            <input type="hidden" name="typ" value="druzyna">
                                            <input type="hidden" name="id" value="<?php echo (int)$dg['id']; ?>">
                                            <button class="btn btn-outline-danger" type="submit">Usuń</button>
                                        </form>
                                    </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /tab-druzyny -->

    </div><!-- /tab-content -->
</div>

<!-- ==================== MODALE ==================== -->


<!-- Modal: Edytuj zawody -->
<div class="modal fade" id="editZawodyModal" tabindex="-1" aria-labelledby="editZawodyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editZawodyModalLabel">Edytuj zawody</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="edit_zawody">
                <input type="hidden" name="id_zawody" id="editZawodyId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nazwa zawodów</label>
                        <input type="text" name="nazwa_zawodow_edit" id="editZawodyNazwa" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edytuj wyścig -->
<div class="modal fade" id="editWyscigModal" tabindex="-1" aria-labelledby="editWyscigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editWyscigModalLabel">Edytuj wyścig</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="edit_wyscig">
                <input type="hidden" name="id_wyscigu_edit" id="editWyscigId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawody</label>
                        <select name="id_zawodow_edit" id="editWyscigZawody" class="form-select" required>
                            <?php foreach ($zawody as $z): ?>
                                <?php if ($z['status'] === 'aktywne'): ?>
                                    <option value="<?php echo (int)$z['id']; ?>"><?php echo htmlspecialchars($z['nazwa']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nazwa wyścigu</label>
                        <input type="text" name="nazwa_wyscigu_edit" id="editWyscigNazwa" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis wyścigu <span class="text-muted">(opcjonalnie)</span></label>
                        <textarea name="opis_wyscigu_edit" id="editWyscigOpis" class="form-control" rows="2" placeholder="Krótka notatka do wyścigu…"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Kategoria</label>
                            <select name="id_kategorii_edit" id="editWyscigKategoria" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($kategorie as $k): ?>
                                    <option value="<?php echo (int)$k['id']; ?>"><?php echo htmlspecialchars($k['nazwa']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dystans</label>
                            <select name="id_dystansu_edit" id="editWyscigDystans" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($dystanse as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['nazwa']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Faza</label>
                            <select name="id_fazy_edit" id="editWyscigFaza" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($fazy as $f): ?>
                                    <option value="<?php echo (int)$f['id']; ?>"><?php echo htmlspecialchars($f['nazwa']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Dodaj drużynę -->
<div class="modal fade" id="addDruzynaModal" tabindex="-1" aria-labelledby="addDruzynaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDruzynaModalLabel">Dodaj drużynę do: <span id="addDruzynaWyscigName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="add_druzyna">
                <input type="hidden" name="id_wyscigu" id="addDruzynaWyscigId">
                <div class="modal-body">

                    <!-- Panel wyników poprzednich faz (pokazuje się gdy faza.kolejnosc > 1) -->
                    <div id="prevFazyPanel" class="mb-3 d-none">
                        <div class="border border-info-subtle rounded overflow-hidden">
                            <div class="bg-info-subtle px-3 pt-2 pb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold text-info-emphasis" style="font-size:.875rem;">📊 Wyniki z poprzednich faz</span>
                                    <span class="text-muted" style="font-size:.78rem;">kliknij wiersz, aby wybrać drużynę</span>
                                </div>
                                <!-- Checkboxy faz renderowane przez JS -->
                                <div id="prevFazyFilter" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div id="prevFazyContent" style="max-height:240px; overflow-y:auto;"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nazwa drużyny</label>
                        <select name="nazwa_druzyny" id="addDruzynaNazwa">
                            <option value=""></option>
                            <?php foreach ($druzyny_globalne as $dg): ?>
                                <option value="<?php echo htmlspecialchars($dg['nazwa'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($dg['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Wyszukaj z listy lub wpisz nową nazwę — zostanie automatycznie dodana do globalnego spisu.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tor</label>
                        <select name="id_toru" id="addDruzynaTor" class="form-select">
                            <option value="">— brak —</option>
                            <?php foreach ($tory as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wynik <span class="text-muted">(MM:SS,mmm, np. 1:23,456)</span></label>
                        <input type="text" name="wynik_druzyny" id="addDruzynaWynik" class="form-control" placeholder="np. 1:23,456">
                        <div id="addWynikFeedback" class="form-text"></div>
                    </div>
                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.9rem;">
                        🏅 Miejsce zostanie przydzielone automatycznie na podstawie czasu.<br>
                        Drużyny bez wyniku trafiają na koniec listy.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Dodaj drużynę</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edytuj drużynę -->
<div class="modal fade" id="editDruzynaModal" tabindex="-1" aria-labelledby="editDruzynaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDruzynaModalLabel">Edytuj drużynę</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="post" id="formEditDruzyna">
                <input type="hidden" name="action" value="edit_druzyna">
                <input type="hidden" name="id_druzyny_edit" id="editDruzynaId">
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="editDruzynaWyscigName"></p>
                    <div class="mb-3">
                        <label class="form-label">Nazwa drużyny</label>
                        <select name="nazwa_druzyny_edit" id="editDruzynaNazwa">
                            <option value=""></option>
                            <?php foreach ($druzyny_globalne as $dg): ?>
                                <option value="<?php echo htmlspecialchars($dg['nazwa'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($dg['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Wyszukaj z listy lub wpisz nową nazwę — zostanie automatycznie dodana do globalnego spisu.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tor</label>
                        <select name="id_toru_edit" id="editDruzynaTor" class="form-select">
                            <option value="">— brak —</option>
                            <?php foreach ($tory as $t): ?>
                                <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wynik <span class="text-muted">(MM:SS,mmm, np. 1:23,456)</span></label>
                        <input type="text" name="wynik_druzyny_edit" id="editDruzynaWynik" class="form-control" placeholder="(opcjonalnie)">
                        <div id="editWynikFeedback" class="form-text"></div>
                    </div>
                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.9rem;">
                        🏅 Miejsce zostanie przeliczone automatycznie po zapisaniu.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="btnDeleteDruzyna">Usuń drużynę</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                </div>
            </form>
            <!-- Ukryty formularz do usuwania -->
            <form method="post" id="formDeleteDruzyna" style="display:none;">
                <input type="hidden" name="action" value="delete_druzyna">
                <input type="hidden" name="id_druzyny_del" id="deleteDruzynaId">
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edytuj pozycję słownika (kategoria/dystans/faza/tor/drużyna globalna) -->
<div class="modal fade" id="editSlownikModal" tabindex="-1" aria-labelledby="editSlownikLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSlownikLabel">Edytuj</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="edit_slownik">
                <input type="hidden" name="typ" id="editSlownikTyp">
                <input type="hidden" name="id" id="editSlownikId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nazwa</label>
                        <input type="text" name="nazwa" id="editSlownikNazwa" class="form-control" required>
                    </div>
                    <div class="mb-3 d-none" id="editSlownikMetryWrap">
                        <label class="form-label">Metry</label>
                        <input type="number" name="metry" id="editSlownikMetry" class="form-control" min="1">
                    </div>
                    <div class="mb-3 d-none" id="editSlownikKolejnoscWrap">
                        <label class="form-label">Kolejność wyświetlania</label>
                        <input type="number" name="kolejnosc" id="editSlownikKolejnosc" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Klasyfikacja generalna -->
<div class="modal fade" id="klasyfikacjaModal" tabindex="-1" aria-labelledby="klasyfikacjaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="klasyfikacjaModalLabel">Klasyfikacja generalna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <!-- Zawody, których dotyczy klasyfikacja -->
                <?php if ($selected_zawody_formularz > 0): ?>
                    <div class="alert alert-primary py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size:0.9rem;">
                        🏆 <span>Zawody: <strong><?php echo htmlspecialchars($selected_zawody_nazwa); ?></strong></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:0.9rem;">
                        Zaznacz zawody w sekcji „Lista zawodów” (po lewej stronie), aby wygenerować klasyfikację.
                    </div>
                <?php endif; ?>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Kategoria</label>
                        <select id="klasKategoria" class="form-select">
                            <option value="">-- wybierz --</option>
                            <?php foreach ($kategorie as $k): ?>
                                <option value="<?php echo (int)$k['id']; ?>"><?php echo htmlspecialchars($k['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Dystans</label>
                        <select id="klasDystans" class="form-select">
                            <option value="">-- wybierz --</option>
                            <?php foreach ($dystanse as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Faza</label>
                        <select id="klasFaza" class="form-select">
                            <option value="">-- wybierz --</option>
                            <?php foreach ($fazy as $f): ?>
                                <option value="<?php echo (int)$f['id']; ?>"><?php echo htmlspecialchars($f['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php if (empty($kategorie) || empty($dystanse) || empty($fazy)): ?>
                    <div class="alert alert-warning py-2 px-3" style="font-size:0.9rem;">
                        Uzupełnij najpierw kategorie, dystanse i fazy w zakładce „Ustawienia”, a następnie przypisz je do wyścigów (przycisk „Edytuj” przy wyścigu) — dopiero wtedy ranking będzie miał z czego liczyć.
                    </div>
                <?php endif; ?>
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-primary" id="btnGenerujRanking"
                            <?php if ($selected_zawody_formularz <= 0): ?>disabled<?php endif; ?>>
                        Generuj ranking
                    </button>
                </div>
                <div id="klasyfikacjaWynik"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Powrót</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== SKRYPTY ==================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length));
        }
        return null;
    }

    (function () {

        // --- Pamiętaj aktywną zakładkę po przeładowaniu strony ---
        // Po kliknięciu "Dodaj" strona się przeładowuje — sessionStorage przywraca zakładkę automatycznie.
        document.querySelectorAll('#mainTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function () {
                sessionStorage.setItem('activeTab', btn.getAttribute('data-bs-target'));
            });
        });
        var savedTab = sessionStorage.getItem('activeTab');
        if (savedTab) {
            var savedBtn = document.querySelector('#mainTabs button[data-bs-target="' + savedTab + '"]');
            if (savedBtn) bootstrap.Tab.getOrCreateInstance(savedBtn).show();
        }

        // --- Pamiętaj i przywracaj pozycję scrolla po każdym przeładowaniu przez POST ---
        document.querySelectorAll('form[method="post"]').forEach(function (form) {
            form.addEventListener('submit', function () {
                sessionStorage.setItem('scrollY', String(window.scrollY));
            });
        });
        var savedScrollY = sessionStorage.getItem('scrollY');
        if (savedScrollY !== null) {
            window.scrollTo(0, parseInt(savedScrollY, 10));
            sessionStorage.removeItem('scrollY');
        }

        // --- Tom Select: lista rozwijana z wyszukiwaniem dla pola Drużyna ---
        var tomSelectBaseOptions = {
            create: true,          // pozwala wpisać nową drużynę spoza listy
            createOnBlur: false,
            maxItems: 1,
            placeholder: 'Wyszukaj lub wpisz nową drużynę…',
            createFilter: /\S+/,   // nie twórz pustych opcji
            render: {
                option_create: function (data, escape) {
                    return '<div class="create">Dodaj: <strong>' + escape(data.input) + '</strong></div>';
                },
                no_results: function (data, escape) {
                    return '<div class="no-results">Brak wyników dla „' + escape(data.input) + '"</div>';
                }
            }
        };

        var tomAddDruzyna  = new TomSelect('#addDruzynaNazwa',  Object.assign({}, tomSelectBaseOptions));
        var tomEditDruzyna = new TomSelect('#editDruzynaNazwa', Object.assign({}, tomSelectBaseOptions));

        // --- Tom Select: zwykłe listy wyboru (bez tworzenia nowych opcji) ---
        var tsPickOptions = {
            create: false,
            allowEmptyOption: true,
            maxItems: 1,
            sortField: { field: '$order', direction: 'asc' }, // zachowaj kolejność z DOM
            render: {
                no_results: function (data, escape) {
                    return '<div class="no-results">Brak wyników dla „' + escape(data.input) + '"</div>';
                }
            }
        };

        // ── Pomocnicze funkcje do szybkiego dodawania z poziomu listy ────────────

        // Tworzy funkcję create dla Tom Select → AJAX do tabeli słownikowej
        function makeTsQuickAdd(typ, labelDop) {
            return function (input, callback) {
                var fd = new FormData();
                fd.append('action', 'add_slownik');
                fd.append('typ',    typ);
                fd.append('nazwa',  input);
                fd.append('ajax',   '1');
                fetch('management.php', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) callback({ value: String(data.id), text: data.nazwa });
                        else { callback(); alert('Nie udało się dodać ' + labelDop + ': ' + (data.error || '')); }
                    })
                    .catch(function () { callback(); });
            };
        }

        // Tworzy funkcję create dla Tom Select → AJAX do tabeli zawody
        function makeTsQuickAddZawody() {
            return function (input, callback) {
                var fd = new FormData();
                fd.append('action',        'add_zawody');
                fd.append('nazwa_zawodow', input);
                fd.append('ajax',          '1');
                fetch('management.php', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) callback({ value: String(data.id), text: data.nazwa });
                        else { callback(); alert('Nie udało się dodać zawodów: ' + (data.error || '')); }
                    })
                    .catch(function () { callback(); });
            };
        }

        // Buduje Tom Select z wyszukiwaniem + szybkim dodawaniem
        function makeTsWithCreate(selector, createFn, createLabel) {
            return new TomSelect(selector, Object.assign({}, tsPickOptions, {
                create: createFn,
                render: {
                    option_create: function (d, e) {
                        return '<div class="create">' + createLabel + ': <strong>' + e(d.input) + '</strong></div>';
                    },
                    no_results: function (d, e) {
                        return '<div class="no-results">Brak wyników dla „' + e(d.input) + '”</div>';
                    }
                }
            }));
        }

        // ── Inicjalizacja wszystkich list rozwijanych ────────────────────────────

        // Formularz Dodaj wyścig
        var tsSelectIdZawodow    = makeTsWithCreate('#selectIdZawodow',    makeTsQuickAddZawody(),                  'Dodaj zawody');
        var tsAddWyscigKategoria = makeTsWithCreate('#addWyscigKategoria', makeTsQuickAdd('kategoria','kategorii'), 'Dodaj kategorię');
        var tsAddWyscigDystans   = makeTsWithCreate('#addWyscigDystans',   makeTsQuickAdd('dystans',  'dystansu'),  'Dodaj dystans');
        var tsAddWyscigFaza      = makeTsWithCreate('#addWyscigFaza',      makeTsQuickAdd('faza',     'fazy'),      'Dodaj fazę');

        // Modal Edytuj wyścig
        var tsEditWyscigZawody    = makeTsWithCreate('#editWyscigZawody',    makeTsQuickAddZawody(),                  'Dodaj zawody');
        var tsEditWyscigKategoria = makeTsWithCreate('#editWyscigKategoria', makeTsQuickAdd('kategoria','kategorii'), 'Dodaj kategorię');
        var tsEditWyscigDystans   = makeTsWithCreate('#editWyscigDystans',   makeTsQuickAdd('dystans',  'dystansu'),  'Dodaj dystans');
        var tsEditWyscigFaza      = makeTsWithCreate('#editWyscigFaza',      makeTsQuickAdd('faza',     'fazy'),      'Dodaj fazę');

        // Modały Dodaj/Edytuj drużynę — tor
        var tsAddDruzynaTor  = makeTsWithCreate('#addDruzynaTor',  makeTsQuickAdd('tor','toru'), 'Dodaj tor');
        var tsEditDruzynaTor = makeTsWithCreate('#editDruzynaTor', makeTsQuickAdd('tor','toru'), 'Dodaj tor');

        // Modal Klasyfikacja generalna
        makeTsWithCreate('#klasKategoria', makeTsQuickAdd('kategoria','kategorii'), 'Dodaj kategorię');
        makeTsWithCreate('#klasDystans',   makeTsQuickAdd('dystans',  'dystansu'),  'Dodaj dystans');
        makeTsWithCreate('#klasFaza',      makeTsQuickAdd('faza',     'fazy'),      'Dodaj fazę');

        // --- Modal: Edytuj zawody ---
        document.querySelectorAll('.edit-zawody-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                document.getElementById('editZawodyId').value = btn.getAttribute('data-id') || '';
                document.getElementById('editZawodyNazwa').value = btn.getAttribute('data-nazwa') || '';

                var modalEl = document.getElementById('editZawodyModal');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        });

        // --- Modal: Edytuj wyścig ---
        var editWyscigModal = document.getElementById('editWyscigModal');
        if (editWyscigModal) {
            editWyscigModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;
                document.getElementById('editWyscigId').value = button.getAttribute('data-id') || '';
                document.getElementById('editWyscigNazwa').value = button.getAttribute('data-nazwa') || '';
                tsEditWyscigZawody.setValue(button.getAttribute('data-idz') || '', true);
                tsEditWyscigKategoria.setValue(button.getAttribute('data-idkategorii') || '', true);
                tsEditWyscigDystans.setValue(button.getAttribute('data-iddystansu') || '', true);
                tsEditWyscigFaza.setValue(button.getAttribute('data-idfazy') || '', true);
                var opisEl = document.getElementById('editWyscigOpis');
                if (opisEl) opisEl.value = button.getAttribute('data-opis') || '';
            });
        }

        // --- Ładowanie rankingu poprzednich faz (wywoływane po zmianie checkboxa lub otwarciu modalu) ---
        function loadPrevFazyRanking(idZawodow, idKategorii, idDystansu, allFazyIds) {
            var content = document.getElementById('prevFazyContent');
            var sel = document.getElementById('prevFazySelect');
            var selectedIds = (sel && sel.value !== 'all') ? [sel.value] : (allFazyIds || []);

            if (selectedIds.length === 0) {
                content.innerHTML = '<div class="text-center text-muted py-2" style="font-size:.85rem;">Brak faz do wyświetlenia.</div>';
                return;
            }

            content.innerHTML = '<div class="text-center text-muted py-2" style="font-size:.85rem;">Wczytywanie…</div>';

            fetch('ajax_get_ranking_faz.php'
                + '?id_zawodow='   + encodeURIComponent(idZawodow)
                + '&id_kategorii=' + encodeURIComponent(idKategorii)
                + '&id_dystansu='  + encodeURIComponent(idDystansu)
                + '&fazy_ids='     + encodeURIComponent(selectedIds.join(',')))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) {
                        content.innerHTML = '<div class="text-center text-danger py-2" style="font-size:.85rem;">B\u0142\u0105d: ' + escapeHtml(data.error) + '</div>';
                        return;
                    }
                    if (!data.ranking || data.ranking.length === 0) {
                        content.innerHTML = '<div class="text-center text-muted py-2" style="font-size:.85rem;">Brak wyników dla wybranych faz.</div>';
                        return;
                    }
                    var rankingData = data.ranking;
                    var html = '<table class="table table-sm table-hover mb-0" style="font-size:.82rem;">'
                        + '<thead class="table-light"><tr><th style="width:34px">#</th><th>Drużyna</th><th>Czas</th><th class="text-muted">Wyścig</th></tr></thead><tbody>';
                    rankingData.forEach(function (row, idx) {
                        html += '<tr class="prev-rank-row" style="cursor:pointer;" data-idx="' + idx + '">'
                            + '<td>' + row.miejsce + '</td>'
                            + '<td><strong>' + escapeHtml(row.nazwa_druzyny) + '</strong></td>'
                            + '<td>' + escapeHtml(row.wynik) + '</td>'
                            + '<td class="text-muted">' + escapeHtml(row.nazwa_wyscigu) + '</td>'
                            + '</tr>';
                    });
                    html += '</tbody></table>';
                    content.innerHTML = html;

                    content.querySelectorAll('.prev-rank-row').forEach(function (row) {
                        row.addEventListener('click', function () {
                            var idx = parseInt(this.getAttribute('data-idx'), 10);
                            var nazwaTeam = rankingData[idx].nazwa_druzyny;
                            content.querySelectorAll('.prev-rank-row').forEach(function (r) { r.classList.remove('table-primary'); });
                            this.classList.add('table-primary');
                            if (!tomAddDruzyna.options[nazwaTeam]) {
                                tomAddDruzyna.addOption({ value: nazwaTeam, text: nazwaTeam });
                            }
                            tomAddDruzyna.setValue(nazwaTeam, true);
                        });
                    });
                })
                .catch(function () {
                    content.innerHTML = '<div class="text-center text-muted py-2 text-danger" style="font-size:.85rem;">Błąd ładowania wyników.</div>';
                });
        }

        // --- Modal: Dodaj drużynę ---
        var addDruzynaModal = document.getElementById('addDruzynaModal');
        if (addDruzynaModal) {
            addDruzynaModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;
                document.getElementById('addDruzynaWyscigId').value = button.getAttribute('data-id') || '';
                document.getElementById('addDruzynaWyscigName').textContent = button.getAttribute('data-wyscignazwa') || '';
                tomAddDruzyna.clear(true);
                tomAddDruzyna.setTextboxValue('');
                tsAddDruzynaTor.clear(true);
                document.getElementById('addDruzynaWynik').value = '';
                document.getElementById('addWynikFeedback').textContent = '';
                document.getElementById('addWynikFeedback').className = 'form-text';

                // ── Panel wyników poprzednich faz ──────────────────────────
                var fazaKolejnosc = parseInt(button.getAttribute('data-faza-kolejnosc') || '0', 10);
                var idKategorii   = button.getAttribute('data-id-kategorii') || '';
                var idDystansu    = button.getAttribute('data-id-dystansu')  || '';
                var idZawodow     = button.getAttribute('data-id-zawodow')   || '';
                var panel         = document.getElementById('prevFazyPanel');
                var filterEl      = document.getElementById('prevFazyFilter');
                var content       = document.getElementById('prevFazyContent');

                if (fazaKolejnosc > 1 && idKategorii && idDystansu && idZawodow) {
                    panel.classList.remove('d-none');

                    // Renderuj checkboxy dla dostępnych poprzednich faz
                    var poprzednieFazy = [];
                    try { poprzednieFazy = JSON.parse(button.getAttribute('data-poprzednie-fazy') || '[]'); } catch (e) {}

                    var allFazyIds = poprzednieFazy.map(function (f) { return String(f.id); });

                    if (poprzednieFazy.length === 0) {
                        filterEl.innerHTML = '<span class="text-muted" style="font-size:.8rem;">Brak zdefiniowanych poprzednich faz.</span>';
                    } else {
                        var fHtml = '<select id="prevFazySelect" class="form-select form-select-sm" style="width:auto; min-width:170px;">'
                            + '<option value="all">Wszystkie poprzednie</option>';
                        poprzednieFazy.forEach(function (f) {
                            fHtml += '<option value="' + f.id + '">' + escapeHtml(f.nazwa) + '</option>';
                        });
                        fHtml += '</select>';
                        filterEl.innerHTML = fHtml;

                        document.getElementById('prevFazySelect').addEventListener('change', function () {
                            loadPrevFazyRanking(idZawodow, idKategorii, idDystansu, allFazyIds);
                        });
                    }

                    content.innerHTML = '';
                    loadPrevFazyRanking(idZawodow, idKategorii, idDystansu, allFazyIds);

                } else {
                    panel.classList.add('d-none');
                    filterEl.innerHTML = '';
                    content.innerHTML = '';
                }
            });
        }

        // Walidacja formatu wyniku na żywo
        var wynikRegex = /^\d{1,2}:\d{2},\d{3}$/;

        function bindWynikValidation(inputId, feedbackId) {
            var inp = document.getElementById(inputId);
            var fb  = document.getElementById(feedbackId);
            if (!inp || !fb) return;
            inp.addEventListener('input', function () {
                var val = inp.value.trim();
                if (val === '') {
                    fb.textContent = 'Brak wyniku — drużyna trafi na koniec listy.';
                    fb.className = 'form-text text-muted';
                    inp.classList.remove('is-valid', 'is-invalid');
                } else if (wynikRegex.test(val)) {
                    fb.textContent = '✔ Poprawny format.';
                    fb.className = 'form-text text-success';
                    inp.classList.remove('is-invalid');
                    inp.classList.add('is-valid');
                } else {
                    fb.textContent = '✘ Format: MM:SS,mmm (np. 1:23,456)';
                    fb.className = 'form-text text-danger';
                    inp.classList.remove('is-valid');
                    inp.classList.add('is-invalid');
                }
            });
        }

        bindWynikValidation('addDruzynaWynik', 'addWynikFeedback');
        bindWynikValidation('editDruzynaWynik', 'editWynikFeedback');

        // --- Modal: Edytuj drużynę (kliknięcie w wiersz) ---
        document.addEventListener('click', function (e) {
            var row = e.target.closest('.team-editable');
            if (!row) return;
            document.getElementById('editDruzynaId').value = row.getAttribute('data-team-id') || '';

            // Ustaw drużynę w Tom Select — dodaj opcję jeśli jej jeszcze nie ma
            var teamName = row.getAttribute('data-team-name') || '';
            if (teamName && !tomEditDruzyna.options[teamName]) {
                tomEditDruzyna.addOption({ value: teamName, text: teamName });
            }
            tomEditDruzyna.setValue(teamName, true); // true = cicho, bez triggeru

            var idToru = row.getAttribute('data-team-idtoru') || '0';
            tsEditDruzynaTor.setValue(idToru === '0' ? '' : idToru, true);
            var wynikVal = row.getAttribute('data-team-wynik') || '';
            document.getElementById('editDruzynaWynik').value = wynikVal;
            document.getElementById('editWynikFeedback').textContent = '';
            document.getElementById('editWynikFeedback').className = 'form-text';
            document.getElementById('editDruzynaWynik').classList.remove('is-valid', 'is-invalid');
            var wyscigId = row.getAttribute('data-team-wyscig') || '';
            document.getElementById('editDruzynaWyscigName').textContent = wyscigId ? 'ID wyścigu: ' + wyscigId : '';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('editDruzynaModal')).show();
        });

        // --- Przycisk Usuń drużynę w modalu edycji ---
        var btnDel = document.getElementById('btnDeleteDruzyna');
        if (btnDel) {
            btnDel.addEventListener('click', function () {
                if (!confirm('Na pewno usunąć tę drużynę?')) return;
                document.getElementById('deleteDruzynaId').value = document.getElementById('editDruzynaId').value;
                document.getElementById('formDeleteDruzyna').submit();
            });
        }

        // --- Modal: Edytuj pozycję słownika (kategoria/dystans/faza/tor/drużyna) ---
        document.querySelectorAll('.edit-slownik-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var typ = btn.getAttribute('data-typ') || '';
                document.getElementById('editSlownikTyp').value = typ;
                document.getElementById('editSlownikId').value = btn.getAttribute('data-id') || '';
                document.getElementById('editSlownikNazwa').value = btn.getAttribute('data-nazwa') || '';
                document.getElementById('editSlownikLabel').textContent = 'Edytuj ' + (btn.getAttribute('data-etykieta') || '');

                var metryWrap = document.getElementById('editSlownikMetryWrap');
                var kolejnoscWrap = document.getElementById('editSlownikKolejnoscWrap');
                metryWrap.classList.add('d-none');
                kolejnoscWrap.classList.add('d-none');

                if (typ === 'dystans') {
                    metryWrap.classList.remove('d-none');
                    document.getElementById('editSlownikMetry').value = btn.getAttribute('data-metry') || '';
                } else if (typ === 'faza') {
                    kolejnoscWrap.classList.remove('d-none');
                    document.getElementById('editSlownikKolejnosc').value = btn.getAttribute('data-kolejnosc') || '';
                }

                bootstrap.Modal.getOrCreateInstance(document.getElementById('editSlownikModal')).show();
            });
        });

        // --- Klasyfikacja generalna: generowanie rankingu ---
        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = (str === null || str === undefined) ? '' : String(str);
            return div.innerHTML;
        }

        // ID i nazwa aktualnie zaznaczonych zawodów (z sekcji Lista zawodów)
        var selectedZawodyId    = <?php echo (int)$selected_zawody_formularz; ?>;
        var selectedZawodyNazwa = '<?php echo addslashes(htmlspecialchars($selected_zawody_nazwa)); ?>';

        var btnGenerujRanking = document.getElementById('btnGenerujRanking');
        if (btnGenerujRanking) {
            btnGenerujRanking.addEventListener('click', function () {
                var idk = document.getElementById('klasKategoria').value;
                var idd = document.getElementById('klasDystans').value;
                var idf = document.getElementById('klasFaza').value;
                var wynikEl = document.getElementById('klasyfikacjaWynik');

                if (!selectedZawodyId) {
                    wynikEl.innerHTML = '<div class="alert alert-warning py-2 mb-0">Zaznacz zawody w sekcji „Lista zawodów” po lewej stronie.</div>';
                    return;
                }
                if (!idk || !idd || !idf) {
                    wynikEl.innerHTML = '<div class="alert alert-warning py-2 mb-0">Wybierz kategorię, dystans i fazę.</div>';
                    return;
                }

                wynikEl.innerHTML = '<div class="text-center text-muted py-3">Generowanie rankingu…</div>';

                fetch('ajax_get_klasyfikacja.php?id_kategorii=' + encodeURIComponent(idk) +
                    '&id_dystansu='  + encodeURIComponent(idd) +
                    '&id_fazy='      + encodeURIComponent(idf) +
                    '&id_zawodow='   + encodeURIComponent(selectedZawodyId))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.error) {
                            wynikEl.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + escapeHtml(data.error) + '</div>';
                            return;
                        }
                        if (!data.ranking || data.ranking.length === 0) {
                            wynikEl.innerHTML = '<div class="alert alert-secondary py-2 mb-0">Brak wyników spełniających wybrane kryteria.</div>';
                            return;
                        }
                        var html = '<table class="table table-striped table-sm align-middle mb-0"><thead><tr>' +
                            '<th style="width:50px">#</th><th>Drużyna</th><th>Wyścig</th><th class="text-end">Czas</th>' +
                            '</tr></thead><tbody>';
                        data.ranking.forEach(function (row) {
                            html += '<tr><td>' + (row.miejsce <= 3 ? '🏅 ' : '') + row.miejsce + '</td><td>' + escapeHtml(row.nazwa_druzyny) +
                                '</td><td class="text-muted small">' + escapeHtml(row.nazwa_wyscigu) + '</td><td class="text-end">' + escapeHtml(row.wynik) + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        wynikEl.innerHTML = html;
                    })
                    .catch(function (err) {
                        wynikEl.innerHTML = '<div class="alert alert-danger py-2 mb-0">Błąd połączenia: ' + escapeHtml(err.message) + '</div>';
                    });
            });
        }

        // Wyczyść ranking przy każdym otwarciu modala od nowa
        var klasyfikacjaModal = document.getElementById('klasyfikacjaModal');
        if (klasyfikacjaModal) {
            klasyfikacjaModal.addEventListener('show.bs.modal', function () {
                document.getElementById('klasyfikacjaWynik').innerHTML = '';
            });
        }

        // --- Wybór zawodów (filtrowanie listy wyścigów) ---
        var zawodyItems = document.querySelectorAll('.zawody-item');
        var selectZawody = document.getElementById('selectIdZawodow');

        function clearSelection() {
            zawodyItems.forEach(function (it) { it.classList.remove('active'); });
            if (tsSelectIdZawodow) tsSelectIdZawodow.clear(true); else if (selectZawody) selectZawody.value = "";
        }

        function selectById(id, saveCookie) {
            zawodyItems.forEach(function (it) {
                if (it.dataset.id === String(id)) it.classList.add('active');
                else it.classList.remove('active');
            });
            if (tsSelectIdZawodow) tsSelectIdZawodow.setValue(String(id), true); else if (selectZawody) selectZawody.value = id;
            if (saveCookie) {
                setCookie('zawody_formularz', id, 30);
                location.reload();
            }
        }

        zawodyItems.forEach(function (it) {
            it.addEventListener('click', function () {
                // Ignoruj kliknięcia w przyciski wewnątrz wiersza
                if (event.target.closest('button') || event.target.closest('form')) return;
                var id = this.dataset.id;
                if (this.classList.contains('active')) {
                    clearSelection();
                    setCookie('zawody_formularz', '', -1);
                    location.reload();
                } else {
                    selectById(id, true);
                }
            });
        });

        var cookieVal = getCookie('zawody_formularz');
        if (cookieVal && cookieVal !== '') {
            var el = document.querySelector('.zawody-item[data-id="' + cookieVal + '"]');
            if (el) selectById(cookieVal, false);
        }

    })();

    // Na zmianie zawodów (przed reload) — ustaw 1 jako tymczasowy numer
    (function () {
        var inp = document.getElementById('addWyscigNazwa');
        var rawZaw = document.getElementById('selectIdZawodow');
        if (inp && rawZaw) {
            rawZaw.addEventListener('change', function () {
                inp.value = '1';
            });
        }
    })();

</script>
</body>
</html>