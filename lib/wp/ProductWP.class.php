<?php

//namespace woocommerce_bsalev2\lib\wp;
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of ProductWP
 *
 * @author angelorum
 */
class ProductWP extends BsaleBase
{

    function __construct()
    {
        $p = Funciones::get_value('DB_PREFIX');
        $prefix = Funciones::get_prefix() . $p;
        $table_name = $prefix . Funciones::get_value('DB_TABLE_PRODUCTS_WC');

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
     * revisa los skus de los prods wc. Si no están en Bsale, agrega un aviso dentro del producto
     */
    public function check_prods_wc()
    {
        $prods_not_in_bsale_arr = $this->marcar_prods_sku_no_bsale();
        $prods_in_bsale_cantidad = $this->desmarcar_prods_sku_no_bsale();

        return array( 'in_bsale' => $prods_in_bsale_cantidad, 'not_in_bsale' => $prods_not_in_bsale_arr );
    }

    /**
     * marca los prods de wc cuyo sku no pertenece a ningún producto de bsale
     */
    public function marcar_prods_sku_no_bsale()
    {
        global $wpdb;
        $table = $this->get_table_name();

        $vars_bsale = new VariantesProductoBsale();

        $table_vars_bsale = $vars_bsale->get_table_name();

        $sql = "select wc_products.* FROM "
                . "$table wc_products where sku "
                . "NOT IN ( SELECT code from $table_vars_bsale);";

        $result = $wpdb->get_results($sql, ARRAY_A);

        $len = is_array($result) ? count($result) : -1;

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql', cantidad: $len");
        }

        $skus_arr = array();

        foreach( $result as $p )
        {
            $prod_wc_id = $p['product_id'];
            $parent_id = $p['parent_id'];
            $sku = trim($p['sku']);

            if( empty($sku) )
            {
                continue;
            }

            $es_variacion = $parent_id > 0;

            if( $es_variacion )
            {
                $str = "sku '$sku' de variacion #$prod_wc_id no pertenece a ningún producto de Bsale. No se sincronizará.<br/>";
            }
            else
            {
                $str = "sku '$sku' no pertenece a ningún producto de Bsale. No se sincronizará.<br/>";
            }


            if( $es_variacion )
            {
                update_post_meta($parent_id, 'bsale_info_variacion', $str);
            }
            else
            {
                update_post_meta($prod_wc_id, 'bsale_info', $str);
            }
            //producto con al menos un sku que no está en Bsale
            update_post_meta($prod_wc_id, 'bsale_missed', 1);

            $skus_arr[] = $sku;
        }

        return $skus_arr;
    }

    /**
     * esn caso de que hubieran, quita la marca de los prods cuyo sku ahora sí está en bsale
     */
    public function desmarcar_prods_sku_no_bsale()
    {
        global $wpdb;
        $table = $this->get_table_name();

        $vars_bsale = new VariantesProductoBsale();

        $table_vars_bsale = $vars_bsale->get_table_name();

        $sql = "select wc_products.* FROM "
                . "$table wc_products where sku "
                . "IN ( SELECT code from $table_vars_bsale);";

        $result = $wpdb->get_results($sql, ARRAY_A);

        $len = is_array($result) ? count($result) : -1;

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql= '$sql', cantidad: $len");
        }

        foreach( $result as $p )
        {
            $prod_wc_id = $p['product_id'];
            $parent_id = $p['parent_id'];
            $sku = $p['sku'];

            $es_variacion = $parent_id > 0;

            delete_post_meta($parent_id, 'bsale_info_variacion');
            delete_post_meta($prod_wc_id, 'bsale_info');
            //producto con al menos un sku que no está en Bsale
            delete_post_meta($prod_wc_id, 'bsale_missed');
        }

        return $len;
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
        $file_products = $this->get_file_name_products();
        //leo y guardo en db
        $total_prods_insertados = $this->read_file_save_in_db($file_products);

        $arr_info[] = "Leo listado de productos desde '$file_products', productos agregados: $total_prods_insertados.";

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("db_save_products desde file '$file_products', se insertaron $total_prods_insertados rows.");
        }

        Funciones::print_r_html("total prods wc insertados en '$table' = $total_prods_insertados");

        $res_delete = 0;
