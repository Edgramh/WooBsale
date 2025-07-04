<?php
require_once dirname(__FILE__) . '/../Autoload.php';


/**
 * Description of BsaleUsers
 *
 * @author Lex
 */
class BsaleUsers extends OCDB
{

    public static $table_name = 'bsale_users';

    function __construct()
    {
        parent::__construct();
        $this->setup_users();
    }

    /**
     * revisa los datos que viene por get y post y hace lo que se deba hacxer
     */
    public function do_process()
    {
        $module = isset($_REQUEST['module']) ? $_REQUEST['module'] : '';

        //recover password?
        if( $module == 'password_sent' )
        {
            $email_recover = isset($_REQUEST['email_recover']) ? $_REQUEST['email_recover'] : '';

            $res = $this->resend_pass($email_recover);
        }
        //guardo datos de ocnfig
        elseif( $module == 'update_settings' )
        {
            $res = $this->save_settings();
        }
    }

    public function save_settings()
    {
        //nada que updatear
        if( !isset($_POST['Submit']) )
        {
            return true;
        }
        //clave/valor a update
        $arraux = array();

        //obtnego datos por post
        $arraux['wc_bsale_token'] = isset($_POST['wc_bsale_token']) ? $_POST['wc_bsale_token'] : '';


        $arraux['wc_bsale_enable_inventario'] = isset($_POST['wc_bsale_enable_inventario']) ? (int) $_POST['wc_bsale_enable_inventario'] : 0;
        $arraux['wc_bsale_enable_facturacion'] = isset($_POST['wc_bsale_enable_facturacion']) ? (int) $_POST['wc_bsale_enable_facturacion'] : 0;

        $arraux['wc_bsale_shopify_set_normal_price'] = isset($_POST['wc_bsale_shopify_set_normal_price']) ? (int) $_POST['wc_bsale_shopify_set_normal_price'] : 0;
        $arraux['wc_bsale_shopify_usar_ticket_boletas'] = isset($_POST['wc_bsale_shopify_usar_ticket_boletas']) ? (int) $_POST['wc_bsale_shopify_usar_ticket_boletas'] : 0;

        $arraux['wc_bsale_casa_matriz_id'] = isset($_POST['wc_bsale_casa_matriz_id']) ? (int) $_POST['wc_bsale_casa_matriz_id'] : 0;
        $arraux['wc_bsale_sucursales_stock'] = isset($_POST['wc_bsale_sucursales_stock']) ? $_POST['wc_bsale_sucursales_stock'] : '';

        $arraux['wc_bsale_declare_sii'] = isset($_POST['wc_bsale_declare_sii']) ? (int) $_POST['wc_bsale_declare_sii'] : 0;
        $arraux['wc_bsale_send_email'] = isset($_POST['wc_bsale_send_email']) ? (int) $_POST['wc_bsale_send_email'] : 0;
        $arraux['wc_bsale_send_sku'] = isset($_POST['wc_bsale_send_sku']) ? (int) $_POST['wc_bsale_send_sku'] : 0;

//        $arraux['wc_bsale_enable_on_hold'] = isset($_POST['wc_bsale_enable_on_hold']) ? (int) $_POST['wc_bsale_enable_on_hold'] : 0;
//        $arraux['wc_bsale_enable_on_completed'] = isset($_POST['wc_bsale_enable_on_completed']) ? (int) $_POST['wc_bsale_enable_on_completed'] : 0;
//        $arraux['wc_bsale_enable_on_processing'] = isset($_POST['wc_bsale_enable_on_processing']) ? (int) $_POST['wc_bsale_enable_on_processing'] : 0;
//        $arraux['wc_bsale_enable_on_pending'] = isset($_POST['wc_bsale_enable_on_pending']) ? (int) $_POST['wc_bsale_enable_on_pending'] : 0;

        $arraux['wc_bsale_estados_nv'] = isset($_POST['wc_bsale_estados_nv']) ? $_POST['wc_bsale_estados_nv'] : '';
        $arraux['wc_bsale_payments_nv'] = isset($_POST['wc_bsale_payments_nv']) ? $_POST['wc_bsale_payments_nv'] : '';
        $arraux['wc_bsale_estados_dte'] = isset($_POST['wc_bsale_estados_dte']) ? $_POST['wc_bsale_estados_dte'] : '';
        $arraux['wc_bsale_estado_dte_cancelled'] = isset($_POST['wc_bsale_estado_dte_cancelled']) ? $_POST['wc_bsale_estado_dte_cancelled'] : 'cancelled';


        $arraux['wc_bsale_boleta_id'] = isset($_POST['wc_bsale_boleta_id']) ? (int) $_POST['wc_bsale_boleta_id'] : 0;
        $arraux['wc_bsale_factura_id'] = isset($_POST['wc_bsale_factura_id']) ? (int) $_POST['wc_bsale_factura_id'] : 0;
        $arraux['wc_bsale_nv_id'] = isset($_POST['wc_bsale_nv_id']) ? (int) $_POST['wc_bsale_nv_id'] : 0;
        $arraux['wc_bsale_nc_id'] = isset($_POST['wc_bsale_nc_id']) ? (int) $_POST['wc_bsale_nc_id'] : 0;
        $arraux['wc_bsale_gd_id'] = isset($_POST['wc_bsale_gd_id']) ? (int) $_POST['wc_bsale_gd_id'] : 0;
        $arraux['wc_bsale_ticket_id'] = isset($_POST['wc_bsale_ticket_id']) ? (int) $_POST['wc_bsale_ticket_id'] : 0;

        $arraux['wc_bsale_dinam_attr_boleta'] = isset($_POST['wc_bsale_dinam_attr_boleta']) ? (int) $_POST['wc_bsale_dinam_attr_boleta'] : 0;
        $arraux['wc_bsale_dinam_attr_factura'] = isset($_POST['wc_bsale_dinam_attr_factura']) ? (int) $_POST['wc_bsale_dinam_attr_factura'] : 0;
        $arraux['wc_bsale_dinam_attr_nv'] = isset($_POST['wc_bsale_dinam_attr_nv']) ? (int) $_POST['wc_bsale_dinam_attr_nv'] : 0;
        $arraux['wc_bsale_dinam_attr_nc'] = isset($_POST['wc_bsale_dinam_attr_nc']) ? (int) $_POST['wc_bsale_dinam_attr_nc'] : 0;
        $arraux['wc_bsale_dinam_attr_gd'] = isset($_POST['wc_bsale_dinam_attr_gd']) ? (int) $_POST['wc_bsale_dinam_attr_gd'] : 0;
        $arraux['wc_bsale_dinam_attr_ticket'] = isset($_POST['wc_bsale_dinam_attr_ticket']) ? (int) $_POST['wc_bsale_dinam_attr_ticket'] : 0;

        $arraux['wc_bsale_despachar_boleta'] = isset($_POST['wc_bsale_despachar_boleta']) ? (int) $_POST['wc_bsale_despachar_boleta'] : 0;
        $arraux['wc_bsale_despachar_factura'] = isset($_POST['wc_bsale_despachar_factura']) ? (int) $_POST['wc_bsale_despachar_factura'] : 0;
        $arraux['wc_bsale_despachar_gd'] = isset($_POST['wc_bsale_despachar_gd']) ? (int) $_POST['wc_bsale_despachar_gd'] : 0;

        $arraux['wc_bsale_anular_dtes_cancelled'] = isset($_POST['wc_bsale_anular_dtes_cancelled']) ? (int) $_POST['wc_bsale_anular_dtes_cancelled'] : 0;

        $arraux['wc_bsale_pagos_bsale'] = isset($_POST['wc_bsale_pagos_bsale']) ? $_POST['wc_bsale_pagos_bsale'] : '';

        $arraux['wc_bsale_lista_precios_id'] = isset($_POST['wc_bsale_lista_precios_id']) ? (int) $_POST['wc_bsale_lista_precios_id'] : 0;
         $arraux['wc_bsale_lista_precios_oferta_id'] = isset($_POST['wc_bsale_lista_precios_oferta_id']) ? (int) $_POST['wc_bsale_lista_precios_oferta_id'] : 0;

        $arraux['wc_bsale_create_prods'] = isset($_POST['wc_bsale_create_prods']) ? (int) $_POST['wc_bsale_create_prods'] : 0;
        $arraux['wc_bsale_update_stock'] = isset($_POST['wc_bsale_update_stock']) ? (int) $_POST['wc_bsale_update_stock'] : 0;
        $arraux['wc_bsale_update_price'] = isset($_POST['wc_bsale_update_price']) ? (int) $_POST['wc_bsale_update_price'] : 0;

        $arraux['wc_bsale_incluir_despacho_en_dte'] = isset($_POST['wc_bsale_incluir_despacho_en_dte']) ? (int) $_POST['wc_bsale_incluir_despacho_en_dte'] : 0;
        $arraux['wc_bsale_descontar_iva_precios'] = isset($_POST['wc_bsale_descontar_iva_precios']) ? (int) $_POST['wc_bsale_descontar_iva_precios'] : 0;

        //shopify
        $arraux['wc_bsale_shopify_api_key'] = isset($_POST['wc_bsale_shopify_api_key']) ? $_POST['wc_bsale_shopify_api_key'] : '';
        $arraux['wc_bsale_shopify_password'] = isset($_POST['wc_bsale_shopify_password']) ? $_POST['wc_bsale_shopify_password'] : '';
        $arraux['wc_bsale_shopify_tienda_url'] = isset($_POST['wc_bsale_shopify_tienda_url']) ? $_POST['wc_bsale_shopify_tienda_url'] : '';
        $arraux['wc_bsale_shopify_app_secret'] = isset($_POST['wc_bsale_shopify_app_secret']) ? $_POST['wc_bsale_shopify_app_secret'] : '';



        //guardo en db
        $options = new OpcionesTable();

        //Funciones::print_r_html($arraux, "save settings:");

        $array_datos = array( 'tipo' => 'settings' );

        foreach( $arraux as $k => $v )
        {
            $array_datos['clave'] = $k;
            $array_datos['valor'] = $v;

            $options->add($array_datos);
        }

        //recargo options de bsale
        $options->load_bsale_options();

        return true;
    }

