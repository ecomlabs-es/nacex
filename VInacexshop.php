<?php
include_once(dirname(__FILE__) . '/nacex.php');

class VInacexshop
{
//NACEX SHOP LIST METHODS
    //public function get_list_of_nacex_shop (&$_html,$chivato,$ag,$tienda,$prov){
    public function get_list_of_nacex_shop(&$_html, $chivato, $ag, $prov, $tienda = null)
    {
        if (!is_null($tienda))
            $tienda = nacexutils::toUtf8($tienda[2]);
        else {
            $tienda = $ag[15];
            // Cambiamos , por . de los datos del archivo droppoint
            $ag[13] = floatval(str_replace(',', '.', $ag[13]));
            $ag[14] = floatval(str_replace(',', '.', $ag[14]));
        }

        $nacex = new nacex();
        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        // Buscamos el http y lo reemplazamos por https o viceversa
        $storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;

        $direccion = $ag[1];
        $_sbd = $ag[10] != "null" ? $ag[10] : "------";
        $_dmg = $ag[11] != "null" ? $ag[11] : "------";
        //<input type='radio' name='shop_item' id='shop_item' value='$chivato' shopcodigo='$ag[0]' shopalias='$ag[12]' shopnombre='$tienda' shopdireccion='$ag[1]' puebcp='$ag[3]' puebnombre='$ag[2]' provnombre='$prov' tlf='$ag[4]'/></td><td>
        $_html .= "<div style='font-size:10pt; color:#000000; font-family:Calibri'>";
        $_html .= "<table align='left'>
                            <!--1085|0831-03|LIBRERíA OPERA|Major 7|08870|SITGES|BARCELONA|938942143-->
				            <tr>
				                <td width='10%'><input type='radio' name='shop_item' id='shop_item' value='$chivato' shopcodigo='$ag[0]' shopalias='$ag[12]' shopnombre='$tienda' shopdireccion='$ag[1]' puebcp='$ag[3]' puebnombre='$ag[2]' provnombre='$prov' tlf='$ag[4]'/></td><td width='40%'>$tienda</td><td width='60%'>$direccion</td><td width='25%'><a href='#' onClick=\"MyWindow = window . open('https://maps.google.com/?q=$ag[13],$ag[14]', 'MyWindow', 'width=1024,height=768'); return false;\">[" . $nacex->l('See map') . "]</a></td><td width='10%'><img name='horario_$chivato' src=" . $storeURL . __PS_BASE_URI__ . "modules/nacex/images/svg/nacex_servicio_08.svg width='20' height='20' title='" . $nacex->l('Click to see schedule') . "' onclick='hide_show(this);'></td>
				            </tr>
                      </table>";
        $_html .= "</div>";
        $_html .= "<div id='horario_$chivato' style='font-size:14pt; font-family:Calibri; display: none; flex !important; flex-direction: column !important; justify-content: center !important;'>";
        $_html .= "<table align='center'>";
        $_html .= "<tr>";
        $_html .= "<td><hr></td><td><hr></td>";
                    $_html .="</tr>";
        $_html .= "<tr>";
        $_html .= "<td><i><b>" . $nacex->l('Monday') . ":&nbsp;&nbsp;</b></i></td><td>";
        $this->horario_nacex_shop_list($_html, $ag[5]);
                    $_html .="</tr>";
        $_html .= "<tr>";
        $_html .= "<td><i><b>" . $nacex->l('Tuesday') . ":&nbsp;&nbsp;</b></i></td><td>";
        $this->horario_nacex_shop_list($_html, $ag[6]);
                    $_html .="</tr>";
        $_html .= "<tr>";
        $_html .= "<td><i><b>" . $nacex->l('Wednesday') . ":&nbsp;&nbsp;</b></i></td><td>";
        $this->horario_nacex_shop_list($_html, $ag[7]);
                    $_html .="</tr>";
        $_html .= "<tr>";
        $_html .= "<td><i><b>" . $nacex->l('Thursday') . ":&nbsp;&nbsp;</b></i></td><td>";
        $this->horario_nacex_shop_list($_html, $ag[8]);
        $_html .= "<tr>";
        $_html .= "<td><i><b>" . $nacex->l('Friday') . ":&nbsp;&nbsp;</b></i></td><td>";
        $this->horario_nacex_shop_list($_html, $ag[9]);
                    $_html .="</tr>";
        $_html .= "<tr>";
        $_html .= "<td><i><b>" . $nacex->l('Saturday') . ":&nbsp;&nbsp;</b></i></td><td>";
        $this->horario_nacex_shop_list($_html, $ag[10]);
                    $_html .="</tr>";
        $_html .= "<tr>";
        $_html .= "<td><i><b>" . $nacex->l('Sunday') . ":&nbsp;&nbsp;</b></i></td><td>";
        $this->horario_nacex_shop_list($_html, $ag[11]);
                    $_html .="</tr>";
                    $_html .="<tr>";
                        $_html .="<td><hr></td><td><hr></td>";
                    $_html .="</tr>";
            $_html .="</table>";
        $_html .="</div>";
    }
    public static function get_list_of_nacex_shop_script(&$_html)
    {
        $_html .= "<script></script>";
    }

