
var $ = jQuery.noConflict();

$(document).ready(function()
{
    try
    {
        console.log("bs admin ok..");

        BsaleCodificandoJs.bsale_style_link_admin_bar();
        //add boton sycd productos en admin de productos
        BsaleCodificandoJs.button_sync_all_add();
        //boton sync producto edit
        BsaleCodificandoJs.do_bsale_sync_button();

        //proceso de sincronizar todos los productos v2
        BsaleCodificandoJs.do_sync_all_productos();

        //reload data in config page
        BsaleCodificandoJs.reload_data_from_bsale();

        //historial sync en pagina de producto
        BsaleCodificandoJs.get_historial_sync_prod_bsale();

        //historial pedidos dtes
        BsaleCodificandoJs.get_historial_order_bsale();

    }
    catch(e)
    {
        console.log("js: ", e);
    }


});

var BsaleCodificandoJs = {
    $: undefined,
    timer_stop_watch: 1,
    steps_arr: {},
    /**
     * carga en stpes_arr listado de pasos a ejecutar
     */
    steps_arr_init: function()
    {
        var me = this;
        //limpio array
        this.steps_arr_clear();


        $ = this.get_jquery();
        var $elem = $("#steps_to_sync_list", ".wrap.bsale_wooc");

        $("li", $elem).each(function(i)
        {
            //obtengo el checkbox con todos los datos a sync
            var $input_obj = $(".step_sync", this);
            var $action = $input_obj.attr("action");
            var $title = $input_obj.attr("title");

            var $lp_id = $input_obj.attr("lp_id");
            var $lp_name = $input_obj.attr("lp_name");
            var $lp_tipo = $input_obj.attr("lp_tipo");

            var $suc_id = $input_obj.attr("suc_id");
            var $suc_name = $input_obj.attr("suc_name");
            var $suc_tipo = $input_obj.attr("suc_tipo");

            //soo si está chequeada
            if(! $input_obj.is(':checked'))
            {
                me.log("step='" + $action + "' " + $title + "' is unchecked, skip");
                me.log($input_obj.html());
            }
            else
            {
                me.log("paso a ejecutAR: step='" + $action + "' " + $title + "'");

                //creo objeto
                var obj = {'action': $action, 'title': $title,
                    'lp_id': $lp_id, 'lp_name': $lp_name, 'lp_tipo': $lp_tipo,
                    'suc_id': $suc_id, 'suc_name': $suc_name, 'suc_tipo': $suc_tipo
                };

                //agrego al array
                me.steps_arr_add(obj);
            }
        });


    },
    /**
     * limpia array con pasos a ejecutar
     */
    steps_arr_clear: function()
    {
        this.steps_arr = [];
    },
    /**
     * agrega datos (objeto) al array con pasos a ejecutar
     * @param {type} data
     * @returns {undefined}
     */    steps_arr_add: function(data)
    {
        this.steps_arr.push(data);
    },
    /**
     * muestra en consola datos de los pasos a ejecutar
     * @returns {undefined}
     */
    steps_arr_print: function()
    {
        console.log("Pasos a ejecutar desde array:");
        console.log(this.steps_arr);
    },

    /**
     * log en consola
     * @param {type} msg
     * @returns {undefined}
     */
    log: function(msg)
    {
        console.log(msg);
    },
    get_jquery: function()
    {
        if(this.$ === undefined)
        {
            this.$ = jQuery.noConflict();
        }
        return this.$;
    },
    //style link a bsale config en admin bar
    bsale_style_link_admin_bar: function()
    {
        $ = this.get_jquery();
        //style de li
        var $li = $(".codifintjs", "ul#wp-admin-bar-root-default");
        var $a = $("a", $li);
        $li.css("background-color", "#ff5c1a");
        $li.css("background-color", "#ff5c1a");
        $li.css("border-top-left-radius", "40px");
        $li.css("border-bottom-left-radius", "40px");
        $li.css("border-top-right-radius", "40px");

        //$a.css("text-transform", "uppercase");
        $a.css("font-weight", "600");
        $a.css("color", "#fff");
    }
    ,

    /**
     * indica estado de la sync bsale
     * @param {type} status 0 empezar, 1 en curso, 2 detenida
     * @returns {undefined}
     */
    set_state_sync_bsale: function(status)
    {
        $ = this.get_jquery();
        $("#state_sync_bsale", ".wrap.bsale_wooc").val(status);

    }
    ,
    /**
     * devuelve estado de la sync bsale
     * @param {type} status
     * @returns {undefined}
     */
    get_state_sync_bsale: function(status)
    {
        $ = this.get_jquery();
        return $("#state_sync_bsale", ".wrap.bsale_wooc").val();
    }
    ,

    /////////////////////////////////////////////
    //boton synd datos prodcutos en admin de productos
    /**
     * agrega boton en admin de prods para sinconizar todos los productos
     * @returns {undefined}
     */
    button_sync_all_add: function()
    {
        $ = this.get_jquery();

        var title = "Sincroniza precio y stock desde Bsale para todos los productos woocommerce.";
        var style = "background-color: #ff5c1a; color: #fff;padding: 5px 20px;text-transform: uppercase;border-color:#717171;border-radius: 5px;";

        var $elem = $("a.page-title-action", "body.wp-admin.post-type-product #wpbody-content .wrap").last();
        $elem.after("<a id='sync_all_productos_bsale' href='tools.php?page=woo_bsale-admin-menu&bsale_tab=bsale_sync&bsale_silent=1' target='_blank' class='page-title-action bsale' title='" + title + "' style='" + style + "'>Sincronizar productos Bsale</a>");
    },

/////////////////////////////////////////////
//boton sync prodcuto individual
    /**
     * agrea boton en product edit para sincronizar producto con bsale
     * @returns {undefined}
     */
    do_bsale_sync_button: function()
    {
        $ = this.get_jquery();
        var $count = 0; //veces que se ha intentado sincronizar

        $(".sync_bsale_btn").on("click", function(e)
        {
            e.preventDefault();

            var prod_id = $(this).attr("data-pid");

            //var url = $(this).attr("href");

            //ajaxurl es global de WP
            var url = ajaxurl + "?action=bsale_ajax_sync_prod_action&param=yes&pid=" + prod_id + "&bsale_silent=1";

            //console.log("sync_bsale_btn, url:  " + url);
            //escondo boton
            $(this).css("display", "none");

            //console.log("sync_bsale_btn, sigo");
            //muestro spinner
            $("#spinner_sync_bsale").css("display", "inline-block");

            var me = $(this);

            $("#result_sync").load(url, function(response, status, xhr)
            {
                console.log("sync_bsale_btn, fin ajax");
                if(status === "error")
                {
                    //incremento intentos fallidos
                    $count ++;
                    if($count <= 3)
                    {
                        //intento de nuevo
                        me.trigger("click");
                    }
                    else
                    {
                        var msg = "No se pudo sincronizar el producto: ";
                        $("#bsale_sync_result").html(msg + xhr.status + " " + xhr.statusText);
                    }
                }
                else
                {
                    $("#bsale_sync_result").html("Sincronización exitosa. Ahora, la página se recargará.");
                    location.reload();

                }

                me.css("display", "inline-block");
                $("#spinner_sync_bsale").css("display", "none");

            });//fin $("#result_sync").
        });
    },
    /**
     * regarga datos de tipos de prod, dtes, usuarios, etc de la cuenta de bsale 
     * 
     * @returns {undefined}
     */
    reload_data_from_bsale: function()
    {
        $ = this.get_jquery();


        $("#reload_data_from_bsale", ".wrap.bsale_wooc").on("click", function(e)
        {
            e.preventDefault();
             //muestro loading
            $(".loading_historial", this).css("display", "inline-block");
            
            var  $link = $(this);

            //ajaxurl es global de WP
            var url = ajaxurl + "?action=bsale_reload_data_action&param=yes&bsale_silent=1";

            $("#result_reload", ".wrap.bsale_wooc").load(url, function(response, status, xhr)
            {                
                console.log("sync_bsale_btn, fin ajax");
                location.reload();
                 //oculto loading. No se muestra, pq la página se recarga primero.
                $(".loading_historial", $link).css("display", "none");

            });//fin $("#result_sync").
        });
    },

    /**
     * devuelve historial de sincronizacion del producto bsale
     * @returns {undefined}
     */
    get_historial_sync_prod_bsale: function()
    {
        $ = this.get_jquery();



        $("#get_info_last_sync_bsale", ".info_sync_div").on("click", function(e)
        {
            e.preventDefault();
            var $product_id = $(this).attr("href");

            var $link = $(this);
            //muestro loading
            $(".loading_historial", this).css("display", "inline-block");

            //ajaxurl es global de WP
            var url = ajaxurl + "?action=get_info_last_sync_bsale_action&pid=" + $product_id + "&bsale_silent=1";

            $("#result_reload_sync", ".info_sync_div").load(url, function(response, status, xhr)
            {
                //oculto loading
                $(".loading_historial", $link).css("display", "none");
                //console.log("get_info_last_sync_bsale_action, fin ajax");

            });//fin $("#result_sync").
        });
    },
    /**
     * devuelve historial de dtes del pedido
     * @returns {undefined}
     */
    get_historial_order_bsale: function()
    {
        $ = this.get_jquery();


        $("#get_info_order_bsale", ".info_sync_div").on("click", function(e)
        {
            e.preventDefault();
            var $order_id = $(this).attr("href");
            var $link = $(this);

            //muestro loading
            $(".loading_historial", this).css("display", "inline-block");

            //ajaxurl es global de WP
            var url = ajaxurl + "?action=get_info_order_docs_action&oid=" + $order_id + "&bsale_silent=1";

            $("#result_reload_sync", ".info_sync_div").load(url, function(response, status, xhr)
            {
                //oculto loading
                $(".loading_historial", $link).css("display", "none");
                // console.log("get_info_order_docs_action, fin ajax");

            });//fin $("#result_sync").
        });
    },

    /**
     * devuelve siguiente paso (step) en el list, para sincronizar
     * y lo borra de la lista de pasos
     * @returns {undefined}
     */
    get_next_step_to_sync: function()
    {
        var $obj = this.steps_arr.shift();

        return $obj;

    },
    /**
     * marca un step como terminado de sincronizar
     * @param {type} $elem_id
     * @returns {undefined}
     */
    set_step_to_sync_finished: function($elem_id)
    {

    },
    /**
     * agrego información a mensajes de la sync
     * @param {type} $data_str_or_html
     * @returns {undefined}
     */
    info_sync_add: function($data_str_or_html)
    {
        var $ = this.get_jquery();

        var $div = $("#mesages_sync_prods", ".sync_all_prods_wrapper");
        $div.append($data_str_or_html);
    },
    sync_stop: function()
    {
        var $ = this.get_jquery();
        //detengo reloj
        this.watch_stop();
        //oculto spinne
        //muestro reloj
        $(".watch_p", ".sync_all_prods_wrapper").css("display", "none");
        //limpio pasos
        this.steps_arr_clear();
        this.log("pasos detenidos");
        this.info_sync_add("<p class='abort_sync'>Sincronización detenida por el usuario.</p>");
        this.set_state_sync_bsale(2);
    },
    watch_start: function()
    {
        var $ = this.get_jquery();
        var $me = this;

        this.timer_stop_watch = 1;
        //coloco en ceor
        $("#stopWatch", ".sync_all_prods_wrapper").html(0);

        //  $me.log("watch start...");

        setInterval(function()
        {
            if($me.timer_stop_watch <= 0)
            {
                //$me.log("watch cero timer");
                return;
            }

            $("#stopWatch", ".sync_all_prods_wrapper").html($me.timer_stop_watch);
            // $me.log("watch update " + $me.timer_stop_watch);
            $me.timer_stop_watch ++;
        }, 1000);

    },
    watch_stop: function()
    {
        this.timer_stop_watch = 0;
        //oculto spinner
        $ = this.get_jquery();
        $(".watch_p", ".sync_all_prods_wrapper").css("display", "none");
    },
    /**
     * copia html de id indicado en href
     * @returns {undefined}
     */
    copy_to_clipboard: function()
    {
        $ = this.get_jquery();
        var $me = this;
        $(".copy_button", ".sync_all_prods_wrapper").on("click", function(e)
        {
            e.preventDefault();
            var elem = $(this).attr("href");
            var html = $(elem).html();

            //navigator es global
            $me.copyToClipboard(html);

        });
    },
    copyToClipboard: function(textToCopy)
    {
        // Navigator clipboard api needs a secure context (https)
        if(navigator.clipboard && window.isSecureContext)
        {
            navigator.clipboard.writeText(textToCopy);
        }
        else
        {
            // Use the 'out of viewport hidden text area' trick
            const textArea = document.createElement("textarea");
            textArea.value = textToCopy;

            // Move textarea out of the viewport so it's not visible
            textArea.style.position = "absolute";
            textArea.style.left = "-999999px";

            document.body.prepend(textArea);
            textArea.select();

            try
            {
                document.execCommand('copy');
            }
            catch(error)
            {
                console.error(error);
            }
            finally
            {
                textArea.remove();
            }
        }
    },

/////////////////////////////////////////////
//sincronizar todos los productos usando ajax
    do_sync_all_productos: function()
    {
        $ = this.get_jquery();
        var $me = this;

        this.copy_to_clipboard();

        //oculto spinner
        $(".watch_p", ".sync_all_prods_wrapper").css("display", "none");

        //para detener la sync
        $("#stop_sync_bsale_products2", ".sync_all_prods_wrapper").on("click", function()
        {
            $me.sync_stop();
        });

        $("#start_sync_bsale_products2", ".sync_all_prods_wrapper").on("click", function()
        {
            //muestro reloj
            $(".watch_p", ".sync_all_prods_wrapper").css("display", "inline-block");

            //cargo pasos en array 
            $me.steps_arr_init();
            //muestro en pantalla
            $me.steps_arr_print();

            $me.set_state_sync_bsale(1);

            //inicio reloj
            $me.watch_start();

            var $step = 1;
            //en caso de que se deba reintentar el paso anterior, solo se debe intentar 3 veces
            var $times_retry = 0;

            $me.info_sync_add("<p class='start_sync'><strong>Iniciando sincronización</strong></p>");

            //recorro PASOSllamando a ajax
            function next_sync_bsale2($obj_step_to_retry = null )
            {
                //sync aun en curso?
                if($me.get_state_sync_bsale() === "2")
                {
                    $me.log("sync terminada");
                    return;
                }

                var $obj_step;

                if($obj_step_to_retry !== null)
                {
                    //estos pasos se repiten muchas veces
                    if($obj_step_to_retry.action === 'save_stocks_sucurs_cons' ||
                            $obj_step_to_retry.action === 'sync_update_prods_wc' ||
                            $obj_step_to_retry.action === 'sync_add_prods_wc')
                    {

                        if($times_retry >= 500)
                        {
                            $me.log("ERROR: fallo al ejecutar paso");
                            $me.log($obj_step_to_retry);
                            $me.log("sincronización terminada con errores");
                            //detengo reloj
                            $me.watch_stop();
                            return false;
                        }
                    }
                    //si ya intenté X veces, para todos los otros pasos
                    else if($times_retry >= 100)
                    {
                        $me.log("ERROR: fallo al ejecutar paso");
                        $me.log($obj_step_to_retry);
                        $me.log("sincronización terminada con errores");
                        //detengo reloj
                        $me.watch_stop();
                        return false;
                    }
                    $me.log("proximo paso a ejecutar, paso anterior a reintentar: ");
                    $me.log($obj_step_to_retry);
                    $obj_step = $obj_step_to_retry;

                    $times_retry ++; //un intento más
                }
                else
                {
                    $obj_step = $me.get_next_step_to_sync();
                    $times_retry = 0;
                }

                $me.log("proximo paso a ejecutar: " + $step);
                $me.log($obj_step);

                //ya se han recorrido todos los pasos
                if($obj_step === undefined || $obj_step === null ||
                        $obj_step.action === undefined || $obj_step.action === null || $obj_step.action === '')
                {
                    $me.log("pasos terminados.");
                    $me.info_sync_add("<p class='finished_sync'><strong>Sincronización terminada correctamente.</strong></p>");
                    //detengo reloj
                    $me.watch_stop();

                    return true;
                }
                $step ++;

                if($times_retry > 0)
                {
                    $me.info_sync_add("<p class='init_step'>" + $obj_step.title + ", paso " + $times_retry + "...</p>");
                }
                else
                {
                    $me.info_sync_add("<p class='init_step'>" + $obj_step.title + "</p>");
                }


                //llamo a ajax con variable global de wp "ajaxurl"
                var jqxhr = $.ajax({
                    //   url: prod_url,
                    url: ajaxurl,
                    data: {
                        action: "bsale_ajax_sync_prod_action2",
                        action_param: $obj_step.action,
                        title: $obj_step.title,
                        lp_id: $obj_step.lp_id,
                        lp_name: $obj_step.lp_name,
                        lp_tipo: $obj_step.lp_tipo,
                        suc_id: $obj_step.suc_id,
                        suc_name: $obj_step.suc_name,
                        suc_tipo: $obj_step.suc_tipo,
                        times_retry: $times_retry,
                        bsale_silent: 1
                    },
                    async: true,
                    timeout: 999999 //miliseconds
                }).done(function(data, textStatus, jqXHR)
                {
                    $me.log("ajax exito: " + textStatus);
                    $me.log(data);


                    //se repite hasta que devuelva menos del total
                    if($obj_step.action === 'save_stocks_sucurs_cons')
                    {
                        $me.info_sync_add("<p>" + data + "</p>");

                        $("#result_stock_sucursales", "#info_result_sync").html(data);
                        var div_result = $("#result_stock_sucursales", "#info_result_sync");

                        //extraigo cantidades
                        var resultado = parseInt($(".resultado", div_result).html());
                        var total = parseInt($(".total", div_result).html());

                        $me.log($obj_step.action + ": leidas " + resultado + " variaciones de " + total + " solicitadas");

                        //repito este paso hasta resultado < total
                        if(resultado >= total)
                        {
                            $me.log("repito paso");
                            $me.log($obj_step);

                            next_sync_bsale2($obj_step);
                            return;
                        }
                    }

                    //esta se repite hasta que devuelve 0
                    //update prods wc con datos de consolidado
                    else if($obj_step.action === 'sync_update_prods_wc')
                    {
                        $("#result_update_prods_wc", "#info_result_sync").html(data);

                        //coloc resultado en tabla html y no en donde se colocam los resultados de las otras divs
                        $("tbody", "table#skus_updated_tbl").append(data);

                        var div_result = $("#result_update_prods_wc", "#info_result_sync");

                        //extraigo cantidades
                        var resultado = parseInt($(".resultado", div_result).html());
                        var total = parseInt($(".total", div_result).html());

                        $me.log($obj_step.action + ": leidas " + resultado + " variaciones de " + total + " solicitadas");

                        //repito este paso hasta resultado < total
                        if(resultado >= total)
                        {
                            $me.log("repito paso");
                            $me.log($obj_step);

                            next_sync_bsale2($obj_step);
                            return;
                        }
                    }
                    //esta se repite hasta que devuelve 0
                    //add a wc productos de bsale
                    else if($obj_step.action === 'sync_add_prods_wc')
                    {
                        $me.info_sync_add("<p>" + data + "</p>");

                        $("#result_add_prods_wc", "#info_result_sync").html(data);
                        var div_result = $("#result_add_prods_wc", "#info_result_sync");

                        //extraigo cantidades
                        var resultado = parseInt($(".resultado", div_result).html());
                        var total = parseInt($(".total", div_result).html());

                        $me.log($obj_step.action + ": leidas " + resultado + " variaciones de " + total + " solicitadas");

                        //repito este paso hasta resultado < total
                        if(resultado >= total)
                        {
                            $me.log("repito paso");
                            $me.log($obj_step);

                            next_sync_bsale2($obj_step);
                            return;
                        }

                    }
                    else if($obj_step.action === 'check_prods_wc')
                    {
                        $("tbody", "table#skus_no_en_bsale").append(data);
                    }
                    else
                    {
                        $me.info_sync_add("<p>" + data + "</p>");
                    }
                    //a paso siguiente
                    next_sync_bsale2();


                }).fail(function(data, textStatus, jqXHR)
                {
                    $me.log("ajax error: " + textStatus);
                    $me.log(data);

                    $me.info_sync_add("<p class='sync_error'>ERROR " + data.status + " " + data.statusText + ", sincronización se reanuda. No se preocupe.</p>");
                    //retry
                    // $times_retry ++;
                    next_sync_bsale2($obj_step);
                });
            }
            //first time
            next_sync_bsale2();
        });

    }
};


