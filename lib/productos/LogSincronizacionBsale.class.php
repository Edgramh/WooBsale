<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TipoProductosBsale
 *
 * @author angelorum
 */
class LogSincronizacionBsale extends BsaleBase
{  

    function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_LOG_SYNC_BSALE');

        $this->set_table_name($table_name);
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

        $product_id = isset($arr_data['product_id']) ? $arr_data['product_id'] : '-1';
        $producto_padre_id = isset($arr_data['producto_padre_id']) ? $arr_data['producto_padre_id'] : '-1';

        $tipo_producto = isset($arr_data['tipo_producto']) ? $wpdb->_real_escape($arr_data['tipo_producto']) : '';
        $nombre = isset($arr_data['nombre']) ? $wpdb->_real_escape($arr_data['nombre']) : '';
        $sku = isset($arr_data['sku']) ? $wpdb->_real_escape($arr_data['sku']) : '';
        $stock = isset($arr_data['stock']) ? $arr_data['stock'] : '-1';

        $precio_normal = isset($arr_data['precio_normal']) ? $arr_data['precio_normal'] : '-1';
        $precio_oferta = isset($arr_data['precio_oferta']) ? $arr_data['precio_oferta'] : '-1';
        $precio_especial_2 = isset($arr_data['precio_especial_2']) ? $arr_data['precio_especial_2'] : '-1';
        $precio_especial_3 = isset($arr_data['precio_especial_3']) ? $arr_data['precio_especial_3'] : '-1';


        $source_file = isset($arr_data['source_file']) ? $wpdb->_real_escape($arr_data['source_file']) : '';
        $source_cpnId = isset($arr_data['source_cpnId']) ? $arr_data['source_cpnId'] : '-1';
        $source_resource = isset($arr_data['source_resource']) ? $wpdb->_real_escape($arr_data['source_resource']) : '';
        $source_resourceId = isset($arr_data['source_resourceId']) ? $arr_data['source_resourceId'] : '-1';
        $source_topic = isset($arr_data['source_topic']) ? $wpdb->_real_escape($arr_data['source_topic']) : '-1';
        $source_action = isset($arr_data['source_action']) ? $wpdb->_real_escape($arr_data['source_action']) : '-1';
        $source_officeId = isset($arr_data['source_officeId']) ? $arr_data['source_officeId'] : '-1';
        $source_send = isset($arr_data['source_send']) ? $arr_data['source_send'] : '-1';

        $accion = isset($arr_data['accion']) ? $wpdb->_real_escape($arr_data['accion']) : '-1';
        $result = isset($arr_data['result']) ? $wpdb->_real_escape($arr_data['result']) : '-1';
        $data = isset($arr_data['data']) ? $wpdb->_real_escape($arr_data['data']) : '-1';
        $msg = isset($arr_data['msg']) ? $wpdb->_real_escape($arr_data['msg']) : '';
        $fecha = date("Y-m-d H:i:s");

        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`product_id`, `producto_padre_id`, `tipo_producto`, `nombre`, `sku`,  `stock`, "
                . "`precio_normal`, `precio_oferta`, `precio_especial_2`, `precio_especial_3`, "
                . "`source_file`, `source_cpnId`, `source_resource`, `source_resourceId`, `source_topic`, "
                . "`source_action`, `source_officeId`, `source_send`, `accion`, `result`, `data`, `msg`, `fecha`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ( '$product_id', '$producto_padre_id', '$tipo_producto', '$nombre', '$sku', '$stock', '$precio_normal', "
                . "'$precio_oferta', '$precio_especial_2', '$precio_especial_3', '$source_file', '$source_cpnId', "
                . "'$source_resource', '$source_resourceId', '$source_topic', '$source_action', '$source_officeId', "
                . "'$source_send', '$accion', '$result', '$data', '$msg', '$fecha' )";

        if( $only_data )
        {
            return $sql_data;
        }

