<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FacturaAfecta
 *
 * @author angelorum
 */
class FacturaAfecta extends Documento
{

    public function getTestFactura()
    {
         $hoy = date('Y-m-d');       
        $gmt_date = strtotime($hoy);
        
        //cliente al que se le vende
        $array_cliente = array(
            'code' => "13952320-2",
            'city' => "Puerto Varas",
            'company' => "LuthorCorp",
            'municipality' => "Tomé",
            'activity' => "venta de helados en bolsita",
            'address' => "calle las vacas voladoras, #1212"
        );
        $array_cliente = array_map('utf8_encode', $array_cliente);

        //productos vendidos con esta factura
        $productos_arr = array();
        for( $i = 0; $i <= 3; $i++ )
        {
            $valor = 10000 + ($i * 5);
            $cantidad = $i + 1;

            $arraux = array(
                'netUnitValue' => $valor, //Valor unitario neto de la variante (Float).
                'quantity' => $cantidad,
                'taxId' => "[" . IMPUESTO_IVA_ID . "]", // Arreglo de identificadores de los impuestos a utilizar, estos tienen que ir dentro de "[]" (String).
                'comment' => "zapallo italiano $i",
                'discount' => 0 //descto a producto (float)
            );
            $productos_arr[] = $arraux;
        }
        /*
         * impuesto IVA
          {
          "href": "https://api.bsale.cl/v1/taxes/1.json",
          "id": 1,
          "name": "IVA",
          "percentage": "19.0",
          "forAllProducts": 0,
          "ledgerAccount": null,
          "code": "0",
          "state": 0
          }
         */

        //factura datos
        $arr = array(
            'documentTypeId' => Funciones::get_factura_id(), 
            'officeId' => Funciones::get_matriz_bsale(),
            // "priceListId" => 18,
            'emissionDate' => $gmt_date,
            'expirationDate' => $gmt_date,
            'declareSii' => Funciones::get_declare_sii(),
            'client' => $array_cliente,
            'details' => $productos_arr
        );

        $url = BSALE_DOCUMENTOS_URL;

        Funciones::print_r_html($arr, "Envio factura de prueba $url");

        $local_id = rand(101, 99999);
        $result = $this->post($url, $arr, $local_id);

        Funciones::print_r_html($result, "Resultado post factura");


        return true;
    }

    public function postFactura($arr_datos, $factura_local_id = null)
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
     * @param type $factura_id
     * @return type
     */
    public function getFactura($factura_id, $debug = true)
    {
        $url = sprintf(BSALE_GET_DOCTO_URL, $factura_id);
        $response = $this->get($url);
        if( $debug )
            Funciones::print_r_html($response, "getFactura $factura_id, $url");

        return $response;
    }

    public function getFacturaCliente($factura_id)
    {
        $f = $this->getFactura($factura_id, false);
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
     * @param type $factura_id
     * @return type
     */
    public function getDetallesFactura($factura_id)
    {
        $doc = new Documento();
        
        $response = $doc->getDetallesDocto($factura_id);
        
        return $response;
        
        /*$url = sprintf(BSALE_GET_DETALLES_DOCTO_URL, $factura_id);
        $response = $this->get($url);

        //Funciones::print_r_html( $response, "getDetalleFactura $factura_id, $url" );

        return $response;*/
    }

}
