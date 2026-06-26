<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stream — fromair.pl</title>
    <style>
        /* ── Reset ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            background: transparent;
            font-family: 'Arial Narrow', Arial, sans-serif;
            min-height: 100vh;
        }

        /* ── Wrapper ───────────────────────────────────────── */
        #streamWrap {
            padding: 20px 24px;
            background: transparent;
        }

        /* ── Brak danych ───────────────────────────────────── */
        .no-race {
            color: #4a5880;
            font-size: 1rem;
            text-align: center;
            padding: 60px 20px;
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        /* ── Kontenery tabelek ─────────────────────────────── */
        .tbl-tory, .tbl-miejsca {
            border-left: 5px solid #0d6efd;
            overflow: hidden;
        }

        /* ── Pasek brandu fromair.pl ───────────────────────── */
        .tbl-brand {
            background: #060b1c;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding: 6px 16px;
            border-bottom: 1px solid #0d3070;
        }
        .tbl-logo {
            height: 20px;
            width: auto;
            filter: brightness(1.2);
        }
        .tbl-brand-name {
            color: #4d9fff;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        /* ── Nagłówek wyścigu ──────────────────────────────── */
        .race-header {
            background: linear-gradient(135deg, #060d20 0%, #0a1432 100%);
            color: #e2ecff;
            padding: 16px 24px;
            font-size: clamp(.9rem, 1.8vw, 1.15rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            text-align: center;
            border-bottom: 2px solid #0d3070;
            line-height: 1.3;
        }

        /* ── Wiersz ────────────────────────────────────────── */
        .race-row {
            display: flex;
            align-items: stretch;
            border-bottom: 1px solid #0d1e42;
            background: #080f22;
            min-height: 54px;
        }
        .race-row:nth-child(even) { background: #0a1328; }
        .race-row:last-child { border-bottom: none; }

        /* ── Tabelka z torami ──────────────────────────────── */
        .tbl-tory .col-label {
            background: #060b1c;
            color: #4d9fff;
            padding: 14px 16px;
            width: 110px;
            flex-shrink: 0;
            text-align: center;
            font-size: clamp(.78rem, 1.4vw, .92rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            border-right: 1px solid #0d2650;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tbl-tory .col-team {
            padding: 14px 22px;
            flex: 1;
            font-size: clamp(.85rem, 1.6vw, 1.05rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #e2ecff;
            display: flex;
            align-items: center;
        }

        /* ── Tabelka z miejscami ───────────────────────────── */
        .tbl-miejsca .col-place {
            background: #060b1c;
            color: #4d9fff;
            padding: 14px 16px;
            width: 150px;
            flex-shrink: 0;
            text-align: center;
            font-size: clamp(.78rem, 1.4vw, .9rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            border-right: 1px solid #0d2650;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tbl-miejsca .col-team {
            padding: 14px 22px;
            flex: 1;
            font-size: clamp(.85rem, 1.6vw, 1.05rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #e2ecff;
            display: flex;
            align-items: center;
        }
        .tbl-miejsca .col-result {
            padding: 14px 20px;
            width: 155px;
            flex-shrink: 0;
            text-align: right;
            font-family: 'Courier New', monospace;
            font-size: clamp(.82rem, 1.5vw, .98rem);
            font-weight: 700;
            color: #4d9fff;
            border-left: 1px solid #0d2650;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            letter-spacing: .04em;
        }

        /* ── Fade przy zmianie treści ──────────────────────── */
        #streamWrap.fading  { opacity: 0; transition: opacity .12s; }
        #streamWrap.showing { opacity: 1; transition: opacity .25s; }
    </style>
</head>
<body>
<div id="streamWrap"></div>
<script>
    (function () {
        var wrap = document.getElementById('streamWrap');
        var lastHtml = null;

        function apply(html) {
            if (html === lastHtml) return;
            lastHtml = html;
            wrap.classList.add('fading');
            setTimeout(function () {
                wrap.innerHTML = html || '<div class="no-race">Nie wybrano wyścigu — ustaw wyścig w panelu operatora</div>';
                wrap.classList.remove('fading');
                wrap.classList.add('showing');
                setTimeout(function () { wrap.classList.remove('showing'); }, 280);
            }, 130);
        }

        function refresh() {
            fetch('ajax_stream.php')
                .then(function (r) { return r.json(); })
                .then(function (d) { apply(d.html || ''); })
                .catch(function () {});
        }

        refresh();
        setInterval(refresh, 2000);
    })();
</script>
</body>
</html>