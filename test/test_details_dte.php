<?php

/* *get detalles dte  */
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




$dte_id = isset($_GET['dteid']) ? $_GET['dteid'] : 0;

if( $dte_id <= 0 )
{
    die('no dte selected');
}
$docto = new Documento();

$res = $docto->getDetallesDocto($dte_id);

Funciones::print_r_html($res, "test details dte para dte id = $dte_id, respuesta");




