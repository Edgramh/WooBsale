
var my_jquery = jQuery.noConflict();

my_jquery(document).ready(function ()
{

    try
    {
        console.log("validador ok");

        //campos de factura, default valuie y escondo hasta que
        //se seleccione docto factura
        my_jquery("#billing_company", "form.checkout").val("---");
        my_jquery("#billing_company_field", "form.checkout").css("display", "none");
        my_jquery("#billing_company", "form.checkout").removeAttr('required').attr('required', 'required').prop('required', true);

        //my_jquery("#billing_giro", "form.checkout").val("---");
        my_jquery("#billing_giro_field", "form.checkout").css("display", "none");
        //peru
        my_jquery("#billing_direccion_fiscal", "form.checkout").val("---");
        my_jquery("#billing_direccion_fiscal_field", "form.checkout").css("display", "none");

        //ruc peru billing_ruc
        my_jquery("#billing_ruc", "form.checkout").val("---");
        my_jquery("#billing_ruc_field", "form.checkout").css("display", "none");

        //oculto o muestro campos de empresa
        my_jquery("#billing_tipo_documento", "form.checkout").change(function ()
        {
            var val = my_jquery(this).val();
            console.log("tipo docto =" + val);

            if (val === "boleta")
            {
                //si no hay ruc o es invalido, habilito el submit
                var ruc = my_jquery('#billing_ruc', "form.checkout").val();

                //   my_jquery("#place_order", "form.checkout").prop('disabled', false);

                //escondo campos de factura
                var empresa = my_jquery("#billing_company", "form.checkout").val();

                //si no tiene texto, agrego porque es campo required
                if (empresa === "")
                {
                    my_jquery("#billing_company", "form.checkout").val("---");
                }
                my_jquery("#billing_company_field", "form.checkout").css("display", "none");


                var giro = my_jquery("#billing_giro", "form.checkout").val();
                //si no tiene texto, agrego porque es campo required
                if (giro === "")
                {
                    //my_jquery("#billing_giro", "form.checkout").val("---");
                }
                my_jquery("#billing_giro_field", "form.checkout").css("display", "none");

                //peru
                var direccion_fical = my_jquery("#billing_direccion_fiscal", "form.checkout").val();
                //si no tiene texto, agrego porque es campo required
                if (direccion_fical === "")
                {
                    my_jquery("#billing_direccion_fiscal", "form.checkout").val("---");
                }
                my_jquery("#billing_direccion_fiscal_field", "form.checkout").css("display", "none");

                //billing_ruc
                var ruc = my_jquery("#billing_ruc", "form.checkout").val();
                //si no tiene texto, agrego porque es campo required
                //if (ruc == "")
                {
                    my_jquery("#billing_ruc", "form.checkout").val("---");
                    my_jquery("#billing_ruc", "form.checkout").change();
                }
                my_jquery("#billing_ruc_field", "form.checkout").css("display", "none");

            } else if (val === "factura")
            {
                //muestro campos de factura
                var empresa = my_jquery("#billing_company", "form.checkout").val();

                //si tiene texto por default, lo saco
                if (empresa === "---")
                {
                    my_jquery("#billing_company", "form.checkout").val("");
                }
                my_jquery("#billing_company_field", "form.checkout").css("display", "block");


                var giro = my_jquery("#billing_giro", "form.checkout").val();
                //si tiene texto por default, lo saco
                if (giro === "---")
                {
                    my_jquery("#billing_giro", "form.checkout").val("");
                }
                my_jquery("#billing_giro_field", "form.checkout").css("display", "block");

                //peru
                var df = my_jquery("#billing_direccion_fiscal", "form.checkout").val();
                //si tiene texto por default, lo saco
                if (df === "---")
                {
                    my_jquery("#billing_direccion_fiscal", "form.checkout").val("");
                }
                my_jquery("#billing_direccion_fiscal_field", "form.checkout").css("display", "block");

                //billing_ruc
                var ruc = my_jquery("#billing_ruc", "form.checkout").val();
                //si tiene texto por default, lo saco
                if (ruc === "---")
                {
                    my_jquery("#billing_ruc", "form.checkout").val("");
                }
                my_jquery("#billing_ruc_field", "form.checkout").css("display", "block");
            }
        });

        //valido dni
        //solo números     
        my_jquery('#billing_rut', "form.checkout").keypress(function (event)
        {
            return isNumberKey8899(event);
        });
        //hasta 9 digitos
        my_jquery('#billing_rut', "form.checkout").keyup(function (event)
        {
            var me = my_jquery(this);
            len = 9;
            return validateMaxLength8899(me, len);
        });
        //si tiene menos de 8 digitos, no sirve
        my_jquery('#billing_rut', "form.checkout").change(function (event)
        {
            var dni = my_jquery(this).val();

            if (dni === "" || (dni && dni.length >= 8))
            {
                console.log('DNI correcto');
                //enable boton de continuar
                //enable submit
                my_jquery("#place_order", "form.checkout").prop('disabled', false);

                //marco rut
                my_jquery('#billing_rut', "form.checkout").css("background-color", "#fff");

                my_jquery('#billing_rut', "form.checkout").css("border", "1px solid #f5f5f5");

                my_jquery(".rut-danger").hide();
            } else
            {
                //disable boton de continuar
                //disable submit
                my_jquery("#place_order", "form.checkout").prop('disabled', true);

                //marco rut
                my_jquery('#billing_rut', "form.checkout").css("background-color", "#FFE7DF");

                my_jquery('#billing_rut', "form.checkout").css("border", "1px solid #FF005D");

                my_jquery(".rut-danger").hide();

                my_jquery('#billing_rut', "form.checkout").after('<div class="text-danger rut-danger">¡ID no válido!</div>');

                my_jquery('#billing_rut', "form.checkout").focus();

                console.log('DNI incorrecto');
            }
        });

        //valido RUC
        my_jquery('#billing_ruc', "form.checkout").change(function (event)
        {
            var ruc = my_jquery(this).val();

            var res;

            //si he seleccionado boleta, se dactiva eror del ruc
            var docto = my_jquery("#billing_tipo_documento", "form.checkout").val();
            //valor por default, se ignora
            if (ruc === "---" && docto === 'boleta')
            {
                console.log("ruc tiene valor por default, se omite validacion");
                res = true;
            } else
            {
                res = validarInput(ruc);
            }

            if (res)
            {
                console.log('RUC correcto');
                //enable boton de continuar
                //enable submit
                my_jquery("#place_order", "form.checkout").prop('disabled', false);

                //marco rut
                my_jquery('#billing_ruc', "form.checkout").css("background-color", "#fff");

                my_jquery('#billing_ruc', "form.checkout").css("border", "1px solid #f5f5f5");

                my_jquery(".ruc-danger").hide();
            } else
            {
                //disable boton de continuar
                //disable submit
                my_jquery("#place_order", "form.checkout").prop('disabled', true);

                //marco rut
                my_jquery('#billing_ruc', "form.checkout").css("background-color", "#FFE7DF");

                my_jquery('#billing_ruc', "form.checkout").css("border", "1px solid #FF005D");

                my_jquery(".ruc-danger").hide();

                my_jquery('#billing_ruc', "form.checkout").after('<div class="text-danger ruc-danger">¡RUC no válido!</div>');

                my_jquery('#billing_ruc', "form.checkout").focus();

                console.log('RUC incorrecto');
            }
        }

        );




    } catch (e)
    {
        console.log("js: ", e);
    }


});

