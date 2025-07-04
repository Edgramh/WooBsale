<?php
//namespace woocommerce_bsalev2\lib\wp;

require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of WpUtils
 *
 * @author Lex
 */
class WpUtils
{

    function __construct()
    {
        
    }

    public function setup()
    {
        
    }

    /**
     * agrega en admin de productos action para sync products selecteds
     */
    public static function product_bulk_actions_add_menu_item($bulk_actions)
    {
        $action = SYNC_TO_BSALE_BULK_ACTION_NAME;
        $bulk_actions[$action] = __('Sincronizar con Bsale', 'txtdomain');
        return $bulk_actions;
    }

    /**
     * funcion que sincroniza los productos selected in admin product 
     * @param type $redirect_url
     * @param type $action
     * @param type $post_ids
     * @return type
     */
    public static function product_bulk_actions_function($redirect_url, $action_admin, $post_ids)
    {
        $action = SYNC_TO_BSALE_BULK_ACTION_NAME;
        $data = new WpDataBsale();

        // die($action_admin);
        if( $action_admin !== $action )
        {
            return $redirect_url;
        }

        foreach( $post_ids as $post_id )
        {
            //sincronizo datos desded bsale para cada producto selected
            $result = $data->product_sync_from_bsale($post_id);
        }
        $redirect_url = add_query_arg($action, count($post_ids), $redirect_url);

        return $redirect_url;
    }

    public static function product_bulk_actions_add_notice()
    {
        $action = SYNC_TO_BSALE_BULK_ACTION_NAME;

        if( !empty($_REQUEST[$action]) )
        {
            $num_changed = (int) $_REQUEST[$action];
            printf('<div id="message" class="updated notice is-dismissable"><p>' . __('%d productos han sido sincronizados con los datos de Bsale.', 'txtdomain') . '</p></div>', $num_changed);
        }
    }

    /**
     * llamada cada vez que se agrega un producto al carro
     * @param type $cart_item_key
     * @param type $product_id
     * @param type $quantity
     * @param type $variation_id
     * @param type $variation
     * @param type $cart_item_data
     */
    public static function check_stock_available_bsale($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($cart_item_data, "check_stock_available_bsale($cart_item_key, $product_id, $quantity, $variation_id");
            Funciones::print_r_html($variation);
        }

