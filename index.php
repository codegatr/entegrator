<?php
require_once __DIR__ . '/init.php';

// ── GET parametreleri ───────────────────────────────────────
$q        = trim($_GET['q'] ?? '');
$hizmet   = $_GET['hizmet'] ?? [];  if(!is_array($hizmet)) $hizmet=[];
$erp      = $_GET['erp'] ?? [];     if(!is_array($erp)) $erp=[];
$segment  = $_GET['segment'] ?? [];  if(!is_array($segment)) $segment=[];
$sort     = in_array($_GET['sort'] ?? '', ['yeni','populer','isim_asc','isim_desc'], true) ? $_GET['sort'] : 'populer';
$sayfa    = max(1, (int)($_GET['p'] ?? 1));
$per_page = 18;

// ── SQL filtreler ───────────────────────────────────────────
$where  = "e.aktif=1";
$params = [];
$joins  = "";

if ($q !== '') {
    $where .= " AND (e.firma_adi LIKE ? OR e.kisa_aciklama LIKE ? OR e.slug LIKE ?)";
    $lk = "%$q%";
    array_push($params, $lk, $lk, $lk);
}
if ($hizmet) {
    $in = implode(',', array_fill(0, count($hizmet), '?'));
    $joins .= " AND EXISTS(SELECT 1 FROM entegrator_hizmetler eh JOIN hizmet_turleri h ON h.id=eh.hizmet_id WHERE eh.entegrator_id=e.id AND h.kod IN ($in))";
    $params = array_merge($params, $hizmet);
}
if ($erp) {
    $in = implode(',', array_fill(0, count($erp), '?'));
    $joins .= " AND EXISTS(SELECT 1 FROM entegrator_erp ee JOIN erp_uyumluluk r ON r.id=ee.erp_id WHERE ee.entegrator_id=e.id AND r.kod IN ($in))";
    $params = array_merge($params, $erp);
}
if ($segment) {
    // whitelist
    $segment = array_values(array_intersect($segment, ['kobi','kurumsal','karma']));
    if ($segment) {
        $in = implode(',', array_fill(0, count($segment), '?'));
        $where .= " AND e.segment IN ($in)";
        $params = array_merge($params, $segment);
    }
}

$order = match($sort) {
    'yeni'      => 'e.created_at DESC',
    'isim_asc'  => 'e.firma_adi ASC',
    'isim_desc' => 'e.firma_adi DESC',
    default     => 'e.one_cikan DESC, e.goruntulenme DESC, e.siralama ASC',
};

// ── Toplam + sayfalama ──────────────────────────────────────
$cnt_sql = "SELECT COUNT(*) FROM entegratorler e WHERE $where $joins";
$cq = $pdo->prepare($cnt_sql);
$cq->execute($params);
$toplam = (int)$cq->fetchColumn();
$toplam_sayfa = max(1, (int)ceil($toplam / $per_page));
$sayfa = min($sayfa, $toplam_sayfa);
$offset = ($sayfa - 1) * $per_page;

// ── Liste ──────────────────────────────────────────────────
$sql = "SELECT e.* FROM entegratorler e WHERE $where $joins ORDER BY $order LIMIT $per_page OFFSET $offset";
$st = $pdo->prepare($sql);
$st->execute($params);
$liste = $st->fetchAll(PDO::FETCH_ASSOC);

// Her entegratörün hizmetlerini tek sorguda çek
$ids = array_column($liste, 'id');
$ent_hiz = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q2 = $pdo->prepare("SELECT eh.entegrator_id, h.ad, h.kod, h.renk FROM entegrator_hizmetler eh JOIN hizmet_turleri h ON h.id=eh.hizmet_id WHERE eh.entegrator_id IN ($in) ORDER BY h.siralama");
    $q2->execute($ids);
    foreach ($q2->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $ent_hiz[$r['entegrator_id']][] = $r;
    }
}

// ── Filtre sayıları ─────────────────────────────────────────
$hizmetler = $pdo->query("SELECT h.id, h.kod, h.ad, h.icon, h.renk, (SELECT COUNT(*) FROM entegrator_hizmetler eh JOIN entegratorler e ON e.id=eh.entegrator_id WHERE eh.hizmet_id=h.id AND e.aktif=1) AS cnt FROM hizmet_turleri h ORDER BY h.siralama")->fetchAll();
$erpler    = $pdo->query("SELECT r.id, r.kod, r.ad, (SELECT COUNT(*) FROM entegrator_erp ee JOIN entegratorler e ON e.id=ee.entegrator_id WHERE ee.erp_id=r.id AND e.aktif=1) AS cnt FROM erp_uyumluluk r ORDER BY r.siralama")->fetchAll();

