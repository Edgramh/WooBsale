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
class TipoProductosBsale extends BsaleBase
{

  function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_BSALE_PRODUCT_TYPES');

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
        $state = isset($arr_data['state']) ? $arr_data['state'] : -1;

        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`id`, `name`, `state`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ('$id', '$name', '$state')";

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
    public function get_all_save_in_db($clear_all_before_insert = true)
    {
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

        $limit = 50;
        $offset = 0;
        $total = $this->count();

        if( $total <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("get_all_tipo_productos: No hay");
            }
            return;
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("get_all_tipo_productos: descargando $total tipos...");
        }

        //cabecera de instrcuccion sql
        $only_header = true;
        $sql_header = $this->get_sql_insert(null, $only_header, false);


        $total_prods_insertados = 0;



        for( $c = 1; $total > 0; $total -= $limit, $c++ )
        {
            //aqui guardo las instrucciones insert d sql, para insertar de a varias filas cada vez
            $sql_insert_arr = array();

            $url = BSALE_PRODUCTO_TYPE_GET_URL . "?limit=$limit&offset=$offset";

            $response_array = $this->get($url);

            //priomera pasada, obtengo el total de productos desde la respuesta del json
            if( $c == 1 )
            {
                $total = isset($response_array['count']) ? (int) $response_array['count'] : $total;

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html(__METHOD__ . " total REAL de items: $total");
                }
            }

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($response_array, " url='$url', respuesta");
            }


            if( isset($response_array['items']) )
            {
                $response_array = $response_array['items'];
            }
            $len = count($response_array);

            for( $i = 0; $i < $len; $i++ )
            {
                $v = $response_array[$i];
                //obtengo insert sql only data y lo guardo en array
                $sql_insert_row = $this->get_sql_insert($v, false, true);

                $sql_insert_arr[] = $sql_insert_row;
            }

            //si llevo 40 filas, inserto, limpio el array y vuelvo a contar
            if( count($sql_insert_arr) > 0 )
            {
                $res1 = $this->sql_execute_from_array($sql_header, $sql_insert_arr, ',');
                $total_prods_insertados += (int) $res1;

                //limpio array
                unset($sql_insert_arr);
                $sql_insert_arr = array();
                //reinicio contador
            }

            $offset += $limit;
            unset($response_array);
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
     * devuelve listado de todos los prod types desde la db
     * @global type $wpdb
     * @return type
     */
    public function get_all($limit = -1)
    {
        $table = $this->get_table_name();
        $sql = "select * from $table order by name ";
        
        if( $limit > 0 )
        {
            $sql .= " LIMIT $limit";
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
     * devuelve aray con todos los ids de productos
     */
    public function get_all_attrib_products_ids()
    {
        $prod_types_arr = $this->get_all();
        $arraux = array();

        foreach( $prod_types_arr as $t )
        {
            if( !isset($t['id']) )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($t, __METHOD__ . " id no encontrado, se omite");
                }
                continue;
            }
            $type_id = $t['id'];
            $arraux[$type_id] = $type_id;
        }

        return $arraux;
    }

    /**
     * SE CONECTA A BSALE Y devuelve tipso de productos
     * @return array
     */
    public function get_tipos_producto()
    {

        return $this->get_all();
    }
    
    public function get_producto_type_from_table($type_producto_id)
    {
        $table = $this->get_table_name();
        $sql = "select * from $table where id= '$type_producto_id' LIMIT 1;";

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql'");
        }

        global $wpdb;

        $result = $wpdb->get_row($sql, ARRAY_A);

        return $result;
    }

}