    /**
     * genera nueva clave y la reenvía al email
     * @param type $email_recover
     */
    public function resend_pass($email_recover)
    {
        //valido email
        $is_valid_email = filter_var($email_recover, FILTER_VALIDATE_EMAIL);

        if( !$is_valid_email )
        {
            $this->add_msg("Email '$email_recover' no es válido. No se creará una nueva clave", 'danger');
            return false;
        }

        //existe este usuario en la db?
        $array_data = array( 'username' => $email_recover );

        $user = $this->get_user($array_data);

        if( !$user || count($user) <= 0 )
        {
            $this->add_msg("Email '$email_recover' no está registrado como usuario", 'danger');
            return false;
        }
        $user_id = $user[0]['id'];

        //genero nueva clave y envio correo
        $clave = password_hash(date('YmdHis'), PASSWORD_DEFAULT);
        $clave = substr($clave, 0, 10);

        $array_data['id'] = $user_id;
        $array_data['pass'] = $clave;

        $res = $this->insert_update($array_data);

        if( $res )
        {
            //url del sitio
            global $HOME_URL;
            $home_url = $HOME_URL;

            $utils = new Utils();

            $to_email = $email_recover;
            $subject = "[Integracion Bsale] cambio de clave";
            $message = "<p>Estimado(a) $email_recover</p>"
                    . "<p>Se ha cambiado la clave del usuario $email_recover.<br/>"
                    . "La nueva clave es:</p>"
                    . "<h3>$clave</h3>"
                    . "<p>Si desea ingresar al sitio, <a href='$home_url'>haga clic aquí.</a></p>"
                    . "<p>Que tenga un buen día.</p>";
            $utils->sendEmail($to_email, $subject, $message);
            $this->add_msg("Se ha enviado un correo a '$email_recover' con la nueva clave.", 'info');
        }
        else
        {
            $this->add_msg("No se pudo cambiar la clave de '$email_recover'. Intente de nuevo.", 'danger');
        }


        return true;
    }

