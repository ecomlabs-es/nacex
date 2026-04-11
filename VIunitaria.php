<?php

include_once(dirname(__FILE__) . '/nacex.php');

class VIunitaria
{
    protected $nacex;

    public function __construct()
    {
        $this->nacex = new nacex();
    }

    public static function cabecera()
    {
        //if ( (isset($_GET['method'])) && ($_GET['method'] == "tab" || $_GET['method'] == "unitaria")) {

        $nacex = new nacex();
        $mess = $nacex->l('You must insert an Order Id', 'nacex');

        if (empty($_GET['id_pedido'])) {
            $_GET['id_pedido'] = '';
        }

        return '
                <div class="panel-heading" style="display:flex;align-items:center;gap:1em;">
                    <a target="_blank" href="https://www.nacex.es">
                        <img style="width:130px;height:auto;" src="' . _MODULE_DIR_ . 'nacex/images/logos/nacex_logista.png" />
                    </a>
                    <span style="font-size:1.1em;">' . $nacex->l('Unitary search', 'nacex') . '</span>
                </div>
                <div class="panel-body" style="display:flex;align-items:center;gap:1em;">
                    <label for="idpedido" style="margin:0;font-weight:600;">' . $nacex->l('Id order', 'nacex') . '</label>
                    <input type="number" id="idpedido" class="form-control" autofocus="autofocus" value="' . htmlspecialchars($_GET['id_pedido'], ENT_QUOTES) . '" style="width:200px;" />
                    <button type="button" class="btn btn-primary" id="btnbuscar" onclick="unitaria.search(idpedido.valueAsNumber,\'' . $mess . '\');">
                        <i class="material-icons" style="font-size:14px;vertical-align:middle;">search</i> ' . $nacex->l('Search', 'nacex') . '
                    </button>
                </div>';
        //}
    }

    public function table($_query, $url, $id_pedido) {
        $_return = [];
        $result_html = '';
        $_result_html_cabecera_tabla = '';
        if ($_query){
            include_once dirname(__FILE__) . '/nacexDTO.php';
            include_once dirname(__FILE__) . '/MOunitaria.php';
            include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'nacexWS.php';
            $_nacexdto = new nacexDTO();
            //CHECK IF ISNACEXCARRIER OR NACEX_FORCE_GENFORM
            //if ($_nacexdto->isNacexCarrier($_query[0]["id_carrier"])) {
            if ($_nacexdto->isNacexCarrier($_query[0]['id_carrier']) ||
                (!$_nacexdto->isNacexCarrier($_query[0]['id_carrier']) && (Configuration::get('NACEX_FORCE_GENFORM') == 'SI'))
            ) {
                $_expeditions = MOunitaria::select_expedition($id_pedido);
                //EXPEDITIONS TO SHOW
                if ($_expeditions) {
                    $_result_html_cabecera_tabla = $this->table_header();
                    foreach ($_expeditions as $_expedition) {
                        $result_html .= $this->table_body($_expedition, $url);
                    }
                    //NOTHIG TO SHOW
                } else {
                    $result_html .= "<div class='alert alert-info' style='text-align:center;margin:1em;'>" . $this->nacex->l('No documented expeditions for this order', 'nacex') . '</div>';
                }
                //IS NACEX SHOP
            } elseif ($_nacexdto->isNacexShopCarrier($_query[0]['id_carrier'])) {
                $_expeditions = MOunitaria::select_expedition($id_pedido);
                //EXPEDITIONS TO SHOW
                if ($_expeditions) {
                    $_result_html_cabecera_tabla = $this->table_header();
                    foreach ($_expeditions as $_expedition) {
                        $result_html .= $this->table_body($_expedition, $url);
                    }
                    //NOTHIG TO SHOW
                } else {
                    $result_html .= "<div class='alert alert-info' style='text-align:center;margin:1em;'>" . $this->nacex->l('No documented expeditions for this order', 'nacex') . '</div>';
                }
                //IS NACEX INTERN
            } elseif ($_nacexdto->isNacexIntCarrier($_query[0]['id_carrier'])) {
                $_expeditions = MOunitaria::select_expedition($id_pedido);
                //EXPEDITIONS TO SHOW
                if ($_expeditions) {
                    $_result_html_cabecera_tabla = $this->table_header();
                    foreach ($_expeditions as $_expedition) {
                        $result_html .= $this->table_body($_expedition, $url);
                    }
                    //NOTHIG TO SHOW
                } else {
                    $result_html .= "<div class='alert alert-info' style='text-align:center;margin:1em;'>" . $this->nacex->l('No documented expeditions for this order', 'nacex') . '</div>';
                }
                //ISNOTNACEX
            } elseif (Configuration::get('NACEX_FORCE_GENFORM') == 'SI') {
                $_expeditions = MOunitaria::select_expedition($id_pedido);
                //EXPEDITIONS TO SHOW
                if ($_expeditions) {
                    $_result_html_cabecera_tabla = $this->table_header();
                    foreach ($_expeditions as $_expedition) {
                        $result_html .= $this->table_body($_expedition, $url);
                    }
                    //NOTHIG TO SHOW
                } else {
                    $result_html .= "<div class='alert alert-info' style='text-align:center;margin:1em;'>" . $this->nacex->l('No documented expeditions for this order', 'nacex') . '</div>';
                }
            } else {
                array_push($_return, ['cod_response' => '200',
                    'result' => "<div class='alert alert-info' style='text-align:center;margin:1em;'>" . $this->nacex->l('You must enable 3rd party carriers', 'nacex') . '</div>'
                ]);
                return $_return;
            }
        }
        if ($result_html != ''){
            $result_html = $_result_html_cabecera_tabla . $result_html;
            $result_html .= $this->table_footer($url, $id_pedido);
            array_push($_return, ['cod_response' => '200',
                'result' => $result_html
            ]);
            return $_return;
        }
        array_push($_return, ['cod_response' => '200',
            'result' => "<div class='alert alert-info' style='text-align:center;margin:1em;'>" . $this->nacex->l('Order not exist', 'nacex') . '</div>'
        ]);
        return $_return;
    }

