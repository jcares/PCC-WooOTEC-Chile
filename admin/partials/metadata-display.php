<?php
/**
 * Página de Metadatos - Mapeo de Campos y Vista Previa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

if ( empty( $metadata_manager ) ) {
	if ( empty( $api_client ) || empty( $logger ) ) {
		$api_client = new \Woo_OTEC_Moodle\API_Client();
		$logger = new \Woo_OTEC_Moodle\Logger();
	}
	$metadata_manager = new \Woo_OTEC_Moodle\Metadata_Manager( $api_client, $logger );
}

$all_mappings = \Woo_OTEC_Moodle\Field_Mapper::get_all_mappings();
$stats = \Woo_OTEC_Moodle\Field_Mapper::get_stats();
?>

<div class="wom-container" style="max-width: 900px; margin-top: 24px;">

	<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">

		<!-- Sección 1: Mapeo de Campos -->
		<div>
			<h2 style="margin-top: 0; font-size: 18px; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-admin-links"></span>
				Mapeo de Campos
			</h2>
			<p style="color: #666; font-size: 13px; margin: 0 0 20px;">
				Campos de Moodle que se sincronizan a WooCommerce
			</p>

			<!-- Estadísticas -->
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
				<div style="background: #f0f9ff; border: 1px solid #bfdbfe; padding: 12px; border-radius: 6px; text-align: center;">
					<div style="font-weight: 600; color: #0369a1; font-size: 20px;">
						<?php echo count($all_mappings); ?>
					</div>
					<div style="font-size: 12px; color: #0c4a6e;">Total</div>
				</div>
				<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 6px; text-align: center;">
					<div style="font-weight: 600; color: #15803d; font-size: 20px;">
						<?php echo $stats['enabled'] ?? 0; ?>
					</div>
					<div style="font-size: 12px; color: #15803d;">Habilitados</div>
				</div>
			</div>

			<!-- Tabla de Campos -->
			<div style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px;">
				<table style="width: 100%; border-collapse: collapse; font-size: 13px; margin: 0;">
					<thead>
						<tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
							<th style="padding: 10px; text-align: left; width: 40px;">Activo</th>
							<th style="padding: 10px; text-align: left;">Campo</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($all_mappings as $field_id => $mapping) : ?>
						<tr style="border-bottom: 1px solid #f3f4f6;">
							<td style="padding: 10px; text-align: center;">
								<input type="checkbox" class="wom-toggle-field" 
									data-field-id="<?php echo esc_attr($field_id); ?>" 
									style="cursor: pointer;"
									<?php checked($mapping['enabled'] ?? true); ?>>
							</td>
							<td style="padding: 10px;">
								<strong><?php echo esc_html($field_id); ?></strong>
								<?php if (!empty($mapping['description'])) : ?>
									<div style="font-size: 12px; color: #666; margin-top: 2px;">
										<?php echo esc_html($mapping['description']); ?>
									</div>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<button type="button" id="wom-reset-mappings" style="margin-top: 12px; padding: 8px 16px; background: #f97316; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
				<span class="dashicons dashicons-update" style="margin-right: 4px;"></span> Restaurar Predeterminados
			</button>
		</div>

		<!-- Sección 2: Vista Previa -->
		<div>
			<h2 style="margin-top: 0; font-size: 18px; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-visibility"></span>
				Vista en Vivo
			</h2>
			<p style="color: #666; font-size: 13px; margin: 0 0 20px;">
				Selecciona un curso para ver sus metadatos
			</p>

			<div style="margin-bottom: 20px;">
				<label for="wom-course-selector" style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px;">
					Curso:
				</label>
				<?php
					$products = get_posts( array(
						'post_type'     => 'product',
						'numberposts'   => -1,
						'orderby'       => 'post_title',
						'order'         => 'ASC',
					) );
				?>
				<?php if ( ! empty( $products ) ) : ?>
					<select id="wom-course-selector" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
						<option value="">-- Selecciona un curso --</option>
						<?php foreach ( $products as $product ) : ?>
							<option value="<?php echo esc_attr( $product->ID ); ?>">
								<?php echo esc_html( $product->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<div style="padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; color: #991b1b; font-size: 13px;">
						<span class="dashicons dashicons-warning" style="margin-right: 4px;"></span>
						No hay productos disponibles aún
					</div>
				<?php endif; ?>
			</div>

			<div id="wom-preview" style="background: #f9fafb; padding: 16px; border-radius: 6px; border: 1px solid #e5e7eb; display: none; max-height: 350px; overflow-y: auto;">
				<h3 id="preview-title" style="margin: 0 0 12px; font-size: 14px; font-weight: 600;"></h3>
				<div id="preview-content" style="font-size: 13px; color: #333; line-height: 1.5;"></div>
			</div>

			<div style="background: #f0f9ff; border: 1px solid #bfdbfe; padding: 12px; border-radius: 6px; margin-top: 20px; font-size: 12px; color: #0c4a6e;">
				<span class="dashicons dashicons-info" style="margin-right: 4px;"></span>
				Los cambios se guardan automáticamente
			</div>
		</div>

	</div>

</div>

<script>
(function($) {
	'use strict';

	const MetadataPage = {
		init: function() {
			console.log('🔧 MetadataPage inicializando...');
			console.log('wooOtecMoodle:', typeof wooOtecMoodle !== 'undefined' ? wooOtecMoodle : 'NO DISPONIBLE');
			
			$(document).on('change', '.wom-toggle-field', this.toggleField.bind(this));
			$(document).on('change', '#wom-course-selector', this.previewCourse.bind(this));
			$(document).on('click', '#wom-reset-mappings', this.resetMappings.bind(this));
		},

		toggleField: function(e) {
			const fieldId = $(e.target).data('field-id');
			const enabled = $(e.target).is(':checked') ? 1 : 0;
			console.log('📝 Guardando campo:', fieldId, 'enabled:', enabled);
			$.post(wooOtecMoodle.ajax_url, {
				action: 'woo_otec_update_field_mapping',
				nonce: wooOtecMoodle.nonce,
				field: fieldId,
				enable: enabled
			}, function(response) {
				console.log('✅ Campo guardado:', response);
			}).fail(function(error) {
				console.error('❌ Error al guardar campo:', error);
			});
		},

		previewCourse: function(e) {
			const productId = $(e.target).value;
			console.log('👀 Seleccionado producto ID:', productId);
			
			if (!productId) {
				$('#wom-preview').hide();
				console.log('ℹ️ Producto vacío, ocultando preview');
				return;
			}

			console.log('📡 Enviando AJAX a:', wooOtecMoodle.ajax_url);
			
			$.post(wooOtecMoodle.ajax_url, {
				action: 'wom_load_product_preview',
				nonce: wooOtecMoodle.nonce,
				product_id: productId
			}, function(response) {
				console.log('✅ Respuesta del servidor:', response);
				if (response.success) {
					const data = response.data || {};
					console.log('📋 Datos recibidos - título:', data.title, 'html length:', data.html ? data.html.length : 0);
					$('#preview-title').text(data.title || 'Curso');
					$('#preview-content').html(data.html || '');
					$('#wom-preview').show();
					console.log('✨ Preview mostrado');
				} else {
					console.error('❌ Error en respuesta:', response.data);
					$('#wom-preview').hide();
				}
			}).fail(function(xhr, status, error) {
				console.error('❌ Error AJAX:', {
					status: status,
					error: error,
					statusCode: xhr.status,
					response: xhr.responseText
				});
				alert('Error al cargar preview: ' + error);
			});
		},

		resetMappings: function(e) {
			e.preventDefault();
			if (!confirm('¿Restablecer todos los mapeos a predeterminados?')) return;
			console.log('🔄 Reseteando mapeos...');
			$.post(wooOtecMoodle.ajax_url, {
				action: 'woo_otec_reset_field_mappings',
				nonce: wooOtecMoodle.nonce
			}, function(response) {
				if (response.success) {
					console.log('✅ Mapeos restaurados');
					alert('Mapeos restaurados correctamente');
					location.reload();
				} else {
					console.error('❌ Error:', response.data);
					alert('Error: ' + response.data);
				}
			}).fail(function(error) {
				console.error('❌ Error AJAX:', error);
			});
		}
	};

	MetadataPage.init();

})(jQuery);
</script>
