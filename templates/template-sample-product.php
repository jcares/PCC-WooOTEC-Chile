<?php
/**
 * Template: Sample Product (Producto Individual)
 * 
 * Plantilla para mostrar un solo producto/curso en detalle
 *
 * @package WOO_OTEC_Moodle
 * @subpackage Templates
 * @version 3.0.7
 *
 * Variables disponibles:
 * @var WP_Post $product Objeto de producto
 * @var array $config Array de configuración
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = isset( $product->ID ) ? $product->ID : 0;
if ( ! $product_id ) {
	return;
}

$price = get_post_meta( $product_id, '_price', true );
$image_id = get_post_thumbnail_id( $product_id );
$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : ( isset( $config['settings']['default_image'] ) ? $config['settings']['default_image'] : includes_url( 'images/media/default.png' ) );
?>

<div class="wom-product-detail-container">
	<div class="wom-product-detail">
		<!-- Image Section -->
		<div class="wom-product-image-section">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->post_title ); ?>" class="wom-product-image-large">
		</div>

		<!-- Content Section -->
		<div class="wom-product-content-section">
			<!-- Category -->
			<?php
			$categories = get_the_terms( $product_id, 'product_cat' );
			if ( ! empty( $categories ) ) :
				?>
				<p class="wom-product-category">
					<?php echo esc_html( $categories[0]->name ); ?>
				</p>
			<?php endif; ?>

			<!-- Title -->
			<h1 class="wom-product-name">
				<?php echo esc_html( $product->post_title ); ?>
			</h1>

			<!-- Description -->
			<?php if ( ! empty( $product->post_excerpt ) || ! empty( $product->post_content ) ) : ?>
				<div class="wom-product-description">
					<?php
					echo wp_kses_post( ! empty( $product->post_excerpt ) ? $product->post_excerpt : wp_trim_words( $product->post_content, 50 ) );
					?>
				</div>
			<?php endif; ?>

			<!-- Price -->
			<?php if ( isset( $config['settings']['show_price'] ) && $config['settings']['show_price'] ) : ?>
				<div class="wom-product-price-section">
					<p class="wom-product-price">
						<?php
						$display_price = ! empty( $price ) ? $price : $config['settings']['default_price'];
						echo '$' . esc_html( $display_price );
						?>
					</p>
				</div>
			<?php endif; ?>

			<!-- Enrollment Button -->
			<div class="wom-product-cta-section">
				<button class="wom-btn wom-btn-enroll">
					<?php echo esc_html( $config['settings']['button_text_enroll'] ?? 'Matricularme' ); ?>
				</button>
			</div>

			<!-- Additional Info -->
			<div class="wom-product-meta-info" style="margin-top: 30px; padding-top: 30px; border-top: 1px solid var(--wom-border);">
				<div class="wom-meta-item">
					<strong>Modalidad:</strong> Online
				</div>
				<div class="wom-meta-item">
					<strong>Duración:</strong> Acceso ilimitado
				</div>
				<div class="wom-meta-item">
					<strong>Certificado:</strong> Sí, al completar
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	.wom-product-detail-container {
		max-width: 900px;
		margin: 0 auto;
	}

	.wom-product-detail {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 40px;
		align-items: start;
	}

	.wom-product-image-large {
		width: 100%;
		height: auto;
		border-radius: 8px;
		display: block;
	}

	.wom-product-price-section {
		margin: 20px 0;
	}

	.wom-product-cta-section {
		margin-top: 25px;
	}

	.wom-btn-enroll {
		width: 100%;
		padding: 15px;
		font-size: 16px;
		font-weight: 600;
	}

	.wom-meta-item {
		padding: 8px 0;
		color: var(--wom-text-light);
		font-size: 14px;
	}

	@media (max-width: 768px) {
		.wom-product-detail {
			grid-template-columns: 1fr;
			gap: 20px;
		}
	}
</style>
