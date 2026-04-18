<?php
require_once __DIR__ . '/init.php';

$ids_raw = $_GET['ids'] ?? '';
$ids = array_values(array_filter(array_map('intval', explode(',', $ids_raw)), fn($x)=>$x>0));
$ids = array_slice(array_unique($ids), 0, 3);

$ents = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT * FROM entegratorler WHERE id IN ($in) AND aktif=1");
    $q->execute($ids);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    // Sırayı koru
    $map = array_column($rows, null, 'id');
    foreach ($ids as $id) if (isset($map[$id])) $ents[] = $map[$id];
}

// Tüm hizmet + ERP listesi (karşılaştırma matrisinde satır olarak)
$tum_hizmetler = $pdo->query("SELECT * FROM hizmet_turleri ORDER BY siralama")->fetchAll();
$tum_erpler    = $pdo->query("SELECT * FROM erp_uyumluluk ORDER BY siralama")->fetchAll();

// Her entegratör için hizmet ve ERP ID setleri
$has_hz = []; $has_er = [];
foreach ($ents as $e) {
    $h = $pdo->prepare("SELECT hizmet_id FROM entegrator_hizmetler WHERE entegrator_id=?");
    $h->execute([$e['id']]);
    $has_hz[$e['id']] = array_map('intval', $h->fetchAll(PDO::FETCH_COLUMN));
    $r = $pdo->prepare("SELECT erp_id FROM entegrator_erp WHERE entegrator_id=?");
    $r->execute([$e['id']]);
    $has_er[$e['id']] = array_map('intval', $r->fetchAll(PDO::FETCH_COLUMN));
}

$page_title = count($ents) >= 2 ? implode(' vs ', array_column($ents, 'firma_adi')) . ' Karşılaştırması' : 'Entegratör Karşılaştırma';
$page_desc  = 'GİB onaylı özel entegratörleri yan yana karşılaştırın. Hizmet türleri, ERP uyumluluğu ve sertifikalar tek tabloda.';

require __DIR__ . '/partials/header.php';
?>

