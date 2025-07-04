<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions' . '.class.php';

function AutoloadJason($classname)
{
    $namespace_base = 'woocommerce_bsalev2\lib';
    $namespace_base_dir = 'woocommerce_bsalev2/lib';
    $folder_name = 'woocommerce-bsalev2';

    $is_namespaced = substr($classname, 0, strlen($namespace_base)) === $namespace_base;

    if( $is_namespaced )
    {
        //cambio \ por /
        $classname = str_replace('\\', '/', $classname);
        $dirpath = $filename = dirname(__FILE__);

       // echo("<p>namespace antes '$classname', cambio '$namespace_base_dir' por  '$dirpath'</p>");
        //si viene namespace, reemplazo el principio por el nombre de la carpeta del plugin
        $classname = str_replace($namespace_base_dir, $dirpath, $classname);

        //detecto el archivo que contiene la clase
        $filename = $classname . '.class.php';

        //echo("<p>namespace de '$filename'</p>");

        if( is_readable($filename) )
        {
            //echo("<p>autoloading namespace $classname en $filename</p>");
            require_once $filename;
            return;
        }
       
    }





    //Can't use __DIR__ as it's only in PHP 5.3+
    $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . $classname . '.class.php';
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opencart' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'opencart3' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'prestashop' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'linio' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {

        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'weberp' . DIRECTORY_SEPARATOR . $classname . '.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'jumpseller' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'magento' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'magento2' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'productos' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wp' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'shopify' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'microcompras' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'autopilot' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }
    if( !is_readable($filename) )
    {
        $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'giftcards' . DIRECTORY_SEPARATOR . $classname . '.class.php';
    }



    if( !is_readable($filename) )
    {
        $filename = null;
    }
    if( $filename != null )
    {
       // echo("<p>autoloading $classname en $filename</p>");
        require_once $filename;
    }
    else
    {
       // echo("<p>ERROR autoloading $classname no encontrada</p>");
    }
}

//echo("autoload.php, php version " . PHP_VERSION . " version_compare( PHP_VERSION, '5.1.2', '>=' )=" . version_compare( PHP_VERSION, '5.1.2', '>=' ) );
//if ( version_compare( PHP_VERSION, '5.1.2', '>=' ) )
{
    //SPL autoloading was introduced in PHP 5.1.2
    if( version_compare(PHP_VERSION, '5.3.0', '>=') )
    {
//        echo("<p>autoloading spl_autoload_register...</p>");
        spl_autoload_register('AutoloadJason', true, true);
    }
    else
    {
        spl_autoload_register('AutoloadJason');
    }
}

bsale_woocommerce_autoload_wp();

function bsale_woocommerce_autoload_wp()
{
    //cargo wordpress :)
    if( INTEGRACION_SISTEMA === 'woocommerce' && !function_exists('add_filter') )
    {
        $file2 = dirname(__FILE__) . '/../../../../wp-load.php';

        if( file_exists($file2) )
        {
            if( !defined('WP_USE_THEMES') )
            {
                define('WP_USE_THEMES', false);
            }
// echo("encontrado $file2");
            require_once $file2;
        }
        else
        {
            die('wp-load.php no encontrado! ' . realpath($file1) . ', ' . realpath($file2));
        }
    }

    if( INTEGRACION_SISTEMA === 'woocommerce' )
    {
        $file_filters = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'bsale_custom_actions_filters_sample.php';

        if( file_exists($file_filters) )
        {
            /* if( isset($_REQUEST['param']) )
              {
              Funciones::print_r_html("incluyo archivo de custom filters '$file_filters'");
              } */
            require_once $file_filters;
        }
    }
}
