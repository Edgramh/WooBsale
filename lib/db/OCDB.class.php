<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OCDB
 *
 * @author angelorum
 */
class OCDB
{

    //ref a database object
    protected static $DB = null;
    protected static $db_prefix = DB_PREFIX;
    public $TRANSACCIONES_TABLE, $LOGS_TABLE;
    //estado de las ordenes completadas, listas para emitir boleta
    public static $ORDER_ESTADO_COMPLETADA = 5;
    public $OC_ORDERS_TABLE, $OC_ORDERS_PRODUCTS_TABLE, $VALORES_TABLE,
            $STOCK_PRODUCTOS_BSALE_TABLE, $STOCK_SKU_PRODUCTOS_BSALE_TABLE,
            $STOCK_SKU_PRODUCTOS_BSALE_TABLE2,
            $STOCK_PRECIOS_PRODUCTOS_BSALE_TABLE, $STOCK_STOCK_PRODUCTOS_BSALE_TABLE,
            $STOCK_PRODUCTOS_JUMPSELLER_TABLE, $OPTIONS_TABLE;

    function __construct()
    {
        $this->conectar();

        $prefix = DB_PREFIX;

        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            $prefix = Funciones::get_prefix() . $prefix;
            self::$db_prefix = $prefix;
        }
        //creo tablas si no existen      
        // $this->LOGS_TABLE = "{$prefix}bsale_dte_logs_table";
        $this->VALORES_TABLE = "{$prefix}fe_valores";
        //opencart
        $this->OC_ORDERS_TABLE = $prefix . "order";
        $this->OC_ORDERS_PRODUCTS_TABLE = $prefix . "order_product";

        //stock tablas
        $this->STOCK_PRODUCTOS_BSALE_TABLE = $prefix . "bsale_products";
        $this->STOCK_SKU_PRODUCTOS_BSALE_TABLE = $prefix . "bsale_sku_productos";
        $this->STOCK_SKU_PRODUCTOS_BSALE_TABLE2 = $prefix . "bsale_sku_productos_v2";
        $this->STOCK_PRECIOS_PRODUCTOS_BSALE_TABLE = $prefix . "bsale_precios_productos";
        $this->STOCK_STOCK_PRODUCTOS_BSALE_TABLE = $prefix . "bsale_stock_productos";
        $this->STOCK_PRODUCTOS_JUMPSELLER_TABLE = $prefix . "jumpseller_products";
        $this->OPTIONS_TABLE = $prefix . "bsale_options";

