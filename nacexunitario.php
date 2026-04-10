<?php

//20180620 mexpositop
include_once dirname(__FILE__) . '/nacexutils.php';
include_once dirname(__FILE__) . '/nacex.php';
include_once dirname(__FILE__) . '/nacexDAO.php';
include_once dirname(__FILE__) .'/nacexDTO.php';
include_once dirname(__FILE__) .'/nacexVIEW.php';
include_once dirname(__FILE__) .'/nacexWS.php';

if (Configuration::get('NACEX_SHOW_ERRORS') == 'SI') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

class nacexunitario extends AdminController {
    private $_html = '';
    private $nacex;
    public function __construct()
    {
        $this->nacex = new nacex();
        $this->display = 'view';
        parent::__construct();

        $this->bootstrap = true;
        $this->meta_title = $this->nacex->l('Unitary search');
        $this->displayName = $this->nacex->l('Unitary search');
        $this->title = $this->nacex->l('Unitary search');
        $this->page_header_toolbar_title = $this->nacex->l('Unitary search');
        //$this->description = $this->nacex->l('Documentación Unitaria de expediciones Nacex');
        //$this->addCSS(_MODULE_DIR_. 'nacex/css/nacex.css');
    }

    public function setMedia($isNewTheme = false)
    {
        $this->addCSS(_MODULE_DIR_ . 'nacex/css/nacex.css', 'all', null, true);
        $this->context->controller->addJS(_MODULE_DIR_ . 'nacex/js/unitaria.js');
        $this->context->controller->addJS(_MODULE_DIR_ . 'nacex/js/nacex.js');
        parent::setMedia();
    }

