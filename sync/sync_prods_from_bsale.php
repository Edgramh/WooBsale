<?php

/* descarga desde bsale todos los datos de los productos: variaciones, sku, precio, stock... */
require_once dirname(__FILE__) . '/../lib/Autoload.php';


if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if( !current_user_can(BSALE_CAPABILITY_CONFIG_INTEGRAC) )
{
    echo('user not allowed!!');
    exit;
}

$step = isset($_GET['s']) ? (int) $_GET['s'] : 0;
if( $step <= 0 )
{
    $step = isset($step_sync) ? $step_sync : $step;
}

if( $step <= 0 )
{
    echo("no step, terminado.");
    return;
}

switch( $step )
{
    case 1: //descargo productos ok
        $prod = new ProductoBsale();

        $res = $prod->get_all();

        break;

    case 2: //guardo productos descargados en db ok 
        $prod = new ProductoBsale();
        $res = $prod->save_in_db();

        break;

    case 3: //descargo variaciones ok
        $prod = new VariantesProductoBsale();
        $res = $prod->get_all();
        break;

    case 4: //guardo variaciones descargados en db ok
        $prod = new VariantesProductoBsale();
        $res = $prod->save_in_db();

        break;

    case 5: //descargo precios
        $prod = new PreciosProductosBsale();
        //descargo solo si debo actualizar precio normal
        if( Funciones::is_update_product_price() )
        {
            //lp precios normales
            $lp_precio_normal_id = Funciones::get_lp_bsale();
            $res = $prod->get_all($lp_precio_normal_id);
        }

        break;
    case 6: //descargo precios oferta
        $prod = new PreciosProductosBsale();

        //descargo solo si debo actualizar precio oferta
        if( Funciones::is_update_product_price_desc() )
        {
            //lp precio oferta
            $lp_precio_oferta_id = Funciones::get_lp_oferta_bsale();
            $res = $prod->get_all($lp_precio_oferta_id);
        }


        break;
    case 7: //descargo precios mayoristas normales
        $prod = new PreciosProductosBsale();

        //precio descto 2 (precio normal mayorista)
        $lp_precio_normal_mayorista_id = Funciones::get_lp_mayorista_normal_bsale();
        $res = $prod->get_all($lp_precio_normal_mayorista_id);


        break;
    case 8: //descargo precios mayorista oferta
        $prod = new PreciosProductosBsale();

        $lp_precio_ofertas_mayorista_id = Funciones::get_lp_mayorista_oferta_bsale();
        $res = $prod->get_all($lp_precio_ofertas_mayorista_id);

        break;
    //////////////////////////////////////////
    case 9:
        //guardo precios normales
        $prod = new PreciosProductosBsale();

        //descargo solo si debo actualizar precio normal
        if( Funciones::is_update_product_price() )
        {
            //lp precios normales
            $lp_precio_normal_id = Funciones::get_lp_bsale();
            $res = $prod->save_in_db($lp_precio_normal_id);
        }

        break;
    case 10:
        //guardo precios oferta
        $prod = new PreciosProductosBsale();

        //descargo solo si debo actualizar precio normal
        if( Funciones::is_update_product_price_desc() )
        {
            //lp precios normales
            $lp_precio_oferta_id = Funciones::get_lp_oferta_bsale();
            $res = $prod->save_in_db($lp_precio_oferta_id);
        }

        break;
    case 11:
        //guardo precios normales mayoristas
        $prod = new PreciosProductosBsale();

        //lp precios normales mayorista
        $lp_precio_normal_mayorista_id = Funciones::get_lp_mayorista_normal_bsale();
        $res = $prod->save_in_db($lp_precio_normal_mayorista_id);


        break;
    case 14:
        //guardo precios oferta mayorista
        $prod = new PreciosProductosBsale();

        $lp_precio_ofertas_mayorista_id = Funciones::get_lp_mayorista_oferta_bsale();
        $res = $prod->save_in_db($lp_precio_ofertas_mayorista_id);


        break;
    //////////////////////////////////////////////

    case 12: //descargo stock
        //stock de casa matriz
        $sucursal_id = Funciones::get_matriz_bsale();
        $prod = new StockProductosBsale();

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " get stock from sucursal principal: $sucursal_id");
        }

        $res = $prod->get_all($sucursal_id);

        break;

    case 13: //descargo stock

        $prod = new StockProductosBsale();

        //debo descargar el stock de todas las sucursales de bsale
        if( Funciones::is_mostrar_stock_sucursal() || Funciones::is_mostrar_stock_sucursal_backend() )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " Funciones::is_mostrar_stock_sucursal() || Funciones::is_mostrar_stock_sucursal_backend() es true: descargar stock de TODAS las sucursales");
            }
            $suc_obj = new SucursalesBsale();
            $sucursales_arr = $suc_obj->get_all_sucursales_names();

            //para no volver a descargar esta
            $sucursal_id = Funciones::get_matriz_bsale();

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($sucursales_arr, __METHOD__ . " descargar stock desde todas las sucursales");
            }

            $i = 0;

            foreach( $sucursales_arr as $suc_id => $name )
            {
                //no se desacarga el stock de la suc principal, pues ya se descargó
                if( $sucursal_id == $suc_id )
                {
                    continue;
                }
                if( isset($_REQUEST['param']) )
                {
                    Funciones::print_r_html($sucursales_arr, __METHOD__ . " descargando stock desde sucursal $suc_id $name");
                }
                $res = $prod->get_all($suc_id);
                $i++;

                //solo test: descargar el stock de solo las 5 primeras sucursales
                if( $i == 5 )
                {
                    if( isset($_REQUEST['param']) )
                    {
                        Funciones::print_r_html($sucursales_arr, __METHOD__ . " DEBUG: solo descargo stock de las primeras $i sucursale");
                    }
                    break;
                }
            }

            return $sucursales_arr;
        }
        //solo de las otras sucursales
        else
        {
            //stocxk de las otras sucursales
            $otras_sucursales_array = Funciones::get_sucursales_bsale();

            if( !is_array($otras_sucursales_array) )
            {
                break;
            }
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($otras_sucursales_array, __METHOD__ . " descargar stock desde otras sucursales");
            }

            foreach( $otras_sucursales_array as $suc_id )
            {
                $res = $prod->get_all($suc_id);
            }
        }



        break;

    case 15: //guardo stock suc principal en db
        $prod = new StockProductosBsale();
        $sucursal_id = Funciones::get_matriz_bsale();

        $res = $prod->save_in_db($sucursal_id);

        break;

    case 16: //guardo stock otras sucursales de bsale en db
        $prod = new StockProductosBsale();
        //stocxk de las otras sucursales
        $otras_sucursales_array = Funciones::get_sucursales_bsale();

        if( !is_array($otras_sucursales_array) )
        {
            break;
        }
        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html($otras_sucursales_array, __METHOD__ . " guardar stock desde otras sucursales");
        }

        foreach( $otras_sucursales_array as $suc_id )
        {
            $res = $prod->save_in_db($suc_id);
        }


        break;

    case 20: //guardo prods wc in file
        $prod = new ProductWP();
        $prod->save_in_file_all_products_wc();

        break;
    case 21: //guardo prods wc from file in db
        $prod = new ProductWP();
        $prod->save_in_db();

        break;
    case 22: //guardo tipos de prods de bsale db ok
        $prod = new TipoProductosBsale();
        $prod->get_all_save_in_db();

        break;
    case 23: //guardo atributos de tipos de prods de bsale db ok
        $prod = new AtributosTipoProductosBsale();
        $prod->get_all_attrib_products_save_in_db();

        break;
    case 24: //creo archivo json con consolidado de bsale: prod, variacion, precio, stock
        $prod = new ConsolidadoBsale();
        $res = $prod->get_consolidado();

        echo("<p>Respuesta: $res</p>");

        break;

    case 25: //guardo archivo consolidado en db
        $prod = new ConsolidadoBsale();
        $prod->save_in_db();

        break;

    case 26: //coloco precios en consolidado
        $prod = new ConsolidadoBsale();
        $res = $prod->get_consolidado_precios();

        echo("<p>Respuesta: $res</p>");

        break;

    case 27: //coloco stocks en consolidado
        $prod = new ConsolidadoBsale();
        $res = $prod->get_consolidado_stocks_save_in_table();

        //   echo("<p>Respuesta: $res</p>");

        break;

    case 28: //descargo sucursales de bsale y save in db ok
        $prod = new SucursalesBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Sucursales descargadas: $res</p>");

        break;
    case 29: //descargo lp de bsale y save in db ok
        $prod = new ListasPrecioBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Listas descargadas: $res</p>");

        break;
    case 30: //descargo usuarios de bsale y save in db ok
        $prod = new UsuariosBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Usuarios descargados: $res</p>");

        break;
    case 31: //descargo tipos dte de bsale y save in db ok
        $prod = new TipoDocumentoBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Tipos dte descargados: $res</p>");

        break;
    case 32: //descargo tipos pago de bsale y save in db ok
        $prod = new TipoPagoBsale();
        $res = $prod->get_all_save_in_db();

        echo("<p>Tipos pago descargados: $res</p>");

        break;
    case 33: //ver fecha/hora ultima descarga de datos desde bsale
        $prod = new UsuariosBsale();
        $res = $prod->get_last_hours_data_loaded();

        echo("<p>Datos descargados hace $res horas atrás</p>");

        break;

    case 34: //ver historial prod sync
        $product_id = isset($_REQUEST['pid']) ? (int) $_REQUEST['pid'] : 0;

        if( $product_id <= 0 )
        {
            echo("falta id de producto.");
            exit(0);
        }

        $log = new LogSincronizacionBsale();
        $data_arr = $log->get_last_log_product_html($product_id);

        echo $data_arr;

        break;



    case 35: //guardo en db listado de prods de wc para sincronizar (tipo, prod_id, sku)


        break;

    //descargar stock desde 1 sucursal de bsale
    //se usará por ajax
    case 100:
        //stock de casa matriz
        $sucursal_id = isset($_REQUEST['param_suc_id']) ? (int) $_REQUEST['param_suc_id'] : 0;
        //desde variable $param_suc_id
        $sucursal_id = ($sucursal_id <= 0) && isset($param_suc_id) ? $param_suc_id : $sucursal_id;

        if( $sucursal_id <= 0 )
        {
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html(__METHOD__ . " get stock from sucursal $sucursal_id es <=0, se omite");
            }
        }

        $prod = new StockProductosBsale();

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . " get stock from sucursal: $sucursal_id");
        }

        $res = $prod->get_all($sucursal_id);

        break;
    default:
        break;
}





