<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_woootec_menu() {
    add_menu_page(
        'PCC WooOTEC Chile',
        'PCC WooOTEC',
        'manage_options',
        'pcc-woootec',
        'pcc_dashboard',
        'dashicons-welcome-learn-more',
        25
    );

    add_submenu_page('pcc-woootec', 'Dashboard', 'Dashboard', 'manage_options', 'pcc-woootec', 'pcc_dashboard');
    add_submenu_page('pcc-woootec', 'Configuracion', 'Configuracion', 'manage_options', 'pcc-settings', 'pcc_settings_page');
    add_submenu_page('pcc-woootec', 'Sincronizacion', 'Sincronizacion', 'manage_options', 'pcc-sync', 'pcc_sync_page');
    add_submenu_page('pcc-woootec', 'Reintentos', 'Reintentos', 'manage_options', 'pcc-retries', 'pcc_retry_page');
    add_submenu_page('pcc-woootec', 'Logs', 'Logs', 'manage_options', 'pcc-logs', 'pcc_logs_page');
}
