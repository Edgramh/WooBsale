<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TipoProductosTable
 *
 * @author angelorum
 */
class TipoProductosTable extends OCDB
{

    public $_table = 'bsale_tipos_producto';

    function __construct()
    {
        parent::__construct();
    }

    function get_table_name()
    {
         if( empty($this->table_name) )
        {
            $p = Funciones::get_value('DB_PREFIX');
            
            $prefix =Funciones::get_prefix() . $p;

            $this->table_name = $prefix . Funciones::get_value('DB_TABLE_BSALE_PRODUCT_TYPES');
        }
        return $this->table_name;
    }

    public function clear_all()
    {
        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }

        try
        {
            $sql = "TRUNCATE TABLE " . self::$_table . ";";
            mysqli_query($conn, $sql);
        }
        catch( Exception $exc )
        {
            //  mysqli_rollback( $conn );
            echo $exc->getTraceAsString();
        }
    }

    public function add($array_datos)
    {
        $conn = $this->conectar();
        if( $conn == false )
        {
            Funciones::print_r_html("Stock Table: error conectando a db");
            return -1;
        }

        $id = $array_datos['id'];
        $name = $array_datos['name'];

        $table = self::$_table;

        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            $name = esc_sql($name);
            global $wpdb;
        }
        else
        {
            $name = mysqli_real_escape_string($conn, $name);
        }
        /* Update order header for invoice charged on */
        $SQL = "INSERT INTO " . self::$_table . "(id, name) "
                . " VALUES ('$id', '$name')";

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($SQL, "TipoProductosTable add guardarTransaccion:");
        }
        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            global $wpdb;
            $Result = $wpdb->query($SQL);
        }
        else
        {
            $Result = mysqli_query($conn, $SQL);
        }


        return $Result;
    }

    public function get_all()
    {
        $sql = "select * from " . self::$_table . " order by id;";

        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }

        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            global $wpdb;

            //paso resulktados a un array
            $arr = array();
            $result = $wpdb->get_results($sql, ARRAY_A);

            foreach( $result as $row )
            {
                $arr[] = $row;
            }
            return $arr;
        }

        if( !$result = mysqli_query($conn, $sql) )
        {
            die("get_all(): $sql: There was an error running the query ['" . mysqli_connect_error() . "']");
        }
        //paso resulktados a un array
        $arr = array();
        while( $row = mysqli_fetch_assoc($result) )
        {
            $arr[] = $row;
        }
        mysqli_free_result($result);
        return $arr;
    }

    public function get_all_array()
    {
        $res = $this->get_all();

        //Funciones::print_r_html($res, "get_all_array() ");

        $arraux = array();
        if( !$res )
        {
            return $arraux;
        }

        foreach( $res as $a )
        {
            $arraux[$a['id']] = $a;
        }

        return $arraux;
    }

    public function get_tipo($tipo_id)
    {
        $sql = "select * from " . self::$_table . " "
                . "where id = '$tipo_id';";

        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }

        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            global $wpdb;

            $result = $wpdb->get_results($sql, ARRAY_A);

            foreach( $result as $row )
            {
                //solo la primera fila
                return $row;
            }
            return null;
        }


        if( !$result = mysqli_query($conn, $sql) )
        {
            die("get_all(): $sql: There was an error running the query ['" . mysqli_connect_error() . "']");
        }

        $row = mysqli_fetch_assoc($result);

        mysqli_free_result($result);
        return $row;
    }

}
