<?php

//namespace woocommerce_bsalev2\lib\wp;
/* error_reporting(E_ALL);
  ini_set('display_errors', 1); */
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of WpBsale
 *
 * @author angelorum
 */
class WpBsale
{

    public function is_valid_for_dte($order, $tipo_documento)
    {
        $order_id = $order->get_id();
        $status = $order->get_status();

        //on-hold no emite boleta ni factura, pues no se ha pagado el pedido
        if( ($status === 'on-hold' || $status === 'pending') && $tipo_documento !== 'nv' )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("on-hold y no nv, no se emite : $tipo_documento");
            }
            return false;
        }

        //solo si hay medio de envio que saltar
        $skip_dte = Funciones::get_value('WC_SKIP_DTE_WITH_SHIPPING', null);
        if( empty($skip_dte) )
        {
            return true;
        }

        //medio de envio de esta orden
        $shipping_methods_arr = $order->get_shipping_methods();

        $shippings_str = $skip_dte;
        //paso a minúsculas
        $shippings_str = strtolower($shippings_str);

        //split por comas
        $shippings_arr = explode(',', $shippings_str);

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("is_valid_for_dte id=$order_id, reviso envios a omitir: '$shippings_str'");
        }

        if( !is_array($shippings_arr) || count($shippings_arr) <= 0 )
        {
            return true;
        }

        foreach( $shipping_methods_arr as $shipping_item_obj )
        {
            $shipping_data = $shipping_item_obj->get_data();

            $shipping_data_id = $shipping_data['id'];
            $shipping_data_order_id = $shipping_data['order_id'];
            $shipping_data_name = $shipping_data['name'];
            $shipping_data_method_title = $shipping_data['method_title'];
            $shipping_data_method_id = $shipping_data['method_id'];
            $shipping_data_instance_id = $shipping_data['instance_id'];
            $shipping_data_total = $shipping_data['total'];
            $shipping_data_total_tax = $shipping_data['total_tax'];
            $shipping_data_taxes = $shipping_data['taxes'];

            //a minusculas 
            $shipping_data_method_title = strtolower($shipping_data_method_title);

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("is_valid_for_dte id=$order_id, reviso envio de orden name = '$shipping_data_method_title'");
            }

            //recorro los medios de envio a omitir y los comparo con el titulo del envio de esta orden
            foreach( $shippings_arr as $shipping_a_omitir )
            {
                $shipping_a_omitir = strtolower($shipping_a_omitir);

                //este envio está en ls lista de los rechazados?
                if( strpos($shipping_data_method_title, $shipping_a_omitir) !== false )
                {
                    if( isset($_REQUEST['test_dte']) )
                    {
                        Funciones::print_r_html("is_valid_for_dte id=$order_id, reviso envio de orden name = '$shipping_data_method_title' "
                                . "está en el envio a omitir = '$shipping_a_omitir'. Para esta orden no se genera dte");
                    }
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * si esta orden, en este estado, puede emitir dte(boleta/factura)
     * @param type $order
     * @return boolean
     */
    public function is_for_dte($order, $tipo_documento)
    {
        $order_id = $order->get_id();

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . ", valido tipo documento = '$tipo_documento'");
        }
        if( $tipo_documento !== 'boleta' && $tipo_documento !== 'factura' )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . ", return true, se emite dte");
            }

            return true;
        }

        $modo_pago = $billing_paymethod = get_post_meta($order_id, '_payment_method', true);
        $status = $order->get_status();

        $tipo_docto_meta = get_post_meta($order_id, 'bsale_docto_tipo', true);

        //si viene test dte, solo es para ver, no para emitir
        //ya se ha emitido dte para esta orden
        if( $tipo_docto_meta === 'boleta' || $tipo_docto_meta === 'factura' )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("is_for_dte: pedido id=$order_id, en estado= '$status' ya ha emitido dtes");
            }
            else
            {
                return false;
            }
        }

        //estados en los que se debe emitir fact o boleta:
        $estados_arr = Funciones::get_estados_dte_arr();

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($estados_arr, "Estados de pedidos permitidos para emitir dte  para el pedido id=$order_id, actualmen te en estado= '$status'");
        }
        //si el estado del pedido no está entre los permitidos, se retorna false ahora
        if( !in_array($status, $estados_arr) )
        {
            return false;
        }

        return true;
    }

    public function is_for_nota_venta($order)
    {
        $order_id = $order->get_id();
        //si ya se ha emitido nv, no se emite de nuevo o se duplicaría
        $bsale_docto_id_nv = (int) get_post_meta($order_id, 'bsale_docto_id_nv', true);

        //nv ya se ha creado, se omite
        if( $bsale_docto_id_nv > 0 )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("Ya se ha emitido nv para el pedido id=$order_id, actualmen te en estado= '$status'");
            }
            else
            {
                return false;
            }
        }

        //este pedido está en un estado para emtir nv?
        $modo_pago = $billing_paymethod = get_post_meta($order_id, '_payment_method', true);
        $status = $order->get_status();

        //estados en los que se debe emitir nv:
        $estados_arr = Funciones::get_estados_nv_arr();

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($estados_arr, "Estados de pedidos permitidos para emitir nv  para el pedido id=$order_id, actualmen te en estado= '$status'");
        }
        //si el estado del pedido no está entre los permitidos, se retorna false ahora
        if( !in_array($status, $estados_arr) )
        {
            return false;
        }

        //por ahora, todos los pedidos en los estados para emitir NV emiten ese documento
        //en versiones anteriores, se podía filtrar por medio de pago, pero nadie usaba esa función
        $payments_nv = Funciones::get_pagos_nv();
        //emito nv para todas las ventas en este estado?
        if( $payments_nv === 'all' )
        {
            return true;
        }

        if( !empty($payments_nv) )
        {
            $arr_pagos = explode(',', $payments_nv);
        }
        else
        {
            $arr_pagos = array();
        }


        foreach( $arr_pagos as $p )
        {
            $p = trim($p);
            $res = strcasecmp($modo_pago, $p);

            if( $res == 0 )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * recorre todos los items y devuelve total con y sin impuesto
     * @param type $datos_items
     * @return type
     */
    public function get_total_productos($datos_items, $iva)
    {
        if( !is_array($datos_items) )
        {
            return array( 'total_con_impuesto' => 0, 'total_sin_impuesto' => 0 );
        }

        $total_con_impuesto = 0;
        $total_sin_impuesto = 0;

        foreach( $datos_items as $item )
        {
            $cantidad = $item['quantity'];
            $precio_unitario = $item['netUnitValue'];
            $descto_porcent = isset($item['discount']) ? $item['discount'] : 0;
            $taxId = isset($item['taxId']) ? $item['taxId'] : null;
            $sku = isset($item['code']) ? $item['code'] : null;
            $comment = isset($item['comment']) ? $item['comment'] : null;

            if( $descto_porcent > 0 )
            {
                $precio_unitario -= ($precio_unitario * $descto_porcent / 100);
            }

            $total_item_sin_impuesto = $precio_unitario * $cantidad;

            if( !empty($taxId) )
            {
                $total_item_con_impuesto = $total_item_sin_impuesto * $iva;
            }
            else
            {
                $total_item_con_impuesto = $total_item_sin_impuesto;
            }

            $total_sin_impuesto += $total_item_sin_impuesto;
            $total_con_impuesto += $total_item_con_impuesto;

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " item sku='$sku' $comment precio sin impuesto: $ $total_item_sin_impuesto, precio CON impuesto: $ $total_item_con_impuesto");
            }
        }

        return array( 'total_con_impuesto' => $total_con_impuesto, 'total_sin_impuesto' => $total_sin_impuesto );
    }

    public function get_nota_cliente($order)
    {
        $utils = new Utils();

        $nota_cliente = $order->get_customer_note();
        $nota_cliente = $utils->filter_chars($nota_cliente);

        return $nota_cliente;
    }

    /**
     * devuelve referecnais a la orden de compra asociada a la factura solicitada
     * 
     * @param type $order
     */
    public function get_oc_references($order)
    {
        $order_id = $order->get_id();
        //en caso de que venga referencia para orden de compra (solo para facturas)
        $billing_oc_folio = get_post_meta($order_id, 'billing_oc_folio', true);
        $billing_oc_fecha = get_post_meta($order_id, 'billing_oc_fecha', true);
        $billing_oc_referencia = get_post_meta($order_id, 'billing_oc_referencia', true);

        $references = array();
        //si viene folio o referencia
        if( !empty($billing_oc_folio) || !empty($billing_oc_referencia) )
        {
            $billing_oc_folio = empty($billing_oc_folio) ? '000' : $billing_oc_folio;
            $billing_oc_referencia = empty($billing_oc_referencia) ? $billing_oc_folio : $billing_oc_referencia;
            $billing_oc_referencia .= "(fecha: $billing_oc_fecha)";
            $billing_oc_fecha = strtotime($billing_oc_fecha);

            $references[] = array(
                'number' => $billing_oc_folio,
                'referenceDate' => $billing_oc_fecha,
                'reason' => $billing_oc_referencia,
                'codeSii' => 801, //801=orden de compra
            );
        }

        return $references;
    }

    public function get_shipping_address($order)
    {
        //DIRECCION de despacho
        $shipping_address = $order->get_formatted_shipping_address();
        $shipping_address = str_replace('<br/>', '; ', $shipping_address);
        $shipping_address = str_replace('<br>', '; ', $shipping_address);

        return $shipping_address;
    }

    public function get_tax_class_exentos($order)
    {
        $is_include_prods_afectos_y_exentos = Funciones::is_include_prods_afectos_y_exentos();

        if( !$is_include_prods_afectos_y_exentos )
        {
            return null;
        }

        //por si solo hay que inlcuir prodcuto con tax class 

        $tax_class_exentos_arr = Funciones::get_tax_class_exentos_for_dte();

        //en caso de que no haya indicado tax clasee
        if( !is_array($tax_class_exentos_arr) || count($tax_class_exentos_arr) <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($tax_class_exentos_arr, "distinguir prods afectos de exentos es true, pero "
                        . "no se ha indicado tax class para prods exentos, se omite regla");
            }
            return null;
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($tax_class_exentos_arr, "Se distinguen prods afectos de exentos, al emitir dte exenta o afecta");
        }
        return $tax_class_exentos_arr;
    }

    public function get_tipo_docto($tipo_documento)
    {
        //asigno $tipo_docto
        switch( $tipo_documento )
        {
            case 'boleta':
                $tipo_docto = 'b';
                break;
            case 'factura':
                $tipo_docto = 'f';
                break;
            case 'nv':
                $tipo_docto = 'nv';
                break;
            default:
                //si no viene tipo doc, debo emitir boleta
                $tipo_docto = 'b';
                break;
        }
        return $tipo_docto;
    }

    public function get_tipo_documento($order)
    {
        $order_id = $order->get_id();

        //tipo docto que requiere el comprador: boleta, factura
        $tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);
        if( empty($tipo_documento) )
        {
            $tipo_documento = get_post_meta($order_id, 'billing_tipo_documento', true);
        }
        if( empty($tipo_documento) )
        {
            $tipo_documento = 'boleta';
        }
        $tipo_documento = strtolower($tipo_documento);

        return $tipo_documento;
    }

    /**
     * devuelve item shipping como producto par aincluir en la boleta
     * @param type $order
     */
    public function get_shipping_arr($order, $tipo_docto, $is_include_prods_afectos_exentos,
            $is_dte_afecto, $iva, $is_ml_order)
    {
        $skip_shipping = !Funciones::is_add_shipping_in_dte();
        $iva_shipping = ($is_include_prods_afectos_exentos && $is_dte_afecto == false) ? 1 : $iva;

        $total_envio = $order->get_shipping_total(); //sin tax
        $tax_envio = $order->get_shipping_tax();

        if( $tax_envio == 0 )
        {
            $tax_envio = $total_envio - ($total_envio / $iva);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " envio con $ 0 tax, coloco iva");
            }
        }
        else
        {
            $total_envio += $tax_envio;
        }


        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " total envio: $total_envio impuesto $tax_envio");
        }
        $neto_envio = $total_envio - $tax_envio;

        $shipping_name_orig = $order->get_shipping_method();

        $envio_arr = null;

        //si es un pedido por ml, no incluyo el envio en la boleta
        if( $is_ml_order )
        {
            return null;
        }

        //si viene envio y no debo omitirlo en la boleta
        if( $neto_envio > 0 && !$skip_shipping )
        {

            $envio_arr = array(
                'quantity' => 1, //added by l sd
                'netUnitValue' => $neto_envio,
                'discount' => 0,
                'comment' => $shipping_name_orig,
                'taxId' => "[" . IMPUESTO_IVA_ID . "]",
                'glossCost' => $neto_envio,
            );

            if( !Funciones::is_include_gloss_cost() )
            {
                unset($envio_arr['glossCost']);
            }

            //agrego sku de envío
            $envio_arr = $this->set_sku_envio($envio_arr, $tipo_docto);

            if( defined('SKIP_IMPUESTO_IVA_ID') && SKIP_IMPUESTO_IVA_ID == true )
            {
                unset($envio_arr['taxId']);
            }

            //dte afecto o exento, segun tax de productos
            if( $is_include_prods_afectos_exentos && $is_dte_afecto == false )
            {
                unset($envio_arr['taxId']);
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($envio_arr, __METHOD__ . "envio neto: $ $neto_envio + impuesto $ $tax_envio = total: $ $total_envio");
            }
        }
        //pedidos por mercado libre
        elseif( $neto_envio < 0 )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html(__METHOD__ . " pedido tiene neto envio negativo $ $neto_envio, viene de ML");
            }
        }

        return $envio_arr;
    }

    public function get_fees_arr($order, $is_include_prods_afectos_exentos, $is_dte_afecto, $iva,
            &$is_ml_order)
    {

        $fees_arr = $this->get_fees_order($order);

        //si algun fee es negativo, no se debe agregar al dte, sino descontar su valor de entre los productos
        $desctos_neto = 0;

        //agrego fees
        $iva_fee = ($is_include_prods_afectos_exentos && $is_dte_afecto == false) ? 1 : $iva;
        $is_ml_order = false;

        $datos_items = array();

        foreach( $fees_arr as $fee )
        {
            //solo fees positivos
            if( $fee['netUnitValue'] > 0 )
            {
                $datos_items[] = $fee;

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($fee, "agrego fee con iva de $" . $fee['netUnitValue'] * $iva_fee);
                }
            }
            else
            {
                $desctos_neto += $fee['netUnitValue']; // / $iva;
                $comment = $fee['comment'];

                $is_ml_order = stripos($comment, 'ML comisión de venta') !== false ? true : false;

                // $total_productos -= $fee['netUnitValue'] * $iva;
                //$total_productos += $fee['netUnitValue'] * $iva;
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($fee, "es pedido ml? : '$is_ml_order', fee negativo, agrego a desctos: " . $fee['netUnitValue']);
                }

                /* if( Funciones::is_descontar_iva_precios() )
                  {
                  //sumo solo el impuesto del fee
                  $impuesto_fee = $fee['netUnitValue'] - ($fee['netUnitValue'] * $iva_fee);
                  //$total_productos += $impuesto_fee;
                  } */
            }
        }

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($datos_items, __METHOD__ . "fees a colocar en dte");
        }

        return $datos_items;
    }

    /**
     * usado en el loop de items de la order, para obtener los key/values de los atributod elegidos por el comprador
     * antes de agregar el producto al carro
     * @param type $item_id
     */
    public function get_wapo_atributes($item_id, $product, $product_variation = null)
    {
        //asocio labels a datos
        $comment_wapo_txt = '';
        //atributos de la variacion (no de wapo)
        $vars_arr = array();

        if( !class_exists('YITH_WAPO_Premium') )
        {
            return '';
        }

        if( $product_variation )
        {
            // Get the variation attributes
            $variation_attributes = $product_variation->get_variation_attributes();
            // Loop through each selected attributes
            foreach( $variation_attributes as $attribute_taxonomy => $term_slug )
            {
                // Get product attribute name or taxonomy
                $taxonomy = str_replace('attribute_', '', $attribute_taxonomy);
                // The label name from the product attribute
                $attribute_name = wc_attribute_label($taxonomy, $product);
                // The term name (or value) from this attribute
                if( taxonomy_exists($taxonomy) )
                {
                    $attribute_value = get_term_by('slug', $term_slug, $taxonomy)->name;
                }
                else
                {
                    $attribute_value = $term_slug; // For custom product attributes
                }
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . "atributo '$attribute_name'=>'$attribute_value'");
                }

                $vars_arr[] = "$attribute_name: $attribute_value";
            }
        }


        $labels = array();
        $values_wapo = array();

        $wapo_meta_data_arr = wc_get_order_item_meta($item_id, '_ywapo_meta_data', true);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($wapo_meta_data_arr, __METHOD__ . "wc_get_order_item_meta( $item_id, '_ywapo_meta_data', true)");
        }

        $yapo_class = YITH_WAPO_Premium::get_instance();

        if( !$wapo_meta_data_arr || !is_array($wapo_meta_data_arr) )
        {
            return '';
        }
        foreach( $wapo_meta_data_arr as $md_id => $option )
        {
            /* echo('<p>wc_get_order_item_meta():</p><pre>');
              print_r($option);
              echo('</pre>'); */

            foreach( $option as $key => $value )
            {
                if( $key && '' !== $value )
                {
                    //Funciones::print_r_html("wapo key $key -> value $value");

                    $values = $yapo_class->split_addon_and_option_ids($key, $value);

                    $addon_id = $values['addon_id'];
                    $option_id = $values['option_id'];

                    $info = yith_wapo_get_option_info($addon_id, $option_id);
                    //$label = yith_wapo_get_option_label($addon_id, $option_id);
                    $label = $info['addon_label'];

                    //en el caso de los select, el valor selected está en $info['label']
                    $tipo = $info['addon_type'];
                    $value_info = $info['label'];

                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html($info, " yith_wapo_get_option_info( $addon_id, $option_id ) $label => $value_info");
                    }

                    if( $tipo === 'select' || $tipo === 'color' || $tipo === 'checkbox' )
                    {
                        $values_wapo[] = $value_info;
                    }
                    else
                    {
                        $values_wapo[] = $value;
                    }

                    $labels[] = $label;
                }
            }
        }
        if( isset($_REQUEST['param']) )
        {
            echo('<p>Labels:</p><pre>');
            print_r($labels);
            echo('</pre>');
            echo('<p>values:</p><pre>');
            print_r($values_wapo);
            echo('</pre>');
        }

        //asocio labels con values
        $arraux = array();

        $i = 0;
        foreach( $labels as $l )
        {
            $v = isset($values_wapo[$i]) ? $values_wapo[$i] : '';
            //en los titles, ambos valores son iguales, se omiten
            if( $l === $v )
            {
                $i++;
                continue;
            }

            //a veces el label viene en blanco.
            //a veces, el label viene duplicado y solo el último valor sirve
            if( !empty($l) )
            {
                $arraux[$l] = "$l: $v";
            }
            else
            {
                $arraux[$i] = "$l: $v";
            }

            $i++;
        }

        if( count($vars_arr) > 0 )
        {
            $comment_wapo_txt .= implode(', ', $vars_arr) . '. ';
        }
        $comment_wapo_txt .= implode(', ', $arraux);
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " wapo atributos: '$comment_wapo_txt'");
        }

        return $comment_wapo_txt;
    }

    /**
     * devuelve listado de fees (impuestos) de la order wc
     * @param type $order
     */
    public function get_fees_order($order)
    {
        $iva = Funciones::get_valor_iva();

        //WC_Order_item_Fee[]
        $fees_arr = $order->get_fees();
        // $iva = Funciones::get_valor_iva();

        $arraux = array();

        if( isset($_REQUEST['test_dte']) || isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " fees a recorrer");
        }

        if( !is_array($fees_arr) || count($fees_arr) <= 0 )
        {
            if( isset($_REQUEST['test_dte']) || isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . "order no tiene fees");
            }
            return $arraux;
        }

        foreach( $fees_arr as $fee )
        {
            $name = $fee->get_name();
            $tax = $fee->get_total_tax();
            $total = $fee->get_total();

            //no trae impuesto, entonces la tienda debe configurarse para impuestos detallados
            //y a este fee debo colocarle el impuesto a la fuerza
            if( $tax == 0 )
            {
                $tax = $total - ($total / $iva);

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " fee tiene impuesto cero, le coloc impuesto %$iva = $ $tax");
                }
            }
            $total += $tax;

            /* if( Funciones::is_descontar_iva_precios() )
              {
              $neto = $total - $tax;
              }
              else
              {
              $neto = $total;
              } */

            $neto = $total - $tax;

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html(__METHOD__ . " loop: nombre fee: '$name' neto: $ $neto + impuesto $ $tax = total: $ $total");
            }

            $arraux[] = array(
                'quantity' => 1, //added by l sd
                'netUnitValue' => $neto,
                'discount' => 0,
                'comment' => $name,
                'taxId' => "[" . IMPUESTO_IVA_ID . "]",
                    //'glossCost' => $neto_envio,
            );
        }

        return $arraux;
    }

    /**
     * devuevel array con datos de cliente según el formato de Bsale 
     * @param type $order
     */
    public function get_customer_arr($order)
    {
        $order_id = $order->get_id();
        $order_number = $order->get_order_number();

        $billing_first_name = get_post_meta($order_id, '_billing_first_name', true);
        $billing_last_name = get_post_meta($order_id, '_billing_last_name', true);
        $billing_company = get_post_meta($order_id, '_billing_company', true);
        //en caso de que no se pueda usar 
        if( empty($billing_company) )
        {
            $billing_company = get_post_meta($order_id, '_billing_company2', true);
        }

        $billing_address = get_post_meta($order_id, '_billing_address_1', true);
        $billing_address2 = get_post_meta($order_id, '_billing_address_2', true);

        $billing_city = get_post_meta($order_id, '_billing_city', true);

        $billing_comuna = get_post_meta($order_id, '_billing_state', true);
        $billing_comuna_value = $billing_comuna;

        $billing_country = get_post_meta($order_id, '_billing_country', true);
        //tipo docto que requiere el comprador: boleta, factura
        $tipo_documento = $this->get_tipo_documento($order);

        $tipo_docto = $this->get_tipo_docto($tipo_documento);

        //giro, en  caso de factura
        $billing_giro = get_post_meta($order_id, '_billing_giro', true);
        $billing_giro = empty($billing_giro) ? strtolower(get_post_meta($order_id, 'billing_giro', true)) : $billing_giro;

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("city: $billing_city, comuna $billing_comuna");
        }
        $pais = Funciones::get_pais();
        //solo facturacion a pais actual
        if( !empty($billing_country) && $billing_country !== $pais )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("pais no es '$pais', sino '$billing_country', no se emite dte");
            }
            $note = "Pedido extranjero, de país '$billing_country'. No se emite $tipo_documento";
            update_post_meta($order_id, 'bsale_docto_error', $note);
            // Add the note
            $order->add_order_note($note);
            return false;
        }

        $wc_data = new WpDataBsale();
        $utils = new Utils();

        //billing comuna value
        $comuna_value = $wc_data->get_comuna_value($billing_comuna, $billing_country);

        if( empty($comuna_value) )
        {
            $comuna_value = $wc_data->get_comuna_value_checkout_field($order_id, 'comuna');
        }

        if( !empty($comuna_value) )
        {
            $billing_comuna = $comuna_value;
        }

        if( empty($billing_city) )
        {
            $billing_city = $billing_comuna;
        }
        if( empty($billing_comuna) )
        {
            $billing_comuna = $billing_city;
        }

        $billing_email = get_post_meta($order_id, '_billing_email', true);
        $billing_phone = get_post_meta($order_id, '_billing_phone', true);

        //rut del comprador
        $billing_rut = get_post_meta($order_id, '_billing_rut', true);

        if( empty($billing_rut) )
        {
            $billing_rut = strtolower(get_post_meta($order_id, 'billing_rut', true));
        }
        if( $billing_rut === RUT_SIN_RUT )
        {
            $billing_rut = '';
        }

        //direccion
        $direccion = $utils->filter_chars("$billing_address $billing_address2");
        $billing_city = $utils->filter_chars($billing_city);
        $billing_giro = $utils->filter_chars($billing_giro);
        $billing_first_name = $utils->filter_chars($billing_first_name);
        $billing_last_name = $utils->filter_chars($billing_last_name);

        //shipping. Hay wc integrados con mercado libre. En los pedidos de ML, el costo de envío es menor a cero
        //en estos casos, el rut del comprador viene en el campo billing_company
        $neto_envio = $order->get_shipping_total(); //$order->get_total_shipping();
        //ML, rut viene en campo billing company
        if( $neto_envio < 0 )
        {
            $billing_rut = get_post_meta($order_id, '_billing_company', true);
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("pedido con envio menor a cero (maybe proviene de Mercado Libre), saco rut de billing company: $billing_rut");
            }
        }

        //default, el comprador es persona natural
        $companyOrPerson = 0;

        if( Funciones::get_pais() === 'PE' && empty($billing_rut) )
        {
            $billing_rut = get_post_meta($order_id, '_billing_dni', true);
        }
        //peru, facturas exigen RUC y DF
        if( Funciones::get_pais() === 'PE' && $tipo_documento === 'factura' )
        {
            $companyOrPerson = 1; //persona jurídica
            //ruc empresa
            $billing_RUC = get_post_meta($order_id, '_billing_ruc', true);
            $billing_rut = !empty($billing_RUC) ? $billing_RUC : '';

            //direccion fiscal empresa
            $billing_df = get_post_meta($order_id, '_billing_direccion_fiscal', true);
            $direccion = !empty($billing_df) ? $billing_df : '';

            if( empty($billing_RUC) || empty($direccion) )
            {
                $error_msg = "Falta RUC o dirección fiscal";
                $str_note = "Factura ERROR: $error_msg";

                update_post_meta($order_id, 'bsale_docto_error', $str_note);
                //update_post_meta($order_id, 'bsale_docto_tipo', $str_note);

                $note = $str_note;
                // Add the note
                $order->add_order_note($note);

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("ERROR pedido $order_number para pais " . Funciones::get_pais() . ": $error_msg");
                }
                return false;
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html(__METHOD__ . " Perú, se requiere factura: RUC = $billing_RUC, empresa= $companyOrPerson, "
                        . "tipo_docto='$tipo_docto', tipo_documento_original='$tipo_documento'");
            }
        }

        $datos_cliente = array(
            //'direccion' => $direccion,
            'code' => $billing_rut,
            'city' => $billing_comuna,
            'company' => $billing_company,
            'municipality' => substr($billing_city, 0, 30), //$billing_city, //comuna
            'activity' => $billing_giro,
            'address' => $direccion,
            'email' => $billing_email,
            'phone' => $billing_phone,
            'firstName' => $billing_first_name,
            'lastName' => $billing_last_name,
            'companyOrPerson' => $companyOrPerson, //si pidió factura, se cambia a 1                
        );

        //perú exige distrito
        if( Funciones::get_pais() === 'PE' )
        {
            $datos_cliente['district'] = $billing_comuna;
        }

        //formateo segun si es boleta o factura
        if( $tipo_documento === 'factura' )
        {
            $datos_cliente['companyOrPerson'] = 1;
        }
        else
        {
            $datos_cliente['companyOrPerson'] = 0;
            unset($datos_cliente['company']);
        }

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($datos_cliente, __METHOD__ . " datos cliente, antes de filter cliente:");
        }
        //valido rut y otros        
        $datos_cliente_filtrado = $utils->filter_client_bsale($datos_cliente);

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($datos_cliente_filtrado, __METHOD__ . " datos cliente filtrados:");
        }
        return $datos_cliente_filtrado;
    }

    /**
     * crea nv, boleta o factura en bsale, de acuerdo al estado en que esté el pedido wc
     * @param type $order_id
     * @return boolean
     */
    public static function crear_dte_bsale($order_id)
    {
        $utils = new Utils();
        $me = new WpBsale();

        //objeto order
        $order = wc_get_order($order_id);

        if( empty($order) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " error: $order_id es null, return.");
            }
            return null;
        }


        if( isset($_REQUEST['param']) )
        {
            //itemized = cada prod con el impuesto (recomendado)
            //single = un item "iva" en el subtotal
            //solo para depuracion
            $woocommerce_tax_total_display = get_option('woocommerce_tax_total_display');
            $is_tax_enabled = wc_tax_enabled();
            Funciones::print_r_html(__METHOD__ . "get option woocommerce_tax_total_display : '$woocommerce_tax_total_display', is tax enabled? '$is_tax_enabled'");
        }
        //fin solo para depuracion

        $order_number = $order->get_order_number();

        //INCLUIR DAtos de webpay en dtes?
        $incluir_webpay = defined('WC_INCLUIR_WEBPAY_PAGO') ? WC_INCLUIR_WEBPAY_PAGO : false;

        if( $incluir_webpay )
        {
            $webpay_transaction_id = get_post_meta($order_id, 'webpay_transaction_id', true);
            $cardNumber = get_post_meta($order_id, 'cardNumber', true);

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("debo incluir datos para webpay. Transacc id: '$webpay_transaction_id', tarjeta: '$cardNumber'");
            }
        }
        else
        {
            $webpay_transaction_id = $cardNumber = null;
        }

        //tipo docto que requiere el comprador: boleta, factura
        $tipo_documento = $me->get_tipo_documento($order);

        //$tipo_documento puede cambiar a 'nv' según el estado en que esté el pedido
        //por eso guardo el tipo doc original
        $tipo_documento_original = $tipo_documento;

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("billing_tipo_documento para order $order_number: '$tipo_documento'");
        }

        //dirección de despacho
        $shipping_address = $me->get_shipping_address($order);

        //nota que el comprador dejó en el checkout
        $nota_cliente = $me->get_nota_cliente($order);

        //medio de pago
        $billing_pago = $order->get_payment_method();

        //por si debo incluir prods afectos y exentos en las boletas, debo detectar cuál es cuál
        //aquí obtengo el listado de clases de productos exentos
        $tax_class_exentos_arr = $me->get_tax_class_exentos($order);
        $is_include_prods_afectos_exentos = is_array($tax_class_exentos_arr) && count($tax_class_exentos_arr) > 0;

        //shipping
        $neto_envio = $order->get_shipping_total(); //$order->get_total_shipping();
        //
        //impuestos
        $iva = Funciones::get_valor_iva();

        $orden_impuestos = $order->get_total_tax();
        $order_total = $order->get_total(); //Gets order total.

        if( isset($_REQUEST['param']) || isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html(__METHOD__ . " impuesto del total del pedido: $ $orden_impuestos. Total del pedido $ $order_total");
            Funciones::print_r_html(__METHOD__ . " costo de envio: order->get_shipping_total() $ $neto_envio");
        }

        if( $orden_impuestos == 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " order impuesto viene en $ 0, coloco iva: $ $orden_impuestos");
            }
            $orden_impuestos = $order_total - ($order_total / $iva);
        }

        $order_neto = $order_total - $orden_impuestos;

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("wp_>crea dte bsale total: $ $order_total - impuestos: $ $orden_impuestos = order neto $ $order_neto");
        }

        $pais = Funciones::get_pais();

        //segun el estado, determino si debo emitir boleta o nv
        if( $me->is_for_nota_venta($order) )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("genero nv, para pedido $order_number");
            }
            //cambio el tipo docto. En variable $tipo_documento_original está el tipo doc solicitado por el cliente
            $tipo_documento = 'nv';
        }

        //si es orden valida para emitir dte en el estado en que se ecuentra?
        if( !$me->is_valid_for_dte($order, $tipo_documento) )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html(__METHOD__ . " order $order_number no es válida para emitir dte, return.");
            }
            /* $note = "Este pedido no genera '$tipo_documento' en estado '$estado_orden'.";
              update_post_meta($order_id, 'bsale_docto_error', $note);
              // Add the note
              $order->add_order_note($note); */
            //Funciones::print_r_html("$order_id en estado= {$order->get_status()} no puede generar dte");
            return false;
        }

        //si es cero, etonces no se puede emitir bol o fact
        if( $order_neto <= 0 && $tipo_documento !== 'nv' )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("wp_>crea dte bsale order neto: $ $order_neto es cero, no se crea dte");
            }
            $note = "El total del pedido es cero ($ $order_neto), no se genera $tipo_documento_original";
            update_post_meta($order_id, 'bsale_docto_error', $note);
            // Add the note
            $order->add_order_note($note);
            return false;
        }

        //ahora, ¿se puede emitir dte: bol o fact, para este pedido?
        //si ya se ha emitido, no se vuelve a emitir
        if( !$me->is_for_dte($order, $tipo_documento) )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("pedido $order_number no emite DTE (excepto en modo de pruebas)");
            }
            //si no es de test, retorno
            if( !isset($_REQUEST['test_dte']) )
            {
                return false;
            }
        }

        //b= boleta, f= factura, devuelve la abreviación
        $tipo_docto = $me->get_tipo_docto($tipo_documento);

        //datos del cliente, según el formato de bsale
        $datos_cliente = $me->get_customer_arr($order);

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($datos_cliente, "datos cliente, despues de filter cliente:");
        }


        $shipping_name_orig = $order->get_shipping_method();
        $billing_comuna = $datos_cliente['municipality'];

        //donde generar el dte
        $sucursal_bsale_to_send = $me->get_sucursal_bsale_to_send($order, $billing_comuna);

        //solo para agregar peso de productos a la boleta/factura
        $peso_total = 0;
        //será true si no tgodos los prods del pedido están incluídos en el dte
        $dte_parcial = false;

        //cambiará si es que se debe emitir dte exento
        if( $is_include_prods_afectos_exentos && $pais === 'CL' )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " DTES pueden incluir prods afectos y exentos. Chile, dte exento por default");
            }
            $is_dte_afecto = false;
        }
        else
        {
            //en peru siempre se emite bol afecta, aunque tenga solo prods exentos
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " DTES pueden incluir prods afectos y exentos. Perú, dte afecto por default");
            }
            $is_dte_afecto = true;
        }

        //items (producto de la orden)
        $datos_items = $me->get_items($order, $is_include_prods_afectos_exentos, $tax_class_exentos_arr,
                $sucursal_bsale_to_send, $is_dte_afecto, $peso_total);

        $is_ml_order = false;
        //agrego fees
        $fees_items = $me->get_fees_arr($order, $is_include_prods_afectos_exentos, $is_dte_afecto, $iva,
                $is_ml_order);

        if( is_array($datos_items) )
        {
            $datos_items = array_merge($datos_items, $fees_items);
        }
        else
        {
            $datos_items = $fees_items;
        }


        //agrego envío
        $envio_arr = $me->get_shipping_arr($order, $tipo_docto, $is_include_prods_afectos_exentos, $is_dte_afecto,
                $iva, $is_ml_order);

        if( !empty($envio_arr) )
        {
            $datos_items[] = $envio_arr;
        }


        $bsale_dte = new BsaleDTE();

        //totales antes de repartir desctos
        $totales_arr = $me->get_total_productos($datos_items, $iva);
        $total_sin_impuesto = $totales_arr['total_sin_impuesto'];

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($totales_arr, "WpBsale->crear_dte_bsale, totales ANTES de repartir desctos: "
                    . " total calculado SIn impuesto: $ $total_sin_impuesto. Total order SIn impuesto: $order_neto");
        }

        //veo si hay desctos o ono
        if( (int) $total_sin_impuesto != (int) $order_neto )
        {
            $total_descto_pesos = $total_sin_impuesto - $order_neto;

            if( $total_descto_pesos > 0 )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($datos_items, __METHOD__ . " prods antes de descto hay desctos total"
                            . " $total_sin_impuesto != neto $order_neto = $ $total_descto_pesos a descontar:");
                }

                $datos_items = self::descontar_de_productos2($datos_items, $total_descto_pesos);

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($datos_items, "wp_>crea dte bsale:prods DESPUES de descto, despues de ajuste de desctos $ $total_descto_pesos, orden: $order_neto ");
                }
            }
            else
            {

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($datos_items, __METHOD__ . " total descto sin impuesto es menor a cero: $ $total_descto_pesos");
                }
            }
        }

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("Voy a generar docto $tipo_documento");
        }

        //solo genero boleta si hay productos. Como es posible que se hayan omitido productos
        //entonces, debo asegurarme
        if( count($datos_items) <= 0 )
        {
            $str_note = "Pedido #$order_number no emite $tipo_documento, pues no tiene productos";

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($str_note);
            }

            update_post_meta($order_id, 'bsale_docto_error', $str_note); //limpio error

            $note = $str_note;
            // Add the note
            $order->add_order_note($note);

            return false;
        }

        $old_timezone = date_default_timezone_get();

        if( Funciones::get_pais() === 'PE' )
        {
            $timezone = 'America/Lima';
        }
        else
        {
            $timezone = 'America/Santiago';
        }

        date_default_timezone_set($timezone);
        ini_set("date.timezone", $timezone);

        $hoy = date('Y-m-d');
        $gmt_date = strtotime($hoy);
        $gmt_date_expiracion = $gmt_date;
        $hoy_from_timestamp = date('Y-m-d', $gmt_date);

        //referencias a oc, solo factura
        $references = $me->get_oc_references($order);

        date_default_timezone_set($old_timezone);
        ini_set("date.timezone", $old_timezone);

        //después de desctos, para ver lo de mercado libre
        $totales_arr = $me->get_total_productos($datos_items, $iva);
        $total_sin_impuesto = $totales_arr['total_sin_impuesto'];
        $total_con_impuesto = $totales_arr['total_con_impuesto'];

        //mercado libre, en el pedido se coloca el subtotal
        if( $neto_envio < 0 || $is_ml_order )
        {
            $total_productos_ml = $total_con_impuesto;
        }
        else
        {
            $total_productos_ml = 0;
        }

        $modo_pago_arr = $utils->get_modo_pago_wp($order_id, $billing_pago, $gmt_date, $tipo_docto, $total_productos_ml);

        $tienda_nombre = '(WC)';

        $peso_total = $peso_total > 0 ? "$peso_total kg." : $peso_total;

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("peso total productos de ls order $order_number: $peso_total");
        }

        $arr_datos = array(
            'tipo_docto' => $tipo_docto,
            'tienda_nombre' => $tienda_nombre,
            'order_number' => $order_number,
            'order_id' => $order_id,
            'gmt_date' => $gmt_date,
            'gmt_date_expiracion' => $gmt_date_expiracion,
            'array_cliente' => $datos_cliente,
            'productos_arr' => $datos_items,
            'modo_pago_arr' => $modo_pago_arr,
            'fecha_hoy' => $hoy,
            'timestamp' => $gmt_date,
            'fecha_from_timestamp' => $hoy_from_timestamp,
            'sucursal_to_send' => $sucursal_bsale_to_send,
            'references' => $references,
            'peso_total' => $peso_total,
            'shipping_address' => $shipping_address,
            'shipping_name' => $shipping_name_orig, //$shipping_name,
            'customer_note' => $nota_cliente,
            'webpay_transaction_id' => $webpay_transaction_id,
            'cardNumber' => $cardNumber,
            'dte_afecto' => isset($is_dte_afecto) ? $is_dte_afecto : true,
            'seller_id' => Funciones::get_seller_id(),
            'neto' => $order_neto,
        );

        if( isset($_REQUEST['test_dte']) )
        {

            Funciones::print_r_html($arr_datos, "WpBsale->crear_dte_bsale, datos a enviar en TEST (no se emitirá $tipo_documento):");
        }

        $result = $bsale_dte->enviar_dte_a_bsale($arr_datos);

        if( isset($_REQUEST['test_dte']) )
        {
            $totales_arr = $bsale_dte->get_totales($datos_items);
            Funciones::print_r_html($totales_arr, "WpBsale->crear_dte_bsale, totales listos para enviar:");

            return;
        }

        $tipo_docto_nombre = $utils->get_tipo_docto_nombre($tipo_docto);

        //si hay algo en el meta,agrego lo de ahora
        $url_docto_anterior = get_post_meta($order_id, 'bsale_docto_url', true);

        //msge de exito
        if( isset($result['urlPublicView']) )
        {
            $str_note = "ver <a href='{$result['urlPublicView']}' target='_blank'>"
                    . "$tipo_docto_nombre #{$result['number']}</a>";

            update_post_meta($order_id, 'bsale_docto_folio', $result['number']);
            update_post_meta($order_id, 'bsale_docto_url', $result['urlPublicView'] . ' ' . $url_docto_anterior);
            update_post_meta($order_id, 'bsale_docto_tipo', $tipo_documento);
            //así puedo obtener el id de la nv asociada a esta orden

            update_post_meta($order_id, "bsale_docto_id_$tipo_documento", $result['id']); //id doc en Bsale
            update_post_meta($order_id, "bsale_docto_id_{$tipo_documento}_url", $result['urlPublicView']); //url
            update_post_meta($order_id, "bsale_docto_folio_$tipo_documento", $result['number']); //folio
            update_post_meta($order_id, 'bsale_docto_error', ''); //limpio error

            if( $dte_parcial )
            {
                update_post_meta($order_id, 'bsale_docto_error', "$tipo_docto parcial #{$result['number']}"); //limpio error
            }

            $note = $str_note;
            // Add the note
            $order->add_order_note($note);
        }
        else
        {
            $error_msg = isset($result['error']) ? $result['error'] : print_r($result, true);
            $str_note = "$tipo_docto_nombre ERROR: $error_msg";

            update_post_meta($order_id, 'bsale_docto_error', $str_note);
            //update_post_meta($order_id, 'bsale_docto_tipo', $str_note);

            $note = $str_note;
            // Add the note
            $order->add_order_note($note);
        }
        //borro logs antiguos
        $utils->delete_old_logs();
        return $result;
    }

    /**
     * devuelve array con items (productos) del pedido, según el formato de bsale
     * @param type $order
     */
    public function get_items($order, $is_include_prods_afectos_exentos, $tax_class_exentos_arr, $sucursal_bsale_to_send,
            &$is_dte_afecto, &$peso_total)
    {
        $datos_items = array();

        //debo colocar en boleta precio del producto en el pedido, o el precio original y el descto?
        //(en caso de que el producto haya sido comprado con descto)
        $is_set_descto_precio_producto = Funciones::is_set_descuento_precios_en_dtes();

        $wc_data = new WpDataBsale();
        $iva = Funciones::get_valor_iva();

        $bundles = new BsaleWPCProductBundles();
        //limpio array de prod packs
        $bundles->clear_prod_pack_ids();

        //listado productos comprados
        $items = $order->get_items();

        foreach( $items as $item_id => $item )
        {
            //iva puede ser modificado dentro del bucle, para los productos exentos y afectos
            $iva_prod = $iva;

            //$item_meta = $item['item_meta'];
            $cantidad = (int) $item['qty'];

            //default, solo aplica si $is_only_prods_with_tax_class = true
            //en caso contrario, se emite boleta afecta o exenta según constantes
            $is_prod_afecto = true;

            //peso para este producto
            $weight = 0;

            //compatibilidad con distintas vewrsiones de WC
            if( is_a($item, 'WC_Order_Item_Product') )
            {
                $product_id = $item->get_product_id();
                $product_variation_id = $item->get_variation_id();
                //Funciones::print_r_html("item es 'WC_Order_Item_Product':$product_id, $product_variation_id");
            }
            else
            {
                $product_id = $item['product_id'];
                $product_variation_id = $item['variation_id'];
            }

            //obtengo precio producto
            $total_precio_item = $item->get_total(); // Total without tax (discounted)
            $total_tax_precio_item = $item->get_total_tax(); // Total tax (discounted)
            //agrego iva
            if( $total_tax_precio_item == 0 )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " impuesto cero, coloco iva = $ $total_tax_precio_item");
                }
                $total_tax_precio_item = $total_precio_item - ($total_precio_item / $iva);
            }
            else
            {
                //le agrego el impuesto
                $total_precio_item += $total_tax_precio_item;
            }

            $neto_precio_item = $total_precio_item - $total_tax_precio_item;

            if( isset($_REQUEST['test_dte']) )
            {

                Funciones::print_r_html(__METHOD__ . "Item, neto: $ $neto_precio_item + impuesto: $total_tax_precio_item = $ $total_precio_item con impuesto");
            }

            //en cvaso de que el producto no exista
            $product = $item->get_product(); //wc_get_product($product_id);
            //$product = $item->get_product();
            if( !$product )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($item, __METHOD__ . " recorro items. Producto no encontrado. Solo se usarán los datos que tenga el pedido  para este producto");
                }
                $product_original_price = $total_precio_item;
                $sku = null;
                //peso
                $auxpeso = 0;
                $weight = 0;
            }
            else
            {
                $product_original_price = $product->get_regular_price();
                $sku = $product->get_sku();
                //peso
                $auxpeso = (float) $product->get_weight();
                $weight = $auxpeso * $cantidad;
            }

            if( $is_include_prods_afectos_exentos && $product )
            {
                $tax_class_product = $product->get_tax_class();

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " se incluyen prods afectos y exentos. Loop. Tax class producto: '$tax_class_product'");
                }

                if( is_array($tax_class_exentos_arr) && in_array($tax_class_product, $tax_class_exentos_arr) )
                {
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html(__METHOD__ . " producto con tax class '$tax_class_product' EXENTO");
                    }
                    // $is_dte_afecto = false;
                    $is_prod_afecto = false;
                    $iva_prod = 1; //iva es 1, es decir, sin impuesto
                }
                else
                {
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html(__METHOD__ . " producto con tax class '$tax_class_product' AFECTO");
                    }
                    $is_dte_afecto = true;
                }
            }

            $discount = 0; //porcentaje de descto, solo cambiará si precio es $0 en la orden

            $product_variation = null;
            $variation_name = null;

            // Check if product has variation, para extraer el sku desde allí
            if( $product_variation_id )
            {
                $product_variation = new WC_Product_Variation($product_variation_id);

                if( $product_variation )
                {
                    $variation_name = $product_variation->get_name(); //current($product_variation->get_variation_attributes());
                    $skuvar = $product_variation->get_sku();

                    $sku = !empty($skuvar) ? $skuvar : $sku;

                    $product_original_price = $product_variation->get_regular_price();
                    //peso variacion
                    // $weight = $product_variation->get_weight() * $cantidad;
                    $auxpeso = (float) $product_variation->get_weight();
                    $weight = $auxpeso * $cantidad;
                }
                else
                {
                    $variation_name = null;
                }
            }

            $comment_wapo_txt = $this->get_wapo_atributes($item_id, $product, $product_variation);

            $nombre = $item->get_name();

            $skip_prod_parte_de_pack = $bundles->is_product_from_pack_to_skip($product_id, $item);

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html(__METHOD__ . "Loop: sku='$sku': nombre '$nombre'");
            }

            //no se pueden colocar items con cantidad 0, porque no emiten impuesto ni pueden descontar inv
            if( $cantidad <= 0 )
            {
                continue;
            }

            //producto viene sin precio, busco precio original y lo dejo con descto de 100%
            if( $total_precio_item <= 0 /* && $tipo_docto !== 'nv' */ )
            {
                if( $skip_prod_parte_de_pack )
                {
                    if( isset($_REQUEST['test_dte']) )
                    {
                        Funciones::print_r_html("producto $product_id pertener a pack 'crea tu...' y tiene precio $0, se omite de la boleta");
                    }
                    continue;
                }

                $discount = 100; //porcentaje
                //en caso de que este prod no tuviese precio en el producto, lo dejo en un default para
                //incluirlo en la orden
                $product_original_price = ($product_original_price > 0) ? $product_original_price : 9999;

                //este producto va con 100% de descto, busco precio original
                $total_precio_item = $product_original_price * $cantidad;

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("producto precio $0 en la orden, "
                            . "busco precio regular: $ $$product_original_price, descto de % $discount :");
                }
            }

            //si debo mostrar el descto en base al precio regular del producto
            if( $is_set_descto_precio_producto && $product_original_price > 0 )
            {
                $precio_totalaux = $total_precio_item;
                //precio unitario del pedido
                $precio_netoaux = $precio_totalaux / $cantidad;

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("set descto en base a precio regular, precio unitario regular: $ $$product_original_price, "
                            . "precio uitario order: $ $precio_netoaux ");
                }

                //solo si precio regular es mayor a precio de orden
                if( $product_original_price > $precio_netoaux )
                {
                    //caclulo descto
                    $percent = $precio_netoaux * 100 / $product_original_price;
                    $discount = 100 - $percent;

                    if( $discount > 0 )
                    {
                        //set precio original como precio de item en la orden
                        $total_precio_item = $product_original_price * $cantidad;
                    }
                    else
                    {
                        $discount = 0;
                    }


                    if( isset($_REQUEST['test_dte']) )
                    {
                        Funciones::print_r_html("set descto en base a precio regular, "
                                . "precio unitario regular: $ $$product_original_price, precio uitario order: $ $precio_netoaux, "
                                . "descto $discount");
                    }
                }
            }//fin set discount

            $precio_total = $total_precio_item;

            //precio que se pagó al comprar, necesario para sumar los totales
            if( $discount > 0 )
            {
                $precio_total_descto = $precio_total - ($precio_total * $discount / 100);
                $precio_total_descto = $precio_total_descto < 0 ? 0 : $precio_total_descto;
            }
            else
            {
                $precio_total_descto = $precio_total;
            }

            $precio_neto = ($precio_total / $cantidad) / $iva_prod;

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("precios: neto $ $precio_neto = ( precio total $ $precio_total / cantidad $cantidad )");
            }

            //ultima validacion de precio
            $precio_neto = $precio_neto < 0 ? 0 : $precio_neto;

            $arrauxitems = array(
                'quantity' => $cantidad,
                'netUnitValue' => $precio_neto,
                'taxId' => "[" . IMPUESTO_IVA_ID . "]",
                'discount' => $discount,
                'comment' => $nombre . $comment_wapo_txt );

            //si envio el sku/barcode a bsale
            if( Funciones::is_send_sku() == true )
            {
                if( !empty($sku) )
                {
                    $arrauxitems['code'] = (string) $sku;
                    unset($arrauxitems['comment']);

                    //mantengo el comentario con los attribs de wapo del item
                    if( !empty($comment_wapo_txt) )
                    {
                        $arrauxitems['comment'] = $comment_wapo_txt;
                    }
                }


                //Transferencia a título gratuíto, solo Perú
                if( Funciones::get_pais() === 'PE' && $precio_neto <= 0 )
                {
                    $arrauxitems['comment'] = 'Transferencia a título gratuíto';
                }

                //se incluyen todos los productos. Si uno de los skus no existe en bsale, el producto se agrega como glosa en la boleta
                $check_sku_exists_in_bsale = Funciones::get_value('CHECK_SKU_EXISTS_IN_BSALE', false);

                if( $check_sku_exists_in_bsale )
                {
                    $stock_bsale = $wc_data->get_product_stock($sku, $sucursal_bsale_to_send);
                }
                else
                {
                    $stock_bsale = 1;
                }

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html(__METHOD__ . " IMPORTANTE! DESCOMENTAR wc_data->get_product_stock($sku, $sucursal_bsale_to_send)!!");
                }
                //sku no existe en bsale
                if( $stock_bsale == BSALE_STOCK_SKU_NOT_EXISTS )
                {
                    if( isset($_REQUEST['test_dte']) )
                    {
                        Funciones::print_r_html("sku='$sku' no existe en bsale, se coloca en la boleta como glosa");
                    }
                    //agrego producto como glosa dentro de la boleta
                    $arrauxitems['comment'] = $nombre;
                    unset($arrauxitems['code']);
                }
            }
            if( defined('SKIP_IMPUESTO_IVA_ID') && SKIP_IMPUESTO_IVA_ID == true )
            {
                unset($arrauxitems['taxId']);
            }
            //dte afecto o exento, segun tax de productos
            if( $is_include_prods_afectos_exentos && $is_prod_afecto == false )
            {
                unset($arrauxitems['taxId']);

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("producto exento, se quita el impuesto");
                }
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($arrauxitems, "wp_>crea dte bsale:item:");
            }

            $datos_items[] = $arrauxitems;

            //sumo peso 
            $peso_total += $weight;

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("peso producto sku='$sku': $weight");
            }
        }//fin foreach

        return $datos_items;
    }

    /**
     * coloca el sku del envio, en caso de que sea necesario
     * @param type $arraux
     * @param type $tipo_docto
     */
    public function set_sku_envio($arraux, $tipo_docto)
    {
        $sku_envio = Funciones::get_sku_envio();
        //sku general
        if( !empty($sku_envio) )
        {
            $arraux['code'] = (string) $sku_envio;
            //unset($arraux['comment']);
        }
        return $arraux;
    }

    /**
     * devuelve el id de la sucursal de bsale donde se debe emitir el dte.
     * Es la sucucrsal principal, a menos que se deba emitir dte según la comuna de facturación
     * //xxx revisar si esta activo lo de emitir dte por comuna de fact
     * @param type $billing_comuna
     */
    public function get_sucursal_bsale_to_send($order, $billing_comuna)
    {
        $wc_data = new WpDataBsale();
        $utils = new Utils();
        $order_id = $order->get_id();

        $shipping_arr = $utils->get_array_medios_envio();
        $shipping_name_orig = $order->get_shipping_method();
        $shipping_name = strtolower($utils->filter_chars($shipping_name_orig));

        //por default, es la indicada en la config
        $sucursal_bsale_to_send = Funciones::get_matriz_bsale();

        //debo emitir dte segun comuna de fact? $shipping_comuna es comuna de shipping
        //actualmente, disabled en config
        $sucursal_comuna_id = $wc_data->get_sucursal_comuna_to_send($billing_comuna);
        if( $sucursal_comuna_id > 0 )
        {
            $sucursal_bsale_to_send = $sucursal_comuna_id;
        }

        //tiene retiro en tienda con tienda asociada?
        //solo sirve conm plugin _shipping_pickup_stores
        $sucursal_para_retiro_tienda = $utils->get_sucursal_retiro_tienda($order_id, $shipping_name);

        if( $sucursal_para_retiro_tienda == 9999 )
        {
            $sucursal_bsale_to_send = $sucursal_para_retiro_tienda;
        }
        elseif( isset($shipping_arr[$sucursal_para_retiro_tienda]) )
        {
            $sucursal_bsale_to_send = $shipping_arr[$sucursal_para_retiro_tienda];
        }

        //veo si este medio de envio está asociado a una sucursal de bsale, para generar la boleta en esa sucursal     
        //esta configuración sobreescribe la anterior
        if( isset($shipping_arr[$shipping_name]) )
        {
            $sucursal_bsale_to_send = $shipping_arr[$shipping_name];
        }

        return $sucursal_bsale_to_send;
    }

    /**
     * 29-06-2019, para usar con nuevas funci8o9nes de dte
     * @param type $datos_items
     * @param type $total_descto_pesos
     * @return type
     */
    public static function descontar_de_productos2($datos_items, $total_descto_pesos)
    {
        if( $total_descto_pesos <= 0 )
        {
            return $datos_items;
        }

        //parto descontando por el precio de envio
        $datos_items_reverse = array_reverse($datos_items);

        $arraux = array();
        foreach( $datos_items_reverse as $item )
        {
            //si ya he aplicado todos el descto o el prod ya tien descto de 100
            if( $item['discount'] >= 100 || $total_descto_pesos <= 0 )
            {
                //Transferencia a título gratuíto, descto 100%, solo Perú
                if( Funciones::get_pais() === 'PE' && $item['discount'] >= 100 )
                {
                    //$item['comment'] = 'Transferencia a título gratuíto';
                }
                $arraux[] = $item;
                continue;
            }

            $cantidad = $item['quantity'];
            $precio_unitario = $item['netUnitValue'];
            //total de este producto
            $total_producto = $cantidad * $precio_unitario;

            if( $total_descto_pesos >= $total_producto )
            {
                //dejo todos estos prodcutos a $0
                $valor_descto = $total_producto /* - $cantidad */;
            }
            else
            {
                $valor_descto = $total_descto_pesos;
            }
            //obtengo cant de descto unitario
            $valor_descto_unidad = ($valor_descto / $cantidad);

            //calculo % de descto
            $porcent_descto_unidad = (100 * $valor_descto_unidad) / $precio_unitario;

            $porcent_descto_unidad = ($porcent_descto_unidad > 100) ? 100 : $porcent_descto_unidad;
            $item['discount'] = $porcent_descto_unidad;

            //Transferencia a título gratuíto, descto 100%, solo Perú
            if( Funciones::get_pais() === 'PE' && $porcent_descto_unidad >= 100 )
            {
                $item['comment'] = 'Transferencia a título gratuíto';
            }

            //resto descto aplicado
            $total_descto_pesos -= $valor_descto;

            //   Funciones::print_r_html( $item, "descuentos $porcent_descto_unidad = ($precio_unitario * $valor_descto_unidad)/100" );

            $arraux[] = $item;
        }
        //otro reverse
        $arraux_normal = array_reverse($arraux);
        return $arraux_normal;
    }
}
