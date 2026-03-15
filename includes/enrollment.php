<?php

add_action('woocommerce_order_status_completed', 'pcc_woootec_process_order');

function pcc_woootec_process_order($order_id){

    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $user = get_userdata($user_id);

    foreach ($order->get_items() as $item){

        $product_id = $item->get_product_id();

        $course_id = get_post_meta($product_id,'moodle_course_id',true);

        if($course_id){

            $moodle_user_id = pcc_moodle_get_or_create_user($user);

            pcc_moodle_enrol_user($moodle_user_id,$course_id);

        }

    }

    pcc_send_access_email($user,$order);
}