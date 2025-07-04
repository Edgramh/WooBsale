var my_jquery = jQuery.noConflict();

my_jquery(document).ready(function()
{

    try
    {
        console.log("validador ok...");
        BsaleValidatorCodificandoJs.init_bsale_checkout_handlers();

        //compatibilidad con plugin fluid checkout (puaj)
        if(my_jquery("a.fc-step__substep-edit", "form.checkout").length > 0)
        {
            my_jquery("a.fc-step__substep-edit", "form.checkout").on("click", function()
            {
                BsaleValidatorCodificandoJs.init_bsale_checkout_handlers();
                console.log("click en step");
            });
        }




    }
    catch(e)
    {
        console.log("js: ", e);
    }


});

var BsaleValidatorCodificandoJs = {
    //variables
    my_jquery: jQuery.noConflict(),
    hide_rut_boletas: false,
    use_radio_facturacion: false,
    rut_foreigner: '55555555-5',
    enable_boleta_sin_rut_option: true,
    rut_sin_rut: '11111111-1', //rut para los que no desean ingresar rut para las boletas
    hide_city_for_boletas: false, //si se debe ocultar el campo ciudad cuando el cliente elige boleta

    ////funciones
    /**
     * devuelve jquery
     * @returns {Object}
     */
    get_jquery: function()
    {
        if(this.my_jquery === undefined || this.my_jquery === null)
        {
            this.my_jquery = jQuery.noConflict();
        }
        return this.my_jquery;
    },
    /**
     * si las boletas deben tener rut obligatorio o no
     * @returns {Boolean}
     */
    is_enable_boleta_sin_rut_option: function()
    {
        return this.enable_boleta_sin_rut_option;
    },
    /**
     * agrega todos los triggers del seelctor de boleta, factura, validador de rut
     * @returns {undefined}
     */
    init_bsale_checkout_handlers: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;
        //oculto campos de factura, pues el default es la boleta, donde estos campos no se llenan. Como son
        //obligsatorios, los lleno con --- 
        if(my_jquery("#billing_company", "form.checkout").val() === "")
        {
            my_jquery("#billing_company", "form.checkout").val("---");
            my_jquery("#billing_company", "form.checkout").attr("value", "---");
            my_jquery("#billing_company_field", "form.checkout").css("display", "none");
        }
        if(my_jquery("#billing_giro", "form.checkout").val() === "")
        {
            my_jquery("#billing_giro", "form.checkout").val("---");
            my_jquery("#billing_giro", "form.checkout").attr("value", "---");
            my_jquery("#billing_giro_field", "form.checkout").css("display", "none");
        }
        if(my_jquery("#billing_rut", "form.checkout").val() === "")
        {
            //my_jquery("#billing_rut", "form.checkout").attr("value", "11111111-1");
        }
        console.log("set defaults");

        //rut no se pide, se oculta y se deja con 1111111-1 pues es obligatorio
        if(this.hide_rut_boletas)
        {
            my_jquery("#billing_rut_field", "form.checkout").css("display", "none");
            my_jquery("#billing_rut", "form.checkout").val(this.rut_sin_rut);
        }

        //la integración agrega un select, pero algunos clientes quieren usar botones de radio. 
        if(this.use_radio_facturacion)
        {
            //elijo boleta
            my_jquery("#billing_tipo_documento_boleta", "form.checkout").on("change", function()
            {
                var val = my_jquery(this).val();
                console.log("tipo docto radio =" + val);

                me.bsale_boleta_selected();

            });

            //oculto o muestro campos de empresa
            my_jquery("#billing_tipo_documento_factura", "form.checkout").on("change", function()
            {
                var val = my_jquery(this).val();
                console.log("tipo docto radio 2 =" + val);

                me.bsale_factura_selected();

            });

        }//fin if rario facturacion
        //uso select
        else
        {
            //oculto o muestro campos de empresa, segun se seleccione boleta o factura
            my_jquery("#billing_tipo_documento", "form.checkout").on("change", function()
            {
                var val = my_jquery(this).val();
                console.log("tipo docto =" + val);

                if(val === "factura")
                {
                    me.bsale_factura_selected();
                }
                else
                {
                    me.bsale_boleta_selected();


                }
            });

        }

        //fecha oc validation
        this.bsale_validate_fecha_oc();

        //solo se llama si rut está vacío
        my_jquery('#billing_rut', "form.checkout").on("change", function()
        {
            me.check_rut_value();

        });
        //rut validation
        my_jquery('#billing_rut', "form.checkout").Rut(
        {
            format: true,
            on_error: function()
            {
                var rut = my_jquery('#billing_rut', "form.checkout").val();

                console.log("validate rut, " + me.get_dte_selected());
                //rut opcional en boletas, todo ok
                if(rut === "" && me.is_boleta_selected() && me.is_enable_boleta_sin_rut_option())
                {
                    console.log("validate rut, boleta selected sin rut");
                    me.rut_ok();
                }
                else
                {
                    me.rut_wrong();
                }

            },
            on_success: function()
            {
                me.rut_ok();
            }
        });

        //submit de form
        my_jquery("form.checkout").on("submit", function()
        {
            me.before_submit_form_action();
        });

        //limpio rut
        if(! this.hide_rut_boletas)
        {
            //my_jquery('#billing_rut', "form.checkout").val("");            

        }

        //si viene rut "sin rut" desde el checkout, lo dejo en blanco
        if(me.rut_sin_rut === my_jquery('#billing_rut', "form.checkout").val())
        {
            my_jquery('#billing_rut', "form.checkout").val("");
            my_jquery('#billing_rut', "form.checkout").attr("value", "");
        }

        my_jquery('#billing_rut', "form.checkout").change();
        //limpio tipo doc
        my_jquery("#billing_tipo_documento option[value=boleta]", "form.checkout").attr('selected', 'selected');

        //select boleta por default
        if(this.use_radio_facturacion)
        {
            my_jquery("#billing_tipo_documento_boleta").prop("checked", true);
            my_jquery("#billing_tipo_documento_boleta").attr('checked', 'checked');
            my_jquery("#billing_tipo_documento_boleta").trigger("change");
        }

        /* bsale_country_change();
         my_jquery("#billing_country", "form.checkout").trigger("change");
         */
        //a veces, hay funciones js o de ajax de wc que puerden ejecutarse después 
        //y esta llamada se puede perder. Por eso, espero 2 seg
        setTimeout(function()
        {
            me.bsale_boleta_selected();
        }, 2000);

    },
    /**
     * rut ingresado correctamente
     * @returns {undefined}
     */
    rut_ok: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;
        //enable submit
        me.wcbsale_display_submit(true);

        //saco color de texto de error del rut
        my_jquery('#billing_rut', "form.checkout").css("background-color", "");

        //quito mensaje de error
        my_jquery(".rut-danger").remove();

        //quito puntos  y vuelvo a escribir el rut   
        var rut = my_jquery('#billing_rut', "form.checkout").val();

        var rut2 = rut.replace(/\./g, "");
        my_jquery('#billing_rut', "form.checkout").val(rut2);

        console.log('Rut correcto ' + rut2);

    },
    /**
     * rut incorrecto
     * @returns {undefined}
     */
    rut_wrong: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        //disable submit
        me.wcbsale_display_submit(false);

        var rut = my_jquery('#billing_rut', "form.checkout").val();

        //marco rut
        my_jquery('#billing_rut', "form.checkout").css("background-color", "#FFE7DF");

        //saco ultimo msg de error, si es que había
        my_jquery(".rut-danger", "form.checkout").remove();

        my_jquery('#billing_rut', "form.checkout").after('<div class="text-danger rut-danger" style="color:red">RUT no válido. Ingrese el rut correcto.</div>');

        my_jquery('#billing_rut', "form.checkout").focus();

        console.log('Rut incorrecto: ' + rut);
    },
    /**
     * si se ha selected boleta y ni hay ruto, todo ok.
     * si se ha selected factura y no hay rut, muestra aviso de error y no permite continuar
     * @returns {undefined}
     */
    check_rut_value: function()
    {
        var rut = this.get_rut_value();

        if(rut === "")
        {
            //se permiten boletas sin rut?
            if(this.is_boleta_selected() && this.is_enable_boleta_sin_rut_option())
            {
                this.rut_ok();
            }
            else
            {
                //boleta requiere rut
                this.rut_wrong();
            }
        }
        //si se ha ingresado rut, todo ok

    },
    /**
     * antes de submit form, si se ha selected boleta y no hay rut, coloca rut 1111111-1
     */
    before_submit_form_action: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        console.log("rut validation after form submit");

        //coloco rut 11111-1
        if(this.get_rut_value() === "" && me.is_boleta_selected())
        {
            this.set_rut_value(this.rut_sin_rut);
        }
    },
    /**
     * devuelve valor del rut
     */
    get_rut_value: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        return  my_jquery('#billing_rut', "form.checkout").val();
    },
    set_rut_value: function(val)
    {
        var my_jquery = this.get_jquery();
        var me = this;

        my_jquery('#billing_rut', "form.checkout").val(val);
        my_jquery('#billing_rut', "form.checkout").attr("value", val);
    },
    /**
     * devuelve el tipo dte selected
     * @returns {unresolved}
     */
    get_dte_selected: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        var sel = my_jquery("#billing_tipo_documento", "form.checkout").val();
        if(sel === '' || sel == null)
        {
            sel = 'boleta';
        }
        return sel;
    },
    /**
     * se ha seleccionado factura?
     * @returns {undefined}
     */
    is_factura_selected: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        var sel = this.get_dte_selected();
        return ( sel === 'factura' );
    },
    /**
     * se ha seleccionado boleta?
     * @returns {undefined}
     */
    is_boleta_selected: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        var sel = this.get_dte_selected();
        return ( sel === 'boleta' );
    },
    bsale_country_change: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        my_jquery("#billing_country", "form.checkout").on("change", function()
        {
            var val = my_jquery(this).val();
            console.log("pais selected. " + val);

            if(val === 'CL')
            {
                me.display_campos_factura(true);
                me.bsale_boleta_selected();
            }
            //otros paises, no se factura para chile
            else
            {
                me.bsale_boleta_selected();
                me.display_campos_factura(false);
            }
        });
    },
    display_campos_factura: function($show)
    {
        var my_jquery = this.get_jquery();
        var me = this;

        if($show)
        {
            //muestro rut      
            my_jquery("#billing_rut_field", "form.checkout").css("display", "block");

            my_jquery("#billing_tipo_documento_field", "form.checkout").css("display", "block");
        }
        else
        {

            //escondo rut
            if(my_jquery("#billing_rut", "form.checkout").val() === "")
            {
                my_jquery("#billing_rut", "form.checkout").val(this.rut_sin_rut);
                my_jquery("#billing_rut", "form.checkout").attr("value", this.rut_sin_rut);
            }
            my_jquery("#billing_rut_field", "form.checkout").css("display", "none");

            my_jquery("#billing_tipo_documento_field", "form.checkout").css("display", "none");

        }
    },
    bsale_validate_fecha_oc: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        my_jquery("#billing_oc_fecha", "form.checkout").on("change", function()
        {
            var fecha = my_jquery("#billing_oc_fecha", "form.checkout").val();

            if(fecha === "")
            {
                my_jquery(".fecha-danger").hide();
                me.wcbsale_display_submit(true);
                return true;

            }
            var dateRegex = /^(?:(?:31(\/|-|\.)(?:0?[13578]|1[02]))\1|(?:(?:29|30)(\/|-|\.)(?:0?[13-9]|1[0-2])\2))(?:(?:1[6-9]|[2-9]\d)?\d{2})$|^(?:29(\/|-|\.)0?2\3(?:(?:(?:1[6-9]|[2-9]\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\/|-|\.)(?:(?:0?[1-9])|(?:1[0-2]))\4(?:(?:1[6-9]|[2-9]\d)?\d{2})$/;

            if(dateRegex.test(fecha))
            {
                console.log("fecha " + fecha + " is ok");
                my_jquery(".fecha-danger").hide();
            }
            else
            {
                console.log("fecha " + fecha + " error!");
                my_jquery("#billing_oc_fecha", "form.checkout").after('<div class="text-danger fecha-danger" style="color:red">¡Fecha no existe!</div>');
                me.wcbsale_display_submit(false);
            }
        });
    },
    bsale_boleta_selected: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        this.check_rut_value();

        if(this.is_enable_boleta_sin_rut_option())
        {
            //rut opcional
            my_jquery("#billing_rut", "form.checkout").attr("required", false);
            my_jquery("label[for='billing_rut'", "#billing_rut_field").html('RUT del comprador <span class="optional">(opcional)</span>');

        }
        else
        {
            //rut obligatorio
            my_jquery("#billing_rut", "form.checkout").attr("required", true);
            my_jquery("label[for='billing_rut'", "#billing_rut_field").html('RUT del comprador <abbr class="required" title="obligatorio">*</abbr>');

        }


        var empresa = my_jquery("#billing_company", "form.checkout").val();

        //si no tiene texto, agrego porque es campo required
        if(empresa === "")
        {
            my_jquery("#billing_company", "form.checkout").val("---");
        }
        my_jquery("#billing_company_field", "form.checkout").css("display", "none");

        //+++rut no se pide
        if(this.hide_rut_boletas)
        {
            my_jquery("#billing_rut_field", "form.checkout").css("display", "none");
            my_jquery("#billing_rut", "form.checkout").val(this.rut_sin_rut);
        }

        var giro = my_jquery("#billing_giro", "form.checkout").val();
        //si no tiene texto, agrego porque es campo required
        if(giro === "")
        {
            my_jquery("#billing_giro", "form.checkout").val("---");
        }
        my_jquery("#billing_giro_field", "form.checkout").css("display", "none");

        //oculto campos OC
        my_jquery("#billing_oc_folio_field", "form.checkout").css("display", "none");
        my_jquery("#billing_oc_fecha_field", "form.checkout").css("display", "none");
        my_jquery("#billing_oc_referencia_field", "form.checkout").css("display", "none");

        //oculto ciudad?
        if(this.hide_city_for_boletas)
        {
            //dejo comuna acá
            // var state = my_jquery("#billing_state", "form.checkout").val();
            //en caso de que el value sea un código y se requiera obtener el texto
            var state = my_jquery("#billing_state option:selected", "form.checkout").text();

            my_jquery("#billing_city", "form.checkout").val(state);
            my_jquery("#billing_city_field", "form.checkout").css("display", "none");
        }
    },
    bsale_factura_selected: function()
    {
        var my_jquery = this.get_jquery();
        var me = this;

        this.check_rut_value();
        //rut obligatorio
        my_jquery("#billing_rut", "form.checkout").attr("required", true);
        my_jquery("label[for='billing_rut'", "#billing_rut_field").html('RUT de la empresa <abbr class="required" title="obligatorio">*</abbr>');

        //muestro campos de factura
        //muestro billing city, required
        // my_jquery("#billing_city", "form.checkout").attr("required", "required");
        //  my_jquery("#billing_city_field", "form.checkout").css("display", "block");

        var empresa = my_jquery("#billing_company", "form.checkout").val();

        //si tiene texto por default, lo saco
        if(empresa === "---")
        {
            my_jquery("#billing_company", "form.checkout").val("");
        }
        my_jquery("#billing_company_field", "form.checkout").css("display", "block");

        //+++rut no se pide
        if(this.hide_rut_boletas)
        {
            my_jquery("#billing_rut_field", "form.checkout").css("display", "block");
            my_jquery("#billing_rut", "form.checkout").val("");
        }


        var giro = my_jquery("#billing_giro", "form.checkout").val();
        //si tiene texto por default, lo saco
        if(giro === "---")
        {
            my_jquery("#billing_giro", "form.checkout").val("");
        }
        my_jquery("#billing_giro_field", "form.checkout").css("display", "block");

        //muestr4o campos OC
        my_jquery("#billing_oc_folio_field", "form.checkout").css("display", "block");
        my_jquery("#billing_oc_fecha_field", "form.checkout").css("display", "block");
        my_jquery("#billing_oc_referencia_field", "form.checkout").css("display", "block");

        //oculto ciudad? entonces, como elegí factura, la vuelvo a mostrar
        if(this.hide_city_for_boletas)
        {
            my_jquery("#billing_city_field", "form.checkout").css("display", "block");
            my_jquery("#billing_city", "form.checkout").val('');
        }
    },
    wcbsale_display_submit: function($val)
    {
        var my_jquery = this.get_jquery();
        var me = this;

        if($val)
        {
            //enable submit
            my_jquery("#place_order", "form.checkout").prop('disabled', false);
            my_jquery("input.next", "#form_actions").css('display', 'inline-block');
            my_jquery("#wpmc-next", ".wpmc-nav-wrapper").css('visibility', 'visible');
        }
        else
        {
            //disable submit
            my_jquery("#place_order", "form.checkout").prop('disabled', true);
            my_jquery("input.next", "#form_actions").css('display', 'none !important');
            my_jquery("#wpmc-next", ".wpmc-nav-wrapper").css('visibility', 'hidden');

        }
    }
};


