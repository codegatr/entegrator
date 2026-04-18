<?php
/**
 * entegrator.codega.com.tr — konfigürasyon
 * Bu dosya update ZIP'lerine DAHİL EDİLMEZ.
 */

// ── Veritabanı ──────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'codega_entegrator');
define('DB_USER', 'codega_entegrator');
define('DB_PASS', 'CHANGE_ME_IN_PRODUCTION');
define('DB_CHARSET', 'utf8mb4');

// ── Site ────────────────────────────────────────────────────
define('SITE_URL',   'https://entegrator.codega.com.tr');
define('SITE_NAME',  'Entegratör Rehberi');
define('SITE_DESC',  'GİB Onaylı e-Fatura, e-Arşiv ve e-Dönüşüm Özel Entegratörleri — CODEGA Rehberi');
define('SITE_OWNER', 'CODEGA');
define('CONTACT_EMAIL', 'info@codega.com.tr');
define('CONTACT_PHONE', '+90 332 XXX XX XX');

// ── Admin varsayılan (ilk çalıştırmada oluşur) ──────────────
define('ADMIN_DEFAULT_USER', 'admin');
define('ADMIN_DEFAULT_PASS', 'admin123');  // İlk girişte değiştirilmesi zorunlu

// ── Güvenlik ────────────────────────────────────────────────
define('CSRF_SECRET', 'CHANGE_THIS_TO_RANDOM_32_CHARS_STRING');
define('SESSION_NAME', 'entegrator_session');

// ── Paths ───────────────────────────────────────────────────
define('ROOT_PATH', __DIR__);
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGOS_PATH', UPLOADS_PATH . '/logos');
define('UPLOADS_URL', SITE_URL . '/uploads');
define('LOGOS_URL', UPLOADS_URL . '/logos');

// ── PDO ──────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('DB bağlantı hatası. Lütfen yönetici ile iletişime geçin.');
}

// ── Session ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ── Basit helper'lar ────────────────────────────────────────
function h(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

function slugify(string $text): string {
    $tr = ['ğ'=>'g','Ğ'=>'g','ü'=>'u','Ü'=>'u','ş'=>'s','Ş'=>'s','ı'=>'i','İ'=>'i','ö'=>'o','Ö'=>'o','ç'=>'c','Ç'=>'c'];
    $text = strtr($text, $tr);
    $text = preg_replace('~[^a-zA-Z0-9]+~', '-', $text);
    $text = strtolower(trim($text, '-'));
    return $text ?: 'firma';
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!admin_logged_in()) {
        header('Location: /yonetim.php');
        exit;
    }
}

// ── Meta helper (SEO) ───────────────────────────────────────
function render_head(string $title, string $desc = '', string $canonical = '', ?array $og = null): string {
    $t = $title . ' · ' . SITE_NAME;
    $d = $desc ?: SITE_DESC;
    $c = $canonical ?: SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/');
    $ogTitle = $og['title'] ?? $t;
    $ogDesc  = $og['desc']  ?? $d;
    $ogImg   = $og['image'] ?? (SITE_URL.'/assets/og-default.png');

    return '<title>'.h($t).'</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="'.h($d).'">
<link rel="canonical" href="'.h($c).'">
<meta property="og:type" content="website">
<meta property="og:title" content="'.h($ogTitle).'">
<meta property="og:description" content="'.h($ogDesc).'">
<meta property="og:image" content="'.h($ogImg).'">
<meta property="og:url" content="'.h($c).'">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="/assets/style.css?v=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">';
}
