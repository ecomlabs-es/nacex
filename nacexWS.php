<?php

/*
 * mexpositop 2017
 */
//session_start();

include_once dirname(__FILE__) .'/nacexDTO.php';
include_once dirname(__FILE__) . '/nacexDAO.php';
//include_once dirname(__FILE__) . "/nacex.php";
include_once dirname(__FILE__) . '/AdminConfig.php';
include_once dirname(__FILE__) .'/nacexutils.php';
include_once dirname(__FILE__) . '/tratardatos.php';
include_once dirname(__FILE__) . '/ROnacexshop.php';

class nacexWS {
    public static function ws_getEstadoExpedicion($datosexpedicion, $errorplain = false)
    {
        $nacexWS = new nacexWS();
        nacexutils::writeNacexLog("----\nINI ws_getEstadoExpedicion :: expe_codigo: " . $datosexpedicion['exp_cod']);

        $urlNacex = Configuration::get('NACEX_WS_URL');
        $URL = $urlNacex . '/soap';
        $getEstadoExpedicionResponse = [];

        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');
        $metodo = 'getEstadoExpedicion';
        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
			      <typ:getEstadoExpedicion>
					<String_1>' . $nacexWSusername . '</String_1>
					<String_2>' . $nacexWSpassword . '</String_2>
			         	<String_3>' . $datosexpedicion['exp_cod'] . '</String_3>
			         	<String_4></String_4>
			         	<String_5></String_5>
			         	<String_6></String_6>
			            <arrayOfString_3>' . nacexWS::getSystemInfo() . '</arrayOfString_3> 
			      </typ:getEstadoExpedicion>
			</soapenv:Body>
        </soapenv:Envelope>';

        nacexutils::writeNacexLog('ws_getEstadoExpedicion :: XML: ' . $XML);

        $result = $nacexWS->requestWS($URL, $XML, $metodo);
        $xmlResult = substr($result, 0, 5);
        if ($xmlResult != '<html' && $xmlResult != 'ERROR') {
            $resultado = $nacexWS->treatmentXML($result, $metodo);

            //if ($postResult[0] == "ERROR" && $resultado[2] != "5611") {
            if ($resultado[0] == 'ERROR' && $resultado[2] != '5611' && tools::getValue('submitputexpedicion', '0') != 'Enviar') {
                nacexutils::writeNacexLog('getEstadoExpedicion :: error al obtener estado la expedicion: ' . $resultado[1]);
                nacexutils::writeNacexLog("FIN ws_getEstadoExpedicion\n----");
                return $resultado;
            }

            $getEstadoExpedicionResponse['expe_codigo'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[0] : $datosexpedicion['exp_cod'];
            $getEstadoExpedicionResponse['fecha'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[1] : '-----';
            $getEstadoExpedicionResponse['hora'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[2] : '--';
            $getEstadoExpedicionResponse['observaciones'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[3] : 'Informaci&oacute;n del sistema';

            $expeDate = new DateTime($datosexpedicion['fecha_alta']);
            nacexutils::writeNacexLog('ESTADO, control: ' . $resultado[0]);
            nacexutils::writeNacexLog('Fecha control:  ' . $expeDate->format('Y-m-d H:i:s'));
            $getEstadoExpedicionResponse['estado'] = $resultado[2] == '5611' && Tools::getValue('submitputexpedicion', '0') == 'Enviar' ?
                'PENDIENTE DE INTEGRACi&Oacute;N' :
                nacexutils::getDefValue($resultado, 4, 'PENDIENTE');

            $agenum = explode('/', $datosexpedicion['ag_cod_num_exp']);
            $getEstadoExpedicionResponse['estado_code'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[5] : '9';
            $getEstadoExpedicionResponse['origen'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[6] : $agenum[0];
            $getEstadoExpedicionResponse['albaran'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[7] : $agenum[1];
            $getEstadoExpedicionResponse['exps_rels'] = is_array($resultado) && $resultado[2] != '5611' ? $resultado[8] : '--';

            nacexutils::writeNacexLog('getEstadoExpedicion :: expe_codigo:' . $getEstadoExpedicionResponse['expe_codigo'] . '|fecha:' . $getEstadoExpedicionResponse['fecha'] . '|hora:' . $getEstadoExpedicionResponse['hora'] . '|observaciones:' . $getEstadoExpedicionResponse['observaciones'] . '|estado:' . $getEstadoExpedicionResponse['estado'] . '|estado_code:' . $getEstadoExpedicionResponse['estado_code'] . '|origen:' . $getEstadoExpedicionResponse['origen'] . '|albaran:' . $getEstadoExpedicionResponse['albaran'] . '|exps_rels:' . $getEstadoExpedicionResponse['exps_rels']);
            nacexutils::writeNacexLog("FIN ws_getEstadoExpedicion\n----");

            return $getEstadoExpedicionResponse;
        } else {
            return ['ERROR',$result];
        }
    }

    public static function ws_getDatosWSExpedicion($expe_codigo, $errorplain = false) {

        nacexutils::writeNacexLog("----\nINI ws_getDatosWSExpedicion :: expe_codigo: " . $expe_codigo);
        $nacexWS = new nacexWS();

        $urlNacex = Configuration::get('NACEX_WS_URL');
        $URL = $urlNacex . '/soap';
        $getDatosWSExpedicion = [];

        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');

        $metodo = 'getInfoEnvio';
        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
			      <typ:getInfoEnvio>
					<String_1>' . $nacexWSusername . '</String_1>
					<String_2>' . $nacexWSpassword . '</String_2>
			        <String_3>E</String_3>
			        <String_4>' . $expe_codigo . '</String_4>
			        <String_5></String_5>
			        <String_6></String_6>
			        <arrayOfString_3>' . nacexWS::getSystemInfo() . '</arrayOfString_3> 
			      </typ:getInfoEnvio>
			</soapenv:Body>
		</soapenv:Envelope>';

        nacexutils::writeNacexLog('ws_getDatosWSExpedicion :: XML: ' . $XML);

        $postResult = $nacexWS->requestWS($URL, $XML, $metodo, 'width:auto;', $errorplain);
        if ($postResult[0] == 'ERROR') {
            return $postResult;
        }
        $resultado = $nacexWS->treatmentXML($postResult, $metodo);
        if ($resultado[0] == 'ERROR') {
            nacexutils::writeNacexLog('ws_getDatosWSExpedicion :: error al obtener estado la expedicion: ' . $resultado[1]);
            return $resultado[1];
        } else {
            $getDatosWSExpedicion['expe_cod'] = $resultado[0];
            $getDatosWSExpedicion['ag'] = $resultado[1];
            $getDatosWSExpedicion['num'] = $resultado[2];
            $getDatosWSExpedicion['serv_cod'] = $resultado[7];
            $array_refs = explode(';', $resultado[14]);
            $getDatosWSExpedicion['ref'] = $array_refs[0];
            nacexutils::writeNacexLog('ws_getDatosWSExpedicion :: expe_cod:' . $resultado[0] . '|ag:' . $resultado[1] . '|num:' . $resultado[2] . '|serv_cod:' . $resultado[7] . '|ref:' . $array_refs[0]);
        }

        nacexutils::writeNacexLog('FIN ws_getDatosWSExpedicion :: expe_codigo: ' . $expe_codigo . "\n----");
        return $getDatosWSExpedicion;
    }

    public static function ws_checkConnection()
    {
        $nacexWS = new nacexWS();

        //Datos de configuración
        $urlNacex = nacexDTO::$url_ws;
        $URL = $urlNacex . '/soap';
        if (isset($_POST['usr']) && isset($_POST['pass'])) {
            $nacexWSusername = $_POST['usr'];
            $nacexWSpassword = strtoupper(md5($_POST['pass']));
        } else {
            $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
            $nacexWSpassword = strtoupper(md5(Configuration::get('NACEX_WSPASSWORD_ORIGINAL')));
        }
        $pais = 'ESP';
        $metodo = 'getCodigoPais';

        $XML =
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
                <soapenv:Header/>
                <soapenv:Body>
                      <typ:getCodigoPais>
                        <String_1>' . $nacexWSusername . '</String_1>
                        <String_2>' . $nacexWSpassword . '</String_2>
                <arrayOfString_3>' . $pais . '</arrayOfString_3>		        
                      </typ:getCodigoPais>
                </soapenv:Body>
            </soapenv:Envelope>';

        $postResult = $nacexWS->requestWS($URL, $XML, $metodo, $style = 'width:auto;');
        if ($postResult[0] == 'ERROR' || strpos($postResult, 'HTTP Status 500')) {
            return $postResult;
        }
        $resultado = $nacexWS->treatmentXML($postResult, $metodo);

        return $resultado;
    }

    public static function ws_getValoracion($cp_ent, $tip_ser, $kil, $tip_env)
    {
        nacexutils::writeNacexLog("----\nINI ws_getValoracion :: cp_ent: " . $cp_ent . ' |tip_ser: ' . $tip_ser . ' |kil: ' . $kil . ' |tip_env: ' . $tip_env);
        $nacexWS = new nacexWS();
        $cp_rec = Configuration::get('NACEX_CP_REC');
        $urlNacex = Configuration::get('NACEX_WS_URL');
        $URL = $urlNacex . '/soap';

        //$getValoracionResponse = array();
        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = strtoupper(md5(Configuration::get('NACEX_WSPASSWORD_ORIGINAL')));

        //  $tip_env_int = Configuration::get('NACEX_TIP_ENV_INT');

        $ag_cli = Configuration::get('NACEX_AGCLI');

        $array_agclis = explode(',', $ag_cli);
        //Cogemos la primera pareja
        $firstagcli = trim($array_agclis[0]);
        $arr_ag_cli = explode('/', $firstagcli);

        $del_cli = trim($arr_ag_cli[0]);
        $num_cli = trim($arr_ag_cli[1]);

        //El motor no esta preparado para valorar pesos < 1Kg, si el peso es menor a 1Kg lo modificamos.
        if (floatval($kil) < 1) {
            $kil = 1;
        }

        // Eliminamos los - que puedan haber en los CP (sobretodo de Portugal)
        $cp_ent = str_replace('-', '', $cp_ent);

        $metodo = 'getValoracion';
        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
			      <typ:getValoracion>
					 <String_1>' . $nacexWSusername . '</String_1>
					 <String_2>' . $nacexWSpassword . '</String_2>
					 <arrayOfString_3>cp_rec=' . $cp_rec . '</arrayOfString_3>
					 <arrayOfString_3>cp_ent=' . $cp_ent . '</arrayOfString_3>
					 <arrayOfString_3>tip_ser=' . $tip_ser . '</arrayOfString_3>         
					 <arrayOfString_3>tip_env=' . $tip_env . '</arrayOfString_3>         
					 <arrayOfString_3>kil=' . $kil . '</arrayOfString_3>         					    
					 <arrayOfString_3>del_cli=' . $del_cli . '</arrayOfString_3>
					 <arrayOfString_3>num_cli=' . $num_cli . '</arrayOfString_3>
			         <arrayOfString_3>' . nacexWS::getSystemInfo() . '</arrayOfString_3> 
			      </typ:getValoracion>
			</soapenv:Body>
		</soapenv:Envelope>';

        nacexutils::writeNacexLog('ws_getValoracion :: XML: ' . $XML);
        $postResult = $nacexWS->requestWS($URL, $XML, $metodo, $style = 'width:auto;');
        if ($postResult[0] == 'ERROR') {
            return $postResult;
        }
        $resultado = $nacexWS->treatmentXML($postResult, $metodo);
        nacexutils::writeNacexLog('ws_getValoracion :: resultado: ' . print_r($resultado, true));

        return $resultado;
    }

    public static function cancelExpedicion($id_pedido, $cod_exp) {
        nacexutils::writeNacexLog("----\nINI cancelExpedicion :: id_pedido: " . $id_pedido . ' |cod_exp: ' . $cod_exp);

        $nacexDTO = new nacexDTO();
        $nacexWS = new nacexWS();

        $id_order = $id_pedido;

        $urlNacex = Configuration::get('NACEX_WS_URL');
        $URL = $urlNacex . '/soap';

        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');

        $metodo = 'cancelExpedicion';
        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
					<soapenv:Header/>
					<soapenv:Body>
						<typ:cancelExpedicion>
							<String_1>' . $nacexWSusername . '</String_1>
							<String_2>' . $nacexWSpassword . '</String_2>
							<arrayOfString_3>expe_codigo=' . $cod_exp . '</arrayOfString_3>
							<arrayOfString_3></arrayOfString_3>
							<arrayOfString_3></arrayOfString_3>
							<arrayOfString_3></arrayOfString_3>
							<arrayOfString_3>' . nacexWS::getSystemInfo() . '</arrayOfString_3>
						</typ:cancelExpedicion>
					</soapenv:Body>
				</soapenv:Envelope>';

        nacexutils::writeNacexLog('cancelExpedicion :: XML: ' . $XML);

        $postResult = $nacexWS->requestWS($URL, $XML, $metodo, $style = 'width:auto;');
        if ($postResult[0] == 'ERROR') {
            return $postResult;
        }
        $resultado = $nacexWS->treatmentXML($postResult, $metodo);

        if ($resultado[0] == 'ERROR') {
            //echo '<br><div class="alert error" style="width:396px">';
            echo '<br><div id="messages-nacex" class="bootstrap" style="margin-top:10px">';
            echo '<div class="alert alert-danger conf" style="width:auto">';
            foreach ($resultado as $res) {
                echo '<strong>' . $res . '</strong>';
                nacexutils::writeNacexLog('cancelExpedicion :: ' . $res);
            }
            echo '</div></div>';
            //return utf8_encode($this->_html);(mexpositop 20171127)
            return null;
        } else {
            //echo '<br><div class="conf confirm" style="width:396px">';
            //echo '<img src="' . $nacexDTO->getPath() . 'logo.gif" />';
            echo '<br><div id="messages-nacex" class="bootstrap" style="margin-top:10px">';
            echo '<div class="alert alert-success conf" style="width:auto">';
            foreach ($resultado as $res) {
                echo '<strong>' . $res . '</strong>';
                nacexutils::writeNacexLog('cancelExpedicion :: ' . $res);
            }
            echo '</div></div>';
            echo '<script>
					jQuery("#ncx_boxinfo").hide();
				  </script>';

            // $datospedido = nacexDAO::getDatosPedido($id_order);
            nacexutils::writeNacexLog('cancelExpedicion :: obtenemos los datos del pedido id_order:' . $id_order);
            nacexDAO::cancelarExpedicion($id_pedido, $cod_exp);

            /*** Cambio de estado al pedido al documentar expedición ***/
            nacexutils::writeNacexLog('cancelExpedicion :: Cambiamos el estado del pedido');
            nacexDAO::actualizaEstadoPedido($id_order, 'c');

            nacexutils::writeNacexLog('FIN cancelExpedicion :: id_pedido: ' . $id_pedido . ' |cod_exp: ' . $cod_exp . "\n----");
        }
    }

    public static function putExpedicionDev($id_pedido, $datospedido, $datosexp, $isnacexcambio, $_unitaria = false)
    {
        nacexutils::writeNacexLog("----\nINI putExpedicionDev :: id_order: " . $datospedido[0]['id_order'] . '| module: ' . $datospedido[0]['module'] . '| total_paid_real: ' . $datospedido[0]['total_paid_real'] . '| email: ' . $datospedido[0]['email'] . '| firstname: ' . $datospedido[0]['firstname'] . '| lastname: ' . $datospedido[0]['lastname'] . '| address1: ' . $datospedido[0]['address1'] . '| postcode: ' . $datospedido[0]['postcode'] . '| city: ' . $datospedido[0]['city'] . '| phone: ' . $datospedido[0]['phone'] . '| phone_mobile: ' . $datospedido[0]['phone_mobile'] . '| iso_code: ' . $datospedido[0]['iso_code'] . '| ncx: ' . $datospedido[0]['ncx'] . '| id_carrier: ' . $datospedido[0]['id_carrier']);
        $nacex = new nacex();
        $nacexDTO = new nacexDTO();
        $nacexWS = new nacexWS();

        //$urlNacex = Configuration::get('NACEX_WS_URL');
        $urlNacex = NacexDTO::$url_ws;
        $URL = $urlNacex . '/soap';
        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');

        $nacex_agcli = Tools::getValue('nacex_agcli');
        $nacex_agcli_array = explode('/', $nacex_agcli);
        $nacexCodigoAgencia = $nacex_agcli_array[0];
        $nacexCodigoCliente = $nacex_agcli_array[1];

        $nacexTipSer = Tools::getValue('nacex_tip_ser');
        $xml_NacexFrecuencia = $nacexTipSer == '09' ? '<arrayOfString_3>frec_codigo=' . Tools::getValue('nacex_frecuencia') . '</arrayOfString_3>' : '';

        $nacexReferencia = nacexutils::getReferenciaGeneral() . $id_pedido;
        /*if ($isnacexcambio) {
            $nacexReferencia = $datosexp["ref"];
        }*/

        $nacexTipCob = Tools::getValue('nacex_tip_cob');
        $nacexTipRee = Tools::getValue('nacex_tip_ree');
        $nacexTipEnv = Tools::getValue('nacex_tip_env');
        $nacexRet = Tools::getValue('nacex_ret');

        $nacexTipSeg = Tools::getValue('nacex_tip_seg');
        $nacexImpSeg = Tools::getValue('nacex_imp_seg');

        $nacexPerEnt = Tools::getValue('nacex_per_ent');

        $tip_pre1 = Tools::getValue('nacex_tip_pre1');
        $pre1 = Tools::getValue('nacex_pre1');
        $mod_pre1 = Tools::getValue('nacex_mod_pre1');
        $prep1lus = Tools::getValue('nacex_pre1_plus');

        $q_r = Tools::getValue('inst_adi');
        $obs1 = Tools::getValue('nacex_obs1');
        $obs2 = Tools::getValue('nacex_obs2');

        // Intrernacional
        $ImpDeclarado = Tools::getValue('nacex_impDeclarado');
        $Contenido = Tools::getValue('nacex_contenido');

        //nacex_contenido OTROS coger valor nacex_descripcion_contenido

        if ($Contenido == 'OTROS') {
            $Contenidodescripcion = Tools::getValue('nacex_descripcion_contenido');
        }

        //Datos del pedido
        $datos = Db::getInstance()->executeS(
            'SELECT o.id_order,o.module,u.email,a.firstname,
				a.lastname,a.address1,a.address2,a.postcode,a.city,a.phone,a.phone_mobile,z.iso_code,
				case when o.total_paid_real > 0 
					then o.total_paid_real
				else
					o.total_paid
				end as total_paid_real
				FROM ' . _DB_PREFIX_ . 'orders AS o
				JOIN ' . _DB_PREFIX_ . 'customer AS u
				JOIN ' . _DB_PREFIX_ . 'address a
				JOIN ' . _DB_PREFIX_ . 'country AS z
				WHERE o.id_order = ' . (int)$id_pedido . '
				AND u.id_customer = o.id_customer
				AND a.id_address = o.id_address_delivery
				AND a.id_country = z.id_country'
        );
        nacexutils::writeNacexLog('putExpedicionDev :: obtenidos datos pedido BD id_order: ' . $datos[0]['id_order'] . ' | module: ' . $datos[0]['module'] . ' | total_paid_real: ' . $datos[0]['total_paid_real'] . ' | email: ' . $datos[0]['email'] . ' | firstname: ' . $datos[0]['firstname'] . ' | lastname ' . $datos[0]['lastname'] . ' | address1: ' . $datos[0]['address1'] . ' | postcode: ' . $datos[0]['postcode'] . ' | city: ' . $datos[0]['city'] . ' | phone: ' . $datos[0]['phone'] . ' | phone_mobile: ' . $datos[0]['phone_mobile'] . ' | iso_code: ' . $datos[0]['iso_code']);

        //Obtenemos más detalles del pedido, como el peso y las referencias
        $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = ' . (int)$id_pedido);

        //Miramos si hay que informar del retorno
        $xml_ret = (isset($nacexRet) && $nacexRet == 'SI') ? '<arrayOfString_3>ret=S</arrayOfString_3>' : '';

        $xml_seg = '';
        //Miramos si hay que informar del seguro
        if ((isset($nacexImpSeg) && $nacexImpSeg != '')) {
            $xml_seg = '<arrayOfString_3>tip_seg=' . $nacexTipSeg . '</arrayOfString_3>';
            $xml_seg .= '<arrayOfString_3>seg=' . number_format((float)str_replace(',', '.', str_replace('.', '', $nacexImpSeg)), 2, '.', '') . '</arrayOfString_3>';
        }

        // Peso del pedido
        //$peso = Tools::getValue('NACEX_PESO', 'C') == "F" ? str_replace(",", ".", str_replace(".", "", Configuration::get("NACEX_PESO_NUMERO"))) : 0;
        $peso = Configuration::get('NACEX_PESO') == 'F' ? (float)str_replace(',', '.', Configuration::get('NACEX_PESO_NUMERO')) : 0.0;
        if ($peso == 0) {
            foreach ($productospedido as $producto) {
                $peso += floatval($producto['product_quantity'] * $producto['product_weight']);
            } }
        $peso = max(1.0, (float)$peso);
        $peso = number_format($peso, 2, '.', '');

        // bultos del pedido, se recupera del formulario del usuario para permitir modificaciones
        $bultos = max(1, Tools::getValue('nacex_bul', 1));

        // Programar fecha de expedicion
        //$program_fecha = Tools::getValue('nacex_fec', date('d/m/Y'));
        $program_fecha = Tools::getValue('nacex_fec', date('Y-m-d'));
        $program_fecha = date_format(date_create($program_fecha), 'd/m/Y');

        // referencias del pedido
        foreach ($productospedido as $producto) {
            $prodref = str_replace(';', ',', $producto['product_reference']);
        }

        //Variables NacexShop
        $shopData = self::resolveNacexShopData($datospedido);
        $is_nxshop = $shopData['is_nxshop'];
        $shop_codigo = $shopData['shop_codigo'];
        $shop_alias = $shopData['shop_alias'];
        $shop_nombre = $shopData['shop_nombre'];
        $shop_direccion = $shopData['shop_direccion'];
        $ncxshop = $shopData['ncxshop_xml'];
        $array_shop_data = $shopData['array_shop_data'];

        // Si es NacexShop la dirección sólo tiene que ser la 1, porque la otra es meramente informativa
        //Preparamos la dirección para sanearla
        if ($is_nxshop) { $nacex_nombre_via_destinatario = $datos[0]['address1']; } else { $nacex_nombre_via_destinatario = $datos[0]['address1'] . ' ' . $datos[0]['address2']; }

        $nacex_nombre_via_destinatario = trim(substr($nacex_nombre_via_destinatario, 0, 60));

        //TREAT ORDER DATA IN ORDER TO AVOID WEBSERVICE ISSUES
        $_tratar_datos = new tratardatos();
        $_tratar_datos->string($datos[0]['email']);
        $_tratar_datos->string($datos[0]['firstname']);
        $_tratar_datos->string($datos[0]['lastname']);
        $_tratar_datos->string($nacex_nombre_via_destinatario);
        $_tratar_datos->string($datos[0]['postcode']);
        $_tratar_datos->string($datos[0]['city']);
        $_tratar_datos->string($datos[0]['phone']);
        $_tratar_datos->string($datos[0]['phone_mobile']);
        $_tratar_datos->string($pre1);
        $_tratar_datos->string($prep1lus);
        $_tratar_datos->string($obs1);
        $_tratar_datos->string($obs2);
        $_tratar_datos->string($q_r);

        // Si la última letra de la dirección es un caracter especial, lo sustituimos por un espacio en blanco para no tener más problemas con símbolos
        //$nacex_nombre_via_destinatario = preg_match('/[^a-zA-Z\d]/', substr($nacex_nombre_via_destinatario, -1)) == 1 ? substr($nacex_nombre_via_destinatario, 0, -2) . ' ' : $nacex_nombre_via_destinatario;

        //Informarmos de las instrucciones adicionales
        $xml_insadi_qr = '';

        $array_insadi_qr = nacexutils::cutupString($q_r, 40, 15);
        for ($i = 0; $i < count($array_insadi_qr); $i++) {
            if ($array_insadi_qr [$i] != '') {
                $xml_insadi_qr .= '<arrayOfString_3><![CDATA[ins_adi';
                $xml_insadi_qr .= $i + 1;
                $xml_insadi_qr .= '=';
                $xml_insadi_qr .= $array_insadi_qr [$i];
                $xml_insadi_qr .= ']]></arrayOfString_3>';
            }
        }

        $xml_insadi_qr .= ($xml_insadi_qr != '') ? '<arrayOfString_3>ins_adi=S</arrayOfString_3>' : '';

        //Obtenemos el importe total del pedido
        $nacex_importe_servicio = $datos[0]['total_paid_real'];

        //Datos del comprador
        // Si es shop, los cambiamos por el nombre de la tienda
        if ($is_nxshop) { $nacex_nombre_destinatario = $shop_alias . ' ' . $shop_nombre; } else { $nacex_nombre_destinatario = $datos[0]['firstname'] . ' ' . $datos[0]['lastname']; }

        $nacex_poblacion_destinatario = $datos[0]['city'];
        $nacex_CP_destinatario = $datos[0]['postcode'];

        $tels = [
            $datos[0]['phone'],
            $datos[0]['phone_mobile']
        ];
        $nacex_telefono_destinatario = implode('/', array_unique(array_filter($tels)));

        // $nacex_email_destinatario = $datos[0]['email'];
        $nacex_pais = $datos[0]['iso_code'];

        //REEMBOLSO ---------------------------------------------------------------------------------------------------------------
        //Nombres módulos pago contra reembolso indicados en Configuración
        $array_modsree = explode('|', Configuration::get('NACEX_MODULOS_REEMBOLSO'));
        $nacex_reembolso = null;
        $metodo_pago = strtolower($datos[0]['module']);

        if (in_array($metodo_pago, $array_modsree) ||
            strpos($metodo_pago, 'cashondelivery') !== false ||
            intval(Tools::getValue('nacex_imp_ree')) > 0) {
            //WE ALWAYS GET THE VALUE FROM THE FORM
            $nacex_reembolso = Tools::getValue('nacex_imp_ree');
            //$nacex_reembolso = floatval($nacex_importe_servicio)==null? Tools::getValue('nacex_imp_ree'):floatval($nacex_importe_servicio);

            nacexutils::writeNacexLog('putExpedicionDev :: detectado modo de pago [' . $metodo_pago . '] expedicion y reembolso de ' . $nacex_reembolso);

            //Si estamos documentando un nacex cambio, debemos comprobar si el cliente ha informado de la cantidad de reembolso
            if ($isnacexcambio) {
                $nacex_reembolso = floatval(str_replace(',', '.', Tools::getValue('nacex_imp_ree')));
                if ($nacex_reembolso == 0) {
                    $nacex_reembolso = null;
                }
            }
        }

        $xml_ree = '';
        if (isset($nacex_reembolso)) {
            $xml_ree = '<arrayOfString_3>tip_ree=' . $nacexTipRee . '</arrayOfString_3>';
            $xml_ree .= '<arrayOfString_3>ree=' . $nacex_reembolso . '</arrayOfString_3>';
        }
        //REEMBOLSO ---------------------------------------------------------------------------------------------------------------

        //PREALERTA ---------------------------------------------------------------------------------------------------------------
        $xml_pre = '';
        if ($tip_pre1 != 'N') {
            $xml_pre .= '<arrayOfString_3>tip_pre1=' . $tip_pre1 . '</arrayOfString_3>';

            $xml_pre .= ($mod_pre1 == 'S' || $mod_pre1 == 'P' || $mod_pre1 == 'R' || $mod_pre1 == 'E') ?
                '<arrayOfString_3>mod_pre1=' . $mod_pre1 . '</arrayOfString_3>' :
                '<arrayOfString_3>mod_pre1=S</arrayOfString_3>';
            $xml_pre .= '<arrayOfString_3>pre1=' . substr($pre1, 0, 50) . '</arrayOfString_3>';

            $xml_pre .= ($mod_pre1 == 'P' || $mod_pre1 == 'E') && ($prep1lus && strlen($prep1lus) > 0) ?
                '<arrayOfString_3>msg1=' . substr($prep1lus, 0, 719) . '</arrayOfString_3>' :
                '';
        }
        //PREALERTA ---------------------------------------------------------------------------------------------------------------

        $xml_obs1 = isset($obs1) && $obs1 != '' ? '<arrayOfString_3><![CDATA[obs1=' . $obs1 . ']]></arrayOfString_3>' : '';
        $xml_obs2 = isset($obs2) && $obs2 != '' ? '<arrayOfString_3><![CDATA[obs2=' . $obs2 . ']]></arrayOfString_3>' : '';
        $xml_per_ent = isset($nacexPerEnt) && $nacexPerEnt != '' ? '<arrayOfString_3>per_ent=' . $nacexPerEnt . '</arrayOfString_3>' : '';

        //DEPARTAMENTOS DEL CLIENTE ----------------------------------------------------------------------------
        $dpto = Tools::getValue('nacex_departamentos', '');
        $xml_dpto = isset($dpto) && $dpto != '' ? '<arrayOfString_3>dep_cli=' . $dpto . '</arrayOfString_3>' : '';
        //DEPARTAMENTOS DEL CLIENTE ----------------------------------------------------------------------------

        //Internacional ----------------------------------------------------------------------------------------
        /**info:
         * RECOGEMOS EL VALOR DECLARADO SI SE TRATA DE UN INTERNACIONAL.
         */
        $xml_NacexImpDeclarado = '';
        $xml_NacexContenido = '';

        if (!in_array($nacex_pais, ['ES', 'PT', 'AD'])) {

            $xml_NacexImpDeclarado = '<arrayOfString_3>val_dec=' . $ImpDeclarado . '</arrayOfString_3>';

            //CONTROL DE CONTENIDO OTROS PARA LA DECLARACIÓN DE MERCANCÍA

            if ($Contenido == 'OTROS') {

                $xml_NacexContenido = '<arrayOfString_3><![CDATA[con=' . $Contenidodescripcion . ']]></arrayOfString_3>';

            } else {

                $xml_NacexContenido = '<arrayOfString_3>con=' . $Contenido . '</arrayOfString_3>';

            }
        }

        /* $xml_NacexImpDeclarado = in_array($nacex_pais, array ('ES','PT','AD'))&& isset($ImpDeclarado) ?
                                                     "<arrayOfString_3>val_dec=" . $nacex_importe_servicio . "</arrayOfString_3>":
                                                     "<arrayOfString_3>val_dec=" . $ImpDeclarado . "</arrayOfString_3>";
        */

        // Añadimos el str-replace para quitar los - del CP, por ejemplo, de Portugal.
        $nacex_CP_destinatario = str_replace('-', '', $is_nxshop ? $nacex_CP_destinatario : substr($nacex_CP_destinatario, 0, 15));

        //Internacional ----------------------------------------------------------------------------------------

        $metodo = 'putExpedicionDev';

        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
				<typ:putExpedicionDev>
					<String_1>' . $nacexWSusername . '</String_1>
					<String_2>' . $nacexWSpassword . '</String_2>
					<arrayOfString_3>del_cli=' . $nacexCodigoAgencia . '</arrayOfString_3>
					<arrayOfString_3>num_cli=' . $nacexCodigoCliente . '</arrayOfString_3>
					<arrayOfString_3>fec=' . $program_fecha . '</arrayOfString_3>' .
            $xml_dpto . '
					<arrayOfString_3>tip_ser=' . $nacexTipSer . '</arrayOfString_3>
					<arrayOfString_3>tip_cob=' . $nacexTipCob . '</arrayOfString_3>
					<arrayOfString_3>tip_env=' . $nacexTipEnv . '</arrayOfString_3>' .
            $xml_ree . '' .
            $xml_pre . '' .
            $xml_obs1 . '' .
            $xml_obs2 . '' .
            $xml_per_ent . '
					<arrayOfString_3>ref_cli=' . $nacexReferencia . '</arrayOfString_3>					
					<arrayOfString_3>bul=' . $bultos . '</arrayOfString_3>
					<arrayOfString_3>kil=' . $peso . '</arrayOfString_3>
					<arrayOfString_3><![CDATA[nom_ent=' . substr($nacex_nombre_destinatario, 0, 50) . ']]></arrayOfString_3>
					<arrayOfString_3><![CDATA[dir_ent=' . $nacex_nombre_via_destinatario . ']]></arrayOfString_3>
					<arrayOfString_3>pais_ent=' . $nacex_pais . '</arrayOfString_3>					
					<arrayOfString_3>cp_ent=' . substr($nacex_CP_destinatario, 0, 15) . '</arrayOfString_3>
					<arrayOfString_3>pob_ent=' . substr($nacex_poblacion_destinatario, 0, 40) . '</arrayOfString_3>
					<arrayOfString_3>tel_ent=' . substr($nacex_telefono_destinatario, 0, 20) . '</arrayOfString_3>' .
            $ncxshop .
            $xml_insadi_qr .
            $xml_ret .
            $xml_seg .
            $xml_NacexImpDeclarado .
            $xml_NacexContenido .
            $xml_NacexFrecuencia . ' 
                    <arrayOfString_3>modelo=PDF_B</arrayOfString_3>                                             
                    <arrayOfString_3>' . nacexWS::getSystemInfo() . '</arrayOfString_3>                                             
                </typ:putExpedicionDev>
			</soapenv:Body>
		</soapenv:Envelope>';

        nacexutils::writeNacexLog('putExpedicionDev :: XML: ' . $XML);
        $errorplain = false;
        $postResult = $nacexWS->requestWS($URL, $XML, $metodo, $style = 'width:auto;', $errorplain);
        if ($_unitaria == true) { $_response = ''; }

        if ($postResult[0] == 'ERROR') {
            if ($_unitaria == false) { return $postResult; } else {
                nacexutils::print_messages($_response, 'ERROR', $postResult);
                return $_response;
            }
        }
        $resultado = $nacexWS->treatmentXML($postResult, $metodo);
        if ($resultado[0] == 'ERROR') {
            //echo '<br><div style="text-align:left;width:396px" class="alert error"><img src="' . $nacexDTO->getPath() . 'logo.gif" />';
            nacexutils::writeNacexLog('FIN putExpedicionDev :: id_pedido: ' . $id_pedido . "\n----");
            if ($_unitaria == false) {
                $return = '<br><div id="messages-nacex" class="bootstrap" style="margin-top:10px">';
                $return .= '<div class="alert alert-danger conf" style="width:auto">';
                foreach ($resultado as $res) {
                    $return .= '<strong>' . $res . '</strong><br/>';
                    nacexutils::writeNacexLog('putExpedicionDev :: ERROR =>' . $res);
                }
                $return .= '</div></div>';
                nacexutils::writeNacexLog('FIN putExpedicionDev :: id_pedido: ' . $id_pedido . "\n----");
                //return $this->_html;(mexpositop 20171127)
                //return false;
            } else {
                $_return = [];
                nacexutils::print_messages($_response, 'ERROR', $resultado[1]);
                nacexutils::writeNacexLog('putExpedicionDev :: ERROR =>' . $resultado[1]);
                array_push($_return, ['cod_response' => '400',
                    'result' => $_response
                ]);
                //return $_return;
            }
        } else {

            $putExpedicionResponse = [];
            $putExpedicionResponse['exp_cod'] = $resultado[0];
            $putExpedicionResponse['ag_cod_num_exp'] = $resultado[1];
            $putExpedicionResponse['color'] = $resultado[2];
            $putExpedicionResponse['ent_ruta'] = $resultado[3];
            $putExpedicionResponse['ent_cod'] = $resultado[4];
            $putExpedicionResponse['ent_nom'] = $resultado[5];
            $putExpedicionResponse['ent_tlf'] = $resultado[6];
            $putExpedicionResponse['serv'] = $resultado[7];
            $putExpedicionResponse['hora_entrega'] = $resultado[8];
            $putExpedicionResponse['barcode'] = $resultado[9];
            $putExpedicionResponse['fecha_objetivo'] = $resultado[10];
            $putExpedicionResponse['cambios'] = $resultado[11];

            $putExpedicionResponse['shop_codigo'] = $shop_codigo;
            $putExpedicionResponse['shop_alias'] = $shop_alias;
            $putExpedicionResponse['shop_nombre'] = $shop_nombre;
            $putExpedicionResponse['shop_direccion'] = $shop_direccion;

            $putExpedicionResponse['ret'] = $nacexRet;

            $putExpedicionResponse['ref'] = $nacexReferencia;

            // Guardamos los PDFs de las expediciones
            nacexutils::generateLabelPDF($resultado[14], 'etiquetas', $putExpedicionResponse['exp_cod']);
            nacexutils::generateLabelPDF($resultado[17], 'etiquetas_dev', $putExpedicionResponse['exp_cod']);

            nacexutils::writeNacexLog('putExpedicionDev :: recibido putExpedicionDevResponse: exp_cod: ' . $putExpedicionResponse['exp_cod'] . '| ag_cod_num_exp: ' . $putExpedicionResponse['ag_cod_num_exp'] . '| color: ' . $putExpedicionResponse['color'] . '| ent_ruta: ' . $putExpedicionResponse['ent_ruta'] . '| ent_cod: ' . $putExpedicionResponse['ent_cod'] . '| ent_nom: ' . $putExpedicionResponse['ent_nom'] . '| ent_tlf: ' . $putExpedicionResponse['ent_tlf'] . '| serv: ' . $putExpedicionResponse['serv'] . '| hora_entrega: ' . $putExpedicionResponse['hora_entrega'] . '| barcode: ' . $putExpedicionResponse['barcode'] . '| fecha_objetivo: ' . $putExpedicionResponse['fecha_objetivo'] . '| cambios: ' . $putExpedicionResponse['cambios'] . '| shop_codigo: ' . $putExpedicionResponse['shop_codigo'] . '| shop_alias: ' . $putExpedicionResponse['shop_alias'] . '| shop_nombre: ' . $putExpedicionResponse['shop_nombre'] . '| shop_direccion: ' . $putExpedicionResponse['shop_direccion'] . '| ret: ' . $putExpedicionResponse['ret']);
            nacexutils::writeNacexLog('putExpedicionDev :: Generados PDF');

            nacexDAO::guardarExpedicion($nacex_agcli, $id_pedido, $putExpedicionResponse, $bultos, $array_shop_data, $nacexRet, $nacexTipSer, $nacex_reembolso);
            //  nacexView::showExpedicionBoxInfo($putExpedicionResponse, $id_pedido, $solicitud);

            nacexDAO::actualizarTrackingExpedicion($id_pedido, $resultado[1]);

            /*** Cambio de estado al pedido al documentar expedición ***/
            nacexutils::writeNacexLog('putExpedicionDev :: Cambiamos el estado del pedido');
            nacexDAO::actualizaEstadoPedido($id_pedido, 'd');

            // Añadimos el código de la expedición que se acaba de hacer a POST
            $_POST['expe_codigo'] = $putExpedicionResponse['exp_cod'];

            nacexutils::writeNacexLog('FIN putExpedicionDev :: id_pedido: ' . $id_pedido . "\n----");
            //echo '<br><div style="text-align:left;width:396px" class="alert error"><img src="' . $nacexDTO->getPath() . 'logo.gif" />';
            if ($_unitaria == false) {
                $return = '<br><div id="messages-nacex" class="bootstrap" style="margin-top:10px">';
                $return .= '<div class="alert alert-success conf" style="width:auto">';
                $return .= '<strong>' . $nacex->l('Expedition created successfully') . '</strong>';
                $return .= '</div></div>';
            } else {
                $return = [];
                nacexutils::print_messages($_response, 'SUCCESS', $nacex->l('Expedition created successfully'));
                array_push($return, ['cod_response' => '200',
                    'result' => $_response
                ]);
            }
        }
        return $return;
    }

    public static function putExpedicion($id_pedido, $datospedido, $datosexp, $isnacexcambio, $_unitaria = false)
    {

        nacexutils::writeNacexLog("----\nINI putExpedicion :: id_order: " . $datospedido[0]['id_order'] . '| module: ' . $datospedido[0]['module'] . '| total_paid_real: ' . $datospedido[0]['total_paid_real'] . '| email: ' . $datospedido[0]['email'] . '| firstname: ' . $datospedido[0]['firstname'] . '| lastname: ' . $datospedido[0]['lastname'] . '| address1: ' . $datospedido[0]['address1'] . '| postcode: ' . $datospedido[0]['postcode'] . '| city: ' . $datospedido[0]['city'] . '| phone: ' . $datospedido[0]['phone'] . '| phone_mobile: ' . $datospedido[0]['phone_mobile'] . '| iso_code: ' . $datospedido[0]['iso_code'] . '| ncx: ' . $datospedido[0]['ncx'] . '| id_carrier: ' . $datospedido[0]['id_carrier']);
        $nacex = new nacex();
        $nacexDTO = new nacexDTO();
        $nacexWS = new nacexWS();

        //$urlNacex = Configuration::get('NACEX_WS_URL');
        $urlNacex = NacexDTO::$url_ws;
        $URL = $urlNacex . '/soap';
        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');

        $nacex_agcli = Tools::getValue('nacex_agcli');
        $nacex_agcli_array = explode('/', $nacex_agcli);
        $nacexCodigoAgencia = $nacex_agcli_array[0];
        $nacexCodigoCliente = $nacex_agcli_array[1];

        $nacexTipSer = Tools::getValue('nacex_tip_ser');
        $xml_NacexFrecuencia = $nacexTipSer == '09' ? '<arrayOfString_3>frec_codigo=' . Tools::getValue('nacex_frecuencia') . '</arrayOfString_3>' : '';

        $nacexReferencia = nacexutils::getReferenciaGeneral() . $id_pedido;
        /*if ($isnacexcambio) {
            $nacexReferencia = $datosexp["ref"];
        }*/

        $nacexTipCob = Tools::getValue('nacex_tip_cob');
        $nacexTipRee = Tools::getValue('nacex_tip_ree');
        $nacexTipEnv = Tools::getValue('nacex_tip_env');
        $nacexRet = Tools::getValue('nacex_ret');

        $nacexTipSeg = Tools::getValue('nacex_tip_seg');
        $nacexImpSeg = Tools::getValue('nacex_imp_seg');

        $nacexPerEnt = Tools::getValue('nacex_per_ent');

        $tip_pre1 = Tools::getValue('nacex_tip_pre1');
        $pre1 = Tools::getValue('nacex_pre1');
        $mod_pre1 = Tools::getValue('nacex_mod_pre1');
        $prep1lus = Tools::getValue('nacex_pre1_plus');

        $q_r = Tools::getValue('inst_adi');
        $obs1 = Tools::getValue('nacex_obs1');
        $obs2 = Tools::getValue('nacex_obs2');

        // Intrernacional
        $ImpDeclarado = Tools::getValue('nacex_impDeclarado');
        $Contenido = Tools::getValue('nacex_contenido');

        //nacex_contenido OTROS coger valor nacex_descripcion_contenido

        if ($Contenido == 'OTROS'){
            $Contenidodescripcion = Tools::getValue('nacex_descripcion_contenido');
        }

        //Datos del pedido
        $datos = Db::getInstance()->executeS(
            'SELECT o.id_order,o.module,u.email,a.firstname,
				a.lastname,a.address1,a.address2,a.postcode,a.city,a.phone,a.phone_mobile,z.iso_code,
				case when o.total_paid_real > 0 
					then o.total_paid_real
				else
					o.total_paid
				end as total_paid_real
				FROM ' . _DB_PREFIX_ . 'orders AS o
				JOIN ' . _DB_PREFIX_ . 'customer AS u
				JOIN ' . _DB_PREFIX_ . 'address a
				JOIN ' . _DB_PREFIX_ . 'country AS z
				WHERE o.id_order = ' . (int)$id_pedido . '
				AND u.id_customer = o.id_customer
				AND a.id_address = o.id_address_delivery
				AND a.id_country = z.id_country'
        );
        nacexutils::writeNacexLog('putExpedicion :: obtenidos datos pedido BD id_order: ' . $datos[0]['id_order'] . ' | module: ' . $datos[0]['module'] . ' | total_paid_real: ' . $datos[0]['total_paid_real'] . ' | email: ' . $datos[0]['email'] . ' | firstname: ' . $datos[0]['firstname'] . ' | lastname ' . $datos[0]['lastname'] . ' | address1: ' . $datos[0]['address1'] . ' | postcode: ' . $datos[0]['postcode'] . ' | city: ' . $datos[0]['city'] . ' | phone: ' . $datos[0]['phone'] . ' | phone_mobile: ' . $datos[0]['phone_mobile'] . ' | iso_code: ' . $datos[0]['iso_code']);

        //Obtenemos más detalles del pedido, como el peso y las referencias
        $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = ' . (int)$id_pedido);

        //Miramos si hay que informar del retorno
        $xml_ret = (isset($nacexRet) && $nacexRet == 'SI') ? '<arrayOfString_3>ret=S</arrayOfString_3>' : '';

        $xml_seg = '';
        //Miramos si hay que informar del seguro
        if ((isset($nacexImpSeg) && $nacexImpSeg != '')) {
            $xml_seg = '<arrayOfString_3>tip_seg=' . $nacexTipSeg . '</arrayOfString_3>';
            $xml_seg .= '<arrayOfString_3>seg=' . number_format((float)str_replace(',', '.', str_replace('.', '', $nacexImpSeg)), 2, '.', '') . '</arrayOfString_3>';
        }

        // Peso del pedido
        //$peso = Tools::getValue('NACEX_PESO', 'C') == "F" ? str_replace(",", ".", str_replace(".", "", Configuration::get("NACEX_PESO_NUMERO"))) : 0;
        $peso = Configuration::get('NACEX_PESO') == 'F' ? (float)str_replace(',', '.', Configuration::get('NACEX_PESO_NUMERO')) : 0.0;
        if ($peso == 0) {
            foreach ($productospedido as $producto) {
                $peso += floatval($producto['product_quantity'] * $producto['product_weight']);
            } }
        $peso = max(1.0, (float)$peso);
        $peso = number_format($peso, 2, '.', '');

        // bultos del pedido, se recupera del formulario del usuario para permitir modificaciones
        $bultos = max(1, Tools::getValue('nacex_bul', 1));

        // Programar fecha de expedicion
        $program_fecha = Tools::getValue('nacex_fec', date('d/m/Y'));
        $program_fecha = date_format(date_create($program_fecha), 'd/m/Y');

        // referencias del pedido
        foreach ($productospedido as $producto) {
            $prodref = str_replace(';', ',', $producto['product_reference']);
        }

        //Variables NacexShop
        $shopData = self::resolveNacexShopData($datospedido);
        $is_nxshop = $shopData['is_nxshop'];
        $shop_codigo = $shopData['shop_codigo'];
        $shop_alias = $shopData['shop_alias'];
        $shop_nombre = $shopData['shop_nombre'];
        $shop_direccion = $shopData['shop_direccion'];
        $ncxshop = $shopData['ncxshop_xml'];
        $array_shop_data = $shopData['array_shop_data'];

        // Si es NacexShop la dirección sólo tiene que ser la 1, porque la otra es meramente informativa
        //Preparamos la dirección para sanearla
        if ($is_nxshop) { $nacex_nombre_via_destinatario = $datos[0]['address1']; } else { $nacex_nombre_via_destinatario = $datos[0]['address1'] . ' ' . $datos[0]['address2']; }

        $nacex_nombre_via_destinatario = trim(substr($nacex_nombre_via_destinatario, 0, 60));

        //TREAT ORDER DATA IN ORDER TO AVOID WEBSERVICE ISSUES
        $_tratar_datos = new tratardatos();
        $_tratar_datos->string($datos[0]['email']);
        $_tratar_datos->string($datos[0]['firstname']);
        $_tratar_datos->string($datos[0]['lastname']);
        $_tratar_datos->string($nacex_nombre_via_destinatario);
        $_tratar_datos->string($datos[0]['postcode']);
        $_tratar_datos->string($datos[0]['city']);
        $_tratar_datos->string($datos[0]['phone']);
        $_tratar_datos->string($datos[0]['phone_mobile']);
        $_tratar_datos->string($pre1);
        $_tratar_datos->string($prep1lus);
        $_tratar_datos->string($obs1);
        $_tratar_datos->string($obs2);
        $_tratar_datos->string($q_r);

        // Si la última letra de la dirección es un caracter especial, lo sustituimos por un espacio en blanco para no tener más problemas con símbolos
        //$nacex_nombre_via_destinatario = preg_match('/[^a-zA-Z\d]/', substr($nacex_nombre_via_destinatario, -1)) == 1 ? substr($nacex_nombre_via_destinatario, 0, -2) . ' ' : $nacex_nombre_via_destinatario;

        //Informarmos de las instrucciones adicionales
        $xml_insadi_qr = '';

        $array_insadi_qr = nacexutils::cutupString($q_r, 40, 15);
        for ($i = 0; $i < count($array_insadi_qr); $i++) {
            if ($array_insadi_qr [$i] != '') {
                $xml_insadi_qr .= '<arrayOfString_3><![CDATA[ins_adi';
                $xml_insadi_qr .= $i + 1;
                $xml_insadi_qr .= '=';
                $xml_insadi_qr .= $array_insadi_qr [$i];
                $xml_insadi_qr .= ']]></arrayOfString_3>';
            }
        }

        $xml_insadi_qr .= ($xml_insadi_qr != '') ? '<arrayOfString_3>ins_adi=S</arrayOfString_3>' : '';

        //Obtenemos el importe total del pedido
        $nacex_importe_servicio = $datos[0]['total_paid_real'];

        //Datos del comprador
        // Si es shop, los cambiamos por el nombre de la tienda
        if ($is_nxshop) { $nacex_nombre_destinatario = $shop_alias . ' ' . $shop_nombre; } else { $nacex_nombre_destinatario = $datos[0]['firstname'] . ' ' . $datos[0]['lastname']; }

        $nacex_poblacion_destinatario = $datos[0]['city'];
        $nacex_CP_destinatario = $datos[0]['postcode'];

        $tels = [
            $datos[0]['phone'],
            $datos[0]['phone_mobile']
        ];
        $nacex_telefono_destinatario = implode('/', array_unique(array_filter($tels)));

        // $nacex_email_destinatario = $datos[0]['email'];
        $nacex_pais = $datos[0]['iso_code'];

        //REEMBOLSO ---------------------------------------------------------------------------------------------------------------
        //Nombres módulos pago contra reembolso indicados en Configuración
        $array_modsree = explode('|', Configuration::get('NACEX_MODULOS_REEMBOLSO'));
        $nacex_reembolso = null;
        $metodo_pago = strtolower($datos[0]['module']);

        if (in_array($metodo_pago, $array_modsree) ||
            strpos($metodo_pago, 'cashondelivery') !== false ||
            intval(Tools::getValue('nacex_imp_ree')) > 0) {
            //WE ALWAYS GET THE VALUE FROM THE FORM
            $nacex_reembolso = Tools::getValue('nacex_imp_ree');
            //$nacex_reembolso = floatval($nacex_importe_servicio)==null? Tools::getValue('nacex_imp_ree'):floatval($nacex_importe_servicio);

            nacexutils::writeNacexLog('putExpedicion :: detectado modo de pago [' . $metodo_pago . '] expedicion y reembolso de ' . $nacex_reembolso);

            //Si estamos documentando un nacex cambio, debemos comprobar si el cliente ha informado de la cantidad de reembolso
            if ($isnacexcambio) {
                $nacex_reembolso = floatval(str_replace(',', '.', Tools::getValue('nacex_imp_ree')));
                if ($nacex_reembolso == 0) {
                    $nacex_reembolso = null;
                }
            }
        }

        $xml_ree = '';
        if (isset($nacex_reembolso)) {
            $xml_ree = '<arrayOfString_3>tip_ree=' . $nacexTipRee . '</arrayOfString_3>';
            $xml_ree .= '<arrayOfString_3>ree=' . $nacex_reembolso . '</arrayOfString_3>';
        }
        //REEMBOLSO ---------------------------------------------------------------------------------------------------------------

        //PREALERTA ---------------------------------------------------------------------------------------------------------------
        $xml_pre = '';
        if ($tip_pre1 != 'N') {
            $xml_pre .= '<arrayOfString_3>tip_pre1=' . $tip_pre1 . '</arrayOfString_3>';

            $xml_pre .= ($mod_pre1 == 'S' || $mod_pre1 == 'P' || $mod_pre1 == 'R' || $mod_pre1 == 'E') ?
                '<arrayOfString_3>mod_pre1=' . $mod_pre1 . '</arrayOfString_3>' :
                '<arrayOfString_3>mod_pre1=S</arrayOfString_3>';
            $xml_pre .= '<arrayOfString_3>pre1=' . substr($pre1, 0, 50) . '</arrayOfString_3>';

            $xml_pre .= ($mod_pre1 == 'P' || $mod_pre1 == 'E') && ($prep1lus && strlen($prep1lus) > 0) ?
                '<arrayOfString_3>msg1=' . substr($prep1lus, 0, 719) . '</arrayOfString_3>' :
                '';
        }
        //PREALERTA ---------------------------------------------------------------------------------------------------------------

        $xml_obs1 = isset($obs1) && $obs1 != '' ? '<arrayOfString_3><![CDATA[obs1=' . $obs1 . ']]></arrayOfString_3>' : '';
        $xml_obs2 = isset($obs2) && $obs2 != '' ? '<arrayOfString_3><![CDATA[obs2=' . $obs2 . ']]></arrayOfString_3>' : '';
        $xml_per_ent = isset($nacexPerEnt) && $nacexPerEnt != '' ? '<arrayOfString_3><![CDATA[per_ent=' . $nacexPerEnt . ']]></arrayOfString_3>' : '';

        //DEPARTAMENTOS DEL CLIENTE ----------------------------------------------------------------------------
        $dpto = Tools::getValue('nacex_departamentos', '');
        $xml_dpto = isset($dpto) && $dpto != '' ? '<arrayOfString_3>dep_cli=' . $dpto . '</arrayOfString_3>' : '';
        //DEPARTAMENTOS DEL CLIENTE ----------------------------------------------------------------------------

        //Internacional ----------------------------------------------------------------------------------------
        /**info:
         * RECOGEMOS EL VALOR DECLARADO SI SE TRATA DE UN INTERNACIONAL.
         */
        $xml_NacexImpDeclarado = '';
        $xml_NacexContenido = '';

        if (!in_array($nacex_pais, ['ES','PT','AD'])){

            $xml_NacexImpDeclarado = '<arrayOfString_3>val_dec=' . $ImpDeclarado . '</arrayOfString_3>';

            //CONTROL DE CONTENIDO OTROS PARA LA DECLARACIÓN DE MERCANCÍA

            if ($Contenido == 'OTROS'){

                $xml_NacexContenido = '<arrayOfString_3><![CDATA[con=' . $Contenidodescripcion . ']]></arrayOfString_3>';

            } else {

                $xml_NacexContenido = '<arrayOfString_3>con=' . $Contenido . '</arrayOfString_3>';

            }
        }

        /* $xml_NacexImpDeclarado = in_array($nacex_pais, array ('ES','PT','AD'))&& isset($ImpDeclarado) ?
                                                     "<arrayOfString_3>val_dec=" . $nacex_importe_servicio . "</arrayOfString_3>":
                                                     "<arrayOfString_3>val_dec=" . $ImpDeclarado . "</arrayOfString_3>";
        */

        // Añadimos el str-replace para quitar los - del CP, por ejemplo, de Portugal.
        $nacex_CP_destinatario = str_replace('-', '', $is_nxshop ? $nacex_CP_destinatario : substr($nacex_CP_destinatario, 0, 15));

        //Internacional ----------------------------------------------------------------------------------------

        $metodo = 'putExpedicion';

        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
				<typ:putExpedicion>
					<String_1>' . $nacexWSusername . '</String_1>
					<String_2>' . $nacexWSpassword . '</String_2>
					<arrayOfString_3>del_cli=' . $nacexCodigoAgencia . '</arrayOfString_3>
					<arrayOfString_3>num_cli=' . $nacexCodigoCliente . '</arrayOfString_3>
					<arrayOfString_3>fec=' . $program_fecha . '</arrayOfString_3>' .
            $xml_dpto . '
					<arrayOfString_3>tip_ser=' . $nacexTipSer . '</arrayOfString_3>
					<arrayOfString_3>tip_cob=' . $nacexTipCob . '</arrayOfString_3>
					<arrayOfString_3>tip_env=' . $nacexTipEnv . '</arrayOfString_3>' .
            $xml_ree . '' .
            $xml_pre . '' .
            $xml_obs1 . '' .
            $xml_obs2 . '' .
            $xml_per_ent . '
					<arrayOfString_3>ref_cli=' . $nacexReferencia . '</arrayOfString_3>					
					<arrayOfString_3>bul=' . $bultos . '</arrayOfString_3>
					<arrayOfString_3>kil=' . $peso . '</arrayOfString_3>
					<arrayOfString_3><![CDATA[nom_ent=' . substr($nacex_nombre_destinatario, 0, 50) . ']]></arrayOfString_3>
					<arrayOfString_3><![CDATA[dir_ent=' . $nacex_nombre_via_destinatario . ']]></arrayOfString_3>
					<arrayOfString_3>pais_ent=' . $nacex_pais . '</arrayOfString_3>					
					<arrayOfString_3>cp_ent=' . substr($nacex_CP_destinatario, 0, 15) . '</arrayOfString_3>
					<arrayOfString_3>pob_ent=' . substr($nacex_poblacion_destinatario, 0, 40) . '</arrayOfString_3>
					<arrayOfString_3>tel_ent=' . substr($nacex_telefono_destinatario, 0, 20) . '</arrayOfString_3>' .
            $ncxshop .
            $xml_insadi_qr .
            $xml_ret .
            $xml_seg .
            $xml_NacexImpDeclarado .
            $xml_NacexContenido .
            $xml_NacexFrecuencia . ' 
                    <arrayOfString_3>' . nacexWS::getSystemInfo() . '</arrayOfString_3>                                             
                </typ:putExpedicion>
			</soapenv:Body>
		</soapenv:Envelope>';

        nacexutils::writeNacexLog('putExpedicion :: XML: ' . $XML);
        $errorplain = false;
        $postResult = $nacexWS->requestWS($URL, $XML, $metodo, $style = 'width:auto;', $errorplain);
        if ($_unitaria == true) { $_response = ''; }
        if ($postResult[0] == 'ERROR') {
            if ($_unitaria == false) { return $postResult; } else {
                nacexutils::print_messages($_response, 'ERROR', $postResult);
                return $_response;
            }
        }
        $resultado =  $nacexWS->treatmentXML($postResult, $metodo);
        if ($resultado[0] == 'ERROR') {
            //echo '<br><div style="text-align:left;width:396px" class="alert error"><img src="' . $nacexDTO->getPath() . 'logo.gif" />';
            nacexutils::writeNacexLog('FIN putExpedicion :: id_pedido: ' . $id_pedido . "\n----");
            if ($_unitaria == false) {
                $return = '<br><div id="messages-nacex" class="bootstrap" style="margin-top:10px">';
                $return .= '<div class="alert alert-danger conf" style="width:auto">';
                foreach ($resultado as $res) {
                    $return .= '<strong>' . $res . '</strong><br/>';
                    nacexutils::writeNacexLog('putExpedicion :: ERROR =>' . $res);
                }
                $return .= '</div></div>';
                nacexutils::writeNacexLog('FIN putExpedicion :: id_pedido: ' . $id_pedido . "\n----");
                //return $this->_html;(mexpositop 20171127)
                //return false;
            } else {
                $_return = [];
                nacexutils::print_messages($_response, 'ERROR', $resultado[1]);
                nacexutils::writeNacexLog('putExpedicion :: ERROR =>' . $resultado[1]);
                array_push($_return, ['cod_response' => '400',
                    'result' => $_response
                ]);
            }
        } else {

            $putExpedicionResponse = [];
            $putExpedicionResponse['exp_cod'] = $resultado[0];
            $putExpedicionResponse['ag_cod_num_exp'] = $resultado[1];
            $putExpedicionResponse['color'] = $resultado[2];
            $putExpedicionResponse['ent_ruta'] = $resultado[3];
            $putExpedicionResponse['ent_cod'] = $resultado[4];
            $putExpedicionResponse['ent_nom'] = $resultado[5];
            $putExpedicionResponse['ent_tlf'] = $resultado[6];
            $putExpedicionResponse['serv'] = $resultado[7];
            $putExpedicionResponse['hora_entrega'] = $resultado[8];
            $putExpedicionResponse['barcode'] = $resultado[9];
            $putExpedicionResponse['fecha_objetivo'] = $resultado[10];
            $putExpedicionResponse['cambios'] = $resultado[11];

            $putExpedicionResponse['shop_codigo'] = $shop_codigo;
            $putExpedicionResponse['shop_alias'] = $shop_alias;
            $putExpedicionResponse['shop_nombre'] = $shop_nombre;
            $putExpedicionResponse['shop_direccion'] = $shop_direccion;

            $putExpedicionResponse['ret'] = $nacexRet;

            $putExpedicionResponse['ref'] = $nacexReferencia;

            nacexutils::writeNacexLog('putExpedicion :: recibido putExpedicionResponse: exp_cod: ' . $putExpedicionResponse['exp_cod'] . '| ag_cod_num_exp: ' . $putExpedicionResponse['ag_cod_num_exp'] . '| color: ' . $putExpedicionResponse['color'] . '| ent_ruta: ' . $putExpedicionResponse['ent_ruta'] . '| ent_cod: ' . $putExpedicionResponse['ent_cod'] . '| ent_nom: ' . $putExpedicionResponse['ent_nom'] . '| ent_tlf: ' . $putExpedicionResponse['ent_tlf'] . '| serv: ' . $putExpedicionResponse['serv'] . '| hora_entrega: ' . $putExpedicionResponse['hora_entrega'] . '| barcode: ' . $putExpedicionResponse['barcode'] . '| fecha_objetivo: ' . $putExpedicionResponse['fecha_objetivo'] . '| cambios: ' . $putExpedicionResponse['cambios'] . '| shop_codigo: ' . $putExpedicionResponse['shop_codigo'] . '| shop_alias: ' . $putExpedicionResponse['shop_alias'] . '| shop_nombre: ' . $putExpedicionResponse['shop_nombre'] . '| shop_direccion: ' . $putExpedicionResponse['shop_direccion'] . '| ret: ' . $putExpedicionResponse['ret']);
            nacexutils::writeNacexLog('putExpedicion :: guardamos expedicion en BD prestashop');

            nacexDAO::guardarExpedicion($nacex_agcli, $id_pedido, $putExpedicionResponse, $bultos, $array_shop_data, $nacexRet, $nacexTipSer, $nacex_reembolso);
            //  nacexView::showExpedicionBoxInfo($putExpedicionResponse, $id_pedido, $solicitud);

            nacexDAO::actualizarTrackingExpedicion($id_pedido, $resultado[1]);

            /*** Cambio de estado al pedido al documentar expedición ***/
            nacexutils::writeNacexLog('putExpedicion :: Cambiamos el estado del pedido');
            nacexDAO::actualizaEstadoPedido($id_pedido, 'd');

            // Añadimos el código de la expedición que se acaba de hacer a POST
            $_POST['expe_codigo'] = $putExpedicionResponse['exp_cod'];

            nacexutils::writeNacexLog('FIN putExpedicion :: id_pedido: ' . $id_pedido . "\n----");
            //echo '<br><div style="text-align:left;width:396px" class="alert error"><img src="' . $nacexDTO->getPath() . 'logo.gif" />';
            if ($_unitaria == false) {
                $return = '<br><div id="messages-nacex" class="bootstrap" style="margin-top:10px">';
                $return .= '<div class="alert alert-success conf" style="width:auto">';
                $return .= '<strong>' . $nacex->l('Expedition created successfully') . '</strong>';
                $return .= '</div></div>';
            } else {
                $return = [];
                nacexutils::print_messages($_response, 'SUCCESS', $nacex->l('Expedition created successfully'));
                array_push($return, ['cod_response' => '200',
                    'result' => $_response
                ]);
            }
        }
        return $return;
    }

    public static function putExpedicionMasivo($id_pedido, $data = []) {

        $datospedido = nacexDAO::getDatosPedido($id_pedido);
        $nacexWS = new nacexWS();
        $nacex = new nacex();

        nacexutils::writeNacexLog('----');
        nacexutils::writeNacexLog('INI putExpedicionMasivo :: id_order: ' . $datospedido[0]['id_order'] . '| module: ' . $datospedido[0]['module'] . '| total_paid_real: ' . $datospedido[0]['total_paid_real'] . '| email: ' . $datospedido[0]['email'] . '| firstname: ' . $datospedido[0]['firstname'] . '| lastname: ' . $datospedido[0]['lastname'] . '| address1: ' . $datospedido[0]['address1'] . '| postcode: ' . $datospedido[0]['postcode'] . '| city: ' . $datospedido[0]['city'] . '| phone: ' . $datospedido[0]['phone'] . '| phone_mobile: ' . $datospedido[0]['phone_mobile'] . '| iso_code: ' . $datospedido[0]['iso_code'] . '| ncx: ' . $datospedido[0]['ncx'] . '| id_carrier: ' . $datospedido[0]['id_carrier']);

        $urlNacex = Configuration::get('NACEX_WS_URL');
        $URL = $urlNacex . '/soap';
        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');

        $nacexInsAdi_qr = Configuration::get('NACEX_INS_ADI_Q_R');

        $nacexCodigoAgencia = $data['nacex_agencia'];
        $nacexCodigoCliente = $data['nacex_cliente'];
        $nacexTipSer = $data['nacex_tip_ser'];
        $xml_NacexFrecuencia = $nacexTipSer == '09' ? '<arrayOfString_3>frec_codigo=' . Tools::getValue('nacex_frecuencia') . '</arrayOfString_3>' : '';
        $nacexReferencia = nacexutils::getReferenciaGeneral() . $id_pedido;
        $nacexTipCob = $data['nacex_tip_cob'];
        $nacexTipRee = $data['nacex_tip_ree'];
        $nacexTipEnv = $data['nacex_tip_env'];
        $nacexRet = $data['nacex_ret'];
        $nacexTipSeg = $data['nacex_tip_seg'];
        $nacexImpSeg = $data['nacex_imp_seg'];
        $bul = $data['nacex_bul'];
        $program_fecha = $data['nacex_fec'];
        $tip_pre1 = $data['nacex_tip_pre1'];
        //$nacexPerEnt = Tools::getValue('nacex_per_ent');
        $pre1 = Tools::getValue('nacex_pre1');
        $mod_pre1 = $data['nacex_mod_pre1'];
        $prep1lus = $data['nacex_pre1_plus'];
        $obs1 = $data['obs1'];
        $obs2 = $data['obs2'];

        // Internacional
        $ImpDeclarado = Tools::getValue('nacex_impDeclarado');
        $Contenido = Tools::getValue('nacex_contenido');

        //Datos del pedido
        $datos = nacexDAO::getDetallepedido($id_pedido);

        nacexutils::writeNacexLog('putExpedicionMasivo :: obtenidos datos pedido BD id_order: ' . $datos[0]['id_order'] . ' | module: ' . $datos[0]['module'] . ' | total_paid_real: ' . $datos[0]['total_paid_real'] . ' | email: ' . $datos[0]['email'] . ' | firstname: ' . $datos[0]['firstname'] . ' | lastname ' . $datos[0]['lastname'] . ' | address1: ' . $datos[0]['address1'] . ' | postcode: ' . $datos[0]['postcode'] . ' | city: ' . $datos[0]['city'] . ' | phone: ' . $datos[0]['phone'] . ' | phone_mobile: ' . $datos[0]['phone_mobile'] . ' | iso_code: ' . $datos[0]['iso_code']);

        //INSTRUCCIONES ADICIONALES Y REFERENCIAS ------------------------------------------------------------------------------
        $instrucciones = '';

        //Obtenemos m�s detalles del pedido, como el peso y las referencias
        $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference, total_price_tax_incl
																										FROM ' . _DB_PREFIX_ . 'order_detail
																										WHERE id_order = ' . (int)$id_pedido);
        $peso = 0;
        $bultos = 0;
        $valor = 0;
        $q_r = '';

        $peso = Configuration::get('NACEX_PESO') == 'F' ? Configuration::get('NACEX_PESO_NUMERO') : 0;
        if ($peso == 0) {
            foreach ($productospedido as $producto) {
                $peso += floatval($producto['product_quantity'] * $producto['product_weight']);
                $bultos += $producto['product_quantity'];
                $prodref = str_replace(';', ',', $producto['product_reference']);
                $valor += floatval($producto['total_price_tax_incl']);
                $q_r .= ' ** ' . $producto['product_quantity'] . ' # ' . $prodref;
            } }
        $peso = max(1.0, (float)$peso);
        $peso = number_format($peso, 2, '.', '');

        /*foreach ($productospedido as $producto) {
            $peso += floatval($producto['product_quantity'] * $producto['product_weight']);
            $bultos += $producto['product_quantity'];
            $prodref = str_replace(";", ",", $producto['product_reference']);
            $valor+= floatval($producto['total_price_tax_incl']);
            $q_r .= " ** " . $producto['product_quantity'] . " # " . $prodref;
        }*/
        $xml_insadi_qr = '';

        /* Programar fecha de entrega */
        $program_fecha = date_format(date_create($program_fecha), 'd/m/Y');

        //Miramos si hay que informar cantidad y referencia en Instrucciones Adicionales:
        if (isset($nacexInsAdi_qr) && $nacexInsAdi_qr == 'SI') {
            $instrucciones .= $q_r . ' ' . $data['inst_adi'];
        } else {
            $instrucciones .= $data['inst_adi'];
        }
        //TREAT ORDER DATA IN ORDER TO AVOID WEBSERVICE ISSUES
        $_tratar_datos = new tratardatos();
        $_tratar_datos->string($datos[0]['email']);
        $_tratar_datos->string($datos[0]['firstname']);
        $_tratar_datos->string($datos[0]['lastname']);
        $_tratar_datos->string($datos[0]['address1']);
        $_tratar_datos->string($datos[0]['address2']);
        $_tratar_datos->string($datos[0]['postcode']);
        $_tratar_datos->string($datos[0]['city']);
        $_tratar_datos->string($datos[0]['phone']);
        $_tratar_datos->string($datos[0]['phone_mobile']);
        $_tratar_datos->string($pre1);
        $_tratar_datos->string($prep1lus);
        $_tratar_datos->string($obs1);
        $_tratar_datos->string($obs2);
        $_tratar_datos->string($instrucciones);

        $array_insadi_qr = nacexutils::cutupString($instrucciones, 40, 15);
        $boolinsadiqr = false;
        $iqr = 1;
        foreach ($array_insadi_qr as $qr) {
            if ($qr) {
                $xml_insadi_qr .= '<arrayOfString_3><![CDATA[ins_adi' . $iqr . '=' . $qr . ']]></arrayOfString_3>';
                $boolinsadiqr = true;
                $iqr++;
            }
        }

        if ($boolinsadiqr) {
            $xml_insadi_qr .= '<arrayOfString_3>ins_adi=S</arrayOfString_3>';
        }
        //INSTRUCCIONES ADICIONALES Y REFERENCIAS ------------------------------------------------------------------------------
        //ENVIO CON RETORNO ----------------------------------------------------------------------------------------------------
        $xml_ret = '';
        //Miramos si hay que informar del retorno
        if ((isset($nacexRet) && $nacexRet == 'SI')) {
            $xml_ret = '<arrayOfString_3>ret=S</arrayOfString_3>';
        }
        //ENVIO CON RETORNO ----------------------------------------------------------------------------------------------------
        //ENVIO CON SEGURO -----------------------------------------------------------------------------------------------------
        $xml_seg = '';
        //Miramos si hay que informar del seguro
        if ((isset($nacexImpSeg) && $nacexImpSeg != '')) {
            $xml_seg = '<arrayOfString_3>tip_seg=' . $nacexTipSeg . '</arrayOfString_3>';
            $nacexImpSeg = str_replace('.', '', $nacexImpSeg);
            $nacexImpSeg = str_replace(',', '.', $nacexImpSeg);
            $importe = number_format((float)$nacexImpSeg, 2, '.', '');
            $xml_seg .= '<arrayOfString_3>seg=' . $importe . '</arrayOfString_3>';
        }
        //ENVIO CON SEGURO -----------------------------------------------------------------------------------------------------
        //KG -------------------------------------------------------------------------------------------------------------------
        if (Configuration::get('NACEX_PESO') == 'F') {
            $peso = Configuration::get('NACEX_PESO_NUMERO');
            $peso = str_replace('.', '', $peso);
            $peso = str_replace(',', '.', $peso);
        } else {
            if ($peso < 1) {
                $peso = 1;
            }
        }
        //KG -------------------------------------------------------------------------------------------------------------------
        //Datos del comprador
        $nacex_nombre_destinatario = $datos[0]['firstname'] . ' ' . $datos[0]['lastname'];
        $nacex_nombre_via_destinatario = $datos[0]['address1'];
        $nacex_poblacion_destinatario = $datos[0]['city'];
        $nacex_CP_destinatario = $datos[0]['postcode'];

        $tlf = '';
        if (isset($datos[0]['phone']) && $datos[0]['phone'] != '') {
            $tlf = $datos[0]['phone'];
            if (isset($datos[0]['phone_mobile']) && $datos[0]['phone_mobile'] != '') {
                $tlf = $tlf . '/';
            }
        }
        if (isset($datos[0]['phone_mobile'])) {
            $tlf = $tlf . $datos[0]['phone_mobile'];
        }

        $nacex_telefono_destinatario = $tlf;
        $nacex_email_destinatario = $datos[0]['email'];
        $nacex_pais = $datos[0]['iso_code'];

        //PREALERTA --------------------------------------------------------------------------------------------------------------
        $xml_pre = '';

        if ($tip_pre1 != 'N') {

            if ($tip_pre1 == 'S') {
                !empty($datos[0]['phone_mobile']) ? $pre1 = $datos[0]['phone_mobile'] : $pre1 = $datos[0]['phone'];
            } elseif ($tip_pre1 == 'E') {
                $pre1 = $datos[0]['email'];
            }

            $xml_pre = '<arrayOfString_3>tip_pre1=' . $tip_pre1 . '</arrayOfString_3>';
            if ($mod_pre1 == 'S' || $mod_pre1 == 'P' || $mod_pre1 == 'R' || $mod_pre1 == 'E') {
                $xml_pre .= '<arrayOfString_3>mod_pre1=' . $mod_pre1 . '</arrayOfString_3>';
            } else {
                $xml_pre .= '<arrayOfString_3>mod_pre1=S</arrayOfString_3>';
            }
            $xml_pre .= '<arrayOfString_3>pre1=' . substr($pre1, 0, 50) . '</arrayOfString_3>';
            if (($mod_pre1 == 'P' || $mod_pre1 == 'E') && ($prep1lus && strlen($prep1lus) > 0)) {
                $xml_pre .= '<arrayOfString_3>msg1=' . substr($prep1lus, 0, 719) . '</arrayOfString_3>';
            }
        }
        //PREALERTA --------------------------------------------------------------------------------------------------------------
        //OBSERVACIONES ----------------------------------------------------------------------------------------------------------
        $xml_obs1 = '';
        if (isset($obs1) && $obs1 != '') {
            $xml_obs1 = '<arrayOfString_3><![CDATA[obs1=' . $obs1 . ']]></arrayOfString_3>';
        }
        $xml_obs2 = '';
        if (isset($obs2) && $obs2 != '') {
            $xml_obs2 = '<arrayOfString_3><![CDATA[obs2=' . $obs2 . ']]></arrayOfString_3>';
        }
        //OBSERVACIONES ----------------------------------------------------------------------------------------------------------

        $xml_per_ent = '';
        if (isset($nacexPerEnt) && $nacexPerEnt != '') {
            $xml_per_ent = '<arrayOfString_3>per_ent=' . $nacexPerEnt . '</arrayOfString_3>';
        }

        //DEPARTAMENTOS DEL CLIENTE ----------------------------------------------------------------------------
        $xml_dpto = '';
        $dpto = Tools::getValue('nacex_departamentos');
        if (isset($dpto) && $dpto != false) {
            $xml_dpto = '<arrayOfString_3>dep_cli=' . $dpto . '</arrayOfString_3>';
        }
        //DEPARTAMENTOS DEL CLIENTE ----------------------------------------------------------------------------
        //Internacional ----------------------------------------------------------------------------------------
        $xml_NacexImpDeclarado = '';
        $xml_NacexContenido = '';
        if ($nacex_pais != 'ES' && $nacex_pais != 'PT' && $nacex_pais != 'AD') {

            if (isset($ImpDeclarado) && $ImpDeclarado != false) {
                $xml_NacexImpDeclarado = '<arrayOfString_3>val_dec=' . $ImpDeclarado . '</arrayOfString_3>';
            } else {
                $xml_NacexImpDeclarado = '<arrayOfString_3>val_dec=' . $valor . '</arrayOfString_3>';
            }

            if (isset($Contenido) && $Contenido != false) {
                $xml_NacexContenido = '<arrayOfString_3>con=' . $Contenido . '</arrayOfString_3>';
            } else {
                $xml_NacexContenido = '<arrayOfString_3>con=OTROS</arrayOfString_3>';
            }
        }
        //Internacional ----------------------------------------------------------------------------------------

        //Variables NacexShop
        $shopData = self::resolveNacexShopData($datospedido);
        $is_nxshop = $shopData['is_nxshop'];
        $shop_codigo = $shopData['shop_codigo'];
        $shop_alias = $shopData['shop_alias'];
        $shop_nombre = $shopData['shop_nombre'];
        $shop_direccion = $shopData['shop_direccion'];
        $ncxshop = $shopData['ncxshop_xml'];
        $array_shop_data = $shopData['array_shop_data'];
        //Variables NacexShop --------------------------------------------------------------------------------------------------------

        //PAGO CONTRA REEMBOLSO --------------------------------------------------------------------------------------------------
        //Nombres módulos pago contra reembolso indicados en Configuraci�n
        $array_modsree = [];
        $array_modsree = explode('|', Configuration::get('NACEX_MODULOS_REEMBOLSO'));

        //Obtenemos el importe total del pedido
        $nacex_importe_servicio = $datos[0]['total_paid_real'];
        $nacex_reembolso = null;

        $metodo_pago = strtolower($datos[0]['module']);
        if (in_array($metodo_pago, $array_modsree) || strpos($metodo_pago, 'cashondelivery') !== false) {
            $nacex_reembolso = floatval($nacex_importe_servicio);
            nacexutils::writeNacexLog('putExpedicionMasivo :: detectado modo de pago [' . $metodo_pago . '] expedicion y reembolso de ' . $nacex_reembolso);
        }
        $xml_ree = '';
        if (isset($nacex_reembolso)) {
            // Si es NACEXSHOP el Reembolso tiene que ser máximo de 600€
            if ($is_nxshop) { $nacex_reembolso = $nacex_reembolso > 600 ? 600 : $nacex_reembolso; }
            $xml_ree = '<arrayOfString_3>tip_ree=' . $nacexTipRee . '</arrayOfString_3>';
            $xml_ree .= '<arrayOfString_3>ree=' . $nacex_reembolso . '</arrayOfString_3>';
        }
        //PAGO CONTRA REEMBOLSO --------------------------------------------------------------------------------------------------

        $metodo = 'putExpedicion';
        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
			<typ:putExpedicion>
			<String_1>' . $nacexWSusername . '</String_1>
			<String_2>' . $nacexWSpassword . '</String_2>
			<arrayOfString_3>del_cli=' . $nacexCodigoAgencia . '</arrayOfString_3>
			<arrayOfString_3>num_cli=' . $nacexCodigoCliente . '</arrayOfString_3>
			<arrayOfString_3>fec=' . $program_fecha . '</arrayOfString_3>
			<arrayOfString_3>tip_ser=' . $nacexTipSer . '</arrayOfString_3>
			<arrayOfString_3>tip_cob=' . $nacexTipCob . '</arrayOfString_3>
			<arrayOfString_3>tip_env=' . $nacexTipEnv . '</arrayOfString_3>
			' . $xml_ree . '
			' . $xml_pre . '
			' . $xml_obs1 . '
			' . $xml_obs2 . '
			' . $xml_per_ent . '
			<arrayOfString_3>ref_cli=' . $nacexReferencia . '</arrayOfString_3>					
			<arrayOfString_3>bul=' . $bul . '</arrayOfString_3>
			<arrayOfString_3>kil=' . $peso . '</arrayOfString_3>
			<arrayOfString_3><![CDATA[nom_ent=' . substr($nacex_nombre_destinatario, 0, 50) . ']]></arrayOfString_3>
			<arrayOfString_3><![CDATA[dir_ent=' . substr($nacex_nombre_via_destinatario, 0, 60) . ']]></arrayOfString_3>
			<arrayOfString_3>pais_ent=' . $nacex_pais . '</arrayOfString_3>					
			<arrayOfString_3>cp_ent=' . substr($nacex_CP_destinatario, 0, 15) . '</arrayOfString_3>
			<arrayOfString_3>pob_ent=' . substr($nacex_poblacion_destinatario, 0, 40) . '</arrayOfString_3>
			<arrayOfString_3>tel_ent=' . substr($nacex_telefono_destinatario, 0, 20) . '</arrayOfString_3>
			' . $ncxshop . '
			' . $xml_insadi_qr . '
			' . $xml_ret . '
			' . $xml_seg . '
			' . $xml_NacexImpDeclarado . '
			' . $xml_NacexContenido . ' 										
			' . $xml_NacexFrecuencia . '
			<arrayOfString_3>' . nacexWS::getSystemInfo() . '</arrayOfString_3>
			</typ:putExpedicion>
			</soapenv:Body>
			</soapenv:Envelope>';

        nacexutils::writeNacexLog('putExpedicionMasivo :: XML: ' . $XML);

        $errorplain = false;
        $postResult = $nacexWS->requestWS($URL, $XML, $metodo, $style = 'width:auto;', $errorplain);
        if ($postResult[0] == 'ERROR') {
            return $postResult;
        }
        $resultado =  $nacexWS->treatmentXML($postResult, $metodo);

        if ($resultado[0] == 'ERROR') {
            $nacex_response = ['tipo' => 'ERROR',
                'msg' => '[' . $nacex->l('Order') . ' ' . $id_pedido . ']: ' . $nacex->l('Error on generating expedition') . ': - ' . $resultado[1]];
        } else {

            $putExpedicionResponse = [];
            $putExpedicionResponse['exp_cod'] = $resultado[0];
            $putExpedicionResponse['ag_cod_num_exp'] = $resultado[1];
            $putExpedicionResponse['color'] = $resultado[2];
            $putExpedicionResponse['ent_ruta'] = $resultado[3];
            $putExpedicionResponse['ent_cod'] = $resultado[4];
            $putExpedicionResponse['ent_nom'] = $resultado[5];
            $putExpedicionResponse['ent_tlf'] = $resultado[6];
            $putExpedicionResponse['serv'] = $resultado[7];
            $putExpedicionResponse['hora_entrega'] = $resultado[8];
            $putExpedicionResponse['barcode'] = $resultado[9];
            $putExpedicionResponse['fecha_objetivo'] = $resultado[10];
            $putExpedicionResponse['cambios'] = $resultado[11];

            $putExpedicionResponse['shop_codigo'] = $shop_codigo;
            $putExpedicionResponse['shop_alias'] = $shop_alias;
            $putExpedicionResponse['shop_nombre'] = $shop_nombre;
            $putExpedicionResponse['shop_direccion'] = $shop_direccion;

            $putExpedicionResponse['ret'] = $nacexRet;

            $putExpedicionResponse['ref'] = $nacexReferencia;

            nacexutils::writeNacexLog('putExpedicionMasivo :: recibido putExpedicionResponse: exp_cod: ' . $putExpedicionResponse['exp_cod'] . '| ag_cod_num_exp: ' . $putExpedicionResponse['ag_cod_num_exp'] . '| color: ' . $putExpedicionResponse['color'] . '| ent_ruta: ' . $putExpedicionResponse['ent_ruta'] . '| ent_cod: ' . $putExpedicionResponse['ent_cod'] . '| ent_nom: ' . $putExpedicionResponse['ent_nom'] . '| ent_tlf: ' . $putExpedicionResponse['ent_tlf'] . '| serv: ' . $putExpedicionResponse['serv'] . '| hora_entrega: ' . $putExpedicionResponse['hora_entrega'] . '| barcode: ' . $putExpedicionResponse['barcode'] . '| fecha_objetivo: ' . $putExpedicionResponse['fecha_objetivo'] . '| cambios: ' . $putExpedicionResponse['cambios'] . '| ret: ' . $putExpedicionResponse['ret']);
            nacexutils::writeNacexLog('putExpedicionMasivo :: guardamos expedicion en BD prestashop');

            $nacex_agcli = $nacexCodigoAgencia . '/' . $nacexCodigoCliente;
            $resultDB = nacexDAO::guardarExpedicion($nacex_agcli, $id_pedido, $putExpedicionResponse, $bul, null, $nacexRet, $nacexTipSer, $nacex_reembolso);

            if (!$resultDB) {
                $nacex_response = ['tipo' => 'ERROR',
                    'msg' => '[' . $nacex->l('Order') . ' ' . $id_pedido . ']: ' . $nacex->l('Error on saving expedition') . ': - ' . $resultado[1]];
            } else {
                nacexDAO::actualizarTrackingExpedicion($id_pedido, $resultado[1]);
                $nacex_response = ['tipo' => 'SUCCESS',
                    'msg' => '[' . $nacex->l('Order') . ' ' . $id_pedido . ']: ' . $nacex->l('Expedition generated successfully') . ': - ' . $resultado[1]];

                /*** Cambio de estado al pedido al documentar expedición ***/
                nacexutils::writeNacexLog('putExpedicionMasivo :: Cambiamos el estado del pedido');
                nacexDAO::actualizaEstadoPedido($id_pedido, 'd');
            }
        }

        nacexutils::writeNacexLog('FIN putExpedicionMasivo :: id_pedido: ' . $id_pedido . "\n----");

        return $nacex_response;
    }

    public static function checkGetEtiqueta($modelo, $cod_exp)
    {
        nacexutils::writeNacexLog("----\nINI checkGetEtiqueta :: cod_exp: " . $cod_exp);

        $nacexWS = new nacexWS();

        $urlNacex = Configuration::get('NACEX_WS_URL');
        $URL = $urlNacex . '/soap';

        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');

        $metodo = 'getEtiqueta';
        $XML = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
					<soapenv:Header/>
					<soapenv:Body>
						<typ:getEtiqueta>
							<String_1>' . $nacexWSusername . '</String_1>
							<String_2>' . $nacexWSpassword . '</String_2>
							<String_3>' . $cod_exp . '</String_3>
							<String_4>' . $modelo . '</String_4>
						</typ:getEtiqueta>
					</soapenv:Body>
				</soapenv:Envelope>';

        nacexutils::writeNacexLog('getEtiqueta :: XML: ' . $XML);

        $postResult = $nacexWS->requestWS($URL, $XML, $metodo);
        if ($postResult[0] == 'ERROR') {
            return $postResult;
        }
        $resultado = $nacexWS->treatmentXML($postResult, $metodo);

        if ($resultado[0] == 'ERROR') {
            return false;
        }

        nacexutils::writeNacexLog("FIN checkGetEtiqueta\n----");
        return true;
    }

    public function treatmentXML($postResult, $metodo)
    {
        if (!is_numeric($postResult)) {
            try {
                $xml = new SimpleXMLElement($postResult);
                $xml->registerXPathNamespace('ns0', 'urn:soap/types');
                $result = $xml->xpath('//ns0:' . $metodo . 'Response');
                if (empty($result) || !isset($result[0]->result)) {
                    nacexutils::writeNacexLog('treatmentXML :: Respuesta vacia para ' . $metodo);
                    return ['ERROR', 'Respuesta inesperada del WS'];
                }
                return (array) $result[0]->result;
            } catch (\Exception $e) {
                nacexutils::writeNacexLog('treatmentXML :: Error XML: ' . $e->getMessage());
                return ['ERROR', 'Error al procesar respuesta del WS'];
            }
        } else {
            nacexutils::writeNacexLog('Ha habido un error de comunicación con el WS:: ' . $metodo);
            return ['500ERROR'];
        }
    }

    private function requestWS($URL, $XML, $metodo, $style = 'text-align:left;width:396px;', $errorplain = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $XML);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PrestaShop-Nacex/' . nacexutils::nacexVersion . ' (PHP/' . phpversion() . ')');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml; charset=utf-8']);

        $postResult = curl_exec($ch);

        // Cojo la info de la llamada de la página para ver si hay un errores
        $info = curl_getinfo($ch);

        //if (($postResult == false && $info['http_code'] == 0) || (preg_match('/5\w{2}/', $info['http_code']) == 1))
        if (($postResult == false && $info['http_code'] == 0)) {
            return 500; }

        if (curl_errno($ch)) {
            $str = $errorplain ? '[Error cURL ' . curl_errno($ch) . ']: ' . curl_error($ch) :
                '<br>
			<div style="' . $style . '" class="alert error ncx_network_error">
				<h1>Error de comunicación con el Web Service</h1>
				<div class="metodo" align="center">' . $metodo . '</div>
				<div class="descripcion">[Error cURL ' . curl_errno($ch) . ']: ' . curl_error($ch) . '</div>
			</div>';
            nacexutils::writeNacexLog($metodo . ' :: [Error cURL ' . curl_errno($ch) . ' ]: ' . curl_error($ch));
            return ['ERROR', $str];
        }
        return $postResult;
    }
    /**
     * Resuelve las variables NacexShop a partir de los datos del pedido.
     * Si ncx solo contiene el código del punto, busca los datos completos en el fichero CSV.
     */
    public static function resolveNacexShopData($datospedido)
    {
        $result = [
            'is_nxshop' => false,
            'shop_codigo' => '',
            'shop_alias' => '',
            'shop_nombre' => '',
            'shop_direccion' => '',
            'ncxshop_xml' => null,
            'array_shop_data' => [],
        ];

        if ($datospedido[0]['ncx'] != '1' && nacexDTO::isNacexShopCarrier($datospedido[0]['id_carrier'])) {
            $result['is_nxshop'] = true;
            $ncx = $datospedido[0]['ncx'];
            $parts = explode('|', $ncx);

            // Si solo tenemos el código, buscar datos completos en el fichero CSV
            if (count($parts) < 4) {
                $ROnacexshop = new nacexshop();
                $data = $ROnacexshop->getFileData($parts[0], true);
                if (!empty($data[0])) {
                    $parts = explode('|', nacexutils::toUtf8($data[0]));
                }
            }

            $result['shop_codigo'] = isset($parts[0]) ? $parts[0] : '';
            $result['shop_alias'] = isset($parts[1]) ? $parts[1] : '';
            $result['shop_nombre'] = isset($parts[2]) ? $parts[2] : '';
            $result['shop_direccion'] = isset($parts[3]) ? $parts[3] : '';
            $result['ncxshop_xml'] = '<arrayOfString_3>shop_codigo=' . $result['shop_codigo'] . '</arrayOfString_3>';
            $result['array_shop_data'] = $parts;
        }

        return $result;
    }

    public static function getSystemInfo() {
        $env = PHP_OS;
        $env .= ';' . phpversion();

        $version_module = nacexutils::nacexVersion;
        $env .= ';PrestaShop-' . _PS_VERSION_ . ';';
        $env .= nacexutils::_moduleName . $version_module;

        $xml = 'environment='.$env;
        return $xml;

    }
    public function get_Agencia3($_postcode) {
        $_ext = 'EXT';
        $_URL = nacexDTO::$url_ws. '/soap';
        $_xml =
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
            <soapenv:Header/>
            <soapenv:Body>
                <typ:getAgencia3>
                    <String_1>'.Configuration::get('NACEX_WSUSERNAME').'</String_1>
                    <String_2>'.Configuration::get('NACEX_WSPASSWORD').'</String_2>
                    <String_3>'.$_postcode.'</String_3>
                    <String_4></String_4>
                    <String_5>'.$_ext.'</String_5>
                </typ:getAgencia3>
            </soapenv:Body>
         </soapenv:Envelope>';
        $_response = $this->requestWS($_URL, $_xml, 'getAgencia3');
        return $_response;
    }

    public function getPuntoEntregaGPS($lat, $long) {
        $URL = nacexDTO::$url_ws. '/soap';
        $xml =
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
            <soapenv:Header/>
            <soapenv:Body>
                <typ:getPuntoEntregaGPS>
                    <String_1>' . Configuration::get('NACEX_WSUSERNAME') . '</String_1>
                    <String_2>' . Configuration::get('NACEX_WSPASSWORD') . '</String_2>
                    <arrayOfString_3>' . $lat . '</arrayOfString_3>
                    <arrayOfString_3>' . $long . '</arrayOfString_3>
                </typ:getPuntoEntregaGPS>
            </soapenv:Body>
         </soapenv:Envelope>';
        $response = $this->requestWS($URL, $xml, 'getPuntoEntregaGPS');
        return $response;
    }

    /** Función que devuelve las agencias del CP indicado o seleccionado por el cliente **/
    public function getSelectShopsValues($shop_codigo, $lat, $lon, $agencia = false, $shopCodSelected = null)
    {
        // Buscamos puntos a los que distribuye la misma agencia que seleccionó el cliente.
        $select_tiendas = '';

        // Miramos si hay conexión o no
        $resultado = $this->getPuntoEntregaGPS($lat, $lon);
        $resultado = $this->treatmentXML($resultado, 'getPuntoEntregaGPS');

        if ($resultado && is_array($resultado)) {
            if ($resultado[0] == '500ERROR') {
                $tiendas = new nacexshop();
                $agenciasInfo = $tiendas->searchAgenciaShopCustomerSelected($shop_codigo, $agencia)[0];

                // Explode de cada uno
                for ($i = 0; $i < sizeof($agenciasInfo); $i++) {
                    $agencia = explode('|', $agenciasInfo[$i]);

                    $selected = ($shopCodSelected != null && $shopCodSelected == $agencia[0]) ? 'selected' : '';
                    $select_tiendas .= "<option value='" . $agencia[0] . "' " . $selected . '> ';
                    $select_tiendas .= $agencia[0] . ' | ' . utf8_decode($agencia[2]) . ' (' . utf8_decode($agencia[3]) . ', ' . utf8_decode($agencia[4]) . ', ' . utf8_decode($agencia[5]) . ')' .
                        '</option>';
                }
            } else {
                array_shift($resultado);
                $agenciasInfo = str_replace('~', '|', $resultado);

                for ($i = 0; $i < sizeof($agenciasInfo); $i++) {
                    $agencia = explode('|', $agenciasInfo[$i]);

                    $selected = ($shopCodSelected != null && $shopCodSelected == $agencia[0]) ? 'selected' : '';
                    $select_tiendas .= "<option value='" . $agencia[0] . "' " . $selected . '> ';
                    $select_tiendas .= $agencia[0] . ' | ' . $agencia[1] . ' - ' . $agencia[3] . ' - ' . $agencia[2] .
                        '</option>';
                }
            }
        }

        return $select_tiendas;
    }
}