        //obtengo datos de varian a partir de sku
        $vars = new VariantesProductoBsale();
        $bsale = new Bsale();
        $sku = null; //sku del prod o variacion agregado al cart
        //si hay variacion, actualizo el stock de esta desde bsale
        if( $variation_id > 0 )
        {
            $product_variation = wc_get_product($variation_id);

            if( !$product_variation )
            {
                //no existe variacion
                return;
            }
            $sku = $product_variation->get_sku();
        }
        //actualizo stock del producto
        elseif( $product_id > 0 )
        {
            $product = wc_get_product($product_id);

            if( !$product )
            {
                //no existe producto
                return;
            }
            $sku = $product->get_sku();

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("producto sku= $sku, cantidad = $quantity");
            }
        }

        if( empty($sku) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("producto/variacion $product_id $variation_id no tiene sku. No se busca stock en bsale");
            }
            return;
        }

        $variacion_resp = $vars->get_variacion_by_sku($sku);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($variacion_resp, "get_variacion_by_sku($sku)");
        }

        if( !isset($variacion_resp['id']) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($variacion_resp, "variacion sku='$sku' no existe");
            }
            return;
        }
        $variacion = $variacion_resp;

        //obtengo datos del producto
        $producto_id = isset($variacion['product']['id']) ? $variacion['product']['id'] : 0;

        $prod_bsale_obj = new ProductoBsale();
        $producto = $prod_bsale_obj->get_producto($producto_id);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($producto, "Producto de la variacion");
        }

        if( !isset($producto['id']) )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($producto, "Producto de la variacion no existe");
            }
            return;
        }

        //con estos datos, puedo sinc stock de este sku
        $variante_id = $variacion_resp['id'];
        //proceso variacion
        $post_vars_array = array( 'cpnId' => $variante_id,
            'resource' => "/v2/variants/$variante_id.json", 'resourceId' => $variante_id,
            'topic' => 'variant', 'action' => 'put',
            'send' => '1553289004', );

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($post_vars_array, "datos para do_variant()");
        }

        $bsale->do_variant($post_vars_array, true);
    }

    public static function add_thankyou_page_custom_text($order_id)
    {
        $html = '<div class="thank_you_bsale_fields" style="padding-left: 20px; display: block; clear: both;margin-bottom: 60px;">'
                . '<p><strong>Datos de facturación</strong></p>';

        //datos de facturacion
        $billing_tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);
        //$billing_tipo_documento = empty($billing_tipo_documento) ? get_post_meta($order_id, '_billing_tipo_documento', true) : $billing_tipo_documento;

        $billing_rut = get_post_meta($order_id, '_billing_rut', true);
        // $billing_rut = empty($billing_rut) ? get_post_meta($order_id, '_billing_rut', true) : $billing_rut;

        $billing_company = get_post_meta($order_id, '_billing_company', true);
        // $billing_company = empty($billing_company) ? get_post_meta($order_id, '_billing_company', true) : $billing_company;

        $billing_ruc = get_post_meta($order_id, '_billing_ruc', true);
        // $billing_ruc = empty($billing_ruc) ? get_post_meta($order_id, '_billing_ruc', true) : $billing_ruc;

        $billing_giro = get_post_meta($order_id, '_billing_giro', true);
        // $billing_giro = empty($billing_giro) ? get_post_meta($order_id, '_billing_giro', true) : $billing_giro;

        $billing_direccion_fiscal = get_post_meta($order_id, '_billing_direccion_fiscal', true);

        $sin_rut = Funciones::get_value('RUT_SIN_RUT', null);

        if( Funciones::get_pais() === 'CL' )
        {
            $html .= '<ul class="bsale_list_datos_fact" style="list-style: none;">';
            if( $billing_rut !== $sin_rut )
            {
                $html .= '<li class="bsale_fact_field rut"><strong>' . __('RUT comprador') . ': ' . $billing_rut . '</strong></li>';
            }

            $html .= '<li class="bsale_fact_field selector">' . __('Documento tributario') . ': <strong>' . $billing_tipo_documento . '</strong></li>';

            if( $billing_tipo_documento === 'factura' )
            {
                $html .= '<li class="bsale_fact_field empresa">' . __('Empresa') . ': <strong>' . $billing_company . '</strong></li>';
                $html .= '<li class="bsale_fact_field giro">' . __('Giro empresa') . ': <strong>' . $billing_giro . '</strong></li>';
            }
            $html .= '</ul>';
        }
        elseif( Funciones::get_pais() === 'PE' )
        {
            $html .= '<ul class="bsale_list_datos_fact">';
            $html .= '<li class="bsale_fact_field selector">' . __('Documento tributario') . ': <strong>' . $billing_tipo_documento . '</strong></li>';
            $html .= '<li class="bsale_fact_field rut">' . __('DNI/CE') . ': <strong>' . $billing_rut . '</strong></li>';

            if( $billing_tipo_documento === 'factura' )
            {
                $html .= '<li class="bsale_fact_field empresa">' . __('Empresa') . ': <strong> ' . $billing_company . '</strong></li>';
                $html .= '<li class="bsale_fact_field ruc">' . __('RUC') . ': <strong> ' . $billing_ruc . '</strong></li>';
                $html .= '<li class="bsale_fact_field giro">' . __('Giro empresa') . ': <strong> ' . $billing_giro . '</strong></li>';
                $html .= '<li class="bsale_fact_field df">' . __('Dirección fiscal') . ': <strong> ' . $billing_direccion_fiscal . '</strong></li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';
        echo $html;
    }

    /**
     * agrega action "geenrar dte bsale" a los pedidos
     * y menu item para quitar links a dtes generados
     * @param type $actions
     */
    public static function add_order_meta_box_actions($actions, $order=null)
    {
        if( !$order )
        {
            return $actions;
        }

        //solo se agrega si el pedido está en uno de los estados en que se puede emitir
        //bol/fact o nv
        $estado_orden = $order->get_status();
        $order_id = $order->get_id();
        //tipo dte que se debe emitior
        $billing_tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);
        $billing_tipo_documento = empty($billing_tipo_documento) ? 'boleta' : $billing_tipo_documento;

        //se ha emitido el dte?
        $folio_dte = get_post_meta($order_id, "bsale_docto_folio_" . $billing_tipo_documento, true);
        $folio_nv = get_post_meta($order_id, "bsale_docto_folio_nv", true);
        $folio_nc = get_post_meta($order_id, "bsale_docto_folio_nc", true);

        // die("tipo= $billing_tipo_documento folio: $folio_dte en 'bsale_docto_folio_$billing_tipo_documento'");
        //ya se ha emitido nv para esta orden
        if( !empty($folio_nv) )
        {
            //agrego links para quitar nv
            $actions['bsale_quitar_nv_pedido_action'] = "[X] Bsale: quitar Nota de venta";
        }
        //ya se ha emitido nc para esta orden
        if( !empty($folio_nc) )
        {
            //agrego links para quitar nv
            $actions['bsale_quitar_nc_pedido_action'] = "[X] Bsale: quitar Nota de crédito";
        }

        //ya se ha emitido dte para esta orden
        if( !empty($folio_dte) )
        {
            $dte = ucfirst($billing_tipo_documento);
            //agrego links para quitar dte
            $actions['bsale_quitar_dte_pedido_action'] = "[X] Bsale: quitar $dte";
        }

        //estado para emit
        //estados en los que se debe emitir fact o boleta:
        $estados_arr = Funciones::get_estados_dte_arr();
        $is_estado_para_dte = in_array($estado_orden, $estados_arr);

        //estado para emitir NV
        //estados en los que se debe emitir nv:
        $estados_arr_nv = Funciones::get_estados_nv_arr();
        $is_estado_para_nv = in_array($estado_orden, $estados_arr_nv);

        $estado_arr_nc = Funciones::get_estado_dte_cancelled();
        $is_estado_para_nc = in_array($estado_orden, $estado_arr_nc);

        //agrego menu item
        if( $is_estado_para_dte && empty($folio_dte) )
        {
            $actions['bsale_crear_dte_pedido_action'] = ">> Bsale: emitir $billing_tipo_documento";
        }
        if( $is_estado_para_nv && empty($folio_nv) )
        {
            $actions['bsale_crear_dte_pedido_action'] = ">> Bsale: emitir Nota de venta";
        }
        if( $is_estado_para_nc && empty($folio_nc) )
        {
            $actions['bsale_crear_nc_pedido_action'] = ">> Bsale: emitir Nota de crédito";
        }

        return $actions;
    }

    public static function order_action_crear_dte_bsale($order)
    {
        $order_id = $order->get_id();

        $wpbsale = new WpBsale();

        $res = $wpbsale->crear_dte_bsale($order_id);
    }

    /**
     * dede menu actions del pedido, permite emitir nc
     * @param type $order
     */
    public static function order_action_crear_nc_bsale($order)
    {
        $order_id = $order->get_id();

        $wpbsale = new WpBsaleNotaCredito();

        $res = $wpbsale->anular_dte_bsale($order_id);
    }

    /**
     * quita links a bol/fact en el pedido wc
     * @param type $order
     */
    public static function bsale_quitar_dte_pedido_action($order)
    {
        $order_id = $order->get_id();

        //metas de dtes de bol/fact a borrar
        $metas_arr = array( 'bsale_docto_folio', 'bsale_docto_tipo', 'bsale_docto_url',
            'bsale_docto_folio_boleta', 'bsale_docto_id_boleta', 'bsale_docto_id_boleta_url',
            'bsale_docto_folio_factura', 'bsale_docto_id_factura', 'bsale_docto_id_factura_url' );

        foreach( $metas_arr as $key )
        {
            delete_post_meta($order_id, $key);
        }
    }

    /**
     * quita links a nota de venta en el pedido wc
     * @param type $order
     */
    public static function bsale_quitar_nv_pedido_action($order)
    {
        $order_id = $order->get_id();

        //metas de dtes de bol/fact a borrar
        $metas_arr = array( 'bsale_docto_folio_nv', 'bsale_docto_id_nv', 'bsale_docto_id_nv_url' );

        foreach( $metas_arr as $key )
        {
            delete_post_meta($order_id, $key);
        }
    }

    /**
     * quita links a NC en el pedido wc
     * @param type $order
     */
    public static function bsale_quitar_nc_pedido_action($order)
    {
        $order_id = $order->get_id();

        //metas de dtes de bol/fact a borrar
        $metas_arr = array( 'bsale_docto_folio_nc', 'bsale_docto_id_nc', 'bsale_docto_id_nc_url' );

        foreach( $metas_arr as $key )
        {
            delete_post_meta($order_id, $key);
        }
    }

    /**
     * BULK action para listado de pedidos wc, para crear dtes
     * sin cambiar estado 
     * @param array $actions
     * @return string
     */
    public static function order_bulk_actions($actions)
    {
        $actions['bsale_bulk_dte_pedido_action'] = 'Bsale: emitir documento';
        return $actions;
    }

    public static function order_bulk_action_bsale($redirect_to, $action, $post_ids)
    {
        if( $action !== 'bsale_bulk_dte_pedido_action' )
        {
            return $redirect_to; // Exit
        }

        $wpbsale = new WpBsale();
        $processed_ids = array();

        foreach( $post_ids as $order_id )
        {
            $res = $wpbsale->crear_dte_bsale($order_id);

            $processed_ids[] = $order_id;
        }

        $redirect_to = add_query_arg(array(
            // 'write_downloads' => '1',
            'processed_count' => count($processed_ids),
            'processed_ids' => implode(',', $processed_ids),
                ), $redirect_to);

        return $redirect_to;
    }

    public function get_cart_items()
    {
        
    }

    public static function bsale_orders_filter($post_type)
    {
        // Check this is the products screen
        if( $post_type !== 'shop_order' )
        {
            return;
        }

        $selected = isset($_GET['bsale_order_filter']) ? $_GET['bsale_order_filter'] : '';

        $arr = array(
            'b' => 'Ver pedidos con boleta emitida',
            'f' => 'Ver pedidos con factura emitida',
            'nv' => 'Ver pedidos con nota de venta emitida',
            'nc' => 'Ver pedidos con nota de crédito emitida',
        );

        $html = '<select name="bsale_order_filter" id="bsale_order_filter" style="border: 2px solid #ff5c1a;">';
        $html .= '<option value>Filtro Bsale para pedidos</option>';

        foreach( $arr as $key => $value )
        {
            $sel = selected($selected, $key, false);
            $html .= "<option value='$key' $sel title='$value'>$value</option>";
        }
        $html .= "</select>";

        echo $html;
    }

    public static function apply_my_custom_orders_filters($query)
    {

        global $pagenow;

// Ensure it is an edit.php admin page, the filter exists and has a value, and that it's the products page
        if( $query->is_admin && $pagenow == 'edit.php' && isset($_GET['bsale_order_filter']) &&
                $_GET['bsale_order_filter'] !== '' && $_GET['post_type'] === 'shop_order' )
        {

            $selected = $_GET['bsale_order_filter'];

            //segun el docto, es el campo meta a consultar
            $meta_key = '';

            switch( $selected )
            {
                case 'b':
                    $meta_key = 'bsale_docto_folio_boleta';
                    break;
                case 'f':
                    $meta_key = 'bsale_docto_folio_factura';
                    break;
                case 'nv':
                    $meta_key = 'bsale_docto_folio_nv';
                    break;
                case 'nc':
                    $meta_key = 'bsale_docto_folio_nc';
                    break;

                default:
                    break;
            }

            // Create meta query array and add to WP_Query
            $meta_key_query = array(
                array(
                    'key' => $meta_key,
                    'value' => array( '' ),
                    'compare' => 'NOT IN',
                )
            );
            $query->set('meta_query', $meta_key_query);
        }
    }

    /**
     * agrega en el admin de productos un filtro de prods que no están en bsale
     */
    public static function bsale_product_exists_filter($post_type)
    {
        // Check this is the products screen
        if( $post_type !== 'product' )
        {
            return;
        }

        $value1 = '';
// Check if filter has been applied already so we can adjust the input element accordingly

        if( isset($_GET['bsale_prod_filter']) )
        {
            switch( $_GET['bsale_prod_filter'] )
            {

                // We will add the "selected" attribute to the appropriate <option> if the filter has already been applied
                case 1:
                    $value1 = ' selected';
                    break;

                default:
                    break;
            }
        }

        // Add your filter input here. Make sure the input name matches the $_GET value you are checking above.
        echo '<select name="bsale_prod_filter" id="bsale_prod_filter" style="border: 2px solid #ff5c1a;">';
        echo '<option value>Filtro productos Bsale</option>';
        echo '<option value="1"' . $value1 . ' title="Muestra los productos de woocommerce cuyo sku no pertenece a ningún producto de Bsale">Ver productos no creados en Bsale</option>';
        echo '</select>';
    }

    public static function apply_my_custom_product_filters($query)
    {

        global $pagenow;

// Ensure it is an edit.php admin page, the filter exists and has a value, and that it's the products page
        if( $query->is_admin && $pagenow == 'edit.php' && isset($_GET['bsale_prod_filter']) &&
                $_GET['bsale_prod_filter'] !== '' && $_GET['post_type'] === 'product' )
        {

            // Create meta query array and add to WP_Query
            $meta_key_query = array(
                array(
                    'key' => 'bsale_missed',
                    'value' => esc_attr($_GET['bsale_prod_filter']),
                )
            );
            $query->set('meta_query', $meta_key_query);
        }
    }

    /**
     * agrego metabox para editr datos de fact in order edit
     */
    public static function edit_campos_facturacion_custom_box()
    {
        $me = new WpUtils();

        add_meta_box(
                'bsale_edit_fact_id', // Unique ID
                'Bsale: Facturación', // Box title
                // 'bsale_edit_fact_custom_box_html',
                array( $me, 'bsale_edit_fact_custom_box_html' ), // Content callback, must be of type callable
                'shop_order', // Post type
                'side', //al lado
                'high'
        );
    }

    /**
     * html del metabox de self::edit_campos_facturacion_custom_box()
     */
    public function bsale_edit_fact_custom_box_html($post)
    {
        //die('edit_fact_custom_box_html');
        //datos de fact de este pedido
        $order_id = $post->ID;

        if( empty($order_id) )
        {
            return;
        }

        $is_add_campos_checkout_boleta = Funciones::is_add_campos_checkout_boleta();
        $is_add_campos_checkout_factura = Funciones::is_add_campos_checkout_factura();

        //datos meta de facturacion
        $billing_tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);
        // $billing_tipo_documento = empty($billing_tipo_documento) ? get_post_meta($order_id, 'billing_tipo_documento', true) : $billing_tipo_documento;

        $billing_rut = get_post_meta($order_id, '_billing_rut', true);
        //$billing_rut = empty($billing_rut) ? get_post_meta($order_id, 'billing_rut', true) : $billing_rut;

        $billing_company = get_post_meta($order_id, '_billing_company', true);
        //  $billing_company = empty($billing_company) ? get_post_meta($order_id, 'billing_company', true) : $billing_company;
        //peru
        $billing_ruc = get_post_meta($order_id, '_billing_ruc', true);
        //$billing_ruc = empty($billing_ruc) ? get_post_meta($order_id, 'billing_ruc', true) : $billing_ruc;

        $billing_giro = get_post_meta($order_id, '_billing_giro', true);
        //  $billing_giro = empty($billing_giro) ? get_post_meta($order_id, 'billing_giro', true) : $billing_giro;

        $billing_direccion_fiscal = get_post_meta($order_id, '_billing_direccion_fiscal', true);
        //$billing_direccion_fiscal = empty($billing_direccion_fiscal) ? get_post_meta($order_id, 'billing_direccion_fiscal', true) : $billing_direccion_fiscal;


        if( Funciones::get_pais() === 'CL' )
        {
            if( $is_add_campos_checkout_factura )
            {
                ?>
                <p>Puede cambiar los datos de facturación para este pedido. Debe hacerlo antes de que el pedido cambie al estado
                    en que se emite la boleta o factura.</p>
                <ul class="bsale_edit_datos_fact" style="color: #d74f04;text-transform: uppercase;">
                    <li>
                        <label for="billing_tipo_documento"><strong>¿Boleta o factura?</strong></label>
                        <select name="billing_tipo_documento" id="billing_tipo_documento" class="postbox" style="margin-bottom: 0;">
                            <option value="">--seleccione--</option>
                            <option value="boleta" <?php selected($billing_tipo_documento, 'boleta'); ?>>Boleta</option>
                            <option value="factura" <?php selected($billing_tipo_documento, 'factura'); ?>>Factura</option>
                        </select>
                    </li>
                    <li>
                        <label for="billing_rut"><strong>Rut para boleta/factura</strong></label>
                        <input type="text" name="billing_rut" id="billing_rut" class="postbox"  style="margin-bottom: 0;" value="<?php echo $billing_rut; ?>" />
                    </li>
                    <li>
                        <label for="billing_company"><strong>Nombre de la empresa (solo factura)</strong></label>
                        <input type="text" name="billing_company" id="billing_company" class="postbox"  style="margin-bottom: 0;" value="<?php echo $billing_company; ?>" />
                    </li>
                    <li>
                        <label for="billing_giro"><strong>Giro de la empresa (solo factura)</strong></label>
                        <input type="text" name="billing_giro" id="billing_giro" class="postbox"  style="margin-bottom: 0;" value="<?php echo $billing_giro; ?>" />
                    </li>
                </ul>

                <?php
            }
            elseif( $is_add_campos_checkout_boleta )
            {
                ?>
                <p>
                    <label for="billing_rut"><strong>Rut para boleta</strong></label>
                    <input type="text" name="billing_rut" id="billing_rut" class="postbox" value="<?php echo $billing_rut; ?>" />
                </p>
                <?php
            }
        }
        elseif( Funciones::get_pais() === 'PE' )
        {
            if( $is_add_campos_checkout_factura )
            {
                ?>
                <p>
                    <label for="billing_tipo_documento">'¿Boleta o factura?</label>
                    <select name="billing_tipo_documento" id="billing_tipo_documento" class="postbox">
                        <option value="">--seleccione--</option>
                        <option value="boleta" <?php selected($billing_tipo_documento, 'boleta'); ?>>Boleta</option>
                        <option value="factura" <?php selected($billing_tipo_documento, 'factura'); ?>>Factura</option>
                    </select>
                </p>
                <p>
                    <label for="billing_rut">DNI/CE (ingrese solo cifras)</label>
                    <input type="text" name="billing_rut" id="billing_rut" class="postbox"  value="<?php echo $billing_rut; ?>" />
                </p>

                <p>
                    <label for="billing_ruc">RUC de la empresa</label>
                    <input type="text" name="billing_ruc" id="billing_ruc" class="postbox"  value="<?php echo $billing_ruc; ?>" />
                </p>
                <p>
                    <label for="billing_company">Razón social</label>
                    <input type="text" name="billing_company" id="billing_company" class="postbox" value="<?php echo $billing_company; ?>" />
                </p>
                <p>
                    <label for="billing_direccion_fiscal">Dirección fiscal</label>
                    <input type="text" name="billing_direccion_fiscal" id="billing_direccion_fiscal" class="postbox" value="<?php echo $billing_direccion_fiscal; ?>" />
                </p>
                <p>
                    <label for="billing_giro">Giro de la empresa</label>
                    <input type="text" name="billing_giro" id="billing_giro" class="postbox" value="<?php echo $billing_giro; ?>" />
                </p>

                <?php
            }
            elseif( $is_add_campos_checkout_boleta )
            {
                ?>
                <p>
                    <label for="billing_rut">DNI/CE (ingrese solo cifras)</label>
                    <input type="text" name="billing_rut" id="billing_rut" class="postbox"  value="<?php echo $billing_rut; ?>" />
                </p>
                <?php
            }
        }
        ?>      

        <?php
    }

    public static function edit_campos_facturacion_save_data($post_id)
    {
        $arr = array( 'billing_tipo_documento', 'billing_rut', 'billing_company',
            'billing_giro', 'billing_ruc', 'billing_direccion_fiscal' );

        foreach( $arr as $key )
        {
            if( array_key_exists($key, $_POST) )
            {
                $value = sanitize_text_field($_POST[$key]);

                update_post_meta($post_id, "_{$key}", $value);
            }
        }
    }

    /**
     * después de presionar el botón "finalizar compra" del checkout, reviso si hay stock en la
     * suc bsale asociada al medio de despacho elegido
     * @param type $fields
     * @param type $errors
     */
    public static function validar_stock_checkout_sucursales($fields, $errors)
    {
        $utils = new Utils();

        //solo si está habilitado el filtro
        if( !Funciones::is_enabled_shipping_filter_stock() )
        {
            return;
        }
        //debug: si es mi email, se ejecuta esta función
        $current_user = wp_get_current_user();
        $user_mail = $current_user->user_email;

        if( $user_mail === 'xxxx' || $user_mail === 'xxxx' )
        {
            //continua el debug
        }
        else
        {
            // return;
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($fields, "user $user_mail, validar_stock_checkout_sucursales(), fields:");
        }

        global $woocommerce;
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        $tienda_retiro = null;
        $tienda_retiro_orig = '';
        $shipings_arr = null;
        $shipings_to_display_arr = null;

        //si es pickup store (retiro en tienda)
        if( $chosen_shipping === 'wc_pickup_store' )
        {
            $tienda_retiro = isset($_REQUEST['shipping_pickup_stores']) ? $_REQUEST['shipping_pickup_stores'] : '';
            $tienda_retiro_orig = $tienda_retiro;

            //si no hay tienda retiro selected, no valido nada
            if( empty($tienda_retiro) )
            {
                return;
            }
        }
        else
        {
            $tienda_retiro = $chosen_shipping;
        }
        //formateo nombre
        $tienda_retiro = trim($tienda_retiro);
        $tienda_retiro = $utils->filter_chars($tienda_retiro);
        $tienda_retiro = strtolower($tienda_retiro);

        //busco sucuarsal id bsale asociada a este medio de envio
        //arreglo: titulo medio envio wc=>id sucursal bsale
        $shipings_arr = $utils->get_array_medios_envio($tienda_retiro);

        //prods del carro
        $cart = $woocommerce->cart->get_cart();

        //arreglo con productos del carro: sku=>cantidad
        $prods_arr = array();
        $prods_names_arr = array();

        // Loop over $cart items
        foreach( $cart as $cart_item_key => $cart_item )
        {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];
            $sku = $product->get_sku();
            $item_name = $product->get_title();
            $link = $product->get_permalink($cart_item);

            //si ya está este sku, sumo la cantidad. Else, la agrego
            if( isset($prods_arr[$sku]) )
            {
                $prods_arr[$sku] += $quantity;
            }
            else
            {
                $prods_arr[$sku] = $quantity;
            }
            $prods_names_arr[$sku] = array( 'name' => $item_name, 'link' => $link );
        }

        //si no hay prods en el carro, retorno
        if( count($prods_arr) <= 0 )
        {
            return;
        }

        //busco prods con stock en las sucursales de Bsale:
        //las sucursales e Bsale tienen stock para todos los productos del carro?
        $data = new WpDataBsale();
        $shipings_to_display_arr = $data->get_shipping_sucursal_stock2($shipings_arr, $prods_arr);

        //debug
        /* $errors->add('validation', "Medio de envio: '$chosen_shipping', tienda retiro: '$tienda_retiro'");
          $errors->add('validation', "shippings en los que buscar stock: " . print_r($shipings_arr, true));
          $errors->add('validation', "prods del carro: " . print_r($prods_arr, true));
          $errors->add('validation', "prods y links: " . print_r($prods_names_arr, true));
          $errors->add('validation', "shippings a mostrar: " . print_r($shipings_to_display_arr, true)); */

        //si state es true, todos los prods del carro tienen stock
        if( $shipings_to_display_arr['state'] == true )
        {
            return;
        }

        //mensaje real, de falta de stock
        $prods_sin_stock = current($shipings_to_display_arr)['prods_sin_stock'];

        $errors->add('validation', "prods sin stock: " . print_r($prods_sin_stock, true));

        $arraux = array();

        foreach( $prods_sin_stock as $p )
        {
            $sku = $p['sku'];

            $prod_data = isset($prods_names_arr[$sku]) ? $prods_names_arr[$sku] : null;

            if( $prod_data == null )
            {
                continue;
            }

            $name = $prod_data['name'];
            $link = $prod_data['link'];

            //obtengo nombre y link del prod con este sku
            $arraux[] = "<li><a href='$link' target='_blank' title='$name'>$name</a></li>";
        }

        //nada que mostrar
        if( count($arraux) <= 0 )
        {
            return;
        }
        $cart_url = wc_get_cart_url();

        $list = '<ul class="prods_sin_stock_bsale">' . implode("\n", $arraux) . '</ul>';
        $msj = "<div class='bsale_stock_error'>"
                . "<p class='titulo'>Los siguientes productos no tienen stock en '$tienda_retiro_orig'.<br/>"
                . "Debe sacarlos del carro para poder terminar la compra:</p>\n"
                . $list
                . "<p class='cart_link'><a href='$cart_url'>Ir al carro</a></p>";

        //mensaje real
        $errors->add('validation', $msj);
    }

    /**
     * guarda datos de billing para facturación
     * en sesion de wc, para pre llenarlos después
     */
    public static function save_billing_data($order_id)
    {
        if( !$order_id )
        {
            return;
        }
        // Getting an instance of the order object
        $order = wc_get_order($order_id);

        if( $order->is_paid() )
        {
            $paid = 'yes';
        }
        else
        {
            $paid = 'no';
        }

        $billing_tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);
        $billing_tipo_documento = strtolower($billing_tipo_documento);
        $billing_tipo_documento = empty($billing_tipo_documento) ? strtolower(get_post_meta($order_id, 'billing_tipo_documento', true)) : $billing_tipo_documento;

        $billing_rut = get_post_meta($order_id, 'billing_rut', true);
        $billing_rut = empty($billing_rut) ? strtolower(get_post_meta($order_id, '_billing_rut', true)) : $billing_rut;

        //giro, en  caso de factura
        $billing_giro = get_post_meta($order_id, 'billing_giro', true);
        $billing_giro = empty($billing_giro) ? strtolower(get_post_meta($order_id, '_billing_giro', true)) : $billing_giro;

        /* Funciones::print_r_html($_GET, "save_billing_data,rut=$billing_rut,  get:");
          Funciones::print_r_html($_POST, "save_billing_data, post:");
          die("save_billing_data"); */



        // Set the session data
        WC()->session->set('custom_data_bsale', array(
            'billing_tipo_documento' => $billing_tipo_documento,
            'billing_rut' => $billing_rut,
            'billing_giro' => $billing_giro ));

        if( isset($_REQUEST['param']) )
        {
            $data = WC()->session->get('custom_data_bsale');
            Funciones::print_r_html($data, "prefill_billing_fields,  WC()->session->get('custom_data_bsale') contiene:");
            //Funciones::print_r_html($address_fields, "prefill_billing_fields,  campos billing que vienen desde WC");
        }
    }

    /** carga en el checkout de woocommerce los datos prefilled del checkout */
    public static function prefill_billing_fields($address_fields)
    {
        if( WC()->session == null )
        {
            if( isset($_REQUEST['param']) )
            {
                //Funciones::print_r_html($address_fields, "prefill_billing_fields, WC()->session is null");
            }
            return $address_fields;
        }
        // Get the session data
        $data = WC()->session->get('custom_data_bsale');

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($data, "prefill_billing_fields,  WC()->session->get('custom_data_bsale') contiene:");
            //Funciones::print_r_html($address_fields, "prefill_billing_fields,  campos billing que vienen desde WC");
        }
        // First Name
        if( isset($data['billing_tipo_documento']) && !empty($data['billing_tipo_documento']) )
        {
            $address_fields['billing_tipo_documento']['default'] = $data['billing_tipo_documento'];
        }
        if( isset($data['billing_rut']) && !empty($data['billing_rut']) )
        {
            $address_fields['billing_rut']['default'] = $data['billing_rut'];
        }
        if( isset($data['billing_giro']) && !empty($data['billing_giro']) )
        {
            $address_fields['billing_giro']['default'] = $data['billing_giro'];
        }

        return $address_fields;
    }

    public static function product_edit_bsale_meta_box()
    {
        $screens = array( 'product' );

        foreach( $screens as $screen )
        {
            add_meta_box(
                    'bsalestock-metabox', 'Integración con Bsale: historial', 'WpUtils::product_edit_bsale_meta_box_back', $screen, 'normal', //Where on the page the meta box should be shown. The available options are normal, advanced, and side
                    'default'         // Priority
            );
        }
    }

    public static function order_edit_bsale_meta_box()
    {
        $screens = array( 'shop_order' );

        foreach( $screens as $screen )
        {
            add_meta_box(
                    'bsaleorder-metabox', 'Integración con Bsale: historial documentos del pedido',
                    'WpUtils::order_edit_bsale_meta_box_back', $screen, 'normal', //Where on the page the meta box should be shown. The available options are normal, advanced, and side
                    'default'         // Priority
            );
        }
    }

    /**
     * meta box con historial de sincronización de producto
     * @param type $post
     * @return type
     */
    public static function product_edit_bsale_meta_box_back($post)
    {
        $post_id = $post->ID;
        $product_id = $post_id;

        $product = wc_get_product($product_id);

//es prod válido?
        if( !$product )
        {
            return;
        }
        //coloco css inline
        $css = '<style> 
        .info_sync_div
        {
            padding: 6px 0 20px 0;
         }
        .info_sync_div a#get_info_last_sync_bsale
        {
            background-color: #f0f0f1;
            text-decoration: none;
            padding: 6px 10px;
            font-size: 1.2em;
        }
        .info_sync_div table.hist_prod
        {
            border: 1px solid #b9b9b9;
            border-collapse: collapse;
        }
        .info_sync_div table.hist_prod th
        {
            border: 1px solid #b9b9b9;
        }
        .info_sync_div table.hist_prod td
        {
            border: 1px solid #b9b9b9;
            padding: 3px 10px;           
        }
         .info_sync_div table.hist_prod td.p_text
        {
            width: 30em;
            overflow-x: auto;
        }
        </style>';
        $html = "<div id='producto_{$product_id}'>\n" . $css;
        
          //imagen de loading
        global $woo_bsale_db_url;
        $loading_img_url = $woo_bsale_db_url . 'img/spinner.gif';

        //ver información de últimos cambios de stock de este producto
        $html .= '<div class="info_sync_div">
            <p><a id="get_info_last_sync_bsale" class="btn-light btn" href="' . $product_id . '" 
                title="Ver últimas sincronizaciones desde bsale para este producto">
                Ver historial de sincronización <span class="loading_historial" 
                    style="margin-left: 30px;display:none;">
                    <img alt="loading..." src="' . $loading_img_url . '" /></span></a></p>
                <div id="result_reload_sync"></div>
                </div>';
        $html .= '</div>';
        echo $html;
    }

    /**
     * meta box con historial de dtes para este pedido
     * @param type $post
     * @return type
     */
    public static function order_edit_bsale_meta_box_back($post)
    {
        $post_id = $post->ID;
        $order_id = $post_id;

        //imagen de loading
        global $woo_bsale_db_url;
        $loading_img_url = $woo_bsale_db_url . 'img/spinner.gif';

        //coloco css inline
        $css = '<style> 
         
        .info_sync_div
        {
            padding: 6px 0 20px 0;
         }
        .info_sync_div a#get_info_order_bsale
        {
            background-color: #f0f0f1;
            text-decoration: none;
            padding: 6px 10px;
            font-size: 1.2em;
        }
        .info_sync_div table.hist_order
        {
            border: 1px solid #b9b9b9;
            border-collapse: collapse;
            width: 100%;           
            clear: both;
        }
        .info_sync_div table.hist_order th
        {
            border: 1px solid #b9b9b9;
        }
        .info_sync_div table.hist_order td
        {
            border: 1px solid #b9b9b9;
            padding: 3px 10px;           
        }
        .info_sync_div table.hist_order td.p_text
        {
            width: 30em;
            overflow-x: auto;
        }
        </style>';
        $html = "<div id='order_{$order_id}'>\n" . $css;

        //ver información de últimos cambios de stock de este producto
        $html .= '<div class="info_sync_div">
            <p><a id="get_info_order_bsale" class="btn-light btn" href="' . $order_id .
                '" title="Ver historial de DTES emitidos para este pedido">
                    Ver historial de documentos emitidos <span class="loading_historial" 
                    style="margin-left: 30px;display:none;">
                    <img alt="loading..." src="' . $loading_img_url . '" /></span></a></p>
                <div id="result_reload_sync"></div>
                </div>';
        $html .= '</div>';
        echo $html;
    }

    public static function product_edit_bsale_meta_box_side()
    {
        $screens = array( 'product' );

        foreach( $screens as $screen )
        {
            add_meta_box(
                    'bsalestock-metabox-side', 'Integración con Bsale', 'WpUtils::product_edit_bsale_meta_box_side_back', $screen, 'side', //Where on the page the meta box should be shown. The available options are normal, advanced, and side
                    'high'         // Priority
            );
        }
    }

    /**
     * meta box con informacion de bsale
     * @param type $post
     * @return type
     */
    public static function product_edit_bsale_meta_box_side_back($post)
    {
        $post_id = $post->ID;
        $product_id = $post_id;

        $product = wc_get_product($product_id);

//es prod válido?
        if( !$product )
        {
            return;
        }
        global $woo_bsale_db_url;
        $loading_url = $woo_bsale_db_url . 'img/loading2.gif';

        $html = "<div id='producto_{$product_id}'>";

        //botón para sincronizar stock de este producto y sus variaciones
        $html .= '<div class="misc-pub-section my-options-bsale">' .
                '<p>Sincronizar datos desde Bsale para este producto y sus variaciones</p>' .
                '<p><a class="button sync_bsale_btn" href="#" data-pid="' . $post_id . '" target="_blank" ' .
                'title="Sincronizar stock/precio desde Bsale para este producto y sus variaciones."' .
                'style="background-color: #ff5c1a; color: #fff; padding: 5px 15px;font-weight: bold;">SINCRONIZAR CON BSALE</a> </p>' .
                '<p id="spinner_sync_bsale" style="display: none;" ><img id="spinner_img_bsale" style="height: 40px;" src="' . $loading_url . '" /><br/>' .
                '<span style="color:red">No cierre la página. Al terminar la sincronización, se recargará por sí sola.</span></p>  ' .
                '<p id="bsale_sync_result"></p>' .
                '<p id="result_sync" style="display: none !important"></p>' .
                '</div>';
        $html .= '</div>';

        echo $html;
    }

    /**
     * AGREGA METABOX CON STOCK POR SUCURSALES AL EDITR DE PRODUCTOS
     */
    public static function stock_sucursales_product_edit()
    {
        $screens = array( 'product' );

        foreach( $screens as $screen )
        {
            add_meta_box(
                    'bsalestock-notice', 'Stock disponible en Bsale', 'bsale_stock_sucursales_product_html_back', $screen, 'side', //Where on the page the meta box should be shown. The available options are normal, advanced, and side
                    'high'         // Priority
            );
        }
    }

    public static function wc_bsale_stock_por_sucursal()
    {
        if( !Funciones::is_mostrar_stock_sucursal() )
        {
            return;
        }
        global $post;
        $post_id = $post->ID;
        $product_id = $post_id;

        $product = wc_get_product($product_id);

        //es prod válido?
        if( !$product )
        {
            return;
        }

        //si es producto variable, agrego el stock de todas sus variaciones
        $variations = $product->get_children();

        $style = is_array($variations) && count($variations) > 0 ? 'display:none' : 'display: block';
        $html = "<div id='producto_{$product_id}' style='$style'>" . get_post_meta($post_id, 'stock_por_sucursal_html', true) . '</div>';

        if( is_array($variations) )
        {
            $html .= '<div class="bsale_stock_variaciones"><p><strong>Variaciones</strong></p>';
            foreach( $variations as $variacion_id )
            {
                $sku = get_post_meta($variacion_id, '_sku', true);
                $product_variation = new WC_Product_Variation($variacion_id);

                if( $product_variation )
                {
                    // $variation_name = current($product_variation->get_variation_attributes());
                    $variation_attributes = $product_variation->get_variation_attributes();
                    $variation_name = '';
                    foreach( $variation_attributes as $key => $value )
                    {
                        $variation_name .= $value . ' ';
                    }
                    $stock = $product_variation->get_stock_quantity();
                    //$skuvar = $product_variation->get_sku();
                }
                else
                {
                    $variation_name = '';
                    $stock = '';
                }

                $html_variacion = get_post_meta($variacion_id, 'stock_por_sucursal_html', true);

                if( !empty($html_variacion) )
                {
                    $html .= "<div id='variacion_{$variacion_id}' class='bsale_variacion {$variation_name} sku_{$sku}' title='$stock'>" . $html_variacion . '</div>';
                }
            }
            $html .= '</div>';
        }

        if( function_exists('bsale_filter_sucursal_html') )
        {
            $html = bsale_filter_sucursal_html($html);
        }

        echo $html;
    }

    /**
     * coloca dentro del editor de pedido una sección con la información 
     * de facturación de Bsale
     * @param type $order
     * @return type
     */
    public static function order_meta_add_info($order)
    {
        $order_id = $order->get_id();

        $folio = get_post_meta($order_id, 'bsale_docto_folio', true);
        $bsale_docto_error = get_post_meta($order_id, 'bsale_docto_error', true);

        //url de dtes
        $bsale_docto_id_nv_url = get_post_meta($order_id, 'bsale_docto_id_nv_url', true);
        $bsale_docto_id_factura_url = get_post_meta($order_id, 'bsale_docto_id_factura_url', true);
        $bsale_docto_id_boleta_url = get_post_meta($order_id, 'bsale_docto_id_boleta_url', true);
        $bsale_docto_id_nc_url = get_post_meta($order_id, 'bsale_docto_id_nc_url', true);

        //folios de dtes
        $bsale_docto_folio_nv = get_post_meta($order_id, 'bsale_docto_folio_nv', true);
        $bsale_docto_folio_boleta = get_post_meta($order_id, 'bsale_docto_folio_boleta', true);
        $bsale_docto_folio_factura = get_post_meta($order_id, 'bsale_docto_folio_factura', true);
        $bsale_docto_folio_nc = get_post_meta($order_id, 'bsale_docto_folio_nc', true);

        $html = '<div class="dtes_bsale" style="padding: 10px 20px;">';
        $empty = true;

        //datos de facturacion
        $billing_tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);
        $billing_tipo_documento = empty($billing_tipo_documento) ? 'boleta' : $billing_tipo_documento;

        $billing_rut = get_post_meta($order_id, '_billing_rut', true);
        // $billing_rut = empty($billing_rut) ? get_post_meta($order_id, '_billing_rut', true) : $billing_rut;

        $billing_company = get_post_meta($order_id, '_billing_company', true);
        // $billing_company = empty($billing_company) ? get_post_meta($order_id, '_billing_company', true) : $billing_company;

        $billing_ruc = get_post_meta($order_id, '_billing_ruc', true);
        // $billing_ruc = empty($billing_ruc) ? get_post_meta($order_id, '_billing_ruc', true) : $billing_ruc;

        $billing_giro = get_post_meta($order_id, '_billing_giro', true);
        // $billing_giro = empty($billing_giro) ? get_post_meta($order_id, '_billing_giro', true) : $billing_giro;

        $billing_direccion_fiscal = get_post_meta($order_id, '_billing_direccion_fiscal', true);
        $sin_rut = Funciones::get_value('RUT_SIN_RUT', null);

        $style_tipo_doc = ($billing_tipo_documento === 'factura') ? 'color: #024eb1;' : 'color: #008200;';

        $style_tipo_doc2 = ($billing_tipo_documento === 'factura') ?
                'border: 1px solid #024eb1;padding: 1px 8px;border-radius: 4px;' :
                'border: 1px solid #008200;padding: 1px 8px;border-radius: 4px;;';

        if( Funciones::get_pais() === 'CL' )
        {
            $html .= '<ul class="bsale_list_datos_fact" style="border-bottom: 1px dotted #ff5b00;padding-bottom: 14px;">';

            if( $billing_rut === $sin_rut )
            {
                $html .= '<li class="bsale_fact_field rut" style="font-size: 1.1em;"><strong>' . __('RUT comprador') . ': SIN RUT</strong></li>';
            }
            else
            {
                $html .= '<li class="bsale_fact_field rut" style="font-size: 1.1em;"><strong>' . __('RUT comprador') . ': ' . $billing_rut . '</strong></li>';
            }


            $html .= '<li class="bsale_fact_field selector" style="font-size: 1.1em;' .
                    $style_tipo_doc . '">' . __('Documento tributario') .
                    ': <strong style="' . $style_tipo_doc2 . '">' . strtoupper($billing_tipo_documento) . '</strong></li>';

            if( $billing_tipo_documento === 'factura' )
            {
                $html .= '<li class="bsale_fact_field empresa">' . __('Empresa') . ': <strong>' . $billing_company . '</strong></li>';
                $html .= '<li class="bsale_fact_field giro">' . __('Giro empresa') . ': <strong>' . $billing_giro . '</strong></li>';
            }
            $html .= '</ul>';
        }
        elseif( Funciones::get_pais() === 'PE' )
        {
            $html .= '<ul class="bsale_list_datos_fact" style="border-bottom: 1px dotted #ff5b00;padding-bottom: 14px;">';

            $html .= '<li class="bsale_fact_field selector" style="font-size: 1.1em;' .
                    $style_tipo_doc . '">' . __('Documento tributario') . ': <strong style="' . $style_tipo_doc2 . '">' .
                    strtoupper($billing_tipo_documento) . '</strong></li>';
            $html .= '<li class="bsale_fact_field rut" style="font-size: 1.1em;">' . __('DNI/CE') . ': <strong>' . $billing_rut . '</strong></li>';

            if( $billing_tipo_documento === 'factura' )
            {
                $html .= '<li class="bsale_fact_field empresa">' . __('Empresa') . ': <strong> ' . $billing_company . '</strong></li>';
                $html .= '<li class="bsale_fact_field ruc" style="font-size: 1.1em;">' . __('RUC') . ': <strong> ' . $billing_ruc . '</strong></li>';
                $html .= '<li class="bsale_fact_field giro">' . __('Giro empresa') . ': <strong> ' . $billing_giro . '</strong></li>';
                $html .= '<li class="bsale_fact_field df">' . __('Dirección fiscal') . ': <strong> ' . $billing_direccion_fiscal . '</strong></li>';
            }
            $html .= '</ul>';
        }

        $html_code = '&#10070;';
        $style_link = 'font-weight: 600;color: #2b7ebd;text-transform: uppercase;text-decoration: none;';
        $style_link_nc = 'font-weight: 600;color: #ca0101;text-transform: uppercase;text-decoration: none;';

        $html .= '<p style="font-weight: 700;text-transform: uppercase;">Documentos de este pedido:</strong></p>'
                . '<ul class="bsale_dtes_list">';

        if( !empty($bsale_docto_id_nc_url) )
        {
            $html .= "<li><a style='$style_link_nc' href='$bsale_docto_id_nc_url' style='color:red;' target='_blank' title='Nota de cr&eacute;dito #$bsale_docto_folio_nc'>$html_code Nota de cr&eacute;dito #$bsale_docto_folio_nc</a></li>";
            $empty = false;
        }
        if( !empty($bsale_docto_id_factura_url) )
        {
            $html .= "<li><a style='$style_link' href='$bsale_docto_id_factura_url' target='_blank' title='Factura #$bsale_docto_folio_factura'>$html_code Factura #$bsale_docto_folio_factura</a></li>";
            $empty = false;
        }
        if( !empty($bsale_docto_id_boleta_url) )
        {
            $html .= "<li><a style='$style_link' href='$bsale_docto_id_boleta_url' target='_blank' title='Boleta #$bsale_docto_folio_boleta'>$html_code Boleta #$bsale_docto_folio_boleta</a></li>";
            $empty = false;
        }

        if( !empty($bsale_docto_id_nv_url) )
        {
            $html .= "<li><a style='$style_link' href='$bsale_docto_id_nv_url' target='_blank' title='Nota de venta #$bsale_docto_folio_nv'>$html_code Nota de venta #$bsale_docto_folio_nv</a></li>";
            $empty = false;
        }


        //backwARD compatibility
        if( $empty )
        {
            $bsale_docto_url = get_post_meta($order_id, 'bsale_docto_url', true);
            $bsale_docto_tipo = get_post_meta($order_id, 'bsale_docto_tipo', true);
            $bsale_docto_folio = get_post_meta($order_id, 'bsale_docto_folio', true);

            if( !empty($bsale_docto_url) && !empty(($bsale_docto_tipo)) )
            {
                $html .= "<li><a href='$bsale_docto_url' target='_blank'>$bsale_docto_tipo #$bsale_docto_folio </a><li/>";
                $empty = false;
            }
        }
        $html .= '</ul>';

        if( !empty($bsale_docto_error) )
        {
            $html .= "<p><span class='bsale_det_error' style='color: #930202;'>Error: $bsale_docto_error</span><p/>";
            $empty = false;
        }

        $html .= '</div>';

        if( $empty )
        {
            return;
        }
        $style_title = 'font-size: 16px;margin: 0;background-color: #ff5b00;line-height: 1.2em; color: #fff;padding: 5px 5px 5px 20px;';
        ?>
        <div class="bsale_wrapper" style="box-shadow: 3px 3px 5px 6px #c6c6c6;margin-top: 20px;margin-bottom: 5px;clear: both;display: block;padding: 0 0 5px 0;border-radius: 5px;border: 2px solid #ff5b00;display: block;float: left;width: 100%;">           
            <h4 style="<?php echo $style_title; ?>">Facturaci&oacute;n Bsale</h4>

            <?php if( !$empty ): ?>
                <?php echo $html ?>
            <?php endif; ?>        
        </div>
        <?php
    }

    /**
     * agrega billing_rut al checkout
     */
    public static function add_campos_boleta($fields)
    {
        $fields['billing_rut'] = array(
            'label' => __('RUT', 'woocommerce'), // Add custom field label
            'placeholder' => _x('Ingrese RUT 11111111-1', 'placeholder', 'woocommerce'), // Add custom field placeholder
            'required' => true, // if field is required or not
            'clear' => false, // add clear or not
            'type' => 'text', // add field type
            'class' => array( 'billing_rut_bsale' )    // add class name
        );

        return $fields;
    }

    /**
     * agrega campos factura al checkout
     * billing_rut 	text 	RUT obligatorio
      billing_tipo_documento 	select 	Documento obligatorio  boleta boleta/factura factura
      billing_company 	text 	Nombre de la empresa obligatorio
      billing_giro 	text 	Giro de la empresa 	obligatorio

     */
    public static function add_campos_factura($fields)
    {
        if( Funciones::get_pais() === 'CL' )
        {
            $fields['billing_tipo_documento'] = array(
                'label' => __('¿Boleta o factura?', 'woocommerce'), // Add custom field label
                'placeholder' => _x('¿Boleta o factura?', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'select', // add field type
                'class' => array( 'billing_tipo_documento_bsale' ),
                'options' => array(
                    '' => __('-seleccione-', 'woocommerce'),
                    'boleta' => __('Boleta', 'woocommerce'),
                    'factura' => __('Factura', 'woocommerce'),
                ),
            );

            $fields['billing_rut'] = array(
                'label' => __('RUT', 'woocommerce'), // Add custom field label
                'placeholder' => _x('Ingrese RUT 11111111-1', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_rut_bsale' )    // add class name
            );

            $fields['billing_company'] = array(
                'label' => __('Empresa', 'woocommerce'), // Add custom field label
                'placeholder' => _x('Nombre de la empresa', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_company_bsale' )    // add class name
            );

            $fields['billing_giro'] = array(
                'label' => __('Giro de la empresa', 'woocommerce'), // Add custom field label
                'placeholder' => _x('Giro de la empresa', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_giro_bsale' )    // add class name
            );
        }
        elseif( Funciones::get_pais() === 'PE' )
        {
            $fields['billing_tipo_documento'] = array(
                'label' => __('¿Boleta o factura?', 'woocommerce'), // Add custom field label
                'placeholder' => _x('¿Boleta o factura?', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'select', // add field type
                'class' => array( 'billing_tipo_documento_bsale' ),
                'options' => array(
                    '' => __('-seleccione-', 'woocommerce'),
                    'boleta' => __('Boleta', 'woocommerce'),
                    'factura' => __('Factura', 'woocommerce'),
                ),
            );

            $fields['billing_rut'] = array(
                'label' => __('DNI/CE', 'woocommerce'), // Add custom field label
                'placeholder' => _x('DNI/CE, solo cifras', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => false, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_rut_bsale' )    // add class name
            );

            $fields['billing_ruc'] = array(
                'label' => __('RUC', 'woocommerce'), // Add custom field label
                'placeholder' => _x('Ingrese RUC', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_rut_bsale' )    // add class name
            );

            $fields['billing_company'] = array(
                'label' => __('Razón social', 'woocommerce'), // Add custom field label
                'placeholder' => _x('Razón social', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_company_bsale' )    // add class name
            );

            $fields['billing_giro'] = array(
                'label' => __('Giro de la empresa', 'woocommerce'), // Add custom field label
                'placeholder' => _x('Giro de la empresa', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => false, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_giro_bsale' )    // add class name
            );
            $fields['billing_direccion_fiscal'] = array(
                'label' => __('Dirección fiscal de la empresa', 'woocommerce'), // Add custom field label
                'placeholder' => _x('Dirección fiscal de la empresa', 'placeholder', 'woocommerce'), // Add custom field placeholder
                'required' => true, // if field is required or not
                'clear' => false, // add clear or not
                'type' => 'text', // add field type
                'class' => array( 'billing_direccion_fiscal' )    // add class name
            );
        }


        return $fields;
    }

    public static function bsale_add_factura_number_column($columns)
    {

// put the column after the Status column
        $new_columns = array_slice($columns, 0, 2, true) +
                array( 'factura_columna' => 'Docto Bsale' ) +
                array_slice($columns, 2, count($columns) - 1, true);
        return $new_columns;
    }

    /**
     * valida campos checkout
     */
    public static function validate_checkout_fields($fields, $errors)
    {
        global $woocommerce;

        $billing_tipo_documento = isset($fields['billing_tipo_documento']) ? sanitize_text_field($fields['billing_tipo_documento']) : 'boleta'; //default, boleta
        $billing_giro = isset($fields['billing_giro']) ? sanitize_text_field($fields['billing_giro']) : '';
        $billing_rut = isset($fields['billing_rut']) ? sanitize_text_field($fields['billing_rut']) : '';
        $billing_company = isset($fields['billing_company']) ? sanitize_text_field($fields['billing_company']) : '';

        if( Funciones::get_pais() === 'CL' )
        {
            //si viene rut, lo valido
            if( !empty($billing_rut) )
            {
                // $rut = sanitize_text_field($fields['billing_rut']);
                $rut = $fields['billing_rut'];
                $utils = new Utils();
                $res = $utils->valida_rut($rut);

                if( !$res )
                {
                    $errors->add('validation', "El rut '$rut' ingresado no es válido. Ingrese el rut nuevamente.");
                }
            }

            if( $billing_tipo_documento === 'factura' )
            {
                if( empty($billing_company) )
                {
                    $errors->add('validation', "Debe ingresar el nombre de la empresa.");
                }
                if( empty($billing_giro) )
                {
                    $errors->add('validation', "Debe ingresar el giro de la empresa.");
                }
            }
        }
    }

    public static function save_campos_checkout_in_order($order_id)
    {
        $fields = $_POST;

        if( isset($fields['billing_rut']) )
        {
            $rut = sanitize_text_field($_POST['billing_rut']);
            update_post_meta($order_id, 'billing_rut', $rut);
            update_post_meta($order_id, '_billing_rut', $rut);
        }

        if( isset($fields['billing_tipo_documento']) )
        {
            $billing_tipo_documento = sanitize_text_field($_POST['billing_tipo_documento']);
            update_post_meta($order_id, 'billing_tipo_documento', $billing_tipo_documento);
            update_post_meta($order_id, '_billing_tipo_documento', $billing_tipo_documento);
        }

        if( isset($fields['billing_giro']) )
        {
            $billing_giro = sanitize_text_field($_POST['billing_giro']);
            update_post_meta($order_id, 'billing_giro', $billing_giro);
            update_post_meta($order_id, '_billing_giro', $billing_giro);
        }
        if( isset($fields['billing_direccion_fiscal']) )
        {
            $billing_giro = sanitize_text_field($_POST['billing_direccion_fiscal']);
            update_post_meta($order_id, 'billing_direccion_fiscal', $billing_giro);
            update_post_meta($order_id, '_billing_direccion_fiscal', $billing_giro);
        }
    }

    public static function display_campos_checkout_in_order($order)
    {
        return;
        $order_id = $order->get_id();

        $billing_tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);
        //$billing_tipo_documento = empty($billing_tipo_documento) ? get_post_meta($order_id, '_billing_tipo_documento', true) : $billing_tipo_documento;

        $billing_rut = get_post_meta($order_id, '_billing_rut', true);
        // $billing_rut = empty($billing_rut) ? get_post_meta($order_id, '_billing_rut', true) : $billing_rut;

        $billing_company = get_post_meta($order_id, '_billing_company', true);
        // $billing_company = empty($billing_company) ? get_post_meta($order_id, '_billing_company', true) : $billing_company;

        $billing_ruc = get_post_meta($order_id, '_billing_ruc', true);
        // $billing_ruc = empty($billing_ruc) ? get_post_meta($order_id, '_billing_ruc', true) : $billing_ruc;

        $billing_giro = get_post_meta($order_id, '_billing_giro', true);
        // $billing_giro = empty($billing_giro) ? get_post_meta($order_id, '_billing_giro', true) : $billing_giro;

        $billing_direccion_fiscal = get_post_meta($order_id, '_billing_direccion_fiscal', true);

        if( Funciones::get_pais() === 'CL' )
        {
            echo '<div class="bsale_fact_section"><p class="bsale_fact_field selector"><strong>' . __('¿Boleta o Factura?') . ':</strong> ' . $billing_tipo_documento . '</p>';
            echo '<p class="bsale_fact_field rut"><strong>' . __('RUT comprador') . ':</strong> ' . $billing_rut . '</p>';
            echo '<p class="bsale_fact_field empresa"><strong>' . __('Empresa') . ':</strong> ' . $billing_company . '</p>';
            echo '<p class="bsale_fact_field giro"><strong>' . __('Giro empresa') . ':</strong> ' . $billing_giro . '</p></div>';
        }
        elseif( Funciones::get_pais() === 'PE' )
        {
            echo '<div class="bsale_fact_section"><p class="bsale_fact_field selector"><strong>' . __('¿Boleta o Factura?') . ':</strong> ' . $billing_tipo_documento . '</p>';
            echo '<p class="bsale_fact_field rut"><strong>' . __('DNI/CE') . ':</strong> ' . $billing_rut . '</p>';
            echo '<p class="bsale_fact_field empresa"><strong>' . __('Empresa') . ':</strong> ' . $billing_company . '</p>';
            echo '<p class="bsale_fact_field ruc"><strong>' . __('RUC') . ':</strong> ' . $billing_ruc . '</p>';
            echo '<p class="bsale_fact_field giro"><strong>' . __('Giro empresa') . ':</strong> ' . $billing_giro . '</p>';
            echo '<p class="bsale_fact_field df"><strong>' . __('Dirección fiscal') . ':</strong> ' . $billing_direccion_fiscal . '</p></div>';
        }
    }

    //* Add selection field value to emails
    public static function display_campos_checkout_in_email($keys)
    {
        $pais = Funciones::get_pais();

        $keys['Documento'] = 'billing_tipo_documento';
        if( $pais === 'CL' )
        {
            $keys['RUT cliente'] = '_billing_rut';
        }
        else
        {
            $keys['DNI/CE cliente'] = '_billing_rut';
        }

        $keys['Empresa'] = '_billing_company';

        if( $pais === 'PE' )
        {
            $keys['RUC empresa'] = '_billing_ruc';
            $keys['Dirección fiscal'] = 'billing_direccion_fiscal';
        }

        $keys['Giro empresa'] = 'billing_giro';

        //agrego boleta o factura si es que existe
        $keys['Boleta'] = 'bsale_docto_id_boleta_url';
        $keys['Factura'] = 'bsale_docto_id_factura_url';

        return $keys;
    }

    public static function display_campos_checkout_in_email2($order, $sent_to_admin, $plain_text)
    {
        $pais = Funciones::get_pais();
        $order_id = $order->get_id();

        $billing_tipo_documento = get_post_meta($order_id, 'billing_tipo_documento', true);
        $billing_tipo_documento = empty($billing_tipo_documento) ? 'boleta' : $billing_tipo_documento;

        $billing_rut = get_post_meta($order_id, '_billing_rut', true);
        $billing_company = $order->get_billing_company();

        $billing_ruc = get_post_meta($order_id, '_billing_ruc', true);
        $billing_direccion_fiscal = get_post_meta($order_id, 'billing_direccion_fiscal', true);
        $billing_giro = get_post_meta($order_id, 'billing_giro', true);
        $bsale_docto_id_boleta_url = get_post_meta($order_id, 'bsale_docto_id_boleta_url', true);
        $bsale_docto_id_factura_url = get_post_meta($order_id, 'bsale_docto_id_factura_url', true);

        // ok, we will add the separate version for plaintext emails
        if( false === $plain_text )
        {
            $html = '<h2>Datos de facturación</h2>' .
                    '<ul>' .
                    "<li><strong>Documento: </strong>$billing_tipo_documento</li>";

            if( $pais === 'CL' )
            {
                $html .= "<li><strong>RUT: </strong>$billing_rut</li>";

                if( $billing_tipo_documento === 'factura' )
                {
                    $html .= "<li><strong>Empresa: </strong>$billing_company</li>";
                    $html .= "<li><strong>Giro: </strong>$billing_giro</li>";
                }
            }
            else
            {
                $html .= "<li><strong>DNI/CE: </strong>$billing_rut</li>";

                if( $billing_tipo_documento === 'factura' )
                {
                    $html .= "<li><strong>RUC: </strong>$billing_ruc</li>";
                    $html .= "<li><strong>Empresa: </strong>$billing_company</li>";
                    $html .= "<li><strong>Dirección fiscal: </strong>$billing_direccion_fiscal</li>";
                    $html .= "<li><strong>Giro: </strong>$billing_giro</li>";
                }
            }

            if( !empty($bsale_docto_id_boleta_url) )
            {
                $html .= "<li><a href='$bsale_docto_id_boleta_url' target='_blank'><strong>Ver $billing_tipo_documento aqui: "
                        . "</strong></a> $bsale_docto_id_boleta_url</li>";
            }
            if( !empty($bsale_docto_id_factura_url) )
            {
                $html .= "<li><a href='$bsale_docto_id_factura_url' target='_blank'><strong>Ver $billing_tipo_documento aqui: "
                        . "</strong></a> $bsale_docto_id_factura_url</li>";
            }

            $html .= '</ul><p>&nbsp;</p>';

            echo $html;
        }
        else
        {

            $html = "Datos de facturación\n\n" .
                    "Documento: $billing_tipo_documento\n";

            if( $pais === 'CL' )
            {
                $html .= "RUT: $billing_rut\n";

                if( $billing_tipo_documento === 'factura' )
                {
                    $html .= "Empresa: $billing_company\n";
                    $html .= "Giro: $billing_giro\n";
                }
            }
            else
            {
                $html .= "DNI/CE: $billing_rut\n";

                if( $billing_tipo_documento === 'factura' )
                {
                    $html .= "RUC: $billing_ruc\n";
                    $html .= "Empresa: $billing_company\n";
                    $html .= "Dirección fiscal: $billing_direccion_fiscal\n";
                    $html .= "Giro: $billing_giro\n";
                }
            }

            if( !empty($bsale_docto_id_boleta_url) )
            {
                $html .= "Ver $billing_tipo_documento aqui: $bsale_docto_id_boleta_url\n";
            }
            if( !empty($bsale_docto_id_factura_url) )
            {
                $html .= "Ver $billing_tipo_documento aqui: $bsale_docto_id_factura_url\n";
            }

            $html .= "\n";

            echo $html;
        }
    }

    public static function bsale_add_factura_number_column_data($column)
    {
        global $post, $the_order, $wpo_wcpdf;

        if( $column == 'factura_columna' )
        {
            if( empty($the_order) || $the_order->get_id() != $post->ID )
            {
                /* $order_id = $post->ID;
                  $folio = get_post_meta( $order_id, 'bsale_docto_folio' );
                  $url = get_post_meta( $order_id, 'bsale_docto_url' );
                  $order = wc_get_order( $post->ID );
                  echo '<a href="$url" target="_blank">#' . $order_id . $folio . '</a>'; */
                do_action('wcpdf_invoice_number_column_end', $post);
            }
            else
            {
                $order_id = $post->ID;

                $billing_rut = get_post_meta($order_id, '_billing_rut', true);
                $billing_tipo_documento = get_post_meta($order_id, '_billing_tipo_documento', true);

                $folio = get_post_meta($order_id, 'bsale_docto_folio', true);
                $bsale_docto_error = get_post_meta($order_id, 'bsale_docto_error', true);

                //url de dtes
                $bsale_docto_id_nv_url = get_post_meta($order_id, 'bsale_docto_id_nv_url', true);
                $bsale_docto_id_factura_url = get_post_meta($order_id, 'bsale_docto_id_factura_url', true);
                $bsale_docto_id_boleta_url = get_post_meta($order_id, 'bsale_docto_id_boleta_url', true);
                $bsale_docto_id_nc_url = get_post_meta($order_id, 'bsale_docto_id_nc_url', true);

                //folios de dtes
                $bsale_docto_folio_nv = get_post_meta($order_id, 'bsale_docto_folio_nv', true);
                $bsale_docto_folio_boleta = get_post_meta($order_id, 'bsale_docto_folio_boleta', true);
                $bsale_docto_folio_factura = get_post_meta($order_id, 'bsale_docto_folio_factura', true);
                $bsale_docto_folio_nc = get_post_meta($order_id, 'bsale_docto_folio_nc', true);

                $html = '<p class="dtes_bsale" style="color:#50575e">';
                $empty = true;

                $billing_tipo_documento = empty($billing_tipo_documento) ? 'boleta' : $billing_tipo_documento;

                $billing_tipo_documento_txt = ucfirst($billing_tipo_documento);

                $pais = Funciones::get_pais();

                if( !empty($billing_rut) )
                {
                    if( $pais === 'PE' )
                    {
                        $html .= "<span>DNI/CE $billing_rut ($billing_tipo_documento_txt)</span><br/><br/>";
                    }
                    else
                    {
                        $sin_rut = Funciones::get_value('RUT_SIN_RUT', null);
                        //chile
                        if( $billing_rut !== $sin_rut )
                        {
                            $html .= "<span>RUT $billing_rut ($billing_tipo_documento_txt)</span><br/><br/>";
                        }
                        else
                        {
                            $html .= "<span>Sin rut ($billing_tipo_documento_txt)</span><br/><br/>";
                        }
                    }
                    $empty = false;
                }

                $html_code = '&#10070;';

                if( !empty($bsale_docto_id_nc_url) )
                {
                    $html .= "<a href='$bsale_docto_id_nc_url' target='_blank' style='color:red;'><span class='t_d'>$html_code Nota de cr&eacute;dito</span> <span class='t_f'>#$bsale_docto_folio_nc</span></a><br/>";
                    $empty = false;
                }
                if( !empty($bsale_docto_id_factura_url) )
                {
                    $html .= "<a href='$bsale_docto_id_factura_url' target='_blank'><span class='t_d'>$html_code Factura</span> <span class='t_f'>#$bsale_docto_folio_factura</span></a><br/>";
                    $empty = false;
                }
                if( !empty($bsale_docto_id_boleta_url) )
                {
                    $html .= "<a href='$bsale_docto_id_boleta_url' target='_blank'><span class='t_d'>$html_code Boleta</span> <span class='t_f'>#$bsale_docto_folio_boleta</span></a><br/>";
                    $empty = false;
                }

                if( !empty($bsale_docto_id_nv_url) )
                {
                    $html .= "<a href='$bsale_docto_id_nv_url' target='_blank'><span class='t_d'>$html_code Nota de venta</span> <span class='t_f'>#$bsale_docto_folio_nv</span></a><br/>";
                    $empty = false;
                }

                if( !$empty )
                {
                    //borrar docs
                    $page = $_SERVER['PHP_SELF'];
                    $params = isset($_REQUEST['post_type']) ? 'post_type=' . $_REQUEST['post_type'] : 'post_type=shop_order';
                    $page = "$page?$params&borrar_factura=$order_id";
                    $question = '¿Deseas borrar los links a los dtes de este pedido?\n'
                            . 'Si los borras, podrás volver a emitirlos cambiando el pedido de estado.\n'
                            . 'Pero deberás anular en Bsale los DTS borrados.\n'
                            . 'Si lo que deseas es anularlos, debes cambiar el pedido a estado CANCELADO.';

                    $page = wp_nonce_url($page, 'borrar_factura', 'nonce');

                    // $html .= "<a title='$question' href='{$page}' onclick='return confirm(\"" . $question . "\") && confirm(\"¿Está seguro?\");' style='display:none; color: #ae0000; font-size: 11px;'>borrar links a docs</a>";
                }

                //backwARD compatibility
                if( $empty )
                {
                    $bsale_docto_url = get_post_meta($order_id, 'bsale_docto_url', true);
                    $bsale_docto_tipo = get_post_meta($order_id, 'bsale_docto_tipo', true);
                    $bsale_docto_folio = get_post_meta($order_id, 'bsale_docto_folio', true);

                    if( !empty($bsale_docto_url) && !empty(($bsale_docto_tipo)) )
                    {
                        $html .= "<a href='$bsale_docto_url' target='_blank'>$html_code $bsale_docto_tipo #$bsale_docto_folio </a><br/>";
                        $empty = false;
                    }
                }

                $html .= '</p>';

                if( !$empty )
                {
                    echo($html);
                }

                if( !empty($bsale_docto_error) )
                {
                    echo("<p class='bsale_det_error' style='color: #930202;'>$bsale_docto_error</p>");
                }


                do_action('wcpdf_invoice_number_column_end', $the_order);
            }
        }
    }

    public static function bsale_product_column($columns)
    {

// put the column after the Status column
        $new_columns = array_slice($columns, 0, 2, true) +
                array( 'bsale_column' => 'Bsale' ) +
                array_slice($columns, 2, count($columns) - 1, true);
        return $new_columns;
    }

    public static function bsale_product_column_data($column, $post_ID)
    {

        if( $column === 'name' )
        {
            if( empty($post_ID) )
            {
                
            }
            else
            {
                $post_id = $post_ID;

                $bsale_info = get_post_meta($post_id, 'bsale_info', true);
                $bsale_info_variacion = get_post_meta($post_id, 'bsale_info_variacion', true);

                if( !empty($bsale_info) )
                {
                    echo "<p><span class='bsale_info' style='color: #dc3232'>$bsale_info</span></p>";
                }
                if( !empty($bsale_info_variacion) )
                {
                    echo "<p><span class='bsale_info' style='color: #dc3232'>$bsale_info_variacion</span></p>";
                }
            }
        }
    }

    public static function bsale_add_factura_number_column_quote($columns)
    {

// put the column after the Status column
        $new_columns = array_slice($columns, 0, 2, true) +
                array( 'nv_columna' => 'NV' ) +
                array_slice($columns, 2, count($columns) - 1, true);
        return $new_columns;
    }

    public static function bsale_add_factura_number_column_data_quote($column, $post_ID)
    {
        global $post, $wpo_wcpdf;

        if( $column === 'nv_columna' )
        {
            if( empty($post) || $post_ID != $post->ID )
            {
                do_action('wcpdf_invoice_number_column_end', $post);
            }
            else
            {

//id de la orden
                $order_id = $post_ID;

//hay que borrar factura? solo si el id a borrar es el de esta columna
                if( isset($_REQUEST['borrar_factura_quote']) &&
                        ((int) $_REQUEST['borrar_factura_quote']) == $order_id )
                {
                    $order_id_ref = (int) $_REQUEST['borrar_factura_quote'];

                    delete_post_meta($order_id_ref, 'bsale_docto_folio_quote');
                    delete_post_meta($order_id_ref, 'bsale_docto_url_quote');
                    delete_post_meta($order_id_ref, 'bsale_docto_tipo_quote');
                    delete_post_meta($order_id_ref, "bsale_docto_id_nv_quote");

                    echo("<p>Se ha borrado el registro de DTEs para esta cotizacion."
                    . "(Los DTES emitidos no han sido anulados. Para ello, debe emitir notas de crédito o lo que corresponda)</p>");

                    unset($_REQUEST['borrar_factura_quote']);
                    do_action('wcpdf_invoice_number_column_end', $post);
                    return;
                }

                $folio = get_post_meta($order_id, 'bsale_docto_folio_quote', true);
                $bsale_docto_error = get_post_meta($order_id, 'bsale_docto_error_quote', true);

//si hay factura en este pedido
                if( !empty($folio) )
                {
                    $url = get_post_meta($order_id, 'bsale_docto_url_quote', true);
                    $tipo_docto = get_post_meta($order_id, 'bsale_docto_tipo_quote', true);

//para determimnar la pagina actual
                    $page = $_SERVER['PHP_SELF'];
                    $params = isset($_REQUEST['post_type']) ? 'post_type=' . $_REQUEST['post_type'] : 'post_type=addify_quote';

                    $page = "$page?$params&borrar_factura_quote=$order_id";

//muestro cada docto asociado a esta orden
                    $url = trim($url);
                    $url_arr = explode(' ', $url);

                    $i = 0;
                    foreach( $url_arr as $u )
                    {
                        $desc = ($i == 0) ? "$tipo_docto #$folio" : 'DTE';
                        echo "<p><a href='$u' target='_blank'>$desc </a></p>";
                        $i++;
                    }

                    echo "<p><a href='{$page}' style='color: red;'>borrar docs</a></p>";
                }
                if( !empty($bsale_docto_error) )
                {
                    echo("<p>$bsale_docto_error</p>");
                }


                do_action('wcpdf_invoice_number_column_end', $the_order);
            }
        }
    }

    /**
     * muestra los puntos de Bsale en el dashboard del comprador
     */
    public static function display_customer_points()
    {
        $user_id = get_current_user_id();

        if( $user_id <= 0 )
        {
            return;
        }
        $user_logged = get_userdata($user_id);

        if( empty($user_logged) )
        {
            return;
        }

        $user_email = $user_logged->user_email;

        if( empty($user_email) )
        {
            return;
        }

        //puntos
        $puntos = (int) get_user_meta($user_id, 'bsale_puntos_customer', true);
        //fecha ultima vez que se preguntó por los puntos
        $puntos_fecha = get_user_meta($user_id, 'bsale_puntos_customer_last_date', true);
        //0 ó 1 
        $puntos_acumula = get_user_meta($user_id, 'bsale_puntos_customer_acumula', true);

        $strinfo = '';

        //tiempo transcurrido desde ultima consulta a Bsale por puntos
        if( $puntos_fecha > 0 )
        {
            $delta_time = time() - $puntos_fecha;
            $hours = floor($delta_time / 3600);
            $delta_time %= 3600;
            $minutes = floor($delta_time / 60);

            $strinfo .= "pf($puntos_fecha) >0, dt($delta_time) = " . time() . " - " . $puntos_fecha . " min=$minutes";
        }
        else
        {
            $intervalo = 0;
            $minutes = 0;
        }

        //si no tiene puntos o nunca he preguntado por ellos
        //pregunta aBsale cada 10 minutos
        if( $puntos <= 0 || $minutes > 10 )
        {
            $cliente = new Cliente();
            $cliente_bsale = $cliente->getCliente_by_email($user_email);

            //cliente no existe en Bsale
            /* if(!is_array($cliente_bsale) || count($cliente_bsale)<=0)
              {
              return;
              } */

            $puntos_acumula = isset($cliente_bsale['accumulatePoints']) ? $cliente_bsale['accumulatePoints'] : 0;

            $puntos = isset($cliente_bsale['points']) ? $cliente_bsale['points'] : 0;

            //actualizo datos meta
            update_user_meta($user_id, 'bsale_puntos_customer', $puntos);
            update_user_meta($user_id, 'bsale_puntos_customer_last_date', time());
            update_user_meta($user_id, 'bsale_puntos_customer_acumula', $puntos_acumula);

            //si no acumula puntos, no hago nada
            if( $puntos_acumula <= 0 )
            {
                return;
            }

            //$strinfo contiene infor para debug
        }
        ?>
        <div id="puntos_bsale" class="woocommerce-info" style="float: left; width: calc( 100% - 300px);">
            <p style="text-transform: uppercase;"><strong>Puntos acumulados: <span class="puntos_bsale_span"><?php echo $puntos; ?></span></strong></p>
        </div>

        <?php
    }

    public static function get_bsale_last_error_notice()
    {
        //no muestra avisos si esta constante está definida
        if( defined('DISABLE_NAG_NOTICES') && DISABLE_NAG_NOTICES == true )
        {
            return;
        }
        return;

        $bsale_option_errors = get_option('bsale_option_errors');
        $bsale_option_errors_arr = !empty($bsale_option_errors) ? json_decode($bsale_option_errors, true) : null;

        $code = isset($arr['code']) ? $arr['code'] : -1;

        if( !is_array($bsale_option_errors_arr) || count($bsale_option_errors_arr) <= 0 )
        {
            return;
        }
        $url = get_site_url() . '/wp-admin/tools.php?page=woo_bsale-admin-menu&bsale_tab=bsale_config';
        ?>
        <div class="notice notice-error is-dismissible" style="background-color: #ffe6dd;">
            <p > 
                <strong>ERROR INTEGRACIÓN WOOCOMMERCE BSALE</strong><br/><br/>
                <span class="help_bsale">
                    <span class="error_bsale"><?php echo $bsale_option_errors_arr['msg']; ?></span><br/>
                    Último intento de conectarse: <?php echo $bsale_option_errors_arr['url']; ?><br/>
                    Código http de respuesta:  <strong><?php echo $bsale_option_errors_arr['code']; ?></strong> 
                    <a href="https://es.wikipedia.org/wiki/Anexo:C%C3%B3digos_de_estado_HTTP" target="_blank">(¿qué significa?)</a><br/><br/>
                    <strong><a href=" <?php echo $url; ?>" target="_blank">Ir a Integración con Bsale</a></strong><br/>


                </span>
            </p>
        </div>
        <?php
    }
}

/**
 * recibe listaod de pickup stores y solo devuelve aquellas en que hay stock en Bsale
 * @param type $get_stores
 */
function bsale_filter_pickup_stores($get_stores)
{
//debug: si es mi email, muesro todas las tiendas
    $current_user = wp_get_current_user();
    $user_mail = $current_user->user_email;

    if( $user_mail === 'xxxx' || $user_mail === 'xxx' )
    {
//continua el debug
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($get_stores, "user $user_mail, muestra todas las tiendas");
        }
        return $get_stores;
    }

    if( isset($_REQUEST['param']) )
    {
        Funciones::print_r_html($get_stores, "bsale_filter_pickup_stores, params:");
    }
    else
    {
//return $get_stores;
    }
    $utils = new Utils();
//arreglo: titulo medio envio wc=>id sucursal bsale
    $shipings_arr = $utils->get_array_medios_envio();

//listado de prods del carro
    global $woocommerce;
    $cart = $woocommerce->cart->get_cart();

//arreglo con productos del carro: sku=>cantidad
    $prods_arr = array();

// Loop over $cart items
    foreach( $cart as $cart_item_key => $cart_item )
    {
        $product = $cart_item['data'];
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        $sku = $cart_item['data']->get_sku();

//si ya está este sku, sumo la cantidad. Else, la agrego
        if( isset($prods_arr[$sku]) )
        {
            $prods_arr[$sku] += $quantity;
        }
        else
        {
            $prods_arr[$sku] = $quantity;
        }
    }

    if( count($prods_arr) <= 0 )
    {
        return $get_stores;
    }

    if( isset($_REQUEST['test_dte']) )
    {
        Funciones::print_r_html($prods_arr, "bsale_filter_pickup_stores, prodcuto del carro y cantidad a comprar: ");
    }

//busco prods con stock en las sucursales de Bsale:
//las sucursales e Bsale tienen stock para todos los productos del carro?
    $data = new WpDataBsale();
    $shipings_to_display_arr = $data->get_shipping_sucursal_stock($shipings_arr, $prods_arr);

    if( isset($_REQUEST['param']) )
    {
        Funciones::print_r_html($shipings_to_display_arr, "bsale_filter_pickup_stores, shipping asociado a suc bsale:");
    }



    $arr = array();
// $arr = $get_stores;
//  $arr[999] = 'test store';

    foreach( $get_stores as $post_id => $store )
    {
        $label = $utils->filter_chars($store);
        $label = strtolower($label);
        $display = true;

        if( isset($shipings_to_display_arr[$label]) )
        {
            $display = $shipings_to_display_arr[$label];

            if( isset($_REQUEST['param']) )
            {
                $displaystr = ($display) ? 'SÍ' : 'NO';
                Funciones::print_r_html("bsale_filter_pickup_stores name = '$label' se muestra en checkout: $displaystr");
            }
        }
        else
        {
            $display = false;
        }
//se debe mostrar este shipping method en el checkout?
        if( $display !== false )
        {
            $arr[$post_id] = $store;
        }
    }

    if( isset($_REQUEST['param']) )
    {
        Funciones::print_r_html($arr, "bsale_filter_pickup_stores, medios de envio que se muestran");
    }

    return $arr;
}

function bsale_stock_sucursales_product_html_back($post)
{
    if( !Funciones::is_mostrar_stock_sucursal_backend() )
    {
        return;
    }
    $post_id = $post->ID;

    /* $html = get_post_meta($post_id, 'stock_por_sucursal_html', true);

      if( function_exists('bsale_filter_sucursal_html') )
      {
      $html = bsale_filter_sucursal_html($html);
      }
      echo($html); */

//    global $post;
//    $post_id = $post->ID;
    $product_id = $post_id;

    $product = wc_get_product($product_id);

//es prod válido?
    if( !$product )
    {
        return;
    }

    $html = "<div id='producto_{$product_id}'>" . get_post_meta($post_id, 'stock_por_sucursal_html', true) . '</div>';

//si es producto variable, agrego el stock de todas sus variaciones
    $variations = $product->get_children();

    if( is_array($variations) && count($variations) > 0 )
    {
        $html .= '<div class="bsale_stock_variaciones"><p><strong>Variaciones</strong></p>';
        foreach( $variations as $variacion_id )
        {
            $sku = get_post_meta($variacion_id, '_sku', true);
            $product_variation = new WC_Product_Variation($variacion_id);

            if( $product_variation )
            {
                $variation_name = current($product_variation->get_variation_attributes());
                //$skuvar = $product_variation->get_sku();
            }
            else
            {
                $variation_name = '';
            }

            $html_variacion = get_post_meta($variacion_id, 'stock_por_sucursal_html', true);

            if( !empty($html_variacion) )
            {
                $html .= "<div id='variacion_{$variacion_id}' class='{$variation_name} sku_{$sku}'>" .
                        "<p><strong>Variacion '$variation_name' sku='$sku'</strong></p>" .
                        $html_variacion . '</div>';
            }
        }
        $html .= '</div>';
    }

    echo $html;
}

function bsale_edit_fact_custom_box_html($post)
{
    die('edit_fact_custom_box_html');
    //datos de fact de este pedido
    $order_id = $post->ID;

    if( empty($order_id) )
    {
        return;
    }

    $is_add_campos_checkout_boleta = Funciones::is_add_campos_checkout_boleta();
    $is_add_campos_checkout_factura = Funciones::is_add_campos_checkout_factura();

    //datos meta de facturacion
    $billing_tipo_documento = get_post_meta($order_id, 'billing_tipo_documento', true);
    $billing_tipo_documento = empty($billing_tipo_documento) ? get_post_meta($order_id, '_billing_tipo_documento', true) : $billing_tipo_documento;

    $billing_rut = get_post_meta($order_id, 'billing_rut', true);
    $billing_rut = empty($billing_rut) ? get_post_meta($order_id, '_billing_rut', true) : $billing_rut;

    $billing_company = get_post_meta($order_id, 'billing_company', true);
    $billing_company = empty($billing_company) ? get_post_meta($order_id, '_billing_company', true) : $billing_company;

    //peru
    $billing_ruc = get_post_meta($order_id, 'billing_ruc', true);
    $billing_ruc = empty($billing_ruc) ? get_post_meta($order_id, '_billing_ruc', true) : $billing_ruc;

    $billing_giro = get_post_meta($order_id, 'billing_giro', true);
    $billing_giro = empty($billing_giro) ? get_post_meta($order_id, '_billing_giro', true) : $billing_giro;

    if( Funciones::get_pais() === 'CL' )
    {
        if( $is_add_campos_checkout_factura )
        {
            ?>
            <p>
                <label for="billing_tipo_documento">'¿Boleta o factura?</label>
                <select name="billing_tipo_documento" id="billing_tipo_documento" class="postbox">
                    <option value="">--seleccione--</option>
                    <option value="something" <?php selected($billing_tipo_documento, 'boleta'); ?>>Boleta</option>
                    <option value="else" <?php selected($billing_tipo_documento, 'factura'); ?>>Factura</option>
                </select>
            </p>
            <p>
                <label for="billing_rut">Rut para boleta/factura</label>
                <input type="text" name="billing_rut" id="billing_rut" class="postbox" value="<?php echo $billing_rut; ?>" />
            </p>
            <p>
                <label for="billing_company">Nombre de la empresa</label>
                <input type="text" name="billing_company" id="billing_company" class="postbox" value="<?php echo $billing_company; ?>" />
            </p>
            <p>
                <label for="billing_giro">Giro de la empresa</label>
                <input type="text" name="billing_giro" id="billing_giro" class="postbox" value="<?php echo $billing_giro; ?>" />
            </p>
            <?php
        }
        elseif( $is_add_campos_checkout_boleta )
        {
            ?>
            <p>
                <label for="billing_rut">Rut para boleta/factura</label>
                <input type="text" name="billing_rut" id="billing_rut" class="postbox" value="<?php echo $billing_rut; ?>" />
            </p>
            <?php
        }
    }
    elseif( Funciones::get_pais() === 'PE' )
    {
        if( $is_add_campos_checkout_factura )
        {
            ?>
            <p>
                <label for="billing_tipo_documento">'¿Boleta o factura?</label>
                <select name="billing_tipo_documento" id="billing_tipo_documento" class="postbox">
                    <option value="">--seleccione--</option>
                    <option value="something" <?php selected($billing_tipo_documento, 'boleta'); ?>>Boleta</option>
                    <option value="else" <?php selected($billing_tipo_documento, 'factura'); ?>>Factura</option>
                </select>
            </p>
            <p>
                <label for="billing_rut">DNI/CE (ingrese solo cifras)</label>
                <input type="text" name="billing_rut" id="billing_rut" class="postbox"  value="<?php echo $billing_rut; ?>" />
            </p>

            <p>
                <label for="billing_ruc">RUC de la empresa</label>
                <input type="text" name="billing_ruc" id="billing_ruc" class="postbox"  value="<?php echo $billing_ruc; ?>" />
            </p>
            <p>
                <label for="billing_company">Razón social</label>
                <input type="text" name="billing_company" id="billing_company" class="postbox" value="<?php echo $billing_company; ?>" />
            </p>
            <p>
                <label for="billing_direccion_fiscal">Dirección fiscal</label>
                <input type="text" name="billing_direccion_fiscal" id="billing_direccion_fiscal" class="postbox" value="<?php echo $billing_direccion_fiscal; ?>" />
            </p>
            <p>
                <label for="billing_giro">Giro de la empresa</label>
                <input type="text" name="billing_giro" id="billing_giro" class="postbox" value="<?php echo $billing_giro; ?>" />
            </p>

            <?php
        }
        elseif( $is_add_campos_checkout_boleta )
        {
            ?>
            <p>
                <label for="billing_rut">DNI/CE (ingrese solo cifras)</label>
                <input type="text" name="billing_rut" id="billing_rut" class="postbox"  value="<?php echo $billing_rut; ?>" />
            </p>
            <?php
        }
    }
}
