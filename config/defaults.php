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
    'github_repo'            => 'jcares/PCC-WooOTEC-Chile',
    'github_release_url'     => 'https://github.com/jcares/PCC-WooOTEC-Chile/blob/main/release.json',
    'auto_update'            => 'no',
    'last_sync'              => array(),
    'redirect_after_purchase'=> 'no',
    'debug_enabled'          => 'no',
    'email_enabled'          => 'yes',
    'email_subject'          => 'Acceso a tus cursos en {{sitio}}',
    'email_template'         => '<p>Hola {{nombre}},</p><p>Tu compra fue confirmada correctamente.</p><p><strong>Usuario:</strong> {{email}}</p><p><strong>Contrasena:</strong> {{password}}</p><p><strong>Cursos:</strong><br>{{cursos}}</p><p><a href="{{url_acceso}}">Acceder al curso</a></p><p>Saludos,<br>{{sitio}}</p>',
    'email_test_recipient'   => '',
    'retry_limit'            => 3,
);
