<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Nota Miru — Wallboard</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; overflow: hidden; cursor: none; }
body {
    font-family: -apple-system, "Helvetica Neue", Arial, sans-serif;
    background: #0a0e1a;
    color: #e8ecf4;
    display: flex;
    flex-direction: column;
}
/* мягкий «дышащий» градиент на фоне */
.bg {
    position: fixed; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle at 30% 30%, rgba(64,120,255,.12), transparent 40%),
                radial-gradient(circle at 70% 70%, rgba(140,80,255,.10), transparent 45%);
    animation: drift 60s linear infinite;
    z-index: 0;
}
@keyframes drift {
    0%   { transform: rotate(0deg) scale(1); }
    50%  { transform: rotate(180deg) scale(1.15); }
    100% { transform: rotate(360deg) scale(1); }
}
.wrap { position: relative; z-index: 1; display: flex; flex-direction: column; height: 100%; padding: 2.2vh 2.2vw; }

header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2vh; }
.brand { font-size: 2.6vh; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #8fa8ff; }
.clock { font-size: 5.4vh; font-weight: 200; font-variant-numeric: tabular-nums; }
.date  { font-size: 2vh; color: #7c89a6; text-align: right; }

.grid { flex: 1; display: grid; grid-template-columns: repeat(4, 1fr); grid-template-rows: auto 1fr; gap: 1.6vh 1vw; min-height: 0; }

.card {
    background: rgba(255,255,255,.045);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 1.4vh;
    padding: 2vh 1.4vw;
    overflow: hidden;
}
.kpi .label { font-size: 1.7vh; color: #7c89a6; text-transform: uppercase; letter-spacing: .08em; margin-bottom: .8vh; }
.kpi .value { font-size: 6.4vh; font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums; }
.kpi .sub   { font-size: 1.8vh; margin-top: 1vh; color: #7c89a6; }
.kpi .up   { color: #5fdd9d; }
.kpi .down { color: #ff7d7d; }

.list-card { grid-column: span 2; display: flex; flex-direction: column; min-height: 0; }
.list-card h2 { font-size: 1.9vh; color: #8fa8ff; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 1.4vh; }
.list { flex: 1; display: flex; flex-direction: column; justify-content: space-around; min-height: 0; }
.row { display: flex; justify-content: space-between; align-items: baseline; gap: 1vw; padding: .5vh 0; border-bottom: 1px solid rgba(255,255,255,.05); }
.row:last-child { border-bottom: none; }
.row .t { font-size: 2.2vh; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.row .v { font-size: 2.2vh; font-weight: 600; color: #8fa8ff; font-variant-numeric: tabular-nums; flex-shrink: 0; }
.row .v.time { color: #7c89a6; font-weight: 400; font-size: 1.9vh; }

footer { display: flex; justify-content: space-between; margin-top: 1.6vh; font-size: 1.7vh; color: #5b6880; }
.status-ok::before  { content: "● "; color: #5fdd9d; }
.status-err::before { content: "● "; color: #ff7d7d; }
</style>
</head>
<body>
<div class="bg"></div>
<div class="wrap">
    <header>
        <div class="brand">Nota Miru — редакция</div>
        <div>
            <div class="clock" id="clock">--:--</div>
            <div class="date" id="date"></div>
        </div>
    </header>

    <div class="grid">
        <div class="card kpi">
            <div class="label">Посетители сегодня</div>
            <div class="value" id="visitors_today">—</div>
            <div class="sub" id="visitors_diff"></div>
        </div>
        <div class="card kpi">
            <div class="label">Просмотры статей</div>
            <div class="value" id="post_views_today">—</div>
            <div class="sub"><span id="post_views_week">—</span> за неделю</div>
        </div>
        <div class="card kpi">
            <div class="label">Публикаций сегодня</div>
            <div class="value" id="published_today">—</div>
            <div class="sub"><span id="pending_count">—</span> в очереди · <span id="published_total">—</span> всего</div>
        </div>
        <div class="card kpi">
            <div class="label">Клики по баннерам</div>
            <div class="value" id="banner_clicks_today">—</div>
            <div class="sub"><span id="not_found_today">—</span> ошибок 404 сегодня</div>
        </div>

        <div class="card list-card">
            <h2>Топ недели</h2>
            <div class="list" id="top_week"></div>
        </div>
        <div class="card list-card">
            <h2>Последние публикации</h2>
            <div class="list" id="latest"></div>
        </div>
    </div>

    <footer>
        <div id="conn" class="status-ok">данные обновлены</div>
        <div>notame.ru · обновление каждые 60 сек · <span id="generated_at">—</span></div>
    </footer>
</div>

<script>
(function () {
    var KEY = {!! json_encode($key) !!};
    var months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
    var days = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function tick() {
        var d = new Date();
        document.getElementById('clock').textContent = pad(d.getHours()) + ':' + pad(d.getMinutes());
        document.getElementById('date').textContent = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()];
    }
    setInterval(tick, 1000);
    tick();

    function fmt(n) {
        if (n === null || n === undefined) return '—';
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '\u2009');
    }

    function setText(id, v) {
        var el = document.getElementById(id);
        if (el) el.textContent = v;
    }

    function renderList(id, items, isTime) {
        var el = document.getElementById(id);
        if (!el) return;
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            html += '<div class="row"><div class="t">' +
                String(it.title).replace(/</g, '&lt;') +
                '</div><div class="v' + (isTime ? ' time' : '') + '">' +
                (isTime ? it.time : fmt(it.views)) + '</div></div>';
        }
        el.innerHTML = html;
    }

    function refresh() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/wallboard/data?key=' + encodeURIComponent(KEY) + '&_=' + Date.now(), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            var conn = document.getElementById('conn');
            if (xhr.status !== 200) {
                conn.className = 'status-err';
                conn.textContent = 'нет связи с сервером';
                return;
            }
            try {
                var d = JSON.parse(xhr.responseText);
                setText('visitors_today', fmt(d.visitors_today));
                var diff = d.visitors_today - d.visitors_yesterday;
                var diffEl = document.getElementById('visitors_diff');
                diffEl.innerHTML = (diff >= 0 ? '<span class="up">▲ ' : '<span class="down">▼ ') +
                    fmt(Math.abs(diff)) + '</span> ко вчера (' + fmt(d.visitors_yesterday) + ')';
                setText('post_views_today', fmt(d.post_views_today));
                setText('post_views_week', fmt(d.post_views_week));
                setText('published_today', fmt(d.published_today));
                setText('pending_count', fmt(d.pending_count));
                setText('published_total', fmt(d.published_total));
                setText('banner_clicks_today', fmt(d.banner_clicks_today));
                setText('not_found_today', fmt(d.not_found_today));
                setText('generated_at', d.generated_at);
                renderList('top_week', d.top_week || [], false);
                renderList('latest', d.latest || [], true);
                conn.className = 'status-ok';
                conn.textContent = 'данные обновлены';
            } catch (e) {
                conn.className = 'status-err';
                conn.textContent = 'ошибка данных';
            }
        };
        xhr.send();
    }
    refresh();
    setInterval(refresh, 60000);

    // страховка от деградации длинной сессии: полная перезагрузка раз в 12 часов
    setTimeout(function () { location.reload(); }, 12 * 3600 * 1000);
})();
</script>
</body>
</html>
