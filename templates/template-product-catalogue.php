<?php
/**
 * Template: Product Catalogue - MEJORADO
 * 
 * Plantilla para mostrar grid de productos/cursos con:
 * - Selector de cantidad
 * - Calificaciones (stars)
 * - Labels dinámicos
 * - Responsive grid
 * - Stock status
 *
 * @package WOO_OTEC_Moodle
 * @subpackage Templates
 * @version 3.0.9
 *
 * Variables disponibles:
 * @var array $courses Array de cursos
 * @var array $config Array de configuración
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Obtener configuración
$show_quantity = isset( $config['settings']['show_quantity'] ) ? $config['settings']['show_quantity'] : true;
$show_rating = isset( $config['settings']['show_rating'] ) ? $config['settings']['show_rating'] : true;
$show_labels = isset( $config['settings']['show_labels'] ) ? $config['settings']['show_labels'] : true;
$show_stock = isset( $config['settings']['show_stock'] ) ? $config['settings']['show_stock'] : true;
$show_description = isset( $config['settings']['show_description'] ) ? $config['settings']['show_description'] : true;

$button_text = $config['settings']['button_label'] ?? 'Agregar al Carrito';
$title = $config['settings']['title'] ?? 'Catálogo de Cursos';
$primary_color = $config['colors']['primary'] ?? '#6366f1';
?>

<div class="wom-catalogue-wrapper">
	<!-- HEADER -->
	<div class="wom-catalogue-header">
		<h2 class="wom-catalogue-title" style="color: <?php echo esc_attr( $primary_color ); ?>;">
			<?php echo esc_html( $title ); ?>
		</h2>
	</div>

	<?php if ( empty( $courses ) ) : ?>
		<!-- EMPTY STATE -->
		<div class="wom-empty-courses" style="padding: 40px; text-align: center; background: #f9fafb; border-radius: 8px;">
			<p style="color: #6b7280; font-size: 16px;">
				<?php esc_html_e( 'No hay cursos disponibles en este momento.', 'woo-otec-moodle' ); ?>
			</p>
		</div>
	<?php else : ?>
		<!-- PRODUCTOS GRID -->
		<div class="wom-courses-grid">
			<?php foreach ( $courses as $course ) :
				$course_id = $course['id'] ?? $course['course_id'] ?? null;
				$is_out_of_stock = isset( $course['stock'] ) && (int) $course['stock'] === 0;
				$has_discount = isset( $course['discount_percent'] ) && (int) $course['discount_percent'] > 0;
				?>
				<div class="wom-product-card <?php echo $is_out_of_stock ? 'wom-out-of-stock' : ''; ?>" data-product-id="<?php echo esc_attr( $course_id ); ?>">
					
					<!-- IMAGEN Y LABELS -->
					<div class="wom-product-image-wrapper">
						<?php
						$image_url = isset( $course['image_url'] ) ? $course['image_url'] : includes_url( 'images/media/default.png' );
						?>
						<img src="<?php echo esc_url( $image_url ); ?>" 
							 alt="<?php echo esc_attr( $course['fullname'] ?? 'Curso' ); ?>" 
							 class="wom-product-image">

						<!-- LABELS DINÁMICOS -->
						<?php if ( $show_labels ) : ?>
							<?php if ( $is_out_of_stock ) : ?>
								<div class="wom-product-label wom-label-out-of-stock">
									<?php esc_html_e( 'AGOTADO', 'woo-otec-moodle' ); ?>
								</div>
							<?php elseif ( $has_discount ) : ?>
								<div class="wom-product-label wom-label-sale">
									-<?php echo esc_html( $course['discount_percent'] ); ?>%
								</div>
							<?php endif; ?>
						<?php endif; ?>

						<!-- RATING STARS -->
						<?php if ( $show_rating && isset( $course['rating'] ) && $course['rating'] > 0 ) : ?>
							<div class="wom-product-rating">
								<?php
								$rating = (float) $course['rating'];
								for ( $i = 1; $i <= 5; $i++ ) :
									if ( $i <= $rating ) {
										echo '<i class="fas fa-star wom-star-filled"></i>';
									} elseif ( $i - $rating < 1 ) {
										echo '<i class="fas fa-star-half-alt wom-star-half"></i>';
									} else {
										echo '<i class="far fa-star wom-star-empty"></i>';
									}
								endfor;
								?>
								<span class="wom-rating-count">
									(<?php echo isset( $course['review_count'] ) ? esc_html( $course['review_count'] ) : '0'; ?>)
								</span>
							</div>
						<?php endif; ?>
					</div>

					<!-- CONTENIDO -->
					<div class="wom-product-content">
						<!-- CATEGORÍA -->
						<?php if ( isset( $config['settings']['show_category'] ) && $config['settings']['show_category'] ) : ?>
							<p class="wom-product-category">
								<?php echo esc_html( $course['categoryname'] ?? __( 'Sin categoría', 'woo-otec-moodle' ) ); ?>
							</p>
						<?php endif; ?>

						<!-- NOMBRE/TÍTULO -->
						<h3 class="wom-product-name">
							<?php echo esc_html( $course['fullname'] ?? 'Curso sin nombre' ); ?>
						</h3>

						<!-- DESCRIPCIÓN -->
						<?php if ( $show_description && isset( $course['summary'] ) ) : ?>
							<p class="wom-product-description">
								<?php echo esc_html( wp_trim_words( $course['summary'], 20 ) ); ?>
							</p>
						<?php endif; ?>

						<!-- PRECIO -->
						<div class="wom-product-price-section">
							<?php
							$regular_price = isset( $course['price'] ) ? $course['price'] : $course['regular_price'] ?? null;
							$sale_price = isset( $course['sale_price'] ) ? $course['sale_price'] : null;
							?>
							<?php if ( $sale_price && $sale_price < $regular_price ) : ?>
								<span class="wom-price wom-price-original">
									<?php echo isset( $course['currency'] ) ? esc_html( $course['currency'] ) : '$'; ?>
									<?php echo esc_html( number_format( $regular_price, 0, ',', '.' ) ); ?>
								</span>
								<span class="wom-price wom-price-sale">
									<?php echo isset( $course['currency'] ) ? esc_html( $course['currency'] ) : '$'; ?>
									<?php echo esc_html( number_format( $sale_price, 0, ',', '.' ) ); ?>
								</span>
							<?php elseif ( $regular_price ) : ?>
								<span class="wom-price">
									<?php echo isset( $course['currency'] ) ? esc_html( $course['currency'] ) : '$'; ?>
									<?php echo esc_html( number_format( $regular_price, 0, ',', '.' ) ); ?>
								</span>
							<?php else : ?>
								<span class="wom-price">Consultar</span>
							<?php endif; ?>
						</div>

						<!-- STOCK INFO -->
						<?php if ( $show_stock && isset( $course['stock'] ) ) : ?>
							<p class="wom-product-stock">
								<?php
								if ( (int) $course['stock'] > 0 ) {
									echo sprintf( __( 'Stock: %d unidades', 'woo-otec-moodle' ), (int) $course['stock'] );
								} else {
									echo __( 'Sin stock', 'woo-otec-moodle' );
								}
								?>
							</p>
						<?php endif; ?>

						<!-- SELECTOR DE CANTIDAD -->
						<?php if ( $show_quantity && ! $is_out_of_stock ) : ?>
							<div class="wom-quantity-selector">
								<button class="wom-qty-btn wom-qty-minus" 
										data-product-id="<?php echo esc_attr( $course_id ); ?>"
										title="<?php esc_attr_e( 'Disminuir cantidad', 'woo-otec-moodle' ); ?>">
									−
								</button>
								<input type="number" 
									   class="wom-qty-input" 
									   value="1" 
									   min="1" 
									   max="<?php echo isset( $course['stock'] ) ? esc_attr( $course['stock'] ) : '999'; ?>"
									   data-product-id="<?php echo esc_attr( $course_id ); ?>"
									   readonly>
								<button class="wom-qty-btn wom-qty-plus" 
										data-product-id="<?php echo esc_attr( $course_id ); ?>"
										title="<?php esc_attr_e( 'Aumentar cantidad', 'woo-otec-moodle' ); ?>">
									+
								</button>
							</div>
						<?php endif; ?>

						<!-- BOTÓN COMPRA/ENROLL -->
						<button class="wom-btn wom-btn-primary wom-add-to-cart" 
								data-product-id="<?php echo esc_attr( $course_id ); ?>"
								data-quantity="1"
								<?php echo $is_out_of_stock ? 'disabled' : ''; ?>
								style="background-color: <?php echo esc_attr( $primary_color ); ?>;">
							<?php echo $is_out_of_stock ? __( 'No disponible', 'woo-otec-moodle' ) : esc_html( $button_text ); ?>
							<span class="wom-btn-loader" style="display: none;">
								<span class="dashicons dashicons-update"></span>
							</span>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<!-- SCRIPT PARA INTERACTIVIDAD -->
<script>
(function($) {
	'use strict';

	// Manejadores de cantidad
	$(document).on('click', '.wom-qty-plus', function(e) {
		e.preventDefault();
		const $input = $(this).siblings('.wom-qty-input');
		const currentVal = parseInt($input.val()) || 1;
		const maxVal = parseInt($input.attr('max')) || 999;
		
		if (currentVal < maxVal) {
			$input.val(currentVal + 1);
			updateCartQuantity($input);
		}
	});

	$(document).on('click', '.wom-qty-minus', function(e) {
		e.preventDefault();
		const $input = $(this).siblings('.wom-qty-input');
		const currentVal = parseInt($input.val()) || 1;
		
		if (currentVal > 1) {
			$input.val(currentVal - 1);
			updateCartQuantity($input);
		}
	});

	// Actualizar cantidad en botón
	function updateCartQuantity($input) {
		const quantity = parseInt($input.val()) || 1;
		const productId = $input.data('product-id');
		const $btn = $(`.wom-add-to-cart[data-product-id="${productId}"]`);
		$btn.data('quantity', quantity).attr('data-quantity', quantity);
	}

	// Agregar al carrito
	$(document).on('click', '.wom-add-to-cart', function(e) {
		e.preventDefault();
		const $btn = $(this);
		const quantity = parseInt($btn.data('quantity')) || 1;
		
		if ($btn.prop('disabled')) return;
		
		$btn.prop('disabled', true);
		$btn.find('.wom-btn-loader').show();
		
		// Disparar evento customizado
		$(document).trigger('wom_add_to_cart', {
			product_id: $btn.data('product-id'),
			quantity: quantity
		});

		// Simular espera (2s)
		setTimeout(function() {
			$btn.prop('disabled', false);
			$btn.find('.wom-btn-loader').hide();
		}, 2000);
	});
})(jQuery);
</script>

<style>
.wom-catalogue-wrapper {
	max-width: 100%;
}

.wom-catalogue-header {
	margin-bottom: 30px;
}

.wom-catalogue-title {
	font-size: 28px;
	font-weight: 600;
	margin: 0;
}

.wom-courses-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.wom-product-card {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	overflow: hidden;
	transition: all 0.3s ease;
	display: flex;
	flex-direction: column;
}

.wom-product-card:hover {
	box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
	transform: translateY(-4px);
}

.wom-product-card.wom-out-of-stock {
	opacity: 0.6;
}

.wom-product-image-wrapper {
	position: relative;
	overflow: hidden;
	background: #f3f4f6;
	height: 220px;
}

.wom-product-image {
	width: 100%;
	height: 100%;
	object-fit: cover;
	transition: transform 0.3s ease;
}

.wom-product-card:hover .wom-product-image {
	transform: scale(1.05);
}

.wom-product-label {
	position: absolute;
	top: 10px;
	right: 10px;
	padding: 6px 12px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 600;
	z-index: 10;
}

.wom-label-sale {
	background: #ef4444;
	color: #fff;
}

.wom-label-out-of-stock {
	background: #6b7280;
	color: #fff;
}

.wom-product-rating {
	position: absolute;
	bottom: 10px;
	left: 10px;
	background: rgba(255, 255, 255, 0.95);
	padding: 6px 10px;
	border-radius: 4px;
	display: flex;
	align-items: center;
	gap: 4px;
	font-size: 12px;
}

.wom-star-filled { color: #f59e0b; }
.wom-star-half { color: #f59e0b; }
.wom-star-empty { color: #d1d5db; }

.wom-rating-count {
	font-size: 11px;
	color: #6b7280;
	margin-left: 4px;
}

.wom-product-content {
	padding: 16px;
	flex: 1;
	display: flex;
	flex-direction: column;
}

.wom-product-category {
	color: #6366f1;
	font-size: 12px;
	font-weight: 600;
	margin: 0 0 8px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.wom-product-name {
	font-size: 16px;
	font-weight: 600;
	margin: 0 0 8px;
	color: #1f2937;
	line-height: 1.4;
}

.wom-product-description {
	font-size: 13px;
	color: #6b7280;
	margin: 0 0 10px;
	line-height: 1.4;
}

.wom-product-price-section {
	display: flex;
	gap: 8px;
	margin-bottom: 10px;
	align-items: baseline;
}

.wom-price {
	font-weight: 600;
	font-size: 14px;
	color: #1f2937;
}

.wom-price-original {
	text-decoration: line-through;
	color: #9ca3af;
	font-weight: 400;
}

.wom-price-sale {
	color: #ef4444;
	font-size: 16px;
}

.wom-product-stock {
	font-size: 12px;
	color: #10b981;
	margin: 0 0 10px;
	font-weight: 500;
}

.wom-quantity-selector {
	display: flex;
	alignment-items: center;
	gap: 8px;
	margin-bottom: 12px;
	background: #f9fafb;
	padding: 6px;
	border-radius: 4px;
}

.wom-qty-btn {
	background: #fff;
	border: 1px solid #d1d5db;
	color: #1f2937;
	padding: 5px 8px;
	border-radius: 3px;
	cursor: pointer;
	font-weight: 600;
	transition: all 0.2s;
}

.wom-qty-btn:hover:not(:disabled) {
	background: #f3f4f6;
	border-color: #9ca3af;
}

.wom-qty-btn:disabled {
	opacity: 0.5;
	cursor: not-allowed;
}

.wom-qty-input {
	flex: 1;
	text-align: center;
	border: none;
	background: transparent;
	font-weight: 600;
	font-size: 14px;
}

.wom-qty-input:focus {
	outline: none;
}

.wom-btn-primary {
	width: 100%;
	padding: 10px 12px;
	border: none;
	border-radius: 4px;
	color: #fff;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 6px;
}

.wom-btn-primary:hover:not(:disabled) {
	opacity: 0.9;
	box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.wom-btn-primary:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

.wom-btn-loader {
	display: inline-flex;
	animation: spin 1s linear infinite;
}

@keyframes spin {
	to { transform: rotate(360deg); }
}

.wom-empty-courses {
	padding: 40px;
	text-align: center;
	background: #f9fafb;
	border-radius: 8px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
	.wom-courses-grid {
		grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		gap: 15px;
	}
	
	.wom-product-image-wrapper {
		height: 180px;
	}
	
	.wom-product-name {
		font-size: 14px;
	}
	
	.wom-product-price-section {
		font-size: 13px;
	}
}

@media (max-width: 480px) {
	.wom-courses-grid {
		grid-template-columns: 1fr;
	}
	
	.wom-catalogue-title {
		font-size: 20px;
	}
}
</style>
