<?php

/**
 */
require_once dirname(__FILE__) . '/../lib/Autoload.php';

//pedido 7234, tienda 2
//pedido 
if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


$limit = 1;
$users = new ConsolidadoBsale();
$res = $users->sync_update_prods_to_wc($limit);

Funciones::print_r_html($res, "sync_update_prods_to_wc($limit), respuesta");
