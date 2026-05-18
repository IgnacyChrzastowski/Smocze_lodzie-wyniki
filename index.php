<?php
session_start();
require_once "config.php";

// Pobranie zawodów
$zawody = [];
$res = $conn->query("SELECT id, nazwa FROM zawody ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $zawody[] = $r;
    $res->free();
}

// Pobranie wyścigów (początkowa lista, zostanie zmieniała przez AJAX)
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
    <title>Wyniki Smoczych Łodzi</title>
    <!-- Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .zawody-item-view { cursor: pointer; }
        .zawody-item-view.active { background-color: #0d6efd; color: #fff; }
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
        <!-- Lewy panel: zawody -->
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Zawody</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (count($zawody) === 0): ?>
                        <div class="p-3">Brak zawodów.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($zawody as $z): ?>
                                <div class="list-group-item zawody-item-view"
                                     data-id="<?php echo (int)$z['id']; ?>">
                                    <?php echo htmlspecialchars($z['nazwa']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="refresh-indicator">
                ♻️ Auto-odświeżanie co 5 sekund
            </div>
        </div>

        <!-- Prawy panel: wyniki -->
        <div class="col-md-9">
            <div id="resultsContainer" class="card shadow-sm">
                <div class="card-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Ładowanie...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    (function(){
        let selectedZawodyId = null;

        const zawodyItems = document.querySelectorAll('.zawody-item-view');
        const resultsContainer = document.getElementById('resultsContainer');

        // funkcja do pobierania i wyświetlania wyników
        function loadResults(zawodyId = null) {
            let url = 'ajax_get_results.php';
            if (zawodyId) {
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

        // obsługa klikania na zawody
        zawodyItems.forEach(item => {
            item.addEventListener('click', function(){
                const id = this.getAttribute('data-id');

                // zmiana zaznaczenia
                zawodyItems.forEach(it => it.classList.remove('active'));
                this.classList.add('active');

                selectedZawodyId = id;
                loadResults(id);
            });
        });

        // initial load - pokaż wszystkie wyścigi
        loadResults();

        // auto-refresh co 5 sekund
        setInterval(() => {
            loadResults(selectedZawodyId);
        }, 5000);
    })();
</script>

</body>
</html>