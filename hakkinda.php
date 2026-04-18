<?php
require_once __DIR__ . '/init.php';
$page_title = 'Hakkında';
$page_desc  = SITE_NAME.' — CODEGA tarafından hazırlanan, GİB onaylı e-Fatura özel entegratörlerinin bağımsız rehberi.';
require __DIR__ . '/partials/header.php';
$stats = $pdo->query("SELECT COUNT(*) toplam, SUM(iso_27001) iso, SUM(kvkk_uyumlu) kvkk FROM entegratorler WHERE aktif=1")->fetch();
?>
<div class="container" style="max-width:860px">
  <h1 style="margin:8px 0 18px;font-size:28px;font-weight:800">Hakkında</h1>

  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px;font-size:15px;line-height:1.75;color:#334155">
    <p><strong><?= h(SITE_NAME) ?></strong>, Türkiye'de faaliyet gösteren <strong>GİB onaylı e-Fatura özel entegratörlerinin</strong> bağımsız, tarafsız ve karşılaştırmalı bir rehberidir. Amacımız; işletmelerin, muhasebe meslek mensuplarının ve BT karar vericilerinin doğru entegratörü seçebilmesi için ihtiyaç duydukları bilgileri tek bir yerde sunmaktır.</p>

    <h2 style="font-size:18px;margin:24px 0 10px">Neden bu rehber?</h2>
    <p>GİB'in resmi listesi yalnızca firma adlarını içerir. Hangi firma hangi hizmeti veriyor, ERP'nizle uyumlu mu, ISO 27001 sertifikası var mı gibi kritik bilgiler orada bulunmaz. Bu site tüm bu bilgileri <strong>yapılandırılmış şekilde</strong> sunar.</p>

    <h2 style="font-size:18px;margin:24px 0 10px">Rakamlarla</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:14px 0">
      <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:24px;font-weight:800;color:#f6821f"><?= (int)$stats['toplam'] ?></div>
        <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Entegratör</div>
      </div>
      <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:24px;font-weight:800;color:#10b981"><?= (int)$stats['iso'] ?></div>
        <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">ISO 27001</div>
      </div>
      <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:24px;font-weight:800;color:#3b82f6"><?= (int)$stats['kvkk'] ?></div>
        <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">KVKK Uyumlu</div>
      </div>
    </div>

    <h2 style="font-size:18px;margin:24px 0 10px">Bu rehberi kim hazırlıyor?</h2>
    <p>Rehber, <a href="https://codega.com.tr" target="_blank" rel="noopener"><strong>CODEGA</strong></a> tarafından hazırlanmaktadır. CODEGA, Konya merkezli bir yazılım ajansı olarak 15+ yıldır KOBİ'lere <a href="https://erp.codega.com.tr" target="_blank" rel="noopener">CodeGa ERP</a> dahil çeşitli iş yazılımları sunmaktadır.</p>

    <h2 style="font-size:18px;margin:24px 0 10px">Bilgi güncel mi?</h2>
    <p>Bilgiler periyodik olarak GİB'in resmi sayfasından kontrol edilerek güncellenir. Yine de en güncel liste için daima <a href="https://ebelge.gib.gov.tr" target="_blank" rel="noopener nofollow">ebelge.gib.gov.tr</a> adresini kontrol etmenizi tavsiye ederiz. Bilgi hatası veya eksiklik farkederseniz <a href="/iletisim.php">bize iletin</a>.</p>

    <h2 style="font-size:18px;margin:24px 0 10px">Listelenmek istiyorum</h2>
    <p>Firmanız GİB onaylı özel entegratör ise rehberimizde ücretsiz yer almanızı sağlayabiliriz. <a href="/iletisim.php">İletişim formunu</a> kullanarak başvurabilir, öne çıkan listeleme seçenekleri için de bize ulaşabilirsiniz.</p>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
