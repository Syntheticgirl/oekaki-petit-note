<?php  
// r2_storage.php - Cloudflare R2ストレージとの連携用クラス  
  
require 'vendor/autoload.php';  
  
use Aws\S3\S3Client;  
use Aws\Exception\AwsException;  
  
class R2Storage {  
    private $s3;  
    private $bucket;  
    private $basePrefix;  
    private $publicUrl;  
      
    public function __construct($config) {  
        $this->s3 = new S3Client([  
            'version' => 'latest',  
            'region' => 'auto',  
            'endpoint' => $config['endpoint'],  
            'credentials' => [  
                'key' => $config['access_key_id'],  
                'secret' => $config['secret_access_key'],  
            ],  
            'use_path_style_endpoint' => true,  
        ]);  
        $this->bucket = $config['bucket'];  
        $this->basePrefix = $config['prefix'] ?? '';  
        $this->publicUrl = $config['public_url'] ?? null;  
    }  
      
    // ファイル読み込み  
    public function readFile($path) {  
        try {  
            $result = $this->s3->getObject([  
                'Bucket' => $this->bucket,  
                'Key' => $this->basePrefix . $path,  
            ]);  
            return $result['Body']->getContents();  
        } catch (AwsException $e) {  
            error_log("R2 read error: " . $e->getMessage());  
            return false;  
        }  
    }  
      
    // ファイル書き込み  
    public function writeFile($path, $content, $contentType = null) {  
        try {  
            $params = [  
                'Bucket' => $this->bucket,  
                'Key' => $this->basePrefix . $path,  
                'Body' => $content,  
                'ACL' => 'public-read',  
            ];  
              
            if ($contentType) {  
                $params['ContentType'] = $contentType;  
            }  
              
            $this->s3->putObject($params);  
            return true;  
        } catch (AwsException $e) {  
            error_log("R2 write error: " . $e->getMessage());  
            return false;  
        }  
    }  
      
    // ファイル削除  
    public function deleteFile($path) {  
        try {  
            $this->s3->deleteObject([  
                'Bucket' => $this->bucket,  
                'Key' => $this->basePrefix . $path,  
            ]);  
            return true;  
        } catch (AwsException $e) {  
            error_log("R2 delete error: " . $e->getMessage());  
            return false;  
        }  
    }  
      
    // ファイルの存在確認  
    public function fileExists($path) {  
        try {  
            return $this->s3->doesObjectExist($this->bucket, $this->basePrefix . $path);  
        } catch (AwsException $e) {  
            error_log("R2 check error: " . $e->getMessage());  
            return false;  
        }  
    }  
      
    // ディレクトリ内のファイル一覧取得  
    public function listFiles($directory) {  
        try {  
            $prefix = $this->basePrefix . $directory;  
            $result = $this->s3->listObjectsV2([  
                'Bucket' => $this->bucket,  
                'Prefix' => $prefix,  
            ]);  
              
            $files = [];  
            if (isset($result['Contents'])) {  
                foreach ($result['Contents'] as $object) {  
                    $key = $object['Key'];  
                    // プレフィックスを除去してファイル名だけを取得  
                    $fileName = substr($key, strlen($prefix));  
                    if (!empty($fileName)) {  
                        $files[] = $fileName;  
                    }  
                }  
            }  
            return $files;  
        } catch (AwsException $e) {  
            error_log("R2 list error: " . $e->getMessage());  
            return [];  
        }  
    }  
      
    // ファイルのURLを取得  
    public function getFileUrl($path) {  
        if ($this->publicUrl) {  
            // カスタムドメインが設定されている場合  
            return rtrim($this->publicUrl, '/') . '/' . $this->basePrefix . $path;  
        } else {  
            // デフォルトのR2 URLを使用  
            return $this->s3->getObjectUrl($this->bucket, $this->basePrefix . $path);  
        }  
    }  
      
    // ファイルをアップロードしてURLを返す  
    public function uploadFile($localPath, $remotePath, $contentType = null) {  
        if (!file_exists($localPath)) {  
            return false;  
        }  
          
        $content = file_get_contents($localPath);  
        if ($this->writeFile($remotePath, $content, $contentType)) {  
            return $this->getFileUrl($remotePath);  
        }  
          
        return false;  
    }  
}