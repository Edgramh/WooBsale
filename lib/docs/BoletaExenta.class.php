<?php

 require_once dirname( __FILE__ ) . '/../Autoload.php';

/**
 * Description of Boleta
 *
 * @author angelorum
 */
class BoletaExenta extends Documento
{

    public function getTestBoleta()
    {
         $hoy = date('Y-m-d');       
        $gmt_date = strtotime($hoy);

        //productos vendidos con esta factura
        $productos_arr = array();
        for ( $i = 0; $i <= 3; $i++ )
        {
            $valor = 10000 + ($i * 5);
            $cantidad = $i + 1;
            $productos_arr[] = array(
                'netUnitValue' => $valor, //Valor unitario neto de la variante (Float).
                'quantity' => $cantidad,
                //  'taxId' => "[" . IMPUESTO_IVA_ID . "]", // Arreglo de identificadores de los impuestos a utilizar, estos tienen que ir dentro de "[]" (String).
                'comment' => "zapallo italiano $i",
                'discount' => 0 //descto a producto (float)
            );
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
            'documentTypeId' => Funciones::get_boleta_id(),
            'officeId' => Funciones::get_matriz_bsale(),
             'sendEmail' => 1,
            // "priceListId" => 18,
            'emissionDate' => $gmt_date,
            'expirationDate' => $gmt_date,
            'declareSii' => Funciones::get_declare_sii(),
            //  'client' => $array_cliente, boleta no lleva cliente
            'details' => $productos_arr
        );

        $url = BSALE_DOCUMENTOS_URL;

        Funciones::print_r_html( $arr, "envio datos para boleta ex" );
        Funciones::print_r_html( $productos_arr, "productos de boleta ex" );
        
        $local_id = rand (101, 99999);

        $result = $this->post( $url, $arr, $local_id );

        Funciones::print_r_html( $result, "respuesta desde bsale para boleta ex" );
        
    }

    public function postBoleta( $arr_datos, $boleta_local_id = null, $productos = null )
    {
        $url = BSALE_DOCUMENTOS_URL;

        $result = $this->post( $url, $arr_datos, $boleta_local_id );            

        return $result;
    }

    /**
     * obtiene factura desde bsale, con links a los pdf y xml 
     * @param type $factura_id
     * @return type
     */
    public function getBoleta( $boleta_id, $debug = false )
    {
        $url = sprintf( BSALE_GET_DOCTO_URL, $boleta_id );
        $response = $this->get( $url );
        if ( $debug )
            Funciones::print_r_html( $response, "getBoleta $boleta_id, $url" );

        return $response;
    }

    /**
     * detalles de ua factura
     * @param type $factura_id
     * @return type
     */
    public function getDetallesBoleta( $boleta_id )
    {
        $url = sprintf( BSALE_GET_DETALLES_DOCTO_URL, $boleta_id );
        $response = $this->get( $url );

        //Funciones::print_r_html( $response, "getDetalleFactura $factura_id, $url" );

        return $response;
    }

}
