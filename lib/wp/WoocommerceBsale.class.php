<?php

//namespace woocommerce_bsalev2\lib\wp;

require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of WoocommerceBsale
 *
 * @author Lex
 */
class WoocommerceBsale /* extends OCDB */
{

    public function sync_variacion_wc($data)
    {
        date_default_timezone_set("America/Santiago");
        ini_set("date.timezone", "America/Santiago");

        if( defined('WC_INSERT_VARIANTES_AS_PRODS') && WC_INSERT_VARIANTES_AS_PRODS == true )
        {
            $nombre = $data['product']['name'] . ' ' . $data['variant_description'];
        }
        else
        {
            $nombre = $data['product']['name'];
        }
        $variant_id = $data['variant_id'];
        $product_name = $data['product']['name'];
        $precio = $data['variant_price']['price'];

        if( isset($data['variant_stock']['variant_total_stock']) && $data['variant_stock']['variant_total_stock'] < 0 )
        {
            //si es -1, entonces error al obtener stock
            //$data['variant_stock']['variant_total_stock'] = 0;
        }
        $stock = $data['variant_stock']['variant_total_stock'];

        $sku = $data['variant_code'];

        if( empty($sku) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($data, __METHOD__ . " sync prod, no tiene sku, se omite");
            }
            return false;
        }

        //datos para archivo log
        $hoy = date('Y-m-d');
        $basename = "sync_variacion_wc-$hoy";
        $log = new DocumentoAbstracto();

        $prod_bsale = new ProductoBsale();

        //los que empiezan con XXX, no sincronizar 
        if( $prod_bsale->is_product_to_skip($sku) )
        {
            $title = date('d-m-Y H:i:s') . " sync_variacion_wc, (id bsale=$variant_id) sku=$sku, nombre '$nombre' empieza con prejijo, omito producto";
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }

            // $log->logFile($basename, null, null, $title);

