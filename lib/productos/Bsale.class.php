<?php

require_once dirname(__FILE__) . '/../Autoload.php';
//use woocommerce_bsalev2\lib\wp\WoocommerceBsale;

/**
 * Description of Bsale
 *
 * @author Lex
 */
class Bsale
{

    /**
      {
      "cpnId":2,
      "resource":"/v2/products/952.json",
      "resourceId":"952",
      "topic":"product",
      "action":"post",  Identifica el tipo de acción en este caso son posible sólo acciones POST=add y PUT=update
      "send":1503500856
      }
     * @param type $data
     */
    public function do_product($data, $get_variants = true, $show = false, $send_to_do_variant = false)
    {
        $empresa = isset($data['cpnId']) ? $data['cpnId'] : null;
        $resource = isset($data['resource']) ? $data['resource'] : null;
        $resourceId = isset($data['resourceId']) ? $data['resourceId'] : null;
        $topic = isset($data['topic']) ? $data['topic'] : null;
        $action = isset($data['action']) ? $data['action'] : null;
        $send = isset($data['send']) ? $data['send'] : null;

        $prod = new ProductoBsale();
        //datos de producto, variante, precio y stock
        $producto_full_data = array();

        $producto_data = $prod->get_producto($resourceId);
        $product_type_id = isset($producto_data['product_type']['id']) ? (int) $producto_data['product_type']['id'] : -1;

        //estado 1 = deshabilitado
        if( !isset($producto_data['state']) || $producto_data['state'] == 1 )
        {
            return false;
        }

        //prodcuto tiene el tipo permitido?
        $product_types_arr = Funciones::get_bsale_product_type_allowed();

        //si solo se deben sinc stok para ciertos tipos de productos y este no está dentro de los permitidos, se omite
        if( is_array($product_types_arr) && count($product_types_arr) > 0 && !in_array($product_type_id, $product_types_arr) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($product_types_arr, "producto id=$resourceId es de tipo '$product_type_id', no está entre los tipos permitidos, se omite");
            }
            return false;
        }

        $producto_full_data['id'] = $resourceId;
        $producto_full_data['name'] = ($producto_data['name']);
        $producto_full_data['description'] = /* utf8_encode */($producto_data['description']);
        $producto_full_data['pack_details'] = isset($producto_data['pack_details']) ? $producto_data['pack_details'] : null;
        //indica la clase del producto 0 es producto, 1 es servicio y 3 si es un pack o promocion(Integer).
        $producto_full_data['classification'] = isset($producto_data['classification']) ? (int) $producto_data['classification'] : 0;

