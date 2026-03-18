<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_get_default_options() {
    return array(
        'pcc_moodle_url'                  => '',
        'pcc_moodle_token'                => '',
        'pcc_moodle_student_role'         => 5,
        'pcc_aula_url'                    => '',
        'pcc_debug_mode'                  => false,
        'pcc_default_image_id'            => 0,
        'pcc_default_price'               => '49000',
        'pcc_default_duration'            => '',
        'pcc_default_instructor'          => 'No asignado',
        'pcc_fallback_description'        => 'Curso sincronizado automaticamente desde Moodle.',
        'pcc_sso_enabled'                 => true,
        'pcc_sso_base_url'                => '',
        'pcc_github_repo'                 => '',
        'pcc_github_release_json'         => '',
        'pcc_github_auto_update'          => false,
        'pcc_last_sync'                   => array(),
    );
}

function pcc_register_default_options() {
    foreach (pcc_get_default_options() as $key => $value) {
        if (get_option($key, null) === null) {
            add_option($key, $value);
        }
    }
}

function pcc_register_settings() {
    $string_fields = array(
        'pcc_moodle_url'           => 'esc_url_raw',
        'pcc_moodle_token'         => 'sanitize_text_field',
        'pcc_aula_url'             => 'esc_url_raw',
        'pcc_default_price'        => 'sanitize_text_field',
        'pcc_default_duration'     => 'sanitize_text_field',
        'pcc_default_instructor'   => 'sanitize_text_field',
        'pcc_fallback_description' => 'sanitize_textarea_field',
        'pcc_sso_base_url'         => 'esc_url_raw',
        'pcc_github_repo'          => 'sanitize_text_field',
        'pcc_github_release_json'  => 'esc_url_raw',
    );

    foreach ($string_fields as $field => $sanitizer) {
        register_setting(
            'pcc_woootec_settings',
            $field,
            array(
                'type'              => 'string',
                'sanitize_callback' => $sanitizer,
                'default'           => pcc_get_default_options()[$field],
            )
        );
    }

    register_setting(
        'pcc_woootec_settings',
        'pcc_moodle_student_role',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 5,
        )
    );

    register_setting(
        'pcc_woootec_settings',
        'pcc_default_image_id',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        )
    );

    foreach (array('pcc_debug_mode', 'pcc_sso_enabled', 'pcc_github_auto_update') as $field) {
        register_setting(
            'pcc_woootec_settings',
            $field,
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'pcc_sanitize_checkbox',
                'default'           => pcc_get_default_options()[$field],
            )
        );
    }
}

function pcc_sanitize_checkbox($value) {
    return !empty($value);
}

function pcc_get_option($key, $default = null) {
    $defaults = pcc_get_default_options();
    if ($default === null && array_key_exists($key, $defaults)) {
        $default = $defaults[$key];
    }

    return get_option($key, $default);
}

function pcc_get_last_sync_state() {
    $state = get_option('pcc_last_sync', array());
    return is_array($state) ? $state : array();
}

function pcc_update_last_sync_state($state) {
    if (!is_array($state)) {
        return;
    }

    $payload = array(
        'timestamp'            => current_time('mysql'),
        'categories_created'   => isset($state['categories_created']) ? (int) $state['categories_created'] : 0,
        'categories_updated'   => isset($state['categories_updated']) ? (int) $state['categories_updated'] : 0,
        'products_created'     => isset($state['products_created']) ? (int) $state['products_created'] : 0,
        'products_updated'     => isset($state['products_updated']) ? (int) $state['products_updated'] : 0,
        'status'               => isset($state['status']) ? sanitize_key((string) $state['status']) : 'success',
        'message'              => isset($state['message']) ? sanitize_text_field((string) $state['message']) : '',
    );

    update_option('pcc_last_sync', $payload, false);
}

function pcc_get_default_image_id() {
    return (int) pcc_get_option('pcc_default_image_id', 0);
}

function pcc_get_default_price() {
    return (string) pcc_get_option('pcc_default_price', '49000');
}

function pcc_get_default_instructor() {
    return (string) pcc_get_option('pcc_default_instructor', 'No asignado');
}

function pcc_get_fallback_description() {
    return (string) pcc_get_option('pcc_fallback_description', 'Curso sincronizado automaticamente desde Moodle.');
}
