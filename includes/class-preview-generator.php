<?php
/**
 * Preview Generator Class
 * 
 * Genera previews HTML de plantillas para visualización en tiempo real
 *
 * @package WOO_OTEC_Moodle
 * @subpackage Templates
 * @version 3.0.7
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Preview_Generator {

	/**
	 * Template Manager instance
	 *
	 * @var \Woo_OTEC_Moodle\Template_Manager
	 */
	private $template_manager;

	/**
	 * Template Customizer instance
	 *
	 * @var \Woo_OTEC_Moodle\Template_Customizer
	 */
	private $customizer;

	/**
	 * Constructor
	 *
	 * @param \Woo_OTEC_Moodle\Template_Manager $template_manager Instance
	 */
	public function __construct( $template_manager ) {
		$this->template_manager = $template_manager;
		$this->customizer       = new \Woo_OTEC_Moodle\Template_Customizer( $template_manager );
	}

	/**
	 * Genera preview HTML para una plantilla
	 *
	 * @param string $template_id ID de la plantilla
	 * @param array  $config Configuración
	 * @param int    $product_id ID del producto (opcional)
	 * @return string HTML preview
	 */
	public function generate_preview( $template_id, $config = array(), $product_id = null ) {
		if ( empty( $config ) ) {
			$config = $this->template_manager->get_saved_config( $template_id );
		}

		switch ( $template_id ) {
			case 'product-catalogue':
				return $this->generate_catalogue_preview( $config );

			case 'sample-product':
				return $this->generate_product_preview( $config, $product_id );

			case 'email':
				return $this->generate_email_preview( $config );

			default:
				return '<p>Template no reconocido</p>';
		}
	}

	/**
	 * Genera preview de catálogo de productos
	 *
	 * @param array $config Configuración
	 * @return string HTML
	 */
	private function generate_catalogue_preview( $config ) {
		$samples = $this->get_sample_products( 3 );

		$html = '<div class="wom-preview-container">';
		$html .= '<div class="wom-courses-grid">';

		foreach ( $samples as $product ) {
			$html .= $this->render_product_card( $product, $config );
		}

		$html .= '</div>';
		$html .= '</div>';

		// Inyectar CSS
		$html = $this->customizer->inject_styles( $html, 'product-catalogue', $config );

		return $html;
	}

	/**
	 * Genera preview de producto individual
	 *
	 * @param array $config Configuración
	 * @param int   $product_id ID del producto
	 * @return string HTML
	 */
	private function generate_product_preview( $config, $product_id = null ) {
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product_data = array(
					'name' => $product->get_name(),
					'description' => $product->get_description() ?: $product->get_short_description(),
					'category' => $this->get_product_category( $product ),
					'price' => $product->get_price(),
					'image' => wp_get_attachment_url( $product->get_image_id() ) ?: includes_url( 'images/media/default.png' ),
				);
			} else {
				$product_data = $this->get_sample_product();
			}
		} else {
			$product_data = $this->get_sample_product();
		}

		$html = '<div class="wom-preview-container-small">';
		$html .= $this->render_product_detail( $product_data, $config );
		$html .= '</div>';

		// Inyectar CSS
		$html = $this->customizer->inject_styles( $html, 'sample-product', $config );

		return $html;
	}

	/**
	 * Genera preview de email
	 *
	 * @param array $config Configuración
	 * @return string HTML
	 */
	private function generate_email_preview( $config ) {
		$courses = array(
			'Curso de JavaScript Avanzado',
			'React para Principiantes',
			'Node.js & Express',
		);

		$html = '<div class="wom-preview-email">';

		// Header
		$html .= '<div class="wom-email-header">';
		$html .= '<h2>¡Bienvenido a nuestros cursos!</h2>';
		$html .= '<p>Tu matrícula ha sido completada</p>';
		$html .= '</div>';

		// Content
		$html .= '<div class="wom-email-content">';

		$html .= '<p>Hola <strong>Juan Pérez</strong>,</p>';
		$html .= '<p>¡Gracias por tu inscripción! Ahora tienes acceso a los siguientes cursos:</p>';

		// Courses List
		$html .= '<div class="wom-email-courses-list">';
		foreach ( $courses as $course ) {
			$html .= '<div class="wom-email-course-item">';
			$html .= '✓ ' . esc_html( $course );
			$html .= '</div>';
		}
		$html .= '</div>';

		// CTA Button
		$html .= '<div class="wom-email-button-container">';
		$html .= '<a href="#" class="wom-email-button">';
		$html .= 'Acceder a mi Aula Virtual';
		$html .= '</a>';
		$html .= '</div>';

		$html .= '<p class="wom-email-contact">Si tienes alguna duda, contáctanos en support@example.com</p>';

		$html .= '</div>';

		// Footer
		$html .= '<div class="wom-email-footer">';
		$html .= '<p>© 2026 WOO-OTEC-MOODLE. Todos los derechos reservados.</p>';
		$html .= '</div>';

		$html .= '</div>';

		// Inyectar CSS
		$html = $this->customizer->inject_styles( $html, 'email', $config );

		return $html;
	}

	/**
	 * Renderiza una tarjeta de producto
	 *
	 * @param array $product Product data
	 * @param array $config Configuración
	 * @return string HTML
	 */
	private function render_product_card( $product, $config ) {
		$image_url = isset( $product['image'] ) ? $product['image'] : '';
		$name = isset( $product['name'] ) ? $product['name'] : 'Curso Ejemplo';
		$description = isset( $product['description'] ) ? $product['description'] : 'Descripción del curso';
		$category = isset( $product['category'] ) ? $product['category'] : 'Categoría';
		$price = isset( $product['price'] ) ? $product['price'] : '0';

		$button_text = isset( $config['settings']['button_text'] ) ? $config['settings']['button_text'] : 'Ver Curso';

		$html = '<div class="wom-product-card">';

		// Image
		if ( $image_url ) {
			$html .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $name ) . '" class="wom-product-image">';
		} else {
			$html .= '<div class="wom-product-image-placeholder">';
			$html .= '<span>Imagen del Curso</span>';
			$html .= '</div>';
		}

		// Content
		$html .= '<div class="wom-product-content">';

		// Category
		if ( isset( $config['settings']['show_category'] ) && $config['settings']['show_category'] ) {
			$html .= '<p class="wom-product-category">' . esc_html( $category ) . '</p>';
		}

		// Name
		$html .= '<h3 class="wom-product-name">' . esc_html( $name ) . '</h3>';

		// Description
		if ( isset( $config['settings']['show_meta'] ) && $config['settings']['show_meta'] ) {
			$html .= '<p class="wom-product-description">' . esc_html( substr( $description, 0, 80 ) ) . '...</p>';
		}

		// Price
		if ( isset( $config['settings']['show_price'] ) && $config['settings']['show_price'] ) {
			$html .= '<p class="wom-product-price">$' . esc_html( $price ) . '</p>';
		}

		// Button
		$html .= '<button class="wom-btn">' . esc_html( $button_text ) . '</button>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Renderiza detalle de producto
	 *
	 * @param array $product Product data
	 * @param array $config Configuración
	 * @return string HTML
	 */
	private function render_product_detail( $product, $config ) {
		$image_url = isset( $product['image'] ) ? $product['image'] : '';
		$name = isset( $product['name'] ) ? $product['name'] : 'Curso Ejemplo';
		$description = isset( $product['description'] ) ? $product['description'] : 'Descripción detallada del curso';
		$price = isset( $product['price'] ) ? $product['price'] : '0';

		$button_text = isset( $config['settings']['button_text_enroll'] ) ? $config['settings']['button_text_enroll'] : 'Matricularme';

		$html = '<div class="wom-product-detail">';

		// Image
		if ( $image_url ) {
			$html .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $name ) . '" class="wom-product-detail-image">';
		} else {
			$html .= '<div class="wom-product-detail-image-placeholder">';
			$html .= '<span>Imagen del Curso</span>';
			$html .= '</div>';
		}

		// Content
		$html .= '<div class="wom-product-detail-content">';

		$html .= '<h2 class="wom-product-name-large">' . esc_html( $name ) . '</h2>';

		$html .= '<p class="wom-product-description-large">' . esc_html( $description ) . '</p>';

		if ( isset( $config['settings']['show_price'] ) && $config['settings']['show_price'] ) {
			$html .= '<p class="wom-product-price-large">$' . esc_html( $price ) . '</p>';
		}

		$html .= '<button class="wom-btn wom-btn-block">' . esc_html( $button_text ) . '</button>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Obtiene productos de ejemplo para preview
	 *
	 * @param int $limit Cantidad de productos
	 * @return array
	 */
	private function get_sample_products( $limit = 3 ) {
		$samples = array(
			array(
				'name' => 'JavaScript Avanzado',
				'description' => 'Aprende conceptos avanzados de JavaScript incluyendo REST APIs, Async/Await y más.',
				'category' => 'Programación',
				'price' => '99',
				'image' => includes_url( 'images/media/default.png' ),
			),
			array(
				'name' => 'React Fundamentals',
				'description' => 'Domina React desde cero. Componentes, hooks, estado y más técnicas modernas.',
				'category' => 'Frontend',
				'price' => '129',
				'image' => includes_url( 'images/media/default.png' ),
			),
			array(
				'name' => 'Node.js & Express',
				'description' => 'Crea servidores web profes con Node.js y Express. RESTAPIs, autenticación y más.',
				'category' => 'Backend',
				'price' => '149',
				'image' => includes_url( 'images/media/default.png' ),
			),
		);

		return array_slice( $samples, 0, $limit );
	}

	/**
	 * Obtiene un producto de ejemplo
	 *
	 * @return array
	 */
	private function get_sample_product() {
		return array(
			'name' => 'Mastería en JavaScript',
			'description' => 'Curso completo de JavaScript desde cero hasta conceptos avanzados. Incluye proyectos reales, ejercicios prácticos y acceso a comunidad.',
			'category' => 'Programación Web',
			'price' => '199',
			'image' => includes_url( 'images/media/default.png' ),
		);
	}

	/**
	 * Obtiene la categoría principal de un producto
	 *
	 * @param WC_Product $product
	 * @return string
	 */
	private function get_product_category( $product ) {
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		return ! empty( $categories ) ? $categories[0] : 'Sin categoría';
	}
}
