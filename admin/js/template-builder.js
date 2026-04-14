(function($) {
	'use strict';

	const WomTemplateBuilder = {

		currentTemplate: null,
		debounceTimer: null,
		debounceDelay: 500,

		init: function() {

			const urlParams = new URLSearchParams(window.location.search);
			this.currentTemplate = urlParams.get('template') || 'product-catalogue';

			console.log('🚀 Template Builder init, template:', this.currentTemplate);
			console.log('🔌 AJAX disponible:', this.hasAjax());
			console.log('📋 wooOtecMoodle:', typeof wooOtecMoodle !== 'undefined' ? wooOtecMoodle : 'NO DEFINIDO');

			this.bindEvents();
			this.initPreviewAutoUpdate();

			// Carga inicial opcional (solo si AJAX existe)
			if (this.hasAjax()) {
				const self = this;
				setTimeout(function() {
					self.loadPreview();
				}, 300);
			}
		},

		hasAjax: function() {
			return (typeof wooOtecMoodle !== 'undefined' && wooOtecMoodle.ajax_url);
		},

		bindEvents: function() {

			const self = this;

			// Guardar configuración
			$(document).on('click', '.wom-btn-save', function(e) {
				e.preventDefault();
				self.saveConfiguration();
			});

			// Reset
			$(document).on('click', '.wom-btn-reset', function(e) {
				e.preventDefault();
				self.resetTemplate();
			});
		},

		initPreviewAutoUpdate: function() {

			const self = this;
			const formSelector = '#wom-template-form-' + this.currentTemplate;

			$(document).on('input change', formSelector + ' input', function() {

				clearTimeout(self.debounceTimer);

				self.debounceTimer = setTimeout(function() {
					self.loadPreview();
				}, self.debounceDelay);
			});
		},

		getCurrentConfig: function() {

			const form = $('#wom-template-form-' + this.currentTemplate);
			const config = { colors: {}, settings: {} };

			form.find('[name^="colors["]').each(function() {
				const match = this.name.match(/colors\[([^\]]+)\]/);
				if (match) {
					config.colors[match[1]] = this.value;
				}
			});

			form.find('[name^="settings["]').each(function() {
				const match = this.name.match(/settings\[([^\]]+)\]/);
				if (match) {
					config.settings[match[1]] = this.value;
				}
			});

			return config;
		},

		loadPreview: function() {

			if (!this.hasAjax()) {
				return;
			}

			const self = this;
			const previewSelector = '#wom-preview-' + this.currentTemplate;
			const $preview = $(previewSelector);

			if (!$preview.length) {
				return;
			}

			// Estado visual opcional (no destructivo)
			$preview.css('opacity', '0.6');

			$.ajax({
				url: wooOtecMoodle.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wom_preview_template',
					template_id: self.currentTemplate,
					config: JSON.stringify(self.getCurrentConfig()),
					nonce: wooOtecMoodle.nonce
				},

				success: function(response) {

					// Restaurar opacidad
					$preview.css('opacity', '1');

					// Validación estricta
					if (
						response &&
						response.success &&
						response.data &&
						response.data.html &&
						response.data.html.trim().length > 50
					) {
						// Usar .html() que respeta cascade de estilos
						$preview.html(response.data.html);
						
						// Forzar reflow para garantizar que los estilos se apliquen
						$preview[0].offsetHeight;
					}
				},

				error: function() {
					$preview.css('opacity', '1');
					console.warn('Preview no actualizado por error AJAX');
				}
			});
		},

		saveConfiguration: function() {

			if (!this.hasAjax()) {
				console.warn('Sin AJAX para guardar');
				return;
			}

			const self = this;
			const config = self.getCurrentConfig();
			
			console.log('Guardando configuración:', {
				template: self.currentTemplate,
				config: config
			});

			$.ajax({
				url: wooOtecMoodle.ajax_url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'wom_save_template_config',
					template_id: self.currentTemplate,
					config: JSON.stringify(config),
					nonce: wooOtecMoodle.nonce
				},

				success: function(response) {
					console.log('Respuesta del servidor:', response);
					if (response && response.success) {
						alert('✅ Configuración guardada correctamente');
					} else {
						console.error('Error en respuesta:', response);
						alert('❌ Error al guardar: ' + (response && response.data ? response.data : 'Error desconocido'));
					}
				},

				error: function(xhr, status, error) {
					console.error('Error AJAX:', {status: status, error: error, xhr: xhr});
					alert('❌ Error de conexión: ' + error);
				}
			});
		},

		resetTemplate: function() {
			location.reload();
		}

	};

	$(document).ready(function() {
		WomTemplateBuilder.init();
	});

})(jQuery);