<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of VariantesProductoBsale
 *
 * @author angelorum
 */
class VariantesProductoBsale extends BsaleBase
{

    function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_VARIANTS');

        $this->set_table_name($table_name);
    }

    public function clear_table()
    {
        global $wpdb;
        $table = $this->get_table_name();
        $sql = "TRUNCATE TABLE $table;";

        $res = $wpdb->query($sql);

        return $res;
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
        $files_arr = $this->get_file_name_variants_array();

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

        Funciones::print_r_html("total prods js insertados en '$table' = $total_prods_insertados");

        $res_delete = 0;
// $res_delete = $this->delete_prods_duplicated_from_db();
        $arr_info[] = "Total productos jumpseller guadaros: $total_prods_insertados";

        return array( 'cantidad' => $total_prods_insertados, 'info' => $arr_info,
            'duplicados' => $res_delete );
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
        $description = isset($arr_data['description']) ? $wpdb->_real_escape($arr_data['description']) : '';
        $unlimitedStock = isset($arr_data['unlimitedStock']) ? $arr_data['unlimitedStock'] : -1;
        $allowNegativeStock = isset($arr_data['allowNegativeStock']) ? $arr_data['allowNegativeStock'] : -1;
        $state = isset($arr_data['state']) ? $arr_data['state'] : -1;
        $code = isset($arr_data['code']) ? $wpdb->_real_escape($arr_data['code']) : '';
        $barCode = isset($arr_data['barCode']) ? $wpdb->_real_escape($arr_data['barCode']) : '';
        $serialNumber = isset($arr_data['serialNumber']) ? $wpdb->_real_escape($arr_data['serialNumber']) : '';
        $isLot = isset($arr_data['isLot']) ? $arr_data['isLot'] : '';
        $product_id = isset($arr_data['product']['id']) ? $arr_data['product']['id'] : -1;

        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`id`, `description`, `unlimitedStock`, `allowNegativeStock`, `state`, `code`, `barCode`, `serialNumber`, `isLot`, `product_id`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ('$id', '$description', '$unlimitedStock', '$allowNegativeStock', "
                . "'$state', '$code', '$barCode' , '$serialNumber', "
                . "'$isLot', '$product_id')";

        if( $only_data )
        {
            return $sql_data;
        }

        return $sql_header . $sql_data . ';';
    }

    public function count()
    {

        $url = BSALE_VARIANTES_COUNT_URL;
        $response_array = $this->get($url);

        Funciones::print_r_html("VariantesProductoBsale->count variaciones en Bsale url='$url'");

        return isset($response_array['count']) ? $response_array['count'] : -1;
    }

    /**
     * para encontrar last file de variaciones y devolver offset
     */
    public function get_last_offset_data()
    {
        $file_names = $this->get_file_name_variants_array();

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
     * descargo variaciones desde bsale y guardo en archivos json
     * @return type
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
            $this->delete_file_name_variants();
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
                Funciones::print_r_html(__METHOD__ . " No hay lista de variants, total = $total");
            }
            return $total;
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " $total vatiants a descargar");
        }



        //recorro y voy guardando en la db temporal

        for(; $total > 0; $total -= $limit, $i++ )
        {
            $url = BSALE_VARIANTES_URL . "?limit=$limit&offset=$offset";
            $url .= '&state=0'; //solo activos

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

            $file = $this->get_file_name_variants($i);
            $res = file_put_contents($file, $response_raw);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " Productos Bsale guardados en $file, from url= '$url'");
            }

            //log
            $msg = __METHOD__ . " url=$url de un total de $total items, i=$i guardo en file='$file'\n";
            $log->loginfo($msg);

            $offset += $limit;

            //sleep(1);
            // break;
        }
        return $total;
    }

    public function get_file_name_variants($page = '')
    {
        $file = dirname(__FILE__) . '/../logs/variaciones_bsale_%s.json';
        $file = sprintf($file, $page);

        return $file;
    }

    public function get_file_name_variants_array()
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
            if( strpos($filename, 'variaciones_bsale_') === false )
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
    public function delete_file_name_variants()
    {
        $path = dirname(__FILE__) . '/../logs/'; //productos_jumpseller_%s.json';
        $files_arr = array();

        $i = 0;

        if( !file_exists($path) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(", folder '$path' no existe.");
            }
            return $files_arr;
        }