function validateMaxLength8899(test_obj, maxlength)
{
    var text = test_obj.val();

    if (maxlength > 0)
    {
        test_obj.val(text.substr(0, maxlength));
    }
}

/**
 * solo permite input numbers
 * @param {type} evt
 * @returns {Boolean}
 */
function isNumberKey8899(evt)
{
    var charCode = (evt.which) ? evt.which : evt.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
}


/**
 * validador de RUC
 * ***/
//Handler para el evento cuando cambia el input
//Elimina cualquier caracter espacio o signos habituales y comprueba validez
function validarInput(input_str)
{
    var ruc = input_str.replace(/[-.,[\]()\s]+/g, "");

    //Es entero?    
    if ((ruc = Number(ruc)) && ruc % 1 === 0
            && rucValido(ruc))
    { // ⬅️ Acá se comprueba
        return true;
        //obtenerDatosSUNAT(ruc);
    } else
    {
        return false;
    }
}

// Devuelve un booleano si es un RUC válido
// (deben ser 11 dígitos sin otro caracter en el medio)
function rucValido(ruc)
{
    //algunos ruc no cunplen con la validacion pero existen en sunat
    return true;
    //11 dígitos y empieza en 10,15,16,17 o 20
    if (!(ruc >= 1e10 && ruc < 11e9
            || ruc >= 15e9 && ruc < 18e9
            || ruc >= 2e10 && ruc < 21e9))
        return false;

    for (var suma = -(ruc % 10 < 2), i = 0; i < 11; i++, ruc = ruc / 10 | 0)
        suma += (ruc % 10) * (i % 7 + (i / 7 | 0) + 1);
    return suma % 11 === 0;

}

//Buscar datos del RUC y si existe
/*function obtenerDatosSUNAT(ruc) {
 var url = "https://cors-anywhere.herokuapp.com/wmtechnology.org/Consultar-RUC/?modo=1&btnBuscar=Buscar&nruc=" + ruc,
 existente = document.getElementById("existente"),
 xhr = false;
 if (window.XMLHttpRequest) 
 xhr = new XMLHttpRequest();
 else if (window.ActiveXObject)
 xhr = new ActiveXObject("Microsoft.XMLHTTP");
 else return false;
 xhr.onreadystatechange = function () {
 if (xhr.readyState == 4 && xhr.status == 200) {
 var doc = document.implementation.createHTMLDocument()
 .documentElement,
 res = "",
 txt, campos,
 ok = false;
 
 doc.innerHTML = xhr.responseText;
 campos = doc.querySelectorAll(".list-group-item");
 if (campos.length) {
 for (txt of campos)
 res += txt.innerText + "\n";
 res = res.replace(/^\s+\n*|(:) *\n| +$/gm,"$1");
 ok = /^Estado: *ACTIVO *$/m.test(res);
 } else
 res = "RUC: " + ruc + "\nNo existe.";
 
 if (ok)
 existente.classList.add("ok");
 else 
 existente.classList.remove("ok");
 existente.innerText = res;
 }
 }
 xhr.open("POST", url, true);
 xhr.send(null);
 }*/
