<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of NotaDebito
 *
 * @author angelorum
 */

class NotaDebito extends Documento
{

    public function crear( $docto_id )
    {
         $hoy = date('Y-m-d');       
        $gmt_date = strtotime($hoy);
        //factura datos
        $arr = array(
            'documentTypeId' => NDE_ID, //factura o nc           
            'referenceDocumentId' => $docto_id,
            'emissionDate' => $gmt_date,
            'expirationDate' => $gmt_date,
            'officeId' => Funciones::get_matriz_bsale(),
            'declareSii' => Funciones::get_declare_sii(), //cambiar a 1 en produccion
                //'number'=>1 //Es el folio de la nota de débito, si no se envía 
                //tomara el siguiente numero disponible (Integer)
        );

        $local_id = rand (101, 99999);
        $url = sprintf( BSALE_NOTA_DEBITO_URL, $docto_id );
        return $this->post( $url, $arr, $local_id );
    }

    public function postND( $docto_id, $local_nc_id, $remote_nc_id )
    {
        $hoy = date('Y-m-d');       
        $gmt_date = strtotime($hoy);
        //factura datos
        $arr = array(
            'documentTypeId' => NDE_ID, //factura o nc           
            'referenceDocumentId' => $docto_id,
            'emissionDate' => $gmt_date,
            'expirationDate' => $gmt_date,
            'officeId' => Funciones::get_matriz_bsale(),
            'declareSii' => Funciones::get_declare_sii(), //cambiar a 1 en produccion
                //'number'=>1 //Es el folio de la nota de débito, si no se envía 
                //tomara el siguiente numero disponible (Integer)
        );

       //  Funciones::print_r_html( $arr, "Nd post datos de envio: " );
         
        $url = sprintf( BSALE_NOTA_DEBITO_URL, $docto_id );
        $result = $this->post( $url, $arr , $gmt_date, $local_nc_id, $remote_nc_id);

        return $result;
    }

    public function getNotaDebitoFromDevolucionId( $devolucion_id )
    {
        $url = sprintf( BSALE_GET_NOTA_CREDITO_URL, $devolucion_id );
        $response = $this->get( $url );

        if ( isset( $response ) && isset( $response['debit_note'] ) )
        {
            $nc_id = $response['debit_note']['id'];
            return $this->getND( $nd_id );
        }
        return null;
    }

    public function getND( $nd_id )
    {
        $url = sprintf( BSALE_GET_DOCTO_URL, $nd_id );
        $response = $this->get( $url );



        return $response;
    }

    public function getDetalles( $nc_id )
    {
        $url = sprintf( BSALE_GET_DETALLES_DOCTO_URL, $nc_id );
        $response = $this->get( $url );

        //Funciones::print_r_html( $response, "getDetalleFactura $factura_id, $url" );

        return $response;
    }

}
