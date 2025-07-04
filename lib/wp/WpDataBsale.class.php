<?php
//namespace woocommerce_bsalev2\lib\wp;
/**
 * similar a WpUtils, devuelve datos de wp o wc que otras clases usarán
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of WpUtils
 *
 * @author Lex
 */
class WpDataBsale
{

    /**
     * devuelve el stock disponible para un prodcuto de bsale
     * @param type $sku
     * @param type $bsale_sucursal_id
     * @param type $is_suma_stock_and_mover_stock, si se debe mover el stock de otras sucursales a sucursal principal
     */
    public function get_product_stock($sku, $bsale_sucursal_id, $is_suma_stock_and_mover_stock = false,
            $stock_expected = -1, $order_number = 0, $order_id = 0)
    {
        $prod_bsale = new ProductoBsale();

        $stock_disponible_sucursal = $prod_bsale->get_stock_producto($sku, $bsale_sucursal_id);

//en este caso falta stock, debo usar gd para moverlo desde otras sucursales
        if( $is_suma_stock_and_mover_stock && $stock_disponible_sucursal < $stock_expected )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($result, __METHOD__ . " stock dispoinble en suc id=$bsale_sucursal_id es $stock_disponible_sucursal pero "
                        . "se necesitan $stock_expected, así que debo sacar stock desde otras sucursales.");
            }
            $stock_faltante = $stock_expected - $stock_disponible_sucursal; //sucursales para mover stock

