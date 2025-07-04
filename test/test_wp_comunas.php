
<?php
/* * devuelve value de comuna wc */
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


$comuna_key = isset($_GET['comuna']) ? $_GET['comuna'] : '';

if( empty($comuna_key) )
{
    die("falta comuna");
}
$data = new WpDataBsale();
$comuna_value = $data->get_comuna_value($comuna_key);

Funciones::print_r_html( "comuna key='$comuna_key' => value='$comuna_value'");

