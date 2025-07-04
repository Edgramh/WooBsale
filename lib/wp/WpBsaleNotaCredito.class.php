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
class WpBsaleNotaCredito
{ /**
 * anula dts en Bsale
 * si solo hay NV, hace un DELETE a la nv
 * si hay boleta o factura, genera NC
 * @param type $order_id
 * @return boolean
 */

    public function anular_dte_bsale($order_id)
    {
        $me = new WpBsale();

        $order = wc_get_order($order_id);

        if( empty($order) )
        {
            return null;
        }

        $estado_orden = $order->get_status();
        $estado_arr_nc = Funciones::get_estado_dte_cancelled();
        $is_estado_para_nc = in_array($estado_orden, $estado_arr_nc);

        //es estado para emitir nc?
        if( !$is_estado_para_nc )
        {
            return;
        }

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("anular_dte_bsale(order id=$order_id)");
        }

        $order_number = $order->get_order_number();
        //se ha emitido nc?
        $bsale_nc_id = (int) get_post_meta($order_id, 'bsale_docto_id_nc', true);

        if( $bsale_nc_id > 0 )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                $bsale_docto_id_nc_url = get_post_meta($order_id, 'bsale_docto_id_nc_url', true);
                Funciones::print_r_html("anular_dte_bsale(order id=$order_id), ya se ha emitido NC id=$bsale_nc_id, url=$bsale_docto_id_nc_url");
            }
            else
            {
                $order->add_order_note('Integración Bsale: Ya se ha emitido nota de crédito para este pedido. No se emite otra vez.');
            }

            return false;
        }

        //url de dtes
        $bsale_docto_id_nv_url = get_post_meta($order_id, 'bsale_docto_id_nv_url', true);
        $bsale_docto_id_factura_url = get_post_meta($order_id, 'bsale_docto_id_factura_url', true);
        $bsale_docto_id_boleta_url = get_post_meta($order_id, 'bsale_docto_id_boleta_url', true);
        $bsale_docto_id_nc_url = get_post_meta($order_id, 'bsale_docto_id_nc_url', true);

        //folios de dtes
        $bsale_docto_folio_nv = get_post_meta($order_id, 'bsale_docto_folio_nv', true);
        $bsale_docto_folio_boleta = get_post_meta($order_id, 'bsale_docto_folio_boleta', true);
        $bsale_docto_folio_factura = get_post_meta($order_id, 'bsale_docto_folio_factura', true);
        $bsale_docto_folio_nc = get_post_meta($order_id, 'bsale_docto_folio_nc', true);

        //tiene boleta o factura?
        $has_bol_or_factura = false;
        $has_nv = false;

        //si tiene boleta, emito NC para la boleta
        if( !empty($bsale_docto_id_boleta_url) )
        {
            $has_bol_or_factura = true;
        }
        //si tiene factura, emito NC para la factura
        if( !empty($bsale_docto_id_factura_url) )
        {
            $has_bol_or_factura = true;
        }
        //solo tiene nv?
        if( !empty($bsale_docto_id_nv_url) )
        {
            $has_nv = true;
        }

        //si no tiene dts, no hago nada
        if( !$has_bol_or_factura && !$has_nv )
        {
            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("anular_dte_bsale(order id=$order_id) no tiene boly, fact ni NV. No se hace nada.");
            }
            else
            {
                $order->add_order_note('Integración Bsale: Este pedido no tiene boleta, factura o nota de venta. No se emite nota de crédito.');
            }
            return true;
        }

        $bsale_dte = new BsaleDTE();

        //si ntiene bol o factura, creo NC
        if( $has_bol_or_factura )
        {
            //veo si ha sido declarada al SII
            $factura_id = get_post_meta($order_id, 'bsale_docto_id_factura', true);
            $boleta_id = get_post_meta($order_id, 'bsale_docto_id_boleta', true);

            //encuentro el tiupo de dte a anualar
            $tipo_dte = !empty($factura_id) ? 'factura' : 'boleta';

            $tipo_documento = $tipo_dte;
            $tipo_docto_nombre = $tipo_dte;

            //id del dte
            $dte_id = $tipo_dte === 'boleta' ? $boleta_id : $factura_id;

            //get sucursal desde donde fue emitida
            $doc = new Documento();

            $doc_details = $doc->get_docto($dte_id);

            $sucursal_id = isset($doc_details['office']['id']) ? $doc_details['office']['id'] : 0;
            $informedSii = isset($doc_details['informedSii']) ? $doc_details['informedSii'] : -1;
            $dte_folio = isset($doc_details['number']) ? $doc_details['number'] : '';

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("anular_dte_bsale(order id=$order_id), debo anular $tipo_dte id=$dte_id, folio $dte_folio, "
                        . "de sucursal id=$sucursal_id, estado en SII=$informedSii");
            }
            $order->add_order_note("Integración Bsale: documento $tipo_dte #$dte_folio, estado= $informedSii");

            //si no es cero, no ha sido declarada o fue rechazada por el sii, solo se hace DELETE
            //parece que siempre debo hacer nc
            // 0 es correcto, 1 es enviado, 2 es rechazado (Integer).
            if( $informedSii == 2 )
            {
                //puedo usar la clase NV, pues llama al delete de Bsale sin distinguir el tipo de dte (bol, fact, nv, etc)
                $nv = new NotaVenta();

                //solo debo hacer DELETE de la nv
                $result = $nv->delete_nv($dte_id, $sucursal_id);

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($result, "anular_dte_bsale(order id=$order_id), estado en SII=$informedSii no aporbado, hago DELETE, anular $tipo_dte id=$dte_id, folio $dte_folio, "
                            . "de sucursal id=$sucursal_id, respuesta");
                }

                //error?
                $error_msg = isset($result['error']) ? $result['error'] : null;

                //exito en la anualcion
                if( empty($error_msg) )
                {
                    $str_note = "Pedido cancelado. $tipo_documento folio #$dte_folio ha sido borrada (" . print_r($result, true) . ")";

//                    update_post_meta($order_id, "bsale_docto_id_{$tipo_documento}", ''); //id doc en Bsale
//                    update_post_meta($order_id, "bsale_docto_id_{$tipo_documento}_url", ''); //url
//                    update_post_meta($order_id, "bsale_docto_folio_$tipo_documento", ''); //folio
                    update_post_meta($order_id, 'bsale_docto_error', ''); //limpio error

                    $note = $str_note;
                    // Add the note
                    $note_id = $order->add_order_note($note);

                    //no hago return, pues si hay nv, debo anularla más abajo
                }
                //error
                else
                {
                    $error_msg = isset($result['error']) ? $result['error'] : print_r($result, true);
                    $str_note = "Pedido cancelado:  $tipo_documento folio #$dte_folio ERROR: $error_msg";

                    update_post_meta($order_id, 'bsale_docto_error', $str_note);
                    //update_post_meta($order_id, 'bsale_docto_tipo', $str_note);

                    $note = $str_note;
                    // Add the note
                    $note_id = $order->add_order_note($note);
                }
                //no hago return, pues si hay nv, debo anularla más abajo
            }
            //genero NC, no se hace DELETE de NV
            elseif( !empty($sucursal_id) )
            {
                $tipo_documento_nc = 'nc';
                $tipo_docto_nombre_nc = 'Nota de crédito';

                $result = $bsale_dte->crear_nc_bsale($order_number, null, $order_id);

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($result, "anular_dte_bsale(order id=$order_id), estado en SII=$informedSii emitido, "
                            . "hago NC, respuesta");
                }

                //msge de exito
                if( isset($result['urlPublicView']) )
                {
                    $str_note = "ver <a href='{$result['urlPublicView']}' target='_blank'>"
                            . "$tipo_docto_nombre_nc #{$result['number']} para $tipo_documento folio #$dte_folio</a>";

                    update_post_meta($order_id, "bsale_docto_id_{$tipo_documento_nc}", $result['id']); //id doc en Bsale
                    update_post_meta($order_id, "bsale_docto_id_{$tipo_documento_nc}_url", $result['urlPublicView']); //url
                    update_post_meta($order_id, "bsale_docto_folio_{$tipo_documento_nc}", $result['number']); //folio
                    update_post_meta($order_id, 'bsale_docto_error', ''); //limpio error

                    $note = $str_note;
                    // Add the note
                    $note_id = $order->add_order_note($note);
                }
                else
                {
                    $error_msg = isset($result['error']) ? $result['error'] : print_r($result, true);
                    $str_note = "$tipo_docto_nombre folio #$dte_folio, $tipo_docto_nombre_nc ERROR: $error_msg";

                    update_post_meta($order_id, 'bsale_docto_error', $str_note);
                    //update_post_meta($order_id, 'bsale_docto_tipo', $str_note);

                    $note = $str_note;
                    // Add the note
                    $note_id = $order->add_order_note($note);
                }
                return $result;
            }
        }

        //si llego hasta aquí, solo puedo hacer DELETE nv
        if( $has_nv )
        {
            $nv_id = get_post_meta($order_id, 'bsale_docto_id_nv', true);
            //get sucursal desde donde fue emitida
            $doc = new Documento();

            $doc_details = $doc->get_docto($nv_id);
            $sucursal_id = isset($doc_details['office']['id']) ? $doc_details['office']['id'] : 0;
            $dte_folio = isset($doc_details['number']) ? $doc_details['number'] : '';

            //doc no existe en bsale
            if( $sucursal_id <= 0 )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("anular_dte_bsale(order id=$order_id) nv id= #$nv_id no existe en Bsale. No se hace nada");
                }
            }

            $nv = new NotaVenta();

            //solo debo hacer DELETE de la nv
            $result = $nv->delete_nv($nv_id, $sucursal_id);

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($result, "anular_dte_bsale(order id=$order_id), DELETE de nv, respuesta");
            }

            //error?
            $error_msg = isset($result['error']) ? $result['error'] : null;

            $tipo_documento = 'nv';
            $tipo_docto_nombre = 'Nota de venta';

            //exito en la anualcion
            if( empty($error_msg) )
            {

                $str_note = "Pedido cancelado. $tipo_docto_nombre folio #$dte_folio ha sido borrada</a>";

                update_post_meta($order_id, "bsale_docto_id_{$tipo_documento}", ''); //id doc en Bsale
                update_post_meta($order_id, "bsale_docto_id_{$tipo_documento}_url", ''); //url
                update_post_meta($order_id, "bsale_docto_folio_$tipo_documento", ''); //folio
                update_post_meta($order_id, 'bsale_docto_error', ''); //limpio error

                $note = $str_note;
                // Add the note
                $note_id = $order->add_order_note($note);
            }
            //error
            else
            {
                $error_msg = isset($result['error']) ? $result['error'] : print_r($result, true);
                $str_note = "Pedido cancelado. $tipo_docto_nombre folio #$dte_folio ERROR: $error_msg";

                update_post_meta($order_id, 'bsale_docto_error', $str_note);
                //update_post_meta($order_id, 'bsale_docto_tipo', $str_note);

                $note = $str_note;
                // Add the note
                $note_id = $order->add_order_note($note);
            }
        }
        return true;
    }
}