            $stock_disponible_sucursal = $this->mover_stock_to_sucursal($sku, $stock_faltante, $bsale_sucursal_id,
                    $order_number, $order_id);
        }

        return $stock_disponible_sucursal;
    }

    /**
     * obtiene stocks de otras sucursales y los mueve a sucursal $bsale_sucursal_id_to_mover_stock
     * usando guias de despacho
     * @param type $sku
     * @param type $stock_faltante
     * @param type $bsale_sucursal_id_to_mover_stock
     */
    public function mover_stock_to_sucursal($sku, $stock_faltante, $bsale_sucursal_id_to_mover_stock,
            $order_number, $order_id)
    {
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . "params: (sku= '$sku', stock a mover= $stock_faltante, "
                    . "suc bsale id a mover stock=$bsale_sucursal_id_to_mover_stock, pedido wc: #$order_number)");
        }

        //este se obtendrá después de mover con gd el stock desde otras sucursales
        $stock_disponible_sucursal_matriz = 0;

        $prod_bsale = new ProductoBsale();

        //sucursales desde las cuales sacar stock
        $otras_sucursales = Funciones::get_sucursales_bsale();

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($otras_sucursales, __METHOD__ . " sucursales bsale a recorrer para mover stock");
        }

        //listado de sucursales y stocks
        $stock_suc_id = array();

        //recorro sucursales y pregunto cuanto stock hay dispoinble en cada una, 
        //hasta alcanzar $stock_faltante
        foreach( $otras_sucursales as $suc_id )
        {
            $stock_disponible_sucursal = $prod_bsale->get_stock_producto($sku, $suc_id);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " stock dispoinble para sku = '$sku' en suc id= $suc_id: $stock_disponible_sucursal unidades");
            }

            //guardo en array, en caso de que tenga que mover el stock desde varias sucursales
            //a suc matriz
            $stock_suc_id[$suc_id] = $stock_disponible_sucursal;

            //si en una sucursal está todo el stock disponible, lo saco desde allí
            if( $stock_disponible_sucursal >= $stock_faltante )
            {
                //genero GD                
                $result = $this->emitir_gd($suc_id, $bsale_sucursal_id_to_mover_stock, $sku, $stock_faltante, $order_number, $order_id);
                
                //nuevamente, obtengo el stock desde sucursal matriz
                $stock_disponible_sucursal_matriz = $prod_bsale->get_stock_producto($sku, $bsale_sucursal_id_to_mover_stock);

                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($result, __METHOD__ . " nuevo stock en sucursal donde se emitirá el dte: "
                            . "$stock_disponible_sucursal_matriz (stock faltante era $stock_faltante)");
                }

                //actualizo stock faltante
                $stock_faltante -= $stock_disponible_sucursal_matriz;
                break;
            }
        }

        return $stock_disponible_sucursal_matriz;
    }

    public function emitir_gd($suc_id_from, $suc_id_to, $sku, $cantidad, $order_number, $order_id)
    {
        //producto
        $productos_arr = array();

        $arrauxitems = array(
            'quantity' => $cantidad,
            'code' => (string) $sku,
                //  'netUnitValue' => $precio_neto,
                //  'taxId' => "[" . IMPUESTO_IVA_ID . "]",
                //  'discount' => 0,
                // 'comment' => $nombre . $comment_wapo_txt
        );

        $producto_arr[] = $arrauxitems;

        $old_timezone = date_default_timezone_get();

        if( Funciones::get_pais() === 'PE' )
        {
            $timezone = 'America/Lima';
        }
        else
        {
            $timezone = 'America/Santiago';
        }

        date_default_timezone_set($timezone);
        ini_set("date.timezone", $timezone);

        $hoy = date('Y-m-d');
        $gmt_date = strtotime($hoy);
        $gmt_date_expiracion = $gmt_date;

        date_default_timezone_set($old_timezone);
        ini_set("date.timezone", $old_timezone);

        $prefix = Funciones::get_order_prefix();

        //usar para indicar el nro de orden asociado
        $dynamicAttributes [] = array(
            'description' => utf8_encode('muevo productos para emitir docto para pedido ' . $prefix . $order_number),
            'dynamicAttributeId' => Funciones::get_dinam_attr_gd() ); //comentario

        $arr = array(
            'documentTypeId' => Funciones::get_gd_id(),
            'officeId' => $suc_id_from,
            'destinationOfficeId' => $suc_id_to,
            'shippingTypeId' => DESPACHO_GD_TIPO_DESPACHO,
            'sendEmail' => 0, //gd no se envia al cliente
            'dispatch' => 2, //despachar stock ahora
            'strictStockValidation' => 1,
            'dynamicAttributes' => $dynamicAttributes,
            'emissionDate' => $gmt_date,
            'expirationDate' => $gmt_date_expiracion,
            'declareSii' => Funciones::get_declare_sii(),
            'details' => $productos_arr,
        );

        $seller_id = Funciones::get_seller_id();

        if( $seller_id > 0 )
        {
            $arr['sellerId'] = $seller_id;
        }


        //cambio declareSii por declare
        if( Funciones::get_pais() === 'PE' )
        {
            $arr['declare'] = $arr['declareSii'];
            unset($arr['declareSii']);
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($arr, __METHOD__ . " arr para emitir gd. Emito GD");
        }

        $gd = new GuiaDespacho();
        $result = $nv->postGD($arr, $order_id);

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($result, __METHOD__ . " resultado post GD");
        }
        
        return $result;
    }

    public function get_shipping_methods_wc($enabled = false, $formatted = true)
    {
        $active_methods = array();
        $shipping_methods = WC()->shipping()->get_shipping_methods();

        foreach( $shipping_methods as $id => $shipping_method )
        {
//Funciones::print_r_html($shipping_method, "get_shipping_methods_wc loop:");
//if( isset($shipping_method->enabled) && 'yes' === $shipping_method->enabled )
            {
                $active_methods[$id] = array(
                    'id' => $id,
                    'title' => $shipping_method->method_title,
                    'description' => $shipping_method->method_description,
                    'enabled' => $shipping_method->enabled,
                    'tax_status' => $shipping_method->tax_status,
                );
            }
        }

        return $active_methods;
    }

    /**
     * devuelve listado de todos los medios de pago instalados en woocommerce
     * @param type $enabled (devuelve solo los enabled)
     * fomrmatted: solo devuelve id, title y enabled
     */
    public function get_payments_wc($enabled = false, $formatted = true)
    {
        $payment_gateways_obj = new WC_Payment_Gateways();
        $enabled_payment_gateways = $payment_gateways_obj->payment_gateways();

        $arr = array();

        foreach( $enabled_payment_gateways as $p )
        {
            if( $enabled && $p->enabled !== 'yes' )
            {
                continue;
            }
            if( $formatted )
            {
                $arr[] = array( 'id' => $p->id, 'title' => $p->title, 'enabled' => $p->enabled );
            }
            else
            {
                $arr[] = (array) $p;
            }
        }
        return $arr;
    }

    /**
     * if user has required role
     * @param type $user_id
     * @param type $role_required
     * @return type
     */
    public function is_role_user($user_id, $role_required)
    {
        $user_info = get_userdata($user_id);
        $user_roles = implode(', ', $user_info->roles);

        return strcasecmp($role_required, $user_roles) == 0;
    }

    /**
     * para valores de comuna creados con select de plugin de checkout
     * @param type $order_id
     * @param type $comuna_key
     * @param type $pais
     * @return type
     */
    public function get_comuna_value_checkout_field($order_id, $comuna_key, $pais = 'CL')
    {
        $fields = WC()->countries->get_address_fields(WC()->countries->get_base_country(), 'billing_');
        foreach( $fields as $name => $field )
        {
            if( $name !== $comuna_key )
            {
                continue;
            }
            $value = get_post_meta($order_id, $name, true);
            $value = empty($value) ? get_post_meta($order_id, "_$name", true) : $value;

            if( !empty($value) )
            {
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($field, "field checkout name='$name', value='$value'");
                }


                $type = isset($field['type']) ? $field['type'] : false;

                if( $type === 'select' || $type === 'radio' )
                {
                    $options = isset($field['options']) ? $field['options'] : array();

                    if( isset($options[$value]) && !empty($options[$value]) )
                    {
                        $value = $options[$value];
                    }

                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html("texto para key $comuna_key='$value'");
                    }
                    return $value;
                }
            }
        }
    }

    public function get_comuna_value($comuna_key, $pais = 'CL')
    {
        if( empty($pais) )
        {
            $pais = Funciones::get_pais();
        }

        $countries_obj = new WC_Countries();
        $default_county_states = $countries_obj->get_states($pais);

        if( isset($_REQUEST['test_dte']) )
        {
//Funciones::print_r_html($default_county_states, "get_comuna_value($comuna_key, $pais, comunas ");
        }

        $res = isset($default_county_states[$comuna_key]) ? $default_county_states[$comuna_key] : null;

        return $res;
    }

    /**
     * remove button if there is no chosen shipping method
     */
    public function disable_checkout_button_no_shipping()
    {

        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

// remove button if there is no chosen shipping method
        if( empty($chosen_shipping_methods) )
        {
            remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
        }
    }

    public function required_chosen_shipping_methods()
    {
        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

        if( is_array($chosen_shipping_methods) && count($chosen_shipping_methods) <= 0 )
        {
// Display an error message
            wc_add_notice(__("Se debe elegir un medio de envío antes de ir al checkout."), 'error');
        }
    }

    /**
     * filtra los medios de de envio colocados en la configuración del plugin y que fueron asociados 
     * a sucursales de Bsale. Si el medio de envio no tiene stock en la sucursal de Bsale asociada, no se muestra
     * 
     * @param type $rates
     * @param type $package
     * @return type
     */
    public function filter_shipping_sucursales($rates, $package)
    {
        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("filter_shipping_sucursales inicio");
        }

        $utils = new Utils();
