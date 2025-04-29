<?php
// R2設定
define('R2_ENDPOINT', getenv('R2_ENDPOINT'));
define('R2_ACCESS_KEY_ID', getenv('R2_ACCESS_KEY_ID'));
define('R2_SECRET_ACCESS_KEY', getenv('R2_SECRET_ACCESS_KEY'));
define('R2_REGION', getenv('R2_REGION') ?: 'auto');
define('R2_BUCKET', getenv('R2_BUCKET'));
define('R2_PUBLIC_URL', getenv('R2_PUBLIC_URL')); 