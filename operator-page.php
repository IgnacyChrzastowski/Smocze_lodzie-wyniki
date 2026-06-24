<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once "config.php";

// Wczytaj aktualne ustawienia streamu
function get_s(mysqli $c, string $k, string $def = ''): string {
    $res = $c->query("SELECT wartosc FROM ustawienia WHERE klucz = '" . $c->real_escape_string($k) . "' LIMIT 1");
    return ($res && $res->num_rows > 0) ? $res->fetch_assoc()['wartosc'] : $def;
}
$stream_zawody_id    = (int)get_s($conn, 'stream_zawody_id', '0');
$stream_wyscig_id    = (int)get_s($conn, 'stream_wyscig_id', '0');
$stream_tryb_tabelki = get_s($conn, 'stream_tryb_tabelki', 'tory');
$stream_tryb_belki   = get_s($conn, 'stream_tryb_belki',   'aktualny');

// Listy do selectów
$zawody = [];
$res = $conn->query("SELECT id, nazwa, status FROM zawody ORDER BY CASE status WHEN 'aktywne' THEN 0 ELSE 1 END, id DESC");
if ($res) { while ($r = $res->fetch_assoc()) $zawody[] = $r; }

$wyscigi = [];
if ($stream_zawody_id > 0) {
    $res = $conn->query("SELECT id, nazwa FROM wyscigi WHERE id_zawodow = $stream_zawody_id ORDER BY id ASC");
    if ($res) { while ($r = $res->fetch_assoc()) $wyscigi[] = $r; }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Operator — Panel sterowania streamem</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }

        /* ── Navbar ── */
        .navbar-brand { font-weight: 700; letter-spacing: .01em; }

        /* ── Karta operatora ── */
        .op-card {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,.07);
            overflow: hidden;
            max-width: 720px;
            margin: 0 auto;
        }
        .op-card-header {
            background: #111;
            color: #fff;
            padding: 18px 28px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .op-card-header .dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #c80000;
            animation: pulse 1.8s infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }

        /* ── Wiersze formularza ── */
        .op-row {
            display: flex;
            align-items: center;
            padding: 18px 28px;
            border-bottom: 1px solid #f0f0f0;
            gap: 16px;
        }
        .op-row:last-child { border-bottom: none; }
        .op-label {
            min-width: 180px;
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #666;
            flex-shrink: 0;
        }
        .op-control { flex: 1; }
        .op-control .form-select,
        .op-control .form-control {
            font-size: .95rem;
            border-color: #e0e0e0;
            border-radius: 8px;
            padding: 9px 14px;
        }
        .op-control .form-select:focus,
        .op-control .form-control:focus {
            border-color: #111;
            box-shadow: 0 0 0 3px rgba(0,0,0,.07);
        }

        /* ── Tryb belki — radio ── */
        .belki-group {
            display: flex;
            gap: 8px;
        }
        .belki-btn {
            flex: 1;
            text-align: center;
            padding: 10px 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #555;
            background: #fff;
            transition: all .18s;
            user-select: none;
        }
        .belki-btn:hover { border-color: #aaa; color: #111; }
        .belki-btn.active { background: #111; border-color: #111; color: #fff; }
        .belki-btn.active.red { background: #c80000; border-color: #c80000; }

        /* ── Status / link ── */
        .status-bar {
            padding: 12px 28px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .82rem;
        }
        .status-saved { color: #28a745; font-weight: 600; display: none; }
        .stream-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            background: #c80000;
            padding: 6px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            font-size: .82rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            transition: background .18s;
        }
        .stream-link:hover { background: #a00000; color: #fff; }

        /* ── Podgląd aktualnie wyświetlanego wyścigu ── */
        .preview-bar {
            padding: 14px 28px;
            background: #111;
            color: #aaa;
            font-size: .8rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            border-bottom: 1px solid #222;
        }
        .preview-bar strong { color: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-3 py-2 d-flex justify-content-between align-items-center">
    <a class="navbar-brand mb-0 fw-bold" href="#">⬤ Operator</a>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-light btn-sm" target="_blank">Wyniki</a>
        <a href="management.php" class="btn btn-outline-light btn-sm">Zarządzanie</a>
        <a href="stream-page.php" class="btn btn-danger btn-sm" target="_blank">Stream</a>
        <form method="post" action="logout.php" class="d-inline">
            <button class="btn btn-outline-secondary btn-sm" type="submit">Wyloguj</button>
        </form>
    </div>
</nav>

<div class="container-fluid px-3 px-md-4 pt-4 pb-5">

    <div class="op-card mt-2">
        <!-- Nagłówek karty -->
        <div class="op-card-header">
            <span class="dot"></span>
            Panel sterowania streamem
        </div>

        <!-- Podgląd aktualnego wyświetlania -->
        <div class="preview-bar" id="previewBar">
            Aktualnie wyświetlane: <strong id="previewText">ładowanie…</strong>
        </div>

        <!-- Wybierz zawody -->
        <div class="op-row">
            <div class="op-label">Wybierz zawody</div>
            <div class="op-control">
                <select class="form-select" id="selZawody">
                    <option value="">— wybierz zawody —</option>
                    <?php
                    $inAkt = $inArch = false;
                    foreach ($zawody as $z):
                        if ($z['status'] === 'aktywne' && !$inAkt) { echo '<optgroup label="🟢 Aktywne">'; $inAkt = true; }
                        if ($z['status'] === 'zarchiwizowane' && !$inArch) {
                            if ($inAkt) echo '</optgroup>';
                            echo '<optgroup label="📦 Archiwum">'; $inArch = true;
                        }
                        $sel = ((int)$z['id'] === $stream_zawody_id) ? 'selected' : '';
                        echo '<option value="' . (int)$z['id'] . '" ' . $sel . '>' . htmlspecialchars($z['nazwa']) . '</option>';
                    endforeach;
                    if ($inAkt || $inArch) echo '</optgroup>';
                    ?>
                </select>
            </div>
        </div>

        <!-- Wybierz wyścig -->
        <div class="op-row">
            <div class="op-label">Wybierz wyścig</div>
            <div class="op-control">
                <select class="form-select" id="selWyscig">
                    <option value="">— wybierz wyścig —</option>
                    <?php foreach ($wyscigi as $w): ?>
                        <option value="<?php echo (int)$w['id']; ?>" <?php echo ((int)$w['id'] === $stream_wyscig_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($w['nazwa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Tryb tabelki -->
        <div class="op-row">
            <div class="op-label">Tryb tabelki</div>
            <div class="op-control">
                <select class="form-select" id="selTrybTabelki">
                    <option value="tory"    <?php echo ($stream_tryb_tabelki === 'tory')    ? 'selected' : ''; ?>>Tabelka z torami</option>
                    <option value="miejsca" <?php echo ($stream_tryb_tabelki === 'miejsca') ? 'selected' : ''; ?>>Tabelka z miejscami</option>
                </select>
            </div>
        </div>

        <!-- Tryb belki -->
        <div class="op-row">
            <div class="op-label">Tryb belki</div>
            <div class="op-control">
                <div class="belki-group" id="belkiGroup">
                    <div class="belki-btn <?php echo $stream_tryb_belki === 'poprzedni' ? 'active' : ''; ?>" data-val="poprzedni">Poprzedni</div>
                    <div class="belki-btn <?php echo $stream_tryb_belki === 'aktualny'  ? 'active red' : ''; ?>" data-val="aktualny">Aktualny</div>
                    <div class="belki-btn <?php echo $stream_tryb_belki === 'nastepny'  ? 'active' : ''; ?>" data-val="nastepny">Następny</div>
                </div>
            </div>
        </div>

        <!-- Status i link do streamu -->
        <div class="status-bar">
            <span class="status-saved" id="statusSaved">✓ Zapisano</span>
            <a href="stream-page.php" target="_blank" class="stream-link">▶ Otwórz stream</a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var selZawody      = document.getElementById('selZawody');
        var selWyscig      = document.getElementById('selWyscig');
        var selTrybTabelki = document.getElementById('selTrybTabelki');
        var belkiGroup     = document.getElementById('belkiGroup');
        var statusSaved    = document.getElementById('statusSaved');
        var previewText    = document.getElementById('previewText');

        var currentBelki = '<?php echo htmlspecialchars($stream_tryb_belki); ?>';
        var saveTimer    = null;

        // ── Zapis ustawień ────────────────────────────────────────────────────
        function save(extraData) {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () {
                var fd = new FormData();
                fd.append('stream_zawody_id',    selZawody.value);
                fd.append('stream_wyscig_id',    selWyscig.value);
                fd.append('stream_tryb_tabelki', selTrybTabelki.value);
                fd.append('stream_tryb_belki',   currentBelki);
                if (extraData) for (var k in extraData) fd.append(k, extraData[k]);

                fetch('ajax_stream.php', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) flashSaved();
                        if (data.wyscigi) rebuildWyscigi(data.wyscigi);
                        updatePreview();
                    })
                    .catch(function (e) { console.error('Save error:', e); });
            }, 200);
        }

        function flashSaved() {
            statusSaved.style.display = 'inline';
            clearTimeout(window._savedTimer);
            window._savedTimer = setTimeout(function () { statusSaved.style.display = 'none'; }, 2000);
        }

        // ── Przebuduj listę wyścigów ──────────────────────────────────────────
        function rebuildWyscigi(list) {
            var cur = selWyscig.value;
            selWyscig.innerHTML = '<option value="">— wybierz wyścig —</option>';
            list.forEach(function (w) {
                var opt = document.createElement('option');
                opt.value = w.id;
                opt.textContent = w.nazwa;
                if (String(w.id) === cur) opt.selected = true;
                selWyscig.appendChild(opt);
            });
        }

        // ── Podgląd tekstu ────────────────────────────────────────────────────
        function updatePreview() {
            fetch('ajax_stream.php')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.empty || !data.html) {
                        previewText.textContent = 'brak';
                        return;
                    }
                    // Wyciągnij nagłówek z HTML
                    var tmp = document.createElement('div');
                    tmp.innerHTML = data.html;
                    var hdr = tmp.querySelector('.race-header');
                    previewText.textContent = hdr ? hdr.textContent.trim() : '—';
                })
                .catch(function () {});
        }

        // ── Tryb belki — kliknięcie ───────────────────────────────────────────
        belkiGroup.querySelectorAll('.belki-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                belkiGroup.querySelectorAll('.belki-btn').forEach(function (b) {
                    b.classList.remove('active', 'red');
                });
                currentBelki = btn.getAttribute('data-val');
                btn.classList.add('active');
                if (currentBelki === 'aktualny') btn.classList.add('red');
                save();
            });
        });

        // ── Eventy na selectach ───────────────────────────────────────────────
        selZawody.addEventListener('change', function () {
            selWyscig.innerHTML = '<option value="">— wybierz wyścig —</option>';
            save({ stream_zawody_id: selZawody.value });
        });

        selWyscig.addEventListener('change', function () { save(); });
        selTrybTabelki.addEventListener('change', function () { save(); });

        // ── Init: wczytaj podgląd ─────────────────────────────────────────────
        updatePreview();
        setInterval(updatePreview, 5000);
    })();
</script>
</body>
</html>