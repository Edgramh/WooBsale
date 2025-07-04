<?php

/* * ejemplo actions para precios mayoristas* */

/**
 * 
 * @param type $post_id int
 * @param type $precios_arr array
 */
function bsale_filter_update_precios_mayoristas_action($post_id, $precios_arr)
{
    return; //sacar si se usaran precios mayoristas

    $precio_normal = isset($precios_arr['precio_may_normal']) ? $precios_arr['precio_may_normal'] : -1;
    $precio_oferta = isset($precios_arr['precio_may_oferta']) ? $precios_arr['precio_may_oferta'] : -1;

    $precio_especial2 = $precio_normal;
    $precio_especial3 = $precio_oferta;


    if( isset($_REQUEST['param']) || ( PHP_SAPI === 'cli' || PHP_SAPI === 'cgi-fcgi' ) )
    {
        Funciones::print_r_html($precios_arr, "bsale_filter_update_precios_mayoristas_action, para post id=$post_id");
    }

    //plugin Wholesale For WooCommerce Lite, 
    ////dos listas de precio: para venta desde 12: $precio_especial2 y 3 unidades: $precio_especial3
    $wholesale_tipo_precio = get_post_meta($post_id, '_wwp_wholesale_type', true);
    $wholesale_cant_minima = get_post_meta($post_id, '_wwp_wholesale_min_quantity', true);
    $wholesale_monto_actual = get_post_meta($post_id, '_wwp_wholesale_amount', true);

    if( isset($_REQUEST['param']) )
    {
        Funciones::print_r_html("bsale_filter_update_precios_mayoristas_action tipo= '$wholesale_tipo_precio', "
                . " $wholesale_cant_minima unidades para post id=$post_id."
                . " antes de procesar precios");
    }

    //solo precios fijos, no % ni desctos
    if( $wholesale_tipo_precio === 'fixed' )
    {


        //solo cantidades 24 y 3 unidades
        if( $wholesale_cant_minima == 24 && $precio_especial2 > 0 )
        {
            update_post_meta($post_id, '_wwp_wholesale_amount', $precio_especial2);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($precios_arr, "bsale_filter_update_precios_mayoristas_action $wholesale_cant_minima unidades para post id=$post_id."
                        . "Antes: $ $wholesale_monto_actual, ahora: $ $precio_especial2");
            }
        }
        elseif( $wholesale_cant_minima == 3 && $precio_especial3 > 0 )
        {
            update_post_meta($post_id, '_wwp_wholesale_amount', $precio_especial3);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($precios_arr, "bsale_filter_update_precios_mayoristas_action $wholesale_cant_minima unidades para post id=$post_id."
                        . "Antes: $ $wholesale_monto_actual, ahora: $ $precio_especial3");
            }
        }
    }

    //enable roled base price
    /* if( function_exists('wc_rbp_update_role_based_price_status') )
      {
      wc_rbp_update_role_based_price_status($post_id, TRUE);

      //si no viene precio o es menos a $1, no hay precio mayorista
      if( $precio_especial2 <= 1 )
      {
      $precio_especial2 = '';
      $precio_especial3 = '';
      }
      else
      {
      $precio_especial3 = $precio_especial3 <= 0 ? '' : $precio_especial3;
      }
      //update_post_meta($post_id, 'wholesale_customer_wholesale_price', $precio_especial2);
      //mayoristas: _enable_role_based_price
      $arr_price_role = array();
      $arr_mayorista = array(
      'regular_price' => "$precio_especial2",
      'selling_price' => "$precio_especial3",
      );

      $arr_price_role['mayoristas'] = $arr_mayorista;

      update_post_meta($post_id, '_role_based_price', $arr_price_role);
      Funciones::print_r_html($arr_price_role, "enable role based add prod simple id=$post_id, set true. Precios mayorista:");
      } */

    /*  $meta_key_mayorista = defined('MAYORISTA_PRECIO_META_FIELD') ? MAYORISTA_PRECIO_META_FIELD : null;

      if( !empty($meta_key_mayorista) )
      {
      update_post_meta($post_id, $meta_key_mayorista, $precio_especial2);
      } */

    /*
      //plugin WooCommerce Tiered Price Table, usando clase
      if( method_exists('PriceManager', 'updateFixedPriceRules') && defined('WC_TIERED_PRICE_AMOUNT') && WC_TIERED_PRICE_AMOUNT > 0 )
      {

      //uso $precio_especial3, descto compra sobre 6 unidades;
      $cantidad = WC_TIERED_PRICE_AMOUNT;
      $amounts = array( 0 => $cantidad );
      $prices = array( 0 => $precio_especial3 );

      if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' )
      {
      Funciones::print_r_html("updateFixedPriceRules, sku= $sku coloco precio $ $precio_especial3 para compras de $cantidad unidades en adelante");
      }

      //PriceManager::updateFixedPriceRules($amounts, $prices, $post_id);
      } */

    //plugin WooCommerce Tiered Price Table, copiando el código de PriceManager::updateFixedPriceRules($amounts, $prices, $post_id);
    //uso $precio_especial3, descto compra sobre 6 unidades;
    /* $cantidad = WC_TIERED_PRICE_AMOUNT;
      $amounts = array( 0 => $cantidad );
      $prices = array( 0 => $precio_especial3 );

      if( isset($_REQUEST['param']) && $_REQUEST['param'] === 'yes' )
      {
      Funciones::print_r_html("updateFixedPriceRules, coloco precio $ $precio_especial3 para compras de $cantidad unidades en adelante");
      }

      $rules = array();
      foreach( $amounts as $key => $amount )
      {
      if( !empty($amount) && !empty($prices[$key]) && !key_exists($amount, $rules) )
      {
      $rules[$amount] = wc_format_decimal($prices[$key]);
      }
      }

      update_post_meta($post_id, '_fixed_price_rules', $rules); */

    //YITH WooCommerce Role Based Prices Premium          

    /*  $precio_normal = get_post_meta($post_id, '_regular_price', true);

      $precio_especial3 = $precio_normal - $precio_especial3;
      $precio_especial2 = $precio_normal - $precio_especial2;

      $precio_especial3 = $precio_especial3 <= 0 ? $precio_normal : $precio_especial3;
      $precio_especial2 = $precio_especial2 <= 0 ? $precio_normal : $precio_especial2;
      $arr_yith = array(
      array(
      'rule_name' => 'Mayorista PRO',
      'rule_role' => 'mayorista_pro',
      'rule_type' => 'discount_val',
      'rule_value' => $precio_especial3,
      ),
      array(
      'rule_name' => 'M',
      'rule_role' => 'mayorista',
      'rule_type' => 'discount_val',
      'rule_value' => $precio_especial2,
      ),
      );
      update_post_meta($post_id, '_product_rules', $arr_yith); */
}