// İstatistik
$stats = $pdo->query("SELECT COUNT(*) toplam, SUM(CASE WHEN iso_27001=1 THEN 1 ELSE 0 END) iso FROM entegratorler WHERE aktif=1")->fetch();

$page_title = $q ? "\"$q\" arama sonuçları" : 'GİB Onaylı Özel Entegratörler';
$page_desc  = 'GİB tarafından onaylı tüm e-Fatura, e-Arşiv ve e-Dönüşüm özel entegratörlerini karşılaştırın. ERP uyumluluğu, hizmet türleri, sertifikalara göre filtreleyin.';

require __DIR__ . '/partials/header.php';
?>

<section class="hero">
  <div class="hero-wrap">
    <h1>GİB Onaylı <span>Özel Entegratörler</span> Tek Yerde</h1>
    <p>Türkiye'deki tüm e-Fatura, e-Arşiv ve e-Dönüşüm özel entegratörlerini <strong>filtreleyin, karşılaştırın, doğru tercihi yapın</strong>.</p>
    <form method="GET" class="hero-search" action="/">
      <i class="fas fa-search"></i>
      <input type="text" name="q" id="hero-search-inp" value="<?= h($q) ?>" placeholder="Firma adı, hizmet, ERP..." autocomplete="off">
      <div id="hero-search-dd" class="sr-dd"></div>
    </form>
    <div class="hero-stats">
      <div class="stat"><span class="num"><?= (int)$stats['toplam'] ?></span><span class="lbl">Entegratör</span></div>
      <div class="stat"><span class="num"><?= count($hizmetler) ?></span><span class="lbl">Hizmet Türü</span></div>
      <div class="stat"><span class="num"><?= (int)$stats['iso'] ?></span><span class="lbl">ISO 27001</span></div>
      <div class="stat"><span class="num"><?= count($erpler) ?></span><span class="lbl">ERP Uyumlu</span></div>
    </div>
  </div>
</section>

