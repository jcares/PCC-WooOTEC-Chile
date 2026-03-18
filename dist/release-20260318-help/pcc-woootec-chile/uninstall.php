<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$defaults = require __DIR__ . '/config/defaults.php';

foreach ($defaults as $key => $value) {
    delete_option('pcc_woootec_pro_' . $key);
}

delete_transient('pcc_woootec_pro_release');
