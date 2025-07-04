<?php

require_once dirname(__FILE__) . '/constants.php';

//este archivo puede ser creado con las actions necesarias para modificar la integración. No viene con el plugin, así que no se sobreescribirá 
//al actulizarlo
$f1 = dirname(__FILE__) . '/bsale_custom_actions_filters.php';

if( file_exists($f1) )
{
    include_once $f1;
}

//sirve para guardar, de ua sola vez, todas las options de bsale

global $BSALE_OPTIONS_GLOBAL;

class Funciones
{

    /**
     * devuelve prefijo de tablas wc, solo si está integrado con wc
     * @global type $wpdb
     * @return type
     */
    public static function get_prefix()
    {
        global $wpdb;
        $prefix = '';
        if( INTEGRACION_SISTEMA === 'woocommerce' && isset($wpdb->prefix) )
        {
            $prefix = $wpdb->prefix;
        }
        return $prefix;
    }

    /**
     * stock a colocar en los productos bsale que deben quedar sin control de stock en wc 
     * @return type
     */
    public static function get_stock_ilimitado_prod_bsale()
    {
        return defined('BSALE_STOCK_ILIMITADO') ? BSALE_STOCK_ILIMITADO : 999999;
    }

    /**
     * copiar avisos del webhook de bsale a carpeta webhooks/resend?
     */
    public static function is_resend_avisos_bsale()
    {
        $arr = self::get_resend_urls();

        return is_array($arr) && count($arr) > 0;
    }

