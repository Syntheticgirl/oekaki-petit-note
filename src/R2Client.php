<?php

namespace App;

use Aws\S3\S3Client;

class R2Client
{
    private S3Client $client;
    private string $bucket;
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf'
    ];

    public function __construct()
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => getenv('R2_ENDPOINT'),
            'credentials' => [
                'key' => getenv('R2_ACCESS_KEY_ID'),
                'secret' => getenv('R2_SECRET_ACCESS_KEY'),
            ],
            'bucket_endpoint' => true,
            'use_path_style_endpoint' => true,
        ]);

        $this->bucket = getenv('R2_BUCKET');
    }

    public function validateFile(array $file): array
    {
        $errors = [];

        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds limit of 5MB';
        }

        if (!in_array($file['type'], self::ALLOWED_TYPES)) {
            $errors[] = 'File type not allowed';
        }

        return $errors;
    }

    public function uploadFile(string $key, string $content, string $contentType = 'application/octet-stream'): bool
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $content,
                'ContentType' => $contentType,
            ]);
            Logger::info('File uploaded successfully', ['key' => $key]);
            return true;
        } catch (\Exception $e) {
            Logger::error('R2 upload error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getFile(string $key): ?string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            Logger::info('File retrieved successfully', ['key' => $key]);
            return $result['Body']->getContents();
        } catch (\Exception $e) {
            Logger::error('R2 get error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function deleteFile(string $key): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            Logger::info('File deleted successfully', ['key' => $key]);
            return true;
        } catch (\Exception $e) {
            Logger::error('R2 delete error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 