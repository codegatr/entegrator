<?php
/**
 * Admin panel — tek dosya pattern
 * Login + Dashboard + Entegratör CRUD + TSV Import + Mesajlar + Şifre
 */
require_once __DIR__ . '/init.php';

// ═════════ LOGOUT ════════════════════════════════════════════
if (isset($_GET['logout'])) {
    $_SESSION = []; session_destroy();
    header('Location: /yonetim.php'); exit;
}

// ═════════ LOGIN POST ════════════════════════════════════════
$login_err = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act'] ?? '')==='login') {
    if (!csrf_verify($_POST['csrf'] ?? '')) { $login_err = 'Güvenlik hatası.'; }
    else {
        $u = trim($_POST['u'] ?? ''); $p = $_POST['p'] ?? '';
        $q = $pdo->prepare("SELECT * FROM admin_kullanicilar WHERE kullanici_adi=? AND aktif=1 LIMIT 1");
        $q->execute([$u]);
        $adm = $q->fetch();
        if (!$adm || !password_verify($p, $adm['sifre_hash'])) {
            $login_err = 'Kullanıcı adı veya şifre yanlış.'; sleep(1);
        } else {
            $_SESSION['admin_id']   = (int)$adm['id'];
            $_SESSION['admin_user'] = $adm['kullanici_adi'];
            $_SESSION['admin_rol']  = $adm['rol'];
            $_SESSION['force_pwd']  = empty($adm['sifre_degistirildi']);
            $pdo->prepare("UPDATE admin_kullanicilar SET son_giris=NOW(), son_ip=? WHERE id=?")
                ->execute([$_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''), $adm['id']]);
            header('Location: /yonetim.php?p=dashboard'); exit;
        }
    }
}

