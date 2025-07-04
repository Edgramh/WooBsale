<?php

require_once dirname(__FILE__) . '/../lib/Autoload.php';
/**
 * test del json con carpeta ftp y correlativo
 */
if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


error_reporting(E_ALL);
ini_set('display_errors', 1);

$res = bsale_integ_call_wh_curl();

Funciones::print_r_html($res, "curl call, result");

