<?php

class R2Storage {
    private $accountId;
    private $accessKeyId;
    private $secretAccessKey;
    private $bucketName;
    private $endpoint;

    public function __construct($accountId, $accessKeyId, $secretAccessKey, $bucketName) {
        $this->accountId = $accountId;
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;
        $this->bucketName = $bucketName;
        $this->endpoint = "https://{$accountId}.r2.cloudflarestorage.com";
    }

    public function writeFile($path, $content, $contentType) {
        $url = "{$this->endpoint}/{$this->bucketName}/{$path}";
        
        $headers = [
            'Content-Type: ' . $contentType,
            'x-amz-date: ' . gmdate('Ymd\THis\Z'),
            'x-amz-content-sha256: ' . hash('sha256', $content)
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->accessKeyId}:{$this->secretAccessKey}");

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    public function readFile($path) {
        $url = "{$this->endpoint}/{$this->bucketName}/{$path}";
        
        $headers = [
            'x-amz-date: ' . gmdate('Ymd\THis\Z')
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->accessKeyId}:{$this->secretAccessKey}");

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200 ? $response : false;
    }
} 