// ═════════ LOGIN EKRANI ══════════════════════════════════════
if (!admin_logged_in()) { ?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Yönetici Girişi · <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{background:linear-gradient(135deg,#0f172a,#1e293b);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;margin:0}
.lb{background:#fff;border-radius:14px;padding:32px;max-width:400px;width:100%;box-shadow:0 20px 50px rgba(0,0,0,0.3)}
.lb h1{margin:0 0 6px;font-size:22px;font-weight:800}
.lb .sub{color:#64748b;font-size:13px;margin-bottom:22px}
.lb .lg{width:52px;height:52px;background:linear-gradient(135deg,#f6821f,#faae40);border-radius:11px;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:14px}
.lb input{width:100%;padding:11px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;margin-bottom:12px}
.lb input:focus{outline:none;border-color:#f6821f;box-shadow:0 0 0 3px rgba(246,130,31,.15)}
.lb button{width:100%;background:#f6821f;color:#fff;border:none;padding:12px;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer}
.lb button:hover{background:#e2761b}
.er{background:#fee2e2;color:#7f1d1d;padding:10px 14px;border-radius:7px;font-size:13px;margin-bottom:14px;border:1px solid #fca5a5}
</style></head><body>
<div class="lb">
  <div class="lg"><i class="fas fa-lock"></i></div>
  <h1>Yönetici Girişi</h1>
  <div class="sub"><?= h(SITE_NAME) ?> admin paneli</div>
  <?php if($login_err): ?><div class="er"><i class="fas fa-exclamation-circle"></i> <?= h($login_err) ?></div><?php endif; ?>
  <form method="POST" autocomplete="off">
    <input type="hidden" name="act" value="login">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="text" name="u" placeholder="Kullanıcı adı" required autofocus>
    <input type="password" name="p" placeholder="Şifre" required>
    <button type="submit"><i class="fas fa-sign-in-alt"></i> Giriş</button>
  </form>
  <div style="margin-top:16px;font-size:11px;color:#94a3b8;text-align:center"><a href="/" style="color:#94a3b8;text-decoration:none">← Siteye Dön</a></div>
</div></body></html>
<?php exit; }

// ═════════ ADMIN SAYFA MANTIK ════════════════════════════════
$adm_id   = (int)$_SESSION['admin_id'];
$adm_user = $_SESSION['admin_user'] ?? '';
$page     = $_GET['p'] ?? 'dashboard';
$msg = ''; $msg_tip = '';

// ─── Şifre değiştir POST ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act'] ?? '')==='sifre_degistir' && csrf_verify($_POST['csrf'] ?? '')) {
    $yeni  = $_POST['yeni'] ?? '';
    $yeni2 = $_POST['yeni2'] ?? '';
    if (strlen($yeni) < 8)  { $msg='Şifre en az 8 karakter olmalı.'; $msg_tip='danger'; }
    elseif ($yeni !== $yeni2) { $msg='Şifreler eşleşmiyor.'; $msg_tip='danger'; }
    else {
        $pdo->prepare("UPDATE admin_kullanicilar SET sifre_hash=?, sifre_degistirildi=1 WHERE id=?")
            ->execute([password_hash($yeni, PASSWORD_BCRYPT), $adm_id]);
        $_SESSION['force_pwd'] = false;
        $msg = 'Şifre güncellendi.'; $msg_tip='success';
    }
}

// ─── Entegratör kaydet POST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act'] ?? '')==='ent_kaydet' && csrf_verify($_POST['csrf'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    $firma_adi = trim($_POST['firma_adi'] ?? '');
    $slug_in = trim($_POST['slug'] ?? '') ?: slugify($firma_adi);
    $data = [
        'slug' => $slug_in,
        'firma_adi' => $firma_adi,
        'kisa_aciklama' => trim($_POST['kisa_aciklama'] ?? '') ?: null,
        'uzun_aciklama' => trim($_POST['uzun_aciklama'] ?? '') ?: null,
        'website'  => trim($_POST['website'] ?? '') ?: null,
        'telefon'  => trim($_POST['telefon'] ?? '') ?: null,
        'email'    => trim($_POST['email'] ?? '') ?: null,
        'adres'    => trim($_POST['adres'] ?? '') ?: null,
        'sehir'    => trim($_POST['sehir'] ?? '') ?: null,
        'vergi_no' => trim($_POST['vergi_no'] ?? '') ?: null,
        'gib_onay_tarihi' => trim($_POST['gib_onay_tarihi'] ?? '') ?: null,
        'onay_numarasi'   => trim($_POST['onay_numarasi'] ?? '') ?: null,
        'iso_27001'       => !empty($_POST['iso_27001']) ? 1 : 0,
        'kvkk_uyumlu'     => !empty($_POST['kvkk_uyumlu']) ? 1 : 0,
        'segment' => in_array($_POST['segment'] ?? 'karma', ['kobi','kurumsal','karma'], true) ? $_POST['segment'] : 'karma',
        'aktif'     => !empty($_POST['aktif']) ? 1 : 0,
        'one_cikan' => !empty($_POST['one_cikan']) ? 1 : 0,
        'siralama'  => (int)($_POST['siralama'] ?? 100),
        'seo_title' => trim($_POST['seo_title'] ?? '') ?: null,
        'seo_desc'  => trim($_POST['seo_desc'] ?? '') ?: null,
    ];
    if (!$data['firma_adi']) { $msg='Firma adı zorunlu.'; $msg_tip='danger'; }
    else {
      try {
        // Logo upload
        if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error']===UPLOAD_ERR_OK) {
            $mime = mime_content_type($_FILES['logo']['tmp_name']);
            $allow = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/svg+xml'=>'svg'];
            if (!isset($allow[$mime])) throw new Exception('Logo JPG/PNG/WEBP/SVG olmalı.');
            if ($_FILES['logo']['size'] > 500*1024) throw new Exception('Logo max 500KB olmalı.');
            $ext = $allow[$mime];
            $fn  = $data['slug'].'-'.time().'.'.$ext;
            if (!is_dir(LOGOS_PATH)) @mkdir(LOGOS_PATH, 0755, true);
            move_uploaded_file($_FILES['logo']['tmp_name'], LOGOS_PATH.'/'.$fn);
            $data['logo_url'] = LOGOS_URL.'/'.$fn;
        }

        if ($id > 0) {
            $set = []; $vals = [];
            foreach ($data as $k=>$v) { $set[] = "$k=?"; $vals[] = $v; }
            $vals[] = $id;
            $pdo->prepare("UPDATE entegratorler SET ".implode(',', $set)." WHERE id=?")->execute($vals);
        } else {
            $cols = array_keys($data);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $pdo->prepare("INSERT INTO entegratorler (".implode(',', $cols).") VALUES ($ph)")
                ->execute(array_values($data));
            $id = (int)$pdo->lastInsertId();
        }
        // M2M: hizmetler
        $pdo->prepare("DELETE FROM entegrator_hizmetler WHERE entegrator_id=?")->execute([$id]);
        foreach ((array)($_POST['hizmetler'] ?? []) as $hzid) {
            $pdo->prepare("INSERT IGNORE INTO entegrator_hizmetler (entegrator_id, hizmet_id) VALUES (?,?)")->execute([$id, (int)$hzid]);
        }
        // M2M: ERP
        $pdo->prepare("DELETE FROM entegrator_erp WHERE entegrator_id=?")->execute([$id]);
        foreach ((array)($_POST['erpler'] ?? []) as $erid) {
            $pdo->prepare("INSERT IGNORE INTO entegrator_erp (entegrator_id, erp_id) VALUES (?,?)")->execute([$id, (int)$erid]);
        }
        $_SESSION['flash'] = ['msg'=>'Kaydedildi.', 'tip'=>'success'];
        header('Location: /yonetim.php?p=ent_duzenle&id='.$id); exit;
      } catch(Exception $e) { $msg='Hata: '.$e->getMessage(); $msg_tip='danger'; }
    }
}

// ─── Entegratör sil ──────────────────────────────────────────
if (($_GET['p'] ?? '')==='ent_sil' && !empty($_GET['id']) && !empty($_GET['csrf']) && hash_equals(csrf_token(), $_GET['csrf'])) {
    $pdo->prepare("DELETE FROM entegratorler WHERE id=?")->execute([(int)$_GET['id']]);
    $_SESSION['flash'] = ['msg'=>'Silindi.', 'tip'=>'success'];
    header('Location: /yonetim.php?p=entegratorler'); exit;
}

// ─── TSV import POST (GIB listesi yükleme) ───────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act'] ?? '')==='tsv_import' && csrf_verify($_POST['csrf'] ?? '')) {
    $tsv = $_POST['tsv'] ?? '';
    $auto_active = !empty($_POST['auto_active']);
    $skip_header = !empty($_POST['skip_header']);
    $update_existing = !empty($_POST['update_existing']);

    if (strlen(trim($tsv)) < 20) { $msg='TSV verisi çok kısa.'; $msg_tip='danger'; }
    else {
        $lines = preg_split('~\r\n|\r|\n~', trim($tsv));
        if ($skip_header && !empty($lines)) array_shift($lines);

        $ins=0; $upd=0; $skip=0; $err=0;
        foreach ($lines as $ln) {
            $cols = explode("\t", $ln);
            if (count($cols) < 1) { $skip++; continue; }
            $firma = trim($cols[0] ?? '');
            $sehir = trim($cols[1] ?? '');
            $tel   = trim($cols[2] ?? '');
            $mail  = trim($cols[3] ?? '');
            if (strlen($firma) < 3) { $skip++; continue; }

            $slug = slugify($firma);
            $chk = $pdo->prepare("SELECT id FROM entegratorler WHERE slug=? OR firma_adi=? LIMIT 1");
            $chk->execute([$slug, $firma]);
            $exist = $chk->fetchColumn();

            if ($exist) {
                if ($update_existing) {
                    $pdo->prepare("UPDATE entegratorler SET sehir=?, telefon=?, email=? WHERE id=?")
                        ->execute([$sehir ?: null, $tel ?: null, $mail ?: null, $exist]);
                    $upd++;
                } else { $skip++; }
            } else {
                try {
                    $pdo->prepare("INSERT INTO entegratorler (slug, firma_adi, sehir, telefon, email, aktif, segment) VALUES (?,?,?,?,?,?,?)")
                        ->execute([$slug, $firma, $sehir ?: null, $tel ?: null, $mail ?: null, $auto_active?1:0, 'karma']);
                    $ins++;
                } catch(Exception $e) { $err++; }
            }
        }
        $msg = "$ins eklendi · $upd güncellendi · $skip atlandı" . ($err>0?" · $err hata":"");
        $msg_tip = 'success';
    }
}

// ─── Flash oku ───────────────────────────────────────────────
if (!empty($_SESSION['flash'])) { $msg = $_SESSION['flash']['msg']; $msg_tip = $_SESSION['flash']['tip']; unset($_SESSION['flash']); }

// Manifest version
$app_ver = '1.0.0';
try { $m = json_decode(file_get_contents(__DIR__.'/manifest.json'), true); if (!empty($m['version'])) $app_ver = $m['version']; } catch(Exception $e) {}
?>
<!DOCTYPE html><html lang="tr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Yönetim · <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body{background:#f1f5f9;margin:0}
.al{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
.as{background:#0f172a;color:#cbd5e1;padding:18px 0}
.as h1{color:#fff;font-size:15px;padding:0 18px 16px;margin:0;border-bottom:1px solid #1e293b;display:flex;align-items:center;gap:8px}
.as h1 .d{width:30px;height:30px;background:linear-gradient(135deg,#f6821f,#faae40);border-radius:6px;color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px}
.as nav{padding:12px 0}
.as a{display:flex;align-items:center;gap:10px;padding:10px 18px;color:#cbd5e1;text-decoration:none;font-size:14px;transition:all .12s}
.as a:hover{background:#1e293b;color:#fff}
.as a.on{background:#f6821f;color:#fff}
.as a i{width:16px;text-align:center}
.as .ft{padding:14px 18px;border-top:1px solid #1e293b;margin-top:20px;font-size:12px;color:#64748b}
.am{padding:24px}
.ah{display:flex;align-items:center;gap:14px;margin-bottom:20px;flex-wrap:wrap}
.ah h2{margin:0;font-size:22px;font-weight:700}
.ah .r{margin-left:auto;display:flex;gap:8px}
.ak{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:22px}
.ak .c{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px}
.ak .n{font-size:24px;font-weight:800;color:#0f172a}
.ak .l{font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-top:2px}
.ap{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:16px}
.aph{padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:14px}
.apb{padding:18px}
.at{width:100%;border-collapse:collapse;font-size:13.5px}
.at th,.at td{padding:9px 12px;text-align:left;border-bottom:1px solid #f1f5f9}
.at th{background:#f8fafc;font-weight:600;color:#475569;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px}
.al-msg{padding:11px 15px;border-radius:7px;margin-bottom:16px;font-size:13.5px}
.al-succ{background:#dcfce7;color:#14532d;border:1px solid #86efac}
.al-err{background:#fee2e2;color:#7f1d1d;border:1px solid #fca5a5}
.al-warn{background:#fef3c7;color:#78350f;border:1px solid #fcd34d}
.fg{margin-bottom:13px}
.fg label{display:block;font-weight:600;font-size:13px;color:#334155;margin-bottom:4px}
.fg input[type=text],.fg input[type=email],.fg input[type=tel],.fg input[type=url],.fg input[type=date],.fg input[type=number],.fg input[type=password],.fg select,.fg textarea,.fg input[type=file]{width:100%;padding:9px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;box-sizing:border-box;font-family:inherit}
.fg textarea{resize:vertical;min-height:80px}
.fg-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.chkg{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:6px}
.chkr{display:flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;background:#fff;font-size:13px}
.chkr:has(input:checked){border-color:#f6821f;background:#fff7ed}
.chkr input{margin:0;accent-color:#f6821f}
.btn{padding:9px 16px;border:none;border-radius:7px;font-size:13.5px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn-pri{background:#f6821f;color:#fff}.btn-pri:hover{background:#e2761b;color:#fff}
.btn-ghost{background:#f1f5f9;color:#334155}.btn-ghost:hover{background:#e2e8f0}
.btn-dng{background:#dc2626;color:#fff}.btn-dng:hover{background:#b91c1c;color:#fff}
.btn-sm{padding:5px 9px;font-size:12px}
.bdg{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
.bok{background:#dcfce7;color:#14532d}.bno{background:#fee2e2;color:#7f1d1d}
@media(max-width:800px){.al{grid-template-columns:1fr}.as{display:none}.fg-row{grid-template-columns:1fr}}
</style></head><body>
<div class="al">
  <aside class="as">
    <h1><span class="d"><i class="fas fa-plug"></i></span> Yönetim</h1>
    <nav>
      <?php $nav=[
        ['dashboard','Dashboard','chart-line'],
        ['entegratorler','Entegratörler','building'],
        ['ent_ekle','Yeni Ekle','plus-circle'],
        ['tsv_import','TSV Import','file-import'],
        ['mesajlar','Mesajlar','envelope'],
        ['sifre','Şifre Değiştir','key'],
      ]; foreach($nav as $n):
        $on = str_starts_with($page, $n[0]) ? 'on' : '';
      ?>
        <a href="/yonetim.php?p=<?= $n[0] ?>" class="<?= $on ?>"><i class="fas fa-<?= $n[2] ?>"></i> <?= $n[1] ?></a>
      <?php endforeach; ?>
      <a href="/yonetim.php?logout=1" style="margin-top:30px;color:#fca5a5"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
      <a href="/" target="_blank"><i class="fas fa-external-link-alt"></i> Siteyi Gör</a>
    </nav>
    <div class="ft"><?= h($adm_user) ?> · v<?= h($app_ver) ?></div>
  </aside>

  <main class="am">
    <?php if (!empty($_SESSION['force_pwd']) && $page !== 'sifre'): ?>
      <div class="al-msg al-warn">
        <i class="fas fa-exclamation-triangle"></i> İlk giriş — güvenlik için şifreyi değiştirin.
        <a href="/yonetim.php?p=sifre" style="margin-left:8px"><strong>Şimdi değiştir →</strong></a>
      </div>
    <?php endif; ?>
    <?php if($msg): ?><div class="al-msg al-<?= $msg_tip==='success'?'succ':($msg_tip==='danger'?'err':'warn') ?>"><?= h($msg) ?></div><?php endif; ?>

<?php
// ═══ SAYFA İÇERİKLERİ ════════════════════════════════════════
if ($page === 'dashboard'):
    $k_ent = (int)$pdo->query("SELECT COUNT(*) FROM entegratorler")->fetchColumn();
    $k_akt = (int)$pdo->query("SELECT COUNT(*) FROM entegratorler WHERE aktif=1")->fetchColumn();
    $k_hz  = (int)$pdo->query("SELECT COUNT(*) FROM hizmet_turleri")->fetchColumn();
    $k_gz  = (int)($pdo->query("SELECT SUM(goruntulenme) FROM entegratorler")->fetchColumn() ?: 0);
    $k_msg = 0; try { $k_msg = (int)$pdo->query("SELECT COUNT(*) FROM iletisim_mesajlari WHERE okundu=0")->fetchColumn(); } catch(Exception $e) {}
    $pop = $pdo->query("SELECT * FROM entegratorler WHERE aktif=1 ORDER BY goruntulenme DESC LIMIT 10")->fetchAll();
?>
    <div class="ah"><h2>Dashboard</h2></div>
    <div class="ak">
      <div class="c"><div class="n"><?= $k_ent ?></div><div class="l">Toplam Entegratör</div></div>
      <div class="c"><div class="n" style="color:#10b981"><?= $k_akt ?></div><div class="l">Aktif</div></div>
      <div class="c"><div class="n" style="color:#3b82f6"><?= $k_hz ?></div><div class="l">Hizmet Türü</div></div>
      <div class="c"><div class="n" style="color:#f6821f"><?= number_format($k_gz) ?></div><div class="l">Görüntülenme</div></div>
      <div class="c"><div class="n" style="color:#ec4899"><?= $k_msg ?></div><div class="l">Okunmamış Mesaj</div></div>
    </div>
    <div class="ap">
      <div class="aph"><i class="fas fa-fire"></i> En Popüler</div>
      <table class="at"><thead><tr><th>#</th><th>Firma</th><th>Görüntülenme</th><th></th></tr></thead><tbody>
        <?php foreach($pop as $i=>$e): ?>
          <tr><td><?= $i+1 ?></td><td><strong><?= h($e['firma_adi']) ?></strong></td>
          <td><?= number_format($e['goruntulenme']) ?></td>
          <td style="text-align:right"><a href="/yonetim.php?p=ent_duzenle&id=<?= $e['id'] ?>" class="btn btn-ghost btn-sm"><i class="fas fa-edit"></i></a></td></tr>
        <?php endforeach; ?>
        <?php if(empty($pop)): ?><tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px">Henüz kayıt yok. <a href="/yonetim.php?p=tsv_import">TSV import ile toplu ekle →</a></td></tr><?php endif; ?>
      </tbody></table>
    </div>

<?php elseif ($page === 'entegratorler'):
    $srh = trim($_GET['q'] ?? '');
    $whr = "1=1"; $par = [];
    if ($srh) { $whr .= " AND (firma_adi LIKE ? OR slug LIKE ?)"; $par[]="%$srh%"; $par[]="%$srh%"; }
    $q = $pdo->prepare("SELECT * FROM entegratorler WHERE $whr ORDER BY created_at DESC LIMIT 500");
    $q->execute($par); $rows = $q->fetchAll();
?>
    <div class="ah">
      <h2>Entegratörler <span style="color:#94a3b8;font-size:14px;font-weight:400">(<?= count($rows) ?>)</span></h2>
      <div class="r">
        <form method="GET" style="display:flex;gap:6px">
          <input type="hidden" name="p" value="entegratorler">
          <input type="text" name="q" value="<?= h($srh) ?>" placeholder="Firma ara..." style="padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px">
          <button type="submit" class="btn btn-ghost"><i class="fas fa-search"></i></button>
        </form>
        <a href="/yonetim.php?p=ent_ekle" class="btn btn-pri"><i class="fas fa-plus"></i> Yeni</a>
      </div>
    </div>
    <div class="ap">
      <table class="at">
        <thead><tr><th>Firma</th><th>Şehir</th><th>Segment</th><th>Hizmet</th><th>Durum</th><th>Görünt.</th><th></th></tr></thead>
        <tbody>
        <?php foreach($rows as $e):
          $hc = $pdo->prepare("SELECT COUNT(*) FROM entegrator_hizmetler WHERE entegrator_id=?"); $hc->execute([$e['id']]);
          $hsayi = (int)$hc->fetchColumn();
        ?>
        <tr>
          <td>
            <div style="display:flex;gap:9px;align-items:center">
              <div style="width:32px;height:32px;border-radius:6px;background:#f8fafc;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:700;color:#f6821f;overflow:hidden">
                <?php if($e['logo_url']): ?><img src="<?= h($e['logo_url']) ?>" style="width:100%;height:100%;object-fit:contain;padding:3px"><?php else: ?><?= h(mb_substr($e['firma_adi'],0,1)) ?><?php endif; ?>
              </div>
              <strong><?= h($e['firma_adi']) ?></strong>
              <?php if($e['one_cikan']): ?><i class="fas fa-star" style="color:#f6821f" title="Öne çıkan"></i><?php endif; ?>
            </div>
          </td>
          <td style="color:#64748b;font-size:12px"><?= h($e['sehir'] ?: '—') ?></td>
          <td><?= h(ucfirst($e['segment'])) ?></td>
          <td><?= $hsayi ?></td>
          <td><span class="bdg <?= $e['aktif']?'bok':'bno' ?>"><?= $e['aktif']?'Aktif':'Pasif' ?></span></td>
          <td style="text-align:right;font-variant-numeric:tabular-nums"><?= number_format($e['goruntulenme']) ?></td>
          <td style="text-align:right;white-space:nowrap">
            <a href="/e.php?s=<?= h($e['slug']) ?>" target="_blank" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i></a>
            <a href="/yonetim.php?p=ent_duzenle&id=<?= $e['id'] ?>" class="btn btn-pri btn-sm"><i class="fas fa-edit"></i></a>
            <a href="/yonetim.php?p=ent_sil&id=<?= $e['id'] ?>&csrf=<?= h(csrf_token()) ?>" class="btn btn-dng btn-sm" onclick="return confirm('«<?= h(addslashes($e['firma_adi'])) ?>» silinsin mi?')"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($rows)): ?><tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:30px">Kayıt yok. <a href="/yonetim.php?p=tsv_import">TSV import</a> ile toplu ekleyebilirsin.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

<?php elseif ($page === 'ent_ekle' || $page === 'ent_duzenle'):
    $id = (int)($_GET['id'] ?? 0);
    $e = ['id'=>0,'slug'=>'','firma_adi'=>'','kisa_aciklama'=>'','uzun_aciklama'=>'','logo_url'=>'','website'=>'','telefon'=>'','email'=>'','adres'=>'','sehir'=>'','vergi_no'=>'','gib_onay_tarihi'=>'','onay_numarasi'=>'','iso_27001'=>0,'kvkk_uyumlu'=>0,'segment'=>'karma','aktif'=>1,'one_cikan'=>0,'siralama'=>100,'seo_title'=>'','seo_desc'=>''];
    $ent_hz = []; $ent_er = [];
    if ($id) {
        $q = $pdo->prepare("SELECT * FROM entegratorler WHERE id=?"); $q->execute([$id]);
        $r = $q->fetch(); if ($r) { $e = array_merge($e, $r); }
        $hq = $pdo->prepare("SELECT hizmet_id FROM entegrator_hizmetler WHERE entegrator_id=?"); $hq->execute([$id]);
        $ent_hz = array_map('intval', $hq->fetchAll(PDO::FETCH_COLUMN));
        $rq = $pdo->prepare("SELECT erp_id FROM entegrator_erp WHERE entegrator_id=?"); $rq->execute([$id]);
        $ent_er = array_map('intval', $rq->fetchAll(PDO::FETCH_COLUMN));
    }
    $hzl = $pdo->query("SELECT * FROM hizmet_turleri ORDER BY siralama")->fetchAll();
    $erl = $pdo->query("SELECT * FROM erp_uyumluluk ORDER BY siralama")->fetchAll();
?>
    <div class="ah">
      <h2><?= $id ? 'Düzenle: '.h($e['firma_adi']) : 'Yeni Entegratör Ekle' ?></h2>
      <div class="r"><a href="/yonetim.php?p=entegratorler" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Geri</a></div>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="act" value="ent_kaydet">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">

      <div class="ap">
        <div class="aph"><i class="fas fa-building"></i> Temel Bilgiler</div>
        <div class="apb">
          <div class="fg-row">
            <div class="fg"><label>Firma Adı *</label><input type="text" name="firma_adi" value="<?= h($e['firma_adi']) ?>" required maxlength="200"></div>
            <div class="fg"><label>Slug (boşsa otomatik)</label><input type="text" name="slug" value="<?= h($e['slug']) ?>" maxlength="120" placeholder="orn-firma-adi"></div>
          </div>
          <div class="fg"><label>Kısa Açıklama (liste kartında görünür)</label><input type="text" name="kisa_aciklama" value="<?= h($e['kisa_aciklama']) ?>" maxlength="500"></div>
          <div class="fg"><label>Uzun Açıklama (detay sayfasında)</label><textarea name="uzun_aciklama" rows="5"><?= h($e['uzun_aciklama']) ?></textarea></div>
          <div class="fg-row">
            <div class="fg"><label>Segment</label>
              <select name="segment">
                <option value="karma" <?= $e['segment']==='karma'?'selected':'' ?>>Karma</option>
                <option value="kobi" <?= $e['segment']==='kobi'?'selected':'' ?>>KOBİ</option>
                <option value="kurumsal" <?= $e['segment']==='kurumsal'?'selected':'' ?>>Kurumsal</option>
              </select>
            </div>
            <div class="fg"><label>Sıralama (küçük = önce)</label><input type="number" name="siralama" value="<?= (int)$e['siralama'] ?>"></div>
          </div>
        </div>
      </div>

      <div class="ap">
        <div class="aph"><i class="fas fa-image"></i> Logo & Görsel</div>
        <div class="apb">
          <?php if($e['logo_url']): ?>
            <div style="margin-bottom:10px"><img src="<?= h($e['logo_url']) ?>" style="max-width:120px;max-height:80px;border:1px solid #e5e7eb;border-radius:6px;padding:4px;background:#f8fafc"></div>
          <?php endif; ?>
          <div class="fg"><label>Logo (JPG/PNG/WEBP/SVG, max 500KB)</label><input type="file" name="logo" accept="image/*"></div>
        </div>
      </div>

      <div class="ap">
        <div class="aph"><i class="fas fa-address-card"></i> İletişim</div>
        <div class="apb">
          <div class="fg-row">
            <div class="fg"><label>Website</label><input type="url" name="website" value="<?= h($e['website']) ?>" placeholder="https://firma.com.tr"></div>
            <div class="fg"><label>Telefon</label><input type="tel" name="telefon" value="<?= h($e['telefon']) ?>"></div>
          </div>
          <div class="fg-row">
            <div class="fg"><label>E-posta</label><input type="email" name="email" value="<?= h($e['email']) ?>"></div>
            <div class="fg"><label>Şehir</label><input type="text" name="sehir" value="<?= h($e['sehir']) ?>"></div>
          </div>
          <div class="fg"><label>Adres</label><textarea name="adres" rows="2"><?= h($e['adres']) ?></textarea></div>
          <div class="fg"><label>Vergi No</label><input type="text" name="vergi_no" value="<?= h($e['vergi_no']) ?>" maxlength="20"></div>
        </div>
      </div>

      <div class="ap">
        <div class="aph"><i class="fas fa-certificate"></i> GİB & Sertifikalar</div>
        <div class="apb">
          <div class="fg-row">
            <div class="fg"><label>GİB Onay Tarihi</label><input type="date" name="gib_onay_tarihi" value="<?= h($e['gib_onay_tarihi']) ?>"></div>
            <div class="fg"><label>Onay Numarası</label><input type="text" name="onay_numarasi" value="<?= h($e['onay_numarasi']) ?>"></div>
          </div>
          <div style="display:flex;gap:24px;margin-top:6px">
            <label><input type="checkbox" name="iso_27001" value="1" <?= $e['iso_27001']?'checked':'' ?>> ISO 27001 Sertifikalı</label>
            <label><input type="checkbox" name="kvkk_uyumlu" value="1" <?= $e['kvkk_uyumlu']?'checked':'' ?>> KVKK Uyumlu</label>
          </div>
        </div>
      </div>

      <div class="ap">
        <div class="aph"><i class="fas fa-file-invoice"></i> Hizmet Türleri</div>
        <div class="apb">
          <div class="chkg">
            <?php foreach($hzl as $hz): ?>
              <label class="chkr">
                <input type="checkbox" name="hizmetler[]" value="<?= (int)$hz['id'] ?>" <?= in_array((int)$hz['id'],$ent_hz,true)?'checked':'' ?>>
                <i class="fas fa-<?= h($hz['icon']) ?>" style="color:<?= h($hz['renk']) ?>"></i>
                <span><?= h($hz['ad']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="ap">
        <div class="aph"><i class="fas fa-plug"></i> ERP Uyumluluğu</div>
        <div class="apb">
          <div class="chkg">
            <?php foreach($erl as $er): ?>
              <label class="chkr">
                <input type="checkbox" name="erpler[]" value="<?= (int)$er['id'] ?>" <?= in_array((int)$er['id'],$ent_er,true)?'checked':'' ?>>
                <span><?= h($er['ad']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="ap">
        <div class="aph"><i class="fas fa-search"></i> SEO (Opsiyonel)</div>
        <div class="apb">
          <div class="fg"><label>SEO Title (boşsa otomatik)</label><input type="text" name="seo_title" value="<?= h($e['seo_title']) ?>" maxlength="200"></div>
          <div class="fg"><label>SEO Description</label><textarea name="seo_desc" rows="2" maxlength="300"><?= h($e['seo_desc']) ?></textarea></div>
        </div>
      </div>

      <div class="ap">
        <div class="apb" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
          <label><input type="checkbox" name="aktif" value="1" <?= $e['aktif']?'checked':'' ?>> Aktif (sitede görünür)</label>
          <label><input type="checkbox" name="one_cikan" value="1" <?= $e['one_cikan']?'checked':'' ?>> Öne Çıkan (üste sabitlenir)</label>
          <div style="margin-left:auto;display:flex;gap:8px">
            <a href="/yonetim.php?p=entegratorler" class="btn btn-ghost">Vazgeç</a>
            <button type="submit" class="btn btn-pri"><i class="fas fa-save"></i> Kaydet</button>
          </div>
        </div>
      </div>
    </form>

<?php elseif ($page === 'tsv_import'): ?>
    <div class="ah"><h2>TSV/Toplu Import</h2></div>
    <div class="ap">
      <div class="aph"><i class="fas fa-file-import"></i> Tab-separated veri yapıştır</div>
      <div class="apb">
        <div class="al-msg al-warn" style="margin-bottom:14px">
          <strong>Format:</strong> Her satır: <code>Ünvan &lt;TAB&gt; İl &lt;TAB&gt; Telefon &lt;TAB&gt; e-Posta</code><br>
          <small>GİB sayfasından veriyi kopyalayıp direkt yapıştırabilirsin. İlk satır başlık ise "Başlık satırını atla" kutusunu işaretle.</small>
        </div>
        <form method="POST">
          <input type="hidden" name="act" value="tsv_import">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <div class="fg"><label>TSV Verisi</label>
            <textarea name="tsv" rows="14" style="font-family:monospace;font-size:12px" placeholder="Firma A&#9;İstanbul&#9;0212 111 11 11&#9;info@firmaa.com.tr
Firma B&#9;Ankara&#9;0312 222 22 22&#9;info@firmab.com.tr"></textarea>
          </div>
          <div style="display:flex;gap:18px;margin-bottom:12px;flex-wrap:wrap">
            <label><input type="checkbox" name="skip_header" value="1" checked> İlk satırı başlık olarak atla</label>
            <label><input type="checkbox" name="auto_active" value="1" checked> Aktif olarak ekle</label>
            <label><input type="checkbox" name="update_existing" value="1"> Varolanları güncelle (iletişim bilgileri)</label>
          </div>
          <button type="submit" class="btn btn-pri"><i class="fas fa-upload"></i> İçe Aktar</button>
        </form>
      </div>
    </div>

<?php elseif ($page === 'mesajlar'):
    $rows = [];
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS iletisim_mesajlari (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ad VARCHAR(100), email VARCHAR(150), telefon VARCHAR(50), konu VARCHAR(200), mesaj TEXT, ip VARCHAR(45), user_agent VARCHAR(500), okundu TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if (!empty($_GET['okundu'])) { $pdo->prepare("UPDATE iletisim_mesajlari SET okundu=1 WHERE id=?")->execute([(int)$_GET['okundu']]); header('Location: /yonetim.php?p=mesajlar'); exit; }
        $rows = $pdo->query("SELECT * FROM iletisim_mesajlari ORDER BY id DESC LIMIT 200")->fetchAll();
    } catch(Exception $e) {}
?>
    <div class="ah"><h2>İletişim Mesajları (<?= count($rows) ?>)</h2></div>
    <div class="ap">
      <table class="at">
        <thead><tr><th>Tarih</th><th>Ad</th><th>E-posta</th><th>Konu</th><th>Mesaj</th><th></th></tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
        <tr style="<?= empty($r['okundu']) ? 'background:#fff7ed' : '' ?>">
          <td style="white-space:nowrap;font-size:12px"><?= h($r['created_at']) ?></td>
          <td><strong><?= h($r['ad']) ?></strong></td>
          <td><a href="mailto:<?= h($r['email']) ?>"><?= h($r['email']) ?></a></td>
          <td><?= h($r['konu']) ?></td>
          <td style="max-width:340px"><div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= h($r['mesaj']) ?>"><?= h($r['mesaj']) ?></div></td>
          <td style="text-align:right">
            <?php if(empty($r['okundu'])): ?><a href="/yonetim.php?p=mesajlar&okundu=<?= $r['id'] ?>" class="btn btn-ghost btn-sm"><i class="fas fa-check"></i> Okundu</a><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($rows)): ?><tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:30px">Mesaj yok</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

<?php elseif ($page === 'sifre'): ?>
    <div class="ah"><h2>Şifre Değiştir</h2></div>
    <div class="ap" style="max-width:500px">
      <div class="apb">
        <form method="POST">
          <input type="hidden" name="act" value="sifre_degistir">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <div class="fg"><label>Yeni Şifre (min 8 karakter)</label><input type="password" name="yeni" required minlength="8"></div>
          <div class="fg"><label>Yeni Şifre (tekrar)</label><input type="password" name="yeni2" required minlength="8"></div>
          <button type="submit" class="btn btn-pri"><i class="fas fa-save"></i> Güncelle</button>
        </form>
      </div>
    </div>
<?php endif; ?>
  </main>
</div>
</body></html>
