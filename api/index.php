<?php
// api/index.php  --- Serverless Function のエントリーポイント
// Petit Note のソースは public 側に置いたままでも OK
chdir(__DIR__ . '/../public');  // 作業ディレクトリを変更
require 'index.php';            // Petit Note 本体を読み込む