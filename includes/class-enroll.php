<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_Enroll {
    private static ?PCC_WooOTEC_Pro_Enroll $instance = null;

    public static function instance(): PCC_WooOTEC_Pro_Enroll {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
    }

    public function boot(): void {
        add_action('woocommerce_order_status_completed', array($this, 'handle_completed_order'));
        add_action('template_redirect', array($this, 'maybe_redirect_after_purchase'));
    }

    public function handle_completed_order(int $order_id): void {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        $user = $this->resolve_order_user($order);
        if (!$user instanceof WP_User) {
            PCC_WooOTEC_Pro_Logger::error('No fue posible resolver el usuario de la orden', array('order_id' => $order_id));
            return;
        }

        $course_ids = $this->get_order_course_ids($order);
        if (empty($course_ids)) {
            $order->update_meta_data('_pcc_moodle_enrollment_complete', 1);
            $order->save();
            return;
        }

        $moodle_result = PCC_WooOTEC_Pro_API::instance()->get_or_create_user($user);
        if (is_wp_error($moodle_result)) {
            PCC_WooOTEC_Pro_Logger::error('No fue posible crear o encontrar usuario Moodle', array('order_id' => $order_id, 'error' => $moodle_result->get_error_message()));
            return;
        }

        update_user_meta($user->ID, '_pcc_moodle_user_id', (int) $moodle_result['id']);

        $enrolled = array();
        $failed = array();

        foreach ($course_ids as $course_id) {
            $enroll_ok = PCC_WooOTEC_Pro_API::instance()->enroll_user((int) $moodle_result['id'], $course_id);
            if ($enroll_ok) {
                $enrolled[] = $course_id;
            } else {
                $failed[] = $course_id;
            }
        }

        $urls = PCC_WooOTEC_Pro_SSO::instance()->store_order_urls($order, $user, $enrolled);
        $order->update_meta_data('_pcc_moodle_enrolled_courses', $enrolled);
        $order->update_meta_data('_pcc_moodle_enrollment_complete', empty($failed) ? 1 : 0);
        $order->update_meta_data('_pcc_moodle_enrollment_last', array(
            'enrolled' => $enrolled,
            'failed'   => $failed,
        ));
        $order->save();

        PCC_WooOTEC_Pro_Logger::info('Matricula procesada', array(
            'order_id' => $order_id,
            'courses'  => $course_ids,
            'enrolled' => $enrolled,
            'failed'   => $failed,
            'sso'      => $urls,
        ));

        if (!empty($moodle_result['password']) || !empty($urls)) {
            $this->send_access_email($user, $order, $moodle_result['password'] ?? null, $urls);
        }
    }

    public function maybe_redirect_after_purchase(): void {
        if (PCC_WooOTEC_Pro_Core::instance()->get_option('redirect_after_purchase', 'no') !== 'yes') {
            return;
        }

        if (!function_exists('is_order_received_page') || !is_order_received_page()) {
            return;
        }

        $order_id = absint(get_query_var('order-received'));
        if ($order_id <= 0) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        $url = (string) $order->get_meta('_moodle_access_url');
        if ($url !== '') {
            wp_safe_redirect($url);
            exit;
        }
    }

    private function resolve_order_user(WC_Order $order): WP_User|false {
        $user_id = (int) $order->get_user_id();
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            if ($user instanceof WP_User) {
                return $user;
            }
        }

        $email = sanitize_email((string) $order->get_billing_email());
        return $email !== '' ? get_user_by('email', $email) : false;
    }

    private function get_order_course_ids(WC_Order $order): array {
        $course_ids = array();

        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();
            $moodle_id = (int) get_post_meta($product_id, '_moodle_id', true);
            if ($moodle_id > 0) {
                $course_ids[$moodle_id] = $moodle_id;
            }
        }

        return array_values($course_ids);
    }

    private function send_access_email(WP_User $user, WC_Order $order, ?string $password, array $urls): void {
        $lines = array(
            'Hola ' . ($user->first_name !== '' ? $user->first_name : $user->display_name) . ',',
            '',
            'Tu compra fue confirmada y tus cursos ya estan disponibles.',
            'Usuario Moodle: ' . $user->user_email,
        );

        if (!empty($password)) {
            $lines[] = 'Contrasena temporal: ' . $password;
        }

        if (!empty($urls)) {
            $lines[] = '';
            $lines[] = 'Accesos directos:';
            foreach ($urls as $course_id => $url) {
                $lines[] = 'Curso #' . (int) $course_id . ': ' . $url;
            }
        }

        wp_mail(
            $user->user_email,
            'Acceso a tus cursos Moodle',
            implode("\n", $lines),
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }
}
