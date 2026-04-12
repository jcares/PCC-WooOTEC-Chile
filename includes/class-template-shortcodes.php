<?php
/**
 * Shortcodes para Template Products con Metadatas
 * 
 * Proporciona shortcodes para mostrar productos individuales y catálogos
 * con metadatas desde Moodle incluidos.
 *
 * @package    Woo_OTEC_Moodle
 * @subpackage Frontend
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Shortcodes {

	/**
	 * Template Manager
	 */
	private $template_manager;

	/**
	 * Metadata Manager
	 */
	private $metadata_manager;

	/**
	 * Constructor
	 */
	public function __construct( $template_manager, $metadata_manager ) {
		$this->template_manager  = $template_manager;
		$this->metadata_manager  = $metadata_manager;

		// Registrar shortcodes
		add_shortcode( 'wom_sample_product', array( $this, 'shortcode_sample_product' ) );
		add_shortcode( 'wom_product_catalogue', array( $this, 'shortcode_product_catalogue' ) );
		add_shortcode( 'wom_product_enroll', array( $this, 'shortcode_product_enroll' ) );
	}

	/**
	 * Shortcode: [wom_sample_product id="123"]
	 * Muestra un producto individual con metadatas de Moodle
	 *
	 * @param array $atts Atributos del shortcode
	 * @return string HTML del producto
	 */
	public function shortcode_sample_product( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'template' => 'sample-product',
			),
			$atts,
			'wom_sample_product'
		);

		$product_id = intval( $atts['id'] );
		if ( ! $product_id ) {
			return '<!-- wom_sample_product: ID de producto requerido -->';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '<!-- wom_sample_product: Producto no encontrado -->';
		}

		// Obtener metadatas del producto
		$moodle_course_name = get_post_meta( $product_id, '_moodle_course_name', true );
		$moodle_course_summary = get_post_meta( $product_id, '_moodle_course_summary', true );
		$moodle_course_category_id = get_post_meta( $product_id, '_moodle_course_category_id', true );
		$default_price = get_post_meta( $product_id, '_moodle_course_default_price', true );
		$default_image_id = get_post_meta( $product_id, '_moodle_course_default_image', true );
		$show_category = get_post_meta( $product_id, '_moodle_show_category', true );
		$show_price = get_post_meta( $product_id, '_moodle_show_price', true );
		$show_meta = get_post_meta( $product_id, '_moodle_show_meta', true );
		$button_text = get_post_meta( $product_id, '_moodle_button_text', true );
		$button_text_enroll = get_post_meta( $product_id, '_moodle_button_text_enroll', true );

		// Fallbacks
		$title = $moodle_course_name ?: $product->get_name();
		$description = $moodle_course_summary ?: wp_trim_words( $product->get_description(), 30 );
		$image_id = $default_image_id ?: $product->get_image_id();
		$price = $default_price ?: $product->get_price();
		$button_text = $button_text ?: 'Ver Curso';
		$button_text_enroll = $button_text_enroll ?: 'Matricularme';

		// Obtener configuración de template
		$config = $this->template_manager->get_saved_config( $atts['template'] );
		$colors = $config['colors'] ?? array();
		$primary_color = $colors['primary'] ?? '#6366f1';

		// HTML
		ob_start();
		?>
		<div class="wom-sample-product" style="
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			overflow: hidden;
			background: #ffffff;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			max-width: 400px;
			margin: 0 auto;
		">
			<!-- Imagen -->
			<?php if ( $image_id ) : ?>
				<div class="wom-product-image" style="overflow: hidden; height: 250px; background: #f3f4f6;">
					<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'style' => 'width: 100%; height: 100%; object-fit: cover;' ) ); ?>
				</div>
			<?php else : ?>
				<div class="wom-product-no-image" style="height: 250px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
					Sin imagen disponible
				</div>
			<?php endif; ?>

			<!-- Contenido -->
			<div class="wom-product-content" style="padding: 20px;">
				<!-- Título -->
				<h2 style="
					margin: 0 0 12px 0;
					font-size: 20px;
					font-weight: 600;
					color: #1f2937;
					line-height: 1.3;
				">
					<?php echo esc_html( $title ); ?>
				</h2>

				<!-- Descripción -->
				<?php if ( $description ) : ?>
					<p style="
						margin: 0 0 16px 0;
						font-size: 14px;
						color: #6b7280;
						line-height: 1.5;
					">
						<?php echo wp_kses_post( $description ); ?>
					</p>
				<?php endif; ?>

				<!-- Metadatas en Grid -->
				<?php if ( $show_meta && ( $moodle_course_category_id || $price ) ) : ?>
					<div class="wom-product-meta" style="
						display: grid;
						grid-template-columns: 1fr 1fr;
						gap: 12px;
						margin-bottom: 16px;
						padding-bottom: 16px;
						border-bottom: 1px solid #e5e7eb;
					">
						<!-- Categoría -->
						<?php if ( $show_category && $moodle_course_category_id ) : ?>
							<div class="wom-meta-item">
								<span style="
									font-size: 11px;
									color: #9ca3af;
									text-transform: uppercase;
									font-weight: 600;
									letter-spacing: 0.5px;
								">Categoría</span>
								<p style="
									margin: 4px 0 0 0;
									font-size: 13px;
									color: #1f2937;
									font-weight: 500;
								">
									<?php echo esc_html( $this->get_category_name( $moodle_course_category_id ) ); ?>
								</p>
							</div>
						<?php endif; ?>

						<!-- Precio -->
						<?php if ( $show_price && $price ) : ?>
							<div class="wom-meta-item">
								<span style="
									font-size: 11px;
									color: #9ca3af;
									text-transform: uppercase;
									font-weight: 600;
									letter-spacing: 0.5px;
								">Precio</span>
								<p style="
									margin: 4px 0 0 0;
									font-size: 13px;
									color: <?php echo esc_attr( $primary_color ); ?>;
									font-weight: 600;
								">
									<?php echo wc_price( $price ); ?>
								</p>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- Botones -->
				<div class="wom-product-actions" style="
					display: grid;
					grid-template-columns: 1fr 1fr;
					gap: 12px;
				">
					<!-- Botón Ver Curso -->
					<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" 
					   class="wom-btn-view" 
					   style="
						background: #f3f4f6;
						color: #1f2937;
						border: 1px solid #d1d5db;
						padding: 10px 16px;
						border-radius: 6px;
						text-align: center;
						text-decoration: none;
						font-size: 13px;
						font-weight: 600;
						transition: all 0.2s;
						display: inline-block;
						cursor: pointer;
					">
						<?php echo esc_html( $button_text ); ?>
					</a>

					<!-- Botón Matricularme -->
					<button class="wom-btn-enroll" 
					        onclick="wom_enroll_product(<?php echo intval( $product_id ); ?>)" 
					        style="
						background: <?php echo esc_attr( $primary_color ); ?>;
						color: #ffffff;
						border: none;
						padding: 10px 16px;
						border-radius: 6px;
						text-align: center;
						font-size: 13px;
						font-weight: 600;
						transition: all 0.2s;
						cursor: pointer;
					">
						<?php echo esc_html( $button_text_enroll ); ?>
					</button>
				</div>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: [wom_product_catalogue template="product-catalogue" limit="6"]
	 * Muestra catálogo de productos con metadatas
	 *
	 * @param array $atts Atributos del shortcode
	 * @return string HTML del catálogo
	 */
	public function shortcode_product_catalogue( $atts ) {
		$atts = shortcode_atts(
			array(
				'template' => 'product-catalogue',
				'limit'    => 6,
				'columns'  => 3,
				'category' => '',
			),
			$atts,
			'wom_product_catalogue'
		);

		$args = array(
			'post_type'      => 'product',
			'numberposts'    => intval( $atts['limit'] ),
			'meta_query'     => array(
				array(
					'key'     => '_moodle_course_id',
					'compare' => 'EXISTS',
				),
			),
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		$products = get_posts( $args );
		$config = $this->template_manager->get_saved_config( $atts['template'] );
		$columns = intval( $atts['columns'] ) ?: intval( $config['settings']['columns'] ?? 3 );

		ob_start();
		?>
		<div class="wom-product-catalogue" style="
			display: grid;
			grid-template-columns: repeat(<?php echo intval( $columns ); ?>, 1fr);
			gap: 24px;
			margin: 20px 0;
		">
			<?php
			foreach ( $products as $product_post ) {
				echo do_shortcode( '[wom_sample_product id="' . $product_post->ID . '" template="' . esc_attr( $atts['template'] ) . '"]' );
			}
			?>
		</div>

		<?php
		if ( empty( $products ) ) {
			echo '<p style="text-align: center; color: #9ca3af;">No hay cursos disponibles.</p>';
		}

		return ob_get_clean();
	}

	/**
	 * Shortcode: [wom_product_enroll id="123"]
	 * Tabla de inscripción para un producto
	 *
	 * @param array $atts Atributos del shortcode
	 * @return string HTML
	 */
	public function shortcode_product_enroll( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'wom_product_enroll'
		);

		$product_id = intval( $atts['id'] );
		if ( ! $product_id ) {
			return '<!-- wom_product_enroll: ID requerido -->';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '<!-- wom_product_enroll: Producto no encontrado -->';
		}

		$moodle_course_name = get_post_meta( $product_id, '_moodle_course_name', true );
		$moodle_course_summary = get_post_meta( $product_id, '_moodle_course_summary', true );
		$default_price = get_post_meta( $product_id, '_moodle_course_default_price', true );
		$price = $default_price ?: $product->get_price();

		ob_start();
		?>
		<div class="wom-enrollment-form" style="
			background: #f9fafb;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			padding: 24px;
			max-width: 500px;
			margin: 20px auto;
		">
			<h3 style="
				margin: 0 0 20px 0;
				font-size: 18px;
				font-weight: 600;
				color: #1f2937;
			">
				Detalles de Inscripción
			</h3>

			<!-- Información del Curso -->
			<table style="
				width: 100%;
				border-collapse: collapse;
				margin-bottom: 20px;
			">
				<tr>
					<td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 13px;">Curso:</td>
					<td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #1f2937; font-weight: 600; font-size: 13px;">
						<?php echo esc_html( $moodle_course_name ?: $product->get_name() ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding: 10px 0; color: #6b7280; font-size: 13px;">Precio:</td>
					<td style="padding: 10px 0; color: #1f2937; font-weight: 600; font-size: 13px;">
						<?php echo wc_price( $price ); ?>
					</td>
				</tr>
			</table>

			<!-- Botón de Inscripción -->
			<button class="woom-enroll-btn" 
			        onclick="wom_enroll_product(<?php echo intval( $product_id ); ?>)" 
			        style="
				width: 100%;
				background: #6366f1;
				color: #ffffff;
				border: none;
				padding: 12px 16px;
				border-radius: 6px;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
				transition: all 0.2s;
			">
				Inscribirse Ahora
			</button>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Obtener nombre de categoría por ID
	 */
	private function get_category_name( $category_id ) {
		$term = get_term( intval( $category_id ), 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term->name;
		}
		return 'Categoría';
	}
}
