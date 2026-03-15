<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
----------------------------------------------------
SINCRONIZACIÓN DE CURSOS MOODLE → WOOCOMMERCE
----------------------------------------------------
*/

function pcc_run_course_sync(){

    echo "<div class='notice notice-info'><p>Iniciando sincronización de cursos...</p></div>";

    if(!function_exists('pcc_moodle_request')){
        echo "<div class='notice notice-error'><p>Error: función de conexión Moodle no encontrada.</p></div>";
        return;
    }

    $courses = pcc_moodle_request('core_course_get_courses');

    if(!$courses || !is_array($courses)){
        echo "<div class='notice notice-error'><p>No se pudieron obtener cursos desde Moodle.</p></div>";
        return;
    }

    $created = 0;
    $updated = 0;

    foreach($courses as $course){

        if(empty($course->id)) continue;

        // evitar cursos del sistema
        if($course->id < 2) continue;

        $product_id = pcc_get_product_by_course($course->id);

        if(!$product_id){

            $product_id = pcc_create_course_product($course);
            $created++;

        }else{

            pcc_update_course_product($product_id,$course);
            $updated++;

        }

    }

    echo "<div class='notice notice-success'>";
    echo "<p>Sincronización finalizada</p>";
    echo "<p>Productos creados: ".$created."</p>";
    echo "<p>Productos actualizados: ".$updated."</p>";
    echo "</div>";

}



/*
----------------------------------------------------
CREAR PRODUCTO WOOCOMMERCE
----------------------------------------------------
*/

function pcc_create_course_product($course){

    if(!class_exists('WC_Product_Simple')){
        echo "<p>WooCommerce no está activo.</p>";
        return false;
    }

    $product = new WC_Product_Simple();

    $product->set_name($course->fullname);

    if(!empty($course->summary)){
        $product->set_description($course->summary);
    }

    $product->set_status('publish');

    $product->set_catalog_visibility('visible');

    $product->set_virtual(true);

    $product->set_regular_price('49000');

    $product_id = $product->save();

    update_post_meta($product_id,'moodle_course_id',$course->id);

    update_post_meta($product_id,'_pcc_synced',1);

    echo "<p>Curso creado: ".$course->fullname."</p>";

    return $product_id;

}



/*
----------------------------------------------------
ACTUALIZAR PRODUCTO EXISTENTE
----------------------------------------------------
*/

function pcc_update_course_product($product_id,$course){

    $product = wc_get_product($product_id);

    if(!$product) return;

    $product->set_name($course->fullname);

    if(!empty($course->summary)){
        $product->set_description($course->summary);
    }

    $product->save();

    echo "<p>Curso actualizado: ".$course->fullname."</p>";

}



/*
----------------------------------------------------
BUSCAR PRODUCTO POR ID MOODLE
----------------------------------------------------
*/

function pcc_get_product_by_course($course_id){

    $args = array(

        'post_type' => 'product',

        'meta_query' => array(
            array(
                'key'   => 'moodle_course_id',
                'value' => $course_id,
                'compare' => '='
            )
        ),

        'posts_per_page' => 1

    );

    $query = new WP_Query($args);

    if($query->have_posts()){

        return $query->posts[0]->ID;

    }

    return false;

}



/*
----------------------------------------------------
IMPORTAR IMAGEN DEL CURSO (opcional)
----------------------------------------------------
*/

function pcc_set_course_image($product_id,$image_url){

    if(empty($image_url)) return;

    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';

    $image_id = media_sideload_image($image_url,$product_id,null,'id');

    if(!is_wp_error($image_id)){

        set_post_thumbnail($product_id,$image_id);

    }

}



/*
----------------------------------------------------
LOGS DEL PLUGIN
----------------------------------------------------
*/

function pcc_log($message){

    if(!is_string($message)){
        $message = json_encode($message);
    }

    error_log("[PCC-WooOTEC] ".$message);

}