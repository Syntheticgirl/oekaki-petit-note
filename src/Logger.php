<?php

namespace App;

class Logger
{
    private const LOG_FILE = __DIR__ . '/../logs/app.log';

    public static function error(string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'message' => $message,
            'context' => $context
        ];

        $logDir = dirname(self::LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents(
            self::LOG_FILE,
            json_encode($logEntry) . PHP_EOL,
            FILE_APPEND
        );
    }

    public static function info(string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'message' => $message,
            'context' => $context
        ];

        $logDir = dirname(self::LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents(
            self::LOG_FILE,
            json_encode($logEntry) . PHP_EOL,
            FILE_APPEND
        );
    }
} 