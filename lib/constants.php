<?php
define('BSALE_CAPABILITY_CONFIG_INTEGRAC', 'edit_posts');

define('RUT_FOREIGNER', '55555555-5'); //RUT PARA EXTRANJEROS 55.555.555-5
define('RUT_SIN_RUT', '11111111-1'); //en caso de que el comprador no haya indicado rut
/* exentas
  define('IMPUESTO_IVA_ID', 1);
  define('SKIP_IMPUESTO_IVA_ID', true);
 *  */
define('UPDATE_PRODUCT_PRICE_MAYORISTA', false);
//si en wc se debe chequear que el sku de cada item existe en bsale antes de agregarlo al dte
define('CHECK_SKU_EXISTS_IN_BSALE', false);


//stock cuando el prod bsale tiene stock si limite o allow negative stock
define('BSALE_STOCK_ILIMITADO', 999999);
define('BSALE_STOCK_SKU_NOT_EXISTS', -1);

//para cambiar visibilidad del catalogo de productos variables, que provienen de versiones anteriores de wc
define('WC_FIX_CATALOG_VISIBILITY_OLDER_PRODS', false);

//NOMBRE DE LA ACTION que se colocará para hacer bulk sync en el admin de prods de woocommerce
define('SYNC_TO_BSALE_BULK_ACTION_NAME', 'sync-to-bsale');

//incluir costo promeiod de cada variacion  de bsale?
define('BSALE_INCLUDE_VARIANT_COST', false);
//% de stock de bsale a dejar en woocoommer
//EJ: SI BSALE_WC_PERCENT_STOCK = 70% Y STOCK BSALE ES 100, SE DEJARÁN 70 DE STOCK
define('BSALE_WC_PERCENT_STOCK', 0);


define('BSALE_USE_REST_API', true);

//incluir en dte productos afectos y exentos?
define('BSALE_DTE_INCLUDE_AFECTOS_Y_EXENTOS', false);

//coma sepparated, tax clases de productos exentos a incluir en boletas afectas
//estos se deben incluir sin cobrar impuesto
define('BSALE_PRODUCTOS_EXENTOS_TAX_CLASSES', ''); //venta-exenta
//para emitir dtes afectos o exentos, segun prods del tax, solo para wc
//SOLO válido si BSALE_DTE_INCLUDE_AFECTOS_Y_EXENTOS==true
define('BOLETA_EXENTA_ID', 0);
define('BOLETA_EXENTA_DINAM_ATTR_ID', 0);
define('FACTURA_EXENTA_ID', 0);
define('FACTURA_EXENTA_DINAM_ATTR_ID', 0);

//peru, nc de boleta es distina a la nc para factura
define('NC_FACTURA_ID_PE', null);
define('NC_FACTURA_DINAM_ATTR_ID_PE', 0);




define('BSALE_BASE_URL', 'https://api.bsale.cl/');
//pronto: define('BSALE_BASE_URL', 'https://api.bsale.io/');

define('BSALE_BASE_URL_PE', 'https://api.bsale.com.pe/'); //api peru, para docs
//define('BSALE_BASE_URL_PE', 'https://api.bsale.io/');
//urls
define('BSALE_DOCUMENTOS_URL', BSALE_BASE_URL . 'v1/documents.json');
define('BSALE_TIPO_DOCTOS_URL', BSALE_BASE_URL . 'v1/document_types.json');

define('BSALE_NOTA_CREDITO_URL', BSALE_BASE_URL . 'v1/returns.json');
define('BSALE_GET_NOTA_CREDITO_URL', BSALE_BASE_URL . 'v1/returns/%s.json');
define('BSALE_NOTA_DEBITO_URL', BSALE_BASE_URL . 'v1/returns/%s/annulments.json');

//define('BSALE_GET_DOCTO_URL', BSALE_BASE_URL . 'v1/documents/%s/details.json');
define('BSALE_GET_DETALLES_DOCTO_URL', BSALE_BASE_URL . 'v1/documents/%s/details.json');
define('BSALE_GET_DOCTO_URL', BSALE_BASE_URL . 'v1/documents/%s.json?expand=[document_type,office,payments]');
//borrar un dte, se debe indicar la sucursal
define('BSALE_DELETE_DOCTO_URL', BSALE_BASE_URL . 'v1/documents/%s.json?officeId=%s');

