<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pcc-admin-wrap">
    <div class="pcc-brand-bar">
        <div class="pcc-brand-bar__main">
            <img class="pcc-brand-bar__logo" src="<?php echo esc_url(PCC_WOOOTEC_PRO_URL . 'assets/images/logo-pccurico.png'); ?>" alt="PCCurico">
            <div>
                <h1>Sincronizacion Moodle -> WooCommerce</h1>
                <p class="pcc-brand-bar__subtitle">Proceso guiado por etapas para categorias y cursos.</p>
            </div>
        </div>
        <div class="pcc-brand-bar__meta">
            <span>www.pccurico.cl</span>
            <span>desarrollado por JCares</span>
        </div>
    </div>

    <div class="pcc-card pcc-sync-shell" data-sync-app>
        <div class="pcc-sync-header">
            <div>
                <h2>Sincronizacion interactiva</h2>
                <p>Ejecuta la exportacion por etapas: primero categorias y luego cursos.</p>
            </div>
            <div class="pcc-badge is-info">Cron cada 1 hora</div>
        </div>

        <div class="pcc-progress" aria-label="Progreso de sincronizacion">
            <div class="pcc-progress-bar" data-sync-progress style="width:0%">0%</div>
        </div>

        <div class="pcc-stage-grid">
            <article class="pcc-stage-card" data-stage-card="categories">
                <div class="pcc-stage-step">Etapa 1</div>
                <h3>Categorias</h3>
                <p>Sincroniza categorias Moodle y su jerarquia en WooCommerce.</p>
                <div class="pcc-stage-status" data-stage-status="categories">Pendiente</div>
            </article>
            <article class="pcc-stage-card" data-stage-card="courses">
                <div class="pcc-stage-step">Etapa 2</div>
                <h3>Cursos</h3>
                <p>Crea o actualiza productos con SKU, fechas, docente y categoria.</p>
                <div class="pcc-stage-status" data-stage-status="courses">Pendiente</div>
            </article>
        </div>

        <div class="pcc-sync-actions">
            <button type="button" class="button button-primary button-hero" data-sync-start>Iniciar sincronizacion</button>
        </div>

        <div class="pcc-sync-result" data-sync-result>
            <p><strong>Ultima ejecucion:</strong> <?php echo !empty($last_sync['timestamp']) ? esc_html((string) $last_sync['timestamp']) : 'Sin ejecuciones'; ?></p>
            <p><strong>Mensaje:</strong> <?php echo !empty($last_sync['message']) ? esc_html((string) $last_sync['message']) : 'Sin datos'; ?></p>
            <p><strong>Categorias:</strong> creadas <?php echo esc_html((string) ($last_sync['categories_created'] ?? 0)); ?> / actualizadas <?php echo esc_html((string) ($last_sync['categories_updated'] ?? 0)); ?></p>
            <p><strong>Productos:</strong> creados <?php echo esc_html((string) ($last_sync['products_created'] ?? 0)); ?> / actualizados <?php echo esc_html((string) ($last_sync['products_updated'] ?? 0)); ?></p>
        </div>
    </div>

    <div class="pcc-admin-signature">
        <span>www.pccurico.cl</span>
        <span>desarrollado por JCares</span>
    </div>
</div>
