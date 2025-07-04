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
class ProductoBsale extends BsaleBase
{

    function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_PRODUCTS');

        $this->set_table_name($table_name);
    }

    /**
     * lee archivos .json con listado de productos y guarda en db local
     * @global type $wpdb
     * @param type $clear_all_before_insert
     * @return boolean
     */
    public function save_in_db($clear_all_before_insert = true)
    {
        global $wpdb;
        $arr_info = array();

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

        //obtengo listado de archivos a leer
        $files_arr = $this->get_file_name_products_array();

        //no hay files para cargar a la db
        if( !is_array($files_arr) || count($files_arr) <= 0 )
        {

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " no hay file spara guardar en tabla $table.");
            }
            return false;
        }


        $total_prods_insertados = 0;
        foreach( $files_arr as $file_products )
        {
            //here
            $inserted = $this->read_file_save_in_db($file_products);

            $arr_info[] = "Leo listado de productos desde '$file_products', productos agregados: $inserted.";

            $total_prods_insertados += (int) $inserted;

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("db_save_products desde file '$file_products', se insertaron $inserted rows.");
            }
        } //fin read files

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("total prods js insertados en '$table' = $total_prods_insertados");
        }

        //si solo debo sync por tipo de producto, solo dejo en db los tipos de producto selected
        $prod_types_arr = Funciones::get_bsale_product_type_allowed();
        $res_delete = 0;

        if( is_array($prod_types_arr) && count($prod_types_arr) > 0 )
        {
            $res_delete = $this->delete_prod_types_not_in($prod_types_arr);

            $total_prods_insertados -= $res_delete;
        }

