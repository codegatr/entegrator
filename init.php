<?php
/**
 * Migration + seed. Her sayfa yüklemede çalışır ama idempotent.
 * Production'da bir kez çalıştırılması yeterli — include ile her yerden tetiklenir.
 */
require_once __DIR__ . '/config.php';

// ── Tablolar ────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS entegratorler (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) UNIQUE NOT NULL,
    firma_adi VARCHAR(200) NOT NULL,
    kisa_aciklama VARCHAR(500) DEFAULT NULL,
    uzun_aciklama TEXT DEFAULT NULL,
    logo_url VARCHAR(255) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    telefon VARCHAR(50) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    adres TEXT DEFAULT NULL,
    sehir VARCHAR(60) DEFAULT NULL,
    vergi_no VARCHAR(20) DEFAULT NULL,
    gib_onay_tarihi DATE DEFAULT NULL,
    onay_numarasi VARCHAR(50) DEFAULT NULL,
    iso_27001 TINYINT(1) DEFAULT 0,
    kvkk_uyumlu TINYINT(1) DEFAULT 0,
    segment ENUM('kobi','kurumsal','karma') DEFAULT 'karma',
    aktif TINYINT(1) DEFAULT 1,
    one_cikan TINYINT(1) DEFAULT 0,
    goruntulenme INT UNSIGNED DEFAULT 0,
    siralama INT DEFAULT 100,
    seo_title VARCHAR(200) DEFAULT NULL,
    seo_desc VARCHAR(300) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_aktif (aktif),
    KEY idx_segment (segment),
    FULLTEXT KEY ft_arama (firma_adi, kisa_aciklama, uzun_aciklama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS hizmet_turleri (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(50) UNIQUE NOT NULL,
    ad VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'file-invoice',
    renk VARCHAR(20) DEFAULT '#3b82f6',
    aciklama TEXT DEFAULT NULL,
    siralama INT DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS entegrator_hizmetler (
    entegrator_id INT UNSIGNED NOT NULL,
    hizmet_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (entegrator_id, hizmet_id),
    KEY idx_hizmet (hizmet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS erp_uyumluluk (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kod VARCHAR(50) UNIQUE NOT NULL,
    ad VARCHAR(100) NOT NULL,
    logo_url VARCHAR(255) DEFAULT NULL,
    siralama INT DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS entegrator_erp (
    entegrator_id INT UNSIGNED NOT NULL,
    erp_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (entegrator_id, erp_id),
    KEY idx_erp (erp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS admin_kullanicilar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) UNIQUE NOT NULL,
    sifre_hash VARCHAR(255) NOT NULL,
    ad_soyad VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    rol ENUM('admin','moderator') DEFAULT 'admin',
    aktif TINYINT(1) DEFAULT 1,
    sifre_degistirildi TINYINT(1) DEFAULT 0,
    son_giris DATETIME DEFAULT NULL,
    son_ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS ziyaretci_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    sayfa VARCHAR(255) DEFAULT NULL,
    entegrator_id INT UNSIGNED DEFAULT NULL,
    referrer VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_entegrator (entegrator_id),
    KEY idx_tarih (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed: Hizmet türleri (11 tane) ──────────────────────────
$hizmetler_seed = [
    ['e_fatura',       'e-Fatura',             'file-invoice',        '#3b82f6', 'Ticari faturaların elektronik ortamda oluşturulması ve iletilmesi'],
    ['e_arsiv',        'e-Arşiv Fatura',       'file-invoice-dollar', '#10b981', 'Son kullanıcılara kesilen elektronik arşiv faturaları'],
    ['e_irsaliye',     'e-İrsaliye',           'truck',               '#f59e0b', 'Elektronik sevk irsaliyesi'],
    ['e_defter',       'e-Defter',             'book',                '#8b5cf6', 'Elektronik yevmiye ve büyük defter'],
    ['e_smm',          'e-SMM',                'receipt',             '#ec4899', 'e-Serbest Meslek Makbuzu'],
    ['e_mustahsil',    'e-Müstahsil Makbuzu',  'leaf',                '#65a30d', 'Zirai ürün alım makbuzu'],
    ['e_doviz',        'e-Döviz',              'money-bill-transfer', '#0891b2', 'Döviz alım-satım belgesi'],
    ['e_adisyon',      'e-Adisyon',            'utensils',            '#dc2626', 'Restoran/kafe adisyon belgesi'],
    ['e_dekont',       'e-Dekont',             'file-invoice',        '#6366f1', 'Banka dekont paketi'],
    ['e_sigorta',      'e-Sigorta',            'shield-halved',       '#7c3aed', 'Sigorta komisyon gider belgesi'],
    ['e_gider_pusula', 'e-Gider Pusulası',     'receipt',             '#ea580c', 'Gider pusulası elektronik'],
];
$hs = $pdo->prepare("INSERT IGNORE INTO hizmet_turleri (kod, ad, icon, renk, aciklama, siralama) VALUES (?,?,?,?,?,?)");
foreach ($hizmetler_seed as $i => $row) $hs->execute([$row[0], $row[1], $row[2], $row[3], $row[4], ($i+1)*10]);

// ── Seed: ERP uyumluluk (9 tane) ────────────────────────────
$erp_seed = [
    ['logo',       'Logo Yazılım'],
    ['mikro',      'Mikro Yazılım'],
    ['netsis',     'Netsis'],
    ['sap',        'SAP'],
    ['parasut',    'Paraşüt'],
    ['zirve',      'Zirve'],
    ['nebim',      'Nebim'],
    ['luca',       'Luca'],
    ['codega_erp', 'CodeGa ERP'],
];
$es = $pdo->prepare("INSERT IGNORE INTO erp_uyumluluk (kod, ad, siralama) VALUES (?,?,?)");
foreach ($erp_seed as $i => $row) $es->execute([$row[0], $row[1], ($i+1)*10]);

// ── Seed: İlk admin ─────────────────────────────────────────
try {
    $chk = $pdo->query("SELECT COUNT(*) FROM admin_kullanicilar")->fetchColumn();
    if ((int)$chk === 0) {
        $hash = password_hash(ADMIN_DEFAULT_PASS, PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO admin_kullanicilar (kullanici_adi, sifre_hash, ad_soyad, rol, sifre_degistirildi) VALUES (?,?,?,?,0)")
            ->execute([ADMIN_DEFAULT_USER, $hash, 'Kurucu Admin', 'admin']);
    }
} catch(Exception $e) { error_log('Admin seed: '.$e->getMessage()); }

// ── Helper: entegrator çekme ────────────────────────────────
function ent_get_by_slug(PDO $pdo, string $slug): ?array {
    $q = $pdo->prepare("SELECT * FROM entegratorler WHERE slug=? AND aktif=1 LIMIT 1");
    $q->execute([$slug]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
function ent_get_hizmetler(PDO $pdo, int $ent_id): array {
    $q = $pdo->prepare("SELECT h.* FROM hizmet_turleri h JOIN entegrator_hizmetler eh ON eh.hizmet_id=h.id WHERE eh.entegrator_id=? ORDER BY h.siralama");
    $q->execute([$ent_id]);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}
function ent_get_erp(PDO $pdo, int $ent_id): array {
    $q = $pdo->prepare("SELECT e.* FROM erp_uyumluluk e JOIN entegrator_erp ee ON ee.erp_id=e.id WHERE ee.entegrator_id=? ORDER BY e.siralama");
    $q->execute([$ent_id]);
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

// ── Otomatik GİB seed (ilk çalıştırmada) ───────────────────
function ent_seed_from_tsv(PDO $pdo): int {
    $tsv_path = __DIR__ . '/seed/gib_entegratorler.tsv';
    if (!file_exists($tsv_path)) return 0;

    // Sadece tablo boşsa seed yap
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM entegratorler")->fetchColumn();
    if ($cnt > 0) return 0;

    $tsv = file_get_contents($tsv_path);
    if (!$tsv) return 0;

    $lines = preg_split('~\r\n|\r|\n~', trim($tsv));
    array_shift($lines);  // başlık satırını atla

    $eklenen = 0;
    $st = $pdo->prepare("INSERT IGNORE INTO entegratorler (slug, firma_adi, sehir, telefon, email, aktif, segment) VALUES (?,?,?,?,?,1,'karma')");
    foreach ($lines as $ln) {
        $cols = explode("\t", $ln);
        $firma = trim($cols[0] ?? '');
        if (strlen($firma) < 3) continue;
        $sehir = trim($cols[1] ?? '');
        $tel   = trim($cols[2] ?? '');
        $mail  = trim($cols[3] ?? '');
        $slug  = slugify($firma);
        try {
            $st->execute([$slug, $firma, $sehir ?: null, $tel ?: null, $mail ?: null]);
            if ($st->rowCount() > 0) $eklenen++;
        } catch(Exception $e) {}
    }
    return $eklenen;
}

// İlk sayfa açılışında otomatik seed (idempotent)
try { ent_seed_from_tsv($pdo); } catch(Exception $e) { error_log('CF auto-seed: '.$e->getMessage()); }

// ── Ziyaretçi log ──────────────────────────────────────────
function ent_log_visit(PDO $pdo, ?int $ent_id = null): void {
    try {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $pg = substr($_SERVER['REQUEST_URI'] ?? '/', 0, 255);
        $rf = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255);
        $pdo->prepare("INSERT INTO ziyaretci_log (ip, user_agent, sayfa, entegrator_id, referrer) VALUES (?,?,?,?,?)")
            ->execute([$ip, $ua, $pg, $ent_id, $rf]);
    } catch(Exception $e) { /* sessiz */ }
}
