<?php

require_once dirname(__FILE__) . '/../lib/Autoload.php';
/**
 * test del json con carpeta ftp y correlativo
 */
if( !isset($_REQUEST['param']) || $_REQUEST['param'] !== 'yes' )
{
    die("no allowed");
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$ajaxf = new AjaxFunctions();
//test 
$your_JSON_variable = '{
        "id": 10259891,
        "name": "KingSong 18L negro - DISPONIBLE",
        "page_title": "Monociclo Electrico KingSong 18L",
        "description": "<p>Marca:&nbsp; KingSong</p>\n<p>Modelo:&nbsp; 18L&nbsp; -&nbsp;<strong>DISPONIBLE</strong><br>\n<br></p>\n<p>La linea mas grande de KingSong es en aro 18\", probado y testeado a más no poder. Potente, fiel, elegante, simple, incansable y cumplidor.</p>\n<p><br><u>CARACTERISTICAS:</u><br></p>\n<p></p>\n<p>1. Manillar telescopico integrado. Portable y maniobrable.<br>2. Panel de luz LED lateral controlable.  6 programas con full espectro de colores a elección.  Indicador LED de estado de bateria.<br>3. Luz frontal blanca y luz trasera de freno color rojo para una conduccíon segura. (5w led + 2 x1w luz de freno).   Funcionan para ambas direcciones.<br>4. Ventilador radiador intercooler 12V, velocidad de ventilacion varia de acuerdo a la temperatura para superar los rendimientos de viaje.<br>5. Bateria 1036wh , rinde hasta 105 kilometros x carga.<br>6. App permite modificar varias caracteristicas. </p>\n<p> - Velocidad instantanea</p>\n<p>- Estatus bateria</p>\n<p>- Temperatura motor</p>\n<p>- gps, social media, blokeo anti robo, clave, luces, tipo de conduccion, seguridad, limites de velocidad, bocina, parlantes, musica, actualizaciones etc etc.<br>7. 2200w high speed motor  (NUEVO MOTOR ACTUALIZADO MARZO 2021)<br>8. 4 Hi-Fi Bluethoot parlantes, 12V power amplifier<br>9. Doble Puerto de carga USB. (Solo se provee un cargador por equipo, el segundo es adicional)<br>10. Sensor de Subida ( sensor que permite detener la rueda cuando jalas el manillar, detiene el motor para facilitar la manipulacion del KingSong)<br>11. Monitoreo real de temperatura, permite tener máxima seguridad de conducción.</p>\n<p></p>\n<p></p>\n<p>------</p>\n<table><tbody><tr><td>Model</td><td>KS-18L</td></tr><tr><td>Top Speed</td><td>Around 40km /h, top speed support decode to 50km/h after 200km total mileage( Default setting: 1st beeping at 18km/h, 2nd beeping at 19km/h, pedal tilt at 20km/h)</td></tr><tr><td>Mileage</td><td>Around 1036Wh, 105km</td></tr><tr><td>Maximum Gradibility</td><td>Around 35°</td></tr><tr><td>Battery</td><td>Rated Power： DC 74V<br>Top Charging Voltage： DC 84V<br>Rated Capacity：1036Wh<br>Smart BMS with balance and protect overshoot/ over discharge/ overcurrent/ short circuit/ overheating function, support monitoring the battery conditions via KingSong APP</td></tr><tr><td>Operating Temperature</td><td>-10℃/+60℃</td></tr><tr><td>Max Load</td><td>120kg</td></tr><tr><td>Charger Voltage</td><td>Input AC 80~240 V ，output DC 84V、1.5A</td></tr><tr><td>Charging Time</td><td>1036Wh about 14h(one charger around 10hrs, double chargers 6 hrs,you may buy another charger as there are double charging ports)</td></tr><tr><td>Rated Power</td><td>2200w</td></tr><tr><td>Max  Power</td><td>5000w</td></tr><tr><td>Color</td><td>Rubber black, White</td></tr><tr><td>Dimension & Weight</td></tr><tr><td>Dimension</td><td>590mm（H） x495mm(L） X 180mm（W）</td></tr><tr><td>Package Size(mm)</td><td>760mm(H) x 565mm(L) x 260mm( W)</td></tr><tr><td>Pedal Altitude (from ground)</td><td>160mm</td></tr><tr><td>Tire Size</td><td>18inch  Diameter 480mm</td></tr><tr><td>Weight</td><td>Around 24kg</td></tr><tr><td>EUC Port</td><td>Charging port x 2; Switch port x1; Light sensor port x1; USB discharge port x2 (one port support USB plug and play )</td></tr><tr><td>Protective Measures</td></tr><tr><td>Tilt Protection</td><td>45° left and right side. ( Motor stalls when over 45° )<br>Place the machine vertically on the ground will restart automatically, no need to restart manually</td></tr><tr><td>Speed Limit Protection</td><td>Beep alarm or voice alarm when exceed limit speed</td></tr><tr><td>Low Battery Protection</td><td>Low battery protection activated on 30% battery, speed will decreases linearly; when the battery is lower than 5%, voice alarm for charge, when battery at 0%, the front part of the pedal will rise to decelerate until full stop</td></tr><tr><td>Alarm & Features</td></tr><tr><td>Swith On/Off</td><td>Short press power key: turn on<br>Long press: hear a click then turn off</td></tr><tr><td>Battery Indicate</td><td>Led shows battery level when EUC power on at rest</td></tr><tr><td>Sound Notification</td><td>EUC fall down, buzzer beep alarm for 5 seconds,<br>Voice alarm：<br>1. Low battery alarm: Your device is in low battery, please charge it. (Please charge the device ASAP)<br>2. Over speed alarm: Pls decelerate. (Please slow down at once)<br>3. Connect Bluetooth: Bluetooth is connected.<br>4. Disconnect Bluetooth: Blutooth is disconnected.<br>5. Over voltage alarm: Be caution, over voltage. (Please pay attention, this is dangerous alarm! )<br>6. Over heated alarm: Be caution, over power. (Please pay attention, at this condition power is about to reach the limit )<br>7. Buzzer beeping</td></tr><tr><td>Package</td></tr><tr><td>Standard Accessories</td><td>Charger x1，user mannuel x1，warranty card x1，certificate x1</td></tr></tbody></table>\n<p></p>\n<p></p>\n<figure><iframe width=\"500\" height=\"281\" src=\"//www.youtube.com/embed/x_Nqzjy8aJA\" frameborder=\"0\" allowfullscreen=\"\"></iframe></figure>\n<figure><iframe width=\"500\" height=\"281\" src=\"//www.youtube.com/embed/Si8mUNg09wg\" frameborder=\"0\" allowfullscreen=\"\"></iframe></figure>\n<figure><iframe width=\"500\" height=\"281\" src=\"//www.youtube.com/embed/RxnlEu8cIjI\" frameborder=\"0\" allowfullscreen=\"\"></iframe></figure>",
        "meta_description": "Scooter de una rueda autobalance -Velocidad máxima 50km/h -Rinde hasta 100km* -\n\nLa linea mas grande de KingSong es en aro 18\", probado y testeado a más no poder. Potente, fiel, elegante, simple, incansable y cumplidor.\nVIDEOS: ",
        "price": 1820000.0,
        "cost_per_item": null,
        "compare_at_price": null,
        "weight": 28.0,
        "stock": 2,
        "stock_unlimited": false,
        "sku": null,
        "brand": "KingSong",
        "barcode": null,
        "featured": true,
        "status": "available",
        "shipping_required": true,
        "created_at": "2021-06-24 05:32:19 UTC",
        "updated_at": "2023-04-06 21:56:28 UTC",
        "package_format": "box",
        "length": 28.0,
        "width": 56.0,
        "height": 68.0,
        "diameter": 0.0,
        "google_product_category": null,
        "categories": [{
            "id": 994250,
            "name": "Monociclos Electricos",
            "description": null
        }],
        "images": [{
            "id": 33949204,
            "position": 1,
            "url": "https://images.jumpseller.com/store/ecobot1/10259891/5.png?1680817634"
        }, {
            "id": 29850740,
            "position": 2,
            "url": "https://images.jumpseller.com/store/ecobot1/10259891/7.png?1680817634"
        }, {
            "id": 17282864,
            "position": 3,
            "url": "https://images.jumpseller.com/store/ecobot1/10259891/__1-800_800.png?1680817634"
        }],
        "variants": [],
        "fields": [],
        "permalink": "monociclo-electrico-kingsong-18l",
        "discount": "182000.0",
        "currency": "CLP"
    }';
$tblName = 'products_js';
$key_items = 'product';

$sql = $ajaxf->JSON_to_table($your_JSON_variable, $tblName, $key_items);

Funciones::print_r_html($sql, "json to table, resultado:");

