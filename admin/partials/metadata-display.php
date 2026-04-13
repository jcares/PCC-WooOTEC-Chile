<?php
/**
 * Página de Metadatos
 * 
 * ¿Qué hace?
 * - Tab 1 - MAPEO: Campos de Moodle que se sincronizan a WooCommerce
 * - Tab 2 - VISTA EN VIVO: Seleccionar curso y ver sus metadatos en tiempo real
 * 
 * ¿Qué debe funcionar?
 * Tab 1 - MAPEO DE CAMPOS:
 * ✅ Tabla con 13 campos (checkbox + descripción + tipo)
 * ✅ Contador: Total | Habilitados | Deshabilitados
 * ✅ Botón "Restaurar" → reset a valores por defecto
 * ✅ Cambios sin reload (AJAX)
 * 
 * Tab 2 - VISTA EN VIVO:
 * ✅ Dropdown selector de cursos
 * ✅ Al seleccionar → muestra preview de metadatos
 * ✅ Muestra título + contenido HTML formateado
 */

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
$metadata = $metadata_manager->get_available_metadata() ?: array();
?>

<div class="wom-wrap">
<div class="wom-tabs">
	<a href="#mapeo" class="wom-tab-link active" data-tab="mapeo">
		<span class="dashicons dashicons-admin-links"></span> Mapeo de Campos
	</a>
	<a href="#preview" class="wom-tab-link" data-tab="preview">
		<span class="dashicons dashicons-visibility"></span> Vista en Vivo
	</a>
</div>

<div id="mapeo" class="wom-tab-content active">
	<div class="wom-container">
		<h2><span class="dashicons dashicons-admin-links"></span> Mapeo de Campos</h2>
		<p style="color: var(--wom-text-muted); margin: 0 0 20px;">
			Selecciona qué campos de Moodle se sincronizarán a WooCommerce
		</p>

		<div class="wom-grid wom-grid-cols-3" style="margin-bottom: 30px;">
			<div class="wom-status-card info">
				<div class="wom-status-card-label">Total</div>
				<div class="wom-status-card-value"><?php echo count($all_mappings); ?></div>
			</div>
			<div class="wom-status-card success">
				<div class="wom-status-card-label">Habilitados</div>
				<div class="wom-status-card-value"><?php echo $stats['enabled'] ?? 0; ?></div>
			</div>
			<div class="wom-status-card warning">
				<div class="wom-status-card-label">Deshabilitados</div>
				<div class="wom-status-card-value"><?php echo $stats['disabled'] ?? 0; ?></div>
			</div>
		</div>

		<table class="wom-table">
			<thead>
				<tr>
					<th style="width: 40px;">Activo</th>
					<th>Campo</th>
					<th>Descripción</th>
					<th>Clave WC</th>
					<th>Tipo</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($all_mappings as $field_id => $mapping) : ?>
				<tr>
					<td>
						<input type="checkbox" class="wom-toggle-field" 
							data-field-id="<?php echo esc_attr($field_id); ?>" 
							<?php checked($mapping['enabled'] ?? true); ?>>
					</td>
					<td><strong><?php echo esc_html($field_id); ?></strong></td>
					<td><small><?php echo esc_html($mapping['description'] ?? ''); ?></small></td>
					<td><code><?php echo esc_html($mapping['wc_key'] ?? ''); ?></code></td>
					<td><span class="wom-badge"><?php echo esc_html($mapping['type'] ?? ''); ?></span></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="wom-actions-row">
			<button type="button" id="wom-reset-mappings" class="wom-btn wom-btn-warning">
				<span class="dashicons dashicons-update"></span> Restaurar
			</button>
			<div id="mapeo-result-message"></div>
		</div>
	</div>
</div>

<div id="preview" class="wom-tab-content">
	<div class="wom-container">
		<h2><span class="dashicons dashicons-visibility"></span> Vista en Vivo</h2>
		<p style="color: var(--wom-text-muted); margin: 0 0 20px;">
			Selecciona un curso para ver cómo aparecen sus metadatos
		</p>

		<div style="margin-bottom: 20px;">
			<label>Curso:</label>
			<select id="wom-course-selector" class="wom-input-select" style="width: 100%; max-width: 300px;">
				<option value="">-- Selecciona un curso --</option>
				<?php
					$products = get_posts( array(
						'post_type'     => 'product',
						'meta_key'      => '_moodle_course_id',
						'numberposts'   => -1,
						'orderby'       => 'post_title',
						'order'         => 'ASC',
					) );
					foreach ( $products as $product ) :
						$moodle_id = get_post_meta( $product->ID, '_moodle_course_id', true );
				?>
					<option value="<?php echo esc_attr( $product->ID ); ?>" data-moodle-id="<?php echo esc_attr( $moodle_id ); ?>">
						<?php echo esc_html( $product->post_title ); ?> (ID: <?php echo esc_html( $moodle_id ); ?>)
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div id="wom-preview" style="background: #f5f5f5; padding: 20px; border-radius: 4px; border: 1px solid #ddd; display: none;">
			<h3 id="preview-title"></h3>
			<div id="preview-content"></div>
		</div>
	</div>
