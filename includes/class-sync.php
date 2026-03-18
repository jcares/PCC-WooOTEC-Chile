<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_Sync {
    private static ?PCC_WooOTEC_Pro_Sync $instance = null;

    public static function instance(): PCC_WooOTEC_Pro_Sync {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
    }

    public function run(bool $verbose = false): array {
        $result = array(
            'status'             => 'error',
            'message'            => '',
            'categories_created' => 0,
            'categories_updated' => 0,
            'products_created'   => 0,
            'products_updated'   => 0,
        );

        if (!class_exists('WooCommerce')) {
            $result['message'] = 'WooCommerce no esta activo.';
            $this->update_last_sync($result);
            return $result;
        }

        $categories = PCC_WooOTEC_Pro_API::instance()->get_categories();
        $category_result = $this->sync_categories($categories);

        $courses = PCC_WooOTEC_Pro_API::instance()->get_courses();
        if (empty($courses)) {
            $result['message'] = 'No se pudieron obtener cursos desde Moodle.';
            $result['categories_created'] = $category_result['created'];
            $result['categories_updated'] = $category_result['updated'];
            $this->update_last_sync($result);
            return $result;
        }

        $course_result = $this->sync_courses($courses);

        $result['status'] = 'success';
        $result['message'] = 'Sincronizacion completada.';
        $result['categories_created'] = $category_result['created'];
        $result['categories_updated'] = $category_result['updated'];
        $result['products_created'] = $course_result['created'];
        $result['products_updated'] = $course_result['updated'];

        $this->update_last_sync($result);
        PCC_WooOTEC_Pro_Logger::info('Sincronizacion Moodle -> WooCommerce completada', $result);

        if ($verbose) {
            foreach ($course_result['messages'] as $message) {
                PCC_WooOTEC_Pro_Logger::info($message);
            }
        }

        return $result;
    }

    public function sync_categories(array $categories): array {
        $result = array('created' => 0, 'updated' => 0);
        if (!taxonomy_exists('product_cat')) {
            return $result;
        }

        $pending = array();
        foreach ($categories as $category) {
            if (!is_object($category) || empty($category->id) || empty($category->name)) {
                continue;
            }

            $pending[(int) $category->id] = $category;
        }

        $attempt = 0;
        while (!empty($pending) && $attempt < 20) {
            $processed = 0;

            foreach ($pending as $moodle_id => $category) {
                $parent_moodle_id = isset($category->parent) ? (int) $category->parent : 0;
                $parent_term_id = 0;

                if ($parent_moodle_id > 0) {
                    $parent_term = $this->get_category_by_moodle_id($parent_moodle_id);
                    if (!$parent_term) {
                        continue;
                    }
                    $parent_term_id = (int) $parent_term->term_id;
                }

                $created = $this->upsert_category($category, $parent_term_id);
                if ($created === null) {
                    continue;
                }

                if ($created) {
                    $result['created']++;
                } else {
                    $result['updated']++;
                }

                unset($pending[$moodle_id]);
                $processed++;
            }

            if ($processed === 0) {
                foreach ($pending as $moodle_id => $category) {
                    $created = $this->upsert_category($category, 0);
                    if ($created === null) {
                        continue;
                    }

                    if ($created) {
                        $result['created']++;
                    } else {
                        $result['updated']++;
                    }

                    unset($pending[$moodle_id]);
                }
                break;
            }

            $attempt++;
        }

        return $result;
    }

    public function sync_courses(array $courses): array {
        $result = array(
            'created'  => 0,
            'updated'  => 0,
            'messages' => array(),
        );

        foreach ($courses as $course) {
            if (!is_object($course) || empty($course->id) || (int) $course->id < 2) {
                continue;
            }

            $product_id = $this->find_product_id((int) $course->id);
            if ($product_id > 0) {
                $this->update_product($product_id, $course);
                $result['updated']++;
                $result['messages'][] = 'Curso actualizado ID Moodle ' . (int) $course->id;
            } else {
                $product_id = $this->create_product($course);
                if ($product_id > 0) {
                    $result['created']++;
                    $result['messages'][] = 'Curso creado ID Moodle ' . (int) $course->id;
                }
            }
        }

        return $result;
    }

    public function find_product_id(int $moodle_course_id): int {
        $query = new WP_Query(
            array(
                'post_type'      => 'product',
                'post_status'    => array('publish', 'draft', 'private'),
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => '_moodle_id',
                        'value'   => $moodle_course_id,
                        'compare' => '=',
                    ),
                ),
            )
        );

        return $query->have_posts() ? (int) $query->posts[0] : 0;
    }

    private function upsert_category(object $category, int $parent_term_id): ?bool {
        $moodle_id = (int) $category->id;
        $name = sanitize_text_field((string) $category->name);
        if ($moodle_id <= 0 || $name === '') {
            return null;
        }

        $existing = $this->get_category_by_moodle_id($moodle_id);
        $slug_seed = !empty($category->idnumber) ? (string) $category->idnumber : 'moodle-cat-' . $moodle_id . '-' . $name;

        if ($existing) {
            $updated = wp_update_term(
                (int) $existing->term_id,
                'product_cat',
                array(
                    'name'   => $name,
                    'slug'   => sanitize_title($slug_seed),
                    'parent' => $parent_term_id,
                )
            );

            if (is_wp_error($updated)) {
                PCC_WooOTEC_Pro_Logger::error('No se pudo actualizar categoria', array('moodle_id' => $moodle_id, 'error' => $updated->get_error_message()));
                return null;
            }

            $term_id = (int) $existing->term_id;
            $created = false;
        } else {
            $inserted = wp_insert_term(
                $name,
                'product_cat',
                array(
                    'slug'   => sanitize_title($slug_seed),
                    'parent' => $parent_term_id,
                )
            );

            if (is_wp_error($inserted) || empty($inserted['term_id'])) {
                PCC_WooOTEC_Pro_Logger::error('No se pudo crear categoria', array('moodle_id' => $moodle_id));
                return null;
            }

            $term_id = (int) $inserted['term_id'];
            $created = true;
        }

        update_term_meta($term_id, 'moodle_id', $moodle_id);
        update_term_meta($term_id, 'moodle_parent_id', isset($category->parent) ? (int) $category->parent : 0);

        return $created;
    }

    private function get_category_by_moodle_id(int $moodle_id): WP_Term|false {
        $terms = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'number'     => 1,
                'meta_query' => array(
                    array(
                        'key'     => 'moodle_id',
                        'value'   => $moodle_id,
                        'compare' => '=',
                    ),
                ),
            )
        );

        return (!is_wp_error($terms) && !empty($terms[0])) ? $terms[0] : false;
    }

    private function create_product(object $course): int {
        $product = new WC_Product_Simple();
        $this->hydrate_product($product, $course);

        $product_id = $product->save();
        if ($product_id > 0) {
            $this->update_product_meta($product_id, $course);
            $this->assign_product_image($product_id, $course);
        }

        return (int) $product_id;
    }

    private function update_product(int $product_id, object $course): void {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            return;
        }

        $this->hydrate_product($product, $course);
        $product->save();
        $this->update_product_meta($product_id, $course);
        $this->assign_product_image($product_id, $course);
    }

    private function hydrate_product(WC_Product $product, object $course): void {
        $product->set_name((string) ($course->fullname ?? 'Curso Moodle'));
        $product->set_slug('moodle-course-' . (int) $course->id);
        $product->set_description($this->get_course_description($course));
        $product->set_short_description(wp_trim_words(wp_strip_all_tags($this->get_course_description($course)), 30));
        $product->set_status(!empty($course->visible) ? 'publish' : 'draft');
        $product->set_virtual(true);
        $product->set_downloadable(false);
        $product->set_catalog_visibility('visible');
        $product->set_sku('MOODLE-' . (int) $course->id);
        $product->set_regular_price((string) PCC_WooOTEC_Pro_Core::instance()->get_option('default_price', '49000'));

        $category_ids = $this->resolve_category_ids((int) ($course->categoryid ?? 0));
        if (!empty($category_ids)) {
            $product->set_category_ids($category_ids);
        }
    }

    private function get_course_description(object $course): string {
        $description = isset($course->summary) ? wp_kses_post((string) $course->summary) : '';
        if ($description !== '') {
            return $description;
        }

        return (string) PCC_WooOTEC_Pro_Core::instance()->get_option('fallback_description', 'Curso sincronizado automaticamente desde Moodle.');
    }

    private function resolve_category_ids(int $moodle_category_id): array {
        if ($moodle_category_id <= 0) {
            return array();
        }

        $term = $this->get_category_by_moodle_id($moodle_category_id);
        return $term ? array((int) $term->term_id) : array();
    }

    private function update_product_meta(int $product_id, object $course): void {
        $teacher_names = PCC_WooOTEC_Pro_API::instance()->get_course_teachers((int) $course->id);
        $teacher = !empty($teacher_names)
            ? implode(', ', $teacher_names)
            : (string) PCC_WooOTEC_Pro_Core::instance()->get_option('default_instructor', 'No asignado');

        update_post_meta($product_id, '_moodle_id', (int) $course->id);
        update_post_meta($product_id, 'moodle_course_id', (int) $course->id);
        update_post_meta($product_id, '_start_date', isset($course->startdate) ? (int) $course->startdate : 0);
        update_post_meta($product_id, '_end_date', isset($course->enddate) ? (int) $course->enddate : 0);
        update_post_meta($product_id, '_instructor', sanitize_text_field($teacher));
        update_post_meta($product_id, '_pcc_synced', 1);
        update_post_meta($product_id, '_moodle_category_id', isset($course->categoryid) ? (int) $course->categoryid : 0);
    }

    private function assign_product_image(int $product_id, object $course): void {
        if (has_post_thumbnail($product_id)) {
            return;
        }

        $image_url = $this->find_moodle_image_url($course);
        if ($image_url !== '') {
            $attachment_id = $this->download_remote_image($image_url, $product_id);
            if ($attachment_id > 0) {
                set_post_thumbnail($product_id, $attachment_id);
                return;
            }
        }

        $default_image_id = (int) PCC_WooOTEC_Pro_Core::instance()->get_option('default_image_id', 0);
        if ($default_image_id > 0) {
            set_post_thumbnail($product_id, $default_image_id);
        }
    }

    private function find_moodle_image_url(object $course): string {
        if (!empty($course->overviewfiles) && is_array($course->overviewfiles)) {
            foreach ($course->overviewfiles as $file) {
                if (!is_object($file) || empty($file->fileurl)) {
                    continue;
                }

                $mimetype = !empty($file->mimetype) ? (string) $file->mimetype : '';
                if ($mimetype === '' || str_starts_with($mimetype, 'image/')) {
                    return add_query_arg('token', PCC_WooOTEC_Pro_API::instance()->get_token(), (string) $file->fileurl);
                }
            }
        }

        if (!empty($course->summary) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $course->summary, $matches)) {
            return html_entity_decode((string) $matches[1], ENT_QUOTES, 'UTF-8');
        }

        return '';
    }

    private function download_remote_image(string $url, int $product_id): int {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url, 30);
        if (is_wp_error($tmp)) {
            PCC_WooOTEC_Pro_Logger::error('No se pudo descargar imagen remota', array('url' => $url, 'error' => $tmp->get_error_message()));
            return 0;
        }

        $filename = wp_basename((string) parse_url($url, PHP_URL_PATH));
        if ($filename === '') {
            $filename = 'moodle-course.jpg';
        }

        $attachment_id = media_handle_sideload(
            array(
                'name'     => $filename,
                'tmp_name' => $tmp,
            ),
            $product_id
        );

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            PCC_WooOTEC_Pro_Logger::error('No se pudo adjuntar imagen al producto', array('error' => $attachment_id->get_error_message()));
            return 0;
        }

        return (int) $attachment_id;
    }

    private function update_last_sync(array $result): void {
        $payload = array(
            'timestamp'            => current_time('mysql'),
            'status'               => sanitize_key((string) ($result['status'] ?? 'error')),
            'message'              => sanitize_text_field((string) ($result['message'] ?? '')),
            'categories_created'   => (int) ($result['categories_created'] ?? 0),
            'categories_updated'   => (int) ($result['categories_updated'] ?? 0),
            'products_created'     => (int) ($result['products_created'] ?? 0),
            'products_updated'     => (int) ($result['products_updated'] ?? 0),
        );

        PCC_WooOTEC_Pro_Core::instance()->update_option('last_sync', $payload);
    }
}
