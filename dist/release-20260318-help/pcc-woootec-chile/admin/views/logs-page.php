<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pcc-admin-wrap">
    <h1>Logs del plugin</h1>

    <div class="pcc-card">
        <h2>sync.log</h2>
        <pre class="pcc-log-view"><?php echo esc_html(implode("\n", $sync_log)); ?></pre>
    </div>

    <div class="pcc-card">
        <h2>error.log</h2>
        <pre class="pcc-log-view"><?php echo esc_html(implode("\n", $error_log)); ?></pre>
    </div>
</div>