define('BSALE_GET_CLIENTE', BSALE_BASE_URL . 'v1/clients/%s.json?expand=[contacts,payment_type]');
define('BSALE_GET_CLIENTES_URL', BSALE_BASE_URL . 'v1/clients.json');

define('BSALE_GET_CONDICIONES_VENTA', BSALE_BASE_URL . 'v1/sale_conditions.json?limit=100&offset=0');
define('BSALE_GET_ATRIBUTOS_DINAMICOS', BSALE_BASE_URL . 'v1/dynamic_attributes.json?limit=100&offset=0');
//get cliente
define('BSALE_GET_CLIENTE_URL', BSALE_BASE_URL . 'v1/clients/%s.json');
//get tipos de pago
define('BSALE_TIPO_PAGOS_URL', BSALE_BASE_URL . 'v1/payment_types.json');

/* bsale productos */
define('BSALE_PRODUCTOS_LISTADO_URL', BSALE_BASE_URL . 'v1/products.json?limit=%s&offset=%s'); //limit=100&offset=0
define('BSALE_PRODUCTO_DETALLE_URL', BSALE_BASE_URL . 'v1/products/%s.json');
define('BSALE_PRODUCTOS_COUNT_URL', BSALE_BASE_URL . 'v1/products/count.json');
define('BSALE_PRODUCTO_POST_URL', BSALE_BASE_URL . 'v1/products.json'); //para post (add)
define('BSALE_PRODUCTO_PUT_URL', BSALE_BASE_URL . 'v1/products/%s.json'); //para editar
define('BSALE_PRODUCTO_DELETE_URL', BSALE_BASE_URL . 'v1/products/%s.json'); //para borrar
//get variantes producto
define('BSALE_VARIANTES_URL', BSALE_BASE_URL . 'v1/variants.json'); //producto id en %s
//get una unica variante
define('BSALE_VARIANTE_URL', BSALE_BASE_URL . 'v1/variants/%s.json');
//costo de una variante
define('BSALE_VARIANTE_COSTS_URL', BSALE_BASE_URL . 'v1/variants/%s/costs.json');

define('BSALE_VARIANTES_COUNT_URL', BSALE_BASE_URL . 'v1/variants/count.json');
//get atributo variantes
define('BSALE_ATRIBUTOS_VARIANTES_URL', BSALE_BASE_URL . 'v1/variants/%s/attribute_values.json');

//get tipo de producto (marca)
define('ADD_TYPE_A_NOMBRE_PRODUCTO', false); //add o tipo de producto al nombre de este en cotizador
define('INCLUDE_TIPO_PRODUCTO_AS_VENDOR', false); //add tipo de producto como "vendor"
define('INCLUDE_NOMBRE_PRODUCTO_AS_VENDOR', false); //add nombre  de producto como "vendor"
define('BSALE_PRODUCTO_TYPE_URL', BSALE_BASE_URL . 'v1/product_types/%s.json');
define('BSALE_PRODUCTO_TYPE_GET_URL', BSALE_BASE_URL . 'v1/product_types.json');
define('BSALE_ATTRIB_PRODUCTO_TYPE_GET_URL', BSALE_BASE_URL . 'v1/product_types/%s/attributes.json');
define('BSALE_PRODUCTO_TYPE_COUNT_URL', BSALE_BASE_URL . 'v1/product_types/count.json');
//usuarios de bsale
define('BSALE_USERS_GET_URL', BSALE_BASE_URL . '/v1/users.json');

