<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

$alert = "";

// ---------------------- HANDLERY POST ----------------------------

// Handler dla zmiany ustawień prezentacji (zapis do tabeli ustawienia)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ustaw_prezentacje') {
    $zawody_id = (int)(isset($_POST['zawody_prezentacyjne']) ? $_POST['zawody_prezentacyjne'] : 0);
    if ($zawody_id <= 0) {
        $alert = "Wybierz poprawne zawody.";
    } else {
        $stmt = $conn->prepare("INSERT INTO ustawienia (`klucz`, `wartosc`) VALUES (?, ?) ON DUPLICATE KEY UPDATE wartosc = VALUES(wartosc)");
        if ($stmt) {
            $klucz = 'aktywne_zawody';
            $wartosc = (string)$zawody_id;
            $stmt->bind_param("ss", $klucz, $wartosc);
            if ($stmt->execute()) {
                $alert = "Ustawienia zapisane.";
            } else {
                $alert = "Błąd zapisu ustawień: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Dodaj nowe zawody
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_zawody') {
    $nazwa = trim(isset($_POST['nazwa_zawodow']) ? $_POST['nazwa_zawodow'] : '');
    if ($nazwa === '') {
        $alert = "Podaj nazwę zawodów.";
    } else {
        $stmt = $conn->prepare("INSERT INTO zawody (nazwa) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $nazwa);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd zapisu zawodów: " . $conn->error;
            }
            $stmt->close();
        } else {
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

// Dodaj wyścig
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_wyscig') {
    $nazwa_w = trim(isset($_POST['nazwa_wyscigu']) ? $_POST['nazwa_wyscigu'] : '');
    $id_z = (int)(isset($_POST['id_zawodow']) ? $_POST['id_zawodow'] : 0);

    if ($id_z <= 0) {
        $alert = "Musisz wybrać zawody przed dodaniem wyścigu.";
    } elseif ($nazwa_w === '') {
        $alert = "Podaj nazwę wyścigu.";
    } else {
        $insw = $conn->prepare("INSERT INTO wyscigi (id_zawodow, nazwa) VALUES (?, ?)");
        if ($insw) {
            $insw->bind_param("is", $id_z, $nazwa_w);
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
    if ($id <= 0 || $nazwa === '' || $id_z <= 0) {
        $alert = "Niepoprawne dane przy edycji wyścigu.";
    } else {
        $stmt = $conn->prepare("UPDATE wyscigi SET id_zawodow = ?, nazwa = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("isi", $id_z, $nazwa, $id);
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
    $res = $conn->query("SELECT id, wynik FROM druzyny WHERE id_wyscigu = " . $id_wyscigu . " ORDER BY id ASC");
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
    $upd = $conn->prepare("UPDATE druzyny SET miejsce = ? WHERE id = ?");
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

// Dodaj drużynę (miejsce obliczane automatycznie)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_druzyna') {
    $id_wyscigu = (int)(isset($_POST['id_wyscigu']) ? $_POST['id_wyscigu'] : 0);
    $nazwa = trim(isset($_POST['nazwa_druzyny']) ? $_POST['nazwa_druzyny'] : '');
    $wynik = trim(isset($_POST['wynik_druzyny']) ? $_POST['wynik_druzyny'] : '');
    $tor = isset($_POST['tor_druzyny']) ? intval($_POST['tor_druzyny']) : null;
    $tor = ($tor === 0 || $tor === null) ? null : $tor;

    if ($id_wyscigu <= 0 || $nazwa === '') {
        $alert = "Podaj nazwę drużyny i wybierz wyścig.";
    } elseif ($wynik !== '' && !preg_match('/^\d{1,2}:\d{2},\d{3}$/', $wynik)) {
        $alert = "Wynik musi być w formacie MM:SS,mmm (np. 1:23,456).";
    } else {
        // Wstaw z miejscem = 0, zaraz przelicz
        $miejsce_tmp = 0;
        if ($wynik === '') {
            $stmt = $conn->prepare("INSERT INTO druzyny (nazwa, tor, miejsce, id_wyscigu) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("siii", $nazwa, $tor, $miejsce_tmp, $id_wyscigu);
                if ($stmt->execute()) {
                    przelicz_miejsca($conn, $id_wyscigu);
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $alert = "Błąd zapisu drużyny: " . $conn->error;
                }
                $stmt->close();
            } else {
                $alert = "Błąd przygotowania zapytania: " . $conn->error;
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO druzyny (nazwa, wynik, tor, miejsce, id_wyscigu) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssiii", $nazwa, $wynik, $tor, $miejsce_tmp, $id_wyscigu);
                if ($stmt->execute()) {
                    przelicz_miejsca($conn, $id_wyscigu);
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $alert = "Błąd zapisu drużyny: " . $conn->error;
                }
                $stmt->close();
            } else {
                $alert = "Błąd przygotowania zapytania: " . $conn->error;
            }
        }
    }
}

// Edytuj drużynę (miejsce obliczane automatycznie)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_druzyna') {
    $id = (int)(isset($_POST['id_druzyny_edit']) ? $_POST['id_druzyny_edit'] : 0);
    $nazwa = trim(isset($_POST['nazwa_druzyny_edit']) ? $_POST['nazwa_druzyny_edit'] : '');
    $wynik = trim(isset($_POST['wynik_druzyny_edit']) ? $_POST['wynik_druzyny_edit'] : '');
    $tor = isset($_POST['tor_druzyny_edit']) ? intval($_POST['tor_druzyny_edit']) : null;
    $tor = ($tor === 0 || $tor === null) ? null : $tor;

    if ($id <= 0 || $nazwa === '') {
        $alert = "Niepoprawne dane przy edycji drużyny.";
    } elseif ($wynik !== '' && !preg_match('/^\d{1,2}:\d{2},\d{3}$/', $wynik)) {
        $alert = "Wynik musi być w formacie MM:SS,mmm (np. 1:23,456).";
    } else {
        // Pobierz id_wyscigu tej drużyny
        $res_id = $conn->query("SELECT id_wyscigu FROM druzyny WHERE id = " . $id . " LIMIT 1");
        $id_wyscigu_tej = 0;
        if ($res_id && $r_id = $res_id->fetch_assoc()) {
            $id_wyscigu_tej = (int)$r_id['id_wyscigu'];
            $res_id->free();
        }

        if ($wynik === '') {
            $stmt = $conn->prepare("UPDATE druzyny SET nazwa = ?, wynik = NULL, tor = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sii", $nazwa, $tor, $id);
                if ($stmt->execute()) {
                    if ($id_wyscigu_tej > 0) przelicz_miejsca($conn, $id_wyscigu_tej);
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $alert = "Błąd aktualizacji drużyny: " . $conn->error;
                }
                $stmt->close();
            } else {
                $alert = "Błąd przygotowania zapytania: " . $conn->error;
            }
        } else {
            $stmt = $conn->prepare("UPDATE druzyny SET nazwa = ?, wynik = ?, tor = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssii", $nazwa, $wynik, $tor, $id);
                if ($stmt->execute()) {
                    if ($id_wyscigu_tej > 0) przelicz_miejsca($conn, $id_wyscigu_tej);
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $alert = "Błąd aktualizacji drużyny: " . $conn->error;
                }
                $stmt->close();
            } else {
                $alert = "Błąd przygotowania zapytania: " . $conn->error;
            }
        }
    }
}

// Usuń drużynę
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_druzyna') {
    $id = (int)(isset($_POST['id_druzyny_del']) ? $_POST['id_druzyny_del'] : 0);
    if ($id <= 0) {
        $alert = "Niepoprawne id przy usuwaniu drużyny.";
    } else {
        $stmt = $conn->prepare("DELETE FROM druzyny WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $alert = "Błąd usuwania drużyny: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// ---------------------- Pobranie danych ----------------------------

$zawody = [];
$res = $conn->query("SELECT id, nazwa FROM zawody ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $zawody[] = $r;
    $res->free();
}

// pobierz ustawienia prezentacyjne (z bazy) aby ustawić select w sekcji ustawień
$aktywne_zawody = 0;
$res_ust = $conn->query("SELECT wartosc FROM ustawienia WHERE klucz = 'aktywne_zawody' LIMIT 1");
if ($res_ust && $res_ust->num_rows > 0) {
    $row_ust = $res_ust->fetch_assoc();
    $aktywne_zawody = (int)$row_ust['wartosc'];
    $res_ust->free();
}

// drużyny z polem tor (wszystkie)
$druzyny_by_wyscig = [];
$res3 = $conn->query("SELECT id, nazwa, wynik, tor, miejsce, id_wyscigu FROM druzyny ORDER BY id_wyscigu ASC, miejsce ASC");
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

// Pobierz wyścigi — FILTRUJEMY jeśli istnieje cookie $selected_zawody_formularz
$wyscigi = [];
$where = '';
if (!empty($selected_zawody_formularz) && (int)$selected_zawody_formularz > 0) {
    $where = ' WHERE w.id_zawodow = ' . (int)$selected_zawody_formularz;
}
$res2 = $conn->query("
    SELECT w.id AS id, w.nazwa AS nazwa_w, w.id_zawodow, z.nazwa AS nazwa_z
    FROM wyscigi w
    LEFT JOIN zawody z ON w.id_zawodow = z.id
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
    <style>
        .zawody-item { cursor: pointer; }
        .list-group-flush .list-group-item.active { background-color: #0d6efd; color: #fff; }
        .teams-row table { margin-bottom: 0; }
        .team-editable { cursor: pointer; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Panel zarządzania</a>
        <div class="ms-auto">
            <a href="index.php" class="btn btn-outline-light btn-sm me-2" target="_blank">Strona prezentacyjna</a>
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

    <div class="row gy-4">
        <!-- USTAWIENIA STRONY PREZENTACYJNEJ -->
        <div class="col-12">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white"><strong>⚙️ Ustawienia strony prezentacyjnej</strong></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="ustaw_prezentacje">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Wybierz zawody do wyświetlania:</strong></label>
                                <select name="zawody_prezentacyjne" class="form-select" required>
                                    <option value="">-- wybierz --</option>
                                    <?php foreach ($zawody as $z): ?>
                                        <option value="<?php echo (int)$z['id']; ?>" <?php echo ($aktywne_zawody === (int)$z['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($z['nazwa']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div style="padding-top: 32px;">
                                    <button type="submit" class="btn btn-info">Zastosuj ustawienia</button>
                                    <small class="text-muted ms-2">Zmiana będzie widoczna na stronie index.php po odświeżeniu.</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center zawody-item<?php echo $isActive ? ' active' : ''; ?>"
                                     data-id="<?php echo (int)$z['id']; ?>">
                                    <div class="zawody-nazwa"><?php echo htmlspecialchars($z['nazwa']); ?></div>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary edit-zawody-btn"
                                                data-id="<?php echo (int)$z['id']; ?>"
                                                data-nazwa="<?php echo htmlspecialchars($z['nazwa'], ENT_QUOTES); ?>">Edytuj</button>
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
                <div class="card-header"><strong>Dodaj wyścig</strong></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add_wyscig">
                        <div class="mb-3">
                            <label class="form-label">Wybierz zawody</label>
                            <select name="id_zawodow" class="form-select" id="selectIdZawodow" required>
                                <option value="">-- wybierz --</option>
                                <?php foreach ($zawody as $z): ?>
                                    <option value="<?php echo (int)$z['id']; ?>" <?php echo ($selected_zawody_formularz === (int)$z['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($z['nazwa']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Kliknij zawody po lewej, aby ustawić domyślny wybór formularza.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nazwa wyścigu</label>
                            <input type="text" name="nazwa_wyscigu" class="form-control" required>
                        </div>
                        <button class="btn btn-success">Dodaj wyścig</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header"><strong>Lista wyścigów</strong></div>
                <div class="card-body p-0">
                    <?php if (empty($wyscigi)): ?>
                        <div class="p-3">Brak wyścigów.</div>
                    <?php else: ?>
                        <table class="table mb-0">
                            <thead>
                            <tr><th style="width:45px">#</th><th>Wyścig</th><th>Zawody</th><th>Akcje</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($wyscigi as $w): ?>
                                <tr class="wyscig-row" data-id="<?php echo (int)$w['id']; ?>" data-zawody="<?php echo (int)$w['id_zawodow']; ?>">
                                    <td class="text-muted small"><?php echo (int)$w['id']; ?></td>
                                    <td><?php echo htmlspecialchars($w['nazwa_w']); ?></td>
                                    <td><?php echo htmlspecialchars(isset($w['nazwa_z']) ? $w['nazwa_z'] : '—'); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addDruzynaModal"
                                                    data-id="<?php echo (int)$w['id']; ?>" data-wyscignazwa="<?php echo htmlspecialchars($w['nazwa_w'], ENT_QUOTES); ?>">Dodaj drużynę</button>
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editWyscigModal"
                                                    data-id="<?php echo (int)$w['id']; ?>" data-nazwa="<?php echo htmlspecialchars($w['nazwa_w'], ENT_QUOTES); ?>" data-idz="<?php echo (int)$w['id_zawodow']; ?>">Edytuj</button>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Usunąć wyścig?');">
                                                <input type="hidden" name="action" value="delete_wyscig">
                                                <input type="hidden" name="id_wyscigu_del" value="<?php echo (int)$w['id']; ?>">
                                                <button class="btn btn-outline-danger" type="submit">Usuń</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="teams-row" data-wyscig="<?php echo (int)$w['id']; ?>">
                                    <td colspan="4" class="small text-muted">
                                        <?php
                                        $teams = isset($druzyny_by_wyscig[(int)$w['id']]) ? $druzyny_by_wyscig[(int)$w['id']] : [];
                                        if (empty($teams)) {
                                            echo '<em>Brak drużyn.</em>';
                                        } else {
                                            echo '<table class="table table-sm mb-0">';
                                            echo '<thead><tr><th style="width:60px">Miejsce</th><th>Nazwa</th><th style="width:60px">Tor</th><th style="width:120px">Wynik</th></tr></thead><tbody>';
                                            foreach ($teams as $t) {
                                                $team_id = (int)$t['id'];
                                                $wynik = $t['wynik'];
                                                $tor = $t['tor'];
                                                $miejsce = (int)$t['miejsce'];
                                                $nazwa_t = htmlspecialchars($t['nazwa']);
                                                echo '<tr class="team-editable" data-team-id="' . $team_id . '" data-team-name="' . $nazwa_t . '" data-team-wynik="' . htmlspecialchars($wynik) . '" data-team-tor="' . htmlspecialchars($tor) . '" data-team-miejsce="' . $miejsce . '" data-team-wyscig="' . (int)$t['id_wyscigu'] . '">';
                                                echo '<td>' . $miejsce . '</td>';
                                                echo '<td>' . $nazwa_t . '</td>';
                                                echo '<td>' . ($tor !== null && $tor !== '' ? htmlspecialchars($tor) : '—') . '</td>';
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
                                <option value="<?php echo (int)$z['id']; ?>"><?php echo htmlspecialchars($z['nazwa']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nazwa wyścigu</label>
                        <input type="text" name="nazwa_wyscigu_edit" id="editWyscigNazwa" class="form-control" required>
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
                    <div class="mb-3">
                        <label class="form-label">Nazwa drużyny</label>
                        <input type="text" name="nazwa_druzyny" id="addDruzynaNazwa" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tor</label>
                        <input type="number" name="tor_druzyny" id="addDruzynaTor" class="form-control" min="1" placeholder="(opcjonalnie)">
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
                        <input type="text" name="nazwa_druzyny_edit" id="editDruzynaNazwa" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tor</label>
                        <input type="number" name="tor_druzyny_edit" id="editDruzynaTor" class="form-control" min="1" placeholder="(opcjonalnie)">
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

<!-- ==================== SKRYPTY ==================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
                var sel = document.getElementById('editWyscigZawody');
                if (sel) sel.value = button.getAttribute('data-idz') || '';
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
                document.getElementById('addDruzynaNazwa').value = '';
                document.getElementById('addDruzynaTor').value = '';
                document.getElementById('addDruzynaWynik').value = '';
                document.getElementById('addWynikFeedback').textContent = '';
                document.getElementById('addWynikFeedback').className = 'form-text';
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
            document.getElementById('editDruzynaNazwa').value = row.getAttribute('data-team-name') || '';
            document.getElementById('editDruzynaTor').value = row.getAttribute('data-team-tor') || '';
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

        // --- Wybór zawodów (filtrowanie listy wyścigów) ---
        var zawodyItems = document.querySelectorAll('.zawody-item');
        var selectZawody = document.getElementById('selectIdZawodow');

        function clearSelection() {
            zawodyItems.forEach(function (it) { it.classList.remove('active'); });
            if (selectZawody) selectZawody.value = "";
        }

        function selectById(id, saveCookie) {
            zawodyItems.forEach(function (it) {
                if (it.dataset.id === String(id)) it.classList.add('active');
                else it.classList.remove('active');
            });
            if (selectZawody) selectZawody.value = id;
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
</script>
</body>
</html>