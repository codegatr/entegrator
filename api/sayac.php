<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false]); exit; }

try {
    $pdo->prepare("UPDATE entegratorler SET goruntulenme=goruntulenme+1 WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]);
} catch(Exception $e) {
    echo json_encode(['ok'=>false]);
}
