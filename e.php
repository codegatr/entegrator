<?php
require_once __DIR__ . '/init.php';

$slug = trim($_GET['s'] ?? '');
if (!$slug || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    header('Location: /'); exit;
}

$ent = ent_get_by_slug($pdo, $slug);
if (!$ent) {
    http_response_code(404);
    $page_title = 'Bulunamadı';
    require __DIR__ . '/partials/header.php';
    echo '<div class="container"><div class="empty" style="margin-top:40px"><i class="fas fa-search"></i><div><strong>Entegratör bulunamadı</strong></div><p>Aradığınız firma listeden kaldırılmış veya URL yanlış olabilir.</p><a href="/" class="btn btn-primary" style="margin-top:14px">Tüm Entegratörler</a></div></div>';
    require __DIR__ . '/partials/footer.php';
    exit;
}

// Görüntülenme artır (asenkron yerine sync — basit)
try {
    $pdo->prepare("UPDATE entegratorler SET goruntulenme=goruntulenme+1 WHERE id=?")->execute([$ent['id']]);
    ent_log_visit($pdo, (int)$ent['id']);
} catch(Exception $e) {}

$hizmetler = ent_get_hizmetler($pdo, (int)$ent['id']);
$erpler    = ent_get_erp($pdo, (int)$ent['id']);

// Benzer entegratörler: aynı hizmetleri veren 3 firma
$benzer = [];
if ($hizmetler) {
    $hz_ids = array_column($hizmetler, 'id');
    $in = implode(',', array_fill(0, count($hz_ids), '?'));
    $sql = "SELECT e.*, COUNT(eh.hizmet_id) ortak FROM entegratorler e
            JOIN entegrator_hizmetler eh ON eh.entegrator_id=e.id
            WHERE eh.hizmet_id IN ($in) AND e.aktif=1 AND e.id<>?
            GROUP BY e.id ORDER BY ortak DESC, e.goruntulenme DESC LIMIT 3";
    $q = $pdo->prepare($sql);
    $q->execute(array_merge($hz_ids, [$ent['id']]));
    $benzer = $q->fetchAll();
}

$page_title    = $ent['seo_title'] ?: ($ent['firma_adi'] . ' - GİB Onaylı Özel Entegratör');
$page_desc     = $ent['seo_desc']  ?: ($ent['kisa_aciklama'] ?: ($ent['firma_adi'].' GİB onaylı özel entegratörü hakkında detaylı bilgi, hizmet türleri, ERP uyumluluğu ve iletişim bilgileri.'));
$page_canonical= SITE_URL . '/e.php?s=' . $ent['slug'];
$page_og       = ['title'=>$ent['firma_adi'], 'desc'=>$page_desc, 'image'=>$ent['logo_url'] ?: (SITE_URL.'/assets/og-default.png')];

require __DIR__ . '/partials/header.php';
?>

