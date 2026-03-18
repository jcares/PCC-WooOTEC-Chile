<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_Frontend {
    private static ?PCC_WooOTEC_Pro_Frontend $instance = null;

    public static function instance(): PCC_WooOTEC_Pro_Frontend {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
    }

    public function boot(): void {
        add_shortcode('pcc_mis_cursos', array($this, 'render_my_courses_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets(): void {
        wp_enqueue_style('pcc-woootec-frontend', PCC_WOOOTEC_PRO_URL . 'assets/css/frontend.css', array(), PCC_WOOOTEC_PRO_VERSION);
        wp_enqueue_script('pcc-woootec-frontend', PCC_WOOOTEC_PRO_URL . 'assets/js/frontend.js', array(), PCC_WOOOTEC_PRO_VERSION, true);
    }

    public function render_my_courses_shortcode(): string {
        if (!is_user_logged_in()) {
            return '<p>Debes iniciar sesion para ver tus cursos.</p>';
        }

        if (!function_exists('wc_get_orders')) {
            return '<p>WooCommerce no esta disponible.</p>';
        }

        $user = wp_get_current_user();
        $orders = wc_get_orders(
            array(
                'customer_id' => $user->ID,
                'status'      => array('completed'),
                'limit'       => -1,
            )
        );

        $courses = array();
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = (int) $item->get_product_id();
                if ($product_id <= 0 || isset($courses[$product_id])) {
                    continue;
                }

                $course_id = (int) get_post_meta($product_id, '_moodle_id', true);
                if ($course_id <= 0) {
                    continue;
                }

                $courses[$product_id] = array(
                    'title'      => get_the_title($product_id),
                    'image'      => get_the_post_thumbnail_url($product_id, 'medium') ?: PCC_WOOOTEC_PRO_URL . 'assets/images/default-course.jpg',
                    'instructor' => (string) get_post_meta($product_id, '_instructor', true),
                    'start_date' => (int) get_post_meta($product_id, '_start_date', true),
                    'end_date'   => (int) get_post_meta($product_id, '_end_date', true),
                    'access_url' => PCC_WooOTEC_Pro_SSO::instance()->build_url((string) $user->user_email, $course_id),
                );
            }
        }

        ob_start();
        $template = PCC_WOOOTEC_PRO_PATH . 'public/templates/my-courses.php';
        if (file_exists($template)) {
            $courses = array_values($courses);
            include $template;
        }

        return (string) ob_get_clean();
    }
}