    /**
     * urls a las que reenviar los avisos de bsale
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_resend_urls($args = null)
    {
        if( $args && isset($args['wc_bsale_redirect_url']) )
        {
            return $args['wc_bsale_redirect_url'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = trim(get_option('wc_bsale_redirect_url'));
        }
        else
        {
            $dato = self::get_value('bsale_redirect_url', null);
        }


        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_redirect_url']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_redirect_url'];
        }

        $arr = explode("\n", $dato);

        //Funciones::print_r_html($arr, "get_resend_urls(), respuesta");

        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();

        foreach( $arr as $s )
        {
            $url = trim($s);

            //valido url
            if( empty($url) || filter_var($url, FILTER_VALIDATE_URL) === FALSE )
            {
                continue;
            }
            $arr2[] = $url;
        }

        //Funciones::print_r_html($arr2, "get_resend_urls(), respuesta despues e filtro");
        return $arr2;
    }

    public static function get_value($key, $default_value = null)
    {
        if( INTEGRACION_SISTEMA !== 'woocommerce' )
        {
            global $BSALE_OPTIONS_GLOBAL;

            //incializo blogarl si no lo ha sido ya
            if( !is_array($BSALE_OPTIONS_GLOBAL) || count($BSALE_OPTIONS_GLOBAL) <= 0 )
            {
                $options = new OpcionesTable();
                $options->load_bsale_options();
            }

            if( isset($BSALE_OPTIONS_GLOBAL[$key]) )
            {
                $dato = $BSALE_OPTIONS_GLOBAL[$key];
            }
            else
            {
                $dato = defined($key) ? constant($key) : $default_value;
            }
            return $dato;
        }
        else
        {
            $dato = defined($key) ? constant($key) : $default_value;
            return $dato;
        }
    }

    public static function get_tax_class_exentos_for_dte($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['BSALE_PRODUCTOS_EXENTOS_TAX_CLASSES']) )
        {
            return $args['BSALE_PRODUCTOS_EXENTOS_TAX_CLASSES'];
        }

        $dato = self::get_value('BSALE_PRODUCTOS_EXENTOS_TAX_CLASSES', '');
        //woocommerce
        /* if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
          {
          //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
          $dato = get_option('wc_bsale_estados_nv');
          //$arraux = explode(',', $dato);
          //$dato = $arraux;
          }
          else
          {

          } */

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['BSALE_PRODUCTOS_EXENTOS_TAX_CLASSES']) )
        {
            $dato = $BSALE_GLOBAL['BSALE_PRODUCTOS_EXENTOS_TAX_CLASSES'];
        }
        $arr = explode(',', $dato);

        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();

        foreach( $arr as $s )
        {
            $arr2[] = trim($s);
        }

        return $arr2;
    }

    public static function is_include_prods_afectos_y_exentos($args = null)
    {
        if( $args && isset($args['BSALE_DTE_INCLUDE_AFECTOS_Y_EXENTOS']) )
        {
            return $args['BSALE_DTE_INCLUDE_AFECTOS_Y_EXENTOS'];
        }

        $dato = self::get_value('BSALE_DTE_INCLUDE_AFECTOS_Y_EXENTOS', false);

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            //$dato = (int) get_option('wc_bsale_set_descto_on_resular_price');
            //$dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['BSALE_DTE_INCLUDE_AFECTOS_Y_EXENTOS']) )
        {
            $dato = $BSALE_GLOBAL['BSALE_DTE_INCLUDE_AFECTOS_Y_EXENTOS'];
        }

        return $dato;
    }

    public static function is_set_descuento_precios_en_dtes($args = null)
    {
        if( $args && isset($args['IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE']) )
        {
            return $args['IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE'];
        }

        // $dato = defined('IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE') ? IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE : false;
        $dato = self::get_value('IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE', false);

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            //$dato = (int) get_option('wc_bsale_set_descto_on_resular_price');
            //$dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE']) )
        {
            $dato = $BSALE_GLOBAL['IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    public static function get_shopify_api_key($args = null)
    {
        if( $args && isset($args['wc_bsale_shopify_api_key']) )
        {
            return $args['wc_bsale_shopify_api_key'];
        }

        $data = self::get_value('wc_bsale_shopify_api_key', '');

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shopify_api_key']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_shopify_api_key'];
        }

        return $data;
    }

    public static function get_shopify_password($args = null)
    {
        if( $args && isset($args['wc_bsale_shopify_password']) )
        {
            return $args['wc_bsale_shopify_password'];
        }


        $data = self::get_value('wc_bsale_shopify_password', '');

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shopify_password']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_shopify_password'];
        }

        return $data;
    }

    public static function get_shopify_tienda_url($args = null)
    {
        if( $args && isset($args['wc_bsale_shopify_tienda_url']) )
        {
            return $args['wc_bsale_shopify_tienda_url'];
        }


        $data = self::get_value('wc_bsale_shopify_tienda_url', '');

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shopify_tienda_url']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_shopify_tienda_url'];
        }

        return $data;
    }

    public static function get_shopify_base_url($args = null)
    {
        $api_key = self::get_shopify_api_key($args);
        $pass = self::get_shopify_password($args);
        $url = self::get_shopify_tienda_url($args);

        $data = 'https://' . $api_key . ':' . $pass . $url; //url @mystore.myshopify.com/admin/   

        return $data;
    }

    public static function get_shopify_productos_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'products.json';

        return $data;
    }

    public static function get_shopify_productos_count_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'products/count.json';

        return $data;
    }

    public static function get_shopify_productos_put_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'products/%s.json';

        return $data;
    }

    public static function get_shopify_producto_get_by_id_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'products/%s.json';

        return $data;
    }

    public static function get_shopify_variacion_get_by_id_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'variants/%s.json';

        return $data;
    }

    public static function get_shopify_variaciones_put_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'variants/%s.json';

        return $data;
    }

    public static function get_shopify_variaciones_post_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'products/%s/variants.json';

        return $data;
    }

    public static function get_shopify_producto_by_sku_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'products/search.json?query=sku:%s';

        return $data;
    }

    public static function get_shopify_order_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'orders/%s.json';

        return $data;
    }

    public static function get_shopify_orders_all_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'orders.json';

        return $data;
    }

    public static function get_shopify_inventario_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'inventory_levels/adjust.json';

        return $data;
    }

    public static function get_shopify_inventario_levels_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'inventory_levels.json';

        return $data;
    }

    public static function get_shopify_metafields_variants_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'products/%s/variants/%s/metafields.json';

        return $data;
    }

    public static function get_shopify_metafields_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'metafields/%s.json';

        return $data;
    }

    public static function get_shopify_inventory_item_url($args = null)
    {
        $data = self::get_shopify_base_url($args) . 'inventory_items/%s.json';

        $id = isset($args['id']) ? $args['id'] : -1;
        if( $id > 0 )
        {
            $data = sprintf($data, $id);
        }
        return $data;
    }

    public static function get_shopify_app_secret($args = null)
    {
        if( $args && isset($args['wc_bsale_shopify_app_secret']) )
        {
            return $args['wc_bsale_shopify_app_secret'];
        }


        $data = self::get_value('wc_bsale_shopify_app_secret', '');

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shopify_app_secret']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_shopify_app_secret'];
        }

        return $data;
    }

    public static function get_shopify_access_token($args = null)
    {
        if( $args && isset($args['SHOPIFY_ACCESS_TOKEN']) )
        {
            return $args['SHOPIFY_ACCESS_TOKEN'];
        }


        $data = self::get_value('SHOPIFY_ACCESS_TOKEN', '');

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['SHOPIFY_ACCESS_TOKEN']) )
        {
            $data = $BSALE_GLOBAL['SHOPIFY_ACCESS_TOKEN'];
        }

        return $data;
    }

    public static function get_order_prefix($args = null)
    {
        if( $args && isset($args['PREFIJO_ORDER']) )
        {
            return $args['PREFIJO_ORDER'];
        }


        $data = self::get_value('PREFIJO_ORDER', '');

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_send_email(), desde options");
            // $data = get_option('wc_bsale_send_email');
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['PREFIJO_ORDER']) )
        {
            $data = $BSALE_GLOBAL['PREFIJO_ORDER'];
        }

        if( empty($data) )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), not found! '$data', args:");
        }
        return $data;
    }

    public static function get_send_email($args = null)
    {
        if( $args && isset($args['wc_bsale_send_email']) )
        {
            return $args['wc_bsale_send_email'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_send_email(), desde options");
            $data = get_option('wc_bsale_send_email');
        }
        else
        {
            // $data = defined('SEND_EMAIL') ? SEND_EMAIL : null;
            $data = self::get_value('wc_bsale_send_email', null);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_send_email']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_send_email'];
        }

        if( empty($data) )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), not found! '$data', args:");
        }
        return $data;
    }

    public static function get_declare_sii($args = null)
    {
        if( $args && isset($args['wc_bsale_declare_sii']) )
        {
            return $args['wc_bsale_declare_sii'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $data = get_option('wc_bsale_declare_sii');
        }
        else
        {
            //$data = defined('DECLARE_SII') ? DECLARE_SII : null;
            $data = self::get_value('wc_bsale_declare_sii', null);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_declare_sii']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_declare_sii'];
        }

        if( empty($data) )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), not found! '$data', args:");
        }
        return $data;
    }

    /**
     * permitir editar campos de facturacion en edit order?
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_edit_facturacion_data($args = null)
    {
        if( $args && isset($args['wc_bsale_edit_facturac_data']) )
        {
            return $args['wc_bsale_edit_facturac_data'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $data = get_option('wc_bsale_edit_facturac_data');
            $data = $data > 0 ? true : false;
        }
        else
        {
            //$data = defined('DECLARE_SII') ? DECLARE_SII : null;
            $data = self::get_value('wc_bsale_edit_facturac_data', true);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_edit_facturac_data']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_edit_facturac_data'];
        }

        if( empty($data) )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), not found! '$data', args:");
        }
        return $data;
    }

    public static function is_mostrar_stock_sucursal($args = null)
    {
        if( $args && isset($args['wc_bsale_stock_por_sucursal']) )
        {
            return $args['wc_bsale_stock_por_sucursal'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $data = get_option('wc_bsale_stock_por_sucursal');
            $data = ($data != 0) ? true : false;
        }
        else
        {
            //$data = defined('DECLARE_SII') ? DECLARE_SII : null;
            $data = self::get_value('wc_bsale_stock_por_sucursal', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_stock_por_sucursal']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_stock_por_sucursal'];
        }

        return $data;
    }

    public static function is_mostrar_stock_sucursal_backend($args = null)
    {
        if( $args && isset($args['wc_bsale_stock_por_sucursal_back']) )
        {
            return $args['wc_bsale_stock_por_sucursal_back'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $data = get_option('wc_bsale_stock_por_sucursal_back');
        }
        else
        {
            //$data = defined('DECLARE_SII') ? DECLARE_SII : null;
            $data = self::get_value('wc_bsale_stock_por_sucursal_back', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_stock_por_sucursal_back']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_stock_por_sucursal_back'];
        }

        return $data;
    }

    public static function get_mostrar_stock_sucursal_place($args = null)
    {
        if( $args && isset($args['wc_bsale_stock_por_sucursal_place']) )
        {
            return $args['wc_bsale_stock_por_sucursal_place'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $data = get_option('wc_bsale_stock_por_sucursal_place');
        }
        else
        {
            $data = null;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_stock_por_sucursal_place']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_stock_por_sucursal_place'];
        }

        return $data;
    }

    /**
     * si debe agregar programatically campos checkout para boleta (billing_rut)
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_add_campos_checkout_boleta($args = null)
    {
        if( $args && isset($args['wc_bsale_add_campos_boleta']) )
        {
            return $args['wc_bsale_add_campos_boleta'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $data = get_option('wc_bsale_add_campos_boleta');
        }
        else
        {
            //$data = defined('DECLARE_SII') ? DECLARE_SII : null;
            $data = (int) self::get_value('wc_bsale_add_campos_boleta', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_add_campos_boleta']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_add_campos_boleta'];
        }

        return $data;
    }

    /**
     * add campos checout para factura:
     * billing_tipo_docto
     * billing_rut
     * billing_giro
     * billing_company obligatorio
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_add_campos_checkout_factura($args = null)
    {
        if( $args && isset($args['wc_bsale_add_campos_factura']) )
        {
            return $args['wc_bsale_add_campos_factura'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $data = get_option('wc_bsale_add_campos_factura');
        }
        else
        {
            //$data = defined('DECLARE_SII') ? DECLARE_SII : null;
            $data = (int) self::get_value('wc_bsale_add_campos_factura', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_add_campos_factura']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_add_campos_factura'];
        }

        return $data;
    }

    /**
     * devuelve el token de bsale
     */
    public static function get_token_bsale($args = null)
    {
        if( $args && isset($args['TOKEN_BSALE']) )
        {
            return $args['TOKEN_BSALE'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $token_bsale = get_option('wc_bsale_token');
        }
        else
        {
            $token_bsale = self::get_value('wc_bsale_token', null);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['TOKEN_BSALE']) )
        {
            $token_bsale = $BSALE_GLOBAL['TOKEN_BSALE'];
        }

        if( empty($token_bsale) )
        {
            
        }
        return $token_bsale;
    }

    /**
     * devuelve el sku para colocar en los costos de envío
     */
    public static function get_sku_envio($args = null)
    {
        if( $args && isset($args['wc_bsale_sku_envio']) )
        {
            return $args['wc_bsale_sku_envio'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $data = get_option('wc_bsale_sku_envio');
        }
        else
        {
            $data = self::get_value('wc_bsale_sku_envio', null);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_sku_envio']) )
        {
            $data = $BSALE_GLOBAL['wc_bsale_sku_en vio'];
        }
        return $data;
    }

    public static function get_valor_iva($args = null)
    {
        if( $args && isset($args['VALOR_IVA']) )
        {
            return $args['VALOR_IVA'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = get_option('wc_bsale_valor_iva');
        }
        else
        {
            $dato = self::get_value('VALOR_IVA', 1);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['VALOR_IVA']) )
        {
            $dato = $BSALE_GLOBAL['VALOR_IVA'];
        }

        //fix return cero
        $dato = ($dato == 0 || empty($dato)) ? 1 : $dato;
        return $dato;
    }

    public static function get_pais($args = null)
    {
        if( $args && isset($args['PAIS']) )
        {
            return $args['PAIS'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = get_option('wc_bsale_pais');
        }
        else
        {
            $dato = self::get_value('PAIS', 'CL');
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['PAIS']) )
        {
            $dato = $BSALE_GLOBAL['PAIS'];
        }

        return $dato;
    }

    /*     * ******id docs ***************** */

    public static function get_boleta_id($args = null)
    {
        if( $args && isset($args['wc_bsale_boleta_id']) )
        {
            return $args['wc_bsale_boleta_id'];
        }



        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_boleta_id');
        }
        else
        {
            //debo usar ticket en lugar de bol/fact?
            if( self::is_shopify_usar_ticket_boletas($args) )
            {
                $dato = self::get_ticket_id($args);
            }
            else
            {
                $dato = (int) self::get_value('wc_bsale_boleta_id', 0);
            }
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_boleta_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_boleta_id'];
        }

        //dtes afectas o exentas, segun prods con taxes
        //se asume quew id bol es de bol afecta
        if( $args && isset($args['is_dte_afecto']) && $args['is_dte_afecto'] == false )
        {
            $dato = (int) self::get_value('BOLETA_EXENTA_ID');
        }

        return $dato;
    }

    public static function get_factura_id($args = null)
    {
        if( $args && isset($args['wc_bsale_factura_id']) )
        {
            return $args['wc_bsale_factura_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_factura_id');
        }
        else
        {
            //debo usar ticket en lugar de bol/fact?
            if( false && self::is_shopify_usar_ticket_boletas($args) )
            {
                $dato = self::get_ticket_id($args);
            }
            else
            {
                $dato = (int) self::get_value('wc_bsale_factura_id', 0);
            }
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_factura_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_factura_id'];
        }

        //dtes afectas o exentas, segun prods con taxes
        //se asume quew id bol es de bol afecta
        if( $args && isset($args['is_dte_afecto']) && $args['is_dte_afecto'] == false )
        {
            $dato = (int) self::get_value('FACTURA_EXENTA_ID');
        }

        return $dato;
    }

    public static function get_nc_id($args = null)
    {
        if( $args && isset($args['wc_bsale_nc_id']) )
        {
            return $args['wc_bsale_nc_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_nc_id');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_nc_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_nc_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_nc_id'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    public static function get_nv_id($args = null)
    {
        if( $args && isset($args['wc_bsale_nv_id']) )
        {
            return $args['wc_bsale_nv_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_nv_id');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_nv_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_nv_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_nv_id'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    public static function get_gd_id($args = null)
    {
        if( $args && isset($args['wc_bsale_gd_id']) )
        {
            return $args['wc_bsale_gd_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_gd_id');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_gd_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_gd_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_gd_id'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    /**
     * restar al stock traido de bsale
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_bsale_limit_stock($args = null)
    {
        if( $args && isset($args['bsale_limit_stock']) )
        {
            return $args['bsale_limit_stock'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('bsale_limit_stock');
        }
        else
        {
            $dato = (int) self::get_value('bsale_limit_stock', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['bsale_limit_stock']) )
        {
            $dato = $BSALE_GLOBAL['bsale_limit_stock'];
        }


        return $dato;
    }

    /**
     * borrar archivos de log cada x días
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_days_delete_logs($args = null)
    {
        if( $args && isset($args['wc_bsale_days_delete_logs']) )
        {
            return $args['wc_bsale_days_delete_logs'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_days_delete_logs');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_days_delete_logs', 5);
        }
        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_days_delete_logs']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_days_delete_logs'];
        }
        return $dato;
    }

    /**
     * borrar historial de sync prods cada x días
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_days_delete_historial_sync_prods($args = null)
    {
        if( $args && isset($args['wc_bsale_table_delete_logs']) )
        {
            return $args['wc_bsale_table_delete_logs'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_table_delete_logs');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_table_delete_logs', 0);
        }
        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_table_delete_logs']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_table_delete_logs'];
        }
        return $dato;
    }

    public static function get_ticket_id($args = null)
    {
        if( $args && isset($args['wc_bsale_ticket_id']) )
        {
            return (int) $args['wc_bsale_ticket_id'];
        }

        //woocommerce
        /* if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
          {
          //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
          $dato = (int) get_option('wc_bsale_factura_id');
          }
          else */
        {
            $dato = (int) self::get_value('wc_bsale_ticket_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_ticket_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_ticket_id'];
        }

        return $dato;
    }

    /*     * ************************ */

    /**
     * 
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_dinam_attr_boleta($args = null)
    {
        if( $args && isset($args['wc_bsale_dinam_attr_boleta']) )
        {
            return $args['wc_bsale_dinam_attr_boleta'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dinam_attr_boleta');
        }
        else
        {
            //debo usar ticket en lugar de bol/fact?
            if( self::is_shopify_usar_ticket_boletas($args) )
            {
                $dato = self::get_dinam_attr_ticket($args);
            }
            else
            {
                $dato = (int) self::get_value('wc_bsale_dinam_attr_boleta', 0);
            }
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dinam_attr_boleta']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dinam_attr_boleta'];
        }

        if( empty($dato) )
        {
            
        }

        //dtes afectas o exentas, segun prods con taxes
        //se asume quew id bol es de bol afecta
        if( $args && isset($args['is_dte_afecto']) && $args['is_dte_afecto'] == false )
        {
            $dato = (int) self::get_value('BOLETA_EXENTA_DINAM_ATTR_ID');
        }

        return $dato;
    }

    public static function get_dinam_attr_nc($args = null)
    {
        if( $args && isset($args['wc_bsale_dinam_attr_nc']) )
        {
            return $args['wc_bsale_dinam_attr_nc'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dinam_attr_nc');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_dinam_attr_nc', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dinam_attr_nc']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dinam_attr_nc'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    public static function get_wc_pagos_bsale($args = null)
    {
        if( $args && isset($args['WC_PAGOS_BSALE']) )
        {
            return $args['WC_PAGOS_BSALE'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = trim(get_option('wc_bsale_pagos_bsale'));
        }


        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WC_PAGOS_BSALE']) )
        {
            $dato = $BSALE_GLOBAL['WC_PAGOS_BSALE'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    /**
     * devuelve array de comuna facturac=>sucursal bsale
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_comunas_sucursales_dte($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['wc_bsale_comuna_sucursal_listado']) )
        {
            return $args['wc_bsale_comuna_sucursal_listado'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            $dato = trim(get_option('wc_bsale_comuna_sucursal_listado'));
        }
        else
        {
            $dato = trim(self::get_value('wc_bsale_comuna_sucursal_listado', ''));
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_comuna_sucursal_listado']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_comuna_sucursal_listado'];
        }

        $arr = explode("\n", $dato);

        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();
        $utils = new Utils();

        foreach( $arr as $s )
        {
            $arr_linea = explode('=', $s);
            if( !is_array($arr_linea) || count($arr_linea) < 2 )
            {
                continue;
            }
            //se espera que linea contenga: nombre comuna = numero sucursal
            //ej: Concepción = 1
            $comuna = strtolower(trim($arr_linea[0]));

            $comuna = $utils->filter_chars($comuna);
            $sucursal_bsale_id = trim($arr_linea[1]);

            $arr2[$comuna] = $sucursal_bsale_id;
        }

        return $arr2;
    }

    public static function get_wc_shipping_bsale($args = null)
    {
        if( $args && isset($args['wc_bsale_shipping_bsale']) )
        {
            return $args['wc_bsale_shipping_bsale'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = trim(get_option('wc_bsale_shipping_bsale'));
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shipping_bsale']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_shipping_bsale'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    public static function get_dinam_attr_factura($args = null)
    {
        if( $args && isset($args['wc_bsale_dinam_attr_factura']) )
        {
            return $args['wc_bsale_dinam_attr_factura'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dinam_attr_factura');
        }
        else
        {
            //debo usar ticket en lugar de bol/fact?
            if( false && self::is_shopify_usar_ticket_boletas($args) )
            {
                $dato = self::get_dinam_attr_ticket($args);
            }
            else
            {
                $dato = (int) self::get_value('wc_bsale_dinam_attr_factura', 0);
            }
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dinam_attr_factura']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dinam_attr_factura'];
        }

        //dtes afectas o exentas, segun prods con taxes
        //se asume quew id bol es de bol afecta
        if( $args && isset($args['is_dte_afecto']) && $args['is_dte_afecto'] == false )
        {
            $dato = (int) self::get_value('FACTURA_EXENTA_DINAM_ATTR_ID');
        }

        return $dato;
    }

    public static function get_dinam_attr_nv($args = null)
    {
        if( $args && isset($args['wc_bsale_dinam_attr_nv']) )
        {
            return $args['wc_bsale_dinam_attr_nv'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dinam_attr_nv');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_dinam_attr_nv', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dinam_attr_nv']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dinam_attr_nv'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    public static function get_dinam_attr_gd($args = null)
    {
        if( $args && isset($args['wc_bsale_dinam_attr_gd']) )
        {
            return $args['wc_bsale_dinam_attr_gd'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dinam_attr_gd');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_dinam_attr_gd', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dinam_attr_gd']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dinam_attr_gd'];
        }

        if( empty($dato) )
        {
            
        }
        return $dato;
    }

    public static function get_dinam_attr_ticket($args = null)
    {
        if( $args && isset($args['wc_bsale_dinam_attr_ticket']) )
        {
            return $args['wc_bsale_dinam_attr_ticket'];
        }

        //woocommerce
        /* if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
          {
          //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
          $dato = (int) get_option('wc_bsale_dinam_attr_gd');
          }
          else */
        {
            $dato = (int) self::get_value('wc_bsale_dinam_attr_ticket', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dinam_attr_ticket']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dinam_attr_ticket'];
        }

        return $dato;
    }

    /*     * ************ */

    public static function get_dinam_attr_nv_notas($args = null)
    {
        if( $args && isset($args['wc_bsale_dattr_nv_customer_notes']) )
        {
            return $args['wc_bsale_dattr_nv_customer_notes'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dattr_nv_customer_notes');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_dattr_nv_customer_notes', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dattr_nv_customer_notes']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dattr_nv_customer_notes'];
        }

        return $dato;
    }

    public static function get_dinam_attr_boleta_notas($args = null)
    {
        if( $args && isset($args['wc_bsale_dattr_boleta_customer_notes']) )
        {
            return $args['wc_bsale_dattr_boleta_customer_notes'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dattr_boleta_customer_notes');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_dattr_boleta_customer_notes', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dattr_boleta_customer_notes']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dattr_boleta_customer_notes'];
        }

        return $dato;
    }

    public static function get_dinam_attr_factura_notas($args = null)
    {
        if( $args && isset($args['wc_bsale_dattr_factura_customer_notes']) )
        {
            return $args['wc_bsale_dattr_factura_customer_notes'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_dattr_factura_customer_notes');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_dattr_factura_customer_notes', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_dattr_factura_customer_notes']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_dattr_factura_customer_notes'];
        }

        return $dato;
    }

    public static function get_matriz_bsale($args = null)
    {
        if( $args && isset($args['wc_bsale_casa_matriz_id']) )
        {
            return $args['wc_bsale_casa_matriz_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_casa_matriz_id');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_casa_matriz_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_casa_matriz_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_casa_matriz_id'];
        }

        if( empty($dato) )
        {
            Funciones::print_r_html($args, "Funciones::get_matriz_bsale(), sucursal not found! '$dato', args:");
        }
        return $dato;
    }

    public static function get_seller_id($args = null)
    {
        if( $args && isset($args['wc_bsale_seller_id']) )
        {
            return $args['wc_bsale_seller_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_seller_id');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_seller_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_seller_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_seller_id'];
        }

        return $dato;
    }

    public static function get_estados_nv_arr($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['WP_EMITIR_NV_EN_ESTADOS']) )
        {
            return $args['WP_EMITIR_NV_EN_ESTADOS'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = get_option('wc_bsale_estados_nv');
            //$arraux = explode(',', $dato);
            //$dato = $arraux;
        }
        else
        {
            $dato = self::get_value('WP_EMITIR_NV_EN_ESTADOS', '');
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WP_EMITIR_NV_EN_ESTADOS']) )
        {
            $dato = $BSALE_GLOBAL['WP_EMITIR_NV_EN_ESTADOS'];
        }
        $arr = explode(',', $dato);

        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();

        foreach( $arr as $s )
        {
            $arr2[] = trim($s);
        }

        return $arr2;
    }

    public static function get_skus_to_skip($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['wc_bsale_skus_prefix_to_skip']) )
        {
            return $args['wc_bsale_skus_prefix_to_skip'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = get_option('wc_bsale_skus_prefix_to_skip');
            //$arraux = explode(',', $dato);
            //$dato = $arraux;
        }
        else
        {
            $dato = self::get_value('BSALE_SKIP_PRODS_STARTS_WITH', null);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_skus_prefix_to_skip']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_skus_prefix_to_skip'];
        }
        $arr = explode(',', $dato);

        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();

        foreach( $arr as $s )
        {
            $arr2[] = trim($s);
        }

        return $arr2;
    }

    /**
     * estado en que se anula el dte con nota de crédito
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return type
     */
    public static function get_estado_dte_cancelled($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['wc_bsale_estado_dte_cancelled']) )
        {
            return $args['wc_bsale_estado_dte_cancelled'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            $dato = get_option('wc_bsale_estado_dte_cancelled');
        }
        else
        {
            $dato = self::get_value('wc_bsale_estado_dte_cancelled', '');
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_estado_dte_cancelled']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_estado_dte_cancelled'];
        }

        $arr = explode(',', $dato);

        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();

        foreach( $arr as $s )
        {
            $arr2[] = trim($s);
        }

        return $arr2;
        //  return trim($dato);
    }

    public static function get_estados_dte_arr($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['WP_EMITIR_DTE_EN_ESTADOS']) )
        {
            return $args['WP_EMITIR_DTE_EN_ESTADOS'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = get_option('wc_bsale_estados_dte');
        }
        else
        {
            $dato = self::get_value('WP_EMITIR_DTE_EN_ESTADOS', '');
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WP_EMITIR_DTE_EN_ESTADOS']) )
        {
            $dato = $BSALE_GLOBAL['WP_EMITIR_DTE_EN_ESTADOS'];
        }
        $arr = explode(',', $dato);

        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();

        foreach( $arr as $s )
        {
            $arr2[] = trim($s);
        }

        return $arr2;
    }

    /**
     * estados en que la integración emitirá dtes (nv, bol o fact)
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return type
     */
    public static function get_estados_enabled_arr($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['wc_bsale_estados_enabled']) )
        {
            return $args['wc_bsale_estados_enabled'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //devuelvo estados en que se emiten nv y boleta
            $datoaux = self::get_estados_nv_arr();
            $datoaux2 = self::get_estados_dte_arr();

            $resultado = array_merge($datoaux, $datoaux2);
            $dato = array_unique($resultado);
        }
        else
        {
            $dato = array();
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_estados_enabled']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_estados_enabled'];
        }

        return $dato;
    }

    /**
     * string, separado pro comas de pagos para los que se debe emitir nv
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return type
     */
    public static function get_pagos_nv($args = null)
    {
        //debe venir como array desde antes
        if( $args && isset($args['WC_PAYMENTS_NV']) )
        {
            return $args['WC_PAYMENTS_NV'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = get_option('wc_bsale_payments_nv');
            $dato = empty($dato) ? 'all' : $dato;
        }
        else
        {
            $dato = self::get_value('WC_PAYMENTS_NV', '');
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WC_PAYMENTS_NV']) )
        {
            $dato = $BSALE_GLOBAL['WC_PAYMENTS_NV'];
        }

        return $dato;
    }

    /**
     * se excluye a la casa matriz
     * @param type $args
     * @return type
     */
    public static function get_sucursales_bsale($args = null)
    {
        if( $args && isset($args['BSALE_SUCURSAL_OTRAS_SUCURSALES_ID']) )
        {
            return $args['BSALE_SUCURSAL_OTRAS_SUCURSALES_ID'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = trim(get_option('wc_bsale_sucursales_stock', ''));
        }
        else
        {
            $dato = self::get_value('BSALE_SUCURSAL_OTRAS_SUCURSALES_ID', '');
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['BSALE_SUCURSAL_OTRAS_SUCURSALES_ID']) )
        {
            $dato = $BSALE_GLOBAL['BSALE_SUCURSAL_OTRAS_SUCURSALES_ID'];
        }

        //Funciones::get_matriz_bsale()
        if( !empty($dato) )
        {
            $otras_sucursales_array = array();
            $array = explode(',', $dato);

            foreach( $array as $id )
            {
                $otras_sucursales_array[$id] = $id;
            }
        }
        else
        {
            $otras_sucursales_array = array();
        }



        if( !is_array($otras_sucursales_array) || count($otras_sucursales_array) <= 0 )
        {
            return array();
        }


        //saco sucursal matriz, si es que está
        $sucursal_id = self::get_matriz_bsale();

        if( ($key = array_search($sucursal_id, $otras_sucursales_array)) !== false )
        {
            unset($otras_sucursales_array[$key]);
        }
        //array sin valores repetidos
        $otras_sucursales_array = array_unique($otras_sucursales_array);

        return $otras_sucursales_array;
    }

    public static function is_despacho_boleta($args = null)
    {
        if( $args && isset($args['wc_bsale_despachar_boleta']) )
        {
            return $args['wc_bsale_despachar_boleta'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_despachar_boleta');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_despachar_boleta', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_despachar_boleta']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_despachar_boleta'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    public static function is_despacho_factura($args = null)
    {
        if( $args && isset($args['wc_bsale_despachar_factura']) )
        {
            return $args['wc_bsale_despachar_factura'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_despachar_factura');
        }
        else
        {
            $dato = self::get_value('wc_bsale_despachar_factura', false);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_despachar_factura']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_despachar_factura'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * despachar guia de despacho?
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_despacho_gd($args = null)
    {
        if( $args && isset($args['wc_bsale_despachar_gd']) )
        {
            return $args['wc_bsale_despachar_gd'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_despachar_gd');
        }
        else
        {
            $dato = self::get_value('wc_bsale_despachar_gd', false);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_despachar_gd']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_despachar_gd'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * integrac de inventario: crear o no productos automatica%
     * @param type $args
     * @return type
     */
    public static function is_create_products($args = null)
    {
        if( $args && isset($args['wc_bsale_create_prods']) )
        {
            return $args['wc_bsale_create_prods'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_create_prods');

            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_create_prods', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_create_prods']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_create_prods'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * integrac de inventario:
     * update prod name o no
     * @param type $args
     * @return type
     */
    public static function is_update_product_name($args = null)
    {
        if( $args && isset($args['UPDATE_PRODUCT_NAME']) )
        {
            return $args['UPDATE_PRODUCT_NAME'];
        }
        $dato = self::get_value('UPDATE_PRODUCT_NAME', false);

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['UPDATE_PRODUCT_NAME']) )
        {
            $dato = $BSALE_GLOBAL['UPDATE_PRODUCT_NAME'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * integ inventario: update variation name o no
     * @param type $args
     * @return type
     */
    public static function is_update_variation_name($args = null)
    {
        if( $args && isset($args['UPDATE_VARIATION_NAME']) )
        {
            return $args['UPDATE_VARIATION_NAME'];
        }

        $dato = self::get_value('UPDATE_VARIATION_NAME', false);

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['UPDATE_VARIATION_NAME']) )
        {
            $dato = $BSALE_GLOBAL['UPDATE_VARIATION_NAME'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * si esta enabled integ stock
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return type
     */
    public static function is_enabled_integ_inventario($args = null)
    {
        if( $args && isset($args['wc_bsale_enable_inventario']) )
        {
            return $args['wc_bsale_enable_inventario'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_enable_inventario', 0);
            $dato = ($dato > 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_enable_inventario', 0);
            $dato = ($dato > 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_enable_inventario']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_enable_inventario'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    public static function is_enabled_integ_facturacion($args = null)
    {
        if( $args && isset($args['wc_bsale_enable_facturacion']) )
        {
            return $args['wc_bsale_enable_facturacion'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_enable_facturacion');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_enable_facturacion', 0);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_enable_facturacion']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_enable_facturacion'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    public static function is_shopify_set_normal_price($args = null)
    {
        if( $args && isset($args['wc_bsale_shopify_set_normal_price']) )
        {
            return $args['wc_bsale_shopify_set_normal_price'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_enable_facturacion');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_shopify_set_normal_price', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shopify_set_normal_price']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_shopify_set_normal_price'];
        }
        return $dato;
    }

    public static function is_include_gloss_cost($args = null)
    {
        $dato = defined('INCLUDE_GLOSS_COST') ? INCLUDE_GLOSS_COST : false;

        return $dato;
    }

    public static function is_shopify_force_taxes_included($args = null)
    {
        $dato = defined('SHOPIFY_FORCE_TAXES_INCLUDED') ? SHOPIFY_FORCE_TAXES_INCLUDED : false;

        return $dato;
    }

    public static function is_shopify_usar_ticket_boletas($args = null)
    {
        if( $args && isset($args['wc_bsale_shopify_usar_ticket_boletas']) )
        {
            return $args['wc_bsale_shopify_usar_ticket_boletas'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            return -1;
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            // $dato = (int) get_option('wc_bsale_shopify_usar_ticket_boletas');
            //$dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_shopify_usar_ticket_boletas', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shopify_usar_ticket_boletas']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_shopify_usar_ticket_boletas'];
        }
        return $dato;
    }

    /**
     * en este estado se emiten bol y fact
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_enabled_inventario_on_completed($args = null)
    {
        if( $args && isset($args['WP_ENABLED_INTEGRACION_ORDER_COMPLETED']) )
        {
            return $args['WP_ENABLED_INTEGRACION_ORDER_COMPLETED'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_enable_on_completed');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('WP_ENABLED_INTEGRACION_ORDER_COMPLETED', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_COMPLETED']) )
        {
            $dato = $BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_COMPLETED'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * se debe incluir el sku al momento de generar un dte?
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_send_sku($args = null)
    {
        if( $args && isset($args['wc_bsale_send_sku']) )
        {
            return $args['wc_bsale_send_sku'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_send_sku');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_send_sku', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_send_sku']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_send_sku'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * a veces se emiten bol y fact en este estado
     * o bien, nv
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_enabled_inventario_on_processing($args = null)
    {
        if( $args && isset($args['WP_ENABLED_INTEGRACION_ORDER_STATUS_PROCESSING']) )
        {
            return $args['WP_ENABLED_INTEGRACION_ORDER_STATUS_PROCESSING'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_enable_on_processing');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('WP_ENABLED_INTEGRACION_ORDER_STATUS_PROCESSING', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_STATUS_PROCESSING']) )
        {
            $dato = $BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_STATUS_PROCESSING'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    public static function is_enabled_inventario_on_pending($args = null)
    {
        if( $args && isset($args['WP_ENABLED_INTEGRACION_ORDER_STATUS_PENDING']) )
        {
            return $args['WP_ENABLED_INTEGRACION_ORDER_STATUS_PENDING'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_enable_on_pending');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('WP_ENABLED_INTEGRACION_ORDER_STATUS_PENDING', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_STATUS_PENDING']) )
        {
            $dato = $BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_STATUS_PENDING'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * mostrar en wc solo los shipping methods asociados a sucursales Bsale
     * que sí tengan stock de todos los productos del carro
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_enabled_shipping_filter_stock($args = null)
    {
        if( $args && isset($args['wc_bsale_shipping_filter_stock']) )
        {
            return $args['wc_bsale_shipping_filter_stock'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_shipping_filter_stock');
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_shipping_filter_stock']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_shipping_filter_stock'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * los pedidos on-hold son los pagados con bacs y otros, emiten nv en este estado.
     * Se debe activar si se va a emitir nv al pagar con bacs
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_enabled_inventario_on_hold($args = null)
    {
        if( $args && isset($args['WP_ENABLED_INTEGRACION_ORDER_ON_HOLD']) )
        {
            return $args['WP_ENABLED_INTEGRACION_ORDER_ON_HOLD'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_enable_on_hold');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('WP_ENABLED_INTEGRACION_ORDER_ON_HOLD', false);
            $dato = ($dato != 0) ? true : false;
        }
        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_ON_HOLD']) )
        {
            $dato = $BSALE_GLOBAL['WP_ENABLED_INTEGRACION_ORDER_ON_HOLD'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    public static function is_add_shipping_in_dte($args = null)
    {
        if( $args && isset($args['wc_bsale_incluir_despacho_en_dte']) )
        {
            return $args['wc_bsale_incluir_despacho_en_dte'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_incluir_despacho_en_dte');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_incluir_despacho_en_dte', false);
            $dato = ($dato != 0) ? true : false;
        }
        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_incluir_despacho_en_dte']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_incluir_despacho_en_dte'];
        }
        return $dato;
    }

    /**
     * solo sirve para woocommerce
     */
    public static function get_bsale_last_error()
    {
        if( INTEGRACION_SISTEMA !== 'woocommerce' )
        {
            return false;
        }

        $bsale_option_errors = get_option('bsale_option_errors');
        $arr = !empty($bsale_option_errors) ? json_decode($bsale_option_errors, true) : null;

        $code = isset($arr['code']) ? $arr['code'] : -1;

        return $arr;
    }

    /**
     * integrac inventario, update precio normal
     * @param type $args
     * @return type
     */
    public static function is_update_product_price($args = null)
    {
        if( $args && isset($args['wc_bsale_update_price']) )
        {
            return $args['wc_bsale_update_price'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_update_price');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_update_price', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_update_price']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_update_price'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * se debe conectar a bsale para checkear stock disponible después de agregar un producto al carro?
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_validate_stock_on_add_to_cart($args = null)
    {
        if( $args && isset($args['wc_bsale_sync_stock_on_cart_add']) )
        {
            return $args['wc_bsale_sync_stock_on_cart_add'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_sync_stock_on_cart_add');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_sync_stock_on_cart_add', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_sync_stock_on_cart_add']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_sync_stock_on_cart_add'];
        }
        
        
        return $dato;
    }

    public static function is_anular_dtes_on_order_cancelled($args = null)
    {
        if( $args && isset($args['ANULAR_DTES_ON_ORDER_CANCEL']) )
        {
            return $args['ANULAR_DTES_ON_ORDER_CANCEL'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_anular_dtes_cancelled');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('ANULAR_DTES_ON_ORDER_CANCEL', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['ANULAR_DTES_ON_ORDER_CANCEL']) )
        {
            $dato = $BSALE_GLOBAL['ANULAR_DTES_ON_ORDER_CANCEL'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * emitir dte segun la comuna de fact? si/no
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_emitir_dte_segun_comuna($args = null)
    {
        if( $args && isset($args['wc_bsale_comuna_sucursal_option']) )
        {
            return $args['wc_bsale_comuna_sucursal_option'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_comuna_sucursal_option');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_comuna_sucursal_option', false);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_comuna_sucursal_option']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_comuna_sucursal_option'];
        }

        return $dato;
    }

    /**
     * si precios de pedidos wc vienen o no con iva. Default: SÍ
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function is_descontar_iva_precios($args = null)
    {
        //itemized = cada prod con el impuesto (recomendado)
        //single = un item "iva" en el subtotal
        $woocommerce_tax_total_display = get_option('woocommerce_tax_total_display');
        $is_tax_enabled = wc_tax_enabled();

        if( !$is_tax_enabled )
        {
            return true;
        }

        return ($woocommerce_tax_total_display === 'itemized');

        /*

          if( $args && isset($args['wc_bsale_descontar_iva_precios']) )
          {
          return $args['wc_bsale_descontar_iva_precios'];
          }

          //woocommerce
          if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
          {
          //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
          $dato = (int) get_option('wc_bsale_descontar_iva_precios');
          $dato = ($dato != 0) ? true : false;
          }
          else
          {
          $dato = self::get_value('BSALE_DESCONTAR_IVA_MANUAL', false);
          $dato = ($dato != 0) ? true : false;
          }

          //global tiene precedencia sobre todo
          global $BSALE_GLOBAL;
          if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_descontar_iva_precios']) )
          {
          $dato = $BSALE_GLOBAL['wc_bsale_descontar_iva_precios'];
          }

          if( empty($dato) )
          {
          //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
          }
          return $dato; */
    }

    /* public static function is_descontar_iva_envios($args = null)
      {
      return self::is_descontar_iva_precios($args);

      if( $args && isset($args['wc_bsale_descontar_iva_envios']) )
      {
      return $args['wc_bsale_descontar_iva_envios'];
      }

      //woocommerce
      if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
      {
      //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
      $dato = (int) get_option('wc_bsale_descontar_iva_envios');
      $dato = ($dato != 0) ? true : false;
      }
      else
      {
      $dato = self::get_value('wc_bsale_descontar_iva_envios', false);
      $dato = ($dato != 0) ? true : false;
      }

      //global tiene precedencia sobre todo
      global $BSALE_GLOBAL;
      if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_descontar_iva_envios']) )
      {
      $dato = $BSALE_GLOBAL['wc_bsale_descontar_iva_envios'];
      }

      return $dato;
      } */

    /**
     * integrac inventario, update precio oferta
     * @param type $args
     * @return type
     */
    public static function is_update_product_price_desc($args = null)
    {
        if( $args && isset($args['UPDATE_PRODUCT_PRICE_DESC']) )
        {
            return $args['UPDATE_PRODUCT_PRICE_DESC'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_sync_precios_oferta');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('UPDATE_PRODUCT_PRICE_DESC', false);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['UPDATE_PRODUCT_PRICE_DESC']) )
        {
            $dato = $BSALE_GLOBAL['UPDATE_PRODUCT_PRICE_DESC'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * devuelve array de id de product types, permitidos para sincronizarse con Bsale 
     * @param type $args
     */
    public static function get_bsale_product_type_allowed($args = null)
    {
        //
        if( $args && isset($args['BSALE_PRODS_TYPE_ALLOWED']) )
        {
            return $args['BSALE_PRODS_TYPE_ALLOWED'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = get_option('wc_bsale_product_types_stock');
        }
        else
        {
            $dato = self::get_value('BSALE_PRODS_TYPE_ALLOWED', false);
        }


        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['BSALE_PRODS_TYPE_ALLOWED']) )
        {
            $dato = $BSALE_GLOBAL['BSALE_PRODS_TYPE_ALLOWED'];
        }

        if( !empty($dato) )
        {
            $arr = explode(',', $dato);
        }
        else
        {
            $arr = null;
        }


        if( $arr == null || !is_array($arr) )
        {
            $arr = array();
        }

        //saco espacios en blanco
        $arr2 = array();

        foreach( $arr as $id )
        {
            $id = (int) trim($id);

            if( $id > 0 )
            {
                $arr2[$id] = $id;
            }
        }

        return count($arr2) > 0 ? $arr2 : array();
    }

    public static function is_update_product_price_mayorista($args = null)
    {
        if( $args && isset($args['UPDATE_PRODUCT_PRICE_MAYORISTA']) )
        {
            return $args['UPDATE_PRODUCT_PRICE_MAYORISTA'];
        }

        $dato = self::get_value('UPDATE_PRODUCT_PRICE_MAYORISTA', false);

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['UPDATE_PRODUCT_PRICE_MAYORISTA']) )
        {
            $dato = $BSALE_GLOBAL['UPDATE_PRODUCT_PRICE_MAYORISTA'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * integ inventario: update stock
     * @param type $args
     * @return type
     */
    public static function is_update_product_stock($args = null)
    {
        if( $args && isset($args['wc_bsale_update_stock']) )
        {
            return $args['wc_bsale_update_stock'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_declare_sii(), desde options");
            $dato = (int) get_option('wc_bsale_update_stock');
            $dato = ($dato != 0) ? true : false;
        }
        else
        {
            $dato = self::get_value('wc_bsale_update_stock', 0);
            $dato = ($dato != 0) ? true : false;
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_update_stock']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_update_stock'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * lista de precio para precio normal integ inventario
     * @param type $args
     * @return type
     */
    public static function get_lp_bsale($args = null)
    {
        if( $args && isset($args['wc_bsale_lista_precios_id']) )
        {
            return $args['wc_bsale_lista_precios_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_lista_precios_id');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_lista_precios_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_lista_precios_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_lista_precios_id'];
        }

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * devuelve lp especiales (de oferta) de Bsale
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_lp_oferta_bsale($args = null)
    {
        if( $args && isset($args['wc_bsale_lista_precios_oferta_id']) )
        {
            return $args['wc_bsale_lista_precios_oferta_id'];
        }

        //woocommerce
        if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
        {
            //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
            $dato = (int) get_option('wc_bsale_lista_precios_oferta_id');
        }
        else
        {
            $dato = (int) self::get_value('wc_bsale_lista_precios_oferta_id', 0);
        }

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_lista_precios_oferta_id']) )
        {
            $dato = $BSALE_GLOBAL['wc_bsale_lista_precios_oferta_id'];
        }

        return $dato;
    }

    /**
     * lp id para precios normales para mayoristas
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_lp_mayorista_normal_bsale($args = null)
    {
        if( $args && isset($args['BSALE_LISTA_PRECIOS_ESPECIALES_2']) )
        {
            return $args['BSALE_LISTA_PRECIOS_ESPECIALES_2'];
        }

        //woocommerce
        /* if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
          {
          //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
          $dato = (int) get_option('wc_bsale_lista_precios_oferta_id');
          }
          else
          {
          $dato = (int) self::get_value('BSALE_LISTA_PRECIOS_ESPECIALES_2', 0);
          } */

        $dato = (int) self::get_value('BSALE_LISTA_PRECIOS_ESPECIALES_2', 0);

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['BSALE_LISTA_PRECIOS_ESPECIALES_2']) )
        {
            $dato = $BSALE_GLOBAL['BSALE_LISTA_PRECIOS_ESPECIALES_2'];
        }

        return $dato;
    }

    /**
     * lp id para precios oferta para mayoristas
     * @global type $BSALE_GLOBAL
     * @param type $args
     * @return \type
     */
    public static function get_lp_mayorista_oferta_bsale($args = null)
    {
        if( $args && isset($args['BSALE_LISTA_PRECIOS_ESPECIALES_3']) )
        {
            return $args['BSALE_LISTA_PRECIOS_ESPECIALES_3'];
        }

        //woocommerce
        /* if( INTEGRACION_SISTEMA === 'woocommerce' && function_exists('get_option') )
          {
          //Funciones::print_r_html($args, "Funciones::get_token_bsale(), token desde options");
          $dato = (int) get_option('wc_bsale_lista_precios_oferta_id');
          }
          else
          {
          $dato = (int) self::get_value('BSALE_LISTA_PRECIOS_ESPECIALES_2', 0);
          } */

        $dato = (int) self::get_value('BSALE_LISTA_PRECIOS_ESPECIALES_3', 0);

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['BSALE_LISTA_PRECIOS_ESPECIALES_3']) )
        {
            $dato = $BSALE_GLOBAL['BSALE_LISTA_PRECIOS_ESPECIALES_3'];
        }

        return $dato;
    }

    public static function get_url_lp_especial()
    {
        $lp_oferta_id = self::get_lp_oferta_bsale();

        return BSALE_BASE_URL . 'v1/price_lists/' . $lp_oferta_id . '/details.json';
    }

    public static function get_url_lp_by_id($lp_id)
    {
        return BSALE_BASE_URL . 'v1/price_lists/' . $lp_id . '/details.json';
    }

    /**
     * devuelve lista de precio url con la lp para inventario u otra que se le indique en lso params
     * @param type $args
     * @return type
     */
    public static function get_lp_url_bsale($args = null)
    {
        if( $args && isset($args['BSALE_PRODUCTO_PRECIO_URL']) )
        {
            return $args['BSALE_PRODUCTO_PRECIO_URL'];
        }

        $dato = defined('BSALE_PRODUCTO_PRECIO_URL') ? BSALE_PRODUCTO_PRECIO_URL : '';

        //coloco la lp en la url
        $lp_id = isset($args['wc_bsale_lista_precios_id']) ? $args['wc_bsale_lista_precios_id'] : self::get_lp_bsale($args);

        //global tiene precedencia sobre todo
        global $BSALE_GLOBAL;
        if( $BSALE_GLOBAL && isset($BSALE_GLOBAL['wc_bsale_lista_precios_id']) )
        {
            $lp_id = $BSALE_GLOBAL['wc_bsale_lista_precios_id'];
        }

        $dato = sprintf($dato, $lp_id);

        if( empty($dato) )
        {
            //Funciones::print_r_html($args, "Funciones::get_sucursales_bsale(), sucursales not found! '$dato', args:");
        }
        return $dato;
    }

    /**
     * imprime un arreglo formateado en pantalla en varias lineas
     * @param <type> $arr
     */
    public static function print_r_html($arr, $title = null)
    {

        if( isset($_REQUEST['bsale_silent']) )
        {
            return;
        }
        if( PHP_SAPI === 'cli' || PHP_SAPI === 'cgi-fcgi' )
        {
            $cli = true;
        }
        else
        {
            $cli = false;
        }
        //para que siempre loguee html
        $cli = false;

        $hoy = date('d-m-Y H:i:s');

        if( !empty($title) )
        {
            $content = "[$hoy] $title";
            $len = strlen($content);
            $subrayado = '';
            $subrayado = str_pad($subrayado, $len, '=');

            if( !$cli )
            {
                $str = "<pre>$content\n$subrayado\n</pre>";
            }
            else
            {
                $str = "$content\n$subrayado\n";
            }
            echo($str);
        }

        if( is_array($arr) )
        {

            $u = new Utils();
            $u->html_show_array($arr, $cli);

            /* $renderer = new ArrayToTextTable($arr);
              $renderer->showHeaders(true);
              $table = $renderer->render(true);
              echo($table); */
        }
        elseif( $arr != null )
        {
            //si no es array ni json
            if( is_string($arr) && strpos($arr, '{') === false )
            {

                if( strlen($arr) > 0 )
                {
                    $content = "[$hoy] $arr";
                    $len = strlen($content);
                    $subrayado = '';
                    $subrayado = str_pad($subrayado, $len, '=');

                    if( !$cli )
                    {
                        $str = "<pre>$content\n$subrayado\n</pre>";
                    }
                    else
                    {
                        $str = "$content\n$subrayado\n";
                    }
                    echo($str);
                }
            }
            else
            {
                if( !$cli )
                {
                    echo('<pre>' . print_r($arr, true) . "\n</pre>");
                    /* echo("<textarea cols='100' rows='3'>"); //pre
                      print_r($arr);
                      echo("</textarea>\n"); */
                }
                else
                {
                    echo( print_r($arr, true) . "\n");
                    echo("\n");
                }
            }
        }
        //flush();
    }

    /**
     * retorna un arreglo formateado en pantalla en varias lineas
     * @param <type> $arr
     * @return <type>
     */
    public static function print_r_html2($arr)
    {
        if( isset($_REQUEST['bsale_silent']) )
        {
            return;
        }
        return '<pre>' . print_r($arr, true) . '</pre>';
    }

    public static function log($str)
    {
        if( isset($_REQUEST['bsale_silent']) )
        {
            return;
        }
        echo ("<p>$str</p>");
    }
}