    public function initContent()
    {
        $url = $this->context->link->getAdminLink('AdminOrders', true);
        //LOAD VARIABLES, LIBRARIES JS AND GET_UNITARIA
        $nacex_impseg = configuration::get('NACEX_DEFAULT_IMP_SEG');

        /** Añadir icono para imprimir una expedición concreta **/
        $etiquetaURL = Configuration::get('NACEX_WS_URL');
        $etiquetaURL .= substr($etiquetaURL, -1) != '/' ? '/' : '';

        $nacexDTO = new NacexDTO();
        $controller = $nacexDTO->getPath() . 'CambioEstadoPedido.php';
        $param = isset($_GET['id_order']) ? $_GET['id_order'] : '';

        // Servicio 44
        $filePath = _PS_BASE_URL_ . _MODULE_DIR_ . 'nacex/files/';

        $this->_html .= "
            <div id='ncx-loading' style='display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:9999;'>
                <div style='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);'>
                    <i class='material-icons' style='font-size:48px;color:#25b9d7;animation:spin 1s linear infinite;'>autorenew</i>
                </div>
            </div>
            <style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
            <script>
//LOAD PARAMETERS GLOBAL PARAMETERS        
                            var Base_uri ='" . __PS_BASE_URI__ . "';
                            var adminUrlToken = '" . $url . "';
/////////////////////////////////////////////////////////////////////            
                            function ready (){
                                // Guardamos la URL con el token de ordersToken correspondiente
                                localStorage.setItem('ordersToken' , adminUrlToken);
                                unitaria.get_unitaria('tab',Base_uri);
                            }
                            function nacexcontenido(){
                		
                		    if ( (document.getElementById(\"nacex_contenido\").value) === \"OTROS\"){
                		    
                		        document.getElementById(\"tagnacexcontenido\").style.visibility = \"visible\";
                		        document.getElementById(\"descripcionnacexcontenido\").style.visibility = \"visible\";
                		        document.getElementById(\"nacex_descripcion_contenido\").focus();
                		        
                		    }else{
                		    
                		        document.getElementById(\"tagnacexcontenido\").style.visibility = \"hidden\";
                		        document.getElementById(\"descripcionnacexcontenido\").style.visibility = \"hidden\";
                		        document.getElementById(\"nacex_contenido\").focus();
                		    
                		    }
                		}     
                		function checkTipoPrealerta(tipo){
                            var obj = document.getElementsByName(\"nacex_tip_pre1\");
                            for(i=0; i<obj.length; i++){
                                if(obj[i].value==tipo){
                                obj[i].checked = true;
                                setprealerta(tipo);
                                }
                            }	
                		}
                		function checkModoPrealerta(modo){
                            var obj = document.getElementsByName(\"nacex_mod_pre1\");
                            for(i=0; i<obj.length; i++){
                                if(obj[i].value==modo){
                                obj[i].checked = true;
                                setprealertaplus(modo);
                                }
                            }	
                		}
                		function checkTipoSeguro(tipo){
                		  if (tipo != \"N\"){
                        		document.getElementById(\"nacex_imp_seg\").disabled = \"\";
                        		document.getElementById(\"nacex_imp_seg\").value = \"' . $nacex_impseg . '\";
                    		}else{
                        		document.getElementById(\"nacex_imp_seg\").disabled = \"disabled\";
                        		document.getElementById(\"nacex_imp_seg\").value = \"\";
                    		}	
                		}
                		
                		function descripcioncontenido(){
                            
                            if(document.getElementById(\"nacex_descripcion_contenido\") !== null) 
                            {
                                var x = document.getElementById(\"nacex_descripcion_contenido\").value;
                                x = x.trim();
                                                        
                                if ( (document.getElementById(\"nacex_contenido\").value) == \"OTROS\" && (x == \"\") ){
                                    alert(\"" . $this->nacex->l('The Content description field is required') . '");
                                    setTimeout(function(){document.getElementById("nacex_descripcion_contenido").focus();}, 2);
                                    document.getElementById("nacex_descripcion_contenido").value ="";
                                }else{
                                    document.getElementById("nacex_descripcion_contenido").value = x;
                                }
                		    }
                		}      	
					function setprealerta(tipo){
						if(tipo === "N"){
							document.getElementById("nacex_pre1").disabled=true;	
							document.getElementById("nacex_pre1").value = "";
							deshabilitamodosprealerta();
						}else if(tipo === "S" && cli_tlf !== ""){
							document.getElementById("nacex_pre1").disabled=false;	
							document.getElementById("nacex_pre1").value = document.getElementById("cli_tlf").value;
							document.getElementById("nacex_pre1").focus();
							habilitamodosprealerta();
						}else if(tipo === "E"){
							document.getElementById("nacex_pre1").disabled=false;	
							document.getElementById("nacex_pre1").value = document.getElementById("cli_email").value;
							document.getElementById("nacex_pre1").focus();
							habilitamodosprealerta();
						}
					}
					function setprealertaplus(tipo){
						if(eval(document.getElementById("nacex_pre1_plus"))){
							if(tipo==="S" || tipo==="R"){
								document.getElementById("nacex_pre1_plus").value = "";
								document.getElementById("nacex_pre1_plus").disabled = true;
							}else if(tipo === "P" || tipo ==="E"){
								document.getElementById("nacex_pre1_plus").value = "' . Configuration::get('NACEX_PREAL_PLUS_TXT') . "\";
								document.getElementById(\"nacex_pre1_plus\").disabled = false;
								document.getElementById(\"nacex_pre1_plus\").focus();
							}
						}
					}
					function deshabilitamodosprealerta(){
						if(eval(document.getElementById(\"nacex_pre1_plus\"))){
							document.getElementById(\"nacex_pre1_plus\").value = \"\";
							document.getElementById(\"nacex_pre1_plus\").disabled = true;
						}	
						var obj = document.getElementsByName(\"nacex_mod_pre1\");
						for(i=0; i<obj.length; i++){
							obj[i].checked=false;
							obj[i].disabled=true;
						}
					}
					function habilitamodosprealerta(){
						var obj = document.getElementsByName(\"nacex_mod_pre1\");
						for(i=0; i<obj.length; i++){
							if(i==0){
								obj[i].checked=true;
							}
							obj[i].disabled=false;
						}
					}
					
					/** Añadir icono para imprimir una expedición concreta **/
                    function ionaPrint(item, id) {
			    
                        var etiquetaURL = '" . $etiquetaURL . "'+
                                        'ws?method=getEtiqueta&user=".Configuration::get('NACEX_WSUSERNAME')."'+
                                        '&pass=".Configuration::get('NACEX_WSPASSWORD')."'+
                                        '&data=modelo=".Configuration::get('NACEX_PRINT_MODEL')."'+
                                        '|codexp=' +item;
                        
                        var posting = $.post( '".Configuration::get('NACEX_PRINT_IONA')."print', { 
                            url: etiquetaURL,
                            impresora : '".Configuration::get('NACEX_PRINT_ET')."'
                          } );
                        posting.done(function( data ) {
                            $.ajax({
                                url : '" . $controller . "',
                                method : 'POST',
                                data : {
                                    ajax : 1,
                                    controller : 'AdminNacexCambioEstado',
                                    action : 'cambioEstado',
                                    id_order : id
                                },
                                success : function (result) {
                                    alert('" . $this->nacex->l('Order status has been changed') . "');
                                }
                            });
                        });
                        posting.fail(function( data ) { alert('" . $this->nacex->l('Error on printing label') . "'); });
                    }
                    
                    // Funcionalidad para el Servicio 44
                    function downloadPDF(folder, exp, id) {
                    
                        var filePath = '" . $filePath . "' + folder + '/';
                        
                        window.open(filePath + exp + '.pdf','_blank');
                        
                        if(folder === 'etiquetas'){
                            // Cambiamos el estado del pedido al de la impresión de etiqueta
                            $.ajax({
                                url : '" . $controller . "',
                                method : 'POST',
                                data : {
                                    ajax : 1,
                                    controller : 'AdminNacexCambioEstado',
                                    action : 'cambioEstado',
                                    id_order : id
                                },
                                async: false,
                                success : function (result) {
                                    alert('" . $this->nacex->l('Order status has been changed') . "');
                                }
                            });
                        }
                    }
                        $(document).ready(ready);
                    </script>     
                    <div id='cabecera'></div>
                    <div id='resultado'></div>
        ";
        $this->context->smarty->assign('content', $this->_html);
    }

}