// $res_delete = $this->delete_prods_duplicated_from_db();
        $arr_info[] = "Total productos jumpseller guadaros: $total_prods_insertados";

        return array( 'cantidad' => $total_prods_insertados, 'info' => $arr_info,
            'borrados' => $res_delete );
    }

    /**
     * borra de la tabla los prodcutos cuyo tipo no es uno de los del array
     * @param type $prod_types_arr
     */
    public function delete_prod_types_not_in($prod_types_arr)
    {
        if( !is_array($prod_types_arr) || count($prod_types_arr) <= 0 )
        {
            return true;
        }
        $str = implode(',', $prod_types_arr);

        $table = $this->get_table_name();

        $sql = "DELETE from $table WHERE product_type NOT IN( $str );";

        $res = $this->execute_sql($sql);

        return $res;
    }

    /**
     * lee un archivo .json con listado de prods de js y lo guarda en la db
     * @param type $filename
     */
    public function read_file_save_in_db($filename)
    {
        $file_str = file_get_contents($filename);

        if( empty($file_str) || $file_str === false )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("db_save_products(), archivo '$filename' no encontrado o está vacío. Abort.");
            }
            return false;
        }

        //convierto json a array
        $data_array = json_decode($file_str, true);
        //obtengo items a recorrer para insertar
        $products_array = isset($data_array['items']) ? $data_array['items'] : null;

        if( $products_array == null )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($data_array, __METHOD__ . " archivo '$filename' no tiene datos para insertar. Abort.");
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

            $pack_details = isset($prod['pack_details']) ? $prod['pack_details'] : null;

            if( !empty($pack_details) )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($pack_details, __METHOD__ . " producto id= {$prod['id']} es pack:");
                }
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

        $id = isset($arr_data['id']) ? $arr_data['id'] : '';
        $name = isset($arr_data['name']) ? $wpdb->_real_escape($arr_data['name']) : '';
        $description = isset($arr_data['description']) ? $wpdb->_real_escape($arr_data['description']) : '';
        $stockControl = isset($arr_data['stockControl']) ? $arr_data['stockControl'] : -1;
        $state = isset($arr_data['state']) ? $arr_data['state'] : -1;
        $product_type = isset($arr_data['product_type']['id']) ? $arr_data['product_type']['id'] : -1;
        $pack_details = isset($arr_data['pack_details']) ? json_encode($arr_data['pack_details'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $es_pack = empty($pack_details) ? 0 : 1; //es 1 si es prod pack
        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`id`, `name`, `description`, `stockControl`, `state`, `product_type`, `es_pack`, "
                . "`pack_details`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ('$id', '$name', '$description', '$stockControl', '$state', "
                . "'$product_type', '$es_pack', '$pack_details')";

        if( $only_data )
        {
            return $sql_data;
        }

        return $sql_header . $sql_data . ';';
    }

    /**
     * cantidad de prodcutos alojados en bsale
     */
    public function count()
    {

        $url = BSALE_PRODUCTOS_COUNT_URL;
        $response_array = $this->get($url);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($response_array, __METHOD__ . " productos $url");
        }

        return isset($response_array['count']) ? $response_array['count'] : -1;
    }

    /**
     * {
      "href": "https://api.bsale.cl/v1/variants.json",
      "count": 3,
      "limit": 25,
      "offset": 0,
      "items": [
      {
      "href": "https://api.bsale.cl/v1/variants/500.json",
      "id": 500,
      "description": "gap",
      "unlimitedStock": 0,
      "allowNegativeStock": 0,
      "state": 0,
      "barCode": "1351176376",
      "code": "1351176376", //este es el SKU!!
      "imagestionCenterCost": 0,
      "imagestionAccount": 0,
      "imagestionConceptCod": 0,
      "imagestionProyectCod": 0,
      "imagestionCategoryCod": 0,
      "imagestionProductId": 0,
      "serialNumber": 0,
      "prestashopCombinationId": 0,
      "prestashopValueId": 0,
      "product": {
      "href": "https://api.bsale.cl/v1/products/150.json",
      "id": "150"
      },
      "attribute_values": {
      "href": "https://api.bsale.cl/v1/variants/500/attribute_values.json"
      },
      "costs": {
      "href": "https://api.bsale.cl/v1/variants/500/costs.json"
      }
      },
     * @param type $producto_id
     * @return type
     */
    public function get_variantes_producto($producto_id)
    {
        $url = sprintf(BSALE_PRODUCTO_VARIANTES_URL, $producto_id);
        $response_array = $this->get($url);

        if( isset($response_array['items']) )
        {
            $response_array = $response_array['items'];
        }
        // Funciones::print_r_html($response_array, "get_variantes_producto( $producto_id ): $url");
        return $response_array;
    }

    /**
     * devuelve datos de una unica variante desde Bsale
     */
    public function get_variante($variante_id, $include_av_cost = false)
    {
        $url = sprintf(BSALE_VARIANTE_URL, $variante_id);
        $response_array = $this->get($url);
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("get_variante($variante_id), $url");
        }

        //debo incluir el costo de la variacion?
        if( $include_av_cost )
        {
            $response_cost = $this->get_variante_average_cost($variante_id);
            $response_array['costs_variation'] = $response_cost;
        }
        return $response_array;
    }

    public function get_variante_average_cost($variante_id)
    {
        $url = sprintf(BSALE_VARIANTE_COSTS_URL, $variante_id);
        $response_array = $this->get($url);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("get_variante_average_cost($variante_id), $url");
        }
        return $response_array;
    }

    /**
     * obtiene datos del prodcuto desde Bsale
     * @param type $producto_id
     * @return type
     */
    public function get_producto($producto_id)
    {
        $url = sprintf(BSALE_PRODUCTO_DETALLE_URL, $producto_id);
        $response_array = $this->get($url);

        return $response_array;
    }

    /**
     * devuelve tipo dep roducto from bsale
     * @param type $type_producto_id
     * @return type
     */
    public function get_producto_type($type_producto_id)
    {
        $url = sprintf(BSALE_PRODUCTO_TYPE_URL, $type_producto_id);
        $response_array = $this->get($url);

        return $response_array;
    }

    public function get_producto_type_from_table($type_producto_id)
    {
        $tbl = new TipoProductosBsale();

        $result = $tbl->get_producto_type_from_table($type_producto_id);

        //si no existe, regargo tipos prod desde Bsale
        if( !isset($result['id']) )
        {
            $tbl->get_all_save_in_db();
            //try again
            $result = $tbl->get_producto_type_from_table($type_producto_id);
        }
        return $result;

        /* $tbl = new TipoProductosTable();
          $result = $tbl->get_tipo($type_producto_id);

          //si no está en la tabla, lo busco desde Bsale
          if( !isset($result['id']) )
          {
          $nuevo_tipo = $this->get_producto_type($type_producto_id);
          //si existe en Bsale, lo agrego a la tabla
          if( isset($nuevo_tipo['name']) )
          {
          $tbl->add($nuevo_tipo);
          $result = $nuevo_tipo;
          }
          else
          {
          //este tipo no existe en bsale
          $result = null;
          }
          }
          return $result; */
    }

    public function get_producto_sku($producto_id)
    {
        //obtengo las variantes para pillar el sku
        $response_array = $this->get_variantes_producto($producto_id);

        $sku = null;
        if( !empty($response_array) && count($response_array) > 0 )
        {
            $response_array = $response_array[0];
            $sku = isset($response_array['code']) ? $response_array['code'] : null;
            // Funciones::print_r_html(null, "get_producto_sku( $producto_id ): $sku");
        }
        unset($response_array);

        return $sku;
    }

    public function get_producto_sku_by_ean($ean)
    {
        $url = BSALE_VARIANTES_URL . "?barcode=$ean";

        Funciones::print_r_html("get_producto_sku_by_ean: $url");
        $response_array = $this->get($url);

        if( isset($response_array['items']) )
        {
            $response_array = $response_array['items'];
        }
        //   Funciones::print_r_html($response_array, "get_variantes_producto( $producto_id )");
        return $response_array;
    }

    /**
     * get stock de variante (aunque diga: de producto)
     * {
      "href": "https://api.bsale.cl/v1/stocks.json",
      "count": 1049,
      "limit": 2,
      "offset": 0,
      "items": [
      {
      "href": "https://api.bsale.cl/v1/stocks/629.json",
      "id": 629,
      "quantity": 60.36,
      "quantityReserved": 0.0,
      "quantityAvailable": 60.36,
      "variant": {
      "href": "https://api.bsale.cl/v1/variants/351.json",
      "id": "351"
      },
      "office": {
      "href": "https://api.bsale.cl/v1/offices/2.json",
      "id": "2"
      }
      },
      {
      "href": "https://api.bsale.cl/v1/stocks/630.json",
      "id": 630,
      "quantity": 0.0,
      "quantityReserved": 0.0,
      "quantityAvailable": 0.0,
      "variant": {
      "href": "https://api.bsale.cl/v1/variants/351.json",
      "id": "351"
      },
      "office": {
      "href": "https://api.bsale.cl/v1/offices/1.json",
      "id": "1"
      }
      }
      ]
      }
     * @param type $producto_sku
     * @return type
     */
    public function get_stock_producto($producto_sku, $officeid = null, $variantid = null)
    {
        if( $officeid == null )
        {
            $officeid = Funciones::get_matriz_bsale();
        }

        $producto_sku = trim($producto_sku);
        $producto_sku = urlencode($producto_sku);

        if( empty($producto_sku) && empty($variantid) )
        {
            return -1;
        }

        $param = 'code';

        //si no viene $variantid, fiultro por sku o barCode
        if( empty($variantid) )
        {
            $url = BSALE_PRODUCTO_STOCK_URL . "?$param=$producto_sku&officeid=$officeid";
        }
        //si viene, prefiero $variantid
        else
        {
            $url = BSALE_PRODUCTO_STOCK_URL . "?variantid=$variantid&officeid=$officeid";
        }

        $i = 0;
        //repito dos veces, en caso de que encuentre stock <0
        do
        {

            $response_array = $this->get($url);

            if( isset($response_array['error']) || !isset($response_array['items']) )
            {
                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html($response_array, __METHOD__ . "get_stock_producto( $producto_sku, $officeid, $variantid ), url= '$url', "
                            . "error al preguntar por stock a Bsale, se asume que sí hay");
                }
                return 10000; //error de bsale, se considera que el producto sí tiene stock, pues no se sabe,
                //así que devuelvo un stock muy alto
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($response_array, "get_stock_producto( $producto_sku, $officeid, $variantid ), url= '$url'");
            }

            $response_array = $response_array['items'];
            if( count($response_array) > 0 )
            {
                $response_array = $response_array[0];
            }


            $stock = isset($response_array['quantityAvailable']) ? $response_array['quantityAvailable'] : BSALE_STOCK_SKU_NOT_EXISTS;

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("get_stock_producto( $producto_sku, stock es '$stock'");
            }

            if( $stock != -1 )
            {
                break;
            }
            $i++;
            //sleep(1);
        }
        while( $i < 0 );

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($response_array, "get_stock_producto( $producto_sku, url='$url', stock es '$stock', respuesta:");
        }

        unset($response_array);
        return $stock;
    }

    /**
     * 
     * 
     * @param type $producto_sku
     * @param type $officeid
     * @return type
     */
    public function get_precio_producto($producto_sku, $lista_precios = null, $variacion_id = null)
    {
        $producto_sku = urlencode(trim($producto_sku));

        $my_url = Funciones::get_lp_url_bsale(); //&officeid=$officeid

        if( !empty($lista_precios) && $lista_precios > 0 )
        {
            $my_url = Funciones::get_url_lp_by_id($lista_precios);
        }
        else
        {
            //si no se indica lp y no tiene lp precio normal asignada
            $lp = Funciones::get_lp_bsale();
            //no tiene lp asignada
            if( $lp <= 0 )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("get_precio_producto no lp asignada: $lp");
                }
                return -1;
            }
        }


        $url = $my_url . "?code=$producto_sku"; //&officeid=$officeid
        $response_array = $this->get($url);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($response_array, "busco precio $url");
        }

        if( isset($response_array['items']) )
        {
            $response_array = $response_array['items'];
            if( count($response_array) > 0 )
            {
                $response_array = $response_array[0];
            }
        }

        //si no se pudo, pruebo con el id
        if( !isset($response_array['variantValueWithTaxes']) && $variacion_id != null )
        {
            $url = $my_url . "?variantid=$variacion_id";
            $response_array = $this->get($url);

            //todo de nuevo
            if( isset($response_array['items']) )
            {
                $response_array = $response_array['items'];

                if( count($response_array) > 0 )
                {
                    $response_array = $response_array[0];
                }
            }
        }
        /* if( Funciones::get_pais() === 'CL' )
          {
          $stock = isset($response_array['variantValueWithTaxes']) ? (int) $response_array['variantValueWithTaxes'] : -1;
          }
          //peru permite decimales
          else
          { */
        $stock = isset($response_array['variantValueWithTaxes']) ? $response_array['variantValueWithTaxes'] : -1;
        // }

        unset($response_array);
        return $stock;
    }

    /**
     * para encontrar last file de productos y devolver offset
     */
    public function get_last_offset_data()
    {
        $file_names = $this->get_file_name_products_array();

        if( !is_array($file_names) || count($file_names) <= 0 )
        {
            return null;
        }
        $last_file = $this->get_last_file($file_names);

        if( empty($last_file) )
        {
            return null;
        }

        $arr_offset = $this->get_last_file_offset($last_file);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($arr_offset, __METHOD__ . " ultimo archivo: $last_file, datos de count, offset y limit:");
        }

        return $arr_offset;
    }

    /**
     * descarga todos los productos de bsale y los guarda en archivos .json en /lib/logs
     * @return int
     */
    public function get_all()
    {
        $log = new BsaleShutdown();

        $limit = 50; //xxx sacar, cambiar por 200
        $offset = 0;
        $total = $this->count();
        $i = 1; //para el loop. este se declara afuera, ya que puede cambirse en caso de que el script haya fallado 
        //por timeout
        //
        //para saber si se llama a esta función por primera vez, o después de un timeout
        $times_retry = isset($_REQUEST['times_retry']) ? (int) $_REQUEST['times_retry'] : 0;

        //borro listado de prods anterior, solo la primera vez
        if( $times_retry <= 0 )
        {
            $this->delete_file_name_products();
        }
        //encuentro el ultimo archivo creado. Saco de allí el offset y el total, y continúo
        //pues esta función quedó pendiente (timeout) y se está llamando por segunda vez.
        //ya hay archivos json descargados desde Bsale. Solo debo continuar donde quedé.
        else
        {
            $arr_offset = $this->get_last_offset_data();

            if( isset($arr_offset['count']) && isset($arr_offset['limit']) && isset($arr_offset['offset']) )
            {
                $last_count = $arr_offset['count'];
                $last_limit = $arr_offset['limit'];
                $last_offset = $arr_offset['offset'];

                //nuevo total, es la cantidad de productos que quedan por descargar
                $total = $last_count - $last_offset - $last_limit;
                //incremento el offset
                $offset = $last_offset + $last_limit;
                $i = (int) (($offset / $last_limit) + 1);

                $msg = __METHOD__ . " times retry: $times_retry, empiezo desde i=$i, total=$total, offset=$offset " .
                        print_r($arr_offset, true) . "\n";
                $log->loginfo($msg);
            }
            else
            {
                $msg = __METHOD__ . " times retry: $times_retry, no data de last offset encontrado:" .
                        print_r($arr_offset, true) . "\n";
                $log->loginfo($msg);
            }
        }

        if( $total <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " No hay lista de productos, total = $total");
            }
            return $total;
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " Productos Bsale get_all: $total productos a descargar");
        }
        //$total = 300;

        $respuesta = array();

        //recorro y voy guardando en la db temporal

        for(; $total > 0; $total -= $limit, $i++ )
        {
            $url = sprintf(BSALE_PRODUCTOS_LISTADO_URL, $limit, $offset);
            $url .= "&state=0"; //solo activos

            $response_raw = $this->get($url, true);

            //priomera pasada, obtengo el total de productos desde la respuesta del json
            if( $i == 1 )
            {
                $response_array = json_decode($response_raw, true);
                $total = isset($response_array['count']) ? (int) $response_array['count'] : $total;

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " total REAL de productos: $total");
                }
                $msg = __METHOD__ . " i=$i es 1, obtengo total real: $total\n";
                $log->loginfo($msg);
            }

            $file = $this->get_file_name_products($i);
            $res = file_put_contents($file, $response_raw);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " Productos Bsale guardados en $file, from url= '$url', faltan $total, con offset = $offset");
            }

            //log
            $msg = __METHOD__ . " url=$url de un total de $total items, i=$i guardo en file='$file'\n";
            $log->loginfo($msg);

            $offset += $limit;
            // sleep(1);
        }
        return $total;
    }

    public function get_file_name_products($page = '')
    {
        $file = dirname(__FILE__) . '/../logs/productos_bsale_%s.json';
        $file = sprintf($file, $page);

        return $file;
    }

    public function get_file_name_products_array()
    {
        $path = dirname(__FILE__) . '/../logs/'; //productos_jumpseller_%s.json';
        $files_arr = array();

        $i = 0;

        if( !file_exists($path) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_file_name_products_array(), folder '$path' no existe.");
            }
            return $files_arr;
        }

