<?php
/**
 * Plugin Name:       Woo OTEC Moodle Integration
 * Plugin URI:        https://cipresalto.cl
 * Description:       Integración profesional entre WooCommerce y Moodle para la gestión
 *                    de cursos OTEC. Arquitectura modular, event-driven, y completamente
 *                    escalable para venta y administración de cursos online.
 * Version:           4.0.1
 * Author:            JCares
 * Author URI:        https://cipresalto.cl
 * Text Domain:       woo-otec-moodle
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * WC requires at least: 5.0
 *
 * @package Woo_OTEC_Moodle
 * @since   4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants - loaded once at initialization.
define( 'WOO_OTEC_MOODLE_VERSION',  '4.0.1' );
define( 'WOO_OTEC_MOODLE_FILE',     __FILE__ );
define( 'WOO_OTEC_MOODLE_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WOO_OTEC_MOODLE_URL',      plugin_dir_url( __FILE__ ) );
define( 'WOO_OTEC_MOODLE_BASENAME', plugin_basename( __FILE__ ) );

// ============================================================================
// AUTOLOADER PSR-4
// ============================================================================

spl_autoload_register( function ( $class ) {
	$prefix = 'Woo_OTEC_Moodle\\';

	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$file = WOO_OTEC_MOODLE_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// ============================================================================
// PLUGIN INITIALIZATION – Entry point after plugins_loaded
// ============================================================================

add_action( 'plugins_loaded', function () {
	// Check WooCommerce availability.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'Woo OTEC Moodle requires WooCommerce to be installed and active.', 'woo-otec-moodle' );
			echo '</p></div>';
		} );
		return;
	}

	// Load translations.
	load_plugin_textdomain(
		'woo-otec-moodle',
		false,
		dirname( WOO_OTEC_MOODLE_BASENAME ) . '/languages'
	);

	// Initialize plugin using new architecture.
	$plugin = \Woo_OTEC_Moodle\Core\Plugin::instance();
	$plugin->boot();

	// Backward compatibility: Legacy hook for old code.
	do_action( 'woo_otec_moodle_initialized', $plugin );

	// Load legacy classes for backward compatibility (temporary).
	// These will be refactored incrementally in future versions.
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-logger.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-api-client.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-admin-settings.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-course-sync.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-enrollment-manager.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-email-manager.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-metadata-manager.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-template-manager.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-template-customizer.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-preview-generator.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-template-shortcodes.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-cron-manager.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-sso-manager.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-field-mapper.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/class-exception-handler.php';
	require_once WOO_OTEC_MOODLE_PATH . 'includes/sample-page-creator.php';
	require_once WOO_OTEC_MOODLE_PATH . 'frontend/class-frontend-renderer.php';

	// Initialize legacy plugin instance for backward compatibility.
	Woo_OTEC_Moodle::instance()->boot();

}, 10 );

// ============================================================================
// LEGACY CLASS – Maintained for backward compatibility
// ============================================================================

/**
 * Legacy plugin class.
 *
 * DEPRECATED: Use \Woo_OTEC_Moodle\Core\Plugin instead.
 * This class exists for backward compatibility during migration phase.
 *
 * @package Woo_OTEC_Moodle
 * @deprecated 4.0.0 Use \Woo_OTEC_Moodle\Core\Plugin
 */
final class Woo_OTEC_Moodle {

	/**
	 * Singleton instance.
	 *
	 * @var Woo_OTEC_Moodle|null
	 */
	private static $instance = null;

	/**
	 * Metadata manager.
	 *
	 * @var \Woo_OTEC_Moodle\Metadata_Manager|null
	 */
	private $metadata_manager = null;

	/**
	 * Template manager.
	 *
	 * @var \Woo_OTEC_Moodle\Template_Manager|null
	 */
	private $template_manager = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Woo_OTEC_Moodle
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (Singleton).
	 */
	private function __construct() {}

