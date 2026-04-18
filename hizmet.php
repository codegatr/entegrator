<?php
require_once __DIR__ . '/init.php';

$kod_raw = trim($_GET['h'] ?? '');
$kod = str_replace('-', '_', $kod_raw);

$q = $pdo->prepare("SELECT * FROM hizmet_turleri WHERE kod=? LIMIT 1");
$q->execute([$kod]);
$hz = $q->fetch();
if (!$hz) { header('Location: /'); exit; }

// Bu hizmeti veren entegratörler
$st = $pdo->prepare("SELECT e.* FROM entegratorler e
    JOIN entegrator_hizmetler eh ON eh.entegrator_id=e.id
    WHERE eh.hizmet_id=? AND e.aktif=1
    ORDER BY e.one_cikan DESC, e.goruntulenme DESC, e.firma_adi ASC");
$st->execute([$hz['id']]);
$liste = $st->fetchAll();

$page_title = $hz['ad'] . ' Özel Entegratörleri';
$page_desc  = 'GİB onaylı '.$hz['ad'].' özel entegratörlerinin tam listesi. '.count($liste).' firma, karşılaştırmalı inceleme.';

require __DIR__ . '/partials/header.php';
?>
<div class="container">
  <nav style="font-size:13px;color:#64748b;margin-bottom:16px">
    <a href="/" style="color:#64748b">Ana Sayfa</a>
    <i class="fas fa-chevron-right" style="font-size:10px;margin:0 6px"></i>
    <span><?= h($hz['ad']) ?></span>
  </nav>
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px;margin-bottom:24px;display:flex;gap:18px;align-items:center">
    <div style="width:60px;height:60px;border-radius:12px;background:<?= h($hz['renk']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px">
      <i class="fas fa-<?= h($hz['icon']) ?>"></i>
    </div>
    <div>
      <h1 style="margin:0;font-size:24px;font-weight:800"><?= h($hz['ad']) ?> Özel Entegratörleri</h1>
      <?php if($hz['aciklama']): ?><p style="margin:4px 0 0;color:#64748b;font-size:14px"><?= h($hz['aciklama']) ?></p><?php endif; ?>
      <div style="margin-top:10px;color:#64748b;font-size:13px"><strong style="color:#f6821f"><?= count($liste) ?></strong> firma bu hizmeti sağlıyor</div>
    </div>
  </div>

  <?php if (empty($liste)): ?>
    <div class="empty"><i class="fas fa-inbox"></i><div>Henüz bu hizmeti sağlayan entegratör bulunmuyor.</div></div>
  <?php else: ?>
    <div class="grid">
      <?php foreach($liste as $e): ?>
      <article class="card <?= $e['one_cikan']?'onec':'' ?>">
        <div class="card-head">
          <div class="card-logo"><?= $e['logo_url']?'<img src="'.h($e['logo_url']).'" alt="" loading="lazy">':h(mb_substr($e['firma_adi'],0,1)) ?></div>
          <div class="card-info">
            <h3><a href="/e.php?s=<?= h($e['slug']) ?>"><?= h($e['firma_adi']) ?></a></h3>
            <div class="segment"><?= h(ucfirst($e['segment'])) ?></div>
          </div>
        </div>
        <?php if($e['kisa_aciklama']): ?><p class="card-desc"><?= h($e['kisa_aciklama']) ?></p><?php endif; ?>
        <div class="card-foot">
          <a href="/e.php?s=<?= h($e['slug']) ?>" class="btn btn-primary"><i class="fas fa-circle-info"></i> Detay</a>
          <button type="button" class="btn btn-compare" data-cmp-toggle="<?= (int)$e['id'] ?>" data-cmp-name="<?= h($e['firma_adi']) ?>">
            <i class="fas fa-scale-balanced"></i> Karşılaştır
          </button>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
