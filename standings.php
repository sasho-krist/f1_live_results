<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Генерално класиране — F1</title>
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/standings.css">
</head>
<body class="has-fixed-footer">
  <div class="wrap legal-page">
    <header class="st-header">
      <h1>Formula 1 <span>Класиране</span></h1>
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
        <a class="back" href="calendar.php">Календар</a>
        <a class="back" href="index.php">← Живи резултати</a>
      </div>
    </header>

    <div id="stStatus" class="st-loading">Зареждане на класирането…</div>
    <div id="stSession" class="st-session" hidden></div>
    <div id="stGrid" class="st-grid" hidden></div>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    function esc(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }

    function teamColor(hex) {
      if (!hex || typeof hex !== 'string') return '#555';
      return '#' + hex.replace(/^#/, '');
    }

    function renderSession(meta) {
      const el = document.getElementById('stSession');
      if (!meta) {
        el.hidden = true;
        return;
      }
      const parts = [
        meta.session_name,
        meta.circuit_short_name,
        meta.country_name,
        meta.year,
      ].filter(Boolean);
      const when = meta.date_end
        ? new Date(meta.date_end).toLocaleString('bg-BG', { dateStyle: 'medium', timeStyle: 'short' })
        : '';
      el.innerHTML =
        '<p><strong>' + esc(parts.join(' · ')) + '</strong></p>' +
        (when ? '<p class="muted">Към края на сесията (ориентир): ' + esc(when) + '</p>' : '');
      el.hidden = false;
    }

    function render(data) {
      const status = document.getElementById('stStatus');
      const grid = document.getElementById('stGrid');

      if (!data.ok) {
        status.className = 'st-error';
        status.textContent = data.error || 'Грешка.';
        status.hidden = false;
        grid.hidden = true;
        return;
      }

      grid.hidden = false;

      if (data.message) {
        status.className = 'st-empty';
        status.textContent = data.message;
        status.hidden = false;
      } else {
        status.hidden = true;
      }

      renderSession(data.session);

      const drivers = data.drivers || [];
      const teams = data.teams || [];

      let driversHtml = '';
      if (drivers.length) {
        driversHtml =
          '<thead><tr><th>#</th><th>Пилот</th><th>Точки</th></tr></thead><tbody>';
        drivers.forEach((row) => {
          const c = row.championship || {};
          const d = row.driver || {};
          const pos =
            c.position_current != null
              ? c.position_current
              : c.position_start != null
                ? c.position_start
                : '—';
          const pts =
            c.points_current != null
              ? c.points_current
              : c.points_start != null
                ? c.points_start
                : '—';
          const tc = teamColor(d.team_colour);
          const name = d.full_name || d.broadcast_name || ('#' + (c.driver_number ?? ''));
          const tm = d.team_name || '';
          driversHtml += `<tr>
            <td class="st-pos">${esc(pos)}</td>
            <td>
              <div class="st-driver-cell">
                <span class="st-bar" style="background:${tc}"></span>
                <div>
                  <div class="st-name">${esc(name)}</div>
                  <div class="st-team">${esc(tm)}</div>
                </div>
              </div>
            </td>
            <td class="st-pts">${esc(pts)}</td>
          </tr>`;
        });
        driversHtml += '</tbody>';
      }

      let teamsHtml = '';
      if (teams.length) {
        teamsHtml =
          '<thead><tr><th>#</th><th>Отбор</th><th>Точки</th></tr></thead><tbody>';
        teams.forEach((t) => {
          const pos = t.position_current ?? '—';
          const pts = t.points_current != null ? t.points_current : '—';
          teamsHtml += `<tr>
            <td class="st-pos">${esc(pos)}</td>
            <td class="st-name">${esc(t.team_name || '—')}</td>
            <td class="st-pts">${esc(pts)}</td>
          </tr>`;
        });
        teamsHtml += '</tbody>';
      }

      const emptyMsg =
        '<p class="st-empty" style="margin:0;border:none;">Няма публикувани данни за класирането за тази сесия (офсийзън или ограничение на API).</p>';

      grid.innerHTML =
        '<section class="st-panel" aria-labelledby="h-drivers">' +
        '<h2 id="h-drivers">Пилоти</h2>' +
        '<div class="st-table-wrap">' +
        (drivers.length
          ? '<table class="st-table">' + driversHtml + '</table>'
          : emptyMsg) +
        '</div></section>' +
        '<section class="st-panel" aria-labelledby="h-teams">' +
        '<h2 id="h-teams">Конструктори</h2>' +
        '<div class="st-table-wrap">' +
        (teams.length
          ? '<table class="st-table">' + teamsHtml + '</table>'
          : emptyMsg) +
        '</div></section>';

      if (data.note) {
        const note = document.createElement('p');
        note.className = 'muted';
        note.style.cssText = 'font-size:0.78rem;color:#8b9aad;margin-top:1rem;';
        note.textContent = data.note;
        grid.appendChild(note);
      }
    }

    async function load() {
      try {
        const res = await fetch('api/standings.php', { cache: 'default' });
        const data = await res.json();
        render(data);
      } catch (e) {
        document.getElementById('stStatus').className = 'st-error';
        document.getElementById('stStatus').textContent = 'Неуспешно зареждане.';
        document.getElementById('stStatus').hidden = false;
      }
    }

    load();
  </script>
</body>
</html>
