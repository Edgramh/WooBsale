<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PreciosProductosBsale
 *
 * @author angelorum
 */
class PreciosProductosBsale extends BsaleBase
{

    function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_PRICES');

        $this->set_table_name($table_name);
    }

    public function clear_table($lp_id = 0)
    {
        global $wpdb;
        $table = $this->get_table_name();

        //borro toda la tabla
        if( $lp_id <= 0 )
        {
            $sql = "TRUNCATE TABLE $table;";
        }
        else
        {
            $sql = "DELETE FROM $table where lp_id = '$lp_id';";
        }

        $res = $wpdb->query($sql);

        return $res;
    }

    /**
     * lee archivos .json con listado de productos y guarda en db local
     * @global type $wpdb
     * @param type $clear_all_before_insert
     * @return boolean
     */
    public function save_in_db($lp_id, $clear_all_before_insert = true)
    {
        global $wpdb;
        $arr_info = array();

        $table = $this->get_table_name();

//limpio tabla antes de insertar?
        if( $clear_all_before_insert )
        {
            $res = $this->clear_table($lp_id);
            $arr_info[] = "Borro tabla $table antes de insertar";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " truncate table '$table', res=$res");
            }
        }

//obtengo listado de archivos a leer
        $files_arr = $this->get_file_name_precios_array($lp_id);

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
            $inserted = $this->read_file_save_in_db($lp_id, $file_products);

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
    public function read_file_save_in_db($lp_id, $filename)
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
        $sql_header = $this->get_sql_insert($lp_id, null, $only_header, false);

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
            $sql_insert_row = $this->get_sql_insert($lp_id, $prod, false, true);

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
    public function get_sql_insert($lp_id, $arr_data, $only_header = false, $only_data = false)
    {
        global $wpdb;

        $table = $this->get_table_name();

        // $id = isset($arr_data['id']) ? $arr_data['id'] : '';
        $variantValue = isset($arr_data['variantValue']) ? $arr_data['variantValue'] : -1;
        $variantValueWithTaxes = isset($arr_data['variantValueWithTaxes']) ? $arr_data['variantValueWithTaxes'] : -1;
        $variant_id = isset($arr_data['variant']['id']) ? $arr_data['variant']['id'] : -1;

        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`variant_id`, `lp_id`, `variantValue`, `variantValueWithTaxes`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ( '$variant_id', '$lp_id', '$variantValue', '$variantValueWithTaxes')";

        if( $only_data )
        {
            return $sql_data;
        }

        return $sql_header . $sql_data . ';';
    }

    public function count($lp_id)
    {
        $args = array( 'wc_bsale_lista_precios_id' => $lp_id );

        $url = Funciones::get_lp_url_bsale($args) . '?limit=1&offset=0';
        $response_array = $this->get($url);

        //   Funciones::print_r_html( $response_array, $url );
        //   die();
        return isset($response_array['count']) ? $response_array['count'] : -1;
    }

    /**
     * para encontrar last file de precios y devolver offset
     */
    public function get_last_offset_data($lp_id)
    {
        $file_names = $this->get_file_name_precios_array($lp_id);

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

    public function get_all($lp_id)
    {
        $log = new BsaleShutdown();

        if( $lp_id <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " lp id no válida: '$lp_id'");
            }
            return false;
        }

        $limit = 50; //xxx sacar, cambiar por 200
        $offset = 0;
        $total = $this->count($lp_id);
        $i = 1; //para el loop. este se declara afuera, ya que puede cambirse en caso de que el script haya fallado 
        //por timeout
        //
        //para saber si se llama a esta función por primera vez, o después de un timeout
        $times_retry = isset($_REQUEST['times_retry']) ? (int) $_REQUEST['times_retry'] : 0;

        //borro listado de prods anterior, solo la primera vez
        if( $times_retry <= 0 )
        {
            $this->delete_file_name_precios($lp_id);
        }
        //encuentro el ultimo archivo creado. Saco de allí el offset y el total, y continúo
        //pues esta función quedó pendiente (timeout) y se está llamando por segunda vez.
        //ya hay archivos json descargados desde Bsale. Solo debo continuar donde quedé.
        else
        {
            $arr_offset = $this->get_last_offset_data($lp_id);

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

        $args = array( 'wc_bsale_lista_precios_id' => $lp_id );
        $base_url = Funciones::get_lp_url_bsale($args);

        if( $total <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " No hay lista de precios, total = $total");
            }
            return $total;
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " $total precios a descargar");
        }

        //recorro y voy guardando en la db temporal

        for(; $total > 0; $total -= $limit, $i++ )
        {
            // $url = BSALE_VARIANTES_URL . "?limit=$limit&offset=$offset";
            $url = $base_url . "?limit=$limit&offset=$offset";
            $response_raw = $this->get($url, true);

            //priomera pasada, obtengo el total de productos desde la respuesta del json
            if( $i == 1 )
            {
                $response_array = json_decode($response_raw, true);
                $total = isset($response_array['count']) ? (int) $response_array['count'] : $total;

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " total REAL de precios: $total");
                }
                $msg = __METHOD__ . " i=$i es 1, obtengo total real: $total\n";
                $log->loginfo($msg);
            }

            $file = $this->get_file_name_precios($lp_id, $i);
            $res = file_put_contents($file, $response_raw);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " precios Bsale guardados en $file, from url= '$url'");
            }
            //log
            $msg = __METHOD__ . " url=$url de un total de $total items, i=$i guardo en file='$file'\n";
            $log->loginfo($msg);

            $offset += $limit;
            //sleep(1);          
        }
        return $total;
    }

    public function get_file_name_precios($lp_id, $page = '')
    {
        $file = dirname(__FILE__) . '/../logs/precios_bsale_lp_%s_%s.json';
        $file = sprintf($file, $lp_id, $page);

        return $file;
    }

    public function get_file_name_precios_array($lp_id)
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

        $base_file_name = "precios_bsale_lp_$lp_id";

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
            if( strpos($filename, $base_file_name) === false )
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
    public function delete_file_name_precios($lp_id)
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
        $base_file_name = "precios_bsale_lp_$lp_id";

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
            if( strpos($filename, $base_file_name) === false )
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
     * obtiene lista de precios normales desde bsale
     * @return boolean
     */
    public function get_all_precios_productos()
    {
        $limit = 50;
        $offset = 0;
        $total = $this->count();

        if( $total <= 0 )
        {
            //Funciones::print_r_html( "No hay lista de precios" );
            return;
        }
        else
        {
            Funciones::print_r_html("get_all_precios_productos: descargando $total precios...");
        }
        //  $total = 300;
        //recorro y voy guardando en la db temporal
        for(; $total > 0; $total -= $limit )
        {
            $url = Funciones::get_lp_url_bsale() . "?limit=$limit&offset=$offset";
            //   Funciones::print_r_html( "get_all_precios_productos: $url" );

            $response_array = $this->get($url);
            if( isset($response_array['items']) )
            {
                $response_array = $response_array['items'];
            }

//devuelvo un array con con [producto_id]=>sku
            $arraux = array();
            $len = count($response_array);
            for( $i = 0; $i < $len; $i++ )
            {
                if( !isset($response_array[$i]) )
                {
                    Funciones::print_r_html($response_array, "get_all_precios_productos: no se encuentra offset $i");
                    continue;
                }
                $v = $response_array[$i];

                //requiero este dato
                if( !isset($v['variant']['id']) )
                {
                    Funciones::print_r_html(null, "no tiene variant id");
                    continue;
                }
                $variante_id = $v['variant']['id'];
                $precio = $v['variantValueWithTaxes'];
                //   $arraux[$producto_id] = $precio;

                $array_datos = array();
                $array_datos['variante_id'] = $variante_id;
                $array_datos['precio'] = $precio;
                $tabla->add($array_datos);
            }


            $offset += $limit;

            unset($response_array);
        }
        return true;
    }

    /*
     * listas de precio
     */

    public function count_lp()
    {

        $url = BSALE_LISTAS_PRECIO_URL . '?limit=1&offset=0';
        $response_array = $this->get($url);

//        Funciones::print_r_html( $response_array, $url );
        //   die();
        return isset($response_array['count']) ? $response_array['count'] : -1;
    }

    public function get_all_listas_de_precios($show = false)
    {
        // $tabla = new PreciosProductosTable();
        // $tabla->clear_all();

        $respuesta = array();

        $limit = 50;
        $offset = 0;
        $total = $this->count_lp();

        if( $total <= 0 )
        {
            //Funciones::print_r_html( "No hay lista de precios" );
            return array();
        }
        //  $total = 300;
        //recorro y voy guardando en la db temporal
        for(; $total > 0; $total -= $limit )
        {
            $url = BSALE_LISTAS_PRECIO_URL . "?limit=$limit&offset=$offset";
            $response_array = $this->get($url);
            if( isset($response_array['items']) )
            {
                $response_array = $response_array['items'];
            }

            if( $show )
            {
                Funciones::print_r_html($response_array, "get_all_listas_de_precios: $url");
            }

            if( !is_array($response_array) )
            {
                return $respuesta;
            }
            $len = count($response_array);
            for( $i = 0; $i < $len; $i++ )
            {
                $v = $response_array[$i];
                $id = $v['id'];
                $respuesta[] = $v;
                //$tabla->add( $array_datos );
            }


            $offset += $limit;

            unset($response_array);
        }
        return $respuesta;
    }
}