        return $sql_header . $sql_data . ';';
    }

   
    
    public function count()
    {
        $url = BSALE_PRODUCTO_TYPE_COUNT_URL;
        $response_array = $this->get($url);

        //  Funciones::print_r_html($response_array, "TipoProductosBsale->count(): $url");

        return isset($response_array['count']) ? $response_array['count'] : -1;
    }

    /**
     * guarda tipos de producto en db
     * @return boolean
     */
    public function insert($data_arr)
    {
        $table = $this->get_table_name();

        $sql = $this->get_sql_insert($data_arr, false, false);
        $res = $this->execute_sql($sql);

        $total_prods_insertados = (int) $res;

        return $total_prods_insertados;
    }

    /**
     * devuelve ultimo registro de sync con bsale del producto y sus variaciones
     * usado para mostrar en pag de edicion del producto la ultima sync con bsale
     * @param type $product_id
     */
    public function get_last_log_product($product_id)
    {
        $product = wc_get_product($product_id);

        //nada que hacer
        if( !$product )
        {
            return null;
        }

        //listado de variaciones
        $variations_ids_arr = $product->get_children();

        $limit = 10;
        $arr_product_log = $this->get_product_log($product_id, -1, null, $limit);

        $arr_vars_log = array();

        if( is_array($variations_ids_arr) && count($variations_ids_arr) > 0 )
        {
            foreach( $variations_ids_arr as $var_id )
            {
                $arraux = $this->get_product_log(-1, $var_id, null, $limit);
                if( isset($arraux[0]) )
                {
                    $arr_vars_log[] = $arraux[0];
                }
            }
        }

        $array = array( 'product_log' => $arr_product_log,
            'variaciones_log' => $arr_vars_log );

        return $array;
    }

    /**
     * devuelve html con hostorial de sync del prod y sus variaciones
     * @param type $product_id
     */
    public function get_last_log_product_html($product_id)
    {
        $data_arr = $this->get_last_log_product($product_id);
        //arrays
        $product_log = isset($data_arr['product_log']) ? $data_arr['product_log'] : null;
        $variaciones_log = isset($data_arr['variaciones_log']) ? $data_arr['variaciones_log'] : null;

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($product_log, "historial producto");
            Funciones::print_r_html($variaciones_log, "historial variaciones");
        }


        if( is_array($product_log) && count($product_log) > 0 )
        {
            $html_producto = '<p><strong>Historial de producto</strong></p>';
            $html_producto .= $this->get_html_product_log($product_log);
        }
        if( is_array($variaciones_log) && count($variaciones_log) > 0 )
        {
            $html_variacion = '<p><strong>Historial de variaciones</strong></p>';
            $html_variacion .= $this->get_html_product_log($variaciones_log);
        }

        $html = '';

        if( isset($html_producto) )
        {
            $html .= $html_producto;
        }
        if( isset($html_variacion) )
        {
            $html .= $html_variacion;
        }

        return $html;
    }

    /**
     * devuelve html de una o varias filas de historial de producto
     * @param type $product_arr
     */
    public function get_html_product_log($product_arr)
    {
        if( !is_array($product_arr) || count($product_arr) <= 0 )
        {
            return '';
        }
        //qué datos mostrar
        $is_update_normal_price = Funciones::is_update_product_price();
        $is_update_sale_price = Funciones::is_update_product_price_desc();
        $is_update_price_may = Funciones::is_update_product_price_mayorista();
        $is_update_stock = Funciones::is_update_product_stock();

        $th_arr = array( 'Nombre', 'SKU' );
        if( $is_update_stock )
        {
            $th_arr[] = 'stock';
        }

        if( $is_update_normal_price )
        {
            $th_arr[] = 'precio normal';
        }
        if( $is_update_sale_price )
        {
            $th_arr[] = 'precio oferta';
        }
        if( $is_update_price_may )
        {
            $th_arr[] = 'precio may normal';
            $th_arr[] = 'precio may oferta';
        }
        $th_arr[] = 'accion';
        $th_arr[] = 'gatillado por';
        $th_arr[] = 'resultado';
        $th_arr[] = 'mensaje';
        $th_arr[] = 'fecha';

        $html = '<table class="hist_prod"><thead><tr>';

        foreach( $th_arr as $th )
        {
            $html .= "<th>$th</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach( $product_arr as $p )
        {
            $tipo_producto = isset($p['tipo_producto']) ? $p['tipo_producto'] : '';
            $nombre = isset($p['nombre']) ? $p['nombre'] : '';
            $sku = isset($p['sku']) ? $p['sku'] : '';
            $stock = isset($p['stock']) ? $p['stock'] : '';
            $precio_normal = isset($p['precio_normal']) ? $p['precio_normal'] : '';
            $precio_oferta = isset($p['precio_oferta']) ? $p['precio_oferta'] : '';
            $precio_especial_2 = isset($p['precio_especial_2']) ? $p['precio_especial_2'] : '';
            $precio_especial_3 = isset($p['precio_especial_3']) ? $p['precio_especial_3'] : '';

            $accion = isset($p['accion']) ? $p['accion'] : '';
            //filtro de nombres
            $accion = $accion === 'update' ? "actualizar $tipo_producto" : "crear $tipo_producto";

            $source_topic = isset($p['source_topic']) ? $p['source_topic'] : '';
            $source_file = isset($p['source_file']) ? $p['source_file'] : '';

            if( $source_topic == -1 )
            {
                if( $source_file === 'sync_stores' )
                {
                    $source_topic = 'sincronizacion general';
                }
                elseif( $source_file === 'self_sync' )
                {
                    $source_topic = 'sincronización manual';
                }
            }
            $source_topic = $source_topic == -1 ? '' : $source_topic;

            $result = isset($p['result']) ? $p['result'] : '';
            $result = $result > 0 ? 'ok' : 'no cambios';

            $msg = isset($p['msg']) ? $p['msg'] : '';
            $fecha = isset($p['fecha']) ? $p['fecha'] : '';

            //agrego row en tabla
            $arr_td = array( $nombre, $sku );

            if( $is_update_stock )
            {
                $arr_td[] = $stock;
            }

            if( $is_update_normal_price )
            {
                $arr_td[] = $precio_normal;
            }
            if( $is_update_sale_price )
            {
                $arr_td[] = $precio_oferta;
            }
            if( $is_update_price_may )
            {
                $arr_td[] = $precio_especial_2;
                $arr_td[] = $precio_especial_3;
            }
            $arr_td[] = $accion;
            $arr_td[] = $source_topic;
            $arr_td[] = $result;
            $arr_td[] = $msg;
            $arr_td[] = $fecha;

            //paso a html
            $html .= '<tr>';
            foreach( $arr_td as $td )
            {
                $html .= "<td>$td</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * devuelve los para produicto, variacion o sku, segun los params enviados
     * @global type $wpdb
     * @param type $product_id
     * @param type $variant_id
     * @param type $sku
     * @return type
     */
    public function get_product_log($product_id = 0, $variant_id = 0, $sku = null, $limit = -1)
    {
        $table = $this->get_table_name();
        $sql = "select * from $table";

        if( $product_id > 0 )
        {
            $sql .= " WHERE product_id= '$product_id' and tipo_producto= 'producto' ";
        }
        elseif( $variant_id > 0 )
        {
            $sql .= " WHERE product_id= '$variant_id' and tipo_producto= 'variacion' ";
        }
        elseif( !empty($sku) )
        {
            $sql .= " WHERE sku= '$sku' ";
        }
        else
        {
            //nada que hacer
            return null;
        }
        $sql .= "ORDER BY fecha desc ";

        if( $limit > 0 )
        {
            $sql .= "LIMIT 0, $limit ";
        }
        $sql .= ';';


        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql'");
        }

        global $wpdb;

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }

    /**
     * devuelve listado de todos los prod types desde la db
     * @global type $wpdb
     * @return type
     */
    public function get_all($where_cond = null, $order_by = null, $from = 0, $limit = -1)
    {
        $table = $this->get_table_name();
        $sql = "select * from $table";
        if( !empty($where_cond) )
        {
            $sql .= " WHERE $where_cond";
        }
        if( !empty($order_by) )
        {
            $sql .= " ORDER BY $order_by";
        }

        if( $limit > 0 )
        {
            $sql .= " LIMIT $from, $limit";
        }
        $sql .= ';';

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql'");
        }

        global $wpdb;

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }

    /**
     * borrar rows que tienen mas de x días de antiguedad
     */
    public function delete_historial()
    {
        $days_older = Funciones::get_days_delete_historial_sync_prods();
        $days_older = ($days_older >= 0) ? $days_older : 0;

        $table = $this->get_table_name();
        //creo la consulta
        $sql = "DELETE FROM $table WHERE DATEDIFF( NOW(), fecha) > $days_older;";

        global $wpdb;
        $res = $wpdb->query($sql);

        return $res;
    }

}