// Open the directory  
        $handle = opendir($path);

        if( $handle === false )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_file_name_products_array(), folder '$path' no se pudo leer.");
            }
            return $files_arr;
        }

// Loop through the directory  
        while( false !== ($file = readdir($handle)) )
        {
// Check the file we're doing is actually a file  
            if( !is_file($path . $file) )
            {
                continue;
            }

            $file_info = pathinfo($path . $file);

//solo files .json
            if( !isset($file_info['extension']) || $file_info['extension'] !== 'json' )
            {
                continue;
            }

            if( !isset($file_info['filename']) )
            {
                continue;
            }

            $filename = $file_info['filename'];

//file contiene "productos_jumpseller_"??
            if( strpos($filename, 'productos_bsale_') === false )
            {
                continue;
            }
//agrego archivo a array
            $files_arr[] = $path . $file;
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($files_arr, "get_file_name_products_array(), respuesta");
        }
        return $files_arr;
    }

    /**
     * borra archivos .json con listados de productos js de la carpeta /logs/
     * @return string|array
     */
    public function delete_file_name_products()
    {
        $path = dirname(__FILE__) . '/../logs/'; //productos_jumpseller_%s.json';
        $files_arr = array();

        $i = 0;

        if( !file_exists($path) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_file_name_products_array(), folder '$path' no existe.");
            }
            return $files_arr;
        }

