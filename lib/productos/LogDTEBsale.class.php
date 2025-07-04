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
class LogDTEBsale extends BsaleBase
{

    function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_LOG_DTES_BSALE_WC');

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

        $order_id = isset($arr_data['order_id']) ? $arr_data['order_id'] : '-1';
        $tipo_doc = isset($arr_data['tipo_doc']) ? $wpdb->_real_escape($arr_data['tipo_doc']) : '-1';
        $doc_id = isset($arr_data['doc_id']) ? $arr_data['doc_id'] : '-1';
        $doc_folio = isset($arr_data['doc_folio']) ? $wpdb->_real_escape($arr_data['doc_folio']) : '-1';
        $json_send = isset($arr_data['json_send']) ? $wpdb->_real_escape($arr_data['json_send']) : '-1';

        $json_recv = isset($arr_data['json_recv']) ? $wpdb->_real_escape($arr_data['json_recv']) : '-1';

        $result = isset($arr_data['result']) ? $wpdb->_real_escape($arr_data['result']) : '-1';

        $link_dte = isset($arr_data['link_dte']) ? $wpdb->_real_escape($arr_data['link_dte']) : '-1';
        $msg = isset($arr_data['msg']) ? $wpdb->_real_escape($arr_data['msg']) : '-1';

        $fecha = date("Y-m-d H:i:s");
        $source = isset($arr_data['source']) ? $wpdb->_real_escape($arr_data['source']) : '-1';

        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`order_id`, `tipo_doc`, `doc_id`, `doc_folio`, `json_send`, `json_recv`,"
                . " `result`, `link_dte`, `msg`, `fecha`, `source`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ( '$order_id', '$tipo_doc', '$doc_id', '$doc_folio', '$json_send', '$json_recv', '$result', "
                . "'$link_dte', '$msg', '$fecha', '$source' )";

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

        // die($sql);//die
        $res = $this->execute_sql($sql);

        $total_prods_insertados = (int) $res;

        return $total_prods_insertados;
    }

    /**
     * devuelve dtes emitidos para esta order id
     */
    public function get_last_log_order($order_id)
    {
        $arr_order_log = $this->get_order_log($order_id);

        return $arr_order_log;
    }

    /**
     * devuelve html con hostorial de sync del prod y sus variaciones
     * @param type $order_id
     */
    public function get_last_log_order_html($order_id)
    {
        $data_arr = $this->get_order_log($order_id);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($data_arr, __METHOD__ . " hostorial order $order_id");
        }


        if( is_array($data_arr) && count($data_arr) > 0 )
        {
            $html_producto = "<p><strong>Historial de pedido #$order_id</strong></p>";
            $html_producto .= $this->get_html_order_log($data_arr);
        }
        $html = '';

        if( isset($html_producto) )
        {
            $html .= $html_producto;
        }
        else
        {
            $html .= "<p>No se han emitido documentos para el pedido #$order_id</p>";
        }

        return $html;
    }

    /**
     * devuelve html de una o varias filas de historial de producto
     * @param type $order_arr
     */
    public function get_html_order_log($order_arr)
    {
        if( !is_array($order_arr) || count($order_arr) <= 0 )
        {
            return '';
        }

        $th_arr = array( 'Pedido', 'Documento', 'Folio', 'Resultado', 'Link docto.', 'Mensaje',
            'Fecha', /* 'Id docto.', */ 'Json enviado', 'Json respuesta' );

        $html = '<table class="hist_order"><thead><tr>';

        foreach( $th_arr as $th )
        {
            $html .= "<th>$th</th>";
        }
        $html .= '</tr></thead><tbody>';

        foreach( $order_arr as $p )
        {
            $order_id = isset($p['order_id']) ? $p['order_id'] : '';
            $tipo_doc = isset($p['tipo_doc']) ? $p['tipo_doc'] : '';
            $doc_id = isset($p['doc_id']) ? $p['doc_id'] : '';
            $doc_folio = isset($p['doc_folio']) ? $p['doc_folio'] : '';
            $json_send = isset($p['json_send']) ? $p['json_send'] : '';
            $json_recv = isset($p['json_recv']) ? $p['json_recv'] : '';
            $result = isset($p['result']) ? $p['result'] : '';
            $link_dte = isset($p['link_dte']) ? $p['link_dte'] : '';
            $msg = isset($p['msg']) ? $p['msg'] : '';
            $fecha = isset($p['fecha']) ? $p['fecha'] : '';
            $source = isset($p['source']) ? $p['source'] : '';

            //agrego row en tabla
            $arr_td = array( $order_id, $tipo_doc, $doc_folio, $result, $link_dte, $msg,
                $fecha, /* $doc_id, */ $json_send, $json_recv );

            //paso a html
            $html .= '<tr>';
            foreach( $arr_td as $td )
            {
                $len = strlen($td);

                if( $len > 100 )
                {
                    $html .= "<td><textarea readonly class='p_text'>$td</textarea></td>";
                }
                else
                {
                    $html .= "<td>$td</td>";
                }
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
    public function get_order_log($order_id)
    {
        $table = $this->get_table_name();
        $sql = "select * from $table";

        $sql .= " WHERE order_id= '$order_id' ORDER BY fecha desc;";

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
        //guardo por al menos 5 días más que el historial de sync
        $days_older = ($days_older >= 0) ? $days_older + 5 : 5;

        $table = $this->get_table_name();
        //creo la consulta
        $sql = "DELETE FROM $table WHERE DATEDIFF( NOW(), fecha) > $days_older;";

        global $wpdb;
        $res = $wpdb->query($sql);

        return $res;
    }
}
