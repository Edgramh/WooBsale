<?php

/* sync de datos de todos los prods bsale hacia woocommerce */
require_once dirname(__FILE__) . '/../lib/Autoload.php';

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}


error_reporting(1);
ini_set('display_errors', 1);

//paso a ejecutar
$step = isset($_GET['action_param']) ? $_GET['action_param'] : '';

if( empty($step) )
{
    $result = array( 'error' => 'No hay paso a ejecutar' );

//$arraux = array( 'result' => $result );
    $data = json_encode($result, JSON_UNESCAPED_UNICODE);

    echo($data);
    exit(0);
}


//$arraux = array( 'result' => $result );
$data = json_encode($_GET, JSON_UNESCAPED_UNICODE);

//steps que por ahora sí sincronizan
$data_skip = array( 'get_suc', 'get_lp', 'get_prod_types', 'get_prod_types_attr',
    'get_prods', 'save_prods', 'get_vars', 'save_vars', 'get_prices', 'save_prices',
    'get_stocks', 'save_stocks', 'get_prods_wc',
    'save_prods_wc',
    'crear_cons',
    'save_cons',
    'save_prices_cons',
    'save_stocks_cons',
    'save_stocks_sucurs_cons', 'sync_update_prods_wc' );

/* if( !in_array($step, $data_skip) )
  {
  echo($data);
  exit(0);
  } */

