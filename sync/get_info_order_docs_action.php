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

$order_id = isset($_REQUEST['oid']) ? (int) $_REQUEST['oid'] : 0;

if( $order_id <= 0 )
{
    echo("falta order.");
    exit(0);
}

$log = new LogDTEBsale();
$data_arr = $log->get_last_log_order_html($order_id);


echo $data_arr;
return;
