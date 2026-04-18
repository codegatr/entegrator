<?php
require_once __DIR__ . '/init.php';

$kod = trim($_GET['e'] ?? '');
$q = $pdo->prepare("SELECT * FROM erp_uyumluluk WHERE kod=? LIMIT 1");
$q->execute([$kod]);
$rp = $q->fetch();
if (!$rp) { header('Location: /'); exit; }

$st = $pdo->prepare("SELECT e.* FROM entegratorler e
    JOIN entegrator_erp ee ON ee.entegrator_id=e.id
    WHERE ee.erp_id=? AND e.aktif=1
    ORDER BY e.one_cikan DESC, e.goruntulenme DESC, e.firma_adi ASC");
$st->execute([$rp['id']]);
$liste = $st->fetchAll();

$page_title = $rp['ad'] . ' Uyumlu Özel Entegratörler';
$page_desc  = $rp['ad'].' yazılımıyla entegre çalışan GİB onaylı e-Fatura özel entegratörlerinin tam listesi.';

require __DIR__ . '/partials/header.php';
?>
<div class="container">
  <nav style="font-size:13px;color:#64748b;margin-bottom:16px">
    <a href="/" style="color:#64748b">Ana Sayfa</a>
    <i class="fas fa-chevron-right" style="font-size:10px;margin:0 6px"></i>
    <span><?= h($rp['ad']) ?></span>
  </nav>
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:26px;margin-bottom:24px">
    <h1 style="margin:0;font-size:24px;font-weight:800"><i class="fas fa-plug" style="color:#f6821f"></i> <?= h($rp['ad']) ?> Uyumlu Entegratörler</h1>
    <div style="margin-top:8px;color:#64748b;font-size:14px">
      <?= h($rp['ad']) ?> yazılımıyla entegre çalışan <strong style="color:#f6821f"><?= count($liste) ?></strong> GİB onaylı özel entegratör.
    </div>
  </div>

  <?php if (empty($liste)): ?>
    <div class="empty"><i class="fas fa-inbox"></i><div>Henüz <?= h($rp['ad']) ?> ile uyumlu entegratör bulunmuyor.</div></div>
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
