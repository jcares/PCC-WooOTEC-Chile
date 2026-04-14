<?php
/**
 * Vista de Configuración General
 * 
 * ¿Qué hace?
 * - Token y URL de Moodle
 * - Opciones globales del plugin
 * 
 * ¿Qué debe funcionar?
 * ✅ Guardar token de Moodle
 * ✅ Guardar URL de Moodle
 * ✅ Botón "Probar Conexión" (AJAX)
 * ✅ Mostrar resultado de prueba (✓/✗)
 * ⚠️ TOKEN EXPUESTO: d4c5be6e5cefe4bbb025ae28ba5630df
 *    Debe ser regenerado en Moodle admin
 * 
 * v3.0.8
 */

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

// Obtener datos de API
$api_url   = get_option( 'woo_otec_moodle_api_url' );
$api_token = get_option( 'woo_otec_moodle_api_token' );
$role_id   = get_option( 'woo_otec_moodle_role_id', '5' );
$auto_sync = get_option( 'woo_otec_moodle_auto_sync', 'no' );

// Obtener datos de SSO
$sso_status = \Woo_OTEC_Moodle\SSO_Manager::get_status();
?>

<div class="wom-wrap">
	<!-- PESTAÑA: API CREDENTIALS -->
	<div class="wom-container">
		<h2><span class="dashicons dashicons-admin-links"></span> Conexión con Moodle</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'woo_otec_moodle_group' ); ?>

			<div class="wom-grid wom-grid-cols-2">
				<div class="wom-form-group">
					<label for="woo_otec_moodle_api_url">URL del Aula Virtual</label>
					<input type="url" name="woo_otec_moodle_api_url" id="woo_otec_moodle_api_url"
						   value="<?php echo esc_attr( $api_url ); ?>"
						   placeholder="https://moodle.ejemplo.com"
						   class="wom-input">
					<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0; line-height: 1.4;">
						URL base del servidor Moodle (sin barra final)
					</p>
				</div>

				<div class="wom-form-group">
					<label for="woo_otec_moodle_api_token">Token de Servicio Web</label>
					<div style="display: flex; gap: 6px;">
						<input type="password" name="woo_otec_moodle_api_token" id="woo_otec_moodle_api_token"
							   value="<?php echo esc_attr( $api_token ); ?>"
							   class="wom-input" style="flex: 1;">
						<button type="button" class="wom-toggle-password" data-target="#woo_otec_moodle_api_token"
								class="wom-btn wom-btn-secondary wom-btn-small"
								title="Mostrar/ocultar">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
					<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0;">
						Genéra en Administración > Usuarios > Cuentas > Weber/servicios > Web
					</p>
				</div>
			</div>

			<div class="wom-grid wom-grid-cols-2">
				<div class="wom-form-group">
					<label for="woo_otec_moodle_role_id">ID Rol de Estudiante</label>
					<input type="number" name="woo_otec_moodle_role_id" id="woo_otec_moodle_role_id"
						   value="<?php echo esc_attr( $role_id ); ?>"
						   min="1" max="99"
						   class="wom-input" style="max-width: 100px;">
					<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0;">
						Típicamente es 5 (Estudiante)
					</p>
				</div>

				<div class="wom-checkbox-group">
				<label for="woo_otec_moodle_auto_sync">
					<input type="checkbox" id="woo_otec_moodle_auto_sync" name="woo_otec_moodle_auto_sync" value="yes"
							   <?php checked( $auto_sync, 'yes' ); ?>
							   class="wom-checkbox">
						Habilitar auto-matrícula al comprar
					</label>
					<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0;">
						Inscribir automáticamente a usuarios en cursos comprados
					</p>
				</div>
			</div>

			<div class="wom-actions-row">
				<button type="submit" class="wom-btn wom-btn-primary">
					<span class="dashicons dashicons-cloud-upload"></span> Guardar Configuración
				</button>
				<button type="button" id="wom-test-connection" class="wom-btn wom-btn-secondary">
					<span class="dashicons dashicons-update-alt"></span> Probar Conexión
				</button>
				<div id="wom-test-result"></div>
			</div>
		</form>
	</div>

	<!-- PESTAÑA: SINGLE SIGN-ON (SSO) -->
	<div class="wom-container" style="margin-top: 30px;">
		<h2><span class="dashicons dashicons-lock-duplicate"></span> Single Sign-On (SSO)</h2>
		<p style="color: var(--wom-text-muted); margin: 0 0 20px;">
			Acceso automático a Moodle desde WooCommerce sin requerir credenciales
		</p>

		<!-- Estado SSO -->
		<div class="wom-grid wom-grid-cols-2" style="margin-bottom: 20px;">
			<div class="wom-status-card <?php echo $sso_status['enabled'] ? 'success' : 'danger'; ?>">
				<div class="wom-status-card-label">Estado</div>
				<div class="wom-status-card-value">
					<?php echo $sso_status['enabled'] ? '✓ ACTIVO' : '✗ INACTIVO'; ?>
				</div>
			</div>

			<div class="wom-status-card <?php echo $sso_status['configured'] ? 'success' : 'warning'; ?>">
				<div class="wom-status-card-label">URL Configurada</div>
				<div class="wom-status-card-value" style="font-size: 12px;">
					<?php echo $sso_status['configured'] ? esc_html( $sso_status['base_url'] ) : 'No configurada'; ?>
				</div>
			</div>
		</div>

		<!-- Formulario SSO -->
		<form id="sso-settings-form" class="wom-form-group">
			<div class="wom-form-group">
				<label for="sso_base_url">URL Base de Moodle</label>
				<input type="url" id="sso_base_url" name="sso_base_url"
					   value="<?php echo esc_attr( $sso_status['base_url'] ); ?>"
					   placeholder="https://moodle.ejemplo.com"
					   class="wom-input">
				<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0;">
					Ejemplo: https://moodle.ejemplo.com (accesible públicamente)
				</p>
			</div>

			<label for="sso_enabled" class="wom-checkbox-group">
				<input type="checkbox" id="sso_enabled" name="sso_enabled" value="1"
					   <?php checked( $sso_status['enabled'] ); ?>
					   class="wom-checkbox">
				Habilitar SSO - Los usuarios recibirán enlaces de acceso directo
			</label>

			<div class="wom-actions-row" style="margin-top: 16px;">
				<button type="submit" class="wom-btn wom-btn-primary">
					<span class="dashicons dashicons-cloud-upload"></span> Guardar
				</button>
				<div id="sso-result-message"></div>
			</div>
		</form>

		<!-- Info Boxes -->
		<div style="display: grid; gap: 12px; margin-top: 20px;">
			<div class="wom-alert wom-alert-info">
				<span class="dashicons dashicons-info wom-alert-icon"></span>
				<div class="wom-alert-content">
					<div class="wom-alert-title">¿Cómo funciona?</div>
					<p class="wom-alert-message" style="margin: 0;">
						1) Usuario compra en WooCommerce. 2) Se genera una URL única de acceso. 3) Recibe email con botón "Acceder a Moodle". 4) Click en botón = acceso automático sin contraseña.
					</p>
				</div>
			</div>

			<div class="wom-alert wom-alert-warning">
				<span class="dashicons dashicons-warning wom-alert-icon"></span>
				<div class="wom-alert-content">
					<div class="wom-alert-title">Consideraciones</div>
					<p class="wom-alert-message" style="margin: 0;">
						• Moodle debe ser accesible públicamente • Requiere autenticación por email en Moodle • Recomendamos usar HTTPS • URLs válidas mientras exista el pedido
					</p>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function($) {
	'use strict';

	// Test de conexión
	$(document).on('click', '#wom-test-connection', function(e) {
		e.preventDefault();
		const $btn = $(this);
		const $result = $('#wom-test-result');

		$btn.prop('disabled', true).appendChild( document.createElement('span') ).className = 'wom-spinner';
		$result.empty();

		$.ajax({
			url: wooOtecMoodle.ajax_url,
			type: 'POST',
			data: {
				action: 'woo_otec_test_connection',
				nonce: wooOtecMoodle.nonce
			},
			success: function(response) {
				if (response.success) {
					$result.html( '<div class="wom-alert wom-alert-success"><span class="dashicons dashicons-yes wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">' + response.data + '</p></div></div>' );
				} else {
					$result.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">' + response.data + '</p></div></div>' );
				}
			},
			error: function() {
				$result.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Error de conexión</p></div></div>' );
			},
			complete: function() {
				$btn.prop('disabled', false).find('.wom-spinner').remove();
			}
		});
	});

	// Guardar SSO settings
	$(document).on('submit', '#sso-settings-form', function(e) {
		e.preventDefault();
		const $form = $(this);
		const $msg = $('#sso-result-message');

		const baseUrl = $('#sso_base_url').val().trim();
		const enabled = $('#sso_enabled').is(':checked');

		if (enabled && !baseUrl) {
			$msg.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Ingresa la URL base de Moodle</p></div></div>' );
			return;
		}

		$msg.html( '<div class="wom-loading"><span class="wom-spinner"></span> Guardando...</div>' ).show();

		$.ajax({
			url: wooOtecMoodle.ajax_url,
			type: 'POST',
			data: {
				action: 'woo_otec_save_sso_settings',
				nonce: wooOtecMoodle.nonce,
				enabled: enabled ? 1 : 0,
				base_url: baseUrl
			},
			success: function(response) {
				if (response.success) {
					$msg.html( '<div class="wom-alert wom-alert-success"><span class="dashicons dashicons-yes wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">✓ Configuración guardada</p></div></div>' );
					setTimeout(() => location.reload(), 1500);
				} else {
					$msg.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Error: ' + response.data + '</p></div></div>' );
				}
			},
			error: function() {
				$msg.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Error de conexión</p></div></div>' );
			}
		});
	});

	// Toggle password visibility
	$(document).on('click', '.wom-toggle-password', function() {
		const target = $(this).data('target');
		const $input = $(target);
		const isPassword = $input.attr('type') === 'password';

		$input.attr('type', isPassword ? 'text' : 'password');
	});
})( jQuery );
</scriptiv class="wom-form-group">
					<label for="woo_otec_moodle_role_id">ID Rol de Estudiante</label>
					<input type="number" name="woo_otec_moodle_role_id" id="woo_otec_moodle_role_id"
						   value="<?php echo esc_attr( $role_id ); ?>"
						   min="1" max="99"
						   class="wom-input" style="max-width: 100px;">
					<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0;">
						Típicamente es 5 (Estudiante)
					</p>
				</div>

				<div class="wom-checkbox-group">
					<label>
						<input type="checkbox" name="woo_otec_moodle_auto_sync" value="yes"
							   <?php checked( $auto_sync, 'yes' ); ?>
							   class="wom-checkbox">
						Habilitar auto-matrícula al comprar
					</label>
					<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0;">
						Inscribir automáticamente a usuarios en cursos comprados
					</p>
				</div>
			</div>

			<div class="wom-actions-row">
				<button type="submit" class="wom-btn wom-btn-primary">
					<span class="dashicons dashicons-cloud-upload"></span> Guardar Configuración
				</button>
				<button type="button" id="wom-test-connection" class="wom-btn wom-btn-secondary">
					<span class="dashicons dashicons-update-alt"></span> Probar Conexión
				</button>
				<div id="wom-test-result"></div>
			</div>
		</form>
	</div>

	<!-- PESTAÑA: SINGLE SIGN-ON (SSO) -->
	<div class="wom-container" style="margin-top: 30px;">
		<h2><span class="dashicons dashicons-lock-duplicate"></span> Single Sign-On (SSO)</h2>
		<p style="color: var(--wom-text-muted); margin: 0 0 20px;">
			Acceso automático a Moodle desde WooCommerce sin requerir credenciales
		</p>

		<!-- Estado SSO -->
		<div class="wom-grid wom-grid-cols-2" style="margin-bottom: 20px;">
			<div class="wom-status-card <?php echo $sso_status['enabled'] ? 'success' : 'danger'; ?>">
				<div class="wom-status-card-label">Estado</div>
				<div class="wom-status-card-value">
					<?php echo $sso_status['enabled'] ? '✓ ACTIVO' : '✗ INACTIVO'; ?>
				</div>
			</div>

			<div class="wom-status-card <?php echo $sso_status['configured'] ? 'success' : 'warning'; ?>">
				<div class="wom-status-card-label">URL Configurada</div>
				<div class="wom-status-card-value" style="font-size: 12px;">
					<?php echo $sso_status['configured'] ? esc_html( $sso_status['base_url'] ) : 'No configurada'; ?>
				</div>
			</div>
		</div>

		<!-- Formulario SSO -->
		<form id="sso-settings-form" class="wom-form-group">
			<div class="wom-form-group">
				<label for="sso_base_url">URL Base de Moodle</label>
				<input type="url" id="sso_base_url" name="sso_base_url"
					   value="<?php echo esc_attr( $sso_status['base_url'] ); ?>"
					   placeholder="https://moodle.ejemplo.com"
					   class="wom-input">
				<p style="font-size: 11px; color: var(--wom-text-muted); margin: 4px 0 0;">
					Ejemplo: https://moodle.ejemplo.com (accesible públicamente)
				</p>
			</div>

			<label for="sso_enabled" class="wom-checkbox-group">
				<input type="checkbox" id="sso_enabled" name="sso_enabled" value="1"
					   <?php checked( $sso_status['enabled'] ); ?>
					   class="wom-checkbox">
				Habilitar SSO - Los usuarios recibirán enlaces de acceso directo
			</label>

			<div class="wom-actions-row" style="margin-top: 16px;">
				<button type="submit" class="wom-btn wom-btn-primary">
					<span class="dashicons dashicons-cloud-upload"></span> Guardar
				</button>
				<div id="sso-result-message"></div>
			</div>
		</form>

		<!-- Info Boxes -->
		<div style="display: grid; gap: 12px; margin-top: 20px;">
			<div class="wom-alert wom-alert-info">
				<span class="dashicons dashicons-info wom-alert-icon"></span>
				<div class="wom-alert-content">
					<div class="wom-alert-title">¿Cómo funciona?</div>
					<p class="wom-alert-message" style="margin: 0;">
						1) Usuario compra en WooCommerce. 2) Se genera una URL única de acceso. 3) Recibe email con botón "Acceder a Moodle". 4) Click en botón = acceso automático sin contraseña.
					</p>
				</div>
			</div>

			<div class="wom-alert wom-alert-warning">
				<span class="dashicons dashicons-warning wom-alert-icon"></span>
				<div class="wom-alert-content">
					<div class="wom-alert-title">Consideraciones</div>
					<p class="wom-alert-message" style="margin: 0;">
						• Moodle debe ser accesible públicamente • Requiere autenticación por email en Moodle • Recomendamos usar HTTPS • URLs válidas mientras exista el pedido
					</p>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function($) {
	'use strict';

	// Test de conexión
	$(document).on('click', '#wom-test-connection', function(e) {
		e.preventDefault();
		const $btn = $(this);
		const $result = $('#wom-test-result');

		$btn.prop('disabled', true).appendChild( document.createElement('span') ).className = 'wom-spinner';
		$result.empty();

		$.ajax({
			url: wooOtecMoodle.ajax_url,
			type: 'POST',
			data: {
				action: 'woo_otec_test_connection',
				nonce: wooOtecMoodle.nonce
			},
			success: function(response) {
				if (response.success) {
					$result.html( '<div class="wom-alert wom-alert-success"><span class="dashicons dashicons-yes wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">' + response.data + '</p></div></div>' );
				} else {
					$result.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">' + response.data + '</p></div></div>' );
				}
			},
			error: function() {
				$result.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Error de conexión</p></div></div>' );
			},
			complete: function() {
				$btn.prop('disabled', false).find('.wom-spinner').remove();
			}
		});
	});

	// Guardar SSO settings
	$(document).on('submit', '#sso-settings-form', function(e) {
		e.preventDefault();
		const $form = $(this);
		const $msg = $('#sso-result-message');

		const baseUrl = $('#sso_base_url').val().trim();
		const enabled = $('#sso_enabled').is(':checked');

		if (enabled && !baseUrl) {
			$msg.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Ingresa la URL base de Moodle</p></div></div>' );
			return;
		}

		$msg.html( '<div class="wom-loading"><span class="wom-spinner"></span> Guardando...</div>' ).show();

		$.ajax({
			url: wooOtecMoodle.ajax_url,
			type: 'POST',
			data: {
				action: 'woo_otec_save_sso_settings',
				nonce: wooOtecMoodle.nonce,
				enabled: enabled ? 1 : 0,
				base_url: baseUrl
			},
			success: function(response) {
				if (response.success) {
					$msg.html( '<div class="wom-alert wom-alert-success"><span class="dashicons dashicons-yes wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">✓ Configuración guardada</p></div></div>' );
					setTimeout(() => location.reload(), 1500);
				} else {
					$msg.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Error: ' + response.data + '</p></div></div>' );
				}
			},
			error: function() {
				$msg.html( '<div class="wom-alert wom-alert-danger"><span class="dashicons dashicons-warning wom-alert-icon"></span><div class="wom-alert-content"><p class="wom-alert-message">Error de conexión</p></div></div>' );
			}
		});
	});

	// Toggle password visibility
	$(document).on('click', '.wom-toggle-password', function() {
		const target = $(this).data('target');
		const $input = $(target);
		const isPassword = $input.attr('type') === 'password';

		$input.attr('type', isPassword ? 'text' : 'password');
	});
})( jQuery );
</script>

<div class="wom-footer">
    Woo OTEC Moodle v<?php echo WOO_OTEC_MOODLE_VERSION; ?>
</div>

</div> <!-- Close wom-wrap -->

