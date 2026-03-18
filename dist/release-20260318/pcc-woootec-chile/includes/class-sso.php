<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_SSO {
    private static ?PCC_WooOTEC_Pro_SSO $instance = null;

    public static function instance(): PCC_WooOTEC_Pro_SSO {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
    }

    public function is_enabled(): bool {
        return PCC_WooOTEC_Pro_Core::instance()->get_option('sso_enabled', 'yes') === 'yes';
    }

    public function get_base_url(): string {
        $custom = trim((string) PCC_WooOTEC_Pro_Core::instance()->get_option('sso_base_url', ''));
        if ($custom !== '') {
            return rtrim($custom, '/');
        }

        return PCC_WooOTEC_Pro_API::instance()->get_moodle_url();
    }

    public function build_url(string $email, int $course_id = 0): string {
        if (!$this->is_enabled()) {
            return '';
        }

        $base_url = $this->get_base_url();
        $email = sanitize_email($email);

        if ($base_url === '' || $email === '') {
            return '';
        }

        $url = add_query_arg(
            array_filter(
                array(
                    'user'     => $email,
                    'courseid' => $course_id > 0 ? $course_id : null,
                ),
                static fn($value) => $value !== null && $value !== ''
            ),
            $base_url . '/auth/token/login.php'
        );

        return esc_url_raw($url);
    }

    public function store_order_urls(WC_Order $order, WP_User $user, array $course_ids): array {
        $urls = array();

        foreach ($course_ids as $course_id) {
            $url = $this->build_url((string) $user->user_email, (int) $course_id);
            if ($url !== '') {
                $urls[(int) $course_id] = $url;
            }
        }

        $order->update_meta_data('_pcc_moodle_access_urls', $urls);
        $order->update_meta_data('_moodle_access_url', !empty($urls) ? (string) reset($urls) : $this->build_url((string) $user->user_email));

        return $urls;
    }
}