    public static function get_nacex_shop_list_footer_jq(&$_html)
    {
        $nacex = new nacex();
        $url = _PS_BASE_URL_ . _MODULE_DIR_;
        $opc = Configuration::get("NACEX_OPC_ID_BOTON");
        $alert = $nacex->l('You must select a NacexShop point');
        $_html .= "<script type='text/javascript'>
                $(document).ready(function() {
                    var isIE = /*@cc_on!@*/false || !!document.documentMode;
                    if(isIE) {
                        $('div#div_table_nacex_shop input[type*=\'radio\']').bind('click', function(event) {
                             seleccionar_punto_shop('$url','$opc','$alert');
                        });
                    } else {
                        $(document).on('change','#div_table_nacex_shop #shop_item', function() {
                            seleccionar_punto_shop('$url','$opc','$alert');
                        });
                    }
                });
            </script>
            <div align='center' style='clear: left;'></div><br><hr>";
    }

    /*public static function get_nacex_shop_list_footer (&$_html){
        //$_html .= "<div id='dialog-confirm'></div>";
        $url=_PS_BASE_URL_._MODULE_DIR_;
        $_html.="<div align='center' style='clear: left;'>
                    <input type='button' align='right' class='ncx_button' id='btnseleccionarpunto' value='Seleccionar Punto' onclick=seleccionar_punto_shop('$url','" . Configuration::get("NACEX_OPC_ID_BOTON") . "')>
                 </div>
                 <br>
                 <hr>";

    }*/
    private function horario_map(&$_horario)
    {
        if ($_horario == "null") {
            $_horario = '------';
        }
    }

//NACEX GOOGLE MAPS METHODS
    //public function get_nacex_shop_map_header(&$_html, $cp, $lat, $long)
    public function get_nacex_shop_map_header(&$_html)
    {
        // Quitamos el nombre del icono porque dependerá si es Agencia o Punto NacexShop
        $_path_icon = _PS_BASE_URL_ . __PS_BASE_URI__ . "/modules/nacex/images/";

        $_html .= "
                  <style>
                    #map {
                       height: 560px;
                        width: 110%;
                        margin-left: -5%;
                    }
                    
                    /* Añadimos los estilos del popup */
                    #ncx_shop_filtros_buttons_container {
                        margin-top: 15px;
                    }
                    
                    #cabecerashop {
                        color: darkorange;
                        display: flex;
                        flex-direction: row;
                        justify-content: center;
                    }
                    
                    .ncxshp-image img {
                         height: 90px;
                         float: left;
                         margin-bottom: 20px;
                         margin-right: 20px;
                    }
                    
                    .nompop{
                         color: #E68800 !important;
                         text-decoration-line: underline !important;
                         font-weight: bold !important;
                     }
                     
                    .horarios {
                        white-space: pre;
                        font-family: monospace;
                        margin-right: 20px;
                        margin-bottom: 20px;
                    }
                    
                    #contenedor-pop {
                        width: 475px;
                    }          
                    
                    #contenedor-pop {
                        width: 480px;
                    }          
                    
                    #botonshop {
                        color: #fff;
                        background-color: #E68800;
                        border: 1px solid #E68800;
                        padding: 5px;
                        cursor: pointer;
                    }
                    
                    #botonshop:hover {
                        color: #E68800;
                        background-color: #fff;
                    }
                    
                    /*infowindow*/
                    .gm-style-iw {
                        width: auto !important; 
                    }
                  </style>
                  <div id='map'></div>";