            return false;
        }

        //stock por sucursal html
        if( Funciones::is_mostrar_stock_sucursal() || Funciones::is_mostrar_stock_sucursal_backend() )
        {
            $bsale = new Bsale();

            //si viene de sync all prods, el stock por sucursal viene aquí
            if( !empty($data['stocks_sucursales']) )
            {
                $stock_sucursal_arr = $bsale->get_stock_sucursal_html_from_json($data['stocks_sucursales']);

                $data['stock_por_sucursal_html'] = $stock_sucursal_arr;

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($stock_sucursal_arr,
                            "stock_sucursal_arr para id bsale=$variant_id FROM JSON");
                }
            }
            else
            {
                $es_pack = (!empty($data['product']['pack_details']) ) ? true : false;

                $stock_sucursal_arr = $bsale->get_stock_sucursal_html($sku, $es_pack, $stock, true);

                $data['stock_por_sucursal_html'] = $stock_sucursal_arr;

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($stock_sucursal_arr, "stock_sucursal_arr para id bsale=$variant_id "
                            . "es pack=$es_pack, stock pack=$stock");
                }
            }
        }

        $prod_wp = new ProductWP();

        //existe una variacion/producto con este sku en wc?
        $product_arr = $prod_wp->getProductoBySku($sku, true);

        //si existen prodcutos con este sku
        if( is_array($product_arr) && count($product_arr) > 0 )
        {
            $result = 0;

            foreach( $product_arr as $product )
            {
                //si existe, actualizo
                if( $product )
                {
                    $title = date('d-m-Y H:i:s') . " sync_variacion_wc, (id bsale=$variant_id) variacion sku= $sku ya existe, actualizo sus datos";
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html($title);
                    }

                    // $log->logFile($basename, null, null, $title);

                    $result = $this->update_variacion_wc($data, $product);
                }
            }
            //updateados, devuelvo
            return $result;
        }

        //¿debo crear productos?
        if( Funciones::is_create_products() !== true )
        {
            $title = date('d-m-Y H:i:s') . " sync_variacion_wc, (id bsale=$variant_id) sku=$sku, nombre '$nombre' no existe en la tienda y "
                    . "la creacion de productos está desactivada. Se omite.";
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }
            //$log->logFile($basename, null, null, $title);
            return false;
        }
        //creo prodcuto/variacion 
        $result = $this->add_variacion_wc($data);

        return $result;
    }

    /**
     *  actualiza prodcuto o ovariacion de WC, según los datos en $data
     * @param type $data
     * @param type $product
     * @param type $sku si $product es null, lo busca por sku
     * @return boolean
     */
    public function update_variacion_wc($data, $product = null, $sku = null)
    {
        //log ultimo cambio
        global $global_file_update_product, $global_file_update_product_data;

        if( empty($global_file_update_product) )
        {
            $global_file_update_product = '';
        }

        if( defined('WC_INSERT_VARIANTES_AS_PRODS') && WC_INSERT_VARIANTES_AS_PRODS == true )
        {
            $nombre = $data['product']['name'] . ' ' . $data['variant_description'];
        }
        else
        {
            $nombre = $data['product']['name'];
        }

        $variant_id = $data['variant_id'];
        $precio = $data['variant_price']['price'];
        $precio_especial = $data['variant_price']['price_desc'];
        $precio_especial2 = (int) $data['variant_price']['price_desc2'];
        $precio_especial3 = (int) $data['variant_price']['price_desc3'];

        $stock = $data['variant_stock']['variant_total_stock'];
        $sku = $data['variant_code'];

        //datos para archivo log
        $hoy = date('Y-m-d');
        $basename = "sync_variacion_wc-$hoy";
        $log = new DocumentoAbstracto();

        $prod_wp = new ProductWP();

        $limit_stock = Funciones::get_bsale_limit_stock();

        //si limito stock
        if( $limit_stock != 0 )
        {

            $title = date('d-m-Y H:i:s') . "update_variacion_wc(): limito stock para ($sku) stock original=$stock - $limit_stock";
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }

            //$log->logFile($basename, null, null, $title);
            //ajusto stock

            if( $stock != Funciones::get_stock_ilimitado_prod_bsale() )
            {
                $stock = ($stock - $limit_stock) >= 0 ? $stock - $limit_stock : 0;
            }

            $title = date('d-m-Y H:i:s') . "update_variacion_wc(): stock limitado para ($sku)=$stock ";
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }

            //$log->logFile($basename, null, null, $title);
        }

        if( $product == null )
        {
            if( !empty($sku) )
            {
                $prod_wp = new ProductWP();

                //existe una variacion/producto con este sku en wc?
                $product = $prod_wp->getProductoBySku($sku);
            }
        }

        //inicio loop productos
        if( !$product )
        {
            $title = date('d-m-Y H:i:s') . "update_variacion_wc(): producto/variacion sku= $sku no encontrado. Se omite";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }
            //$log->logFile($basename, null, null, $title);
            return false;
        }

        //busco producto/variacion id
        $product_id = null;

        //depende de la vbersion de Woocommerce
        if( method_exists($product, 'get_id') )
        {
            $product_id = $product->get_id();
        }
        else
        {
            $product_id = $product->id;
        }


        if( !$product_id )
        {
            $title = date('d-m-Y H:i:s') . "update_variacion_wc(): producto/variacion sku= $sku PRODUCTO ID NO ENCONTRADO!. Se omite";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }
            //$log->logFile($basename, null, null, $title);
            return false;
        }

        $product_wc_name = $product->get_title();

        $title = date('d-m-Y H:i:s') . "update_variacion_wc(): producto/variacion id wc=$product_id sku= $sku, "
                . "nombre producto wc= '$product_wc_name', stock=$stock, precio= $ $precio, precio descto= $ $precio_especial, precio2= $ $precio_especial2";
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($title);
        }

        //debo actualizar producto o variacion?  
        //¿es variacion?
        if( $product instanceof WC_Product_Variation )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("Producto id wc=$product_id es VARIACION");
            }
            $es_variante = true;

            $producto_padre_id = $product->get_parent_id();
            $product_padre = wc_get_product($producto_padre_id);

            if( $product_padre !== false )
            {
                //para saber si debo hacer save o no
                $changed = false;

                $title = date('d-m-Y H:i:s') . " update_variacion_wc(): sku='$sku' es variante: $es_variante del producto: " . $product_padre->get_id() . "  '" . $product_padre->get_name() . "'";
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($title);
                }
                //desactivo manejar inventario a nivel de producto padre, 
                //ya que se debe hacer a nivel de variacion
                // $product_padre->set_stock_status('instock');
                if( $product_padre->get_manage_stock() )
                {
                    $product_padre->set_manage_stock('no');
                    $changed = true;
                }
                $fix = Funciones::get_value('WC_FIX_CATALOG_VISIBILITY_OLDER_PRODS', false);

                if( $fix )
                {
                    //Options: 'hidden', 'visible', 'search' and 'catalog'.
                    //fix wc update, para prods provenientes de antiguas versiones de wc
                    $product_padre->set_catalog_visibility('catalog');
                    $product_padre->save();
                    $product_padre->set_catalog_visibility('visible');
                    $resv = $product_padre->save();

                    $changed = false; //lo dejo en false, pues ya guardé cambios del producto
                }
                //hay cambios que guardar?
                if( $changed )
                {
                    $resv = $product_padre->save();
                }


                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("save prod variable $producto_padre_id: disable gestion inventario");
                }

                // $log->logFile($basename, null, null, $title);
            }
            else
            {
                $title = date('d-m-Y H:i:s') . " update_variacion_wc(): sku='$sku' es variante sin padre. Se omite.";
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($title);
                }
                // $log->logFile($basename, null, null, $title);
                return false;
            }
        }
        //es producto simple
        else
        {
            $es_variante = false;
            $producto_padre_id = 0;
            $product_padre = null;

            $title = date('d-m-Y H:i:s') . " update_variacion_wc(): sku='$sku' es producto simple";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }
            //$log->logFile($basename, null, null, $title);
        }

        try
        {

            $post_id = (int) $product_id;

            //actualizo variacion
            if( $es_variante )
            {
                //para taxonomia con/sin stock
                $product_variante = wc_get_product($post_id);

                if( !$product_variante )
                {
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html(__METHOD__ . "get variacion $post_id, no existe. No se actualiza.");
                    }
                    return false;
                }
                //para saber si hay cambios que guardar
                $changed = false;

                //precio
                if( Funciones::is_update_product_price() && $precio > 0 )
                {
                    $changed = $this->update_product_regular_price($product_variante, $precio) ? true : $changed;
                }
                //precio descto
                if( Funciones::is_update_product_price_desc() )
                {
                    $changed = $this->update_product_sale_price($product_variante, $precio_especial) ? true : $changed;
                }

                //precio mayorista normal $precio_especial2,
                //precio mayorista oferta  $precio_especial3
                if( Funciones::is_update_product_price_mayorista() )
                {
                    //filtros para precios mayoristas, para quie puedan ser manipulados fuera del plgun
                    $arr_filter_param = array( 'precio_may_normal' => $precio_especial2, 'precio_may_oferta' => $precio_especial3 );
                    do_action('bsale_filter_update_precios_mayoristas', $post_id, $arr_filter_param);
                }

                //stock
                if( Funciones::is_update_product_stock() && $stock > -1 )
                {
                    $changed = $this->update_product_stock($product_variante, $stock) ? true : $changed;
                }

                //guardo datos de producto
                $res_save = $this->save_product($product_variante, $changed);

                if( Funciones::is_mostrar_stock_sucursal() || Funciones::is_mostrar_stock_sucursal_backend() )
                {
                    //stock por sucursal
                    $stock_por_sucursal_html = isset($data['stock_por_sucursal_html']) ? $data['stock_por_sucursal_html'] : '';
                    $this->save_stock_sucursal_html($product_variante, $stock_por_sucursal_html);
                }

                //info
                $info = date('Y-m-d H:i:s') . ": update variacion sku=$sku, precio = $precio, stock= $stock, archivo: '$global_file_update_product'\n" .
                        print_r($data, true);
                //update_post_meta($producto_padre_id, 'variacion_' . $sku, $info);
                //wc_delete_product_transients($post_id);
                //wc_delete_product_transients($producto_padre_id);

                $arr_info = array(
                    'product_id' => $post_id, 'producto_padre_id' => $producto_padre_id,
                    'tipo' => 'variacion', 'nombre' => $product_variante->get_name(),
                    'sku' => $sku, 'stock' => $stock, 'precio_normal' => $precio,
                    'precio_oferta' => $precio_especial, 'precio_especial_2' => $precio_especial2,
                    'precio_especial_3' => $precio_especial3,
                    'source_file' => $global_file_update_product,
                    'source_array' => $global_file_update_product_data,
                    'accion' => 'update', 'result' => $res_save,
                    'data' => $data,
                );

                $this->log_result_sync($arr_info);
            }
            //actualizo producto simple
            else
            {
                //para taxonomia con/sin stock
                $product_simple = wc_get_product($post_id);

                if( !$product_simple )
                {
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html(__METHOD__ . "get producto simple $post_id, no existe. No se actualiza.");
                    }
                    return false;
                }

                $changed = false;

                /* if( $product_simple->get_manage_stock() == false )
                  {
                  $product_simple->set_manage_stock('yes');
                  $changed = true;
                  if( isset($_REQUEST['param']) )
                  {
                  Funciones::print_r_html(__METHOD__ . " producto simple $post_id, changed set_manage_stock().");
                  }
                  // update_post_meta($post_id, '_manage_stock', 'yes');
                  } */

                //update nombre
                if( Funciones::is_update_product_name() && !empty($nombre) )
                {
                    if( $product_simple->get_name() !== $nombre )
                    {
                        $product_simple->set_name($nombre);
                        $changed = true;
                        if( isset($_REQUEST['param']) )
                        {
                            Funciones::print_r_html(__METHOD__ . " producto simple $post_id, changed name.");
                        }
                    }
                    // $my_post = array( 'ID' => $post_id, 'post_title' => $nombre );
                    // wp_update_post($my_post);
                }
                //update price
                if( Funciones::is_update_product_price() && $precio > 0 )
                {
                    $changed = $this->update_product_regular_price($product_simple, $precio) ? true : $changed;
                }
                //price desc
                if( Funciones::is_update_product_price_desc() )
                {
                    $changed = $this->update_product_sale_price($product_simple, $precio_especial) ? true : $changed;
                }
                //precio mayorista normal $precio_especial2,
                //precio mayorista oferta  $precio_especial3
                if( Funciones::is_update_product_price_mayorista() )
                {
                    //filtros para precios mayoristas, para quie puedan ser manipulados fuera del plgun
                    $arr_filter_param = array( 'precio_may_normal' => $precio_especial2, 'precio_may_oferta' => $precio_especial3 );
                    do_action('bsale_filter_update_precios_mayoristas', $post_id, $arr_filter_param);
                }

                //update stock
                if( Funciones::is_update_product_stock() && $stock > -1 )
                {
                    $changed = $this->update_product_stock($product_simple, $stock) ? true : $changed;

                    $fix = Funciones::get_value('WC_FIX_CATALOG_VISIBILITY_OLDER_PRODS', false);

                    if( $fix )
                    {
                        //Options: 'hidden', 'visible', 'search' and 'catalog'.
                        $product_simple->set_catalog_visibility('catalog');
                        $resv = $product_simple->save();
                        $product_simple->set_catalog_visibility('visible');
                        $resv = $product_simple->save();
                        $changed = false; //lo dejo en false, pues ya guardé cambios del producto
                    }


                    // wc_delete_product_transients($post_id);
                }

                $res_save = $this->save_product($product_simple, $changed);

                if( Funciones::is_mostrar_stock_sucursal() || Funciones::is_mostrar_stock_sucursal_backend() )
                {
                    $stock_por_sucursal_html = isset($data['stock_por_sucursal_html']) ? $data['stock_por_sucursal_html'] : '';
                    $this->save_stock_sucursal_html($product_simple, $stock_por_sucursal_html);
                }
                //stock por sucursal
                //log ultimo cambio
                $info = date('Y-m-d H:i:s') . ": update producto simple sku=$sku, precio = $precio, stock= $stock, archivo: '$global_file_update_product'\n" .
                        print_r($data, true);
                //update_post_meta($post_id, 'producto_update', $info);

                $arr_info = array(
                    'product_id' => $post_id, 'producto_padre_id' => 0,
                    'tipo' => 'producto', 'nombre' => $product_simple->get_name(),
                    'sku' => $sku, 'stock' => $stock, 'precio_normal' => $precio,
                    'precio_oferta' => $precio_especial, 'precio_especial_2' => $precio_especial2,
                    'precio_especial_3' => $precio_especial3,
                    'source_file' => $global_file_update_product,
                    'source_array' => $global_file_update_product_data,
                    'accion' => 'update', 'result' => $res_save,
                    'data' => $data,
                );

                $this->log_result_sync($arr_info);

                //wc_delete_product_transients($post_id);
            }

            //     Funciones::print_r_html( "updateProductoMAG: $producto_mag_id despues de save()" );
        }
        catch( Exception $exc )
        {
            $title = date('d-m-Y H:i:s') . " update_variacion_wc(): exception";
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($exc, $title);
            }
            // $log->logFile($basename, null, null, $title);
        }
        //fin loop
        //Funciones::print_r_html("update variacion, fin");
        return true; //$product_result
    }

    public function clear_product_cache($product_id)
    {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->prefix}options "
                . "WHERE {$wpdb->prefix}options.option_name "
                . "LIKE '_transient_timeout_wc_var_prices_$product_id'");

        $wpdb->query("DELETE FROM {$wpdb->prefix}options "
                . "WHERE {$wpdb->prefix}options.option_name "
                . "LIKE '_transient_wc_var_prices_$product_id'");
    }

    /**
     * crea un producto simple; una variación en un producto variable existente,
     * o un producto variable + la variacion 
     * @global type $woocommerce
     * @global type $woocommerce
     * @param type $data
     * @return boolean
     */
    public function add_variacion_wc($data)
    {
        //¿debo crear productos?
        if( !Funciones::is_create_products() )
        {
            return false;
        }

        //log ultimo cambio
        global $global_file_update_product, $global_file_update_product_data;

        if( empty($global_file_update_product) )
        {
            $global_file_update_product = '';
        }

        /* $conn = $this->conectar();
          if( $conn == false )
          {
          return null;
          } */

        if( defined('WC_INSERT_VARIANTES_AS_PRODS') && WC_INSERT_VARIANTES_AS_PRODS == true )
        {
            $nombre = $data['product']['name'] . ' ' . $data['variant_description'];
            $nombre_producto = $nombre;
            $nombre_variacion = $nombre;
        }
        else
        {
            $nombre = $data['product']['name'];
            $nombre_producto = $data['product']['name'];
            $nombre_variacion = $data['variant_description'];
        }

        $variant_id = $data['variant_id'];
        $precio = $data['variant_price']['price'];
        $precio_especial = $data['variant_price']['price_desc'];
        $precio_especial2 = $data['variant_price']['price_desc2'];
        $precio_especial3 = $data['variant_price']['price_desc3'];

        $stock = $data['variant_stock']['variant_total_stock'];
        $sku = $data['variant_code'];

        if( /* ($stock == -1 || $stock == Funciones::get_stock_ilimitado_prod_bsale()) && */ $precio <= 0 && Funciones::is_update_product_price() )
        {
            $msg = "producto con sku='$sku' tiene precio $ $precio <=0, no se agrega a woocommerce hasta que "
                    . "tenga un precio mayor a $ 1.";
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__, $msg);
            }

            $arr_info = array(
                'product_id' => 0, 'producto_padre_id' => 0,
                'tipo' => 'variacion', 'nombre' => '',
                'sku' => $sku, 'stock' => $stock, 'precio_normal' => $precio,
                'precio_oferta' => $precio_especial, 'precio_especial_2' => $precio_especial2,
                'precio_especial_3' => $precio_especial3,
                'source_file' => $global_file_update_product,
                'source_array' => $global_file_update_product_data,
                'accion' => 'create', 'result' => 0,
                'data' => $data,
                'msg' => $msg,
            );
            $this->log_result_sync($arr_info);
            return false;
        }

        /* if( $stock <= 0  ) // || $precio <= 0 
          {
          $msg = "producto con sku='$sku' tiene stock='$stock' <=0, no se agrega a woocommerce hasta "
          . "que tenga stock >0.";
          if( isset($_REQUEST['param']) )
          {
          Funciones::print_r_html(__METHOD__, $msg);
          }

          $arr_info = array(
          'product_id' => 0, 'producto_padre_id' => 0,
          'tipo' => 'variacion', 'nombre' => '',
          'sku' => $sku, 'stock' => $stock, 'precio_normal' => $precio,
          'precio_oferta' => $precio_especial, 'precio_especial_2' => $precio_especial2,
          'precio_especial_3' => $precio_especial3,
          'source_file' => $global_file_update_product,
          'source_array' => $global_file_update_product_data,
          'accion' => 'create', 'result' => 0,
          'data' => $data,
          'msg' => $msg,
          );

          $this->log_result_sync($arr_info);
          return false;
          } */

        //datos para archivo log
        $hoy = date('Y-m-d');
        $basename = "sync_variacion_wc-$hoy";
        $log = new DocumentoAbstracto();

        $prod_wp = new ProductWP();

        $limit_stock = Funciones::get_bsale_limit_stock();

        //si limito stock
        if( $limit_stock != 0 )
        {

            $title = date('d-m-Y H:i:s') . "add_variacion_wc(): limito stock para ($sku) stock original=$stock - $limit_stock";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }

            $log->logFile($basename, null, null, $title);

            //ajusto stock
            if( $stock != Funciones::get_stock_ilimitado_prod_bsale() )
            {
                $stock = ($stock - $limit_stock) >= 0 ? $stock - $limit_stock : 0;
            }
            $title = date('d-m-Y H:i:s') . "add_variacion_wc(): stock limitado para ($sku)=$stock ";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($title);
            }

            //$log->logFile($basename, null, null, $title);
        }
        //existe el prodcuto o variacion?   
        //si debo insertar nuevos productos como variantes?
        //valor x defecto para no insertar variante
        $product_parent_id = 0;

        //si viene en global
        global $BSALE_GLOBAL;
        //dueño del producto
        $post_author_id = isset($BSALE_GLOBAL['SELLER_ID']) ? (int) $BSALE_GLOBAL['SELLER_ID'] : 1;

        //si debo insertar como variante
        if( WC_INSERT_NUEVOS_AS_VARIANTES )
        {
            //Funciones::print_r_html( $bsale_producto, "addproducto params:" );
            //existe un producto con este nombre al que agregarle la nueva variante?
            $product_parent = get_page_by_title($nombre_producto, OBJECT, 'product');

            //si producto no existe, lo creo
            if( $product_parent == null )
            {

                global $woocommerce;

                $product_parent = array(
                    'post_author' => $post_author_id,
                    'post_status' => 'pending', //publish
                    'post_title' => $nombre_producto,
                    'post_content' => '',
                    'post_parent' => '',
                    'post_type' => 'product',
                        //'post_status' => 'private',
                );
                //Create post               
                $product_parent_id = wp_insert_post($product_parent);
                //tipo de producto: variable
                wp_set_object_terms($product_parent_id, 'variable', 'product_type');
                update_post_meta($product_parent_id, '_visibility', 'visible'); //search
                update_post_meta($product_parent_id, '_stock_status', 'instock');
                //update_post_meta( $product_parent_id, '_regular_price', $precio );
                //update_post_meta( $product_parent_id, '_price', $precio );
                // update_post_meta( $post_id, '_sale_price', $precio_especial );
                update_post_meta($product_parent_id, '_purchase_note', "");
                update_post_meta($product_parent_id, '_featured', "no");
                update_post_meta($product_parent_id, '_weight', "");
                update_post_meta($product_parent_id, '_length', "");
                update_post_meta($product_parent_id, '_width', "");
                update_post_meta($product_parent_id, '_height', "");
                // update_post_meta( $product_parent_id, '_sku', $sku );
                // update_post_meta( $product_parent_id, '_product_attributes', array() );
                update_post_meta($product_parent_id, '_sale_price_dates_from', "");
                update_post_meta($product_parent_id, '_sale_price_dates_to', "");
                //update_post_meta($product_parent_id, '_price', $precio);
                update_post_meta($product_parent_id, '_sold_individually', "no");
                update_post_meta($product_parent_id, '_manage_stock', "no");
                update_post_meta($product_parent_id, '_backorders', "no");
                update_post_meta($product_parent_id, '_stock', null);
                update_post_meta($product_parent_id, '_et_pb_page_layout', 'et_full_width_page');

                $title = date('d-m-Y H:i:s') . "add_variacion_wc(): creo producto padre de variacion: '$nombre_producto', id= $product_parent_id";

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($title);
                }

                //$log->logFile($basename, null, null, $title);

                $producto_padre_obj = wc_get_product($product_parent_id);

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($producto_padre_obj);
                }

                //agrego porducto a catyegoria
                //$cat_id = WC_CATEGORIA_RECIEN_LLEGADOS;
                //wp_set_object_terms( $post_id, $cat_id, 'product_cat' );
                //xxx crear automaticamente este atributo si no existe
                $talla_attributes = $prod_wp->get_attributes_wc('pa_talla', true);

                $available_attributes = array( 'pa_talla' );
                $variations = array( 'pa_talla' => $talla_attributes, );

                // Add attributes passing the new post id, attributes & variations
                $prod_wp->insert_product_attributes($product_parent_id, $available_attributes, $variations);
            }
            else
            {
                if( method_exists($product_parent, 'get_id') )
                {
                    $product_parent_id = $product_parent->get_id();
                }
                else
                {
                    $product_parent_id = $product_parent->ID;
                }

                $title = date('d-m-Y H:i:s') . "add_variacion_wc(): obtengo producto padre id= $product_parent_id, '$nombre_producto' de variacion a crear";

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($title);
                }

                // $log->logFile($basename, null, null, $title);
            }
        }

        try
        {

            //inserto producto               
            global $woocommerce;

            if( WC_INSERT_NUEVOS_AS_VARIANTES )
            {

                $title = date('d-m-Y H:i:s') . "add_variacion_wc(): creo variacion sku= $sku, '$nombre_variacion', para producto $product_parent_id '$nombre_producto', "
                        . "precio= $ $precio, precio desc= $ $precio_especial, precio2 = $ $precio_especial2, stock=$stock ";
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($title);
                }
                // $log->logFile($basename, null, null, $title);

                $post = array(
                    'post_author' => $post_author_id,
                    'post_status' => 'publish', //publish pending
                    'post_title' => $nombre_variacion,
                    'post_content' => '',
                    'post_parent' => $product_parent_id,
                    'post_type' => 'product_variation',
                        //'post_status' => 'private',
                );
                //Create post               
                $post_id = wp_insert_post($post);

                update_post_meta($post_id, '_visibility', 'search'); //search

                update_post_meta($post_id, '_regular_price', $precio);
                update_post_meta($post_id, '_sku', $sku);
                update_post_meta($post_id, '_price', $precio);
                update_post_meta($post_id, '_sold_individually', 'no');
                update_post_meta($post_id, '_manage_stock', 'yes');
                update_post_meta($post_id, '_backorders', 'no');
                update_post_meta($post_id, '_stock', $stock);

                if( $stock > 0 )
                {
                    update_post_meta($post_id, '_stock_status', 'instock');
                }
                else
                {
                    update_post_meta($post_id, '_stock_status', 'outofstock');
                }
                //update_post_meta($post_id, '_manage_stock', 'yes');
                //update_post_meta($post_id, '_et_pb_page_layout', 'et_full_width_page');
                //precio descto
                if( Funciones::is_update_product_price_desc() )
                {
                    $precio_especial = $precio_especial <= 1 ? '' : $precio_especial;
                    //no puede ser igual al precio normal
                    $precio_especial = ($precio_especial >= $precio) ? '' : $precio_especial;

                    update_post_meta($post_id, '_sale_price', $precio_especial);
                }
                //precio mayorista normal $precio_especial2,
                //precio mayorista oferta  $precio_especial3
                if( Funciones::is_update_product_price_mayorista() )
                {

                    //filtros para precios mayoristas, para quie puedan ser manipulados fuera del plgun
                    $arr_filter_param = array( 'precio_may_normal' => $precio_especial2, 'precio_may_oferta' => $precio_especial3 );
                    do_action('bsale_filter_add_precios_mayoristas', $post_id, $arr_filter_param);
                }

                if( Funciones::is_mostrar_stock_sucursal() || Funciones::is_mostrar_stock_sucursal_backend() )
                {
                    if( isset($data['stock_por_sucursal_html']) && !empty($data['stock_por_sucursal_html']) )
                    {
                        if( isset($_REQUEST['param']) )
                        {
                            Funciones::print_r_html($data['stock_por_sucursal_html'], "wc: add variacion id=$post_id sku=$sku, stock_por_sucursal_html");
                        }
                        update_post_meta($post_id, "stock_{$sku}", $data['stock_por_sucursal_html']);
                        update_post_meta($post_id, "stock_por_sucursal_html", $data['stock_por_sucursal_html']);
                    }
                }

                //inserto atributo               
                $atributo_value = '20'; //atributo por default               

                $attribute_term = get_term_by('name', $atributo_value, 'pa_talla'); // We need to insert the slug not the name into the variation post meta
                if( isset($attribute_term->slug) )
                {
                    //Funciones::print_r_html($attribute_term->slug, "get_term_by( 'name', $atributo_value, 'pa_talla' )");
                    update_post_meta($post_id, 'attribute_pa_talla', $attribute_term->slug);
                    // Again without variables: update_post_meta(25, 'attribute_pa_size', 'small')
                }
                //add size attributes to this variation:
                $talla_attributes = $this->get_attributes_wc('pa_talla');
                wp_set_object_terms($post_id, $talla_attributes, 'pa_talla');
            }
            //inserto producto simple
            else
            {

                $title = date('d-m-Y H:i:s') . "add_variacion_wc(): creo producto simple sku= $sku, '$nombre_producto'. precio= $ $precio, precio desc= $ $precio_especial, precio2 = $ $precio_especial2, stock=$stock";
                if( isset($_REQUEST['param']) )
                {

                    Funciones::print_r_html($title);
                }
                //$log->logFile($basename, null, null, $title);

                $objProduct = new WC_Product();

                $objProduct->set_name($nombre_producto);
                $objProduct->set_status('pending');  // can be publish,draft or any wordpress post status
                //Options: 'hidden', 'visible', 'search' and 'catalog'.
                $objProduct->set_catalog_visibility('visible'); // add the product visibility status
                $objProduct->set_description($nombre_producto);
                $objProduct->set_sku($sku); //can be blank in case you don't have sku, but You can't add duplicate sku's
                $objProduct->set_price($precio); // set product price
                $objProduct->set_regular_price($precio); // set product regular price
                //no control stock
                if( $stock == Funciones::get_stock_ilimitado_prod_bsale() )
                {
                    $objProduct->set_stock_status('instock');
                    $objProduct->set_manage_stock('no');
                }
                else
                {
                    $objProduct->set_manage_stock('yes'); // true or false
                    $objProduct->set_stock_quantity($stock);

                    if( $stock > 0 )
                    {
                        $objProduct->set_stock_status('instock'); // in stock or out of stock value
                    }
                    else
                    {
                        $objProduct->set_stock_status('outofstock');
                    }
                }

                $objProduct->set_backorders('no');
                $objProduct->set_reviews_allowed(true);
                $objProduct->set_sold_individually(false);
                //$objProduct->set_category_ids(array( 1, 2, 3 )); // array of category ids, 

                $post_id = $objProduct->save(); // it will save the product and return the generated product id

                if( Funciones::is_mostrar_stock_sucursal() || Funciones::is_mostrar_stock_sucursal_backend() )
                {
                    $stock_por_sucursal_html = isset($data['stock_por_sucursal_html']) ? $data['stock_por_sucursal_html'] : '';
                    $this->save_stock_sucursal_html($objProduct, $stock_por_sucursal_html);
                }

                //precio descto
                if( Funciones::is_update_product_price_desc() )
                {
                    $this->update_product_sale_price($objProduct, $precio_especial);
                }
                //precio mayorista normal $precio_especial2,
                //precio mayorista oferta  $precio_especial3
                if( Funciones::is_update_product_price_mayorista() )
                {
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html("update_post_meta($post_id, '_product_rules', YITH role based price");
                    }
                    //filtros para precios mayoristas, para quie puedan ser manipulados fuera del plgun
                    $arr_filter_param = array( 'precio_may_normal' => $precio_especial2, 'precio_may_oferta' => $precio_especial3 );
                    do_action('bsale_filter_add_precios_mayoristas', $post_id, $arr_filter_param);
                }
                //guardo datos de producto
                $res_save = $this->save_product($objProduct, true);

                //log ultimo cambio
                $info = date('Y-m-d H:i:s') . ": create producto simple sku=$sku, precio = $precio, stock= $stock, archivo: '$global_file_update_product'\n" .
                        print_r($data, true);
                $arr_info = array(
                    'product_id' => $post_id, 'tipo' => 'producto', 'nombre' => $nombre_producto,
                    'sku' => $sku, 'stock' => $stock, 'precio_normal' => $precio,
                    'precio_oferta' => $precio_especial, 'precio_especial_2' => $precio_especial2,
                    'precio_especial_3' => $precio_especial3,
                    'source_file' => $global_file_update_product,
                    'source_array' => $global_file_update_product_data,
                    'accion' => 'create', 'result' => $res_save,
                    'data' => $data,
                    'msg' => ($res_save > 0) ? "producto creado id=$res_save" : 'Error al crear producto',
                );

                $this->log_result_sync($arr_info);

                // update_post_meta($post_id, 'producto_update', $info);
            }

            return true;
