<?php
/* * borra dte a partir de order id de WC */
require_once dirname(__FILE__) . '/../lib/Autoload.php';


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



$order_id = isset($_GET['oid']) ? (int) $_GET['oid'] : 0;

if( $order_id <= 0 )
{
    die("No order");
}
 $wc_nc = new WpBsaleNotaCredito();
$wc_nc->anular_dte_bsale($order_id);
/*
$docto = new Documento();
$result = $docto->get_docto($docto_id);
$sucursal_id = isset($result['office']['id']) ? $result['office']['id'] : 0;
$document_type_id = isset($result['document_type']['id']) ? $result['document_type']['id'] : 0;

//informedSii, indica si el documento fue informado al SII, 0 es correcto, 1 es enviado, 2 es rechazado (Integer).
$informedSii = isset($result['informedSii']) ? $result['informedSii'] : -1;

$bsale_dte = new BsaleDTE();

Funciones::print_r_html($result, "get docto($docto_id) de tipo = $document_type_id,  desde sucursal $sucursal_id, informedsii= $informedSii, "
        . "respuesta");

if( $document_type_id == Funciones::get_nv_id() )
{
    Funciones::print_r_html("$docto_id es nota de venta");

    $nv = new NotaVenta();

    //solo debo hacer DELETE de la nv
    $result = $nv->delete_nv($docto_id, $sucursal_id);

    Funciones::print_r_html($result, "delete nv id=$docto_id, resultado");
}
elseif( $document_type_id == Funciones::get_factura_id() )
{
    Funciones::print_r_html("$docto_id es factura, se anula para orden = $order_number");

    $result = $bsale_dte->crear_nc_bsale($order_number, null, $order_number);

    Funciones::print_r_html($result, "$docto_id es factura, se anula para orden = $order_number, resultado");
}
elseif( $document_type_id == Funciones::get_boleta_id() )
{
    Funciones::print_r_html("$docto_id es boleta, se anula para orden = $order_number");

    $result = $bsale_dte->crear_nc_bsale($order_number, null, $order_number);

    Funciones::print_r_html($result, "$docto_id es boleta, se anula para orden = $order_number, resultado");
}*/