    /**
     * crea al superusuario
     */
    public function setup_users()
    {
        $username = 'jason.matamala@gmail.com';
        $pass1 = 'soyunagaviot0';
        $array_data = array( 'username' => $username, 'pass' => $pass1 );

        $this->insert_update($array_data);
    }

    /**
     * saca los datos del post y actualiza datos de user y en la sesion
     */
    public function do_update_user()
    {
        $username = (!empty($_POST['txtUserEdit'])) ? $_POST['txtUserEdit'] : null;
        $pass1 = (!empty($_POST['txtPasswordEdit'])) ? $_POST['txtPasswordEdit'] : null;
        $pass2 = (!empty($_POST['txtPassword2Edit'])) ? $_POST['txtPassword2Edit'] : null;
        $user_id = (!empty($_POST['userIdEdit'])) ? $_POST['userIdEdit'] : null;

        if( $user_id == null || $username == null )
        {
            return "falta user id y/o username";
        }
        $array_data = array( 'username' => $username, 'id' => $user_id );

        if( $pass1 != null && $pass1 === $pass2 )
        {

            $array_data['pass'] = $pass1;
        }

        //Funciones::print_r_html($array_data, "do_update_user, data:");

        $this->insert_update($array_data);

        //update username en sesion
        $_SESSION['username'] = $username;

        $this->add_msg("Datos modificados con éxito.", 'info');
    }

    /**
     * devuelve si un user puede entrar al módulo o no
     */
    public function is_allowed($module)
    {
        if( !$this->is_logged() )
        {
            return false;
        }
        //Funciones::print_r_html("is_allowed a entrar a modulo  '$module'");

        return true;
    }

