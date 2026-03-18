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
        if (!defined('PCC_WOOOTEC_PATH')) {
            return;
        }

        require_once PCC_WOOOTEC_PATH . 'includes/logger.php';
        require_once PCC_WOOOTEC_PATH . 'includes/admin.php';
        require_once PCC_WOOOTEC_PATH . 'includes/cron.php';

        pcc_register_default_options();
        PCC_WooOTEC_Cron::install_table();
        PCC_WooOTEC_Cron::ensure_scheduled();
    }

    public static function deactivate() {
        if (!defined('PCC_WOOOTEC_PATH')) {
            return;
        }

        require_once PCC_WOOOTEC_PATH . 'includes/cron.php';
        PCC_WooOTEC_Cron::unschedule();
    }

    private function load_dependencies() {
        require_once PCC_WOOOTEC_PATH . 'includes/logger.php';
        require_once PCC_WOOOTEC_PATH . 'includes/admin.php';
        require_once PCC_WOOOTEC_PATH . 'includes/cron.php';
        require_once PCC_WOOOTEC_PATH . 'includes/api.php';
        require_once PCC_WOOOTEC_PATH . 'includes/sync.php';
        require_once PCC_WOOOTEC_PATH . 'includes/enroll.php';
        require_once PCC_WOOOTEC_PATH . 'includes/sso.php';
        require_once PCC_WOOOTEC_PATH . 'includes/updater.php';

        if (is_admin()) {
            require_once PCC_WOOOTEC_PATH . 'admin/admin-menu.php';
            require_once PCC_WOOOTEC_PATH . 'admin/dashboard.php';
            require_once PCC_WOOOTEC_PATH . 'admin/settings-page.php';
            require_once PCC_WOOOTEC_PATH . 'admin/retry-page.php';
            require_once PCC_WOOOTEC_PATH . 'admin/logs-page.php';
            require_once PCC_WOOOTEC_PATH . 'admin/sync-page.php';
        }
    }

    private function register_hooks() {
        add_action('admin_init', array($this, 'check_dependencies'));
        add_action('admin_init', array($this, 'register_settings'));

        add_action('admin_menu', 'pcc_woootec_menu');
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));

        add_action('init', array($this, 'register_shortcodes'));
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'));

        add_filter('cron_schedules', array('PCC_WooOTEC_Cron', 'add_cron_schedule'));
        add_action('init', array('PCC_WooOTEC_Cron', 'ensure_scheduled'));
        add_action(PCC_WooOTEC_Cron::CRON_HOOK, array('PCC_WooOTEC_Cron', 'retry_failed_enrollments'));
        add_action(PCC_WooOTEC_Cron::SYNC_HOOK, 'pcc_run_scheduled_sync');

        add_filter('pre_set_site_transient_update_plugins', array('PCC_WooOTEC_Updater', 'inject_update'));
        add_filter('plugins_api', array('PCC_WooOTEC_Updater', 'plugins_api'), 10, 3);
        add_filter('auto_update_plugin', array('PCC_WooOTEC_Updater', 'maybe_enable_auto_update'), 10, 2);
        add_action('admin_notices', array('PCC_WooOTEC_Updater', 'maybe_render_notice'));
    }

    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
        }
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>PCC-WooOTEC requiere WooCommerce instalado y activo.</p></div>';
    }

    public function admin_assets($hook) {
        if (strpos((string) $hook, 'pcc') === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'pcc-admin-style',
            PCC_WOOOTEC_URL . 'assets/css/admin-style.css',
            array(),
            PCC_WOOOTEC_VERSION
        );
    }

    public function handle_order_completed($order_id) {
        if (function_exists('pcc_enroll_user')) {
            pcc_enroll_user($order_id);
        }
    }

    public function register_settings() {
        if (is_admin() && function_exists('pcc_register_settings')) {
            pcc_register_settings();
        }
    }

    public function register_shortcodes() {
        if (function_exists('pcc_register_shortcodes')) {
            pcc_register_shortcodes();
        }
    }
}
