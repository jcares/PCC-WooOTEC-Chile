<?php
/**
 * Template Builder - Personalizador de Plantillas
 * 
 * ¿Qué hace?
 * - Personalizar vista previa de productos
 * - Editar colores, textos, visibilidad
 * - 3 plantillas diferentes: product-catalogue, sample-product, email
 * 
 * ¿Qué debe funcionar?
 * ✅ Panel izquierdo: Opciones (colores, textos, toggles)
 * ✅ Panel derecho: Preview en tiempo real (live preview)
 * ✅ product-catalogue: Grid de 3 columnas con productos reales
 * ✅ sample-product: Producto individual + selector dinámico
 * ✅ sample-product: Auto-cargar primer producto por defecto
 * ✅ email: Plantilla de email de matrícula
 * ✅ Botón "Guardar" → AJAX
 * ✅ Botón "Reset" → restaurar a valores por defecto
 * 
 * v3.0.8
 */

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

$templates = $template_manager->get_available_templates();
$active_template = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : 'product-catalogue';

if ( ! isset( $templates[ $active_template ] ) ) {
	$active_template = 'product-catalogue';
}

$config = $template_manager->get_saved_config( $active_template );
$defaults = $template_manager->get_template_defaults();

// Obtener productos de WooCommerce
$products = wc_get_products( array(
	'limit' => 100,
	'status' => 'publish',
) );

$first_product_id = ! empty( $products ) ? $products[0]->get_id() : 0;
?>

<div class="wom-wrap">
	<h2><span class="dashicons dashicons-edit"></span> Personalizador de Plantillas</h2>

	<!-- Template Navigation -->
	<div class="wom-template-nav" style="margin: 20px 0; border-bottom: 2px solid #e5e7eb;">
		<?php foreach ( $templates as $tmpl_id => $tmpl_data ) : ?>
			<a href="?page=woo-otec-moodle-template-builder&template=<?php echo esc_attr( $tmpl_id ); ?>" 
			   style="display: inline-block; padding: 12px 16px; color: <?php echo $active_template === $tmpl_id ? '#6366f1' : '#6b7280'; ?>; text-decoration: none; border-bottom: 3px solid <?php echo $active_template === $tmpl_id ? '#6366f1' : 'transparent'; ?>; transition: all 0.3s;">
				<?php echo esc_html( $tmpl_data['name'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<!-- Main Content: 3-Column Layout with Full-Width Preview Below -->
	<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
		
		<!-- Left Column: Colors -->
		<div>
			<form id="wom-template-form-<?php echo esc_attr( $active_template ); ?>" data-template="<?php echo esc_attr( $active_template ); ?>">
				
				<fieldset style="background: #fff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px;">
					<legend style="font-size: 16px; font-weight: 600; padding: 0 10px;">Colores</legend>

					<!-- Primary Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color Primario
						</label>
						<input type="color" name="colors[primary]" class="wom-color-picker"
							value="<?php echo esc_attr( $config['colors']['primary'] ?? $defaults['colors']['primary'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>

					<!-- Text Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color de Texto
						</label>
						<input type="color" name="colors[text]" class="wom-color-picker"
							value="<?php echo esc_attr( $config['colors']['text'] ?? $defaults['colors']['text'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>

					<!-- Text Light Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color de Texto Secundario
						</label>
						<input type="color" name="colors[text_light]" class="wom-color-picker"
							value="<?php echo esc_attr( $config['colors']['text_light'] ?? $defaults['colors']['text_light'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>

					<!-- Border Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color de Bordes
						</label>
						<input type="color" name="colors[border]" class="wom-color-picker"
							value="<?php echo esc_attr( $config['colors']['border'] ?? $defaults['colors']['border'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>
				</fieldset>
			</form>
		</div>

		<!-- Middle Column: Texts -->
		<div>
			<!-- Textos -->
			<fieldset style="background: #fff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px;">
				<legend style="font-size: 16px; font-weight: 600; padding: 0 10px;">Textos</legend>

				<?php if ( 'email' !== $active_template ) : ?>
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Título de Botón
						</label>
						<input type="text" name="settings[button_label]" class="wom-text-input"
							value="<?php echo esc_attr( $config['settings']['button_label'] ?? 'Seleccionar' ); ?>"
							style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
					</div>

					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Título de Carrito
						</label>
					<input type="text" name="settings[cart_label]" class="wom-text-input"
						value="<?php echo esc_attr( $config['settings']['cart_label'] ?? 'Ir al Carrito' ); ?>"
						style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
				</div>
			<?php endif; ?>

			<div style="margin-bottom: 16px;">
				<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
					Título de Encabezado
				</label>
				<input type="text" name="settings[title]" class="wom-text-input"
					value="<?php echo esc_attr( $config['settings']['title'] ?? 'Cursos Disponibles' ); ?>"
						style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
				</div>
			</fieldset>
		</div>

		<!-- Right Column: Buttons -->
		<div>
			<!-- Selector de Producto para Sample-Product -->
			<?php if ( 'sample-product' === $active_template ) : ?>
				<fieldset style="background: #fff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px; margin-bottom: 10px;">
					<legend style="font-size: 16px; font-weight: 600; padding: 0 10px;">Seleccionar Producto</legend>
					<select id="sample-product-select" style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
						<?php foreach ( $products as $product ) : ?>
							<option value="<?php echo esc_attr( $product->get_id() ); ?>" <?php selected( $first_product_id, $product->get_id() ); ?>>
								<?php echo esc_html( $product->get_name() ); ?> (ID: <?php echo $product->get_id(); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</fieldset>
			<?php endif; ?>

			<!-- Buttons -->
			<div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
				<button type="button" class="wom-btn wom-btn-save wom-btn-success" style="width: 100%; padding: 10px 12px; white-space: normal; word-break: break-word; overflow-wrap: break-word;">
					<span class="dashicons dashicons-yes"></span> Guardar Cambios
				</button>
				<button type="button" class="wom-btn wom-btn-reset wom-btn-warning" style="width: 100%; padding: 10px 12px; white-space: normal; word-break: break-word; overflow-wrap: break-word;">
					<span class="dashicons dashicons-update"></span> Restaurar Predeterminados
				</button>
			</div>

			<div id="template-message" style="margin-top: 15px;"></div>
		</div>

			<!-- Full-Width Preview Below All Columns -->
		<div id="wom-preview-container-<?php echo esc_attr( $active_template ); ?>" style="grid-column: 1 / -1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
			<h3 style="margin: 0 0 15px; font-size: 14px; font-weight: 600; color: #374151;">Vista Previa</h3>
			<!-- Para product-catalogue: mostrar en 3 columnas; para otros: contenedor normal -->
			<?php if ( 'product-catalogue' === $active_template ) : ?>
				<div id="wom-preview-<?php echo esc_attr( $active_template ); ?>" style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #e5e7eb; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
					<p style="color: #6b7280; text-align: center; padding: 20px; grid-column: 1 / -1;">Cargando preview...</p>
				</div>
			<?php else : ?>
				<div id="wom-preview-<?php echo esc_attr( $active_template ); ?>" style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #e5e7eb; overflow-x: auto; max-height: 600px;">
					<p style="color: #6b7280; text-align: center; padding: 20px;">Cargando preview...</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>