    /**
     * devuelve si un usario ha iniciado sesión o no
     */
    public function is_logged()
    {
        $logged = !empty($_SESSION['username']) && !empty($_SESSION['id']) ? true : false;

        if( $logged )
        {
            return true;
        }

        //si no se ha logueado, veo si intenta loguearse
        $txtUser = isset($_POST['txtUser']) ? $_POST['txtUser'] : null;
        $txtPassword = isset($_POST['txtPassword']) ? $_POST['txtPassword'] : null;

        //si falta uno de estos, no est´alogueado ni trata de loguearse
        if( empty($txtUser) || empty($txtPassword) )
        {
            return false;
        }

        //si vienen, try to log
        $arr = array( 'username' => $txtUser, 'pass' => $txtPassword );

        if( $this->login_user($arr) !== false )
        {
            return true;
        }
        else
        {
            //mensaje de error
        }
        return false;
    }

    /**
     * devuelve mensajes guardados en la sesion y los borra de la sesion despues
     * @param type $clear_after_return
     */
    public function get_messajes($clear_after_return = true)
    {
        $arraux = null;

        if( !empty($_SESSION['bsale_msg']) && is_array($_SESSION['bsale_msg']) )
        {
            $arraux = $_SESSION['bsale_msg'];
        }

        if( $clear_after_return )
        {
            unset($_SESSION['bsale_msg']);
        }
        return $arraux;
    }

