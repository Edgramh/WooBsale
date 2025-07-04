<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Description of Utils
 *
 * @author angelorum
 */
class Utils
{

    /**
     * deveulve listado de estados para pedidos de woocommerce
     * @param type $skip_prefix: si es tru, va a devolver sin el 'wc-' al inicio
     * @return type
     */
    public function woocommerce_get_order_statuses($skip_prefix = false)
    {
        $order_statuses = wc_get_order_statuses();

        // $order_statuses = get_terms('shop_order_status', array( 'hide_empty' => false ));
        $statuses = array();
        foreach( $order_statuses as $slug => $name )
        {
            //excluyo draft
            if( strcasecmp($name, 'draft') == 0 )
            {
                continue;
            }

            if( $skip_prefix )
            {
                $slug = str_replace('wc-', '', $slug);
            }
            $statuses[$slug] = $name;
        }
        return $statuses;
    }

    /**
     * devuelve ip remota del cliente
     * @return type
     */
    public function get_remote_ip()
    {
        //whether ip is from share internet
        if( !empty($_SERVER['HTTP_CLIENT_IP']) )
        {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from proxy
        elseif( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
        {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from remote address
        else
        {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        return $ip_address;
    }

    /**
     * devuelve array con información de la ip del cliente
     * @return type
     */
    public function get_remote_ip_arr()
    {
        $arr_info = array();
        $arr_info['HTTP_CLIENT_IP'] = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '';
        $arr_info['HTTP_X_FORWARDED_FOR'] = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $arr_info['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

        return $arr_info;
    }

    public function remove_emoji($string)
    {

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $string);

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        return $clear_string;
    }

    /**
     * devuelve el modo de pago Bsal para cada medio de pago de WP
     * se pueden sobreescribir los valores en utils, par ano afectar WpBsale
     * @param type $order_id
     * @param type $tipo_pago
     * @param type $gmt_date
     * @param type $tipo_docto
     *  * @param type $total_neto_calculado no se usa
     * @return type
     */
    public function get_modo_pago_wp($order_id, $tipo_pago, $gmt_date, $tipo_docto, $total_productos_ml = 0)
    {
        $order = wc_get_order($order_id);

        $order_total = $order->get_total(); //con impuesto

        //no inlcuir shipping in dte?
        if( !Funciones::is_add_shipping_in_dte() )
        {
            //shipping
            $costo_envio = $order->get_total_shipping();
            $impuesto_envio = $order->get_shipping_tax();
            //en la realidad, este es el precio cvon impuesto
            $neto_envio = $costo_envio - $impuesto_envio;
            //resto shipping
            $order_total -= $neto_envio;
        }
       
        //SI EXISTE MEDIO DE PAGO GLOBAL
        if( $tipo_docto === 'b' && defined('WC_MEDIO_PAGO_BOLETA') && WC_MEDIO_PAGO_BOLETA > 0 )
        {
            $modo_pago_id = WC_MEDIO_PAGO_BOLETA;
        }
        else if( $tipo_docto === 'f' && defined('WC_MEDIO_PAGO_FACTURA') && WC_MEDIO_PAGO_FACTURA > 0 )
        {
            $modo_pago_id = WC_MEDIO_PAGO_FACTURA;
        }
        else
        {
            $arr_pagos = $this->get_array_medios_pago();

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($arr_pagos, "Utils->get_modo_pago_wp($order_id, $tipo_pago, $gmt_date, $tipo_docto), pagos arr");
            }

            if( isset($arr_pagos[$tipo_pago]) )
            {
                $modo_pago_id = $arr_pagos[$tipo_pago];
            }
            else
            {
                //busco default
                $default = isset($arr_pagos['default']) ? $arr_pagos['default'] : 10; //bsale webpay

                $modo_pago_id = $default;
            }
        }

        //mercado libroe, boletas deben ser por subtotal
        if( $total_productos_ml > 0 )
        {
            $order_total = $total_productos_ml;
        }

        $arr_pagos_bsale = array();

        $arr_pagos_bsale[] = array(
            'paymentTypeId' => $modo_pago_id,
            'amount' => $order_total,
            'recordDate' => $gmt_date );

        return $arr_pagos_bsale;
    }

    /**
     * devuelve array[id pago wc]=>id pago bsale asociado
     */
    public function get_array_medios_pago()
    {
        $pagos_arr = Funciones::get_wc_pagos_bsale();

        if( empty($pagos_arr) )
        {
            return array();
        }
        //separo en lineas
        $lineas_arr = explode("\n", $pagos_arr);

        if( isset($_REQUEST['test_dte']) )
        {
            //Funciones::print_r_html($lineas_arr, "Utils->get_array_medios_pago(), arr:");
        }

        $pagos_arr_formatted = array();

        //separo en clave=valor
        foreach( $lineas_arr as $l )
        {
            $arraux = explode('=', $l);

            //solo se permite: id=id
            if( count($arraux) != 2 )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("Utils->get_array_medios_pago(),linea '$l' tienemas de dos xx=yyy, se omite ");
                }
                continue;
            }
            //wc id es string
            $wc_pago_id = trim($arraux[0]);
            //bsale id es int
            $bsale_pago_id = (int) trim($arraux[1]);

            if( $bsale_pago_id <= 0 )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($pagos_arr, "Utils->get_array_medios_pago(), bsale pago id " . $arraux[1] .
                            " es menor a cero: $bsale_pago_id, se omite ");
                }
                continue;
            }
            if( isset($_REQUEST['test_dte']) )
            {
                //Funciones::print_r_html($pagos_arr_formatted, "Utils->get_array_medios_pago(), agrego [$wc_pago_id] = $bsale_pago_id");
            }
            $pagos_arr_formatted[$wc_pago_id] = $bsale_pago_id;
        }

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($pagos_arr_formatted, "Utils->get_array_medios_pago(), resultado");
        }

        return $pagos_arr_formatted;
    }

    /**
     * devuelve array[titulo shipping wc]=>id sucursal bsale asociada
     * param: $tienda_retiro solo devuelve esta tienda
     */
    public function get_array_medios_envio($tienda_retiro = null)
    {
        //solo si esta funcion está activada
        /* if( !Funciones::is_enabled_shipping_filter_stock() )
          {
          if( isset($_REQUEST['param']) )
          {
          Funciones::print_r_html("Utils->get_array_medios_envio() !Funciones::is_enabled_shipping_filter_stock() es false, devuelve array vacio");
          }
          return array();
          } */
        $pagos_arr = Funciones::get_wc_shipping_bsale();

        if( empty($pagos_arr) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("Utils->get_array_medios_envio() no hay medios de enviko asociados a sucursales, devuelve array vacio");
            }
            return array();
        }
        //separo en lineas
        $lineas_arr = explode("\n", $pagos_arr);

        if( isset($_REQUEST['test_dte']) )
        {
            //Funciones::print_r_html($lineas_arr, "Utils->get_array_medios_envio(), arr:");
        }

        $pagos_arr_formatted = array();

        //separo en clave=valor
        foreach( $lineas_arr as $l )
        {
            $arraux = explode('=', $l);

            //solo se permite: id=id
            if( count($arraux) != 2 )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("Utils->get_array_medios_envio(),linea '$l' tienemas de dos xx=yyy, se omite ");
                }
                continue;
            }
            //wc id es string
            $wc_pago_id = trim($arraux[0]);
            $wc_pago_id = $this->filter_chars($wc_pago_id);
            $wc_pago_id = strtolower($wc_pago_id);

            //bsale id es int
            $bsale_pago_id = (int) trim($arraux[1]);

            if( $bsale_pago_id <= 0 )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($pagos_arr, "Utils->get_array_medios_envio(), bsale pago id " . $arraux[1] .
                            " es menor a cero: $bsale_pago_id, se omite ");
                }
                continue;
            }
            if( isset($_REQUEST['test_dte']) )
            {
                //Funciones::print_r_html($pagos_arr_formatted, "Utils->get_array_medios_envio(), agrego [$wc_pago_id] = $bsale_pago_id");
            }
            //si se indicó tienda, solo agrego esta tienda al array
            if( !empty($tienda_retiro) )
            {
                if( $tienda_retiro === $wc_pago_id )
                {
                    $pagos_arr_formatted[$wc_pago_id] = $bsale_pago_id;
                    break;
                }
                else
                {
                    continue;
                }
            }

            $pagos_arr_formatted[$wc_pago_id] = $bsale_pago_id;
        }

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($pagos_arr_formatted, "Utils->get_array_medios_envio(), resultado");
        }


        return $pagos_arr_formatted;
    }

    public function get_sucursal_retiro_tienda($order_id, $shipping_name)
    {
        $retiro_tienda_name = defined('RETIRO_TIENDA_STRING') ? trim(RETIRO_TIENDA_STRING) : '';
        $retiro_tienda_name = $this->filter_chars($retiro_tienda_name);
        $retiro_tienda_name = strtolower($retiro_tienda_name);

        if( empty($retiro_tienda_name) )
        {
            return -1;
        }
        //saco el $0
        $retiro_tienda_name = str_replace('$0', '', $retiro_tienda_name);
        $shipping_name = str_replace('$0', '', $shipping_name);
        $shipping_name = trim($shipping_name);
        $retiro_tienda_name = trim($retiro_tienda_name);

        //si este pedido no tiene envio
        if( empty($shipping_name) )
        {
            return -1;
        }

        //solo medios de envio que se llamen asçi
        if( strpos($retiro_tienda_name, $shipping_name) === false )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_sucursal_retiro_tienda('$shipping_name'), no es '$retiro_tienda_name', se omite."
                        . "Resultado comparación= " . strpos($retiro_tienda_name, $shipping_name));
            }
            return -1;
        }
        else
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_sucursal_retiro_tienda('$shipping_name'), sí  es '$retiro_tienda_name");
            }
        }

        $tienda_retiro = get_post_meta($order_id, '_shipping_pickup_stores', true);

        if( empty($tienda_retiro) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_sucursal_retiro_tienda($shipping_name), tienda de retiro '$tienda_retiro' vacía, se omite");
                return -1;
            }
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("get_sucursal_retiro_tienda($shipping_name), SÍ es 'retiro en tienda: $0', busco tienda asociada: '$tienda_retiro'");
        }

        $tienda_retiro = $this->filter_chars($tienda_retiro);
        $tienda_retiro = strtolower($tienda_retiro);

        //ahora que tengo la tienda de retiro, busco sucursal Bsale asociada
        return $tienda_retiro;
    }

    /**
     * implode un array multidimensional
     * @param type $delim
     * @param type $array_multi
     */
    public function implode_array($array_multi, $delim_indices = ',', $delim_rows = "\n")
    {
        $str = '';
        foreach( $array_multi as $arr )
        {
            if( is_array($arr) )
            {
                $str .= implode($delim_indices, $arr) . "$delim_rows";
            }
            else
            {
                $str .= $arr . "$delim_rows";
            }
        }

        return $str;
    }

    /**
     * string to slug
     * @param type $string
     * @return type
     */
    public function slugify($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }

    public function get_perfume_genero($perfume)
    {
        //genero por default 
        $genero = '';

        //es de hombre?
        $findme = '(H)';
        $pos = stripos($perfume, $findme);
        if( $pos !== false )
        {
            $genero = 'Hombre';
            return $genero;
        }
        //es de mujer
        $findme = '(M)';
        $pos = stripos($perfume, $findme);
        if( $pos !== false )
        {
            $genero = 'Mujer';
            return $genero;
        }
        //es unisex
        $findme = '(U)';
        $pos = stripos($perfume, $findme);

        if( $pos !== false )
        {
            $genero = 'Unisex';
            return $genero;
        }
        return $genero;
    }

    public function get_perfume_tipo($perfume)
    {
        //tipo por default 
        $tipo = '';
        $tipo_arr = array();

        $findme = 'Tester';
        $pos = stripos($perfume, $findme);
        if( $pos !== false )
        {
            $tipo_arr[] = 'Tester ';
        }

        $findme = 'Body Mist';
        $pos = stripos($perfume, $findme);
        if( $pos !== false )
        {
            $tipo_arr[] = 'Splash ';
        }

        $findme = 'Splash';
        $pos = stripos($perfume, $findme);

        if( $pos !== false )
        {
            $tipo_arr[] = 'Splash ';
        }

        $findme = 'Set';
        $pos = stripos($perfume, $findme);

        if( $pos !== false )
        {
            $tipo_arr[] = 'Set ';
        }

        $findme = '+';
        $pos = stripos($perfume, $findme);

        if( $pos !== false )
        {
            $tipo_arr[] = 'Set ';
        }

        $findme = 'estuche';
        $pos = stripos($perfume, $findme);

        if( $pos !== false )
        {
            $tipo_arr[] = 'Set ';
        }

        $findme = 'miniaturas';
        $pos = stripos($perfume, $findme);

        if( $pos !== false )
        {
            $tipo_arr[] = 'Set ';
        }


        $findme = 'Crema';
        $pos = stripos($perfume, $findme);

        if( $pos !== false )
        {
            $tipo_arr[] = 'Crema ';
        }
        //remuevo duplicados
        $tipo_arr_unique = array_unique($tipo_arr);
        //a string 
        $tipo = implode(', ', $tipo_arr_unique);

        return $tipo;
    }

    /**
     * saca tildes y eñes
     * @param type $string
     */
    public function filter_chars($cadena)
    {
        if(empty($cadena))
        {
            return $cadena;
        }
        //Reemplazamos la A y a
        $cadena = str_replace(
                array( 'Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª' ), array( 'A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a' ), $cadena
        );

        //Reemplazamos la E y e
        $cadena = str_replace(
                array( 'É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê' ), array( 'E', 'E', 'E', 'E', 'e', 'e', 'e', 'e' ), $cadena);

        //Reemplazamos la I y i
        $cadena = str_replace(
                array( 'Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î' ), array( 'I', 'I', 'I', 'I', 'i', 'i', 'i', 'i' ), $cadena);

        //Reemplazamos la O y o
        $cadena = str_replace(
                array( 'Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô' ), array( 'O', 'O', 'O', 'O', 'o', 'o', 'o', 'o' ), $cadena);

        //Reemplazamos la U y u
        $cadena = str_replace(
                array( 'Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û' ), array( 'U', 'U', 'U', 'U', 'u', 'u', 'u', 'u' ), $cadena);

        //Reemplazamos la N, n, C y c
        $cadena = str_replace(
                array( 'Ñ', 'ñ', 'Ç', 'ç', '\'', "’" ), array( 'N', 'n', 'C', 'c', '', '' ), $cadena
        );

        return $cadena;
    }

    /**
     * crea un csv y lo guarda en el file indicado
     * @param type $array_datos
     */
    public function csv_crear($array_datos, $file_output)
    {
        unset($array_datos['proveedor_items']);
        Funciones::print_r_html($array_datos, "csv_crear en '$file_output'");

        //"2.- csv, debo confirmar con el proveedor que va en el campo centro de costo. 
        //por ahora pon el nombre y teléfono de quien hace el pedido mas los comentarios del pedido."
        $centro_costo = 0; //siempre cero
        //direccion del comprador
        $direcion = $array_datos['client']['address'];
        $rut = $array_datos['client']['code'];

        $nombre_cliente = substr($array_datos['client']['firstName'] . ' '
                . $array_datos['client']['lastName'], 0, 40);
        $direccion2 = substr($direcion, 0, 125);

        $comuna = substr($array_datos['client']['municipality'], 0, 18);

        $ciudad = substr($array_datos['client']['city'], 0, 18);

        $fono = substr($array_datos['client']['phone'], 0, 13);

        //creo array para el csv
        $array_csv = array();

        //rut super facil, 3 cols by line
        // $linea = array( CSV_RUT_EMPRESA );
        //$array_csv[] = $linea;
        //centro de costo
        //$linea = array( $centro_costo );
        // $array_csv[] = $linea;
        //direccion de superfacil
        // $linea = array( $this->filter_chars(CSV_DIRECCION_EMPRESA) );
        // $array_csv[] = $linea;
        //hora
        //$linea = array( date('H:i:s') );
        // $array_csv[] = $linea;
        //marca y //datos del cliente
        //Nombre (40chrs), Dirección (125chrs), comuna (18chrs), ciudad (18chrs), telefono (13chrs)
        $linea = array( $rut, $nombre_cliente, $direccion2, $comuna, $ciudad, $fono );
        $array_csv[] = $linea;

        //Listado de productos
        foreach( $array_datos['items'] as $p )
        {
            $sku = $p['code'];
            $precio_neto = $p['netUnitValue'];
            $qty = $p['quantity'];
            $costo_unitario = $p['costo_unitario'];

            //add costo unitario a csv de respaldo, 14-09-2020
            $linea = array( $sku, $qty, $precio_neto, $costo_unitario );
            $array_csv[] = $linea;
        }

        //ahora, creo el csv
        $fp = fopen($file_output, 'w');

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($array_csv, "Utils->csv_crea() para file '$file_output', test dte no escriber archivo");
            return false;
        }

        foreach( $array_csv as $campos )
        {
            $campos2 = str_replace(',', ' ', $campos);
            $linea = implode(',', $campos2) . "\n";
            fwrite($fp, $linea);
            //fputcsv($fp, $campos);
        }

        fclose($fp);

        return true;
    }

    /**
     * crea un csv por proveedor y lo guarda en el file indicado
     * @param type $array_datos
     */
    public function csv_crear_proveedor($array_datos, $file_output)
    {
        $utils = new Utils();
        $arr_config = $utils->config_read();

        unset($array_datos['items']);
        Funciones::print_r_html($array_datos, "csv_crear_proveedor() en '$file_output'");

        //Listado de productos
        foreach( $array_datos['proveedor_items'] as $proveedor => $proveedor_items )
        {
            //detecto tipo de proveedor
            $is_proveedor_drop = $this->is_proveedor_drop($proveedor, $arr_config);
            $is_proveedor_no_drop = $this->is_proveedor_no_drop($proveedor, $arr_config);

            if( $is_proveedor_drop )
            {
                Funciones::print_r_html($proveedor_items, "csv_crear_proveedor() proveedor '$proveedor' es proveedor drop");
                $this->crear_file_proveedor_drop($array_datos, $proveedor, $file_output);
            }
            if( $is_proveedor_no_drop )
            {
                Funciones::print_r_html($proveedor_items, "csv_crear_proveedor() proveedor '$proveedor' es proveedor NO drop");
                $this->crear_file_proveedor_no_drop($array_datos, $proveedor, $file_output);
            }

            if( !$is_proveedor_drop && !$is_proveedor_no_drop )
            {
                Funciones::print_r_html($array_datos, "csv_crear_proveedor() proveedor '$proveedor' NO ES PROVEEDOR DROP NI 'NO DROP', se omite");
                continue;
            }
        }

        return true;
    }

    /**
     * crear archivo para proveedor drop
     * @param type $array_datos
     * @param type $proveedor
     * @param type $proveedor_items
     * @return boolean
     */
    public function crear_file_proveedor_drop($array_datos, $proveedor, $file_output)
    {
        $utils = new Utils();
        $arr_config = $utils->config_read();

        $proveedor_items = isset($array_datos['proveedor_items'][$proveedor]) ? $array_datos['proveedor_items'][$proveedor] : null;

        if( empty($proveedor_items) )
        {
            Funciones::print_r_html($proveedor_items, "crear_file_proveedor_drop() para proveedor '$proveedor', no items, no se hace nada");
            return false;
        }

        Funciones::print_r_html("crear_file_proveedor_drop() para proveedor '$proveedor'...");

        //direccion del comprador
        $direcion = $array_datos['client']['address'];
        $rut = $array_datos['client']['code'];

        $nombre_cliente = substr($array_datos['client']['firstName'] . ' '
                . $array_datos['client']['lastName'], 0, 40);
        $direccion2 = substr($direcion, 0, 125);

        $comuna = substr($array_datos['client']['municipality'], 0, 18);

        $ciudad = substr($array_datos['client']['city'], 0, 18);

        $fono = substr($array_datos['client']['phone'], 0, 13);

        //creo array para el csv
        $array_csv = array();

        $linea = array( $rut, $nombre_cliente, $direccion2, $comuna, $ciudad, $fono );
        $array_csv[] = $linea;

        $array_csv_proveedor = array();

        $file_output_p = $file_output;

        //recorro por proveedor
        foreach( $proveedor_items as $p )
        {
            $sku = $p['code'];
            $precio_neto = $p['netUnitValue'];
            $qty = $p['quantity'];
            //$proveedor = $p['proveedor'];
            $costo_unitario = $p['costo_unitario'];

            //sku, cant, costo uitario (no precio)          
            $linea = array( $sku, $qty, $costo_unitario );

            $array_csv_proveedor[] = $linea;
        }

        //lineas para el csv por proveedor
        $array_proveedor_full = array_merge($array_csv, $array_csv_proveedor);

        $folder_provedores = isset($arr_config['carpeta_proveedores']) ? $arr_config['carpeta_proveedores'] : dirname(__FILE__) . '/../../proveedores';

        //creo folder de proveedores, si este no existe
        //14-09-2020 add subfolder /pedidos/
        $folder_provedores = "$folder_provedores/$proveedor/pedidos/";

        if( !is_dir($folder_provedores) )
        {
            mkdir($folder_provedores, 0755, true);
        }

        $file_output_p = $folder_provedores . $file_output_p;

        $res = $this->crear_file_write_csv($file_output_p, $array_proveedor_full);

        return true;
    }

    /**
     * crear archivo para proveedor no drop
     * @param type $array_datos
     * @param type $proveedor
     * @param type $proveedor_items
     * @return boolean
     */
    public function crear_file_proveedor_no_drop($array_datos, $proveedor, $file_output)
    {
        $utils = new Utils();
        $arr_config = $utils->config_read();

        $proveedor_items = isset($array_datos['proveedor_items'][$proveedor]) ? $array_datos['proveedor_items'][$proveedor] : null;

        if( empty($proveedor_items) )
        {
            Funciones::print_r_html($proveedor_items, "crear_file_proveedor_no_drop() para proveedor '$proveedor', no items, no se hace nada");
            return false;
        }

        //linea byr
        $byr_linea = isset($arr_config['byr']) ? $arr_config['byr'] : '';
        //linea byr
        $byr_arr = explode(',', $byr_linea);

        Funciones::print_r_html("crear_file_proveedor_no_drop() para proveedor '$proveedor'...");

        //direccion del comprador
        $direcion = $array_datos['client']['address'];
        $rut = $array_datos['client']['code'];

        $nombre_cliente = substr($array_datos['client']['firstName'] . ' '
                . $array_datos['client']['lastName'], 0, 40);
        $direccion2 = substr($direcion, 0, 125);

        $comuna = substr($array_datos['client']['municipality'], 0, 18);

        $ciudad = substr($array_datos['client']['city'], 0, 18);

        $fono = substr($array_datos['client']['phone'], 0, 13);

        //creo array para el csv
        $array_csv = array();

        $linea = array( $rut, $nombre_cliente, $direccion2, $comuna, $ciudad, $fono );
        $array_csv[] = $linea;

        $array_csv_proveedor = array();
        //lleva byr/sku, cant, costo unitario
        $array_csv_centro_distribucion = array();

        if( !empty($byr_arr) )
        {
            $array_csv_centro_distribucion[] = $byr_arr;
        }

        $file_output_p = $file_output;

        //recorro por proveedor
        foreach( $proveedor_items as $p )
        {
            $sku = $p['code'];
            $precio_neto = $p['netUnitValue'];
            $qty = $p['quantity'];
            //$proveedor = $p['proveedor'];
            $costo_unitario = $p['costo_unitario'];

            //sku, cant, precio (no costo uitario)          
            $linea = array( $sku, $qty, $precio_neto );
            //para centro dictribucion
            $linea_cu = array( $sku, $qty, $costo_unitario );

            $array_csv_proveedor[] = $linea;
            $array_csv_centro_distribucion[] = $linea_cu;
        }

        //lineas para el csv por proveedor
        $array_proveedor_full = array_merge($array_csv, $array_csv_proveedor);
        $array_centro_distrib_full = array_merge($array_csv, $array_csv_centro_distribucion);

        $folder_provedores = isset($arr_config['carpeta_proveedores']) ? $arr_config['carpeta_proveedores'] : dirname(__FILE__) . '/../../proveedores';

        //proveedor, pedidos
        $folder_provedores_aux = "$folder_provedores/$proveedor/pedidos/";

        if( !is_dir($folder_provedores_aux) )
        {
            mkdir($folder_provedores_aux, 0755, true);
        }

        $file_output_paux = $folder_provedores_aux . $file_output_p;

        Funciones::print_r_html($array_centro_distrib_full, "crear_file_proveedor_no_drop() para proveedor '$proveedor' a carpeta '/nombre proveedor/pedidos/': BYR/ sku, cant, costo uitario en file: '$file_output_paux'");

        $res = $this->crear_file_write_csv($file_output_paux, $array_centro_distrib_full);

        return true;
    }

    /**
     * crear archivo de /pedidos/ y /ordenesdecompra/
     * BYR/SKU, CANT, COSTO UNITARIO    
     * @param type $array_datos
     * @param type $proveedor
     * @param type $file_output
     * @return boolean
     */
    public function crear_file_centro_dist($array_datos, $file_output)
    {
        unset($array_datos['items']);
        Funciones::print_r_html($array_datos, "crear_file_centro_distrubicion() en '$file_output'");

        $utils = new Utils();
        $arr_config = $utils->config_read();

        //linea byr
        $byr_linea = isset($arr_config['byr']) ? $arr_config['byr'] : '';
        //linea byr
        $byr_arr = explode(',', $byr_linea);

        //direccion del comprador
        $direcion = $array_datos['client']['address'];
        $rut = $array_datos['client']['code'];

        $nombre_cliente = substr($array_datos['client']['firstName'] . ' '
                . $array_datos['client']['lastName'], 0, 40);
        $direccion2 = substr($direcion, 0, 125);

        $comuna = substr($array_datos['client']['municipality'], 0, 18);

        $ciudad = substr($array_datos['client']['city'], 0, 18);

        $fono = substr($array_datos['client']['phone'], 0, 13);

        //creo array para el csv
        $array_csv = array();

        $linea = array( $rut, $nombre_cliente, $direccion2, $comuna, $ciudad, $fono );
        $array_csv[] = $linea;

        //lleva byr/sku, cant, costo unitario
        $array_csv_centro_distribucion = array();

        if( !empty($byr_arr) )
        {
            $array_csv_centro_distribucion[] = $byr_arr;
        }

        $file_output_p = $file_output;

        //Listado de productos
        foreach( $array_datos['proveedor_items'] as $proveedor => $proveedor_items )
        {
            $is_proveedor_no_drop = $this->is_proveedor_no_drop($proveedor, $arr_config);

            if( !$is_proveedor_no_drop )
            {
                Funciones::print_r_html("crear_file_centro_dist(), proveedor '$proveedor' se omite");
                continue;
            }

            Funciones::print_r_html("crear_file_centro_dist(), proveedor '$proveedor' sí es no drop, se agregan sus productos a csv");

            foreach( $proveedor_items as $p )
            {
                $sku = $p['code'];
                $precio_neto = $p['netUnitValue'];
                $qty = $p['quantity'];
                //$proveedor = $p['proveedor'];
                $costo_unitario = $p['costo_unitario'];

                //sku, cant, precio (no costo uitario)          
                $linea = array( $sku, $qty, $precio_neto );
                //para centro dictribucion
                $linea_cu = array( $sku, $qty, $costo_unitario );

                $array_csv_proveedor[] = $linea;
                $array_csv_centro_distribucion[] = $linea_cu;
            }
        }

        //lineas para el csv por proveedor
        $array_proveedor_full = array_merge($array_csv, $array_csv_proveedor);
        $array_centro_distrib_full = array_merge($array_csv, $array_csv_centro_distribucion);
        //sin datos del cliente
        $array_centro_distrib_ordenesdecompra = $array_csv_centro_distribucion;

        $folder_provedores = isset($arr_config['carpeta_proveedores']) ? $arr_config['carpeta_proveedores'] : dirname(__FILE__) . '/../../proveedores';
        $centro_distrib_name = isset($arr_config['centro_distribucion']) ? $arr_config['centro_distribucion'] : '';
        $centro_distrib_name = trim($centro_distrib_name);

        //agrego csv en  caprtea /prilogoc/pedidos/
        if( !empty($centro_distrib_name) )
        {

            $folder_provedores_aux = "$folder_provedores/$centro_distrib_name/pedidos/";

            if( !is_dir($folder_provedores_aux) )
            {
                mkdir($folder_provedores_aux, 0755, true);
            }

            $file_output_paux = $folder_provedores_aux . $file_output_p;

            Funciones::print_r_html($array_proveedor_full, "crear_file_proveedor_no_drop() para proveedor '$proveedor' a carpeta '/$centro_distrib_name/pedidos/': sku, cantt, precio (sin byr) en file: '$file_output_paux'");

            $res = $this->crear_file_write_csv($file_output_paux, $array_proveedor_full);
        }


        //creo files para centro de distribuc 
        //ordenes de compra
        $folder_provedores_aux = "$folder_provedores/$centro_distrib_name/ordenesdecompra/";

        if( !is_dir($folder_provedores_aux) )
        {
            mkdir($folder_provedores_aux, 0755, true);
        }

        $file_output_paux = $folder_provedores_aux . $file_output_p;

        Funciones::print_r_html($array_centro_distrib_full, "crear_file_proveedor_no_drop() para proveedor '$proveedor' a carpeta '/$centro_distrib_name/ordenesdecompra/': BYR/ sku, cant, costo uitario en file: '$file_output_paux'");

        $res = $this->crear_file_write_csv($file_output_paux, $array_centro_distrib_ordenesdecompra);

        return true;
    }

    /**
     * escriboe cvs en file indicado
     */
    public function crear_file_write_csv($file_output_p, $array_proveedor_full)
    {
        //saco // duplicados
        $file_output_p = preg_replace('#/+#', '/', $file_output_p);

        Funciones::print_r_html($array_proveedor_full, "Utils->crear_file_write_csv() para file '$file_output_p'");

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("Utils->crear_file_write_csv()request test dte, no se escribe file");
            return true;
        }

        //ahora, creo el csv
        $fp = fopen($file_output_p, 'w');

        foreach( $array_proveedor_full as $campos )
        {
            $campos2 = str_replace(',', ' ', $campos);
            $linea = implode(',', $campos2) . "\n";
            fwrite($fp, $linea);
            //fputcsv($fp, $campos);
        }

        fclose($fp);

        return true;
    }

    /**
     * devuelve true si el proveedor es dropshipping
     * @param type $proveedor_str
     * @param type $arr_config
     */
    public function is_proveedor_drop($proveedor_str, $arr_config = null)
    {
        if( $arr_config == null )
        {
            $arr_config = $this->config_read();
        }
        $proveedores_line = isset($arr_config['proveedores_drop']) ? trim($arr_config['proveedores_drop']) : '';

        if( empty($proveedores_line) )
        {
            return false;
        }

        //paso a array
        $proveedores_arr = explode(',', $proveedores_line);

        //busco proveedor
        foreach( $proveedores_arr as $p )
        {
            $p = trim($p);
            if( strcasecmp($proveedor_str, $p) == 0 )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * devuelve true si el proveedor NO es dropshipping
     * @param type $proveedor_str
     * @param type $arr_config
     */
    public function is_proveedor_no_drop($proveedor_str, $arr_config = null)
    {
        if( $arr_config == null )
        {
            $arr_config = $this->config_read();
        }
        $proveedores_line = isset($arr_config['proveedores_no_drop']) ? trim($arr_config['proveedores_no_drop']) : '';

        if( empty($proveedores_line) )
        {
            return false;
        }

        //paso a array
        $proveedores_arr = explode(',', $proveedores_line);

        //busco proveedor
        foreach( $proveedores_arr as $p )
        {
            $p = trim($p);
            if( strcasecmp($proveedor_str, $p) == 0 )
            {
                return true;
            }
        }
        return false;
    }

    public function config_set_defaults()
    {
        $folder = realpath(dirname(__FILE__) . '/../csv/');
        $arr = array( 'carpeta_ftp' => $folder, 'correlativo' => 1 );

        $this->config_save($arr);
    }

    /**
     * abre archivo de config y devuelve todo o al valor de la $key indicada
     * @param type $key
     */
    public function config_read($key = null)
    {
        $file = CONFIG_FILE;

        if( !file_exists($file) )
        {
            return null;
        }

        $json = file_get_contents($file);

        $json = trim($json);

        if( strlen($json) <= 1 )
        {
            return null;
        }

        $arr = json_decode($json, true);

        if( $key )
        {
            return isset($arr[$key]) ? trim($arr[$key]) : '';
        }
        else
        {
            return $arr;
        }
    }

    public function config_set($key, $value)
    {
        $arr = $this->config_read();

        $arr[$key] = trim($value);

        $this->config_save($arr);
    }

    public function config_save($arr)
    {
        $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $file = CONFIG_FILE;

        file_put_contents($file, $json);
    }

    /**
     * incrementa el correlativo en $add_value
     * @param type $add_value
     */
    public function config_increment($add_value = 1, $key = 'correlativo')
    {
        $arr = $this->config_read();

        $old = isset($arr[$key]) ? (int) $arr[$key] : 0;
        //incremento correlativo
        $new = $old + (int) $add_value;

        $arr[$key] = (int) $new;

        $this->config_save($arr);
    }

    public function normalize($string)
    {
        $table = array(
            'Š' => 'S', 'š' => 's', 'Ð' => 'Dj', 'd' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'C' => 'C', 'c' => 'c', 'C' => 'C', 'c' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'R' => 'R', 'r' => 'r', "'" => ''
        );

        return strtr($string, $table);
    }

    public function get_tipo_docto_nombre($tipo_docto)
    {
        switch( $tipo_docto )
        {
            case 'b':
                $tipo_docto_nombre = 'Boleta';
                break;
            case 'f':
                $tipo_docto_nombre = 'Factura';
                break;
            case 'nv':
                $tipo_docto_nombre = 'Nota de venta';
                break;
            case 'gd':
                $tipo_docto_nombre = 'Guia de despacho';
                break;
            case 'nc':
                $tipo_docto_nombre = 'Nota de credito';
                break;
            case 'nd':
                $tipo_docto_nombre = 'Nota de debito';
                break;
            case 'gp':
                $tipo_docto_nombre = 'Guia de salida';
                break;
            default:
                $tipo_docto_nombre = 'DTE';
                break;
        }
        return $tipo_docto_nombre;
    }

    // check if  string ends with specific sub-string
    public function has_file_extension($filename, $ext)
    {
        $length = strlen($ext);
        if( $length == 0 )
        {
            return true;
        }

        return (substr($filename, -$length) === $ext);
    }

    /**
     * lee los logs de /webhooks/logs
     * y los convierte en archivos json separados
     */
    public function logs_webhook_to_json()
    {
        $folder_path = dirname(__FILE__) . '/../webhooks/logs/';
        $folder_dest = dirname(__FILE__) . '/../webhooks/notificaciones/';

        $array_ext = array( 'log' );

        //leo archivos log
        // Open the directory  
        $handle = opendir($folder_path);

        if( $handle === false )
        {
            Funciones::print_r_html("logs_webhook_to_json('$folder_path'), no se pudo abrir directorio");
            return;
        }
        $path = $folder_path;

        // Loop through the directory  
        while( false !== ($file = readdir($handle)) )
        {
            // Check the file we're doing is actually a file  
            if( is_file($path . $file) )
            {
                $content = file_get_contents($path . $file);

                if( empty($content) )
                {
                    continue;
                }

                //creo archivos json y los dejo en carpeta notificaciones
                $this->create_json($content, $folder_dest);
            }
        }
    }

    public function create_json($content, $folder_dest)
    {
        $array_lines = explode("\n", $content);
        $i = 0;
        foreach( $array_lines as $l )
        {
            //si empieza con "{", es json
            if( substr($l, 0, 1) !== "{" )
            {
                continue;
            }
            //es linea con json
            $linea = trim($l);

            //guardo como archivo en folder dest
            $hoy = date("YmdHis");
            $filename = $folder_dest . "json_{$hoy}_{$i}.json";

            Funciones::print_r_html($linea, "json a file '$filename'");

            file_put_contents($filename, $linea);
            $i++;
        }
    }

    public function filter_client_bsale($client_array)
    {
        $arr = $client_array;

        //pais, para saber si debo validar o no
        $pais = Funciones::get_pais();

        //VALIDO RUT
        if( $pais === 'CL' )
        {
            $arr['code'] = $this->get_rut_formatted($client_array['code']);
            //si rut no válido
            $has_rut_valido = $this->valida_rut($arr['code']);

            $extranjero_pe = false;
        }
        elseif( $pais === 'PE' )
        {
            //en el caso de perú, si viene companyOrPerson=1, entonces el campo code es RUC, 
            $companyOrPerson = $client_array['companyOrPerson'];

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("filter cliente bsale: Perú");
            }
            //solo peru
            $extranjero_pe = false;

            //restricciones de perú: DNI=8 digitos
            //carnet de extranjería, 9 dígitos
            //perú, el dni siempre es válido
            $dni_pe = trim($client_array['code']);
            //saco puntos
            $dni_pe = str_replace('.', '', $dni_pe);

            //solo numeros
            if( !is_numeric($dni_pe) )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("DNI/RUC no valido: '$dni_pe' (es empresa: $companyOrPerson)");
                }
                $has_rut_valido = false;
            }
            //si es empresa
            elseif( $companyOrPerson == 1 )
            {
                $has_rut_valido = true;
            }
            else
            {
                $len = strlen($dni_pe);

                //DNI
                if( $len == 8 )
                {
                    $has_rut_valido = true;
                    $arr['code'] = $dni_pe;
                }
                //carnet de extranjeria
                elseif( $len == 9 )
                {
                    $has_rut_valido = false;
                    $arr['code'] = $dni_pe;
                    $extranjero_pe = true;
                }
                //no es dni ni carnet de extranjeria               
                else
                {
                    if( isset($_REQUEST['test_dte']) )
                    {
                        Funciones::print_r_html("DNI no valido, no tiene 8 ni 9 digitos: '$dni_pe' ");
                    }
                    $has_rut_valido = false;
                    $arr['code'] = $dni_pe;
                }
            }
        }
        //algun otro pais, por agregar
        else
        {
            $has_rut_valido = false;
        }

        //si estoy usando rut de pruebas, en fase de prueba
        /* if( defined('SET_RUT_PRUEBAS_DTE') && SET_RUT_PRUEBAS_DTE == true )
          {
          $arr['code'] = RUT_PRUEBAS_DTE;
          $has_rut_valido = true;
          } */

        //si es persona, no tiene giro
        if( $arr['companyOrPerson'] == 0 || $arr['companyOrPerson'] === '0' )
        {
            $arr['activity'] = '';
        }

        //si es empresa con rut no válido, no sirve enviar el del extranjero
        if( !$has_rut_valido &&
                ($arr['companyOrPerson'] == 1 || $arr['companyOrPerson'] === '1') )
        {
            //saco el rut, para que no se emita factura
            unset($arr['code']);
            unset($arr['email']);
            unset($arr['isForeigner']);
        }
        elseif( !$has_rut_valido && $extranjero_pe )
        {
            //dejo el codigo, pero lo marco como extranjero
            $arr['isForeigner'] = 1;
        }
        elseif( !$has_rut_valido )
        {
            //no envío rut, solo el isForeigner
            unset($arr['code']);
            $arr['isForeigner'] = 1;
        }

        return $arr;
    }

    public function filter_client_bsale_test($client_array)
    {

        $arr = $client_array;

        //si estoy usando rut de pruebas, en fase de prueba
        /* if( defined('SET_RUT_PRUEBAS_DTE') && SET_RUT_PRUEBAS_DTE == true )
          {
          $client_array['code'] = RUT_PRUEBAS_DTE;
          } */

        //VALIDO RUT
        $arr['code'] = $this->get_rut_formatted($client_array['code']);
        //si rut no válido
        $has_rut_valido = $this->valida_rut($arr['code']);

        //si es persona, no tiene giro
        if( $arr['companyOrPerson'] == 0 || $arr['companyOrPerson'] === '0' )
        {
            $arr['activity'] = '';
        }

        //si es empresa con rut no válido, no sirve enviar el del extranjero
        if( !$has_rut_valido &&
                ($arr['companyOrPerson'] == 1 || $arr['companyOrPerson'] === '1') )
        {
            //saco el rut, para que no se emita factura
            unset($arr['code']);
            unset($arr['email']);
            unset($arr['isForeigner']);
        }
        elseif( !$has_rut_valido )
        {
            //no envío rut, solo el isForeigner
            unset($arr['code']);
            $arr['isForeigner'] = 1;
        }


        return $arr;
    }

    /**
     * recibe un rut con o sin puntos y guin y lo devuelve sin puntos con guion
     * @param type $rut
     * @param type $separador_miles
     * @param type $separador_dv
     */
    public function get_rut_formatted($rut, $set_separador_dv = true)
    {
        if( empty($rut) )
        {
            return $rut;
        }
        if( defined('VALIDAR_RUT') && VALIDAR_RUT == false )
        {
            return $rut;
        }
        //saco separador de miles        
        $rut = str_replace('.', '', $rut);
        //saco guion
        $rut = str_replace('-', '', $rut);
        //coloc guion para dv        
        //tamaño del rut
        $len = strlen($rut);
        //extraigo substring para colocar guion y dv
        $rut_number = substr($rut, 0, $len - 1);
        $rut_dv = substr($rut, -1);

        /* if(isset($_REQUEST['param']) && $_REQUEST['param'] ==='yes')
          {
          Funciones::print_r_html("nuevo rut: '$rut_number-$rut_dv'");
          } */

        //tamaño minimo del rut: 12223335
        if( $len < 7 )
        {
            return $rut;
        }

        //viene con xxx-dv?
        $arrauxrut = explode('-', $rut);

        //coloco el -antes del ultimo caracter
        $rut_nro = substr($rut, 0, $len - 1);
        $dv = substr($rut, -1);
        $rut_nro = (int) $rut_nro;

        //debo devolver el rut con guin o no?
        if( $set_separador_dv )
        {
            $new_rut = $rut_nro . '-' . $dv;
        }
        else
        {
            $new_rut = $rut_nro . $dv;
        }

        return $new_rut;
    }

    public function valida_rut($rut)
    {
        if( defined('VALIDAR_RUT') && VALIDAR_RUT == false )
        {
            return $rut;
        }

        $rut = preg_replace('/[^k0-9]/i', '', $rut);
        $dv = substr($rut, -1);

        $numero = substr($rut, 0, strlen($rut) - 1);

        if( strlen($numero) < 6 )
        {
            return false;
        }

        $i = 2;
        $suma = 0;

        foreach( array_reverse(str_split($numero)) as $v )
        {
            if( $i == 8 )
            {
                $i = 2;
            }
            $suma += $v * $i;
            ++$i;
        }
        $dvr = 11 - ($suma % 11);

        if( $dvr == 11 )
        {
            $dvr = 0;
        }
        if( $dvr == 10 )
        {
            $dvr = 'K';
        }

        $dv = strtoupper($dv);

        $dvr = (string) $dvr;
        $dv = (string) $dv;

        if( $dvr === $dv )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * borrar acrchivos .log dejados por la integración
     * borra rows de tabla db con historial de actualización de stock/precio de productos
     */
    public function delete_old_logs()
    {
        //borro rows de historial sync productos
        $log_obj = new LogSincronizacionBsale();
        $log_obj->delete_historial();

        //logs de hostorial de dte de pedidos
        $log_dte = new LogDTEBsale();
        $log_dte->delete_historial();

        //giftcards: borro logs table
        /* $gift = new GiftFolios();
          $days_older = defined('DELETE_DAYS_OLDER') ? (int) DELETE_DAYS_OLDER : 60;
          $gift->clear_logs($days_older); */

        $hoy = (int) date('d');

        //listado de folders que contienen logs
        $array_folders = array(
            //logs de la raiz (webhooks de shopify, jumpseller, etc)
            dirname(__FILE__) . '/../logs/',
            //logs del webhook
            dirname(__FILE__) . '/../webhooks/logs/',
            //json procesados desde el webhook
            dirname(__FILE__) . '/../webhooks/notificaciones/',
            dirname(__FILE__) . '/../webhooks/procesados/',
            //product created
            dirname(__FILE__) . '/../webhooks/shop_products_fallidos/',
            dirname(__FILE__) . '/../webhooks/shop_products_pendientes/',
            dirname(__FILE__) . '/../webhooks/shop_products_procesados/',
            //orders de shopify
            dirname(__FILE__) . '/../webhooks/shop_gd_procesadas/',
            dirname(__FILE__) . '/../webhooks/shopify_gd_orders/',
            dirname(__FILE__) . '/../webhooks/shopify_orders/',
            dirname(__FILE__) . '/../webhooks/shop_orders_cancelled_procesadas/',
            dirname(__FILE__) . '/../webhooks/shop_nv_procesadas/',
            dirname(__FILE__) . '/../webhooks/shop_boletas_procesadas/',
            dirname(__FILE__) . '/../webhooks/shop_boletas_fallidas/',
            dirname(__FILE__) . '/../webhooks/js_orders_cancelled_procesadas/',
            //linio
            dirname(__FILE__) . '/../webhooks/linio_prods_pendientes/',
            dirname(__FILE__) . '/../webhooks/linio_prods_procesados/',
            dirname(__FILE__) . '/../webhooks/linio_orders_pendientes/',
            dirname(__FILE__) . '/../webhooks/linio_orders_procesadas/',
            dirname(__FILE__) . '/../webhooks/linio_orders_fallidas/',
            dirname(__FILE__) . '/../webhooks/linio_orders/',
            //logs del lib (boletas emitidas, logs de conexiones a las tiendas)
            dirname(__FILE__) . '/logs/',
        );
        $array_ext = array( 'log', 'json' );
        $days_older = Funciones::get_days_delete_logs(); //

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " borro archivo con más de $days_older días de antiguedad");
        }

        //borro logs viejos
        foreach( $array_folders as $f )
        {
            $this->delete_files_older_than($f, $array_ext, $days_older);
        }
    }

    public function delete_files_older_than($folder_path, $array_ext, $days_older)
    {
        $days = $days_older;
        $path = $folder_path; //'./logs/';
        $filetypes_to_delete = $array_ext;

        $i = 0;

        if( !file_exists($path) )
        {
            return;
        }

        // Open the directory  
        $handle = opendir($path);

        if( $handle === false )
        {
            //Funciones::print_r_html("delete_files_older_than('$folder_path'), no se pudo abrir directorio");
            return;
        }
        // Loop through the directory  
        while( false !== ($file = readdir($handle)) )
        {
            // Check the file we're doing is actually a file  
            if( is_file($path . $file) )
            {
                $file_info = pathinfo($path . $file);
                if( isset($file_info['extension']) && in_array(strtolower($file_info['extension']), $filetypes_to_delete) )
                {
                    // Check if the file is older than X days old  
                    if( filemtime($path . $file) < ( time() - ( $days * 24 * 60 * 60 ) ) )
                    {
                        //Funciones::print_r_html("borro archivo '{$path}{$file}'...");
                        // Do the deletion  
                        unlink($path . $file);
                        $i++;
                    }
                }
            }
        }
        if( isset($_REQUEST['param']) && $i > 0 )
        {
            Funciones::print_r_html("delete_files_older_than( desde folder '$folder_path') total de archivos borrados: $i");
        }
    }

    function print_r_reverse($in)
    {
        $lines = explode("\n", trim($in));
        if( trim($lines[0]) != 'Array' )
        {
            // bottomed out to something that isn't an array
            return $in;
        }
        else
        {
            // this is an array, lets parse it
            if( preg_match("/(\s{5,})\(/", $lines[1], $match) )
            {
                // this is a tested array/recursive call to this function
                // take a set of spaces off the beginning
                $spaces = $match[1];
                $spaces_length = strlen($spaces);
                $lines_total = count($lines);
                for( $i = 0; $i < $lines_total; $i++ )
                {
                    if( substr($lines[$i], 0, $spaces_length) == $spaces )
                    {
                        $lines[$i] = substr($lines[$i], $spaces_length);
                    }
                }
            }
            array_shift($lines); // Array
            array_shift($lines); // (
            array_pop($lines); // )
            $in = implode("\n", $lines);
            // make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one)
            preg_match_all("/^\s{4}\[(.+?)\] \=\> /m", $in, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            $pos = array();
            $previous_key = '';
            $in_length = strlen($in);
            // store the following in $pos:
            // array with key = key of the parsed array's item
            // value = array(start position in $in, $end position in $in)
            foreach( $matches as $match )
            {
                $key = $match[1][0];
                $start = $match[0][1] + strlen($match[0][0]);
                $pos[$key] = array( $start, $in_length );
                if( $previous_key != '' )
                    $pos[$previous_key][1] = $match[0][1] - 1;
                $previous_key = $key;
            }
            $ret = array();
            foreach( $pos as $key => $where )
            {
                // recursively see if the parsed out value is an array too
                $ret[$key] = $this->print_r_reverse(substr($in, $where[0], $where[1] - $where[0]));
            }
            return $ret;
        }
    }

    public function do_offset($level)
    {
        $offset = "";             // offset for subarry 
        for( $i = 1; $i < $level; $i++ )
        {
            $offset = $offset . "<td></td>";
        }
        return $offset;
    }

    public function show_array($array, $level, $sub)
    {
        if( is_array($array) == 1 )
        {          // check if input is an array
            foreach( $array as $key_val => $value )
            {
                $offset = "";
                if( is_array($value) == 1 )
                {   // array is multidimensional
                    if( is_numeric($key_val) )
                        echo "<tr style='font-weight:bold;background-color:#EEEEEE;'>";
                    else
                        echo "<tr>";
                    $offset = $this->do_offset($level);
                    /* if ( is_numeric( $key_val ) )
                      echo $offset . "<td style='font-weight:bold;background-color:#EEEEEE;'>" . $key_val . "</td>";
                      else */
                    echo $offset . "<td style='font-weight:bold;background-color:#EEEEEE;'>" . $key_val . "</td>";

                    $this->show_array($value, $level + 1, 1);
                }
                elseif( is_object($value) )
                {
                    echo('<pre>');
                    print_r($value);
                    echo('</pre>');
                }
                else
                {                        // (sub)array is not multidim
                    if( $sub != 1 )
                    {          // first entry for subarray
                        echo "<tr nosub>";
                        $offset = $this->do_offset($level);
                    }
                    $sub = 0;
                    echo $offset . "<td main " . $sub . " width=\"120\">" . $key_val .
                    "</td><td width=\"120\">" . $value . "</td>";
                    echo "</tr>\n";
                }
            } //foreach $array
        }
        else
        { // argument $array is not an array
            return;
        }
    }

    public function html_show_array($array)
    {

        echo "\n<table cellspacing=\"0\" border=\"2\">\n";
        $this->show_array($array, 1, 0);
        echo "\n</table>\n";
    }

    public function sendEmail($to_email = null, $subject = null, $message = null, $cc_email = null)
    {
        if( empty($to_email) )
        {
            return;
        }
        //si es que se puede enviar correo o no
        $f = dirname(__FILE__) . "/PHPMailer/PHPMailerAutoload.php";

        if( !class_exists('PHPMailer') && file_exists($f) )
        {
            require_once $f;
        }
        else
        {
            echo("sendEmail: class PHPMailer not found in '$f'");
            return false;
        }

        //valido email
        /* if( empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL) )
          {
          $emailErr = $to_email;
          $to_email = EMAIL_ERROR;
          $subject = "$subject";

          $message = "<p>Se intenta enviar boleta a email no valido: '$emailErr'</p>\n"
          . "$message";
          return false;
          } */

        if( !defined('EMAIL_USER') || EMAIL_USER === '' )
        {
            echo('sendEmail: falta EMAIL_USER');
            return;
        }

        $mail = new PHPMailer();

        //$mail->CharSet = 'iso-8859-1';

        $from_name = 'Integracion Bsale';
        $from_email = EMAIL_USER;

        $mail->IsSMTP(); //ok

        $mail->SMTPAuth = true; //ok
        $mail->Username = EMAIL_USER; //ok
        $mail->Password = EMAIL_PASS; //ok

        $mail->SMTPSecure = EMAIL_SMTP_SECURE; //ok
        /* Set the SMTPSecure value, if set to none, leave this blank */
        /*  if ( $swpsmtp_options['smtp_settings']['type_encryption'] !== 'none' )
          {
          $mail->SMTPSecure = $swpsmtp_options['smtp_settings']['type_encryption'];
          } */

        /* Set the other options */
        $mail->Host = EMAIL_SERVER; //ok
        $mail->Port = EMAIL_SMTP_PORT; //ok
        $mail->SetFrom($from_email, $from_name); //ok
        $mail->isHTML(true); //ok
        $mail->Subject = $subject; //ok
        $mail->MsgHTML($message);
        $mail->addAddress($to_email); //ok	
        if( defined('EMAIL_MESSAGES') && EMAIL_MESSAGES !== '' )
        {
            $mail->addAddress(EMAIL_MESSAGES);
        }
        $mail->SMTPDebug = EMAIL_SMTP_DEBUG;

        if( defined('EMAIL_TEST_CC') && EMAIL_TEST_CC !== '' )
        {
            $mail->addCC(EMAIL_TEST_CC); //para ver si llegan las boletas, luego lo saco
        }
        if( !empty($cc_email) )
        {
            $mail->addCC($cc_email);
        }


        /* $mail->addReplyTo('','');

          $mail->addBCC(''); */

        /* Send mail and return result */
        if( !$mail->Send() )
        {
            $errors = $mail->ErrorInfo;
        }

        $mail->ClearAddresses();
        $mail->ClearAllRecipients();

        if( !empty($errors) )
        {
            //Funciones::print_r_html($errors, "sendEmail: error en envio de email");
            return $errors;
        }
        else
        {
            return true;
        }
    }
}