//arreglo: titulo medio envio wc=>id sucursal bsale
        $shipings_arr = $utils->get_array_medios_envio();

//sino hay envios, no se hace nada
        if( !is_array($shipings_arr) || count($shipings_arr) <= 0 )
        {
            return $rates;
        }
//arreglo con productos del carro: sku=>cantidad
        $prods_arr = array();

// Loop through cart items Checking for the defined shipping method
        foreach( $package['contents'] as $cart_item )
        {
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'];
            $quantity = $cart_item['quantity'];
            $sku = $cart_item['data']->get_sku();

//solo prods con sku y cantidad > 0
            if( empty($sku) || $quantity < 0 )
            {
                continue;
            }

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("filter_shipping_sucursales cart item pid=$product_id, varid= $variation_id, sku= '$sku', cantidad: $quantity");
            }

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
//las sucursales e Bsale tienen stock para todos los productos del carro?
        $shipings_to_display_arr = $this->get_shipping_sucursal_stock($shipings_arr, $prods_arr);

        $rates_new = array();
// Loop through shipping methods
        foreach( $rates as $rate_key => $rate )
        {
            $display = true;
            $label = $utils->filter_chars($rate->label);
            $label = strtolower($label);

            if( isset($_REQUEST['test_dte']) )
            {
                Funciones::print_r_html("filter_shipping_sucursales rate para key '$rate_key' name = '$label'");
            }

            if( isset($shipings_to_display_arr[$label]) )
            {
                $display = ($shipings_to_display_arr[$label] == true || $shipings_to_display_arr[$label] > 0) ? true : false;

                if( isset($_REQUEST['test_dte']) )
                {
                    $displaystr = ($display) ? 'SÍ' : 'NO';
                    Funciones::print_r_html("filter_shipping_sucursales name = '$label' se muestra en checkout: $displaystr");
                }
            }

//se debe mostrar este shipping method en el checkout?
            if( $display !== false )
            {
                $rates_new[$rate_key] = $rate;
            }
        }

        return $rates_new;
    }

    public function bsale_display_stock_product_page($price)
    {
        global $woocommerce_loop;
        global $product;

        if( is_product() && !$woocommerce_loop['name'] == 'related' && $product->is_in_stock() )
        {
            $stock = $product->get_stock_quantity();
// $sku = $product->get_sku() ;
            $texto_after_precio = "<span class='product_stock'>Internet: <strong class='stock_q'>$stock</strong></span>";

            return $price . $texto_after_precio;
        }
        else
        {
            return $price;
        }
    }

    public function call_url_curl($url)
    {
        if( empty($url) )
        {
            return '';
        }
        $cliente = curl_init();
        curl_setopt($cliente, CURLOPT_URL, $url);
        curl_setopt($cliente, CURLOPT_HEADER, 0);
        curl_setopt($cliente, CURLOPT_RETURNTRANSFER, true);

        $contenido = curl_exec($cliente);
        curl_close($cliente);

        return $contenido;
    }

    /**
     * devuelve arrayd id, title deshipping method usado en la order
     * @param type $order
     * @param type $order_id
     */
    public function get_order_shipping_method($order, $order_id = 0)
    {
        $arraux = array();

        if( empty($order) && $order_id > 0 )
        {
            $order = wc_get_order($order_id);
        }

        if( empty($order) )
        {
            return $arraux;
        }

// Iterating through order shipping items
        foreach( $order->get_items('shipping') as $item_id => $shipping_item_obj )
        {
// Get the data in an unprotected array
            $shipping_item_data = $shipping_item_obj->get_data();

            return $shipping_item_data;

            /* $shipping_data_id = $shipping_data['id'];
              $shipping_data_order_id = $shipping_data['order_id'];
              $shipping_data_name = $shipping_data['name'];
              $shipping_data_method_title = $shipping_data['method_title'];
              $shipping_data_method_id = $shipping_data['method_id'];
              $shipping_data_instance_id = $shipping_data['instance_id'];
              $shipping_data_total = $shipping_data['total'];
              $shipping_data_total_tax = $shipping_data['total_tax'];
              $shipping_data_taxes = $shipping_data['taxes']; */
        }
    }

    /**
     * devuelve el arreglo $shippings_arr de esta manera: titulo shipping method wc=>true|false (mostrar o no en checkout)
     * @param type $shippings_arr: titulo shipping method wc->id sucursal bslae
     * @param type $productos_arr sku=>cantidad en el carro
     */
    public function get_shipping_sucursal_stock($shippings_arr, $productos_arr)
    {
        if( count($shippings_arr) <= 0 )
        {
            return $shippings_arr;
        }

        $shippings_to_display_arr = $shippings_arr;

        $prod_bsale = new ProductoBsale();
//recorro sucursales, preguntando por el stock de todos los productos del carro
        foreach( $shippings_arr as $ship_title => $sucursal_id )
        {
            $display_ship = true;
//por default, shipping method se muestra
            $shippings_to_display_arr[$ship_title] = $display_ship;

            if( isset($_REQUEST['test_dte']) )
            {
// Funciones::print_r_html("get_shipping_sucursal_stock, recorro shipping method '$ship_title'=> sucursal bsal e#$sucursal_id, buscando stocks");
            }

//recorro todos los productos y veo si hay stock en esta sucursal
            foreach( $productos_arr as $sku => $cantidad )
            {
                if( isset($_REQUEST['test_dte']) )
                {
// Funciones::print_r_html("----get_shipping_sucursal_stock, busco stock de sku='$sku', cantidad= $cantidad");
                }
                $stock_disponible_sucursal = $prod_bsale->get_stock_producto($sku, $sucursal_id);

//si no hay stock suficiente, no se debe mostrar este medio de envio en el checkout de wc
                if( $stock_disponible_sucursal < $cantidad )
                {
                    $display_ship = false;
                    $shippings_to_display_arr[$ship_title] = $display_ship;

                    if( isset($_REQUEST['test_dte']) )
                    {
                        Funciones::print_r_html("----get_shipping_sucursal_stock '$ship_title', sku='$sku', no tiene stock suficiente (disponible: $stock_disponible_sucursal, se oculta");
                    }
                    break;
                }

                if( isset($_REQUEST['test_dte']) )
                {
                    Funciones::print_r_html("----get_shipping_sucursal_stock '$ship_title', sku='$sku', SÍ tiene stock suficiente (disponible: $stock_disponible_sucursal, se muestra");
                }
            }
        }


        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($shippings_to_display_arr, "get_shipping_sucursal_stock, sucursales a mostrar:");
        }

        return $shippings_to_display_arr;
    }

    /**
     * devuelve arreglo: titulo shipping method wc=>state:true|false (mostrar o no en checkout)
     *  shipping method wc=>prods_stock[sku=>stock en bsale]
     * 
     * @param type $shippings_arr
     * @param type $productos_arr
     * @return boolean
     */
    public function get_shipping_sucursal_stock2($shippings_arr, $productos_arr)
    {
        if( count($shippings_arr) <= 0 )
        {
            return $shippings_arr;
        }

        $shippings_to_display_arr = $shippings_arr;

        $prod_bsale = new ProductoBsale();
//recorro sucursales, preguntando por el stock de todos los productos del carro
        foreach( $shippings_arr as $ship_title => $sucursal_id )
        {
            $display_ship = true;
            $shippings_to_display_arr[$ship_title] = array();
//por default, shipping method se muestra
            $shippings_to_display_arr[$ship_title]['state'] = $display_ship;
//prods con stock
            $shippings_to_display_arr[$ship_title]['prods_con_stock'] = array();
//prods sin stock
            $shippings_to_display_arr[$ship_title]['prods_sin_stock'] = array();

            if( isset($_REQUEST['test_dte']) )
            {
// Funciones::print_r_html("get_shipping_sucursal_stock, recorro shipping method '$ship_title'=> sucursal bsal e#$sucursal_id, buscando stocks");
            }

//recorro todos los productos y veo si hay stock en esta sucursal
            foreach( $productos_arr as $sku => $cantidad )
            {
                if( isset($_REQUEST['test_dte']) )
                {
// Funciones::print_r_html("----get_shipping_sucursal_stock, busco stock de sku='$sku', cantidad= $cantidad");
                }
                $stock_disponible_sucursal = $prod_bsale->get_stock_producto($sku, $sucursal_id);

//si no hay stock suficiente, no se debe mostrar este medio de envio en el checkout de wc
                if( $stock_disponible_sucursal < $cantidad )
                {
                    $display_ship = false;
//no se debe mostrar, pues falta al menos un prod del carro en esta sucursal de bsale
                    $shippings_to_display_arr[$ship_title]['state'] = $display_ship;

                    $shippings_to_display_arr[$ship_title]['prods_sin_stock'][] = array( 'sku' => $sku, 'stock' => $stock_disponible_sucursal );

                    if( isset($_REQUEST['test_dte']) )
                    {
                        Funciones::print_r_html("----get_shipping_sucursal_stock '$ship_title', sku='$sku', no tiene stock suficiente (disponible: $stock_disponible_sucursal, se oculta");
                    }
                }
                else
                {
                    $shippings_to_display_arr[$ship_title]['prods_con_stock'][] = array( 'sku' => $sku, 'stock' => $stock_disponible_sucursal );
                }
            }
        }


        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($shippings_to_display_arr, "get_shipping_sucursal_stock, sucursales a mostrar:");
        }

        return $shippings_to_display_arr;
    }

    /**
     * suvursal de la comuna de facturac donde debe emitirse el dte
     * @param type $billing_comuna
     */
    public function get_sucursal_comuna_to_send($billing_comuna)
    {
//debo emitr boleta segun comuna de fact?
        if( !Funciones::is_emitir_dte_segun_comuna() )
        {
            return 0;
        }
        $utils = new Utils();

        $comunas_sucursal_id_arr = Funciones::get_comunas_sucursales_dte();

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html($comunas_sucursal_id_arr, "get_sucursal_comuna_to_send($billing_comuna), listado de comunas=sucursal id");
        }
