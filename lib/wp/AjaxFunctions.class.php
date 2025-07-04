<?php

//namespace woocommerce_bsalev2\lib\wp;
require_once dirname(__FILE__) . '/../Autoload.php';

//use woocommerce_bsalev2\lib\wp\WpUtils;
/**
 * Funciones que gatilla llamadas ajax en el admin
 *
 * @author angelorum
 */
class AjaxFunctions
{

    public function setup_actions()
    {
        //shortcode, not used
        //add_shortcode('woo_bsale',  array( $this, 'woo_bsale_shortcode' ));

        if( Funciones::is_enabled_integ_facturacion() && Funciones::is_add_campos_checkout_factura() )
        {
            //para reordenar fields
            add_filter('woocommerce_checkout_fields', array( $this, 'woocommerce_bsale_checkout_fields_function' ), 9999999);
        }

        if( Funciones::is_enabled_integ_inventario() )
        {
            //para cronjjobs con wp control        
            add_action('bsale_action_cronjob_webhook', array( $this, 'bsale_action_cronjob_webhook_function' ));
        }
        //se ejcuta ya sea que inventario esté enabled o no
        add_action('bsale_action_cronjob_resend', array( $this, 'bsale_action_cronjob_resend_function' ));

        add_action('admin_notices', array( $this, 'bsale_codificacl_general_admin_notice' ));

        if( Funciones::is_enabled_integ_inventario() )
        {
            //ajax para sync prodcuto en edit prod
            add_action('wp_ajax_bsale_ajax_sync_prod_action', array( $this, 'bsale_ajax_sync_prod_action' ));
            //para sinbcronizar todos los productos
            add_action('wp_ajax_bsale_ajax_sync_prod_action2', array( $this, 'bsale_ajax_sync_prod_action2' ));
            //para ver historial de sync prodcuto en pag de edit producto
            add_action('wp_ajax_get_info_last_sync_bsale_action', array( $this, 'get_info_last_sync_bsale_action' ));
        }

        if( Funciones::is_enabled_integ_facturacion() )
        {
            //para ver historial de dtes apara pedido
            add_action('wp_ajax_get_info_order_docs_action', array( $this, 'get_info_order_docs_action' ));
        }

        //para recargar datos desde bsale en pagina de config
        add_action('wp_ajax_bsale_reload_data_action', array( $this, 'bsale_reload_data_action' ));
        //agrega id de transsaccion en los emails
        //add_filter('woocommerce_email_order_meta_fields', array( $this, 'custom_woocommerce_email_order_meta_fields'), 10, 3);

        add_action('admin_bar_menu', array( $this, 'codificabsale_toolbar_link' ), 999);

        // link a settings en plugin list page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'codificabsale_plugin_settings_link' ));
        
        //shutdown manejo
        $shut = new BsaleShutdown();
        $shut->register_shutdown();
        
    }

    public function codificabsale_plugin_settings_link($links)
    {
        $url = admin_url() . 'tools.php?page=woo_bsale-admin-menu';
        $settings_link = '<a href="' . esc_url($url) . '">Configurar</a>';
        $links[] = $settings_link;
        return $links;
    }

    // add a link to the WP Toolbar
    public function codificabsale_toolbar_link($wp_admin_bar)
    {
        $args = array(
            'id' => 'codificabsale',
            'title' => 'Integración Bsale',
            'href' => get_admin_url() . 'tools.php?page=woo_bsale-admin-menu',
            'meta' => array(
                'class' => 'codifintjs',
                'title' => 'Para configurar la integracion con Bsale, haga clic aquí',
            )
        );
        $wp_admin_bar->add_node($args);
    }

    /**
     * shortcodes
     */
//[foobar]
    public function woo_bsale_shortcode($atts)
    {
        
    }