<div class="container">
  <button class="filter-toggle-mob" onclick="document.querySelector('.sidebar').classList.toggle('closed')" style="display:none">
    <i class="fas fa-filter"></i> Filtreler
  </button>
  <div class="layout">
    <aside class="sidebar">
      <form method="GET" action="/" id="filter-form">
        <?php if($q): ?><input type="hidden" name="q" value="<?= h($q) ?>"><?php endif; ?>

        <div class="filter-group">
          <h3><i class="fas fa-file-invoice"></i> Hizmet Türü</h3>
          <?php foreach($hizmetler as $hz): ?>
            <label class="filter-row">
              <input type="checkbox" name="hizmet[]" value="<?= h($hz['kod']) ?>" <?= in_array($hz['kod'],$hizmet)?'checked':'' ?>>
              <span><?= h($hz['ad']) ?></span>
              <span class="count"><?= (int)$hz['cnt'] ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="filter-group">
          <h3><i class="fas fa-link"></i> ERP Uyumluluğu</h3>
          <?php foreach($erpler as $rp): if(!$rp['cnt']) continue; ?>
            <label class="filter-row">
              <input type="checkbox" name="erp[]" value="<?= h($rp['kod']) ?>" <?= in_array($rp['kod'],$erp)?'checked':'' ?>>
              <span><?= h($rp['ad']) ?></span>
              <span class="count"><?= (int)$rp['cnt'] ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="filter-group">
          <h3><i class="fas fa-building"></i> Segment</h3>
          <?php foreach([['kobi','KOBİ'],['kurumsal','Kurumsal'],['karma','Karma']] as $sg): ?>
            <label class="filter-row">
              <input type="checkbox" name="segment[]" value="<?= $sg[0] ?>" <?= in_array($sg[0],$segment)?'checked':'' ?>>
              <span><?= $sg[1] ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <?php if($q || $hizmet || $erp || $segment): ?>
          <a href="/" class="filter-clear" style="display:inline-block;text-align:center;text-decoration:none"><i class="fas fa-times"></i> Filtreleri Temizle</a>
        <?php endif; ?>
      </form>
    </aside>

    <main>
      <div class="main-head">
        <h2>
          <?php if($q): ?>"<?= h($q) ?>" için sonuçlar<?php else: ?>Tüm Entegratörler<?php endif; ?>
        </h2>
        <span class="result-count"><?= $toplam ?> sonuç</span>
        <div class="sort-box">
          <span style="color:#64748b;font-size:13px">Sırala:</span>
          <select onchange="var u=new URL(location);u.searchParams.set('sort',this.value);location=u;">
            <option value="populer" <?= $sort==='populer'?'selected':'' ?>>Popülerlik</option>
            <option value="yeni" <?= $sort==='yeni'?'selected':'' ?>>En Yeni</option>
            <option value="isim_asc" <?= $sort==='isim_asc'?'selected':'' ?>>A → Z</option>
            <option value="isim_desc" <?= $sort==='isim_desc'?'selected':'' ?>>Z → A</option>
          </select>
        </div>
      </div>

      <?php if(empty($liste)): ?>
        <div class="empty">
          <i class="fas fa-search"></i>
          <div><strong>Sonuç bulunamadı</strong></div>
          <p style="margin:8px 0 0">Farklı anahtar kelimeler veya filtrelerle tekrar deneyin.</p>
        </div>
      <?php else: ?>
        <div class="grid">
          <?php foreach($liste as $e):
            $hzlist = $ent_hiz[$e['id']] ?? [];
          ?>
          <article class="card <?= $e['one_cikan']?'onec':'' ?>">
            <div class="card-head">
              <div class="card-logo">
                <?php if($e['logo_url']): ?>
                  <img src="<?= h($e['logo_url']) ?>" alt="<?= h($e['firma_adi']) ?>" loading="lazy">
                <?php else: ?>
                  <?= h(mb_substr($e['firma_adi'], 0, 1)) ?>
                <?php endif; ?>
              </div>
              <div class="card-info">
                <h3><a href="/e.php?s=<?= h($e['slug']) ?>"><?= h($e['firma_adi']) ?></a></h3>
                <div class="segment"><?= h(ucfirst($e['segment'])) ?><?php if($e['iso_27001']): ?> · <i class="fas fa-shield-halved" title="ISO 27001"></i><?php endif; ?></div>
              </div>
            </div>
            <?php if($e['kisa_aciklama']): ?>
              <p class="card-desc"><?= h($e['kisa_aciklama']) ?></p>
            <?php endif; ?>
            <?php if($hzlist): ?>
            <div class="card-tags">
              <?php foreach(array_slice($hzlist, 0, 4) as $hz): ?>
                <span class="tag"><?= h($hz['ad']) ?></span>
              <?php endforeach; ?>
              <?php if(count($hzlist) > 4): ?>
                <span class="tag more">+<?= count($hzlist)-4 ?></span>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="card-foot">
              <a href="/e.php?s=<?= h($e['slug']) ?>" class="btn btn-primary"><i class="fas fa-circle-info"></i> Detay</a>
              <?php if($e['website']): ?>
                <a href="<?= h($e['website']) ?>" target="_blank" rel="noopener nofollow" class="btn btn-ghost" title="Web sitesi"><i class="fas fa-external-link-alt"></i></a>
              <?php endif; ?>
              <button type="button" class="btn btn-compare" data-cmp-toggle="<?= (int)$e['id'] ?>" data-cmp-name="<?= h($e['firma_adi']) ?>">
                <i class="fas fa-scale-balanced"></i> Karşılaştır
              </button>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <?php if($toplam_sayfa > 1): ?>
        <nav class="pg">
          <?php
            $qs = $_GET; unset($qs['p']);
            $base = '/?' . http_build_query($qs);
            $sep  = strpos($base, '?')===false ? '?' : '&';
            for($i=1; $i<=$toplam_sayfa; $i++):
              if($i == $sayfa): echo '<span class="cur">'.$i.'</span>';
              else: echo '<a href="'.$base.$sep.'p='.$i.'">'.$i.'</a>';
              endif;
            endfor;
          ?>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
</div>

<style>
.sr-dd{position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-top:6px;max-height:380px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,0.08);display:none;z-index:50}
.sr-item{display:flex;gap:11px;padding:11px 14px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;align-items:center}
.sr-item:hover{background:#fff7ed}
.sr-item:last-child{border-bottom:none}
.sr-logo{width:36px;height:36px;border-radius:7px;background:#f8fafc;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:700;color:#f6821f;overflow:hidden;flex-shrink:0}
.sr-logo img{width:100%;height:100%;object-fit:contain;padding:3px}
.sr-info{flex:1;min-width:0}
.sr-info strong{display:block;font-size:14px;color:#0f172a}
.sr-info small{color:#64748b;font-size:12px}
.sr-empty{padding:20px;text-align:center;color:#94a3b8;font-size:13px}
@media(max-width:700px){.filter-toggle-mob{display:block !important}.sidebar.closed{display:none}}
</style>

<?php require __DIR__ . '/partials/footer.php'; ?>
