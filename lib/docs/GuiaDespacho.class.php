<?php
require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 
 * Description of GuiaDespacho
 *
 * @author angelorum
 */
class GuiaDespacho extends Documento
{
   public function postGD($arr_datos, $boleta_local_id = null, $productos = null)
    {
        $url = BSALE_DESPACHO_URL;

        $pais = Funciones::get_pais();
        //cambio endpoint segun pais
        if( $pais === 'PE' )
        {
            $url = str_replace(BSALE_BASE_URL, BSALE_BASE_URL_PE, $url);
        }

        $result = $this->post($url, $arr_datos, $boleta_local_id);

        return $result;
    }

    /**
     * obtiene cualquier documento desde bsale, con links a los pdf y xml 
     * @param type $factura_id
     * @return type
     */
    public function getGD($boleta_id, $debug = false)
    {
        $url = sprintf(BSALE_GET_DOCTO_URL, $boleta_id);
        $response = $this->get($url);
        if( $debug )
        {
            Funciones::print_r_html($response, "getGD $boleta_id, $url");
        }

        return $response;
    }

    /**
     * detalles de ua factura/boleta/nv/lo que sea
     * @param type $factura_id
     * @return type
     */
    public function getDetallesGD($boleta_id)
    {
        return $this->getDetallesDocto($boleta_id);
    }

    /**
     * devuelve los ids de los prodcutos contenido en el docto
     * @param type $boleta_id
     */
    public function getDetailsIdGD($boleta_id)
    {
        return $this->getDetailsIdDocto($boleta_id);
    }
}
