<?php
declare(strict_types=1);
/** @var string $footerBase префикс за пътища (празно за страници в root) */
$footerBase = $footerBase ?? '';
?>
<footer class="site-footer" role="contentinfo">
  <div class="site-footer__inner">
    <nav class="site-footer__nav" aria-label="Информация">
      <a href="<?= htmlspecialchars($footerBase) ?>privacy.php">Поверителност</a>
      <span aria-hidden="true">·</span>
      <a href="<?= htmlspecialchars($footerBase) ?>terms.php">Условия</a>
      <span aria-hidden="true">·</span>
      <a href="<?= htmlspecialchars($footerBase) ?>faq.php">ЧЗВ</a>
      <span aria-hidden="true">·</span>
      <a href="<?= htmlspecialchars($footerBase) ?>calendar.php">Календар</a>
      <span aria-hidden="true">·</span>
      <a href="<?= htmlspecialchars($footerBase) ?>standings.php">Класиране</a>
      <span aria-hidden="true">·</span>
      <a href="<?= htmlspecialchars($footerBase) ?>sitemap.php">Sitemap</a>
    </nav>
    <p class="site-footer__copy">
      Всички права запазени.
      <a href="https://sasho-dev.com/portfolio" target="_blank" rel="noopener">sasho-dev.com/portfolio</a>
    </p>
    <p class="site-footer__meta">
      Данни: <a href="https://openf1.org/" target="_blank" rel="noopener">OpenF1</a>.
      Опресняване на клиента ~5 сек.
    </p>
  </div>
</footer>
