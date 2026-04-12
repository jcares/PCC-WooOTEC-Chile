<?php
/**
 * Plugin Name: Woo OTEC Moodle Integration
 * Plugin URI:  https://cipresalto.cl
 * Description: Integración profesional entre WooCommerce y Moodle para la gestión de cursos OTEC.
 * Version:     3.0.7
 * Author:      JCares
 * Author URI:  https://cipresalto.cl
 * Text Domain: woo-otec-moodle
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 *
 * @package    Woo_OTEC_Moodle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Definición de constantes del plugin
define( 'WOO_OTEC_MOODLE_VERSION', '3.0.7' );
define( 'WOO_OTEC_MOODLE_FILE', __FILE__ );
define( 'WOO_OTEC_MOODLE_PATH', str_replace( '\\', '/', plugin_dir_path( __FILE__ ) ) );
define( 'WOO_OTEC_MOODLE_URL', str_replace( '\\', '/', plugin_dir_url( __FILE__ ) ) );
define( 'WOO_OTEC_MOODLE_BASENAME', str_replace( '\\', '/', plugin_basename( __FILE__ ) ) );

// Incluir archivos de clases para análisis estático
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
require_once WOO_OTEC_MOODLE_PATH . 'includes/sample-page-creator.php';
require_once WOO_OTEC_MOODLE_PATH . 'frontend/class-frontend-renderer.php';
require_once WOO_OTEC_MOODLE_PATH . 'includes/class-cron-manager.php';
require_once WOO_OTEC_MOODLE_PATH . 'includes/class-sso-manager.php';
require_once WOO_OTEC_MOODLE_PATH . 'includes/class-field-mapper.php';
require_once WOO_OTEC_MOODLE_PATH . 'includes/class-exception-handler.php';

/**
 * Clase principal del plugin
 */
final class Woo_OTEC_Moodle {

	/**
	 * Instancia única de la clase
	 */
	private static $instance = null;

	/**
	 * Manager de metadatos
	 */
	private $metadata_manager = null;

	/**
	 * Manager de plantillas
	 */
	private $template_manager = null;

	/**
	 * Obtener la instancia única
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor privado para evitar múltiples instancias
	 */
	private function __construct() {
		$this->includes();
	}

	/**
	 * Incluir archivos necesarios
	 */
	private function includes() {
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
		require_once WOO_OTEC_MOODLE_PATH . 'includes/sample-page-creator.php';
		require_once WOO_OTEC_MOODLE_PATH . 'frontend/class-frontend-renderer.php';
		require_once WOO_OTEC_MOODLE_PATH . 'includes/class-cron-manager.php';
		require_once WOO_OTEC_MOODLE_PATH . 'includes/class-sso-manager.php';
		require_once WOO_OTEC_MOODLE_PATH . 'includes/class-field-mapper.php';
	}

	/**
	 * Obtener instancia del Metadata Manager
	 */
	public function get_metadata_manager() {
		return isset( $this->metadata_manager ) ? $this->metadata_manager : null;
	}

	/**
	 * Obtener instancia del Template Manager
	 */
	public function get_template_manager() {
		return isset( $this->template_manager ) ? $this->template_manager : null;
	}

	/**
	 * Inicializar componentes del plugin
	 */
	public function boot() {

    $logger        = new \Woo_OTEC_Moodle\Logger();
    $api_client    = new \Woo_OTEC_Moodle\API_Client();
    $email_manager = new \Woo_OTEC_Moodle\Email_Manager( $logger );

    // Inicializar managers (propiedades ya declaradas en la clase)
    $this->metadata_manager = new \Woo_OTEC_Moodle\Metadata_Manager( $api_client, $logger );
    $this->template_manager = new \Woo_OTEC_Moodle\Template_Manager();
    // Programador de tareas (CRON)
    new \Woo_OTEC_Moodle\Cron_Manager();
    // SSO - Single Sign-On
    new \Woo_OTEC_Moodle\SSO_Manager();
    // Mapeador de campos
    new \Woo_OTEC_Moodle\Field_Mapper();
    // Administración
    new \Woo_OTEC_Moodle\Admin_Settings(
        $api_client,
        $logger,
        $this->metadata_manager,
        $this->template_manager
    );

    // Sincronización
    new \Woo_OTEC_Moodle\Course_Sync( $api_client, $logger );

    // Matriculación
    new \Woo_OTEC_Moodle\Enrollment_Manager(
        $api_client,
        $logger,
        $email_manager
    );

    // Frontend
    new \Woo_OTEC_Moodle\Frontend_Renderer(
        $this->metadata_manager,
        $logger
    );

    // Template Shortcodes
    new \Woo_OTEC_Moodle\Template_Shortcodes(
        $this->template_manager,
        $this->metadata_manager
    );

    // CSS admin
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
}

	/**
	 * Enqueuing de estilos en el admin
	 */
	public function enqueue_admin_styles() {
		wp_enqueue_style( 'wom-admin-css', WOO_OTEC_MOODLE_URL . 'admin/css/admin-style.css', array(), WOO_OTEC_MOODLE_VERSION );
		wp_enqueue_style( 'wom-email-css', WOO_OTEC_MOODLE_URL . 'assets/css/email.css', array(), WOO_OTEC_MOODLE_VERSION );
	}
}

/**
 * Función global para iniciar el plugin de forma segura después de que otros plugins se hayan cargado.
 */
add_action( 'plugins_loaded', function() {

	// Verificar si WooCommerce está activo
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function() {
			$message = __( 'Woo OTEC Moodle requiere que WooCommerce esté instalado y activo.', 'woo-otec-moodle' );
			echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
		} );
		return;
	}

	// Cargar traducciones
	load_plugin_textdomain( 'woo-otec-moodle', false, dirname( WOO_OTEC_MOODLE_BASENAME ) . '/languages' );

	// Iniciar plugin
	Woo_OTEC_Moodle::instance()->boot();

	// Filtro para usar plantillas personalizadas en lugar de WooCommerce
	add_filter( 'woocommerce_locate_template', function( $template, $template_name, $template_path ) {
		$custom_template_path = WOO_OTEC_MOODLE_PATH . 'templates/' . $template_name;
		
		// Si existe una plantilla personalizada, usarla
		if ( file_exists( $custom_template_path ) ) {
			return $custom_template_path;
		}
		
		return $template;
	}, 10, 3 );

}, 20 );

/**
 * Gancho de activación
 */
register_activation_hook( __FILE__, function() {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'Woo OTEC Moodle requiere PHP 7.4 o superior.' );
	}
} );
