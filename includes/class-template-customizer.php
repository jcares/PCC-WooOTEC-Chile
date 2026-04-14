<?php
/**
 * Template Customizer Class
 * 
 * Aplica personalizaciones visuales (colores, textos) a los templates
 *
 * @package WOO_OTEC_Moodle
 * @subpackage Templates
 * @version 3.0.7
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Customizer {

	/**
	 * Template Manager instance
	 *
	 * @var \Woo_OTEC_Moodle\Template_Manager
	 */
	private $template_manager;

	/**
	 * Constructor
	 *
	 * @param \Woo_OTEC_Moodle\Template_Manager $template_manager Instance
	 */
	public function __construct( $template_manager ) {
		$this->template_manager = $template_manager;
	}

	/**
	 * Genera CSS personalizado a partir de configuración
	 *
	 * @param string $template_id ID de la plantilla
	 * @param array  $config Configuración
	 * @return string CSS code
	 */
	/**
	 * Reemplaza textos en HTML según configuración
	 *
	 * @param string $html HTML code
	 * @param array  $config Configuración
	 * @return string HTML modificado
	 */
	public function apply_text_customization( $html, $config ) {
		if ( ! isset( $config['settings'] ) ) {
			return $html;
		}

		$settings = $config['settings'];

		// Reemplazar textos de botones
		if ( isset( $settings['button_text'] ) ) {
			$html = str_replace(
				'{{button_text}}',
				esc_html( $settings['button_text'] ),
				$html
			);
		}

		if ( isset( $settings['button_text_enroll'] ) ) {
			$html = str_replace(
				'{{button_text_enroll}}',
				esc_html( $settings['button_text_enroll'] ),
				$html
			);
		}

		return $html;
	}

	/**
	 * Obtiene HTML con estilos inyectados
	 *
	 * @param string $html HTML code
	 * @param string $template_id ID de la plantilla
	 * @param array  $config Configuración
	 * @return string HTML con estilos
	 */
	public function inject_styles( $html, $template_id, $config = array() ) {
		if ( empty( $config ) ) {
			$config = $this->template_manager->get_saved_config( $template_id );
		}

		// SOLO inyectar variables CSS, NO estilos completos
		$css_vars = $this->template_manager->export_css_variables( $template_id, $config );

		// Inyectar <style> solo con variables CSS
		$html_with_styles = $html;
		
		if ( strpos( $html_with_styles, '<head>' ) !== false ) {
			$html_with_styles = str_replace(
				'<head>',
				'<head><style>' . $css_vars . '</style>',
				$html_with_styles
			);
		} elseif ( strpos( $html_with_styles, '<body>' ) !== false ) {
			$html_with_styles = '<style>' . $css_vars . '</style>' . $html_with_styles;
		} else {
			$html_with_styles = '<style>' . $css_vars . '</style>' . $html_with_styles;
		}

		return $html_with_styles;
	}

	/**
	 * Valida valores de configuración
	 *
	 * @param array $config Configuración a validar
	 * @return array Configuración válida
	 */
	public function validate_configuration( $config ) {
		return $this->template_manager->validate_config( $config );
	}

	/**
	 * Genera atributos de estilo inline para elementos
	 *
	 * @param array $config Configuración
	 * @param string $element Elemento ('card', 'button', 'text')
	 * @return string Style attribute
	 */
	public function get_inline_styles( $config, $element = 'card' ) {
		$styles = array();

		if ( ! isset( $config['colors'] ) ) {
			return '';
		}

		$colors = $config['colors'];

		switch ( $element ) {
			case 'button':
				$styles[] = 'background-color: ' . esc_attr( $colors['button'] ) . ' !important';
				$styles[] = 'color: white !important';
				$styles[] = 'border: none !important';
				$styles[] = 'border-radius: 6px !important';
				break;

			case 'text':
				$styles[] = 'color: ' . esc_attr( $colors['text'] ) . ' !important';
				break;

			case 'title':
				$styles[] = 'color: ' . esc_attr( $colors['primary'] ) . ' !important';
				$styles[] = 'font-weight: 600 !important';
				break;

			case 'card':
			default:
				$styles[] = 'border: 1px solid ' . esc_attr( $colors['border'] ) . ' !important';
				$styles[] = 'background: ' . esc_attr( $colors['background'] ) . ' !important';
				break;
		}

		return implode( ';', $styles ) . ';';
	}
}
