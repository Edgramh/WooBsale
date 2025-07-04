<?php

/* * test anular boletas, fact y nv de order woocommerce */
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

$folio = isset($_GET['f']) ? (int) $_GET['f'] : 0;
$tipo_dte = isset($_GET['t']) ? (int) $_GET['t'] : 0;

if( $folio <= 0 )
{
    die("No folio");
}
if( $tipo_dte <= 0 )
{
    die("No tipo dte");
}


$doc = new Documento();
$res = $doc->get_docto(0, $folio, $tipo_dte);


$dte_id = isset($res['id']) ? $res['id'] : 0;

if( $dte_id <= 0 )
{
    Funciones::print_r_html("no se ha encontrador doc para el folio=$folio, tipo doc=$tipo_dte");
    return;
}

//busco mismo dte, por id
$res2 = $doc->get_docto($dte_id);

Funciones::print_r_html($res2, "get dte por id=$dte_id, respuesta");
