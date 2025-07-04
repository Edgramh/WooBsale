<?php

require_once dirname(__FILE__) . '/../Autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TipoDocto
 *
 * @author angelorum
 */
class TipoDocto extends DocumentoAbstracto
{

    //put your code here
    public function getTiposDoc($codesii = null)
    {
        if( $codesii == null )
        {
            $url = BSALE_TIPO_DOCTOS_URL . '?limit=100&offset=0&state=0';
        }
        else
        {
            $url = BSALE_TIPO_DOCTOS_URL . "?codesii=$codesii";
        }
        $response_array = $this->get($url);

        if( isset($response_array) && isset($response_array['items']) )
        {
            $listado = $response_array['items'];
        }
        else
        {
            $listado = $response_array;
        }

        //dejo solo los campos que me sirven
        $arr = array();
        foreach( $listado as $c )
        {
            if( !isset($c['id']) )
            {
                continue;
            }
            $arr[] = array(
                'id' => $c['id'],
                'name' => $c['name'],
                'initialNumber' => $c['initialNumber'],
            );
        }


        return $arr;
    }

}
