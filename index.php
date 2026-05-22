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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --font-base: clamp(1.05rem, 2.5vw, 1.25rem);
        }

        body {
            font-size: var(--font-base);
            background: #f0f4f8;
        }

        /* ── Navbar ── */
        .navbar-brand {
            font-size: clamp(1.1rem, 3vw, 1.4rem);
            font-weight: 700;
            letter-spacing: .01em;
        }
        .refresh-pill {
            font-size: 0.78rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .refresh-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s infinite;
            flex-shrink: 0;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: .3; }
        }

        /* ── Nagłówek zawodów ── */
        .zawody-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: #fff;
            border-radius: 14px;
            padding: clamp(14px, 3vw, 22px) clamp(16px, 4vw, 28px);
            margin-bottom: 18px;
            box-shadow: 0 4px 18px rgba(13,110,253,.25);
        }
        .zawody-header .label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            opacity: .8;
            margin-bottom: 2px;
        }
        .zawody-header .nazwa {
            font-size: clamp(1.15rem, 3.5vw, 1.6rem);
            font-weight: 700;
            line-height: 1.2;
        }

        /* ── Karta wyścigu ── */
        .race-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            overflow: hidden;
            margin-bottom: 18px;
        }
        .race-card .race-title {
            background: #1a1a2e;
            color: #fff;
            font-size: clamp(1rem, 2.8vw, 1.2rem);
            font-weight: 700;
            padding: clamp(10px, 2.5vw, 16px) clamp(14px, 3vw, 22px);
            letter-spacing: .02em;
        }

        /* ── Tabela wyników ── */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: clamp(1rem, 2.6vw, 1.15rem);
        }
        .results-table th {
            background: #f8f9fa;
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6c757d;
            padding: 10px 14px;
            border-bottom: 2px solid #e9ecef;
        }
        .results-table td {
            padding: clamp(10px, 2.5vw, 16px) clamp(10px, 2.5vw, 16px);
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .results-table tr:last-child td { border-bottom: none; }
        .results-table tr:hover { background: #f7f9ff; }

        /* Miejsce - duży kolorowy badge */
        .place-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: clamp(36px, 7vw, 48px);
            height: clamp(36px, 7vw, 48px);
            border-radius: 50%;
            font-weight: 800;
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            background: #e9ecef;
            color: #495057;
            flex-shrink: 0;
        }
        .place-badge.gold   { background: linear-gradient(135deg,#ffd700,#f0a500); color:#7a5000; box-shadow:0 2px 8px rgba(240,165,0,.4); }
        .place-badge.silver { background: linear-gradient(135deg,#e0e0e0,#b0b0b0); color:#444; box-shadow:0 2px 8px rgba(150,150,150,.3); }
        .place-badge.bronze { background: linear-gradient(135deg,#cd7f32,#a0522d); color:#fff; box-shadow:0 2px 8px rgba(160,82,45,.35); }

        /* Nazwa drużyny */
        .team-name {
            font-weight: 600;
            font-size: clamp(1rem, 2.6vw, 1.15rem);
            line-height: 1.3;
        }

        /* Wynik - monospace, wyróżniony */
        .result-time {
            font-family: 'Courier New', Courier, monospace;
            font-size: clamp(1rem, 2.6vw, 1.15rem);
            font-weight: 700;
            color: #0d6efd;
            white-space: nowrap;
        }
        .result-time.empty { color: #adb5bd; font-weight: 400; font-style: italic; font-family: inherit; }

        /* Tor */
        .lane-badge {
            display: inline-block;
            background: #e8f0fe;
            color: #3a5fc8;
            border-radius: 6px;
            padding: 2px 9px;
            font-size: .8rem;
            font-weight: 600;
        }

        /* Brak wyścigów */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #adb5bd;
            font-size: 1rem;
        }

        /* Logo Fromair */
        .fromair-logo {
            height: 38px;
            width: auto;
            border-radius: 6px;
        }

        #resultsContainer { min-height: 200px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-3 py-2 d-flex justify-content-between align-items-center">
    <a class="navbar-brand mb-0" href="index.php">Smocze Łodzie</a>
    <div class="d-flex align-items-center gap-3">
        <a href="https://fromair.pl/" target="_blank" rel="noopener"
           class="d-flex align-items-center gap-2 text-decoration-none"
           title="Fromair.pl - Twoje Wydarzenie W Lepszym Wymiarze">
            <img src="https://fromair.pl/wp-content/uploads/2025/02/Logo_bez_ta_Obszar_roboczy_1-120x120.png"
                 alt="Fromair.pl" class="fromair-logo">
            <span style="color:#fff; font-size: clamp(0.7rem, 2vw, 0.85rem); line-height:1.25; max-width:110px;">
                <strong>fromair.pl</strong><br>
            </span>
        </a>
        <div class="refresh-pill">
            <span class="refresh-dot"></span> live
        </div>
    </div>
</nav>

<div class="container-fluid px-3 px-md-4 pt-3 pb-5" style="max-width:900px;">

    <div class="zawody-header">
        <div class="label">Aktywne zawody</div>
        <div class="nazwa" id="zawodyNazwa"><?php echo htmlspecialchars($nazwa_zawodow ?: 'Ładowanie...'); ?></div>
    </div>

    <div id="resultsContainer">
        <div class="text-center pt-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Ładowanie...</span>
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