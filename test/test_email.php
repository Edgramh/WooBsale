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


$email_cliente = isset($_REQUEST['email']) ? $_REQUEST['email'] : null;

if( empty($email_cliente) )
{
    die('no email');
}

//envio email de aviso
$utils = new Utils();
$subject = "test email";

$msg = 'este es un correo de prueba, para comprobar el funcionamiento. Por favor, ignorar.';


$message = "<p>$msg</p>";
$response = $utils->sendEmail($email_cliente, $subject, $message);

Funciones::print_r_html("send email, response: '$response'");
