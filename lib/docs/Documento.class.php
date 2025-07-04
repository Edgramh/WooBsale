<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of Documento
 *
 * @author angelorum
 */
class Documento extends DocumentoAbstracto
{

    /**
     * 
     * @param type $pdf_url_arrayrecibo array de pdfs, los descarga, guarda e nun zip y muestra enlace
     */
    public function download_docs($filename_zip, $pdf_url_array)
    {
        /* $files = array(
          'http://google.com/images/logo.png',
          'http://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Wikipedia-logo-en-big.png/220px-Wikipedia-logo-en-big.png',
          ); */

        $files = $pdf_url_array;

# create new zip object
        $zip = new ZipArchive();

# create a temp file & open it
        $tmp_file = tempnam('./downloads/', '') . '.zip';
        $zip->open($tmp_file, ZipArchive::CREATE);

# loop through each file
        foreach( $files as $file )
        {
            # download file
            $download_file = file_get_contents($file);

            #add it to the zip
            $zip->addFromString(basename($file) . '.pdf', $download_file);
        }

# close zip
        $zip->close();

# send the file to the browser as a download
        header('Content-disposition: attachment; filename="' . $filename_zip . '.zip"');
        header('Content-type: application/zip');
        readfile($tmp_file);
        unlink($tmp_file);


        return basename($tmp_file);
    }

    public function listarDocs_arr($tipoDoc, $filters = null)
    {
        $docs_orig = $this->listarDocs($tipoDoc, $filters);

        if( !isset($docs_orig['items']) )
        {
            return null;
        }

        $docs = $docs_orig['items'];

        $arraux = array();

        //debo incluir medio de pago?
        $get_payments = !empty($filters) && (strpos($filters, 'payments') !== false) ? true : false;

        //date para timestamp convert
        date_default_timezone_set('UTC');

        foreach( $docs as $d )
        {
            //debo preguntar de nuevo por el tipo de pago
            if( $get_payments )
            {
                $docto = $this->get_docto($d['id']);
            }
            else
            {
                $docto = null;
            }
            $doc_arr = array(
                'id' => $d['id'],
                'emissionDate' => $d['emissionDate'],
                'expirationDate' => $d['expirationDate'],
                'generationDate' => $d['generationDate'],
                'emissionDate_formatted' => date('d-m-Y', $d['emissionDate']),
                'expirationDate_formatted' => date('d-m-Y', $d['expirationDate']),
                'generationDate_formatted' => date('d-m-Y', $d['generationDate']),
                'number' => $d['number'],
                'totalAmount' => $d['id'],
                'totalAmount' => $d['totalAmount'],
                'netAmount' => $d['netAmount'],
                'taxAmount' => $d['taxAmount'],
                'urlPdf' => $d['urlPdf'],
                'urlPublicView' => $d['urlPublicView'],
                'payment_id' => isset($docto['payments'][0]['id']) ? $docto['payments'][0]['id'] : null,
                'payment_name' => isset($docto['payments'][0]['name']) ? $docto['payments'][0]['name'] : null,
            );
            $arraux[] = $doc_arr;
        }

        //vuelvo a time zone
        date_default_timezone_set("America/Santiago");

        $arr_resp = array();
        $arr_resp['count'] = $docs_orig['count'];
        $arr_resp['limit'] = $docs_orig['limit'];
        $arr_resp['offset'] = $docs_orig['offset'];
        $arr_resp['items'] = $arraux;

        return $arr_resp;
    }

    /**
     * listado de documentos de un tipo especifico
     * 
     * informedsii, filtra documentos si fue declarado en el SII, 
     * 0 es correcto, 1 es enviado, 2 es rechazado (Integer).
     * @param type $tipoDoc
     * @return type
     */
    public function listarDocs($tipoDoc, $filters = null, $showdebug = false)
    {
        $url = BSALE_DOCUMENTOS_URL . "?documenttypeid={$tipoDoc}{$filters}";

        // Funciones::print_r_html("listarDocs, URL: $url");

        $response_array = $this->get($url);

        if( $showdebug )
        {
            Funciones::print_r_html($response_array, "listarDocs, URL: $url");
        }

        return $response_array;
    }

