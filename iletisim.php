<?php
require_once __DIR__ . '/init.php';

$mesaj = ''; $tip = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) { $mesaj='Güvenlik hatası.'; $tip='danger'; }
    else {
        $ad  = trim($_POST['ad'] ?? '');
        $ep  = trim($_POST['email'] ?? '');
        $tel = trim($_POST['telefon'] ?? '');
        $kn  = trim($_POST['konu'] ?? '');
        $mj  = trim($_POST['mesaj'] ?? '');
        $bot = trim($_POST['website'] ?? ''); // honeypot

        if ($bot) { $mesaj='İşlem reddedildi.'; $tip='danger'; }
        elseif ($ad === '' || $ep === '' || $mj === '') { $mesaj='Ad, e-posta ve mesaj zorunludur.'; $tip='danger'; }
        elseif (!filter_var($ep, FILTER_VALIDATE_EMAIL)) { $mesaj='Geçersiz e-posta.'; $tip='danger'; }
        elseif (strlen($mj) < 10) { $mesaj='Mesaj en az 10 karakter olmalı.'; $tip='danger'; }
        else {
            // DB'ye kaydet (iletisim_mesajlari tablosu basit)
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS iletisim_mesajlari (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ad VARCHAR(100), email VARCHAR(150), telefon VARCHAR(50),
                    konu VARCHAR(200), mesaj TEXT,
                    ip VARCHAR(45), user_agent VARCHAR(500),
                    okundu TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $pdo->prepare("INSERT INTO iletisim_mesajlari (ad,email,telefon,konu,mesaj,ip,user_agent) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$ad, $ep, $tel, $kn, $mj, $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,500)]);
                $mesaj = 'Mesajınız alındı. En kısa sürede size dönüş yapacağız.'; $tip='success';
            } catch (Exception $e) {
                error_log('İletişim kayıt: '.$e->getMessage());
                $mesaj = 'Kayıt sırasında hata. Lütfen tekrar deneyin.'; $tip='danger';
            }
        }
    }
}

$page_title = 'İletişim';
$page_desc  = 'Entegratör Rehberi iletişim formu. Soru, öneri veya listelenme talebi için bize yazın.';
require __DIR__ . '/partials/header.php';
?>
<div class="container" style="max-width:860px">
  <h1 style="margin:8px 0 18px;font-size:28px;font-weight:800">İletişim</h1>

  <?php if($mesaj): ?>
    <div style="padding:13px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;background:<?= $tip==='success'?'#dcfce7':'#fee2e2' ?>;color:<?= $tip==='success'?'#14532d':'#7f1d1d' ?>;border:1px solid <?= $tip==='success'?'#86efac':'#fca5a5' ?>">
      <i class="fas fa-<?= $tip==='success'?'check':'times' ?>-circle"></i> <?= h($mesaj) ?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px">
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px">
      <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
          <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px">Ad Soyad *</label>
            <input type="text" name="ad" required maxlength="100" style="width:100%;padding:9px 12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box">
          </div>
          <div>
            <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px">Telefon</label>
            <input type="tel" name="telefon" maxlength="30" style="width:100%;padding:9px 12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box">
          </div>
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px">E-posta *</label>
          <input type="email" name="email" required maxlength="150" style="width:100%;padding:9px 12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box">
        </div>
        <div style="margin-bottom:12px">
          <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px">Konu</label>
          <select name="konu" style="width:100%;padding:9px 12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box;background:#fff">
            <option value="genel">Genel soru</option>
            <option value="listeleme">Firmamı listele</option>
            <option value="hata">Bilgi hatası bildir</option>
            <option value="onerili">Öneri / geri bildirim</option>
            <option value="isbirligi">İş birliği</option>
          </select>
        </div>
        <div style="margin-bottom:14px">
          <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:4px">Mesaj *</label>
          <textarea name="mesaj" required maxlength="2000" rows="6" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;box-sizing:border-box;resize:vertical;font-family:inherit"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="padding:11px 24px;font-size:14px"><i class="fas fa-paper-plane"></i> Gönder</button>
      </form>
    </div>
    <aside style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:22px;font-size:14px;color:#334155;height:fit-content">
      <h3 style="margin:0 0 14px;font-size:14px;text-transform:uppercase;letter-spacing:0.5px;color:#475569">Doğrudan İletişim</h3>
      <div style="margin-bottom:12px"><i class="fas fa-envelope" style="color:#f6821f;width:18px"></i> <a href="mailto:<?= h(CONTACT_EMAIL) ?>"><?= h(CONTACT_EMAIL) ?></a></div>
      <div style="margin-bottom:12px"><i class="fas fa-building" style="color:#f6821f;width:18px"></i> <a href="https://codega.com.tr" target="_blank" rel="noopener">codega.com.tr</a></div>
      <div style="margin-bottom:12px"><i class="fas fa-map-marker-alt" style="color:#f6821f;width:18px"></i> Konya, Türkiye</div>
      <div style="margin-top:18px;padding-top:18px;border-top:1px solid #f1f5f9;font-size:12.5px;color:#64748b">
        Genellikle <strong>iş günlerinde 24 saat içinde</strong> dönüş yapıyoruz.
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
