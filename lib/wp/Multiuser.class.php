<?php
//namespace woocommerce_bsalev2\lib\wp;
require_once dirname(__FILE__) . '/../Autoload.php';

//set session error handler
/* function session_error_handling_function($code, $msg, $file, $line) {
  echo("<p>Session error: $code '$msg', archivo $file, linea $line</p>");
  }

  set_error_handler('session_error_handling_function'); */

/**
 * Description of WoocommerceBsale
 *
 * @author Lex
 */
class Multiuser
{

    protected static $FILE_INDEX = 'index.php';
    protected static $FILE_WEBHOOK = 'bsale_product_webhook_mu.php';
    protected static $FILE_WEBHOOK_PROCESAR = 'bsale_product_wh_procesar_mu.php';
    protected static $FOLDER_RESEND = 'resend';

    /**
     * devuelve array con ids de wp users que tienen activada la integración con Bsale
     */
    public function get_users_bsale()
    {
        $arr = array();

        // WP_User_Query arguments
        $args = array(
            'role' => 'seller',
            'order' => 'DESC',
            'orderby' => 'ID',
        );

// The User Query
        $user_query = new WP_User_Query($args);

// The User Loop
        if( empty($user_query->results) )
        {
            return $arr;
        }
        foreach( $user_query->results as $user )
        {
            $user_id = $user->ID;

            //echo '<li><span>' . esc_html($user->shop_name) . '</span></li>';
            //tiene enabled la integracion con Bsale?
            $token = get_user_meta($user_id, 'parameters_dokan_token', true);

            if( !empty($token) )
            {
                $arr[] = $user_id;
            }
        }

        //query administrator     
        // WP_User_Query arguments
        $args = array(
            'role' => 'administrator',
            'order' => 'DESC',
            'orderby' => 'ID',
        );

// The User Query
        $user_query = new WP_User_Query($args);

// The User Loop
        if( empty($user_query->results) )
        {
            return $arr;
        }
        foreach( $user_query->results as $user )
        {
            $user_id = $user->ID;

            //echo '<li><span>' . esc_html($user->shop_name) . '</span></li>';
            //tiene enabled la integracion con Bsale?
            $token = get_user_meta($user_id, 'parameters_dokan_token', true);

            if( !empty($token) )
            {
                $arr[] = $user_id;
            }
        }

        return $arr;
    }

    /**
     * devuelve path de la carpeta webhook pase
     * para el user id
     * @param type $user_id
     */
    public function get_base_dir($user_id, $relative = false)
    {
        if( !$relative )
        {
            $folder_tienda = dirname(__FILE__) . "/../../webhooks/tienda_{$user_id}/";
            if( file_exists($folder_tienda) )
            {
                $folder_tienda = realpath($folder_tienda) . '/';
            }
        }
        else
        {
            $folder_tienda = "webhooks/tienda_{$user_id}/";
        }

        return $folder_tienda;
    }

    public function get_webhook_path($user_id, $relative = false)
    {
        $file = $this->get_base_dir($user_id, $relative) . self::$FILE_WEBHOOK;

        return $file;
    }

    public function get_webhook_procesar_path($user_id, $relative = false)
    {
        $file = $this->get_base_dir($user_id, $relative) . self::$FILE_WEBHOOK_PROCESAR;

        return $file;
    }

    /**
     * devuelve archivo a copiar en las carpetas webhooks de cada tienda (user)
     * @return type
     */
    public function get_file_webhook()
    {
        $folder_tienda = dirname(__FILE__) . "/../../webhooks/" . self::$FILE_WEBHOOK;
        $folder_tienda = realpath($folder_tienda);

        return $folder_tienda;
    }

    /**
     * devuelve archivo que procesara los avisos del webhook.
     * Este se copiará en las carpetas webhooks de cada tienda
     * @return type
     */
    public function get_file_webhook_procesar()
    {
        $folder_tienda = dirname(__FILE__) . "/../../webhooks/" . self::$FILE_WEBHOOK_PROCESAR;
        $folder_tienda = realpath($folder_tienda);

        return $folder_tienda;
    }

    /**
     * Devuelve url o folder del webhook para el usewr id indicado
     * @param type $user_id
     * @param type $ret 'url', devuelve url. 'path', devuelve path absoluto 
     * @param type $create_if_no_exists crear estructura de archivos para este user en caso de que no exista
     */
    public function get_tienda_webhook($user_id, $ret = 'url', $create_if_no_exists = true)
    {
        if( empty($user_id) )
        {
            return null;
        }

        if( $create_if_no_exists )
        {
            $this->create_folder_webhook($user_id);
        }

        //devuelvo path
        if( $ret === 'path' )
        {
            $folder_tienda = $this->get_base_dir($user_id);
            $file_webhook = $this->get_webhook_path($user_id);

            return $file_webhook;
        }
        //devuelvo url
        if( $ret === 'url' )
        {
            global $woo_bsale_db_url;
            // $folder_tienda = $this->get_base_dir($user_id, true);
            $file_webhook = $this->get_webhook_path($user_id, true);
            $url = $woo_bsale_db_url . $file_webhook;
            return $url;
        }
    }

