function pcc_validate_license($key){

$domain = $_SERVER['SERVER_NAME'];

$response = wp_remote_post(
'https://licencias.pccurico.cl/api/validate',
array(
'body'=>array(
'license_key'=>$key,
'domain'=>$domain
)
)
);

if(is_wp_error($response)){
return false;
}

$data = json_decode(
wp_remote_retrieve_body($response),
true
);

if($data['status']=="valid"){
return true;
}

return false;

}