function bsale_filter_update_precios_mayoristas()
{
    do_action('bsale_filter_update_precios_mayoristas');
}

function bsale_filter_add_precios_mayoristas()
{
    do_action('bsale_filter_add_precios_mayoristas');
}

//inicio custom actions
//bsale_filter_update_precios_mayoristas();
//bsale_filter_add_precios_mayoristas();

add_action('bsale_filter_update_precios_mayoristas', 'bsale_filter_update_precios_mayoristas_action', 10, 2);
add_action('bsale_filter_add_precios_mayoristas', 'bsale_filter_update_precios_mayoristas_action', 10, 2);

/* add_action('plugins_loaded', 'bsale_my_late_loader');

  function bsale_my_late_loader()
  {
  if( ( PHP_SAPI === 'cli' || PHP_SAPI === 'cgi-fcgi' ) )
  {
  Funciones::print_r_html("bsale_my_late_loader");
  }

  add_action('bsale_filter_update_precios_mayoristas', 'bsale_filter_update_precios_mayoristas_action', 10, 2);
  add_action('bsale_filter_add_precios_mayoristas', 'bsale_filter_update_precios_mayoristas_action', 10, 2);
  } */

//wc
function bsale_filter_sucursal_name($sucursal)
{
    /* if( strcasecmp($sucursal, 'BODEGA CENTRAL') == 0 )
      {
      $sucursal = 'INTERNET';
      } */
    return $sucursal;
}

function bsale_filter_sucursal_html($html)
{
    //$html = str_ireplace('bodega central', $replace, $html);
    return $html;
}
