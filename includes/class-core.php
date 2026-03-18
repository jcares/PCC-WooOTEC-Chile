<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_Core {
    private static ?PCC_WooOTEC_Pro_Core $instance = null;

    private array $defaults = array();

    public static function instance(): PCC_WooOTEC_Pro_Core {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $defaults = require PCC_WOOOTEC_PRO_PATH . 'config/defaults.php';
        $this->defaults = is_array($defaults) ? $defaults : array();
    }

    public function boot(): void {
        $this->load_dependencies();
        $this->register_hooks();
    }

    public static function activate(): void {
        $core = self::instance();
        $core->load_dependencies();
        $core->register_default_options();
        PCC_WooOTEC_Pro_Cron::install();
    }

    public static function deactivate(): void {
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-cron.php';
        PCC_WooOTEC_Pro_Cron::unschedule();
    }

    public function get_defaults(): array {
        return $this->defaults;
    }

    public function get_option(string $key, mixed $fallback = null): mixed {
        if ($fallback === null && array_key_exists($key, $this->defaults)) {
            $fallback = $this->defaults[$key];
        }

        return get_option('pcc_woootec_pro_' . $key, $fallback);
    }

    public function update_option(string $key, mixed $value): bool {
        return update_option('pcc_woootec_pro_' . $key, $value, false);
    }

    public function register_default_options(): void {
        foreach ($this->defaults as $key => $value) {
            $option_name = 'pcc_woootec_pro_' . $key;
            if (get_option($option_name, null) === null) {
                add_option($option_name, $value);
            }
        }
    }

    private function load_dependencies(): void {
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-logger.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-api.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-mailer.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-sso.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-sync.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-enroll.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-cron.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'admin/class-admin.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'public/class-frontend.php';
        require_once PCC_WOOOTEC_PRO_PATH . 'updater/class-updater.php';
    }

    private function register_hooks(): void {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'register_runtime'));
        add_action('before_woocommerce_init', array($this, 'declare_woocommerce_compatibility'));
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('pcc-woootec-chile', false, dirname(PCC_WOOOTEC_PRO_BASENAME) . '/languages');
    }

    public function register_runtime(): void {
        $this->register_default_options();

        PCC_WooOTEC_Pro_Admin::instance()->boot();
        PCC_WooOTEC_Pro_Frontend::instance()->boot();
        PCC_WooOTEC_Pro_Mailer::instance();
        PCC_WooOTEC_Pro_Enroll::instance()->boot();
        PCC_WooOTEC_Pro_Cron::boot();
        PCC_WooOTEC_Pro_Updater::instance()->boot();
    }

    public function declare_woocommerce_compatibility(): void {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PCC_WOOOTEC_PRO_FILE, true);
        }
    }
}
