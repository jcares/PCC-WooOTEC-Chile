<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_logs_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $log_path = class_exists('PCC_Logger') ? PCC_Logger::get_log_path() : null;
    $lines = array();
    $error = null;

    if ($log_path && file_exists($log_path) && is_readable($log_path)) {
        $max_lines = (int) apply_filters('pcc_logs_max_lines', 250);
        if ($max_lines < 10) {
            $max_lines = 10;
        }
        if ($max_lines > 2000) {
            $max_lines = 2000;
        }

        $lines = pcc_read_last_lines($log_path, $max_lines);
    } else {
        $error = $log_path ? 'El archivo de log aún no existe (o no es legible).' : 'No se pudo determinar la ruta del log.';
    }

    ?>

    <div class="wrap pcc-admin">
        <h1>Logs PCC WooOTEC</h1>

        <p>
            <strong>Archivo:</strong>
            <code><?php echo esc_html((string) $log_path); ?></code>
        </p>

        <?php if ($error) : ?>
            <div class="notice notice-info"><p><?php echo esc_html($error); ?></p></div>
        <?php endif; ?>

        <form method="post" style="margin: 12px 0;">
            <?php wp_nonce_field('pcc_logs_refresh'); ?>
            <input type="submit" name="pcc_logs_refresh" class="button" value="Refrescar">
        </form>

        <pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:600px;overflow:auto;white-space:pre-wrap;"><?php
        if (!empty($lines)) {
            echo esc_html(implode("\n", $lines));
        } else {
            echo esc_html('(sin logs)');
        }
        ?></pre>
    </div>

    <?php
}

function pcc_read_last_lines($file_path, $max_lines) {
    $max_lines = (int) $max_lines;
    if ($max_lines < 1) {
        $max_lines = 1;
    }

    $fp = @fopen($file_path, 'rb');
    if (!$fp) {
        return array();
    }

    $buffer = '';
    $chunk_size = 8192;
    $pos = -1;
    $line_count = 0;

    fseek($fp, 0, SEEK_END);
    $file_size = ftell($fp);
    if ($file_size === 0) {
        fclose($fp);
        return array();
    }

    while ($line_count <= $max_lines && -$pos < $file_size) {
        $read_size = min($chunk_size, $file_size + $pos);
        $pos -= $read_size;
        fseek($fp, $pos, SEEK_END);
        $chunk = fread($fp, $read_size);
        $buffer = $chunk . $buffer;
        $line_count = substr_count($buffer, "\n");
        if ($pos === -$file_size) {
            break;
        }
    }

    fclose($fp);

    $all_lines = preg_split("/\r\n|\n|\r/", trim($buffer));
    if (!is_array($all_lines)) {
        return array();
    }

    return array_slice($all_lines, -$max_lines);
}

