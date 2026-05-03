<?php
declare(strict_types=1);
$y = (int) gmdate('Y');
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>F1 календар — сезони</title>
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/calendar.css">
</head>
<body class="has-fixed-footer">
  <div class="wrap legal-page">
    <div class="cal-header">
      <h1>Formula 1 <span>Календар</span></h1>
      <nav class="cal-toolbar" aria-label="Сезон">
        <label for="yearSel">Сезон</label>
        <select id="yearSel" title="Година на сезона"></select>
        <a class="back" href="standings.php" style="margin-left:0.5rem;">Класиране</a>
        <a class="back" href="index.php">← Живи резултати</a>
      </nav>
    </div>

    <div id="calStatus" class="cal-loading">Зареждане на календар…</div>
    <div id="calRoot" class="cal-grid" hidden></div>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    const CURRENT_YEAR = <?= json_encode($y, JSON_THROW_ON_ERROR) ?>;
    const MIN_YEAR = 2023;
    const MAX_YEAR = Math.max(CURRENT_YEAR + 1, 2030);

    const yearSel = document.getElementById('yearSel');
    for (let y = MIN_YEAR; y <= MAX_YEAR; y++) {
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

    function fmtRange(start, end) {
      if (!start) return '—';
      const a = new Date(start);
      const line = end
        ? `${a.toLocaleString('bg-BG', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })} — ${new Date(end).toLocaleString('bg-BG', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}`
        : a.toLocaleString('bg-BG', { dateStyle: 'long', timeStyle: 'short' });
      return line;
    }

    function fmtSession(dt) {
      if (!dt) return '—';
      return new Date(dt).toLocaleString('bg-BG', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
      });
    }

    function render(data) {
      const root = document.getElementById('calRoot');
      const status = document.getElementById('calStatus');
      if (!data.ok) {
        status.className = 'cal-error';
        status.textContent = data.error || 'Няма данни.';
        status.hidden = false;
        root.hidden = true;
        return;
      }
      status.hidden = true;
      root.hidden = false;
      root.innerHTML = '';

      const rounds = data.rounds || [];
      if (!rounds.length) {
        root.innerHTML = '<div class="cal-loading">Няма обявени събития за този сезон.</div>';
        return;
      }

      rounds.forEach((item) => {
        const m = item.meeting || {};
        const sessions = item.sessions || [];
        const cancelled = m.is_cancelled === true;

        const card = document.createElement('article');
        card.className = 'cal-card';

        const head = document.createElement('div');
        head.className = 'cal-card__head';

        const flagWrap = document.createElement('div');
        if (m.country_flag) {
          const img = document.createElement('img');
          img.className = 'cal-flag';
          img.src = m.country_flag;
          img.alt = esc(m.country_name || '');
          img.loading = 'lazy';
          img.referrerPolicy = 'no-referrer';
          flagWrap.appendChild(img);
        } else {
          const ph = document.createElement('div');
          ph.className = 'cal-flag cal-flag--ph';
          ph.setAttribute('aria-hidden', 'true');
          flagWrap.appendChild(ph);
        }

        const titles = document.createElement('div');
        titles.className = 'cal-card__titles';
        const h2 = document.createElement('h2');
        h2.textContent = m.meeting_name || m.meeting_official_name || 'Grand Prix';
        titles.appendChild(h2);
        const sub = document.createElement('p');
        sub.textContent = [m.circuit_short_name, m.location, m.country_name].filter(Boolean).join(' · ');
        titles.appendChild(sub);
        if (cancelled) {
          const b = document.createElement('span');
          b.className = 'cal-card__cancelled';
          b.textContent = 'Отменено';
          titles.appendChild(b);
        }

        const dates = document.createElement('div');
        dates.className = 'cal-card__dates';
        dates.innerHTML = esc(fmtRange(m.date_start, m.date_end));

        head.appendChild(flagWrap);
        head.appendChild(titles);
        head.appendChild(dates);
        card.appendChild(head);

        if (sessions.length) {
          const table = document.createElement('table');
          table.className = 'cal-sessions';
          table.innerHTML = `
            <thead><tr>
              <th>Сесия</th>
              <th>Начало (локално)</th>
              <th>Край</th>
            </tr></thead>
            <tbody></tbody>`;
          const tbody = table.querySelector('tbody');
          sessions.forEach((s) => {
            const tr = document.createElement('tr');
            if (s.is_cancelled) tr.className = 'sess-cancelled';
            tr.innerHTML = `
              <td>${esc(s.session_name || s.session_type || '—')}</td>
              <td>${esc(fmtSession(s.date_start))}</td>
              <td>${esc(fmtSession(s.date_end))}</td>`;
            tbody.appendChild(tr);
          });
          card.appendChild(table);
        } else {
          const empty = document.createElement('div');
          empty.className = 'cal-empty-sessions';
          empty.textContent = 'Няма публикувани часове на сесиите за това събитие в OpenF1.';
          card.appendChild(empty);
        }

        root.appendChild(card);
      });
    }

    async function loadCal() {
      const year = yearSel.value;
      const status = document.getElementById('calStatus');
      const root = document.getElementById('calRoot');
      status.hidden = false;
      status.className = 'cal-loading';
      status.textContent = 'Зареждане на календар…';
      root.hidden = true;
      try {
        const res = await fetch('api/calendar.php?year=' + encodeURIComponent(year), { cache: 'default' });
        const data = await res.json();
        render(data);
      } catch (e) {
        status.className = 'cal-error';
        status.textContent = 'Неуспешно зареждане. Опитай отново.';
        status.hidden = false;
      }
    }

    yearSel.addEventListener('change', loadCal);
    loadCal();
  </script>
</body>
</html>