        if( INTEGRACION_SISTEMA !== 'woocommerce' )
        {
            $this->setupTables();

            //funcion para manejo de errores fatales
            register_shutdown_function("bsale_fatal_handler");
            register_shutdown_function("bsale_say_goodbye");
        }
    }

    static function getDb_prefix()
    {
        return self::$db_prefix;
    }

    static function setDb_prefix($db_prefix)
    {
        self::$db_prefix = $db_prefix;
    }

    public function conectar()
    {
        //usa global $wpdb
        if( INTEGRACION_SISTEMA === 'woocommerce' )
        {
            return true;
        }
        if( self::$DB != null && mysqli_ping(self::$DB) )
        {
            return self::$DB;
        }
        if( self::$DB != null )
        {
            try
            {
                mysqli_close(self::$DB);
            }
            catch( Exception $exc )
            {
                echo $exc->getTraceAsString();
            }
        }
        //Funciones::print_r_html( "conectando a db" );
        $port = defined('DB_PORT') && DB_PORT > 0 ? DB_PORT : ini_get("mysqli.default_port");

        $db = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD_BSALE, DB_DATABASE, $port);

        if( mysqli_connect_errno() || !$db )
        {
            die('Unable to connect to database ' . $db . ' ERROR: [' . mysqli_connect_error() . ']<br/>'
                    . 'mysqli_connect(' . DB_HOSTNAME . ',' . DB_USERNAME . ', passxxx' . ', ' .
                    DB_DATABASE . ', ' . $port . ');');
        }
        self::$DB = $db;

        return self::$DB;
    }

    /**
     * crea las tablas necesariaspara este "plugin"
     */
    public function setupTables()
    {
        //usa global $wpdb
        $is_wc = INTEGRACION_SISTEMA === 'woocommerce';

        if( $is_wc )
        {
            // upgrade contiene la función dbDelta la cuál revisará si existe la tabla.
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }

        $this->setupTables2();

        //coinstante de OC
        $prefix = self::$db_prefix;

        //tablas de stock
        $this->setupStockTables();

        //veo si tablas existen. Si no existe al menos una, las creo
        /* if( $this->tableExists($this->TRANSACCIONES_TABLE) )
          {
          return;
          } */
        //  Funciones::print_r_html( "Tablas no existen en la db: creando (prefijo $prefix) ..." );
        $conn = $this->conectar();
        if( $conn == false )
        {
            Funciones::print_r_html("OCDB::error al conectar a db, setupTables");
            return;
        }

        if( INTEGRACION_SISTEMA === 'opencart' )
        {
            $this->setupOpencartTables();
        }

        if( !$is_wc )
        {
            //creo tabla de users
            $sql = "CREATE TABLE IF NOT EXISTS `bsale_users` (
                `id` INT unsigned NOT NULL AUTO_INCREMENT,
                `modulo_id` BIGINT unsigned NOT NULL,                
                `modulo_name` varchar(4) NOT NULL,               
                `estado` int(11) NOT NULL DEFAULT '1',               
                `username` varchar(100) NOT NULL,             
                `pass` varchar(300) NOT NULL,
                UNIQUE(`username`),
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if( $is_wc )
            {

                // Creamos la tabla
                dbDelta($sql);
            }
            else
            {
                mysqli_query($conn, $sql);
            }
        }
    }

    /**
     * borra tablas desactualizadas de versiones anteriores de la integración
     */
    public function delete_old_tables()
    {
        //usa global $wpdb
        $is_wc = INTEGRACION_SISTEMA === 'woocommerce';

        if( !$is_wc )
        {
            return false;
        }


        $prefix = self::$db_prefix;
        global $wpdb;

        $tables_to_delete_arr = array( Funciones::get_value('DB_TABLE_SUCURSALES_BSALE') );

        foreach( $tables_to_delete_arr as $table )
        {
            $table_name = "{$prefix}{$table}";

            //obtengo listado de columnas de la tabla
            $existing_columns = $wpdb->get_col("DESC $table_name", 0);

            $sql = "DROP TABLE IF EXISTS $table_name";
            $result = $wpdb->query($sql);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($existing_columns, __METHOD__ . " antes de borrar table old '$table_name' con columnas:");

                Funciones::print_r_html($result, __METHOD__ . " borro table old '$table_name', sql= '$sql', resultado:");
            }
        }
        return true;
    }

    /**
     * tablas para sumar stocks por sucursal
     */
    public function setupTables2()
    {
        $conn = $this->conectar();

        //usa global $wpdb
        $is_wc = INTEGRACION_SISTEMA === 'woocommerce';

        if( $is_wc )
        {
            // upgrade contiene la función dbDelta la cuál revisará si existe la tabla.
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }

        $prefix = self::$db_prefix;

        $table = Funciones::get_value('DB_TABLE_SUCURSALES_BSALE');
        //listado de sucursales
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` BIGINT unsigned NOT NULL,
            `name` varchar(200) NOT NULL,
            `address` varchar(300) NULL,
            `country` VARCHAR(100) NULL,
            `municipality` VARCHAR(200) NULL,
            `city` VARCHAR(200) NULL,
            `description` varchar(300) NULL,
            `isVirtual` smallint(6) NOT NULL,
            `state` smallint(6) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //listas de precio de bsale
        $table = Funciones::get_value('DB_TABLE_LISTAS_PRECIO_BSALE');
        //listado de sucursales
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` BIGINT unsigned NOT NULL,
            `name` varchar(200) NOT NULL,           
            `description` varchar(300) NULL,           
            `state` smallint(6) NOT NULL,
             `coin` smallint(6) NOT NULL,
             `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla de stock de prodcuto por sucursal
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}bsale_stock_sucursal (
             `variante_id` BIGINT unsigned NOT NULL,
            `sucursal_id` BIGINT unsigned NOT NULL,
             `producto_sku_ean` varchar(200) default NULL,
             `stock` BIGINT unsigned NOT NULL,
             `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`variante_id`,`sucursal_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para productos
        $table = Funciones::get_value('DB_TABLE_PRODUCTS');

        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` int(11) NOT NULL,
            `name` varchar(200) NOT NULL,
            `description` varchar(600) DEFAULT NULL,
            `stockControl` smallint(6) NOT NULL,
            `state` smallint(6) NOT NULL,
            `product_type` int(11) NOT NULL,
            `es_pack` TINYINT NOT NULL DEFAULT '0',
            `estado_sync` varchar(6) DEFAULT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `texto1` varchar(50) NOT NULL,
            `texto2` varchar(50) NOT NULL,
            `pack_details` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para variaciones
        $table = Funciones::get_value('DB_TABLE_VARIANTS');

        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
           `id` int(11) NOT NULL,
            `description` varchar(200) DEFAULT NULL,
            `unlimitedStock` smallint(6) NOT NULL,
             `allowNegativeStock` smallint(6) NOT NULL,
            `state` smallint(6) NOT NULL,
            `code` varchar(100) NOT NULL,
            `barCode` varchar(100) NOT NULL,
            `serialNumber` varchar(100) NOT NULL,
            `isLot` smallint(6) NOT NULL,
            `product_id` int(11) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `estado_sync` varchar(6) NOT NULL,
            `texto1` varchar(50) NOT NULL,
            `texto2` varchar(50) NOT NULL,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para precios
        $table = Funciones::get_value('DB_TABLE_PRICES');

        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `variant_id` int(11) NOT NULL,
            `lp_id` int(11) NOT NULL,
            `variantValue` FLOAT NOT NULL,
            `variantValueWithTaxes` FLOAT NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para stock
        $table = Funciones::get_value('DB_TABLE_STOCK');

        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `variant_id` int(11) NOT NULL,
            `sucursal_id` int(11) NOT NULL,
            `quantityReserved` float NOT NULL,
            `quantityAvailable` float NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para productos de wc
        $table = Funciones::get_value('DB_TABLE_PRODUCTS_WC');

        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `parent_id` INT NULL,
            `nombre` varchar(200) NOT NULL,
            `tipo` varchar(40) NOT NULL,
            `estado` varchar(40) NOT NULL,
            `sku` varchar(100) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }


        $table = Funciones::get_value('DB_TABLE_BSALE_PRODUCT_TYPES');

        //creo tabla para tipos de productos
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` int(11) NOT NULL,                     
            `name` varchar(200) not null, 
            `state` smallint(6) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para tipos de doctos 
        $table = Funciones::get_value('DB_TABLE_TIPO_DOCUMENTO_BSALE');

        //creo tabla para tipos de dte
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `initialNumber` int(11) NOT NULL,
            `codeSii` varchar(100) NOT NULL,
            `isElectronicDocument` smallint(6) NOT NULL,
            `breakdownTax` int(11) NOT NULL,
            `use_` smallint(6) NOT NULL,
            `isSalesNote` smallint(6) NOT NULL,
            `isExempt` smallint(6) NOT NULL,
            `restrictsTax` smallint(6) NOT NULL,
            `useClient` smallint(6) NOT NULL,
            `messageBodyFormat` varchar(10) NOT NULL,
            `thermalPrinter` int(11) NOT NULL,
            `state` smallint(6) NOT NULL,
            `copyNumber` int(11) NOT NULL,
            `isCreditNote` smallint(6) NOT NULL,
            `continuedHigh` int(11) NOT NULL,
            `ledgerAccount` varchar(10) NOT NULL,
            `ipadPrint` int(11) NOT NULL,
            `ipadPrintHigh` int(11) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para tipos de pago
        $table = Funciones::get_value('DB_TABLE_TIPO_PAGO_BSALE');

        //creo tabla para tipos de pago
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
            `id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `isVirtual` smallint(6) NOT NULL,
            `isCheck` smallint(6) NOT NULL,
            `maxCheck` int(11) NOT NULL,
            `isCreditNote` smallint(6) NOT NULL,
            `isClientCredit` smallint(6) NOT NULL,
            `isCash` smallint(6) NOT NULL,
            `isCreditMemo` smallint(6) NOT NULL,
            `state` smallint(6) NOT NULL,
            `maxClientCuota` smallint(6) NOT NULL,
            `ledgerAccount` varchar(100) NOT NULL,
            `ledgerCode` varchar(100) NOT NULL,
            `isAgreementBank` smallint(6) NOT NULL,
            `agreementCode` varchar(5) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para atributos de tipos de productos
        $table = Funciones::get_value('DB_TABLE_BSALE_PRODUCT_TYPES_ATTR');

        //creo tabla para tipos de productos
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
           `product_type_id` int(11) NOT NULL,
            `id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `isMandatory` smallint(6) NOT NULL,
            `generateVariantName` smallint(6) NOT NULL,
            `hasOptions` smallint(6) NOT NULL,
            `options` text NOT NULL,
            `state` smallint(6) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`product_type_id`,`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para usuarios de la cuenta de bsale
        $table = Funciones::get_value('DB_TABLE_USUARIOS_BSALE');

        //creo tabla para usuarios de bsale
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
           `id` int(11) NOT NULL,
            `firstName` varchar(100) NOT NULL,
            `lastName` varchar(200) NOT NULL,
            `email` varchar(100) NOT NULL,
            `state` smallint(6) NOT NULL,
            `office` int(11) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla consolidado: prod, variacion, precios y stock a sync hacia la web
        $table = Funciones::get_value('DB_TABLE_CONSOLIDADO_BSALE_PRODS');

        //creo tabla para consolidado
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
           `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `product_name` varchar(200) NOT NULL,
            `product_stock_control` smallint(6) NOT NULL,
            `product_state` smallint(6) NOT NULL,
            `product_type` int(11) NOT NULL,    
            `es_pack` TINYINT NOT NULL DEFAULT '0',
            `var_id` int(11) NOT NULL,
            `var_description` varchar(200) NOT NULL,
            `var_unlimited_stock` smallint(6) NOT NULL,
            `var_allow_negative_stock` smallint(6) NOT NULL,
            `var_state` smallint(6) NOT NULL,
            `var_code` varchar(200) NOT NULL,
            `var_barcode` VARCHAR(200) DEFAULT NULL,
            `var_serial_number` varchar(200) NOT NULL,
            `var_is_lot` smallint(6) NOT NULL,
            `precio_normal` float NOT NULL,
            `precio_oferta` float NOT NULL,
            `precio_normal2` float NOT NULL,
            `precio_oferta2` float NOT NULL,
            `precio_normal3` float DEFAULT NULL,
            `precio_oferta3` float DEFAULT NULL,
            `stock` float NOT NULL,
            `stocks_sucursales` text,
            `estado_sync` char(1) NOT NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        //tabla para loguear sincronizacion
        //DB_TABLE_LOG_SYNC_BSALE
        $table = Funciones::get_value('DB_TABLE_LOG_SYNC_BSALE');

        //tabla para loguear sincronizacion
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (
           `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `producto_padre_id` int(11) NOT NULL,
            `tipo_producto` varchar(20) NOT NULL,
            `nombre` varchar(100) NOT NULL,
            `sku` varchar(100) NOT NULL,
            `stock` FLOAT NOT NULL,
            `precio_normal` float NOT NULL,
            `precio_oferta` float NOT NULL,
            `precio_especial_2` float NOT NULL,
            `precio_especial_3` float NOT NULL,
            `source_file` varchar(200) NOT NULL,
            `source_cpnId` int(11) NOT NULL,
            `source_resource` varchar(100) NOT NULL,
            `source_resourceId` int(11) NOT NULL,
            `source_topic` varchar(20) NOT NULL,
            `source_action` varchar(20) NOT NULL,
            `source_officeId` int(11) NOT NULL,
            `source_send` timestamp NULL DEFAULT NULL,
            `accion` varchar(10) NOT NULL,
            `result` varchar(10) NOT NULL,
            `data` text NOT NULL,
            `msg` VARCHAR(400) NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        if( !$is_wc )
        {
            //tabla de logs de pedidos y dtes emitidos      
            $table = Funciones::get_value('DB_TABLE_LOG_SYNC_BSALE');
            $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (          
            `local_id` varchar(20) NOT NULL,
            `remoto_id` BIGINT(60) unsigned NOT NULL,
            `tipo` varchar(4) NOT NULL,
            `source` varchar(10) NOT NULL default '',
            `json_post` text,
            `array_post` text,
            `json_respuesta` text,
            `array_respuesta` text,
            `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`local_id`,`tipo`, `remoto_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            mysqli_query($conn, $sql);
        }

        //tabla de logs de pedidos y dtes emitidos  
        //por ahora, solo wc
        $table = Funciones::get_value('DB_TABLE_LOG_DTES_BSALE_WC');
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}{$table} (          
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `tipo_doc` varchar(10) NOT NULL,
            `doc_id` int(11) NOT NULL,
            `doc_folio` varchar(30) NULL,
            `json_send` text NULL,
            `json_recv` text NULL,
            `result` varchar(10) NOT NULL,
            `link_dte` varchar(200) NULL,
            `msg` VARCHAR(300) NULL,
            `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `source` varchar(10) NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        if( $is_wc )
        {
            // Creamos la tabla
            dbDelta($sql);
        }
    }

    /**
     * tablas para gift cards
     */
    public function setupTablesGiftCards()
    {
        $conn = $this->conectar();
        if( $conn == false )
            return;
        //coinstante de OC
        $prefix = self::$db_prefix;

        //listado de sucursales
        $sql = "CREATE TABLE  IF NOT EXISTS {$prefix}bsale_giftcards (
            `folio` BIGINT unsigned NOT NULL,
            `barcode` TEXT NOT NULL,
            fecha timestamp not null,
            PRIMARY KEY (`folio`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        mysqli_query($conn, $sql);

        $sql = "CREATE TABLE IF NOT EXISTS `giftcard_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `pedido_id` int(11) NOT NULL,
                `rut` varchar(12) DEFAULT NULL,
                `nombres` varchar(200) DEFAULT NULL,
                `apellidos` varchar(200) DEFAULT NULL,
                `email` varchar(200) DEFAULT NULL,
                `region` varchar(200) DEFAULT NULL,
                `comuna` varchar(200) DEFAULT NULL,
                `direccion` varchar(400) DEFAULT NULL,
                `total_pedido` int(11) DEFAULT NULL,
                `sku_producto` varchar(100) DEFAULT NULL,
                `producto` varchar(200) DEFAULT NULL,
                `resultado_folios_activados` VARCHAR(400) DEFAULT NULL,
                `folio` varchar(100) DEFAULT NULL,
                `barcode` text DEFAULT NULL,
                `monto_giftcard` int(11) DEFAULT NULL,
                `resultado_email` char(5) DEFAULT NULL,
                `notas` text DEFAULT NULL,
                `fecha_giftcard` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        mysqli_query($conn, $sql);

        //tabla de stock de prodcuto por sucursal
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}bsale_stock_sucursal (
             `variante_id` BIGINT unsigned NOT NULL,
            `sucursal_id` BIGINT unsigned NOT NULL,
             `producto_sku_ean` varchar(200) default NULL,
             `stock` BIGINT unsigned NOT NULL,
            PRIMARY KEY (`variante_id`,`sucursal_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        mysqli_query($conn, $sql);
    }

    public function setupStockTables()
    {
        //usa global $wpdb
        $is_wc = INTEGRACION_SISTEMA === 'woocommerce';

        if( $is_wc )
        {
            // upgrade contiene la función dbDelta la cuál revisará si existe la tabla.
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }
        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }

//options    
        if( !$is_wc && !$this->tableExists($this->OPTIONS_TABLE) )
        {

            $sql = "CREATE TABLE IF NOT EXISTS `{$this->OPTIONS_TABLE}` (               
                `clave` varchar(50) NOT NULL,
                `valor` varchar(400) DEFAULT NULL,
                 `tipo` varchar(20) DEFAULT NULL,
                PRIMARY KEY (`clave`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("setupStockTables, sql: '$sql'");
            }
            if( $is_wc )
            {
                // Creamos la tabla
                dbDelta($sql);
            }
            else
            {
                mysqli_query($conn, $sql);
            }
        }
        if( !$is_wc && !$this->tableExists($this->STOCK_SKU_PRODUCTOS_BSALE_TABLE) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->STOCK_SKU_PRODUCTOS_BSALE_TABLE}` (  
            `variante_id` int(11) NOT NULL,
            `producto_bsale_id` int(11) default 0,
            `nombre_producto` varchar(200) COLLATE utf8_bin default null,
            `sku` varchar(200) COLLATE utf8_bin,  
            `dato1` varchar(200) COLLATE utf8_bin,  
            `dato2` varchar(600) COLLATE utf8_bin,  
            `descripcion` varchar(200) COLLATE utf8_bin,
            `precio` varchar(20) COLLATE utf8_bin default 0, 
            `precio2` varchar(20) COLLATE utf8_bin default 0,
            `precio3` varchar(20) COLLATE utf8_bin default 0,
            `precio4` varchar(20) COLLATE utf8_bin default 0,
            `precio5` varchar(20) COLLATE utf8_bin default 0,
            `precio6` varchar(20) COLLATE utf8_bin default 0,
            `stock` int(11) COLLATE utf8_bin default 0,   
            `estado_para_enviar` CHAR(1) NULL,
            `atributos` text default NULL,
            PRIMARY KEY (`variante_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("setupStockTables, sql: '$sql'");
            }

            if( $is_wc )
            {
                // Creamos la tabla
                dbDelta($sql);
            }
            else
            {
                mysqli_query($conn, $sql);
            }
        }
        if( !$this->tableExists($this->STOCK_SKU_PRODUCTOS_BSALE_TABLE2) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->STOCK_SKU_PRODUCTOS_BSALE_TABLE2}` (  
            `variante_id` int(11) NOT NULL,
            `producto_bsale_id` int(11) default 0,
             `nombre_producto` varchar(200) COLLATE utf8_bin default null,
            `sku` varchar(200) COLLATE utf8_bin,  
            `dato1` varchar(200) COLLATE utf8_bin,  
            `dato2` varchar(600) COLLATE utf8_bin,  
            `descripcion` varchar(200) COLLATE utf8_bin,
            `precio` varchar(20) COLLATE utf8_bin default 0, 
            `precio2` varchar(20) COLLATE utf8_bin default 0,
            `precio3` varchar(20) COLLATE utf8_bin default 0,
            `precio4` varchar(20) COLLATE utf8_bin default 0,
            `precio5` varchar(20) COLLATE utf8_bin default 0,
            `precio6` varchar(20) COLLATE utf8_bin default 0,
            `stock` int(11) COLLATE utf8_bin default 0,                
            PRIMARY KEY (`variante_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("setupStockTables, sql: '$sql'");
            }
            if( $is_wc )
            {
                // Creamos la tabla
                dbDelta($sql);
            }
            else
            {
                mysqli_query($conn, $sql);
            }
        }
        if( !$this->tableExists($this->STOCK_PRECIOS_PRODUCTOS_BSALE_TABLE) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->STOCK_PRECIOS_PRODUCTOS_BSALE_TABLE}` (            
            `precio` varchar(20) COLLATE utf8_bin,   
             `precio2` varchar(20) COLLATE utf8_bin default 0,
             `precio3` varchar(20) COLLATE utf8_bin default 0,
             `precio4` varchar(20) COLLATE utf8_bin default 0,
             `precio5` varchar(20) COLLATE utf8_bin default 0,
             `precio6` varchar(20) COLLATE utf8_bin default 0,
            `variante_id` int(11) NOT NULL,
             PRIMARY KEY (`variante_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("setupStockTables, sql: '$sql'");
            }
            if( $is_wc )
            {
                // Creamos la tabla
                dbDelta($sql);
            }
            else
            {
                mysqli_query($conn, $sql);
            }
        }
        if( !$this->tableExists($this->STOCK_STOCK_PRODUCTOS_BSALE_TABLE) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->STOCK_STOCK_PRODUCTOS_BSALE_TABLE}` (  
             `variante_id` int(11) NOT NULL,
            `stock` varchar(200) COLLATE utf8_bin,            
             PRIMARY KEY (`variante_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("setupStockTables, sql: '$sql'");
            }
            if( $is_wc )
            {
                // Creamos la tabla
                dbDelta($sql);
            }
            else
            {
                mysqli_query($conn, $sql);
            }
        }



        //veo si tablas existen. Si no existe al menos una, las creo
        if( !$this->tableExists($this->STOCK_PRODUCTOS_BSALE_TABLE) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->STOCK_PRODUCTOS_BSALE_TABLE}` (
            `producto_bsale_id` int(11) NOT NULL,
            `name` varchar(200) COLLATE utf8_bin,
            `description` varchar(200) COLLATE utf8_bin,
            `centro_costo` varchar(20) COLLATE utf8_bin,
            `stock_control` varchar(1000) default null,
            `state` int(11) NOT NULL,
            `product_type_id` int(11) default NULL,
            `sku` varchar(200) COLLATE utf8_bin,
            `precio` varchar(10) COLLATE utf8_bin,
            `stock` varchar(10) COLLATE utf8_bin default null,
            `estado_para_enviar` CHAR(1) NULL,
             PRIMARY KEY (`producto_bsale_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";
            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("setupStockTables, sql: '$sql'");
            }
            if( $is_wc )
            {
                // Creamos la tabla
                dbDelta($sql);
            }
            else
            {
                mysqli_query($conn, $sql);
            }
        }
        //veo si tablas existen. Si no existe al menos una, las creo
        if( !$is_wc && !$this->tableExists($this->STOCK_PRODUCTOS_JUMPSELLER_TABLE) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->STOCK_PRODUCTOS_JUMPSELLER_TABLE}` (
            `producto_jumpseller_id` varchar(50) NOT NULL,
            `name` varchar(200) COLLATE utf8_bin,
            `description` varchar(200) COLLATE utf8_bin,
            `centro_costo` varchar(50) COLLATE utf8_bin,           
            `state` int(11) NOT NULL,
            `sku` varchar(200) COLLATE utf8_bin,
            `precio` varchar(10) COLLATE utf8_bin,
            `stock` varchar(10) COLLATE utf8_bin,
            `stock2` varchar(10) COLLATE utf8_bin,
            `producto_bsale_id` int(11) default 0,
             PRIMARY KEY (`producto_jumpseller_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";
            mysqli_query($conn, $sql);
        }

        //07 2019, crear tabla para listado prods de jumpseller        
        $table = 'bsale_jumpseller_prods';

        if( !$is_wc && !$this->tableExists($table) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `$table` (  
            `tienda_id` int(11) NOT NULL,
            `producto_id` BIGINT UNSIGNED NOT NULL,             
            `nombre_producto` varchar(200) COLLATE utf8_bin default null,
            `variante_id` BIGINT UNSIGNED NOT NULL,
            `nombre_variante` varchar(200) COLLATE utf8_bin default null,            
            `sku` varchar(200) COLLATE utf8_bin,  
            `precio` varchar(20) COLLATE utf8_bin default 0, 
            `precio2` varchar(20) COLLATE utf8_bin default 0,
            `precio3` varchar(20) COLLATE utf8_bin default 0,
            `stock` int(11) COLLATE utf8_bin default 0,            
            `dato1` varchar(200) COLLATE utf8_bin,  
            `dato2` varchar(600) COLLATE utf8_bin,  
            `dato3` varchar(200) COLLATE utf8_bin,  
            `dato4` varchar(200) COLLATE utf8_bin,  
            `descripcion` varchar(200) COLLATE utf8_bin default null,               
             PRIMARY KEY (`tienda_id`, `producto_id`, `variante_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";
            mysqli_query($conn, $sql);
        }

        $table = 'bsale_shopify_prods';
        if( !$is_wc && !$this->tableExists($table) )
        {
            //creo tabla para comisiones
            $sql = "CREATE TABLE IF NOT EXISTS `$table` (  
            `tienda_id` int(11) NOT NULL,
            `producto_id` BIGINT UNSIGNED NOT NULL,             
            `nombre_producto` varchar(200) COLLATE utf8_bin default null,
            `variante_id` BIGINT UNSIGNED NOT NULL,
            `nombre_variante` varchar(200) COLLATE utf8_bin default null,            
            `sku` varchar(200) COLLATE utf8_bin,  
            `precio` varchar(20) COLLATE utf8_bin default 0, 
            `precio2` varchar(20) COLLATE utf8_bin default 0,
            `precio3` varchar(20) COLLATE utf8_bin default 0,
            `stock` int(11) COLLATE utf8_bin default 0,            
            `dato1` varchar(200) COLLATE utf8_bin,  
            `dato2` varchar(600) COLLATE utf8_bin,  
            `dato3` varchar(200) COLLATE utf8_bin,  
            `dato4` varchar(200) COLLATE utf8_bin,  
            `descripcion` varchar(200) COLLATE utf8_bin default null,               
             PRIMARY KEY (`tienda_id`, `producto_id`, `variante_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
          ";
            mysqli_query($conn, $sql);
        }
    }

    public function setupOpencartTables()
    {
        //coinstante de OC
        $prefix = self::$db_prefix;

        Funciones::print_r_html("setupOpencartTables(): Tablas no existen en la db: creando (prefijo $prefix) ...");
        $conn = $this->conectar();
        if( $conn == false )
            return;

        //modifico tabla orders (arrojará error si el campo ya existe)
        $sql = "ALTER TABLE `{$prefix}order` ADD `docto_id` INT NULL ;";
        try
        {
            mysqli_query($conn, $sql);
        }
        catch( Exception $exc )
        {
            //  echo $exc->getTraceAsString();
        }
        $sql = "ALTER TABLE `{$prefix}product` ADD `producto_bsale_id` INT NULL ;";
        try
        {
            mysqli_query($conn, $sql);
        }
        catch( Exception $exc )
        {
            //  echo $exc->getTraceAsString();
        }
    }

    public function tableExists($table)
    {
        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }

        //usa global $wpdb
        $is_wc = INTEGRACION_SISTEMA === 'woocommerce';

        if( $is_wc )
        {
            global $wpdb;
        }


        $sql = "SHOW TABLES LIKE '$table'";

        if( $is_wc )
        {
            $res = $wpdb->query($sql);
            return $res > 0;
        }
        else
        {
            mysqli_query($conn, $sql);
        }

        $res = mysqli_query($conn, $sql);
        try
        {
            return mysqli_num_rows($res) > 0;
        }
        catch( Exception $exc )
        {
            return false;
        }
        return false;

        //sacar


        $sql = "select 1 from `$table` LIMIT 1";
        $res = mysqli_query($conn, $sql);

        //  Funciones::print_r_html( $res, $sql . " nrows: " . mysqli_num_rows( $res ) );

        try
        {
            return mysqli_num_rows($res) > 0;
        }
        catch( Exception $exc )
        {
            return false;
        }
    }

    public function clearDatabase()
    {
        $tables_to_clear = array( $this->STOCK_PRODUCTOS_BSALE_TABLE,
            $this->STOCK_SKU_PRODUCTOS_BSALE_TABLE,
            $this->STOCK_PRECIOS_PRODUCTOS_BSALE_TABLE,
            $this->STOCK_STOCK_PRODUCTOS_BSALE_TABLE,
            $this->STOCK_PRODUCTOS_JUMPSELLER_TABLE );
        return $this->clearTables($tables_to_clear);
    }

    public function clearTables($tables_to_clear)
    {
        $conn = $this->conectar();
        if( $conn == false )
            return;

        try
        {

//            $sql = "SET FOREIGN_KEY_CHECKS=0;";
//            mysqli_query( $conn, $sql );
            //limpiuo tablas que no borro
            foreach( $tables_to_clear as $tt )
            {
                $sql = "TRUNCATE $tt;";
                mysqli_query($conn, $sql);
            }

//            $sql = "SET FOREIGN_KEY_CHECKS=1;";
//            mysqli_query( $conn, $sql );
        }
        catch( Exception $exc )
        {

            echo $exc->getTraceAsString();
        }
    }

    /**
     * tablas separadas x coma
     * @param type $tables_to_drop
     * @return type
     */
    public function dropTables($tables_to_drop)
    {

        $conn = $this->conectar();
        if( $conn == false )
            return;

        try
        {

            $sql = "SET FOREIGN_KEY_CHECKS=0;";
            mysqli_query($conn, $sql);

            $sql = "DROP TABLE $tables_to_drop;";
            Funciones::print_r_html($sql, "sql para drop en db " . DB_DATABASE . ":");

            mysqli_query($conn, $sql);

            $sql = "SET FOREIGN_KEY_CHECKS=1;";

            mysqli_query($conn, $sql);
        }
        catch( Exception $exc )
        {
            //  mysqli_rollback( $conn );
            echo "dropTables($tables_to_drop ):" . $exc->getTraceAsString();
        }
    }

    public static function lockFile()
    {
        $path = dirname(__FILE__);
        $filename = "$path/lockfile.txt";

        /*  if(file_exists($filename))
          {
          Funciones::print_r_html("lockFile: $filename existe. Termino");
          die();
          } */
        $f = fopen($filename, 'w+') or die("lockFile: Cannot create lock file $filename");

        $wouldblock = 0;
        if( flock($f, LOCK_EX | LOCK_NB, $wouldblock) )
        {
            Funciones::print_r_html("lockFile: archivo $filename creado y lockeado con exito. ");
            return true;
        }
        else
        {
            Funciones::print_r_html("lockFile: archivo $filename está bloqueado. Cron aun está corriendo");
            die();
        }
    }

    public static function unlockFile()
    {
        $path = dirname(__FILE__);
        $filename = "$path/lockfile.txt";
        $f = fopen($filename, 'w+') or die("unlockFile: Cannot create lock file $filename");
        flock($f, LOCK_UN);
        if( file_exists($filename) )
            unlink($filename);
        Funciones::print_r_html("unlockFile: borro archivo");
    }
}

function bsale_fatal_handler()
{

    $errfile = "unknown file";
    $errstr = "shutdown";
    $errno = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if( $error !== NULL )
    {
        $errno = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr = $error["message"];

        //si es el mensaje de, no lo envio, es asunto del hosting
        //Error fatal :PHP Startup: Unable to load dynamic library 
        //'/usr/local/lib/php/extensions/no-debug-non-zts-20100525/imagick.so' 
        //- libMagickWand.so.2: cannot open shared object file: No such file or directory:
        if( strpos($errstr, 'libMagickWand') !== false )
            return;
        //Error fatal :mysqli_connect(): (HY000/1130): Host 'localhost' is not allowed to connect to this MySQL server:
        /* if ( strpos( $errstr, 'mysqli_connect' ) !== false )
          {

          } */


        //envio email de aviso
        $utils = new Utils();
        $email_cliente = EMAIL_TEST_CC;
        $subject = "Error fatal :$errstr";
        $message = "<h3>Error fatal :$errstr:</h3>" .
                "<p>Archivo $errline, linea$errfile: $errstr</p>";

        $hoy = date('d-m-Y H:i:s');
        $fichero = dirname(__FILE__) . 'logerror.log';
        $txt = "$hoy FATAL HANDLER\n$subject\n$message\n";
        //file_put_contents( $fichero, $txt, FILE_APPEND | LOCK_EX );
        //$utils->sendEmail( $email_cliente, $subject, $message );
        //Funciones::print_r_html($message, $subject);
        //no logueo nada, porque no se puede       
        // error_mail( format_error( $errno, $errstr, $errfile, $errline ) );
    }
}

function bsale_say_goodbye()
{
    if( connection_aborted() )
    {
        //  Perform some action if user has aborted the transaction
    }
    elseif( connection_status() == CONNECTION_TIMEOUT )
    {
        Funciones::print_r_html("ERROR: Script timeout!");
        $utils = new Utils();
        $email_cliente = EMAIL_TEST_CC;
        $subject = "Error Script timeout";
        $message = "<h3>Error timeout</h3>" .
                "<p>" . gethostname() . ': ' . Funciones::print_r_html2("ERROR: Script timeout!") . "</p>";

        $hoy = date('d-m-Y H:i:s');
        $fichero = dirname(__FILE__) . 'logerror.log';
        $txt = "$hoy CONNECTION_TIMEOUT\n$subject\n$message\n";
        //file_put_contents( $fichero, $txt, FILE_APPEND | LOCK_EX );


        $utils->sendEmail($email_cliente, $subject, $message);
        Funciones::print_r_html($message, $subject);
    }
    else
    {
        //  any normal completion actions
    }
}
