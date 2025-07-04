<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of NotaCredito
 *
 * @author angelorum
 */
class NotaCredito extends Documento
{

    /**
     * Tres clases de la devolución

      Si se desea crear una devolución para corregir información,
     *  se debe enviar el editTexts en 1 y el priceAdjustment en 0, 
     * ademas de enviar en el nodo details todos los detalles originales
     *  del documento (quantity = 0, unitValue = 0).

      Si se desea crear una devolución para ajustar el precio de los productos,
     *  se debe enviar el editTexts en 0 y el priceAdjustment en 1, ademas de 
     * enviar en el nodo details solo los detalles que van a cambiar de precio del 
     * documento original (quantity = 0, unitValue = nuevo precio)

      Si se desea crear una devolución solo para retornar productos,
     * se debe enviar el editTexts en 0 y el priceAdjustment en 0, 
     * ademas de enviar en el nodo details solo los detalles que van a
     *  cambiar de cantidad del documento original (quantity = nueva cantidad, unitValue = 0).

     * @param type $factura_id id del docto (no el folio)
     * * @param type $order_number numero del pedido
     * @param type $just_anular_dte isi es true, solo se anula dte pero no se devuelve el stock)
     */
    public function crearNotaCredito($factura_id, $order_number, $order_id, $just_anular_dte = false, $dte_anulado = null)
    {
//detallñe de la factura
        $f = new FacturaAfecta();

//datos factura        
        $factura_datos = $f->getFactura($factura_id, false);
//lo saco porque enel print se muestra basura y no uso este campo
        unset($factura_datos['ted']);
//Funciones::print_r_html($factura_datos, "crearNotaCredito( $factura_id ), docto: id={$factura_datos['id']}");

        $folio = $factura_datos['number'];
        $cliente_id = $factura_datos['client']['id'];
//cliente factura
        $cliente = new Cliente();
        $cliente_datos = $cliente->getCliente($cliente_id);

//obtengo productos de la factura
        $detalle = $f->getDetallesFactura($factura_id);

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($cliente_datos, __METHOD__ . "dte id=$factura_id cliente:");
            Funciones::print_r_html($detalle, __METHOD__ . "dte id=$factura_id productos:");
        }

//si no tiene detalle, regreso
        if( !isset($detalle) || !isset($detalle['items']) )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($detalle, "dte id=$factura_id no tiene productos");
            }
            return false;
        }
//indice con listado de productos
        $productos_factura = $detalle['items'];
// Funciones::print_r_html( $detalle, "productos factura $factura_id" );

        $hoy = date('Y-m-d');
        $gmt_date = strtotime($hoy);

//cliente de la NC
        $array_cliente = array(
            'code' => $cliente_datos['code'],
            'city' => $cliente_datos['city'],
            'company' => $cliente_datos['company'],
            'municipality' => $cliente_datos['municipality'],
            'activity' => $cliente_datos['activity'],
            'address' => $cliente_datos['address'],
        );
        $pais = Funciones::get_pais();

//perú exige distrito
        if( $pais === 'PE' )
        {
            $array_cliente['district'] = $cliente_datos['municipality'];
        }

//productos modificados por la nc
        $productos_arr = array();

//recorro los productos de la factura y voy modificando
        foreach( $productos_factura as $pf )
        {
            $id = $pf['id'];
// $comment = $pf['variant']['description'];

            $quantity = $pf['quantity'];
            $unitValue = 0;

            $productos_arr[] = array(
                'documentDetailId' => $id, //Id del detalle del documento original que se va a devolver 
                'quantity' => $quantity, //Cantidad a devolver (Float).
                'unitValue' => $unitValue, //Valor unitario del detalle (String).
            );
        }

        $prefix = Funciones::get_order_prefix();
        $dinam_attr_nc = Funciones::get_dinam_attr_nc();

        if( $dinam_attr_nc > 0 )
        {
            if( $pais === 'PE' )
            {
//en caso de peru, a veces hay 2 nc distintas, ujna para anular personas y otras para empresas
                if( $dte_anulado === 'Factura' )
                {
                    $dinam_attr_id_pe = defined('NC_FACTURA_DINAM_ATTR_ID_PE') ? NC_FACTURA_DINAM_ATTR_ID_PE : Funciones::get_dinam_attr_nc();

                    $dynamicAttributes [] = array(
                        'description' => utf8_encode($prefix . $order_number),
                        'dynamicAttributeId' => $dinam_attr_id_pe,
                    ); //comentario
                }
                else
                {
                    $dynamicAttributes [] = array(
                        'description' => utf8_encode($prefix . $order_number),
                        'dynamicAttributeId' => Funciones::get_dinam_attr_nc(),
                    ); //comentario
                }
            }
            else
            {
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($prefix . $order_number),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_nc(),
                ); //comentario
            }
        }
        else
        {
            $dynamicAttributes = null;
        }

        $editTexts = 0;
        $priceAdjustment = 0;
        $type = 0;

        if( $just_anular_dte )
        {
//$editTexts = 1;
        }