//get variantes producto
define('BSALE_PRODUCTO_VARIANTES_URL', BSALE_BASE_URL . 'v1/products/%s/variants.json'); //producto id en %s
//get stock prodcuto
define('BSALE_PRODUCTO_STOCK_URL', BSALE_BASE_URL . 'v1/stocks.json'); //param code, filtra por el SKU de la variante (String).
define('BSALE_DOCUMENTO_STOCK_URL', BSALE_BASE_URL . 'v2/stocks/documents/%s.json'); //%S = NRO DEL DOCTO, resourdeId de webhook
//get all listas de precio
define('BSALE_LISTAS_PRECIO_URL', BSALE_BASE_URL . 'v1/price_lists.json');
define('BSALE_SUCURSALES_URL', BSALE_BASE_URL . 'v1/offices.json'); //?limit=10&offset=0
define('BSALE_DESPACHO_URL', BSALE_BASE_URL . 'v1/shippings.json'); //?limit=10&offset=0
//Si deseas consultar la serie de una variante por api, puedes consumir el siguiente endpoint, pasando el id de la variante y sucursal. 
///v1/variants/:id/serials.json?officeid=1
define('BSALE_SERIALS_URL', BSALE_BASE_URL . 'v1/variants/%s/serials.json');

define('BSALE_DINAM_ATRIB_BOLETA_SERIE', false);
define('BSALE_DINAM_ATRIB_FACTURA_SERIE', false);
define('BSALE_DINAM_ATRIB_NV_SERIE', false);

define('BSALE_DINAM_ATRIB_BOLETA_MEDIO_ENVIO1', 0);
define('BSALE_DINAM_ATRIB_BOLETA_MEDIO_ENVIO2', 0);
define('BSALE_DINAM_ATRIB_FACTURA_MEDIO_ENVIO1', 0);
define('BSALE_DINAM_ATRIB_FACTURA_MEDIO_ENVIO2', 0);

//USADOS PARA colocar el peso de los productos en la boleta
define('BSALE_DINAM_ATRIB_BOLETA_PESO_PRODUCTOS', 0);
define('BSALE_DINAM_ATRIB_FACTURA_PESO_PRODUCTOS', 0);
define('BSALE_DINAM_ATRIB_NV_PESO_PRODUCTOS', 0);

//USADOS PARA colocar le direccion de despacho en el dte
define('BSALE_DINAM_ATRIB_BOLETA_DIR_DESPACHO', 0);
define('BSALE_DINAM_ATRIB_FACTURA_DIR_DESPACHO', 0);
define('BSALE_DINAM_ATRIB_NV_DIR_DESPACHO', 0);

define('RETIRO_TIENDA_STRING', 'retiro en tienda: $0');

//solo sync prods de estos tipos 1,2,3,4...
define('BSALE_PRODS_TYPE_ALLOWED', '');

//incluir nro de serie en dtes?
define('BSALE_INCLUDE_NRO_SERIE_EN_DTE', true);
/* * ***************************************************** */

//listado de sucursales desde las cuales se intentará emitir boleta si falla al
//emitirla dede casa matriz
define('BSALE_SUCURSAL_DTE_SUCURSALES', null); //'2,3'
//
//define('PACKS_SKU_PREFIJO', null); //'2,3'
//otras sucursales desde la que se sincroniza stock
define('BSALE_SUCURSAL_OTRAS_SUCURSALES_ID', null); //'2,3'
//si deseo sumar el stock de todas las sucursales 
define('BSALE_SUCURSAL_ALL_SUCURSALES_ID', false);

//restar al stock traído desde bsale
define('BSALE_RESTAR_A_STOCK_BSALE', 0);

//no sincronizar prodcuts cuyo nombre comience con xxx
define('BSALE_SKIP_PRODS_STARTS_WITH', null);
define('BSALE_SKIP_SKUS_STARTS_WITH', null);

//define('BSALE_LISTA_PRECIO_PESOS', 3); //shop=1 lista base, aa=2
//precios de desctos
define('BSALE_LISTA_PRECIOS_ESPECIALES_PESOS', 0);
define('BSALE_LISTA_PRECIOS_ESPECIALES_2', 0);
define('BSALE_LISTA_PRECIOS_ESPECIALES_3', 0);
define('BSALE_LISTA_PRECIOS_ESPECIALES_4', 0);
define('BSALE_LISTA_PRECIOS_ESPECIALES_5', 0);

