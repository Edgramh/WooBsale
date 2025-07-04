<?php

require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of BsaleDTE
 *
 * @author Lex
 */
class BsaleDTE
{

    public function crear_nc_bsale($order_number, $access_array = null, $order_id = 0)
    {
        //obtengo id del docto boleta asociado al pedido
        $logs = new LogDocumentos();

        $source = isset($access_array['source']) ? $access_array['source'] : null;

        //si es woocommerce, trato de obtener id bol o fact desde campos meta
        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            $dte_id_bol_wc = get_post_meta($order_id, 'bsale_docto_id_boleta', true);
            $dte_id_fact_wc = get_post_meta($order_id, 'bsale_docto_id_factura', true);
        }
        else
        {
            $dte_id_bol_wc = -1;
            $dte_id_fact_wc = -1;
        }

        $log_row = $logs->getIdsDocumentoFromLog('be', $order_id, $source);
        $dte_anulado = 'Boleta';

        //si no hay, busco factura
        if( !isset($log_row['remoto_id']) )
        {
            $log_row = $logs->getIdsDocumentoFromLog('f', $order_id, $source);
            $dte_anulado = 'Factura';
        }

        $dte_id = isset($log_row['remoto_id']) ? $log_row['remoto_id'] : null;

        //si no hay datos, los intento sacar de los campos meta de wc
        if( empty($dte_id) || $dte_id <= 0 )
        {
            if( $dte_id_bol_wc > 0 )
            {
                $dte_id = $dte_id_bol_wc;
                $dte_anulado = 'Boleta';
            }
            elseif( $dte_id_fact_wc > 0 )
            {
                $dte_id = $dte_id_fact_wc;
                $dte_anulado = 'Factura';
            }
        }


        if( empty($dte_id) )
        {
            $dte_anulado = null;
            //no se ha generado boleta para este pedido, por lo 
            //que no sepuede generar nc
            $err = "crear_nc_bsale($order_number) NO hay boleta/factura asociada al pedido #$order_number, por lo "
                    . "que no se puede generar la Nota de crédito.";

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($err);
            }
            //busco nv
            $log_row = $logs->getIdsDocumentoFromLog('nv', $order_id, $source);
            $dte_id = isset($log_row['remoto_id']) ? $log_row['remoto_id'] : null;

