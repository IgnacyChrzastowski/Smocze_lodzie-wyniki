<?php
session_start();
require_once "config.php";

// Pobierz id zawodów z cookies (ustawiany z management.php)
$zawody_id = 0;
if (isset($_COOKIE['zawody_prezentacyjne'])) {
    $zawody_id = (int)$_COOKIE['zawody_prezentacyjne'];
}

// Jeśli brak cookies lub id = 0, wyświetl informację
if ($zawody_id === 0) {
    // Pobierz pierwsze zawody jako fallback
    $res = $conn->query("SELECT id FROM zawody ORDER BY id ASC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $zawody_id = $row['id'];
        $res->free();
    }
}

// Pobierz dane bieżących zawodów
$nazwa_zawodow = '';
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
    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #resultsContainer { min-height: 300px; }
        .refresh-indicator { font-size: 0.85rem; color: #999; margin-top: 10px; }
        .nav-link { cursor: pointer; }
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
        <!-- Główna sekcja: wyniki -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Wyniki: <?php echo htmlspecialchars($nazwa_zawodow ?: 'Brak zawodów'); ?></strong>
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function(){
        const resultsContainer = document.getElementById('resultsContainer');
        const zawodyId = <?php echo (int)$zawody_id; ?>;

        // funkcja do pobierania i wyświetlania wyników
        function loadResults() {
            let url = 'ajax_get_results.php';
            if (zawodyId > 0) {
                url += '?zawody_id=' + encodeURIComponent(zawodyId);
            }

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    resultsContainer.innerHTML = html;
                })
                .catch(error => {
                    resultsContainer.innerHTML = '<div class="alert alert-danger">Błąd ładowania danych</div>';
                    console.error('Error:', error);
                });
        }

        // initial load
        loadResults();

        // auto-refresh co 5 sekund
        setInterval(() => {
            loadResults();
        }, 5000);
    })();
</script>

</body>
</html>