    /**
     * llama a listarDocs(), pero devuelve un array solo con los indices indicados en el argumeto fields
     * @param type $tipoDoc
     * @param type $showdebug
     */
    public function listarDocsFilter($tipoDoc, $fields_array = null, $filters = null)
    {
        $listarDocs = $this->listarDocs($tipoDoc, null, false);

        $arr = array();


        //si no viene nada, retorno nada
        if( !isset($listarDocs) || !isset($listarDocs['items']) )
        {
            // echo("<h1>validddo recorrer</h1>"); 
            //  Funciones::print_r_html($listarDocs, null, true);
            return $arr;
        }

        $datos = $listarDocs['items'];

        //  echo("<h1>valido recorrer</h1>"); 
        //si viene null, desvuelvo todo
        if( $fields_array == null )
        {
            return $listarDocs;
        }

        //  echo("<h1>a recorrer</h1>"); 
        //devuelvo solo fields inidicados en $fields_array
        foreach( $datos as $value )
        {

            $docto_datos = array();

            foreach( $fields_array as $key )
            {
                $docto_datos[$key] = $value[$key];
            }
            $arr[] = $docto_datos;
        }
        return $arr;
    }

    public function get_docto($doctobsale_id, $folio = null, $documenttypeid = null)
    {
        if( !empty($doctobsale_id) && $doctobsale_id > 0 )
        {
            $url = sprintf(BSALE_GET_DOCTO_URL, $doctobsale_id);
            $response = $this->get($url);

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($response, "Documento->get_docto( (BY ID)doc id=$doctobsale_id, folio=$folio, tipo doc=$documenttypeid), "
                        . "url='$url'");
            }

            return $response;
        }

        //devuelvo doc por folio
        elseif( !empty($folio) )
        {
            $url = BSALE_DOCUMENTOS_URL . '?number=' . $folio;

            //tipo de docto
            if( !empty($documenttypeid) && $documenttypeid > 0 )
            {
                $url .= "&documenttypeid=$documenttypeid";
            }
            $response = $this->get($url);
            //solo primer resultado
            $response = isset($response['items'][0]) ? $response['items'][0] : $response;

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($response, "Documento->get_docto( (BY FOLIO)doc id=$doctobsale_id, folio=$folio, tipo doc=$documenttypeid), "
                        . "url='$url'");
            }


            return $response;
        }
    }

    /**
     * detalles de ua factura/boleta/nv/lo que sea
     * @param type $factura_id
     * @return type
     */
    public function getDetallesDocto($doctobsale_id)
    {
        $limit = 50;
        $offset = 0;
        $total = -1; //count not set aun, hasta la primera llamada
        $ok = false;

        $response_array = array( 'items' => array() );

        $url = sprintf(BSALE_GET_DETALLES_DOCTO_URL, $doctobsale_id);


        while( !$ok )
        {
            //get items
            $url2 = $url . "?limit=$limit&offset=$offset";

            $response = $this->get($url2);

            //total de items en este dte
            if( isset($response['count']) )
            {
                $total = $response['count'];
                $response_array['count'] = $total;
            }
            $limit2 = isset($response['limit'])? $response['limit'] : 0;

            $offset += $limit;
            
            if(!isset($response['items']))
            {
                break;
            }

            //agrego items al array de response
            $response_array['items'] = array_merge($response_array['items'], $response['items']);

            //cuando recibo la cant de items 
            if( $offset > $total )
            {
                break;
            }
        }

        return $response_array;
    }

    /**
     * devuelve los ids de los prodcutos contenido en el docto
     * @param type $doctobsale_id
     */
    public function getDetailsIdDocto($doctobsale_id, $just_id_and_cantidad = false)
    {
        $details = $this->getDetallesDocto($doctobsale_id);

        if( empty($details) || !isset($details['items']) )
        {
            Funciones::print_r_html($details, "getDetailsIdDocto( $doctobsale_id )");
            return array();
        }

        $items = $details['items'];
        $arr = array();
        //recorro todos los prodcutos de la boleta y los devuelvo del tipo sku=>id bsale (detail id)
        foreach( $items as $it )
        {
            $sku = $it['variant']['code'];
            $detail_id = $it['id'];
            $cantidad = $it['quantity'];
            $description = $it['variant']['description'];
            $variant_id = $it['variant']['id'];

            $arraux = array( 'detailId' => $detail_id, 'quantity' => $cantidad,
                'description' => $description, 'sku' => $sku, 'variant_id' => $variant_id );

            //esto es para asociar una boleta/factura a los items de una nv
            //caso los elems que sobran
            if( $just_id_and_cantidad )
            {
                unset($arraux['description']);
                unset($arraux['sku']);
                unset($arraux['variant_id']);
            }

            $arr[] = $arraux;
        }
        return $arr;
    }

    public function getCliente($cliente_id)
    {
        $url = sprintf(BSALE_GET_CLIENTE_URL, $cliente_id);
        $response = $this->get($url);
        return $response;
    }

}
