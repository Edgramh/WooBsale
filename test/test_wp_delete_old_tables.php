<?php

/** muestra options de wp.
 * çUtil para detectar cuándo algunos valores guardados no funcionan
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

$osdb = new OCDB();

$res = $osdb->delete_old_tables();

if( isset($_REQUEST['param']) )
{
    Funciones::print_r_html(__FILE__ . "delete_old_tables(), resultado: '$res'");
}