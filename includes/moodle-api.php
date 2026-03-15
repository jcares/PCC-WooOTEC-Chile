<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
---------------------------------------
FUNCION BASE PARA CONSULTAR MOODLE API
---------------------------------------
*/

function pcc_moodle_request($function,$params=array()){

    $url = get_option('pcc_moodle_url');
    $token = get_option('pcc_moodle_token');

    $endpoint = $url.'/webservice/rest/server.php';

    $query = array_merge($params,[
        'wstoken'=>$token,
        'wsfunction'=>$function,
        'moodlewsrestformat'=>'json'
    ]);

    $response = wp_remote_post($endpoint,[
        'body'=>$query
    ]);

    if(is_wp_error($response)){
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response));
}

/*
---------------------------------------
BUSCAR O CREAR USUARIO EN MOODLE
---------------------------------------
*/

function pcc_moodle_get_or_create_user($user){

    $result = pcc_moodle_request(
        'core_user_get_users',
        [
            'criteria'=>[
                [
                    'key'=>'email',
                    'value'=>$user->user_email
                ]
            ]
        ]
    );

    if(!empty($result->users)){
        return $result->users[0]->id;
    }

    $password = wp_generate_password();

    $create = pcc_moodle_request(
        'core_user_create_users',
        [
            'users'=>[
                [
                    'username'=>$user->user_email,
                    'password'=>$password,
                    'firstname'=>$user->first_name,
                    'lastname'=>$user->last_name,
                    'email'=>$user->user_email
                ]
            ]
        ]
    );

    if(isset($create[0]->id)){
        return $create[0]->id;
    }

    return false;
}

/*
---------------------------------------
MATRICULAR USUARIO EN CURSO
---------------------------------------
*/

function pcc_moodle_enrol_user($userid,$courseid){

    return pcc_moodle_request(
        'enrol_manual_enrol_users',
        [
            'enrolments'=>[
                [
                    'roleid'=>5,
                    'userid'=>$userid,
                    'courseid'=>$courseid
                ]
            ]
        ]
    );

}