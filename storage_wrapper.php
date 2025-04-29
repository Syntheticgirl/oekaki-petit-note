<?php  
// storage_wrapper.php - Petit Noteのファイル操作をオーバーライド  
  
require_once 'r2_storage.php';  
  
// R2ストレージの初期化  
$r2Config = [  
    'endpoint' => getenv('R2_ENDPOINT'), // 例: https://xxxxx.r2.cloudflarestorage.com  
    'access_key_id' => getenv('R2_ACCESS_KEY_ID'),  
    'secret_access_key' => getenv('R2_SECRET_ACCESS_KEY'),  
    'bucket' => getenv('R2_BUCKET'),  
    'prefix' => getenv('R2_PREFIX') ?? 'petitnote/',  
    'public_url' => getenv('R2_PUBLIC_URL') ?? null, // カスタムドメインがある場合  
];  

// 環境変数が設定されている場合のみR2ストレージを初期化
if ($r2Config['endpoint'] && $r2Config['access_key_id'] && $r2Config['secret_access_key'] && $r2Config['bucket']) {
    try {
        $r2Storage = new R2Storage($r2Config);
        error_log('R2 storage initialized successfully');
    } catch (Exception $e) {
        error_log('R2 storage initialization failed: ' . $e->getMessage());
        $r2Storage = null;
    }
} else {
    error_log('R2 storage configuration is incomplete. Falling back to local file system.');
    $r2Storage = null;
}

// キャッシュの設定  
$fileCache = [];  
$cacheExpiry = [];  
$cacheTTL = 300; // 5分  
  
// ログファイルを開く関数のオーバーライド  
function openLogFile($filename, $mode) {  
    global $r2Storage, $fileCache, $cacheExpiry;  
      
    $path = 'log/' . $filename;  
      
    if ($mode === 'r') {  
        // キャッシュチェック  
        $now = time();  
        if (isset($fileCache[$path]) && isset($cacheExpiry[$path]) && $cacheExpiry[$path] > $now) {  
            // キャッシュが有効  
            $content = $fileCache[$path];  
        } else {  
            // R2から読み込み  
            $content = $r2Storage ? $r2Storage->readFile($path) : file_get_contents($path);  
            if ($content === false) {  
                // ファイルが存在しない場合は空のコンテンツを返す  
                $content = '';  
            }  
              
            // キャッシュに保存  
            $fileCache[$path] = $content;  
            $cacheExpiry[$path] = $now + $cacheTTL;  
        }  
          
        // メモリ上の一時ファイルを作成して返す  
        $temp = fopen('php://temp', 'r+');  
        fwrite($temp, $content);  
        rewind($temp);  
        return $temp;  
    } else {  
        // 書き込みモードの場合は一時ファイルを返し、closeFile時に保存  
        return [  
            'handle' => fopen('php://temp', 'r+'),  
            'path' => $path,  
            'mode' => $mode  
        ];  
    }  
}  
  
// ファイルクローズ時の処理  
function closeFile($fp) {  
    global $r2Storage, $fileCache, $cacheExpiry;  
      
    if (is_array($fp)) {  
        // 書き込みモードの場合  
        rewind($fp['handle']);  
        $content = stream_get_contents($fp['handle']);  
        fclose($fp['handle']);  
          
        // R2に保存  
        $result = $r2Storage ? $r2Storage->writeFile($fp['path'], $content) : file_put_contents($fp['path'], $content);  
          
        // キャッシュを更新  
        if ($result) {  
            $fileCache[$fp['path']] = $content;  
            $cacheExpiry[$fp['path']] = time() + $cacheTTL;  
        }  
          
        return $result;  
    } else {  
        // 読み込みモードの場合は単にクローズ  
        return fclose($fp);  
    }  
}  
  
// ファイル存在チェックのオーバーライド  
function file_exists_wrapper($path) {  
    global $r2Storage;  
      
    // ローカルパスの場合はデフォルトの関数を使用  
    if (strpos($path, 'log/') === 0 || strpos($path, 'src/') === 0 ||   
        strpos($path, 'thumbnail/') === 0 || strpos($path, 'temp/') === 0) {  
        return $r2Storage ? $r2Storage->fileExists($path) : file_exists($path);  
    }  
      
    // それ以外はデフォルトの関数を使用  
    return file_exists($path);  
}  
  
