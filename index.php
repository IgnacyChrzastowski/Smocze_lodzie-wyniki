<?php
require_once "config.php";

$zawody_id = 0;
$nazwa_zawodow = '';

$res = $conn->query("SELECT wartosc FROM ustawienia WHERE klucz = 'aktywne_zawody' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $zawody_id = (int)$row['wartosc'];
    $res->free();
}

if ($zawody_id === 0) {
    $res = $conn->query("SELECT id FROM zawody ORDER BY id ASC LIMIT 1");
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
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Wyniki Smoczych Łodzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #resultsContainer { min-height: 300px; }
        .refresh-indicator { font-size: 0.85rem; color: #999; margin-top: 10px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Smocze Łodzie - Wyniki</a>
    </div>
</nav>

<div class="container">
    <div class="row gy-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Wyniki: <span id="zawodyNazwa"><?php echo htmlspecialchars($nazwa_zawodow ?: 'Ładowanie...'); ?></span></strong>
                </div>
                <div id="resultsContainer" class="card-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Ładowanie...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="refresh-indicator">
                ♻️ Auto-odświeżanie co 5 sekund
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function(){
        const resultsContainer = document.getElementById('resultsContainer');
        const zawodyNazwaEl = document.getElementById('zawodyNazwa');
        let currentZawodyId = <?php echo (int)$zawody_id; ?>;

        function loadResults() {
            fetch('ajax_get_settings.php')
                .then(r => r.json())
                .then(data => {
                    if (data.zawody_id && data.zawody_id !== currentZawodyId) {
                        currentZawodyId = data.zawody_id;
                        zawodyNazwaEl.textContent = data.nazwa_zawodow || 'Brak nazwy';
                    }

                    let url = 'ajax_get_results.php?zawody_id=' + currentZawodyId;
                    return fetch(url).then(r => r.text());
                })
                .then(html => {
                    resultsContainer.innerHTML = html;
                })
                .catch(err => {
                    resultsContainer.innerHTML = '<div class="alert alert-danger">Błąd: ' + err.message + '</div>';
                    console.error(err);
                });
        }

        loadResults();
        setInterval(loadResults, 5000);
    })();
</script>

</body>
</html>