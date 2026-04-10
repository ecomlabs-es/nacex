<?php

/*
 * mexpositop 2017
 */
include_once dirname(__FILE__) . '/nacex.php';
include_once dirname(__FILE__) . '/nacexWS.php';
include_once dirname(__FILE__) . '/nacexDTO.php';
include_once dirname(__FILE__) .'/nacexDAO.php';
include_once dirname(__FILE__) .'/AdminConfig.php';
include_once dirname(__FILE__) .'/nacexutils.php';
include_once dirname(__FILE__) .'/hash.php';

/** Añadimos este archivo porque si no peta al hacer la llamada a NacexShop **/
require_once(dirname(__FILE__).'/ROnacexshop.php');

class nacexVIEW
{
    public static function showExpedicionForm($id_order, $datospedido, $datosexpedicion, $isnacexcambio, $ver177 = false)
    {
        $nacex = new nacex();
        $nacexDTO = new nacexDTO();
        $html = '';

        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        // Buscamos el http y lo reemplazamos por https o viceversa
        $storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;

        //echo "<h1>Carrier ID: " . $datospedido[0]['id_carrier'] . "</h1>";
        //var_dump($datospedido);

        /*$nacex_impseg = $isnacexcambio ? Tools::getValue('nacex_imp_seg') ? Tools::getValue('nacex_imp_seg') : "" :
            Tools::getValue('nacex_imp_seg') ? Tools::getValue('nacex_imp_seg') : Configuration::get('NACEX_DEFAULT_IMP_SEG');*/
        $nacex_impseg = $isnacexcambio ? Tools::getValue('nacex_imp_seg') ? Tools::getValue('nacex_imp_seg') : '' : (Tools::getValue('nacex_imp_seg') ? Tools::getValue('nacex_imp_seg') : Configuration::get('NACEX_DEFAULT_IMP_SEG'));
        $css_id = 'ncx_info_exp';
        $webtext = $nacex->l('Go to Nacex web');
        $webdir = 'https://www.nacex.es';
        //        $webimg = _MODULE_DIR_ . "nacex/images/logos/NACEX.png";
        $webimg = _MODULE_DIR_ . 'nacex/images/logos/nacex_logista.png';
        $titulo = ($isnacexcambio ? $nacex->l('Generate expedition') . ' Nacex C@mbio' : $nacex->l('Generate expedition')) . '<span  style="padding-left: 10px;"><b>' . nacexutils::getReferenciaGeneral() . $id_order . '</b></span>';

        $shop_codigo = null;

        $modo_f = (Configuration::get('NACEX_SERV_BACK_OR_FRONT') == 'F');

        $att = '';

        $externalCarriers = explode('|', Configuration::get('NACEXSHOP_EXTERNAL_MODULES'));

        $isShop = ($datospedido[0]['ncx'] != '1' &&
                $datospedido[0]['id_carrier'] == nacexDTO::getNacexShopIdCarrier()) ||
            nacexDTO::isNacexShopCarrier($datospedido[0]['id_carrier'])
            || in_array($datospedido[0]['id_carrier'], $externalCarriers);

        //Si ncx es "1" es Nacex normal, si no es un código NacexShop
        // Si las direcciones de envío y facturación son diferentes
        if ($isShop) {
            $iso_code = Language::getIsoById(nacexutils::getCurrentLang());

            $array_shop = explode('|', $datospedido[0]['ncx']);
            $shop_codigo = $array_shop[0];
            $css_id = 'ncx_info_shop';
            $webtext = $nacex->l('Go to NacexShop web');
            $webdir = 'https://www.nacexshop.com';
            $webimg = _MODULE_DIR_ . "nacex/images/logos/NACEXshop_sostenible_$iso_code.svg";

            $array_address_invoice = nacexDAO::getAddressInvoiceByOrder($id_order);
            $att = $array_address_invoice[0]['firstname'] . ' ' . $array_address_invoice[0]['lastname'];
        }

        $tlf = '';
        if ((isset($datospedido[0]['phone']) && $datospedido[0]['phone'] != '') && (isset($datospedido[0]['phone_mobile'])
                && $datospedido[0]['phone_mobile'] == '')) {
            $tlf = $datospedido[0]['phone'];
        } elseif (isset($datospedido[0]['phone_mobile']) && $datospedido[0]['phone_mobile'] != '') {
            $tlf = $datospedido[0]['phone_mobile'];
        }

        $cliente_tlf = $tlf;
        $cliente_email = $datospedido[0]['email'];

        $html .= '</script>
				<script>
					var cli_tlf = "' . addslashes(substr($cliente_tlf, 0, 50)) . '";
					var cli_email = "' . addslashes(substr($cliente_email, 0, 50)) . '";
					function setprealerta(tipo){
						if(tipo==="N"){
							document.getElementById("nacex_pre1").readOnly=true;	
							document.getElementById("nacex_pre1").value = "";
							deshabilitamodosprealerta();
						}else if(tipo === "S"){
							document.getElementById("nacex_pre1").readOnly=false;	
							if(cli_tlf !== "") document.getElementById("nacex_pre1").value = cli_tlf;
							else document.getElementById("nacex_pre1").value = "";
					//		document.getElementById("nacex_pre1").focus();
							habilitamodosprealerta();
						}else if(tipo === "E"){
							document.getElementById("nacex_pre1").readOnly=false;	
							document.getElementById("nacex_pre1").value = cli_email;
					//		document.getElementById("nacex_pre1").focus();
							habilitamodosprealerta();
						}
					}
					function setprealertaplus(tipo){
						if(eval(document.getElementById("nacex_pre1_plus"))){
							if(tipo==="S" || tipo==="R"){
								document.getElementById("nacex_pre1_plus").value = "";
								document.getElementById("nacex_pre1_plus").disabled = true;
							}else if(tipo === "P" || tipo==="E"){
								document.getElementById("nacex_pre1_plus").value = "' . Configuration::get('NACEX_PREAL_PLUS_TXT') . '";	
								document.getElementById("nacex_pre1_plus").disabled = false;
					//			document.getElementById("nacex_pre1_plus").focus();
							}
						}
					}
					function deshabilitamodosprealerta(){
						if(eval(document.getElementById("nacex_pre1_plus"))){
							document.getElementById("nacex_pre1_plus").value = "";
							document.getElementById("nacex_pre1_plus").disabled = true;
						}	
						var obj = document.getElementsByName("nacex_mod_pre1");
						for(i=0; i<obj.length; i++){
							obj[i].checked=false;
							obj[i].disabled=true;
						}
					}
					function habilitamodosprealerta(){
						var obj = document.getElementsByName("nacex_mod_pre1");
						for(i=0; i<obj.length; i++){
							if(i===0){
								obj[i].checked=true;
							}
							obj[i].disabled=false;
						}
					}
                    // Llamada ajax para que nos devuelva los puntos Shop que le pasemos
                    function getPuntosByCp() {
                        let cp = document.getElementById("cpChangePoint").value;
                        let shopId = document.getElementById("hidden_shop_codigo").innerHTML;
                        let _url = "' . $storeURL . __PS_BASE_URI__ . '";
                        _url += "modules/nacex/COeditarShopPuntoOrder.php";
                        
                        jQuery.ajax({
                            url: _url,
                            type: "post",
                            data: "cp="+cp+"&shopCod="+shopId,
                            beforeSend: function () {
                                // Show image container
                                jQuery("#spinningLoad").html(\'<img src= \"../modules/nacex/images/loading.gif\" style=\"width:30px;float: right;margin-right: 4em;margin-top: -2.5em\">\');
                            },
                            success: function (response) {
                                // Miramos si ya existe un elemento desplegable cpPointsChoices y lo eliminamos
                                if(jQuery("#cpPointsChoices").length > 0) jQuery("#cpPointsChoices").remove();
                                
                                jQuery("#spinningLoad").after(response);
                            },
                            complete: function (data) {
                                // Hide image container
                                jQuery("#spinningLoad").html("");
                            }
                        });
                    }

                    function blurDateValidation(customerDate) {
                        var valida = validateFutureDate(customerDate);
                        if(valida) saveDateLocalStorage(customerDate);
                        return valida;
                    }
                    
                    function formattedNow() {
                        var today = new Date();
                        var yyyy = today.getFullYear();
                        let mm = today.getMonth() + 1; // Months start at 0!
                        let dd = today.getDate();
                    
                        if (dd < 10) dd = "0" + dd;
                        if (mm < 10) mm = "0" + mm;
                    
                        return now = yyyy + "-" + mm + "-" + dd;
                    }
                    
                    // Validar que la fecha no es una anterior
                    function validateFutureDate(customerDate) {
                        var now = formattedNow();
                        return customerDate >= now;
                    }
                    
                    function saveDateLocalStorage(customerDate) {
                        localStorage.nacex_fec = customerDate;
                    }
                    
                    jQuery(document).ready(function() {
                        var date = (typeof localStorage.nacex_fec !== "undefined") ? localStorage.nacex_fec : formattedNow();
                        jQuery("#nacex_fec").val(date);
                    });
				</script>
		';

        //PAGO CONTRA REEMBOLSO --------------------------------------------------------------------------------------------------
        $metodo_pago = strtolower($datospedido[0]['module']);
        $array_modsree = explode('|', Configuration::get('NACEX_MODULOS_REEMBOLSO'));  //Nombres módulos pago contra reembolso indicados en Configuración
        $nacex_imp_ree = (in_array($metodo_pago, $array_modsree) && is_numeric($datospedido[0]['total_paid_real'])) ?
            number_format($datospedido[0]['total_paid_real'], 2) : '0';
        //------------------------------------------------------------------------------------------------------------------------

        $array_agclis = explode(',', Configuration::get('NACEX_AGCLI'));
        $select_agcli = '';
        for ($i = 0; $i < count($array_agclis); $i++) {
            $select_agcli = $select_agcli . '<option ' . nacexutils::markSelectedOption('nacex_agcli', 'NACEX_AGCLI', trim($array_agclis[$i])) . " value='" . trim($array_agclis[$i]) . "'>" . trim($array_agclis[$i]) . '</option>';
        }
        //Para documentar Nacex C@mbio debe usarse la misma agencia y cliente que la expedicion padre
        if ($isnacexcambio) {
            $select_agcli = "<option value='" . $datosexpedicion['agcli'] . "'>" . $datosexpedicion['agcli'] . '</option>';
        }

        $str_aviso_modo_f = '';
        $carrier_name = nacexDAO::getCarrierName($datospedido[0]['id_carrier']);

        $modo_f_con_b = false;
        $auxstr = '';
        if (nacexutils::isNacexGenericCarrier($datospedido[0]['id_carrier'])) {
            $modo_f_con_b = true;
            $auxstr = ' genérico ';
        }

        if ($modo_f) {
            $str_aviso_modo_f = '<p>' . $nacex->l('Customer selected service') . ' ' . $auxstr . '<b>' . str_replace('_', ' ', $carrier_name) . '</b> ' . $nacex->l('from frontend') . '</p>';
        }

        $sel_serv = null;
        $def_serv = null;
        $opt_serv = null;
        $array_servicios = null;

        $internacional = false;

        if (isset($shop_codigo)) {
            $sel_serv = Tools::getValue('nacex_tip_nxshop_ser', null);
            $def_serv = Configuration::get('NACEX_DEFAULT_TIP_NXSHOP_SER');
            $opt_serv = explode('|', Configuration::get('NACEX_AVAILABLE_TIP_NXSHOP_SER'));
            $array_servicios = $nacexDTO->getServiciosNacexShop();
        } else {

            $sel_serv = Tools::getValue('nacex_tip_ser', null);
            $nacex_pais = $datospedido[0]['iso_code'];
            if ($nacex_pais == 'ES' || $nacex_pais == 'PT' || $nacex_pais == 'AD' || // envios considerados nacionales
                $nacex_pais == null) { // ojo. añadimos el null como entrega no internacional
                $def_serv = Configuration::get('NACEX_DEFAULT_TIP_SER');
                $opt_serv = explode('|', Configuration::get('NACEX_AVAILABLE_TIP_SER'));
                $array_servicios = $nacexDTO->getServiciosNacex();
            } else {
                $def_serv = Configuration::get('NACEX_DEFAULT_TIP_SER_INT');
                $opt_serv = explode('|', Configuration::get('NACEX_AVAILABLE_TIP_SER_INT'));
                $array_servicios = $nacexDTO->getServiciosNacexInt();
                $internacional = true;
            }
        }

        $carrier_Array = explode('_', $carrier_name);
        $num_serv = isset($carrier_Array[1]) ? $carrier_Array[1] : $def_serv;
        $carrier_id = nacexDAO::getCarrierById($datospedido[0]['id_carrier']);
        $carrier_id = $carrier_id[0]['tip_serv'] ?? $carrier_id;
        if ($carrier_id != $num_serv && !empty($num_serv) && !empty($carrier_id)) { $num_serv = $carrier_id; }

        $select_serv = '';

        if ($isnacexcambio && !$internacional) { // Internacional no admite cambios con el servicio 33
            //Para documentar Nacex C@mbio se obliga a usar el servicio 33
            $select_serv = "<option value='33'>33 - NACEX C@MBIO</option>";
        } else {

            if (isset($sel_serv) && $sel_serv != '') {
                $select_serv = "<optgroup label='" . $nacex->l('Default service') . "'><option selected='selected' value='" . $sel_serv . "'>" .
                    $sel_serv . $nacexDTO->getServSeparador() . $array_servicios[$sel_serv]['nombre'] .
                    '</option></optgroup>';
            } else {
                $select_serv = "<optgroup label='" . $nacex->l('Default service') . "'><option selected='selected' value='" . $def_serv . "'>"
                    . $def_serv . $nacexDTO->getServSeparador() . $array_servicios[$def_serv]['nombre'] .
                    '</option></optgroup>';
            }

            if (isset($opt_serv)) {
                $select_serv = $select_serv . "<optgroup label='" . $nacex->l('Available services') . "'>";
            }

            for ($i = 0; $i < count($opt_serv); $i++) {
                if ($modo_f && !$modo_f_con_b) {
                    $select_serv = $select_serv . '<option ' .
                        nacexutils::markSelectedFrontendOption('nacex_tip_ser', $num_serv, $opt_serv[$i]) .
                        " value='" . $opt_serv[$i] . "'>" .
                        $opt_serv[$i] .
                        $nacexDTO->getServSeparador() .
                        $array_servicios[$opt_serv[$i]]['nombre'] .
                        '</option>';
                } else {
                    $select_serv = $select_serv . '<option ' .
                        nacexutils::markSelectedOption('nacex_tip_ser', 'NACEX_TIP_SER', $opt_serv[$i]) .
                        " value='" . $opt_serv[$i] . "'>" .
                        $opt_serv[$i] .
                        $nacexDTO->getServSeparador() .
                        $array_servicios[$opt_serv[$i]]['nombre'] .
                        '</option>';
                }
            }
            // Si el servicio es genérico
            /*if (!$modo_f_con_b && $modo_f && !in_array($num_serv, $opt_serv)) {
                $select_serv = $select_serv . "<option selected='selected' value='" . $num_serv . "'>" .
                    $num_serv . $nacexDTO->getServSeparador() . @$array_servicios[$num_serv]["nombre"] .
                    "</option>";
            }*/
            if (isset($opt_serv)) {
                $select_serv = $select_serv . '</optgroup>';
            }
        }

        $select_tipseg = '';
        /**
         * TODO:
         * control de seguros en el FrontEnd
         */
        foreach ($nacexDTO->getSeguros() as $seg => $value) {
            $segname = $value['nombre'];
            if ($isnacexcambio) {
                $select_tipseg .= '<option ' . nacexutils::markSelectedFrontendOption('nacex_tip_seg', 'N', $seg) . " value='" . $seg . "'>" . $segname . '</option>';
            } else {
                $select_tipseg .= '<option ' . nacexutils::markSelectedFrontendOption('nacex_tip_seg', Configuration::get('NACEX_DEFAULT_TIP_SEG'), $seg) . " value='" . $seg . "'>" . $segname . '</option>';
            }
        }

        $prealertaval = '';

        //$prealertaplusval = "";
        $prealertaplusval = Tools::getValue('nacex_pre1_plus', Configuration::get('NACEX_PREAL_PLUS_TXT'));

        if (Tools::getValue('nacex_tip_pre1') == 'N') {
            $prealertaval = '';
        } elseif (Tools::getValue('nacex_tip_pre1') == 'S') {
            $prealertaval = $cliente_tlf;
        } elseif (Tools::getValue('nacex_tip_pre1') == 'E') {
            $prealertaval = $cliente_email;
        }

        //CALCULAMOS EL NUMERO DE BULTOS POR DEFECTO
        //Obtenemos más detalles del pedido, como el peso y las referencias
        //$numbultos = 0;
        $numproductos = 0;
        $valor = 0;

        $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference, total_price_tax_incl FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = ' . (int)$id_order);

        foreach ($productospedido as $producto) {
            $numproductos += $producto['product_quantity'];
            $valor += floatval($producto['total_price_tax_incl']);
        }

        $numbultos = Configuration::get('NACEX_BULTOS') == 'F' ?
            Tools::getValue('nacex_bul', Configuration::get('NACEX_BULTOS_NUMERO')) :
            (Configuration::get('NACEX_BULTOS') == 'C' ?
                Tools::getValue('nacex_bul', $numproductos) :
                '1');

        //SELECTOR DEPARTAMENTOS ----------------------------------------------------------------------------
        $departamentos = Configuration::get('NACEX_DEPARTAMENTOS');
        $select_dpt = '';
        if ($departamentos) {
            $array_dept = explode(',', $departamentos);
            //  $deptdef = $array_dept[0];
            foreach ($array_dept as $dpt) {
                $select_dpt .= '<option ' . nacexutils::markSelectedFrontendOption('nacex_departamentos', $array_dept[0], $dpt) . " value='" . $dpt . "'>" . $dpt . '</option>';
            }
        }

        //INSTRUCCIONES ADICIONALES CUSTOMIZADAS----------------------------------------------------------------------------------------
        $instrucciones = '';
        $inst_adi_pers = (Configuration::get('NACEX_INST_PERS') == 'SI');
        $inst_adi_val = Configuration::get('NACEX_CUSTOM_INST_ADI');
        $inst_adi_cantyref = (Configuration::get('NACEX_INS_ADI_Q_R') == 'SI');

        // Cogemos también las observaciones
        $obs_def1 = '';
        $obs_def2 = '';
        $observaciones = Configuration::get('NACEX_CUSTOM_OBS');

        // Cogemos los comentarios que ha hecho el cliente en el pedido
        $mensaje = '';
        $comentario_cli_sino = Configuration::get('NACEX_COMENTARIOS_CLI_SINO') == 'SI';
        $comentario_cli = $nacexDTO::$comentarios_cliente;

        $order = new Order($id_order);
        if ($comentario_cli_sino && $comentario_cli == 'delivery_message') {
            $mensaje = utf8_decode($order->getFirstMessage());
        }   // else implementarlo para módulo específico

        // Rellenamos campo de observaciones con el contenido que le toca
        if ($inst_adi_pers) {
            if ($observaciones != '') {
                $obs_def1 = substr($observaciones, 0, 38);
                $obs_def2 = substr($observaciones, 38, 38);
            } elseif ($comentario_cli_sino) {    // Observaciones vacías y habilitado el coger comentarios de clientes
                $obs_def1 = substr($mensaje, 0, 38);
                $obs_def2 = substr($mensaje, 38, 38);
                $mensaje = substr($mensaje, 76);
            }
        }

        // Revisamos que este habilitado en la configuración el Mostrar Empresa
        $showEmpresa = Configuration::get('NACEX_SHOW_EMPRESA');

        NacexUtils::writeNacexLog('CUSTOMER');
        $id_address = $order->id_address_delivery;
        $address = new Address($id_address);
        $campoEmpresa = $address->company;

        $id_addressInvoice = $order->id_address_invoice;
        $addressInvoice = new Address($id_addressInvoice);
        $campoEmpresaInvoice = $addressInvoice->company;

        $tieneCampoEmpresa = strpos($campoEmpresa, '|');
        if ($tieneCampoEmpresa === false){
            if ($campoEmpresa !== $campoEmpresaInvoice){
                $campoEmpresafinal = pSQL($campoEmpresa . '|' . $campoEmpresaInvoice);
                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'address SET company="' . $campoEmpresafinal . '" WHERE id_address = ' . (int)$id_address);
                $address->company = $campoEmpresa . '|' . $campoEmpresaInvoice;
                $address->update();

            }
            $campoEmpresa = $campoEmpresaInvoice;

        } else {

            $campoEmpresa = $campoEmpresaInvoice;
        }

        if ($showEmpresa == 'SI' && !empty($campoEmpresa)){
            $obs_def1 = substr($campoEmpresa, 0, 38);
            $obs_def2 = substr($campoEmpresa, 38, 38);
            $mensaje = substr($mensaje, 76);
        }

        if ($inst_adi_cantyref) {
            //Obtenemos más detalles del pedido, como el peso y las referencias
            $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = ' . (int)$id_order);
            foreach ($productospedido as $producto) {
                $prodref = str_replace(';', ',', $producto['product_reference']);
                $instrucciones .= ' ** ' . $producto['product_quantity'] . ' # ' . $prodref;
            }
            $instrucciones .= ' ';
        }
        if ($mensaje != '') { $instrucciones .= ' ** ' . $mensaje; }
        if ($instrucciones != '') { $instrucciones .= ' ** ' . $inst_adi_val; } elseif ($inst_adi_val) { $instrucciones .= $inst_adi_val; }

        $texto_instrucciones = Tools::getValue('inst_adi', $instrucciones);

        /* Editar dirección Punto NacexShop */
        $order = new Order((int)$id_order);
        $delivery_details = new Address((int)($order->id_address_delivery));
        $invoice_details = new Address((int)($order->id_address_invoice));
        $matchAddress = $isShop && ($delivery_details->alias == $invoice_details->alias);

        if ($ver177) {
            $html .= '
            <div class="accordion col-md-12 left-column card mt-2 d-print-none card">
                <div class="card-header">
                    <span>
                        <i class="material-icons">local_shipping</i>
                        ' . $nacex->l('Document shipments') . '
                    </span>
				</div>';
        } else {
            $html .= '
            <div class="accordion panel card col-lg-12">
                <span class="panel-heading card-header">
					<i class="icon-truck"></i>
					' . $nacex->l('Document shipments') . '
				</span>';
        }

        $html .= '
				<div align="center" id="ncx_boxinfo' . (isset($datosexpedicion['exp_cod']) ? $datosexpedicion['exp_cod'] : '') . '" class="card-body">					
				  <a target="_blank" title="' . $webtext . '" href="' . $webdir . '" >
				    <img style="margin-bottom:5px; width:130px;height: auto;" src="' . $webimg . '" />
                  </a>
				  <div id="' . $css_id . '">				  
				  <fieldset class="diana">
					<legend style="margin-left:5px;padding-left: 10px;">' . $titulo . '</legend>
							<script>
                                function checkform(){
                                    
                                    if ($( "#nacex_tip_ser" ).val()==""){
                                        alert ("' . $nacex->l('You must select a service') . '");
                                        return false;
                                    } else if($("#cpChangePoint").length > 0 && ( $("#cpChangePoint").val() === "" || $("#cpPointsChoices").val() === "" || $("#cpPointsChoices").val() === undefined )) {
                                        alert ("' . $nacex->l('You must select a NacexShop point') . '");
                                        return false;
                                    } else {
                                        procesando();
                                        return true;
                                    }
                                }          
                            </script>
                                           
					<form action="' . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') . '" method="post" name="generarExpedicion" onsubmit=\'return checkform();\'>';

        //HASH TO AVOID F5.

        $_hash = hash::hash_form($id_order);

        if (isset($shop_codigo)) {
            $html .= '<span id="hidden_shop_codigo" style="float:right;color:#555;margin-top:-20px;">' . $shop_codigo . '</span>';
        }
        $html .= '  <input type="hidden" value="' . $id_order . '" name="order_id" />
                    <input type="hidden" value="' . $_hash . '" name="hash" />
                    <p style="margin-left:5px;">' . $nacex->l('Agency/Customer') . ': 								
                    <select name="nacex_agcli" size="1" style="margin-left:15px;width: 130px;text-align:right;">' . $select_agcli . '</select>
                </p>';

        if ($select_dpt != '') {
            $html .= '<p style="margin-left:5px;width: auto; float: left;">' . $nacex->l('Department') . ': 								
			<select name="nacex_departamentos"  style="margin-left:15px;width: 130px;text-align:right;" >' . $select_dpt . '</select>
		</p>';
        }
        $styles_servicio = 'margin-left:5px; width: auto;';
        $styles_servicio .= $matchAddress ? 'float: left; margin-right: 20px;' : '';
        $html .= $str_aviso_modo_f . '
				            				
							<p style="' . $styles_servicio . '">' . $nacex->l('Service') . ': 														
								<select name="nacex_tip_ser" id="nacex_tip_ser" size="1" style="margin-left:15px;width:auto;text-align:right;">
									' . $select_serv . '
								</select>';

        if ($matchAddress) {
            $html .= '<p style="margin-left:5px;">Cambiar direccion entrega:
                <input type="text" id="cpChangePoint" name="cpChangePoint" size="10" style="width: auto;" placeholder="CP" value="' . $delivery_details->postcode . '" onclick="getPuntosByCp()" onfocusout="getPuntosByCp()" />
                <span id="spinningLoad"></span>
                </p>';
        }

        $html .= '<select name="nacex_frecuencia" id="nacex_frecuencia" size="1" style="display:none;margin-left:15px;width:70%;text-align:right;">
									<option value="1">1 - ' . $nacex->l('Morning freq.') . '</option>
									<option value="2" selected="selected">2 - ' . $nacex->l('Afternoon freq.') . '</option>										
									<option value="8">8 - ' . $nacex->l('Night freq.') . '</option>
								</select>
								<script>
									
									var frecDep=0;
									
									if ( $("#nacex_tip_ser option:selected").val()== "09") { 				            						
				            							$("#nacex_frecuencia").slideToggle();	
				            							frecDep=1;	
												}
				            								
									$(document).on("change","#nacex_tip_ser",function(){ 
									    if ( $("#nacex_tip_ser option:selected").val()== "09") { 
					            			alert("' . $nacex->l('Select the requency for Interdia service.\\n Frecuencies are availables in certain deliver and pickup postcodes depending on shipment request schedule.') . '");
					            			$("#nacex_frecuencia").slideToggle(); frecDep=1;	
										}else {if (frecDep){ $("#nacex_frecuencia").slideToggle(); frecDep=0;} }
									});
					            		';

        if (substr($datospedido[0]['postcode'], 0, 2) != '07') {
            $html .= 'if ( $("#nacex_tip_ser option:selected").val()== "20") { 
					            					alert("' . $nacex->l('Delivery zip code is NOT available in Balearic Islands') . ': ' . $datospedido[0]['postcode'] . '");}';
        }

