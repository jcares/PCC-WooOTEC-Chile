<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
----------------------------------------------------
SINCRONIZACIÓN DE CURSOS MOODLE → WOOCOMMERCE
----------------------------------------------------
*/

function pcc_run_course_sync() {
    echo "<div class='notice notice-info'><p>Iniciando sincronización de cursos...</p></div>";

    if (!function_exists('pcc_moodle_request')) {
        echo "<div class='notice notice-error'><p>Error: función de conexión Moodle no encontrada.</p></div>";
        return;
    }

    $courses = pcc_moodle_request('core_course_get_courses');

    if (!$courses || !is_array($courses)) {
        echo "<div class='notice notice-error'><p>No se pudieron obtener cursos desde Moodle.</p></div>";
        return;
    }

    $created = 0;
    $updated = 0;

    foreach ($courses as $course) {
        if (empty($course->id)) {
            continue;
        }

        // Evitar cursos del sistema.
        if ((int) $course->id < 2) {
            continue;
        }

        $product_id = pcc_get_product_by_course((int) $course->id);

        if (!$product_id) {
            $product_id = pcc_create_course_product($course);
            if ($product_id) {
                $created++;
            }
        } else {
            pcc_update_course_product($product_id, $course);
            $updated++;
        }

        if ($product_id) {
            pcc_sync_course_image($product_id, $course);
        }
    }

    echo "<div class='notice notice-success'>";
    echo "<p>Sincronización finalizada</p>";
    echo "<p>Productos creados: " . (int) $created . "</p>";
    echo "<p>Productos actualizados: " . (int) $updated . "</p>";
    echo "</div>";
}

/*
----------------------------------------------------
CATEGORÍA WC PARA CURSOS (autocreación)
----------------------------------------------------
*/

function pcc_get_or_create_wc_course_category_id() {
    if (!function_exists('taxonomy_exists') || !taxonomy_exists('product_cat')) {
        return 0;
    }

    $category_id = (int) get_option('pcc_wc_course_category', 0);
    if ($category_id > 0) {
        $term = get_term($category_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            return $category_id;
        }
    }

    $name = (string) apply_filters('pcc_default_course_category_name', 'Cursos Moodle');
    $name = $name !== '' ? $name : 'Cursos Moodle';

    $slug = sanitize_title($name);
    $existing = get_term_by('slug', $slug, 'product_cat');
    if ($existing && !is_wp_error($existing)) {
        update_option('pcc_wc_course_category', (int) $existing->term_id);
        return (int) $existing->term_id;
    }

    $created = wp_insert_term($name, 'product_cat', array('slug' => $slug));
    if (is_wp_error($created)) {
        if (function_exists('pcc_log')) {
            pcc_log('No se pudo crear categoría de cursos', array('error' => $created->get_error_message()));
        }
        return 0;
    }

    $new_id = isset($created['term_id']) ? (int) $created['term_id'] : 0;
    if ($new_id > 0) {
        update_option('pcc_wc_course_category', $new_id);
    }

    return $new_id;
}

/*
----------------------------------------------------
CREAR PRODUCTO WOOCOMMERCE
----------------------------------------------------
*/

function pcc_create_course_product($course) {
    if (!class_exists('WC_Product_Simple')) {
        echo '<p>WooCommerce no está activo.</p>';
        return false;
    }

    $product = new WC_Product_Simple();

    $product->set_name(isset($course->fullname) ? (string) $course->fullname : 'Curso Moodle');

    if (!empty($course->summary)) {
        $product->set_description((string) $course->summary);
        $product->set_short_description(wp_trim_words(wp_strip_all_tags((string) $course->summary), 35));
    }

    $visible = isset($course->visible) ? (int) $course->visible : 1;
    $product->set_status($visible ? 'publish' : 'draft');

    $product->set_catalog_visibility('visible');
    $product->set_virtual(true);
    $product->set_downloadable(false);

    if (!empty($course->shortname) && is_string($course->shortname)) {
        $product->set_sku((string) $course->shortname);
    }

    $default_price = apply_filters('pcc_default_course_price', '49000', $course);
    $product->set_regular_price((string) $default_price);

    $category_id = pcc_get_or_create_wc_course_category_id();
    if ($category_id > 0) {
        $product->set_category_ids(array($category_id));
    }

    $product_id = $product->save();

    if ($product_id) {
        update_post_meta($product_id, 'moodle_course_id', (int) $course->id);
        update_post_meta($product_id, 'moodle_shortname', isset($course->shortname) ? (string) $course->shortname : '');
        update_post_meta($product_id, 'moodle_category_id', isset($course->categoryid) ? (int) $course->categoryid : 0);
        update_post_meta($product_id, 'course_start', isset($course->startdate) ? (int) $course->startdate : 0);
        update_post_meta($product_id, 'course_end', isset($course->enddate) ? (int) $course->enddate : 0);
        update_post_meta($product_id, '_pcc_synced', 1);

        echo '<p>Curso creado: ' . esc_html((string) $product->get_name()) . '</p>';
    }

    return $product_id;
}

