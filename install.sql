-- ═════════════════════════════════════════════════════════════
-- entegrator.codega.com.tr — install.sql
-- v1.0.0 · 2026-04-18
--
-- Kurulum: phpMyAdmin > Import > install.sql
-- veya:   mysql -u USER -p DBNAME < install.sql
--
-- Not: init.php'nin auto-migration'ı zaten bu yapıyı oluşturur.
--      Bu dosya manuel kurulum, yedek alma ve referans içindir.
-- ═════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET time_zone = '+03:00';

-- ─── Tablo: entegratorler ────────────────────────────────────
DROP TABLE IF EXISTS `entegratorler`;
CREATE TABLE `entegratorler` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(120) UNIQUE NOT NULL,
  `firma_adi` VARCHAR(200) NOT NULL,
  `kisa_aciklama` VARCHAR(500) DEFAULT NULL,
  `uzun_aciklama` TEXT DEFAULT NULL,
  `logo_url` VARCHAR(255) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `telefon` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `adres` TEXT DEFAULT NULL,
  `sehir` VARCHAR(60) DEFAULT NULL,
  `vergi_no` VARCHAR(20) DEFAULT NULL,
  `gib_onay_tarihi` DATE DEFAULT NULL,
  `onay_numarasi` VARCHAR(50) DEFAULT NULL,
  `iso_27001` TINYINT(1) DEFAULT 0,
  `kvkk_uyumlu` TINYINT(1) DEFAULT 0,
  `segment` ENUM('kobi','kurumsal','karma') DEFAULT 'karma',
  `aktif` TINYINT(1) DEFAULT 1,
  `one_cikan` TINYINT(1) DEFAULT 0,
  `goruntulenme` INT UNSIGNED DEFAULT 0,
  `siralama` INT DEFAULT 100,
  `seo_title` VARCHAR(200) DEFAULT NULL,
  `seo_desc` VARCHAR(300) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_aktif` (`aktif`),
  KEY `idx_segment` (`segment`),
  FULLTEXT KEY `ft_arama` (`firma_adi`, `kisa_aciklama`, `uzun_aciklama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tablo: hizmet_turleri ───────────────────────────────────
