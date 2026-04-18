<?php
// Beklenir: $page_title, $page_desc (opsiyonel), $page_canonical (opsiyonel), $page_og (opsiyonel)
$page_title     = $page_title     ?? SITE_NAME;
$page_desc      = $page_desc      ?? SITE_DESC;
$page_canonical = $page_canonical ?? '';
$page_og        = $page_og        ?? null;
?><!DOCTYPE html>
<html lang="tr">
<head>
<?= render_head($page_title, $page_desc, $page_canonical, $page_og) ?>
</head>
<body>
<nav class="nav">
  <div class="nav-wrap">
    <a href="/" class="nav-logo"><span class="dot"><i class="fas fa-plug"></i></span> Entegratör Rehberi</a>
    <button class="nav-mob" aria-label="Menü"><i class="fas fa-bars"></i></button>
    <div class="nav-links">
      <a href="/">Tüm Entegratörler</a>
      <a href="/karsilastir.php">Karşılaştır</a>
      <a href="/hakkinda.php">Hakkında</a>
      <a href="/iletisim.php" class="nav-cta"><i class="fas fa-envelope"></i> İletişim</a>
    </div>
  </div>
</nav>
