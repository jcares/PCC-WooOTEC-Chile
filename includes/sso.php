<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_is_sso_enabled() {
    return (bool) pcc_get_option('pcc_sso_enabled', true);
}

function pcc_get_sso_base_url() {
    $url = trim((string) pcc_get_option('pcc_sso_base_url', ''));
    if ($url === '') {
        $url = pcc_get_moodle_url();
    }

    return rtrim($url, '/');
}

function pcc_build_moodle_access_url($email, $course_id = 0) {
    $email = sanitize_email((string) $email);
    $base_url = pcc_get_sso_base_url();

    if ($email === '' || $base_url === '' || !pcc_is_sso_enabled()) {
        return '';
    }

    $url = $base_url . '/auth/token/login.php';
    $args = array('user' => $email);

    if ((int) $course_id > 0) {
        $args['courseid'] = (int) $course_id;
    }

    return add_query_arg($args, $url);
}

function pcc_build_order_access_urls($order, $user, $course_ids) {
    if (!($user instanceof WP_User) || !is_array($course_ids)) {
        return array();
    }

    $urls = array();
    foreach ($course_ids as $course_id) {
        $access_url = pcc_build_moodle_access_url($user->user_email, (int) $course_id);
        if ($access_url !== '') {
            $urls[(int) $course_id] = esc_url_raw($access_url);
        }
    }

    if ($order instanceof WC_Order) {
        $primary_url = '';
        if (!empty($urls)) {
            $primary_url = (string) reset($urls);
        } elseif (pcc_is_sso_enabled()) {
            $primary_url = pcc_build_moodle_access_url($user->user_email);
        }

        $order->update_meta_data('_moodle_access_url', $primary_url);
        $order->update_meta_data('_pcc_moodle_access_urls', $urls);
    }

    return $urls;
}

function pcc_register_shortcodes() {
    add_shortcode('pcc_my_courses', 'pcc_render_my_courses_shortcode');
}

function pcc_render_my_courses_shortcode() {
    if (!is_user_logged_in() || !function_exists('wc_get_orders')) {
        return '<p>Debes iniciar sesion para ver tus cursos.</p>';
    }

    $user = wp_get_current_user();
    $orders = wc_get_orders(array(
        'customer_id' => $user->ID,
        'status'      => array('completed'),
        'limit'       => -1,
    ));

    if (empty($orders)) {
        return '<p>No tienes cursos comprados todavia.</p>';
    }

    $items = array();
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();
            if ($product_id <= 0) {
                continue;
            }

            $course_id = (int) get_post_meta($product_id, 'moodle_course_id', true);
            if ($course_id <= 0) {
                continue;
            }

            $access_url = pcc_build_moodle_access_url($user->user_email, $course_id);
            $items[] = sprintf(
                '<li>%s %s</li>',
                esc_html($item->get_name()),
                $access_url !== '' ? '<a class="button" href="' . esc_url($access_url) . '">Acceder al curso</a>' : ''
            );
        }
    }

    if (empty($items)) {
        return '<p>No se encontraron cursos Moodle asociados a tus compras.</p>';
    }

    return '<ul class="pcc-my-courses">' . implode('', $items) . '</ul>';
}