//$billing_comuna es la comuna de facturacion
        $billing_comuna_send = strtolower($billing_comuna);
        $billing_comuna_send = $utils->filter_chars($billing_comuna_send);

        $sucursal_comuna_id = isset($comunas_sucursal_id_arr[$billing_comuna_send]) ? $comunas_sucursal_id_arr[$billing_comuna_send] : 0;

        if( isset($_REQUEST['test_dte']) )
        {
            Funciones::print_r_html("get_sucursal_comuna_to_send($billing_comuna), sucursal bsale donde emitir dte: $sucursal_comuna_id");
        }

        return $sucursal_comuna_id;
    }

    /**
     * resync stock y precio de este producto
     * @param type $product_id
     */
    public function product_update($meta_id, $object_id, $meta_key, $_meta_value)
    {
        if( !is_admin() )
        {
            return;
        }
        if( $meta_key === '_sku' && !empty($_meta_value) )
        {
//die("product_update($meta_id, $object_id, $meta_key, $_meta_value)");
            $this->product_sync($object_id);
        }
    }

    public function product_variation_update($product_id)
    {
        $this->product_sync($product_id);
    }

    public function product_sync($product_id)
    {
        if( !is_admin() )
        {
            return;
        }
//get sku
        $sku = get_post_meta($product_id, '_sku', true);
        $post_id = $product_id;

        if( empty($sku) )
        {
            delete_post_meta($post_id, 'bsale_info');
            return false;
            /* $product = wc_get_product($product_id); //wc_get_product

              if( !$product )
              {
              return false;
              }
              $parent_id = $product->get_parent_id();

              if( $parent_id <= 0 )
              {
              $msg = "Producto no tiene sku";
              update_post_meta($post_id, 'bsale_info', $msg);
              }
              else
              {
              $parent_id = $product->get_parent_id();

              if( $parent_id > 0 )
              {
              $msg = "Variacion #$product_id no tiene sku";
              $post_id = $parent_id;
              update_post_meta($post_id, 'bsale_info_variacion', $msg);
              }
              }

              return false; */
        }

        $product = wc_get_product($product_id);

        if( !$product )
        {
            return false;
        }

        $parent_id = $product->get_parent_id();

//envio file .json para actualizar
//obtengo datos de varian a partir de sku
        $vars = new VariantesProductoBsale();

        $variacion_resp = $vars->get_variacion_by_sku($sku);

        if( !isset($variacion_resp['id']) )
        {
            if( $parent_id <= 0 )
            {
                $msg = "Sku '$sku' no está en Bsale.";
                update_post_meta($post_id, 'bsale_info', $msg);
            }
            else
            {
                $msg = "$parent_id: sku '$sku' de variacion #$product_id no está en Bsale.";
                update_post_meta($parent_id, 'bsale_info_variacion', $msg);
            }

            return false;
        }

        $variacion_id = $variacion_resp['id'];

//arreglo
        $post_vars = '{"cpnId":10615,"resource":"/v2/variants/' . $variacion_id . '.json","resourceId":"' . $variacion_id . '","topic":"variant","action":"put","send":1553289004}';
//json
        $post_vars_array = json_decode($post_vars, true);

        $topic = $post_vars_array['topic'];
        $post_vars = trim($post_vars);

        $hoy = date('Y-m-d');
//dokan archivos deben llevar: {client_id}_{$hoy}_{$topic}_{$resourceId}
        $fichero = dirname(__FILE__) . "/../../webhooks/notificaciones/{$hoy}_autoupdate_{$topic}_{$variacion_id}.json";

//guardo json
        file_put_contents($fichero, $post_vars);

//limpio avisos de error
        if( $parent_id > 0 )
        {
            $post_id = $parent_id;
        }

        delete_post_meta($post_id, 'bsale_info');
        delete_post_meta($post_id, 'bsale_info_variacion');

//update_post_meta($post_id, 'bsale_info', $fichero);
// die();
    }

    /**
     * sincroniza stock y sky del prodcuto y sus variacioens
     * @param type $product_id
     * @return boolean
     */
    public function product_sync_from_bsale($product_id, $contains = null)
    {
        $arraux = array();

//si contains es string, lo paso a array
        if( $contains != null && !is_array($contains) )
        {
            $contains = array( $contains );
        }

        $str_error = '';

        $post_id = $product_id;
        $arraux['product_id'] = $product_id;

        $product = wc_get_product($product_id);

        if( !$product )
        {
            $str_error .= "no se ha encontrado prodcuto con id=$product_id.";

            $arraux['error'] = $str_error;
            $arraux['result'] = false;
            return $arraux;
        }

//get sku
        $sku = get_post_meta($product_id, '_sku', true);

//reviso si este producto em,pieza con los prefijos de sku o no
        if( !empty($contains) && !empty($sku) )
        {
            foreach( $contains as $c )
            {
                $c = trim($c);

                if( !empty($c) && stripos($sku, $c) === false )
                {
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html("sku '$sku' no contiene '$c', se omite");
                        $str_error .= "sku '$sku' no contiene '$c', se omite";

                        $arraux['error'] = $str_error;
                        $arraux['result'] = false;
                        return $arraux;
                    }
                }
                elseif( isset($_REQUEST['param']) && !empty($c) )
                {
                    Funciones::print_r_html("sku '$sku' sí contiene '$c', se actualiza");
                }
            }
        }


        $arraux['product_sku'] = $sku;
        $arraux['product_name'] = $product->get_name();
        $arraux['product_stock'] = $product->get_stock_quantity();
        $arraux['product_stock_status'] = $product->get_stock_status();
        $arraux['product_normal_price'] = $product->get_price();

