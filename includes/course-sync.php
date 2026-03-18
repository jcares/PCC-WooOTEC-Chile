<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_run_course_sync() {
    $result = pcc_perform_course_sync(true);

    if ($result['status'] !== 'success') {
        echo "<div class='notice notice-error'><p>" . esc_html($result['message']) . "</p></div>";
        return;
    }

    echo "<div class='notice notice-success'>";
    echo "<p>Sincronizacion finalizada.</p>";
    echo "<p>Categorias creadas: " . (int) $result['categories_created'] . "</p>";
    echo "<p>Categorias actualizadas: " . (int) $result['categories_updated'] . "</p>";
    echo "<p>Productos creados: " . (int) $result['products_created'] . "</p>";
    echo "<p>Productos actualizados: " . (int) $result['products_updated'] . "</p>";
    echo "</div>";
}

function pcc_run_scheduled_sync() {
    $result = pcc_perform_course_sync(false);
    if (!empty($result['message'])) {
        PCC_Logger::log('Sincronizacion programada', $result['status'] === 'success' ? 'info' : 'error', $result, true);
    }
}

function pcc_perform_course_sync($echo_messages = false) {
    $response = array(
        'status'              => 'error',
        'message'             => '',
        'categories_created'  => 0,
        'categories_updated'  => 0,
        'products_created'    => 0,
        'products_updated'    => 0,
    );

    if (!function_exists('pcc_moodle_request')) {
        $response['message'] = 'No se encontro el modulo de conexion Moodle.';
        pcc_update_last_sync_state($response);
        return $response;
    }

    if ($echo_messages) {
        echo "<div class='notice notice-info'><p>Etapa 1/2: sincronizando categorias...</p></div>";
    }

    $categories = function_exists('pcc_moodle_get_categories') ? pcc_moodle_get_categories() : array();
    $category_result = pcc_sync_moodle_categories($categories, $echo_messages);

    if ($echo_messages) {
        echo "<div class='notice notice-info'><p>Etapa 2/2: sincronizando cursos...</p></div>";
    }

    $courses = function_exists('pcc_moodle_get_courses') ? pcc_moodle_get_courses() : array();
    if (empty($courses) || !is_array($courses)) {
        $response['message'] = 'No se pudieron obtener cursos desde Moodle.';
        $response['categories_created'] = (int) $category_result['created'];
        $response['categories_updated'] = (int) $category_result['updated'];
        pcc_update_last_sync_state($response);
        return $response;
    }

    $product_result = pcc_sync_moodle_courses($courses, $echo_messages);

    $response['status'] = 'success';
    $response['message'] = 'Sincronizacion ejecutada correctamente.';
    $response['categories_created'] = (int) $category_result['created'];
    $response['categories_updated'] = (int) $category_result['updated'];
    $response['products_created'] = (int) $product_result['created'];
    $response['products_updated'] = (int) $product_result['updated'];

    pcc_update_last_sync_state($response);
    return $response;
}

function pcc_sync_moodle_categories($categories, $echo_messages = false) {
    $result = array(
        'created' => 0,
        'updated' => 0,
    );

    if (!taxonomy_exists('product_cat') || empty($categories) || !is_array($categories)) {
        return $result;
    }

    $pending = array();
    foreach ($categories as $category) {
        if (!is_object($category) || empty($category->id) || empty($category->name)) {
            continue;
        }

        $pending[(int) $category->id] = $category;
    }

    $safety = 0;
    while (!empty($pending) && $safety < 10) {
        $processed = 0;

        foreach ($pending as $moodle_category_id => $category) {
            $parent_moodle_id = isset($category->parent) ? (int) $category->parent : 0;
            $parent_term_id = 0;

            if ($parent_moodle_id > 0) {
                $parent_term = pcc_get_category_by_moodle_id($parent_moodle_id);
                if (!$parent_term) {
                    continue;
                }
                $parent_term_id = (int) $parent_term->term_id;
            }

            $term = pcc_upsert_moodle_category($category, $parent_term_id, $echo_messages);
            if (!$term) {
                continue;
            }

            if (!empty($term['created'])) {
                $result['created']++;
            } else {
                $result['updated']++;
            }

            unset($pending[$moodle_category_id]);
            $processed++;
        }

        if ($processed === 0) {
            foreach ($pending as $moodle_category_id => $category) {
                $term = pcc_upsert_moodle_category($category, 0, $echo_messages);
                if (!$term) {
                    continue;
                }

                if (!empty($term['created'])) {
                    $result['created']++;
                } else {
                    $result['updated']++;
                }

                unset($pending[$moodle_category_id]);
            }
            break;
        }

        $safety++;
    }

    return $result;
}

