<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of LogDocumentos
 *
 * @author angelorum
 */
class LogDocumentos extends OCDB
{

    function __construct()
    {
        parent::__construct();
    }

    public $table_name = null;

    public function get_table_name()
    {
        if( empty($this->table_name) )
        {
            $p = Funciones::get_value('DB_PREFIX');
            $prefix = Funciones::get_prefix() . $p;

            $this->table_name = $prefix . Funciones::get_value('DB_TABLE_LOG_DTES_BSALE');
        }
        return $this->table_name;
    }

    public function getIdsDocumentoFromLog($tipo_doc, $local_id, $source = null)
    {
//para wc, saco los datos de los campos meta de la orden
        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            switch( $tipo_doc )
            {
                case 'nv':
                    //bsale_docto_id_nv
                    $bsale_docto_id = (int) get_post_meta($local_id, 'bsale_docto_id_nv', true);
                    break;
                case 'be':
                    //bsale_docto_id_nv
                    $bsale_docto_id = (int) get_post_meta($local_id, 'bsale_docto_id_boleta', true);
                    break;

                case 'f':
                    //bsale_docto_id_nv
                    $bsale_docto_id = (int) get_post_meta($local_id, 'bsale_docto_id_factura', true);
                    break;
                case 'nc':
                    //bsale_docto_id_nv
                    $bsale_docto_id = (int) get_post_meta($local_id, 'bsale_docto_id_nc', true);
                    break;
                default:
                    $bsale_docto_id = -1;
                    break;
            }
            if( $bsale_docto_id > 0 )
            {
                $log_row = array( 'remoto_id' => $bsale_docto_id );
            }
            else
            {
                $log_row = array();
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html($log_row, "getIdsDocumentoFromLog(tipo='$tipo_doc', local id='$local_id',"
                        . " respuesta para WC");
            }
            return $log_row;
        }
        $table = $this->get_table_name();
        $SQL = "SELECT * FROM `$table` WHERE local_id='$local_id' and tipo='$tipo_doc' ";

        if( !empty($source) )
        {
            $SQL .= "and source = '$source' ";
        }
        $SQL .= " order by fecha desc limit 1";
        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }
        $Result = mysqli_query($conn, $SQL);

        $myrow = mysqli_fetch_assoc($Result);

        mysqli_free_result($Result);

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($myrow, "getIdsDocumentoFromLog(tipo='$tipo_doc', local id='$local_id',"
                    . "sql = '$SQL', respuesta");
        }

        return $myrow;
    }

    public function getIdsDocumentoFromLog_wc()
    {
        
    }

    /**
     * guarda post en log table
     * @param type $tipo_doc
     * @param type $local_id
     * @param type $id_remoto
     * @param type $post_json
     * @param type $post_array
     * @param type $response_json
     * @param type $response_array
     * @return type
     */
    public function guardarLogTable($tipo_doc, $local_id, $id_remoto, $post_json, $post_array, $response_json, $response_array)
    {
        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            return true;
        }
        //detecto el source de ventas desde Jumpseller
        $tienda = isset($post_array['tienda']) ? strtolower($post_array['tienda']) : '';
        $tienda = substr($tienda, 0, 10);

        //intento detectar la tienda desde donde se originó la solicitud, para guardar 
        //y después poder emitir nc

        $url_dte = isset($response_array['urlPublicView']) ? $response_array['urlPublicView'] : '';
        $error_dte = isset($response_array['error']) ? $response_array['error'] : '';
        //viene url de dte?
        $str_respuesta = !empty($url_dte) ? $url_dte : '';
        //si no viene, hay error?
        $str_respuesta = !empty($str_respuesta) ? $str_respuesta : $error_dte;
        //si no hay url ni erro, guardo respuesta completa
        $str_respuesta = !empty($str_respuesta) ? $str_respuesta : print_r($response_json, true);

        $post_array = print_r($post_array, true);
        $response_array = print_r($response_array, true);

        //no es necesario tener tantos datos, pues estos se guardan en un archivo
        $post_json = '';
        $response_json = '';


        $SQL = "INSERT INTO {$this->LOGS_TABLE}(tipo, local_id, remoto_id, json_post, "
                . "array_post, json_respuesta, array_respuesta, source) "
                . " VALUES ('$tipo_doc', '$local_id', '$id_remoto', '$post_json', '', '$str_respuesta',"
                . " '', '$tienda' )";

        $conn = $this->conectar();
        if( $conn == false )
            return;
        $Result = mysqli_query($conn, $SQL);

        return $Result;
    }

    public function local_id_exists($tipo_doc, $local_id, $return_json_result = false)
    {
        $conn = $this->conectar();
        if( $conn == false )
            return;
        $sql = "select * from  {$this->LOGS_TABLE}  "
                . "where tipo='$tipo_doc' AND local_id = '$local_id' limit 1;";

        $Result = mysqli_query($conn, $sql);


        $myrow = mysqli_fetch_array($Result);

        if( isset($myrow['local_id']) && $myrow['local_id'] > 0 )
        {
            if( $return_json_result )
            {
                $res = trim($myrow['json_respuesta']);
            }
            else
            {
                $res = true;
            }
        }
        else
        {
            $res = false;
        }
        mysqli_free_result($Result);
        return $res;
    }

}
