<?php
/**
 * Template Builder - Personalizador de Plantillas (v3.0.9)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

$templates = $template_manager->get_available_templates();
$active_template = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : 'product-catalogue';

if ( ! isset( $templates[ $active_template ] ) ) {
	$active_template = 'product-catalogue';
}

$config   = $template_manager->get_saved_config( $active_template );
$defaults = $template_manager->get_template_defaults();
?>

<div class="wrap wom-template-builder-wrap">

	<div class="wom-tb-header">
		<h1>Personalizador de Plantillas</h1>
		<p>Configura el diseño de tus cursos y productos</p>
	</div>

	<div class="wom-template-tabs">
		<?php foreach ( $templates as $tmpl_id => $tmpl_data ) : ?>
			<a href="?page=woo-otec-moodle-template-builder&template=<?php echo esc_attr( $tmpl_id ); ?>"
			   class="<?php echo $active_template === $tmpl_id ? 'is-active' : ''; ?>">
				<?php echo esc_html( $tmpl_data['name'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<div class="wom-builder-grid">

		<aside class="wom-builder-sidebar">
			<form id="wom-template-form-<?php echo esc_attr( $active_template ); ?>" onsubmit="return false;">

				<div class="wom-form-section">
					<h3>Colores</h3>
					<input type="color" name="colors[primary]" value="<?php echo esc_attr( $config['colors']['primary'] ?? $defaults['colors']['primary'] ); ?>">
					<input type="color" name="colors[text]" value="<?php echo esc_attr( $config['colors']['text'] ?? $defaults['colors']['text'] ); ?>">
					<input type="color" name="colors[text_light]" value="<?php echo esc_attr( $config['colors']['text_light'] ?? $defaults['colors']['text_light'] ); ?>">
					<input type="color" name="colors[border]" value="<?php echo esc_attr( $config['colors']['border'] ?? $defaults['colors']['border'] ); ?>">
				</div>

				<div class="wom-form-section">
					<h3>Textos</h3>
					<input type="text" name="settings[button_label]" value="<?php echo esc_attr( $config['settings']['button_label'] ?? 'Ver Curso' ); ?>">
					<input type="text" name="settings[title]" value="<?php echo esc_attr( $config['settings']['title'] ?? 'Catálogo de Cursos' ); ?>">
				</div>

				<button type="button" class="button button-primary wom-btn-save">Guardar</button>
				<button type="button" class="button wom-btn-reset">Restaurar</button>

			</form>
		</aside>

		<main class="wom-builder-main">
			<div class="wom-preview-wrapper">

				<h3>Vista Previa</h3>

				<?php if ( 'product-catalogue' === $active_template ) : ?>

					<div id="wom-preview-<?php echo esc_attr( $active_template ); ?>">

						<?php
						$products = function_exists( 'wc_get_products' ) ? wc_get_products( [
							'limit'  => 6,
							'status' => 'publish'
						] ) : [];

						if ( ! empty( $products ) ) :
							foreach ( $products as $product ) :
								$image = wp_get_attachment_image_url( $product->get_image_id(), 'medium' );
								?>

								<div class="wom-preview-card">
									<div class="wom-preview-card-image">
										<?php if ( $image ) : ?>
											<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>">
										<?php endif; ?>
									</div>
									<div class="wom-preview-card-content">
										<div class="wom-preview-card-title"><?php echo esc_html( $product->get_name() ); ?></div>
										<div class="wom-preview-card-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
										<a href="#" class="wom-preview-card-button"><?php echo esc_html( $config['settings']['button_label'] ?? 'Ver Curso' ); ?></a>
									</div>
								</div>

							<?php endforeach;
						else :
							?>

						<div class="wom-preview-empty">No hay productos disponibles</div>

						<?php endif; ?>

					</div>

				<?php else : ?>

					<div id="wom-preview-<?php echo esc_attr( $active_template ); ?>">
						<div class="wom-preview-single-image"></div>
						<div class="wom-preview-single-content">
							<h4>Nombre del Curso</h4>
							<p>Descripción del curso</p>
							<div class="wom-preview-single-price">$0</div>
							<a href="#" class="wom-preview-single-button"><?php echo esc_html( $config['settings']['button_label'] ?? 'Inscribirse' ); ?></a>
						</div>
					</div>

				<?php endif; ?>

			</div>
		</main>

	</div>

</div>