	/**
	 * Get metadata manager.
	 *
	 * @return \Woo_OTEC_Moodle\Metadata_Manager|null
	 */
	public function get_metadata_manager() {
		return $this->metadata_manager;
	}

	/**
	 * Get template manager.
	 *
	 * @return \Woo_OTEC_Moodle\Template_Manager|null
	 */
	public function get_template_manager() {
		return $this->template_manager;
	}

	/**
	 * Boot legacy components.
	 *
	 * This method is called for backward compatibility.
	 * New code should use \Woo_OTEC_Moodle\Core\Plugin instead.
	 *
	 * @return void
	 * @deprecated 4.0.0
	 */
	public function boot() {
		// Instance all legacy classes.
		$logger = new \Woo_OTEC_Moodle\Logger();
		$api_client = new \Woo_OTEC_Moodle\API_Client();
		$email_manager = new \Woo_OTEC_Moodle\Email_Manager( $logger );

		$this->metadata_manager = new \Woo_OTEC_Moodle\Metadata_Manager( $api_client, $logger );
		$this->template_manager = new \Woo_OTEC_Moodle\Template_Manager();

		$template_customizer = new \Woo_OTEC_Moodle\Template_Customizer( $this->template_manager );
		$preview_generator = new \Woo_OTEC_Moodle\Preview_Generator( $this->template_manager );

		new \Woo_OTEC_Moodle\Cron_Manager();
		new \Woo_OTEC_Moodle\SSO_Manager();

		new \Woo_OTEC_Moodle\Admin_Settings(
			$api_client,
			$logger,
			$this->metadata_manager,
			$this->template_manager,
			$template_customizer,
			$preview_generator
		);

		new \Woo_OTEC_Moodle\Course_Sync( $api_client, $logger );

		new \Woo_OTEC_Moodle\Enrollment_Manager(
			$api_client,
			$logger,
			$email_manager
		);

		new \Woo_OTEC_Moodle\Frontend_Renderer(
			$this->metadata_manager,
			$logger
		);

		new \Woo_OTEC_Moodle\Template_Shortcodes(
			$this->template_manager,
			$this->metadata_manager
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( false === strpos( $hook, 'woo-otec-moodle' ) ) {
			return;
		}

		wp_enqueue_style(
			'wom-admin-css',
			WOO_OTEC_MOODLE_URL . 'admin/css/admin-style.css',
			array(),
			WOO_OTEC_MOODLE_VERSION
		);

		wp_enqueue_style(
			'wom-email-css',
			WOO_OTEC_MOODLE_URL . 'assets/css/email.css',
			array(),
			WOO_OTEC_MOODLE_VERSION
		);

		wp_enqueue_style(
			'wom-builder-css',
			WOO_OTEC_MOODLE_URL . 'admin/css/template-builder.css',
			array( 'wom-admin-css' ),
			filemtime( WOO_OTEC_MOODLE_PATH . 'admin/css/template-builder.css' )
		);
	}
}

// ============================================================================
// HOOKS – Activation, Deactivation, Uninstall
// ============================================================================

/**
 * Plugin activation hook.
 */
register_activation_hook( __FILE__, function () {
	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Woo OTEC Moodle requires PHP 8.0 or later.', 'woo-otec-moodle' ),
			esc_html__( 'Activation Error', 'woo-otec-moodle' ),
			array( 'back_link' => true )
		);
	}
} );

/**
 * WooCommerce template override filter.
 *
 * Allows the plugin to override WooCommerce templates by loading
 * custom versions from the templates/ directory.
 */
add_filter(
	'woocommerce_locate_template',
	function ( $template, $template_name ) {
		$custom = WOO_OTEC_MOODLE_PATH . 'templates/' . $template_name;
		if ( file_exists( $custom ) ) {
			return $custom;
		}
		return $template;
	},
	10,
	2
);

// ============================================================================
// EOF – Woo OTEC Moodle Plugin Main Entry Point
// ============================================================================