/*
----------------------------------------------------
ACTUALIZAR PRODUCTO EXISTENTE
----------------------------------------------------
*/

function pcc_update_course_product($product_id, $course) {
    $product = wc_get_product($product_id);
    if (!$product) {
        return;
    }

    if (!empty($course->fullname)) {
        $product->set_name((string) $course->fullname);
    }

    if (!empty($course->summary)) {
        $product->set_description((string) $course->summary);
        $product->set_short_description(wp_trim_words(wp_strip_all_tags((string) $course->summary), 35));
    }

    $visible = isset($course->visible) ? (int) $course->visible : 1;
    $product->set_status($visible ? 'publish' : 'draft');

    if (!empty($course->shortname) && is_string($course->shortname) && !$product->get_sku()) {
        $product->set_sku((string) $course->shortname);
    }

    $category_id = pcc_get_or_create_wc_course_category_id();
    if ($category_id > 0) {
        $product->set_category_ids(array($category_id));
    }

    $product->save();

    update_post_meta($product_id, 'moodle_shortname', isset($course->shortname) ? (string) $course->shortname : '');
    update_post_meta($product_id, 'moodle_category_id', isset($course->categoryid) ? (int) $course->categoryid : 0);
    update_post_meta($product_id, 'course_start', isset($course->startdate) ? (int) $course->startdate : 0);
    update_post_meta($product_id, 'course_end', isset($course->enddate) ? (int) $course->enddate : 0);

    echo '<p>Curso actualizado: ' . esc_html((string) $product->get_name()) . '</p>';
}

/*
----------------------------------------------------
BUSCAR PRODUCTO POR ID MOODLE
----------------------------------------------------
*/

function pcc_get_product_by_course($course_id) {
    $args = array(
        'post_type'      => 'product',
        'post_status'    => array('publish', 'draft', 'private'),
        'meta_query'     => array(
            array(
                'key'     => 'moodle_course_id',
                'value'   => (int) $course_id,
                'compare' => '=',
            ),
        ),
        'posts_per_page' => 1,
        'fields'         => 'ids',
    );

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        return (int) $query->posts[0];
    }

    return false;
}

/*
----------------------------------------------------
IMAGEN DESTACADA DESDE MOODLE (overviewfiles)
----------------------------------------------------
*/

function pcc_sync_course_image($product_id, $course) {
    $product_id = (int) $product_id;
    if ($product_id <= 0) {
        return;
    }

    if (has_post_thumbnail($product_id)) {
        return;
    }

    if (!isset($course->overviewfiles) || !is_array($course->overviewfiles) || empty($course->overviewfiles)) {
        return;
    }

    $file = null;
    foreach ($course->overviewfiles as $f) {
        if (!is_object($f) || empty($f->fileurl)) {
            continue;
        }

        $mimetype = isset($f->mimetype) ? (string) $f->mimetype : '';
        if ($mimetype !== '' && strpos($mimetype, 'image/') === 0) {
            $file = $f;
            break;
        }

        $url_path = (string) parse_url((string) $f->fileurl, PHP_URL_PATH);
        $ext = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
        if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
            $file = $f;
            break;
        }
    }

    if (!$file) {
        return;
    }

    $token = function_exists('pcc_get_moodle_token') ? pcc_get_moodle_token() : (string) get_option('pcc_moodle_token');
    if ($token === '') {
        return;
    }

    $image_url = add_query_arg('token', $token, (string) $file->fileurl);

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($image_url, 30);
    if (is_wp_error($tmp)) {
        if (function_exists('pcc_log')) {
            pcc_log('No se pudo descargar imagen de Moodle', array('product_id' => $product_id, 'error' => $tmp->get_error_message()));
        }
        return;
    }

    $name = wp_basename((string) parse_url($image_url, PHP_URL_PATH));
    if ($name === '') {
        $name = 'moodle-course-image.jpg';
    }

    $file_array = array(
        'name'     => $name,
        'tmp_name' => $tmp,
    );

    $attachment_id = media_handle_sideload($file_array, $product_id);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        if (function_exists('pcc_log')) {
            pcc_log('No se pudo adjuntar imagen al producto', array('product_id' => $product_id, 'error' => $attachment_id->get_error_message()));
        }
        return;
    }

    set_post_thumbnail($product_id, (int) $attachment_id);
}

// Back-compat (si alguna parte vieja llamaba esta función).
function pcc_set_course_image($product_id, $image_url) {
    if (empty($image_url)) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $image_id = media_sideload_image($image_url, (int) $product_id, null, 'id');
    if (!is_wp_error($image_id)) {
        set_post_thumbnail((int) $product_id, (int) $image_id);
    }
}

