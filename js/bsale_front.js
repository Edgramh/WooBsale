
var my_jquery = jQuery.noConflict();

my_jquery(document).ready(function ()
{

    try
    {
        console.log("front end ok");
        //oculto stock variaciones
        bsale_init_variation_stock();
        //handler
        bsale_handler();


    } catch (e)
    {
        console.log("js front end, error: ", e);
    }


});

/**
 * muestra/oculta stock de variaciones hasta que se seleccione una de ellas
 * @returns {undefined}
 */
function bsale_init_variation_stock($display = 'none')
{
    my_jquery(".bsale_variacion", ".bsale_stock_variaciones").css("display", $display);


}

/**
 * clic en variacion, muestra stock de esa variacion
 * @returns {undefined}
 */
function bsale_handler()
{
    my_jquery(".reset_variations", "form.variations_form.cart").on("click", function ()
    {
        //oculto todos los stock de variaciones
        bsale_init_variation_stock('none');
    });
    my_jquery("a.filter-item", "form.variations_form.cart").on("click", function ()
    {
        //velues de los ttr selected, ej: rojo, xl
        var name = my_jquery(this).attr("data-value");
        //cantidad de attrs selected para esta variacion
        var count_attrs = 1;

        //cantidad de attrs que se deben seleccionar para esta variacion
        var numItems = 1;
        //val contiene: si variacion es color, contendrá: rojo, azul, etc


        //se han selected todos los attrs de esta variación?
        if (count_attrs < numItems)
        {
            name = ".noselected";
            console.log("faltan attrs por select, selected: " + count_attrs + "; required: " + numItems);
        }

        //var name = my_jquery("input[type=radio]", this).val();

        //nmae contiene los values de las variacion selected. puede ser uno o varios values
        var class_display = ".bsale_variacion" + '.' + name;

        console.log("attrs de variacion selected: " + name + ". Class " + class_display);

        var stock = my_jquery(class_display, ".bsale_stock_variaciones").attr("title");

        //oculto todos los stock de variaciones
        bsale_init_variation_stock('none');

        //muestro stock de variacion selected
        console.log("click en var=" + name + ", muestro div '" + class_display + "' con stock=" + stock);

        my_jquery(class_display, ".bsale_stock_variaciones").css("display", 'block');
        bsale_display_stock_product_page(stock);
    });

    my_jquery("label.sw-radio-variation", "form.variations_form.cart").on("click", function ()
    {
        //velues de los ttr selected, ej: rojo, xl
        var name = "";
        //cantidad de attrs selected para esta variacion
        var count_attrs = 0;

        //cantidad de attrs que se deben seleccionar para esta variacion
        var numItems = my_jquery('.sw-custom-variation').length;
        //val contiene: si variacion es color, contendrá: rojo, azul, etc
        //recorro todos los labels selected. Cada label selected es un attr de la variación, ej: rojo, xl
        my_jquery("label.sw-radio-variation.selected").each(function (index)
        {
            var aux = my_jquery("input[type=radio]", this).val();
            if (aux === "" || aux == null)
            {
                aux = "noselected";
            }
            console.log("attr " + aux);
            name += "." + aux;

            count_attrs++;
        });

        //se han selected todos los attrs de esta variación?
        if (count_attrs < numItems)
        {
            name = ".noselected";
            console.log("faltan attrs por select, selected: " + count_attrs + "; required: " + numItems);
        }

        //var name = my_jquery("input[type=radio]", this).val();

        //nmae contiene los values de las variacion selected. puede ser uno o varios values
        var class_display = ".bsale_variacion" + name;

        console.log("attrs de variacion selected");

        var stock = my_jquery(class_display, ".bsale_stock_variaciones").attr("title");

        //oculto todos los stock de variaciones
        bsale_init_variation_stock('none');

        //muestro stock de variacion selected
        console.log("click en var=" + name + ", muestro div '" + class_display + "' con stock=" + stock);

        my_jquery(class_display, ".bsale_stock_variaciones").css("display", 'block');
        bsale_display_stock_product_page(stock);
    });
}

/**
 * muestra stock de variacion, una vez selected la variacion
 * espera hasta que se cargue el html de la variacion
 * @param {type} $stock
 * @returns {undefined}
 */
function bsale_display_stock_product_page($stock)
{
    my_jquery(".variacion_stock_app").remove();

    var elem = my_jquery("span.variable-price");

    if (elem == null || elem.length <= 0)
    {
        console.log("wait...");
        setTimeout(function ()
        {
            console.log("end wait");
            //ahora, voy a la funcion
            bsale_display_html_stock($stock);

        }, 3000);
    } else
    {
        bsale_display_html_stock($stock);
    }


}

function bsale_display_html_stock($stock)
{
    my_jquery(".variacion_stock_app").remove();

    var elem = my_jquery("span.variable-price");


    elem.after('<span class="price"><span class="variacion_stock_app product_stock">Internet: <strong class="stock_q">' + $stock + '</strong></span></span>');

    my_jquery(".product_stock", elem).css("display", "none");
    console.log("antes de set precio");
    console.log(elem.html());

    return;
    my_jquery(".stock_q", elem).html($stock);
    console.log("precio html:");
    console.log(elem.html());
}