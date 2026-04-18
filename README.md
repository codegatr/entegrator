# entegrator.codega.com.tr

GİB Onaylı e-Fatura, e-Arşiv ve e-Dönüşüm **özel entegratörlerinin** bağımsız rehberi.

https://entegrator.codega.com.tr

## Özellikler

- **89 GİB onaylı özel entegratör** (otomatik seed)
- **11 hizmet türü**: e-Fatura · e-Arşiv · e-İrsaliye · e-Defter · e-SMM · e-Müstahsil · e-Döviz · e-Adisyon · e-Dekont · e-Sigorta · e-Gider Pusulası
- **9 ERP uyumluluğu**: Logo · Mikro · Netsis · SAP · Paraşüt · Zirve · Nebim · Luca · CodeGa ERP
- Filtreli arama, sıralama, sayfalama
- 2-3 entegratör yan yana **karşılaştırma aracı**
- SEO dostu URL'ler ve Schema.org markup
- Hizmet ve ERP bazlı taxonomy sayfaları
- Admin panel: CRUD, logo upload, TSV toplu import, iletişim mesajları
- Tek dosya admin (`yonetim.php`)
- TSV/CSV import özelliği

## Teknik

- PHP 8.0+ (geliştirme: PHP 8.3)
- MySQL 5.7+ / MariaDB 10.3+
- Cloudflare arkasında (entegrator.codega.com.tr)
- Single-file pattern + SEO partials

## Kurulum

### Seçenek A: Otomatik (önerilen)
1. Repo'yu `/public_html/entegrator.codega.com.tr/` altına aç
2. `config.php` düzenle: DB bilgileri, `CSRF_SECRET`, `ADMIN_DEFAULT_PASS`
3. İlk sayfa açılışında otomatik olarak:
   - 8 tablo oluşur (`entegratorler`, `hizmet_turleri`, `entegrator_hizmetler`, `erp_uyumluluk`, `entegrator_erp`, `admin_kullanicilar`, `ziyaretci_log`, `iletisim_mesajlari`)
   - 11 hizmet türü + 9 ERP seed edilir
   - İlk admin oluşur: `admin` / `admin123` (ilk girişte şifre değiştirme zorunlu)
   - 89 GİB entegratörü `seed/gib_entegratorler.tsv` dosyasından yüklenir
4. `/yonetim.php` → şifreni değiştir → entegratörleri zenginleştirmeye başla

### Seçenek B: Manuel SQL import
phpMyAdmin veya CLI kullanıyorsan `install.sql` ile tek seferde kur:
```bash
mysql -u USER -p DBNAME < install.sql
```
veya phpMyAdmin → Veritabanı seç → **Import** sekmesi → `install.sql` yükle.

Dosya içeriği:
- 8 tablo (DROP + CREATE)
- 11 hizmet türü seed
- 9 ERP uyumluluğu seed
- 1 admin (admin / admin123)
- **89 GİB onaylı entegratör seed**

Yedek alma için de aynı dosya yapı referansı olarak kullanılabilir.

## Mimari

```
/
├── index.php             Ana sayfa: arama + filtre + grid
├── e.php                 Detay sayfası (slug bazlı)
├── karsilastir.php       Yan yana karşılaştırma (2-3 firma)
├── hizmet.php            SEO: hizmet bazlı liste
├── erp.php               SEO: ERP bazlı liste
├── hakkinda.php · iletisim.php
├── yonetim.php           Admin (tek dosya)
├── sitemap.xml.php       Dinamik sitemap
├── config.php            DB + site ayarları (ZIP'te YOK)
├── init.php              Migration + auto-seed + helpers
├── api/
│   ├── arama.php         Live search
│   └── sayac.php         Async view counter
├── assets/
│   ├── style.css
│   ├── app.js            Compare bar, search, filter
│   └── favicon.svg
├── partials/
│   ├── header.php
│   └── footer.php
├── seed/
│   └── gib_entegratorler.tsv   89 firma (GİB listesi)
├── uploads/logos/        Firma logoları
├── .htaccess             URL rewrite + güvenlik
├── robots.txt
└── manifest.json         Versiyon
```

## Güvenlik

- Bcrypt password hash
- CSRF token (session bazlı)
- XSS koruması (`h()` helper her yerde)
- Prepared statements her sorguda
- Logo upload: MIME validation, 500KB max, uploads/ dizininde PHP exec kapalı
- Rate limit: login başarısız olunca 1sn delay
- Admin panel `/yonetim.php`, `robots.txt` disallow
- Cloudflare WAF önünde

## Lisans

CODEGA tarafından geliştirilmiştir. Kaynak kod MIT lisansı altında — kullan, fork et, katkı ver.

## İletişim

- Website: https://codega.com.tr
- ERP: https://erp.codega.com.tr
- Sorun bildir: GitHub Issues
