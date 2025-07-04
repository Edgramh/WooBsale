<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of NotaVenta
 *
 * @author angelorum
 */
class NotaVenta extends Documento
{

    public function postNotaVenta($arr_datos, $factura_local_id = null)
    {
        $url = BSALE_DOCUMENTOS_URL;
        $pais = Funciones::get_pais();

        //cambio endpoint segun pais
        if( $pais === 'PE' )
        {
            $url = str_replace(BSALE_BASE_URL, BSALE_BASE_URL_PE, $url);
        }

        $result = $this->post($url, $arr_datos, $factura_local_id);

        return $result;
    }

    /**
     * obtiene factura desde bsale, con links a los pdf y xml 
     * @param type $notaventa_id
     * @return type
     */
    public function getNotaVenta($notaventa_id, $debug = true)
    {
        $url = sprintf(BSALE_GET_DOCTO_URL, $notaventa_id);
        $response = $this->get($url);
        if( $debug )
            Funciones::print_r_html($response, "getNotaVenta $notaventa_id, $url");

        return $response;
    }

    public function getNotaVentaCliente($notaventa_id)
    {
        $f = $this->getNotaVenta($notaventa_id, false);
        //valido
        if( !isset($f) || !isset($f['client']['id']) )
            return null;

        $cliente_id = $f['client']['id'];
        $cliente = new Cliente();
        $response = $cliente->getCliente($cliente_id);

        return $response;
    }

    /**
     * detalles de ua factura
     * @param type $notaventa_id
     * @return type
     */
    public function getDetallesNotaVenta($notaventa_id)
    {
        $url = sprintf(BSALE_GET_DETALLES_DOCTO_URL, $notaventa_id);
        $response = $this->get($url);

        //Funciones::print_r_html( $response, "getDetalleNotaVenta $notaventa_id, $url" );

        return $response;
    }

    public function delete_nv($notaventa_id, $sucursal_id=null)
    {
        if(empty($sucursal_id))
        {
            $sucursal_id = Funciones::get_matriz_bsale();
        }
        
        $url = sprintf(BSALE_DELETE_DOCTO_URL, $notaventa_id, $sucursal_id);
        $response = $this->delete($url);
        
        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($response, "delete dte $notaventa_id, $url");
        }

        return $response;
    }

}
