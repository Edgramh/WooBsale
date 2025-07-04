<?php
/**test de emitir dte dede Wp, sin em itirlo*/
require_once dirname(__FILE__) . '/../lib/Autoload.php';

if( !is_user_logged_in() )
{
    echo('not allowed!');
    exit;
}
if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}
if( !isset($_REQUEST['test_dte']) || $_REQUEST['test_dte'] !== 'yes' )
{
    die("no allowed test_dte=yes");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


$email_cliente = isset($_GET['email']) ? $_GET['email'] : '';

if( empty($email_cliente) )
{
    die("falta email");
}

$wp = new Cliente();


$arr_datos= $wp->getCliente_by_email($email_cliente);
Funciones::print_r_html($arr_datos, "get clienbte para '$email_cliente', respuesta");

$accumulate_points = isset($arr_datos['accumulatePoints']) ? $arr_datos['accumulatePoints'] : 0;

$puntos = isset($arr_datos['points']) ? $arr_datos['points'] : 0;

Funciones::print_r_html( "get cliente para '$email_cliente', puntos: acumula puntos? '$accumulate_points'. Puntos: '$puntos'");
