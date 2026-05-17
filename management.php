<?php
session_start();
require_once "config.php"; // musi ustawić $conn jako mysqli

$alert = "";

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
                $alert = "Dodano zawody.";
            } else {
                $alert = "Błąd: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
    // redirect aby uniknąć ponownego wysłania formularza
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
                $alert = "Zaktualizowano zawody.";
            } else {
                $alert = "Błąd aktualizacji: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Usuń zawody
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_zawody') {
    $id = (int)(isset($_POST['id_zawody_del']) ? $_POST['id_zawody_del'] : 0);
    if ($id <= 0) {
        $alert = "Niepoprawne id przy usuwaniu zawodów.";
    } else {
        // usuwamy zawody — jeśli masz FK z ON DELETE CASCADE, powiązane wyścigi zostaną usunięte
        $stmt = $conn->prepare("DELETE FROM zawody WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $alert = "Usunięto zawody.";
            } else {
                $alert = "Błąd usuwania: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Dodaj wyścig (z obsługą dodania nowych zawodów w jednym formularzu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_wyscig') {
    $nazwa_w = trim(isset($_POST['nazwa_wyscigu']) ? $_POST['nazwa_wyscigu'] : '');
    $id_z = (int)(isset($_POST['id_zawodow']) ? $_POST['id_zawodow'] : 0);
    $nowe_zawody = trim(isset($_POST['nowe_zawody']) ? $_POST['nowe_zawody'] : '');

    if ($nowe_zawody !== '') {
        $ins = $conn->prepare("INSERT INTO zawody (nazwa) VALUES (?)");
        if ($ins) {
            $ins->bind_param("s", $nowe_zawody);
            if ($ins->execute()) {
                $id_z = $conn->insert_id;
            } else {
                $alert = "Błąd dodawania zawodów: " . $conn->error;
            }
            $ins->close();
        } else {
            $alert = "Błąd przygotowania dodania zawodów: " . $conn->error;
        }
    }

    if ($alert === '') {
        if ($nazwa_w === '' || $id_z <= 0) {
            $alert = "Podaj nazwę wyścigu i wybierz (lub utwórz) zawody.";
        } else {
            $insw = $conn->prepare("INSERT INTO wyscigi (id_zawodow, nazwa) VALUES (?, ?)");
            if ($insw) {
                $insw->bind_param("is", $id_z, $nazwa_w);
                if ($insw->execute()) {
                    $alert = "Dodano wyścig.";
                } else {
                    $alert = "Błąd dodawania wyścigu: " . $conn->error;
                }
                $insw->close();
            } else {
                $alert = "Błąd przygotowania zapytania: " . $conn->error;
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
                $alert = "Zaktualizowano wyścig.";
            } else {
                $alert = "Błąd aktualizacji: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
                $alert = "Usunięto wyścig.";
            } else {
                $alert = "Błąd usuwania: " . $conn->error;
            }
            $stmt->close();
        } else {
            $alert = "Błąd przygotowania zapytania: " . $conn->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -------------------------------------------------------------------------

// Pobranie listy zawodów i wyścigów
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
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Zarządzanie zawodami i wyścigami</title>
    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">Panel zarządzania</a>
        <div class="ms-auto">
            <form method="post" action="logout.php" class="d-inline">
                <button class="btn btn-outline-light btn-sm" type="submit">Wyloguj</button>
            </form>
        </div>
    </div>
</nav>

<div class="container">

    <?php if ($alert): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($alert); ?></div>
    <?php endif; ?>

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
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><?php echo htmlspecialchars($z['nazwa']); ?></div>
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
                    <form method="post">
                        <input type="hidden" name="action" value="add_wyscig">

                        <div class="mb-3">
                            <label class="form-label">Wybierz istniejące zawody</label>
                            <select name="id_zawodow" class="form-select">
                                <option value="">-- wybierz --</option>
                                <?php foreach ($zawody as $z): ?>
                                    <option value="<?php echo (int)$z['id']; ?>"><?php echo htmlspecialchars($z['nazwa']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">... LUB dodaj nowe zawody</label>
                            <input type="text" name="nowe_zawody" class="form-control" placeholder="Nazwa nowych zawodów">
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
                                <tr>
                                    <td><?php echo (int)$w['id']; ?></td>
                                    <td><?php echo htmlspecialchars($w['nazwa_w']); ?></td>
                                    <td><?php echo htmlspecialchars(isset($w['nazwa_z']) ? $w['nazwa_z'] : '—'); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
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

<!-- Bootstrap JS (popper + bootstrap) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Wypełnianie modali danymi z data-attributes
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
</script>

</body>
</html>