    /**
     * myestra html con los msgs de la sesion y luego los borra de la sesión
     */
    public function display_msgs()
    {
        $arraux = $this->get_messajes();

        if( !is_array($arraux) || count($arraux) <= 0 )
        {
            return;
        }
        ?>
        <div class="container pt-5">
            <div class="row align-items-center">
                <div class="col">
                    <?php
                    foreach( $arraux as $a )
                    {
                        $type = $a['type'];
                        $msg = $a['msg'];
                        ?>
                        <div class="alert alert-<?php echo $type; ?>" role="alert">
                            <p><?php echo $msg; ?></p>                    
                        </div>

                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * guarda eun msg en la sesion del usuario
     * @param type $str
     * @param type $type
     */
    public function add_msg($str, $type = 'info')
    {
        if( !isset($_SESSION['bsale_msg']) || !is_array($_SESSION['bsale_msg']) )
        {
            $_SESSION['bsale_msg'] = array();
        }
        $_SESSION['bsale_msg'][] = array( 'msg' => $str, 'type' => $type );

        return $_SESSION['bsale_msg'];
    }

    public function get_user_logged()
    {

        if( !$this->is_logged() )
        {
            return false;
        }
        return $_SESSION;
    }

    public function logout_user()
    {
        //$_SESSION['username'] se borra si el tiempo maximo de sesion del hosting ha finalizado
        if( isset($_REQUEST['salir']) && $_REQUEST['salir'] === '1' && isset($_SESSION['username']) )
        {
            $username = $_SESSION['username'];
            unset($_SESSION['username']);
            unset($_SESSION['id']);
            session_destroy();

            $this->add_msg("Usuario '$username' ha cerrado sesion.", 'info');

            return true;
        }
        return false;
    }

    /**
     * ejemplo: 
     * aakash@eliteperfumes.cl
     * secret0@X
     * @param type $username
     * @param type $pass
     */
    public function login_user($array_data)
    {
        $username = isset($array_data['username']) ? $array_data['username'] : null;
        $pass = isset($array_data['pass']) ? $array_data['pass'] : null;

        if( empty($username) || empty($pass) )
        {
            return false;
        }

        $conn = $this->conectar();
        if( $conn == false )
        {
            return false;
        }
        $username = mysqli_real_escape_string($conn, $username);
        //pass hash
        //$pass_hash = password_hash($pass, PASSWORD_DEFAULT);



        $sql = "select * from " . self::$table_name . " where username= '$username' LIMIT 1;";

        //Funciones::print_r_html("login_user: $sql");

        if( !$result = mysqli_query($conn, $sql) )
        {
            die("get_all(): $sql: There was an error running the query ['" . mysqli_connect_error() . "']");
            return false;
        }
        $row = mysqli_fetch_assoc($result);

        $hashed_password = $row['pass'];


        if( !password_verify($pass, $hashed_password) )
        {
            $this->add_msg("La clave para '$username' no es correcta. Intente de nuevo.", 'info');
            return false;
        }
        unset($row['pass']);

        mysqli_free_result($result);

        //existe usuario?
        //guardo datos en sesion
        if( isset($row['username']) && $row['id'] )
        {
            $_SESSION['username'] = $row['username'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['modulo_name'] = $row['modulo_name'];
        }
        else
        {
            return false;
        }

        return $row;
    }

    public function insert_update($array_data)
    {
        $conn = $this->conectar();
        if( $conn == false )
        {
            return false;
        }

        $username = isset($array_data['username']) ? $array_data['username'] : null;
        $user_id = isset($array_data['id']) ? $array_data['id'] : null;
        $pass = isset($array_data['pass']) ? $array_data['pass'] : null;

        //no se usan por ahora
        $modulo_id = isset($array_data['modulo_id']) ? $array_data['modulo_id'] : 1;
        $modulo_name = isset($array_data['modulo_name']) ? $array_data['modulo_name'] : 'all';
        $estado = isset($array_data['estado']) ? $array_data['estado'] : 1;

        //email valido
        $is_valid_email = filter_var($username, FILTER_VALIDATE_EMAIL);

        if( !$is_valid_email )
        {
            // $username = null;
            Funciones::print_r_html("insert or update user, username no es email: '$username', se omite");
            return false;
        }

        //$username = mysqli_real_escape_string($conn, $username);
        //trunco
        $modulo_name = substr($modulo_name, 0, 4);
        //pass hash
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);


        //user existe?
        $user = $this->get_user($array_data);

        //usuario no existe, lo creo
        if( !$user )
        {
            $sql = "INSERT INTO " . self::$table_name .
                    "(`modulo_id`, `modulo_name`, `estado`, "
                    . "`username`, `pass`) "
                    . " VALUES ("
                    . "'$modulo_id', '$modulo_name', '$estado', "
                    . "'$username', '$pass_hash');";
        }
        //usuario existe, actualizo
        else
        {
            $sql = "UPDATE " . self::$table_name .
                    " SET username='$username' ";
            if( !empty($pass) )
            {
                $sql .= ", pass= '$pass_hash' ";
            }

            $sql .= " WHERE id='$user_id';";
        }

        //Funciones::print_r_html("insert_update: $sql");


        $Result = mysqli_query($conn, $sql);
        /* if($Result)
          {

          }
          mysqli_free_result($Result); */

        return $Result;
    }

    public function get_user($array_data)
    {
        $username = isset($array_data['username']) ? $array_data['username'] : null;
        $user_id = isset($array_data['id']) ? (int) $array_data['id'] : null;
        $conn = $this->conectar();
        if( $conn == false )
        {
            return false;
        }
        $is_valid_email = filter_var($username, FILTER_VALIDATE_EMAIL);

        if( !$is_valid_email )
        {
            return false;
        }
        //$username = mysqli_real_escape_string($conn, $username);

        $sql = "select * from " . self::$table_name . " where ";
        if( $user_id )
        {
            $sql .= " id = '$user_id'; ";
        }
        else
        {
            $sql .= " username= '$username';";
        }

        // Funciones::print_r_html("get_user: $sql");

        if( !$result = mysqli_query($conn, $sql) )
        {
            die("get_all(): $sql: There was an error running the query ['" . mysqli_connect_error() . "']");
            return false;
        }

        $arr = array();
        while( $row = mysqli_fetch_assoc($result) )
        {
            unset($row['pass']);
            $arr[] = $row;
        }
        mysqli_free_result($result);

        if( count($arr) <= 0 )
        {
            return false;
        }
        else
        {
            return $arr;
        }
    }

    public function get_users()
    {
        $conn = $this->conectar();
        if( $conn == false )
        {
            return false;
        }

        $sql = "select * from " . self::$table_name . " order by username;";

        Funciones::print_r_html("get_users: $sql");

        if( !$result = mysqli_query($conn, $sql) )
        {
            die("get_all(): $sql: There was an error running the query ['" . mysqli_connect_error() . "']");
            return false;
        }

        $arr = array();
        while( $row = mysqli_fetch_assoc($result) )
        {
            unset($row['pass']);
            $arr[] = $row;
        }
        mysqli_free_result($result);

        return $arr;
    }

    public function delete_user($array_data)
    {
        $username = isset($array_data['username']) ? $array_data['username'] : null;
        $username_id = isset($array_data['id']) ? $array_data['id'] : null;

        $username = mysqli_real_escape_string($conn, $username);

        $sql = "delete from " . self::$_table . " WHERE username = '$username' ;";

        $conn = $this->conectar();
        if( $conn == false )
        {
            return;
        }
        Funciones::print_r_html("delete_user: $sql");
        $result = mysqli_query($conn, $sql);

        mysqli_free_result($result);

        return true;
    }

}
