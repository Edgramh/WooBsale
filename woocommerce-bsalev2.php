<?php
/**
 * Plugin Name: Woocommerce Bsale V2
 * Plugin URI: https://codificando.cl/www/bsale-woocommerce/
 * Description: Integración de Woocommerce con Bsale para sincronizar inventario y facturación (boletas, facturas, notas de venta)
 * Version: 7.1.6
 * Author: Jason Matamala Gajardo
 * Author URI: https://codificando.cl
 * License: GPL2
 * 
 * WC requires at least: 5.0
 * WC tested up to: 7.7.0
 */
//no direct access allowed
defined('ABSPATH') or die('No script kiddies please!');
define('BSALE_OPTIONS_GROUP', 'bsaleoption-group');
define('BSALE_WOOC_URL', plugin_dir_url(__FILE__));

//https://rudrastyh.com/woocommerce/create-product-programmatically.html products attributes
//versin de la tabla de la db
global $woo_bsale_db_version;
$woo_bsale_db_version = '2.1';

global $woo_bsale_db_url;
$woo_bsale_db_url = plugin_dir_url(__FILE__);

global $woo_bsale_db_path;
$woo_bsale_db_path = plugin_dir_path(__FILE__);

function bsale_wc_woocommerce_disabled_notice()
{
    ?>
    <div class="notice notice-error">
        <p>Woocommerce está desactivado. El plugin <strong>Woocomerce Bsale V2</strong> no podrá funcionar hasta que se active woocommerce.</p>
    </div>
    <?php
}

//check woocomerce is active
if( !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) )
{
    add_action('admin_notices', 'bsale_wc_woocommerce_disabled_notice');
    return;
}

/**
 * crea tablas
 * @global <type> $wpdb
 * @global string $woo_bsale_db_version
 */
function woo_bsale_install($delete_old_table = false)
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    global $woo_bsale_db_version;
    add_option('woo_bsale_db_version', $woo_bsale_db_version);

    require_once dirname(__FILE__) . '/lib/Autoload.php';
    require_once dirname(__FILE__) . '/lib/db/OCDB.class.php';
    require_once dirname(__FILE__) . '/lib/wp/CronFunctions.class.php';

    $OCDB = new OCDB();

    $OCDB->setupTables();

    //cronjobs
    //cronjobs. Solo para test
   // $cron = new CronFunctions();
    //esta función solo debe ser llamada una vez
   // $cron->setup_cronjob_actions();

    //die(__FUNCTION__);
}

function woo_bsale_deactivate()
{
    /*require_once dirname(__FILE__) . '/lib/Autoload.php';
    require_once dirname(__FILE__) . '/lib/wp/CronFunctions.class.php';

    $cron = new CronFunctions();
    $cron->remove_cronjob_actions();*/
}

//hooks  para que se llamen las funciones al activar el plugin
register_activation_hook(__FILE__, 'woo_bsale_install');
register_deactivation_hook(__FILE__, 'woo_bsale_deactivate');

/**
 * para saber si hay que upgradear la db
 * @global <type> $jal_db_version
 */
function woo_bsale_update_db_check()
{
    global $woo_bsale_db_version;
    // if( get_site_option('woo_bsale_db_version') != $woo_bsale_db_version )
    //  {
    woo_bsale_install(true);
    //  }
}

//add_action('plugins_loaded', 'woo_bsale_update_db_check');
//actualización de plugin
add_action('upgrader_process_complete', 'woo_bsale_upgrade_function', 10, 2);

function woo_bsale_upgrade_function($upgrader_object, $options)
{
    $current_plugin_path_name = plugin_basename(__FILE__);

    if( !isset($options['action']) || !isset($options['type']) || !isset($options['plugins']) )
    {
        return;
    }

    if( $options['action'] == 'update' && $options['type'] == 'plugin' )
    {
        foreach( $options['plugins'] as $each_plugin )
        {
            if( $each_plugin == $current_plugin_path_name )
            {
                woo_bsale_install();
            }
        }
    }
}