//cantidad sobre la que corre el precio de descto por compra por cantidad
define('WC_TIERED_PRICE_AMOUNT', -1);

define('BSALE_LISTA_PRECIOS_ESPECIALES_2_NOMBRE', '');
define('BSALE_LISTA_PRECIOS_ESPECIALES_3_NOMBRE', '');

//get precio del producto.
define('BSALE_PRODUCTO_PRECIO_URL', BSALE_BASE_URL . 'v1/price_lists/%s/details.json'); //param code, filtra por el SKU de la variante (String).
//define('BSALE_PRODUCTO_PRECIO_ESPECIAL_URL', BSALE_BASE_URL . 'v1/price_lists/' . BSALE_LISTA_PRECIOS_ESPECIALES_PESOS . '/details.json');
define('BSALE_PRODUCTO_PRECIO_ESPECIAL2_URL', BSALE_BASE_URL . 'v1/price_lists/' . BSALE_LISTA_PRECIOS_ESPECIALES_2 . '/details.json');
define('BSALE_PRODUCTO_PRECIO_ESPECIAL3_URL', BSALE_BASE_URL . 'v1/price_lists/' . BSALE_LISTA_PRECIOS_ESPECIALES_3 . '/details.json');
define('BSALE_PRODUCTO_PRECIO_ESPECIAL4_URL', BSALE_BASE_URL . 'v1/price_lists/' . BSALE_LISTA_PRECIOS_ESPECIALES_4 . '/details.json');
define('BSALE_PRODUCTO_PRECIO_ESPECIAL5_URL', BSALE_BASE_URL . 'v1/price_lists/' . BSALE_LISTA_PRECIOS_ESPECIALES_5 . '/details.json');

//para incluir en dtes datos de pago webpay
define('WC_INCLUIR_WEBPAY_PAGO', false);
define('DINAM_ATRIB_WEBPAY_TARJETA_NV', '');
define('DINAM_ATRIB_WEBPAY_TRANSAC_ID_NV', '');
define('DINAM_ATRIB_WEBPAY_TARJETA_BOLETA', '');
define('DINAM_ATRIB_WEBPAY_TRANSAC_ID_BOLETA', '');
define('DINAM_ATRIB_WEBPAY_TARJETA_FACTURA', '');
define('DINAM_ATRIB_WEBPAY_TRANSAC_ID_FACTURA', '');

//para los casos en que woocommerce contiene el precio total (con iva) en el valor del 
//producto, es necesario sacarle le iva manualmente, el codigo no lo devuelve. default:true
define('BSALE_DESCONTAR_IVA_MANUAL', true);

define('IMPUESTO_IVA_ID', 1);
define('SKIP_IMPUESTO_IVA_ID', false);

//sucursal de Bsale dede donde se emite la gd
define('SUCURSAL_BSALE_GUIA_DESPACHO_FROM', 0);
//sucursal de bsale hacia donde se mueven los productos de la gd
define('SUCURSAL_BSALE_GUIA_DESPACHO_TO', 0);

define('DESPACHO_GD_TIPO_DESPACHO', 5); // "https://api.bsale.cl/v1/shipping_types/5.json",
//  "name": "Traslados internos (no constituye venta)",
//incluir gloss cost para que despacho aparezca sin margen en bsale?
//Si no es incluye, al despacho se le desglosará el iva
define('INCLUDE_GLOSS_COST', true);
define('INCLUDE_DELIVERY_ZERO', false);

//wholesale_customer_wholesale_price, _wholesale_price, b2bking_regular_product_price_group_33422
define('MAYORISTA_PRECIO_META_FIELD', null);
//con qué sistema se está integrando esto?
define('INTEGRACION_SISTEMA', 'woocommerce'); //woocommerce, shopify

define('DTE_PRECIOS_FROM_BSALE', false);

