<?php
/**
 * Gestión de ajustes y administración multi-pestaña (Arquitectura 6 pestañas).
 * 
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'class-template-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'class-template-customizer.php';
require_once plugin_dir_path( __FILE__ ) . 'class-preview-generator.php';

class Admin_Settings {

	/**
	 * Cliente de la API de Moodle.
	 */
	private $api_client;

	/**
	 * Gestor de Logs.
	 */
	private $logger;

	/**
	 * Gestor de Metadatos.
	 */
	private $metadata_manager;

	/**
	 * Gestor de Plantillas.
	 */
	private $template_manager;

	/**
	 * Customizador de Plantillas.
	 */
	private $template_customizer;

	/**
	 * Generador de Previews.
	 */
	private $preview_generator;

	/**
	 * Gestor de Mapeo de Campos.
	 */
	private $field_mapper;

	/**
	 * Constructor.
	 */
	public function __construct( $api_client, $logger, $metadata_manager = null, $template_manager = null ) {
		$this->api_client       = $api_client;
		$this->logger           = $logger;
		$this->metadata_manager = $metadata_manager;

		// Instanciar Template Managers
		$this->template_manager   = $template_manager ?: new \Woo_OTEC_Moodle\Template_Manager();
		$this->template_customizer = new \Woo_OTEC_Moodle\Template_Customizer( $this->template_manager );
		$this->preview_generator   = new \Woo_OTEC_Moodle\Preview_Generator( $this->template_manager );
		$this->field_mapper        = new \Woo_OTEC_Moodle\Field_Mapper();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_woo_otec_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_wom_set_product_image', array( $this, 'ajax_set_product_image' ) );
		add_action( 'wp_ajax_wom_load_product_preview', array( $this, 'ajax_load_product_preview' ) );
		add_action( 'wp_ajax_woo_otec_save_cron_interval', array( $this, 'ajax_save_cron_interval' ) );
		add_action( 'wp_ajax_woo_otec_save_sso_settings', array( $this, 'ajax_save_sso_settings' ) );
		add_action( 'wp_ajax_woo_otec_update_field_mapping', array( $this, 'ajax_update_field_mapping' ) );
		add_action( 'wp_ajax_woo_otec_reset_field_mappings', array( $this, 'ajax_reset_field_mappings' ) );
		add_action( 'wp_ajax_woo_otec_save_metadata', array( $this, 'ajax_save_metadata' ) );
		add_action( 'wp_ajax_woo_otec_reset_metadata', array( $this, 'ajax_reset_metadata' ) );
		add_action( 'wp_ajax_wom_preview_template', array( $this, 'ajax_preview_template' ) );
		add_action( 'wp_ajax_wom_save_template_config', array( $this, 'ajax_save_template_config' ) );
		add_action( 'wp_ajax_wom_reset_template', array( $this, 'ajax_reset_template' ) );
	}

	/**
	 * Añadir menú de administración con 8 sub-páginas (pestañas).
	 */
	public function add_admin_menu() {
		// Menú principal (Dashboard)
		add_menu_page(
			'Woo OTEC Moodle',
			'OTEC Moodle',
			'manage_options',
			'woo-otec-moodle',
			array( $this, 'render_dashboard_page' ),
			WOO_OTEC_MOODLE_URL . 'admin/images/plugin-icon.svg',
			56
		);

		$submenu_pages = array(
			array( 'Dashboard', 'Dashboard', 'woo-otec-moodle', 'render_dashboard_page' ),
			array( 'Configuración', 'Configuración', 'woo-otec-moodle-settings', 'render_settings_page' ),
			array( 'Cursos', 'Cursos', 'woo-otec-moodle-courses', 'render_courses_page' ),
			array( 'Metadatos', 'Metadatos', 'woo-otec-moodle-metadata', 'render_metadata_page' ),
			array( 'Personalización', 'Personalización', 'woo-otec-moodle-template-builder', 'render_template_builder_page' ),
			array( 'Sincronización', 'Sincronización', 'woo-otec-moodle-cron', 'render_cron_page' ),
			array( 'Email', 'Email', 'woo-otec-moodle-email', 'render_email_page' ),
			array( 'Usuarios', 'Usuarios', 'woo-otec-moodle-users', 'render_users_page' ),
			array( 'Bitácora', 'Bitácora', 'woo-otec-moodle-logs', 'render_logs_page' ),
		);

		foreach ( $submenu_pages as $page ) {
			add_submenu_page(
				'woo-otec-moodle',
				$page[0],
				$page[1],
				'manage_options',
				$page[2],
				array( $this, $page[3] )
			);
		}
	}

	/**
	 * Registrar ajustes de WordPress.
	 */
	public function register_settings() {
		register_setting( 'woo_otec_moodle_group', 'woo_otec_moodle_api_url' );
		register_setting( 'woo_otec_moodle_group', 'woo_otec_moodle_api_token' );
		register_setting( 'woo_otec_moodle_group', 'woo_otec_moodle_role_id' );
		register_setting( 'woo_otec_moodle_group', 'woo_otec_moodle_auto_sync', array( 'default' => 'no' ) );
	}

	/**
	 * Encolar estilos y scripts.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'woo-otec-moodle' ) === false ) {
			return;
		}

		wp_enqueue_style( 'woo-otec-moodle-admin-style', WOO_OTEC_MOODLE_URL . 'admin/css/admin-style.css', array(), time() );
		wp_enqueue_style( 'woo-otec-moodle-admin-forms', WOO_OTEC_MOODLE_URL . 'admin/css/admin-forms.css', array(), time() );
		wp_enqueue_script( 'woo-otec-moodle-admin-js', WOO_OTEC_MOODLE_URL . 'admin/js/admin-app.js', array( 'jquery' ), time(), true );

		// Template Builder Assets
		if ( strpos( $hook, 'template-builder' ) !== false ) {
			wp_enqueue_style( 'wom-template-builder-style', WOO_OTEC_MOODLE_URL . 'admin/css/template-builder.css', array(), time() );
			wp_enqueue_script( 'wom-template-builder-js', WOO_OTEC_MOODLE_URL . 'admin/js/template-builder.js', array( 'jquery', 'wp-color-picker' ), time(), true );
			wp_enqueue_style( 'wp-color-picker' );
		}

		wp_enqueue_media();

		wp_localize_script( 'woo-otec-moodle-admin-js', 'wooOtecMoodle', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'woo-otec-moodle-nonce' ),
		) );

		// Localizar para Template Builder también
		if ( strpos( $hook, 'template-builder' ) !== false ) {
			wp_localize_script( 'wom-template-builder-js', 'wooOtecMoodle', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'woo-otec-moodle-nonce' ),
			) );
		}
	}

	/* ─── Renders ───────────────────────────────── */

	public function render_dashboard_page() {
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/dashboard-display.php';
	}

	public function render_settings_page() {
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/settings-display.php';
	}

	public function render_sso_page() {
		// Legacy: SSO integrated into settings. This method kept for backward compatibility.
		$this->render_settings_page();
	}

	public function render_mapper_page() {
		// Legacy support - Mapper merged into metadata
		$this->render_metadata_page();
	}

	public function render_wc_page() {
		// Legacy support - Redirect to woocommerce display
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/woocommerce-display.php';
	}

	public function render_courses_page() {
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/courses-display.php';
	}

	public function render_users_page() {
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/users-display.php';
	}

	public function render_logs_page() {
		$recent_logs = $this->logger->get_recent_logs( 100 );
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/logs-display.php';
	}

	public function render_email_page() {
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/email-display.php';
	}

	public function render_metadata_page() {
		$metadata_manager = $this->metadata_manager;
		$api_client = $this->api_client;
		$logger = $this->logger;
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/metadata-display.php';
	}

	public function render_template_builder_page() {
		$template_manager = $this->template_manager;
		$customizer = $this->template_customizer;
		$preview_generator = $this->preview_generator;
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/template-builder.php';
	}

	public function render_cron_page() {
		require_once WOO_OTEC_MOODLE_PATH . 'admin/partials/cron-display.php';
	}

	/**
	 * AJAX Handler para probar conexión.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'No autorizado' );

		$result = $this->api_client->test_connection();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success( 'Conexión exitosa con Moodle.' );
	}

	public function ajax_set_product_image() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$product_id  = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;

		if ( ! $product_id || ! $attachment_id ) {
			wp_send_json_error( 'Datos inválidos' );
		}

		if ( get_post_type( $product_id ) !== 'product' ) {
			wp_send_json_error( 'El producto no existe' );
		}

		set_post_thumbnail( $product_id, $attachment_id );

		$this->logger->log( 'SUCCESS', "Imagen actualizada para producto ID: {$product_id}" );
		wp_send_json_success( 'Imagen actualizada correctamente' );
	}

	public function ajax_load_product_preview() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$fields = isset( $_POST['fields'] ) ? (array) $_POST['fields'] : array();

		if ( ! $product_id ) {
			wp_send_json_error( 'ID de producto requerido' );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( 'Producto no encontrado' );
		}

		if ( empty( $fields ) ) {
			wp_send_json_error( 'Selecciona al menos un campo para ver la preview.' );
		}

		// Obtener configuración del plugin
		$config = get_option( 'wom_template_config', array() );
		if ( empty( $config ) ) {
			$config = array(
				'settings' => array(
					'show_price'         => true,
					'show_category'      => true,
					'show_meta'          => true,
					'button_text_enroll' => 'Matricularme',
					'default_price'      => '0',
				),
			);
		}

		// Usar output buffering para capturar el HTML de la plantilla
		ob_start();
		
		// Variables disponibles para la template
		$product_obj = get_post( $product_id );
		
		// Incluir plantilla personalizada
		$template_path = WOO_OTEC_MOODLE_PATH . 'templates/template-sample-product.php';
		if ( file_exists( $template_path ) ) {
			$product = $product_obj;
			include $template_path;
		} else {
			// Fallback: generar HTML simple si la plantilla no existe
			?>
			<div style="padding: 20px;">
				<h3><?php echo esc_html( $product->get_name() ); ?></h3>
				<?php if ( in_array( 'summary', $fields, true ) ) : ?>
					<p><?php echo wp_kses_post( wp_trim_words( $product->get_description(), 50 ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}
		
		$preview_html = ob_get_clean();

		wp_send_json_success( $preview_html );
	}

	/**
	 * AJAX Handler para guardar metadatos
	 */
	public function ajax_save_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$metadata = isset( $_POST['metadata'] ) ? json_decode( sanitize_text_field( $_POST['metadata'] ), true ) : array();

		if ( ! is_array( $metadata ) ) {
			wp_send_json_error( 'Formato inválido' );
		}

		update_option( 'woo_otec_moodle_metadata_enabled', $metadata );
		$this->logger->log( 'SUCCESS', 'Metadatos guardados' );

		wp_send_json_success( 'Metadatos guardados' );
	}

	/**
	 * AJAX Handler para resetear metadatos
	 */
	public function ajax_reset_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		delete_option( 'woo_otec_moodle_metadata_enabled' );
		$this->logger->log( 'SUCCESS', 'Metadatos reseteados' );

		wp_send_json_success( 'Metadatos reseteados' );
	}
	 */
	public function ajax_save_cron_interval() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$interval = isset( $_POST['interval'] ) ? intval( $_POST['interval'] ) : 6;

		if ( $interval < 1 || $interval > 24 ) {
			wp_send_json_error( 'El intervalo debe estar entre 1 y 24 horas' );
		}

		$cron = new Cron_Manager();
		$updated_interval = $cron->update_interval( $interval );

		$this->logger->log( 'SUCCESS', "CRON configurado para cada {$updated_interval} hora(s)" );

		wp_send_json_success( "CRON configurado correctamente" );
	}

	/**
	 * AJAX Handler para guardar configuración del SSO
	 */
	public function ajax_save_sso_settings() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$enabled = isset( $_POST['enabled'] ) ? (bool) intval( $_POST['enabled'] ) : false;
		$base_url = isset( $_POST['base_url'] ) ? sanitize_text_field( $_POST['base_url'] ) : '';

		// Validar URL
		if ( $enabled && ! empty( $base_url ) ) {
			$validation = SSO_Manager::validate_base_url( $base_url );
			if ( is_wp_error( $validation ) ) {
				wp_send_json_error( $validation->get_error_message() );
			}
		}

		$settings = SSO_Manager::update_settings( $enabled, $base_url );

		$status_msg = $enabled ? 'SSO habilitado' : 'SSO deshabilitado';
		$this->logger->log( 'SUCCESS', "$status_msg. URL: " . ( $base_url ? $base_url : 'no configurada' ) );

		wp_send_json_success( "Configuración guardada: $status_msg" );
	}

	/**
	 * AJAX Handler para actualizar mapeo de campo
	 */
	public function ajax_update_field_mapping() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$field = isset( $_POST['field'] ) ? sanitize_text_field( $_POST['field'] ) : '';
		$enable = isset( $_POST['enable'] ) ? (bool) intval( $_POST['enable'] ) : false;

		if ( empty( $field ) ) {
			wp_send_json_error( 'Campo requerido' );
		}

		if ( $enable ) {
			Field_Mapper::enable_field( $field );
			$this->logger->log( 'SUCCESS', "Campo '{$field}' habilitado en mapeo" );
		} else {
			Field_Mapper::disable_field( $field );
			$this->logger->log( 'SUCCESS', "Campo '{$field}' deshabilitado en mapeo" );
		}

		wp_send_json_success( "Mapeo actualizado" );
	}

	/**
	 * AJAX Handler para resetear mapeos
	 */
	public function ajax_reset_field_mappings() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		Field_Mapper::reset_to_defaults();
		$this->logger->log( 'SUCCESS', 'Todos los mapeos de campos reseteados a valores por defecto' );

		wp_send_json_success( 'Mapeos reseteados' );
	}

	/**
	 * AJAX Handler para guardar configuración de template con metatags
	 */
	public function ajax_save_template_config_old() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
		$config = isset( $_POST['config'] ) ? json_decode( stripslashes( $_POST['config'] ), true ) : array();
		$apply_to_products = isset( $_POST['apply_to_products'] ) ? rest_sanitize_boolean( $_POST['apply_to_products'] ) : false;

		if ( ! $template_id ) {
			wp_send_json_error( 'Template ID es requerido' );
		}

		// Guardar configuración en Template Manager
		if ( $this->template_manager->save_config( $template_id, $config ) ) {
			// Si se solicita, aplicar parámetros a productos de WooCommerce
			if ( $apply_to_products && isset( $config['settings'] ) ) {
				$this->apply_template_settings_to_products( $config['settings'] );
			}

			$this->logger->log( 'SUCCESS', "Template '{$template_id}' configuración guardada" );
			wp_send_json_success( array(
				'message' => 'Configuración guardada exitosamente',
				'template_id' => $template_id,
			) );
		} else {
			wp_send_json_error( 'Error al guardar configuración' );
		}
	}

	/**
	 * Aplicar settings de template a productos de WooCommerce como metatags
	 *
	 * @param array $settings Configuración de settings
	 */
	private function apply_template_settings_to_products( $settings ) {
		if ( empty( $settings ) ) {
			return;
		}

		// Obtener todos los productos de WooCommerce
		$products = get_posts( array(
			'post_type'      => 'product',
			'numberposts'    => -1,
			'posts_per_page' => -1,
		) );

		foreach ( $products as $product_post ) {
			$product_id = $product_post->ID;

			// Guardar precio por defecto si no existe precio
			if ( isset( $settings['default_price'] ) && $settings['default_price'] > 0 ) {
				$current_price = get_post_meta( $product_id, '_price', true );
				if ( empty( $current_price ) ) {
					update_post_meta( $product_id, '_price', $settings['default_price'] );
					update_post_meta( $product_id, '_regular_price', $settings['default_price'] );
				}
			}

			// Guardar imagen por defecto si no existe imagen
			if ( isset( $settings['default_image'] ) && ! empty( $settings['default_image'] ) ) {
				$image_id = get_post_thumbnail_id( $product_id );
				if ( empty( $image_id ) ) {
					$attachment_id = $this->get_or_create_attachment( $settings['default_image'] );
					if ( $attachment_id ) {
						set_post_thumbnail( $product_id, $attachment_id );
					}
				}
			}

			// Guardar configuración de visibilidad como metatags
			if ( isset( $settings['show_category'] ) ) {
				update_post_meta( $product_id, '_moodle_show_category', rest_sanitize_boolean( $settings['show_category'] ) ? '1' : '0' );
			}
			if ( isset( $settings['show_price'] ) ) {
				update_post_meta( $product_id, '_moodle_show_price', rest_sanitize_boolean( $settings['show_price'] ) ? '1' : '0' );
			}
			if ( isset( $settings['show_meta'] ) ) {
				update_post_meta( $product_id, '_moodle_show_meta', rest_sanitize_boolean( $settings['show_meta'] ) ? '1' : '0' );
			}

			// Guardar textos de botones
			if ( isset( $settings['button_text'] ) ) {
				update_post_meta( $product_id, '_moodle_button_text', sanitize_text_field( $settings['button_text'] ) );
			}
			if ( isset( $settings['button_text_enroll'] ) ) {
				update_post_meta( $product_id, '_moodle_button_text_enroll', sanitize_text_field( $settings['button_text_enroll'] ) );
			}

			// Guardar layout y columnas
			if ( isset( $settings['layout'] ) ) {
				update_post_meta( $product_id, '_moodle_layout', sanitize_text_field( $settings['layout'] ) );
			}
			if ( isset( $settings['columns'] ) ) {
				update_post_meta( $product_id, '_moodle_columns', intval( $settings['columns'] ) );
			}
		}

		$this->logger->log( 'SUCCESS', "Template settings aplicados a " . count( $products ) . " productos(s)" );
	}

	/**
	 * Obtener o crear attachment desde URL
	 *
	 * @param string $image_url URL de imagen
	 * @return int|bool ID del attachment o false
	 */
	private function get_or_create_attachment( $image_url ) {
		if ( empty( $image_url ) ) {
			return false;
		}

		// Descargar imagen
		$tmp = download_url( $image_url );
		if ( is_wp_error( $tmp ) ) {
			return false;
		}

		// Crear attachment
		$file_array = array(
			'name'     => basename( $image_url ),
			'tmp_name' => $tmp,
		);

		$id = media_handle_sideload( $file_array, 0 );

		if ( is_wp_error( $id ) ) {
			@unlink( $tmp );
			return false;
		}

		return $id;
	}

	/**
	 * AJAX Handler para guardar metadatos
	 */
	public function ajax_save_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$metadata = isset( $_POST['metadata'] ) ? json_decode( sanitize_text_field( $_POST['metadata'] ), true ) : array();

		if ( ! is_array( $metadata ) ) {
			wp_send_json_error( 'Formato inválido' );
		}

		update_option( 'woo_otec_moodle_metadata_enabled', $metadata );
		$this->logger->log( 'SUCCESS', 'Metadatos guardados' );

		wp_send_json_success( 'Metadatos guardiados' );
	}

	/**
	 * AJAX Handler para resetear metadatos
	 */
	public function ajax_reset_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		delete_option( 'woo_otec_moodle_metadata_enabled' );
		$this->logger->log( 'SUCCESS', 'Metadatos reseteados' );

		wp_send_json_success( 'Metadatos reseteados' );
	}

	/**
	 * AJAX Handler para preview de template
	 */
	public function ajax_preview_template() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
		$config = isset( $_POST['config'] ) ? json_decode( sanitize_text_field( $_POST['config'] ), true ) : array();

		if ( empty( $template ) ) {
			wp_send_json_error( 'Template requerido' );
		}

		// Generar preview HTML
		$preview_html = '<div style="padding: 20px; text-align: center;">';
		$preview_html .= '<p style="color: ' . ( $config['colors']['text'] ?? '#333' ) . '; font-size: 16px; margin: 0 0 10px;">';
		$preview_html .= esc_html( $config['texts']['title'] ?? 'Vista Previa' );
		$preview_html .= '</p>';
		$preview_html .= '<button style="background: ' . ( $config['colors']['primary'] ?? '#6366f1' ) . '; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">';
		$preview_html .= esc_html( $config['texts']['button_label'] ?? 'Botón' );
		$preview_html .= '</button>';
		$preview_html .= '</div>';

		wp_send_json_success( $preview_html );
	}

	/**
	 * AJAX Handler para guardar configuración de template
	 */
	public function ajax_save_template_config() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
		$config = isset( $_POST['config'] ) ? json_decode( sanitize_text_field( $_POST['config'] ), true ) : array();

		if ( empty( $template ) ) {
			wp_send_json_error( 'Template requerido' );
		}

		// Sanitizar y guardar
		$saved_config = array(
			'colors' => array(
				'primary'      => isset( $config['colors']['primary'] ) ? sanitize_hex_color( $config['colors']['primary'] ) : '#6366f1',
				'text'         => isset( $config['colors']['text'] ) ? sanitize_hex_color( $config['colors']['text'] ) : '#1f2937',
				'text_light'   => isset( $config['colors']['text_light'] ) ? sanitize_hex_color( $config['colors']['text_light'] ) : '#6b7280',
				'border'       => isset( $config['colors']['border'] ) ? sanitize_hex_color( $config['colors']['border'] ) : '#e5e7eb',
			),
			'texts'  => array(
				'title'        => isset( $config['texts']['title'] ) ? sanitize_text_field( $config['texts']['title'] ) : 'Cursos Disponibles',
				'button_label' => isset( $config['texts']['button_label'] ) ? sanitize_text_field( $config['texts']['button_label'] ) : 'Seleccionar',
				'cart_label'   => isset( $config['texts']['cart_label'] ) ? sanitize_text_field( $config['texts']['cart_label'] ) : 'Ir al Carrito',
			),
		);

		update_option( 'wom_template_config_' . $template, $saved_config );
		$this->logger->log( 'SUCCESS', "Configuración de template '$template' guardada" );

		wp_send_json_success( 'Configuración guardada' );
	}

	/**
	 * AJAX Handler para resetear template a predeterminados
	 */
	public function ajax_reset_template() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';

		if ( empty( $template ) ) {
			wp_send_json_error( 'Template requerido' );
		}

		delete_option( 'wom_template_config_' . $template );
		$this->logger->log( 'SUCCESS', "Template '$template' reseteado a predeterminados" );

		wp_send_json_success( 'Template reseteado' );
	}
}

