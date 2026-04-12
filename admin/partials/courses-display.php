<?php
/**
 * Vista de Cursos - Sincronización con Moodle
 */

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

// Obtener cursos sincronizados
$products = get_posts( array(
    'post_type'      => 'product',
    'meta_key'       => '_moodle_course_id',
    'numberposts'   => -1,
) );
?>

<div class="wom-wrap">
	<div class="wom-section">
		<h2><span class="dashicons dashicons-welcome-learn-more"></span> Cursos Sincronizados</h2>
		
		<!-- Botón de sincronización -->
		<div style="margin-bottom: 20px;">
			<button type="button" id="wom-sync-now" class="wom-btn wom-btn-info">
				<span class="dashicons dashicons-update"></span> Sincronizar desde Moodle
			</button>
			<div id="sync-message" style="margin-top: 10px;"></div>
		</div>

		<!-- Tabla de cursos -->
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 80px;">ID Moodle</th>
					<th>Nombre</th>
					<th>Descripción</th>
					<th style="width: 120px;">Acciones</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $products ) ) : ?>
					<?php foreach ( $products as $product ) :
						$moodle_id = get_post_meta( $product->ID, '_moodle_course_id', true );
					?>
					<tr>
						<td><strong><?php echo esc_html( $moodle_id ); ?></strong></td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $product->ID ) ); ?>">
								<?php echo esc_html( $product->post_title ); ?>
							</a>
						</td>
						<td><small><?php echo wp_trim_words( $product->post_excerpt, 20 ); ?></small></td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $product->ID ) ); ?>" class="button button-secondary">Editar</a>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4" style="text-align: center; padding: 20px; color: #999;">
							No hay cursos sincronizados. Haz clic en "Sincronizar desde Moodle".
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<script>
(function($) {
	'use strict';

	$('#wom-sync-now').on('click', function() {
		const btn = $(this);
		btn.prop('disabled', true);

		$.post(wooOtecMoodle.ajax_url, {
			action: 'woo_otec_sync_courses',
			nonce: wooOtecMoodle.nonce
		}, function(response) {
			if (response.success) {
				$('#sync-message').html('<div class="notice notice-success"><p>✓ Sincronización completada: ' + response.data.synced + ' curso(s)</p></div>');
				setTimeout(function() {
					location.reload();
				}, 2000);
			} else {
				$('#sync-message').html('<div class="notice notice-error"><p>✗ Error: ' + response.data + '</p></div>');
			}
		}).always(function() {
			btn.prop('disabled', false);
		});
	});

})(jQuery);
</script>
