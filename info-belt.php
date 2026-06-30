<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Belka informacyjna — fromair.pl</title>
    <style>
        /* ── Reset ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            background: transparent;
            font-family: 'Arial Narrow', Arial, sans-serif;
            overflow: hidden;
        }

        /* ── Wrapper ───────────────────────────────────────── */
        #beltWrap {
            padding: 14px 18px;
            background: transparent;
        }

        /* ── Belka ─────────────────────────────────────────── */
        .belt {
            display: flex;
            align-items: stretch;
            min-height: 62px;
            overflow: hidden;
            border-left: 5px solid #0d6efd;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        /* ── Etykieta trybu ────────────────────────────────── */
        .belt-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px;
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: #fff;
            white-space: nowrap;
            min-width: 112px;
            flex-shrink: 0;
        }
        .belt--aktualny  .belt-label { background: rgba(13, 110, 253, 0.78); }
        .belt--nastepny  .belt-label { background: rgba(6, 11, 28, 0.62); border-right: 3px solid #0d6efd; color: #4d9fff; }
        .belt--poprzedni .belt-label { background: rgba(12, 21, 48, 0.62); color: #7aa8e0; }

        /* ── Treść belki ───────────────────────────────────── */
        .belt-body {
            background: linear-gradient(90deg, rgba(10,18,48,0.88) 0%, rgba(6,13,30,0.88) 100%);
            flex: 1;
            display: flex;
            align-items: center;
            padding: 0 22px;
            gap: 18px;
            overflow: hidden;
        }

        /* ── Nazwa wyścigu ─────────────────────────────────── */
        .belt-name {
            color: #e2ecff;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* ── Opis wyścigu ──────────────────────────────────── */
        .belt-opis {
            color: #8ab4e8;
            font-size: .76rem;
            font-weight: 400;
            letter-spacing: .04em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 260px;
            flex-shrink: 1;
        }

        /* ── Separator ─────────────────────────────────────── */
        .belt-sep {
            width: 1px;
            height: 28px;
            background: #1a3060;
            flex-shrink: 0;
        }

        /* ── Tagi ──────────────────────────────────────────── */
        .belt-tags {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
            overflow: hidden;
        }
        .belt-tag {
            background: rgba(13, 30, 66, 0.60);
            color: #7ab8ff;
            padding: 4px 11px;
            font-size: .76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
            border: 1px solid rgba(26, 50, 100, 0.55);
        }

        /* ── Brand fromair.pl (prawa strona) ───────────────── */
        .belt-brand {
            background: rgba(6, 11, 28, 0.62);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 18px;
            flex-shrink: 0;
            text-decoration: none;
            border-left: 1px solid rgba(13, 38, 80, 0.55);
            min-width: 130px;
            justify-content: center;
        }
        .belt-brand img {
            height: 22px;
            width: auto;
            filter: brightness(1.15);
        }
        .belt-brand span {
            color: #4d9fff;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* ── Brak danych ───────────────────────────────────── */
        .no-data {
            color: #2a3a60;
            font-size: .9rem;
            text-align: center;
            padding: 18px;
            text-transform: uppercase;
            letter-spacing: .1em;
        }

        /* ── Fade ──────────────────────────────────────────── */
        #beltWrap.fading  { opacity: 0; transition: opacity .1s; }
        #beltWrap.showing { opacity: 1; transition: opacity .22s; }
    </style>
</head>
<body>
<div id="beltWrap"></div>
<script>
    (function () {
        var wrap     = document.getElementById('beltWrap');
        var lastHtml = null;

        function apply(html) {
            if (html === lastHtml) return;
            lastHtml = html;
            wrap.classList.add('fading');
            setTimeout(function () {
                wrap.innerHTML = html || '<div class="no-data">Nie wybrano wyścigu</div>';
                wrap.classList.remove('fading');
                wrap.classList.add('showing');
                setTimeout(function () { wrap.classList.remove('showing'); }, 250);
            }, 110);
        }

        function refresh() {
            fetch('ajax_belt.php')
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