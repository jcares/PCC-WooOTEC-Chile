<?php
/**
 * Template: Product Catalogue
 * 
 * Plantilla para mostrar grid de productos/cursos
 *
 * @package WOO_OTEC_Moodle
 * @subpackage Templates
 * @version 3.0.7
 *
 * Variables disponibles:
 * @var array $courses Array de cursos
 * @var array $config Array de configuración
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wom-catalogue-container">
	<h2 class="wom-catalogue-title" style="font-size: 28px; font-weight: 600; margin-bottom: 30px; color: var(--wom-primary);">
		Catálogo de Cursos
	</h2>

	<?php if ( empty( $courses ) ) : ?>
		<div class="wom-no-courses" style="padding: 40px; text-align: center; background: #f9fafb; border-radius: 8px;">
			<p style="color: var(--wom-text-light); font-size: 16px;">
				No hay cursos disponibles en este momento.
			</p>
		</div>
	<?php else : ?>
		<div class="wom-courses-grid">
			<?php foreach ( $courses as $course ) : ?>
				<div class="wom-product-card">
					<?php
					// Image - placeholder for now
					$image_url = includes_url( 'images/media/default.png' );
					?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $course['fullname'] ); ?>" class="wom-product-image">

					<div style="padding: 15px;">
						<?php if ( isset( $config['settings']['show_category'] ) && $config['settings']['show_category'] ) : ?>
							<p class="wom-product-category">
								<?php echo esc_html( $course['categoryname'] ?? 'Sin categoría' ); ?>
							</p>
						<?php endif; ?>

						<h3 class="wom-product-name">
							<?php echo esc_html( $course['fullname'] ); ?>
						</h3>

						<?php if ( isset( $config['settings']['show_meta'] ) && $config['settings']['show_meta'] ) : ?>
							<p class="wom-product-description">
								<?php echo esc_html( wp_trim_words( $course['summary'], 15 ) ); ?>
							</p>
						<?php endif; ?>

						<?php if ( isset( $config['settings']['show_price'] ) && $config['settings']['show_price'] ) : ?>
							<p class="wom-product-price">
								<?php echo esc_html( $config['settings']['default_price'] ?? 'Consultar' ); ?>
							</p>
						<?php endif; ?>

						<button class="wom-btn">
							<?php echo esc_html( $config['settings']['button_text'] ?? 'Ver Curso' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
