<?php
//namespace woocommerce_bsalev2\lib\wp;
 /**
     * solo funciona con plugin "WPC Product Bundles for WooCommerce"
     * https://doc.wpclever.net/woosb/
     * 'WPC Product Bundles for WooCommerce is a plugin developed by WPClever 
     * for creating bundles of products. When you sell various products as a bundle, 
     * customers will get incentives and be encouraged to buy more.
     *  In addition, creating bundled products also helps your customers 
     * better decide which items can fit well together.'
     */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of WpBsale
 *
 * @author angelorum
 */
class BsaleWPCProductBundles
{

    //para guardar arreglo de ids de packs
    public $products_pack_ids;

    function __construct()
    {
        $this->products_pack_ids = array();
    }

    /**
     * solo funciona con plugin "WPC Product Bundles for WooCommerce"
     * https://doc.wpclever.net/woosb/
     * 'WPC Product Bundles for WooCommerce is a plugin developed by WPClever 
     * for creating bundles of products. When you sell various products as a bundle, 
     * customers will get incentives and be encouraged to buy more.
     *  In addition, creating bundled products also helps your customers 
     * better decide which items can fit well together.'
     * @param type $product_id
     * @param type $item
     * @return boolean
     */
    public function is_product_from_pack_to_skip($product_id, $item)
    {
        $enable_packs = Funciones::get_value('ENABLE_PACKS_PRODS_WC', false);
        if( !$enable_packs )
        {
            return false;
        }

        //para packs de productos: cuando son packs con prods fijos, en la boleta solo
        //se incluye el sku del pack y el precio de este. Los prods de este pack no
        //aparecerán en la boleta, ya que se descontaria de bsale el pack y los productos por separado, 
        //es decir, se descontaria stock dos veces. Los prods del pack tienen precio $0, por lo que no 
        //alterará el monto de la boleta
        //en el caso de los packs "crea tu caja", se deben colocar los productos, ya que estos son elegidos
        //por el comprador y no estan "fijos" en el pack
        $item_meta_data = $item->get_meta_data();
        $nombre = $item->get_name();

        // get only All item meta data even hidden (in an unprotected array)
        //$formatted_meta_data = $item->get_formatted_meta_data( '_', true );          
        //datos del primer campo meta              
        $meta_data = isset($item_meta_data[0]) ? $item_meta_data[0]->get_data() : null;

        $key_packs_productos = defined('PACKS_NOMBRE_WC') ? PACKS_NOMBRE_WC : 'Packs de productos';

        //esta clave viene en los productos pack (que estan formados por varios productos, ya sean 
        //packs fijos o que el comprador puede armar antes de agregar al carro
        $is_pack = isset($meta_data['key']) && $meta_data['key'] === $key_packs_productos ? true : false;

        //esta clave viene en los prods del cart que son parte de un pack
        $is_parte_de_pack = isset($meta_data['key']) && $meta_data['key'] === '_woosb_parent_id' ? true : false;

        //cuando la key==_woosb_parent_id, es decir, en los prods del carro que son parte de un pack,
        //en value viene el id del prodcuto padre (el producto pack del que es parte este producto)
        //en el caso de los prodds pack, viene el nombre del producto y no se usa
        $metadata_value = isset($meta_data['value']) ? $meta_data['value'] : '';

        //el prod pack empieza con "CREA TU"?
        $prefijo = defined('PACKS_PREFIJO_WC') ? PACKS_PREFIJO_WC : 'crea tu';
        $len_prefijo = strelen($prefijo);

        //no se ha colocado prefijo, no se puede saber qué packs son armados por el comprador
        if( $len_prefijo <= 0 )
        {
            return false;
        }

        $prefijo_pack = substr($nombre, 0, $len_prefijo);
        //!=0, es decir, si no es pack "crea tu caja
        $is_not_prod_crea_tu_caja = strcasecmp($prefijo_pack, $prefijo) != 0;


        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html("producto id=$product_id, prefijo '$prefijo_pack' es pack? " .
                    ( $is_pack ? 'SÍ' : 'NO')
                    . ' ¿es "crea tu caja"? ' . ($is_not_prod_crea_tu_caja ? 'SÍ' : 'NO')
                    . ' ¿es parte de pack? ' . ($is_parte_de_pack ? 'SÍ' : 'NO'));

            //Funciones::print_r_html($meta_data, "item meta data");
        }

        if( $is_pack && $is_not_prod_crea_tu_caja )
        {
            //agrego id de prod a prod pack,
            //para consultarlo despues
            $this->add_prod_pack_id($product_id);


            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html($this->get_prod_pack_ids(), "agrego prod id = $product_id a arreglo de prods id de packs");
            }
        }
        //si prod es parte de un pack
        elseif( $is_parte_de_pack )
        {
            //key _woosb_parent_id contiene value= prod id padre pack
            $prod_parent_pack_id = (int) $metadata_value;

            //veo si el prod pack al que pertenece este product es un "crea tu caja"
            //en este caso los productos del pack "crea tu caja" deben aparecer en la boleta,
            //ya que no son fijos
            //si el prod pack id de este prod está dentro de este arreglo, 
            //entonces el prod pack NO ES agrega tu caja y el producto hijo no aparecerá en la boleta
            $skip_prod_parte_de_pack = $this->isset_prod_pack_id($prod_parent_pack_id);

            if( isset($_REQUEST['param']) )
            {
                Funciones::print_r_html("producto $product_id es parte de pack prefijo '$prefijo'? " .
                        ( $skip_prod_parte_de_pack ? 'SÍ' : 'NO'));
            }
        }

        return $skip_prod_parte_de_pack;
    }

    public function add_prod_pack_id($prod_id)
    {
        $this->products_pack_ids[$prod_id] = $prod_id;
    }

    public function get_prod_pack_ids()
    {
        return $this->products_pack_ids;
    }

    public function isset_prod_pack_id($prod_id)
    {
        //si viene null, lo inicializo como array
        if( $this->products_pack_ids == null || !is_array($this->products_pack_ids) )
        {
            $this->clear_prod_pack_ids();
        }
        return isset($this->products_pack_ids[$prod_id]);
    }

    public function clear_prod_pack_ids()
    {
        $this->products_pack_ids = array();
    }
}
