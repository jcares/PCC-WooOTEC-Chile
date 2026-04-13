<?php
/**
 * Plugin Name:       Woo OTEC Moodle Integration
 * Plugin URI:        https://cipresalto.cl
 * Description:       Integración profesional entre WooCommerce y Moodle para la gestión
 *                    de cursos OTEC. Sincroniza cursos, gestiona matrículas y publicaciones
 *                    de productos en WooCommerce de forma automatizada.
 * Version:           3.0.8
 * Author:            JCares
 * Author URI:        https://cipresalto.cl
 * Text Domain:       woo-otec-moodle
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 *
 * @package Woo_OTEC_Moodle
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constantes del plugin. No se usa str_replace() porque plugin_dir_path() y
// plugin_dir_url() devuelven siempre forward slashes en todos los sistemas operativos.
define( 'WOO_OTEC_MOODLE_VERSION',  '3.0.8' );
define( 'WOO_OTEC_MOODLE_FILE',     __FILE__ );
define( 'WOO_OTEC_MOODLE_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WOO_OTEC_MOODLE_URL',      plugin_dir_url( __FILE__ ) );
define( 'WOO_OTEC_MOODLE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Clase principal del plugin.
 *
 * Implementa el patrón Singleton para garantizar una única instancia durante
 * el ciclo de vida de la petición. Los archivos de clases se cargan una sola
 * vez desde includes(), eliminando la duplicación que causaba el error fatal
 * "Cannot redeclare" al existir require_once en el scope global Y dentro del
 * método includes() al mismo tiempo.
 *
 * @package Woo_OTEC_Moodle
 * @since   1.0.0
 */
final class Woo_OTEC_Moodle {

    /**
     * Instancia única de la clase.
     *
     * @since  1.0.0
     * @var    Woo_OTEC_Moodle|null
     */
    private static $instance = null;

    /**
     * Gestor de metadatos de cursos.
     *
     * @since  1.0.0
     * @var    \Woo_OTEC_Moodle\Metadata_Manager|null
     */
    private $metadata_manager = null;

    /**
     * Gestor de plantillas de producto.
     *
     * @since  1.0.0
     * @var    \Woo_OTEC_Moodle\Template_Manager|null
     */
    private $template_manager = null;

    /**
     * Devuelve la instancia única del plugin.
     *
     * @since  1.0.0
     * @return Woo_OTEC_Moodle
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor privado.
     *
     * Carga los archivos de clases una única vez. No registra hooks aquí;
     * eso se delega a boot() para asegurar que WooCommerce esté disponible.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->includes();
    }

    /**
     * Carga todos los archivos de clases del plugin.
     *
     * Este método es el único punto de carga. El scope global del archivo
     * no debe contener ningún require/include adicional para evitar
     * redeclaraciones de clase.
     *
     * @since 1.0.0
     * @return void
     */
    private function includes() {
        $inc = WOO_OTEC_MOODLE_PATH . 'includes/';

        require_once $inc . 'class-logger.php';
        require_once $inc . 'class-api-client.php';
        require_once $inc . 'class-admin-settings.php';
        require_once $inc . 'class-course-sync.php';
        require_once $inc . 'class-enrollment-manager.php';
        require_once $inc . 'class-email-manager.php';
        require_once $inc . 'class-metadata-manager.php';
        require_once $inc . 'class-template-manager.php';
        require_once $inc . 'class-template-customizer.php';
        require_once $inc . 'class-preview-generator.php';
        require_once $inc . 'class-template-shortcodes.php';
        require_once $inc . 'class-cron-manager.php';
        require_once $inc . 'class-sso-manager.php';
        require_once $inc . 'class-field-mapper.php';
        require_once $inc . 'class-exception-handler.php';
        require_once $inc . 'sample-page-creator.php';
        require_once WOO_OTEC_MOODLE_PATH . 'frontend/class-frontend-renderer.php';
    }

    /**
     * Devuelve el gestor de metadatos.
     *
     * @since  1.0.0
     * @return \Woo_OTEC_Moodle\Metadata_Manager|null
     */
    public function get_metadata_manager() {
        return $this->metadata_manager;
    }

