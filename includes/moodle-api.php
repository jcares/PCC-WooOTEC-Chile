<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
========================================
CONFIGURACIÓN BASE MOODLE
========================================
*/

function pcc_get_moodle_url() {
    $url = trim((string) get_option('pcc_moodle_url'));
    if ($url === '') {
        return '';
    }
    return rtrim($url, '/');
}

function pcc_get_moodle_token() {
    return trim((string) get_option('pcc_moodle_token'));
}

function pcc_moodle_get_student_role_id() {
    $roleid = (int) get_option('pcc_moodle_student_role');
    return $roleid > 0 ? $roleid : 5;
}

function pcc_moodle_log($message, $context = array()) {
    if (!empty($context) && is_array($context)) {
        if (isset($context['wstoken'])) {
            $context['wstoken'] = '***';
        }
        $message .= ' | ' . wp_json_encode($context);
    }

    if (function_exists('pcc_log')) {
        pcc_log($message);
        return;
    }

    error_log('[PCC-WooOTEC] ' . $message);
}

/*
========================================
FUNCIÓN PRINCIPAL API MOODLE
========================================
*/

function pcc_moodle_request_raw($function, $params = array(), $request_args = array()) {
    $moodle_url = pcc_get_moodle_url();
    $token      = pcc_get_moodle_token();

    if ($moodle_url === '' || $token === '') {
        return new WP_Error('pcc_moodle_config_missing', 'Moodle URL o Token no configurado');
    }

    $function = trim((string) $function);
    if ($function === '') {
        return new WP_Error('pcc_moodle_invalid_function', 'wsfunction inválida');
    }

    $endpoint = $moodle_url . '/webservice/rest/server.php';

    $body = array_merge(
        array(
            'wstoken'            => $token,
            'wsfunction'         => $function,
            'moodlewsrestformat' => 'json',
        ),
        is_array($params) ? $params : array()
    );

    $args = array_merge(
        array(
            'timeout' => (int) apply_filters('pcc_moodle_request_timeout', 20, $function, $params),
            'body'    => $body,
        ),
        is_array($request_args) ? $request_args : array()
    );

    $response = wp_remote_post($endpoint, $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $raw_body    = (string) wp_remote_retrieve_body($response);

    if ($status_code < 200 || $status_code >= 300) {
        return new WP_Error(
            'pcc_moodle_http_error',
            'Respuesta HTTP inválida desde Moodle',
            array(
                'status' => $status_code,
                'body'   => substr($raw_body, 0, 1000),
            )
        );
    }

    if ($raw_body === '') {
        return new WP_Error('pcc_moodle_empty_response', 'Respuesta vacía desde Moodle');
    }

    $data = json_decode($raw_body);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error(
            'pcc_moodle_invalid_json',
            'JSON inválido desde Moodle: ' . json_last_error_msg(),
            array('body' => substr($raw_body, 0, 1000))
        );
    }

    if (is_object($data) && (isset($data->exception) || isset($data->errorcode))) {
        return new WP_Error(
            'pcc_moodle_api_exception',
            isset($data->message) ? (string) $data->message : 'Error Moodle API',
            array(
                'exception' => isset($data->exception) ? (string) $data->exception : null,
                'errorcode' => isset($data->errorcode) ? (string) $data->errorcode : null,
                'debuginfo' => isset($data->debuginfo) ? (string) $data->debuginfo : null,
            )
        );
    }

    return $data;
}

/*
 * Back-compat: retorna `false` en error (NO WP_Error).
 */
function pcc_moodle_request($function, $params = array(), $request_args = array()) {
    $result = pcc_moodle_request_raw($function, $params, $request_args);
    if (is_wp_error($result)) {
        pcc_moodle_log('Error Moodle request', array('wsfunction' => $function, 'error' => $result->get_error_message()));
        return false;
    }
    return $result;
}

/*
========================================
OBTENER CURSOS MOODLE
========================================
*/

function pcc_moodle_get_courses() {
    $courses = pcc_moodle_request('core_course_get_courses');
    return is_array($courses) ? $courses : array();
}

/*
========================================
CREAR USUARIO EN MOODLE
========================================
*/

