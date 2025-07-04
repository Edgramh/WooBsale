<?php

/* * delete dte bsal por folio y tipo */
require_once dirname(__FILE__) . '/../lib/Autoload.php';


if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}
/* if( !isset($_REQUEST['test_dte']) || $_REQUEST['test_dte'] !== 'yes' )
  {
  die("no allowed test_dte=yes");
  } */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}



$folio = isset($_GET['f']) ? (int) $_GET['f'] : 0;
$tipo_dte = isset($_GET['t']) ? (int) $_GET['t'] : 0;
$sucursal_id = isset($_GET['s']) ? (int) $_GET['s'] : 0;
$order_number = isset($_GET['oid']) ? (int) $_GET['oid'] : 0;

if( $folio <= 0 )
{
    die("No folio");
}
if( $tipo_dte <= 0 )
{
    die("No tipo dte");
}
if( $sucursal_id <= 0 )
{
    $sucursal_id = Funciones::get_matriz_bsale();
}




$doc = new Documento();
$res = $doc->get_docto(0, $folio, $tipo_dte);


$dte_id = isset($res['id']) ? $res['id'] : 0;
$informedSii = isset($res['informedSii']) ? $res['informedSii'] : -1;

if( $dte_id <= 0 )
{
    Funciones::print_r_html("no se ha encontrador doc para el folio=$folio, tipo doc=$tipo_dte");
    return;
}


//si no es cero, no ha sido declarada o fue rechazada por el sii, solo se hace DELETE
// 0 es correcto, 1 es enviado, 2 es rechazado (Integer).
if( $informedSii == 2 )
{
    //puedo usar la clase NV, pues llama al delete de Bsale sin distinguir el tipo de dte (bol, fact, nv, etc)
    $nv = new NotaVenta();

    //solo debo hacer DELETE de la nv
    $result = $nv->delete_nv($dte_id, $sucursal_id);

    Funciones::print_r_html($result, "borrar dte Bsale, estado en SII=$informedSii no aporbado, hago DELETE, anular $tipo_dte id=$dte_id, folio $folio, "
            . "de sucursal id=$sucursal_id, respuesta");
}
//emito nc
else
{
    if( $order_number <= 0 )
    {
        die("no order number");
    }


    $bsale = new BsaleDTE();
    $result = $bsale->crear_nc_bsale($order_number);

    Funciones::print_r_html($result, "crear NC Bsale, anular $tipo_dte id=$dte_id, folio $folio, "
            . "de sucursal id=$sucursal_id, respuesta");
}