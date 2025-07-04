<?php

/**
 * envia emaul de prueba
 */
require_once dirname(__FILE__) . '/../lib/Autoload.php';


//pedido 7234, tienda 2
//pedido 
if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$users = new UsuariosBsale();
$users_arr = $users->get_usuarios_array();

Funciones::print_r_html($users_arr, "get usuarios, respuesta");
