<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Cliente
 *
 * @author angelorum
 */
class Cliente extends Documento
{

    public function getCliente($cliente_id)
    {
        $url = sprintf(BSALE_GET_CLIENTE, $cliente_id);
        $response = $this->get($url);

        // Funciones::print_r_html( $response, "getCliente $cliente_id, $url" );

        return $response;
    }

    public function getCliente_by_email($email)
    {
        $url = BSALE_GET_CLIENTES_URL . "?email=$email";
        $response = $this->get($url);

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($response, "getCliente_by_email $email, $url");
        }

        if( isset($response['items'][0]) )
        {
            $response = $response['items'][0];
        }
        else
        {
            $response = null;
        }
        return $response;
    }

}
