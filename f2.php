<?php
declare(strict_types=1);
$year = (int) gmdate('Y');
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>F2 — Live и резултати</title>
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/f2.css">
</head>
<body class="has-fixed-footer">
  <div class="wrap legal-page">
    <header class="f2-header">
      <h1>Formula 2 <span>Live</span></h1>
      <div class="f2-tools">
        <label for="f2Year">Сезон</label>
        <select id="f2Year"></select>
        <a class="back" href="index.php">← F1 Live</a>
      </div>
    </header>

    <div id="f2Status" class="f2-info">Зареждане на F2 данни…</div>

    <section class="f2-links" id="f2Links" hidden>
      <h2>Официално F2 класиране</h2>
      <div class="f2-link-row" id="f2OfficialLinks"></div>
      <p class="muted">Официалната таблица (пилоти/отбори) е от FIA Formula 2. Тук е добавен и free live feed за събитията.</p>
    </section>

    <section class="f2-standings" id="f2Standings" hidden>
      <h2>Генерално класиране</h2>
      <div class="f2-standings-grid">
        <div class="f2-standings-panel">
          <h3>Пилоти</h3>
          <div class="f2-table-wrap">
            <table class="f2-table" id="f2DriversTable"></table>
          </div>
        </div>
        <div class="f2-standings-panel">
          <h3>Отбори</h3>
          <div class="f2-table-wrap">
            <table class="f2-table" id="f2TeamsTable"></table>
          </div>
        </div>
      </div>
      <p class="muted">Забележка: таблиците се взимат от официалните страници на FIA Formula 2.</p>
    </section>

    <div class="f2-grid">
      <section class="f2-panel">
        <h2>Live в момента</h2>
        <div id="f2Live"></div>
      </section>

      <section class="f2-panel">
        <h2>Последни резултати</h2>
        <div id="f2Recent"></div>
      </section>

      <section class="f2-panel">
        <h2>Предстоящи събития</h2>
        <div id="f2Upcoming"></div>
      </section>
    </div>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    const CURRENT_YEAR = <?= json_encode($year, JSON_THROW_ON_ERROR) ?>;
    const MIN_YEAR = 2017;
    const MAX_YEAR = CURRENT_YEAR + 1;

    const yearSel = document.getElementById('f2Year');
    for (let y = MAX_YEAR; y >= MIN_YEAR; y--) {
      const opt = document.createElement('option');
      opt.value = String(y);
      opt.textContent = String(y);
      if (y === CURRENT_YEAR) opt.selected = true;
      yearSel.appendChild(opt);
    }

    function esc(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }

    function fmtDate(e) {
      const ts = e.strTimestamp ? new Date(e.strTimestamp + 'Z') : null;
      if (ts && !Number.isNaN(ts.getTime())) {
        return ts.toLocaleString('bg-BG', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
      }
      const local = [e.dateEventLocal || e.dateEvent, e.strTimeLocal || e.strTime].filter(Boolean).join(' ');
      return local || '—';
    }

    function scoreLine(e) {
      const hs = e.intHomeScore;
      const as = e.intAwayScore;
      if (hs == null && as == null) return '—';
      return `${hs ?? '-'} : ${as ?? '-'}`;
    }

    function renderList(rootId, items, emptyText) {
      const root = document.getElementById(rootId);
      if (!items || !items.length) {
        root.innerHTML = `<div class="f2-empty">${esc(emptyText)}</div>`;
        return;
      }
      root.innerHTML = items.map((e) => {
        const venue = [e.strVenue, e.strCountry].filter(Boolean).join(' · ');
        return `<article class="f2-item">
          <div class="f2-item-top">
            <strong>${esc(e.strEvent || 'F2 Event')}</strong>
            <span>${esc(fmtDate(e))}</span>
          </div>
          <div class="f2-item-meta">${esc(venue || '—')}</div>
          <div class="f2-item-bottom">
            <span>Статус: ${esc(e.strStatus || 'N/A')}</span>
            <span>Резултат: ${esc(scoreLine(e))}</span>
          </div>
        </article>`;
      }).join('');
    }

    function renderLinks(links) {
      const wrap = document.getElementById('f2OfficialLinks');
      if (!links) {
        wrap.innerHTML = '';
        return;
      }
      wrap.innerHTML = `
        <a target="_blank" rel="noopener" href="${esc(links.drivers || '#')}">Класиране пилоти</a>
        <a target="_blank" rel="noopener" href="${esc(links.teams || '#')}">Класиране отбори</a>
        <a target="_blank" rel="noopener" href="${esc(links.live_timing || '#')}">Live Timing</a>`;
      document.getElementById('f2Links').hidden = false;
    }

    function renderStandings(tableId, rows, kind) {
      const t = document.getElementById(tableId);
      if (!rows || !rows.length) {
        t.innerHTML = `<thead><tr><th>#</th><th>${esc(kind)}</th><th>Точки</th></tr></thead>` +
          `<tbody><tr><td colspan="3" class="f2-td-empty">Няма налична таблица в момента.</td></tr></tbody>`;
        return;
      }
      const headName = kind === 'Пилот' ? 'Пилот' : 'Отбор';
      t.innerHTML = `<thead><tr><th>#</th><th>${esc(headName)}</th><th>Точки</th></tr></thead><tbody>` +
        rows.map((r) => {
          const name = r.name || '—';
          const team = r.team || '';
          const sub = team ? `<div class="f2-sub">${esc(team)}</div>` : '';
          return `<tr>
            <td class="f2-pos">${esc(r.pos ?? '—')}</td>
            <td><div class="f2-name">${esc(name)}</div>${sub}</td>
            <td class="f2-pts">${esc(r.points ?? '—')}</td>
          </tr>`;
        }).join('') +
        '</tbody>';
    }

    function render(data) {
      const status = document.getElementById('f2Status');
      if (!data.ok) {
        status.className = 'f2-error';
        status.textContent = data.error || 'Неуспешно зареждане.';
        return;
      }
      status.className = 'f2-info';
      status.textContent = `F2 сезон ${data.year} · ${data.note || ''}`;

      renderLinks(data.official_standings_links);
      renderStandings('f2DriversTable', data.standings_drivers, 'Пилот');
      renderStandings('f2TeamsTable', data.standings_teams, 'Отбор');
      document.getElementById('f2Standings').hidden = false;
      renderList('f2Live', data.live_events, 'В момента няма маркирано live F2 събитие.');
      renderList('f2Recent', data.recent_results, 'Няма налични последни резултати.');
      renderList('f2Upcoming', data.upcoming_events, 'Няма публикувани предстоящи събития.');
    }

    async function loadF2() {
      const y = yearSel.value;
      document.getElementById('f2Status').className = 'f2-info';
      document.getElementById('f2Status').textContent = 'Зареждане на F2 данни…';
      try {
        const res = await fetch('api/f2.php?year=' + encodeURIComponent(y), { cache: 'default' });
        const data = await res.json();
        render(data);
      } catch (e) {
        const status = document.getElementById('f2Status');
        status.className = 'f2-error';
        status.textContent = 'Неуспешна връзка за F2 данни.';
      }
    }

    yearSel.addEventListener('change', loadF2);
    loadF2();
  </script>
</body>
</html>
