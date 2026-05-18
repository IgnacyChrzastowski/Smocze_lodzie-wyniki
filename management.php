<?php
session_start();

// SPRAWDZENIE LOGOWANIA
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once "config.php"; // musi ustawić $conn jako mysqli

$alert = "";

// -- OBSŁUGA COOKIES -------------------------------------------------------
// Jeśli użytkownik zmieni wybór zawodów dla strony prezentacyjnej
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_zawody_prezentacyjne') {
    $zawody_id = isset($_POST['zawody_prezentacyjne']) ? (int)$_POST['zawody_prezentacyjne'] : 0;
    if ($zawody_id > 0) {
        setcookie('zawody_prezentacyjne', $zawody_id, time() + (30 * 24 * 60 * 60), '/'); // 30 dni
        $_COOKIE['zawody_prezentacyjne'] = $zawody_id;
        $alert = "Zawody do wyświetlania zmienione.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Jeśli użytkownik zmieni wybór zawodów dla formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_zawody_formularz') {
    $zawody_id = isset($_POST['zawody_formularz']) ? (int)$_POST['zawody_formularz'] : 0;
    if ($zawody_id > 0) {
        setcookie('zawody_formularz', $zawody_id, time() + (30 * 24 * 60 * 60), '/'); // 30 dni
        $_COOKIE['zawody_formularz'] = $zawody_id;
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -- HANDLERY POST -------------------------------------------------------
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
                $alert = "Błąd: " . $conn->error;
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
                $alert = "Błąd aktualizacji: " . $conn->error;
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
                $alert = "Błąd usuwania: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Dodaj wyścig (wymagane wybranie istniejących zawodów)
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
                $alert = "Błąd aktualizacji: " . $conn->error;
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
                $alert = "Błąd usuwania: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
}

// Dodaj drużynę (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_druzyna') {
    $id_wyscigu = (int)(isset($_POST['id_wyscigu']) ? $_POST['id_wyscigu'] : 0);
    $nazwa = trim(isset($_POST['nazwa_druzyny']) ? $_POST['nazwa_druzyny'] : '');
    $wynik = trim(isset($_POST['wynik_druzyny']) ? $_POST['wynik_druzyny'] : '');
    $miejsce = isset($_POST['miejsce_druzyny']) ? intval($_POST['miejsce_druzyny']) : 0;

    if ($id_wyscigu <= 0 || $nazwa === '' || $miejsce <= 0) {
        $alert = "Podaj nazwę drużyny, poprawne miejsce (>=1) i wybierz wyścig.";
    } else {
        if ($wynik === '') {
            $stmt = $conn->prepare("INSERT INTO druzyny (nazwa, miejsce, id_wyscigu) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sii", $nazwa, $miejsce, $id_wyscigu);
                if ($stmt->execute()) {
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
            if (!preg_match('/^\d{1,2}:\d{2},\d{3}$/', $wynik)) {
                $alert = "Wynik musi być w formacie MM:SS,mmm (np. 1:23,456).";
            } else {
                $stmt = $conn->prepare("INSERT INTO druzyny (nazwa, wynik, miejsce, id_wyscigu) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("ssii", $nazwa, $wynik, $miejsce, $id_wyscigu);
                    if ($stmt->execute()) {
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
}

// Edytuj drużynę
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_druzyna') {
    $id = (int)(isset($_POST['id_druzyny_edit']) ? $_POST['id_druzyny_edit'] : 0);
    $nazwa = trim(isset($_POST['nazwa_druzyny_edit']) ? $_POST['nazwa_druzyny_edit'] : '');
    $wynik = trim(isset($_POST['wynik_druzyny_edit']) ? $_POST['wynik_druzyny_edit'] : '');
    $miejsce = isset($_POST['miejsce_druzyny_edit']) ? intval($_POST['miejsce_druzyny_edit']) : 0;

    if ($id <= 0 || $nazwa === '' || $miejsce <= 0) {
        $alert = "Niepoprawne dane przy edycji drużyny.";
    } else {
        if ($wynik === '') {
            $stmt = $conn->prepare("UPDATE druzyny SET nazwa = ?, wynik = NULL, miejsce = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sii", $nazwa, $miejsce, $id);
                if ($stmt->execute()) {
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
            if (!preg_match('/^\d{1,2}:\d{2},\d{3}$/', $wynik)) {
                $alert = "Wynik musi być w formacie MM:SS,mmm (np. 1:23,456).";
            } else {
                $stmt = $conn->prepare("UPDATE druzyny SET nazwa = ?, wynik = ?, miejsce = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("ssii", $nazwa, $wynik, $miejsce, $id);
                    if ($stmt->execute()) {
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

// -------------------------------------------------------------------------

// Pobranie zawodów i wyścigów
$zawody = [];
$res = $conn->query("SELECT id, nazwa FROM zawody ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $zawody[] = $r;
    $res->free();
}

$wyscigi = [];
$res2 = $conn->query("
    SELECT w.id AS id, w.nazwa AS nazwa_w, w.id_zawodow, z.nazwa AS nazwa_z
    FROM wyscigi w
    LEFT JOIN zawody z ON w.id_zawodow = z.id
    ORDER BY w.id ASC
");
if ($res2) {
    while ($r = $res2->fetch_assoc()) $wyscigi[] = $r;
    $res2->free();
}

// Pobierz drużyny pogrupowane po id_wyscigu
$druzyny_by_wyscig = [];
$res3 = $conn->query("SELECT id, nazwa, wynik, miejsce, id_wyscigu FROM druzyny ORDER BY id_wyscigu ASC, miejsce ASC");
if ($res3) {
    while ($row = $res3->fetch_assoc()) {
        $idw = (int)$row['id_wyscigu'];
        if (!isset($druzyny_by_wyscig[$idw])) $druzyny_by_wyscig[$idw] = [];
        $druzyny_by_wyscig[$idw][] = $row;
    }
    $res3->free();
}

// Pobierz cookies dla zapamiętanych zawodów
$selected_zawody_prezentacyjne = isset($_COOKIE['zawody_prezentacyjne']) ? (int)$_COOKIE['zawody_prezentacyjne'] : 0;
$selected_zawody_formularz = isset($_COOKIE['zawody_formularz']) ? (int)$_COOKIE['zawody_formularz'] : 0;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Zarządzanie zawodami i wyścigami</title>
    <!-- Bootstrap CSS (CDN) -->
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

    <!-- Sekcja wyboru zawodów do wyświetlania na stronie prezentacyjnej -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm bg-info bg-opacity-10 border-info">
                <div class="card-header bg-info text-white">
                    <strong>⚙️ Ustawienia strony prezentacyjnej</strong>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3 align-items-end">
                        <input type="hidden" name="action" value="set_zawody_prezentacyjne">
                        <div class="col-md-6">
                            <label class="form-label">Zawody wyświetlane na stronie index.php:</label>
                            <select name="zawody_prezentacyjne" class="form-select" required>
                                <option value="">-- wszystkie zawody --</option>
                                <?php foreach ($zawody as $z): ?>
                                    <option value="<?php echo (int)$z['id']; ?>"
                                            <?php echo ($selected_zawody_prezentacyjne === (int)$z['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($z['nazwa']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">Zapisz ustawienia</button>
                        </div>
                    </form>
                    <small class="text-muted">Wybrane zawody będą wyświetlane na stronie prezentacyjnej. Wybór zostanie zapamiętany.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Dodaj nowe zawody</strong>
                </div>
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
                <div class="card-header">
                    <strong>Lista zawodów</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (count($zawody) === 0): ?>
                        <div class="p-3">Brak zawodów.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($zawody as $z): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center zawody-item"
                                     data-id="<?php echo (int)$z['id']; ?>">
                                    <div class="zawody-nazwa"><?php echo htmlspecialchars($z['nazwa']); ?></div>
                                    <div class="btn-group">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editZawodyModal"
                                                data-id="<?php echo (int)$z['id']; ?>"
                                                data-nazwa="<?php echo htmlspecialchars($z['nazwa'], ENT_QUOTES); ?>">
                                            Edytuj
                                        </button>

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

        <!-- Dodaj wyścig -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Dodaj wyścig</strong></div>
                <div class="card-body">
                    <form method="post" id="formAddWyscig">
                        <input type="hidden" name="action" value="add_wyscig">

                        <div class="mb-3">
                            <label class="form-label">Wybierz zawody</label>
                            <div class="input-group">
                                <select name="id_zawodow" class="form-select" id="selectIdZawodow" required>
                                    <option value="">-- wybierz --</option>
                                    <?php foreach ($zawody as $z): ?>
                                        <option value="<?php echo (int)$z['id']; ?>"
                                                <?php echo ($selected_zawody_formularz === (int)$z['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($z['nazwa']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" id="btnZapamietajZawody" title="Zapamiętaj wybór tej opcji">💾</button>
                            </div>
                            <small class="text-muted">Kliknij 💾 aby zapamiętać wybrane zawody.</small>
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
                    <?php if (count($wyscigi) === 0): ?>
                        <div class="p-3">Brak wyścigów.</div>
                    <?php else: ?>
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Wyścig</th>
                                <th>Zawody</th>
                                <th>Akcje</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($wyscigi as $w): ?>
                                <tr class="wyscig-row" data-id="<?php echo (int)$w['id']; ?>" data-zawody="<?php echo (int)$w['id_zawodow']; ?>">
                                    <td><?php echo (int)$w['id']; ?></td>
                                    <td><?php echo htmlspecialchars($w['nazwa_w']); ?></td>
                                    <td><?php echo htmlspecialchars(isset($w['nazwa_z']) ? $w['nazwa_z'] : '—'); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button"
                                                    class="btn btn-outline-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addDruzynaModal"
                                                    data-id="<?php echo (int)$w['id']; ?>"
                                                    data-wyscignazwa="<?php echo htmlspecialchars($w['nazwa_w'], ENT_QUOTES); ?>">
                                                Dodaj drużynę
                                            </button>

                                            <button type="button"
                                                    class="btn btn-outline-secondary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editWyscigModal"
                                                    data-id="<?php echo (int)$w['id']; ?>"
                                                    data-nazwa="<?php echo htmlspecialchars($w['nazwa_w'], ENT_QUOTES); ?>"
                                                    data-idz="<?php echo (int)$w['id_zawodow']; ?>">
                                                Edytuj
                                            </button>

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
                                        if (count($teams) === 0) {
                                            echo '<em>Brak drużyn.</em>';
                                        } else {
                                            echo '<table class="table table-sm mb-0">';
                                            echo '<thead><tr><th style="width:80px">Miejsce</th><th>Nazwa</th><th>Wynik</th></tr></thead>';
                                            echo '<tbody>';
                                            foreach ($teams as $t) {
                                                $team_id = (int)$t['id'];
                                                $wynik = $t['wynik'];
                                                $miejsce = (int)$t['miejsce'];
                                                $nazwa_t = htmlspecialchars($t['nazwa']);
                                                echo '<tr class="team-editable" data-team-id="' . $team_id . '" data-team-name="' . $nazwa_t . '" data-team-wynik="' . htmlspecialchars($wynik) . '" data-team-miejsce="' . $miejsce . '" data-team-wyscig="' . (int)$t['id_wyscigu'] . '">';
                                                echo '<td>' . $miejsce . '</td>';
                                                echo '<td>' . $nazwa_t . '</td>';
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

<!-- Modal edycji zawodów -->
<div class="modal fade" id="editZawodyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="edit_zawody">
            <input type="hidden" name="id_zawody" id="editZawodyId" value="">
            <div class="modal-header">
                <h5 class="modal-title">Edytuj zawody</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nazwa</label>
                    <input type="text" name="nazwa_zawodow_edit" id="editZawodyNazwa" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Anuluj</button>
                <button class="btn btn-primary" type="submit">Zapisz</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal edycji wyścigu -->
<div class="modal fade" id="editWyscigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="edit_wyscig">
            <input type="hidden" name="id_wyscigu_edit" id="editWyscigId" value="">
            <div class="modal-header">
                <h5 class="modal-title">Edytuj wyścig</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nazwa wyścigu</label>
                    <input type="text" name="nazwa_wyscigu_edit" id="editWyscigNazwa" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Zawody</label>
                    <select name="id_zawodow_edit" id="editWyscigZawody" class="form-select" required>
                        <option value="">-- wybierz --</option>
                        <?php foreach ($zawody as $z): ?>
                            <option value="<?php echo (int)$z['id']; ?>"><?php echo htmlspecialchars($z['nazwa']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Anuluj</button>
                <button class="btn btn-primary" type="submit">Zapisz</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal dodawania drużyny -->
<div class="modal fade" id="addDruzynaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="add_druzyna">
            <input type="hidden" name="id_wyscigu" id="addDruzynaWyscigId" value="">
            <div class="modal-header">
                <h5 class="modal-title">Dodaj drużynę</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nazwa drużyny</label>
                    <input type="text" name="nazwa_druzyny" id="addDruzynaNazwa" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Wynik (opcjonalnie)</label>
                    <input type="text" name="wynik_druzyny" id="addDruzynaWynik" class="form-control" placeholder="MM:SS,mmm" pattern="\d{1,2}:\d{2},\d{3}">
                    <div class="form-text">Format: MM:SS,mmm (np. 1:23,456). Jeśli puste, wynik nie będzie zapisywany.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Miejsce (liczba)</label>
                    <input type="number" name="miejsce_druzyny" id="addDruzynaMiejsce" class="form-control" min="1" required>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Wyścig: <span id="addDruzynaWyscigName"></span></small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Anuluj</button>
                <button class="btn btn-primary" type="submit">Dodaj drużynę</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal edycji drużyny -->
<div class="modal fade" id="editDruzynaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- edycja -->
            <form method="post" id="formEditDruzyna">
                <input type="hidden" name="action" value="edit_druzyna">
                <input type="hidden" name="id_druzyny_edit" id="editDruzynaId" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Edytuj drużynę</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nazwa drużyny</label>
                        <input type="text" name="nazwa_druzyny_edit" id="editDruzynaNazwa" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wynik (opcjonalnie)</label>
                        <input type="text" name="wynik_druzyny_edit" id="editDruzynaWynik" class="form-control" placeholder="MM:SS,mmm" pattern="\d{1,2}:\d{2},\d{3}">
                        <div class="form-text">Format: MM:SS,mmm (np. 1:23,456). Pozostaw puste aby usunąć wynik.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Miejsce</label>
                        <input type="number" name="miejsce_druzyny_edit" id="editDruzynaMiejsce" class="form-control" min="1" required>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Wyścig: <span id="editDruzynaWyscigName"></span></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="btnDeleteDruzyna">Usuń drużynę</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>

            <!-- ukryty formularz do usuwania (wywoływany przez JS) -->
            <form method="post" id="formDeleteDruzyna" style="display:none;">
                <input type="hidden" name="action" value="delete_druzyna">
                <input type="hidden" name="id_druzyny_del" id="deleteDruzynaId" value="">
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS (popper + bootstrap) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Modale dla zawodów / wyścigów
    var editZawodyModal = document.getElementById('editZawodyModal');
    if (editZawodyModal) {
        editZawodyModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var nazwa = button.getAttribute('data-nazwa');
            document.getElementById('editZawodyId').value = id;
            document.getElementById('editZawodyNazwa').value = nazwa;
        });
    }

    var editWyscigModal = document.getElementById('editWyscigModal');
    if (editWyscigModal) {
        editWyscigModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var nazwa = button.getAttribute('data-nazwa');
            var idz = button.getAttribute('data-idz');
            document.getElementById('editWyscigId').value = id;
            document.getElementById('editWyscigNazwa').value = nazwa;
            var sel = document.getElementById('editWyscigZawody');
            if (sel) sel.value = idz || "";
        });
    }

    // Dodawanie drużyny modal
    var addModal = document.getElementById('addDruzynaModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var nazwa = button.getAttribute('data-wyscignazwa') || '';
            document.getElementById('addDruzynaWyscigId').value = id;
            document.getElementById('addDruzynaWyscigName').textContent = nazwa;
            document.getElementById('addDruzynaNazwa').value = '';
            document.getElementById('addDruzynaWynik').value = '';
            document.getElementById('addDruzynaMiejsce').value = '';
        });
    }

    // Zapamiętanie wyboru zawodów w formularzu dodawania wyścigu
    document.getElementById('btnZapamietajZawody').addEventListener('click', function(){
        var selectEl = document.getElementById('selectIdZawodow');
        var zawodyId = selectEl.value;
        if (zawodyId === '') {
            alert('Proszę wybrać zawody.');
            return;
        }
        // Utwórz ukryty formularz i wyślij POST
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        form.innerHTML = '<input type="hidden" name="action" value="set_zawody_formularz"><input type="hidden" name="zawody_formularz" value="' + zawodyId + '">';
        document.body.appendChild(form);
        form.submit();
    });

    // Zaznaczanie zawodów, ustawianie selecta i filtrowanie wyścigów
    (function(){
        const zawodyItems = document.querySelectorAll('.zawody-item');
        const selectZawody = document.getElementById('selectIdZawodow');
        let selectedId = null;

        function clearSelection() {
            zawodyItems.forEach(it => it.classList.remove('active'));
            selectedId = null;
            if (selectZawody) selectZawody.value = "";
            document.querySelectorAll('.wyscig-row, .teams-row').forEach(el => el.style.display = '');
        }

        function selectById(id) {
            zawodyItems.forEach(it => {
                if (it.dataset.id === String(id)) it.classList.add('active'); else it.classList.remove('active');
            });
            selectedId = String(id);
            if (selectZawody) selectZawody.value = selectedId;
            document.querySelectorAll('.wyscig-row').forEach(row => {
                row.style.display = (row.dataset.zawody === selectedId) ? '' : 'none';
            });
            document.querySelectorAll('.teams-row').forEach(tr => {
                const idw = tr.dataset.wyscig;
                const wr = document.querySelector('.wyscig-row[data-id="' + idw + '"]');
                tr.style.display = (wr && wr.style.display !== 'none') ? '' : 'none';
            });
        }

        zawodyItems.forEach(it => {
            it.addEventListener('click', function(e){
                const id = this.dataset.id;
                if (this.classList.contains('active')) {
                    clearSelection();
                } else {
                    selectById(id);
                }
            });
        });

        clearSelection();

        // Obsługa edycji drużyny: kliknięcie wiersza
        document.addEventListener('click', function(e){
            var row = e.target.closest('.team-editable');
            if (!row) return;
            var id = row.getAttribute('data-team-id');
            var name = row.getAttribute('data-team-name') || '';
            var wynik = row.getAttribute('data-team-wynik') || '';
            var miejsce = row.getAttribute('data-team-miejsce') || '';
            var wyscig = row.getAttribute('data-team-wyscig') || '';

            document.getElementById('editDruzynaId').value = id;
            document.getElementById('editDruzynaNazwa').value = name;
            document.getElementById('editDruzynaWynik').value = wynik;
            document.getElementById('editDruzynaMiejsce').value = miejsce;
            document.getElementById('editDruzynaWyscigName').textContent = '';

            var modalEl = document.getElementById('editDruzynaModal');
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        // Obsługa usuwania drużyny z modala
        var btnDel = document.getElementById('btnDeleteDruzyna');
        if (btnDel) {
            btnDel.addEventListener('click', function(){
                if (!confirm('Na pewno usunąć tę drużynę?')) return;
                var id = document.getElementById('editDruzynaId').value;
                document.getElementById('deleteDruzynaId').value = id;
                document.getElementById('formDeleteDruzyna').submit();
            });
        }
    })();
</script>

</body>
</html>