</div>

<!-- Info -->
<div style="margin-top: 30px;">
	<div class="wom-alert wom-alert-info">
		<span class="dashicons dashicons-info wom-alert-icon"></span>
		<div class="wom-alert-content">
			<div class="wom-alert-title">Sincronización</div>
			<p class="wom-alert-message" style="margin: 0;">
				Los cambios se guardan automáticamente en el servidor.
			</p>
		</div>
	</div>
</div>
</div>

<style>
.wom-metadata-box {
	background: var(--wom-white);
	border: 1px solid var(--wom-border);
	padding: 16px;
	border-radius: var(--wom-radius-sm);
	transition: all var(--wom-transition);
}
.wom-metadata-box:hover {
	border-color: var(--wom-primary);
	box-shadow: var(--wom-shadow-md);
}
.wom-badge {
	display: inline-block;
	background: var(--wom-primary-light);
	color: var(--wom-primary);
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 11px;
	font-weight: 600;
}
.wom-tabs {
	display: flex;
	gap: 12px;
	margin: 0 0 24px;
	border-bottom: 2px solid var(--wom-border);
}
.wom-tab-link {
	padding: 12px 16px;
	text-decoration: none;
	color: var(--wom-text-muted);
	border-bottom: 3px solid transparent;
	transition: all var(--wom-transition);
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 6px;
}
.wom-tab-link.active {
	color: var(--wom-primary);
	border-bottom-color: var(--wom-primary);
}
.wom-tab-content {
	display: none;
}
.wom-tab-content.active {
	display: block;
}
</style>

<script>
(function($) {
	'use strict';

	const Tab = {
		init: function() {
			$(document).on('click', '.wom-tab-link', this.switch.bind(this));
			$(document).on('change', '.wom-toggle-field', this.toggleField.bind(this));
			$(document).on('change', '#wom-course-selector', this.previewCourse.bind(this));
			$(document).on('click', '#wom-reset-mappings', this.resetMappings.bind(this));
		},

		switch: function(e) {
			e.preventDefault();
			const tab = $(e.currentTarget).data('tab');
			$('.wom-tab-link').removeClass('active');
			$(e.currentTarget).addClass('active');
			$('.wom-tab-content').removeClass('active');
			$('#' + tab).addClass('active');
		},

		toggleField: function(e) {
			const fieldId = $(e.target).data('field-id');
			const enabled = $(e.target).is(':checked') ? 1 : 0;
			$.post(wooOtecMoodle.ajax_url, {
				action: 'woo_otec_update_field_mapping',
				nonce: wooOtecMoodle.nonce,
				field: fieldId,
				enable: enabled
			});
		},

		toggleMetadata: function(e) {},

		previewCourse: function(e) {
			const productId = $(e.target).value;
			if (!productId) {
				$('#wom-preview').hide();
				return;
			}

			$.post(wooOtecMoodle.ajax_url, {
				action: 'wom_load_product_preview',
				nonce: wooOtecMoodle.nonce,
				product_id: productId
			}, function(response) {
				if (response.success) {
					const product = wc_get_product( productId );
					const title = response.data.title || (typeof product !== 'undefined' ? product.name : 'Curso');
					$('#preview-title').text(title);
					$('#preview-content').html(response.data.html || response.data || '');
					$('#wom-preview').show();
				} else {
					console.error('Error al cargar preview:', response.data);
					$('#wom-preview').hide();
				}
			});
		},

		resetMappings: function(e) {
			e.preventDefault();
			if (!confirm('¿Restablecer a predeterminados?')) return;
			$.post(wooOtecMoodle.ajax_url, {
				action: 'woo_otec_reset_field_mappings',
				nonce: wooOtecMoodle.nonce
			}, function(response) {
				if (response.success) {
					alert('Mapeos restaurados correctamente');
					location.reload();
				} else {
					alert('Error al restaurar: ' + response.data);
				}
			});
		}
	};

	Tab.init();

})(jQuery);
</script>
