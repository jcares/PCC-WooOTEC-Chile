/**
 * Template Shortcodes Frontend
 * 
 * Gestiona interacciones del usuario con shortcodes de productos
 */

jQuery(document).ready(function($) {
	'use strict';

	// Función global para inscribirse en un producto
	window.wom_enroll_product = function(productId) {
		if (!productId) {
			alert('ID de producto no válido');
			return;
		}

		// Si el usuario no está logueado, redirigir a login
		if (!wooOtecMoodle.is_user_logged_in) {
			window.location.href = wp.template('login-url', { redirect_to: window.location.href });
			return;
		}

		// Mostrar diálogo de confirmación
		if (!confirm('¿Deseas inscribirte en este curso?')) {
			return;
		}

		// AJAX: Registrar inscripción
		$.ajax({
			url: wooOtecMoodle.ajax_url,
			type: 'POST',
			data: {
				action: 'wom_enroll_product',
				nonce: wooOtecMoodle.nonce,
				product_id: productId
			},
			beforeSend: function() {
				$('.wom-btn-enroll, .woom-enroll-btn').prop('disabled', true).css('opacity', '0.6');
			},
			success: function(response) {
				if (response.success) {
					// Mostrar mensaje de éxito
					alert('¡Inscripción exitosa! Revisa tu email para más información.');
					
					// Recargar página después de 2 segundos
					setTimeout(function() {
						window.location.reload();
					}, 2000);
				} else {
					alert('Error: ' + (response.data || 'No se pudo completar la inscripción'));
				}
			},
			error: function() {
				alert('Error de conexión. Intenta nuevamente.');
			},
			complete: function() {
				$('.wom-btn-enroll, .woom-enroll-btn').prop('disabled', false).css('opacity', '1');
			}
		});
	};

	// Hover effects en botones
	$(document).on('mouseenter', '.wom-btn-view', function() {
		$(this).css('background', '#e5e7eb');
	}).on('mouseleave', '.wom-btn-view', function() {
		$(this).css('background', '#f3f4f6');
	});

	// Animación de entrada para productos
	$('.wom-sample-product').each(function(index) {
		$(this).css({
			'opacity': '0',
			'transform': 'translateY(20px)'
		});
		
		setTimeout(function() {
			$(this).animate({
				'opacity': '1'
			}, 300, function() {
				$(this).css('transform', 'translateY(0)');
			});
		}.bind(this), index * 50);
	});
});