//skus a actualizar
        $skus_arr = array();

        if( !empty($sku) )
        {
            $skus_arr[$product_id] = $sku;
        }

        $variations = $product->get_children();

        if( is_array($variations) )
        {
            foreach( $variations as $variacion_id )
            {
                $sku = get_post_meta($variacion_id, '_sku', true);

//agrego a los skus a actualizar
                if( !empty($sku) )
                {
                    $skus_arr[$variacion_id] = $sku;
//Funciones::print_r_html("variacion #$variacion_id, sku=$sku");
                }
            }
        }

        $arraux['list_skus'] = $skus_arr;

        $bsale = new Bsale();
        $vars = new VariantesProductoBsale();

//limpio metas
//update_post_meta($post_id, 'bsale_info', '');
// update_post_meta($post_id, 'bsale_info_variacion', '');

        $str_post_meta = '';
        $str_post_meta_variacion = '';
//Funciones::print_r_html($skus_arr, "skus_arr");
//ahora recorro arrays de sku a actualizar y actualizo
        foreach( $skus_arr as $var_id => $sku_to_update )
        {

            if( empty($sku_to_update) )
            {

                $str_error .= "sku en blanco, por lo que sus datos no se actualizaron.";

//agrego avisos de error en campos meta
                if( $var_id == $post_id )
                {
                    
                }
                else
                {
//sku de variaciones
                    $str_post_meta_variacion .= "variacion #$var_id no tiene sku, no se actualizó.<br/>";
                }
                continue;
            }
//Funciones::print_r_html("variacion $id => $sku_to_update");
            $variacion_resp = $vars->get_variacion_by_sku($sku_to_update);

            if( isset($_REQUEST['param']) )
            {
//Funciones::print_r_html($variacion_resp, "get_variacion_by_sku($sku_to_update), respuesta");
            }

            if( !isset($variacion_resp['id']) )
            {
                if( isset($_REQUEST['param']) )
                {
//Funciones::print_r_html($variacion_resp, "ERROR get_variacion_by_sku($sku_to_update), no viene id:");
                }
                $str_error .= "sku '$sku_to_update' no pertenece a ningún producto de Bsale. No se sincronizará.";

//agrego avisos de error en campos meta
                if( $var_id == $post_id )
                {
//sku del producto
                    $str_post_meta .= "Sku '$sku_to_update' no pertenece a ningún producto de Bsale. No se sincronizará.<br/>";
                }
                else
                {
//sku de variaciones
                    $str_post_meta_variacion .= "sku '$sku_to_update' de variacion #$var_id no pertenece a ningún producto de Bsale. No se sincronizará.<br/>";
                }
                continue;
            }


            $variante_id = $variacion_resp['id'];
//proceso variacion
            $post_vars_array = array( 'cpnId' => $variante_id,
                'resource' => "/v2/variants/$variante_id.json", 'resourceId' => $variante_id,
                'topic' => 'variant', 'action' => 'put',
                'send' => '1553289004', );

            global $global_file_update_product;
            $global_file_update_product = 'self_sync';

            $bsale->do_variant($post_vars_array, true, true);
        }

