<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Motor de matrícula: se invoca desde el hook en `pcc-woootec-chile.php`.
 * Retorna `true` si no hubo fallas de matrícula; `false` si hubo errores.
 */
function pcc_enroll_user($order_id) {
    if (!function_exists('wc_get_order')) {
        return false;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }

    if ($order->get_meta('_pcc_moodle_enrollment_complete')) {
        return true;
    }

    $user_id = (int) $order->get_user_id();
    if ($user_id > 0) {
        $user = get_userdata($user_id);
    } else {
        $billing_email = (string) $order->get_billing_email();
        $user = $billing_email !== '' ? get_user_by('email', $billing_email) : false;
    }

    if (!($user instanceof WP_User)) {
        pcc_moodle_log('No se pudo determinar usuario WP para matrícula', array('order_id' => (int) $order_id));
        return false;
    }

    $course_ids = array();
    foreach ($order->get_items() as $item) {
        $product_id = (int) $item->get_product_id();
        if ($product_id <= 0) {
            continue;
        }

        $course_id = (int) get_post_meta($product_id, 'moodle_course_id', true);
        if ($course_id > 0) {
            $course_ids[$course_id] = true;
        }
    }

    $course_ids = array_keys($course_ids);
    if (empty($course_ids)) {
        $order->update_meta_data('_pcc_moodle_enrollment_complete', 1);
        $order->save();
        return true;
    }

    $moodle_user_id = (int) get_user_meta($user->ID, 'pcc_moodle_user_id', true);
    $created_password = null;

    if ($moodle_user_id <= 0) {
        $create_info = pcc_moodle_get_or_create_user_with_password($user);
        if (!$create_info || empty($create_info['id'])) {
            pcc_moodle_log('Falló creación/búsqueda de usuario Moodle', array('order_id' => (int) $order_id, 'user_id' => (int) $user->ID));
            return false;
        }

        $moodle_user_id = (int) $create_info['id'];
        update_user_meta($user->ID, 'pcc_moodle_user_id', $moodle_user_id);

        if (!empty($create_info['created']) && !empty($create_info['password'])) {
            $created_password = (string) $create_info['password'];
        }
    }

    $already = $order->get_meta('_pcc_moodle_enrolled_courses');
    if (!is_array($already)) {
        $already = array();
    }

    $enrolled = array();
    $failed = array();

    foreach ($course_ids as $course_id) {
        if (in_array($course_id, $already, true)) {
            continue;
        }

        $ok = pcc_moodle_enroll_user($moodle_user_id, $course_id);
        if ($ok) {
            $enrolled[] = $course_id;
            $already[] = $course_id;
        } else {
            $failed[] = $course_id;
        }
    }

    $order->update_meta_data('_pcc_moodle_enrolled_courses', array_values(array_unique($already)));
    $order->update_meta_data('_pcc_moodle_enrollment_last', array('enrolled' => $enrolled, 'failed' => $failed));

    if (empty($failed)) {
        $order->update_meta_data('_pcc_moodle_enrollment_complete', 1);
    }

    $order->save();

    if ($created_password !== null) {
        $ttl = (int) apply_filters('pcc_moodle_temp_password_ttl', HOUR_IN_SECONDS, $order_id, $user->ID);
        set_transient('pcc_moodle_pw_' . (int) $order_id, $created_password, $ttl);
    }

    $send_password = (bool) apply_filters('pcc_send_moodle_password_email', true, $order_id, $user->ID);
    pcc_send_moodle_access_email($user, $order, $send_password ? $created_password : null, $course_ids);

    if ($created_password !== null) {
        delete_transient('pcc_moodle_pw_' . (int) $order_id);
    }

    return empty($failed);
}

function pcc_send_moodle_access_email($user, $order, $moodle_password = null, $course_ids = array()) {
    if (!($user instanceof WP_User)) {
        return false;
    }

    $aula_url = trim((string) get_option('pcc_aula_url'));
    if ($aula_url === '') {
        $aula_url = pcc_get_moodle_url();
    }

    $subject = 'Acceso a tu curso';

    $lines = array();
    $lines[] = 'Hola ' . ($user->first_name ? $user->first_name : $user->display_name) . ',';
    $lines[] = '';
    $lines[] = 'Tu compra fue confirmada.';

    if (!empty($course_ids)) {
        $lines[] = '';
        $lines[] = 'Cursos activados: ' . implode(', ', array_map('intval', $course_ids));
    }

    if ($aula_url !== '') {
        $lines[] = '';
        $lines[] = 'Accede a tu Aula Virtual:';
        $lines[] = $aula_url;
    }

    $lines[] = '';
    $lines[] = 'Usuario: ' . $user->user_email;

    if ($moodle_password !== null && $moodle_password !== '') {
        $lines[] = 'Contraseña: ' . $moodle_password;
        $lines[] = '';
        $lines[] = 'Guarda esta contraseña. Luego puedes cambiarla en Moodle.';
    } else {
        $lines[] = '';
        $lines[] = 'Si ya tenías cuenta, usa tu contraseña habitual (o recupera contraseña desde Moodle).';
    }

    $lines[] = '';
    $lines[] = 'Saludos,';
    $lines[] = 'OTEC';

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    return wp_mail($user->user_email, $subject, implode("\n", $lines), $headers);
}
