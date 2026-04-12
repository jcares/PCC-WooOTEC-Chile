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
	public function generate_custom_css( $template_id, $config = array() ) {
		if ( empty( $config ) ) {
			$config = $this->template_manager->get_saved_config( $template_id );
		}

		$css = '';

		// CSS Variables (base)
		$css .= $this->template_manager->export_css_variables( $template_id, $config );

		// CSS Customizations basadas en template
		if ( 'product-catalogue' === $template_id || 'sample-product' === $template_id ) {
			$css .= $this->generate_product_css( $config );
		} elseif ( 'email' === $template_id ) {
			$css .= $this->generate_email_css( $config );
		}

		return $css;
	}

	/**
	 * Genera CSS para templates de productos
	 *
	 * @param array $config Configuración
	 * @return string CSS code
	 */
	private function generate_product_css( $config ) {
		$css = "\n\n/* Product Template Custom Styles */\n";

		// Card styling
		$css .= ".wom-product-card {
  border-color: var(--wom-border);
  background: var(--wom-background);
  transition: all 0.3s ease;
}\n";

		$css .= ".wom-product-card:hover {
  border-color: var(--wom-primary);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
}\n";

		// Image styling
		if ( isset( $config['settings']['image_size'] ) ) {
			$css .= ".wom-product-image {
  height: var(--wom-image-size);
  width: 100%;
  object-fit: cover;
}\n";
		}

		// Text colors
		$css .= ".wom-product-name {
  color: var(--wom-text);
  font-size: 18px;
  font-weight: 600;
}\n";

		$css .= ".wom-product-description {
  color: var(--wom-text-light);
  font-size: 14px;
  line-height: var(--wom-line-height);
}\n";

		$css .= ".wom-product-category {
  color: var(--wom-primary);
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}\n";

		$css .= ".wom-product-price {
  color: var(--wom-primary);
  font-size: 20px;
  font-weight: 700;
}\n";

		// Button styling
		$css .= ".wom-btn {
  background: var(--wom-button);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 10px 20px;
  font-size: var(--wom-button-text-size);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: var(--wom-primary-font);
}\n";

		$css .= ".wom-btn:hover {
  background: var(--wom-button-hover);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}\n";

		// Grid layout
		$css .= ".wom-courses-grid {
  display: grid;
  grid-template-columns: repeat(var(--wom-columns), 1fr);
  gap: 20px;
  margin-top: 20px;
}\n";

		$css .= "@media (max-width: 768px) {
  .wom-courses-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}\n";

		$css .= "@media (max-width: 480px) {
  .wom-courses-grid {
    grid-template-columns: 1fr;
  }
}\n";

		return $css;
	}

	/**
	 * Genera CSS para template de email
	 *
	 * @param array $config Configuración
	 * @return string CSS code
	 */
	private function generate_email_css( $config ) {
		$css = "\n\n/* Email Template Custom Styles */\n";

		$css .= ".wom-email-header {
  background: linear-gradient(135deg, var(--wom-primary) 0%, var(--wom-primary-hover) 100%);
  color: white;
  padding: 40px 20px;
  text-align: center;
}\n";

		$css .= ".wom-email-content {
  color: var(--wom-text);
  font-family: var(--wom-primary-font);
  line-height: var(--wom-line-height);
}\n";

		$css .= ".wom-email-courses-list {
  background: #f9fafb;
  border-left: 4px solid var(--wom-primary);
  padding: 15px;
  margin: 15px 0;
}\n";

		$css .= ".wom-email-course-item {
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
}\n";

		$css .= ".wom-email-course-item:last-child {
  border-bottom: none;
}\n";

		$css .= ".wom-email-button {
  background: var(--wom-button);
  color: white;
  padding: 12px 30px;
  text-decoration: none;
  border-radius: 6px;
  display: inline-block;
  font-weight: 600;
  margin: 20px 0;
}\n";

		$css .= ".wom-email-footer {
  color: var(--wom-text-light);
  font-size: 12px;
  border-top: 1px solid '#e5e7eb';
  padding-top: 20px;
  text-align: center;
}\n";

		return $css;
	}

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

		$css = $this->generate_custom_css( $template_id, $config );

		// Inyectar <style> en el HTML
		$html_with_styles = $html;
		if ( strpos( $html_with_styles, '<head>' ) !== false ) {
			$html_with_styles = str_replace(
				'<head>',
				'<head><style>' . $css . '</style>',
				$html_with_styles
			);
		} elseif ( strpos( $html_with_styles, '<body>' ) !== false ) {
			$html_with_styles = '<style>' . $css . '</style>' . $html_with_styles;
		} else {
			$html_with_styles = '<style>' . $css . '</style>' . $html_with_styles;
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