/* * *
 * creo admin page para importar los miembros del partido
 *
 *
 */

add_action('admin_menu', 'woo_bsale_admin_menu');

/** Step 1. */
function woo_bsale_admin_menu()
{
    if( is_admin() )
    { // admin actions
        // Add the new admin menu and page and save the returned hook suffix
        $hook_suffix = add_management_page('Woocommerce Bsale', 'Woocommerce Bsale', 'publish_posts', 'woo_bsale-admin-menu', 'woo_bsale_admin_options');

        add_action('admin_init', 'bsale_register_mysettings');
    }
    else
    {
        // non-admin enqueues, actions, and filters
    }
}

function bsale_register_mysettings()
{
    $args_intval = array(
        'sanitize_callback' => 'intval',
    );
    $args_text_field = array(
        'sanitize_callback' => 'sanitize_text_field',
    );
    //register_setting(BSALE_OPTIONS_GROUP, 'bsale_categorias_id', 'bsale_sanitize');
    register_setting(BSALE_OPTIONS_GROUP, 'bsale_limit_stock', $args_intval);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_days_delete_logs', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_table_delete_logs', $args_intval);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_token', $args_text_field);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_sku_envio', $args_text_field);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_valor_iva', $args_text_field);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_pais', $args_text_field);

    //skus to skip
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_skus_prefix_to_skip', $args_text_field);

    //estados para nv
    //old register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_estados_nv');

    $args_nv = array(
        'type' => 'array',
        'sanitize_callback' => 'bsale_sanitize_order_statuses',
        'default' => NULL,
    );
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_estados_nv', $args_nv);

    $args_dte = array(
        'type' => 'array',
        'sanitize_callback' => 'bsale_sanitize_order_statuses',
        'default' => NULL,
    );
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_estados_dte', $args_dte);

    $args_nc = array(
        'type' => 'array',
        'sanitize_callback' => 'bsale_sanitize_order_statuses',
        'default' => NULL,
    );
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_estado_dte_cancelled', $args_nc);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_payments_nv', $args_text_field);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_stock_por_sucursal', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_stock_por_sucursal_place');
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_declare_sii', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_edit_facturac_data', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_only_prods_bsale', $args_intval);
    //checkout fields
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_add_campos_boleta', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_add_campos_factura', $args_intval);

    //inventario
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_create_prods', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_update_stock', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_update_price', $args_intval);
    //check stock after add product to cart
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_sync_stock_on_cart_add', $args_intval);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_incluir_despacho_en_dte', $args_intval);
    //register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_descontar_iva_precios', $args_intval);
    //register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_descontar_iva_envios', $args_intval);
    //anular dtes    
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_anular_dtes_cancelled', $args_intval);

    //emitir boleta segun comuna de facturacion?
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_comuna_sucursal_option', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_comuna_sucursal_listado');

    //enable integraciones
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_enable_inventario', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_enable_facturacion', $args_intval);

    //casa ma triz y lista de precios
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_casa_matriz_id', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_lista_precios_id', $args_intval);
    //seller id    
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_seller_id', $args_intval);

    //lp oferta
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_sync_precios_oferta', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_lista_precios_oferta_id', $args_intval);

    //despachos
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_despachar_boleta', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_despachar_factura', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_despachar_gd', $args_intval);

    //facturacion
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_send_email', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_send_sku', $args_intval);
    //atrib dinamicos
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dinam_attr_boleta', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dinam_attr_factura', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dinam_attr_nv', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dinam_attr_gd', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dinam_attr_nc', $args_intval);

    //customer notes d.trr
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dattr_boleta_customer_notes', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dattr_factura_customer_notes', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_dattr_nv_customer_notes', $args_intval);

    //id docs
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_boleta_id', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_factura_id', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_nv_id', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_gd_id', $args_intval);
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_nc_id', $args_intval);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_stock_por_sucursal_back', $args_text_field);

    $args = array(
        'type' => 'array',
        'sanitize_callback' => 'bsale_sanitize_bsale_sucursales_stock',
        'default' => NULL,
    );
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_sucursales_stock', $args);

    $args2 = array(
        'type' => 'array',
        'sanitize_callback' => 'bsale_sanitize_wc_bsale_product_types_stock',
        'default' => NULL,
    );
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_product_types_stock', $args2);

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_estados_enabled');
    //pagos wc->pagos bsale
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_pagos_bsale');

    //bsale redirects urls
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_redirect_url');

    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_shipping_bsale');
    register_setting(BSALE_OPTIONS_GROUP, 'wc_bsale_shipping_filter_stock');

    //recargo datos dede bsale si el token ha sido cambiado
    $old_token = get_option('wc_bsale_token');

    $new_token = isset($_REQUEST['wc_bsale_token']) ? $_REQUEST['wc_bsale_token'] : null;

    if( $new_token && $new_token !== $old_token )
    {
        //al llamar a este método aún no se actualizan los datos hasta que este termine de ejecutarse
        update_option('wc_bsale_token', $new_token);

        $config = new ConfigUtils();
        $config->reload_data_from_bsale('all', -1);
    }
}