    public function printTable($id_pedido, $url) {
        $result_html = '';
        include_once dirname(__FILE__) . '/MOunitaria.php';
        $_expeditions = MOunitaria::select_expedition($id_pedido);
        //EXPEDITIONS TO SHOW
        if ($_expeditions) {
            $result_html .= $this->table_header();
            foreach ($_expeditions as $_expedition) {
                $result_html .= $this->table_body($_expedition, $url);
            }
            //NOTHIG TO SHOW
        } else {
            $result_html .= "<div class='alert alert-info' style='text-align:center;margin:1em;'>" . $this->nacex->l('No documented expeditions for this order', 'nacex') . '</div>';
        }
        $result_html .= $this->table_footer($url, $id_pedido);

        return $result_html;
    }

    private function table_header()
    {
        $_html = '';
        $_html .= '
                  <div class="table-responsive" style="margin-top:1em;">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>' . $this->nacex->l('Order', 'nacex') . '</th>
                        <th>' . $this->nacex->l('Date', 'nacex') . '</th>
                        <th>' . $this->nacex->l('Service', 'nacex') . '</th>
                        <th>' . $this->nacex->l('See order', 'nacex') . '</th>
                        <th>' . $this->nacex->l('Status', 'nacex') . '</th>';
        /** Añadir icono para imprimir una expedición concreta **/
        $_html .= '<th>' . $this->nacex->l('Print label', 'nacex') . '</th>';
        $_html .= '</tr></thead><tbody>';
        return $_html;
    }

