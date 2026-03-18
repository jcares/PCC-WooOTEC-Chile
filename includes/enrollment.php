<?php

if (!defined('ABSPATH')) {
    exit;
}

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

    $user = pcc_get_order_user($order);
    if (!($user instanceof WP_User)) {
        pcc_moodle_log('No se pudo determinar usuario WP para matricula', array('order_id' => (int) $order_id));
        return false;
    }

    $course_ids = pcc_get_order_moodle_course_ids($order);
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
            foreach ($course_ids as $course_id) {
                PCC_WooOTEC_Cron::enqueue_failed($order_id, $user->user_email, (int) $course_id, 'No se pudo crear/buscar el usuario en Moodle');
            }
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
            PCC_WooOTEC_Cron::enqueue_failed($order_id, $user->user_email, $course_id, 'Fallo matricula inicial');
        }
    }

    $already = array_values(array_unique(array_map('intval', $already)));
    $order->update_meta_data('_pcc_moodle_enrolled_courses', $already);
    $order->update_meta_data('_pcc_moodle_enrollment_last', array(
        'enrolled' => $enrolled,
        'failed'   => $failed,
    ));

    if (empty($failed)) {
        $order->update_meta_data('_pcc_moodle_enrollment_complete', 1);
    }

    $access_urls = pcc_build_order_access_urls($order, $user, $already);
    $order->save();

    $send_password = (bool) apply_filters('pcc_send_moodle_password_email', true, $order_id, $user->ID);
    pcc_send_moodle_access_email($user, $order, $send_password ? $created_password : null, $already, $access_urls);

    return empty($failed);
}

function pcc_get_order_user($order) {
    if (!($order instanceof WC_Order)) {
        return false;
    }

    $user_id = (int) $order->get_user_id();
    if ($user_id > 0) {
        $user = get_userdata($user_id);
        if ($user instanceof WP_User) {
            return $user;
        }
    }

    $billing_email = sanitize_email((string) $order->get_billing_email());
    if ($billing_email !== '') {
        $user = get_user_by('email', $billing_email);
        if ($user instanceof WP_User) {
            return $user;
        }
    }

    return false;
}

function pcc_get_order_moodle_course_ids($order) {
    if (!($order instanceof WC_Order)) {
        return array();
    }

    $course_ids = array();
    foreach ($order->get_items() as $item) {
        $product_id = (int) $item->get_product_id();
        if ($product_id <= 0) {
            continue;
        }

        $course_id = (int) get_post_meta($product_id, '_moodle_id', true);
        if ($course_id <= 0) {
            $course_id = (int) get_post_meta($product_id, 'moodle_course_id', true);
        }

        if ($course_id > 0) {
            $course_ids[$course_id] = $course_id;
        }
    }

    return array_values($course_ids);
}

function pcc_send_moodle_access_email($user, $order, $moodle_password = null, $course_ids = array(), $access_urls = array()) {
    if (!($user instanceof WP_User)) {
        return false;
    }

    $aula_url = trim((string) pcc_get_option('pcc_aula_url', ''));
    if ($aula_url === '') {
        $aula_url = pcc_get_moodle_url();
    }

    $subject = 'Acceso a tus cursos';
    $lines = array(
        'Hola ' . ($user->first_name ? $user->first_name : $user->display_name) . ',',
        '',
        'Tu compra fue confirmada y tus cursos fueron procesados.',
    );

    if (!empty($course_ids)) {
        $lines[] = '';
        $lines[] = 'Cursos Moodle activados: ' . implode(', ', array_map('intval', $course_ids));
    }

    if (!empty($access_urls)) {
        $lines[] = '';
        $lines[] = 'Accesos directos:';
        foreach ($access_urls as $course_id => $url) {
            $lines[] = 'Curso #' . (int) $course_id . ': ' . $url;
        }
    } elseif ($aula_url !== '') {
        $lines[] = '';
        $lines[] = 'Aula virtual: ' . $aula_url;
    }

    $lines[] = '';
    $lines[] = 'Usuario: ' . $user->user_email;

    if (!empty($moodle_password)) {
        $lines[] = 'Contrasena temporal: ' . $moodle_password;
    }

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    return wp_mail($user->user_email, $subject, implode("\n", $lines), $headers);
}