//endif;
        }
        catch( Exception $e )
        {
            $title = date('d-m-Y H:i:s') . "add_variacion_wc(): sku=$sku, excepcion";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($e, $title);
            }

            //$log->logFile($basename, $e, null, $title);
        }
    }

    /**
     * guarda en la db el resultado de la ultima sincronizacion de este producto
     * @param type $arr_info
     */
    public function log_result_sync($arr_info)
    {
        $product_id = isset($arr_info['product_id']) ? $arr_info['product_id'] : 0;
        $producto_padre_id = isset($arr_info['producto_padre_id']) ? $arr_info['producto_padre_id'] : 0; //solo para las variaciones

        $tipo_producto = isset($arr_info['tipo']) ? $arr_info['tipo'] : ''; //producto, variacion
        $nombre = isset($arr_info['nombre']) ? $arr_info['nombre'] : '';

        $sku = isset($arr_info['sku']) ? $arr_info['sku'] : '';
        $stock = isset($arr_info['stock']) ? $arr_info['stock'] : '';
        $precio_normal = isset($arr_info['precio_normal']) ? $arr_info['precio_normal'] : '';
        $precio_oferta = isset($arr_info['precio_oferta']) ? $arr_info['precio_oferta'] : '';
        $precio_especial_2 = isset($arr_info['precio_especial_2']) ? $arr_info['precio_especial_2'] : '';
        $precio_especial_3 = isset($arr_info['precio_especial_3']) ? $arr_info['precio_especial_3'] : '';

        $source_file = isset($arr_info['source_file']) ? $arr_info['source_file'] : '';
        $source_array = isset($arr_info['source_array']) ? $arr_info['source_array'] : array();
        $msg = isset($arr_info['msg']) ? $arr_info['msg'] : array();

        //producto
        /*
          {
          "cpnId": 11420,
          "resource": "/v2/products/1820.json",
          "resourceId": "1820",
          "topic": "product",
          "action": "put",
          "send": 1684864590
          }
         */
        //variant
        /*
          {
          "cpnId": 80546,
          "resource": "/v2/variants/8996.json",
          "resourceId": "8996",
          "topic": "variant",
          "action": "put",
          "send": 1684858326
          }
         */
        //price
        /*
          {
          "cpnId": 80546,
          "resource": "/v2/price_lists/6/details.json?variant=594",
          "resourceId": "594",
          "topic": "price",
          "action": "put",
          "priceListId": "6",
          "send": 1684869929
          }
         */
        //stock
        /*
          {
          "cpnId": 80546,
          "resource": "/v2/stocks.json?variant=8712\u0026office=2",
          "resourceId": "8712",
          "topic": "stock",
          "action": "put",
          "officeId": "2",
          "send": 1684874486
          }
         */
        //documento
        /*
          {
          "cpnId": 80546,
          "resource": "/documents/2198.json",
          "resourceId": "2198",
          "topic": "document",
          "action": "post",
          "officeId": "1",
          "send": 1684878493
          }
         */
        $accion = isset($arr_info['accion']) ? $arr_info['accion'] : ''; //create update
        $result = isset($arr_info['result']) ? $arr_info['result'] : '';
        $data = isset($arr_info['data']) ? $arr_info['data'] : '';

        $arr_data = array();

        $arr_data['product_id'] = $product_id;
        $arr_data['producto_padre_id'] = $producto_padre_id;

        $arr_data['tipo_producto'] = $tipo_producto;
        $arr_data['nombre'] = $nombre;
        $arr_data['sku'] = $sku;
        $arr_data['stock'] = $stock;

        $arr_data['precio_normal'] = $precio_normal;
        $arr_data['precio_oferta'] = $precio_oferta;
        $arr_data['precio_especial_2'] = $precio_especial_2;
        $arr_data['precio_especial_3'] = $precio_especial_3;

        $arr_data['source_file'] = $source_file;
        $arr_data['source_cpnId'] = isset($source_array['cpnId']) ? $source_array['cpnId'] : -1;
        $arr_data['source_resource'] = isset($source_array['resource']) ? $source_array['resource'] : -1;
        $arr_data['source_resourceId'] = isset($source_array['resourceId']) ? $source_array['resourceId'] : -1;
        $arr_data['source_topic'] = isset($source_array['topic']) ? $source_array['topic'] : -1;
        $arr_data['source_action'] = isset($source_array['action']) ? $source_array['action'] : -1;
        $arr_data['source_officeId'] = isset($source_array['officeId']) ? $source_array['officeId'] : -1; //solo docs
        $arr_data['source_send'] = isset($source_array['send']) ? $source_array['send'] : -1;

        $arr_data['accion'] = $accion;
        $arr_data['result'] = $result;
        $arr_data['data'] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $arr_data['msg'] = $msg;

        $log_table = new LogSincronizacionBsale();
        $res = $log_table->insert($arr_data);

        return $res;

        //limpiar cada x dias
        //link en cada prod para ver ultimas actualizaciones
        //pagina tabla con opciones de busqueda por prod id, sku
        //sync masiva de prods wc, descargar stock y precios, guardar en db y sync despues
        //filtrar por categ, skus, prod ids
        //orders: ver json enviado, este quedará en una tabla de db por x días
    }

    /**
     * actualiza precio regular de producto o variacion
     * @param type $product_obj prod simple o variable
     * @param type $new_price
     * @param type $save_on_changes guardar el prodcuto despues de los cambios o no
     */
    public function update_product_regular_price($product_obj, $new_price, $save_on_changes = false)
    {
        $changed = false;
        //precio normal
        $regular_price_current = $product_obj->get_regular_price();

        //solo actualizo si es distinto
        if( $regular_price_current !== $new_price && $regular_price_current != $new_price )
        {
            $product_obj->set_regular_price($new_price);
            // update_post_meta($post_id, '_regular_price', $precio);
            $changed = true;
            if( isset($_REQUEST['param']) && $changed )
            {
                Funciones::print_r_html(__METHOD__ . " producto precio normal distinto de $regular_price_current a $new_price.");
            }
        }

        //coloco como precio el precio normal u oferta, segun corresponda
        //$precio_oferta = get_post_meta($post_id, '_sale_price', true);
        $precio_oferta = $product_obj->get_sale_price();
        $precio_actual_current = $product_obj->get_price();

        //solo si se cambió el precio regular
        if( $changed )
        {
            //¿debo colocar como actual price el precio oferta o el precio regultar?
            //el precio oferta es menor al precio actual?
            if( !empty($precio_oferta) && $precio_oferta < $new_price &&
                    ($precio_actual_current !== $precio_oferta && $precio_actual_current != $precio_oferta) )
            {
                //update_post_meta($post_id, '_price', $precio_oferta);
                $product_obj->set_price($precio_oferta);
                $changed = true;
                if( isset($_REQUEST['param']) && $changed )
                {
                    Funciones::print_r_html(__METHOD__ . " producto changed price set precio oferta $precio_oferta.");
                }
            }
            //si el precio regultar es != al precio actual
            elseif( $precio_actual_current !== $new_price && $precio_actual_current != $new_price )
            {
                //update_post_meta($post_id, '_price', $precio);
                $product_obj->set_price($new_price);
                $changed = true;
                if( isset($_REQUEST['param']) && $changed )
                {
                    Funciones::print_r_html(__METHOD__ . " producto changed price set precio $new_price.");
                }
            }
        }


        if( $changed && $save_on_changes )
        {
            $res = $product_obj->save();
            return $res;
        }
        else
        {
            if( isset($_REQUEST['param']) && $changed )
            {
                Funciones::print_r_html(__METHOD__ . " producto changed price de $regular_price_current a $new_price.");
            }
            return $changed;
        }
    }

    /**
     * 
     * @param type $product_obj prod simple o variable
     * @param type $new_price
     * @param type $save_on_changes
     */
    public function update_product_sale_price($product_obj, $sale_price, $save_on_changes = false)
    {
        $changed = false;
        $precio_oferta_current = $product_obj->get_sale_price();
        $precio = $regular_price_current = $product_obj->get_regular_price();

        $sale_price = $sale_price <= 1 ? '' : $sale_price;
        //no puede ser igual al precio normal
        $sale_price = ($sale_price >= $precio) ? '' : $sale_price;

        //solo actualizo si precio oferta es distinto
        if( $precio_oferta_current !== $sale_price && $precio_oferta_current != $sale_price )
        {
            //update_post_meta($post_id, '_sale_price', $precio_especial);
            $product_obj->set_sale_price($sale_price);
            $changed = true;
            if( isset($_REQUEST['param']) && $changed )
            {
                Funciones::print_r_html(__METHOD__ . " producto changed price oferta de $precio_oferta_current a $sale_price.");
            }
        }

        $precio_actual_current = $product_obj->get_price();
        //si precio normal no está vacío, actualizo
        if( !empty($sale_price) && $sale_price > 0 &&
                ($sale_price !== $precio_actual_current && $sale_price != $precio_actual_current) )
        {
            //update_post_meta($post_id, '_price', $precio_especial);
            $product_obj->set_price($sale_price);
            $changed = true;
            if( isset($_REQUEST['param']) && $changed )
            {
                Funciones::print_r_html(__METHOD__ . " producto changed price de $precio_actual_current a sale $sale_price.");
            }
        }
        //dejo precio normal
        elseif( $precio_actual_current !== $regular_price_current && $precio_actual_current != $regular_price_current )
        {
            // $precio_normal = get_post_meta($post_id, '_regular_price', true);                        
            //update_post_meta($post_id, '_price', $precio_normal);
            $regular_price_current = $product_obj->get_regular_price();
            $product_obj->set_price($regular_price_current);
            $changed = true;
            if( isset($_REQUEST['param']) && $changed )
            {
                Funciones::print_r_html(__METHOD__ . " producto changed price de $precio_actual_current a regular $regular_price_current.");
            }
        }
        if( $changed && $save_on_changes )
        {
            $res = $product_obj->save();
            return $res;
        }
        else
        {
            if( isset($_REQUEST['param']) && $changed )
            {
                Funciones::print_r_html(__METHOD__ . " producto changed price oferta.");
            }
            return $changed;
        }
    }

    public function update_product_stock($product_obj, $stock, $save_on_changes = false)
    {
        $changed = false;
        if( $stock != Funciones::get_stock_ilimitado_prod_bsale() )
        {
            $stock_current = $product_obj->get_stock_quantity();

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " Comparo stocks  actual: $stock_current != nuevo: $stock");
            }

            //solo si hay cambois de stock
            if( $stock_current != $stock )
            {
                $product_obj->set_stock_quantity($stock);
                $changed = true;

                if( $stock > 0 )
                {
                    //update_post_meta($post_id, '_stock_status', 'instock');
                    $product_obj->set_stock_status('instock');
                    $changed = true;
                }
                else
                {
                    //update_post_meta($post_id, '_stock_status', 'outofstock');
                    $product_obj->set_stock_status('outofstock');
                    $changed = true;
                }

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " stock cambiado, ajusto stock");
                }


                if( !$product_obj->get_manage_stock() )
                {
                    $product_obj->set_manage_stock('yes');
                    $changed = true;
                }
            }
        }
        //no control de stock
        else
        {
            if( $product_obj->get_stock_status() !== 'instock' )
            {
                $product_obj->set_stock_status('instock');
                $changed = true;
            }

            //desactivo control de stock
            if( $product_obj->get_manage_stock() )
            {
                $product_obj->set_manage_stock('no');
                $changed = true;
            }
        }
        if( $changed && $save_on_changes )
        {
            $res = $product_obj->save();
            return $res;
        }
        else
        {
            if( isset($_REQUEST['param']) && $changed )
            {
                Funciones::print_r_html(__METHOD__ . " producto , changed stock.");
            }
            return $changed;
        }
    }

    /**
     * guarda datos de producto if $changed=true
     * @param type $product_obj
     * @param type $changed
     */
    public function save_product($product_obj, $changed)
    {
        $is_variacion = $product_obj instanceof WC_Product_Variation;
        $post_id = $product_obj->get_id();
        $sku = $product_obj->get_sku();

        $tipo = $is_variacion ? 'variacion' : 'rpducto simple';
        //si hay cambios por guardar
        if( $changed )
        {
            $resv = $product_obj->save();

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("save $tipo $post_id: $resv cambios guardados");
            }
        }
        else
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("save $tipo $post_id sku=$sku, no hay cambios por guardar");
            }
            $resv = 0;
        }
        return $resv;
    }

    /**
     * guarda stock_por_sucursal_html en prod simple o variacion
     * @param type $product_obj
     * @param type $stock_por_sucursal_html
     */
    public function save_stock_sucursal_html($product_obj, $stock_por_sucursal_html)
    {
        $is_variacion = $product_obj instanceof WC_Product_Variation;
        $post_id = $product_obj->get_id();
        $sku = $product_obj->get_sku();
        $tipo = $is_variacion ? 'variacion' : 'producto simple';

        if( !empty($stock_por_sucursal_html) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($stock_por_sucursal_html, "wc: update $tipo id=$post_id sku=$sku, stock_por_sucursal_html");
            }
            update_post_meta($post_id, "stock_{$sku}", $stock_por_sucursal_html);
            update_post_meta($post_id, "stock_por_sucursal_html", $stock_por_sucursal_html);
        }
        else
        {
            delete_post_meta($post_id, "stock_{$sku}");
            delete_post_meta($post_id, "stock_por_sucursal_html");
        }
        return true;
    }
}
