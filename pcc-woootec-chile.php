<?php
/*
Plugin Name: PCC-WooOTEC-Chile
Description: Integración Moodle + WooCommerce para OTEC Chile. Sincroniza cursos, gestiona pagos y matrículas automáticas.
Version: 1.0
Author: PCC
*/

if (!defined('ABSPATH')) {
    exit;
}

/* ================================
   CONSTANTES DEL PLUGIN
================================ */

define('PCC_WOOOTEC_VERSION', '1.0');

define('PCC_WOOOTEC_PATH', plugin_dir_path(__FILE__));
define('PCC_WOOOTEC_URL', plugin_dir_url(__FILE__));



/* ================================
   ACTIVACIÓN DEL PLUGIN
================================ */

register_activation_hook(__FILE__, 'pcc_plugin_activate');

function pcc_plugin_activate(){

    if(!get_option('pcc_license_status')){
        update_option('pcc_license_status','inactive');
    }

}



/* ================================
   VERIFICAR DEPENDENCIAS
================================ */

add_action('admin_init','pcc_check_dependencies');

function pcc_check_dependencies(){

    if(!class_exists('WooCommerce')){

        add_action('admin_notices','pcc_woocommerce_missing');

    }

}

function pcc_woocommerce_missing(){

    echo '<div class="notice notice-error">';
    echo '<p>PCC-WooOTEC requiere WooCommerce instalado y activo.</p>';
    echo '</div>';

}



/* ================================
   CARGAR ARCHIVOS DEL PLUGIN
================================ */

require_once PCC_WOOOTEC_PATH.'includes/moodle-api.php';
require_once PCC_WOOOTEC_PATH.'includes/enrollment.php';
require_once PCC_WOOOTEC_PATH.'includes/course-sync.php';
require_once PCC_WOOOTEC_PATH.'includes/license.php';

require_once PCC_WOOOTEC_PATH.'admin/admin-menu.php';
require_once PCC_WOOOTEC_PATH.'admin/sync-page.php';



/* ================================
   LICENCIA DEL PLUGIN
================================ */

add_action('admin_init','pcc_check_license');

function pcc_check_license(){

    $license = get_option('pcc_license_status');

    if($license != "valid"){

        add_action('admin_notices','pcc_license_notice');

    }

}

function pcc_license_notice(){

    echo '<div class="notice notice-warning">';
    echo '<p>PCC-WooOTEC-Chile requiere activación de licencia.</p>';
    echo '</div>';

}



/* ================================
   CARGAR CSS DEL PANEL ADMIN
================================ */

add_action('admin_enqueue_scripts','pcc_admin_assets');

function pcc_admin_assets($hook){

    if(strpos($hook,'pcc') === false){
        return;
    }

    wp_enqueue_style(
        'pcc-admin-style',
        PCC_WOOOTEC_URL.'assets/css/admin-style.css',
        array(),
        PCC_WOOOTEC_VERSION
    );

}



/* ================================
   HOOK DE ORDEN COMPLETADA
================================ */

add_action('woocommerce_order_status_completed','pcc_process_course_enrollment');

function pcc_process_course_enrollment($order_id){

    if(!function_exists('pcc_enroll_user')){
        return;
    }

    pcc_enroll_user($order_id);

}