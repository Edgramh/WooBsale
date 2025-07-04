<?php

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

$sku = isset($_GET['sku']) ? $_GET['sku'] : null;

if( empty($sku) )
{
    die("falta sku");
}

//obtengo datos de varian a partir de sku
$vars = new VariantesProductoBsale();

$variacion_resp = $vars->get_variacion_by_sku($sku);

Funciones::print_r_html($variacion_resp, "get_variacion_by_sku($sku)");

if( !isset($variacion_resp['id']) )
{
    Funciones::print_r_html($variacion_resp, "variacion sku='$sku' no existe");
    exit(0);
}
$variacion = $variacion_resp;

//obtengo datos del producto
$producto_id = isset($variacion['product']['id']) ? $variacion['product']['id'] : 0;

$prod_bsale_obj = new ProductoBsale();
$producto = $prod_bsale_obj->get_producto($producto_id);

Funciones::print_r_html($producto, "Producto de la variacion");

if( !isset($producto['id']) )
{
    Funciones::print_r_html($producto, "Producto de la variacion no existe");
    exit(0);
}

//tipo de producto
//obtengo datos del tipo de producto
$type_producto_id = isset($producto['product_type']['id']) ? $producto['product_type']['id'] : 0;
$type_data = $prod_bsale_obj->get_producto_type_from_table($type_producto_id);

Funciones::print_r_html($type_data, "Tipo de Producto");

//nombre del tipo de prod
$type_producto_nombre = isset($type_data['name']) ? $type_data['name'] : null;

if( !isset($type_data['id']) )
{
    Funciones::print_r_html("Tipo de Producto no existe");
   // exit(0);
}


$variacion_id = $variacion['id'];

//debo generar como .json en notificaciones/para que lo procese el webhook?
if( $variacion_id > 0 )
{
    global $woo_bsale_db_url;

    if( empty($woo_bsale_db_url) )
    {
        $woo_bsale_db_url = defined('BSALE_WOOC_URL') ? BSALE_WOOC_URL : '';
    }

    $url = get_rest_url(null, 'wcbsalev2/v1/webhook/1') . '?rid=' . $variacion_id;

    $url = str_replace('test/', '', $url);
    echo("<p><a href='$url' target='_blank'>Send to webhook</a></p>");
}


//busco nro de serie
$serie_obj = new ProductoBsale();


$serie = $serie_obj->get_next_serie_variante($variacion_id);

Funciones::print_r_html($serie, "get_next_serie_variante($variacion_id)");



