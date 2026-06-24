<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stream</title>
    <style>
        /* ── Reset & base ──────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            background: transparent;
            color: #f0f0f0;
            font-family: 'Arial Narrow', Arial, sans-serif;
            min-height: 100vh;
        }

        /* ── Wrapper ───────────────────────────────────── */
        #streamWrap {
            padding: 24px 32px;
            max-width: 1400px;
            margin: 0 auto;
            background: transparent;
        }

        /* ── Brak danych ───────────────────────────────── */
        .no-race {
            text-align: center;
            color: #999;
            font-size: 1.1rem;
            padding: 60px 20px;
            text-transform: uppercase;
            letter-spacing: .12em;
        }

        /* ── Tabelki ───────────────────────────────────── */
        .tbl-tory, .tbl-miejsca {
            border-left: 5px solid #c80000;
            overflow: hidden;
        }

        /* ── Nagłówek wyścigu ──────────────────────────── */
        .race-header {
            background: #0d0d0d;
            color: #f0f0f0;
            padding: 16px 28px;
            font-size: clamp(.9rem, 1.8vw, 1.2rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            text-align: center;
            border-bottom: 2px solid #2a2a2a;
            line-height: 1.3;
        }

        /* ── Wiersz ────────────────────────────────────── */
        .race-row {
            display: flex;
            align-items: stretch;
            border-bottom: 1px solid #2a2a2a;
            background: #1f1f1f;
            min-height: 56px;
        }
        .race-row:nth-child(even) { background: #252525; }
        .race-row:last-child { border-bottom: none; }

        /* ── Tabelka z torami ──────────────────────────── */
        .tbl-tory .col-label {
            background: #131313;
            color: #c8c8c8;
            padding: 14px 18px;
            width: 110px;
            flex-shrink: 0;
            text-align: center;
            font-size: clamp(.8rem, 1.4vw, .95rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            border-right: 1px solid #2e2e2e;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tbl-tory .col-team {
            padding: 14px 24px;
            flex: 1;
            font-size: clamp(.85rem, 1.6vw, 1.05rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            display: flex;
            align-items: center;
        }

        /* ── Tabelka z miejscami ───────────────────────── */
        .tbl-miejsca .col-place {
            background: #131313;
            color: #c8c8c8;
            padding: 14px 18px;
            width: 150px;
            flex-shrink: 0;
            text-align: center;
            font-size: clamp(.8rem, 1.4vw, .95rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            border-right: 1px solid #2e2e2e;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .tbl-miejsca .col-team {
            padding: 14px 24px;
            flex: 1;
            font-size: clamp(.85rem, 1.6vw, 1.05rem);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            display: flex;
            align-items: center;
        }
        .tbl-miejsca .col-result {
            padding: 14px 22px;
            width: 160px;
            flex-shrink: 0;
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            font-size: clamp(.85rem, 1.5vw, 1rem);
            font-weight: 700;
            color: #8ac4ff;
            border-left: 1px solid #2e2e2e;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            letter-spacing: .04em;
        }

        /* ── Fade-in przy zmianie ──────────────────────── */
        #streamWrap.fade-out { opacity: 0; transition: opacity .12s ease; }
        #streamWrap.fade-in  { opacity: 1; transition: opacity .25s ease; }

        /* ── Scrollbar ciemny ─────────────────────────── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #333; }
    </style>
</head>
<body>
<div id="streamWrap"></div>
<script>
    (function () {
        var wrap = document.getElementById('streamWrap');
        var lastHtml = null;

        function applyHtml(html) {
            if (html === lastHtml) return;
            lastHtml = html;
            wrap.classList.add('fade-out');
            setTimeout(function () {
                wrap.innerHTML = html || '<div class="no-race">Nie wybrano wyścigu — ustaw wyścig w panelu operatora</div>';
                wrap.classList.remove('fade-out');
                wrap.classList.add('fade-in');
                setTimeout(function () { wrap.classList.remove('fade-in'); }, 300);
            }, 130);
        }

        function refresh() {
            fetch('ajax_stream.php')
                .then(function (r) { return r.json(); })
                .then(function (data) { applyHtml(data.html || ''); })
                .catch(function () {});
        }

        refresh();
        setInterval(refresh, 2000);
    })();
</script>
</body>
</html>