<?php
/**
 * carga listado de tipos de producto desde bsale y los guarda en una tabla
 */
require_once dirname(__FILE__) . '/../lib/Autoload.php';

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


if( !(PHP_SAPI === 'cli' || PHP_SAPI === 'cgi-fcgi') && (!isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' ) )
{
    echo('not allowed');
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$tipo = new TipoProductosBsale();
$tipo->get_all_save_in_db();
