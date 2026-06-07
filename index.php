<?php

require_once "config.php";

$zawody_id      = 0;
$nazwa_zawodow  = '';
$status_zawodow = '';

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
    $res = $conn->query("SELECT nazwa, status FROM zawody WHERE id = " . (int)$zawody_id);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $nazwa_zawodow  = $row['nazwa'];
        $status_zawodow = $row['status'];
        $res->free();
    }
}

// Wszystkie zawody do listy rozwijalnej
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
        $lista_zawodow[] = $row;
    }
    $res_lista->free();
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

        /* ── Kompaktowy select w navbar ── */
        .navbar-zawody-select {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .navbar-zawody-select .select-label {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #b8c5d6;
            white-space: nowrap;
            margin: 0;
        }
        .navbar-zawody-select select {
            border-radius: 6px;
            font-size: 0.75rem;
            padding: 4px 8px;
            border: 1px solid rgba(255,255,255,.3);
            background: rgba(255,255,255,.12);
            color: #fff;
            cursor: pointer;
            min-width: 160px;
            transition: background 0.2s;
        }
        .navbar-zawody-select select:hover {
            background: rgba(255,255,255,.2);
        }
        .navbar-zawody-select select:focus {
            outline: none;
            background: rgba(255,255,255,.25);
            border-color: rgba(255,255,255,.5);
            box-shadow: none;
        }
        .navbar-zawody-select select option {
            background: #1a1a2e;
            color: #fff;
        }

        .refresh-pill {
            font-size: 0.78rem;
            color: #d0e0ff;
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
        .refresh-dot.archived {
            background: #adb5bd;
            animation: none;
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
            transition: background 0.4s, box-shadow 0.4s;
        }
        .zawody-header.archived {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            box-shadow: 0 4px 18px rgba(73,80,87,.25);
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
        .zawody-header .header-bottom {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .status-badge {
            display: inline-block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .status-badge.aktywne        { background: rgba(255,255,255,.25); color: #fff; }
        .status-badge.zarchiwizowane { background: rgba(0,0,0,.2); color: #e0e0e0; }

        /* ── Przycisk "Wróć do aktywnych" ── */
        .btn-back-active {
            font-size: .78rem;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 20px;
            background: rgba(255,255,255,.18);
            color: #fff;
            border: 1.5px solid rgba(255,255,255,.5);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-back-active:hover {
            background: rgba(255,255,255,.32);
            color: #fff;
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

        .team-name {
            font-weight: 600;
            font-size: clamp(1rem, 2.6vw, 1.15rem);
            line-height: 1.3;
        }
        .result-time {
            font-family: 'Courier New', Courier, monospace;
            font-size: clamp(1rem, 2.6vw, 1.15rem);
            font-weight: 700;
            color: #0d6efd;
            white-space: nowrap;
        }
        .result-time.empty { color: #adb5bd; font-weight: 400; font-style: italic; font-family: inherit; }
        .lane-badge {
            display: inline-block;
            background: #e8f0fe;
            color: #3a5fc8;
            border-radius: 6px;
            padding: 2px 9px;
            font-size: .8rem;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #adb5bd;
            font-size: 1rem;
        }

        /* --- wyrównanie brand + skalowanie logo względem tekstu --- */
        .navbar { align-items: center; }

        .navbar .navbar-brand {
            display: inline-flex;
            align-items: center;
            height: 100%;
            line-height: 1;
            margin: 0;
            padding-top: 0;
            padding-bottom: 0;
        }

        /* obrazek fromair - wysokość w em, skalowana razem z rozmiarem fontu */
        .fromair-logo {
            display: block;
            height: 1.15em;   /* dopasowuje logo do wysokości tekstu; zmień tutaj jeśli trzeba */
            width: auto;
            border-radius: 6px;
            vertical-align: middle;
            margin-left: 6px;
        }

        /* upewniamy się, że link z logo ma centrowanie */
        .navbar .d-flex.align-items-center a,
        .navbar .d-flex.align-items-center > a {
            display: inline-flex;
            align-items: center;
        }

        /* mniejsze selecty na bardzo wąskich ekranach, żeby nie wypychały logo */
        .navbar-zawody-select select {
            min-width: 140px;
        }

        @media (max-width: 576px) {
            .navbar-zawody-select select { min-width: 110px; }
            .fromair-logo { height: 1.0em; }   /* mniejsze logo na bardzo wąskich ekranach */
            .navbar .navbar-brand { font-size: clamp(1rem, 4vw, 1.2rem); }
        }

        #resultsContainer { min-height: 200px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary px-3 py-2 d-flex justify-content-between align-items-center">
    <a class="navbar-brand mb-0" href="index.php">Smocze Łodzie</a>

    <div class="d-flex align-items-center gap-3">
        <!-- Select zawodów w navbar -->
        <div class="navbar-zawody-select">
            <div class="select-label">Zawody:</div>
            <select id="zawodySelect">
                <?php
                $inActive    = false;
                $inArchived  = false;
                foreach ($lista_zawodow as $z):
                    if ($z['status'] === 'aktywne' && !$inActive) {
                        echo '<optgroup label="🟢 Aktywne">';
                        $inActive = true;
                    } elseif ($z['status'] === 'zarchiwizowane' && !$inArchived) {
                        if ($inActive) echo '</optgroup>';
                        echo '<optgroup label="📦 Archiwum">';
                        $inArchived = true;
                    }
                    $selected = ((int)$z['id'] === $zawody_id) ? 'selected' : '';
                    echo '<option value="' . (int)$z['id'] . '" data-status="' . htmlspecialchars($z['status']) . '" ' . $selected . '>'
                            . htmlspecialchars($z['nazwa']) . '</option>';
                endforeach;
                if ($inActive || $inArchived) echo '</optgroup>';
                ?>
            </select>
        </div>

        <a href="https://fromair.pl/" target="_blank" rel="noopener"
           class="d-flex align-items-center gap-2 text-decoration-none"
           title="Fromair.pl - Twoje Wydarzenie W Lepszym Wymiarze">
            <img src="https://fromair.pl/wp-content/uploads/2025/02/Logo_bez_ta_Obszar_roboczy_1-120x120.png"
                 alt="Fromair.pl" class="fromair-logo">
            <span style="color:#fff; font-size: clamp(0.7rem, 2vw, 0.85rem); line-height:1.25; max-width:110px;">
                <strong>fromair.pl</strong>
            </span>
        </a>
        <div class="refresh-pill">
            <span class="refresh-dot" id="refreshDot"></span>
            <span id="refreshLabel">live</span>
        </div>
    </div>
</nav>

<div class="container-fluid px-3 px-md-4 pt-3 pb-5" style="max-width:900px;">

    <!-- Nagłówek zawodów -->
    <div class="zawody-header<?php echo ($status_zawodow === 'zarchiwizowane') ? ' archived' : ''; ?>" id="zawodyHeader">
        <div class="label" id="zawodyLabel"><?php echo ($status_zawodow === 'zarchiwizowane') ? 'Zarchiwizowane zawody' : 'Aktywne zawody'; ?></div>
        <div class="nazwa" id="zawodyNazwa"><?php echo htmlspecialchars($nazwa_zawodow ?: 'Ładowanie...'); ?></div>
        <div class="header-bottom">
            <span class="status-badge <?php echo ($status_zawodow === 'zarchiwizowane') ? 'zarchiwizowane' : 'aktywne'; ?>" id="zawodyStatusBadge">
                <?php echo ($status_zawodow === 'zarchiwizowane') ? '📦 Zarchiwizowane' : '● Aktywne'; ?>
            </span>
            <!-- Przycisk "Wróć do aktywnych" — widoczny tylko gdy oglądamy archiwum -->
            <button class="btn-back-active" id="btnBackToActive" style="<?php echo ($status_zawodow !== 'zarchiwizowane') ? 'display:none' : ''; ?>">
                ↩ Wróć do aktywnych zawodów
            </button>
        </div>
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
        const zawodyNazwaEl    = document.getElementById('zawodyNazwa');
        const zawodyLabelEl    = document.getElementById('zawodyLabel');
        const zawodyHeaderEl   = document.getElementById('zawodyHeader');
        const zawodyStatusEl   = document.getElementById('zawodyStatusBadge');
        const refreshDot       = document.getElementById('refreshDot');
        const refreshLabel     = document.getElementById('refreshLabel');
        const zawodySelect     = document.getElementById('zawodySelect');
        const btnBackToActive  = document.getElementById('btnBackToActive');

        let currentZawodyId = <?php echo (int)$zawody_id; ?>;
        let activeZawodyId  = <?php echo (int)$zawody_id; ?>;
        // userOverride = true gdy użytkownik ręcznie wybrał inne zawody niż aktywne
        let userOverride    = false;

        // ── Aktualizacja UI nagłówka ─────────────────────────────────────
        function updateHeader(nazwa, status) {
            zawodyNazwaEl.textContent = nazwa || 'Brak nazwy';
            const isArchived = (status === 'zarchiwizowane');

            zawodyLabelEl.textContent = isArchived ? 'Zarchiwizowane zawody' : 'Aktywne zawody';
            zawodyHeaderEl.classList.toggle('archived', isArchived);
            zawodyStatusEl.textContent  = isArchived ? '📦 Zarchiwizowane' : '● Aktywne';
            zawodyStatusEl.className    = 'status-badge ' + (isArchived ? 'zarchiwizowane' : 'aktywne');
            refreshDot.classList.toggle('archived', isArchived);
            refreshLabel.textContent    = isArchived ? 'archiwum' : 'live';

            // Przycisk powrotu — widoczny tylko przy archiwum
            btnBackToActive.style.display = isArchived ? '' : 'none';
        }

        // ── Ładowanie wyników ────────────────────────────────────────────
        function loadResults(zawodyId) {
            fetch('ajax_get_results.php?zawody_id=' + zawodyId)
                .then(r => r.text())
                .then(html => { resultsContainer.innerHTML = html; })
                .catch(err => {
                    resultsContainer.innerHTML = '<div class="alert alert-danger">Błąd: ' + err.message + '</div>';
                    console.error(err);
                });
        }

        // ── Aktualizacja opcji w <select> bez przebudowy całego drzewa ───
        function updateSelectOptions(lista) {
            lista.forEach(function(z) {
                const opt = zawodySelect.querySelector('option[value="' + z.id + '"]');
                if (opt) opt.dataset.status = z.status;
            });
        }

        // ── Powrót do aktywnych zawodów ──────────────────────────────────
        btnBackToActive.addEventListener('click', function() {
            if (!activeZawodyId) return;
            userOverride = false;
            currentZawodyId = activeZawodyId;
            zawodySelect.value = String(activeZawodyId);
            const opt = zawodySelect.querySelector('option[value="' + activeZawodyId + '"]');
            const status = opt ? (opt.dataset.status || 'aktywne') : 'aktywne';
            const nazwa  = opt ? opt.textContent.trim() : '';
            updateHeader(nazwa, status);
            loadResults(activeZawodyId);
        });

        // ── Zmiana zawodów przez użytkownika ─────────────────────────────
        zawodySelect.addEventListener('change', function() {
            const selId  = parseInt(this.value, 10);
            const selOpt = this.options[this.selectedIndex];
            const status = selOpt ? (selOpt.dataset.status || 'aktywne') : 'aktywne';
            const nazwa  = selOpt ? selOpt.textContent.trim() : '';

            currentZawodyId = selId;
            userOverride    = (selId !== activeZawodyId);

            updateHeader(nazwa, status);
            loadResults(selId);
        });

        // ── Live polling ─────────────────────────────────────────────────
        function pollSettings() {
            fetch('ajax_get_settings.php')
                .then(r => r.json())
                .then(data => {
                    if (data.lista_zawodow) updateSelectOptions(data.lista_zawodow);

                    // Nie nadpisuj wyboru użytkownika
                    if (userOverride) return;

                    if (data.zawody_id && data.zawody_id !== activeZawodyId) {
                        activeZawodyId  = data.zawody_id;
                        currentZawodyId = data.zawody_id;
                        zawodySelect.value = String(data.zawody_id);
                        updateHeader(data.nazwa_zawodow, data.status_zawodow || 'aktywne');
                        loadResults(currentZawodyId);
                    }
                })
                .catch(err => console.error('Poll error:', err));
        }

        // ── Init ─────────────────────────────────────────────────────────
        (function() {
            const initOpt    = zawodySelect.querySelector('option[value="' + currentZawodyId + '"]');
            const initStatus = initOpt ? (initOpt.dataset.status || 'aktywne') : 'aktywne';
            const initNazwa  = '<?php echo addslashes(htmlspecialchars($nazwa_zawodow)); ?>';
            updateHeader(initNazwa, initStatus);
        })();

        loadResults(currentZawodyId);

        setInterval(function() {
            pollSettings();
            if (!userOverride) loadResults(currentZawodyId);
        }, 5000);

    })();
</script>

</body>
</html>