// Open the directory  
        $handle = opendir($path);

        if( $handle === false )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("folder '$path' no se pudo leer.");
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
            if( strpos($filename, 'variaciones_bsale_') === false )
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

    /**
     * devuelve la variacion dede Bsale
     * @param type $sku
     * @return type
     */
    public function get_variacion_by_sku($sku)
    {
        $sku = urlencode($sku);
        sleep(1);
        //Funciones::print_r_html("get_variacion_by_sku sku= '$sku'");
        $url = BSALE_VARIANTES_URL . "?code=$sku"; //fields=[code,id]&
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("get_variacion_by_sku {$sku}: $url");
        }
        $response_array = $this->get($url);
        if( isset($response_array['items']) )
        {
            $response_array = $response_array['items'];
        }

        $len = is_array($response_array) ? count($response_array) : 0;
        for( $i = 0; $i < $len; $i++ )
        {
            $v = $response_array[$i];

            return $v;
        }

        return null;
    }

    public function get_atributos_variante($variante_id)
    {
        $url = sprintf(BSALE_PRODUCTO_VARIANTES_URL, $variante_id);
        $response = $this->get($url);

        Funciones::print_r_html($response, " get_atributos_variante( $variante_id)");
    }

    public function get_stock_producto($producto_sku, $officeid = null)
    {
        if( $officeid == null )
        {
            $officeid = Funciones::get_matriz_bsale();
        }

        $url = BSALE_PRODUCTO_STOCK_URL . "?code=$producto_sku&officeid=$officeid";
        $response_array = $this->get($url);

        //  Funciones::print_r_html( $response_array, "get_stock_producto( $producto_sku, $officeid )" );
        if( isset($response_array['items']) )
        {
            $response_array = $response_array['items'];
        }
        //xxx dejar stock en 0
        $stock = isset($response_array['quantityAvailable']) ? $response_array['quantityAvailable'] : 0;

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
    public function get_precio_producto($producto_sku, $officeid = null)
    {
        if( $officeid == null )
        {
            $officeid = Funciones::get_matriz_bsale();
        }
        $url = Funciones::get_lp_url_bsale() . "?code=$producto_sku&officeid=$officeid";
        $response_array = $this->get($url);

        // Funciones::print_r_html( $response_array, "get_precio_producto( $producto_sku, $officeid )" );

        if( isset($response_array['items']) )
        {
            $response_array = $response_array['items'];
        }
        //xxx dejar precio en 0
        $stock = isset($response_array['variantValueWithTaxes']) ? $response_array['variantValueWithTaxes'] : 0;

        unset($response_array);
        return $stock;
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
     * devuelve array con listado de variaciones desde la db
     * @param type $from
     * @param type $limit
     */
    public function get_variaciones_from_db($from = 0, $limit = 10, $estado_sync = '')
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
     * coloca el campo estado sync al valor indicado, para las ids variaciones del array
     * @param type $variacion_ids_arr
     * @param type $estado_sync
     */
    public function set_estado_sync($variacion_ids_arr, $estado_sync)
    {
        if( count($variacion_ids_arr) <= 0 )
        {
            return 0;
        }

        global $wpdb;
        $table = $this->get_table_name();

        $ids_str = implode(', ', $variacion_ids_arr);
        $sql = "update $table set estado_sync = '$estado_sync' where id IN ($ids_str);";

        $res = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($res, __METHOD__ . "sql= '$sql'");
        }

        return $res;
    }
}
