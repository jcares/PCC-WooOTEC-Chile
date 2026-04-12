/**
 * Template Builder JavaScript
 * 
 * Maneja:
 * - Live preview en tiempo real
 * - Guardado via AJAX
 * - Color picker integration
 * - Debouncing para optimización
 */

(function($) {
	'use strict';

	const WomTemplateBuilder = {
		// Configuración
		debounceDelay: 500,
		debounceTimer: null,
		currentTemplate: null,
		previewFrame: null,
		previewDoc: null,

		// Inicialización
		init: function() {
			// Verificar que wooOtecMoodle esté disponible
			if (typeof wooOtecMoodle === 'undefined') {
				console.error('wooOtecMoodle no está definido. Verifica la localización del script.');
				return;
			}

			// Determinar template activo
			const urlParams = new URLSearchParams(window.location.search);
			this.currentTemplate = urlParams.get('template') || 'product-catalogue';

			console.log('Template Builder inicializado - Template activo:', this.currentTemplate);

			// Setup jQuery color picker para inputs con clase .wom-color-picker
			this.setupColorPickersWP();

			// Event listeners
			this.setupEventListeners();

			// Cargar preview inicial (después de pequeño delay para asegurar DOM listo)
			const self = this;
			setTimeout(() => {
				self.loadPreview();
			}, 300);
		},

		// Setup de color pickers de WordPress
		setupColorPickersWP: function() {
			if (typeof wp === 'undefined' || typeof wp.color === 'undefined') {
				console.warn('WordPress color picker no disponible');
				return;
			}

			console.log('Inicializando color pickers...');
			$('.wom-color-picker').each(function() {
				console.log('Configurando color picker para:', this.id);
				wp.color.picker(this, {
					change: function(color) {
						console.log('Color cambiado:', color.toString());
						WomTemplateBuilder.onFieldChange();
					}
				});
			});
		},

		// Setup de event listeners
		setupEventListeners: function() {
			const self = this;

			// Cambio de pestañas de plantilla
			$(document).on('click', '.wom-tab', function(e) {
				e.preventDefault();
				const href = $(this).attr('href');
				window.location.href = href; // Cambiar a la nueva URL para recargar con la plantilla seleccionada
			});

			// Cambios en inputs de color
			$(document).on('change', '.wom-color-picker', function() {
				self.onFieldChange();
			});

			// Cambios en inputs de texto
			$(document).on('input', '.wom-text-input', function() {
				self.debouncePreviewUpdate();
			});

			// Cambios en select/checkbox
			$(document).on('change', '.wom-select-input, .wom-checkbox', function() {
				self.onFieldChange();
			});

			// Cambios en inputs de número
			$(document).on('change', '.wom-number-input', function() {
				self.onFieldChange();
			});

			// Cambio de producto para preview de sample-product
			$(document).on('change', '#sample-product-select', function() {
				self.onProductChange();
			});

			// Botón guardar
			$(document).on('click', '.wom-btn-save', function(e) {
				e.preventDefault();
				self.saveConfiguration();
			});

			// Botón resetear
			$(document).on('click', '.wom-btn-reset', function(e) {
				e.preventDefault();
				if (confirm('¿Estás seguro de que deseas restaurar los valores por defecto?')) {
					self.resetTemplate();
				}
			});

			// Upload de imagen
			$(document).on('click', '.wom-btn-upload-image', function(e) {
				e.preventDefault();
				self.openMediaUploader();
			});
		},

		// Manejo de cambio de producto para preview
		onProductChange: function() {
			const productId = $('#sample-product-select').val();
			if (productId) {
				$('#wom-preview-placeholder').hide();
				this.loadPreview();
			} else {
				$('#wom-preview-placeholder').show();
				$('#wom-preview-iframe-sample-product').attr('src', 'about:blank');
			}
		},

		// Manejo de cambios en campos
		onFieldChange: function() {
			clearTimeout(this.debounceTimer);
			this.debounceTimer = setTimeout(() => {
				this.loadPreview();
			}, this.debounceDelay);
		},

		// Debounce para preview
		debouncePreviewUpdate: function() {
			clearTimeout(this.debounceTimer);
			this.debounceTimer = setTimeout(() => {
				this.loadPreview();
			}, this.debounceDelay);
		},

		// Obtener configuración actual del formulario
		getCurrentConfig: function() {
			const form = $(`#wom-template-form-${this.currentTemplate}`);
			const config = {
				colors: {},
				typography: {},
				settings: {}
			};

			// Colores
			form.find('[name^="colors["]').each(function() {
				const name = $(this).attr('name').match(/colors\[([^\]]+)\]/)[1];
				config.colors[name] = $(this).val();
			});

			// Textos y parámetros
			form.find('[name^="settings["]').each(function() {
				const name = $(this).attr('name').match(/settings\[([^\]]+)\]/)[1];
				const $input = $(this);

				if ($input.is(':checkbox')) {
					config.settings[name] = $input.is(':checked');
				} else if ($input.is('[type="number"]')) {
					config.settings[name] = parseInt($input.val()) || 0;
				} else {
					config.settings[name] = $input.val();
				}
			});

			return config;
		},

		// Cargar preview actualmente
		loadPreview: function() {
			const self = this;
			const config = this.getCurrentConfig();
			const productId = this.currentTemplate === 'sample-product' ? $('#sample-product-select').val() : null;

			// Si es sample-product y no hay producto seleccionado, usar placeholder
			if (this.currentTemplate === 'sample-product' && !productId) {
				$('#wom-preview-' + this.currentTemplate).html(
					'<div style="padding: 20px; text-align: center; color: #999;">Selecciona un producto para ver el preview</div>'
				);
				return;
			}

			// Mostrar loading
			const $loadingEl = $('#wom-preview-loading');
			if ($loadingEl.length) {
				$loadingEl.show();
			}

			console.log('Cargando preview para:', this.currentTemplate, 'Config:', config);

			return $.ajax({
				url: wooOtecMoodle.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wom_preview_template',
					template_id: self.currentTemplate,
					config: JSON.stringify(config),
					product_id: productId,
					nonce: wooOtecMoodle.nonce
				},
				timeout: 10000,
				success: function(response) {
					console.log('Preview response:', response);
					if (response.success && response.data && response.data.html) {
						self.renderPreview(response.data.html);
					} else {
						console.error('Response inválida:', response);
						$('#wom-preview-' + self.currentTemplate).html(
							'<div style="padding: 20px; text-align: center; color: #d32f2f;">Error al generar preview</div>'
						);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error('Error al cargar preview:', textStatus, errorThrown);
					$('#wom-preview-' + self.currentTemplate).html(
						'<div style="padding: 20px; text-align: center; color: #d32f2f;">Error: ' + textStatus + '</div>'
					);
				},
				complete: function() {
					if ($loadingEl.length) {
						$loadingEl.hide();
					}
				}
			});
		},

		// Renderizar preview en cuadro específico (no iframe)
		renderPreview: function(html) {
			const previewSelector = '#wom-preview-' + this.currentTemplate;
			const $preview = $(previewSelector);

			if ($preview.length === 0) {
				console.warn('Preview container no encontrado:', previewSelector);
				return;
			}

			try {
				$preview.html(html);
				console.log('Preview renderizado exitosamente');
			} catch (e) {
				console.error('Error al escribir preview:', e);
				$preview.html('<div style="color: #d32f2f;">Error al renderizar: ' + e.message + '</div>');
			}
		},

		// Estilos base del iframe
		getIframeBaseStyles: function() {
			return `
				* {
					box-sizing: border-box;
				}
				body {
					margin: 0;
					padding: 20px;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, sans-serif;
				}
				.wom-btn:hover {
					opacity: 0.9;
				}
			`;
		},

		// Guardar configuración via AJAX
		saveConfiguration: function() {
			const self = this;
			const config = this.getCurrentConfig();
			const $btn = $('.wom-btn-save');
			const originalText = $btn.text();

			// Cambiar estado del botón
			$btn.prop('disabled', true).text('Guardando...');

			$.ajax({
				url: wooOtecMoodle.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wom_save_template_config',
					template_id: this.currentTemplate,
					config: JSON.stringify(config),
					nonce: wooOtecMoodle.nonce
				},
				success: function(response) {
					if (response.success) {
						self.showNotification('Configuración guardada exitosamente', 'success');
						$btn.prop('disabled', false).text(originalText);
					} else {
						self.showNotification('Error al guardar: ' + response.data, 'error');
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					self.showNotification('Error de conexión', 'error');
					$btn.prop('disabled', false).text(originalText);
				}
			});
		},

		// Resetear template a valores por defecto
		resetTemplate: function() {
			const self = this;

			$.ajax({
				url: wooOtecMoodle.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wom_reset_template',
					template_id: this.currentTemplate,
					nonce: wooOtecMoodle.nonce
				},
				success: function(response) {
					if (response.success) {
						const defaults = response.data.defaults;

						// Actualizar inputs con valores por defecto
						const form = $(`#wom-template-form-${self.currentTemplate}`);
						
						// Colors
						Object.keys(defaults.colors).forEach(key => {
							form.find(`[name="colors[${key}]"]`).val(defaults.colors[key]);
						});

						// Settings
						Object.keys(defaults.settings).forEach(key => {
							const $input = form.find(`[name="settings[${key}]"]`);
							if ($input.is(':checkbox')) {
								$input.prop('checked', defaults.settings[key]);
							} else {
								$input.val(defaults.settings[key]);
							}
						});

						self.loadPreview();
						self.showNotification('Valores restaurados a por defecto', 'success');
					}
				},
				error: function() {
					self.showNotification('Error al resetear', 'error');
				}
			});
		},

		// Abrir media uploader
		openMediaUploader: function() {
			const self = this;

			if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
				console.warn('WordPress media API no disponible');
				return;
			}

			const frame = wp.media({
				title: 'Seleccionar Imagen por Defecto',
				multiple: false,
				library: {
					type: 'image'
				},
				button: {
					text: 'Establecer Imagen'
				}
			});

			frame.on('select', function() {
				const attachment = frame.state().get('selection').first().toJSON();
				
				// Guardar ID del attachment
				$(`#wom-template-form-${self.currentTemplate}`).find('[name="settings[default_image]"]').val(attachment.id);
				
				// Actualizar previsualización de imagen
				const previewContainer = $('#image-preview-container');
				previewContainer.html(`
					<img src="${attachment.url}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
				`);

				self.onFieldChange();
			});

			frame.open();
		},

		// Mostrar notificación
		showNotification: function(message, type = 'info') {
			const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
			const $notification = $(`
				<div style="
					position: fixed;
					top: 20px;
					right: 20px;
					background: ${bgColor};
					color: white;
					padding: 15px 20px;
					border-radius: 6px;
					box-shadow: 0 4px 12px rgba(0,0,0,0.15);
					z-index: 9999;
					max-width: 350px;
					word-wrap: break-word;
				">
					${message}
				</div>
			`);

			$('body').append($notification);

			setTimeout(() => {
				$notification.fadeOut(300, function() {
					$(this).remove();
				});
			}, 3000);
		}
	};

	// Iniciar cuando el documento esté listo
	$(document).ready(function() {
		WomTemplateBuilder.init();
	});

})(jQuery);
