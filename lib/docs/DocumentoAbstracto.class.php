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
class DocumentoAbstracto
{

    public static $LOG_FOLDER = null, $FILES_FOLDER = null;

    /**
     *  envia un GET
     * @param type $url
     * @return type
     */
    function __construct()
    {
        if( DocumentoAbstracto::$LOG_FOLDER == null )
        {
            $aux = dirname(__FILE__) . '/../logs';
            $aux = realpath($aux);
            DocumentoAbstracto::$LOG_FOLDER = $aux;
        }
        if( DocumentoAbstracto::$FILES_FOLDER == null )
        {
            DocumentoAbstracto::$FILES_FOLDER = dirname(__FILE__) . '/../xml_docs';
        }
    }

    public function get($url, $return_raw = false)
    {
        sleep(1);
        $access_token = Funciones::get_token_bsale();

        // Inicia cURL
        $session = curl_init($url);

        // Indica a cURL que retorne data
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 400);
        curl_setopt($session, CURLOPT_TIMEOUT, 400); //timeout in seconds
        // Configura cabeceras
        $headers = array(
            'access_token: ' . $access_token,
            'Accept: application/json',
            'Content-Type: application/json'
        );
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        //Tell cURL that it should only spend 10 seconds
        //trying to connect to the URL in question.
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 120);

        //A given cURL operation should only take
        //30 seconds max.
        curl_setopt($session, CURLOPT_TIMEOUT, 180);

        // Ejecuta cURL
        $response = curl_exec($session);
        $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

        // Cierra la sesión cURL
        curl_close($session);

        //too many requests
        if( $code == 429 )
        {
            $response_array = array(
                'error' => 'Error al conectarse a Bsale: Demasiadas conexiones por minuto. Espere unos minutos antes de volver a intentar',
            );
            return $response_array;
        }

        if( $return_raw )
        {
            return $response;
        }

        //Esto es sólo para poder visualizar lo que se está retornando
        //    echo("<p>respuesta para $url:</p>");
        //   Funciones::print_r_html($response, "Respuesta de GET para: $url");
        $response_array = json_decode($response, true);

        //  Funciones::print_r_html($response_array);
        //print_r( $response );
        //no autorizado, tokenm invalido, agrego alerta
        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            //cualquier error, menos de autenticación
            /* if( $code >= 400 && $code != 401 && $code != 404)
              {
              $arr = array(
              'type' => 'curl_error',
              'code' => $code,
              'msg' => "Error:" . date('d-m-Y H:i') . " error al conectar a Bsale.",
              'url' => $url,
              'method' => 'GET',
              'fecha' => date('d-m-Y H:i'),
              );
              $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
              update_option('bsale_option_errors', $json);
              } */
            //error de autenticación
            if( $code == 401 )
            {
                $arr = array(
                    'type' => 'curl_error',
                    'code' => $code,
                    'msg' => "Error:" . date('d-m-Y H:i') . " no se pudo conectar a Bsale. Debe escribir el token correcto en la página de configuración en Herramientas/Woocommerce Bsale",
                    'url' => $url,
                    'method' => 'GET',
                    'fecha' => date('d-m-Y H:i'),
                );
                $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                update_option('bsale_option_errors', $json);
            }

            //todo ok
            if( $code < 400 )
            {
                update_option('bsale_option_errors', null);
            }
        }

        return $response_array;
    }

    public function post($url, $array_data = null, $local_id = null, $parent_local_id = null, $parent_remote_id = null)
    {
        sleep(1);
        //tipo de docto que deseo postear
        $doc_type = $this->getDocumentType($array_data['documentTypeId']);
        $doc_name = $this->getDocumentName($array_data['documentTypeId']);

        //si es boleta, y vienen sus procutos, los saco
        $productos_boleta = isset($array_data['productos_boleta']) ? $array_data['productos_boleta'] : null;
        //los saco pues no van en el post
        unset($array_data['productos_boleta']);

        //saco tienda
        $tienda = isset($array_data['tienda']) ? $array_data['tienda'] : '';
        unset($array_data['tienda']);

        $access_token = Funciones::get_token_bsale();

        $modo2 = ( defined('BSALE_MODO2') && BSALE_MODO2 === true ) ? true : false;

        if( $modo2 )
        {
            $access_token = 'xxx';
            //Funciones::print_r_html("uso token depruebas $access_token");
        }

        // Parsea a JSON
        $data = json_encode($array_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = null;

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($array_data, __METHOD__ . " post array:");
            Funciones::print_r_html($data, __METHOD__ . " post json:");
            return false;
        }

        //en caso de error, reintentar 6 veces
        $i = 0;
        do
        {
            // Inicia cURL
            $session = curl_init($url);

            // Indica a cURL que retorne data
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);

            // Activa SSL
            curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2); //antes era true
            //Tell cURL that it should only spend 10 seconds
            //trying to connect to the URL in question.
            curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 120);

            //A given cURL operation should only take
            //30 seconds max.
            curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 400);
            curl_setopt($session, CURLOPT_TIMEOUT, 400); //timeout in seconds
            // Configura cabeceras
            $headers = array(
                'access_token: ' . $access_token,
                'Accept: application/json',
                'Content-Type: application/json'
            );
            curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
            // Indica que se va ser una petición POST
            curl_setopt($session, CURLOPT_POST, true);
            // Agrega parámetros
            curl_setopt($session, CURLOPT_POSTFIELDS, $data);

            // Ejecuta cURL
            $response = curl_exec($session);
            $code = (int) curl_getinfo($session, CURLINFO_HTTP_CODE);

            // Cierra la sesión cURL
            curl_close($session);

            //error, espero 1 seg para reintentar
            if( $code >= 400 )
            {
                sleep(1);
            }
            else
            {
                break;
            }
            $i++;

            //solo woocommerce intenta varias veces
            if( INTEGRACION_SISTEMA !== 'woocommerce' )
            {
                break;
            }
        }
        while( $i < 4 );

        //Funciones::print_r_html($data, "post a Bsale, url = '$url', enviado:");
        //Funciones::print_r_html($response, "post a Bsale, url = '$url' respuesta:");
        //Esto es sólo para poder visualizar lo que se está retornando
        $response_array = json_decode($response, true);
        //    Funciones::print_r_html( $response, "respuesta" );
        //si hay error, aviso
        if( isset($response_array) && isset($response_array['id']) )
        {
            //si venian, los agrego de nuevo para que queden en el log
            if( isset($productos_boleta) )
            {
                $array_data['productos_boleta'] = $productos_boleta;
            }
            if( !empty($tienda) )
            {
                $array_data['tienda'] = $tienda;
            }

            $array_log = array(
                //'headers' => $headers,
                'url' => $url,
                // 'token' => $access_token,
                'post_array' => $array_data,
                'post_json' => $data,
                'post_respuesta_array' => $response_array,
                'post_respuesta_json' => $response,
            );

            //SI EL TIPO = 'nc' entonces tengo que haceruna consulta adicional,
            //pues bsale me devuelve el id de la devolucion, mas no los datos de la nc emitida
            if( $doc_type == 'nc' )
            {

                $devolucion_id = $response_array['id'];
                //   $fecha = $result['emissionDate'];
                //si vienen results, hago un get de la nc
                $nc = new NotaCredito();
                $response_array = $nc->getNotaCreditoFromDevolucionId($devolucion_id);
            }
            else if( $doc_type == 'nd' )
            {
                //Funciones::print_r_html( $response_array, "post nd" );
                $nota_debito_id = $response_array['debit_note']['id'];

                $nota_debito = new NotaDebito();
                $response_array = $nota_debito->getND($nota_debito_id);
            }
            $doc_id_remoto = $response_array['id'];
            $folio = isset($response_array['number']) ? $response_array['number'] : 0; //folio 
            $urlPublicView = isset($response_array['urlPublicView']) ? $response_array['urlPublicView'] : 0;

            //inicio log         
            //fecha docto
            $gmt_date = isset($array_data['emissionDate']) ? $array_data['emissionDate'] : time(); //fecha docto
            $basename = "{$doc_name}_post_{$local_id}_{$doc_id_remoto}_{$folio}_{$gmt_date}";
            $title = "POST de {$doc_name} folio: $folio, "
                    . " nro pedido: $local_id, id bsale: $doc_id_remoto, fecha: $gmt_date";

            $this->logFile($basename, $array_log, $url, $title);

            //guardo transaccion en la db
            $logdoc = new LogDocumentos();
            try
            {
                $logdoc->guardarLogTable($doc_type, $local_id, $doc_id_remoto, $data, $array_data, $response, $response_array);
            }
            catch( Exception $exc )
            {
                Funciones::print_r_html($exc->getTraceAsString(), "error al guardar datos en tabla");
            }

            //los saco del log
            unset($array_data['productos_boleta']);

            //descargar files
            $url_doc = isset($response_array['urlPdf']) ? $response_array['urlPdf'] : '';
            //url xml
            $url_xml = isset($response_array['urlXml']) ? $response_array['urlXml'] : '';

            $doc_id = $response_array['id'];
            // $fecha = $response_array['emissionDate'];
            $target_folder = self::$FILES_FOLDER;

            $filename = "{$doc_type}_{$folio}_{$local_id}.pdf"; //archivo pdf
            $filename_xml = "{$doc_type}_{$folio}_{$local_id}.xml"; //archivo xml           
            //   echo("<p>obteniendo pdf desde $url_doc, para guardar en $target_folder/$filename</p>");
            //si viene error en respuesta de bsale
            if( isset($response_array['error']) )
            {

                //envio email de aviso
                /* $utils = new Utils();
                  $email_cliente = EMAIL_ERROR;
                  $subject = "Integración Bsale - Error al emitir $doc_name";
                  $message = "<h3>Error al emitir $doc_name ($url):</h3>" .
                  "<p>Error en POST, datos :</p>" .
                  Funciones::print_r_html2($array_log) .
                  ""; */
                //$utils->sendEmail($email_cliente, $subject, $message);
                //no logueo nada, porque no se puede
                // Funciones::print_r_html($response_array, "Error en post de documento $doc_name");
                return $response_array;
            }
            // $this->saveUrlToFile($url_doc, $target_folder, $filename);
            //$this->saveUrlToFile($url_xml, $target_folder, $filename_xml);
        }
        //si hubo error, logueo el error
        else
        {
            $array_log = array(
                'url' => $url,
                // 'token' => $access_token,
                'post_array' => $array_data,
                'post_json' => $data,
                'post_respuesta_array' => $response_array,
                'post_respuesta_json' => $response,
                'curl_getinfo code' => $code,
            );
            $doc_id_remoto = -1;
            //inicio log         
            //fecha docto
            $gmt_date = isset($array_data['emissionDate']) ? $array_data['emissionDate'] : time(); //fecha docto
            $basename = "{$doc_name}_post_{$local_id}_{$doc_id_remoto}_ERROR_{$gmt_date}";
            $title = "POST de {$doc_name} folio: ERROR, "
                    . " nro local: $local_id, id bsale: $doc_id_remoto, fecha: $gmt_date";

            $this->logFile($basename, $array_log, $url, $title);
        }

        //log resultado
        $this->save_log_table_wc($doc_type, $doc_name, $local_id, $doc_id_remoto, $data, $array_data, $response, $response_array);

        //mensaje de error traducido
        if( isset($response_array['error']) )
        {
            $response_array['error'] = $this->translate($response_array['error'], $doc_name);
        }

        //si viene error, incluyo código http response
        if( $code >= 400 )
        {
            if( isset($response_array['error']) )
            {
                $response_array['error'] .= " ($code)";
            }
            else
            {
                $response_array['error'] = "ERROR $code";
            }

            if( INTEGRACION_SISTEMA === 'woocommerce' )
            {
                //cualquier error, menos de autenticación
                if( $code > 400 && $code != 401 )
                {
                    $arr = array(
                        'type' => 'curl_error',
                        'code' => $code,
                        'msg' => "Error:" . date('d-m-Y H:i') . " error al conectar a Bsale.",
                        'url' => $url,
                        'method' => 'POST',
                        'fecha' => date('d-m-Y H:i'),
                    );
                    $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    update_option('bsale_option_errors', $json);
                }
                //error de autenticación
                if( $code == 401 )
                {
                    $arr = array(
                        'type' => 'curl_error',
                        'code' => $code,
                        'msg' => "Error:" . date('d-m-Y H:i') . " no se pudo conectar a Bsale. Debe escribir el token correcto en la página de configuración en Herramientas/Woocommerce Bsale",
                        'url' => $url,
                        'method' => 'POST',
                        'fecha' => date('d-m-Y H:i'),
                    );
                    $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    update_option('bsale_option_errors', $json);
                }
                //todo ok
                if( $code < 400 )
                {
                    update_option('bsale_option_errors', null);
                }
            }
        }
        return $response_array;
    }

    public function save_log_table_wc($tipo_doc, $doc_name, $local_id, $id_remoto, $post_json, $post_array, $response_json, $response_array)
    {
        if( INTEGRACION_SISTEMA !== 'woocommerce' )
        {
            return;
        }

        $order_id = $local_id;
        $doc_id = $id_remoto;
        $doc_folio = isset($response_array['number']) ? $response_array['number'] : '-1';
        $json_send = $post_json;

        $json_recv = $response_json;

        $result = isset($response_array['urlPublicView']) ? 'ok' : 'error';

        $link_dte = isset($response_array['urlPublicView']) ? $response_array['urlPublicView'] : '';

        //error, guardo respuesta de bsale en msg
        if( $result !== 'ok' )
        {
            $msg = isset($response_array['error']) ? $response_array['error'] : '';
            //traduzco mensaje de error
            $msg = $this->translate($msg, $doc_name);
        }
        else
        {
            $msg = '';
        }

        $fecha = date("Y-m-d H:i:s");
        $source = 'wc';

        $arr_data = array(
            'order_id' => $order_id,
            'tipo_doc' => $tipo_doc,
            'doc_id' => $doc_id,
            'doc_folio' => $doc_folio,
            'json_send' => $json_send,
            'json_recv' => $json_recv,
            'result' => $result,
            'link_dte' => $link_dte,
            'msg' => $msg,
            'fecha' => $fecha,
            'source' => $source,
        );

        $log_table = new LogDTEBsale();
        $res = $log_table->insert($arr_data);

        return $res;
    }

    /**
     * devuelve el mensaje de Bsale en español
     * @param type $bsale_msg
     */
    public function translate($bsale_msg, $doc_name)
    {
        $bsale_msg = strtolower($bsale_msg);
        $str = $bsale_msg;

        if( $bsale_msg === 'invalid variant' )
        {
            $str = "SKU no pertenece a ningún producto de Bsale. Debe crear producto con mismo sku en Bsale e intentar de nuevo ($bsale_msg).";
        }
        $str = str_ireplace('there is no stock for this products', 'No hay stock en Bsale de estos productos. Agregue stock e intente de nuevo', $str);

        $str = str_ireplace('does not have numbers available, check sii caf', 'Se agotaron los folios para ' . $doc_name . '. Debe cargar folios en su cuenta Bsale e intentar de nuevo.', $str);
        $str = str_ireplace('invalid price list', 'La configuración de su cuenta de Bsale está incompleta. Falta asignar una "lista de precios" a la sucursal Bsale donde se emite este documento. Puede ser la que usted desee.', $str);
        $str = str_ireplace('invalid pack promo variant', 'Se intentó vender un pack que en Bsale está creado a nivel de producto. Modifique el pack en Bsale para que quede creado a nivel de variación e intente de nuevo.', $str);
        $str = str_ireplace('the sii caf used is expired, you must request and upload a new one', 'El archivo CAF del SII desde el que se obtienen los folios para emitir ' . $doc_name . ', ha vencido. Debe subir uno nuevo.', $str);
        $str = str_ireplace('This document may not be removed', 'Este documento ' . $doc_name . ' no puede ser anulado o borrado en Bsale. Contacte al soporte si desea borrarlo.', $str);
        $str = str_ireplace('document amount is less than the minimum allowed', 'No se puede generar el documento debido a que el total del pedido es menor que lo permitido (para emitir boletas o facturas, el monto mínimo es $ 180)', $str);
        $str = str_ireplace('reference document is not declared yet', 'el documento no se puede anular, pues aún no ha sido declarado. Puede borrarlo directamente en su cuenta de Bsale.', $str);
        $str = str_ireplace('the json does not have document type', 'En la configuración de la integración no se ha seleccionado el documento ' . $doc_name . '. Selecciónelo e intente de nuevo', $str);

        $str = str_ireplace('this office is configured as warehouse', 'La sucursal de Bsale donde se quiere emitir ' . $doc_name . ' es una "Bodega". '
                . 'No se pueden emitir documentos desde una bodega. Debe configurarla en su cuenta de Bsale para que sea una "sucursal"', $str);

        return $str;
    }

    public function put($url, $array_data = null, $local_id = null, $parent_local_id = null, $parent_remote_id = null)
    {
        sleep(1);
        $access_token = Funciones::get_token_bsale();

        //Funciones::print_r_html( null, "put: Enviando datos $url:" );
        // Funciones::print_r_html( $array_data, "datos array $url:" );
        // Inicia cURL
        $session = curl_init($url);
        if( !empty($array_data) )
        {
            // Parsea a JSON
            $data = json_encode($array_data, JSON_UNESCAPED_UNICODE);
            // Funciones::print_r_html( $data, "json put: Enviando datos $url:" );
            // Agrega parámetros
            curl_setopt($session, CURLOPT_POSTFIELDS, $data);
        }

        // Indica a cURL que retorne data
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 400);
        curl_setopt($session, CURLOPT_TIMEOUT, 400); //timeout in seconds
        // Activa SSL
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2); //antes era true
        // Configura cabeceras
        $headers = array(
            'access_token: ' . $access_token,
            'Accept: application/json',
            'Content-Type: application/json'
        );
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        // Indica que se va ser una petición put
        //  curl_setopt( $session, CURLOPT_PUT, true );
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($session, CURLOPT_HEADER, false);

        // Agrega parámetros
        curl_setopt($session, CURLOPT_POSTFIELDS, $data);
        // Ejecuta cURL
        $response = curl_exec($session);
        $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

        // Cierra la sesión cURL
        curl_close($session);

        //Esto es sólo para poder visualizar lo que se está retornando
        $response_array = json_decode($response, true);
        //   Funciones::print_r_html( $response_array );
        //si hay error, aviso
        if( isset($response_array) /* && isset( $response_array['error'] ) */ )
        {

            $array_log = array(
                'post_array' => $array_data,
                'post_json' => $data,
                'post_respuesta_array' => $response_array,
                'post_respuesta_json' => $response
            );

            //guardo transaccion en la db
            $logdoc = new LogDocumentos();
            try
            {
                $logdoc->guardarLogTable('bs', $local_id, 555, $data, $array_data, $response, $response_array);
            }
            catch( Exception $exc )
            {
                Funciones::print_r_html($exc->getTraceAsString(), "error al guardar datos en tabla");
            }


            //si viene error en respuesta de jumpseller
            if( isset($response_array['message']) )
            {
                /* //envio email de aviso
                  $utils = new Utils();
                  $email_cliente = EMAIL_ERROR;
                  $subject = "Error en POST Docto";
                  $message = "<h3>Error en POST ($url):</h3>" .
                  "<p>Error en POST, datos :</p>" .
                  Funciones::print_r_html2($array_log) .
                  "";
                  $utils->sendEmail($email_cliente, $subject, $message);
                  //no logueo nada, porque no se puede
                  Funciones::print_r_html($response_array, "Error en post de documento $doc_name"); */
                return $response_array;
            }
        }
        return $response_array;
    }

    public function delete($url, $array_data = null)
    {
        $access_token = Funciones::get_token_bsale();
        // Parsea a JSON
        //  $data = json_encode( $array_data );
        //Funciones::print_r_html(null, "delete: Enviando datos $url:");
        // Funciones::print_r_html( $array_data, "datos array $url:" );
        // Inicia cURL
        $session = curl_init($url);

        if( !empty($array_data) )
        {
            // Parsea a JSON
            $data = json_encode($array_data, JSON_UNESCAPED_UNICODE);
            // Funciones::print_r_html( $data, "json put: Enviando datos $url:" );
            // Agrega parámetros
            curl_setopt($session, CURLOPT_POSTFIELDS, $data);
        }
        // Indica a cURL que retorne data
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);

        // Activa SSL
        curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2); //antes era true
        // Configura cabeceras
        $headers = array(
            'access_token: ' . $access_token,
            'Accept: application/json',
            'Content-Type: application/json'
        );
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        // Indica que se va ser una petición put
        //  curl_setopt( $session, CURLOPT_PUT, true );
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($session, CURLOPT_HEADER, false);

        // Agrega parámetros
        //  curl_setopt( $session, CURLOPT_POSTFIELDS, $data );
        // Ejecuta cURL
        $response = curl_exec($session);
        $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

        // Cierra la sesión cURL
        curl_close($session);
        //Esto es sólo para poder visualizar lo que se está retornando
        $response_array = json_decode($response, true);

        $response_array['curl_code'] = $code;

        return $response_array;
    }

    private function getDocumentType($doc_id)
    {
        $tipo = null;

        switch( $doc_id )
        {
            case Funciones::get_factura_id():
                $tipo = 'f';

                break;

            case Funciones::get_boleta_id(): //boleta extenta
                $tipo = 'be';

                break;
            case Funciones::get_nc_id():
                $tipo = 'nc';
                /* case nc_id_facturas_pe:
                  $tipo = 'nc'; */

                break;
            case Funciones::get_nv_id():
                $tipo = 'nv';
                break;
            case -1:
                $tipo = 'db';
                break;
            case Funciones::get_gd_id():
                $tipo = 'gd';
                break;

            default:
                $tipo = 'dte';
                break;
        }
        return $tipo;
    }

    private function getDocumentName($doc_id)
    {
        $tipo = null;

        switch( $doc_id )
        {
            case Funciones::get_factura_id():
                $tipo = 'factura';

                break;

            case Funciones::get_boleta_id(): //boleta extenta
                $tipo = 'boleta';

                break;
            case Funciones::get_nc_id():
                $tipo = 'nota de credito';

                break;

            case Funciones::get_nv_id():
                $tipo = 'nota de venta';
                break;
            case -1:
                $tipo = 'despacho boleta';
                break;
            case Funciones::get_gd_id():
                $tipo = 'guia de despacho';
                break;

            default:
                $tipo = 'dte';
                break;
        }
        return $tipo;
    }

    public function getDocumentFile($rootpath, $tipo_doc, $remote_id, $extension = 'pdf')
    {
        $target_folder = $rootpath . '/xml_docs';

        $filename = "{$tipo_doc}_{$remote_id}.$extension";

        return "$target_folder/$filename";
    }

    public function logFile($basename, $arr_data, $url, $title = null)
    {
        $target_folder = self::$LOG_FOLDER;

        //si no existe, lo creo
        if( !is_dir($target_folder) )
        {
            mkdir($target_folder, 0755, true);
        }

        $filename = "$basename.log";

        $content = $title . "\n";

        if( !empty($url) )
        {
            $content .= "url: $url\n";
        }
        if( !empty($arr_data) )
        {
            $content .= print_r($arr_data, true) . "\n";
        }

        //{
        try
        {
            file_put_contents("$target_folder/$filename", $content, FILE_APPEND | LOCK_EX);
        }
        catch( Exception $ex )
        {
            
        }


        //}
    }

    public function getLogFolder()
    {
        return self::$LOG_FOLDER;
    }

    public function getFilesFolder()
    {
        return self::$FILES_FOLDER;
    }

    public function saveUrlToFile($url, $target_folder, $filename)
    {
        if( empty($url) || empty($filename) || empty($target_folder) )
            return;

        //identifico la extension y si debo o no descargar este archivo
        $auxx = explode('.', $filename);
        $ext = end($auxx);

//        if(empty($filename))
//            $filename = 'desconocido.txt';
        //si no existe, lo creo
        if( !is_dir($target_folder) )
        {
            mkdir($target_folder, 0755, true);
        }

        try
        {
            $xml = file_get_contents($url);
            file_put_contents("$target_folder/$filename", $xml);
        }
        catch( Exception $exc )
        {
            echo("\nsaveUrlToFile(): ext: $ext, $url no existe\n");
            return;
        }
    }
}
