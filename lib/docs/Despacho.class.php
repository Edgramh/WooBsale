<?php

require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of Boleta
 *
 * @author angelorum
 */
class Despacho extends Documento
{

    public function postDespacho($arr_datos, $boleta_local_id = null)
    {
        $url = BSALE_DESPACHO_URL;

        $result = $this->post($url, $arr_datos, $boleta_local_id);
        if( isset($result['error']) )
        {
            Funciones::print_r_html($result, "ERROR: postDespacho respuesta:");
        }
        return $result;
    }

    /**
     * obtiene factura desde bsale, con links a los pdf y xml 
     * @param type $factura_id
     * @return type
     */
    public function getDespacho($boleta_id, $debug = true)
    {
        $url = sprintf(BSALE_GET_DOCTO_URL, $boleta_id);
        $response = $this->get($url);
        if( $debug )
        {
            Funciones::print_r_html($response, "getDespacho $boleta_id, $url");
        }
        return $response;
    }

}
