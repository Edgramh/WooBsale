<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OpcionesTable
 *
 * @author angelorum
 */
class OpcionesTable extends OCDB
{

    public static $PROCESO_PENDIENTE = 0, $PROCESO_COMPLETADO = 1,
            $PROCESO_PREFIX = 'proceso_';
    public static
            $PROCESO_PRECIOS_BSALE = 'proceso_precios_bsale',
            $PROCESO_PRECIOS_ESPECIALES_BSALE = 'proceso_precios_esp_bsale',
            $PROCESO_STOCK_BSALE = 'proceso_stock_bsale',
            $PROCESO_PRODUCTOS_BSALE = 'proceso_productos_bsale',
            $PROCESO_VARIANTES_BSALE = 'proceso_variantes_bsale',
            $PROCESO_PRODUCTOS_MAGENTO = 'proceso_productos_magento';

    function __construct()
    {
        parent::__construct();
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

            //  $sql = "SET FOREIGN_KEY_CHECKS=0;";
            // mysqli_query( $conn, $sql );

            $sql = "TRUNCATE TABLE {$this->OPTIONS_TABLE};";
            //   Funciones::print_r_html( $sql, "sql para clear" );

            mysqli_query($conn, $sql);

            //recargo options de bsale
            $this->load_bsale_options();

            //  $sql = "SET FOREIGN_KEY_CHECKS=1;";
            //  mysqli_query( $conn, $sql );
        }
        catch( Exception $exc )
        {
            //  mysqli_rollback( $conn );
            echo $exc->getTraceAsString();
        }
    }

    public function get_estado_procesos()
    {
        $procesos = $this->get_procesos();
        $estados_procesos = array();

        $procesos_nombres = array( self::$PROCESO_PRECIOS_BSALE,
            self::$PROCESO_PRECIOS_ESPECIALES_BSALE,
            self::$PROCESO_STOCK_BSALE,
            self::$PROCESO_PRODUCTOS_BSALE,
            self::$PROCESO_VARIANTES_BSALE,
            self::$PROCESO_PRODUCTOS_MAGENTO );

        //recorro los procesos y veo si estan completados o pendientes
        //true: completa; false. pendiente
        foreach( $procesos_nombres as $pp )
        {
            $estados_procesos[$pp] = isset($procesos[$pp]) ?
                    $pp == self::$PROCESO_COMPLETADO : false;
        }
        return $estados_procesos;
    }

    /* /
     * borrar datos de global
     */

    public function clear_bsale_options()
    {
        global $BSALE_OPTIONS_GLOBAL;

        $BSALE_OPTIONS_GLOBAL = array();
    }

    /**
     * carga opcionmes de bsale y las guarda en variable global
     */
    public function load_bsale_options()
    {
        global $BSALE_OPTIONS_GLOBAL;
        //vacio datos
        $BSALE_OPTIONS_GLOBAL = $this->get_all_by_clave('settings');


        if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' && INTEGRACION_SISTEMA !== 'woocommerce' )
        {
            Funciones::print_r_html($BSALE_OPTIONS_GLOBAL, "load_bsale_options(), global contiene ahora:");
        }
    }

    /**
     * si viene param tipo, soo devuelve array clave->valor
     * @param type $tipo
     * @return type
     */
    public function get_all_by_clave($tipo = null)
    {
        $rows = $this->get_all();
        $arraux = array();

        $l = count($rows);

        if( $l <= 0 )
        {
            return array();
        }

        for( $i = 0; $i < $l; $i++ )
        {
            $c = $rows[$i];

            if( $tipo != null )
            {
                if( $c['tipo'] === $tipo )
                {
                    $sku = $c['clave'];
                    $arraux[$sku] = $c['valor'];
                }
            }
            else
            {
                $sku = $c['clave'];
                $arraux[$sku] = $c;
            }
        }
        unset($rows);

        return $arraux;
    }

    public function hayProcesosPendientes()
    {
        $arr = $this->get_all_by_clave();

        foreach( $arr as $k => $r )
        {
            //solo las claves que empiezan por "proceso_"
            if( strpos(self::PROCESO_PREFIX, $k) === false )
            {
                continue;
            }
            $val = (int) $r['valor'];
            //si viene al menos un dato con o, entonces es que no esta completo el proceso
            if( $val == self::$PROCESO_PENDIENTE || $val == null )
            {
                return $arr;
            }
        }

        return false;
    }

    /**
     *  devuelve solo las claves de los procesos
     * @return type
     */
    public function get_procesos()
    {
        $arr = $this->get_all_by_clave();
        $arraux = array();

        foreach( $arr as $k => $r )
        {
            //solo las claves que empiezan por "proceso_"
            if( strpos(self::PROCESO_PREFIX, $k) === false )
                continue;
            $arraux[$k] = $r;
        }

        return $arraux;
    }

    public function add($array_datos)
    {
        $conn = $this->conectar();
        if( $conn == false )
        {
            return -1;
        }

        $c = isset($array_datos['clave']) ? $array_datos['clave'] : null;
        $v = isset($array_datos['valor']) ? $array_datos['valor'] : '';
        $t = isset($array_datos['tipo']) ? $array_datos['tipo'] : 'settings';

        if( $c == null )
        {
            return;
        }

        //corto strings
        $c = substr($c, 0, 50);
        $v = substr($v, 0, 400);
        $t = substr($t, 0, 20);

        //scape
        $c = mysqli_real_escape_string($conn, $c);
        $v = mysqli_real_escape_string($conn, $v);
        $t = mysqli_real_escape_string($conn, $t);

        /* Update order header for invoice charged on */
        $SQL = "INSERT INTO {$this->OPTIONS_TABLE}(clave, valor, tipo) "
                . " VALUES ('$c', '$v', '$t') "
                . "ON DUPLICATE KEY "
                . "UPDATE "
                . "`valor` = '$v';";


        //Funciones::print_r_html($SQL, "guardarTransaccion:");
        $Result = mysqli_query($conn, $SQL);


        return $Result;
    }

    public function update($clave, $update_str)
    {

        $sql = "update {$this->OPTIONS_TABLE} set $update_str"
                . " WHERE clave = '$clave' ;";

        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }

        $result = mysqli_query($conn, $sql);

        //recargo options de bsale
        $this->load_bsale_options();

        return $result;
    }

    public function get_all($fields = '*')
    {
        $sql = "select $fields from {$this->OPTIONS_TABLE } order by clave;";
        //usa global $wpdb
        $is_wc = INTEGRACION_SISTEMA === 'woocommerce';

        if( $is_wc )
        {
            return array();
        }

        $conn = $this->conectar();
        if( $conn == false )
        {
            return array();
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

        if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' )
        {
            //Funciones::print_r_html($arr, "OptionsTable->get_all(): sql= '$sql'");
        }
        return $arr;
    }

    public function get_value($clave)
    {
        $sql = "select * from {$this->OPTIONS_TABLE } where clave = '$clave';";

        $conn = $this->conectar();
        if( $conn == false )
        {
            return null;
        }

        if( !$result = mysqli_query($conn, $sql) )
        {
            die("get_all(): $sql: There was an error running the query ['" . mysqli_connect_error() . "']");
        }

        $row = mysqli_fetch_assoc($result);

        mysqli_free_result($result);

        $value = isset($row['valor']) ? $row['valor'] : null;
        $tipo = isset($row['tipo']) ? $row['tipo'] : null;

        //cambio valor segun tipo

        if( $value != null )
        {
            switch( $tipo )
            {
                case 'bool':
                    //empty puede ser que no está indicado ningun valor
                    if( !empty($value) )
                    {
                        $value = ($value == 1 || $value === 'true') ? true : false;
                    }
                    break;
                default :
                    break;
            }
        }

        return $value;
    }

}