//nc datos
        $arr = array(
            'documentTypeId' => Funciones::get_nc_id(),
            'officeId' => Funciones::get_matriz_bsale(),
            'referenceDocumentId' => $factura_id,
            'dynamicAttributes' => $dynamicAttributes,
            'emissionDate' => $gmt_date,
            'expirationDate' => $gmt_date,
            'motive' => "NC para DTE folio #$folio, pedido #$order_number",
            'declareSii' => Funciones::get_declare_sii(), //cambiar a 1 en produccion
            'priceAdjustment' => $priceAdjustment, //Si la devolución corresponde a un ajuste 
//de precio de los productos se envía 1, en caso contrario 0 (Boolean).
            'editTexts' => $editTexts, // Si la devolución corresponde a una corrección de texto 
//(por forma) se envía 1, en caso contrario 0 (Boolean).
            'type' => $type, // Indica como se va a devolver el dinero del documento, 
//0 para devolución dinero, 
//1 para forma pago nueva venta, 
//2 para abono linea de crédito (Integer).
            'clientId' => (int) $cliente_id,
            //'client' => $array_cliente,
            'details' => $productos_arr
        );

//cambio declareSii por declare
        if( $pais === 'PE' )
        {
            $arr['declare'] = $arr['declareSii'];
            unset($arr['declareSii']);

//en caso de peru, a veces hay 2 nc distintas, ujna para anular personas y otras para empresas
            if( $dte_anulado === 'Factura' )
            {
                $dinam_attr_doc_id_pe = defined('NC_FACTURA_ID_PE') ? NC_FACTURA_ID_PE : $arr['documentTypeId'];
                $arr['documentTypeId'] = $dinam_attr_doc_id_pe; //id de nc para facturas
            }
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($arr, "crearNotaCredito($factura_id, $order_number), datos a enviar:");
        }
        $result = array();

        $url = BSALE_NOTA_CREDITO_URL;

        if( $pais === 'PE' )
        {
            $url = str_replace(BSALE_BASE_URL, BSALE_BASE_URL_PE, $url);
        }

        //si isset($_REQUEST['test_dte'], no se hace post de nada
        $result = $this->post($url, $arr, $order_id);

        if( isset($_REQUEST['param']) || isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($result, __METHOD__ . " test crearNotaCredito($factura_id, $order_number), no se genera");
            return false;
        }

//agrego folio borrado
        $result['dte_anulado_folio'] = $folio;
        $result['dte_anulado_id'] = $factura_datos['id'];

        return $result;
    }

    public function getNotaCreditoFromDevolucionId($devolucion_id)
    {
        $url = sprintf(BSALE_GET_NOTA_CREDITO_URL, $devolucion_id);
        $response = $this->get($url);

        if( isset($response) && isset($response['credit_note']) )
        {
            $nc_id = $response['credit_note']['id'];
            return $this->getNotaCredito($nc_id);
        }
        return null;
    }

    public function getNotaCredito($nc_id)
    {
        $url = sprintf(BSALE_GET_DOCTO_URL, $nc_id);
        $response = $this->get($url);

        return $response;
    }

    public function getDetalles($nc_id)
    {
        $url = sprintf(BSALE_GET_DETALLES_DOCTO_URL, $nc_id);
        $response = $this->get($url);

//Funciones::print_r_html( $response, "getDetalleFactura $factura_id, $url" );

        return $response;
    }

    public function postNC($arr_datos, $nc_local_id = null, $parent_local_id = null, $parent_remote_id = null)
    {
        $url = BSALE_NOTA_CREDITO_URL;
        $result = $this->post($url, $arr_datos, $nc_local_id, $parent_local_id, $parent_remote_id);

        return $result;
    }
}