<div class="container">
  <nav style="font-size:13px;color:#64748b;margin-bottom:16px">
    <a href="/" style="color:#64748b">Ana Sayfa</a>
    <i class="fas fa-chevron-right" style="font-size:10px;margin:0 6px"></i>
    <span>Karşılaştırma</span>
  </nav>

  <h1 style="margin:0 0 22px;font-size:26px;font-weight:800">
    <i class="fas fa-scale-balanced" style="color:#f6821f"></i>
    Entegratör Karşılaştırma
  </h1>

  <?php if (count($ents) < 2): ?>
    <div class="empty">
      <i class="fas fa-scale-balanced"></i>
      <div><strong>Karşılaştırmak için en az 2 entegratör seçmelisiniz</strong></div>
      <p style="margin:8px 0 0">Ana sayfadaki <strong>Karşılaştır</strong> butonlarıyla 2 veya 3 firma seçin,<br>sonra aşağıdaki bar'daki <strong>Karşılaştır</strong> butonuna tıklayın.</p>
      <a href="/" class="btn btn-primary" style="margin-top:16px;text-decoration:none"><i class="fas fa-arrow-left"></i> Tüm Entegratörler</a>
    </div>
  <?php else: ?>

    <!-- Özet: üst satırda firma logolar + genel -->
    <div class="cmp-grid" style="margin-bottom:0">
      <div class="cmp-row">
        <div class="cmp-cell head"></div>
        <?php foreach($ents as $e): ?>
          <div class="cmp-cell title">
            <div style="width:58px;height:58px;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:22px;color:#f6821f;overflow:hidden">
              <?php if($e['logo_url']): ?><img src="<?= h($e['logo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;padding:5px"><?php else: ?><?= h(mb_substr($e['firma_adi'],0,1)) ?><?php endif; ?>
            </div>
            <h3><a href="/e.php?s=<?= h($e['slug']) ?>" style="color:#0f172a;text-decoration:none"><?= h($e['firma_adi']) ?></a></h3>
            <div style="color:#64748b;font-size:12px;margin-top:4px"><?= h(ucfirst($e['segment'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Genel bilgi -->
      <div class="cmp-row">
        <div class="cmp-cell head">Web Sitesi</div>
        <?php foreach($ents as $e): ?>
          <div class="cmp-cell"><?= $e['website'] ? '<a href="'.h($e['website']).'" target="_blank" rel="noopener nofollow">'.h(preg_replace('~^https?://~','',$e['website'])).'</a>' : '—' ?></div>
        <?php endforeach; ?>
      </div>
      <div class="cmp-row">
        <div class="cmp-cell head">Telefon</div>
        <?php foreach($ents as $e): ?><div class="cmp-cell"><?= h($e['telefon'] ?: '—') ?></div><?php endforeach; ?>
      </div>
      <div class="cmp-row">
        <div class="cmp-cell head">ISO 27001</div>
        <?php foreach($ents as $e): ?><div class="cmp-cell <?= $e['iso_27001']?'yes':'no' ?>"><?= $e['iso_27001'] ? '✓' : '—' ?></div><?php endforeach; ?>
      </div>
      <div class="cmp-row">
        <div class="cmp-cell head">KVKK Uyumlu</div>
        <?php foreach($ents as $e): ?><div class="cmp-cell <?= $e['kvkk_uyumlu']?'yes':'no' ?>"><?= $e['kvkk_uyumlu'] ? '✓' : '—' ?></div><?php endforeach; ?>
      </div>
      <div class="cmp-row">
        <div class="cmp-cell head">GİB Onay Tarihi</div>
        <?php foreach($ents as $e): ?><div class="cmp-cell"><?= $e['gib_onay_tarihi'] ? h(date('d.m.Y', strtotime($e['gib_onay_tarihi']))) : '—' ?></div><?php endforeach; ?>
      </div>

      <!-- Hizmet türleri -->
      <div class="cmp-row">
        <div class="cmp-cell head" style="grid-column:1 / -1;background:#fff7ed;color:#9a3412;padding-top:14px;padding-bottom:14px;font-size:13px">
          <i class="fas fa-file-invoice"></i> Hizmet Türleri
        </div>
      </div>
      <?php foreach($tum_hizmetler as $hz): ?>
        <div class="cmp-row">
          <div class="cmp-cell head" style="font-weight:500;text-transform:none;color:#334155"><?= h($hz['ad']) ?></div>
          <?php foreach($ents as $e):
            $var = in_array((int)$hz['id'], $has_hz[$e['id']], true);
          ?>
            <div class="cmp-cell <?= $var?'yes':'no' ?>"><?= $var ? '✓' : '—' ?></div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <!-- ERP uyumluluğu -->
      <?php $any_erp = false; foreach($has_er as $a) if($a){$any_erp=true;break;} ?>
      <?php if($any_erp): ?>
      <div class="cmp-row">
        <div class="cmp-cell head" style="grid-column:1 / -1;background:#eff6ff;color:#1e40af;padding-top:14px;padding-bottom:14px;font-size:13px">
          <i class="fas fa-plug"></i> ERP Uyumluluğu
        </div>
      </div>
      <?php foreach($tum_erpler as $er): ?>
        <div class="cmp-row">
          <div class="cmp-cell head" style="font-weight:500;text-transform:none;color:#334155"><?= h($er['ad']) ?></div>
          <?php foreach($ents as $e):
            $var = in_array((int)$er['id'], $has_er[$e['id']], true);
          ?>
            <div class="cmp-cell <?= $var?'yes':'no' ?>"><?= $var ? '✓' : '—' ?></div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <!-- Aksiyonlar -->
      <div class="cmp-row">
        <div class="cmp-cell head"></div>
        <?php foreach($ents as $e): ?>
          <div class="cmp-cell title" style="gap:6px;display:flex;flex-direction:column">
            <a href="/e.php?s=<?= h($e['slug']) ?>" class="btn btn-primary" style="margin-bottom:6px"><i class="fas fa-circle-info"></i> Detay</a>
            <?php if($e['website']): ?>
              <a href="<?= h($e['website']) ?>" target="_blank" rel="noopener nofollow" class="btn btn-ghost"><i class="fas fa-external-link-alt"></i> Siteye Git</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Paylaş -->
    <div style="margin-top:22px;padding:14px 18px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <strong style="color:#475569;font-size:13px"><i class="fas fa-share-alt"></i> Bu karşılaştırmayı paylaş:</strong>
      <input type="text" id="cmp-url" value="<?= h(SITE_URL.$_SERVER['REQUEST_URI']) ?>" readonly style="flex:1;min-width:220px;padding:7px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;background:#f8fafc">
      <button type="button" class="btn btn-primary" onclick="var i=document.getElementById('cmp-url');i.select();document.execCommand('copy');this.innerHTML='<i class=\'fas fa-check\'></i> Kopyalandı';setTimeout(()=>this.innerHTML='<i class=\'fas fa-copy\'></i> Kopyala',2000)">
        <i class="fas fa-copy"></i> Kopyala
      </button>
    </div>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
