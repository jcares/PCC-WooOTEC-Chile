<?php

if (!defined('ABSPATH')) {
    exit;
}

class PCC_Logger {
    private const OPTION_DEBUG = 'pcc_debug_mode';

    public static function log($message, $level = 'info', $context = array(), $force = false) {
        $level = strtolower((string) $level);
        $is_debug = (bool) get_option(self::OPTION_DEBUG, false);

        if (!$force && !$is_debug && $level !== 'error') {
            return;
        }

        $line = '[' . gmdate('c') . '] [' . $level . '] ' . self::stringify($message);
        if (!empty($context)) {
            $line .= ' | ' . wp_json_encode(self::sanitize_context($context));
        }
        $line .= "\n";

        $path = self::get_log_file_path();
        if ($path) {
            self::ensure_dir(dirname($path));
            @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
            return;
        }

        error_log('[PCC-WooOTEC] ' . rtrim($line));
    }

    public static function error($message, $context = array()) {
        self::log($message, 'error', $context, true);
    }

    private static function stringify($value) {
        if (is_string($value)) {
            return $value;
        }
        return wp_json_encode($value);
    }

    private static function sanitize_context($context) {
        if (!is_array($context)) {
            return array();
        }

        $safe = $context;
        if (isset($safe['wstoken'])) {
            $safe['wstoken'] = '***';
        }
        if (isset($safe['token'])) {
            $safe['token'] = '***';
        }
        return $safe;
    }

    private static function get_log_file_path() {
        // Prefer uploads (más probable que tenga permisos de escritura).
        if (function_exists('wp_upload_dir')) {
            $uploads = wp_upload_dir(null, false);
            if (!empty($uploads['basedir']) && is_string($uploads['basedir'])) {
                return rtrim($uploads['basedir'], '/\\') . '/pcc-woootec/logs/pcc-woootec.log';
            }
        }

        return null;
    }

    public static function get_log_path() {
        return self::get_log_file_path();
    }

    private static function ensure_dir($dir) {
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
    }
}

if (!function_exists('pcc_log')) {
    function pcc_log($message, $context = array()) {
        if (class_exists('PCC_Logger')) {
            PCC_Logger::log($message, 'info', is_array($context) ? $context : array(), false);
            return;
        }

        if (!is_string($message)) {
            $message = wp_json_encode($message);
        }

        error_log('[PCC-WooOTEC] ' . $message);
    }
}