switch( $step )
{
    case 'get_lp':
        $prod = new ListasPrecioBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Listas descargadas: $res</p>");
        exit(0);

    case 'get_suc':
        $prod = new SucursalesBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Sucursales descargadas: $res</p>");
        exit(0);

    case 'get_prod_types':
        $prod = new TipoProductosBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Tipos de productos descargados: $res</p>");
        exit(0);

    case 'get_prod_types_attr':
        $prod = new AtributosTipoProductosBsale();
        $res = $prod->get_all_attrib_products_save_in_db();

        echo("<p>Atributos de tipos productos descargados: $res</p>");
        exit(0);

    case 'get_prods':
        $prod = new ProductoBsale();

        $res = $prod->get_all();
        echo("<p>Productos descargados ok.</p>");
        exit(0);

    case 'save_prods':
        $prod = new ProductoBsale();
        $res = $prod->save_in_db();
        $cantidad = isset($res['cantidad']) ? $res['cantidad'] : 'ok';
        echo("<p>Productos guardados $cantidad</p>");
        exit(0);

    case 'get_vars':
        $prod = new VariantesProductoBsale();
        $res = $prod->get_all();

        echo("<p>Variaciones descargadas ok</p>");
        exit(0);

    case 'save_vars':
        $prod = new VariantesProductoBsale();
        $res = $prod->save_in_db();

        $cantidad = isset($res['cantidad']) ? $res['cantidad'] : 'ok';
        echo("<p>Variaciones guardadas $cantidad</p>");
        exit(0);

    case 'get_prices':
        //params
        $lp_id = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
        $lp_name = isset($_REQUEST['lp_name']) ? $_REQUEST['lp_name'] : '';
        //precio normal, de oferta, etc
        $lp_tipo = isset($_REQUEST['lp_tipo']) ? $_REQUEST['lp_tipo'] : '';

        $lp_id = ($lp_id <= 0) ? 0 : $lp_id;

        $prod = new PreciosProductosBsale();

        $res = $prod->get_all($lp_id);

        echo("<p>Precios de lista de precios <strong>$lp_id $lp_name</strong> para obtener el <strong>$lp_tipo</strong> descargados: ok</p>");
        exit(0);

    case 'save_prices':
        //params
        $lp_id = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
        $lp_name = isset($_REQUEST['lp_name']) ? $_REQUEST['lp_name'] : '';
        //precio normal, de oferta, etc
        $lp_tipo = isset($_REQUEST['lp_tipo']) ? $_REQUEST['lp_tipo'] : '';

        $lp_id = ($lp_id <= 0) ? 0 : $lp_id;

        $prod = new PreciosProductosBsale();

        $res = $prod->save_in_db($lp_id);

        $cantidad = isset($res['cantidad']) ? $res['cantidad'] : 'ok';
        echo("<p>Precios de lista de precios <strong>$lp_id $lp_name</strong> para <strong>$lp_tipo</strong> guardados: $cantidad</p>");
        exit(0);

    case 'get_stocks':
        //params
        $suc_id = isset($_REQUEST['suc_id']) ? (int) $_REQUEST['suc_id'] : 0;
        $suc_name = isset($_REQUEST['suc_name']) ? $_REQUEST['suc_name'] : '';
        //precio normal, de oferta, etc
        $suc_tipo = isset($_REQUEST['suc_tipo']) ? $_REQUEST['suc_tipo'] : '';

        $suc_id = ($suc_id <= 0) ? 0 : $suc_id;

        $prod = new StockProductosBsale();
        $res = $prod->get_all($suc_id);

        echo("<p>Stocks de sucursal <strong>$suc_id $suc_name</strong> <strong>$suc_tipo</strong> descargados: $res</p>");
        exit(0);

    case 'save_stocks':
        //params
        $suc_id = isset($_REQUEST['suc_id']) ? (int) $_REQUEST['suc_id'] : 0;
        $suc_name = isset($_REQUEST['suc_name']) ? $_REQUEST['suc_name'] : '';
        //precio normal, de oferta, etc
        $suc_tipo = isset($_REQUEST['suc_tipo']) ? $_REQUEST['suc_tipo'] : '';

        $suc_id = ($suc_id <= 0) ? 0 : $suc_id;

        $prod = new StockProductosBsale();
        $res = $prod->save_in_db($suc_id);

        $cantidad = isset($res['cantidad']) ? $res['cantidad'] : 'ok';

        echo("<p>Stocks de sucursal <strong>$suc_id $suc_name</strong> <strong>$suc_tipo</strong> descargados: $cantidad</p>");
        exit(0);

    case 'get_prods_wc':
        $prod = new ProductWP();
        $res = $prod->save_in_file_all_products_wc();

        echo("<p>Productos woocommerce descargados ok</p>");
        exit(0);

    case 'save_prods_wc':
        $prod = new ProductWP();
        $res = $prod->save_in_db();
        $cantidad = isset($res['cantidad']) ? $res['cantidad'] : 'ok';
        echo("<p>Productos woocommerce guardados: $cantidad</p>");
        exit(0);

    case 'check_prods_wc':
        $prod = new ProductWP();
        $res = $prod->check_prods_wc();

        $in_bsale = isset($res['in_bsale']) ? $res['in_bsale'] : '';
        $prods_not_in_bsale_arr = isset($res['not_in_bsale']) ? $res['not_in_bsale'] : array();

        $html = '';
        $i = 1;

        if( !is_array($prods_not_in_bsale_arr) || count($prods_not_in_bsale_arr) <= 0 )
        {
            $prods_not_in_bsale_arr = array( 'todos los skus están en bsale' );
        }

        foreach( $prods_not_in_bsale_arr as $sku )
        {
            $html .= "<tr><td>$i</td><td>$sku</td></tr>";
            $i++;
        }

        echo($html);

        exit(0);

    case 'crear_cons':
        $prod = new ConsolidadoBsale();
        $res = $prod->get_consolidado();

        echo("<p>Crear consolidado de productos Bsale: ok</p>");
        exit(0);

    case 'save_cons':
        $prod = new ConsolidadoBsale();
        $res = $prod->save_in_db();

        echo("<p>Guardar consolidado de productos Bsale: $res</p>");
        exit(0);

    case 'save_prices_cons':
        $prod = new ConsolidadoBsale();
        $res = $prod->get_consolidado_precios();

        echo("<p>Colocar precios en consolidado de productos Bsale: $res</p>");
        exit(0);

    case 'save_stocks_cons':
        $prod = new ConsolidadoBsale();
        $res = $prod->get_consolidado_stocks_save_in_table();

        echo("<p>Colocar stocks en consolidado de productos Bsale: $res</p>");
        exit(0);

    case 'save_stocks_sucurs_cons':
        $prod = new ConsolidadoBsale();
        $limit = 200; //200 variaciones por pasada       

        $res = $prod->save_stock_sucursales_consolidado();
        echo("<p>Colocar en consolidado de productos Bsale el stock por sucursal de cada producto: "
        . "<span class='resultado'>$res</span> de <span class='total'>$limit</span></p>");
        exit(0);

    case 'update_stock_packs':

        $cons = new ConsolidadoBsale();
        $res = $cons->update_stock_packs();

        $cantidad = isset($res['cantidad']) ? $res['cantidad'] : 'ok';
        $skus_arr = isset($res['skus']) ? $res['skus'] : array();

        $skus_str = implode(', ', $skus_arr);

        echo("<p>Stocks de packs colocados: $cantidad. Skus de packs cuyo stock se actualizó: $skus_str</p>");
        exit(0);

    case 'sync_update_prods_wc':

        global $global_file_update_product;
        //indico que esta sync viene de sincronizar todos los prods
        $global_file_update_product = 'sync_stores';

        $prod = new ConsolidadoBsale();
        $limit = 50; //200 variaciones por pasada       

        $res = $prod->sync_update_prods_to_wc($limit);
        $cantidad = isset($res['cantidad']) ? $res['cantidad'] : '-1';
        $skus_arr = isset($res['skus']) ? $res['skus'] : array();

        $skus_str = implode(', ', $skus_arr);

        //echo("<p><strong>Actualizando datos de productos Bsale hacia woocommerce: <span class='resultado'>$cantidad</span> de <span class='total'>$limit</span></strong></p>");

        if( $cantidad > 0 )
        {
            //¿uso precios mayoristas?
            $is_update_mayor_normal = Funciones::get_lp_mayorista_normal_bsale() > 0;
            $is_update_mayor_oferta = Funciones::get_lp_mayorista_oferta_bsale() > 0;

            $is_update_normal = Funciones::is_update_product_price();
            $is_update_oferta = Funciones::is_update_product_price_desc();

            $is_update_stock = Funciones::is_update_product_stock();

            $rows_arr = array();
            $i = 1;

            $stock_ilimitado = Funciones::get_stock_ilimitado_prod_bsale();

            foreach( $skus_arr as $prod )
            {
                $sku = $prod['variant_code'];
                $stock = $prod['variant_stock']['variant_total_stock'];
                $precio_normal = $prod['variant_price']['price'];
                $precio_oferta = $prod['variant_price']['price_desc'];
                $price_desc2 = $prod['variant_price']['price_desc2'];
                $price_desc3 = $prod['variant_price']['price_desc3'];

                $td_arr = array();

                //solo en la primera row agrego cantidad
                if( $i == 1 )
                {
                    $td_arr[] = "<td><span class='resultado' style='display:none;'>$cantidad</span>"
                            . "<span class='total' style='display:none;'>$limit</span>$i</td>"
                            . "<td><strong>$sku</strong></td>";
                }
                else
                {
                    $td_arr[] = "<td>$i</td><td><strong>$sku</strong></td>";
                }


                if( $is_update_stock )
                {
                    //si stock es limitado, aparecerá este aviso en lugar de la cantidad de stock
                    $stock = ($stock == $stock_ilimitado) ? 'stock ilimitado' : $stock;

                    $td_arr[] = "<td class='p s'>$stock</td>";
                }
                if( $is_update_normal )
                {
                    $td_arr[] = "<td class='p pn'>$precio_normal</td>";
                }
                if( $is_update_oferta )
                {
                    $td_arr[] = "<td class='p po'>$precio_oferta</td>";
                }
                if( $is_update_mayor_normal )
                {
                    $td_arr[] = "<td class='p pmn'>$precio_especial2</td>";
                }
                if( $is_update_mayor_oferta )
                {
                    $td_arr[] = "<td class='p pmo'>$precio_especial3</td>";
                }
                $rows_arr[] = '<tr>' . implode('', $td_arr) . '</tr>';

                $i++;
            }

            $html = implode('', $rows_arr);

            echo($html);
        }

        exit(0);

    case 'sync_add_prods_wc':
        //obtiene los prox 50 prods que faltan pro crearse y los crea
        $res = 0;
        echo("<p>PENDIENTE:$res</p>");
        exit(0);
}

$result = array( 'ok' );

//$arraux = array( 'result' => $result );
$data = json_encode($result, JSON_UNESCAPED_UNICODE);

echo($data);
exit(0);

