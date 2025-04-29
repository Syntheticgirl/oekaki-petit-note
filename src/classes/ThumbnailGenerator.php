<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;

class ThumbnailGenerator {
    private $r2Client;
    private $r2Bucket;
    private $tempDir;
    
    public function __construct() {
        $this->r2Client = getR2Client();
        $this->r2Bucket = getR2Bucket();
        $this->tempDir = getenv('TEMP_DIR') ?: '/tmp';
    }
    
    public function generateThumbnail($sourceKey, $targetKey, $maxWidth, $maxHeight, $options = []) {
        try {
            // 元画像を取得
            $result = $this->r2Client->getObject([
                'Bucket' => $this->r2Bucket,
                'Key'    => $sourceKey
            ]);
            
            // 一時ファイルに保存
            $tempFile = $this->tempDir . '/thumb_' . uniqid();
            file_put_contents($tempFile, $result['Body']);
            
            // 画像を読み込む
            $sourceImage = imagecreatefromstring(file_get_contents($tempFile));
            if (!$sourceImage) {
                unlink($tempFile);
                return false;
            }
            
            // 元のサイズを取得
            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);
            
            // アスペクト比を維持しながらリサイズ
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
            
            // 新しい画像を作成
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // アルファチャンネルを保持
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            
            // リサイズ
            imagecopyresampled(
                $newImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );
            
            // 一時ファイルに保存
            $tempThumbFile = $this->tempDir . '/thumb_' . uniqid();
            
            // WebP形式で保存
            if (isset($options['webp']) && $options['webp']) {
                imagewebp($newImage, $tempThumbFile, 80);
            } else {
                imagejpeg($newImage, $tempThumbFile, 80);
            }
            
            // R2にアップロード
            $this->r2Client->putObject([
                'Bucket' => $this->r2Bucket,
                'Key'    => $targetKey,
                'Body'   => fopen($tempThumbFile, 'rb'),
                'ContentType' => isset($options['webp']) && $options['webp'] ? 'image/webp' : 'image/jpeg'
            ]);
            
            // 一時ファイルを削除
            unlink($tempFile);
            unlink($tempThumbFile);
            
            // メモリを解放
            imagedestroy($sourceImage);
            imagedestroy($newImage);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
} 