    private function table_body($_expedition, $url)
    {
        //GET ADMIN DIRECTORY
        /*$_baseuri = Context::getContext()->link->getAdminBaseLink();
        $_string = str_replace($_baseuri,'',$_SERVER['HTTP_REFERER']);
        $_pos = strpos($_string,"/");
        $_admin = substr($_string,0,$_pos);
        $_url= $_baseuri.$_admin."/index.php?controller=AdminOrders&id_order=".$_expedition['id_envio_order']."&token=" . $url . "&vieworder=1";*/

        $link = new Link();
        $_url = $link->getAdminLink('AdminOrders', true, ['id_order' => $_expedition['id_envio_order'], 'vieworder' => 1]);

        /*
                <td align='center' padding: 8px><img class='zoomable' id='printIcon' src='".nacexDTO::getPath()."img/print_icon.png'
                        title='".$this->l('Imprimir etiquetas Nacex', 'nacex')."' alt='".$this->l('Imprimir etiquetas Nacex', 'nacex')."' width='38px' /></td>
         */

        $_result_html = "
            <tr>
                <td align='center' id=" . $_expedition['id_envio_order'] . '>' . $_expedition['id_envio_order'] . "</td>
                <td align='center'>" . $_expedition['fecha_alta'] . "</td>
                <td align='center'>" . $_expedition['serv'] . "</td>
                <td align='center'><a href=" . $_url . " target='_blank'>" . $this->nacex->l('See order', 'nacex') . "</a></td>
                <td align='center'>" . $_expedition['estado'] . '</td>';

        // Si hay servicio 44 activo y etiquetas

        $onClickPrint = '';
        if (!nacexutils::existFileLabelPDF('etiquetas', $_expedition['exp_cod'])) { $onClickPrint = "onclick='ionaPrint(\"" . $_expedition['exp_cod'] . '","' . $_expedition['id_envio_order'] . "\");' "; }

        if (nacexWS::ws_checkConnection()[0] != '500ERROR') {
            $_result_html .= "<td align='center' class='imprimirEtiquetas' name='" . $_expedition['id_envio_order'] . '~' . $_expedition['exp_cod'] . "' " . $onClickPrint . "style='text-align: center;'>";
        } else { $_result_html .= '<td></td>'; }

        // Cuando no es posible modificar la expedición porque el getEtiqueta devuelve error o algún otro motivo por el que no se puede imprimir
        $modelPrint = Configuration::get('NACEX_PRINT_MODEL');
        $canPrint = nacexWS::checkGetEtiqueta($modelPrint, $_expedition['exp_cod']);
        $gestionAgencia = nacexDAO::getGestionAgencia($_expedition, $canPrint);

        /** Añadir icono para imprimir una expedición concreta **/

        if (nacexutils::existFileLabelPDF('etiquetas', $_expedition['exp_cod'])) {

            $_result_html .= '<a onClick="downloadPDF(\'etiquetas\',\'' . $_expedition['exp_cod'] . '\',\'' . $_expedition['id_envio_order'] . '\')" style="cursor:pointer;margin-right:0.5em;" title="' . addslashes($this->nacex->l('Print label', 'nacex')) . '">';
            $_result_html .= "<img class='zoomable' src='" . nacexDTO::getPath() . "images/print_icon.png' title='" . addslashes($this->nacex->l('Print label', 'nacex')) . ' [' . $_expedition['exp_cod'] . "]' alt='" . addslashes($this->nacex->l('Print label', 'nacex')) . ' [' . $_expedition['exp_cod'] . "]'/>";
            $_result_html .= '</a>';

            // La etiqueta sólo puede imprimirse una vez, por lo que comprobamos si existe el archivo antes de mostrar el botón
            if (nacexutils::existFileLabelPDF('etiquetas_dev', $_expedition['exp_cod'])) {
                $_result_html .= '<a onClick="downloadPDF(\'etiquetas_dev\',\'' . $_expedition['exp_cod'] . '\',\'' . $_expedition['id_envio_order'] . '\')" title="' . $this->nacex->l('Print return label', 'nacex') . '"  style="cursor:pointer;margin-right:0.5em;">';
                $_result_html .= "<img class='zoomable' src='" . nacexDTO::getPath() . "images/imprimir-devoluciones.svg' style='width: 2em;' title='" . $this->nacex->l('Print return label', 'nacex') . ' [' . $_expedition['exp_cod'] . "]' alt='" . $this->nacex->l('Print return label', 'nacex') . ' [' . $_expedition['exp_cod'] . "]'/>";
                $_result_html .= '</a>';
            }

        } elseif (nacexWS::ws_checkConnection()[0] != '500ERROR' && $_expedition['estado'] != 'BAJA' && $_expedition['estado'] != 'ANULADA' && !$gestionAgencia && $canPrint) {
            $_result_html .= "<img class='zoomable' id='printIcon' src='" . nacexDTO::getPath() . "images/print_icon.png' title='" . addslashes($this->nacex->l('Print label', 'nacex')) . ' [' . $_expedition['ag_cod_num_exp'] . '~' . $_expedition['exp_cod'] . "]' alt='" . addslashes($this->nacex->l('Print label', 'nacex')) . ' [' . $_expedition['ag_cod_num_exp'] . '~' . $_expedition['exp_cod'] . "]'/>";
        }

        $_result_html .= '</td>
            </tr>';

        return $_result_html;
    }

