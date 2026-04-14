<?php
/**
 * Página de WooCommerce
 * 
 * ¿Qué hace?
 * - Configuración específica de integración WooCommerce
 * - Mapeo de categorías y atributos
 * 
 * ¿Qué debe funcionar?
 * ✅ Configuración de categoría de cursos
 * ✅ Mapeo de atributos (precio, descripción, etc.)
 * ✅ Opciones de carrito y checkout
 */
include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

// Obtener estadísticas reales
$total_products = count( get_posts( array( 'post_type' => 'product', 'numberposts' => -1 ) ) );
$linked_products = count( get_posts( array( 'post_type' => 'product', 'meta_key' => '_moodle_course_id', 'numberposts' => -1 ) ) );
$unlinked_products = $total_products - $linked_products;
?>

<div class="wom-wrap" style="max-width: 900px;">
    <div style="padding-bottom: 12px; border-bottom: 2px solid #4f46e5; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #1f2937; font-size: 24px;">WooCommerce - Configuración</h2>
        <p style="margin: 4px 0 0; color: #6b7280; font-size: 14px;">Ajustes del flujo de compra y sincronización automática</p>
    </div>

    <!-- ESTADÍSTICAS RÁPIDAS -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px;">
        <div style="background: #f0f4ff; padding: 12px; border-radius: 6px; border-left: 4px solid #6366f1; text-align: center;">
            <p style="margin: 0 0 4px; font-size: 11px; text-transform: uppercase; color: #6366f1; font-weight: 600;">Total Productos</p>
            <p style="margin: 0; font-size: 20px; font-weight: 700; color: #6366f1;"><?php echo esc_html( $total_products ); ?></p>
        </div>
        <div style="background: #d1fae5; padding: 12px; border-radius: 6px; border-left: 4px solid #10b981; text-align: center;">
            <p style="margin: 0 0 4px; font-size: 11px; text-transform: uppercase; color: #10b981; font-weight: 600;">Vinculados</p>
            <p style="margin: 0; font-size: 20px; font-weight: 700; color: #10b981;"><?php echo esc_html( $linked_products ); ?></p>
        </div>
        <div style="background: #fee2e2; padding: 12px; border-radius: 6px; border-left: 4px solid #dc2626; text-align: center;">
            <p style="margin: 0 0 4px; font-size: 11px; text-transform: uppercase; color: #dc2626; font-weight: 600;">Sin vincular</p>
            <p style="margin: 0; font-size: 20px; font-weight: 700; color: #dc2626;"><?php echo esc_html( $unlinked_products ); ?></p>
        </div>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'woo_otec_moodle_group' ); ?>

        <!-- SECCIÓN: Colores del Catálogo -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 600; color: #1f2937;">Colores del Catálogo</h3>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div>
                    <label for="woo_otec_catalog_primary" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 12px;">Color Primario</label>
                    <input type="color" id="woo_otec_catalog_primary" name="woo_otec_catalog_primary" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" />
                </div>
                <div>
                    <label for="woo_otec_catalog_success" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 12px;">Color de Éxito</label>
                    <input type="color" id="woo_otec_catalog_success" name="woo_otec_catalog_success" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" />
                </div>
                <div>
                    <label for="woo_otec_catalog_bg" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 12px;">Fondo</label>
                    <input type="color" id="woo_otec_catalog_bg" name="woo_otec_catalog_bg" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" />
                </div>
                <div>
                    <label for="woo_otec_catalog_text" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 12px;">Texto Principal</label>
                    <input type="color" id="woo_otec_catalog_text" name="woo_otec_catalog_text" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;" />
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Estados de Pedido -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #1f2937;">Estados de Pedido</h3>
            
            <label for="woo_otec_trigger_status" style="display: block; font-size: 12px; margin-bottom: 8px;">Estado que activa matriculación</label>
            <select id="woo_otec_trigger_status" name="woo_otec_trigger_status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px;">
                <option selected>Completado (Recomendado)</option>
                <option>Procesando</option>
            </select>
            <p style="margin: 6px 0 0; font-size: 11px; color: #6b7280;">Alumno se matriculará al alcanzar este estado</p>
        </div>

        <!-- SECCIÓN: Opciones -->
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #1f2937;">Opciones Adicionales</h3>
            
            <label for="woo_otec_auto_linking" style="display: flex; align-items: center; gap: 8px; font-size: 12px; margin-bottom: 8px;">
                <input type="checkbox" id="woo_otec_auto_linking" name="woo_otec_auto_linking" checked disabled />
                Vinculación automática de cursos a productos
            </label>
            <label for="woo_otec_unenroll_refund" style="display: flex; align-items: center; gap: 8px; font-size: 12px; margin-bottom: 8px;">
                <input type="checkbox" id="woo_otec_unenroll_refund" name="woo_otec_unenroll_refund" disabled />
                Desenrolar al procesar reembolso
            </label>
        </div>

        <div style="display: flex; gap: 10px;">
            <?php submit_button( 'Guardar', 'primary', 'submit', false, array( 'style' => 'padding: 8px 20px; font-size: 13px;' ) ); ?>
        </div>
    </form>
</div>