define('SKUS_SKIP_INTEGRACION_STOCK', null); //texto que debe contener el sku para ser omitido de la integracion
//
define('PREFIJO_ORDER', '');
//
//define('CREATE_PRODUCTS', false); //true o false
define('WC_INSERT_NUEVOS_AS_VARIANTES', false); //insertar nuevos como variantes
define('WC_INSERT_VARIANTES_AS_PRODS', true);

//separados por coma, no emitir dtes para los pedidos con este medio de envío
define('WC_SKIP_DTE_WITH_SHIPPING', null); //separados por coma
//mostrar puntos Bsale del customer en el dashboard
define('WC_MOSTRAR_PUNTOS_BSALE_CUSTOMER', false);

//agregar impuesto a envio?
define('SET_IMPUESTO_ENVIO', true); //true o false

define('VALIDAR_RUT', true); //true o false
//valor iva: 1.19 Chile, 1.18 Perú
define('VALOR_IVA', 1.19);
define('PAIS', 'CL'); //CL, PE
//anular nv y generar nota de crédito al cancelar un pedido?
define('WC_MEDIO_PAGO_BOLETA', 0);
define('WC_MEDIO_PAGO_FACTURA', 0);

//comuna despacho => suc bsale
define('WC_ENABLE_FACTURACION_COMUNA_DESPACHO', false);

//wc, colocar descto en boletas en base al precio normal del producto
//se suele usar en Perú
define('IS_SET_DESCTO_OF_REGULAR_PRICE_IN_DTE', false);

//usuario al que asociar la boleta emitida
define('SELLER_BSALE_ID_TO_BOLETA', 0); //id de vendedor para emitir boletas
define('SELLER_BSALE_ID_TO_FACTURA', 0);
define('SELLER_BSALE_ID_TO_NV', 0); //id de vendedor para emitir nv
define('PAYMENT_TYPE_ID_BOLETA', 0); //tipo de pago a usar cuando se emite una boleta
define('PAYMENT_TYPE_ID_FACTURA', 0);

define('ENABLE_PACKS_PRODS_WC', false);
define('PACKS_PREFIJO_WC', ''); //'crea tu'
define('PACKS_NOMBRE_WC', 'Packs de productos'); //'Packs de productos'
//tablas de la db
define('DB_TABLE_PRODUCTS', 'bsale_products');
define('DB_TABLE_VARIANTS', 'bsale_variants');
define('DB_TABLE_PRICES', 'bsale_prices');
define('DB_TABLE_STOCK', 'bsale_stock');
define('DB_TABLE_PRODUCTS_WC', 'bsale_products_wc');
define('DB_TABLE_BSALE_PRODUCT_TYPES', 'tipos_producto');
define('DB_TABLE_BSALE_PRODUCT_TYPES_ATTR', 'tipos_producto_atributos');
define('DB_TABLE_CONSOLIDADO_BSALE_PRODS', 'consolidado_productos');
DEFINE('DB_TABLE_SUCURSALES_BSALE', 'bsale_sucursales');
DEFINE('DB_TABLE_LISTAS_PRECIO_BSALE', 'bsale_listas_precio');
DEFINE('DB_TABLE_USUARIOS_BSALE', 'bsale_usuarios_bsale');
DEFINE('DB_TABLE_TIPO_DOCUMENTO_BSALE', 'bsale_tipo_documento');
DEFINE('DB_TABLE_TIPO_PAGO_BSALE', 'bsale_tipo_pago');
DEFINE('DB_TABLE_LOG_SYNC_BSALE', 'bsale_log_sync');
DEFINE('DB_TABLE_LOG_DTES_BSALE', 'bsale_dte_logs_table');
DEFINE('DB_TABLE_LOG_DTES_BSALE_WC', 'bsale_dte_logs_table_wc');

//tiempo máximo de espera para volver a descargar desde bsale los datos de tipos dte, tipos productos, usuarios, medios de pago
define('HOURS_DOWNLOAD_DATA_TIME', 24);

define('DB_PREFIX', 'bsale_');

if( INTEGRACION_SISTEMA === 'shopify' )
{
    require_once dirname(__FILE__) . '/shopify/shop_constants.php';
}


