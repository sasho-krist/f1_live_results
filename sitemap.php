<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sitemap — F1 Live</title>
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/footer.css">
</head>
<body class="has-fixed-footer">
  <div class="wrap legal-page">
    <a class="back" href="index.php">← Начало</a>
    <h1>Карта на сайта</h1>

    <section>
      <p>Основни адреси на проекта (относително към инсталационната папка, напр. <code>/f1/</code>):</p>
      <table>
        <thead>
          <tr>
            <th>Страница</th>
            <th>Описание</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><a href="index.php">index.php</a></td>
            <td>Начална страница — живо табло с класиране и Race Control.</td>
          </tr>
          <tr>
            <td><a href="privacy.php">privacy.php</a></td>
            <td>Политика за поверителност.</td>
          </tr>
          <tr>
            <td><a href="terms.php">terms.php</a></td>
            <td>Условия за ползване.</td>
          </tr>
          <tr>
            <td><a href="faq.php">faq.php</a></td>
            <td>Често задавани въпроси.</td>
          </tr>
          <tr>
            <td><a href="sitemap.php">sitemap.php</a></td>
            <td>Тази страница.</td>
          </tr>
          <tr>
            <td><code>api/live.php</code></td>
            <td>JSON endpoint за клиентското приложение (не е предвидена за директно ползване от посетители).</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
