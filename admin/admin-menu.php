<?php

add_action('admin_menu','pcc_woootec_menu');

function pcc_woootec_menu(){

    add_menu_page(
    'PCC WooOTEC Chile',
    'PCC WooOTEC',
    'manage_options',
    'pcc-woootec',
    'pcc_dashboard',
    'dashicons-welcome-learn-more',
    25
	
	add_submenu_page(
	'pcc-woootec',
	'Sincronizar cursos',
	'Sincronizar cursos',
	'manage_options',
	'pcc-sync',
	'pcc_sync_page'
);

}