function bsale_sanitize_bsale_sucursales_stock($input)
{
    if( !is_array($input) )
    {
        return '';
    }
    //print_r($input);

    $str = implode(',', $input);
    //die($str); 
    return $str;
}

function bsale_sanitize_order_statuses($input)
{
    if( !is_array($input) )
    {
        return '';
    }
    //print_r($input);

    $str = implode(',', $input);
    //saco espacios en blanco
    $str = str_replace(' ', '', $str);
    //die($str); 
    return $str;
}

function bsale_sanitize_wc_bsale_product_types_stock($input)
{
    if( !is_array($input) )
    {
        return '';
    }

    $str = implode(',', $input);
    //die($str); 
    return $str;
}

/**
 * sanitixa valores antes de guardarlos en option
 * @param type $input
 * @return type
 */
function bsale_sanitize($input)
{
    $new_input = array();
    $arraux = explode(',', $input);

    //solo acepto un array de numeros 
    foreach( $arraux as $a )
    {
        $n = trim($a);
        if( empty($n) )
        {
            continue;
        }
        $n = intval($n);
        if( $n == 0 )
        {
            continue;
        }

        $new_input[] = $n;
    }
    //saco duplicados
    $new_input = array_unique($new_input);

    $str = implode(',', $new_input);
    return $str;
}