DROP TABLE IF EXISTS `hizmet_turleri`;
CREATE TABLE `hizmet_turleri` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kod` VARCHAR(50) UNIQUE NOT NULL,
  `ad` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'file-invoice',
  `renk` VARCHAR(20) DEFAULT '#3b82f6',
  `aciklama` TEXT DEFAULT NULL,
  `siralama` INT DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tablo: entegrator_hizmetler (M2M) ───────────────────────
DROP TABLE IF EXISTS `entegrator_hizmetler`;
CREATE TABLE `entegrator_hizmetler` (
  `entegrator_id` INT UNSIGNED NOT NULL,
  `hizmet_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`entegrator_id`, `hizmet_id`),
  KEY `idx_hizmet` (`hizmet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Tablo: erp_uyumluluk ────────────────────────────────────
DROP TABLE IF EXISTS `erp_uyumluluk`;
CREATE TABLE `erp_uyumluluk` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kod` VARCHAR(50) UNIQUE NOT NULL,
  `ad` VARCHAR(100) NOT NULL,
  `logo_url` VARCHAR(255) DEFAULT NULL,
  `siralama` INT DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tablo: entegrator_erp (M2M) ─────────────────────────────
DROP TABLE IF EXISTS `entegrator_erp`;
CREATE TABLE `entegrator_erp` (
  `entegrator_id` INT UNSIGNED NOT NULL,
  `erp_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`entegrator_id`, `erp_id`),
  KEY `idx_erp` (`erp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Tablo: admin_kullanicilar ───────────────────────────────
DROP TABLE IF EXISTS `admin_kullanicilar`;
CREATE TABLE `admin_kullanicilar` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kullanici_adi` VARCHAR(50) UNIQUE NOT NULL,
  `sifre_hash` VARCHAR(255) NOT NULL,
  `ad_soyad` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `rol` ENUM('admin','moderator') DEFAULT 'admin',
  `aktif` TINYINT(1) DEFAULT 1,
  `sifre_degistirildi` TINYINT(1) DEFAULT 0,
  `son_giris` DATETIME DEFAULT NULL,
  `son_ip` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tablo: ziyaretci_log ────────────────────────────────────
DROP TABLE IF EXISTS `ziyaretci_log`;
CREATE TABLE `ziyaretci_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `sayfa` VARCHAR(255) DEFAULT NULL,
  `entegrator_id` INT UNSIGNED DEFAULT NULL,
  `referrer` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_entegrator` (`entegrator_id`),
  KEY `idx_tarih` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Tablo: iletisim_mesajlari ──────────────────────────────
DROP TABLE IF EXISTS `iletisim_mesajlari`;
CREATE TABLE `iletisim_mesajlari` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ad` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `telefon` VARCHAR(50) DEFAULT NULL,
  `konu` VARCHAR(200) DEFAULT NULL,
  `mesaj` TEXT DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `okundu` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═════════════════════════════════════════════════════════════
-- SEED: Hizmet Türleri (11 tür)
-- ═════════════════════════════════════════════════════════════
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_fatura', 'e-Fatura', 'file-invoice', '#3b82f6', 'Ticari faturaların elektronik ortamda oluşturulması ve iletilmesi', 10);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_arsiv', 'e-Arşiv Fatura', 'file-invoice-dollar', '#10b981', 'Son kullanıcılara kesilen elektronik arşiv faturaları', 20);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_irsaliye', 'e-İrsaliye', 'truck', '#f59e0b', 'Elektronik sevk irsaliyesi', 30);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_defter', 'e-Defter', 'book', '#8b5cf6', 'Elektronik yevmiye ve büyük defter', 40);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_smm', 'e-SMM', 'receipt', '#ec4899', 'e-Serbest Meslek Makbuzu', 50);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_mustahsil', 'e-Müstahsil Makbuzu', 'leaf', '#65a30d', 'Zirai ürün alım makbuzu', 60);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_doviz', 'e-Döviz', 'money-bill-transfer', '#0891b2', 'Döviz alım-satım belgesi', 70);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_adisyon', 'e-Adisyon', 'utensils', '#dc2626', 'Restoran/kafe adisyon belgesi', 80);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_dekont', 'e-Dekont', 'file-invoice', '#6366f1', 'Banka dekont paketi', 90);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_sigorta', 'e-Sigorta', 'shield-halved', '#7c3aed', 'Sigorta komisyon gider belgesi', 100);
INSERT INTO `hizmet_turleri` (`kod`, `ad`, `icon`, `renk`, `aciklama`, `siralama`) VALUES ('e_gider_pusula', 'e-Gider Pusulası', 'receipt', '#ea580c', 'Gider pusulası elektronik', 110);

-- ═════════════════════════════════════════════════════════════
-- SEED: ERP Uyumluluk (9 ERP)
-- ═════════════════════════════════════════════════════════════
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('logo', 'Logo Yazılım', 10);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('mikro', 'Mikro Yazılım', 20);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('netsis', 'Netsis', 30);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('sap', 'SAP', 40);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('parasut', 'Paraşüt', 50);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('zirve', 'Zirve', 60);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('nebim', 'Nebim', 70);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('luca', 'Luca', 80);
INSERT INTO `erp_uyumluluk` (`kod`, `ad`, `siralama`) VALUES ('codega_erp', 'CodeGa ERP', 90);

-- ═════════════════════════════════════════════════════════════
-- SEED: İlk Admin (admin / admin123 — İLK GİRİŞTE DEĞİŞTİR!)
-- ═════════════════════════════════════════════════════════════
INSERT INTO `admin_kullanicilar` (`kullanici_adi`, `sifre_hash`, `ad_soyad`, `rol`, `sifre_degistirildi`)
VALUES ('admin', '$2y$10$iYSuspQQ1MkkVDx8yHE5GeTxa5TF78vzT5SxoWYwmNvWdOXdpOMbK', 'Kurucu Admin', 'admin', 0);

-- ═════════════════════════════════════════════════════════════
-- SEED: 89 GİB Onaylı Özel Entegratör
-- Kaynak: ebelge.gib.gov.tr/efaturaozelentegratorlerlistesi.html
-- ═════════════════════════════════════════════════════════════
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('idea-teknoloji-cozumleri-bilgisayar-san-ve-ticaret-anonim-sirketi', 'İDEA Teknoloji Çözümleri Bilgisayar San. ve Ticaret Anonim Şirketi', 'İstanbul', '(212) 276 56 76', 'info@ideateknoloji.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('mebitech-bilisim-a-s', 'Mebitech Bilişim A.Ş.', 'İstanbul', '444 4 865', 'info@mebitech.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('veriban-elektronik-veri-isleme-ve-saklama-hizmetleri-a-s', 'VERİBAN Elektronik Veri İşleme ve Saklama Hizmetleri A.Ş.', 'İstanbul', '(212) 340 65 00', 'info@veriban.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('uyumsoft-bilgi-sistemleri-ve-teknolojileri-ticaret-anonim-sirketi', 'UYUMSOFT Bilgi Sistemleri ve Teknolojileri Ticaret Anonim Şirketi', 'İstanbul', '(212) 467 33 33', 'efatura@uyumsoft.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('logo-yazilim-sanayi-ve-ticaret-anonim-sirketi', 'LOGO YAZILIM SANAYİ VE TİCARET ANONİM ŞİRKETİ', 'Kocaeli', '(262) 679 80 00', 'info@elogo.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('bizofis-bilgi-teknolojileri-ve-yonetim-danismanlik-hizmetleri-ticaret-limited-sirketi', 'BİZOFİS Bilgi Teknolojileri ve Yönetim Danışmanlık Hizmetleri Ticaret Limited Şirketi', 'İstanbul', '(212) 212 18 98 (Pbx)', 'e-fatura@bizofis.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('izibiz-bilisim-teknolojileri-anonim-sirketi', 'İZİBİZ Bilişim Teknolojileri Anonim Şirketi', 'İstanbul', '(216) 222 00 00', 'info@izibiz.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('sabanci-dijital-teknoloji-hizmetleri-anonim-sirketi', 'SABANCI DİJİTAL TEKNOLOJİ HİZMETLERİ ANONİM ŞİRKETİ', 'İstanbul', '(216) 425 10 50', 'efatura@sabancidx.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('turkkep-kayitli-elektronik-posta-hizmetleri-sanayi-ve-ticaret-anonim-sirketi', 'TÜRKKEP Kayıtlı Elektronik Posta Hizmetleri Sanayi Ve Ticaret Anonim Şirketi', 'İstanbul', '(850) 470 05 37', 'destek@turkkep.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('innova-bilisim-cozumleri-anonim-sirketi', 'İNNOVA Bilişim Çözümleri Anonim Şirketi', 'İstanbul', '(212) 329 70 00', 'info@innova.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('turk-telekomunikasyon-anonim-sirketi', 'TÜRK Telekomünikasyon Anonim Şirketi', 'Ankara', '444 1 444', 'iletisim@turktelekom.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('mikro-yazilimevi-yazilim-hizmetleri-bilgisayar-ve-sanayi-ticaret-a-s', 'MİKRO Yazılımevi Yazılım Hizmetleri Bilgisayar ve Sanayi Ticaret A.Ş.', 'İstanbul', '(216) 472 84 00 (Pbx)', 'info@mikro.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('qnb-esolutions-elektronik-ticaret-ve-bilisim-hizmetleri-a-s', 'QNB ESOLUTIONS ELEKTRONİK TİCARET VE BİLİŞİM HİZMETLERİ A.Ş.', 'İstanbul', '(850) 250 67 50', 'info@qnbesolutions.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('isnet-elektronik-bilgi-uretim-dagitim-ticaret-ve-iletisim-hizmetleri-a-s', 'İŞNET Elektronik Bilgi Üretim Dağıtım Ticaret ve İletişim Hizmetleri A.Ş.', 'İstanbul', '(850) 290 90 90', 'efaturadestek@nettefatura.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('edm-bilisim-sistemleri-ve-danismanlik-hizmetleri-a-s', 'EDM Bilişim Sistemleri ve Danışmanlık Hizmetleri A.Ş.', 'İstanbul', '(850) 723 63 36', 'bilgi@edmbilisim.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('nes-bulut-yazilim-ticaret-limited-sirketi', 'NES BULUT YAZILIM TİCARET LİMİTED ŞİRKETİ', 'İstanbul', '02166885100', 'info@nesbilgi.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('sni-teknoloji-hizmetleri-a-s', 'SNI Teknoloji Hizmetleri A.Ş.', 'İstanbul', '(212) 438 04 73', 'contact@snitechnology.net', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('fit-bilgi-islem-sistemleri-serv-san-ve-tic-a-s-sovos', 'FIT BİLGİ İŞLEM SİSTEMLERİ SERV. SAN. VE TİC. A.Ş. (SOVOS)', 'İstanbul', '(850) 733 28 87', 'bilgi@sovos.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('protel-bilgisayar-anonim-sirketi', 'PROTEL Bilgisayar Anonim Şirketi', 'İstanbul', '(212) 355 00 00', 'edonusum@protel.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('intecon-bilisim-ve-danismanlik-hizmetleri-tic-ltd-sti', 'İNTECON Bilişim ve Danışmanlık Hizmetleri Tic. Ltd.Şti.', 'İstanbul', '(216) 314 08 06', 'info@intecon.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('detay-danismanlik-bilgisayar-hizmetleri-sanayi-ve-dis-ticaret-anonim-sirketi', 'DETAY DANIŞMANLIK BİLGİSAYAR HİZMETLERİ SANAYİ VE DIŞ TİCARET ANONİM ŞİRKETİ', 'İstanbul', '(216) 443 13 29', 'efatura@detaysoft.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ayasofyazilim-bilisim-anonim-sirketi', 'Ayasofyazılım Bilişim Anonim Şirketi', 'Kocaeli', '(850) 532 32 32', 'info@ayasofyazilim.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('eplatform-bulut-bilisim-anonim-sirketi-turkcell-e-sirket', 'ePlatform Bulut Bilişim Anonim Şirketi (Turkcell e-Şirket)', 'İstanbul', '(216) 977 70 00', 'info@ePlatform.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('netbt-danismanlik-hizmetleri-anonim-sirketi', 'NetBT Danışmanlık Hizmetleri Anonim Şirketi', 'İstanbul', '(216) 688 48 03', 'ozelentegrator@net-bt.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('crssoft-yazilim-hizmetleri-anonim-sirketi', 'CRSSOFT Yazılım Hizmetleri Anonim Şirketi', 'İstanbul', '(212) 489 33 13', 'efatura@crssoft.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('vbt-yazilim-anonim-sirketi', 'VBT YAZILIM ANONİM ŞİRKETİ', 'İstanbul', '(216) 577 69 21', 'ozelentegrator@vbt.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('g-yazilim-bilgisayar-teknolojileri-danismanlik-ve-organizasyon-reklam-insaat-sanayi-ticaret-limited-sirketi', 'G-Yazılım Bilgisayar Teknolojileri Danışmanlık ve Organizasyon Reklam İnşaat Sanayi Ticaret Limited Şirketi', 'Ankara', '(312) 480 23 44', 'bilgi@gyazilim.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('hitit-bilgisayar-hizmetleri-a-s', 'Hitit Bilgisayar Hizmetleri A.Ş.', 'İstanbul', '(212) 276 15 00', 'sales@hititcs.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('kolaysoft-teknoloji-a-s', 'KOLAYSOFT TEKNOLOJİ A.Ş.', 'Ankara', '(850) 259 90 90', 'info@kolaysoft.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('odeal-teknoloji-a-s', 'ÖDEAL TEKNOLOJİ A.Ş.', 'İstanbul', '(212) 280 81 15', 'efatura@ode.al', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('btc-bilisim-hizmetleri-anonim-sirketi', 'BTC Bilişim Hizmetleri Anonim Şirketi', 'İstanbul', '(216) 575 45 90', 'contact@btc-ag.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('zirve-bilgi-teknolojileri-medikal-turizm-sanayi-ticaret-limited-sirketi', 'Zirve Bilgi Teknolojileri Medikal Turizm Sanayi Ticaret Limited Şirketi', 'Ankara', '(312) 473 28 00', 'entegrator@zirveyazilim.net', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('dogan-e-donusum-hizmetleri-anonim-sirketi', 'DOĞAN E DÖNÜŞÜM HİZMETLERİ ANONİM ŞİRKETİ', 'İstanbul', '(212) 222 86 22', 'info@doganedonusum.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('avalara-turkey-bilisim-limited-sirketi', 'AVALARA TURKEY BİLİŞİM LİMİTED ŞİRKETİ', 'İzmir', '(232) 369 19 44', 'info@inposia.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('vegagrup-yazilim-ve-bilisim-teknolojileri-ticaret-anonim-sirketi', 'VEGAGRUP YAZILIM VE BİLİŞİM TEKNOLOJİLERİ TİCARET ANONİM ŞİRKETİ', 'Ankara', '(850) 346 94 94', 'entegrator@vegayazilim.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('mikrokom-bilgi-teknolojileri-anonim-sirketi', 'MİKROKOM BİLGİ TEKNOLOJİLERİ ANONİM ŞİRKETİ', 'Ankara', '(312) 215 15 10', 'entegrator@mikrokomdonusum.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('bien-teknoloji-anonim-sirketi', 'BİEN TEKNOLOJİ ANONİM ŞİRKETİ', 'Ankara', '(850) 532 74 71', 'entegrator@bienteknoloji.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ice-bilisim-teknolojileri-anonim-sirket', 'ICE BİLİŞİM TEKNOLOJİLERİ ANONİM ŞİRKET', 'İstanbul', '(216) 589 89 02', 'info@ıceteknoloji.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('smart-donusum-teknoloji-anonim-sirketi', 'SMART DÖNÜŞÜM TEKNOLOJİ ANONİM ŞİRKETİ', 'Ankara', '(850) 259 90 90', 'entegrator@smartdonusum.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('mbt-e-donusum-teknolojileri-anonim-sirketi', 'MBT E DÖNÜŞÜM TEKNOLOJİLERİ ANONİM ŞİRKETİ', 'Ankara', '(850) 308 06 28', 'info@mbtteknoloji.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('park-entegrasyon-e-donusum-ve-bilisim-hizmetleri-a-s', 'PARK ENTEGRASYON E DÖNÜŞÜM VE BİLİŞİM HİZMETLERİ A.Ş.', 'İstanbul', '(212) 322 3 777', 'info@parkentegrasyon.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('dia-yazilim-sanayi-ve-ticaret-a-s', 'DİA YAZILIM SANAYİ VE TİCARET A.Ş.', 'Ankara', '(312) 210 00 90', 'edevlet@dia.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('sankonline-internet-hizmetleri-ve-yatirim-anonim-sirketi', 'SANKONLİNE İNTERNET HİZMETLERİ VE YATIRIM ANONİM ŞİRKETİ', 'Gaziantep', '(342) 211 30 00', 'efatura@sankonline.net', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('elpo-bilisim-otomasyon-elektronik-pompa-sistemleri-ve-petrol-sanayi-ticaret-ltd-sti', 'ELPO Bilişim Otomasyon Elektronik Pompa Sistemleri ve Petrol Sanayi Ticaret LTD.ŞTİ.', 'Konya', '(850) 532 07 24', 'entegrator@elpo.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('hizli-bilisim-teknolojileri-a-s', 'HIZLI BİLİŞİM TEKNOLOJİLERİ A.Ş.', 'Adana', '(322) 248 12 22', 'info@hizliteknoloji.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('superonline-iletisim-hizmetleri-a-s', 'SUPERONLINE İLETİŞİM HİZMETLERİ A.Ş.', 'İstanbul', '(850) 222 76 76', 'INFO-EFATURA@turkcell.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('uyumsoft-kurumsal-is-sistemleri-ve-teknolojileri-a-s', 'UYUMSOFT KURUMSAL İŞ SİSTEMLERİ VE TEKNOLOJİLERİ A.Ş.', 'İstanbul', '(212) 467 33 33', 'edonusum@uyum.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ticari1-teknoloji-anonim-sirketi', 'Ticari1 Teknoloji Anonim Şirketi', 'İstanbul', '(212) 212 42 32', 'info@ticari1.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ard-grup-bilisim-teknolojileri-anonim-sirketi', 'ARD GRUP BİLİŞİM TEKNOLOJİLERİ ANONİM ŞİRKETİ', 'Ankara', '(312) 299 25 95', 'info@ardgrup.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('idecon-danismanlik-hizmetleri-anonim-sirketi', 'IDECON DANIŞMANLIK HİZMETLERİ ANONİM ŞİRKETİ', 'İstanbul', '(850) 888 0 433', 'info@idecon.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('farmakom-eczane-bilgisayar-hiz-san-ve-tic-a-s', 'Farmakom Eczane Bilgisayar Hiz. San. Ve Tic. A.Ş.', 'Ankara', '(312) 495 00 95', 'entegrator@farmakom.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('univera-bilgisayar-sistemleri-sanayi-ve-ticaret-anonim-sirketi', 'UNİVERA BİLGİSAYAR SİSTEMLERİ SANAYİ VE TİCARET ANONİM ŞİRKETİ', 'İzmir', '(232) 445 94 70', 'edonusum@univera.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('devatek-bilgi-teknolojileri-sanayi-ve-ticaret-anonim-sirketi', 'DEVATEK BİLGİ TEKNOLOJİLERİ SANAYİ VE TİCARET ANONİM ŞİRKETİ', 'İstanbul', '(216) 807 08 08', 'edonusum@devatek.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('erciyes-anadolu-holding-anonim-sirketi', 'ERCİYES ANADOLU HOLDİNG ANONİM ŞİRKETİ', 'Kayseri', '(352) 207 18 00', 'efatura@erciyes.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('mysoft-dijital-donusum-a-s', 'MYSOFT DİJİTAL DÖNÜŞÜM A.Ş.', 'İstanbul', '(212) 901 02 12', 'info@mysoft.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('mimsoft-arge-arsivleme-teknolojileri-anonim-sirketi', 'MİMSOFT ARGE ARŞİVLEME TEKNOLOJİLERİ ANONİM ŞİRKETİ', 'İstanbul', '(212) 909 97 27', 'bilgi@mimsoft.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('pavo-finansal-teknoloji-cozumleri-a-s', 'Pavo Finansal Teknoloji Çözümleri A.Ş.', 'İstanbul', '(850) 611 04 44', 'edonusumdestek@pavo.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('birfatura-yazilim-teknolojileri-a-s', 'BirFatura Yazılım Teknolojileri A.Ş.', 'Ankara', '(850) 303 32 96', 'entegrator@birfatura.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('token-finansal-teknolojiler-anonim-sirketi', 'TOKEN Finansal Teknolojiler Anonim Şirketi', 'İstanbul', '(212) 942 88 88', 'ebelge-info@tokeninc.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('turmob-tesmer-egitim-yayin-ve-yazilim-hizmetleri-iktisadi-isletmesi', 'TÜRMOB-TESMER EĞİTİM YAYIN VE YAZILIM HİZMETLERİ İKTİSADİ İŞLETMESİ', 'Ankara', '(312) 987 06 06', 'entegratordestek@luca.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('tolasoft-yazilim-a-s-super-entegrator', 'TOLASOFT Yazılım A.Ş. (Süper Entegratör)', 'İstanbul', '(216) 288 07 78', 'info@superentegrator.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ileka-akademi-egitim-danismanlik-ve-mesleki-yeterlilik-belgelendirme-a-s-turkbelge', 'İLEKA AKADEMİ EĞİTİM DANIŞMANLIK VE MESLEKİ YETERLİLİK BELGELENDİRME A.Ş (TÜRKBELGE)', 'İzmir', '(850) 333 0 353', 'info@turkbelge.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('10-bolge-antalya-eczaci-odasi-iktisadi-isletmesi', '10.Bölge Antalya Eczacı Odası İktisadi İşletmesi', 'Antalya', '(242) 311 03 29', 'info@eczfatura.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('vadi-kurumsal-bilgi-sistemleri-a-s', 'Vadi Kurumsal Bilgi Sistemleri A.Ş.', 'İstanbul', '(212) 483 74 00', 'edonusum@vadi.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('genesis-yapay-zeka-teknolojileri-anonim-sirketi', 'GENESİS YAPAY ZEKA TEKNOLOJİLERİ ANONİM ŞİRKETİ', 'İstanbul', '(212) 932 82 00', 'info@gai.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ubl-teknoloji-anonim-sirketi', 'UBL TEKNOLOJİ ANONİM ŞİRKETİ', 'Ankara', '(850) 259 92 92', 'info@ubl.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('worldline-odeme-sistem-cozumleri-anonim-sirketi', 'WORLDLİNE ÖDEME SİSTEM ÇÖZÜMLERİ ANONİM ŞİRKETİ', 'İstanbul', '(212) 366 48 00', 'dl-tr.ebelge.destek@worldline.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('mira-teknoloji-anonim-sirketi', 'MİRA TEKNOLOJİ ANONİM ŞİRKETİ', 'Ankara', '(312) 472 50 10', 'efatura@miraerp.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('finalizer-bilisim-teknolojileri-anonim-sirketi', 'Finalizer Bilişim Teknolojileri Anonim Şirketi', 'İstanbul', '(212) 967 25 34', 'edonusum@finalizer.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('nilvera-yazilim-ve-bilisim-hizmetleri-tic-ltd-sti', 'NİLVERA YAZILIM VE BİLİŞİM HİZMETLERİ TİC. LTD. ŞTİ.', 'Kayseri', '(850) 251 40 10', 'info@nilvera.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('proje-turizm-danismanlik-ve-bilgisayar-hizmetleri-ltd-sti', 'PROJE TURIZM DANIŞMANLIK ve BİLGİSAYAR HİZMETLERİ LTD.ŞTİ.', 'Ankara', '(850) 202 04 96', 'info@etapi.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('pos-perakende-otomasyon-sistemleri-ticaret-ve-sanayi-a-s', 'POS Perakende Otomasyon Sistemleri Ticaret Ve Sanayi A.Ş.', 'İstanbul', '(216) 464 03 23', 'ebelge@toshibagcs.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ias-dijital-donusum-teknolojileri-ve-ticaret-a-s', 'IAS Dijital Dönüşüm Teknolojileri ve Ticaret A.Ş.', 'İstanbul', '444 22 65', 'edonusum@iasentegrator.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('e-pozitif-ileri-teknoloji-a-s', 'E POZİTİF İLERİ TEKNOLOJİ A.Ş.', 'İstanbul', '(216) 540 34 20', 'edonusum@epozitif.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('odaksoft-teknoloji-bilisim-a-s', 'ODAKSOFT TEKNOLOJİ BİLİŞİM A.Ş.', 'Ankara', '(850) 308 63 25', 'edonusum@odaksoft.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('dsm-e-donusum-ve-bilisim-hizmetleri-anonim-sirketi', 'DSM E-DÖNÜŞÜM VE BİLİŞİM HİZMETLERİ ANONİM ŞİRKETİ', 'İstanbul', '(212) 331 32 50', 'info@trendyolefaturam.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('rtc-teknoloji-anonim-sirketi', 'RTC Teknoloji Anonim Şirketi', 'İstanbul', '(216) 576 63 88', 'contact@rtcsuite.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('eanship-teknoloji-anonim-sirketi', 'Eanship Teknoloji Anonim Şirketi', 'İstanbul', '(850) 733 91 52', 'info@eanship.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('bym-yazilim-sanayi-ve-ticaret-a-s', 'BYM YAZILIM SANAYİ VE TİCARET A.Ş.', 'Sakarya', '(850) 480 02 96', 'info@bymyazilim.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('akti-f-donusum-yazilim-bi-lgi-sayar-si-stemleri-sanayi-ve-ti-caret-li-mi-ted-si-rketi', 'Akti̇f Dönüşüm Yazılım Bi̇lgi̇sayar Si̇stemleri̇ Sanayi̇ Ve Ti̇caret Li̇mi̇ted Şi̇rketi̇', 'Ankara', '(850) 304 33 88', 'info@aktifdonusum.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('hst-mobil-a-s', 'HST MOBİL A.Ş.', 'İstanbul', '(850) 241 11 12', 'entegrator@hstmobil.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('koyu-teknoloji-anonim-sirketi', 'KOYU TEKNOLOJİ ANONİM ŞİRKETİ', 'Ankara', '(850) 441 56 98', 'entegrator@koyuteknoloji.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('sysmond-yazilim-teknolojileri-a-s', 'SYSMOND YAZILIM TEKNOLOJİLERİ A.Ş.', 'Bursa', '(224) 955 05 05', 'ebelge@sysmond.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('ekupsoft-bilisim-teknolojileri-anonim-sirketi', 'EKÜPSOFT BİLİŞİM TEKNOLOJİLERİ ANONİM ŞİRKETİ', 'Kayseri', '(212) 541 40 02', 'ebelge@ekupbilisim.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('taxten-bilisim-teknolojileri-a-s', 'TAXTEN Bilişim Teknolojileri A.Ş.', 'İstanbul', '(850) 532 82 90', 'info@taxten.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('cg-bilgi-yazilim-ve-danismanlik-anonim-sirketi-bimasraf', 'CG BİLGİ YAZILIM VE DANIŞMANLIK ANONİM ŞİRKETİ (Bimasraf)', 'İstanbul', '(216) 599 10 10', 'info@bimasraf.com', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('datasoft-bilgisayar-sistemleri-yazilim-ve-donanim-san-ve-tic-ltd-sti', 'DATASOFT Bilgisayar Sistemleri Yazılım ve Donanım San. ve Tic. LTD. ŞTİ.', 'İstanbul', '(850) 260 00 83', 'entegrator@datasoft.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('cloud-labs-teknoloji-sis-san-tic-a-s', 'CLOUD LABS TEKNOLOJİ SİS. SAN.TİC.A.Ş.', 'İstanbul', '(850) 277 06 66', 'bilgi@cloudlabs.com.tr', 1, 'karma');
INSERT INTO `entegratorler` (`slug`, `firma_adi`, `sehir`, `telefon`, `email`, `aktif`, `segment`) VALUES ('nox-yazilim-hizmetleri-limited-sirketi', 'Nox Yazılım Hizmetleri Limited Şirketi', 'İzmir', '(554) 978 29 89', 'info@noxyazilim.com.tr', 1, 'karma');

SET foreign_key_checks = 1;

-- ═════════════════════════════════════════════════════════════
-- Kurulum tamamlandı. Toplam 89 entegratör yüklendi.
-- Admin: admin / admin123 (ilk girişte şifre değiştirme zorunlu)
-- ═════════════════════════════════════════════════════════════