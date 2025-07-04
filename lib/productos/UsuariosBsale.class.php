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
class UsuariosBsale extends BsaleBase
{ 
 function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_USUARIOS_BSALE');

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
        $firstName = isset($arr_data['firstName']) ? $wpdb->_real_escape($arr_data['firstName']) : '';
        $lastName = isset($arr_data['lastName']) ? $wpdb->_real_escape($arr_data['lastName']) : '';
        $email = isset($arr_data['email']) ? $wpdb->_real_escape($arr_data['email']) : '';
        $state = isset($arr_data['state']) ? $arr_data['state'] : -1;
        $office = isset($arr_data['office']['id']) ? $arr_data['office']['id'] : -1;
        $fecha = date("Y-m-d H:i:s");

        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`id`, `firstName`, `lastName`, `email`, `state`, `office`, `fecha`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ('$id', '$firstName', '$lastName','$email', '$state', '$office', '$fecha')";

        if( $only_data )
        {
            return $sql_data;
        }

        return $sql_header . $sql_data . ';';
    }

    public function count()
    {
        //no existe en bsale, se obtiene count en el promer get de users
        return 10;
    }

    public function get_all_array()
    {
        $sucursales = $this->get_all(false);

        $arraux = array();

        if( !is_array($sucursales) )
        {
            return $arraux;
        }

        foreach( $sucursales as $s )
        {
            $suc_id = $s['id'];
            $suc_name = $s['name'];

            $arraux[$suc_id] = $suc_name;
        }

        return $arraux;
    }

    /*     * $state = 0, devuelve solo sucrsales activas
     * indices: id, name, etc
     * @return type
     */

    public function get_all($state = 0, $limit = -1)
    {
        $table = $this->get_table_name();
        $sql = "select * from $table";

        if( $state == 0 || $state == 1 )
        {
            $sql .= " WHERE state= '$state'";
        }

        $sql .= " ORDER BY firstName";

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
     * devuelve la cantidad de horas dede que se descargaron estos datos desde bsale
     */
    public function get_last_hours_data_loaded()
    {
        $user_arr = $this->get_all(-1, 1);
        if( !isset($user_arr[0]['id']) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . "no hay usuarios en la db");
            }
            //no se han descargado
            return -1;
        }
        $fecha_user = $user_arr[0]['fecha'];

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . "fecha desde db: '$fecha_user'");
        }
        $fecha_user_datetime = date("Y-m-d H:i:s", strtotime($fecha_user));
        $fecha_user_timestamp = strtotime($fecha_user_datetime);

        // Current date and time
        $datetime = date("Y-m-d H:i:s");
        // Convert datetime to Unix timestamp
        $timestamp = strtotime($datetime);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . "fecha desde db: '$fecha_user', misma fecha en  php: '$fecha_user_datetime. "
                    . "Fecha de hoy: '$datetime'");
        }

        // Subtract time from datetime
        // $time = $timestamp - $fecha_user_timestamp; //(2 * 60 * 60);
        $horas_diferencia = round(($timestamp - $fecha_user_timestamp) / 3600);
        // Date and time after subtraction
        //$datetime = date("Y-m-d H:i:s", $time);
        // $hours = date("H", $time);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " Horas de diferencia: $horas_diferencia");
        }

        return $horas_diferencia;
    }

    public function get_usuarios_array($only_enabled = false)
    {
        $sucursales = $this->get_all_usuarios(false);

        $arraux = array();

        if( !is_array($sucursales) )
        {
            return $arraux;
        }

        foreach( $sucursales as $s )
        {
            //state=0 user enabled
            if( $only_enabled && $s['state'] != 0 )
            {
                continue;
            }
            if( !isset($s['id']) )
            {
                continue;
            }

            $user_id = $s['id'];
            $arraux[$user_id] = $s;
        }

        return $arraux;
    }

    /**
     * descarga listado de usuarios de bsale y guarda en db
     * @param type $clear_all_before_insert
     * @return type `
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
                Funciones::print_r_html(__METHOD__ . " No hay");
            }
            return;
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " descargando $total tipos...");
        }

        //cabecera de instrcuccion sql
        $only_header = true;
        $sql_header = $this->get_sql_insert(null, $only_header, false);

        $total_prods_insertados = 0;

        for( $c = 1; $total > 0; $total -= $limit, $c++ )
        {
            //aqui guardo las instrucciones insert d sql, para insertar de a varias filas cada vez
            $sql_insert_arr = array();

            $url = BSALE_USERS_GET_URL . "?limit=$limit&offset=$offset";

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
                if( !isset($response_array[$i]) )
                {
                    continue;
                }
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
     * indices: id, name, etc
     * @return type
     */
    public function get_all_usuarios($filter = null)
    {
        $res = $this->get_all(-1);
        return $res;
    }

    /**
     * devuelve un array con los ids de todas las sucursales 
     */
    public function get_all_usuarios_ids()
    {
        $arr = $this->get_all_usuarios();
        $array_sucursales = array();

        foreach( $arr as $value )
        {
            $array_sucursales[$value['id']] = $value['id'];
        }
        return $array_sucursales;
    }

}
