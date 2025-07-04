<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ProductoBsale
 *
 * @author angelorum
 */
class ConsolidadoBsale extends BsaleBase
{

    function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_CONSOLIDADO_BSALE_PRODS');

        $this->set_table_name($table_name);
    }

    public function get_file_name()
    {
        $file = dirname(__FILE__) . '/../logs/bsale_consolidado.json';

        return $file;
    }

    /**
     * filename base para archivo json con precios con impuesto
     * @param type $lp_id
     * @return string
     */
    public function get_file_name_precios($lp_id)
    {
        $file = dirname(__FILE__) . '/../logs/bsale_consolidado_precios' . $lp_id . '.json';

        return $file;
    }

    /**
     * filename base para archivos json con stock por sucursal
     * @param type $stock_id
     * @return string
     */
    public function get_file_name_stocks($stock_id)
    {
        $file = dirname(__FILE__) . '/../logs/bsale_consolidado_precios' . $stock_id . '.json';

        return $file;
    }

    /**
     * actualiza stock de packs en consolidad
     */
    public function update_stock_packs()
    {
        $skus_arr = array();

        $prods = new ProductoBsale();
        $table_prods = $prods->get_table_name();

        $vars = new VariantesProductoBsale();
        $table_vars = $vars->get_table_name();

        $sql = "SELECT p.id as product_id, p.name as prod_name, p.pack_details as pack_details,
                v.id as var_id, v.code as sku
                FROM `$table_prods` p,  $table_vars v where p.es_pack>0
                and p.id = v.product_id";

        global $wpdb;
        $result = $wpdb->get_results($sql, ARRAY_A);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($result, __METHOD__ . " sql= '$sql' prods packs");
        }

        $cantidad = 0;
        //por cada producto que tiene pack
        foreach( $result as $v )
        {
            $product_id = $v['product_id'];
            $var_id = $v['var_id'];
            $sku = $v['sku'];
            $pack_details = $v['pack_details'];

            //convierto pack details a array
            if( empty($pack_details) )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($v, __METHOD__ . " pack details está vacío, se omite");
                }
                continue;
            }
            //convierto a array
            $pack_details_arr = json_decode($pack_details, true);

            //obtengo el menor stock para colocar como stock de la variación del prod pack
            $menor_stock = $this->get_menor_stock_pack($pack_details_arr);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " menor stock para este pack: $menor_stock");
            }

            if( $menor_stock < 0 )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " menor stock es menor a cero: $menor_stock, lo dejo en cero ");
                }
                $menor_stock = 0;
            }

            //actualizo stock para esta variación
            $res = $this->update_stock_variacion($var_id, $menor_stock);
            $cantidad += $res;
            $skus_arr[] = $sku;
        }

        return array( 'cantidad' => $cantidad, 'skus' => $skus_arr );
    }

    /**
     * obtiene stock de cada variación del pack y retorna el menor
     * @param type $pack_details_arr
     */
    public function get_menor_stock_pack($pack_details_arr)
    {
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($pack_details_arr, __METHOD__ . " pack details a recorrer");
        }
        $menor_stock = -1;

        if( !is_array($pack_details_arr) )
        {
            return $menor_stock;
        }

        //sucrsales de bsale desde las que debo obtener el stock
        $otras_sucursales = Funciones::get_sucursales_bsale();
        $sucursales_array = array_merge(array( Funciones::get_matriz_bsale() ), $otras_sucursales);

        $sucursales_array = array_unique($sucursales_array);

        //dejo las ids como claves
        $suc_ids_arr = array();

        $stock_obj = new StockProductosBsale();

        foreach( $sucursales_array as $s )
        {
            $suc_ids_arr[$s] = $s;
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($suc_ids_arr, __METHOD__ . " sucursales ids desde donde obtener stock");
        }

        $stock_vars = array();
        //marca si el pack tiene variaciones o está creado a nivel de produccto
        $has_variation = false;

        foreach( $pack_details_arr as $p )
        {
            //solo packs a nivel de variacion
            if( !isset($p['variant']) )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($p, __METHOD__ . " error, p['variant'] no existe. Se omite");
                }
                continue;
            }
            $quantity = $p['quantity'];
            $var_id = isset($p['variant']['id']) ? $p['variant']['id'] : -1;

            if( $var_id <= 0 )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($p, __METHOD__ . " error, p['variant']['id'] es z00 0 no existe. Se omite");
                }
                continue;
            }
            //sí está creado a nivel de variación
            $has_variation = true;
            //obtengo el stock de este pack en las sucursales de bsale seleccionadas en config
            $stock_sucursales = $stock_obj->get_stock_sucursales_producto($var_id);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($stock_sucursales, __METHOD__ .
                        " stock disponible para var=$var_id en TODAs las sucursales de Bsale");
            }

            $stock_vars[$var_id] = $this->get_total_stock_var_pack($var_id, $stock_sucursales, $suc_ids_arr);
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($stock_vars, __METHOD__ . " stock de cada variacion del pack:");
        }

        //no se econtró stock
        if( !$has_variation || count($stock_vars) <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($stock_vars, __METHOD__ . " pack no tiene variaciones, se devuelve -1:");
            }
            return -1;
        }

        $minimo = min($stock_vars);
        return $minimo;
    }

    /**
     * devuelve el total de stock disponible para una variacion en las sucursales indicadas
     * @param type $stock_sucursales
     * @param type $suc_ids_arr
     */
    public function get_total_stock_var_pack($var_id, $stock_sucursales, $suc_ids_arr)
    {
        $total_stock = 0;

        //recorro stock de todas las sucursales
        foreach( $stock_sucursales as $s )
        {
            $suc_id = $s['sucursal_id'];

            //esta sucursal está dentro de las desde las que se obtiene el stock?
            if( !isset($suc_ids_arr[$suc_id]) )
            {
                continue;
            }

            $total_stock += isset($s['quantityAvailable']) ? $s['quantityAvailable'] : 0;
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " agrego stock de suc id= $suc_id: {$s['quantityAvailable']}");
            }
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " total stock a devolver: $total_stock");
        }
        return $total_stock;
    }

    /**
     * actualiza stock de variacion en consolidado.
     * Sirve para el stock d packs
     * @global type $wpdb
     * @param type $var_id
     * @param type $stock
     */
    public function update_stock_variacion($var_id, $stock)
    {
        global $wpdb;
        $table = $this->get_table_name();

        $sql = "update $table set stock = '$stock' where var_id = '$var_id';";

        //$res = 0;
        $res = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($res, __METHOD__ . "sql= '$sql'");
        }
        return $res;
    }

    /**
     * coloca los precios (oferta, normal) de cada producto en el consolidado
     */
    public function get_consolidado_precios()
    {

        $precio_normal_lp_id = Funciones::is_update_product_price() ? Funciones::get_lp_bsale() : 0;
        $precio_oferta_lp_id = Funciones::is_update_product_price_desc() ? Funciones::get_lp_oferta_bsale() : 0;
        $precio_normal_mayorista_lp_id = Funciones::get_lp_mayorista_normal_bsale();
        $precio_oferta_mayorista_lp_id = Funciones::get_lp_mayorista_oferta_bsale();

        $arraux = array(
            'precio_normal' => $precio_normal_lp_id,
            'precio_oferta' => $precio_oferta_lp_id,
            'precio_normal2' => $precio_normal_mayorista_lp_id,
            'precio_oferta2' => $precio_oferta_mayorista_lp_id
        );

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . "lps a sincronizar");
        }
        //para cada lista de precio, creo el archivo .json
        $total = 0;
        foreach( $arraux as $k => $lp_id )
        {
            if( $lp_id <= 0 )
            {

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " lp id es cero: $k='$lp_id', se omite");
                    continue;
                }
            }
            $res = $this->get_consolidado_precios_by_lp($lp_id, $k);
            $total += $res;

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " precios de lp id $lp_id guardados en file: '$res'");
            }
        }
        return $total;
    }

    /**
     * lee precios de tabla de precios y los coloca en tabla de consolidado, en el campo precio normal u oferta, según corresponda
     * @global type $wpdb
     * @global type $wpdb
     * @param type $lp_id
     * @param type $save_in_file
     * @return type
     */
    public function get_consolidado_precios_by_lp($lp_id, $field_price_name, $save_in_file = true)
    {
        if( $lp_id <= 0 )
        {
            return null;
        }

        global $wpdb;
        $filename = $save_in_file ? $this->get_file_name_precios($lp_id) : null;

        //para consulta sql
        $precios_obj = new PreciosProductosBsale();
        $precios_table = $precios_obj->get_table_name();
        $consolidado_table = $this->get_table_name();

        //colocar el insert into
        $sql = 'SELECT '
                . 'pre.variant_id as  variant_id, pre.variantValueWithTaxes as precio '
                . 'FROM '
                . "`$precios_table` pre "
                . "WHERE pre.lp_id = '$lp_id';";

        $sql = "UPDATE `$consolidado_table` AS `dest`,
            (
                SELECT
                    variant_id, variantValueWithTaxes
                FROM
                    `$precios_table`
                WHERE
                    `lp_id` = '$lp_id'
            ) AS `src`
        SET
            `dest`.`$field_price_name` = `src`.`variantValueWithTaxes`
        WHERE
            `dest`.`var_id` = variant_id;";

        $result = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . "sync lpid= $lp_id,  sql= '$sql', resultado: $result");
        }

        return $result;
    }

    /**
     * coloca el stock total en tabla de consolidado
     * @global type $wpdb
     * @global type $wpdb
     * @param type $lp_id
     * @param type $field_price_name
     * @param type $save_in_file
     * @return type
     */
    public function get_consolidado_stocks_save_in_table()
    {
        global $wpdb;

        //para consulta sql
        $stocks_obj = new StockProductosBsale();
        $stocks_table = $stocks_obj->get_table_name();
        $consolidado_table = $this->get_table_name();

        $otras_sucursales = Funciones::get_sucursales_bsale();

        $sucursales_array = array_merge(array( Funciones::get_matriz_bsale() ), $otras_sucursales);
        $sucursales_array = array_unique($sucursales_array);
        //sucursales desde las que debosumar el stock a dejar en wc
        $sucursales_str = implode(',', $sucursales_array);

        //  //SELECT variant_id as vid, SUM(quantityAvailable) as stock_new FROM `wp_bsale_bsale_stock` group by variant_id
        //colocar el insert into

        $sql = "UPDATE `$consolidado_table` AS `dest`, 
                ( SELECT variant_id, SUM(quantityAvailable) as stock_new 
                FROM `$stocks_table` WHERE sucursal_id IN($sucursales_str) group by variant_id ) AS `src` "
                . "SET `dest`.`stock` = `src`.`stock_new` WHERE `dest`.`var_id` = variant_id;";

        global $wpdb;
        $result = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql', resultado: $result");
        }

        return $result;
    }

    /**
     * si esta´enabled la función de stock por sucursales, 
     * coloca en tabla de consolidado el stock por sucursales
     */
    public function save_stock_sucursales_consolidado($limit = 200)
    {
        //solo si mostrar stock está enabled
        if( !Funciones::is_mostrar_stock_sucursal() && !Funciones::is_mostrar_stock_sucursal_backend() )
        {
            return true;
        }
        $sucursales_obj = new SucursalesBsale();
        //suc_id=>suc_name
        $sucursales_arr = $sucursales_obj->get_all_sucursales_array();

        //por cada variación, debo buscar el stock por sucursal y guardarlo en 
        //voy leyendo variaciones ids de a 100, de la tabla de variaciones
        $variacion_obj = new VariantesProductoBsale();
        $stock = new StockProductosBsale();

        $from = 0;
        $limit = ($limit > 0) ? $limit : 10;

        //obtengo listado de variaciones
        $variaciones_arr = $variacion_obj->get_variaciones_from_db($from, $limit);
        //catidad de filas devueltas
        $cantidad = is_array($variaciones_arr) ? count($variaciones_arr) : 0;

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($variaciones_arr, __METHOD__ . " variaciones devueltas: get_variaciones_from_db($from, $limit)");
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " total variaciones devueltas: $cantidad");
        }

        //no quedan más vars por sync
        if( $cantidad <= 0 )
        {
            return $cantidad;
        }

        //obtengo los ids
        $variacion_ids_arr = array();

        //por cada variación, obengo el stock por sucursal y coloco en tabla de consolidado
        foreach( $variaciones_arr as $v )
        {
            $variant_id = $v['id'];
            $sku = $v['code'];
            $variacion_ids_arr[] = $variant_id;

            $stocks_sucursal_variacion = $stock->get_stock_sucursales_producto_array($variant_id, $sku, $sucursales_arr);

            //paso a json
            $json = json_encode($stocks_sucursal_variacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $res_update = $this->update($variant_id, 'stocks_sucursales', $json);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " resultado update json de stocks sucursales para var id #$variant_id: $res_update");
            }
        }

        //marco como sincronizadas
        $variacion_obj->set_estado_sync($variacion_ids_arr, 'sync');

        return $cantidad;
    }

    public function get_rows_from_db($from = 0, $limit = 10, $estado_sync = '')
    {
        $table = $this->get_table_name();
        //solo rows para las que el estado sync es ='', es decir, no se han sincronizado
        //sirve para el stock sucursales
        $sql = "select * from $table where estado_sync = '$estado_sync' LIMIT $from, $limit;";

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql'");
        }

        global $wpdb;

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }

    /**
     * sincroniza, de a $limit productos por pasada, los datos de prods desde bsale a wc
     * @param type $limit
     */
    public function sync_update_prods_to_wc($limit = 200)
    {
        $from = 0;
        $limit = ($limit > 0) ? $limit : 10;

        //obtengo listado de variaciones
        $rows_arr = $this->get_rows_from_db($from, $limit);
        //catidad de filas devueltas
        $cantidad = is_array($rows_arr) ? count($rows_arr) : 0;

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($rows_arr, __METHOD__ . " rows devueltas: get_variaciones_from_db($from, $limit)");
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " total rows devueltas: $cantidad");
        }

        //no quedan más vars por sync
        if( $cantidad <= 0 )
        {
            return array( 'cantidad' => $cantidad, 'skus' => array() );
        }

        //obtengo los ids
        $row_ids_arr = array();
        $skus_arr = array();

        //por cada variación, obengo el stock por sucursal y coloco en tabla de consolidado
        foreach( $rows_arr as $v )
        {
            $variant_id = $v['var_id'];
            $var_description = $v['var_description'];
            $prod_id = $v['product_id'];
            $prod_name = $v['product_name'];

            $normal_price = $v['precio_normal'];
            $price_desc = $v['precio_oferta'];
            $price_desc2 = $v['precio_normal2'];
            $price_desc3 = $v['precio_oferta2'];

            $variant_code = $v['var_code'];
            $variant_barcode = $v['var_barcode']; //no usada
            $variant_average_cost = -1; //no usada
            $variant_total_stock = $v['stock'];
            $variant_stock_sucursales = $v['stocks_sucursales'];

            //restricciones de stock
            $var_unlimited_stock = $v['var_unlimited_stock'];
            $var_allow_negative_stock = $v['var_allow_negative_stock'];

            //stock ilimitado
            if( $var_unlimited_stock == 1 )
            {
                $variant_total_stock = Funciones::get_stock_ilimitado_prod_bsale();
            }

            //stocks sin info en bsale tienen -1. Se dejan en cero
            if( $variant_total_stock < 0 )
            {
                $variant_total_stock = 0;
            }

            $row_ids_arr[] = $variant_id;

            $variant_stock = array( 'variant_total_stock' => $variant_total_stock );

            $variant_price = array(
                'price' => $normal_price,
                'price_desc' => $price_desc,
                'price_desc2' => $price_desc2,
                'price_desc3' => $price_desc3,
            );

            $product = array(
                'id' => $prod_id,
                'name' => $prod_name,
            );

            $producto_full_data = array(
                'variant_id' => $variant_id,
                'variant_description' => $var_description,
                'variant_code' => $variant_code,
                'variant_barcode' => $variant_barcode,
                'variant_average_cost' => $variant_average_cost,
                'variant_stock' => $variant_stock,
                'variant_price' => $variant_price,
                'product' => $product,
                'stocks_sucursales' => $variant_stock_sucursales,
            );

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($producto_full_data, __METHOD__ . " datos a sincronizar hacia wc:");
            }

            $wc = new WoocommerceBsale();
            $result = $wc->sync_variacion_wc($producto_full_data);

            $skus_arr[] = $producto_full_data;
        }

        //marco como sincronizadas
        $this->set_estado_sync($row_ids_arr, '1');

        return array( 'cantidad' => $cantidad, 'skus' => $skus_arr );
    }

    /**
     * coloca campo "estado_sync" de tabla db en ""
     */
    public function clear_estado_sync()
    {
        global $wpdb;
        $table = $this->get_table_name();
        $sql = "update $table set estado_sync = '';";

        $res = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($res, __METHOD__ . " sql = '$sql'");
        }

        return $res;
    }

    /**
     * coloca el campo estado sync al valor indicado, para las ids variaciones del array
     * @param type $variacion_ids_arr
     * @param type $estado_sync
     */
    public function set_estado_sync($ids_arr, $estado_sync)
    {
        global $wpdb;
        $table = $this->get_table_name();

        if( count($ids_arr) <= 0 )
        {
            return 0;
        }

        $ids_str = implode(', ', $ids_arr);
        $sql = "update $table set estado_sync = '$estado_sync' where var_id IN ($ids_str);";

        $res = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($res, __METHOD__ . "sql= '$sql'");
        }

        return $res;
    }

    /**
     * actualiza tabla de consolidado por variacion
     * @param type $variant_id
     * @param type $key
     * @param type $value
     */
    public function update($variant_id, $key, $value)
    {
        global $wpdb;

        $table = $this->get_table_name();

        $sql = "update $table set $key = '$value' where var_id = '$variant_id';";

        $res = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($res, __METHOD__ . " sql = '$sql'");
        }
    }

    /**
     * crea consulta sql que devuelve resultados para crear consolidado
     * @global type $wpdb
     * @global type $wpdb
     * @param type $save_in_file: guarda salida en archivo y devuelve path al archivo
     * @return type
     */
    public function get_consolidado($save_in_file = true)
    {
        global $wpdb;
        $filename = $save_in_file ? $this->get_file_name() : null;

        //para consulta sql
        $prods_obj = new ProductoBsale();
        $products_table = $prods_obj->get_table_name();

        $variants_obj = new VariantesProductoBsale();
        $variants_table = $variants_obj->get_table_name();

        //colocar el insert into
        $sql = 'SELECT '
                . 'p.id as product_id, p.name as product_name, p.stockControl as product_stock_control, '
                . 'p.state as product_state, p.product_type as product_type, p.es_pack as es_pack,'
                . 'v.id as var_id, v.description as var_description, v.unlimitedStock as var_unlimited_stock, '
                . 'v.allowNegativeStock as var_allow_negative_stock, v.state as var_state, v.code as var_code, '
                . 'v.barCode as var_barcode, '
                . 'v.serialNumber as var_serial_number, v.isLot as var_is_lot '
                . 'FROM '
                . "`$products_table` p, `$variants_table` v "
                . 'WHERE p.id = v.product_id order by p.id, v.id;';

        global $wpdb;

        $result = $wpdb->get_results($sql, ARRAY_A);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql'");
        }

        //guardo en archivo y devbuelvo nombre de archivo
        if( $filename )
        {
            if( $result )
            {
                $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            else
            {
                $json = null;
            }
            $res = file_put_contents($filename, $json);
            unset($result);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " archivo creado: '$filename' con $res bytes");
            }

            return $filename;
        }
        //devuelvo array con resultados
        return $result;
    }

    /**
     * lee archivo json con query de consolidado y guarda en tabla
     */
    public function save_in_db($clear_all_before_insert = true)
    {
        global $wpdb;
        $arr_info = array();

        $filename = $this->get_file_name();
        $table = $this->get_table_name();

        //limpio tabla antes de insertar?
        if( $clear_all_before_insert )
        {
            $res = $this->clear_table();
            $arr_info[] = "Borro tabla $table antes de insertar";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " truncate table '$table', res=$res");
            }
        }

        $file_str = file_get_contents($filename);

        if( empty($file_str) || $file_str === false )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " archivo '$filename' no encontrado o está vacío. Abort.");
            }
            return false;
        }

        //convierto json a array
        $products_array = json_decode($file_str, true);

        if( $products_array == null )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($products_array, __METHOD__ . " archivo '$filename' no tiene datos para insertar. Abort.");
            }
            return false;
        }

        $len_prods = count($products_array);

        if( $len_prods <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($products_array, __METHOD__ . "no hay items en '$filename' para insertar en db.");
                return false;
            }
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " items a insertar: $len_prods");
        }

        //cabecera de instrcuccion sql
        $only_header = true;
        $sql_header = $this->get_sql_insert(null, $only_header, false);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($sql_header, __METHOD__ . " sql header: ");
        }
        //por cada item, agrego una row
        $i = 0;
        //aqui guardo las instrucciones insert d sql, para insertar de a varias filas cada vez
        $sql_insert_arr = array();

        $total_prods_insertados = 0;

        foreach( $products_array as $prod )
        {
            if( isset($_REQUEST['param']) && $i <= 1 )
            {
                Funciones::print_r_html(/* $prod, */ __METHOD__ . " leo prod desde json");
            }

            //obtengo insert sql only data y lo guardo en array
            $sql_insert_row = $this->get_sql_insert($prod, false, true);

            $sql_insert_arr[] = $sql_insert_row;

            $i++;

            //si llevo 40 filas, inserto, limpio el array y vuelvo a contar
            if( count($sql_insert_arr) >= 200 )
            {
                $res1 = $this->sql_execute_from_array($sql_header, $sql_insert_arr, ',');
                $total_prods_insertados += (int) $res1;

                //limpio array
                unset($sql_insert_arr);
                $sql_insert_arr = array();
                //reinicio contador
                $i = 0;
            }
        }

        if( count($sql_insert_arr) > 0 )
        {
//por si quedaron sqls pendientes
            $res1 = $this->sql_execute_from_array($sql_header, $sql_insert_arr, ',');
            $total_prods_insertados += (int) $res1;
        }

        return $total_prods_insertados;
    }

    /**
     * sql para insertar item en db
     * @global type $wpdb
     * @param type $arr_data
     * @param type $only_header
     * @param type $only_data
     * @return string
     */
    public function get_sql_insert($arr_data, $only_header = false, $only_data = false)
    {
        global $wpdb;

        $table = $this->get_table_name();

        //$id = isset($arr_data['id']) ? $arr_data['id'] : '';
        $product_id = isset($arr_data['product_id']) ? $arr_data['product_id'] : '';

        $product_name = isset($arr_data['product_name']) ? $wpdb->_real_escape($arr_data['product_name']) : '';
        $product_stock_control = isset($arr_data['product_stock_control']) ? $arr_data['product_stock_control'] : '';
        $product_state = isset($arr_data['product_state']) ? $arr_data['product_state'] : '';
        $product_type = isset($arr_data['product_type']) ? $arr_data['product_type'] : '';

        $var_id = isset($arr_data['var_id']) ? $arr_data['var_id'] : '';
        $var_description = isset($arr_data['var_description']) ? $wpdb->_real_escape($arr_data['var_description']) : '';
        $var_unlimited_stock = isset($arr_data['var_unlimited_stock']) ? $arr_data['var_unlimited_stock'] : '';
        $var_allow_negative_stock = isset($arr_data['var_allow_negative_stock']) ? $arr_data['var_allow_negative_stock'] : '';
        $var_state = isset($arr_data['var_state']) ? $arr_data['var_state'] : '';
        $var_code = isset($arr_data['var_code']) ? $wpdb->_real_escape($arr_data['var_code']) : '';
        $var_barcode = isset($arr_data['var_barcode']) ? $wpdb->_real_escape($arr_data['var_barcode']) : '';
        $var_serial_number = isset($arr_data['var_serial_number']) ? $wpdb->_real_escape($arr_data['var_serial_number']) : '';
        $var_is_lot = isset($arr_data['var_is_lot']) ? $arr_data['var_is_lot'] : '';

        $precio_normal = isset($arr_data['precio_normal']) ? $arr_data['precio_normal'] : '-1';
        $precio_oferta = isset($arr_data['precio_oferta']) ? $arr_data['precio_oferta'] : '-1';
        $precio_normal2 = isset($arr_data['precio_normal2']) ? $arr_data['precio_normal2'] : '-1';
        $precio_oferta2 = isset($arr_data['precio_oferta2']) ? $arr_data['precio_oferta2'] : '-1';
        $precio_normal3 = isset($arr_data['precio_normal3']) ? $arr_data['precio_normal3'] : '-1';
        $precio_oferta3 = isset($arr_data['precio_oferta3']) ? $arr_data['precio_oferta3'] : '-1';

        $stock = isset($arr_data['stock']) ? $arr_data['stock'] : '-1';
        $stocks_sucursales = isset($arr_data['stocks_sucursales']) ? $arr_data['stocks_sucursales'] : '';
        $estado_sync = isset($arr_data['estado_sync']) ? $arr_data['estado_sync'] : '';
        $es_pack = $arr_data['es_pack'];

        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`product_id`, `product_name`, `product_stock_control`, `product_state`, `product_type`, "
                . "`es_pack`, "
                . "`var_id`, `var_description`, `var_unlimited_stock`, `var_allow_negative_stock`, "
                . "`var_state`, `var_code`, `var_barcode`, `var_serial_number`, `var_is_lot`, "
                . "`precio_normal`, `precio_oferta`, `precio_normal2`, `precio_oferta2`, `precio_normal3`, `precio_oferta3`, "
                . "`stock`, `stocks_sucursales`, `estado_sync`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ('$product_id', '$product_name', '$product_stock_control', '$product_state', "
                . "'$product_type', '$es_pack', "
                . "'$var_id', '$var_description', '$var_unlimited_stock', '$var_allow_negative_stock', "
                . "'$var_state', '$var_code', '$var_barcode', '$var_serial_number', '$var_is_lot', "
                . "'$precio_normal', '$precio_oferta', '$precio_normal2', '$precio_oferta2', '$precio_normal3', '$precio_oferta3', "
                . "'$stock', '$stocks_sucursales', '$estado_sync')";

        if( $only_data )
        {
            return $sql_data;
        }

        return $sql_header . $sql_data . ';';
    }
}
