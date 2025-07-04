<?php
//namespace woocommerce_bsalev2\lib\wp;
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Description of ConfigUtils
 *
 * @author Lex
 */
class ConfigUtils
{

    /**
     * devuelve array con datos de lp para sincrinozar
     * @param type $lp_id
     * @param type $tipo
     * @param type $lp_arr
     * @return type
     */
    public function get_lp_arr_to_sync($lp_id, $tipo, $lp_arr)
    {
        $lp_name = isset($lp_arr[$lp_id]) ? $lp_arr[$lp_id] : 'unknown';
        $arraux = array( 'lp_id' => $lp_id, 'name' => $lp_name, 'tipo' => $tipo );

        return $arraux;
    }

    /**
     * devuelve array con datos de sucursal para sincronizar stock
     * @param type $lp_id
     * @param type $tipo
     * @param type $lp_arr
     * @return type
     */
    public function get_stock_arr_to_sync($suc_id, $tipo, $sucursales_id_names_arr)
    {
        $name = isset($sucursales_id_names_arr[$suc_id]) ? $sucursales_id_names_arr[$suc_id] : null;

        //sucursal no existe
        if( $name == null )
        {
            return null;
        }
        $arraux = array( 'suc_id' => $suc_id, 'name' => $name, 'tipo' => $tipo );

        return $arraux;
    }

    /**
     * devuelve ul li html con listado de pasos para sincronizar
     * Esta lista será recorrida usando ajax, para conectarse al servidor en cada uno de los pasos
     * @param type $steps_arr
     */
    public function get_list_steps_to_sync($steps_arr)
    {
        if( !is_array($steps_arr) )
        {
            return '<p>no array</p>';
        }
        $html = '<ul id="steps_to_sync_list">';

        $i = 1;

        $li_arr = array();
        
        $checked = 'checked';//checked

        foreach( $steps_arr as $s )
        {
            $action = isset($s['action']) ? $s['action'] : '';
            $title = isset($s['title']) ? $s['title'] : '';
            $elem_name = "sync_$i";

            if( $action === 'get_prices' || $action === 'save_prices' )
            {
                //para sync precios
                $lp_id = isset($s['lp_id']) ? $s['lp_id'] : 0;
                $lp_name = isset($s['lp_name']) ? $s['lp_name'] : '';
                $lp_tipo = isset($s['lp_tipo']) ? $s['lp_tipo'] : '';

                $li_arr[] = "<li>($i)<input type='checkbox' id='$elem_name' $checked action='$action' class='step_sync' "
                        . "lp_id='$lp_id' lp_name='$lp_name' title='$title' lp_tipo='$lp_tipo'/>"
                        . "<label for='$elem_name'>$action para lp ($lp_id) $lp_name $lp_tipo</label></li>";
            }
            elseif( $action === 'get_stocks' || $action === 'save_stocks' )
            {
                //para sync stock
                $suc_id = isset($s['suc_id']) ? $s['suc_id'] : '';
                $suc_name = isset($s['suc_name']) ? $s['suc_name'] : '';
                $suc_tipo = isset($s['suc_tipo']) ? $s['suc_tipo'] : '';

                $li_arr[] = "<li>($i)<input type='checkbox' id='$elem_name' $checked action='$action' class='step_sync' "
                        . "title='$title' suc_id='$suc_id' suc_name='$suc_name' suc_tipo='$suc_tipo'/>"
                        . "<label for='$elem_name'>$action para lp ($suc_id) $suc_name $suc_tipo</label></li>";
            }
            else
            {
                //action sin params
                $li_arr[] = "<li>($i)<input type='checkbox' id='$elem_name' $checked action='$action' title='$title' class='step_sync'/>"
                        . "<label for='$elem_name'>$action sin parámetros</label></li>";
            }

            $i++;
        }
        $li_str = implode("\n", $li_arr);

        $html .= $li_str . '</ul>';
        return $html;
    }

