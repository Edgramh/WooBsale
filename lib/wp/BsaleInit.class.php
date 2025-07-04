<?php

//namespace woocommerce_bsalev2\lib\wp;
require_once dirname(__FILE__) . '/../Autoload.php';

//use woocommerce_bsalev2\lib\wp\WpDataBsale;

/**
 * gatilla action de inti de wp
 *
 * @author Lex
 */
class BsaleInit
{

    /**
     * llamada por el evento "init" de wp
     */
    public function wooc_bsale_action_init()
    {
        if( !class_exists('WpDataBsale') )
        {
            require_once dirname(__FILE__) . '/WpDataBsale.class.php';
        }
        $data = new WpDataBsale();

        //mostrar puntos de Bsale en el dashboard de Wc?
        if( defined('WC_MOSTRAR_PUNTOS_BSALE_CUSTOMER') && WC_MOSTRAR_PUNTOS_BSALE_CUSTOMER == true )
        {
            //echo('woocommerce_before_account_navigation');
            add_action('woocommerce_account_navigation', 'WpUtils::display_customer_points', 10, 0);
        }

        //integracion de facturacion (aunque diga "inventario")
        if( Funciones::is_enabled_integ_facturacion() == true )
        {
            //agrego campos de boleta y factura a thank you page
            add_action('woocommerce_thankyou', 'WpUtils::add_thankyou_page_custom_text');

            //action para generar dte desde las acciones al editar pedido
            // add our own item to the order actions meta box
            add_action('woocommerce_order_actions', 'WpUtils::add_order_meta_box_actions', 10, 2);
            // process the custom order meta box order action
            //crear bol o fact
            add_action('woocommerce_order_action_bsale_crear_dte_pedido_action', 'WpUtils::order_action_crear_dte_bsale');
            //crear nc
            add_action('woocommerce_order_action_bsale_crear_nc_pedido_action', 'WpUtils::order_action_crear_nc_bsale');
            //quitar dtes emitidos
            add_action('woocommerce_order_action_bsale_quitar_dte_pedido_action', 'WpUtils::bsale_quitar_dte_pedido_action');
            add_action('woocommerce_order_action_bsale_quitar_nv_pedido_action', 'WpUtils::bsale_quitar_nv_pedido_action');
            add_action('woocommerce_order_action_bsale_quitar_nc_pedido_action', 'WpUtils::bsale_quitar_nc_pedido_action');

            //bulk action para generar dtes desde listado de pedidos
            add_filter('bulk_actions-edit-shop_order', 'WpUtils::order_bulk_actions', 20, 1);
            add_filter('handle_bulk_actions-edit-shop_order', 'WpUtils::order_bulk_action_bsale', 10, 3);

            $is_add_campos_checkout_boleta = Funciones::is_add_campos_checkout_boleta();
            $is_add_campos_checkout_factura = Funciones::is_add_campos_checkout_factura();
            $is_editar_campos_fact = Funciones::is_edit_facturacion_data();

            if( $is_add_campos_checkout_boleta && !$is_add_campos_checkout_factura )
            {
                add_filter('woocommerce_billing_fields', 'WpUtils::add_campos_boleta', 9999998, 1);
            }
            elseif( $is_add_campos_checkout_factura )
            {
                add_filter('woocommerce_billing_fields', 'WpUtils::add_campos_factura', 9999998, 1);
            }

            //valido datos requeridos
            if( $is_add_campos_checkout_boleta || $is_add_campos_checkout_factura )
            {
                add_action('woocommerce_after_checkout_validation', 'WpUtils::validate_checkout_fields', 9999, 2);

                //guardo campos en order
                add_action('woocommerce_checkout_update_order_meta', 'WpUtils::save_campos_checkout_in_order', 9999, 1);

                //muestro campos checkout in order edit
                add_action('woocommerce_admin_order_data_after_billing_address', 'WpUtils::display_campos_checkout_in_order', 90, 1);
            }

            //editar campos de facturacion en order edit?
            if( $is_editar_campos_fact )
            {
                //agregar boxes con html
                add_action('add_meta_boxes', 'WpUtils::edit_campos_facturacion_custom_box');
                //save meta data de datos fact
                add_action('save_post', 'WpUtils::edit_campos_facturacion_save_data');
            }

            //muestro campos en emails,versiones nuevas de woocomerce
            add_filter('woocommerce_email_order_meta', 'WpUtils::display_campos_checkout_in_email2', 90, 3);

            //versiones antiguas de woocommerce
            //muestro campos en emails
            add_filter('woocommerce_email_order_meta_keys', 'WpUtils::display_campos_checkout_in_email', 90, 1);

            //hooks para los estados en que se emiten dtes
            $estados = Funciones::get_estados_enabled_arr();

            foreach( $estados as $estado )
            {
                add_action('woocommerce_order_status_' . $estado, 'WpBsale::crear_dte_bsale', 10, 1);
            }

            //anular dtes on order cancelled?
            if( Funciones::is_anular_dtes_on_order_cancelled() )
            {
                $wc_nc = new WpBsaleNotaCredito();
                $estado_cancelled_arr = Funciones::get_estado_dte_cancelled();
                foreach( $estado_cancelled_arr as $estado_cancelled )
                {
                    add_action('woocommerce_order_status_' . $estado_cancelled, array( $wc_nc, 'anular_dte_bsale' ), 10, 1); //
                }
            }

            //mostrar solo medios de envio con stock
            if( Funciones::is_enabled_shipping_filter_stock() )
            {
                add_filter('woocommerce_package_rates', array( $data, 'filter_shipping_sucursales' ), 20, 2);
                add_action('woocommerce_check_cart_items', array( $data, 'required_chosen_shipping_methods' ), 999);
                //validar que hay stock de todos los productos, después de presionar "finalizar compra"
                //add_action('woocommerce_after_checkout_validation', 'WpUtils::validar_stock_checkout_sucursales', 10, 2);
                //
                // add_action( 'woocommerce_proceed_to_checkout', array($data, 'disable_checkout_button_no_shipping'), 1 );
            }
            //instrucciones en el correo
            // add_action('woocommerce_email_before_order_table', 'WpBsale::add_order_email_instructions', 10, 2);
            //cols en admin de pedidos
            add_filter('manage_edit-shop_order_columns', 'WpUtils::bsale_add_factura_number_column', 999);
            add_action('manage_shop_order_posts_custom_column', 'WpUtils::bsale_add_factura_number_column_data', 2);

            //seccion e edit pedido con datos de facturacion
            //add post meta de enviam a order details
            add_action('woocommerce_admin_order_data_after_order_details', 'WpUtils::order_meta_add_info',998);

            //prefill campos checkout
            add_action('woocommerce_thankyou', 'WpUtils::save_billing_data', 10, 1);
            add_filter('woocommerce_billing_fields', 'WpUtils::prefill_billing_fields', 99999);

            //agrego filtros para ver pedidos con boleta, factura, nv y nc
            //agregar filtro: mostrar prods que no estan en bsale
            add_action('restrict_manage_posts', 'WpUtils::bsale_orders_filter');
            add_action('pre_get_posts', 'WpUtils::apply_my_custom_orders_filters');

            //historial de dtes para pedido
            add_action('add_meta_boxes', 'WpUtils::order_edit_bsale_meta_box');
        }

        if( Funciones::is_enabled_integ_inventario() == true )
        {
            $is_use_rest = defined('BSALE_USE_REST_API') ? BSALE_USE_REST_API : false;

            if( $is_use_rest )
            {
                //rest api init WPBsaleRest class
                require_once dirname(__FILE__) . '/WPBsaleRest.class.php';

                add_action('rest_api_init', 'bsale_codifica_init_api_rest');
                $rest = new WPBsaleRest();
            }

            //agregar filtro: mostrar prods que no estan en bsale
            add_action('restrict_manage_posts', 'WpUtils::bsale_product_exists_filter');
            add_action('pre_get_posts', 'WpUtils::apply_my_custom_product_filters');

            //mostrar stock al lado de precio producto
            //add_action('woocommerce_get_price_html', array($data, 'bsale_display_stock_product_page'));
            //mostrar stock sucursal en pag de producto
            if( Funciones::is_mostrar_stock_sucursal() )
            {
                $action_wc = Funciones::get_mostrar_stock_sucursal_place();

                if( !empty($action_wc) )
                {
                    add_action($action_wc, 'WpUtils::wc_bsale_stock_por_sucursal', 15);
                }
            }
            if( Funciones::is_mostrar_stock_sucursal_backend() )
            {
                add_action('add_meta_boxes', 'WpUtils::stock_sucursales_product_edit');
            }

            //meta box de bsale con botones para sync e info de stock
            add_action('add_meta_boxes', 'WpUtils::product_edit_bsale_meta_box_side');
            add_action('add_meta_boxes', 'WpUtils::product_edit_bsale_meta_box');

            //autoupdate de precios y stock on save product o variation
            // add_action('woocommerce_update_product', 'WpBsale::product_update', 10, 1);
            //add_action('woocommerce_update_product_variation', array( $data, 'product_variation_update' ), 10, 1);
            //cuando se cambia sku, se revisa si existe en Bsale
            add_action('updated_post_meta', array( $data, 'product_update' ), 10, 4);

            // add_action('woocommerce_save_product_variation', 'WpBsale::product_variation_save', 10, 2);
            //mensaje en columnas de producto
            // add_filter('manage_edit-product_columns', 'WpBsale::bsale_product_column', 999);
            //muestra infor en pantalla de post edit
            add_action('admin_notices', array( $data, 'post_edit_info' ));

            //link para sync prodcut from Bsale 
            // //movido a meta box propia
            // add_action('post_submitbox_misc_actions', 'WpUtils::post_edit_bsale_block');
            //muestra mensaje de skus con errors arriba del nombre del producto           
            add_action('manage_product_posts_custom_column', 'WpUtils::bsale_product_column_data', 2, 2);

            if( Funciones::is_validate_stock_on_add_to_cart() && Funciones::is_update_product_stock() )
            {
                //sync stock de product cada vez que es agregado al carro
                add_action('woocommerce_add_to_cart', 'WpUtils::check_stock_available_bsale', 10, 6);
            }

            //bulk action in products admin
            add_filter('bulk_actions-edit-product', 'WpUtils::product_bulk_actions_add_menu_item');
            add_filter('handle_bulk_actions-edit-product', 'WpUtils::product_bulk_actions_function', 10, 3);
            add_action('admin_notices', 'WpUtils::product_bulk_actions_add_notice');
        }
    }
}
