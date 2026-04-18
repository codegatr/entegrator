<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) { echo json_encode(['results'=>[]]); exit; }

try {
    $lk = '%'.$q.'%';
    $st = $pdo->prepare("SELECT id, slug, firma_adi, kisa_aciklama, logo_url
        FROM entegratorler
        WHERE aktif=1 AND (firma_adi LIKE ? OR kisa_aciklama LIKE ? OR slug LIKE ?)
        ORDER BY
          CASE
            WHEN firma_adi LIKE ? THEN 1
            WHEN firma_adi LIKE ? THEN 2
            ELSE 3
          END,
          goruntulenme DESC
        LIMIT 8");
    $st->execute([$lk, $lk, $lk, $q.'%', '% '.$q.'%']);
    echo json_encode(['results' => $st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'search_failed']);
}
