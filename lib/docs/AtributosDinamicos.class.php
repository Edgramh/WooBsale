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
class AtributosDinamicos  extends DocumentoAbstracto
{

    //put your code here
    public function getAtributosDinamicos()
    {

        $url = BSALE_GET_ATRIBUTOS_DINAMICOS;
        $response_array = $this->get( $url );

        if ( isset( $response_array ) && isset( $response_array['items'] ) )
            $listado = $response_array['items'];
        else
            $listado = $response_array;

       // Funciones::print_r_html( $listado, "Condiciones de venta: URL: $url" );

        return $listado;
    }

    public function getTiposDePago_arr()
    {
         $sucursales = $this->getTiposDePago();

        $arraux = array();

        if( !is_array($sucursales) )
        {
            return $arraux;
        }

        foreach( $sucursales as $s )
        {
            $suc_id = $s['id'];
            $suc_name = $s['name'];

            $arraux[$suc_id] = $suc_name;
        }

        return $arraux;
    }
    public function getTiposDePago()
    {

        $url = BSALE_TIPO_PAGOS_URL;
        $response_array = $this->get( $url );

        if ( isset( $response_array ) && isset( $response_array['items'] ) )
        {
            $listado = $response_array['items'];
        }
        else
        {
            $listado = array(); //$response_array;
        }

       // Funciones::print_r_html( $listado, "Condiciones de venta: URL: $url" );

        return $listado;
    }
}
