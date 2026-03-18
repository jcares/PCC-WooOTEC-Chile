<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pcc-admin-wrap">
    <div class="pcc-brand-bar">
        <div class="pcc-brand-bar__main">
            <span class="pcc-brand-bar__logo-wrap">
                <img class="pcc-brand-bar__logo" src="<?php echo esc_url(PCC_WOOOTEC_PRO_URL . 'assets/images/logo-pccurico.png'); ?>" alt="PCCurico">
            </span>
            <div>
                <h1>Logs del plugin</h1>
                <p class="pcc-brand-bar__subtitle">Revision rapida de eventos, sincronizaciones y errores.</p>
            </div>
        </div>
        <div class="pcc-brand-bar__meta">
            <span>www.pccurico.cl</span>
            <span>desarrollado por JCares</span>
        </div>
    </div>

    <div class="pcc-card">
        <h2>sync.log</h2>
        <pre class="pcc-log-view"><?php echo esc_html(implode("\n", $sync_log)); ?></pre>
    </div>

    <div class="pcc-card">
        <h2>error.log</h2>
        <pre class="pcc-log-view"><?php echo esc_html(implode("\n", $error_log)); ?></pre>
    </div>
</div>