    /**
     * Devuelve url o folder del webhook para el usewr id indicado
     * @param type $user_id
     * @param type $ret 'url', devuelve url. 'path', devuelve path absoluto 
     * @param type $create_if_no_exists crear estructura de archivos para este user en caso de que no exista
     */
    public function get_tienda_webhook_procesar($user_id, $ret = 'url', $create_if_no_exists = true)
    {
        if( empty($user_id) )
        {
            return null;
        }

        if( $create_if_no_exists )
        {
            $this->create_folder_webhook($user_id);
        }

        //devuelvo path
        if( $ret === 'path' )
        {
            $folder_tienda = $this->get_base_dir($user_id);
            $file_webhook = $this->get_webhook_procesar_path($user_id);

            return $file_webhook;
        }
        //devuelvo url
        if( $ret === 'url' )
        {
            global $woo_bsale_db_url;
            // $folder_tienda = $this->get_base_dir($user_id, true);
            $file_webhook = $this->get_webhook_procesar_path($user_id, true);
            $url = $woo_bsale_db_url . $file_webhook;
            return $url;
        }
    }

    /**
     * devuelve path absoluto del folder resend, con / al final
     * @global type $woo_bsale_db_url
     * @param type $user_id
     * @param type $create_if_no_exists
     * @return string
     */
    public function get_tienda_folder_resend($user_id, $create_if_no_exists = true)
    {
        if( empty($user_id) )
        {
            return null;
        }

        if( $create_if_no_exists )
        {
            $this->create_folder_webhook($user_id);
        }

        $folder_tienda = $this->get_base_dir($user_id) . self::$FOLDER_RESEND . '/';
        return $folder_tienda;
    }

    /**
     * crea subfolders dentro de la carpeta /rot plugin/ webohooks, para recibir los avisos de bsale
     * además coipia archivo php que será llamado por bsale
     * @param type $tienda_id
     */
    public function create_folder_webhook($tienda_id)
    {
        $folder_tienda = $this->get_base_dir($tienda_id);

        $folders = array(
            $folder_tienda,
            "{$folder_tienda}resend/",
            "{$folder_tienda}notificaciones/",
            "{$folder_tienda}pendientes/",
            "{$folder_tienda}procesados/",
            "{$folder_tienda}fallidos/",
            "{$folder_tienda}logs/",
        );

        $res = false;

        foreach( $folders as $folder )
        {
            if( is_dir($folder) )
            {
                $res = true;
                continue;
            }
            //Funciones::print_r_html("create_folder_webhook($tienda_id), creando folder = '$folder'");
            $res = mkdir($folder, 0755, true);
        }

        //copia archivo php webhook
        $file = $this->get_webhook_path($tienda_id);
        $file_source = $this->get_file_webhook();
        $file_source_procesar = $this->get_file_webhook_procesar();

        //si archivo .php no existe, lo copio a esa carpeta
        if( !file_exists($file) )
        {
            /* Funciones::print_r_html("create_folder_webhook($tienda_id), copiando fil"
              . "copy($file_source, $folder_tienda" . self::$FILE_WEBHOOK . ")"); */
            copy($file_source, $folder_tienda . self::$FILE_WEBHOOK);
        }
        if( !file_exists($folder_tienda . self::$FILE_WEBHOOK_PROCESAR) )
        {
            copy($file_source_procesar, $folder_tienda . self::$FILE_WEBHOOK_PROCESAR);
        }
        if( !file_exists($folder_tienda . self::$FILE_INDEX) )
        {
            copy($file_source_procesar, $folder_tienda . self::$FILE_INDEX);
        }

        return $res;
    }

    public function send_to_url($url, $data)
    {
        $debug = isset($_REQUEST['param']);

        //en caso de error, reintentar 6 veces
        $i = 0;
        do
        {
            // Inicia cURL
            $session = curl_init($url);

            // Activa SSL
            // curl_setopt($session, CURLOPT_SSL_VERIFYHOST, 2); //antes era true
            // Configura cabeceras
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json'
            );
            curl_setopt($session, CURLOPT_HTTPHEADER, $headers);
            //curl_setopt($session, CURLOPT_URL, $url);
            // Indica que se va ser una petición POST
            curl_setopt($session, CURLOPT_POST, 1);
               // Agrega parámetros
            curl_setopt($session, CURLOPT_POSTFIELDS, $data);
            
            // Indica a cURL que retorne data
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);

           // curl_setopt($session, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
         
            //A given cURL operation should only take
            //30 seconds max.
            curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 400);
            curl_setopt($session, CURLOPT_TIMEOUT, 400); //timeout in seconds

            if( $debug )
            {
                // // CURLOPT_VERBOSE: TRUE to output verbose information.
                // Writes output to STDERR, 
                // -or- the file specified using CURLOPT_STDERR.
                curl_setopt($session, CURLOPT_VERBOSE, true);

                $streamVerboseHandle = fopen('php://temp', 'w+');
                curl_setopt($session, CURLOPT_STDERR, $streamVerboseHandle);
            }

            // Ejecuta cURL
            $response = curl_exec($session);
            $code = curl_getinfo($session, CURLINFO_HTTP_CODE);

            // Cierra la sesión cURL
            curl_close($session);
            if( $debug )
            {
                rewind($streamVerboseHandle);
                $verboseLog = stream_get_contents($streamVerboseHandle);

                echo ("<h4>cUrl verbose information:</h4>");
                echo ("<pre>" . htmlspecialchars($verboseLog) . "</pre>\n");
            }


            //error, espero 1 seg para reintentar
            if( $code >= 400 )
            {
                sleep(1);
            }
            else
            {
                break;
            }
            $i++;
        }
        while( $i < 2 );

        //Esto es sólo para poder visualizar lo que se está retornando
        $response_array = json_decode($response, true);
        $array['code'] = $code;
        $array['respuesta_array'] = $response_array;
        $array['respuesta_raw'] = $response;

        Funciones::print_r_html($data, "send_to_url '$url' datos post");
        Funciones::print_r_html($response, "send_to_url '$url' codigo: $code, respuesta");

        //si viene error, incluyo código http response
        if( $code >= 400 )
        {
            
        }
        return $array;
    }

}
