<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>F1 — живи резултати</title>
  <link rel="preconnect" href="https://api.openf1.org">
  <link rel="stylesheet" href="css/footer.css">
  <style>
    :root {
      --bg: #0a0e14;
      --panel: #12181f;
      --border: #1e2836;
      --text: #e8eef5;
      --muted: #8b9aad;
      --accent: #e10600;
      --ok: #22c55e;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Segoe UI", system-ui, sans-serif;
      background: radial-gradient(1200px 600px at 10% -10%, #1a2332 0%, var(--bg) 55%);
      color: var(--text);
    }
    .wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 1.5rem 1rem 3rem;
    }
    header {
      display: flex;
      flex-wrap: wrap;
      align-items: baseline;
      gap: 0.75rem 1.5rem;
      margin-bottom: 1.25rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 1rem;
    }
    h1 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    h1 span { color: var(--accent); }
    .top-nav { margin-left: auto; }
    .top-nav a {
      color: #93c5fd;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .top-nav a:hover { text-decoration: underline; }
    .meta {
      font-size: 0.875rem;
      color: var(--muted);
    }
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 0.25rem 0.6rem;
      border-radius: 999px;
      background: #1a2330;
      border: 1px solid var(--border);
    }
    .dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--muted);
      animation: pulse 2s ease-in-out infinite;
    }
    .dot.live { background: var(--ok); }
    .dot.waiting {
      background: #86efac;
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.35; }
    }
    .session-card {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin-bottom: 1.25rem;
    }
    .session-card h2 { margin: 0 0 0.35rem; font-size: 1.1rem; }
    .session-card p { margin: 0.2rem 0; color: var(--muted); font-size: 0.9rem; }
    .grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.25rem;
    }
    @media (min-width: 900px) {
      .grid { grid-template-columns: 1fr 320px; }
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      overflow: hidden;
    }
    th, td {
      padding: 0.65rem 0.75rem;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }
    th {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted);
      background: #0f141c;
    }
    tr:last-child td { border-bottom: none; }
    .pos {
      font-weight: 700;
      width: 3rem;
      color: var(--muted);
    }
    .driver-cell {
      display: flex;
      align-items: center;
      gap: 0.65rem;
    }
    .color-bar {
      width: 4px;
      height: 36px;
      border-radius: 2px;
      flex-shrink: 0;
      background: #444;
    }
    .name { font-weight: 600; }
    .team { font-size: 0.8rem; color: var(--muted); }
    .gap { font-variant-numeric: tabular-nums; color: #cbd5e1; }
    .msg-list {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 0.75rem 1rem;
      max-height: 420px;
      overflow-y: auto;
    }
    .msg-list h3 {
      margin: 0 0 0.75rem;
      font-size: 0.85rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }
    .msg {
      font-size: 0.82rem;
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--border);
      color: #c5d0de;
    }
    .msg:last-child { border-bottom: none; }
    .msg time {
      display: block;
      font-size: 0.72rem;
      color: var(--muted);
      margin-bottom: 0.25rem;
    }
    .banner {
      padding: 0.75rem 1rem;
      border-radius: 8px;
      background: #1e293b;
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 0.85rem;
      margin-bottom: 1rem;
    }
    .error {
      background: #3f1515;
      border-color: #7f1d1d;
      color: #fecaca;
    }
    .banner-wait {
      background: #14532d;
      border-color: #166534;
      color: #bbf7d0;
    }
    .header-brand {
      display: flex;
      align-items: baseline;
      gap: 1rem 1.25rem;
      flex-wrap: wrap;
    }
    .nav-top a {
      color: #93c5fd;
      font-size: 0.9rem;
      font-weight: 500;
      text-decoration: none;
    }
    .nav-top a:hover { text-decoration: underline; }
    .nav-sep { color: var(--muted); margin: 0 0.1rem; user-select: none; }
  </style>
