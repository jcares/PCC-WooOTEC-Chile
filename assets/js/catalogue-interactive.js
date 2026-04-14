(function($) {
	'use strict';

	const WOMCatalogue = {
		config: {
			ajaxUrl: typeof wom_ajax !== 'undefined' ? wom_ajax.ajax_url : null,
			nonce: typeof wom_ajax !== 'undefined' ? wom_ajax.nonce : null,
		},

		init: function() {
			this.bindEvents();
			this.setupEventListeners();
		},

		bindEvents: function() {
			// Cantidad +
			$(document).on('click', '.wom-qty-plus', this.handleQtyPlus.bind(this));
			
			// Cantidad -
			$(document).on('click', '.wom-qty-minus', this.handleQtyMinus.bind(this));
			
			// Agregar al carrito
			$(document).on('click', '.wom-add-to-cart', this.handleAddToCart.bind(this));

			// Cambio en input de cantidad
			$(document).on('change', '.wom-qty-input', this.handleQtyChange.bind(this));
		},

		handleQtyPlus: function(e) {
			e.preventDefault();
			const $input = $(e.target).siblings('.wom-qty-input');
			const currentVal = parseInt($input.val()) || 1;
			const maxVal = parseInt($input.attr('max')) || 999;
			
			if (currentVal < maxVal) {
				$input.val(currentVal + 1).change();
			}
		},

		handleQtyMinus: function(e) {
			e.preventDefault();
			const $input = $(e.target).siblings('.wom-qty-input');
			const currentVal = parseInt($input.val()) || 1;
			
			if (currentVal > 1) {
				$input.val(currentVal - 1).change();
			}
		},

		handleQtyChange: function(e) {
			const $input = $(e.target);
			const quantity = parseInt($input.val()) || 1;
			const productId = $input.data('product-id');
			const $btn = $(`.wom-add-to-cart[data-product-id="${productId}"]`);
			
			$btn.data('quantity', quantity).attr('data-quantity', quantity);
		},

		handleAddToCart: function(e) {
			e.preventDefault();
			
			const $btn = $(e.target).closest('.wom-add-to-cart');
			if ($btn.prop('disabled')) return;

			const productId = $btn.data('product-id');
			const quantity = parseInt($btn.data('quantity')) || 1;

			this.addToCart(productId, quantity, $btn);
		},

		addToCart: function(productId, quantity, $btn) {
			$btn.prop('disabled', true);
			const $loader = $btn.find('.wom-btn-loader');
			$loader.show();

			// AJAX request si está disponible
			if (this.config.ajaxUrl && this.config.nonce) {
				$.ajax({
					type: 'POST',
					url: this.config.ajaxUrl,
					data: {
						action: 'wom_add_to_cart',
						nonce: this.config.nonce,
						product_id: productId,
						quantity: quantity,
					},
					success: (response) => {
						if (response.success) {
							this.showNotification('Producto agregado al carrito', 'success');
							$(document).trigger('wom_cart_updated', response.data);
						} else {
							this.showNotification(response.data?.message || 'Error al agregar producto', 'error');
						}
					},
					error: () => {
						this.showNotification('Error de conexión', 'error');
					},
					complete: () => {
						$btn.prop('disabled', false);
						$loader.hide();
					},
				});
			} else {
				// Fallback: Trigger evento customizado sin AJAX
				setTimeout(() => {
					$(document).trigger('wom_add_to_cart', {
						product_id: productId,
						quantity: quantity,
					});
					this.showNotification('Producto agregado (modo offline)', 'success');
					$btn.prop('disabled', false);
					$loader.hide();
				}, 500);
			}
		},

		showNotification: function(message, type = 'info') {
			const bgColor = {
				success: '#d1fae5',
				error: '#fee2e2',
				info: '#dbeafe',
			}[type] || '#f3f4f6';

			const textColor = {
				success: '#065f46',
				error: '#7f1d1d',
				info: '#0c4a6e',
			}[type] || '#374151';

			const html = `
				<div class="wom-notification wom-notification-${type}" style="
					position: fixed;
					top: 20px;
					right: 20px;
					background: ${bgColor};
					color: ${textColor};
					padding: 12px 16px;
					border-radius: 4px;
					box-shadow: 0 2px 8px rgba(0,0,0,0.1);
					z-index: 9999;
					animation: slideIn 0.3s ease;
				">
					${message}
				</div>
			`;

			const $notification = $(html);
			$('body').append($notification);

			setTimeout(() => {
				$notification.fadeOut(300, function() {
					$(this).remove();
				});
			}, 3000);
		},

		setupEventListeners: function() {
			// Evento customizado cuando se agrega al carrito
			$(document).on('wom_add_to_cart', (e, data) => {
				console.log('Producto agregado:', data);
				// Aquí se puede integrar con WooCommerce
			});

			// Evento cuando carrito se actualiza
			$(document).on('wom_cart_updated', (e, data) => {
				console.log('Carrito actualizado:', data);
				// Actualizar UI del carrito
			});
		},
	};

	// Inicializar cuando DOM esté listo
	$(document).ready(function() {
		WOMCatalogue.init();
	});

	// Exponer globalmente
	window.WOMCatalogue = WOMCatalogue;

})(jQuery);