    /**
     * checkboxes para marcar estados de pedido, incluyendo los personalizados
     * @param type $arr
     * @param type $tag_start
     * @param type $tag_end
     * @return string
     */
    public function codifica_int_display_checkboxes($arr)
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $selected_arr = isset($arr['selected_arr']) ? $arr['selected_arr'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = "<div class='checkbox_p $class_container'>"
                . "<p class='chb_title'><strong>$label</strong></p>"
                . '<ul class="sucursales_ul list_cols4">';
        //para prevenir ids de label iguales 
        $hash = rand(strlen($select_name), strlen($select_name) * 2);

        foreach( $options_arr as $k => $v )
        {
            $selected = in_array($k, $selected_arr) ? 'checked="checked"' : '';
            $selected_style = !empty($selected) ? 'class="check_sel"' : '';

            $input .= "<li $selected_style><input type='checkbox' name='$select_name' $selected value='$k' id='status{$k}_$hash' />" .
                    "<label for='status{$k}_$hash'>{$v}</label></li>";
        }

        //agrego checkboxes de estados personalizados

        $input .= '</ul>';

        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text";
            $input .= "</span>\n";
        }

        $input .= '</div>';

        return $input;
    }

    /**
     * config: muestra selecto y sleeciona la opcion indicada
     */
    public function codifica_int_display_select($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $key_to_select = isset($arr['value']) ? $arr['value'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $select_text = isset($arr['select_text']) ? $arr['select_text'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $html = '';
        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "<$tag_start class='field_wc field_{$select_name} $class_container'>";
        }
        //si he seleccionado "sí" value=1, entonces descatar el select
        //en este caso, los values pueden  ser 0, 1, y quizás un tercero
        $sel_class = ($key_to_select == 1 && count($options_arr) == 2) ? 'class="select_yes"' : '';

        $html .= "<label title='$key_to_select' for='$select_name'>$label</label>" .
                "<select id='$select_name' name='$select_name' $sel_class>";

        if( !empty($select_text) )
        {
            $html .= "<option value=''>$select_text</option>";
        }

        foreach( $options_arr as $k => $v )
        {
            $selected = ($key_to_select == $k) || ($key_to_select === $k) ? 'selected="selected"' : '';
            $html .= "<option $selected value='$k'>$v</option>";
        }

        $html .= '</select>';

        if( !empty($help_text) )
        {
            $html .= "<span class='help_bsale'>$help_text</span>";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "</$tag_end>";
        }


        return $html;
    }

    /**
     * config: muestra input text o number con el value indicado
     * @param type $arr
     */
    public function codifica_int_display_text($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $name = isset($arr['name']) ? $arr['name'] : '';
        $value = isset($arr['value']) ? $arr['value'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';
        //para type number
        $type = isset($arr['type']) ? $arr['type'] : 'text';
        $min = isset($arr['min']) ? $arr['min'] : '';
        $step = isset($arr['step']) ? $arr['step'] : '';

        $html = '';

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "<$tag_start class='field_wc field_{$name} $class_container'>";
        }


        $html .= "<label for='$name' title='$label'>$label</label>" .
                "<input type='$type' id='$name' name='$name' value='$value' ";

        if( $min !== '' )
        {
            $html .= " min='$min' ";
        }
        if( !empty($step) )
        {
            $html .= " step='$step' ";
        }

        $html .= '/>';

        if( !empty($help_text) )
        {
            $html .= "<span class='help_bsale'>$help_text</span>";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "</$tag_end>";
        }

        return $html;
    }

    public function codifica_int_display_number($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $name = isset($arr['name']) ? $arr['name'] : '';
        $value = isset($arr['value']) ? $arr['value'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';
        //para type number       
        $min = isset($arr['min']) ? $arr['min'] : '';
        $step = isset($arr['step']) ? $arr['step'] : '';

        $html = '';

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "<$tag_start class='field_wc field_{$name} $class_container'>";
        }

        $html .= "<label for='$name' title='$label'>$label</label>" .
                "<input type='number' id='$name' name='$name' value='$value' ";

        if( $min !== '' )
        {
            $html .= " min='$min' ";
        }
        if( !empty($step) )
        {
            $html .= " step='$step' ";
        }

        $html .= '/>';

        if( !empty($help_text) )
        {
            $html .= "<span class='help_bsale'>$help_text</span>";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "</$tag_end>";
        }

        return $html;
    }

    public function codifica_int_display_textarea($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $name = isset($arr['name']) ? $arr['name'] : '';
        $value = isset($arr['value']) ? $arr['value'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';
        $placeholder = isset($arr['placeholder']) ? $arr['placeholder'] : '';
        $rows = isset($arr['rows']) ? $arr['rows'] : '4';
        $cols = isset($arr['cols']) ? $arr['cols'] : '100';

        $html = '';
        //armo el html
        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "<$tag_start class='field_wc field_{$name} $class_container textarea_cont'>";
        }

        $html .= "<label for='$name' title='$label'>$label</label>";


        $html .= "<textarea id='$name' cols='$cols' rows='$rows' name='$name' placeholder='$placeholder'>$value</textarea>";
        if( !empty($help_text) )
        {
            $html .= "<span class='help_bsale'>$help_text</span>";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $html .= "</$tag_end>";
        }

        return $html;
    }

    public function codifica_int_display_select_bsale($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $key_to_select = isset($arr['value']) ? $arr['value'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $select_text = isset($arr['select_text']) ? $arr['select_text'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = '';

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "<$tag_start class='field_wc field_{$select_name} $class_container'>";
        }

        $input .= "<label for='$select_name'>$label</label>\n";

        $input .= "<select id='$select_name' name='$select_name'>\n";

        if( !empty($select_text) )
        {
            $input .= "<option value=''>$select_text</option>\n";
        }

        foreach( $options_arr as $k => $v )
        {
            $selected = ($key_to_select == $k) || ($key_to_select === $k) ? 'selected' : '';
            $input .= "<option $selected value='$k'>$v</option>\n";
        }

        $input .= "</select>\n";
        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text</span>\n";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "</$tag_end>";
        }

        return $input;
    }

    public function codifica_int_display_select_sucursal_bsale($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $wc_bsale_casa_matriz_id = isset($arr['value']) ? $arr['value'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $select_text = isset($arr['select_text']) ? $arr['select_text'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = '';

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "<$tag_start class='field_wc field_{$select_name} $class_container'>";
        }

        $input .= "<label for='$select_name' title='$wc_bsale_casa_matriz_id'>$label</label>\n";

        $input .= "<select id='$select_name' name='$select_name'>\n";

        if( !empty($select_text) )
        {
            $input .= "<option value=''>$select_text</option>\n";
        }

        foreach( $options_arr as $lp )
        {
            $status = $lp['state'];

            $selected = $lp['id'] == $wc_bsale_casa_matriz_id ? 'selected="selected"' : '';
            $enabled = $status > 0 ? '' : '';
            $status_style = $status > 0 ? 'color:#bf0000; text-decoration: line-through;' : '';

            if( !empty($selected) )
            {
                $suc_principal_selected = true;
            }

            $input .= "<option $selected value='{$lp['id']}' style='$status_style'>{$lp['name']} $enabled</option>";
        }

        $input .= "</select>\n";
        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text";

            if( $wc_bsale_casa_matriz_id > 0 && !isset($suc_principal_selected) )
            {
                $input .= "<br/><i class='error_bsale'>ERROR: La sucursal Bsale #$wc_bsale_casa_matriz_id no existe." .
                        "Debe asignar otra.</i>";
            }
            $input .= "</span>\n";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "</$tag_end>";
        }

        return $input;
    }

    /**
     * muestra select con listas de precios de bsale
     * @param type $arr
     * @param type $tag_start
     * @param type $tag_end
     * @return type
     */
    public function codifica_int_display_select_lp_bsale($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $wc_bsale_casa_matriz_id = isset($arr['value']) ? $arr['value'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $select_text = isset($arr['select_text']) ? $arr['select_text'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = '';

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "<$tag_start class='field_wc field_{$select_name} $class_container'>";
        }

        $input .= "<label for='$select_name'>$label</label>\n";

        $input .= "<select id='$select_name' name='$select_name'>\n";

        if( !empty($select_text) )
        {
            $input .= "<option value=''>$select_text</option>\n";
        }

        foreach( $options_arr as $lp )
        {
            $status = $lp['state'];

            $selected = $lp['id'] == $wc_bsale_casa_matriz_id ? 'selected="selected"' : '';
            $enabled = $status > 0 ? '' : '';
            $status_style = $status > 0 ? 'color:#bf0000; text-decoration: line-through;' : '';

            if( !empty($selected) )
            {
                $suc_principal_selected = true;
            }

            $input .= "<option $selected value='{$lp['id']}' style='$status_style'>{$lp['name']} $enabled</option>";
        }

        $input .= "</select>\n";
        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text";

            if( $wc_bsale_casa_matriz_id > 0 && !isset($suc_principal_selected) )
            {
                $input .= "<br/><i class='error_bsale'>ERROR: La sucursal Bsale #$wc_bsale_casa_matriz_id no existe." .
                        "Debe asignar otra.</i>";
            }
            $input .= "</span>\n";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "</$tag_end>";
        }

        return $input;
    }

    public function codifica_int_display_select_dtes_bsale($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $wc_bsale_casa_matriz_id = isset($arr['value']) ? $arr['value'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $select_text = isset($arr['select_text']) ? $arr['select_text'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = '';

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "<$tag_start class='field_wc field_{$select_name} $class_container'>";
        }

        $input .= "<label for='$select_name'>$label</label>\n";

        $input .= "<select id='$select_name' name='$select_name'>\n";

        if( !empty($select_text) )
        {
            $input .= "<option value=''>$select_text</option>\n";
        }

        foreach( $options_arr as $lp )
        {
            $selected = $lp['id'] == $wc_bsale_casa_matriz_id ? 'selected="selected"' : '';

            if( !empty($selected) )
            {
                $suc_principal_selected = true;
            }

            $input .= "<option $selected value='{$lp['id']}'>{$lp['name']}</option>";
        }

        $input .= "</select>\n";
        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text";

            if( $wc_bsale_casa_matriz_id > 0 && !isset($suc_principal_selected) )
            {
                $input .= "<br/><i class='error_bsale'>ERROR: La sucursal Bsale #$wc_bsale_casa_matriz_id no existe." .
                        "Debe asignar otra.</i>";
            }
            $input .= "</span>\n";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "</$tag_end>";
        }

        return $input;
    }

    /**
     * muestra select para elegir un vendedor
     * @param type $arr
     * @return string
     */
    public function codifica_int_display_select_seller_bsale($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $seller_id = isset($arr['value']) ? $arr['value'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $select_text = isset($arr['select_text']) ? $arr['select_text'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = '';
        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "<$tag_start class='field_wc field_{$select_name} $class_container'>";
        }

        $input .= "<label for='$select_name'>$label</label>\n";

        $input .= "<select id='$select_name' name='$select_name'>\n";

        if( !empty($select_text) )
        {
            $input .= "<option value=''>$select_text</option>\n";
        }

        foreach( $options_arr as $lp )
        {
            $status = $lp['state'];
            $nombre = $lp['firstName'] . ' ' . $lp['lastName'] . '-' . $lp['email'];

            $selected = $lp['id'] == $seller_id ? 'selected="selected"' : '';
            $enabled = $status > 0 ? '' : '';
            $status_style = $status > 0 ? 'color:#bf0000; text-decoration: line-through;' : '';

            if( !empty($selected) )
            {
                $seller_selected = true;
            }

            $input .= "<option $selected value='{$lp['id']}' style='$status_style'>$nombre $enabled</option>";
        }

        $input .= "</select>\n";
        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text";

            if( $seller_id > 0 && !isset($seller_selected) )
            {
                $input .= "<br/><i class='error_bsale'>ERROR: El vendedor #$seller_id no existe en Bsale." .
                        "Debe asignar otro.</i>";
            }
            $input .= "</span>\n";
        }

        if( !empty($tag_start) && !empty($tag_end) )
        {
            $input .= "</$tag_end>";
        }

        return $input;
    }

    /**
     * muestra listado de checkboxes con sucursales bsale, para elegir una o varias
     * @param type $arr
     * @return string
     */
    public function codifica_int_display_checkboxes_sucursales_bsale($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $selected_arr = isset($arr['selected_arr']) ? $arr['selected_arr'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = '<div class="checkbox_p">'
                . "<p class='chb_title'><strong>$label</strong></p>"
                . '<ul class="sucursales_ul list_cols4">';

        foreach( $options_arr as $lp )
        {
            $status = $lp['state'];

            $selected = in_array($lp['id'], $selected_arr) ? 'checked="checked"' : '';
            $selected_style = !empty($selected) ? 'class="check_sel"' : '';

            $enabled = $status > 0 ? '' : '';
            $status_style = $status > 0 ? 'color:#bf0000; text-decoration: line-through;' : '';


            $input .= "<li $selected_style><input type='checkbox' name='$select_name' $selected value='{$lp['id']}' id='sucursal_{$lp['id']}' />" .
                    "<label for='sucursal_{$lp['id']}' style='$status_style'>{$lp['name']}</label></li>";
        }

        $input .= '</ul>';

        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text";
            $input .= "</span>\n";
        }

        $input .= '</div>';

        return $input;
    }

    /**
     * muestra asociacios de medio de pago wc a medio de pago de bsale
     * @param type $wc_pagos_arr
     * @param type $wc_pagos_bsale_arr
     */
    public function codifica_int_display_medios_pago_wc_bsale($arr)
    {
        $label = isset($arr['label']) ? $arr['label'] : '';
        $wc_pagos_arr = isset($arr['wc_pagos_arr']) ? $arr['wc_pagos_arr'] : array();
        $wc_pagos_bsale_arr = isset($arr['wc_pagos_bsale_arr']) ? $arr['wc_pagos_bsale_arr'] : array();
        $wc_bsale_pagos_bsale = isset($arr['wc_bsale_pagos_bsale']) ? $arr['wc_bsale_pagos_bsale'] : '';
        $tipos_pago_arr = isset($arr['tipos_pago_arr']) ? $arr['tipos_pago_arr'] : array();
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';

        $wc_pagos_arr2 = $wc_pagos_arr;
        //agrego medio de pago por defecto
        $wc_pagos_arr2[] = array( 'enabled' => 'yes', 'id' => 'default', 'title' => 'Pago por defecto' );

        $html = '';
        foreach( $wc_pagos_arr2 as $pago )
        {

            $status = $pago['enabled'];
            $style = $status !== 'yes' ? ' style="text-decoration: line-through;" ' : '';

            $select_name = 'pago_' . $pago['id'];
            $pago_wc_id = $pago['id'];

            $html .= '<p>';

            $html .= "<label for='$select_name' $style title='$pago_wc_id'>{$pago['title']}</label>";

            $html .= "<select id='$select_name' class='bsale_select_pagos' data-wcpago='$pago_wc_id'>";
            $html .= "<option value=''>-seleccione-</option>";
            foreach( $tipos_pago_arr as $lp )
            {

                $status = $lp['state'];
                $enabled = $status > 0 ? '(desactivado en Bsale)' : '';
                $status_style = $status > 0 ? 'color:#bf0000;' : '';

                $selected = isset($wc_pagos_bsale_arr[$pago_wc_id]) && $wc_pagos_bsale_arr[$pago_wc_id] == $lp['id'] ? 'selected="selected"' : '';

                $html .= "<option $selected value='{$lp['id']}' style='$status_style'>{$lp['name']} $enabled</option>";
            }


            $html .= '</select>'
                    . '</p>';
        }

        $html .= "<p><span class='help_bsale'>$help_text</span></p>";

        //textarea oculta
        $html .= "<div style='display: none'>" .
                "<textarea id='wc_bsale_pagos_bsale' cols='60' rows='15' name='wc_bsale_pagos_bsale'>$wc_bsale_pagos_bsale</textarea>" .
                '</div>';

        return $html;
    }

    public function codifica_int_display_checkboxes_tipos_producto_bsale($arr, $tag_start = 'p', $tag_end = 'p')
    {
        $label = isset($arr['label']) ? $arr['label'] : 'no label';
        $select_name = isset($arr['name']) ? $arr['name'] : '';
        $selected_arr = isset($arr['selected_arr']) ? $arr['selected_arr'] : '';
        $options_arr = isset($arr['options_arr']) ? $arr['options_arr'] : '';
        $help_text = isset($arr['help_text']) ? $arr['help_text'] : '';
        $class_container = isset($arr['class_container']) ? $arr['class_container'] : '';

        $input = '<div class="checkbox_p">'
                . "<p class='chb_title'><strong>$label</strong></p>"
                . '<ul class="prod_types_ul list_cols4">';

        foreach( $options_arr as $lp )
        {
            $status = $lp['state'];

            $selected = in_array($lp['id'], $selected_arr) ? 'checked="checked"' : '';
            $selected_style = !empty($selected) ? 'class="check_sel"' : '';

            $enabled = $status > 0 ? '' : '';
            $status_style = $status > 0 ? 'color:#bf0000; text-decoration: line-through;' : '';

            $input .= "<li $selected_style><input type='checkbox' name='$select_name' $selected value='{$lp['id']}' id='types_{$lp['id']}' />" .
                    "<label for='types_{$lp['id']}' style='$status_style'>{$lp['name']} $enabled</label></li>";
        }

        $input .= '</ul>';

        if( !empty($help_text) )
        {
            $input .= "<span class='help_bsale'>$help_text";
            $input .= "</span>\n";
        }

        $input .= '</div>';

        return $input;
    }

    /**
     * muestra select para elegir dte (bol, fact) set dinam attr para nro de pedido y notas
     * @param type $arr
     * @return string
     */
    public function codifica_int_display_text_dtes($arr_select_dte, $arr_input_nro_pedido, $arr_input_notas_pedido)
    {
        $html = $this->codifica_int_display_select_dtes_bsale($arr_select_dte, '', '');
        $html .= $this->codifica_int_display_number($arr_input_nro_pedido, '', '');
        if( !empty($arr_input_notas_pedido) )
        {
            $html .= $this->codifica_int_display_number($arr_input_notas_pedido, '', '');
        }

        return '<p class="dte_p">' . $html . '</p>';
    }

    /**
     * vuelve a descargar desde bsale datos si es que ya  han pasado más de 1 hora desde la última descarga, 
     * 
     */
    public function check_reload_data_time($data = 'all')
    {
        return $this->reload_data_from_bsale('all', true);
    }

    /**
     * recarga de bsale datos de: listas de precio, usuarios, sucursales, tipos de dte, tipos de pago, tipos de producto,
     * 
     * @param string $data
     * @param type $check_hours_after_last_load Si es mayor a cero, solo recargará los datos de bsale si es que han pasado 
     * más de $check_hours_after_last_load horas desde la última vez que se cargó
     * @return boolean
     */
    public function reload_data_from_bsale($data = 'all', $check_hours_after_last_load = 0)
    {
        //si se indica hora, reviso cuando se descargaron por última vez estos datos
        if( $check_hours_after_last_load > 0 )
        {
            $obj = new UsuariosBsale();
            $hours = $obj->get_last_hours_data_loaded();
            $hours_amount = Funciones::get_value('HOURS_DOWNLOAD_DATA_TIME', 1);

            //aun no es necesario volver a descargar los datos desde bsale
            if( $hours < $hours_amount )
            {
                return true;
            }
        }
        //descargo todos
        if( $data === 'all' )
        {
            $arr = array( 'lp', 'users', 'sucursales', 'tipos_dte', 'tipos_pago', 'tipos_prod',
                    //'tipos_prod_attr', este no, pues solo se usa para sync
            );
            foreach( $arr as $data )
            {
                $this->reload_data_from_bsale($data);
            }
            return true;
        }

        if( isset($_REQUEST['param']) )
        {
            Funciones::print_r_html(__METHOD__ . "data= '$data");
        }
        switch( $data )
        {
            //descargo lista de precio
            case 'lp':
                $obj = new ListasPrecioBsale();
                $res = $obj->get_all_save_in_db();
                break;
            //descargo usuarios
            case 'users':
                $obj = new UsuariosBsale();
                $res = $obj->get_all_save_in_db();
                break;
            //descargo sucursales
            case 'sucursales':
                $obj = new SucursalesBsale();
                $res = $obj->get_all_save_in_db();
                break;
            //descargo tipos_dte
            case 'tipos_dte':
                $obj = new TipoDocumentoBsale();
                $res = $obj->get_all_save_in_db();
                break;
            //descargo tipos_pago
            case 'tipos_pago':
                $obj = new TipoPagoBsale();
                $res = $obj->get_all_save_in_db();
                break;
            //descargo tipos_prod
            case 'tipos_prod':
                $obj = new TipoProductosBsale();
                $res = $obj->get_all_save_in_db();
                break;
            //descargo tipos_prod
            case 'tipos_prod_attr':
                $obj = new AtributosTipoProductosBsale();
                $res = $obj->get_all_attrib_products_save_in_db();
                break;
        }
        return true;
    }

}