</head>
<body class="has-fixed-footer">
  <div class="wrap">
    <header>
      <div class="header-brand">
        <h1>Formula 1 <span>Live</span></h1>
        <nav class="nav-top" aria-label="Навигация">
          <a href="calendar.php">Календар</a>
          <span class="nav-sep" aria-hidden="true">·</span>
          <a href="standings.php">Класиране</a>
          <span class="nav-sep" aria-hidden="true">·</span>
          <a href="f2.php">F2 Live</a>
        </nav>
      </div>
      <span class="pill" title="Авто опресняване"><span class="dot live" id="statusDot"></span> <span id="statusText">Зареждане…</span></span>
      <span class="meta" id="clock"></span>
    </header>

    <div id="banner" class="banner" hidden></div>

    <div id="sessionBox" class="session-card" hidden>
      <h2 id="sessionTitle"></h2>
      <p id="sessionCircuit"></p>
      <p id="sessionWhen"></p>
    </div>

    <div class="grid">
      <div>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Пилот / отбор</th>
              <th>Зад лидера</th>
              <th>Интервал</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td colspan="4" class="gap" style="padding:1.5rem;">Зареждане на данни…</td></tr>
          </tbody>
        </table>
      </div>
      <div>
        <div class="msg-list">
          <h3>Race control</h3>
          <div id="messages"></div>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    const POLL_MS = 5000;

    function formatGap(v) {
      if (v === null || v === undefined) return '—';
      if (v === '+1 LAP') return '+1 обиколка';
      if (typeof v === 'number') return v.toFixed(3) + ' s';
      return String(v);
    }

    function esc(s) {
      const d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    function teamColor(hex) {
      if (!hex || typeof hex !== 'string') return '#555';
      const h = hex.replace(/^#/, '');
      return '#' + h;
    }

    const WAIT_MSG =
      'Данните временно не са налични — при следващото опресняване ще се опита отново. Това не е грешка при теб, а кратка пауза от услугата.';

    /** @type {object|null} последен успешен отговор от API — при кратък провал не изчистваме таблицата */
    let lastGoodData = null;

    function showWaitingState() {
      const banner = document.getElementById('banner');
      const statusText = document.getElementById('statusText');
      const statusDot = document.getElementById('statusDot');
      banner.hidden = false;
      banner.classList.remove('error');
      banner.classList.add('banner-wait');
      banner.textContent = WAIT_MSG;
      statusText.textContent = 'Изчакване';
      statusDot.classList.remove('live');
      statusDot.classList.add('waiting');
    }

    function hideBanner() {
      const banner = document.getElementById('banner');
      const statusDot = document.getElementById('statusDot');
      banner.hidden = true;
      banner.classList.remove('error', 'banner-wait');
      statusDot.classList.add('live');
      statusDot.classList.remove('waiting');
    }

    function showEmptyPlaceholders() {
      const tbody = document.getElementById('tbody');
      const sessionBox = document.getElementById('sessionBox');
      const messages = document.getElementById('messages');
      tbody.innerHTML =
        '<tr><td colspan="4" class="gap" style="padding:1.5rem;">Още няма заредена таблица — опитваме отново автоматично.</td></tr>';
      sessionBox.hidden = true;
      messages.innerHTML = '<div class="msg">Ще се появят заедно с данните.</div>';
    }

    function render(data) {
      const tbody = document.getElementById('tbody');
      const sessionBox = document.getElementById('sessionBox');
      const messages = document.getElementById('messages');
      const statusText = document.getElementById('statusText');

      if (!data.ok) {
        showWaitingState();
        if (!lastGoodData) {
          showEmptyPlaceholders();
        }
        return;
      }

      lastGoodData = data;
      hideBanner();

      const s = data.session || {};
      sessionBox.hidden = false;
      document.getElementById('sessionTitle').textContent =
        [s.session_name, s.year].filter(Boolean).join(' · ') || 'Сесия';
      document.getElementById('sessionCircuit').textContent =
        [s.circuit_short_name, s.country_name].filter(Boolean).join(' · ') || '';
      const start = s.date_start ? new Date(s.date_start).toLocaleString('bg-BG') : '';
      const end = s.date_end ? new Date(s.date_end).toLocaleString('bg-BG') : '';
      document.getElementById('sessionWhen').textContent =
        start && end ? `${start} — ${end} (UTC се конвертира локално)` : '';

      statusText.textContent = 'Актуално · ' + (data.data_mode === 'intervals' ? 'интервали' : 'позиции');

      const rows = data.standings || [];
      if (!rows.length) {
        tbody.innerHTML =
          '<tr><td colspan="4" class="gap" style="padding:1.5rem;">Няма таблични данни за тази сесия (възможно е да няма активно състезание или достъпът до live е ограничен).</td></tr>';
      } else {
        tbody.innerHTML = rows.map((r) => {
          const d = r.driver || {};
          const tc = teamColor(d.team_colour);
          const name = d.full_name || d.broadcast_name || ('#' + r.driver_number);
          const team = d.team_name || '';
          return `<tr>
            <td class="pos">${r.position}</td>
            <td>
              <div class="driver-cell">
                <span class="color-bar" style="background:${tc}"></span>
                <div>
                  <div class="name">${esc(name)}</div>
                  <div class="team">${esc(team)}</div>
                </div>
              </div>
            </td>
            <td class="gap">${formatGap(r.gap_to_leader)}</td>
            <td class="gap">${formatGap(r.interval)}</td>
          </tr>`;
        }).join('');
      }

      const rc = data.race_control || [];
      if (!rc.length) {
        messages.innerHTML = '<div class="msg">Няма скорошни съобщения.</div>';
      } else {
        messages.innerHTML = rc.map((m) => {
          const t = m.date ? new Date(m.date).toLocaleTimeString('bg-BG') : '';
          const text = m.message || '';
          return `<div class="msg"><time>${esc(t)}</time>${esc(text)}</div>`;
        }).join('');
      }
    }

    async function load() {
      try {
        const res = await fetch('api/live.php', { cache: 'no-store' });
        let data;
        try {
          data = await res.json();
        } catch (e) {
          showWaitingState();
          if (!lastGoodData) showEmptyPlaceholders();
          return;
        }
        if (!res.ok) {
          showWaitingState();
          if (!lastGoodData) showEmptyPlaceholders();
          return;
        }
        render(data);
      } catch (e) {
        showWaitingState();
        if (!lastGoodData) showEmptyPlaceholders();
      }
    }

    function tickClock() {
      document.getElementById('clock').textContent =
        new Date().toLocaleString('bg-BG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    tickClock();
    setInterval(tickClock, 1000);
    load();
    setInterval(load, POLL_MS);
  </script>
</body>
</html>