function pcc_moodle_create_user($user) {
    if ($user instanceof WP_User) {
        $user = array(
            'username'  => $user->user_email,
            'password'  => null,
            'firstname' => (string) $user->first_name,
            'lastname'  => (string) $user->last_name,
            'email'     => (string) $user->user_email,
        );
    }

    if (!is_array($user)) {
        pcc_moodle_log('Usuario inválido para crear en Moodle');
        return false;
    }

    $required = array('username', 'password', 'firstname', 'lastname', 'email');
    foreach ($required as $key) {
        if (empty($user[$key]) || !is_string($user[$key])) {
            pcc_moodle_log('Faltan datos para crear usuario Moodle', array('missing' => $key));
            return false;
        }
    }

    $result = pcc_moodle_request(
        'core_user_create_users',
        array(
            'users' => array(
                array(
                    'username'  => $user['username'],
                    'password'  => $user['password'],
                    'firstname' => $user['firstname'],
                    'lastname'  => $user['lastname'],
                    'email'     => $user['email'],
                ),
            ),
        )
    );

    if (!$result || !is_array($result) || empty($result[0]->id)) {
        return false;
    }

    return (int) $result[0]->id;
}

/*
========================================
BUSCAR USUARIO MOODLE
========================================
*/

function pcc_moodle_get_user($email) {
    $email = trim((string) $email);
    if ($email === '') {
        return false;
    }

    $result = pcc_moodle_request(
        'core_user_get_users',
        array(
            'criteria' => array(
                array(
                    'key'   => 'email',
                    'value' => $email,
                ),
            ),
        )
    );

    if (!$result || empty($result->users) || !is_array($result->users) || empty($result->users[0]->id)) {
        return false;
    }

    return (int) $result->users[0]->id;
}

/*
========================================
MATRICULAR USUARIO EN CURSO
========================================
*/

function pcc_moodle_enroll_user($userid, $courseid, $roleid = null) {
    $userid   = (int) $userid;
    $courseid = (int) $courseid;
    $roleid   = $roleid === null ? pcc_moodle_get_student_role_id() : (int) $roleid;

    if ($userid <= 0 || $courseid <= 0 || $roleid <= 0) {
        pcc_moodle_log('Parámetros inválidos para matrícula Moodle', array('userid' => $userid, 'courseid' => $courseid, 'roleid' => $roleid));
        return false;
    }

    $result = pcc_moodle_request(
        'enrol_manual_enrol_users',
        array(
            'enrolments' => array(
                array(
                    'roleid'   => $roleid,
                    'userid'   => $userid,
                    'courseid' => $courseid,
                ),
            ),
        )
    );

    return $result === false ? false : true;
}

// Alias histórico (enrol vs enroll).
function pcc_moodle_enrol_user($userid, $courseid, $roleid = null) {
    return pcc_moodle_enroll_user($userid, $courseid, $roleid);
}

/*
========================================
OBTENER CURSOS DE USUARIO
========================================
*/

function pcc_moodle_get_user_courses($userid) {
    $userid = (int) $userid;
    if ($userid <= 0) {
        return array();
    }

    $courses = pcc_moodle_request('core_enrol_get_users_courses', array('userid' => $userid));
    return is_array($courses) ? $courses : array();
}

/*
========================================
TEST DE CONEXIÓN
========================================
*/

function pcc_moodle_test_connection() {
    $result = pcc_moodle_request('core_webservice_get_site_info');
    return $result !== false;
}

/*
========================================
BUSCAR O CREAR USUARIO EN MOODLE (LEGACY)
========================================
*/

function pcc_moodle_get_or_create_user($user){
    if (!($user instanceof WP_User)) {
        return false;
    }

    $existing_id = pcc_moodle_get_user($user->user_email);
    if ($existing_id) {
        return $existing_id;
    }

    $password = wp_generate_password(14, true, true);

    $created_id = pcc_moodle_create_user(
        array(
            'username'  => (string) $user->user_email,
            'password'  => $password,
            'firstname' => (string) $user->first_name,
            'lastname'  => (string) $user->last_name,
            'email'     => (string) $user->user_email,
        )
    );

    return $created_id ? $created_id : false;
}

/*
========================================
NUEVO: OBTENER/CREAR + PASSWORD
========================================
*/

function pcc_moodle_get_or_create_user_with_password($user) {
    if (!($user instanceof WP_User)) {
        return false;
    }

    $existing_id = pcc_moodle_get_user($user->user_email);
    if ($existing_id) {
        return array(
            'id'       => $existing_id,
            'created'  => false,
            'password' => null,
        );
    }

    $password = wp_generate_password(14, true, true);

    $created_id = pcc_moodle_create_user(
        array(
            'username'  => (string) $user->user_email,
            'password'  => $password,
            'firstname' => (string) $user->first_name,
            'lastname'  => (string) $user->last_name,
            'email'     => (string) $user->user_email,
        )
    );

    if (!$created_id) {
        return false;
    }

    return array(
        'id'       => $created_id,
        'created'  => true,
        'password' => $password,
    );
}
