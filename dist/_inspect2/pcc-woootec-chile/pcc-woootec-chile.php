<?php
/*
Plugin Name: PCC-WooOTEC-Chile
Description: Integración Moodle + WooCommerce para OTEC Chile. Sincroniza cursos, gestiona pagos y matrículas automáticas.
Version: 1.0.0
Author: PCC
*/

if (!defined('ABSPATH')) {
    exit;
}

define('PCC_WOOOTEC_VERSION', '1.0.0');
define('PCC_WOOOTEC_PATH', plugin_dir_path(__FILE__));
define('PCC_WOOOTEC_URL', plugin_dir_url(__FILE__));

require_once PCC_WOOOTEC_PATH . 'includes/core/class-plugin.php';

register_activation_hook(__FILE__, array('PCC_WooOTEC', 'activate'));
register_deactivation_hook(__FILE__, array('PCC_WooOTEC', 'deactivate'));

(new PCC_WooOTEC())->init();
