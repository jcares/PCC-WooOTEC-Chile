<?php
/**
 * Template Manager Class
 * 
 * Gestiona configuraciones de plantillas, valores por defecto y almacenamiento
 * de personalizaciones visuales.
 *
 * @package WOO_OTEC_Moodle
 * @subpackage Templates
 * @version 3.0.7
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Manager {

	/**
	 * Definición de plantillas disponibles
	 */
	private $templates = array(
		'product-catalogue' => array(
			'id'          => 'product-catalogue',
			'name'        => 'Catálogo de Productos',
			'description' => 'Grid de productos con metadatos seleccionados',
			'file'        => 'templates/template-product-catalogue.php',
		),
		'sample-product' => array(
			'id'          => 'sample-product',
			'name'        => 'Producto Individual',
			'description' => 'Vista detallada de un producto',
			'file'        => 'templates/template-sample-product.php',
		),
		'email' => array(
			'id'          => 'email',
			'name'        => 'Email de Matrícula',
			'description' => 'Correo que recibe el alumno al matricularse',
			'file'        => 'templates/template-email.php',
		),
	);

	/**
	 * Configuración por defecto
	 */
	private $defaults = array(
		'colors' => array(
			'primary'        => '#6366f1',
			'primary_hover'  => '#4f46e5',
			'text'           => '#1f2937',
			'text_light'     => '#6b7280',
			'button'         => '#0073aa',
			'button_hover'   => '#005a87',
			'border'         => '#e5e7eb',
			'background'     => '#ffffff',
		),
		'typography' => array(
			'primary_font'   => '-apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, sans-serif',
			'heading_size'   => '28px',
			'button_text_size' => '14px',
			'line_height'    => '1.6',
		),
		'settings' => array(
			'default_price'  => 0,
			'default_image'  => '',
			'show_category'  => true,
			'show_price'     => true,
			'show_meta'      => true,
			'button_text'    => 'Ver Curso',
			'button_text_enroll' => 'Matricularme',
			'layout'         => 'grid',
			'columns'        => 3,
			'image_size'     => '250px',
		),
	);

	/**
	 * Option key para almacenar configuraciones
	 */
	const OPTION_KEY = 'woo_otec_moodle_template_config';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Nota: Los hooks AJAX se registran desde Admin_Settings para evitar duplicación
		// add_action( 'wp_ajax_wom_save_template_config', array( $this, 'ajax_save_template_config' ) );
		// add_action( 'wp_ajax_wom_preview_template', array( $this, 'ajax_preview_template' ) );
		// add_action( 'wp_ajax_wom_reset_template', array( $this, 'ajax_reset_template' ) );
	}

	/**
	 * Obtiene template por ID
	 *
	 * @param string $template_id ID de la plantilla
	 * @return array
	 */
	public function get_template( $template_id ) {
		if ( ! isset( $this->templates[ $template_id ] ) ) {
			return array();
		}
		return $this->templates[ $template_id ];
	}

	/**
	 * Obtiene todas las plantillas disponibles
	 *
	 * @return array
	 */
	public function get_available_templates() {
		return $this->templates;
	}

	/**
	 * Obtiene configuración guardada de una plantilla
	 *
	 * @param string $template_id ID de la plantilla
	 * @return array
	 */
	public function get_saved_config( $template_id ) {
		$all_config = get_option( self::OPTION_KEY, array() );

		if ( ! isset( $all_config[ $template_id ] ) ) {
			return $this->get_template_defaults( $template_id );
		}

		// Merge con defaults para valores faltantes
		return wp_parse_args(
			$all_config[ $template_id ],
			$this->get_template_defaults( $template_id )
		);
	}

	/**
	 * Obtiene valores por defecto de una plantilla
	 *
	 * @param string $template_id ID de la plantilla
	 * @return array
	 */
	public function get_template_defaults( $template_id = null ) {
		if ( null === $template_id ) {
			return $this->defaults;
		}

		// Todos los templates usan los mismos defaults
		return $this->defaults;
	}

	/**
	 * Guarda configuración de template
	 *
	 * @param string $template_id ID de la plantilla
	 * @param array  $config Configuration array
	 * @return bool
	 */
	public function save_config( $template_id, $config ) {
		if ( ! isset( $this->templates[ $template_id ] ) ) {
			return false;
		}

		// Validar configuración
		$config = $this->validate_config( $config );

		// Obtener configuración actual
		$all_config = get_option( self::OPTION_KEY, array() );

		// Guardar nueva configuración
		$all_config[ $template_id ] = $config;
		$all_config[ $template_id ]['last_modified'] = time();

		return update_option( self::OPTION_KEY, $all_config );
	}

	/**
	 * Valida configuración antes de guardar
	 *
	 * @param array $config Configuration array
	 * @return array Configuración validada
	 */
	public function validate_config( $config ) {
		$validated = array();

		// Validar colores (formato hex)
		if ( isset( $config['colors'] ) && is_array( $config['colors'] ) ) {
			$validated['colors'] = array();
			foreach ( $config['colors'] as $key => $value ) {
				if ( $this->is_valid_hex_color( $value ) ) {
					$validated['colors'][ $key ] = sanitize_hex_color( $value );
				}
			}
		}

		// Validar typography
		if ( isset( $config['typography'] ) && is_array( $config['typography'] ) ) {
			$validated['typography'] = array();
			foreach ( $config['typography'] as $key => $value ) {
				$validated['typography'][ $key ] = sanitize_text_field( $value );
			}
		}

		// Validar settings
		if ( isset( $config['settings'] ) && is_array( $config['settings'] ) ) {
			$validated['settings'] = array();
			foreach ( $config['settings'] as $key => $value ) {
				if ( in_array( $key, array( 'default_price', 'columns' ), true ) ) {
					$validated['settings'][ $key ] = intval( $value );
				} elseif ( in_array( $key, array( 'show_category', 'show_price', 'show_meta' ), true ) ) {
					$validated['settings'][ $key ] = rest_sanitize_boolean( $value );
				} elseif ( 'default_image' === $key ) {
					$validated['settings'][ $key ] = esc_url_raw( $value );
				} else {
					$validated['settings'][ $key ] = sanitize_text_field( $value );
				}
			}
		}

		return $validated;
	}

	/**
	 * Valida si es un color hex válido
	 *
	 * @param string $color Color value
	 * @return bool
	 */
	private function is_valid_hex_color( $color ) {
		return preg_match( '/^#[a-fA-F0-9]{6}$/', $color ) === 1;
	}

	/**
	 * Genera CSS variables a partir de configuración
	 *
	 * @param string $template_id ID de la plantilla
	 * @param array  $config Configuration array
	 * @return string CSS
	 */
	public function export_css_variables( $template_id, $config = array() ) {
		if ( empty( $config ) ) {
			$config = $this->get_saved_config( $template_id );
		}

		$css = ':root {';

		// Colores
		if ( isset( $config['colors'] ) ) {
			foreach ( $config['colors'] as $key => $value ) {
				$css .= "\n  --wom-{$key}: {$value};";
			}
		}

		// Typography
		if ( isset( $config['typography'] ) ) {
			foreach ( $config['typography'] as $key => $value ) {
				$css .= "\n  --wom-{$key}: {$value};";
			}
		}

		// Settings
		if ( isset( $config['settings'] ) ) {
			if ( isset( $config['settings']['image_size'] ) ) {
				$css .= "\n  --wom-image-size: {$config['settings']['image_size']};";
			}
			if ( isset( $config['settings']['columns'] ) ) {
				$css .= "\n  --wom-columns: {$config['settings']['columns']};";
			}
		}

		$css .= "\n}";

		return $css;
	}

	/**
	 * Resetea template a valores por defecto
	 *
	 * @param string $template_id ID de la plantilla
	 * @return bool
	 */
	public function reset_template( $template_id ) {
		$all_config = get_option( self::OPTION_KEY, array() );

		if ( isset( $all_config[ $template_id ] ) ) {
			unset( $all_config[ $template_id ] );
			return update_option( self::OPTION_KEY, $all_config );
		}

		return false;
	}

	/**
	 * AJAX Handler: Guardar configuración de template
	 */
	public function ajax_save_template_config() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
		$config_json = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '{}';
		$config      = json_decode( $config_json, true );
		
		if ( ! is_array( $config ) ) {
			$config = array();
		}

		if ( ! $template_id || ! isset( $this->templates[ $template_id ] ) ) {
			wp_send_json_error( 'Template inválido' );
		}

		if ( $this->save_config( $template_id, $config ) ) {
			wp_send_json_success( array(
				'message' => 'Configuración guardada exitosamente',
				'template_id' => $template_id,
			) );
		} else {
			wp_send_json_error( 'Error al guardar configuración' );
		}
	}

	/**
	 * AJAX Handler: Preview de template
	 */
	public function ajax_preview_template() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
		$config      = isset( $_POST['config'] ) ? json_decode( stripslashes( $_POST['config'] ), true ) : array();
		$product_id  = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : null;

		if ( ! $template_id || ! isset( $this->templates[ $template_id ] ) ) {
			wp_send_json_error( 'Template inválido' );
		}

		// Generar preview
		$preview_generator = new \Woo_OTEC_Moodle\Preview_Generator( $this );
		$html = $preview_generator->generate_preview( $template_id, $config, $product_id );

		wp_send_json_success( array(
			'html' => $html,
		) );
	}

	/**
	 * AJAX Handler: Resetear template
	 */
	public function ajax_reset_template() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';

		if ( ! $template_id || ! isset( $this->templates[ $template_id ] ) ) {
			wp_send_json_error( 'Template inválido' );
		}

		if ( $this->reset_template( $template_id ) ) {
			wp_send_json_success( array(
				'message' => 'Template restaurado a valores por defecto',
				'defaults' => $this->get_template_defaults(),
			) );
		} else {
			wp_send_json_error( 'Error al resetear template' );
		}
	}
}
