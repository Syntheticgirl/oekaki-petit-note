<?php

namespace App;

use Aws\S3\S3Client;

class R2Client
{
    private S3Client $client;
    private string $bucket;

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

    public function uploadFile(string $key, string $content, string $contentType = 'application/octet-stream'): bool
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $content,
                'ContentType' => $contentType,
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("R2 upload error: " . $e->getMessage());
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
            return $result['Body']->getContents();
        } catch (\Exception $e) {
            error_log("R2 get error: " . $e->getMessage());
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
            return true;
        } catch (\Exception $e) {
            error_log("R2 delete error: " . $e->getMessage());
            return false;
        }
    }
} 