<?php

/* sincroniza dede Bsale datos del producto y sus variaciones */
require_once dirname(__FILE__) . '/../lib/Autoload.php';


if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


error_reporting(0);//E_ALL
ini_set('display_errors', 0);//1


$product_id = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;

if( empty($product_id) )
{
    die("falta producto");
}
//use woocommerce_bsalev2\lib\wp\WpDataBsale;

$data_bsale = new WpDataBsale();
$result = $data_bsale->product_sync_from_bsale($product_id);

if( isset($_REQUEST['test']) )
{
    Funciones::print_r_html($result, " product_sync_from_bsale($product_id), resultado:");
}

//$arraux = array( 'result' => $result );
$data = json_encode($result, JSON_UNESCAPED_UNICODE);

echo($data);
exit(0);


