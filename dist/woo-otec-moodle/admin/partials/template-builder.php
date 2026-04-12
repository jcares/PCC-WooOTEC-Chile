<?php
/**
 * Template Builder - Personalizador de Plantillas v3.0.8
 * Interfaz para personalizar colores y textos de templates
 */

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

$templates = $template_manager->get_available_templates();
$active_template = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : 'product-catalogue';

if ( ! isset( $templates[ $active_template ] ) ) {
	$active_template = 'product-catalogue';
}

$config = $template_manager->get_saved_config( $active_template );
$defaults = $template_manager->get_default_config();
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

	<!-- Main Content: 2-Column Layout -->
	<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
		
		<!-- Left Column: Settings -->
		<div>
			<form id="wom-template-form" data-template="<?php echo esc_attr( $active_template ); ?>">
				
				<fieldset style="background: #fff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px;">
					<legend style="font-size: 16px; font-weight: 600; padding: 0 10px;">Colores</legend>

					<!-- Primary Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color Primario
						</label>
						<input type="color" name="colors[primary]" class="wom-color-field"
							value="<?php echo esc_attr( $config['colors']['primary'] ?? $defaults['colors']['primary'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>

					<!-- Text Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color de Texto
						</label>
						<input type="color" name="colors[text]" class="wom-color-field"
							value="<?php echo esc_attr( $config['colors']['text'] ?? $defaults['colors']['text'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>

					<!-- Text Light Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color de Texto Secundario
						</label>
						<input type="color" name="colors[text_light]" class="wom-color-field"
							value="<?php echo esc_attr( $config['colors']['text_light'] ?? $defaults['colors']['text_light'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>

					<!-- Border Color -->
					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Color de Bordes
						</label>
						<input type="color" name="colors[border]" class="wom-color-field"
							value="<?php echo esc_attr( $config['colors']['border'] ?? $defaults['colors']['border'] ); ?>"
							style="width: 100%; height: 36px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
					</div>
				</fieldset>

				<!-- Textos -->
				<fieldset style="background: #fff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px; margin-top: 20px;">
					<legend style="font-size: 16px; font-weight: 600; padding: 0 10px;">Textos</legend>

					<?php if ( 'email' !== $active_template ) : ?>
						<div style="margin-bottom: 16px;">
							<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
								Título de Botón
							</label>
							<input type="text" name="texts[button_label]"
								value="<?php echo esc_attr( $config['texts']['button_label'] ?? 'Seleccionar' ); ?>"
								style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
						</div>

						<div style="margin-bottom: 16px;">
							<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
								Título de Carrito
							</label>
							<input type="text" name="texts[cart_label]"
								value="<?php echo esc_attr( $config['texts']['cart_label'] ?? 'Ir al Carrito' ); ?>"
								style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
						</div>
					<?php endif; ?>

					<div style="margin-bottom: 16px;">
						<label style="display: block; margin-bottom: 6px; color: #374151; font-weight: 500; font-size: 13px;">
							Título de Encabezado
						</label>
						<input type="text" name="texts[title]"
							value="<?php echo esc_attr( $config['texts']['title'] ?? 'Cursos Disponibles' ); ?>"
							style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px;">
					</div>
				</fieldset>

				<!-- Buttons -->
				<div style="margin-top: 20px; display: flex; gap: 10px;">
					<button type="button" id="wom-save-template" class="wom-btn wom-btn-success">
						<span class="dashicons dashicons-yes"></span> Guardar Cambios
					</button>
					<button type="button" id="wom-reset-template" class="wom-btn wom-btn-warning">
						<span class="dashicons dashicons-update"></span> Restaurar Predeterminados
					</button>
				</div>

				<div id="template-message" style="margin-top: 15px;"></div>
			</form>
		</div>

		<!-- Right Column: Preview -->
		<div id="wom-preview-container" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; max-height: 600px; overflow-y: auto;">
			<h3 style="margin: 0 0 15px; font-size: 14px; font-weight: 600; color: #374151;">Vista Previa</h3>
			<div id="wom-preview" style="background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #e5e7eb;">
				<p style="color: #6b7280; text-align: center; padding: 20px;">Cargando preview...</p>
			</div>
		</div>
	</div>
</div>

<script>
(function($) {
	'use strict';

	const TemplateBuilder = {
		init: function() {
			this.loadPreview();
			$(document).on('change', '.wom-color-field, input[name^="texts"]', this.updatePreview.bind(this));
			$(document).on('click', '#wom-save-template', this.saveTemplate.bind(this));
			$(document).on('click', '#wom-reset-template', this.resetTemplate.bind(this));
		},

		loadPreview: function() {
			const template = $('#wom-template-form').data('template');
			this.renderPreview(template);
		},

		renderPreview: function(template) {
			const formData = new FormData($('#wom-template-form')[0]);
			const data = Object.fromEntries(formData);

			$.post(wooOtecMoodle.ajax_url, {
				action: 'wom_preview_template',
				nonce: wooOtecMoodle.nonce,
				template: template,
				config: JSON.stringify(data)
			}, (response) => {
				if (response.success) {
					$('#wom-preview').html(response.data);
				}
			});
		},

		updatePreview: function() {
			setTimeout(() => {
				this.loadPreview();
			}, 300);
		},

		saveTemplate: function(e) {
			e.preventDefault();
			const template = $('#wom-template-form').data('template');
			const formData = new FormData($('#wom-template-form')[0]);
			const data = Object.fromEntries(formData);

			$.post(wooOtecMoodle.ajax_url, {
				action: 'wom_save_template_config',
				nonce: wooOtecMoodle.nonce,
				template: template,
				config: JSON.stringify(data)
			}, (response) => {
				if (response.success) {
					$('#template-message').html('<div class="notice notice-success"><p>✓ Configuración guardada correctamente</p></div>');
					setTimeout(() => {
						$('#template-message').fadeOut();
					}, 3000);
				} else {
					$('#template-message').html('<div class="notice notice-error"><p>✗ Error: ' + response.data + '</p></div>');
				}
			});
		},

		resetTemplate: function(e) {
			e.preventDefault();
			if (!confirm('¿Restablecer plantilla a valores por defecto?')) return;

			const template = $('#wom-template-form').data('template');
			$.post(wooOtecMoodle.ajax_url, {
				action: 'wom_reset_template',
				nonce: wooOtecMoodle.nonce,
				template: template
			}, (response) => {
				if (response.success) {
					location.reload();
				}
			});
		}
	};

	TemplateBuilder.init();

})(jQuery);
</script>