// Open the directory  
        $handle = opendir($path);

        if( $handle === false )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_file_name_products_array(), folder '$path' no se pudo leer.");
            }
            return $files_arr;
        }

// Loop through the directory  
        while( false !== ($file = readdir($handle)) )
        {
// Check the file we're doing is actually a file  
            if( !is_file($path . $file) )
            {
                continue;
            }

            $file_info = pathinfo($path . $file);

//solo files .json
            if( !isset($file_info['extension']) || $file_info['extension'] !== 'json' )
            {
                continue;
            }

            if( !isset($file_info['filename']) )
            {
                continue;
            }

            $filename = $file_info['filename'];

//file contiene "productos_jumpseller_"??
            if( strpos($filename, 'productos_bsale_') === false )
            {
                continue;
            }
//agrego archivo a array
            $files_arr[] = $path . $file;
            unlink($path . $file);
        }

        if( isset($_REQUEST['param']) )
        {
            // Funciones::print_r_html($files_arr, "get_file_name_products_array(), files borrados");
        }
        return true;
    }

    public function is_product_to_skip($sku = null)
    {
        $sku_arr = Funciones::get_skus_to_skip();

        if( empty($sku_arr) || !is_array($sku_arr) )
        {
            return false;
        }
        //recorro cada coincidencia
        foreach( $sku_arr as $text )
        {
            if( empty($text) )
            {
                continue;
            }
            $len = strlen($text);

            $start = substr($sku, 0, $len);
            if( strcasecmp($start, $text) == 0 )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($sku_arr, "is_product_to_skip($sku), skip sku='$sku'. Prefijos prohibidos:");
                }
                //skip product
                return true;
            }
        }

        return false;
    }

    public function get_stock_producto_sucursales($sku_or_ean, $variantid = null)
    {
        //listado de sucursales, la primera es la matriz
        $otras_sucursales = Funciones::get_sucursales_bsale();

        $sucursales_array = array_merge(array( Funciones::get_matriz_bsale() ), $otras_sucursales);

        $sucursales_stock = array();
        foreach( $sucursales_array as $sucursal_id )
        {
            if( empty($sucursal_id) )
            {
                continue;
            }
            $stock = $this->get_stock_producto($sku_or_ean, $sucursal_id, $variantid);
            $sucursales_stock[$sucursal_id] = $stock;
        }
        return $sucursales_stock;
    }

    /**
     * devuelve el proximo numero de serie disponible para la variante indicada
     * @param type $variante_id
     * @param type $office_id
     * @return type
     */
    public function get_serie_variante($variante_id, $office_id = null, $quantity = 1)
    {
        $office_id = $office_id == null ? Funciones::get_matriz_bsale() : $office_id;

        $url = sprintf(BSALE_SERIALS_URL, $variante_id) . "?officeid={$office_id}";
        $response = $this->get($url);
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($response, "get_serie_variante($variante_id, $office_id) url = '$url'");
        }
        return $response;
    }

    /**
     * deveulve el sgte numero de serie disponible para la variacion indicada
     * @param type $variante_id
     * @param type $office_id
     */
    public function get_next_serie_variante($variante_id, $quantity = 1, $office_id = null)
    {
        $resp = $this->get_serie_variante($variante_id, $office_id, $quantity);

        //veo si hay serie disponibles
        if( !isset($resp['items']) || count($resp['items']) <= 0 )
        {
            return null;
        }

        $arr_serials = array();
        $serials_count = 0;

        foreach( $resp['items'] as $s )
        {
            $stock = $s['stockAvailable'];

            //si no hay stock o serie, continuo
            if( $stock <= 0 || empty($s['serialNumber']) )
            {
                continue;
            }
            //coloco seriales
            $stock_restante = $stock;

            //recorro seriales
            for( $ii = 0; ($ii < $quantity) && ($stock_restante > 0); $ii++ )
            {
                $arr_serials[] = $s['serialNumber'];
                $serials_count++;
                //decremento stock usado
                $stock_restante--;

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html("get_next_serie_variante id=$variante_id, serie='{$s['serialNumber']}' con stock=$stock, "
                            . "la asigno a una unidad, dejando stock restante de serie en $stock_restante");
                }
            }

            if( $serials_count >= $quantity )
            {
                break;
            }
        }
        //devuelvo string con nros de serie
        if( count($arr_serials) > 0 )
        {
            $seriales_txt = $arr_serials; //implode(',', $arr_serials);
        }
        else
        {
            $seriales_txt = null;
        }

        return $seriales_txt;
    }
}