/** Step 3. */
function woo_bsale_admin_options()
{
    //manage_options
    if( !current_user_can('publish_posts') )
    {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
//    require_once dirname(__FILE__) . '/lib/wp/WpBsale.class.php';


    if( isset($_GET['settings-updated']) )
    {
        $msg = '<div id="message" class="updated fade"><p><strong>Los cambios se han guardado correctamente.</strong></p></div>';
    }
    else
    {
        $msg = '';
    }

    require_once dirname(__FILE__) . '/wc_tabs/css.php';

    $tab_to_display = isset($_GET['bsale_tab']) ? $_GET['bsale_tab'] : 'bsale_config';
    ?>

    <div class="wrap bsale_wooc">
        <ul class="bsale_tabs">
            <li class="<?php echo($tab_to_display === 'bsale_config' ? 'sel_tab' : '' ); ?>"><a href="tools.php?page=woo_bsale-admin-menu&bsale_tab=bsale_config">Configurar</a></li> 
            <?php if( Funciones::is_enabled_integ_inventario() ): ?>
                <li class="<?php echo($tab_to_display === 'bsale_sync' ? 'sel_tab' : '' ); ?>"><a href="tools.php?page=woo_bsale-admin-menu&bsale_tab=bsale_sync">Sincronizar productos</a></li>
            <?php endif; ?>
            <?php if( $tab_to_display === 'test' ): ?>
                <li class="<?php echo($tab_to_display === 'test' ? 'sel_tab' : '' ); ?>"><a href="tools.php?page=woo_bsale-admin-menu&bsale_tab=test">Test</a></li>
            <?php endif; ?>
            <!--            <li><a href="tools.php?page=woo_bsale-admin-menu&bsale_tab=bsale_help">Ayuda</a></li>-->
        </ul>
        <?php echo $msg; ?>
        <?php
        if( empty($tab_to_display) || $tab_to_display === 'bsale_config' )
        {
            $file = dirname(__FILE__) . '/wc_tabs/config.php';
        }
        elseif( $tab_to_display === 'bsale_sync' )
        {
            $file = dirname(__FILE__) . '/wc_tabs/sync_prods.php';
        }
        elseif( $tab_to_display === 'bsale_xxx' )
        {
            $file = dirname(__FILE__) . '/wc_tabs/bsale_help.php';
        }
        elseif( $tab_to_display === 'test' )
        {
            $file = dirname(__FILE__) . '/wc_tabs/test_page.php';
        }

        if( !empty($file) )
        {
            require_once($file );
        }
        ?>
    </div>

    <?php
}

/**
 * carga scripts para validar
 * @param type $hook
 */
function woo_bsale_my_load_scripts()
{
    global $wp;
    $include = /* is_cart() || */ is_checkout();

    if( Funciones::is_enabled_integ_facturacion() == true && $include )
    {
        wp_enqueue_script('jquery');

        if( Funciones::get_pais() === 'CL' )
        {
            wp_enqueue_script('jquery-rut', plugins_url('js/jquery.Rut.js', __FILE__), array( 'jquery' ), '0.1.1');
            wp_enqueue_script('valida-rut', plugins_url('js/valida_rut.js', __FILE__), array( 'jquery' ), '0.5.1');
            //wp_enqueue_script('datable', plugins_url('js/datable.js', __FILE__), array( 'jquery' ), '0.5');
        }
        elseif( Funciones::get_pais() === 'PE' )
        {
            wp_enqueue_script('valida-dni', plugins_url('js/valida_dni.js', __FILE__), array( 'jquery' ), '0.4.1');
        }
    }
    if( Funciones::is_enabled_integ_inventario() && Funciones::is_mostrar_stock_sucursal() )
    {
        //css con diseño para stock sucursales in page prod
        wp_enqueue_style('bsale-stock', plugins_url('css/bsale_front.css', __FILE__), false, '1.2.1', 'all');
        wp_enqueue_script('bsale-front', plugins_url('js/bsale_front.js', __FILE__), array( 'jquery' ), '0.8.1');
    }

    //wp_register_style('my_css', plugins_url('style.css', __FILE__), false, $my_css_ver);
    // wp_enqueue_style('my_css');
}

add_action('wp_enqueue_scripts', 'woo_bsale_my_load_scripts');

function woo_bsale_my_load_scripts_admin($hook)
{
    wp_enqueue_script('jquery-bsale_admin', plugins_url('js/bsale_admin.js', __FILE__), array( 'jquery' ), '1.8.4', true);
}

add_action('admin_enqueue_scripts', 'woo_bsale_my_load_scripts_admin');

/* add_filter('woocommerce_billing_fields', 'bsale_require_checkout_fields', 999);

  function bsale_require_checkout_fields($fields)
  {
  //echo("loading...");
  $fields['billing_company']['required'] = true;
  if( isset($fields['billing_ruc']['required']) )
  {
  $fields['billing_ruc']['required'] = true;
  }
  return $fields;
  } */



require_once dirname(__FILE__) . '/lib/Autoload.php';

//use woocommerce_bsalev2\lib\wp\BsaleInit;
//use woocommerce_bsalev2\lib\wp\AjaxFunctions;
//init
$action_init = new BsaleInit();
add_action('init', array( $action_init, 'wooc_bsale_action_init' ));
//init actions
$ajax_f = new AjaxFunctions();
$ajax_f->setup_actions();



