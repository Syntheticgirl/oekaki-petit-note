<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class R2Storage {
    private $client;
    private $bucket;
    private $publicUrl;
    
    public function __construct() {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => R2_REGION,
            'endpoint' => R2_ENDPOINT,
            'credentials' => [
                'key' => R2_ACCESS_KEY_ID,
                'secret' => R2_SECRET_ACCESS_KEY,
            ],
            'use_path_style_endpoint' => true,
        ]);
        $this->bucket = R2_BUCKET;
        $this->publicUrl = R2_PUBLIC_URL;
    }
    
    public function uploadFile($localPath, $key) {
        try {
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $localPath,
                'ACL' => 'public-read',
            ]);
            return $this->getFileUrl($key);
        } catch (AwsException $e) {
            error_log('R2 Upload Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getFileUrl($key) {
        if ($this->publicUrl) {
            return rtrim($this->publicUrl, '/') . '/' . $key;
        }
        
        try {
            $cmd = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key
            ]);
            $request = $this->client->createPresignedRequest($cmd, '+1 day');
            return (string)$request->getUri();
        } catch (AwsException $e) {
            error_log('R2 GetURL Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function deleteFile($key) {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            return true;
        } catch (AwsException $e) {
            error_log('R2 Delete Error: ' . $e->getMessage());
            return false;
        }
    }
} 