//coloco avisos, si es que hay   
        if( !empty($str_post_meta) )
        {
            update_post_meta($post_id, 'bsale_info', $str_post_meta);
        }
        else
        {
            delete_post_meta($post_id, 'bsale_info');
        }

        if( !empty($str_post_meta_variacion) )
        {
            update_post_meta($post_id, 'bsale_info_variacion', $str_post_meta_variacion);
        }
        else
        {
            delete_post_meta($post_id, 'bsale_info_variacion');
        }


        if( !empty($str_post_meta) || !empty($str_post_meta_variacion) )
        {
//producto con al menos un sku que no está en Bsale
            update_post_meta($post_id, 'bsale_missed', 1);
        }
        else
        {
//sku está en bsale, se borra mensaje
            delete_post_meta($post_id, 'bsale_missed');
        }


// update_post_meta($post_id, 'bsale_info', "from sync.");

        $arraux['error'] = $str_error;
        $arraux['result'] = empty($str_error) ? true : false;
        return $arraux;
    }

    /**
     * avisos de Bsale cuando un sku no existe en Bsale
     * @global type $post
     * @return type
     */
    public function post_edit_info()
    {
        $screen = get_current_screen();
        if( /* $screen->post_type !== 'product' || */ $screen->id !== 'product' )
        {
            return;
        }

        global $post;
        $post_id = $post->ID;

        $str = ''; //"test post id=$post_id";

        $bsale_info = get_post_meta($post_id, 'bsale_info', true);
        $bsale_info_variacion = get_post_meta($post_id, 'bsale_info_variacion', true);

        if( !empty($bsale_info) )
        {
            $str .= "<p>Integración Bsale: <strong class='bsale_info' style='color: #dc3232'>$bsale_info</strong></p>";
        }
        if( !empty($bsale_info_variacion) )
        {
            $str .= "<p>Integración Bsale: <strong class='bsale_info' style='color: #dc3232'>$bsale_info_variacion</strong></p>";
        }

        $class = 'notice-error ';

        if( !empty($str) )
        {
            ?>
            <div class="notice <?php echo $class; ?>">
                <p><?php echo $str; ?></p>
            </div>
            <?php
        }
    }
}
