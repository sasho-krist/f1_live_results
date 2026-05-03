<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ЧЗВ — F1 Live</title>
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/footer.css">
</head>
<body class="has-fixed-footer">
  <div class="wrap legal-page">
    <a class="back" href="index.php">← Начало</a>
    <h1>Често задавани въпроси</h1>

    <section>
      <h2>Защо колоните „Зад лидера“ и „Интервал“ са с „—“?</h2>
      <p>При квалификация и много сесии извън състезание OpenF1 често не подава състезателни интервали. При състезание (Race / Sprint) обикновено се показват интервали, когато са налични.</p>
    </section>

    <section>
      <h2>Защо понякога има пауза при опресняване?</h2>
      <p>Мрежата или API могат временно да не отговорят. Страницата показва неутрален статус и автоматично опитва отново.</p>
    </section>

    <section>
      <h2>Нужен ли е API ключ?</h2>
      <p>За текущата интеграция с OpenF1 не се изисква ключ. Условията на доставчика могат да се променят — виж <a href="https://openf1.org/" target="_blank" rel="noopener">openf1.org</a>.</p>
    </section>

    <section>
      <h2>Колко често се обновяват данните?</h2>
      <p>Клиентът прави заявка приблизително на всеки <strong>5 секунди</strong>.</p>
    </section>

    <section>
      <h2>Откъде са данните?</h2>
      <p>От публичния API <a href="https://openf1.org/" target="_blank" rel="noopener">OpenF1</a> (<code>api.openf1.org</code>), през обобщаващ скрипт <code>api/live.php</code>.</p>
    </section>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