<div class="container">
  <nav style="font-size:13px;color:#64748b;margin-bottom:16px">
    <a href="/" style="color:#64748b">Tüm Entegratörler</a>
    <i class="fas fa-chevron-right" style="font-size:10px;margin:0 6px"></i>
    <span><?= h($ent['firma_adi']) ?></span>
  </nav>

  <div class="detail-hero">
    <div class="detail-top">
      <div class="detail-logo">
        <?php if($ent['logo_url']): ?>
          <img src="<?= h($ent['logo_url']) ?>" alt="<?= h($ent['firma_adi']) ?>">
        <?php else: ?>
          <?= h(mb_substr($ent['firma_adi'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <div class="detail-title">
        <h1><?= h($ent['firma_adi']) ?></h1>
        <?php if($ent['kisa_aciklama']): ?>
          <p class="sub"><?= h($ent['kisa_aciklama']) ?></p>
        <?php endif; ?>
        <div class="detail-badges">
          <span class="badge badge-success"><i class="fas fa-circle-check"></i> GİB Onaylı</span>
          <?php if($ent['iso_27001']): ?><span class="badge badge-info"><i class="fas fa-shield-halved"></i> ISO 27001</span><?php endif; ?>
          <?php if($ent['kvkk_uyumlu']): ?><span class="badge badge-info"><i class="fas fa-lock"></i> KVKK Uyumlu</span><?php endif; ?>
          <?php if($ent['one_cikan']): ?><span class="badge badge-warn"><i class="fas fa-star"></i> Öne Çıkan</span><?php endif; ?>
          <span class="badge" style="background:#f1f5f9;color:#475569"><?= h(ucfirst($ent['segment'])) ?></span>
        </div>

        <div class="detail-actions">
          <?php if($ent['website']): ?>
            <a href="<?= h($ent['website']) ?>" target="_blank" rel="noopener nofollow" class="btn btn-primary"><i class="fas fa-external-link-alt"></i> Web Sitesi</a>
          <?php endif; ?>
          <?php if($ent['telefon']): ?>
            <a href="tel:<?= h(preg_replace('/\s+/','',$ent['telefon'])) ?>" class="btn btn-ghost"><i class="fas fa-phone"></i> <?= h($ent['telefon']) ?></a>
          <?php endif; ?>
          <?php if($ent['email']): ?>
            <a href="mailto:<?= h($ent['email']) ?>" class="btn btn-ghost"><i class="fas fa-envelope"></i> E-posta</a>
          <?php endif; ?>
          <button type="button" class="btn btn-compare" data-cmp-toggle="<?= (int)$ent['id'] ?>" data-cmp-name="<?= h($ent['firma_adi']) ?>">
            <i class="fas fa-scale-balanced"></i> Karşılaştırmaya Ekle
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="detail-grid">
    <div>
      <?php if($ent['uzun_aciklama']): ?>
      <section class="detail-sec">
        <h2><i class="fas fa-circle-info"></i> Hakkında</h2>
        <div style="color:#334155;line-height:1.7"><?= nl2br(h($ent['uzun_aciklama'])) ?></div>
      </section>
      <?php endif; ?>

      <?php if($hizmetler): ?>
      <section class="detail-sec">
        <h2><i class="fas fa-file-invoice"></i> Hizmet Türleri</h2>
        <div class="hiz-grid">
          <?php foreach($hizmetler as $hz): ?>
            <a href="/hizmet.php?h=<?= h(str_replace('_','-',$hz['kod'])) ?>" class="hiz-item" style="text-decoration:none">
              <i class="fas fa-<?= h($hz['icon']) ?>" style="color:<?= h($hz['renk']) ?>"></i>
              <?= h($hz['ad']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if($erpler): ?>
      <section class="detail-sec">
        <h2><i class="fas fa-plug"></i> ERP Uyumluluğu</h2>
        <div class="hiz-grid">
          <?php foreach($erpler as $er): ?>
            <a href="/erp.php?e=<?= h($er['kod']) ?>" class="hiz-item" style="text-decoration:none">
              <i class="fas fa-check-circle" style="color:#10b981"></i>
              <?= h($er['ad']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if($benzer): ?>
      <section class="detail-sec">
        <h2><i class="fas fa-shuffle"></i> Benzer Entegratörler</h2>
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px">
          <?php foreach($benzer as $bz): ?>
          <a href="/e.php?s=<?= h($bz['slug']) ?>" style="text-decoration:none">
            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px;display:flex;gap:10px;align-items:center;transition:border-color .15s" onmouseover="this.style.borderColor='#f6821f'" onmouseout="this.style.borderColor='#e5e7eb'">
              <div style="width:40px;height:40px;border-radius:7px;background:#fff;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:700;color:#f6821f;overflow:hidden;flex-shrink:0">
                <?php if($bz['logo_url']): ?><img src="<?= h($bz['logo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;padding:3px"><?php else: ?><?= h(mb_substr($bz['firma_adi'],0,1)) ?><?php endif; ?>
              </div>
              <div style="flex:1;min-width:0">
                <strong style="color:#0f172a;font-size:13.5px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($bz['firma_adi']) ?></strong>
                <small style="color:#64748b;font-size:11.5px"><?= (int)$bz['ortak'] ?> ortak hizmet</small>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </div>

    <aside>
      <section class="detail-sec">
        <h2><i class="fas fa-building"></i> Firma Bilgileri</h2>
        <?php if($ent['website']): ?>
          <div class="info-row"><div class="lbl">Website</div><div class="val"><a href="<?= h($ent['website']) ?>" target="_blank" rel="noopener nofollow"><?= h(preg_replace('~^https?://~','',$ent['website'])) ?></a></div></div>
        <?php endif; ?>
        <?php if($ent['telefon']): ?>
          <div class="info-row"><div class="lbl">Telefon</div><div class="val"><?= h($ent['telefon']) ?></div></div>
        <?php endif; ?>
        <?php if($ent['email']): ?>
          <div class="info-row"><div class="lbl">E-posta</div><div class="val"><?= h($ent['email']) ?></div></div>
        <?php endif; ?>
        <?php if($ent['adres']): ?>
          <div class="info-row"><div class="lbl">Adres</div><div class="val"><?= h($ent['adres']) ?></div></div>
        <?php endif; ?>
        <?php if($ent['sehir']): ?>
          <div class="info-row"><div class="lbl">Şehir</div><div class="val"><?= h($ent['sehir']) ?></div></div>
        <?php endif; ?>
        <?php if($ent['vergi_no']): ?>
          <div class="info-row"><div class="lbl">Vergi No</div><div class="val"><?= h($ent['vergi_no']) ?></div></div>
        <?php endif; ?>
      </section>

      <section class="detail-sec">
        <h2><i class="fas fa-certificate"></i> Onay & Sertifikalar</h2>
        <?php if($ent['gib_onay_tarihi']): ?>
          <div class="info-row"><div class="lbl">GİB Onay Tarihi</div><div class="val"><?= h(date('d.m.Y', strtotime($ent['gib_onay_tarihi']))) ?></div></div>
        <?php endif; ?>
        <?php if($ent['onay_numarasi']): ?>
          <div class="info-row"><div class="lbl">Onay No</div><div class="val"><?= h($ent['onay_numarasi']) ?></div></div>
        <?php endif; ?>
        <div class="info-row"><div class="lbl">ISO 27001</div><div class="val"><?= $ent['iso_27001'] ? '<i class="fas fa-check-circle" style="color:#10b981"></i> Evet' : '<span style="color:#94a3b8">Belirtilmemiş</span>' ?></div></div>
        <div class="info-row"><div class="lbl">KVKK</div><div class="val"><?= $ent['kvkk_uyumlu'] ? '<i class="fas fa-check-circle" style="color:#10b981"></i> Uyumlu' : '<span style="color:#94a3b8">Belirtilmemiş</span>' ?></div></div>
        <div class="info-row"><div class="lbl">Görüntülenme</div><div class="val"><?= number_format($ent['goruntulenme']) ?></div></div>
      </section>
    </aside>
  </div>
</div>

<!-- Schema.org Organization -->
<script type="application/ld+json">
<?= json_encode([
  "@context" => "https://schema.org",
  "@type" => "Organization",
  "name" => $ent['firma_adi'],
  "url" => $ent['website'] ?: $page_canonical,
  "logo" => $ent['logo_url'] ?: null,
  "description" => $ent['kisa_aciklama'],
  "telephone" => $ent['telefon'],
  "email" => $ent['email'],
  "address" => $ent['adres'] ? [
    "@type" => "PostalAddress",
    "streetAddress" => $ent['adres'],
    "addressLocality" => $ent['sehir'],
    "addressCountry" => "TR"
  ] : null,
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
