<?php

if (!defined('ABSPATH')) {
    exit;
}

final class PCC_WooOTEC_Pro_Admin {
    private static ?PCC_WooOTEC_Pro_Admin $instance = null;

    public static function instance(): PCC_WooOTEC_Pro_Admin {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
    }

    public function boot(): void {
        if (!is_admin()) {
            return;
        }

        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_pcc_woootec_run_sync', array($this, 'handle_manual_sync'));
    }

    public function register_menu(): void {
        add_menu_page(
            'PCC WooOTEC Chile',
            'PCC WooOTEC Chile',
            'manage_options',
            'pcc-woootec-chile',
            array($this, 'render_settings_page'),
            'dashicons-welcome-learn-more',
            25
        );

        add_submenu_page('pcc-woootec-chile', 'Configuracion', 'Configuracion', 'manage_options', 'pcc-woootec-chile', array($this, 'render_settings_page'));
        add_submenu_page('pcc-woootec-chile', 'Sincronizacion', 'Sincronizacion', 'manage_options', 'pcc-woootec-chile-sync', array($this, 'render_sync_page'));
        add_submenu_page('pcc-woootec-chile', 'Logs', 'Logs', 'manage_options', 'pcc-woootec-chile-logs', array($this, 'render_logs_page'));
    }

    public function register_settings(): void {
        $fields = array(
            'moodle_url'           => 'esc_url_raw',
            'moodle_token'         => 'sanitize_text_field',
            'student_role_id'      => 'absint',
            'default_price'        => 'sanitize_text_field',
            'default_instructor'   => 'sanitize_text_field',
            'fallback_description' => 'sanitize_textarea_field',
            'default_image_id'     => 'absint',
            'sso_base_url'         => 'esc_url_raw',
            'github_repo'          => 'sanitize_text_field',
            'github_release_url'   => 'esc_url_raw',
        );

        foreach ($fields as $field => $sanitize_callback) {
            register_setting(
                'pcc_woootec_pro_settings',
                'pcc_woootec_pro_' . $field,
                array(
                    'type'              => in_array($sanitize_callback, array('absint'), true) ? 'integer' : 'string',
                    'sanitize_callback' => $sanitize_callback,
                    'default'           => PCC_WooOTEC_Pro_Core::instance()->get_defaults()[$field] ?? '',
                )
            );
        }

        foreach (array('sso_enabled', 'auto_update', 'redirect_after_purchase', 'debug_enabled') as $field) {
            register_setting(
                'pcc_woootec_pro_settings',
                'pcc_woootec_pro_' . $field,
                array(
                    'type'              => 'string',
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'default'           => PCC_WooOTEC_Pro_Core::instance()->get_defaults()[$field] ?? 'no',
                )
            );
        }
    }

    public function sanitize_checkbox(mixed $value): string {
        return !empty($value) && $value !== 'no' ? 'yes' : 'no';
    }

    public function enqueue_assets(string $hook): void {
        if (strpos($hook, 'pcc-woootec-chile') === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('pcc-woootec-admin', PCC_WOOOTEC_PRO_URL . 'admin/assets/css/admin.css', array(), PCC_WOOOTEC_PRO_VERSION);
        wp_enqueue_script('pcc-woootec-admin', PCC_WOOOTEC_PRO_URL . 'admin/assets/js/admin.js', array('jquery'), PCC_WOOOTEC_PRO_VERSION, true);
    }

    public function handle_manual_sync(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado.');
        }

        check_admin_referer('pcc_woootec_run_sync');

        $result = PCC_WooOTEC_Pro_Sync::instance()->run(true);
        $redirect = add_query_arg(
            array(
                'page'   => 'pcc-woootec-chile-sync',
                'status' => $result['status'],
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public function render_settings_page(): void {
        $data = array(
            'core'         => PCC_WooOTEC_Pro_Core::instance(),
            'last_sync'    => PCC_WooOTEC_Pro_Core::instance()->get_option('last_sync', array()),
            'connection_ok'=> PCC_WooOTEC_Pro_API::instance()->test_connection(),
        );

        $this->render_view('settings-page.php', $data);
    }

    public function render_sync_page(): void {
        $this->render_view(
            'sync-page.php',
            array(
                'last_sync' => PCC_WooOTEC_Pro_Core::instance()->get_option('last_sync', array()),
            )
        );
    }

    public function render_logs_page(): void {
        $this->render_view(
            'logs-page.php',
            array(
                'sync_log'  => PCC_WooOTEC_Pro_Logger::read_tail(PCC_WooOTEC_Pro_Logger::SYNC_LOG),
                'error_log' => PCC_WooOTEC_Pro_Logger::read_tail(PCC_WooOTEC_Pro_Logger::ERROR_LOG),
            )
        );
    }

    private function render_view(string $view, array $data = array()): void {
        $view_path = PCC_WOOOTEC_PRO_PATH . 'admin/views/' . $view;
        if (!file_exists($view_path)) {
            return;
        }

        extract($data, EXTR_SKIP);
        include $view_path;
    }
}
