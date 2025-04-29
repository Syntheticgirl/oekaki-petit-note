<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\R2Client;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$r2 = new R2Client();

// ファイルのアップロード処理
if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // バリデーション
    $errors = $r2->validateFile($file);
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => $errors]);
        exit;
    }

    $content = file_get_contents($file['tmp_name']);
    $key = 'uploads/' . uniqid() . '_' . basename($file['name']);
    
    if ($r2->uploadFile($key, $content, $file['type'])) {
        echo json_encode([
            'success' => true,
            'key' => $key,
            'url' => '/api/get.php?key=' . urlencode($key)
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
} 