            $arr = array( 'error' => $err, 'nv_for_delete' => $dte_id );
            return $arr;
        }

        //veo si ya se ha generado una NC para este pedido. En ese caso, no se genera otra
        $log_row_nc = $logs->getIdsDocumentoFromLog('nc', $order_id, $source);
        $nc_id = isset($log_row_nc['remoto_id']) ? $log_row_nc['remoto_id'] : null;

        if( !empty($nc_id) )
        {
            /* $err = "Ya se ha generado una nota de credito nro #$nc_id para el pedido #$order_id de $source, dte #$dte_id";

              if( isset($_REQUEST['test_dte']) )
              {
              Funciones::print_r_html($err);
              }

              $arr = array( 'error' => $err );
              return $arr; */
        }

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("crear_nc_bsale($order_number): genero nc para crearNotaCredito(dteid=$dte_id, "
                    . "order=$order_number, order id=$order_id)");
            //return false;
        }

        $nc = new NotaCredito();

        $result = $nc->crearNotaCredito($dte_id, $order_number, $order_id, false, $dte_anulado);

        //para saber qué dte (boleta, factura) fue el que se anuló con la NC
        $result['dte_anulado_nombre'] = $dte_anulado;

        return $result;
    }

    /**
     * recibe el arreglo de datos a enviar a Bsale y devuelve total con y sin iva
     * @param type $productos_arr 
     */
    public function get_totales($productos_arr)
    {

        if( !is_array($productos_arr) || count($productos_arr) <= 0 )
        {
            return array( 'neto' => 0, 'total' => 0 );
        }
        $total_neto = 0;
        $iva = Funciones::get_valor_iva();

        foreach( $productos_arr as $p )
        {
            $netUnitValue = isset($p['netUnitValue']) ? $p['netUnitValue'] : 0;

            if( $netUnitValue <= 0 )
            {
                continue;
            }
            $quantity = isset($p['quantity']) ? $p['quantity'] : 1;
            $discount_porcent = isset($p['discount']) ? $p['discount'] : 0;
            $taxId = isset($p['taxId']) ? $p['taxId'] : null;

            //calculo neto y lo sumo
            $total_neto += ($netUnitValue * $quantity) - ( ($netUnitValue * $quantity) * $discount_porcent / 100);
        }
        return array( 'neto' => $total_neto, 'total' => ($iva * $total_neto) );
    }

    /**
     * 
     * @param type $arr_datos   
     * @return boolean
     */
    public function enviar_dte_a_bsale($arr_datos)
    {
        $tipo_docto = $arr_datos['tipo_docto'];
        $tienda_nombre = $arr_datos['tienda_nombre'];
        $order_number = $arr_datos['order_number'];
        $order_id = isset($arr_datos['order_id']) ? $arr_datos['order_id'] : $order_number;
        $gmt_date = $arr_datos['gmt_date'];
        $gmt_date_expiracion = $arr_datos['gmt_date_expiracion'];
        $array_cliente = $arr_datos['array_cliente'];
        $productos_arr = $arr_datos['productos_arr'];
        $modo_pago_arr = $arr_datos['modo_pago_arr'];
        //shopify: de donde provinee la orden
        $source = isset($arr_datos['source']) ? $arr_datos['source'] : '';
        //force sucursal de bsale a la que enviar el documento
        $sucursal_to_send = isset($arr_datos['sucursal_to_send']) ? $arr_datos['sucursal_to_send'] : -1;
        $references = isset($arr_datos['references']) ? $arr_datos['references'] : null;
        //peso de los productos de la orden
        $peso_total = isset($arr_datos['peso_total']) ? $arr_datos['peso_total'] : 0;
        $shipping_address = isset($arr_datos['shipping_address']) ? $arr_datos['shipping_address'] : '';
        $shipping_name = isset($arr_datos['shipping_name']) ? $arr_datos['shipping_name'] : '';
        $customer_note = isset($arr_datos['customer_note']) ? $arr_datos['customer_note'] : '';
        $webpay_transaction_id = isset($arr_datos['webpay_transaction_id']) ? $arr_datos['webpay_transaction_id'] : '';
        $cardNumber = isset($arr_datos['cardNumber']) ? $arr_datos['cardNumber'] : '';
        $is_dte_afecto = isset($arr_datos['dte_afecto']) ? $arr_datos['dte_afecto'] : true;
        $seller_id = isset($arr_datos['seller_id']) ? $arr_datos['seller_id'] : 0;
        $order_neto = isset($arr_datos['neto']) ? $arr_datos['neto'] : -1;

        $is_dte_afecto_arr = ($is_dte_afecto == false) ? array( 'is_dte_afecto' => $is_dte_afecto ) : null;

        //solo para esos casos donde se debe emitir otro documento, que no sea boleta, para rebajar stock
        //y que permita monto $ 0, ya que el sii exige un mínimo de 4 180
        if( $order_neto == 0 && $tipo_docto !== 'nv' )
        {
            $tipo_docto = 'b';
        }

        /*   'fecha_hoy' => $hoy,
          'timestamp' => $gmt_date,
          'fecha_from_timestamp' => $hoy_from_timestamp,
          'sucursal_to_send' => $sucursal_bsale_to_send, */

        $logtable = new LogDocumentos();

        //guardo aquí el id del cliente de la nv, en caso de que exista
        $client_id = null;

        //nvb= nota de vta boleta
        $log_row = $logtable->getIdsDocumentoFromLog('nv', $order_id);
        $log_row_bol = $logtable->getIdsDocumentoFromLog('be', $order_id);
        $log_row_fact = $logtable->getIdsDocumentoFromLog('f', $order_id);

        //busco nv asociada
        //detailId= id de la nv, no el folio                      
        $nv_id = isset($log_row['remoto_id']) ? (int) $log_row['remoto_id'] : null;
        $boleta_id = isset($log_row_bol['remoto_id']) ? (int) $log_row_bol['remoto_id'] : null;
        $factura_id = isset($log_row_fact['remoto_id']) ? (int) $log_row_fact['remoto_id'] : null;

        if( isset($_REQUEST['test_dte']) && $nv_id )
        {
            Funciones::print_r_html("ya se ha emitido nv id=$nv_id para order #$order_number. Continua.");
        }
        if( isset($_REQUEST['test_dte']) && $boleta_id )
        {
            Funciones::print_r_html("ya se ha emitido boleta id=$boleta_id para order #$order_number. Continua.");
        }
        if( isset($_REQUEST['test_dte']) && $factura_id )
        {
            Funciones::print_r_html("ya se ha emitido fact id=$factura_id para order #$order_number. Continua.");
        }



        //series de los prods de esta venta, en caso de que tengan
        $series = '';

        $sistema = INTEGRACION_SISTEMA; //
        //
        if( $tipo_docto === 'b' && $boleta_id && $sistema !== 'woocommerce' )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("pedido #$order_number intenta emitir boleta, pero ya tiene boleta $boleta_id. Se omite");
            }
            else
            {
                return false;
            }
        }
        elseif( $tipo_docto === 'f' && $factura_id && $sistema !== 'woocommerce' )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("pedido #$order_number intenta emitir factura, pero ya tiene boleta $factura_id. Se omite");
            }
            else
            {
                return false;
            }
        }

        //si la encontré, paso la nv asociada          
        if( $nv_id && $tipo_docto != 'nv' )
        {
            //obtengo el detalle de los items de la nv
            $doctoclass = new Documento();
            $datos_items = $doctoclass->getDetailsIdDocto($nv_id, true);

            //asigno prodcutos de la nv al dte a emitir, solo si la nv tiene prods
            if( $datos_items && count($datos_items) > 0 )
            {
                $productos_arr = $datos_items;

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($productos_arr, "NV id = $nv_id encontrada para orden $order_number, que quiere emitir"
                            . "tipo docto = '$tipo_docto', items:");
                }
            }
            else
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($datos_items, "NV id = $nv_id encontrada para orden $order_number, que quiere emitir"
                            . "tipo docto = '$tipo_docto', pero no tiene items. Se genera $tipo_docto desde cero:");
                }
            }

            //busco cliente id de la nv
            $bol = new Boleta();
            $docto = $bol->getBoleta($nv_id);

            //id del cliente que debo enviar en el dte a emitir, asociado a la nv
            $client_id = isset($docto['client']['id']) ? $docto['client']['id'] : null;
            //Funciones::print_r_html($docto, "NV id = $nv_id docto:");
        }
        //intento emitir nv para pedido que ya tiene nv
        elseif( $nv_id && $tipo_docto === 'nv' && $sistema !== 'woocommerce' )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("pedido #$order_number intenta emitir nv, pero ya tiene nv asociada: $nv_id. Se omite");
            }
            if( !isset($_REQUEST['test_dte']) )
            {
                return false;
            }
        }
        //intento emitir nv para pedido que ya tiene fact o bol
        elseif( $tipo_docto === 'nv' && ($factura_id || $boleta_id) && $sistema !== 'woocommerce' )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("pedido #$order_number intenta emitir nv, pero ya tiene factura $factura_id o boleta $boleta_id. Se omite");
            }
            if( !isset($_REQUEST['test_dte']) )
            {
                return false;
            }
        }
        else
        {
            //debo incluir numeros de serie para cada item?
            $prods_con_serie_arr = $this->add_serial_to_items($productos_arr);
            $productos_arr = $prods_con_serie_arr['items'];

            $series = $prods_con_serie_arr['serie'];
        }

        //genero dte según el tipo docto
        $utils = new Utils();

        $tipo_docto_nombre = $utils->get_tipo_docto_nombre($tipo_docto);

        //gd=guia de despacho
        if( $tipo_docto !== 'gd' && defined('DTE_PRECIOS_FROM_BSALE') && DTE_PRECIOS_FROM_BSALE === true )
        {
            Funciones::print_r_html($productos_arr, "antes del filtro");
            $productos_arr = $this->filter_precios_bsale($productos_arr);

            Funciones::print_r_html($productos_arr, "despues del filtro");
            //no emito boleta por ahora, pues estoy en pruebas
            //return true;
        }

        $sucursal_id = Funciones::get_matriz_bsale();

        //forzar sucursa del Bsale?
        if( $sucursal_to_send > 0 )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("BsaleDTE->crear_dte_bsale, fuerzo sucursal Bsale to send a: $sucursal_to_send:");
            }
            $sucursal_id = $sucursal_to_send;
        }


        if( $tipo_docto === 'b' )
        {
            $dynamicAttributes = array();

            if( Funciones::get_dinam_attr_boleta($is_dte_afecto_arr) > 0 )
            {
                $prefix = Funciones::get_order_prefix();
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($prefix . $order_number),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_boleta($is_dte_afecto_arr) ); //comentario
            }
            //nota dejada por el cliente en el checkout
            if( Funciones::get_dinam_attr_boleta_notas($is_dte_afecto_arr) > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($customer_note),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_boleta_notas($is_dte_afecto_arr) ); //comentario
            }

            //datos de webpay
            if( defined('DINAM_ATRIB_WEBPAY_TARJETA_BOLETA') && DINAM_ATRIB_WEBPAY_TARJETA_BOLETA > 0 )
            {
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($cardNumber),
                    'dynamicAttributeId' => DINAM_ATRIB_WEBPAY_TARJETA_BOLETA ); //comentario
            }
            if( defined('DINAM_ATRIB_WEBPAY_TRANSAC_ID_BOLETA') && DINAM_ATRIB_WEBPAY_TRANSAC_ID_BOLETA > 0 )
            {
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($webpay_transaction_id),
                    'dynamicAttributeId' => DINAM_ATRIB_WEBPAY_TRANSAC_ID_BOLETA ); //comentario
            }

            //peso de productos en bol
            if( $peso_total > 0 && defined('BSALE_DINAM_ATRIB_BOLETA_PESO_PRODUCTOS') && BSALE_DINAM_ATRIB_BOLETA_PESO_PRODUCTOS > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($peso_total),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_BOLETA_PESO_PRODUCTOS ); //comentario
            }

            //direcc despacho en bol
            if( !empty($shipping_name) && defined('BSALE_DINAM_ATRIB_BOLETA_DIR_DESPACHO') && BSALE_DINAM_ATRIB_BOLETA_DIR_DESPACHO > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => $utils->filter_chars("$shipping_name: $shipping_address"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_BOLETA_DIR_DESPACHO ); //comentario
            }

            //SOLO nombre de envio
            if( !empty($shipping_name) && defined('BSALE_DINAM_ATRIB_BOLETA_MEDIO_ENVIO1') && BSALE_DINAM_ATRIB_BOLETA_MEDIO_ENVIO1 > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$shipping_name"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_BOLETA_MEDIO_ENVIO1 ); //comentario
            }


            if( !empty($series) && defined('BSALE_DINAM_ATRIB_BOLETA_SERIE') && BSALE_DINAM_ATRIB_BOLETA_SERIE > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$series"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_BOLETA_SERIE,
                ); //comentario
            }

            //agrego campos de shopify
            if( $tienda_nombre === '(SHOP)' )
            {
                $stop_date = date('Y-m-d 00:00:00');
                $tomorrow = date('d-m-Y', strtotime($stop_date . ' +1 day'));

                //si emito ticket en lugar de boletas
                if( Funciones::is_shopify_usar_ticket_boletas() )
                {
                    //agrego source de la venta (web)
                    if( defined('SHOPIFY_TICKET_DONDE_PROVIENE_VENTA_DINAM_ATTR_ID') )
                    {
                        //usar para indicar el nro de orden asociado
                        $dynamicAttributes [] = array(
                            'description' => SHOPIFY_TICKET_DONDE_PROVIENE_VENTA_VALUE,
                            'dynamicAttributeId' => SHOPIFY_TICKET_DONDE_PROVIENE_VENTA_DINAM_ATTR_ID,
                        );
                    }
                    //fecha de entrega
                    if( defined('SHOPIFY_TICKET_FECHA_ENTREGA_DINAM_ATTR_ID') && SHOPIFY_TICKET_FECHA_ENTREGA_DINAM_ATTR_ID > 0 )
                    {
                        //usar para indicar el nro de orden asociado
                        $dynamicAttributes [] = array(
                            'description' => $tomorrow,
                            'dynamicAttributeId' => SHOPIFY_TICKET_FECHA_ENTREGA_DINAM_ATTR_ID,
                        );
                    }
                }
                else
                {
                    //agrego source de la venta (web)
                    if( defined('SHOPIFY_BOL_DONDE_PROVIENE_VENTA_DINAM_ATTR_ID') &&
                            SHOPIFY_BOL_DONDE_PROVIENE_VENTA_DINAM_ATTR_ID > 0 )
                    {
                        $dynamicAttributes [] = array(
                            'description' => $source, // SHOPIFY_BOL_DONDE_PROVIENE_VENTA_VALUE,
                            'dynamicAttributeId' => SHOPIFY_BOL_DONDE_PROVIENE_VENTA_DINAM_ATTR_ID,
                        );
                    }
                    //fecha de entrega
                    if( defined('SHOPIFY_BOL_FECHA_ENTREGA_DINAM_ATTR_ID') && SHOPIFY_BOL_FECHA_ENTREGA_DINAM_ATTR_ID !== '' )
                    {
                        //usar para indicar el nro de orden asociado
                        $dynamicAttributes [] = array(
                            'description' => $tomorrow,
                            'dynamicAttributeId' => SHOPIFY_BOL_FECHA_ENTREGA_DINAM_ATTR_ID,
                        );
                    }
                }
            }
            if( count($dynamicAttributes) <= 0 )
            {
                $dynamicAttributes = null;
            }


            $arr = array(
                'documentTypeId' => Funciones::get_boleta_id($is_dte_afecto_arr),
                'officeId' => $sucursal_id,
                'sendEmail' => Funciones::get_send_email(),
                'dispatch' => Funciones::is_despacho_boleta(),
                'dynamicAttributes' => $dynamicAttributes,
                // "priceListId" => 18,
                'emissionDate' => $gmt_date,
                'expirationDate' => $gmt_date_expiracion,
                'declareSii' => Funciones::get_declare_sii(),
                'client' => $array_cliente,
                'details' => $productos_arr,
                'tienda' => $tienda_nombre,
                    // 'payments'=>$modo_pago_arr,
            );

            if( $seller_id > 0 )
            {
                $arr['sellerId'] = $seller_id;
            }

            //incluyo referencia a orden de copra (oc)
            if( is_array($references) && count($references) > 0 )
            {
                $arr['references'] = $references;
            }

            //cambio declareSii por declare
            if( Funciones::get_pais() === 'PE' )
            {
                $arr['declare'] = $arr['declareSii'];
                unset($arr['declareSii']);
            }

            //despacho de prods marcados como
            //"permitir vender sin stock"
            if( Funciones::is_despacho_boleta() == 2 )
            {
                $arr['strictStockValidation'] = 1;
            }

            //tienda shopify?
            if( $tienda_nombre === '(SHOP)' )
            {
                if( defined('DESPACHO_BOLETA_SHOPIFY') )
                {
                    $arr['dispatch'] = DESPACHO_BOLETA_SHOPIFY;
                }
                //despacho de prods marcados como
                //"permitir vender sin stock"
                if( defined('DESPACHO_BOLETA_SHOPIFY') && DESPACHO_BOLETA_SHOPIFY == 2 )
                {
                    $arr['strictStockValidation'] = 1;
                }
            }

            //seller id
            if( defined('SELLER_BSALE_ID_TO_BOLETA') && SELLER_BSALE_ID_TO_BOLETA > 0 )
            {
                $arr['sellerId'] = (int) SELLER_BSALE_ID_TO_BOLETA;
            }
            //si hay modo pago
            if( $modo_pago_arr != null )
            {
                $arr['payments'] = $modo_pago_arr;
            }

            //saco datos del cliente, dejo el id
            if( $client_id != null )
            {
                unset($arr['client']);
                $arr['clientId'] = $client_id;
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($arr, "BsaleDTE->crear_dte_bsale, datos a enviar (datos no enviados Bsale en test):");
                return;
            }

            $nv = new Boleta();
            $result = $nv->postBoleta($arr, $order_number);

            if( !isset($result['urlPublicView']) )
            {
                $otras_sucursales = defined('BSALE_SUCURSAL_DTE_SUCURSALES')  && !empty(BSALE_SUCURSAL_DTE_SUCURSALES)? explode(',', BSALE_SUCURSAL_DTE_SUCURSALES) : array();
                $otras_sucursales2 = Funciones::get_sucursales_bsale();

                if( is_array($otras_sucursales2) && count($otras_sucursales2) > 0 )
                {
                    $otras_sucursales = array_merge($otras_sucursales, $otras_sucursales2);
                }

                $otras_sucursales = array_merge(array( Funciones::get_matriz_bsale() ), $otras_sucursales);

                $otras_sucursales = array_unique($otras_sucursales);

                //saco sucursal id desde la que intenté emitir el dte 
                if( ($key = array_search($sucursal_id, $otras_sucursales)) !== false )
                {
                    unset($otras_sucursales[$key]);
                }


                foreach( $otras_sucursales as $sucursal_id )
                {
                    $sucursal_id = (int) $sucursal_id;

                    if( $sucursal_id <= 0 )
                    {
                        continue;
                    }
                    $arr['officeId'] = $sucursal_id;

                    $nv = new Boleta();
                    $result = $nv->postBoleta($arr, $order_number);

                    //si se emitió correctamente, salgo del loop
                    if( isset($result['urlPublicView']) )
                    {
                        break;
                    }
                }
            }
        }
        if( $tipo_docto === 'nv' )
        {
            $dynamicAttributes = array();

            if( Funciones::get_dinam_attr_nv() > 0 )
            {
                $prefix = Funciones::get_order_prefix();
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($prefix . $order_number),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_nv(),
                ); //comentario
            }
            //nota dejada por el cliente en el checkout
            if( Funciones::get_dinam_attr_nv_notas() > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($customer_note),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_nv_notas() ); //comentario
            }
            if( !empty($series) && defined('BSALE_DINAM_ATRIB_NV_SERIE') && BSALE_DINAM_ATRIB_NV_SERIE > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$series"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_NV_SERIE,
                ); //comentario
            }

            //peso de productos en nv
            if( $peso_total > 0 && defined('BSALE_DINAM_ATRIB_NV_PESO_PRODUCTOS') && BSALE_DINAM_ATRIB_NV_PESO_PRODUCTOS > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($peso_total),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_NV_PESO_PRODUCTOS ); //comentario
            }

            //direcc despacho en nv
            if( !empty($shipping_name) && defined('BSALE_DINAM_ATRIB_NV_DIR_DESPACHO') && BSALE_DINAM_ATRIB_NV_DIR_DESPACHO > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$shipping_name: $shipping_address"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_NV_DIR_DESPACHO ); //comentario
            }

            if( !empty($shipping_name) && defined('BSALE_DINAM_ATRIB_NV_MEDIO_ENVIO1') && BSALE_DINAM_ATRIB_NV_MEDIO_ENVIO1 > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$shipping_name"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_NV_MEDIO_ENVIO1 ); //comentario
            }
            if( count($dynamicAttributes) <= 0 )
            {
                $dynamicAttributes = null;
            }

            $sendEmail = 0; //Funciones::get_send_email()

            $arr = array(
                'documentTypeId' => Funciones::get_nv_id(),
                'officeId' => $sucursal_id,
                'sendEmail' => $sendEmail, //,
                //'dispatch' => 0, nv nolleva despacho
                'dynamicAttributes' => $dynamicAttributes,
                // "priceListId" => 18,
                'emissionDate' => $gmt_date,
                'expirationDate' => $gmt_date_expiracion,
                'declareSii' => Funciones::get_declare_sii(),
                'client' => $array_cliente,
                'details' => $productos_arr,
                    // 'payments'=>$modo_pago_arr,
            );
            if( $seller_id > 0 )
            {
                $arr['sellerId'] = $seller_id;
            }

            //incluyo referencia a orden de copra (oc)
            if( is_array($references) && count($references) > 0 )
            {
                $arr['references'] = $references;
            }

            //cambio declareSii por declare
            if( Funciones::get_pais() === 'PE' )
            {
                $arr['declare'] = $arr['declareSii'];
                unset($arr['declareSii']);
            }

            //seller id
            if( defined('SELLER_BSALE_ID_TO_NV') && SELLER_BSALE_ID_TO_NV > 0 )
            {
                $arr['sellerId'] = (int) SELLER_BSALE_ID_TO_NV;
            }
            //si hay modo pago, nv no lleva pago
            /* if( $modo_pago_arr != null )
              {
              $arr['payments'] = $modo_pago_arr;
              } */

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($arr, "BsaleDTE->crear_dte_bsale, datos a enviar (datos no enviados Bsale en test):");
                return;
            }

            $nv = new NotaVenta();
            $result = $nv->postNotaVenta($arr, $order_number);

            if( !isset($result['urlPublicView']) )
            {
                $otras_sucursales = defined('BSALE_SUCURSAL_DTE_SUCURSALES') && !empty(BSALE_SUCURSAL_DTE_SUCURSALES) ? explode(',', BSALE_SUCURSAL_DTE_SUCURSALES) : array();

                $otras_sucursales2 = Funciones::get_sucursales_bsale();

                if( is_array($otras_sucursales2) && count($otras_sucursales2) > 0 )
                {
                    $otras_sucursales = array_merge($otras_sucursales, $otras_sucursales2);
                }
                //saco sucursal id desde la que intenté emitir el dte 
                if( ($key = array_search($sucursal_id, $otras_sucursales)) !== false )
                {
                    unset($otras_sucursales[$key]);
                }
                /* Funciones::print_r_html($otras_sucursales, "otras surusales para mietir nv");
                  die("xxx"); */
                foreach( $otras_sucursales as $sucursal_id )
                {
                    if( $sucursal_id <= 0 )
                    {
                        continue;
                    }

                    $arr['officeId'] = (int) $sucursal_id;

                    $nv = new NotaVenta();
                    $result = $nv->postNotaVenta($arr, $order_number);

                    //si se emitió correctamente, salgo del loop
                    if( isset($result['urlPublicView']) )
                    {
                        break;
                    }
                }
            }
        }
        elseif( $tipo_docto === 'f' )
        {
            $dynamicAttributes = array();

            if( Funciones::get_dinam_attr_factura($is_dte_afecto_arr) > 0 )
            {
                $prefix = Funciones::get_order_prefix();
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($prefix . $order_number),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_factura($is_dte_afecto_arr) ); //comentario
            }
            //nota dejada por el cliente en el checkout
            if( Funciones::get_dinam_attr_factura_notas() > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($customer_note),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_factura_notas() ); //comentario
            }
            if( !empty($series) && defined('BSALE_DINAM_ATRIB_FACTURA_SERIE') && BSALE_DINAM_ATRIB_FACTURA_SERIE > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$series"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_FACTURA_SERIE,
                ); //comentario
            }
            //SOLO nombre de envio
            if( !empty($shipping_name) && defined('BSALE_DINAM_ATRIB_FACTURA_MEDIO_ENVIO1') && BSALE_DINAM_ATRIB_FACTURA_MEDIO_ENVIO1 > 0 )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("factura, set shipping name a '$shipping_name'");
                }
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$shipping_name"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_FACTURA_MEDIO_ENVIO1 ); //comentario
            }

            //peso de productos en fact
            if( $peso_total > 0 && defined('BSALE_DINAM_ATRIB_FACTURA_PESO_PRODUCTOS') && BSALE_DINAM_ATRIB_FACTURA_PESO_PRODUCTOS > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($peso_total),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_FACTURA_PESO_PRODUCTOS ); //comentario
            }
            //direcc despacho en fact
            if( !empty($shipping_name) && defined('BSALE_DINAM_ATRIB_FACTURA_DIR_DESPACHO') && BSALE_DINAM_ATRIB_FACTURA_DIR_DESPACHO > 0 )
            {
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode("$shipping_name: $shipping_address"),
                    'dynamicAttributeId' => BSALE_DINAM_ATRIB_FACTURA_DIR_DESPACHO ); //comentario
            }

            //agrego campos de shopify
            if( $tienda_nombre === '(SHOP)' )
            {
                $stop_date = date('Y-m-d 00:00:00');
                $tomorrow = date('d-m-Y', strtotime($stop_date . ' +1 day'));

                //agrego source de la venta (web)
                if( defined('SHOPIFY_FACTURA_DONDE_PROVIENE_VENTA_DINAM_ATTR_ID') )
                {
                    $dynamicAttributes [] = array(
                        'description' => $source,
                        'dynamicAttributeId' => SHOPIFY_FACTURA_DONDE_PROVIENE_VENTA_DINAM_ATTR_ID,
                    );
                }
                //fecha de entrega
                if( defined('SHOPIFY_FACTURA_FECHA_ENTREGA_DINAM_ATTR_ID') )
                {
                    //usar para indicar el nro de orden asociado
                    $dynamicAttributes [] = array(
                        'description' => $tomorrow,
                        'dynamicAttributeId' => SHOPIFY_FACTURA_FECHA_ENTREGA_DINAM_ATTR_ID,
                    );
                }
            }

            if( count($dynamicAttributes) <= 0 )
            {
                $dynamicAttributes = null;
            }

            $arr = array(
                'documentTypeId' => Funciones::get_factura_id($is_dte_afecto_arr),
                'officeId' => $sucursal_id,
                'sendEmail' => Funciones::get_send_email(),
                'dispatch' => Funciones::is_despacho_factura(),
                'dynamicAttributes' => $dynamicAttributes,
                // "priceListId" => 18,
                'emissionDate' => $gmt_date,
                'expirationDate' => $gmt_date_expiracion,
                'declareSii' => Funciones::get_declare_sii(),
                'client' => $array_cliente,
                'details' => $productos_arr,
            );
            if( $seller_id > 0 )
            {
                $arr['sellerId'] = $seller_id;
            }

            //incluyo referencia a orden de copra (oc)
            if( is_array($references) && count($references) > 0 )
            {
                $arr['references'] = $references;
            }

            //cambio declareSii por declare
            if( Funciones::get_pais() === 'PE' )
            {
                $arr['declare'] = $arr['declareSii'];
                unset($arr['declareSii']);
            }

            //despacho de prods marcados como
            //"permitir vender sin stock"
            if( Funciones::is_despacho_factura() == 2 )
            {
                $arr['strictStockValidation'] = 1;
            }

            //seller id
            if( defined('SELLER_BSALE_ID_TO_FACTURA') && SELLER_BSALE_ID_TO_FACTURA > 0 )
            {
                $arr['sellerId'] = (int) SELLER_BSALE_ID_TO_FACTURA;
            }
            //si hay modo pago
            if( $modo_pago_arr != null )
            {
                $arr['payments'] = $modo_pago_arr;
            }

            //saco datos del cliente, dejo el id
            if( $client_id != null )
            {
                unset($arr['client']);
                $arr['clientId'] = $client_id;
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($arr, "BsaleDTE->crear_dte_bsale, datos a enviar (datos no enviados Bsale en test):");
                return;
            }

            $nv = new FacturaAfecta();
            $result = $nv->postFactura($arr, $order_number);

            //si hubo error, intento emitir la boleta dede otra sucursal de Bsale
            if( !isset($result['urlPublicView']) )
            {
                $otras_sucursales = defined('BSALE_SUCURSAL_DTE_SUCURSALES') && !empty(BSALE_SUCURSAL_DTE_SUCURSALES) ? explode(',', BSALE_SUCURSAL_DTE_SUCURSALES) : array();
                $otras_sucursales2 = Funciones::get_sucursales_bsale();

                if( is_array($otras_sucursales2) && count($otras_sucursales2) > 0 )
                {
                    $otras_sucursales = array_merge($otras_sucursales, $otras_sucursales2);
                }
                //saco sucursal id desde la que intenté emitir el dte 
                if( ($key = array_search($sucursal_id, $otras_sucursales)) !== false )
                {
                    unset($otras_sucursales[$key]);
                }

                foreach( $otras_sucursales as $sucursal_id )
                {
                    if( $sucursal_id <= 0 )
                    {
                        continue;
                    }

                    $arr['officeId'] = (int) $sucursal_id;

                    $nv = new FacturaAfecta();
                    $result = $nv->postFactura($arr, $order_number);

                    //si se emitió correctamente, salgo del loop
                    if( isset($result['urlPublicView']) )
                    {
                        break;
                    }
                }
            }
        }
        //guia de despacho
        elseif( $tipo_docto === 'gd' )
        {
            $sucursal_id = SUCURSAL_BSALE_GUIA_DESPACHO_FROM;

            $dynamicAttributes = array();

            if( Funciones::get_dinam_attr_gd() > 0 )
            {
                $prefix = Funciones::get_order_prefix();
                //usar para indicar el nro de orden asociado
                $dynamicAttributes [] = array(
                    'description' => utf8_encode($prefix . $order_number),
                    'dynamicAttributeId' => Funciones::get_dinam_attr_gd() ); //comentario
            }

            if( count($dynamicAttributes) <= 0 )
            {
                $dynamicAttributes = null;
            }

            $arr = array(
                'documentTypeId' => Funciones::get_gd_id(),
                'officeId' => $sucursal_id,
                'destinationOfficeId' => SUCURSAL_BSALE_GUIA_DESPACHO_TO,
                'shippingTypeId' => DESPACHO_GD_TIPO_DESPACHO,
                'sendEmail' => 0, //gd no se envia al cliente
                'dispatch' => Funciones::is_despacho_gd(),
                'dynamicAttributes' => $dynamicAttributes,
                // "priceListId" => 18,
                'emissionDate' => $gmt_date,
                'expirationDate' => $gmt_date_expiracion,
                'declareSii' => Funciones::get_declare_sii(),
                'client' => $array_cliente, //gd no lleva cliente
                'details' => $productos_arr,
                'tienda' => $tienda_nombre,
                    // 'payments'=>$modo_pago_arr,
            );
            if( $seller_id > 0 )
            {
                $arr['sellerId'] = $seller_id;
            }

            //incluyo referencia a orden de copra (oc)
            if( is_array($references) && count($references) > 0 )
            {
                $arr['references'] = $references;
            }

            //cambio declareSii por declare
            if( Funciones::get_pais() === 'PE' )
            {
                $arr['declare'] = $arr['declareSii'];
                unset($arr['declareSii']);
            }

            //despacho de prods marcados como
            //"permitir vender sin stock"
            if( Funciones::is_despacho_gd() == 2 )
            {
                $arr['strictStockValidation'] = 1;
            }

            //si hay modo pago
            //gd no lleva modo de pago
            if( $modo_pago_arr != null )
            {
                //$arr['payments'] = $modo_pago_arr;
            }

            //saco datos del cliente, dejo el id
            if( $client_id != null )
            {
                unset($arr['client']);
                $arr['clientId'] = $client_id;
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($arr, "BsaleDTE->crear_dte_bsale, datos a enviar (datos no enviados Bsale en test):");
                return;
            }

            $nv = new GuiaDespacho();
            $result = $nv->postGD($arr, $order_number);

            Funciones::print_r_html($arr, "emito guia de despacho, datos a enviar a Bsale:");
            Funciones::print_r_html($result, "respuesta de Bsale:");

            if( !isset($result['urlPublicView']) )
            {
                //veo si viene GD
                if( isset($result['shipping_type']) && isset($result['guide']) )
                {
                    $gd_id = $result['guide']['id'];
                    //get del documento para obtener su url
                    $docto_obj = new Documento();

                    $gd = $docto_obj->get_docto($gd_id);

                    if( isset($gd['urlPublicView']) )
                    {
                        $result = $gd;
                    }
                }
            }
        }
        if( !isset($result['urlPublicView']) )
        {
            /*//envio email de aviso
            $utils = new Utils();
            $email_cliente = EMAIL_ERROR;
            $subject = "$tienda_nombre: Error al emitir $tipo_docto_nombre para orden $order_number";

            $error_msg = isset($result['error']) ? $result['error'] : print_r($result, true);

            $json = json_encode($arr, JSON_UNESCAPED_UNICODE);

            $message = "<p>$tienda_nombre: Error al emitir $tipo_docto_nombre para orden #$order_number:</p>" .
                    "<p>Respuesta de Bsale :</p>" .
                    "<p><strong>$error_msg</strong></p>" .
                    "<p></p>" .
                    "<p>Datos enviados (sirven para enviarlos al soporte, en caso de que sea necesario):</p>" .
                    $json .
                    "<p></p>";
            $utils->sendEmail($email_cliente, $subject, $message);*/
        }
        else
        {
            //agrego als series a la respuesta, para guardarlas en las tiendas
            $result['series'] = $series;
        }


        return $result;
    }

    /**
     * 
     * @param type $arr_datospara cada prodcutos del pedido, obtiene su precio de Bsale
     */
    public function filter_precios_bsale($productos_arr)
    {
        $pbsale = new ProductoBsale();

        //mismo arr, pero con los precios de Bsale y sin descto
        $productos_bsale_arr = array();
        $new_arr = array();

        //total descto neto en pesos, de shopify
        $descto_shop_neto = 0;

        //total del pedido en neto, con los precios de shopify
        //se le ha restado el descto en pesos
        $total_shop_neto = 0;

        //recorro los productos
        foreach( $productos_arr as $p )
        {
            $qty = $p['quantity'];
            $code = isset($p['code']) ? $p['code'] : null;

            $discount = $p['discount']; //descto porcent, sobre el valor del item CON impuesto
            $precio_neto = $p['netUnitValue'];

            //shop: valor total para este item
            $total_linea = $precio_neto * Funciones::get_valor_iva() * $qty;
            //shop: total linea sin impuesto
            $total_linea_neto = $precio_neto * $qty;

            //viene descto?
            if( $discount > 0 )
            {
                //(paso descto a pesos
                $descto_item_pesos = ($discount / 100) * $total_linea;
                //descto pesos sin impuesto
                $descto_item_pesos_neto = $descto_item_pesos / Funciones::get_valor_iva();
                //$descto_shop_neto += $descto_item_pesos_neto;
                //resto en neto al total del peidos 
                //$total_shop_neto -= $descto_shop_neto;

                Funciones::print_r_html("filter precios bsale: hay descto de % $discount, "
                        . "descto total: $ $descto_item_pesos_neto");
            }
            else
            {
                $descto_item_pesos = 0;
                $descto_item_pesos_neto = 0;
            }

            //resto el descto neto enm pesos, del precio neto en pesos
            $p['netUnitValue'] = $precio_neto - $descto_item_pesos_neto;

            //en caso de que quede en valor negativo, al preguntar por el precio a bsale y hacer el cálculo
            $p['netUnitValue'] = $p['netUnitValue'] < 0 ? 0 : $p['netUnitValue'];

            $precio_neto = $p['netUnitValue'];

            $total_shop_neto += $precio_neto * $qty;

            //el descto estará redistribuido entre los itemes de la compra
            //por lo que este param no va
            unset($p['discount']);

            if( empty($code) )
            {
                Funciones::print_r_html("filter precios bsale: producto sin sku, se agrega y omiten ajustes");
                $precio_bsale_con_impuesto = 0;
            }
            else
            {
                //busco precio con impuestos en Bsale para este producto
                $precio_bsale_con_impuesto = $pbsale->get_precio_producto($code);
            }

            //si en bsale no existe este prodcuto o no tiene precio, omito reajuste
            if( $precio_bsale_con_impuesto <= 0 )
            {
                Funciones::print_r_html("filter precios bsale: producto sku=$code no tiene precio en Bsale, se agrega y omiten ajustes");
                $precio_neto_bsale = 0;
            }
            else
            {
                $precio_neto_bsale = $precio_bsale_con_impuesto / Funciones::get_valor_iva();
            }

            $p['bsale_neto'] = $precio_neto_bsale;
            $productos_bsale_arr[] = $p;
        }

        Funciones::print_r_html($productos_bsale_arr, "filter precios bsale: antes de repartir descto");

        //calculo % de precios para repartir descto
        $productos_bsale_arr = $this->filter_add_bsale_percent($productos_bsale_arr);

        Funciones::print_r_html($productos_bsale_arr, "filter precios bsale: despues de agregar % en precios bsale");

        //calculo suma de prods bsale antes del decto
        $total_neto_bsale_antes_descto = $this->filter_total_bsale_neto($productos_bsale_arr);

        //reparto descto
        $new_productos = $this->filter_descuento($productos_bsale_arr, $total_shop_neto, $descto_shop_neto, $total_neto_bsale_antes_descto);
        $total_neto_nuevo = $this->filter_total_neto($new_productos);

        Funciones::print_r_html($new_productos, "filter precios bsale: después de repartir descto (total shop neto: $ $total_shop_neto, "
                . "total neto precios bsale: $total_neto_nuevo)");

        //redistribuyo diferencia de totales
        $new_productos = $this->filter_repartir_total($new_productos, $total_shop_neto, $total_neto_nuevo);

        $new_productos = $this->filter_quitar_extra($new_productos);

        Funciones::print_r_html($new_productos, "filter precios bsale: después de todo el proceso");

        //die("boleta no se emite aun");

        return $new_productos;
    }

    /**
     * 
     * @param type $new_productos
     * @param type $total_shop_neto neto que DEBE sumar el pedido
     */
    private function filter_repartir_total($productos_arr, $total_shop_neto, $total_neto_nuevo)
    {
        $arr_new = array();
        //si son iguales, se devuelve el mismo:
        if( $total_shop_neto == $total_neto_nuevo )
        {
            Funciones::print_r_html("filter_repartir_total, totales son iguales, no recorro prods");
            return $productos_arr;
        }
        //diferencia a repartir:
        //si es > 0, debo SUMAR al precio de los prods
        //si es > 0, debo RESTAR a los productos
        $diferencia = $total_shop_neto - $total_neto_nuevo;

        Funciones::print_r_html("filter_repartir_total, diferencia shop = $total_shop_neto - neto = $total_neto_nuevo a distribuir: $diferencia");

        //recorro los productos
        foreach( $productos_arr as $p )
        {
            $qty = $p['quantity'];
            $code = isset($p['code']) ? $p['code'] : null;
            $precio_neto = $p['netUnitValue'];

            if( empty($code) )
            {
                Funciones::print_r_html("filter_repartir_total: producto qty=$qty, sin sku, se agrega y omite ajuste");
                $arr_new[] = $p;
                continue;
            }

            //diferencia unitaria
            //descto que intento hacer para este producto
            $descto_unitario = $diferencia / $qty;

            $precio_neto_aux = $precio_neto + $descto_unitario;

            //si el descto es mayor al precio, lo dejo en $1 menos que el precio neto
            if( $precio_neto_aux == 0 )
            {
                if( $descto_unitario < 0 )
                {
                    //para que el precio quede en $ 1
                    $descto_unitario = $descto_unitario + 1;
                }
                else
                {
                    //para que el precio quede en $ 1
                    $descto_unitario = $descto_unitario - 1;
                }
            }
            //cambio el precio para colocar el descto de hsopify
            $p['netUnitValue'] = $precio_neto + $descto_unitario;

            $arr_new[] = $p;

            //resto al descto global el descto que asigné aquí
            $diferencia -= $descto_unitario * $qty;
        }

        return $arr_new;
    }

    /**
     * devuelve la suma neta del pedido
     * @param type $productos_arr
     */
    private function filter_total_neto($productos_arr)
    {
        $total_neto = 0;

        foreach( $productos_arr as $p )
        {
            $qty = $p['quantity'];
            $code = isset($p['code']) ? $p['code'] : null;
            $precio_neto = $p['netUnitValue'];

            if( empty($code) )
            {
                Funciones::print_r_html("filter_total_neto: producto qty=$qty, sin sku, se agrega y omite ajuste");
                continue;
            }

            //shop: valor total para este item
            //$total_linea = $precio_neto * Funciones::get_valor_iva() * $qty;
            //shop: total linea sin impuesto
            $total_linea_neto = $precio_neto * $qty;

            $total_neto += $total_linea_neto;
        }

        return $total_neto;
    }

    /**
     * recorre listado de productos, despuyes de todo el proceso,
     * y saca los que tiene qty o precio z=0
     * @param type $productos_arr
     * @return type
     */
    private function filter_quitar_extra($productos_arr)
    {
        $arraux = array();

        foreach( $productos_arr as $p )
        {
            $qty = $p['quantity'];
            // $code = isset($p['code']) ? $p['code'] : null;
            $precio_neto = $p['netUnitValue'];

            unset($p['descto_unitario']);

            if( $qty <= 0 || $precio_neto <= 0 )
            {
                Funciones::print_r_html("filter_quitar_extra: saco producto con qty=$qty y precio $ $precio_neto");
                continue;
            }
            $arraux[] = $p;
        }

        return $arraux;
    }

    /**
     * devuelve la suma de los netos de bsale
     * @param type $productos_arr
     * @return type
     */
    private function filter_total_bsale_neto($productos_arr)
    {
        $total_neto = 0;

        foreach( $productos_arr as $p )
        {
            $qty = $p['quantity'];
            $code = isset($p['code']) ? $p['code'] : null;

            if( empty($code) )
            {
                Funciones::print_r_html("filter_total_bsale_neto: producto qty=$qty, sin sku, se agrega y omite ajuste");
                continue;
            }

            $precio_neto = $p['bsale_neto'];

            //shop: valor total para este item
            //$total_linea = $precio_neto * Funciones::get_valor_iva() * $qty;
            //shop: total linea sin impuesto
            $total_linea_neto = $precio_neto * $qty;

            $total_neto += $total_linea_neto;
        }


        return $total_neto;
    }

    /**
     * recorre el precio bsale de los productos y agrega el campo
     * 'bsale_percent' con el % de ese precio dentro del total del pedido 
     * @param type $productos_arr
     * @return type
     */
    private function filter_add_bsale_percent($productos_arr)
    {
        $total_bsale_neto = $this->filter_total_bsale_neto($productos_arr);

        $arraux = array();

        foreach( $productos_arr as $p )
        {
            $qty = $p['quantity'];
            $code = isset($p['code']) ? $p['code'] : null;

            if( empty($code) )
            {
                Funciones::print_r_html("filter_add_bsale_percent: producto qty=$qty, sin sku, se agrega y omite ajuste");
                $p['bsale_neto_porcent'] = 0;
                $arraux[] = $p;
                continue;
            }

            $precio_neto = $p['bsale_neto'];
            //calculo %
            $porcent = $precio_neto * $qty * 100 / $total_bsale_neto;
            $p['bsale_neto_porcent'] = $porcent;

            $arraux[] = $p;
        }
        return $arraux;
    }

    /**
     * recorro el listado de productos, dikstribuye el desto entre ellos
     * y paso el precio neto bsale a precio neto del pedido
     * @param type $productos_bsale_arr
     * @param type $total_shop_neto
     * @param type $descto_shop_neto
     */
    private function filter_descuento($productos_bsale_arr, $total_shop_neto, $descto_shop_neto, $total_neto_bsale_antes_descto)
    {
        $new_arr = array();

        foreach( $productos_bsale_arr as $p )
        {
            $qty = $p['quantity'];
            $code = isset($p['code']) ? $p['code'] : null;
            $neto = $p['netUnitValue'];

            /* if( empty($code) )
              {
              Funciones::print_r_html("filter_descuento: producto qty=$qty $ $neto, sin sku, se agrega y omite ajuste");
              $new_arr[] = $p;
              continue;
              } */

            $precio_neto = $p['bsale_neto'];
            $bsale_neto_porcent = $p['bsale_neto_porcent'];

            Funciones::print_r_html($p, "filter_descuento, recorro producto, bsale neto: $precio_neto, {$p['bsale_neto']}");

            //ya no se usarán

            unset($p['bsale_neto']);
            unset($p['bsale_neto_porcent']);
            //unset($p['code']); //xxx borrar al final!
            // $p['comment'] = "sku= $code"; //xxx borrar al final
            //si no hay descto que aplicar, paso el precio Bsale al precio neto 
            //o si en bsale este prorcuto tiene precio =0 
            if( /* $descto_shop_neto <= 0 || */ $precio_neto <= 0 )
            {
                Funciones::print_r_html("sku= $code, no hay descto que aplicar para este item");
                $p['netUnitValue'] = $precio_neto;
                $p['descto_unitario'] = 0;
                $new_arr[] = $p;
                continue;
            }

            //descto que intento hacer para este producto
            $descto_unitario = $precio_neto * $qty / $total_neto_bsale_antes_descto * $total_shop_neto;

            Funciones::print_r_html("sku= $code) filter_descuento, calculo descto "
                    . "descto unitario: $ $descto_unitario = neto bsale: $ $precio_neto * qty: $qty / total neto bsale: $ $total_neto_bsale_antes_descto"
                    . " * total shop neto: $ $total_shop_neto;");

            //$descto_unitario = $descto_shop_neto * $bsale_neto_porcent / $qty;
            //si el descto es mayor al precio, lo dejo en $1 menos que el precio neto
            if( $descto_unitario >= $precio_neto )
            {
                $descto_unitario = $precio_neto - 1;
            }
            $p['descto_unitario'] = $descto_unitario;

            //cambio el precio para colocar el descto de hsopify
            $p['netUnitValue'] = $descto_unitario; //xxx $precio_neto - $descto_unitario;
            //resto al descto global el descto que asigné aquí
            //xxx $descto_shop_neto -= $descto_unitario * $qty;

            $new_arr[] = $p;
        }

        return $new_arr;
    }

    public function add_serial_to_items($items)
    {
        //debo incluir numeros de serie para cada item?
        if( !defined('BSALE_INCLUDE_NRO_SERIE_EN_DTE') || BSALE_INCLUDE_NRO_SERIE_EN_DTE != true )
        {
            return array( 'items' => $items, 'serie' => null );
        }

        $arr = array();

        //obtengo datos de variante a partir de sku
        $vars = new VariantesProductoBsale();
        //busco nro de serie
        $serie_obj = new ProductoBsale();

        //listado de series de todos los productos de esta venta
        $series_txt = '';

        //recorro cada item y agrego el numero de serie
        foreach( $items as $i )
        {
            $sku = isset($i['code']) ? $i['code'] : null;

            //si no viene sku, no hago nada con este ime (ej: envios)
            if( empty($sku) )
            {
                $arr[] = $i;
                continue;
            }

            $quantity = $i['quantity'];

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("add_serial_to_items, busco serie para sku='$sku'...");
            }

            //datos de variacion de Bsale
            $variacion_resp = $vars->get_variacion_by_sku($sku);

            //si no existe en Bsale, no hago nada
            if( !isset($variacion_resp['id']) )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("variacion sku='$sku' no existe en Bsale");
                }
                $arr[] = $i;
                continue;
            }
            $variacion_id = $variacion_resp['id'];

            //busco nro de serie
            $serie_arr = $serie_obj->get_next_serie_variante($variacion_id, $quantity);

            //si no hay nro de serie, no hago nada
            if( empty($serie_arr) || count($serie_arr) <= 0 )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("variacion sku='$sku', id bsale=$variacion_id no tiene nro de serie available");
                }
                $arr[] = $i;
                continue;
            }


            //SI TIENE y se compró más de un prodcuto, debo colocar lo itemes de uno en uno
            if( $quantity == 1 )
            {
                $serie = $serie_arr[0];
                //si tiene nro de serie, lo coloco en el campo comment
                $i['comment'] = $serie;
                $arr[] = $i;

                $series_txt .= "$serie ";

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("add_serial_to_items sku='$sku', cantidad $quantity tiene serie '$serie'");
                }
                continue;
            }
            //si tiene más de un prodcuto con este sku
            for( $j = 0; $j < $quantity; $j++ )
            {
                //nuevo item
                $item_aux = $i;
                //cantidad 1
                $item_aux['quantity'] = 1;
                //agrego serie
                $nueva_serie = isset($serie_arr[$j]) ? $serie_arr[$j] : '';

                $item_aux['comment'] = $nueva_serie;

                $arr[] = $item_aux;

                $series_txt .= "$nueva_serie ";

                Funciones::print_r_html("add_serial_to_items sku='$sku', cantidad $quantity tiene serie '$nueva_serie' para item $j de $quantity");
            }
        }

        return array( 'items' => $arr, 'serie' => $series_txt );
    }
}