        $html .= '
                    $(document).ready(function() {
                        $("#nacex_bul").keydown(function (e) {
                            if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
                                (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
                                (e.keyCode >= 35 && e.keyCode <= 40)) {
                                     return;
                            }
                            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                                e.preventDefault();
                            }
                        });
                    });
                    </script>	
								</p>			
								<p
								style="margin-left: 5px;float: left;display: block;">' . $nacex->l('Packages') . ':   
								<input type="number" min="1" id="nacex_bul" name="nacex_bul" value=' . $numbultos . '
								size="2" length="1" maxlength="3" style="margin-left:15px;width: auto;text-align:right;" />
			            		</p>';

        $html .= '<p style="margin-left: 30%;display: block;">' . $nacex->l('Expedition date') . ' 
                <input type="date" id="nacex_fec" name="nacex_fec"
                    min="' . date('Y-m-d') . '" 
                    value=""
                    onkeydown="return false"
                    onblur="blurDateValidation(this.value)"
                    style="margin-left:15px;width: auto;text-align:right;" />
                </p>
                <hr/>';

        if ($internacional) {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Shipment content') . ': ';
            $html .= '<select id="nacex_contenido" name="nacex_contenido" value="' . Tools::getValue('nacex_contenido', '') . '" style="width:94%;; margin-left:15px;" length=1 maxlength="38" onchange="nacexcontenido()">';
            foreach ($nacexDTO->getContenidos() as $cont) {
                $html .= '<option ' . nacexutils::markSelectedOption('nacex_contenido', 'NACEX_DEFAULT_CONTENIDO', $cont) . ' value="' . $cont . '">' . $cont . '</option>';
            }
            $html .= '</select> </p>';

            $html .= '
                     <div align="left" id="tagnacexcontenido" style="visibility: hidden">
                        ' . $nacex->l('Content description') . ':
                     </div>
                     <div id="descripcionnacexcontenido" style="visibility: hidden">
                     
                        <input type="text" id="nacex_descripcion_contenido" name="nacex_descripcion_contenido" value="" style="width:94%; margin-left:15px;" length=1 maxlength="38" />
                
                     </div>
                     ';

            $html .= '<p style="margin-left:5px;">' . $nacex->l('Declared amount') . ':<br>
			<input type="text" id="nacex_impDeclarado" name="nacex_impDeclarado" value="' . $valor . '" style="margin-left:15px;width: 100px;text-align:right;display:inline" length="1" maxlength="10"/> &euro;
                    </p>
                    <hr/>';
        }

        if (!$internacional) {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Charges') . ':
			<input type="radio" name="nacex_tip_cob" value="O" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'O') . '/>O - ' . $nacex->l('Origen') . '  
			<input type="radio" name="nacex_tip_cob" value="D" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'D') . '/>D -  ' . $nacex->l('Destino') . '
			<input type="radio" name="nacex_tip_cob" value="T" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'T') . '/>T - ' . $nacex->l('Tercera') . ' 
                    </p>';
        } else {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Charges') . ':
                        <input type="radio" name="nacex_tip_cob" value="O" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'O') . '/>O - ' . $nacex->l('Origen') . '
			            <input type="radio" name="nacex_tip_cob" value="T" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'T') . '/>T - ' . $nacex->l('Tercera') . '
                    </p>';
        }

        if (!$internacional) {
            $html .= '<hr><p style="margin-left:5px;">' . $nacex->l('Refund') . ':
                                <input type="radio" name="nacex_tip_ree" value="O" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'O') . '/>O - ' . $nacex->l('Origen') . '
                                <input type="radio" name="nacex_tip_ree" value="D" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'D') . '/>D - ' . $nacex->l('Destino') . '
                                <input type="radio" name="nacex_tip_ree" value="T" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'T') . '/>T - ' . $nacex->l('Tercera') . '
                            </p>
                             <p style="margin-left:5px;">' . $nacex->l('Refund amount') . ':';
            if (isset($shop_codigo)) { $html .= '<input type="number" id="nacex_imp_ree" name="nacex_imp_ree" pattern="^\d+(?:\.\d{1,2})?$" step="0.01" required min="0" max="600" value="' . $nacex_imp_ree . '" size="20" maxlength="20" style="margin-left:15px;text-align:right;display:inline"/> &euro;'; } else { $html .= '<input type="number" id="nacex_imp_ree" name="nacex_imp_ree" pattern="^\d+(?:\.\d{1,2})?$" step="0.01" required min="0" max="2500" value="' . $nacex_imp_ree . '" size="20" maxlength="20" style="margin-left:15px;text-align:right;display:inline"/> &euro;'; }

            //            $html .= '<a href="#" onclick="return false;"><img style="opacity:1" src="' . nacexDTO::getPath() . 'images/infoicon.png" width="20px" title="Importe TOTAL a abonar por parte del destinatario. Importe 0 NO tiene reembolso." alt="Importe TOTAL a abonar por parte del destinatario. Importe 0 NO tiene reembolso."/></a>
            //                             </p>';
            $html .= '<span class="info-tooltip" title="' . $nacex->l('Total amount to pay for the receiver. Cost 0 do NOT have refund') . '">?</span>
                     </p>';

            if (isset($shop_codigo)) {
                $html .= '<p class="maxRefundText">' . $nacex->l('Max. refund is') . ' <strong>600€</strong></p>'; }
        } else {
            if (intval($nacex_imp_ree > 0)) {
                $html .= '<p style="margin-left:5px;">' . $nacex->l('Refund') . ': ' . $nacex->l('In International shipments is NOT available management RETURN shipments') . '</p>';
            }
        }

        if (!$internacional) {
            $html .= '<hr><p style="margin-left:5px;">' . $nacex->l('Shipment') . ':
								            			<input type="radio" name="nacex_tip_env" value="0" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '0') . '/>0 - DOCS
								            			<input type="radio" name="nacex_tip_env" value="1" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '1') . '/>1 - BAG
														<input type="radio" name="nacex_tip_env" value="2" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '2') . '/>2 - PAQ																														
								            </p>';
        } else {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Shipment') . ':
								            			<input type="radio" name="nacex_tip_env" value="M" checked="checked" ' . nacexutils::markCheckedOption('nacex_tip_env_int', 'NACEX_TIP_ENV_INT', 'M') . '/> M - MUESTRA
								            			<input type="radio" name="nacex_tip_env" value="D" ' . nacexutils::markCheckedOption('nacex_tip_env_int', 'NACEX_TIP_ENV_INT', 'D') . '/> D - DOCUMENTO														
								            </p>';
        }

        if (!$internacional) { //Internacional no admite retornos
            if ($isnacexcambio) {
                $html .= '<p style="margin-left:5px;">' . $nacex->l('Return') . ':
									            	<input type="radio" name="nacex_ret" value="NO" disabled="true"/>' . $nacex->l('No') . '
													<input type="radio" name="nacex_ret" value="SI" checked="checked"/>' . $nacex->l('Yes') . ' 
									            </p>';
            } else {
                $html .= '<p style="margin-left:5px;">' . $nacex->l('Return') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									            	<input type="radio" name="nacex_ret" value="NO" ' . nacexutils::markCheckedOption('nacex_ret', 'NACEX_RET', 'NO') . '/>' . $nacex->l('No') . '
													<input type="radio" name="nacex_ret" value="SI" ' . nacexutils::markCheckedOption('nacex_ret', 'NACEX_RET', 'SI') . '/>' . $nacex->l('Yes') . '  
									            </p>';
            }
        }
        //seguro
        $html .= '<hr/>
						   			 <p style="margin-left:5px;">' . $nacex->l('Insurance') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						   						<select name="nacex_tip_seg" size="1" onchange="checkTipoSeguro(this.value);" style="width:200px; margin-left:15px;">
													' . $select_tipseg . '
												</select>
								      </p> 
								      <p style="margin-left:5px;">' . $nacex->l('Insured amount') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								      </p>
								      <p style="margin-left:5px;">				
						   					<input type="number" min="0" id="nacex_imp_seg" name="nacex_imp_seg" value="' . $nacex_impseg . '"  length=1 maxlength="35" ' . ($nacex_impseg == '' ? 'disabled="disabled"' : '') . ' style="margin-left:15px;width: 100px;text-align:right;display:inline"/> &euro; 
								      </p>';

        $html .= '<hr/>
                <p style="margin-left:5px;">' . $nacex->l('Prealert') . ':<br>';
        if (!$isShop) {
            $html .= '<input type="radio" name="nacex_tip_pre1" value="N" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'N') . ' onclick="setprealerta(&quot;N&quot;);" checked="checked" />N - No&nbsp;&nbsp;
                      <input type="radio" name="nacex_tip_pre1" value="S" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'S') . ' onclick="setprealerta(&quot;S&quot;);" />S - SMS&nbsp;&nbsp;
                      <input type="radio" name="nacex_tip_pre1" value="E" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'E') . ' onclick="setprealerta(&quot;E&quot;);" />E - Email';
        } else {
            $html .= '<input type="radio" name="nacex_tip_pre1" value="S" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'S') . ' onclick="setprealerta(&quot;S&quot;);" />S - SMS&nbsp;&nbsp;
                      <input type="radio" name="nacex_tip_pre1" value="E" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'E') . ' onclick="setprealerta(&quot;E&quot;);"/>E - Email';
        }
        $html .= '<br>';
        $html .= '<input style="margin-left:15px;display:inline;text-align:center;width:94%;" type="text" id="nacex_pre1" name="nacex_pre1" value="' . $prealertaval . '" length="1" maxlength="50" readonly /></p>';

        $html .= '<p style="margin-left:5px;">' . $nacex->l('Prealert mode') . ':<br>
									<input type="radio" name="nacex_mod_pre1" value="S" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'S') . ' onclick="setprealertaplus(&quot;S&quot;);"  />S - Standard';
        if (!isset($shop_codigo)) {
            $html .= '<input type="radio" name="nacex_mod_pre1" value="P" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'P') . ' onclick="setprealertaplus(&quot;P&quot;);"  />P - Plus';
            $html .= '<input type="radio" name="nacex_mod_pre1" value="R" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'R') . ' onclick="setprealertaplus(&quot;R&quot;);"  />R - Reparto';
            $html .= '<input type="radio" name="nacex_mod_pre1" value="E" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'E') . ' onclick="setprealertaplus(&quot;E&quot;);"  />E - Reparto Plus';
            $html .= '<br> <input style="margin-left:15px;display:inline;text-align:center;width:94%;" type="text" id="nacex_pre1_plus" name="nacex_pre1_plus" value="' . $prealertaplusval . '"  length="1" maxlength="719" disabled="true"/>';
        }
        $html .= '</p>
				            
				            <hr style="margin-top:2px" />';
        if (isset($shop_codigo)) {
            $html .= '
								<p style="margin-left:5px;">' . $nacex->l('For the attention of') . ': 
								<input type="text" id="nacex_att" name="nacex_per_ent" value="' . $att . '" style="width:94%;; margin-left:15px;" length=1 maxlength="38" length=1 maxlength="35"/></p>';
        }
        $html .= '<p style="margin-left:5px;">
					            ' . $nacex->l('Delivery observations') . ' (1)
					            <input type="text" id="nacex_obs1" name="nacex_obs1" value="' . $obs_def1 . '" style="width:94%;; margin-left:15px;" length=1 maxlength="38" length=1 maxlength="38" />
					            ' . $nacex->l('Delivery observations') . ' (2)
					            <input type="text" id="nacex_obs2" name="nacex_obs2" value="' . $obs_def2 . '" style="width:94%;; margin-left:15px;" length=1 maxlength="38" length=1 maxlength="38" />
				            </p>';

        $html .= '<hr style="margin-top:2px" />
                    <p style="margin-left:5px;">' . $nacex->l('Additional shipping instructions') . ':</p> 
                    <textarea style="width:94%;; margin-left:15px;height:75px;" length=1 maxlength="600" name="inst_adi">' . $texto_instrucciones . '</textarea>';

        $messJs = $nacex->l('Max. refund is');

        $html .= '<div>';

        /** Comprobamos el servicio 44 **/
        $is44Active = Configuration::get('NACEX_SERVICIO44') == 'SI';

        if ($is44Active) {
            //$html .= self::printDevolucionLabel($datosexpedicion);
            $html .= '<input type="submit" class="button"
                       id="printDevolucion"
                       name="printDevolucion"
                       title="' . $nacex->l('Print refund and expedition labels') . '"
                       value="' . $nacex->l('Print refund and expedition labels') . '" 
                       onclick="descripcioncontenido()" />';
        } else {
            $html .= '<input type="submit"  name="submitputexpedicion" value="' . $nacex->l('Generate expedition') . '" alt="' . $nacex->l('Generate expedition') . '" title="' . $nacex->l('Generate expedition') . '" class="button" onclick="descripcioncontenido()" />';
        }

        $html .= '</div>
		</form>	
		</fieldset>
		</div>
		</div>
        </div> <!-- accordion panel -->
		<br/>';

        $tpre1 = Tools::getValue('nacex_tip_pre1', Configuration::get('NACEX_TIP_PREAL'));
        $tip_pre1 = empty($tpre1) ? 'N' : $tpre1;

        $html .= '<script type="text/javascript">     

                        function checkTipoPrealerta(tipo){
                            var obj = document.getElementsByName("nacex_tip_pre1");
                            for(i=0; i<obj.length; i++){
                                if(obj[i].value===tipo){
                                    obj[i].checked = true;
                                    setprealerta(tipo);
                                    break;
                                }
                            }	
                		}
		
                		function checkModoPrealerta(modo){
                            var obj = document.getElementsByName("nacex_mod_pre1");
                            for(i=0; i<obj.length; i++){
                                if(obj[i].value===modo){
                                obj[i].checked = true;
                                setprealertaplus(modo);
                                }
                            }	
                		}
					
                		function checkTipoSeguro(tipo){
                		  if (tipo != "N"){
                        		document.getElementById("nacex_imp_seg").disabled = "";
                        		document.getElementById("nacex_imp_seg").value = "' . $nacex_impseg . '";
                    		}else{
                        		document.getElementById("nacex_imp_seg").disabled = "disabled";
                        		document.getElementById("nacex_imp_seg").value = "";
                    		}	
                		}
                		function nacexcontenido(){
                		
                		    if ( (document.getElementById("nacex_contenido").value) === "OTROS"){
                		    
                		        document.getElementById("tagnacexcontenido").style.visibility = "visible";
                		        document.getElementById("descripcionnacexcontenido").style.visibility = "visible";
                		        document.getElementById("nacex_descripcion_contenido").focus();
                		        
                		    }else{
                		    
                		        document.getElementById("tagnacexcontenido").style.visibility = "hidden";
                		        document.getElementById("descripcionnacexcontenido").style.visibility = "hidden";
                		        document.getElementById("nacex_contenido").focus();
                		    
                		    }
                		
                		}
                		
                		function descripcioncontenido(){
                            
                            var x = document.getElementById("nacex_descripcion_contenido").value;
                            x = x.trim();
                            
                		    if ( (document.getElementById("nacex_contenido").value) == "OTROS" && (x == "") ){
                		        alert("' . $nacex->l('The Content description field is required') . '");
                		        setTimeout(function(){document.getElementById("nacex_descripcion_contenido").focus();}, 2);
                		        document.getElementById("nacex_descripcion_contenido").value ="";
                		    }else{
                		        document.getElementById("nacex_descripcion_contenido").value = x;
                		    }
                		}
                		
                 //Antes estaba en un if(!isset($shop_codigo))
                 checkTipoPrealerta  ("' . $tip_pre1 . '");
                 checkModoPrealerta  ("' . Tools::getValue('nacex_mod_pre1', Configuration::get('NACEX_MOD_PREAL')) . '");
                 checkTipoSeguro     ("' . Tools::getValue('nacex_tip_seg', Configuration::get('NACEX_DEFAULT_TIP_SEG')) . '");
                 window.onload = function (){
                                     if( typeof nacex_contenido != "undefined" ){
                                            nacexcontenido();
                                     }
                                }
                </script>';
        return $html;
    }

    public static function showExpedicionBoxInfo($datosexpedicion, $id_pedido, $solicitud, $ver177 = false)
    {
        $html = '';

        require_once(dirname(__FILE__) . '/ROnacexshop.php');
        $_nacex_shop = new nacexshop();
        $nacex = new nacex();

        $urlPrint = Configuration::get('NACEX_PRINT_URL');
        $modelPrint = Configuration::get('NACEX_PRINT_MODEL');
        $etPrint = Configuration::get('NACEX_PRINT_ET');
        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');
        $parametros = 'user=' . $nacexWSusername . '&pass=' . $nacexWSpassword . '&model=' . $modelPrint . '&et=' . $etPrint . '&ref=' . nacexutils::getReferenciaGeneral() . $id_pedido . '&expe_codigo=' . $datosexpedicion['exp_cod'];
        $urlPrint = $urlPrint . '?' . $parametros;

        $mapacambios = nacexutils::getMapCambios($datosexpedicion['cambios']);
        $html_tip_ser_cambiado = '';
        if (isset($mapacambios['tip_ser'])) {
            $html_tip_ser_cambiado = $nacex->l('Service type was changed to') . ' ' . $mapacambios['tip_ser'] . ' ' . $nacex->l('by Web Service to be able to document the expedition');
        }

        $fecha_alta = date('H:i:s, d/m/Y');
        if (isset($datosexpedicion['fecha_alta'])) {
            $fecha_alta = $datosexpedicion['fecha_alta'];
        }

        if (!isset($datosexpedicion['ret'])) {
            $datosexpedicion['ret'] = 'NO'; }

        $leyenda_agencia_entrega = $nacex->l('Delivery agency');
        $agencia_entrega_codigo = $datosexpedicion['ent_cod'];
        $agencia_entrega_nombre = $datosexpedicion['ent_nom'];
        // Antes estaba rellena con el teléfono del cliente porque no querían que se mostraran los teléfonos de la agencia
        $leyenda_agencia_entrega_tlf_or_addres = '';
        $agencia_entrega_tlf_or_addres = '';
        $a_visibilidad_agencia_entrega = ' style="display:block;" ';
        $fondo_nacexshop = '';
        $att = '';

        $webtext = $nacex->l('Go to Nacex web');
        $webdir = 'https://www.nacex.es';
        $webimg = _MODULE_DIR_ . 'nacex/images/logos/nacex_logista.png';

        // ¿Es NacexShop?
        $isShop = isset($datosexpedicion['shop_codigo']) && $datosexpedicion['shop_codigo'] != 1 && $datosexpedicion['shop_codigo'] != '';

        $agenciasInfo = $_nacex_shop->importFromCsvFile($datosexpedicion['shop_codigo'], $isShop);
        if ($agenciasInfo) {
            $agenciasInfo = explode('|', $agenciasInfo[0]);
        }

        if ($isShop) {
            $iso_code = Language::getIsoById(nacexutils::getCurrentLang());

            $leyenda_agencia_entrega = $nacex->l('Delivery point');
            $agencia_entrega_codigo = @$datosexpedicion['shop_alias'];
            $agencia_entrega_nombre = $agenciasInfo[2];
            $leyenda_agencia_entrega_tlf_or_addres = 'dirección';
            $agencia_entrega_tlf_or_addres = @$datosexpedicion['shop_direccion'];
            $a_visibilidad_agencia_entrega = ' style="display:none;" ';
            $fondo_nacexshop = ' class="bg_nacexshop" ';

            $webtext = $nacex->l('Go to NacexShop web');
            $webdir = 'https://www.nacexshop.com';
            $webimg = _MODULE_DIR_ . "nacex/images/logos/NACEXshop_sostenible_$iso_code.svg";

            $array_address_invoice = nacexDAO::getAddressInvoiceByOrder($id_pedido);
            $att = '<p><i><b>' . $nacex->l('Attn') . ':</b> ' . $array_address_invoice[0]['firstname'] . ' ' . $array_address_invoice[0]['lastname'] . '</i></p>';
        }

        $respuestaGetEstadoExpedicion = null;
        $estadosComprobacion = ['INCIDENCIA EXPEDICION', 'INCIDENCIA', 'TRANSITO', 'REPARTO', 'PENDIENTE', 'PENDIENTE DE INTEGRA', ''];

        if (in_array($datosexpedicion['estado'], $estadosComprobacion)) {
            // Cache WS: solo llamar si la ultima actualizacion fue hace mas de 5 minutos
            $wsCache = false;
            if (!empty($datosexpedicion['fecha_estado'])) {
                $lastUpdate = strtotime($datosexpedicion['fecha_estado']);
                $wsCache = $lastUpdate !== false && (time() - $lastUpdate) < 300;
            }

            if ($wsCache && !Tools::isSubmit('submitputexpedicion')) {
                // Usar datos de BD como cache
                $respuestaGetEstadoExpedicion = $datosexpedicion;
                $getDatosWSExpedicion = null;
                nacexutils::writeNacexLog('showExpedicionBoxInfo :: usando cache BD para exp ' . $datosexpedicion['exp_cod'] . ' (actualizado hace ' . (time() - $lastUpdate) . 's)');
            } else {
                // Llamar al WS y actualizar cache en BD
                $respuestaGetEstadoExpedicion = nacexWS::ws_getEstadoExpedicion($datosexpedicion);
                if (is_array($respuestaGetEstadoExpedicion) && isset($respuestaGetEstadoExpedicion['estado'])) {
                    nacexDAO::actDatosNacexExpediciones($datosexpedicion['id_envio_order'], $datosexpedicion, $respuestaGetEstadoExpedicion['estado'], $respuestaGetEstadoExpedicion['fecha'], $respuestaGetEstadoExpedicion['hora']);
                }
                $getDatosWSExpedicion = nacexWS::ws_getDatosWSExpedicion($datosexpedicion['exp_cod']);
            }
        } else {
            $respuestaGetEstadoExpedicion = $datosexpedicion;
            $getDatosWSExpedicion = null;
        }
        $estado_exp = null;

        $canPrint = nacexWS::checkGetEtiqueta($modelPrint, $datosexpedicion['exp_cod']);

        try {
            $gestionAgencia = !(is_null($respuestaGetEstadoExpedicion) && empty($respuestaGetEstadoExpedicion) && is_null($getDatosWSExpedicion)) ?
                $solicitud != 1 && (
                    $solicitud == 3 || (isset($respuestaGetEstadoExpedicion['estado_code']) &&
                        !in_array($respuestaGetEstadoExpedicion['estado_code'], ['16', '1', '2', '3', '4'])) ||
                    $respuestaGetEstadoExpedicion['estado'] == 'ANULADA' || $respuestaGetEstadoExpedicion['estado'] == 'BAJA' ||
                    (isset($getDatosWSExpedicion[2]) && @$getDatosWSExpedicion[2] == '5611') || !$canPrint
                ) :
                false;
        } catch (Exception $e) { // no existe estado previo
            $gestionAgencia = false;
        }

        $html_fieldset_body_estado = '';

        if (!is_array($respuestaGetEstadoExpedicion)) {
            $html_fieldset_body_estado = $respuestaGetEstadoExpedicion;
        } elseif (!isset($respuestaGetEstadoExpedicion)) {
            $html_fieldset_body_estado = '<p align="center"><b><i>' . $nacex->l('No data') . '</i></b></p>';
        } elseif (isset($respuestaGetEstadoExpedicion[0]) && $respuestaGetEstadoExpedicion[0] == 'ERROR') {

            if ($solicitud == 1 || (isset($respuestaGetEstadoExpedicion[2]) && $respuestaGetEstadoExpedicion[2] == '5611')) {
                $html_fieldset_body_estado = '<p align="center"><b><i>' . $nacex->l('Pending integration') . '</i></b></p>';
                $estado_exp = 'PENDIENTE';
            } else { $html_fieldset_body_estado = '<div style="width:auto" class="alert error"><p>' . $respuestaGetEstadoExpedicion[0] . ' ' . $respuestaGetEstadoExpedicion[2] . '</p></div>'; }
        } else {
            $URLSHOWEXPEDICION = 'https://www.nacex.es/seguimientoDetalle.do?estado&internacional&externo=1';
            $agencia_albaran = $datosexpedicion['ag_cod_num_exp'];
            $array = explode('/', $agencia_albaran);
            $agencia = $array[0];
            $albaran = $array[1];

            $shLink = $respuestaGetEstadoExpedicion['estado'] != 'ANULADA' ? '<a id="historico_' . $datosexpedicion['exp_cod'] . '" class="ncx_fieldset_icon zoomable" style="cursor:pointer" onclick="$(ncx_seg_his_' . $datosexpedicion['exp_cod'] . ').slideToggle();">' . $nacex->l('Expedition history') . '</a>' : '';
            $iframe = $respuestaGetEstadoExpedicion['estado'] != 'ANULADA' ? '<iframe style="border-radius:5px;display:none" id="ncx_seg_his_' . $datosexpedicion['exp_cod'] . '" width="100%" height="800" frameborder="0" src="' . $URLSHOWEXPEDICION . '&agencia_origen=' . $agencia . '&numero_albaran=' . $albaran . '"></iframe>' : '';

            $estado_exp = isset($respuestaGetEstadoExpedicion['estado']) ? $respuestaGetEstadoExpedicion['estado'] : '';
            $fecha_estado_exp = isset($respuestaGetEstadoExpedicion['fecha']) ? $respuestaGetEstadoExpedicion['fecha'] : '';
            $hora_estado_exp = isset($respuestaGetEstadoExpedicion['hora']) ? $respuestaGetEstadoExpedicion['hora'] : '';
            $obs_estado_exp = isset($respuestaGetEstadoExpedicion['observaciones']) ? $respuestaGetEstadoExpedicion['observaciones'] : '';

            $html_fieldset_body_estado = $shLink . '
						<p><b>' . $nacex->l('Date') . ':</b> ' . $fecha_estado_exp . '</p>		
			            ' . $iframe . '
						<p><b>' . $nacex->l('Hour') . '::</b> ' . $hora_estado_exp . '</p>					
						<p><b>' . $nacex->l('Obs.') . ':</b> ' . $obs_estado_exp . '</p>
						<p><b>' . $nacex->l('Status') . ':</b> ' . $estado_exp . '</p>
				  	';
        }

        $html_fieldset_exp_rel = '';
        $array_exp_rel = nacexDAO::getExpRelacionadas($id_pedido, $datosexpedicion['exp_cod'] ?? null);
        if (!empty($array_exp_rel)) {

            $html_fieldset_exp_rel .= '<fieldset><legend>' . $nacex->l('Expedition history') . "</legend>
                <div style='position:relative;overflow:auto;width:94%;'>
                    <table width='100%' border='0'>
                    <tr>
                        <td nowrap='nowrap' style='font-size:8pt'><b>" . $nacex->l('Agency/Delivery note num.') . "</b></td>
                        <td nowrap='nowrap' style='font-size:8pt'><b>" . $nacex->l('Reference') . "</b></td>
                        <td nowrap='nowrap' style='font-size:8pt'><b>" . $nacex->l('Date') . "</b></td>
                        <td nowrap='nowrap' style='font-size:8pt'><b>" . $nacex->l('Status') . "</b></td>
                        <td nowrap='nowrap' style='font-size:8pt'><b>" . $nacex->l('Status date') . '</b></td>
                    </tr>';

            $i = 0;
            foreach ($array_exp_rel as $exp_rel) {
                $html_fieldset_exp_rel .= $i % 2 == 0 ? "<tr bgcolor='#F7F7F7'>" : "<tr bgcolor='#EEEEEE'>";
                $html_fieldset_exp_rel .= "<td nowrap='nowrap'><font style='font-size:8pt'>" . $exp_rel['ag_cod_num_exp'] . "</td>
                                               <td nowrap='nowrap' style='font-size:8pt'>" . $exp_rel['ref'] . "</td>
                                               <td nowrap='nowrap' style='font-size:8pt'>" . $exp_rel['fecha_alta'] . "</td>
                                               <td nowrap='nowrap' style='font-size:8pt'>" . $exp_rel['estado'] . "</td>
                                               <td nowrap='nowrap' style='font-size:8pt'>" . $exp_rel['fecha_estado'] . '</td>
                                            </tr>';
                $i++;
            }

            $html_fieldset_exp_rel .= '</table>
                                       </div>
                                       </fieldset>';
        }
        // Con esta función actualizamos el campo 'serv_cod' de la tabla nacex_expediciones (para exp creadas con versiones anteriores a la 1.4.9)
        //nacexDAO::actDatosNacexExpediciones($id_pedido, $getDatosWSExpedicion, $estado_exp, $fecha_estado_exp, $hora_estado_exp);

        if (is_null($estado_exp)) { $truckColor = 'orange'; } elseif ($estado_exp == 'ANULADA' || $estado_exp == 'BAJA') { $truckColor = 'red'; } elseif ($estado_exp == 'INCIDENCIA') { $truckColor = 'goldenrod'; } elseif ($estado_exp == 'OK') { $truckColor = 'green'; } else { $truckColor = 'orange'; }

        //$truckColor = is_null($estado_exp)?"orange": $estado_exp=="ANULADA"?"red":$estado_exp=="INCIDENCIA"?"goldenrod": $estado_exp= "OK"?"green": "orange";
        $acc_active = Tools::getValue('expe_codigo') == $datosexpedicion['exp_cod'] ? 'display: block;' : 'display: none;';

        $nxcShop = new nacexshop();
        $codigo = $isShop ? $datosexpedicion['shop_codigo'] : $datosexpedicion['ent_cod'];
        $shop = $nxcShop->getShopByCode($codigo, $isShop)[0];
        $iframe = self::printIframe($shop[0], $datosexpedicion['exp_cod']);

        if ($ver177) {
            $html .= '
            <div class="accordion col-md-12 left-column card mt-2 d-print-none card">
                <div class="card-header pointer" onClick="$(ncx_boxinfo' . $datosexpedicion['exp_cod'] . ').slideToggle();">
                    <span>
                        <i class="material-icons ' . $truckColor . '">local_shipping</i>
                        NACEX  ' . $datosexpedicion['ag_cod_num_exp'] . '
                    </span>
				</div>';
        } else {
            $html .= '
            <div class="accordion panel col-lg-12">
                <span onClick="$(ncx_boxinfo' . $datosexpedicion['exp_cod'] . ').slideToggle();" class="panel-heading">
					<i class="icon-truck ' . $truckColor . '"></i>
					NACEX  ' . $datosexpedicion['ag_cod_num_exp'] . '
				</span>';
        }

        $html .= '
          <div align="center" id="ncx_boxinfo' . $datosexpedicion['exp_cod'] . '" style="margin-left: 20px;' . $acc_active . '">
              <a target="_blank" title="' . $webtext . '" href="' . $webdir . '" ><img style="margin-bottom:5px; width:130px;height: auto;" src="' . $webimg . '" /></a>';

        $cssShop = $isShop ? ' shop' : '';

        $html .= ' 
			  <div id="ncx_info_exp" class="ncx_box' . $cssShop . '">
				<fieldset>					
					<legend>' . $nacex->l('Expedition') . '</legend>	
			  		<div id="ncx_cod_exp" style="position: relative; right: 18px;">' . $datosexpedicion['exp_cod'] . '</div>';
        if (isset($getDatosWSExpedicion['ref'])) {
            $html .= '<p><b>' . $nacex->l('Reference') . ':</b> ' . @$getDatosWSExpedicion['ref'] . '</p>'; } else { $html .= '<p><b>' . $nacex->l('Reference') . ':</b> ' . $getDatosWSExpedicion . '</p>'; }

        $html .= '<p><b>' . $nacex->l('Agency/Number') . ':</b> ' . $datosexpedicion['ag_cod_num_exp'] . '</p>
			  		<p><b>' . $nacex->l('Register expedition') . ':</b> ' . $fecha_alta . '</p>
		 	  	</fieldset>

			  	<fieldset>
			  		<legend>' . $nacex->l('Service') . '</legend>
					<sub> ' . $html_tip_ser_cambiado . '</sub>
					<p><b>' . $nacex->l('Service') . ':</b> ' . $datosexpedicion['serv'] . ' <sub>(' . $datosexpedicion['hora_entrega'] . ')</sub></p>
					<p><b>' . $nacex->l('Delivery target date') . ':</b> ' . $datosexpedicion['fecha_objetivo'] . '</p>
					<p><b>' . $nacex->l('Return') . ':</b> ' . $datosexpedicion['ret'] . '</p>
			  	</fieldset>';

        if ($datosexpedicion['serv_cod'] != 'H' && $datosexpedicion['serv_cod'] != 'G') {
            $html .= '<fieldset ' . $fondo_nacexshop . '>
			  		<legend>' . $leyenda_agencia_entrega . '</legend>
					<a id="agencia_' . $datosexpedicion['exp_cod'] . '"
					   style="cursor: pointer;"
					   class="ncx_fieldset_icon zoomable" ' . $a_visibilidad_agencia_entrega . ' title="' . $nacex->l('See agency details') . '"
					   onClick="$(ncx_det_age_' . $datosexpedicion['exp_cod'] . ').slideToggle();">
					   	<!-- <img title="' . $nacex->l('See agency details') . '" alt="' . $nacex->l('See agency details') . '" src="' . nacexDTO::getPath() . 'images/lupa.png" style="position: relative; right: 18px;top: 5px;"/> -->
					   	<div id="magnifying-glass"></div>
					</a>
					' . $iframe;
        }

        $agenciaEntrega = ($agencia_entrega_tlf_or_addres != '') ? '<p><b>' . $leyenda_agencia_entrega_tlf_or_addres . ':</b> ' . $agencia_entrega_tlf_or_addres . '</p>' : '';

        $html .= '<p><b>' . $nacex->l('Code') . ' - ' . $nacex->l('Name') . ':</b> ' . $agencia_entrega_codigo . ' - ' . $agencia_entrega_nombre . '</p>
					' . $agenciaEntrega . '					
					' . $att . '					
			  	</fieldset>	

			  	<fieldset>
			  		<legend>' . $nacex->l('Status') . '</legend>' . $html_fieldset_body_estado . '
			  	</fieldset>

			  	' . $html_fieldset_exp_rel . ' <br>
			  	
			  	<div class="additional-actions">';

        // No se puede modificar la expedicion (no mostrar si ya está anulada/baja)
        $isAnulada = isset($estado_exp) && in_array($estado_exp, ['ANULADA', 'BAJA']);
        if ($gestionAgencia && !$canPrint && !$isAnulada) {
            $html .= "<div id='messages-nacex' class='bootstrap' style='margin-top:10px'>
            <div class='alert alert-info conf' style='width:auto'>
                " . $nacex->l("It's not possible to modify the expedition") . '
            </div></div>';
        }

        $expId = htmlspecialchars($datosexpedicion['exp_cod'], ENT_QUOTES, 'UTF-8');
        $expIdJs = preg_replace('/[^a-zA-Z0-9_]/', '_', $datosexpedicion['exp_cod']);

        $html .= '<script>';
        // print - funcion unica por expedicion para soportar multiples expediciones
        if (!$gestionAgencia) {
            $html .= 'function printiframe_' . $expIdJs . '(id,exp_cod){
                            var array_exp = exp_cod==null ? ["' . $datosexpedicion['id_envio_order'] . '","' . $datosexpedicion['exp_cod'] . '"]: [id,exp_cod];
                            ionaPrint( array_exp);
                        }';
        }

        $html .= ' if (typeof confirmar !== "function") { function confirmar(msg){ return confirm(msg); } }
    		</script>';

        $html .= '<form action="' . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') . '" method="post">';
        $html .= '<input type="hidden" name="exp_cod" value="' . $expId . '" />';

        // cancel + edit — disponible cuando la expedición está notificada/pendiente
        $estadosCancelables = ['PENDIENTE', 'PENDIENTE DE INTEGRA', 'NOTIFICADO'];
        if (isset($estado_exp) && (in_array($estado_exp, $estadosCancelables) || strpos($estado_exp, 'PENDIENTE') === 0)) {
            $html .= '<button type="submit" name="submitcancelexpedicion" value="1" class="zoomable" style="background:none;border:none;cursor:pointer;vertical-align:top;padding:0;" onclick="return confirmar(\'' . addslashes($nacex->l('Do you want to cancel this expedition?')) . '\')">
                       <img width="38px"
                       alt="' . $nacex->l('Cancel expedition') . '"
                       title="' . $nacex->l('Cancel expedition') . '"
                       src="' . nacexDTO::getPath() . 'images/Ic_cancel_48px.svg" /></button>';
            $html .= '&nbsp;&nbsp;<button type="submit" name="submiteditexpedicion" value="1" class="btn btn-default btn-sm" style="vertical-align:top;">
                       <i class="material-icons" style="font-size:14px;vertical-align:middle;">edit</i> ' . $nacex->l('Edit expedition') . '</button>';
        }

        // cambio
        $servicio = isset($getDatosWSExpedicion['serv_cod']) ? $getDatosWSExpedicion['serv_cod'] : $respuestaGetEstadoExpedicion['serv_cod'];
        if (($servicio == '27' || $servicio == '33') && $estado_exp == 'OK') {
            $tituloCambio = $nacex->l('Generate') . " Nacex C@mbio&nbsp;<span  style='padding-left: 10px;'><b>Pedido" . @$getDatosWSExpedicion['ref'] . '</b></span>';
            $html .= "&nbsp;&nbsp;&nbsp;&nbsp;
            <img style='opacity:0.8;cursor:pointer;' class='zoomable' id='ncxcambio' alt='Nacex C@mbio' title='Nacex C@mbio'
                   src='" . nacexDTO::getPath() . "images/servicios/svg/nacex_servicio_13_negro.svg'
                   width=\"37px\"
                   onclick='ncxCambio();'/>
                       
            <script>
            function ncxCambio(){
                if ( confirm('" . $nacex->l('It will be proceeded to documentation of a Nacex C@mbio expedition.\\nDo you want to continue?') . "')){
                       
                     $('<input>').attr({ type: 'hidden', id: 'submitcambioexpedicion', name: 'submitcambioexpedicion', value: '987987987' }).appendTo('form');
                     $( \"[id='tituloGenerarExpedicion']\" ).replaceWith( \"" . $tituloCambio . "\" );
                         
                         
                     $(\"[name='nacex_agcli']\").val ('" . $datosexpedicion['agcli'] . "');
                     $(\"[name='nacex_agcli']\").prop('readonly', true);
                         
                     $(\"#nacex_tip_ser\").append('<option value=33>NACEX DEV. C@MBIO</option>');
                     $(\"[name='nacex_tip_ser']\").val ('33');
                     $(\"[name='nacex_tip_ser']\").prop('readonly', true);
                         
                     $(\"[id='nacex_bul']\").val ('1');
                         
                     $(\"[name='nacex_tip_cob'][value='O']\").prop('checked', true);
                     $(\"[name='nacex_tip_cob']\").prop('readonly', true);
                         
                     $(\"[name='nacex_tip_env'][value='2']\").prop('checked', true);
                     $(\"[name='nacex_tip_env']\").prop('readonly', true);
                         
                     $(\"[name='nacex_ret'][value='SI']\").prop('checked', true);
                     $(\"[name='nacex_ret']\").prop('readonly', true);
                         
                     $(\"[name='nacex_tip_seg']\").prop('readonly', true);
                     $(\"[name='nacex_imp_seg']\").prop('readonly', true);
                         
                     $(\"[id='nacex_bul']\").focus();
                         
                  /*
                     $(\"[name='nacex_tip_pre1']\");
                     $(\"[name='nacex_pre1']\");
                     $(\"[name='nacex_mod_pre1']\");
                     $(\"[name='nacex_pre1_plus']\");
                     $(\"[name='inst_adi']\");
                     $(\"[name='nacex_obs1']\");
                     $(\"[name='nacex_obs2']\");
                    */
                }
                return false;
            }
            </script>
            ";
        }

        $html .= '</form>';

        /** Comprobamos el servicio 44 **/
        $filePath = _PS_BASE_URL_ . _MODULE_DIR_ . 'nacex/files/';

        // Preparamos el cambio de estado. A la que pulses sobre el botón de imprimir, se cambiará el estado de la expedición.
        $nacexDTO = new NacexDTO();
        $controller = $nacexDTO->getPath() . 'CambioEstadoPedido.php';

        $html .= "<script type='text/javascript'>
                function downloadPDF_" . $expIdJs . "(folder) {

                    var exp = '" . $datosexpedicion['exp_cod'] . "';
                    var filePath = '" . $filePath . "' + folder + '/';

                    window.open(filePath + exp + '.pdf','_blank');

                    if(folder === 'etiquetas'){
                        $.ajax({
                            url : '" . $controller . "',
                            method : 'POST',
                            data : {
                                ajax : 1,
                                controller : 'AdminNacexCambioEstado',
                                action : 'cambioEstado',
                                id_order : '" . $datosexpedicion['id_envio_order'] . "'
                            },
                            async: false,
                            success : function (result) {
                                alert('" . $nacex->l('Order status has been changed') . "');
                            }
                        });
                    }
                }
            </script>";

        if (nacexutils::existFileLabelPDF('etiquetas', $datosexpedicion['exp_cod'])) {

            $html .= '<a onClick="downloadPDF_' . $expIdJs . '(\'etiquetas\')" style="cursor:pointer;float:right;margin-right:0.2em;" title="' . addslashes($nacex->l('Print label')) . '"><img class="zoomable" src="' . nacexDTO::getPath() . 'images/Print-outlined-circular-interface-button.svg" title="' . addslashes($nacex->l('Print label')) . '" alt="' . addslashes($nacex->l('Print label')) . '" width="38px" /></a>';

            // La etiqueta solo puede imprimirse una vez, por lo que comprobamos si existe el archivo antes de mostrar el boton
            if (nacexutils::existFileLabelPDF('etiquetas_dev', $datosexpedicion['exp_cod'])) {
                $html .= '<input type="button" class="printDevolucion button" onClick="downloadPDF_' . $expIdJs . '(\'etiquetas_dev\')" value="' . $nacex->l('Print return label') . '" />';
            }

        } else {
            // Si no esta activado el servicio 44, que se impriman la etiqueta de la expedicion
            // print
            if (!$gestionAgencia) {
                $html .= '<a onClick="printiframe_' . $expIdJs . '(' . $datosexpedicion['id_envio_order'] . ',' . $datosexpedicion['exp_cod'] . ');" style="cursor:pointer;float:right;margin-right:0.2em;" title="' . addslashes($nacex->l('Print label')) . '"><img class="zoomable" src="' . nacexDTO::getPath() . 'images/Print-outlined-circular-interface-button.svg" title="' . addslashes($nacex->l('Print label')) . '" alt="' . addslashes($nacex->l('Print label')) . '" width="38px" /></a>';
            }
            // end print
        }

        // Finalizamos infobox
        $html .= '</div></div></div>
        </div>'; // accordion panel

        // print
        if (!$gestionAgencia) { $html .= self::showIoNA(); }

        return $html;
    }

    public static function mostrarErrores($errors = [])
    {
        $error_message = '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger error" style="width:auto">';

        foreach ($errors as $error) {
            $error_message .= $error . '<br/>';
        }

        $error_message .= '</div></div>';
        return $error_message;
    }

    public static function mostrarSuccess($success = [])
    {
        $success_message = '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-success conf" style="width:auto">';

        foreach ($success as $aux) {
            $success_message .= $aux . '<br/>';
        }

        $success_message .= '</div></div>';
        return $success_message;
    }

    public static function showIoNA()
    {
        $nacex = new nacex();
        $nacexDTO = new NacexDTO();
        $controller = $nacexDTO->getPath() . 'CambioEstadoPedido.php';

        $urlPrint = Configuration::get('NACEX_PRINT_IONA') . 'print?impresora=' . Configuration::get('NACEX_PRINT_ET');

        $urlNacex = Configuration::get('NACEX_WS_URL');

        $URL = substr($urlNacex, -1) == '/' ? $urlNacex : $urlNacex . '/';
        $etiquetaURL = $URL . 'ws?method=getEtiqueta&user=' . Configuration::get('NACEX_WSUSERNAME') . '&pass=' . Configuration::get('NACEX_WSPASSWORD') . '&data=modelo=' . Configuration::get('NACEX_PRINT_MODEL') . '|codexp=';

        $urlPrint .= '&url=' . urlencode($etiquetaURL);

        //$html = "</script><script type='text/javascript' src='" . _MODULE_DIR_ . "nacex/js/jquery-3.3.1.min.js'></script>";
        $html = "<script type='text/javascript'>

                    function imprimir_iona(refs){
                        refs.forEach(ionaPrint);
                    }

                    function ionaPrint(item){
                        
                        var id = item[0];
                        var cod = item[1];

                        var urlprint = '" . $urlPrint . "' + cod;
                        
                        $.get( urlprint, function() { } )
                        .done(function(data) {
                            console.log('done');
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
                                    alert('" . $nacex->l('Order status has been changed') . "');
                                }
                            });
                        })
                        .fail(function( jqXHR, textStatus, errorThrown ) {
                            // En algunas máquinas da problemas ya que imprime pero devuelve error por no ser una estructura JSON.
                            // De esta manera controlamos si es un error 'válido' o es un error con estado 'OK'
                            if (status === 'error' || !jqXHR.responseText) {
                                alert( '" . $nacex->l('Error on printing label') . ' - ' . Configuration::get('NACEX_PRINT_MODEL') . "');
                                console.log('data response jqXHR:: ' + JSON.stringify(jqXHR));
                                console.log('data response textStatus:: ' + JSON.stringify(textStatus));
                                console.log('data response errorThrown:: ' + JSON.stringify(errorThrown));
                            }
                        });

                    }

            </script>";

        return $html;
    }

    private static function printIframe($shopData, $id)
    {
        $nacex = new nacex();
        $shop = explode('|', $shopData);
        $cp = $shop[4];

        // Sacamos la provincia del CP
        $provincia_id = array_search(substr($cp, 0, 2), array_column(nacexDTO::PROVINCIAS_ES, 'Codigo'));
        $provincia = array_keys(nacexDTO::PROVINCIAS_ES)[$provincia_id];

        $isShop = true;
        $alias = explode('-', $shop[1]);
        $bkgrd_image = $isShop ? 'https://www.nacex.es/fotoNacexShop.do?codigo=' . $shop[0] : 'https://www.nacex.es/pages/img/cercadeti/delegaciones/' . $alias[0] . '_foto.jpg';

        $html = '
        <div id="ncx_det_age_' . $id . '" class="Z3" style="border-radius:5px;display:none">
            <div class="nx-row-padding">
                <img src="https://www.nacex.es/pages/img/cercadeti/mapa2Titulo.gif" width="40px" style="float: left;margin-left: 0.5em;">
                <div class="c7" style="margin-left:10px;"><h3>' . $nacex->l('Agency information') . '</h3></div><br>
                <div id="foto"><img src="' . $bkgrd_image . '" title="' . $shop[2] . '" alt="' . $shop[2] . '" /></div>
                <div class="c7 texto">
                    <span class="c4">' . $nacex->l('Agency') . '</span>
                    <span class="c6"><strong>' . $shop[2] . ' (' . utf8_decode($alias[0]) . ')</strong></span>
                    <span class="c4">' . $nacex->l('Postcode') . '</span>
                    <span class="c6"><strong>' . $shop[4] . '</strong></span>
                    <span class="c4">' . $nacex->l('Address') . '<strong></strong></span><strong>
                    <span class="c6"></span>' . utf8_decode($shop[3]) . '</strong>
                    <span class="c4">' . $nacex->l('City') . '</span>
                    <span class="c6"><strong>' . utf8_decode($shop[5]) . '</strong></span>
                    <span class="c4">' . $nacex->l('State') . '</span>
                    <span class="c6"><strong>' . utf8_decode($provincia) . '</strong></span>
                </div>
            </div>
        </div>
        ';

        return $html;
    }
}