//                   $nada= "<script>
//                        var contentshop = new Array();
//                        var puntosNxsp = new Array();
//                        var titles = new Array();
//                        var icono = new Array();
//                        var iconPath = '" . $_path_icon . "';
//                        var opc = '" . Configuration::get("NACEX_OPC_ID_BOTON") . "';
//                        function initMap() {
//                            var center = {lat: " . $lat . ", lng: " . $long . "};
//                            var map = new google.maps.Map(document.getElementById('map'), {center: center,zoom: 11});";
    }

//    public function get_nacex_shop_map_body ($cp,&$_html,$punto,$chivatomarker,$size){
//        $this->provincia($cp,$prov);
//        $this->horario_map($punto[5]);
//        $this->horario_map($punto[6]);
//        $this->horario_map($punto[7]);
//        $this->horario_map($punto[8]);
//        $this->horario_map($punto[9]);
//        $this->horario_map($punto[10]);
//        $this->horario_map($punto[11]);
//        $punto[1] = addslashes($punto[1]);
//        $punto[2] = nacexutils::toUtf8($punto[2]);
//        $punto[15] = nacexutils::toUtf8($punto[15]);
//        $selectshop = "$punto[0]|$punto[12]|$punto[2]|$punto[1]|$punto[3]|$punto[2]|$prov|$punto[4]";
//
//        // Esta variable asigna el icono de agencia y de nacexshop dependiendo del tipo
//        $icono = substr($punto[12], strpos($punto[12], '-')) == '00' ? '_punto_NACEX_75G.png' : '_punto_NACEXshop_75G.png';
//
//        $url = _PS_BASE_URL_ . _MODULE_DIR_;
//
//        $_html .= "
//                contentshop['" . $chivatomarker . "'] = '<input type=\"text\" id=\"pedido_$chivatomarker\" value=\"$selectshop\"hidden>'+
//                    '<h3 class=nompop>" . $punto[15] . "&nbsp;&nbsp;<img name=horario_" . $chivatomarker . " src=" . _PS_BASE_URL_ . __PS_BASE_URI__ . "modules/nacex/images/nacex_servicio_08.jpg width=20 height=20 title=Pulsar Para Ver Horario onclick=hide_show(this);></h3></h3>'+
//                    '" . $punto[1] . " (" . $punto[3] . ") - " . $punto[2] . "'+
//                    '<div id=horario_" . $chivatomarker . " align=center style=font-size:9pt;font-family:Calibri;display:none;>'+
//                    '<br>'+
//                    '<hr>'+
//                    '<b>HORARIO</b>'+
//                    '<hr>'+
//                        '<table>'+
//                            '<tr>'+
//                                '<td align=\"left\" style=\"font-size:12px\"><i><b>LUNES:&nbsp;&nbsp;</b></i></td><td>" . $punto[5] . "</td>'+
//                            '</tr>'+
//                            '<tr>'+
//                                '<td align=\"left\" style=\"font-size:12px\"><i><b>MARTES:&nbsp;&nbsp;</b></i></td><td>" . $punto[6] . "</td>'+
//                            '</tr>'+
//                            '<tr>'+
//                                '<td align=\"left\" style=\"font-size:12px\"><i><b>MIERCOLES:&nbsp;&nbsp;</b></i></td><td>" . $punto[7] . "</td>'+
//                            '</tr>'+
//                            '<tr>'+
//                                '<td align=\"left\" style=\"font-size:12px\"><i><b>JUEVES:&nbsp;&nbsp;</b></i></td><td>" . $punto[8] . "</td>'+
//                            '</tr>'+
//                            '<tr>'+
//                                '<td align=\"left\" style=\"font-size:12px\"><i><b>VIERNES:&nbsp;&nbsp;</b></i></td><td>" . $punto[9] . "</td>'+
//                            '</tr>'+
//                            '<tr>'+
//                                '<td align=\"left\" style=\"font-size:12px\"><i><b>SABADO:&nbsp;&nbsp;</b></i></td><td>" . $punto[10] . "</td>'+
//                            '</tr>'+
//                            '<tr>'+
//                                '<td align=\"left\" style=\"font-size:12px\"><i><b>DOMINGO:&nbsp;&nbsp;</b></i></td><td>" . $punto[11] . "</td>'+
//                            '</tr>'+
//                        '</table>'+
//                    '</div>'+
//                    '<br>'+
//                    '<div align=\"right\">'+
//                        '<input type=\"button\" value=\"Seleccionar Punto NacexShop\" onclick=seleccionar_punto_map(\"$url\",document.getElementById(pedido_$chivatomarker.id).value,\"'+opc+'\")>'+
//                    '</div>';
//                puntosNxsp['" . $chivatomarker . "'] = [" . $punto[13] . "," . $punto[14] . "];
//                titles['" . $chivatomarker . "'] = '" . $punto[1] . " (" . $punto[3] . ") - " . $punto[2] . "';
//                icono['" . $chivatomarker . "'] = '" . $icono . "';
//                           ";
//    }


    public function get_nacex_shop_map_body($cp, &$_html, $tiendas)
    {
        $puntos = json_encode($tiendas);
        nacexutils::provincia($cp, $prov);
        $_path_icon = __PS_BASE_URI__ . "modules/nacex/images/punto_NACEXshop.png";
        $nacex = new nacex();

        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        $storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;
        $uri = $storeURL . __PS_BASE_URI__ . 'modules/';

        $_html .= '<script>
                function initMap() {
                    var puntos = ' . $puntos . ';
                    var prov = ' . '"' . $prov . '"' . ';
                    var map = new google.maps.Map(document.getElementById("map"));
                    var bounds = new google.maps.LatLngBounds();
                    var infowindow = new google.maps.InfoWindow({maxWidth: 700});
                    var iconBase = ' . '"' . $_path_icon . '"' . ';
                    var contentshop = [];
                    
                    var opc = "' . Configuration::get("NACEX_OPC_ID_BOTON") . '";
                    
                    for(var i = 0; i < puntos.length; i++) {
                        var punto = puntos[i];
                        
                        var lunes = (punto[5] !== "") ? "' . $nacex->l('Monday') . ': " + punto[5].replace("/-", "") : "' . $nacex->l('Monday') . ': ' . $nacex->l('closed') . '";
                        var martes = (punto[6] !== "") ? "' . $nacex->l('Tuesday') . ': " + punto[6].replace("/-", "") : "' . $nacex->l('Tuesday') . ': ' . $nacex->l('closed') . '";
                        var miercoles = (punto[7] !== "") ? "' . $nacex->l('Wednesday') . ': " + punto[7].replace("/-", "") : "' . $nacex->l('Wednesday') . ': ' . $nacex->l('closed') . '";
                        var jueves = (punto[8] !== "") ? "' . $nacex->l('Thursday') . ': " + punto[8].replace("/-", "") : "' . $nacex->l('Thursday') . ': ' . $nacex->l('closed') . '";
                        var viernes = (punto[9] !== "") ? "' . $nacex->l('Friday') . ': " + punto[9].replace("/-", "") : "' . $nacex->l('Friday') . ': ' . $nacex->l('closed') . '";
                        var sabado = (punto[10] !== "" && punto[10] !== "null") ? "' . $nacex->l('Saturday') . ': " + punto[10].replace("/-", "") : "' . $nacex->l('Saturday') . ': ' . $nacex->l('closed') . '";
                        var domingo = (punto[11] !== "" && punto[11] !== "null") ? "' . $nacex->l('Sunday') . ': " + punto[11].replace("/-", "") : "' . $nacex->l('Sunday') . ': ' . $nacex->l('closed') . '";
                        
                        /*
                            // Esto es un problema que arreglé para la Vall d\'Uixò 12200 
                            $punto[1] = addslashes($punto[1]);
                            $punto[2] = nacexutils::toUtf8($punto[2]);
                            $punto[2] = addslashes($punto[2]);
                            $punto[15] = nacexutils::toUtf8($punto[15]);
                            $punto[15] = addslashes($punto[15]);
                         */
                        punto[15] = punto[15];
                        punto[1] = punto[1];
                        punto[3] = punto[3];
                        punto[2] = punto[2];
                        punto[13] = punto[13].replace(",", ".");
                        punto[14] = punto[14].replace(",", ".");
                        var str = "E";
                        var selectshop = punto[0]+"|"+punto[12]+"|"+punto[15]+"|"+punto[1]+"|"+punto[3]+"|"+punto[2]+"|" + prov + "|";
                        var selec = selectshop.replace(/ /g, "~");  //Usando la expresión regular nos reemplaza todos los espacios en blanco
                        
                        var fiesta = "";
                        // Días festivos
                        var festivos = 0;
                        if(typeof punto[16] !== "undefined" && punto[16]) festivos = punto[16].split(",");
                        
                        if (festivos.length > 0) {
                            for (var j = 0; j < festivos.length; j++) {
                                fiesta += festivos[j] + ", ";
                            }
                            // Quitamos el "," final
                            fiesta = fiesta.substr(0, -2);
                        }
                    
                        var marker = new google.maps.Marker({position: new google.maps.LatLng(punto[13],punto[14]), map: map});
                        marker.setIcon(iconBase);
                        bounds.extend(marker.position);
                        
                        contentshop[i] = printInfoNcxShp(punto,lunes,martes,miercoles,jueves,viernes,sabado,domingo,str,selec,fiesta, opc);
                        google.maps.event.addListener(marker, "click", (function(marker, i) {
                            return function() {
                                infowindow.setContent(contentshop[i]);
                                infowindow.open(map,marker);    
                            }
                        })(marker, i));
                    }
                    
                    // Ajustamos el zoom hasta que se vean todos los puntos
                    map.fitBounds(bounds);
                    // Problemas con mapas que no se ajustan
					google.maps.event.addListenerOnce(map, "idle", function() {
						if (map.getZoom() < 10) map.setZoom(10);
					});
                }
                
                function printInfoNcxShp(punto,lunes,martes,miercoles,jueves,viernes,sabado,domingo,str,selec,fiesta, opc) {
                    var html = "<div id=contenedor-pop>";
                    html += "<h3 class=nompop>" + punto[15] + "</h3>";
                    html += "<h4 class=dirpop><b>" + punto[1] + " (" + punto[3] + ") - " + punto[2] + "</b></h4>";
                    html += "<div class=\'ncxshp-image\'><img src=\'https://www.nacex.es/fotoNacexShop.do?codigo=" + punto[0] + "\' /></div>";
                    html += "<div class=horarios>";
                    html += lunes + "<br/>";
                    html += martes + "<br/>";
                    html += miercoles + "<br/>";
                    html += jueves + "<br/>";
                    html += viernes + "<br/>";
                    html += sabado + "<br/>";
                    html += domingo + "<br/>";
                    html += fiesta + "<br/>";
                    html += "</div></div>";
                    html += "<div align=right>";
                    //html += "<input type=button id=botonshop value=Seleccionar onclick=seleccionadoNacexShop(\'" + str + "\',\'" + selec + "\',true) />";
                    html += "<input type=button id=botonshop value=' . $nacex->l('Select') . ' onclick=\"seleccionadoNacexShop(\'" + str + "\',\'" + selec + "\',\'' . $uri . '\',\'" + opc + "\');\" />";
                    html += "</div>";
                    
                    return html;
                }
                </script>';
    }

//    public function get_nacex_shop_map_footer(&$_html)
//    {
//        $_html .= "
//                       var infoWindow = new google.maps.InfoWindow();
//                       for(var p = 0; p < contentshop.length; p++) {
//
//                            var marker= new google.maps.Marker({
//                                position: new google.maps.LatLng(puntosNxsp[p][0], puntosNxsp[p][1]),
//                                title: titles[p],
//                                map: map
//                            });
//
//                            // Asignamos la ruta y el nombre del correspondiente icono al punto
//                            marker.setIcon(iconPath + icono[p]);
//                            //marker.setIcon(iconPath);
//
//                            var content = contentshop[p];
//
//                            google.maps.event.addListener(marker, 'click', (function(marker, content) {
//                                return function() {
//                                    infoWindow.setContent(content);
//                                    infoWindow.open(map, marker);
//                                }
//                            })(marker, content));
//                       }
//                    </script>";
//    }
    private function horario_nacex_shop_list(&$_html, $_horario)
    {
        if ($_horario != "null") {
            $_html .= $_horario . "</td>";
        } else {
            $_html .= '----------------------------------' . "</td>";
        }
    }
}