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
abstract class BsaleBase
{

    protected $table_name = null;

    //cada clase hija debe declarar este métido
    //abstract public function get_table_name();

    public function get_table_name()
    {
        return $this->table_name;
    }

    public function set_table_name($table_name): void
    {
        $this->table_name = $table_name;
    }

    public function clear_table()
    {
        global $wpdb;
        $table = $this->get_table_name();

        if( empty($table) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " $table is empty. Return.");
            }
            return false;
        }
        $sql = "TRUNCATE TABLE $table;";

        $res = $wpdb->query($sql);

        return $res;
    }

    public function get($url, $return_raw = false)
    {
        $bsale = new DocumentoAbstracto();

        return $bsale->get($url, $return_raw);
    }

    /**
     * lee un array de instrucciones sql, las une en un solo string y las ejecuta con 
     * una sola conexion a la db
     * @param type $arr_sql
     * @return boolean
     */
    public function sql_execute_from_array($sql_header_str, $arr_sql, $implode_char = "\n")
    {
        $len_arr = count($arr_sql);

        if( $len_arr <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($arr_sql, __METHOD__ . " array vacio, se ignora");
            }
            return false;
        }
        //paso a string
        $str_sql = implode($implode_char, $arr_sql);

        if( !empty($sql_header_str) )
        {
            $str_sql = $sql_header_str . $str_sql . ';';
        }

//inserto
        $res = $this->execute_sql($str_sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " resultado de $len_arr items $res, sql = '$str_sql'");
        }

        if( isset($_REQUEST['param']) && $res <= 0 )
        {
            Funciones::print_r_html(__METHOD__ . " resultado de $len_arr items $res, sql = '$str_sql'");
        }
        return $res;
    }

    /**
     * ejecuta sql. Pueden ser varios insert, por ejemplo
     * @global type $wpdb
     * @param type $sql
     * @return type
     */
    public function execute_sql($sql)
    {
        global $wpdb;

        $res = $wpdb->query($sql);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($sql, __METHOD__ . ", respuesta='$res'");
        }
        return $res;
    }

    /**
     * devuelve el archivo creado más recientemente 
     * @param type $files_arr
     * @return type
     */
    public function get_last_file($files_arr)
    {
        $latest_ctime = 0;
        $latest_filename = '';

        foreach( $files_arr as $file )
        {
            if( is_file($file) && filectime($file) > $latest_ctime )
            {
                $latest_ctime = filectime($file);
                $latest_filename = $file;
            }
        }
        return $latest_filename;
    }

    /**
     * recibe un archivo en formato json, proveniente de la api de Bsale
     * y devuelve un array con 
     * count, limit, offset
     * @param type $file_name
     */
    public function get_last_file_offset($filename)
    {
        $file_str = file_get_contents($filename);

        //convierto a json
        $data_array = json_decode($file_str, true);

        $arr = array();
        $arr['count'] = isset($data_array['count']) ? $data_array['count'] : -1;
        $arr['limit'] = isset($data_array['limit']) ? $data_array['limit'] : -1;
        $arr['offset'] = isset($data_array['offset']) ? $data_array['offset'] : -1;

        return $arr;
    }
}
