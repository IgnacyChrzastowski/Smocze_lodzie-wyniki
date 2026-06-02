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
        $nazwa_zawodow = $row['nazwa'];
        $res->free();
    }
}

// Pobierz wszystkie zawody do listy rozwijalnej
$lista_zawodow = [];
$res_lista = $conn->query("SELECT id, nazwa, status FROM zawody ORDER BY status ASC, id DESC");
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
        /* Gdy przeglądamy zakończone zawody — szara kropka bez animacji */
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
            transition: background 0.3s;
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
        .zawody-header .status-badge {
            display: inline-block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 2px 10px;
            border-radius: 20px;
            margin-top: 6px;
        }
        .status-badge.aktywne   { background: rgba(255,255,255,.25); color: #fff; }
        .status-badge.zakonczone { background: rgba(0,0,0,.2); color: #e0e0e0; }

        /* ── Select zawodów ── */
        .zawody-select-wrap {
            margin-bottom: 18px;
        }
        .zawody-select-wrap select {
            border-radius: 10px;
            font-size: clamp(0.95rem, 2.3vw, 1.05rem);
            padding: 8px 14px;
            border: 1.5px solid #c5d3e8;
            background: #fff;
            color: #212529;
            width: 100%;
            cursor: pointer;
        }
        .zawody-select-wrap select:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,.15);
        }
        .zawody-select-wrap .select-label {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6c757d;
            margin-bottom: 6px;
        }
        optgroup {
            font-weight: 700;
            color: #495057;
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
            <span class="refresh-dot" id="refreshDot"></span>
            <span id="refreshLabel">live</span>
        </div>
    </div>
</nav>

<div class="container-fluid px-3 px-md-4 pt-3 pb-5" style="max-width:900px;">

    <!-- Lista rozwijalna zawodów -->
    <div class="zawody-select-wrap">
        <div class="select-label">Wybierz zawody</div>
        <select id="zawodySelect">
            <?php
            $activeGroup = false;
            $archivedGroup = false;
            foreach ($lista_zawodow as $z):
                if ($z['status'] === 'aktywne' && !$activeGroup):
                    echo '<optgroup label="🟢 Aktywne zawody">';
                    $activeGroup = true;
                elseif ($z['status'] === 'zakończone' && !$archivedGroup):
                    if ($activeGroup) echo '</optgroup>';
                    echo '<optgroup label="📁 Zakończone zawody">';
                    $archivedGroup = true;
                endif;
                $selected = ((int)$z['id'] === $zawody_id) ? 'selected' : '';
                echo '<option value="' . (int)$z['id'] . '" data-status="' . htmlspecialchars($z['status']) . '" ' . $selected . '>' . htmlspecialchars($z['nazwa']) . '</option>';
            endforeach;
            if ($activeGroup || $archivedGroup) echo '</optgroup>';
            ?>
        </select>
    </div>

    <!-- Nagłówek aktywnych zawodów -->
    <div class="zawody-header" id="zawodyHeader">
        <div class="label" id="zawodyLabel">Aktywne zawody</div>
        <div class="nazwa" id="zawodyNazwa"><?php echo htmlspecialchars($nazwa_zawodow ?: 'Ładowanie...'); ?></div>
        <span class="status-badge aktywne" id="zawodyStatusBadge">● Aktywne</span>
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

        // Aktualnie wybrany ID zawodów
        let currentZawodyId   = <?php echo (int)$zawody_id; ?>;
        // Czy użytkownik ręcznie wybrał zawody (nie śledzimy zmian live)
        let userOverride      = false;
        // ID aktywnych zawodów wg bazy (do live update)
        let activeZawodyId    = <?php echo (int)$zawody_id; ?>;

        let autoRefreshTimer  = null;

        // ── Aktualizacja nagłówka ──────────────────────────────────────
        function updateHeader(nazwa, status) {
            zawodyNazwaEl.textContent = nazwa || 'Brak nazwy';
            if (status === 'aktywne') {
                zawodyLabelEl.textContent = 'Aktywne zawody';
                zawodyHeaderEl.classList.remove('archived');
                zawodyStatusEl.textContent = '● Aktywne';
                zawodyStatusEl.className = 'status-badge aktywne';
                refreshDot.classList.remove('archived');
                refreshLabel.textContent = 'live';
            } else {
                zawodyLabelEl.textContent = 'Zakończone zawody';
                zawodyHeaderEl.classList.add('archived');
                zawodyStatusEl.textContent = '✓ Zakończone';
                zawodyStatusEl.className = 'status-badge zakonczone';
                refreshDot.classList.add('archived');
                refreshLabel.textContent = 'archiwum';
            }
        }

        // ── Ładowanie wyników ──────────────────────────────────────────
        function loadResults(zawodyId) {
            const url = 'ajax_get_results.php?zawody_id=' + zawodyId;
            fetch(url)
                .then(r => r.text())
                .then(html => {
                    resultsContainer.innerHTML = html;
                })
                .catch(err => {
                    resultsContainer.innerHTML = '<div class="alert alert-danger">Błąd: ' + err.message + '</div>';
                    console.error(err);
                });
        }

        // ── Aktualizacja listy select + wykrycie zmian aktywnych zawodów ──
        function updateSelectOptions(lista) {
            // Zaktualizuj opcje jeśli długość listy się zmieniła (nowe zawody)
            // W prostej implementacji: aktualizujemy tylko wartości data-status (nie przebudowujemy całego selecta)
            lista.forEach(function(z) {
                const opt = zawodySelect.querySelector('option[value="' + z.id + '"]');
                if (opt) {
                    opt.dataset.status = z.status;
                }
                // Gdyby pojawiły się nowe zawody — nie obsługujemy dynamicznego dodawania optgroup (wymaga przeładowania strony)
            });
        }

        // ── Live poll (tylko gdy user nie wybrał ręcznie) ──────────────
        function pollSettings() {
            fetch('ajax_get_settings.php')
                .then(r => r.json())
                .then(data => {
                    // Aktualizuj listę opcji
                    if (data.lista_zawodow) {
                        updateSelectOptions(data.lista_zawodow);
                    }

                    // Jeśli użytkownik ręcznie wybrał zawody — nie nadpisujemy jego wyboru
                    if (userOverride) return;

                    if (data.zawody_id && data.zawody_id !== activeZawodyId) {
                        activeZawodyId  = data.zawody_id;
                        currentZawodyId = data.zawody_id;

                        // Zaktualizuj select
                        zawodySelect.value = String(data.zawody_id);
                        const selOpt = zawodySelect.querySelector('option[value="' + data.zawody_id + '"]');
                        const status = selOpt ? (selOpt.dataset.status || 'aktywne') : 'aktywne';
                        updateHeader(data.nazwa_zawodow, status);
                        loadResults(currentZawodyId);
                    }
                })
                .catch(err => console.error('Settings poll error:', err));
        }

        // ── Zmiana zawodów przez użytkownika ──────────────────────────
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

        // ── Start ──────────────────────────────────────────────────────
        // Ustal status aktualnie wybranych zawodów
        (function initHeader() {
            const initOpt = zawodySelect.querySelector('option[value="' + currentZawodyId + '"]');
            const initStatus = initOpt ? (initOpt.dataset.status || 'aktywne') : 'aktywne';
            const initNazwa  = '<?php echo addslashes(htmlspecialchars($nazwa_zawodow)); ?>';
            updateHeader(initNazwa, initStatus);
        })();

        loadResults(currentZawodyId);

        // Poll co 5 sekund
        setInterval(function() {
            pollSettings();
            // Odświeżaj wyniki tylko gdy nie jest archiwum (lub aktywne zawody)
            if (!userOverride) {
                loadResults(currentZawodyId);
            }
        }, 5000);

    })();
</script>

</body>
</html>
