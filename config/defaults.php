<?php

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'moodle_url'             => '',
    'moodle_token'           => '',
    'student_role_id'        => 5,
    'default_price'          => '49000',
    'default_instructor'     => 'No asignado',
    'fallback_description'   => 'Curso sincronizado automaticamente desde Moodle.',
    'default_image_id'       => 0,
    'sso_enabled'            => 'yes',
    'sso_base_url'           => '',
    'github_repo'            => '',
    'github_release_url'     => '',
    'auto_update'            => 'no',
    'last_sync'              => array(),
    'redirect_after_purchase'=> 'no',
    'debug_enabled'          => 'no',
);