    private function table_footer($url, $id_pedido)
    {
        include_once dirname(__FILE__) . '/nacexDAO.php';
        $_datospedido = nacexDAO::getDatosPedido($id_pedido);
        $_datosexpediciones = nacexDAO::getDatosExpedicion($id_pedido);

        // Lo inicializamos a null por si el pedido no contiene ninguna expedicion
        $_datosexpedicion = null;
        if (is_array($_datosexpediciones) && !empty($_datosexpediciones)) { $_datosexpedicion = $_datosexpediciones[sizeof($_datosexpediciones) - 1]; }

        $_result_html = '   
         </tbody>
         </table>
         </div>';

        if (nacexWS::ws_checkConnection()[0] != '500ERROR') {
            $_result_html .= $this->showExpedicionForm($id_pedido, $_datospedido, $_datosexpedicion, Tools::isSubmit('submitcambioexpedicion'), $url); } else { $_result_html .= "<p class='alert alert-info'>
                " . $this->nacex->l('There is a problem with Nacex Web Service connection and some functionality may be affected, as well as <strong>some expedition status</strong>. Please, wait few minutes until connextion will be restored. We apologize for the inconvenients', 'nacex') . '
                </p>'; }

        return $_result_html;
    }

    private static function showExpedicionForm($id_order, $datospedido, $datosexpedicion, $isnacexcambio, $urlToken)
    {
        include_once dirname(__FILE__) .'/hash.php';
        $nacexDTO = new nacexDTO();
        $nacex = new nacex();
        $html = '';

        //echo "<h1>Carrier ID: " . $datospedido[0]['id_carrier'] . "</h1>";
        //var_dump($datospedido);

        $nacex_impseg = configuration::get('NACEX_DEFAULT_IMP_SEG');
        /*$isnacexcambio ? Tools::getValue('nacex_imp_seg') ? Tools::getValue('nacex_imp_seg') : "" :
        Tools::getValue('nacex_imp_seg') ? Tools::getValue('nacex_imp_seg') : Configuration::get('NACEX_DEFAULT_IMP_SEG');*/
        $css_id = 'ncx_info_exp';

        //($isnacexcambio ? "Generar Nacex C@mbio" : "Generar Expedición ") .
        $titulo = '<span><b>' . $nacex->l('Generate expedition', 'nacex') . ': </b>' . nacexutils::getReferenciaGeneral() . $id_order . '</span>';

        $shop_codigo = null;

        $modo_f = (Configuration::get('NACEX_SERV_BACK_OR_FRONT') == 'F');

        $att = '';

        // Si no le pasamos datos de pedido, los buscamos nosotros
        if (empty($datospedido)) { $datospedido = nacexDAO::getDatosPedido($id_order); }

        //Si ncx es "1" es Nacex normal, si no es un código NacexShop
        if (($datospedido[0]['ncx'] != '1' && $datospedido[0]['id_carrier'] == nacexDTO::getNacexShopIdCarrier()) ||
            nacexDTO::isNacexShopCarrier($datospedido[0]['id_carrier'])) {
            $array_shop = explode('|', $datospedido[0]['ncx']);
            $shop_codigo = $array_shop[0];
            $css_id = 'ncx_info_shop';
            $webtext = $nacex->l('Go to NacexShop web', 'nacex');
            $webdir = 'https://www.nacexshop.com';
            $webimg = _MODULE_DIR_ . 'nacex/images/logos/nacex_logista.png';

            $array_address_invoice = nacexDAO::getAddressInvoiceByOrder($id_order);
            $att = $array_address_invoice[0]['firstname'] . ' ' . $array_address_invoice[0]['lastname'];
        }

        $tlf = '';
        if ((isset($datospedido[0]['phone']) && $datospedido[0]['phone'] != '') && (isset($datospedido[0]['phone_mobile']) && $datospedido[0]['phone_mobile'] == '')) {

            $tlf = $datospedido[0]['phone'];

        } elseif (isset($datospedido[0]['phone_mobile']) && $datospedido[0]['phone_mobile'] != '') {
            $tlf = $datospedido[0]['phone_mobile'];
        }

        $cliente_tlf = $tlf;
        $cliente_email = $datospedido[0]['email'];

        $html .= '</script>
				<script>
					   	var cli_tlf = "' . substr($cliente_tlf, 0, 50) . '";
					    var cli_email = "' . substr($cliente_email, 0, 50) . '";
				</script>
		';

        //PAGO CONTRA REEMBOLSO --------------------------------------------------------------------------------------------------
        $metodo_pago = strtolower($datospedido[0]['module']);
        $array_modsree = explode('|', Configuration::get('NACEX_MODULOS_REEMBOLSO'));  //Nombres módulos pago contra reembolso indicados en Configuración
        $nacex_imp_ree = (in_array($metodo_pago, $array_modsree) || strpos($metodo_pago, 'cashondelivery') !== false) && is_numeric($datospedido[0]['total_paid_real']) ?
            number_format($datospedido[0]['total_paid_real'], 2) : '0';
        //------------------------------------------------------------------------------------------------------------------------

        $array_agclis = explode(',', Configuration::get('NACEX_AGCLI'));
        $select_agcli = '';
        for ($i = 0; $i < count($array_agclis); $i++) {
            $select_agcli = $select_agcli . '<option ' . nacexutils::markSelectedOption('nacex_agcli', 'NACEX_AGCLI', trim($array_agclis[$i])) . " value='" . trim($array_agclis[$i]) . "'>" . trim($array_agclis[$i]) . '</option>';
        }
        //Para documentar Nacex C@mbio debe usarse la misma agencia y cliente que la expedicion padre
        if ($isnacexcambio && !is_null($datosexpedicion)) {
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
            $str_aviso_modo_f = '<p style="margin-left:5px;">' . $nacex->l('Customer selected service', 'nacex') . ' ' . $auxstr . '<b>' . str_replace('_', ' ', $carrier_name) . '</b> ' . $nacex->l('from frontend', 'nacex') . '</p>';
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
                $select_serv = '<optgroup label="' . $nacex->l('Default service', 'nacex') . "\"><option selected='selected' value='" . $sel_serv . "'>" .
                    $sel_serv . $nacexDTO->getServSeparador() . $array_servicios[$sel_serv]['nombre'] .
                    '</option></optgroup>';
            } else {
                $select_serv = '<optgroup label="' . $nacex->l('Default service', 'nacex') . "\"><option selected='selected' value='" . $def_serv . "'>"
                    . $def_serv . $nacexDTO->getServSeparador() . $array_servicios[$def_serv]['nombre'] .
                    '</option></optgroup>';
            }

            if (isset($opt_serv)) {
                $select_serv = $select_serv . '<optgroup label="' . $nacex->l('Available services', 'nacex') . '">';
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

        $prealertaval = Configuration::get('NACEX_TIP_PREAL');
        if ($prealertaval == 'N') { $prealertaval = ''; }

        $prealertaplusval = Tools::getValue('nacex_pre1_plus', Configuration::get('NACEX_PREAL_PLUS_TXT'));

        //CALCULAMOS EL NUMERO DE BULTOS POR DEFECTO
        //Obtenemos más detalles del pedido, como el peso y las referencias
        //$numbultos = 0;
        $numproductos = 0;
        $valor = 0;

        $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference, total_price_tax_incl FROM ' . _DB_PREFIX_ . 'order_detail where id_order = "' . $id_order . '"');

        foreach ($productospedido as $producto) {
            $numproductos += $producto['product_quantity'];
            $valor += floatval($producto['total_price_tax_incl']);
        }

        $numbultos = Configuration::get('NACEX_BULTOS') == 'F' ? Configuration::get('NACEX_BULTOS_NUMERO') : $numproductos;

        /*$numbultos = Configuration::get('NACEX_BULTOS') == "F" ?
            Tools::getValue("nacex_bul", Configuration::get('NACEX_BULTOS_NUMERO')) :
            (Configuration::get('NACEX_BULTOS') == "C" ?
                Tools::getValue("nacex_bul", $numproductos) :
                "1");*/

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

        if ($comentario_cli_sino && $comentario_cli == 'delivery_message') {
            $order = new Order($id_order);
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

        if ($inst_adi_cantyref) {
            //Obtenemos más detalles del pedido, como el peso y las referencias
            $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference FROM ' . _DB_PREFIX_ . 'order_detail where id_order = "' . $id_order . '"');
            foreach ($productospedido as $producto) {
                $prodref = str_replace(';', ',', $producto['product_reference']);
                $instrucciones .= ' ** ' . $producto['product_quantity'] . ' # ' . $prodref;
            }
            $instrucciones .= ' ';
        }
        if ($mensaje != '') { $instrucciones .= ' ** ' . $mensaje; }
        //$instrucciones .= " ** " . $inst_adi_val;
        if ($instrucciones != '') { $instrucciones .= ' ** ' . $inst_adi_val; } elseif ($inst_adi_val) { $instrucciones .= $inst_adi_val; }

        $texto_instrucciones = Tools::getValue('inst_adi', $instrucciones);

        // Comprobamos que datosexpedicion no está vacío
        $datosExpedicionExpCod = !is_null($datosexpedicion) ? $datosexpedicion['exp_cod'] : '';

        $html .= '	
            <div class="accordion panel">
                <span class="panel-heading">
					<i class="icon-truck"></i>
					' . $nacex->l('Document shipments', 'nacex') . '
				</span>
							
				<div align="center" id="ncx_boxinfo' . $datosExpedicionExpCod . '">
				  <div id="' . $css_id . '">				  
				  <fieldset class="diana">
					<legend style="margin-left:5px;padding-left: 10px;">' . $titulo . '</legend>
							<script>
                                function checkform(){
                                    if ($( "#nacex_tip_ser" ).val() === ""){
                                        alert ("' . $nacex->l('You must select a service', 'nacex') . '");
                                        return false;
                                    }else{
                                        procesando();
                                        return true;
                                    }
                                }
                            </script>
                                           
					<form method="post" name="generarExpedicion" id="generarExpedicion" onsubmit=\'return checkform();\'>';

        //HASH TO AVOID F5.

        $_hash = hash::hash_form($id_order);

        if (isset($shop_codigo)) {
            $html .= '<span style="float:right;color:#555;margin-top:-20px;">' . $shop_codigo . '</span>';
        }
        $html .= '  <input type="hidden" value="' . $id_order . '" name="order_id" />
                    <input type="hidden" value="' . $_hash . '" name="hash" />
                    <input type="hidden" value="' . substr($cliente_tlf, 0, 50) . '" name="cli_tlf" id="cli_tlf"/>
                    <input type="hidden" value="' . substr($cliente_email, 0, 50) . '" name="cli_email" id="cli_email"/>
                    <p style="margin-left:5px;">' . $nacex->l('Agency/Customer', 'nacex') . ': 								
                    <select name="nacex_agcli" size="1" style="margin-left:15px;width: 130px;text-align:right;border: 0;">' . $select_agcli . '</select>
                </p>';

        if ($select_dpt != '') {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Department', 'nacex') . ': 								
			<select name="nacex_departamentos"  style="margin-left:15px;width: 130px;text-align:right;border: 0;" >' . $select_dpt . '</select>
		</p>';
        }
        $html .= $str_aviso_modo_f . '
				            				
							<p style="margin-left:5px;">' . $nacex->l('Service', 'nacex') . ': 														
								<select name="nacex_tip_ser" id="nacex_tip_ser" size="1" style="margin-left:15px;width:auto;text-align:right;border: 0;">
									' . $select_serv . '
								</select>
								<select name="nacex_frecuencia" id="nacex_frecuencia" size="1" style="display:none;margin-left:15px;width:70%;text-align:right;border: 0;">
									<option value="1">1 - ' . $nacex->l('Morning freq.', 'nacex') . '</option>
									<option value="2" selected="selected">2 - ' . $nacex->l('Afternoon freq.', 'nacex') . '</option>										
									<option value="8">8 - ' . $nacex->l('Night freq.', 'nacex') . '</option>
								</select>
								<script>
									
									var frecDep=0;
									
									if ( $("#nacex_tip_ser option:selected").val()== "09") { 				            						
				            							$("#nacex_frecuencia").slideToggle();	
				            							frecDep=1;	
												}
				            								
									$(document).on("change","#nacex_tip_ser",function(){ 
									    if ( $("#nacex_tip_ser option:selected").val()== "09") { 
					            			alert("' . $nacex->l('Select the requency for Interdia service.\\n Frecuencies are availables in certain deliver and pickup postcodes depending on shipment request schedule.', 'nacex') . '");
					            			$("#nacex_frecuencia").slideToggle(); frecDep=1;	
										}else {if (frecDep){ $("#nacex_frecuencia").slideToggle(); frecDep=0;} }
									});
					            		';

        if (substr($datospedido[0]['postcode'], 0, 2) != '07') {
            $html .= 'if ( $("#nacex_tip_ser option:selected").val()== "20") { 
                alert("' . $nacex->l('Delivery zip code is NOT available in Balearic Islands', 'nacex') . ': ' . $datospedido[0]['postcode'] . '");
            }';
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
								style="margin-left: 5px;">' . $nacex->l('Packages', 'nacex') . '  
								<input
								type="number"
								min="1"
								id="nacex_bul"
								name="nacex_bul"
								value=' . $numbultos . '
								size="2"
								length="1"
								maxlength="3"
								style="margin-left:15px;width: auto;text-align:right;border: 0;" />		
			            		</p>';
        $html .= '<p style="display: block;">' . $nacex->l('Expedition date', 'nacex') . ' 
                <input type="date" id="nacex_fec" name="nacex_fec"
                    min="' . date('Y-m-d') . '" 
                    value=""
                    onkeydown="return false"
                    onblur="blurDateValidation(this.value);"
                    style="margin-left:15px;width: auto;text-align:right;border: 0;" />
                </p>
				<hr/>';

        if ($internacional) {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Shipment content', 'nacex') . ': ';
            $html .= '<select id="nacex_contenido" name="nacex_contenido" value="' . Tools::getValue('nacex_contenido', '') . '" style="width:94%; margin-left:15px;" length=1 maxlength="38" onchange="nacexcontenido()">';
            foreach ($nacexDTO->getContenidos() as $cont) {
                $html .= '	<option ' . nacexutils::markSelectedOption('nacex_contenido', 'NACEX_DEFAULT_CONTENIDO', $cont) . ' value="' . $cont . '">' . $cont . '</option>';
            }
            $html .= '</select> </p>';

            $html .= '
                     <div align="left" id="tagnacexcontenido" style="visibility: hidden">
                        ' . $nacex->l('Content description', 'nacex') . ':
                     </div>
                     <div id="descripcionnacexcontenido" style="visibility: hidden">
                     
                        <input type="text" id="nacex_descripcion_contenido" name="nacex_descripcion_contenido" value="" style="width:94%; margin-left:15px;" length=1 maxlength="38" />
                
                     </div>
                     ';

            $html .= '<p style="margin-left:5px;">' . $nacex->l('Declared amount', 'nacex') . ':<br>
			<input type="text" id="nacex_impDeclarado" name="nacex_impDeclarado" value="' . $valor . '" style="margin-left:15px;width: 100px;text-align:right;border: 0;display:inline" length="1" maxlength="10"/> &euro;
                    </p>
                    <hr/>';
        }

        if (!$internacional) {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Charges', 'nacex') . ':
                <input type="radio" name="nacex_tip_cob" value="O" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'O') . '/>O - Origen  
                <input type="radio" name="nacex_tip_cob" value="D" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'D') . '/>D -  Destino
                <input type="radio" name="nacex_tip_cob" value="T" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'T') . '/>T - Tercera 
            </p>';
        } else {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Charges', 'nacex') . ':
                <input type="radio" name="nacex_tip_cob" value="O" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'O') . '/>O - Origen
                <input type="radio" name="nacex_tip_cob" value="T" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'T') . '/>T - Tercera 
            </p>';
        }

        if (!$internacional) {
            $html .= '<hr><p style="margin-left:5px;">' . $nacex->l('Refund', 'nacex') . ':
                        <input type="radio" name="nacex_tip_ree" value="O" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'O') . '/>O - Origen
                        <input type="radio" name="nacex_tip_ree" value="D" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'D') . '/>D - Destino
                        <input type="radio" name="nacex_tip_ree" value="T" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'T') . '/>T - Tercera
                    </p>
                    <p style="margin-left:5px;">' . $nacex->l('Refund amount', 'nacex') . ':';
            if (isset($shop_codigo)) { $html .= '<input type="number" id="nacex_imp_ree" name="nacex_imp_ree" pattern="^\d+(?:\.\d{1,2})?$" step="0.01" required min="0" max="600" value="' . $nacex_imp_ree . '" size="20" maxlength="20" style="margin-left:15px;text-align:right;border: 0;display:inline"/> &euro;'; } else { $html .= '<input type="number" id="nacex_imp_ree" name="nacex_imp_ree" pattern="^\d+(?:\.\d{1,2})?$" step="0.01" required min="0" max="2500" value="' . $nacex_imp_ree . '" size="20" maxlength="20" style="margin-left:15px;text-align:right;border: 0;display:inline"/> &euro;'; }

            $html .= '<span data-toggle="tooltip" data-placement="top" title="' . $nacex->l('Total amount to pay for the receiver. Cost 0 do NOT have refund', 'nacex') . '" style="cursor:help;background-color:#25b9d7;padding:2px 7px;border-radius:50%;color:#fff;font-size:12px;font-weight:bold;margin-left:0.5em;">?</span>
                     </p>';

            if (isset($shop_codigo)) {
                $html .= '<p class="maxRefundText">' . $nacex->l('Max. refund is', 'nacex') . ' <strong>600€</strong></p>'; }

        } else {
            if (intval($nacex_imp_ree > 0)){
                $html .= '<p style="margin-left:5px;">' . $nacex->l('Refund', 'nacex') . ': ' . $nacex->l('In International shipments is NOT available management RETURN shipments', 'nacex') . '</p>';
            }
        }

        if (!$internacional) {
            $html .= '<hr><p style="margin-left:5px;">' . $nacex->l('Shipment', 'nacex') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								            			<input type="radio" name="nacex_tip_env" value="0" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '0') . '/>0 - DOCS
								            			<input type="radio" name="nacex_tip_env" value="1" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '1') . '/>1 - BAG
														<input type="radio" name="nacex_tip_env" value="2" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '2') . '/>2 - PAQ																														
								            </p>';
        } else {
            $html .= '<p style="margin-left:5px;">' . $nacex->l('Shipment', 'nacex') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;								            			
								            			<input type="radio" name="nacex_tip_env" value="M" checked="checked" ' . nacexutils::markCheckedOption('nacex_tip_env_int', 'NACEX_TIP_ENV_INT', 'M') . '/> M - MUESTRA
								            			<input type="radio" name="nacex_tip_env" value="D" ' . nacexutils::markCheckedOption('nacex_tip_env_int', 'NACEX_TIP_ENV_INT', 'D') . '/> D - DOCUMENTO														
								            </p>';
        }

        if (!$internacional) { //Internacional no admite retornos
            if ($isnacexcambio) {
                $html .= '<p style="margin-left:5px;">' . $nacex->l('Return', 'nacex') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									            	<input type="radio" name="nacex_ret" value="NO" disabled="true"/>NO
													<input type="radio" name="nacex_ret" value="SI" checked="checked"/>SI 
									            </p>';
            } else {
                $html .= '<p style="margin-left:5px;">' . $nacex->l('Return', 'nacex') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									            	<input type="radio" name="nacex_ret" value="NO" ' . nacexutils::markCheckedOption('nacex_ret', 'NACEX_RET', 'NO') . '/>NO
													<input type="radio" name="nacex_ret" value="SI" ' . nacexutils::markCheckedOption('nacex_ret', 'NACEX_RET', 'SI') . '/>SI 
									            </p>';
            }
        }
        //seguro
        $html .= '<hr/>
						   			 <p style="margin-left:5px;">' . $nacex->l('Insurance', 'nacex') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						   						<select name="nacex_tip_seg" size="1" onchange="checkTipoSeguro(this.value);" style="width:200px; margin-left:15px;">
													' . $select_tipseg . '
												</select>
								      </p> 
								      <p style="margin-left:5px;">' . $nacex->l('Insured amount', 'nacex') . ':&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								      </p>
								      <p style="margin-left:5px;">				
						   					<input type="number" min="0" id="nacex_imp_seg" name="nacex_imp_seg" value="' . $nacex_impseg . '"  length=1 maxlength="35" ' . ($nacex_impseg == '' ? 'disabled="disabled"' : '') . ' style="margin-left:15px;width: 100px;text-align:right;border: 0;display:inline"/> &euro; 
								      </p>';

        $html .= '<hr/>';
        $html .= '<p style="margin-left:5px;">' . $nacex->l('Prealert', 'nacex') . ':<br>
										<input type="radio" name="nacex_tip_pre1" value="N" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'N') . ' onclick="setprealerta(\'N\');"  checked="true"/>N - No&nbsp;&nbsp;
										<input type="radio" name="nacex_tip_pre1" value="S" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'S') . ' onclick="(setprealerta(\'S\'));" />S - SMS&nbsp;&nbsp;
										<input type="radio" name="nacex_tip_pre1" value="E" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'E') . ' onclick="setprealerta(\'E\');" />E - Email';
        $html .= '<br>';
        $html .= '<input style="margin-left:15px;display:inline;text-align:center;width:94%" type="text" id="nacex_pre1" name="nacex_pre1" value="' . $prealertaval . '" length="1" maxlength="50" disabled="true"/></p>';

        $html .= '<p style="margin-left:5px;">' . $nacex->l('Prealert mode', 'nacex') . ':<br>
			<input type="radio" name="nacex_mod_pre1" value="S" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'S') . ' onclick="setprealertaplus(\'S\');"  />S - Standard';
        if (!isset($shop_codigo)) {
            $html .= '<input type="radio" name="nacex_mod_pre1" value="P" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'P') . ' onclick="setprealertaplus(\'P\');"  />P - Plus';
            $html .= '<input type="radio" name="nacex_mod_pre1" value="R" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'R') . ' onclick="setprealertaplus(\'R\');"  />R - Reparto';
            $html .= '<input type="radio" name="nacex_mod_pre1" value="E" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'E') . ' onclick="setprealertaplus(\'E\');"  />E - Reparto Plus';
            $html .= '<br> <input style="margin-left:15px;display:inline;text-align:center;width:94%" type="text" id="nacex_pre1_plus" name="nacex_pre1_plus" value="' . $prealertaplusval . '" length="1" maxlength="719" disabled="true"/>';
        }
        $html .= '</p>
				            
				            <hr style="margin-top:2px" />';
        if (isset($shop_codigo)) {
            $html .= '
								<p style="margin-left:5px;">' . $nacex->l('For the attention of', 'nacex') . ':: 
								<input type="text" id="nacex_att" name="nacex_per_ent" value="' . $att . '" style="width:94%; margin-left:15px;" length=1 maxlength="38" length=1 maxlength="35"/></p>';
        }
        $html .= '<p style="margin-left:5px;">
                        ' . $nacex->l('Delivery observations', 'nacex') . ' (1)
                        <input type="text" id="nacex_obs1" name="nacex_obs1" value="' . $obs_def1 . '" style="width:94%; margin-left:15px; display: block;" length=1 maxlength="38" length=1 maxlength="38"/>
                        ' . $nacex->l('Delivery observations', 'nacex') . ' (2)
                        <input type="text" id="nacex_obs2" name="nacex_obs2" value="' . $obs_def2 . '" style="width:94%; margin-left:15px; display: block;" length=1 maxlength="38" length=1 maxlength="38"/>
                    </p>';

        $html .= '<hr style="margin-top:2px" />
                    <p style="margin-left:5px;">' . $nacex->l('Additional shipping instructions', 'nacex') . ':</p> 
                    <textarea style="width:94%; margin-left:15px;height:75px;" length=1 maxlength="600" name="inst_adi">' . $texto_instrucciones . '</textarea>';

        $messJs = $nacex->l('Max. refund is', 'nacex');
        $html .= '<div>
        <input type="hidden" name="oToken" value="' . $urlToken . '" />';

        /** Comprobamos el servicio 44 **/
        $is44Active = Configuration::get('NACEX_SERVICIO44') == 'SI';

        $html .= '<input type="button" name="submitputexpedicion" value="Enviar" alt="' . $nacex->l('Generate expedition', 'nacex') . '" title="' . $nacex->l('Generate expedition', 'nacex') . '" class="button" onclick="descripcioncontenido();unitaria.post_order(Base_uri,idpedido.value,document.getElementById(\'generarExpedicion\'),\'' . isset($shop_codigo) . '\',\'' . $messJs . '\',\'' . $is44Active . '\');">
        <br><br>

		</div>
		</div> <!-- accordion panel -->
		</form>	
		</fieldset>
		</div>
		</div>
		<br/>';

        $tpre1 = Tools::getValue('nacex_tip_pre1', Configuration::get('NACEX_TIP_PREAL'));
        $tip_pre1 = empty($tpre1) ? 'N' : $tpre1;
        $html .= '<script type="text/javascript">';
        if (!isset($shop_codigo)) {   $html .= '
                 checkTipoPrealerta  ("'. $tip_pre1 . '");
                 checkModoPrealerta  ("'. Tools::getValue('nacex_mod_pre1', Configuration::get('NACEX_MOD_PREAL')) .'");
                 checkTipoSeguro     ("'. Tools::getValue('nacex_tip_seg', Configuration::get('NACEX_DEFAULT_TIP_SEG')) . '");
                 window.onload = nacexcontenido();';
        }
        $html .= '
                </script>';
        return $html;
    }
}
