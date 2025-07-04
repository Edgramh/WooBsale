<?php
/**
 * devuelve hostorial de sync prodcut en pag de edit producto
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Santiago');

require_once dirname(__FILE__) . '/../lib/Autoload.php';

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


//echo("sync producto info");

$product_id = isset($_REQUEST['pid'])? (int)$_REQUEST['pid'] : 0;

if($product_id<=0)
{
    echo("falta id de producto.");
    exit(0);
}

$log = new LogSincronizacionBsale();
$data_arr = $log->get_last_log_product_html($product_id);


echo $data_arr;
return;