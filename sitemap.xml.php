<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

$urls = [
    [SITE_URL.'/', '1.0', 'daily'],
    [SITE_URL.'/karsilastir.php', '0.6', 'weekly'],
    [SITE_URL.'/hakkinda.php', '0.4', 'monthly'],
    [SITE_URL.'/iletisim.php', '0.4', 'monthly'],
];

try {
    $hzl = $pdo->query("SELECT kod FROM hizmet_turleri ORDER BY siralama")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($hzl as $k) $urls[] = [SITE_URL.'/hizmet.php?h='.str_replace('_','-',$k), '0.7', 'weekly'];

    $erl = $pdo->query("SELECT kod FROM erp_uyumluluk WHERE (SELECT COUNT(*) FROM entegrator_erp WHERE erp_id=erp_uyumluluk.id)>0 ORDER BY siralama")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($erl as $k) $urls[] = [SITE_URL.'/erp.php?e='.$k, '0.6', 'weekly'];

    $entl = $pdo->query("SELECT slug, updated_at FROM entegratorler WHERE aktif=1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($entl as $e) $urls[] = [SITE_URL.'/e.php?s='.$e['slug'], '0.8', 'weekly', $e['updated_at']];
} catch(Exception $e) {}

foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>".htmlspecialchars($u[0])."</loc>\n";
    if (!empty($u[3])) echo "    <lastmod>".substr($u[3],0,10)."</lastmod>\n";
    echo "    <changefreq>".$u[2]."</changefreq>\n";
    echo "    <priority>".$u[1]."</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>'."\n";