// ファイル読み込みのオーバーライド  
function file_get_contents_wrapper($path) {  
    global $r2Storage, $fileCache, $cacheExpiry;  
      
    // ストレージ対象のパスかチェック  
    if (strpos($path, 'log/') === 0 || strpos($path, 'src/') === 0 ||   
        strpos($path, 'thumbnail/') === 0) {  
          
        // キャッシュチェック  
        $now = time();  
        if (isset($fileCache[$path]) && isset($cacheExpiry[$path]) && $cacheExpiry[$path] > $now) {  
            return $fileCache[$path];  
        }  
          
        $content = $r2Storage ? $r2Storage->readFile($path) : file_get_contents($path);  
        if ($content !== false) {  
            // キャッシュに保存  
            $fileCache[$path] = $content;  
            $cacheExpiry[$path] = $now + $cacheTTL;  
        }  
        return $content;  
    }  
      
    // それ以外はデフォルトの関数を使用  
    return file_get_contents($path);  
}  
  
// ファイル書き込みのオーバーライド  
function file_put_contents_wrapper($path, $content) {  
    global $r2Storage, $fileCache, $cacheExpiry;  
      
    // ストレージ対象のパスかチェック  
    if (strpos($path, 'log/') === 0 || strpos($path, 'src/') === 0 ||   
        strpos($path, 'thumbnail/') === 0 || strpos($path, 'temp/') === 0) {  
          
        $result = $r2Storage ? $r2Storage->writeFile($path, $content) : file_put_contents($path, $content);  
          
        if ($result) {  
            // キャッシュを更新  
            $fileCache[$path] = $content;  
            $cacheExpiry[$path] = time() + $cacheTTL;  
            return strlen($content); // 成功時は書き込んだバイト数を返す  
        }  
        return false;  
    }  
      
    // それ以外はデフォルトの関数を使用  
    return file_put_contents($path, $content);  
}  
  
// ファイル削除のオーバーライド  
function unlink_wrapper($path) {  
    global $r2Storage, $fileCache, $cacheExpiry;  
      
    // ストレージ対象のパスかチェック  
    if (strpos($path, 'log/') === 0 || strpos($path, 'src/') === 0 ||   
        strpos($path, 'thumbnail/') === 0 || strpos($path, 'temp/') === 0) {  
          
        $result = $r2Storage ? $r2Storage->deleteFile($path) : unlink($path);  
          
        if ($result) {  
            // キャッシュから削除  
            unset($fileCache[$path]);  
            unset($cacheExpiry[$path]);  
        }  
          
        return $result;  
    }  
      
    // それ以外はデフォルトの関数を使用  
    return unlink($path);  
}  
  
// ディレクトリ内のファイル一覧取得のオーバーライド  
function scandir_wrapper($directory) {  
    global $r2Storage;  
      
    // ストレージ対象のディレクトリかチェック  
    if ($directory === 'log/' || $directory === 'src/' ||   
        $directory === 'thumbnail/' || $directory === 'temp/') {  
          
        $files = $r2Storage ? $r2Storage->listFiles($directory) : scandir($directory);  
        // 現在のディレクトリと親ディレクトリを追加（scandir互換にするため）  
        array_unshift($files, '.', '..');  
        return $files;  
    }  
      
    // それ以外はデフォルトの関数を使用  
    return scandir($directory);  
}  
  
// 画像URLの取得  
function getImageUrl($path) {  
    global $r2Storage;  
    return $r2Storage ? $r2Storage->getFileUrl($path) : $path;  
}  
  
Wiki pages you might want to explore:  
- [Overview (satopian/Petit_Note)](/wiki/satopian/Petit_Note#1)  
- [Social Media Integration (satopian/Petit_Note)](/wiki/satopian/Petit_Note#5)