        if( $producto_full_data['classification'] == 1 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("producto id=$resourceId es servicio, se omite");
            }
            return false;
        }


        if( isset($producto_data['pack_details']) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("producto id=$resourceId es pack");
            }
        }
        //agrego tipo prod como vendor?
        if( defined('INCLUDE_TIPO_PRODUCTO_AS_VENDOR') && INCLUDE_TIPO_PRODUCTO_AS_VENDOR == true )
        {
            //get el type id de este producto
            $type_producto_id = isset($producto_data['product_type']['id']) ? $producto_data['product_type']['id'] : 0;

            if( $type_producto_id > 0 )
            {
                $type_data = $prod->get_producto_type_from_table($type_producto_id);
                //si viene tipo o no
                $type_name = isset($type_data['name']) ? $type_data['name'] : '';
                //"sin tipo", no se escribe 
                if( strcasecmp($type_name, 'sin tipo') == 0 )
                {
                    $type_name = '';
                }
                $producto_full_data['vendor'] = $type_name;
                //append al nombre del prod
                //$producto_full_data['name'] = $type_name . ' ' . $producto_full_data['name'];
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("add product type como vendor: tipo= '$type_name' para prod id=$resourceId, "
                            . $producto_full_data['name']);
                }
            }
            else
            {
                $producto_full_data['vendor'] = null;
            }
        }
        //INCLUDE_NOMBRE_PRODUCTO_AS_VENDOR
        //agrego nombre prod como vendor?
        if( defined('INCLUDE_NOMBRE_PRODUCTO_AS_VENDOR') && INCLUDE_NOMBRE_PRODUCTO_AS_VENDOR == true )
        {
            $producto_full_data['vendor'] = $producto_full_data['name'];
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("agrego nombre producto como vendor: " . $producto_full_data['vendor']);
            }
        }

        if( $get_variants )
        {
            //variantes del producto
            $variantes_prod = $prod->get_variantes_producto($resourceId);

            $variantes = array();
            //recorro las variantes y las voy guardando en un array
            foreach( $variantes_prod as $v )
            {
                $aux = array();
                $aux['id'] = $v['id'];
                $aux['description'] = ($v['description']);
                $aux['code'] = $v['code'];
                $aux['barcode'] = $v['barCode'];

                $arraux = array( 'resourceId' => $v['id'], 'cpnId' => $data['cpnId'],
                    'topic' => 'variant', 'action' => 'get', 'send' => $data['send'], 'resource' => '' );
                //full data variant
                $full_variant = $this->do_variant($arraux, true, false);

                $variantes[] = $full_variant;
            }
            $producto_full_data['variants'] = $variantes;
        }

        if( $show )
        {
            //Funciones::print_r_html($producto_data, "do_product from Bsale $resourceId");
            //Funciones::print_r_html($variantes, "do_product, variantes $resourceId");
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($producto_full_data, "do_product, full data");
            }
        }
        //enviar a do variant
        if( $send_to_do_variant )
        {
            foreach( $producto_full_data['variants'] as $v )
            {
                $data = array();
                $data['cpnId'] = $empresa;
                $data['resourceId'] = $v['variant_id'];
                $data['resource'] = '/v2/variants/' . $data['resourceId'];
                $data['topic'] = 'variant';
                $data['action'] = $action;
                $data['send'] = $send;

                $this->do_variant($data, true, true);
            }
            return true;
        }

        return $producto_full_data;
    }

    public function do_document($data, $get_variants = true, $show = false)
    {
        $empresa = isset($data['cpnId']) ? $data['cpnId'] : null;
        $resource = isset($data['resource']) ? $data['resource'] : null;
        $resourceId = isset($data['resourceId']) ? $data['resourceId'] : null;
        $topic = isset($data['topic']) ? $data['topic'] : null;
        $action = isset($data['action']) ? $data['action'] : null;
        $send = isset($data['send']) ? $data['send'] : null;

        $doc = new Documento();
        //obtengo variaciones
        $result = $doc->getDetailsIdDocto($resourceId);
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($result, "do_document id=$resourceId");
        }
        //recorro las variaciones y obtengo su id
        //$var = new VariantesProductoBsale();
        $data2 = $data;

        $data2['topic'] = 'variant';

        foreach( $result as $v )
        {
            if( empty($v['sku']) || empty($v['variant_id']) )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($v, "do_document id=$resourceId, variant no existe o no tiene datos, se omite");
                }
                continue;
            }
            $sku = $v['sku'];
            $variant_id = $v['variant_id'];
            /*
              $variacion = $var->get_variacion_by_sku($sku);

              Funciones::print_r_html($variacion, "get_variacion_by_sku($sku)");

              if( !isset($variacion['id']) )
              {
              continue;
              }

              $data2['resourceId'] = $variacion['id']; */

            $data2['resourceId'] = $variant_id;
            //envio aviso para actualizar variacion
            $this->do_variant($data2, true, true);
            sleep(1);
        }
        return $result;
    }

    /**
      {
      "cpnId":2,
      "resource":"/v2/variants/7079.json",
      "resourceId":"7079",
      "topic":"variant",
      "action":"post", Identifica el tipo de acción en este caso son posible sólo acciones POST=add y PUT=update
      "send":1503500856
      }
     * @param type $data
     */
    public function do_variant($data, $get_product = true, $show = false)
    {

        $empresa = $data['cpnId'];
        $resource = $data['resource'];
        $resourceId = $data['resourceId'];
        $topic = $data['topic'];
        $action = $data['action'];
        $send = $data['send'];

        //datos de producto, variante, precio y stock
        $producto_full_data = array();

        $prod = new ProductoBsale();

        $include_cost = defined('BSALE_INCLUDE_VARIANT_COST') ? BSALE_INCLUDE_VARIANT_COST : false;

        $variante_data = $prod->get_variante($resourceId, $include_cost);

        //state=1 es deshabilitado
        if( !isset($variante_data['code']) || empty($variante_data['code']) || $variante_data['state'] == 1 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($variante_data, "do_variant, variante id=$resourceId no tiene sku "
                        . "o está deshabilitada ({$variante_data['state']}), se omite.");
            }
            return false;
        }
        //tiene sku to skip?
        $skus_to_skip = Funciones::get_value('SKUS_SKIP_INTEGRACION_STOCK', null);

        if( !empty($skus_to_skip) && stripos($variante_data['code'], $skus_to_skip) !== false )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("do_variant, variante id=$resourceId sku=({$variante_data['code']}) "
                        . "tiene sku a omitir. Se omite.");
            }
            return false;
        }

        //datos de la variante
        $producto_full_data['variant_id'] = $resourceId;
        $producto_full_data['variant_description'] = $variante_data['description'];
        //empty($variante_data['description']) ? $resourceId : $variante_data['description'];
        $producto_full_data['variant_code'] = trim($variante_data['code']);
        $producto_full_data['variant_barcode'] = trim($variante_data['barCode']);

        $producto_full_data['variant_description'] = /* utf8_encode */($producto_full_data['variant_description']);

        //get stock de variante en cada sucursal
        $sku = $variante_data['code'];

        //costo promedio de la variacion
        $producto_full_data['variant_average_cost'] = isset($variante_data['costs_variation']['averageCost']) ?
                $variante_data['costs_variation']['averageCost'] * Funciones::get_valor_iva() : -1;
        $producto_full_data['variant_average_cost'] = (int) $producto_full_data['variant_average_cost'];

        $arraux = array( 'resourceId' => $resourceId, 'code' => $sku );
        $variant_stock = $this->do_stock($arraux, false, false);

        $producto_full_data['variant_stock'] = $variant_stock;

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($variante_data, "do variant, datos variacion");
        }

        //unlimitedStock, indica si la variante posee stock ilimitado No(0) o Si (1) (Boolean).
        if( isset($variante_data['unlimitedStock']) && $variante_data['unlimitedStock'] == 1 )
        {
            //en este caso, se le asigna un stock muy alto
            $producto_full_data['variant_stock']['variant_total_stock'] = Funciones::get_stock_ilimitado_prod_bsale();

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("do variant sku='$sku', unlimitedStock se deja con stock ilimitado");
            }
        }
        elseif( /* isset($variante_data['allowNegativeStock']) && $variante_data['allowNegativeStock'] == 1 && */
                $producto_full_data['variant_stock']['variant_total_stock'] < 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("do variant sku='$sku' allowNegativeStock y tiene stock <0 = "
                        . $producto_full_data['variant_stock']['variant_total_stock'] . ", se deja con stock=0");
            }
            $producto_full_data['variant_stock']['variant_total_stock'] = 0;
        }
        //precio de la variante
        $variante_precio = $this->do_price($arraux, false, false);

        $producto_full_data['variant_price'] = $variante_precio;

        //busco datos del producto
        if( $get_product && isset($variante_data['product']['id']) )
        {
            $producto_id = $variante_data['product']['id'];

            $arraux_data = array( 'resourceId' => $producto_id );
            $producto_data = $this->do_product($arraux_data, false, false);

            if( $producto_data === false )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("producto es false, se omite variant");
                }
                return false;
            }
            $producto_full_data['product'] = $producto_data;

            //veo si el prodcuto padre es un pack para ajustar el stock de la variante, en caso de que sea cero
            //variante tiene stock=0?
            if( !empty($producto_full_data['product']['pack_details']) /* $producto_full_data['variant_stock']['variant_total_stock'] <= 0 */ )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("prod id=$producto_id producto_full_data['product']['pack_details'] es pack, voy a buscar stock");
                }
                $stock_pack = $this->get_stock_pack($producto_full_data['product']);

                if( $stock_pack !== false )
                {
                    $producto_full_data['variant_stock']['variant_total_stock'] = $stock_pack;
                    //marco que esta variante es de un producto pack y que su stock es de pack
                    $producto_full_data['variant_stock']['variant_es_pack'] = 1;
                }
            }
        }

        //productos "despacho" no se envian
        if( strcasecmp($producto_full_data['variant_description'], 'despacho') == 0 || strcasecmp($producto_full_data['product']['name'], 'despacho') == 0 )
        {
            if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' )
            {
                Funciones::print_r_html($producto_full_data, "do_variant, full data para " . INTEGRACION_SISTEMA . " es despacho, se omite");
            }
            return false;
        }


        if( $show )
        {
            //Funciones::print_r_html($variante_data, "do_variant $resourceId");
            //Funciones::print_r_html($producto_data, "do_variant, producto padre: $producto_id");
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($producto_full_data, "do_variant, full data para " . INTEGRACION_SISTEMA);
            }
        }

        if( Funciones::is_enabled_integ_inventario() != true )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html('do_variant, inventario not enabled');
            }
            return false;
        }

        // return $producto_full_data;
        //según la constante, veo donde actualizar
        switch( INTEGRACION_SISTEMA )
        {
            case 'shopify':

                $shop = new Shopify();
                $result = $shop->sync_variacion_shopify($producto_full_data);
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($result, "do_variant $resourceId, hacia " . INTEGRACION_SISTEMA);
                }

                break;
            case 'woocommerce':

                $wc = new WoocommerceBsale();
                $result = $wc->sync_variacion_wc($producto_full_data);
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($result, "do_variant '$resourceId', hacia " . INTEGRACION_SISTEMA);
                }

                break;

            default :
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($result, "do_variant $resourceId, ERROR: '" . INTEGRACION_SISTEMA . "' no se reconoce. No se hace nada.");
                }
                return null;
        }

        return $producto_full_data;
    }

    /**
     * {
      "cpnId":2,
      "resource":"/v2/price_lists/2/details.json?variant=7079",
      "resourceId":"7079",
      "topic":"price",
      "action":"put", este caso sólo existen notificaciones de actualizaciones de precio, por lo que la única acción notificada será PUT
      "officeId":"2",
      "send":1503500856
      }
     * @param type $data
     * @param type $show
     */
    public function do_price($data, $get_variant = true, $show = false)
    {
        //$empresa = $data['cpnId'];
        //$resource = $data['resource'];
        $resourceId = $data['resourceId'];
        //$topic = $data['topic'];
        //$action = $data['action'];
        // $send = $data['send'];
        $sku = isset($data['code']) ? $data['code'] : null;

        //busco datos de la variante 
        $prod = new ProductoBsale();
        //si no viene sku en los parametros, debo obtenerlo
        if( $get_variant || empty($sku) )
        {
            $variante_data = $prod->get_variante($resourceId, false, false);
            $sku = isset($variante_data['code']) ? $variante_data['code'] : null;
        }
        //si no se encontró sku para esta variante
        if( empty($sku) )
        {
            return null;
        }
        //precio normal
        if( Funciones::is_update_product_price() )
        {
            $variante_precio = $prod->get_precio_producto($sku, null, $resourceId);
        }
        else
        {
            $variante_precio = -1;
        }

        $variante_precio_descto1 = 0;
        $variante_precio_descto2 = $variante_precio_descto3 = 0;

        //Funciones::print_r_html($data, "do price");
        //precio descto
        if( Funciones::is_update_product_price_desc() )
        {
            $lp_oferta_id = Funciones::get_lp_oferta_bsale();
        }
        else
        {
            $lp_oferta_id = -1;
        }
        if( $lp_oferta_id > 0 )
        {
            $variante_precio_descto1 = $prod->get_precio_producto($sku, $lp_oferta_id, $resourceId);
        }

        $lp_mayorista_precio_normal = Funciones::get_lp_mayorista_normal_bsale();
        //precio descto 2 (precio normal mayorista)
        if( $lp_mayorista_precio_normal > 0 )
        {
            $variante_precio_descto2 = $prod->get_precio_producto($sku, $lp_mayorista_precio_normal, $resourceId);
        }

        $lp_mayorista_precio_oferta = Funciones::get_lp_mayorista_oferta_bsale();
        //precio descto 2 (precio descto mayorista)
        if( $lp_mayorista_precio_oferta > 0 )
        {
            $variante_precio_descto3 = $prod->get_precio_producto($sku, $lp_mayorista_precio_oferta, $resourceId);
        }

        $arr = array();
        $arr['price'] = $variante_precio;
        $arr['price_desc'] = $variante_precio_descto1;
        $arr['price_desc2'] = $variante_precio_descto2;
        $arr['price_desc3'] = $variante_precio_descto3;

        if( $get_variant )
        {
            $arr['variant'] = $variante_data;
        }

        if( $show )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($arr, "do_price, full data, variante $resourceId");
            }
        }

        return $arr;
    }

    /**
     * {
      "cpnId":2,
      "resource":"/v2/stocks.json?variant=7079&office=1",
      "resourceId":"7079",
      "topic":"stock",
      "action":"put",en este caso sólo existen notificaciones de actualizaciones de Stock, por lo que la única acción notificada será PUT
      "officeId":"1",
      "send":1503500856
      }
     * @param type $data
     * @param type $show
     */
    public function do_stock($data, $get_variant = true, $show = false)
    {
        //$empresa = $data['cpnId'];
        //$resource = $data['resource'];
        $resourceId = $data['resourceId'];
        //$topic = $data['topic'];
        //$action = $data['action'];
        // $send = $data['send'];
        $sku = isset($data['code']) ? trim($data['code']) : null;

        //busco datos de la variante 
        $prod = new ProductoBsale();
        //si no viene sku en los parametros, debo obtenerlo
        if( $get_variant || empty($sku) )
        {
            $variante_data = $prod->get_variante($resourceId, false, false);
            $sku = isset($variante_data['code']) ? $variante_data['code'] : null;
        }
        //si no se encontró sku para esta variante
        if( empty($sku) )
        {
            return null;
        }
        $total_stock = 0;

        //obtengo datos del stock
        //listado de sucursales, la primera es la matriz
        $otras_sucursales = Funciones::get_sucursales_bsale();

        $sucursales_array = array_merge(array( Funciones::get_matriz_bsale() ), $otras_sucursales);

        $sucursales_array = array_unique($sucursales_array);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($sucursales_array, "busco stock desde sucursales. ");
        }

        //stock de otras sucursales
        $variante_stock = array();
        $sucursales_stock = array();
        $has_zero = false; //si hay que sumar stocks y al menos hay un stock -1 pero hay otros con stock=0, se deja el stock=0

        foreach( $sucursales_array as $sucursal_id )
        {
            if( empty($sucursal_id) )
            {
                continue;
            }
            $stock_sucursal = $prod->get_stock_producto($sku, $sucursal_id, $resourceId);

            if( $stock_sucursal < 0 )
            {
                $stock_sucursal = 0;
            }

            $porcent = Funciones::get_value('BSALE_WC_PERCENT_STOCK', null);

            //colocar en wc solo un porcentaje del stock de bsale
            if( $porcent )
            {
                //si es un valor, 
                $porcent_dec = ($porcent > 0 && $porcent < 100) ? $porcent / 100 : 1;

                $stock_sucursal_new = (int) ($stock_sucursal * $porcent_dec);

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("get_stock_sucursal_html, sku='$sku', dejar % de stock. Stock orig: $stock_sucursal "
                            . "porcent a dejar: %$porcent, nuevo stock $stock_sucursal_new");
                }
                $stock_sucursal = $stock_sucursal_new;
            }

            $sucursales_stock[$sucursal_id] = $stock_sucursal;

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_stock_sucursal_html, sku='$sku', sucursal bsale='$sucursal_id', stock='$stock_sucursal' ");
            }
            //sumo total stock 
            if( $stock_sucursal > 0 )
            {
                $total_stock += $stock_sucursal;
            }

            if( $stock_sucursal == 0 )
            {
                $has_zero = true;
            }
        }

        //ajuste total stock = 0 aunque enuna sucursal no haya stock (-1)
        if( $total_stock < 0 && $has_zero )
        {
            $total_stock = 0;
        }

        //$total_stock = ($total_stock < 0) ? 0 : $total_stock;

        $variante_stock['variant_total_stock'] = $total_stock;
        $variante_stock['variant_stock_sucursal'] = $sucursales_stock;

        if( $get_variant )
        {
            $variante_stock['variant'] = $variante_data;
        }

        if( $show )
        {
            
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($variante_stock, "do_stock, full data, variante $resourceId");
        }

        return $variante_stock;
    }

    /**
     * devuelve un arreglo con stock de todas las sucursales
     * @param type $sku
     */
    public function get_stock_por_sucursal($sku, $all_sucursales = false)
    {
        $sucursales = new SucursalesBsale();
        $prod_obj = new ProductoBsale();

        $arr = array();

        $sucursales_arr = $sucursales->get_all_sucursales();

        if( empty($sucursales_arr) )
        {
            return $arr;
        }

        if( !$all_sucursales && BSALE_SUCURSAL_ALL_SUCURSALES_ID == false )
        {
            //solo stock de sucursales indicadas
            $otras_sucursales = Funciones::get_sucursales_bsale();

            $sucursales_array = array_merge(array( Funciones::get_matriz_bsale() ), $otras_sucursales);
        }
        else
        {
            //stock de todas las sucursales
            $sucursales_array = null;
        }

        //recorro sucursales
        foreach( $sucursales_arr as $s )
        {
            $suc_id = $s['id'];
            $suc_name = $s['name'];

            if( $sucursales_array && !in_array($suc_id, $sucursales_array) )
            {
                continue;
            }

            $stock = $prod_obj->get_stock_producto($sku, $suc_id);

            $arraux = array( 'sucursal_id' => $suc_id, 'sucursal_nombre' => $suc_name, 'sku' => $sku, 'stock' => $stock );
            $arr[] = $arraux;
        }

        return $arr;
    }

    /**
     * recorre el arreglo pack_details d, obtiene el stock de cada variante y 
     * devuelve el menor stock hallado
     * @param type $product_data
     */
    public function get_stock_pack($product_data)
    {
        //viene pack?
        if( !isset($product_data['pack_details']) || !is_array($product_data['pack_details']) )
        {
            return false;
        }

        $prod = new ProductoBsale();

        //recorro variantes, obtengo el stock de cada una en Bsale y asigno el menor al pack
        //array con el stock de cada variante que forma el pack
        $arr_stocks_variante = array();

        if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' )
        {
            Funciones::print_r_html($product_data['pack_details'], "get_stock_pack: busco stock para variaciones del pack");
        }
        foreach( $product_data['pack_details'] as $v )
        {
            $quantity = $v['quantity'];
            if( $quantity <= 0 )
            {
                if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' )
                {
                    Funciones::print_r_html($v, "get_stock_pack: busco stock para variantid='$variant_id' stock= $quantity <=0, se omite ");
                }
                continue;
            }
            $variant_id = isset($v['variant']['id']) ? $v['variant']['id'] : 0;

            if( $variant_id <= 0 )
            {
                if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' )
                {
                    Funciones::print_r_html($v, "get_stock_pack: busco stock para variantid='$variant_id' <=0, se omite ");
                }
                continue;
            }
            //array con stocks por sucursal
            $stock_variante_bsale_arr = $prod->get_stock_producto_sucursales(null, $variant_id);

            //sumo los stock de todas las sucursales
            $stock_variante_bsale = $this->get_suma_stocks($stock_variante_bsale_arr);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_stock_pack: busco stock para variantid='$variant_id', stock en el pack=$quantity " .
                        "Stock disponible en Bsale=$stock_variante_bsale");
            }

            //si no hay stock de al menos uina variante, el pack completo no lo tendrá
            if( $stock_variante_bsale <= 0 )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("No hay stock de la variacion $variant_id para el pack. Elpack queda en stock cero.");
                }
                return 0;
            }
            //si mi pack tiene 2 unid y en stock hay 40, el máximo de stock del pack
            //es de 40/2= 20 unidades del pack
            $stock_variante = $stock_variante_bsale / $quantity;

            //el stock debe ser, al menos, igual a la cantidad de esa variante que contiene el pack
            if( $stock_variante <= 0 )
            {
                $stock_variante = 0;
            }

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("Divido el stock de la variacion $variant_id para el pack. "
                        . "stock bsale= $stock_variante_bsale / cantidad= $quantity = stcok vpara este pack=$stock_variante");
            }

            //guardo en listado de stocks de variantes
            $arr_stocks_variante[] = $stock_variante;
        }

        $total_stock_pack = 0;
        //en $arr_stocks_variante están los stock, por separado, de cada variante que conforma el 
        //pack. Extraigo el menor etock y lo devuelvo
        if( count($arr_stocks_variante) > 0 )
        {
            $total_stock_pack = min($arr_stocks_variante);
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($arr_stocks_variante, "get_stock_pack(), menor stock= $total_stock_pack");
        }
        unset($arr_stocks_variante);

        return floor($total_stock_pack);
    }

    /**
     * recibe un arreglo sucursal id=>stock y devuelve la sum de esos stocks
     * @param type $arr
     */
    public function get_suma_stocks($arr)
    {
        $total = 0;

        if( !$arr || count($arr) <= 0 )
        {
            return $total;
        }

        foreach( $arr as $k => $v )
        {
            $total += (int) $v;
        }
        return $total;
    }

    /**
     * devielve un string html con el stock por sucursal
     * @param type $data
     */
    public function get_stock_sucursal_html($sku, $es_pack = false, $stock_pack = null, $all_sucursales = false)
    {

        $bsale = new Bsale();

        $suc_matriz_bsale_id = Funciones::get_matriz_bsale();

        $arr_stock = $bsale->get_stock_por_sucursal($sku, $all_sucursales);
        $limit_stock = Funciones::get_bsale_limit_stock();

        //transformo a html
        $arr_html = array();
        $desp_domicilio = false;

        foreach( $arr_stock as $s )
        {
            $sucursal_id = $s['sucursal_id'];
            $sucursal_nombre = trim($s['sucursal_nombre']);
            $sku = $s['sku'];
            $stock = (int) $s['stock'];

            if( $stock > 0 && $es_pack )
            {
                $stock = $stock_pack;
            }

            $sucursal_nombre = bsale_filter_sucursal_name($sucursal_nombre);

            //limite de stock
            $stock -= $limit_stock;
            $stock = $stock < 0 ? 0 : $stock;

            //html de info
            $disp = '';
            $disp = $stock > 3 ? '<span class="disp_stock yes_disp">Hay unidades disponibles</span>' : '<span class="disp_stock no_disp">Solo quedan ' . $stock . ' unidades</span>';

            if( $stock <= 0 )
            {
                $disp = '<span class="disp_stock no_disp">Agotado</span>';
            }
            $straux = "<li id='suc_{$sucursal_id}'><span class='sucursal_name'>$sucursal_nombre:</span> <b class='stock'>$stock</b> $disp</li>";

            $arr_html[] = $straux;

            /*   if( stripos("Apoquindo", $sucursal_nombre) !== false )
              {
              $disp = $stock > 0 ? '<span class="disp_stock yes_disp">disponible</span>' : '<span class="disp_stock no_disp">no disponible</span>';
              $straux = "<li id='suc_888'><span>Vitacura:</span> <b>$stock</b> $disp</li>";
              $arr_html[] = $straux;
              }
             */
            /* if( !$desp_domicilio && stripos("Mac iver", $sucursal_nombre) !== false && $stock > 0 )
              {
              $disp = $stock > 0 ? '<span class="disp_stock yes_disp">disponible</span>' : '<span class="disp_stock no_disp">no disponible</span>';
              $straux = "<li id='suc_999'><span>Despacho a domicilio:</span> <b>$stock</b> $disp</li>";
              array_unshift($arr_html, $straux);
              $desp_domicilio = true;
              //[] = ;
              }
              else if( !$desp_domicilio && stripos("Bodegal", $sucursal_nombre) !== false && $stock > 0 )
              {
              $disp = $stock > 0 ? '<span class="disp_stock yes_disp">disponible</span>' : '<span class="disp_stock no_disp">no disponible</span>';
              $straux = "<li id='suc_999'><span>Despacho a domicilio:</span> <b>$stock</b> $disp</li>";
              array_unshift($arr_html, $straux);
              $desp_domicilio = true;
              //[] = ;
              } */
        }

        //si no se ha colocado despacho a domic, es que stock a domic =0
        /* if( !$desp_domicilio && count($arr_html) > 0 )
          {
          $stock = 0;
          $disp = $stock > 0 ? '<span class="disp_stock yes_disp">disponible</span>' : '<span class="disp_stock no_disp">no disponible</span>';
          $straux = "<li id='suc_999'><span>Despacho a domicilio:</span> <b>$stock</b> $disp</li>";
          array_unshift($arr_html, $straux);
          $desp_domicilio = true;
          } */

        if( count($arr_html) > 0 )
        {
            //paso a string
            $str_html = implode('', $arr_html);
            $str_html .= '<li><span class="stock_info">*Stock desfasado en 10 minutos*</span></li>';
        }
        else
        {
            $str_html = '<li><span class="stock_info no_stock">SIN STOCK</span></li>';
        }


        $str_html = '<div class="bsale_stock_sucursal sku_' . $sku . '">'
                . '<p class="titulo">Stock disponible <span>sku=' . $sku . '</span></p>'
                . '<ul class="stock_list">' . $str_html . '</ul></div>';

        //  Funciones::print_r_html($str_html, "get_stock_sucursal_html($sku)");
        return $str_html;
    }

    /**
     * devielve un string html con el stock por sucursal
     * @param type $json, debe convertirlo a array y crear el list
     */
    public function get_stock_sucursal_html_from_json($json)
    {
        $limit_stock = Funciones::get_bsale_limit_stock();

        $arr_stock = json_decode($json, true);

        //nada que mostrar
        if( !is_array($arr_stock) )
        {
            return '';
        }

        $arr_html = array();
        foreach( $arr_stock as $s )
        {
            $sucursal_id = $s['sucursal_id'];
            $sucursal_nombre = trim($s['sucursal_nombre']);
            $sku = $s['sku'];
            $stock = (int) $s['stock'];

            //limite de stock
            $stock -= $limit_stock;
            $stock = $stock < 0 ? 0 : $stock;

            //html de info
            $disp = '';
            $disp = $stock > 3 ? '<span class="disp_stock yes_disp">Hay unidades disponibles</span>' : '<span class="disp_stock no_disp">Solo quedan ' . $stock . ' unidades</span>';

            if( $stock <= 0 )
            {
                $disp = '<span class="disp_stock no_disp">Agotado</span>';
            }
            $straux = "<li id='suc_{$sucursal_id}'><span class='sucursal_name'>$sucursal_nombre:</span> <b class='stock'>$stock</b> $disp</li>";

            $arr_html[] = $straux;
        }

        if( count($arr_html) > 0 )
        {
            //paso a string
            $str_html = implode('', $arr_html);
            $str_html .= '<li><span class="stock_info">*Stock desfasado en 10 minutos*</span></li>';
        }
        else
        {
            $str_html = '<li><span class="stock_info no_stock">SIN STOCK</span></li>';
        }


        $str_html = '<div class="bsale_stock_sucursal sku_' . $sku . '">'
                . '<p class="titulo">Stock disponible <span>sku=' . $sku . '</span></p>'
                . '<ul class="stock_list">' . $str_html . '</ul></div>';

        //  Funciones::print_r_html($str_html, "get_stock_sucursal_html($sku)");
        return $str_html;
    }
}