function pcc_upsert_moodle_category($category, $parent_term_id = 0, $echo_messages = false) {
    $moodle_category_id = isset($category->id) ? (int) $category->id : 0;
    $name = isset($category->name) ? sanitize_text_field((string) $category->name) : '';

    if ($moodle_category_id <= 0 || $name === '') {
        return false;
    }

    $existing = pcc_get_category_by_moodle_id($moodle_category_id);
    $slug_source = !empty($category->idnumber) ? (string) $category->idnumber : 'moodle-cat-' . $moodle_category_id . '-' . $name;

    if ($existing) {
        $updated = wp_update_term($existing->term_id, 'product_cat', array(
            'name'   => $name,
            'parent' => max(0, (int) $parent_term_id),
            'slug'   => sanitize_title($slug_source),
        ));

        if (is_wp_error($updated)) {
            PCC_Logger::error('No se pudo actualizar categoria Moodle', array('moodle_id' => $moodle_category_id, 'error' => $updated->get_error_message()));
            return false;
        }

        $term_id = (int) $existing->term_id;
        $created = false;
    } else {
        $created_term = wp_insert_term($name, 'product_cat', array(
            'parent' => max(0, (int) $parent_term_id),
            'slug'   => sanitize_title($slug_source),
        ));

        if (is_wp_error($created_term) || empty($created_term['term_id'])) {
            PCC_Logger::error('No se pudo crear categoria Moodle', array('moodle_id' => $moodle_category_id));
            return false;
        }

        $term_id = (int) $created_term['term_id'];
        $created = true;
    }

    update_term_meta($term_id, 'moodle_category_id', $moodle_category_id);
    update_term_meta($term_id, 'moodle_id', $moodle_category_id);
    update_term_meta($term_id, 'moodle_parent_id', isset($category->parent) ? (int) $category->parent : 0);

    if ($echo_messages) {
        echo '<p>Categoria ' . ($created ? 'creada' : 'actualizada') . ': ' . esc_html($name) . '</p>';
    }

    return array(
        'term_id' => $term_id,
        'created' => $created,
    );
}

function pcc_get_category_by_moodle_id($moodle_category_id) {
    $terms = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'number'     => 1,
        'meta_query' => array(
            array(
                'key'     => 'moodle_category_id',
                'value'   => (int) $moodle_category_id,
                'compare' => '=',
            ),
        ),
    ));

    if (is_wp_error($terms) || empty($terms)) {
        return false;
    }

    return $terms[0];
}

function pcc_sync_moodle_courses($courses, $echo_messages = false) {
    $result = array(
        'created' => 0,
        'updated' => 0,
    );

    foreach ($courses as $course) {
        if (!is_object($course) || empty($course->id) || (int) $course->id < 2) {
            continue;
        }

        $product_id = pcc_get_product_by_course((int) $course->id);
        if ($product_id) {
            pcc_update_course_product($product_id, $course, $echo_messages);
            $result['updated']++;
        } else {
            $product_id = pcc_create_course_product($course, $echo_messages);
            if ($product_id) {
                $result['created']++;
            }
        }

        if ($product_id) {
            pcc_sync_course_image($product_id, $course);
        }
    }

    return $result;
}

function pcc_create_course_product($course, $echo_messages = false) {
    if (!class_exists('WC_Product_Simple')) {
        return false;
    }

    $product = new WC_Product_Simple();
    $product->set_name(isset($course->fullname) ? (string) $course->fullname : 'Curso Moodle');
    $product->set_slug('moodle-course-' . (int) $course->id);
    $product->set_description(pcc_get_course_description($course));
    $product->set_short_description(wp_trim_words(wp_strip_all_tags(pcc_get_course_description($course)), 35));
    $product->set_status(!empty($course->visible) ? 'publish' : 'draft');
    $product->set_catalog_visibility('visible');
    $product->set_virtual(true);
    $product->set_downloadable(false);
    $product->set_sku(pcc_get_course_sku($course));
    $product->set_regular_price((string) pcc_get_default_price());
    $product->set_category_ids(pcc_get_course_category_ids($course));

    $product_id = $product->save();
    if ($product_id) {
        pcc_update_course_product_meta($product_id, $course);
        if ($echo_messages) {
            echo '<p>Curso creado: ' . esc_html((string) $product->get_name()) . '</p>';
        }
    }

    return $product_id;
}

function pcc_update_course_product($product_id, $course, $echo_messages = false) {
    $product = wc_get_product($product_id);
    if (!$product) {
        return false;
    }

    $product->set_name(isset($course->fullname) ? (string) $course->fullname : $product->get_name());
    $product->set_description(pcc_get_course_description($course));
    $product->set_short_description(wp_trim_words(wp_strip_all_tags(pcc_get_course_description($course)), 35));
    $product->set_status(!empty($course->visible) ? 'publish' : 'draft');
    $product->set_sku(pcc_get_course_sku($course));
    $product->set_category_ids(pcc_get_course_category_ids($course));

    if ($product->get_regular_price() === '') {
        $product->set_regular_price((string) pcc_get_default_price());
    }

    $product->save();
    pcc_update_course_product_meta($product_id, $course);

    if ($echo_messages) {
        echo '<p>Curso actualizado: ' . esc_html((string) $product->get_name()) . '</p>';
    }

    return true;
}

