<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 require_once dirname( __FILE__ ) . '/../Autoload.php';
/**
 * Description of CondicionesVenta
 *
 * @author angelorum
 */
class CondicionesVenta  extends DocumentoAbstracto
{

    //put your code here
    public function getCondicionesVenta()
    {

        $url = BSALE_GET_CONDICIONES_VENTA;
        $response_array = $this->get( $url );

        if ( isset( $response_array ) && isset( $response_array['items'] ) )
            $listado = $response_array['items'];
        else
            $listado = $response_array;

       // Funciones::print_r_html( $listado, "Condiciones de venta: URL: $url" );

        return $listado;
    }

}