    /**
     * Devuelve el gestor de plantillas.
     *
     * @since  1.0.0
     * @return \Woo_OTEC_Moodle\Template_Manager|null
     */
    public function get_template_manager() {
        return $this->template_manager;
    }

    /**
     * Inicializa todos los componentes del plugin.
     *
     * Se invoca desde el hook plugins_loaded, garantizando que WooCommerce
     * ya está disponible cuando se registran los hooks de integración.
     *
     * @since  1.0.0
     * @return void
     */
   public function boot() {

    $logger        = new \Woo_OTEC_Moodle\Logger();
    $api_client    = new \Woo_OTEC_Moodle\API_Client();
    $email_manager = new \Woo_OTEC_Moodle\Email_Manager($logger);

    $this->metadata_manager = new \Woo_OTEC_Moodle\Metadata_Manager($api_client, $logger);
    $this->template_manager = new \Woo_OTEC_Moodle\Template_Manager();

    $template_customizer = new \Woo_OTEC_Moodle\Template_Customizer($this->template_manager);

    $preview_generator = new \Woo_OTEC_Moodle\Preview_Generator(
        $this->template_manager,
        $logger
    );

    new \Woo_OTEC_Moodle\Cron_Manager();
    new \Woo_OTEC_Moodle\SSO_Manager();
    new \Woo_OTEC_Moodle\Field_Mapper();

    new \Woo_OTEC_Moodle\Admin_Settings(
        $api_client,
        $logger,
        $this->metadata_manager,
        $this->template_manager,
        $template_customizer,
        $preview_generator
    );

    new \Woo_OTEC_Moodle\Course_Sync($api_client, $logger);

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

    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
}

    /**
     * Encola los estilos globales del área de administración.
     *
     * Se restringe a las páginas del propio plugin mediante el parámetro $hook
     * para no impactar el rendimiento del resto del panel de WordPress.
     *
     * @since  1.0.0
     * @param  string $hook Sufijo de la página actual del panel.
     * @return void
     */
    public function enqueue_admin_styles( $hook ) {
        if ( strpos( $hook, 'woo-otec-moodle' ) === false ) {
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
			array('wom-admin-css'),
			filemtime(WOO_OTEC_MOODLE_PATH . 'admin/css/template-builder.css')
    );
    }
}

/**
 * Punto de entrada del plugin.
 *
 * Se ejecuta en plugins_loaded (prioridad 20) para asegurar que WooCommerce
 * y el resto de plugins ya están inicializados.
 */
add_action( 'plugins_loaded', function () {

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            $message = __( 'Woo OTEC Moodle requiere que WooCommerce esté instalado y activo.', 'woo-otec-moodle' );
            printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
        } );
        return;
    }

    load_plugin_textdomain( 'woo-otec-moodle', false, dirname( WOO_OTEC_MOODLE_BASENAME ) . '/languages' );

    Woo_OTEC_Moodle::instance()->boot();

    /**
     * Permite que el plugin reemplace plantillas de WooCommerce con versiones
     * personalizadas ubicadas en templates/.
     *
     * Solo actúa sobre rutas que estén físicamente dentro del directorio
     * templates/ del plugin para prevenir path traversal.
     */
    add_filter( 'woocommerce_locate_template', function ( $template, $template_name ) {
        $custom     = WOO_OTEC_MOODLE_PATH . 'templates/' . $template_name;
        $real_base  = realpath( WOO_OTEC_MOODLE_PATH . 'templates/' );
        $real_custom = realpath( $custom );

        if ( $real_base && $real_custom && strpos( $real_custom, $real_base ) === 0 ) {
            return $custom;
        }

        return $template;
    }, 10, 3 );

}, 20 );

/**
 * Gancho de activación.
 *
 * Verifica los requisitos mínimos antes de permitir la activación del plugin.
 */
register_activation_hook( __FILE__, function () {
    if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'Woo OTEC Moodle requiere PHP 7.4 o superior.', 'woo-otec-moodle' ),
            esc_html__( 'Error de activación', 'woo-otec-moodle' ),
            array( 'back_link' => true )
        );
    }
} );