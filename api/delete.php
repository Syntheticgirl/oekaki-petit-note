<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\R2Client;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

if ($r2->deleteFile($key)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete file']);
} 