// $res_delete = $this->delete_prods_duplicated_from_db();
        $arr_info[] = "Total productos wc guadaros: $total_prods_insertados";

        return array( 'cantidad' => $total_prods_insertados, 'info' => $arr_info,
            'duplicados' => $res_delete );
    }

    public function get_file_name_products()
    {
        return $file_temp = dirname(__FILE__) . '/../logs/wc_products.json';
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
        $products_array = json_decode($file_str, true);

        if( $products_array == null )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($products_array, __METHOD__ . " archivo '$filename' no tiene datos para insertar. Abort.");
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

        $product_id = isset($arr_data['product']['product_id']) ? $arr_data['product']['product_id'] : '';
        $nombre = isset($arr_data['product']['nombre']) ? $wpdb->_real_escape($arr_data['product']['nombre']) : '';
        $sku = isset($arr_data['product']['sku']) ? $wpdb->_real_escape($arr_data['product']['sku']) : '';
        $tipo = isset($arr_data['product']['tipo']) ? $arr_data['product']['tipo'] : -1;
        $estado = isset($arr_data['product']['estado']) ? $arr_data['product']['estado'] : -1;
        $parent_id = $estado = isset($arr_data['product']['parent_id']) ? $arr_data['product']['parent_id'] : -1;
        //header
        $sql_header = "INSERT INTO `$table`"
                . "(`product_id`, `parent_id`, `nombre`, `tipo`, `estado`, `sku`) "
                . "VALUES ";

        if( $only_header )
        {
            return $sql_header;
        }
        //cuerpo del sql
        $sql_data = " ('$product_id', '$parent_id', '$nombre', '$tipo', '$estado', '$sku')";

        if( $only_data )
        {
            return $sql_data;
        }

        return $sql_header . $sql_data . ';';
    }

    public function get_all_products_ids2($post_type = "'product'", $limit = 500)
    {
        global $wpdb;
        // $limit determines how many rows you want to handle at any given time
        // increase / decrease this limit to see how much your server can handle at a time 
        $limit = ($limit < 0) ? 1 : (int) $limit;
        $post_type = empty($post_type) ? "'product'" : $post_type;

        $start = 0;

        $file_temp = dirname(__FILE__) . '/../logs/wp_all_import.txt';
        //borro file if existes
        if( file_exists($file_temp) )
        {
            unlink($file_temp);
        }

        // open file handle
        $myfile = fopen($file_temp, 'a');

        $qry = "SELECT ID FROM `$wpdb->posts` where post_type IN($post_type) AND post_status IN('publish', 'pending') limit %d, %d";
        while( $result = $wpdb->get_results($wpdb->prepare($qry, array( $start, $limit ))) )
        {
            $write_data = '';
            foreach( $result as $row )
            {
                $write_data .= $row->ID . "\n";
            }

            fwrite($myfile, $write_data);
            $start = $start + $limit;
        }

        // close file handle
        fclose($myfile);

        //abro archivo y paso a string
        $archivolocal = file_get_contents($file_temp);
        //remuevo el ultimo \n
        $archivolocal = substr($archivolocal, 0, -1);
        $arraux = explode("\n", $archivolocal);

        return $arraux;
    }

    /**
     * devuelve array con listado de product ids
     */
    public function get_all_products_ids($post_type = "'product'", $limit = 500)
    {
        return $this->get_all_products_ids2($post_type, $limit);
    }

    public function get_stock_sucursal_from_meta($post_id = 0, $sku = null)
    {
        global $wpdb;

        $meta_key = "stock_{$sku}";

        $sql = "SELECT meta.meta_value FROM "
                . "{$wpdb->postmeta} meta, "
                . "{$wpdb->posts} post "
                . " WHERE meta.meta_key='$meta_key' "
                . "AND "
                . "meta.post_id = post.ID "
                . "AND post.post_status <> 'trash' "
                . " LIMIT 1";

//Funciones::print_r_html("get_stock_sucursal_from_meta(sku=$sku), sql='$sql'");

        $stock_html = $wpdb->get_var($sql);

        return $stock_html;
    }

    /**
     * 
     * @global type $wpdb
     * @param type $sku_or_ean
     * @param type $not_join
     * @return \WC_Product
     */
    public function getProductoBySku($sku_or_ean, $return_array = false)
    {

        global $BSALE_GLOBAL;
        $user_id = isset($BSALE_GLOBAL['SELLER_ID']) ? (int) $BSALE_GLOBAL['SELLER_ID'] : 0;

        global $wpdb;

        $sql = "SELECT meta.post_id as post_id, post.post_type as post_type FROM "
                . "{$wpdb->postmeta} meta, "
                . "{$wpdb->posts} post "
                . " WHERE meta.meta_key='_sku' AND meta.meta_value='%s' "
                . "AND "
                . "meta.post_id = post.ID ";

//multiuser: solo posts (productos) de este user
        if( $user_id > 0 )
        {
            $sql .= " AND post.post_author = '$user_id' ";
        }

        $sql .= "AND post.post_status <> 'trash' ";

        if( !$return_array )
        {
            $sql .= " LIMIT 1";
        }


        $sql = $wpdb->prepare($sql, $sku_or_ean);

        $results = $wpdb->get_results($sql);

        $productos_arr = array();

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($results, "getProductoBySku($sku_or_ean), sql='$sql', respuesta");
        }

        foreach( $results as $r )
        {
            $product_id = $r->post_id;
            $post_type = $r->post_type;

            if( $product_id <= 0 )
            {
                continue;
            }
            if( $post_type === 'product' )
            {
                //try
                // {
                $prod_object = wc_get_product($product_id);

                if( $prod_object )
                {
                    $productos_arr[] = $prod_object;
                }
                // }
                // catch( Exception $exc )
                // {
                //    Funciones::print_r_html($exc, "getProductoBySku, post $product_id, del tipo= '$post_type', no se puedo crear WC_Product()");
                //    continue;
                // }
            }
            //product_variation
            elseif( $post_type === 'product_variation' )
            {
                try
                {
                    $productos_arr[] = new WC_Product_Variation($product_id);
                }
                catch( Exception $exc )
                {
                    Funciones::print_r_html($exc, "getProductoBySku, post $product_id, del tipo= '$post_type', no se puedo crear WC_Product_Variation()");
                    continue;
                }
            }
        }

        //si no retorno array, solo retorno el 1er producto
        if( !$return_array )
        {
            return isset($productos_arr[0]) ? $productos_arr[0] : null;
        }

        //retorno array de objetos
        return $productos_arr;
    }

    /**
     * devuelve listado de prods y variants de wc 
     */
    public function save_in_file_all_products_wc($limit = 500)
    {
        global $wpdb;
        // $limit determines how many rows you want to handle at any given time
        // increase / decrease this limit to see how much your server can handle at a time 
        $limit = ($limit < 0) ? 1 : (int) $limit;
        $post_type = "'product', 'product_variation'";

        $start = 0;

        $file_temp = dirname(__FILE__) . '/../logs/wc_products.json';
        //borro file if existes
        if( file_exists($file_temp) )
        {
            unlink($file_temp);
        }

        // open file handle
        $myfile = fopen($file_temp, 'a');

        $qry = "SELECT prod.ID as product_id, prod.post_title as nombre, prod.post_type as tipo, "
                . "prod.post_status as estado, prod.post_parent as parent_id, "
                . "meta.meta_value as sku "
                . "FROM `{$wpdb->posts}` prod, `{$wpdb->postmeta}` meta "
                . "WHERE "
                . "prod.post_type IN($post_type) AND prod.post_status IN('publish', 'pending') AND "
                . "prod.ID = meta.post_id AND meta.meta_key='_sku' "
                . "limit %d, %d";

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " sql: '$qry'");
        }

        $write_data = '[';
        fwrite($myfile, $write_data);
        //para saber cuando colocar coma al final
        $i = 0;

        while( $result = $wpdb->get_results($wpdb->prepare($qry, array( $start, $limit )), ARRAY_A) )
        {
            $write_data = '';

            foreach( $result as $row )
            {
                $arraux = array( 'product' => $row );
                $json = json_encode($arraux, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                //las segundas veces en adelante, coloca una coma
                if( $i > 0 )
                {
                    $write_data .= ',';
                }
                $write_data .= $json;
                $i++;
            }

            fwrite($myfile, $write_data);
            $start = $start + $limit;
        }
        $write_data = ']';
        fwrite($myfile, $write_data);

        // close file handle
        fclose($myfile);

        return $file_temp;

        //abro archivo y paso a string
//        $archivolocal = file_get_contents($file_temp);
//        //remuevo el ultimo \n
//        $archivolocal = substr($archivolocal, 0, -1);
//        $arraux = explode("\n", $archivolocal);
//
//        return $arraux;
    }

    public function get_attributes_wc($taxonomy, $create_if_no_exists = false)
    {
//Funciones::print_r_html( 'get_attributes_wc' );
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        $arr = array();

        foreach( $terms as $each_term )
        {
// echo $each_term->name . '</br>';
            $arr[] = $each_term->name;
        }
        return $arr;
    }

    public function insert_product_attributes($post_id, $available_attributes, $variations)
    {
        foreach( $available_attributes as $attribute ) // Go through each attribute
        {
            $values = array(); // Set up an array to store the current attributes values.
//hay values para este atributo?
// Funciones::print_r_html("insert_product_attributes, testing '$attribute'...");
            if( !isset($variations[$attribute]) )
            {
                Funciones::print_r_html('insert_product_attributes: atributo ' . $attribute . ' sin values');
                continue;
            }
            $valuesarr = $variations[$attribute];

// Essentially we want to end up with something like this for each attribute:
// $values would contain: array('small', 'medium', 'medium', 'large');

            $values = /* array_unique */( $valuesarr ); // Filter out duplicate values
// Store the values to the attribute on the new post, for example without variables:
// wp_set_object_terms(23, array('small', 'medium', 'large'), 'pa_size');
            wp_set_object_terms($post_id, $values, $attribute);
// Funciones::print_r_html("wp_set_object_terms( $post_id, values, $attribute );");
// Funciones::print_r_html( $values, "insert_product_attributes: wp_set_object_terms para $attribute, post= $post_id" );
        }

        $product_attributes_data = array(); // Setup array to hold our product attributes data

        foreach( $available_attributes as $attribute ) // Loop round each attribute
        {
            $product_attributes_data[$attribute] = array( // Set this attributes array to a key to using the prefix 'pa'

                'name' => $attribute,
                'value' => '',
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '1'
            );
        }

        update_post_meta($post_id, '_product_attributes', $product_attributes_data); // Attach the above array to the new posts meta data key '_product_attributes'
    }
}