    public function woocommerce_bsale_checkout_fields_function($checkout_fields)
    {
        $p = 2999;
        $checkout_fields['billing']['billing_tipo_documento']['priority'] = $p++;
        $checkout_fields['billing']['billing_rut']['priority'] = $p++;
        $checkout_fields['billing']['billing_company']['label'] = 'Nombre de la empresa';
        $checkout_fields['billing']['billing_company']['required'] = true;
        $checkout_fields['billing']['billing_company']['priority'] = $p++;
        $checkout_fields['billing']['billing_giro']['priority'] = $p++;
        return $checkout_fields;
    }

    /**
     * para cronjob con wp cron, para usar con WP control
     */
    public function bsale_action_cronjob_webhook_function()
    {
        $_REQUEST['param'] = 'yes';
        include_once __DIR__ . '/../../webhooks/bsale_product_wh_procesar.php';
        return true;

        //$url = get_site_url() . '/wp-content/plugins/woocommerce-bsalev2/webhooks/bsale_product_wh_procesar.php?param=yes';
        //se debe descomentar este endpoint antes de crear el cronjob de wp
        $url = get_site_url() . '/wp-json/wcbsalev2/v1/webhook_procesar/1?param=yes';
        wp_remote_get($url);

        return true;

        // Inicia cURL
        $session = curl_init($url);

        // Indica a cURL que retorne data
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 400);
        curl_setopt($session, CURLOPT_TIMEOUT, 400); //timeout in seconds
        // Configura cabeceras
        $headers = array(
        );
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        //Tell cURL that it should only spend 10 seconds
        //trying to connect to the URL in question.
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 300);

        //A given cURL operation should only take
        //30 seconds max.
        curl_setopt($session, CURLOPT_TIMEOUT, 300);

        // Ejecuta cURL
        $response = curl_exec($session);
        $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

        // Cierra la sesión cURL
        curl_close($session);
    }

    /**
     * para cronjob con wp cron resend webhook messages
     */
    public function bsale_action_cronjob_resend_function()
    {
        include_once __DIR__ . '/../../webhooks/bsale_product_resend.php';
        return true;

        $url = get_site_url() . '/wp-content/plugins/woocommerce-bsalev2/webhooks/bsale_product_resend_single.php?param=yes';
        wp_remote_get($url);

        return true;

        // Inicia cURL
        $session = curl_init($url);

        // Indica a cURL que retorne data
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 400);
        curl_setopt($session, CURLOPT_TIMEOUT, 400); //timeout in seconds
        // Configura cabeceras
        $headers = array(
        );
        curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
        //Tell cURL that it should only spend 10 seconds
        //trying to connect to the URL in question.
        curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 300);

        //A given cURL operation should only take
        //30 seconds max.
        curl_setopt($session, CURLOPT_TIMEOUT, 300);

        // Ejecuta cURL
        $response = curl_exec($session);
        $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

        // Cierra la sesión cURL
        curl_close($session);
    }

    public function bsale_codificacl_general_admin_notice()
    {
        global $pagenow;
//    if ( $pagenow == 'options-general.php' ) {
//       
//    }
        if( !is_admin() )
        {
            return;
        }
        require_once dirname(__FILE__) . '/../Autoload.php';

        WpUtils::get_bsale_last_error_notice();
    }

    /**
     * sincroniza datos deproducto individual y sus variaciones, se gatilla al presionar el boton en el 
     * editor de producto
     */
    public function bsale_ajax_sync_prod_action()
    {
        //global $wpdb; // this is how you get access to the database

        include_once dirname(__FILE__) . '/../../sync/product_sync_bsale.php';
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * sync prods nueva version, para sincronizar todos los productos
     */
    public function bsale_ajax_sync_prod_action2()
    {
        //global $wpdb; // this is how you get access to the database
        //echo("en ajax!");
        include_once dirname(__FILE__) . '/../../sync/product_sync_bsale2.php';
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * recarga datos de bsale tipso prod, user, sucursales, ec
     */
    public function bsale_reload_data_action()
    {
        include_once dirname(__FILE__) . '/../../sync/bsale_ajax_reload_data.php';
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * devuelve hosrotial de sync prodcuto en pag de edit producto
     */
    public function get_info_last_sync_bsale_action()
    {
        include_once dirname(__FILE__) . '/../../sync/get_info_last_sync_bsale_action.php';
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * devuelve historial de dtes emitidos para pedido
     */
    public function get_info_order_docs_action()
    {
        include_once dirname(__FILE__) . '/../../sync/get_info_order_docs_action.php';
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * agrega id de transsaccion en los emails
     */
    public function custom_woocommerce_email_order_meta_fields($fields, $sent_to_admin, $order)
    {
        $tid = get_post_meta($order->id, 'webpay_transaction_id', true);
        $cn = get_post_meta($order->id, 'cardNumber', true);

        if( !empty($tid) )
        {
            $fields['webpay_transaction_id'] = array(
                'label' => __('Id de transaccion Webpay'),
                'value' => $tid,
            );
        }
        if( !empty($cn) )
        {
            $fields['cardNumber'] = array(
                'label' => __('Tarjeta'),
                'value' => "***$cn",
            );
        }

        return $fields;
    }

    public function JSON_to_table($your_JSON_variable, $tblName, $key_items = null)
    {
        global $wpdb;

        $j_obj = json_decode($your_JSON_variable, true);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($your_JSON_variable, __METHOD__ . "json original:");
            Funciones::print_r_html($j_obj, __METHOD__ . "json decodificado:");
        }

        if( empty($j_obj) )
        {
            return '';
        }

        $j_obj = !empty($key_items) && isset($j_obj[$key_items]) ? $j_obj[$key_items] : $j_obj;

        $cq = "CREATE TABLE " . $tblName . " ( ";
        $has_id_attr = false;

        $tbody = array();

        foreach( $j_obj as $j_arr_key => $value )
        {
            $is_pk = ($j_arr_key === 'id');
            //contiene clave id, que será la primaria
            if( $is_pk )
            {
                $has_id_attr = true;
            }

            if( isset($_REQUEST['param']) )
            {
                //Funciones::print_r_html(__METHOD__ . " key='$j_arr_key' es pk? '$is_pk'");
            }
            //revisar si variable es string o int o qué
            $tipo = gettype($value);

            $tipo_dato = null;
            switch( $tipo )
            {
                case 'boolean':
                    $tipo_dato = " tinyint(4) DEFAULT NULL";
                    break;
                case 'integer':
                    $tipo_dato = " int(11) DEFAULT -1";
                    break;
                //float and double
                case 'double':
                    $tipo_dato = " FLOAT DEFAULT -1";
                    break;
                case 'string':
                    $tipo_dato = " VARCHAR(256)";
                    break;

                case 'array':
                    $tipo_dato = " TEXT";
                    break;
                case 'NULL':
                case 'object':
                case 'resource':
                case 'resource (closed)':
                case 'unknown type':
                    $tipo_dato = " VARCHAR(256)";
                    break;
                default:
                    $tipo_dato = " VARCHAR(256)";
            }

            if( $is_pk )
            {
                $tipo_dato .= ' PRIMARY KEY';
            }

            $tipo_dato .= "";
            $tbody[] = $j_arr_key . $tipo_dato;
        }

        //coloco campo de clave primaria al principio
        if( !$has_id_attr )
        {
            $tbody = array_unshift( $tbody," id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        }

        $tbody_str = implode(",\n", $tbody);
       
        $cq .= $tbody_str . ")\n"
                . "ENGINE=InnoDB DEFAULT CHARSET=utf8;\n\n";

        if( true )
        {
            return $cq;
        }

        $qi = "INSERT INTO $tblName (";
        reset($j_obj);
        foreach( $j_obj as $j_arr_key => $value )
        {
            $qi .= $j_arr_key . ",";
        }
        $qi = substr_replace($qi, "", -1);
        $qi .= ") VALUES (";
        reset($j_obj);
        foreach( $j_obj as $j_arr_key => $value )
        {
            $qi .= "'" . $wpdb->_real_escape($value) . "',";
        }
        $qi = substr_replace($qi, "", -1);
        $qi .= ")";

        return $qi;
    }
}