function pcc_get_course_description($course) {
    if (!empty($course->summary)) {
        return (string) $course->summary;
    }

    return pcc_get_fallback_description();
}

function pcc_get_course_sku($course) {
    return 'MOODLE-' . (int) $course->id;
}

function pcc_get_course_category_ids($course) {
    $moodle_category_id = isset($course->categoryid) ? (int) $course->categoryid : 0;
    if ($moodle_category_id <= 0) {
        return array();
    }

    $term = pcc_get_category_by_moodle_id($moodle_category_id);
    if (!$term) {
        return array();
    }

    return array((int) $term->term_id);
}

function pcc_update_course_product_meta($product_id, $course) {
    $course_id = isset($course->id) ? (int) $course->id : 0;
    $teacher = function_exists('pcc_moodle_get_course_teacher') ? pcc_moodle_get_course_teacher($course_id) : '';
    if ($teacher === '') {
        $teacher = pcc_get_default_instructor();
    }

    update_post_meta($product_id, 'moodle_course_id', $course_id);
    update_post_meta($product_id, '_moodle_id', $course_id);
    update_post_meta($product_id, 'moodle_shortname', isset($course->shortname) ? (string) $course->shortname : '');
    update_post_meta($product_id, 'moodle_category_id', isset($course->categoryid) ? (int) $course->categoryid : 0);
    update_post_meta($product_id, 'course_start', isset($course->startdate) ? (int) $course->startdate : 0);
    update_post_meta($product_id, 'course_end', isset($course->enddate) ? (int) $course->enddate : 0);
    update_post_meta($product_id, '_start_date', isset($course->startdate) ? (int) $course->startdate : 0);
    update_post_meta($product_id, '_end_date', isset($course->enddate) ? (int) $course->enddate : 0);
    update_post_meta($product_id, '_instructor', $teacher);
    update_post_meta($product_id, '_duration', (string) pcc_get_option('pcc_default_duration', ''));
    update_post_meta($product_id, '_pcc_synced', 1);
}

function pcc_get_product_by_course($course_id) {
    $query = new WP_Query(array(
        'post_type'      => 'product',
        'post_status'    => array('publish', 'draft', 'private'),
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => 'moodle_course_id',
                'value'   => (int) $course_id,
                'compare' => '=',
            ),
        ),
    ));

    if ($query->have_posts()) {
        return (int) $query->posts[0];
    }

    return false;
}

function pcc_sync_course_image($product_id, $course) {
    $product_id = (int) $product_id;
    if ($product_id <= 0) {
        return;
    }

    if (has_post_thumbnail($product_id)) {
        return;
    }

    $downloaded = false;
    if (isset($course->overviewfiles) && is_array($course->overviewfiles) && !empty($course->overviewfiles)) {
        foreach ($course->overviewfiles as $file) {
            if (!is_object($file) || empty($file->fileurl)) {
                continue;
            }

            $downloaded = pcc_attach_remote_image_to_product($product_id, (string) $file->fileurl);
            if ($downloaded) {
                break;
            }
        }
    }

    if (!$downloaded && !empty($course->summary)) {
        $summary_image = pcc_extract_image_from_course_summary((string) $course->summary);
        if ($summary_image !== '') {
            $downloaded = pcc_attach_remote_image_to_product($product_id, $summary_image);
        }
    }

    if (!$downloaded) {
        pcc_apply_default_course_image($product_id);
    }
}

function pcc_extract_image_from_course_summary($summary_html) {
    if (!is_string($summary_html) || $summary_html === '') {
        return '';
    }

    if (!preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $summary_html, $matches)) {
        return '';
    }

    return !empty($matches[1]) ? html_entity_decode((string) $matches[1], ENT_QUOTES, 'UTF-8') : '';
}

function pcc_attach_remote_image_to_product($product_id, $file_url) {
    $token = pcc_get_moodle_token();
    if ($token === '' || $file_url === '') {
        return false;
    }

    $image_url = add_query_arg('token', $token, $file_url);

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($image_url, 30);
    if (is_wp_error($tmp)) {
        return false;
    }

    $name = wp_basename((string) parse_url($image_url, PHP_URL_PATH));
    if ($name === '') {
        $name = 'moodle-course-image.jpg';
    }

    $attachment_id = media_handle_sideload(array(
        'name'     => $name,
        'tmp_name' => $tmp,
    ), $product_id);

    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return false;
    }

    set_post_thumbnail($product_id, (int) $attachment_id);
    return true;
}

function pcc_apply_default_course_image($product_id) {
    $default_image_id = pcc_get_default_image_id();
    if ($default_image_id > 0) {
        set_post_thumbnail($product_id, $default_image_id);
    }
}

function pcc_set_course_image($product_id, $image_url) {
    if (!empty($image_url)) {
        pcc_attach_remote_image_to_product((int) $product_id, (string) $image_url);
    }
}
