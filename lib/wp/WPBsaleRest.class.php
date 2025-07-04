<?php
//namespace woocommerce_bsalev2\lib\wp;
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of WPBsaleRest
 *
 * @author Lex
 */
class WPBsaleRest
{

    //recibe datos de bsale y los guarda en la carpeta "webhooks/notificaciones"
    public function bsale_webhook_endpoint(WP_REST_Request $request)
    {
        require_once dirname(__FILE__) . '/../../webhooks/bsale_product_webhook.php';
    }

    /**
     * procesar avisos del webhook desde url
     * @param WP_REST_Request $request
     */
    public function bsale_procesar_webhook_endpoint(WP_REST_Request $request)
    {
        require_once dirname(__FILE__) . '/../../webhooks/bsale_product_wh_procesar.php';
    }

    public function bsale_sync_stores_endpoint(WP_REST_Request $request)
    {
        require_once dirname(__FILE__) . '/../../sync_bsale_to_stores.php';
    }

    public function bsale_sync_product_endpoint(WP_REST_Request $request)
    {
        require_once dirname(__FILE__) . '/../../sync/product_sync_bsale.php';
    }
    public function bsale_resend_endpoint(WP_REST_Request $request)
    {
        require_once dirname(__FILE__) . '/../../webhooks/bsale_product_resend_single.php';
    }    
    

    public function bsale_test_sku_endpoint(WP_REST_Request $request)
    {
        require_once dirname(__FILE__) . '/../../test/test_serials.php';
    }

    public function bsale_test_pedido_endpoint(WP_REST_Request $request)
    {
        require_once dirname(__FILE__) . '/../../test/test_wp_order.php';
    }

}

/**
 * inicia api rest
 */
function bsale_codifica_init_api_rest()
{
    $t2m = new WPBsaleRest();
    //endpoint para webhooks de bsale
    register_rest_route(
            'wcbsalev2/v1', '/webhook/(?P<id>\d+)', array(
        'methods' => 'POST,GET',
        'callback' => array( $t2m, 'bsale_webhook_endpoint' ),
        'permission_callback' => '__return_true',
            )
    );

    //endpoint procesar webhook
    //endpoint para webhooks de bsale
   register_rest_route(
           'wcbsalev2/v1', '/webhook_procesar/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => array( $t2m, 'bsale_procesar_webhook_endpoint' ),
        'permission_callback' => '__return_true',
            )
    );
    //sync producto por ajax
   /* register_rest_route(
            'wcbsalev2/v1', '/syncprod/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => array( $t2m, 'bsale_sync_product_endpoint' ),
        'permission_callback' => '__return_true',
            )
    );*/
    //sync stores
   /* register_rest_route(
            'wcbsalev2/v1', '/syncstores/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => array( $t2m, 'bsale_sync_stores_endpoint' ),
        'permission_callback' => '__return_true',
            )
    );*/

    //test sku
   /* register_rest_route(
            'wcbsalev2/v1', '/testsku/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => array( $t2m, 'bsale_test_sku_endpoint' ),
        'permission_callback' => '__return_true',
            )
    );*/

    //test pedido
   /* register_rest_route(
            'wcbsalev2/v1', '/testpedido/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => array( $t2m, 'bsale_test_pedido_endpoint' ),
        'permission_callback' => '__return_true',
            )
    );*/
    
      //sync producto por ajax
    register_rest_route(
            'wcbsalev2/v1', '/resend/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => array( $t2m, 'bsale_resend_endpoint' ),
        'permission_callback' => '__return_true',
            )
    );
}
