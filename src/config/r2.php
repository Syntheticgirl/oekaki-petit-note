<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;

function getR2Client() {
    return new S3Client([
        'version' => 'latest',
        'region'  => 'auto',
        'endpoint' => $_ENV['R2_ENDPOINT'],
        'credentials' => [
            'key'    => $_ENV['R2_ACCESS_KEY_ID'],
            'secret' => $_ENV['R2_SECRET_ACCESS_KEY'],
        ],
    ]);
}

function getR2Bucket() {
    return $_ENV['R2_BUCKET'];
} 