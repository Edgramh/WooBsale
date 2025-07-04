<?php

//namespace woocommerce_bsalev2\lib\wp;
require_once dirname(__FILE__) . '/../Autoload.php';

//use woocommerce_bsalev2\lib\wp\WpUtils;
/**
 * Funciones de cronjobs (no usadas aún)
 *
 * @author angelorum
 */
class CronFunctions
{

    /**
     * agrega cronjobs. Llamada al activar el plugin
     */
    public function setup_cronjob_actions()
    {
        //agrego intervalos de ejecución que necesito
        add_filter('cron_schedules', array( $this, 'my_cron_schedules' ));

        //agrego cronjob
        if( !wp_next_scheduled('bsale_codificando_task_hook') )
        {
            wp_schedule_event(time(), '5min', 'bsale_codificando_task_hook');
        }

        add_action('bsale_codificando_task_hook', array( $this, 'my_task_function' ));
    }
    /**
     * disable cronjobs creados con setup_cronjob_actions. 
     * LLamada al desactivar el plugin
     */
    public function remove_cronjob_actions()
    {
        wp_clear_scheduled_hook( 'bsale_codificando_task_hook' );
    }

    /**
     * agrega intervalos de tiempo para ejecutar cronjobs
     * @param type $schedules
     * @return type
     */
    public function my_cron_schedules($schedules)
    {
        if( !isset($schedules['5min']) )
        {
            $schedules['5min'] = array(
                'interval' => 5 * 60,
                'display' => __('Once every 5 minutes') );
        }
        if( !isset($schedules['3min']) )
        {
            $schedules['3min'] = array(
                'interval' => 3 * 60,
                'display' => __('Once every 3 minutes') );
        }
        return $schedules;
    }

    /**
     * funcion que se ejecuta como cronjob
     */
    function my_task_function()
    {
        $fichero = dirname(__FILE__) . '/../logs/my_task_function.log';

        $hoy = date('d-m-Y H:i:s');

        $txt = "$hoy cronjob ejecutado\n";
        file_put_contents($fichero, $txt, FILE_APPEND | LOCK_EX);
    }
}
