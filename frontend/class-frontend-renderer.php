<?php
/**
 * Renderizador de Cursos para Frontend
 * 
 * Responsabilidades:
 * - Obtener cursos y metadatos configurados
 * - Aplicar transformaciones visuales
 * - Renderizar en frontend (shortcode, hooks, etc)
 * - Manejar responsive design y CSS
 * 
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Frontend_Renderer {

	/**
	 * Metadata Manager
	 */
	private $metadata_manager;

	/**
	 * Logger
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct( $metadata_manager, $logger ) {
		$this->metadata_manager = $metadata_manager;
		$this->logger           = $logger;

		// Registrar shortcode
		add_shortcode( 'moodle_courses', array( $this, 'render_courses_shortcode' ) );

		// Enqueue styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueued estilos y scripts del frontend
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'wom-frontend-style', WOO_OTEC_MOODLE_URL . 'frontend/css/courses.css', array(), WOO_OTEC_MOODLE_VERSION );
		wp_enqueue_style( 'wom-template-shortcodes', WOO_OTEC_MOODLE_URL . 'frontend/css/template-shortcodes.css', array(), WOO_OTEC_MOODLE_VERSION );
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'wom-template-shortcodes', WOO_OTEC_MOODLE_URL . 'frontend/js/template-shortcodes.js', array( 'jquery' ), WOO_OTEC_MOODLE_VERSION, true );
		
		wp_localize_script( 'wom-template-shortcodes', 'wooOtecMoodle', array(
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'woo-otec-moodle-nonce' ),
			'is_user_logged_in'  => is_user_logged_in(),
		) );
	}

	/**
	 * Renderizar cursos vía shortcode [moodle_courses]
	 * 
	 * @param array $atts Atributos del shortcode
	 * @return string HTML renderizado
	 */
	public function render_courses_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'     => 12,
				'columns'   => 3,
				'orderby'   => 'id',
				'order'     => 'ASC',
			),
			$atts,
			'moodle_courses'
		);

		return $this->render_courses_grid( $atts );
	}

	/**
	 * Renderizar grid de cursos usando plantilla personalizada
	 * 
	 * @param array $args Argumentos de renderizado
	 * @return string HTML
	 */
	private function render_courses_grid( $args ) {
		// Obtener cursos cacheados
		$courses = $this->metadata_manager->get_cached_courses();

		if ( is_wp_error( $courses ) ) {
			$this->logger->log( 'ERROR', 'Error en render frontend: ' . $courses->get_error_message() );
			return $this->render_error_message( 'No se pudieron cargar los cursos. Intenta más tarde.' );
		}

		if ( empty( $courses ) ) {
			return $this->render_empty_message( 'No hay cursos disponibles en este momento.' );
		}

		// Obtener configuración de campos
		$configured_fields = $this->metadata_manager->get_configured_fields();
		$metadata_structure = $this->metadata_manager->get_available_metadata();

		// Obtener configuración del plugin
		$config = get_option( 'wom_template_config', array() );
		if ( empty( $config ) ) {
			$config = array(
				'settings' => array(
					'show_price'    => true,
					'show_category' => true,
					'show_meta'     => true,
					'button_text'   => 'Ver Curso',
					'default_price' => '0',
				),
			);
		}

		// Aplicar límite y ordenamiento
		$courses = array_slice( $courses, 0, (int) $args['limit'] );

		// Usar output buffering para capturar la plantilla
		ob_start();
		
		// Variables disponibles para la template
		$template_path = WOO_OTEC_MOODLE_PATH . 'templates/template-product-catalogue.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<p>Plantilla no encontrada. Contacta al soporte técnico.</p>';
		}

		$html = ob_get_clean();
		
		// Agregar estilos
		$html = $this->add_inline_styles() . $html;

		return $html;
	}

	/**
	 * Renderizar una tarjeta de curso individual
	 * 
	 * @param array $course Datos del curso
	 * @param array $fields Campos configurados a mostrar
	 * @param array $metadata_structure Estructura de metadatos
	 * @return string HTML de tarjeta
	 */
	private function render_course_card( $course, $fields, $metadata_structure ) {
		$html = '<div class="wom-course-card">';
		
		// Encabezado con título
		if ( in_array( 'fullname', $fields, true ) ) {
			$html .= '<div class="wom-card-header">';
			$html .= '<h3 class="wom-course-title">' . esc_html( $course['fullname'] ) . '</h3>';
			$html .= '</div>';
		}

		// Contenido principal
		$html .= '<div class="wom-card-body">';

		// Descripción
		if ( in_array( 'summary', $fields, true ) && ! empty( $course['summary'] ) ) {
			$summary = wp_trim_words( $course['summary'], 20, '...' );
			$html .= '<p class="wom-course-description">' . wp_kses_post( $summary ) . '</p>';
		}

		// Metadatos secundarios en grid
		$secondary_fields = array_filter(
			$fields,
			function( $field ) {
				return ! in_array( $field, array( 'fullname', 'summary', 'id' ), true );
			}
		);

		if ( ! empty( $secondary_fields ) ) {
			$html .= '<div class="wom-metadata-grid">';

			foreach ( $secondary_fields as $field ) {
				$html .= $this->render_metadata_field( $course, $field, $metadata_structure );
			}

			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Renderizar un campo de metadato individual
	 * 
	 * @param array $course Datos del curso
	 * @param string $field_key Clave del campo
	 * @param array $metadata_structure Estructura de metadatos
	 * @return string HTML
	 */
	private function render_metadata_field( $course, $field_key, $metadata_structure ) {
		if ( ! isset( $course[ $field_key ] ) ) {
			return '';
		}

		$value = $course[ $field_key ];
		$definition = isset( $metadata_structure[ $field_key ] ) ? $metadata_structure[ $field_key ] : array();
		$label = isset( $definition['label'] ) ? $definition['label'] : ucfirst( str_replace( '_', ' ', $field_key ) );
		$type = isset( $definition['type'] ) ? $definition['type'] : 'text';

		// Ignorar campos vacíos o sin valor
		if ( empty( $value ) ) {
			return '';
		}

		$html = '<div class="wom-metadata-item" data-field="' . esc_attr( $field_key ) . '">';

		// Formatear valor según tipo
		$formatted_value = $this->format_metadata_value( $value, $type );

		$html .= '<span class="wom-metadata-label">' . esc_html( $label ) . ':</span> ';
		$html .= '<span class="wom-metadata-value">' . $formatted_value . '</span>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Formatear valor de metadato según su tipo
	 * 
	 * @param mixed $value Valor a formatear
	 * @param string $type Tipo de dato
	 * @return string Valor formateado
	 */
	private function format_metadata_value( $value, $type ) {
		switch ( $type ) {
			case 'date':
				if ( is_numeric( $value ) && (int) $value > 0 ) {
					return wp_date( 'd/m/Y', (int) $value );
				}
				return '—';

			case 'boolean':
				return $value ? 'Sí' : 'No';

			case 'number':
				return number_format( (int) $value, 0, ',', '.' );

			case 'textarea':
				return wp_kses_post( wp_trim_words( (string) $value, 30, '...' ) );

			case 'text':
			case 'custom':
			default:
				return esc_html( (string) $value );
		}
	}

	/**
	 * Agregar estilos inline para responsive
	 * 
	 * @return string CSS
	 */
	private function add_inline_styles() {
		static $styles_added = false;
		
		if ( $styles_added ) {
			return '';
		}

		$styles_added = true;

		$css = '
<style>
.wom-courses-container {
	width: 100%;
	padding: 20px 0;
}

.wom-courses-grid {
	display: grid;
	grid-template-columns: repeat(var(--columns, 3), 1fr);
	gap: 20px;
	width: 100%;
}

@media (max-width: 1024px) {
	.wom-courses-grid {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 640px) {
	.wom-courses-grid {
		grid-template-columns: 1fr;
	}
}

.wom-course-card {
	background: #fff;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	transition: all 0.3s ease;
	display: flex;
	flex-direction: column;
	height: 100%;
}

.wom-course-card:hover {
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	transform: translateY(-2px);
}

.wom-card-header {
	background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
	color: white;
	padding: 16px;
	border-bottom: 1px solid #004a73;
}

.wom-course-title {
	margin: 0;
	font-size: 18px;
	font-weight: 600;
	color: #fff;
	line-height: 1.3;
}

.wom-card-body {
	padding: 16px;
	flex-grow: 1;
	display: flex;
	flex-direction: column;
}

.wom-course-description {
	margin: 0 0 12px 0;
	font-size: 14px;
	color: #555;
	line-height: 1.5;
}

.wom-metadata-grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 8px;
	margin-top: auto;
	padding-top: 12px;
	border-top: 1px solid #f0f0f0;
}

.wom-metadata-item {
	font-size: 13px;
	color: #666;
	display: flex;
	align-items: baseline;
	gap: 8px;
}

.wom-metadata-label {
	font-weight: 500;
	color: #333;
	min-width: 100px;
	flex-shrink: 0;
}

.wom-metadata-value {
	color: #0073aa;
	word-break: break-word;
}

.wom-error-message,
.wom-empty-message {
	padding: 20px;
	border-radius: 8px;
	text-align: center;
	font-size: 16px;
}

.wom-error-message {
	background: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
}

.wom-empty-message {
	background: #d1ecf1;
	color: #0c5460;
	border: 1px solid #bee5eb;
}
</style>
		';

		return $css;
	}

	/**
	 * Renderizar mensaje de error
	 * 
	 * @param string $message Mensaje a mostrar
	 * @return string HTML
	 */
	private function render_error_message( $message ) {
		return '<div class="wom-error-message"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Renderizar mensaje de vacío
	 * 
	 * @param string $message Mensaje a mostrar
	 * @return string HTML
	 */
	private function render_empty_message( $message ) {
		return '<div class="wom-empty-message"><p>' . esc_html( $message ) . '</p></div>';
	}
}
