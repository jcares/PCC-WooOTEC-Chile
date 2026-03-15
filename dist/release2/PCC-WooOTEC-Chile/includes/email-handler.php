<?php

function pcc_send_access_email($user,$order){

    $aula_url = get_option('pcc_aula_url');

    $subject = "Acceso a tu curso";

    $message = "
    Hola ".$user->first_name.",

    Tu compra fue confirmada.

    Accede a tu Aula Virtual:

    ".$aula_url."

    Usuario: ".$user->user_email."

    Saludos
    OTEC
    ";

    wp_mail($user->user_email,$subject,$message);

}