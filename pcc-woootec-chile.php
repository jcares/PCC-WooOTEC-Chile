<?php
/**
 * Plugin Name:       PCC-WooOTEC-Chile PRO
 * Plugin URI:        https://github.com/
 * Description:       Integracion profesional entre Moodle y WooCommerce para sincronizar cursos, venderlos y matricular usuarios con acceso SSO.
 * Version:           2.0.0
 * Author:            PCC
 * Requires PHP:      8.1
 * Requires at least: 6.4
 * Text Domain:       pcc-woootec-chile
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   9.8
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PCC_WOOOTEC_PRO_VERSION', '2.0.0');
define('PCC_WOOOTEC_PRO_FILE', __FILE__);
define('PCC_WOOOTEC_PRO_BASENAME', plugin_basename(__FILE__));
define('PCC_WOOOTEC_PRO_PATH', plugin_dir_path(__FILE__));
define('PCC_WOOOTEC_PRO_URL', plugin_dir_url(__FILE__));

require_once PCC_WOOOTEC_PRO_PATH . 'includes/class-core.php';

register_activation_hook(PCC_WOOOTEC_PRO_FILE, array('PCC_WooOTEC_Pro_Core', 'activate'));
register_deactivation_hook(PCC_WOOOTEC_PRO_FILE, array('PCC_WooOTEC_Pro_Core', 'deactivate'));

PCC_WooOTEC_Pro_Core::instance()->boot();
