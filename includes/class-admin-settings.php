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
	 * Constructor.
	 * 
	 * @param API_Client $api_client Cliente de API de Moodle.
	 * @param Logger $logger Gestor de logs.
	 * @param ?Metadata_Manager $metadata_manager Gestor de metadatos (opcional).
	 * @param ?Template_Manager $template_manager Gestor de plantillas (opcional).
	 * @param ?Template_Customizer $template_customizer Personalizador de plantillas (opcional).
	 * @param ?Preview_Generator $preview_generator Generador de previews (opcional).
	 */
	public function __construct( 
		API_Client $api_client, 
		Logger $logger, 
		?Metadata_Manager $metadata_manager = null, 
		?Template_Manager $template_manager = null, 
		?Template_Customizer $template_customizer = null, 
		?Preview_Generator $preview_generator = null 
	) {
		$this->api_client       = $api_client;
		$this->logger           = $logger;
		$this->metadata_manager = $metadata_manager;

		// Inicializar Template Managers con lazy loading si no se proporcionan
		$this->template_manager   = $template_manager ?? new Template_Manager();
		$this->template_customizer = $template_customizer ?? new Template_Customizer( $this->template_manager );
		$this->preview_generator   = $preview_generator ?? new Preview_Generator( $this->template_manager );

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

		wp_enqueue_style( 'woo-otec-moodle-admin-style', WOO_OTEC_MOODLE_URL . 'admin/css/admin-style.css', array(), WOO_OTEC_MOODLE_VERSION );
		wp_enqueue_style( 'woo-otec-moodle-admin-forms', WOO_OTEC_MOODLE_URL . 'admin/css/admin-forms.css', array(), WOO_OTEC_MOODLE_VERSION );
		wp_enqueue_script( 'woo-otec-moodle-admin-js', WOO_OTEC_MOODLE_URL . 'admin/js/admin-app.js', array( 'jquery' ), WOO_OTEC_MOODLE_VERSION, true );

		// Template Builder Assets
		if ( strpos( $hook, 'template-builder' ) !== false ) {
			wp_enqueue_style( 'wom-template-builder-style', WOO_OTEC_MOODLE_URL . 'admin/css/template-builder.css', array(), WOO_OTEC_MOODLE_VERSION );
			wp_enqueue_script( 'wom-template-builder-js', WOO_OTEC_MOODLE_URL . 'admin/js/template-builder.js', array( 'jquery', 'wp-color-picker' ), WOO_OTEC_MOODLE_VERSION, true );
			wp_enqueue_style( 'wp-color-picker' );
		}

		wp_enqueue_media();

		// Objeto global para TODOS los scripts del plugin
		$woo_otec_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'woo-otec-moodle-nonce' ),
		);

		// UNA sola localización para admin-app.js (será disponible globalmente)
		wp_localize_script( 'woo-otec-moodle-admin-js', 'wooOtecMoodle', $woo_otec_data );

		// Template Builder: localizar solo si está activo
		if ( strpos( $hook, 'template-builder' ) !== false ) {
			// Reutilizar el mismo objeto global en template-builder.js
			wp_localize_script( 'wom-template-builder-js', 'wooOtecMoodle', $woo_otec_data );
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
		try {
			// Validar nonce
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'woo-otec-moodle-nonce' ) ) {
				wp_send_json_error( 'Verificación de seguridad fallida' );
				die();
			}

			// Verificar permisos
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'No autorizado' );
				die();
			}

			// Obtener ID del producto
			$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

			if ( ! $product_id ) {
				wp_send_json_error( 'ID de producto requerido' );
				die();
			}

			// Verificar que el producto existe
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				wp_send_json_error( 'Producto no encontrado' );
				die();
			}

			// Generar preview HTML
			ob_start();
			
			?>
			<div style="background: #fff; padding: 16px; border-radius: 4px;">
				<h3 style="margin: 0 0 12px; font-weight: 600;"><?php echo esc_html( $product->get_name() ); ?></h3>
				
				<?php if ( $product->get_price() ) : ?>
					<p style="margin: 0 0 12px; font-weight: 500;">
						<strong>Precio:</strong> <?php echo wp_kses_post( $product->get_price_html() ); ?>
					</p>
				<?php endif; ?>

				<?php if ( $product->get_description() ) : ?>
					<p style="margin: 0 0 12px; color: #666; line-height: 1.5;">
						<?php echo wp_kses_post( wp_trim_words( $product->get_description(), 50 ) ); ?>
					</p>
				<?php endif; ?>

				<?php 
					$moodle_id = get_post_meta( $product_id, '_moodle_course_id', true );
					if ( $moodle_id ) : 
				?>
					<p style="margin: 0; font-size: 12px; color: #999;">
						<strong>ID Moodle:</strong> <?php echo esc_html( $moodle_id ); ?>
					</p>
				<?php endif; ?>
			</div>
			<?php

			$preview_html = ob_get_clean();

			// Responder con JSON
			wp_send_json_success( array(
				'title' => $product->get_name(),
				'html'  => $preview_html
			) );
			die();

		} catch ( Exception $e ) {
			wp_send_json_error( 'Error: ' . $e->getMessage() );
			die();
		}
	}

	/**
	 * AJAX Handler para guardar metadatos
	 */
	public function ajax_save_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$metadata = isset( $_POST['metadata'] ) ? json_decode( wp_unslash( $_POST['metadata'] ), true ) : array();

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

	/**
	 * AJAX Handler para guardar intervalo de CRON
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
	 * AJAX Handler para preview de template
	 */
	public function ajax_preview_template() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		try {
			// Aceptar tanto "template" como "template_id" para compatibilidad
			$template = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
			if ( empty( $template ) ) {
				$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
			}

			$config = isset( $_POST['config'] ) ? json_decode( wp_unslash( $_POST['config'] ), true ) : array();
			$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : null;

			if ( empty( $template ) ) {
				wp_send_json_error( 'Template requerido' );
				die();
			}

			// Validar que tenemos template_manager
			if ( ! $this->template_manager ) {
				$this->template_manager = new \Woo_OTEC_Moodle\Template_Manager();
			}

			// Validar que preview_generator existe
			if ( ! $this->preview_generator ) {
				$this->preview_generator = new \Woo_OTEC_Moodle\Preview_Generator( $this->template_manager );
			}

			// Si config está vacía, obtener configuración guardada
			if ( empty( $config ) ) {
				$config = $this->template_manager->get_saved_config( $template );
			}

			// Generar preview usando Preview_Generator
			$preview_html = $this->preview_generator->generate_preview( $template, $config, $product_id );

			if ( empty( $preview_html ) ) {
				wp_send_json_error( 'No se pudo generar el preview' );
				die();
			}

			wp_send_json_success( array( 'html' => $preview_html ) );
			die();

		} catch ( \Exception $e ) {
			$this->logger->log( 'ERROR', 'ajax_preview_template error: ' . $e->getMessage() );
			wp_send_json_error( 'Error: ' . $e->getMessage() );
			die();
		}
	}

	/**
	 * AJAX Handler para guardar configuración de template
	 */
	public function ajax_save_template_config() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
			die();
		}

		$template = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
		if ( empty( $template ) ) {
			$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
		}

		if ( empty( $template ) ) {
			wp_send_json_error( 'Template requerido' );
			die();
		}

		$config_raw = isset( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : '{}';
		$config = json_decode( $config_raw, true );
		
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		// Preparar configuración sanitizada: solo guardar lo que viene
		$saved_config = array();
		
		// Colores
		if ( isset( $config['colors'] ) && is_array( $config['colors'] ) ) {
			$saved_config['colors'] = array();
			foreach ( $config['colors'] as $color_key => $color_value ) {
				$saved_config['colors'][ $color_key ] = sanitize_hex_color( $color_value ) ?: '#000000';
			}
		} else {
			$saved_config['colors'] = array();
		}

		// Settings
		if ( isset( $config['settings'] ) && is_array( $config['settings'] ) ) {
			$saved_config['settings'] = array();
			foreach ( $config['settings'] as $setting_key => $setting_value ) {
				$saved_config['settings'][ $setting_key ] = sanitize_text_field( $setting_value );
			}
		} else {
			$saved_config['settings'] = array();
		}

		// Guardar en BD
		update_option( 'wom_template_config_' . $template, $saved_config );
		$this->logger->log( 'SUCCESS', "Configuración de template '$template' guardada: " . json_encode( $saved_config ) );

		wp_send_json_success( array(
			'message' => 'Configuración guardada',
			'config' => $saved_config
		) );
		die();
	}

	/**
	 * AJAX Handler para resetear template a predeterminados
	 */
	public function ajax_reset_template() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
			die();
		}

		$template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';

		if ( empty( $template ) ) {
			wp_send_json_error( 'Template requerido' );
			die();
		}

		delete_option( 'wom_template_config_' . $template );
		$this->logger->log( 'SUCCESS', "Template '$template' reseteado a predeterminados" );

		wp_send_json_success( 'Template reseteado' );
		die();
	}
}

