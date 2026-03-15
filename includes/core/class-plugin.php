<?php

if (!defined('ABSPATH')) {
    exit;
}

class PCC_WooOTEC {
    public function init() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    public static function activate() {
        if (!get_option('pcc_license_status')) {
            update_option('pcc_license_status', 'inactive');
        }
    }

    private function load_dependencies() {
        require_once PCC_WOOOTEC_PATH . 'includes/logger.php';
        require_once PCC_WOOOTEC_PATH . 'includes/moodle-api.php';
        require_once PCC_WOOOTEC_PATH . 'includes/enrollment.php';
        require_once PCC_WOOOTEC_PATH . 'includes/course-sync.php';
        require_once PCC_WOOOTEC_PATH . 'includes/license.php';

        if (is_admin()) {
            require_once PCC_WOOOTEC_PATH . 'admin/admin-menu.php';
            require_once PCC_WOOOTEC_PATH . 'admin/dashboard.php';
            require_once PCC_WOOOTEC_PATH . 'admin/settings-page.php';
            require_once PCC_WOOOTEC_PATH . 'admin/sync-page.php';
        }
    }

    private function register_hooks() {
        add_action('admin_init', array($this, 'check_dependencies'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'check_license'));

        add_action('admin_menu', 'pcc_woootec_menu');
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));

        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'));
    }

    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
        }
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error">';
        echo '<p>PCC-WooOTEC requiere WooCommerce instalado y activo.</p>';
        echo '</div>';
    }

    public function check_license() {
        $license = get_option('pcc_license_status');
        if ($license !== 'valid') {
            add_action('admin_notices', array($this, 'license_notice'));
        }
    }

    public function license_notice() {
        echo '<div class="notice notice-warning">';
        echo '<p>PCC-WooOTEC-Chile requiere activación de licencia.</p>';
        echo '</div>';
    }

    public function admin_assets($hook) {
        if (strpos((string) $hook, 'pcc') === false) {
            return;
        }

        wp_enqueue_style(
            'pcc-admin-style',
            PCC_WOOOTEC_URL . 'assets/css/admin-style.css',
            array(),
            PCC_WOOOTEC_VERSION
        );
    }

    public function handle_order_completed($order_id) {
        if (!function_exists('pcc_enroll_user')) {
            return;
        }

        pcc_enroll_user($order_id);
    }

    public function register_settings() {
        if (!is_admin()) {
            return;
        }

        if (!function_exists('pcc_register_settings')) {
            return;
        }

        pcc_register_settings();
    }
}
