<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_Logger {
    public const SYNC_LOG = 'sync.log';
    public const ERROR_LOG = 'error.log';

    public static function info(string $message, array $context = array()): void {
        self::write(self::SYNC_LOG, 'INFO', $message, $context);
    }

    public static function error(string $message, array $context = array()): void {
        self::write(self::ERROR_LOG, 'ERROR', $message, $context);
    }

    public static function get_directory(): string {
        $uploads = wp_upload_dir(null, false);
        $base_dir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : WP_CONTENT_DIR . '/uploads';
        $directory = trailingslashit($base_dir) . 'pcc-logs/';

        if (!is_dir($directory)) {
            wp_mkdir_p($directory);
        }

        return $directory;
    }

    public static function get_file_path(string $filename): string {
        return self::get_directory() . ltrim($filename, '/\\');
    }

    public static function read_tail(string $filename, int $max_lines = 200): array {
        $file = self::get_file_path($filename);
        if (!file_exists($file) || !is_readable($file)) {
            return array();
        }

        $content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($content)) {
            return array();
        }

        return array_slice($content, -1 * max(1, $max_lines));
    }

    private static function write(string $filename, string $level, string $message, array $context = array()): void {
        $line = sprintf(
            "[%s] [%s] %s",
            gmdate('c'),
            $level,
            $message
        );

        if (!empty($context)) {
            $line .= ' | ' . wp_json_encode(self::sanitize_context($context));
        }

        $line .= PHP_EOL;
        file_put_contents(self::get_file_path($filename), $line, FILE_APPEND | LOCK_EX);
    }

    private static function sanitize_context(array $context): array {
        foreach (array('token', 'wstoken', 'moodle_token') as $secret_key) {
            if (isset($context[$secret_key])) {
                $context[$secret_key] = '***';
            }
        }

        return $context;
    }
}
