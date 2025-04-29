<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\R2Client;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$key = $_GET['key'] ?? null;

if (!$key) {
    http_response_code(400);
    echo json_encode(['error' => 'Key parameter is required']);
    exit;
}

$r2 = new R2Client();
$content = $r2->getFile($key);

if ($content === null) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// ファイルの種類に応じてContent-Typeを設定
$extension = pathinfo($key, PATHINFO_EXTENSION);
$contentType = match($extension) {
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    default => 'application/octet-stream'
};

header('Content-Type: ' . $contentType);
echo $content; 