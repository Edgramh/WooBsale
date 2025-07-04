<?php

/* * emite dte desde WC  */
require_once dirname(__FILE__) . '/../lib/Autoload.php';


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


$order_id = isset($_GET['oid']) ? $_GET['oid'] : 0;
$use_array = isset($_GET['use']) ? $_GET['use'] : 0;


$wp = new WpBsale();

if( $use_array )
{
    $order_array = array( );

    foreach( $order_array as $order_id )
    {
        Funciones::print_r_html("do dte para order = $order_id");
        $arr_datos = $wp->crear_dte_bsale($order_id);
        Funciones::print_r_html($arr_datos, "do dte para order = $order_id, respuesta");
    }
}

if( empty($order_id) )
{
    die("falta order id");
}

Funciones::print_r_html("do dte para order = $order_id");
$arr_datos = $wp->crear_dte_bsale($order_id);
Funciones::print_r_html($arr_datos, "do dte para order = $order_id, respuesta");



