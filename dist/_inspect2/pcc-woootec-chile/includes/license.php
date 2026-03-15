<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validación de licencia (base).
 * Nota: el flujo completo de licenciamiento se implementará en un módulo futuro.
 */
function pcc_validate_license($key) {
    $key = sanitize_text_field((string) $key);
    if ($key === '') {
        return false;
    }

    $domain = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field((string) $_SERVER['SERVER_NAME']) : '';
    if ($domain === '') {
        return false;
    }

    $response = wp_remote_post(
        'https://licencias.pccurico.cl/api/validate',
        array(
            'timeout' => 15,
            'body'    => array(
                'license_key' => $key,
                'domain'      => $domain,
            ),
        )
    );

    if (is_wp_error($response)) {
        if (function_exists('pcc_log')) {
            pcc_log('Licencia: error de conexión', array('error' => $response->get_error_message()));
        }
        return false;
    }

    $raw = (string) wp_remote_retrieve_body($response);
    if ($raw === '') {
        return false;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return false;
    }

    return isset($data['status']) && $data['status'] === 'valid';
}

