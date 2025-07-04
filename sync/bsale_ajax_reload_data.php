<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('America/Santiago');

require_once dirname(__FILE__) . '/../lib/Autoload.php';

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


Funciones::print_r_html("realoading data...");
//recarga datos de tipos de prod , sucursales, etc desde bsale
$config = new ConfigUtils();
$config->reload_data_from_bsale('all', -1);
//para no volver a recargarlos
update_option('bsale_reload_data', 0);
