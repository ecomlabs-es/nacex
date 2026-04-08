<?php

//header('Content-Type: text/html; charset=utf-8');
// session_start(); mexpositop 20180201
include_once dirname(__FILE__) . '/nacexWS.php';
include_once dirname(__FILE__) . '/nacexVIEW.php';
include_once dirname(__FILE__) . '/AdminConfig.php';
include_once dirname(__FILE__) . '/nacexutils.php';
include_once dirname(__FILE__) . '/nacexDAO.php';
//include_once dirname(__FILE__) . "/nacexcontrolversion.php";

/*
 * 2012 Nacex PrestaShop
 * 2017 mexpositop Adapted for PS1.7
 */

if (class_exists(Configuration::class)) {
    if (Configuration::get('NACEX_SHOW_ERRORS') == 'SI') {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    } else {
        error_reporting(0);
        ini_set('display_errors', '0');
    }
}

if (!defined('_PS_VERSION_')) {
    exit();
}

class nacex extends CarrierModule
{
    protected $_html = '';
    protected $_postErrors = [];
    protected $_confErrors = [];
    //protected $_moduleName = 'nacex';

    private $storeURL;
    public $id_carrier;

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You must have the PHP cURL library installed to install this module');
            return false;
        }

        nacexDAO::createTablesIfNotExists();

        //Creamos todos los transportistas (menos los genéricos)
        nacexDAO::setTransportistasFrontend();

        if (Shop::isFeatureActive()) { Shop::setContext(Shop::CONTEXT_ALL); }

        $ok = $this->installTab() && parent::install() ? true : false;
        $ok &= $this->registerHook('actionCarrierUpdate');
        $ok &= $this->registerHook('displayBeforeCarrier');
        $ok &= $this->registerHook('displayOrderConfirmation');
        $ok &= $this->registerHook('displayOrderDetail');
        $ok &= $this->registerHook('displayPDFInvoice');
        $ok &= $this->registerHook('displayHeader');
        $ok &= $this->registerHook('displayBackOfficeHeader');
        $ok &= $this->registerHook('displayBeforeBodyClosingTag');
        $ok &= $this->registerHook('actionValidateOrder');
        $ok &= $this->registerHook('displayAdminOrderMainBottom');
        $ok &= $this->registerHook('actionOrderGridQueryBuilderModifier');

        /*
        * Hooks registrados
        select *
        from ps_hook h, ps_hook_module hm, ps_module m
        where h.name= 'displayBackOfficeHeader'
        and hm.id_hook= h.id_hook
        and hm.id_module= m.id_module


        Hooks registrados
        select *
        from ps_hook h, ps_hook_module hm, ps_module m
        where m.name like  '%nacex%'
        and hm.id_hook= h.id_hook
        and hm.id_module= m.id_module

        */

        return $ok == 1;
    }

    protected function deleteCarriers()
    {
        nacexutils::writeNacexLog('----');
        nacexutils::writeNacexLog('INI NACEX::deleteCarriers() ');
        $query = 'SELECT id_carrier from ' . _DB_PREFIX_ . "carrier WHERE ncx LIKE 'nacex%'";
        $result = Db::getInstance()->executeS($query);
        foreach ($result as $value) {
            $query = 'DELETE from ' . _DB_PREFIX_ . 'carrier WHERE id_carrier = ' . $value['id_carrier'];
            if (!Db::getInstance()->execute($query)) {
                nacexutils::writeNacexLog('DeleteCarriers :: Error al borrar Carriers.');
                return false;
            }
        }
        nacexutils::writeNacexLog('FIN NACEX::deleteCarriers() ');
        nacexutils::writeNacexLog('----');
        return true;
    }

    public function uninstall()
    {
        nacexutils::writeNacexLog('----');
        nacexutils::writeNacexLog('INI NACEX::uninstall() ');
        // Borramos TABs NACEX
        /*Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'tab WHERE module = "' . $this->_moduleName . '"');
        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'authorization_role WHERE slug like \'%NACEX%\'');
        Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'tab_lang WHERE name like \'%NACEX%\'');*/
        // Borramos tablas, configuración y transportistas.
        if (Configuration::get('NACEX_BORRAR_CONFIGURACION') == 'SI') {
            nacexDAO::deleteNcxZones();
            $this->deleteCarriers();
            nacexDAO::DeleteTables();
            nacexDAO::DeleteConfiguration();
        }

        if (!$this->uninstallTab() || !parent::uninstall()) {
            return false; }

        // Limpiar si queda algún tab del módulo
        $delete = 'DELETE FROM ' . _DB_PREFIX_ . "tab WHERE module = 'nacex'";
        Db::getInstance()->execute($delete);

        nacexutils::writeNacexLog('FIN NACEX::uninstall() ');
        nacexutils::writeNacexLog('----');
        return true;
    }

    public function installTab()
    {
        $tabs = [
            [
                'name' => $this->l('Configuration'),
                'class_name' => 'AdminNacexConfig'
            ],
            [
                'name' => $this->l('Customer support'),
                'class_name' => 'AdminNacexFeedback'
            ],
            [
                'name' => $this->l('Delivery notes listing'),
                //'class_name' => 'AdminNacexListadoSalidas'
                'class_name' => 'nacextab'
            ],
            [
                'name' => $this->l('Massive expeditions'),
                //'class_name' => 'AdminNacexMasivo'
                'class_name' => 'nacextabMasivo'
            ],
            [
                'name' => $this->l('Unitary search'),
                //'class_name' => 'AdminNacexUnitario'
                'class_name' => 'nacexunitario'

            ],
            [
                'name' => $this->l('See logs'),
                'class_name' => 'AdminNacexLogs'
            ],
            [
                'name' => $this->l('Zone management'),
                'class_name' => 'AdminNacexZonas'
            ],
            [
                'name' => $this->l('Carrier management'),
                'class_name' => 'AdminNacexTarifas'
            ]
        ];

        $languages = Language::getLanguages(false);

        //Main Parent menu
        if (!(int)Tab::getIdFromClassName('AdminNacex')) {
            $parentTab = new Tab();
            $parentTab->active = 1;
            $parentTab->class_name = 'AdminNacex';
            $parentTab->name = [];
            foreach ($languages as $language) {
                $parentTab->name[$language['id_lang']] = $this->l('Nacex  V.' . nacexutils::nacexVersion);
            }
            $parentTab->id_parent = (int)Tab::getIdFromClassName('SELL');
            $parentTab->icon = 'local_shipping';
            $parentTab->module = $this->name;
            $parentTab->add();
        }

        //Sub menu code
        foreach ($tabs as $item) {
            //if (!(int)Tab::getIdFromClassName($item['class_name'])) {
            $tabId = (int)Tab::getIdFromClassName($item['class_name']);
            if (!$tabId) {
                $tabId = null;
            }

            $parentTabID = Tab::getIdFromClassName('AdminNacex');
            $parentTab = new Tab($parentTabID);

            $tab = new Tab($tabId);
            $tab->active = 1;
            $tab->class_name = $item['class_name'];
            $tab->name = [];
            foreach ($languages as $language) {
                $tab->name[$language['id_lang']] = $this->l($item['name']);
            }
            $tab->id_parent = $parentTab->id;
            $tab->module = $this->name;
            $tab->add();
            //}
        }
        return $parentTab->save() && (isset($tab) ? $tab->save() : true);
    }

    public function uninstallTab()
    {
        $tabId = (int)Tab::getIdFromClassName('AdminNacex');
        if (!$tabId) {
            return true;
        }

        $tab = new Tab((int)$tabId);

        return $tab->delete();
    }

    public function __construct()
    {
        $this->name = 'nacex';
        $this->tab = 'shipping_logistics';
        $this->version = nacexutils::nacexVersion;
        $this->author = 'Nacex';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        // $this->context = Context::getContext();

        $this->displayName = $this->l('Nacex module');
        $this->description = $this->l('Generate expeditions and print labels sending info to Nacex in only 1 click');

        $httpURL = Configuration::get('PS_SSL_ENABLED') ? 'https' : 'http';
        // Buscamos el http y lo reemplazamos por https o viceversa
        $this->storeURL = strpos(_PS_BASE_URL_, $httpURL) === false ? str_replace(substr(_PS_BASE_URL_, 0, strpos(_PS_BASE_URL_, ':')), $httpURL, _PS_BASE_URL_) : _PS_BASE_URL_;

    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();
    }

    /*
     * * Hook Header
     * *
     */
    public function hookDisplayHeader($params)
    {

        if (@$this->context->controller->php_self == 'order') {

            $this->context->controller->registerStylesheet('nacex-style', _MODULE_DIR_ . 'nacex/css/nacex.css', [
                'position' => 'head',
                'inline' => true,
                'priority' => 9,
                'server' => 'remote'
            ]);
        }
    }

    public function hookDisplayBackOfficeHeader($params)
    {

        if (@$this->context->controller->controller_name == 'AdminOrders') {
            $this->context->controller->addCSS(_MODULE_DIR_ . 'nacex/css/nacex.css', 'all');
        }
    }

    /**
     * Esta función Utiliza el Params de Prestashop y el Builder que se ejecuta en ciertos Hooks
     * para actualizar el estado de las expediciones y el estado de los pedidos a los pedidos de la pagina actual de pedidos.
     *
     * @param array $params
     */
    public function checkOrderListData(array $params) {

        nacexutils::writeNacexLog('INI checkOrderListData');

        $query_builder = $params['search_query_builder'];

        if ($query_builder instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            // executeQuery() para Doctrine DBAL 3.x+ (PS9), execute() para DBAL 2.x (PS 1.7.8/PS8)
            $result = method_exists($query_builder, 'executeQuery') ? $query_builder->executeQuery() : $query_builder->execute();
            $orders = method_exists($result, 'fetchAllAssociative') ? $result->fetchAllAssociative() : $result->fetchAll();

            if (!empty($orders)) {
                foreach ($orders as $order) {
                    $dataExpedition = nacexDAO::getDatosExpedicion($order['id_order']);

                    if (count($dataExpedition) !== 0) {
                        foreach ($dataExpedition as $expedition) {
                            //Update Expedition status
                            if ($this->do_i_have_to_update_expedition_status($expedition)) {
                                nacexutils::writeNacexLog('checkOrderListData :: ESTADOS');
                                $this->update_expedition_status($expedition, $_estado);

                                // Actualizamos el listado de pedidos actualizando todos los estados
                                $this->updateOrderStatusInOrderList($order['id_order'], $_estado, $order['current_state']);
                            }
                        }
                    }
                }
            }
        }
        nacexutils::writeNacexLog('FIN checkOrderListData');
    }

    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        nacexutils::writeNacexLog('---------');
        nacexutils::writeNacexLog('INI hookActionOrderGridQueryBuilderModifier :: ');
        $this->checkOrderListData($params);
        nacexutils::writeNacexLog('FIN hookActionOrderGridQueryBuilderModifier :: ');
        nacexutils::writeNacexLog('---------');
    }

    //updateOrderStatusInOrderList para actualizar estados de pedido en el listado
    private function updateOrderStatusInOrderList($id_order, $estado, $OrderStatusActual = '')
    {
        nacexutils::writeNacexLog('---------');
        nacexutils::writeNacexLog('INI updateOrderStatusInOrderList :: ');

        // Miramos de actualizar el estado del pedido
        $tipo = '';    // Por defecto el estado es Documentado
        if ($estado['estado'] == 'ANULADA') { $tipo = 'c'; }
        if ($estado['estado'] == 'OK') { $tipo = 'o'; }
        if ($estado['estado'] == 'TRANSITO') { $tipo = 'd'; }

        /** Si tipo es vacío (estado expedición REPARTO) no se actualiza el estado (se supone que es el mismo que documentado **/
        /** Además, si el estado del pedido == al estado de imprimir de la configuración y la expedición no es OK ni ANULADA, tampoco se mirará **/

        if ($OrderStatusActual === ''){
            $order = new Order($id_order);
            $OrderStatusActual = $order->getCurrentState();
        }

        if ($tipo != '') {
            if (!($OrderStatusActual == Configuration::get('NACEX_CAMBIAR_ESTADO_IMPRIMIR') && ($estado['estado'] != 'OK' && $estado['estado'] != 'ANULADA'))) {
                /** Cuando el pedido sea OK la primera vez entrará y lo cambiará, pero en la segunda no hará nada porque está el filtro en el
                 * cambio de estado del pedido */

                nacexDAO::actualizaEstadoPedido($id_order, $tipo);
            }
        }
        nacexutils::writeNacexLog('FIN updateOrderStatusInOrderList :: ');
        nacexutils::writeNacexLog('---------');
    }

    public function callbackMethod()
    {
        nacexutils::writeNacexLog('INI callbackMethod :: ');
        $_html = '';
        //GET ICON CREATE EXPEDITION
        $this->icon_create($_html);
        //GET EXPEDITIONS
        $_expediciones = nacexDAO::getDatosExpedicion($this->smarty->smarty->tpl_vars['order']->value->id);
        if (sizeof($_expediciones) != 0) {
            foreach ($_expediciones as $_expedicion) {
                //UPDATE ORDER STATUS
                if ($this->do_i_have_to_update_expedition_status($_expedicion)) {
                    $this->update_expedition_status($_expedicion, $_estado);
                    // Actualizamos el estado del pedido
                    $this->updateOrderStatusInOrderList($_expedicion['id_envio_order'], $_estado);

                    if (!$this->analyze_result_update_expedition_status($_estado, $_expedicion, $_html)) {
                        return $_html;
                    }
                }
                //GET ICON STATUS
                $this->get_icon_status($_expedicion, $_html);
            }
            $_html .= '</p>';
        } else {
            //GET TAG EXPEDITION NOT SAVED
            $this->expedition_not_saved($_html);
        }
        return $_html;
        /*$this->tag_shipment_status($_expedicion['estado'],$_html);
         $_html.='<input type="button" id="'.$this->smarty->smarty->tpl_vars['order']->value->id.'" value="Actualizar Estado">
                 <br>
                 <br>';
         $_html .= '<b><a href="http://www.nacex.es/seguimientoFormularioExterno.do?intcli=' . $_expedicion ["ag_cod_num_exp"] . '" target="_blank">' . $_expedicion ["ag_cod_num_exp"] . '</a></b>
                <br>
                <br>';*/
    }
    private function expedition_not_saved(&$_html) {
        $_html .= '<p class="label color_field" style="background-color:#ff5100;color:white">' . $this->l('NOT documented shipment') . '</p></p>';
    }
    private function icon_create(&$_html)
    {
        $_url = $this->context->link->getAdminLink('nacexunitario', true) . '&id_pedido=' . $this->smarty->smarty->tpl_vars['order']->value->id;
        $_html = '<p>
                        <a href="' . $_url . '">
                            <img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/servicios/svg/nacex_servicio_26.svg height="15" width="15" title="' . $this->l('Document shipments') . '">
                        </a>';
    }

    public function do_i_have_to_update_expedition_status($_expedicion)
    {
        nacexutils::writeNacexLog('do_i_have_to_update_expedition_status :: ¿Actualizamos estado de las expediciones en el listado de pedidos?');
        switch ($_expedicion['estado']) {
            case 'INCIDENCIA EXPEDICION':
                //15 MINUTES AGO
                if (($this->diff_time(strtotime($_expedicion['fecha_estado']))) > 900000) {
                    nacexutils::writeNacexLog('INCIDENCIA EXPEDICION :: TRUE - Hemos mirado hace 15 minutos');
                    return true;
                } else {
                    nacexutils::writeNacexLog('INCIDENCIA EXPEDICION :: FALSE - No ha pasado el tiempo estipulado desde la última consulta');
                    return false;
                }
                // no break
            case 'TRANSITO':
                //DAY AGO
                //if (($this->diff_time(strtotime($_expedicion['fecha_estado']))) > 86400000) {
                //nacexutils::writeNacexLog("TRANSITO :: TRUE - Hemos mirado hace 1 día");
                if (($this->diff_time(strtotime($_expedicion['fecha_estado']))) > 28800000) {
                    nacexutils::writeNacexLog('TRANSITO :: TRUE - Hemos mirado hace 8 horas');
                    return true;
                } else {
                    nacexutils::writeNacexLog('TRANSITO :: FALSE - No ha pasado el tiempo estipulado desde la última consulta');
                    return false;
                }
                // no break
            case 'REPARTO':
                //60 MINUTES AGO
                if (($this->diff_time(strtotime($_expedicion['fecha_estado']))) > 3600000) {
                    nacexutils::writeNacexLog('REPARTO :: TRUE - Hemos mirado hace 60 minutos');
                    return true;
                } else {
                    nacexutils::writeNacexLog('REPARTO :: FALSE - No ha pasado el tiempo estipulado desde la última consulta');
                    return false;
                }
                // no break
            case 'PENDIENTE':
                //60 MINUTES AGO
                if (($this->diff_time(strtotime($_expedicion['fecha_estado']))) > 3600000) {
                    nacexutils::writeNacexLog('PENDIENTE :: TRUE - Hemos mirado hace 60 minutos');
                    return true;
                } else {
                    nacexutils::writeNacexLog('PENDIENTE :: FALSE - No ha pasado el tiempo estipulado desde la última consulta');
                    return false;
                }
                // no break
            default:
                nacexutils::writeNacexLog('DEFAULT :: FALSE - El estado de la expedición ' . $_expedicion['exp_cod'] . ' es ' . $_expedicion['estado']);
                return false;
        }
    }
    private function diff_time($_date_expedicion) {
        return abs($_date_expedicion * 1000 - strtotime(date('Y/m/d h:i:s')) * 1000);
    }
    private function update_expedition_status($_expedicion, &$_estado) {
        $_estado = nacexWS::ws_getEstadoExpedicion($_expedicion);
    }
    private function analyze_result_update_expedition_status($_estado, $_expedicion, &$_html)
    {
        //$_result_ws=nacexWS::treatmentXML($_estado,"getEstadoExpedicion");
        if (isset($_estado[0]) && $_estado[0] == 'ERROR') {
            $_html .= '<span class="label color_field" style="background-color:#E10018;color:white">' . $this->l('Error in') . ': ' . $_expedicion['ag_cod_num_exp'] . '  | ' . $this->l('Error detail') . ': ' . $_estado[1] . '</span>';
            $_html .= '</p>';
            return false;
        } elseif ($_estado == 'ERROR') {
            $_html .= '<span class="label color_field" style="background-color:#E10018;color:white">' . $this->l('Error in') . ': ' . $_expedicion['ag_cod_num_exp'] . '  | ' . $this->l('Error detail') . ': ' . $_estado[1] . '</span>';
            $_html .= '</p>';
            return false;
        } else {
            nacexDAO::actDatosNacexExpediciones($this->smarty->smarty->tpl_vars['order']->value->id, $_expedicion, $_estado['estado'], $_estado['fecha'], $_estado['hora']);
            return true;
        }
    }
    private function get_icon_status($_expedicion, &$_html) {
        switch ($_expedicion['estado']) {
            case 'BAJA':
            case 'ANULADA':
                $_html .= '&nbsp<img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/cancelled.png height="15" width="15" title="' . $this->l('Expedition') . ':  ' . $_expedicion['ag_cod_num_exp'] . ' ' . $this->l('Cancelled') . '">';
                break;
            case 'INCIDENCIA EXPEDICION':
                $_html .= '&nbsp<img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/error.gif height="15" width="15" title="' . $this->l('Expedition') . ':  ' . $_expedicion['ag_cod_num_exp'] . ' ' . $this->l('in faulty') . '">';
                $this->tracking_link($_html, $_expedicion ['ag_cod_num_exp']);
                break;
            case 'TRANSITO':

                // Miramos que no haya ningún problema con las etiquetas
                $modelPrint = Configuration::get('NACEX_PRINT_MODEL');
                $canPrint = nacexWS::checkGetEtiqueta($modelPrint, $_expedicion['exp_cod']);
                $gestionAgencia = nacexDAO::getGestionAgencia($_expedicion, $canPrint);

                // Si se puede imprmir porque no hay ningún error
                if (!$gestionAgencia && $canPrint) {
                    $_html .= '&nbsp<img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/print_icon.png height="15" width="15" onclick="ionaPrint([' . $_expedicion['id_envio_order'] . ',' . $_expedicion['exp_cod'] . ']);" title="' . $this->l('Print expedition') . ':  ' . $_expedicion['ag_cod_num_exp'] . '.">';
                    $this->tracking_link($_html, $_expedicion ['ag_cod_num_exp']);
                    $_html .= nacexVIEW::showIoNA();
                } else {
                    $_html .= '&nbsp<img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/nosent.png height="15" width="15" title="' . $this->l('Expedition') . ':  ' . $_expedicion['ag_cod_num_exp'] . ' ' . $this->l('Cannot be modified') . '">';
                }
                break;
            case 'REPARTO':
                $_html .= '&nbsp<img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/sent.png height="15" width="15" title="' . $this->l('Expedition') . ':  ' . $_expedicion['ag_cod_num_exp'] . ' ' . $this->l('in distribution') . '">';
                $this->tracking_link($_html, $_expedicion ['ag_cod_num_exp']);
                break;
            case 'SOL SIN OK':
            case 'OK':
                $_html .= '&nbsp<img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/checked.gif height="15" width="15" title="' . $this->l('Expedition') . ':  ' . $_expedicion['ag_cod_num_exp'] . ' ' . $this->l('delivered') . '">';
                $this->tracking_link($_html, $_expedicion ['ag_cod_num_exp']);
                break;
            default:
                $_html .= '&nbsp<img src=' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/images/nosent.png height="15" width="15" title="' . $this->l('Expedition') . ':  ' . $_expedicion['ag_cod_num_exp'] . ' ' . $this->l('Pending') . '">';
        }
    }
    private function tracking_link(&$_html, $_cod_expedition) {
        $_html .= '&nbsp;<strong><a href="' . nacexDTO::$url_seguimiento . '/seguimientoFormularioExterno.do?intcli=' . $_cod_expedition . '" target="_blank">' . $_cod_expedition . '</a></strong>';
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        $html = '';

        // Revisamos si está instalado y habilitado el módulo OPC "Supercheckout"
        $opc_modules = ['supercheckout'];
        $isOpcEnabled = nacexutils::checkEnabledModule($opc_modules);

        if (@$this->context->controller->php_self == 'order' || @$this->context->controller->controller_name == 'AdminOrders' ||
            (@$this->context->controller->page_name == 'module-supercheckout-supercheckout' && $isOpcEnabled)) {
            //          echo '<script type="text/javascript" src="'._MODULE_DIR_ . 'nacex/js/jquery.showModalDialog.js"></script>';
            $html .= '<script type="text/javascript" src="' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/js/jquery.cluetip.js"></script>';
            $html .= '<script type="text/javascript" src="' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/js/nacex.js"></script>';
            $html .= '<script type="text/javascript" src="' . $this->storeURL . __PS_BASE_URI__ . 'modules/nacex/js/nacex_shop_cart.js"></script>';
            //          echo '<script type="text/javascript" src="'._PS_BASE_URL_.__PS_BASE_URI__ . 'modules/nacex/js/thickbox.js"></script>';
        }

        /*****/
        if (@$this->context->controller->php_self == 'order' || (@$this->context->controller->page_name == 'module-supercheckout-supercheckout' && $isOpcEnabled)) {
            // Tipo de Checkout. Es para la extensión "One Page Checkout PrestaShop" de 'PresTeamShop'
            $customopc = @$this->context->controller->opc != null ? 1 : 0;
            // Miramos si el módulo activo es el "The Checkout"
            if ($customopc == 0) {

                if (!$isOpcEnabled && @$this->context->controller->php_self == 'order') {
                    $customopc = @$this->context->controller->module->conf_prefix == 'opc_' ? 1 : 0; } else {
                        // Miramos si hay activo un OPC que no se ha detectado mediante estos métodos
                        $customopc = $isOpcEnabled ? 1 : 0;
                    }
            }

            $html .= "<script>
            var customopc = '" . $customopc . "';
            var opc_idBoton = '" . Configuration::get('NACEX_OPC_ID_BOTON') . "';
            var isIE = /*@cc_on!@*/false || !!document.documentMode;
            
            $(document).ready(function(){
                nacex_shop_seleccionado();
                if (typeof nxshopids !== 'undefined'){
                    if (nxshopids[$('input[name^=\"delivery_option\"]:checked', '#js-delivery').val().match(/\d+/)]) {
                        document.getElementById('div_table_nacex_shop').style.display = 'block';
                    }
                }
                if(customopc == 0 && !isIE) {
                    
                    $('button[name=\"confirmDeliveryOption\"]').click(function(event) {
                        if (nxshopids[$('input[name^=\"delivery_option\"]:checked', '#js-delivery').val().match(/\d+/)]) {
                            console.log($('#nxshop_codigo').val());
                            if ($('#nxshop_codigo').val() === '') {
                                    event.preventDefault();
                                    return submitJsDelivery();
                            } else {
                                $('#js-delivery').submit(function(event) {
                                    console.log('Si hay datos');
                                    return submitJsDelivery();
                                });
                            }
                        } else {
                            $('#js-delivery').submit(function(event) {
                                console.log('ES ESTANDAR');
                                return submitJsDelivery();
                            });
                        }
                    });
                } else if(customopc == 0 && isIE) {
                     $('form#js-delivery > button[type*=\'submit\']').bind('click', function(event) {
                         var form = $(this).parent;
                         if(submitJsDelivery()) {
                             form.submit();
                             event.preventDefault();
                         } else return submitJsDelivery();
                     });
                } else {
                    $(document).on('click','#'+opc_idBoton,function() {
                        checkPuntosSelected();
                    });
                }
                
                function submitJsDelivery() {
                    // verificamos que está seleccionado un Carrier NacexShop y que haya seleccionado un punto.
                    if (nxshopids[$('input[name^=\"delivery_option\"]:checked', '#js-delivery').val().match(/\d+/)]) {
                        if ( $('#nxshop_codigo').val() === '' ) {
                            alert('" . $this->l('Select NacexShop point where you will pick-up the shipment') . "');
                            window.scroll(0,0);
                            GetShop(carrierNacexshop, opc_idBoton);
                            document.getElementById('div_table_nacex_shop').style.display = 'block';
                            //GetShop(carrierNacexshop,'" . $this->storeURL . __PS_BASE_URI__ . "',codigo_postal_entrega,agencias_clientes);
                            console.log('No hay datos');
                            return false;
                        } else return true;
                    }
                    
                }
                
                function checkPuntosSelected() {
                    // verificamos que está seleccionado un Carrier NacexShop y que haya seleccionado un punto.
                    if (nxshopids[$('input[name^=\"delivery_option\"]:checked', '#js-delivery').val().match(/\d+/)]) {
                        if ( $('#nxshop_codigo').val() === '' ) {
                            alert('" . $this->l('Select NacexShop point where you will pick-up the shipment') . "');
                            window.scroll(0,0);
                            GetShop(carrierNacexshop, opc_idBoton);
                            document.getElementById('div_table_nacex_shop').style.display = 'block';
                            //GetShop(carrierNacexshop,'" . $this->storeURL . __PS_BASE_URI__ . "',codigo_postal_entrega,agencias_clientes);
                            $('#'+opc_idBoton).prop('disabled',true);
                            return false;
                        } else {
                            $('#'+opc_idBoton).prop('disabled',false);
                            return true;
                        }     
                    } else
                        return false;
                }
                
                function nacex_shop_seleccionado () {
                   $('input[name^=\"delivery_option\"]').each(function() {
                        if ($(this)[0].checked){
                            var num = $(this).val().match(/\d+/);
                            if ((nxshopids === null || nxshopids === 'undefined') && nxshopids[num]){
                               document.getElementById('div_table_nacex_shop').style.display = 'block';
                               window.scroll(0,0);
                            }else{
                                if(document.getElementById('div_table_nacex_shop')) {
                                    //ClearShop('" . $this->storeURL . __PS_BASE_URI__ . "');
                                    document.getElementById('div_table_nacex_shop').style.display = 'none';
                                }
                            }
                        }    
                   });
                }
            });

            </script>";

        }

        return $html;
        /****/
    }

    private function loadNacexCarriers($params)
    {
        $html = '';
        $nacexDTO = new nacexDTO();
        $pagina = $_SERVER['REQUEST_URI'];

        if (!strpos($pagina, 'order-opc')) {

            nacexutils::writeNacexLog('hookBeforeCarrier :: Obteniendo datos direccion en modalidad 5 pasos');

            if ($params['cart']->id_address_delivery) {
                $tools_id_address = $params['cart']->id_address_delivery;

                try {
                    $datosdireccion = Db::getInstance()->executeS('SELECT a.firstname,a.lastname,a.address1,a.postcode,a.city,a.phone,a.phone_mobile
    							FROM ' . _DB_PREFIX_ . 'address a
    							WHERE a.id_address = ' . $tools_id_address);
                    $cp = $datosdireccion[0]['postcode'];
                    nacexutils::writeNacexLog('hookBeforeCarrier :: Capturado CP del cliente (' . $cp . ')');
                } catch (Exception $e) {
                }
            }
        }

        $array_nxshop_id_carriers = [];
        $array_id_carriers = [];
        $array_nxint_id_carriers = [];

        $cart = $params['cart'];
        $carriersList = $cart->getDeliveryOptionList();
        $carriers = array_keys($carriersList[array_keys($carriersList)[0]]);

        foreach ($carriers as $key => $value) {
            $id = str_replace(',', '', $value);
            if ($nacexDTO->isNacexCarrier($id)) {
                nacexutils::writeNacexLog('loadNacexCarriers :: ES Estándar');
                array_push($array_id_carriers, intval($value));
            } elseif ($nacexDTO->isNacexShopCarrier($id)) {
                nacexutils::writeNacexLog('loadNacexCarriers :: ES NacexShop');
                array_push($array_nxshop_id_carriers, intval($value));
            } elseif ($nacexDTO->isNacexIntCarrier($id)) {
                nacexutils::writeNacexLog('loadNacexCarriers :: ES Internacional');
                array_push($array_nxint_id_carriers, intval($value));
            }
        }

        $num_nxshops = count($array_nxshop_id_carriers);
        $num_nxs = count($array_id_carriers);
        $num_nxint = count($array_nxint_id_carriers);
        $id_cart = $cart->id;
        $id_carrier = $cart->id_carrier;
        $id_ncx_carrier = $nacexDTO->getNacexIdCarrier();
        $id_ncxshop_carrier = $nacexDTO->getNacexShopIdCarrier();
        $isNacexShopCarrier = $nacexDTO->isNacexShopCarrier($id_carrier);
        $id_ncxint_carrier = $nacexDTO->getNacexIntIdCarrier();
        $tipoOrder = strpos($this->context->controller->php_self, 'order-opc') == false ? 'true' : 'false';
        $isGuest = $cart->isGuestCartByCartId($cart->id) ? 'true' : 'false';

        // Miramos si hay un OPC de terceros instalado

        // Revisamos si está instalado y habilitado el módulo OPC "Supercheckout"
        $opc_modules = ['supercheckout'];
        $isOpcEnabled = nacexutils::checkEnabledModule($opc_modules);

        // Tipo de Checkout. Es para la extensión "One Page Checkout PrestaShop" de 'PresTeamShop'
        $customopc = (isset($this->context->controller->opc) && @$this->context->controller->opc != null) ? 1 : 0;

        // Miramos si el módulo activo es el "The Checkout"
        //if ($customopc == 0) {

        if (!$isOpcEnabled && @$this->context->controller->php_self == 'order') {
            $customopc = (isset($this->context->controller->module->conf_prefix) && @$this->context->controller->module->conf_prefix == 'opc_') ? 1 : $customopc; } elseif (@$this->context->controller->page_name == 'module-supercheckout-supercheckout') {
                // Miramos si hay activo un OPC que no se ha detectado mediante estos métodos
                $customopc = 1;
            } else {
                // Miramos si hay activo un OPC que no se ha detectado mediante estos métodos
                $customopc = $isOpcEnabled ? 1 : 0;
            }
        //}

        // Para el OPC no hace falta cargar el jquery, si no, sí que hace falta cargarlo
        if ($customopc == 0) {
            $html .= "<script type='text/javascript' src='" . _MODULE_DIR_ . "nacex/js/jquery-3.3.1.min.js'></script>"; }

        $html .= "<script>                
                var id_cart = '" . $id_cart . "';                    
                var isGuest = '" . $isGuest . "';
                var href_opc = '';                    
                var agencias_clientes  = '" . Configuration::get('NACEX_AGCLI') . "';                   
                var api_mapa  = '" . Configuration::get('NACEX_GOOGLE_API') . "';                   
                var num_nxs = " . $num_nxs . "
                var ncx_carrier = '" . $id_ncx_carrier . "';
                var nxids = new Array();
            ";

        for ($i = 0; $i < $num_nxs; $i++) { $html .= "nxids['" . $array_id_carriers[$i] . "']=1;
            "; }

        $html .= '
            var num_nxshops = ' . $num_nxshops . "
            var ncxshop_carrier = '" . $id_ncxshop_carrier . "';
            var nxshopids = new Array();
            ";

        for ($i = 0; $i < $num_nxshops; $i++) { $html .= "nxshopids['" . $array_nxshop_id_carriers[$i] . "']=1;
            "; }

        $html .= '
            var num_nxint = ' . $num_nxint . "
            var ncxint_carrier = '" . $id_ncxint_carrier . "';
            var nxintids = new Array();
            ";

        for ($i = 0; $i < $num_nxint; $i++) { $html .= "nxintids['" . $array_nxint_id_carriers[$i] . "']=1;
            "; }

        $html .= '  
            var carrierNacexshop = 0;
            
            var currentCarrier = ' . $id_carrier . ";
            var customopc = '" . $customopc . "';
            var isOpcEnabled = '" . $isOpcEnabled . "';
            var opc_idDivGeneral = '" . Configuration::get('NACEX_OPC_ID_DIVGENERAL') . "';
            var codigo_postal_entrega = '';
            var opc_idBoton = '" . Configuration::get('NACEX_OPC_ID_BOTON') . "';
        
        $(function() {
            
            if(customopc == 0) {
                
                try{//PS1.7.3
                    codigo_postal_entrega = prestashop.customer.addresses[$('input[name=id_address_delivery]:checked').val()].postcode;
                }catch (e){
                    codigo_postal_entrega = prestashop.customer.addresses[prestashop.cart.id_address_delivery].postcode;
                }
                
                // Ponemos los datos del punto guardado en cookie
                let alreadySelectedPoint = checkSelectedPoint();
                
                $('input[name^=\"delivery_option\"]').on('click', function() {
                    
                    var num = $(this).val().match(/\d+/);
                    establecerCookie('selected_carrier',num);
                    
                    if(nxshopids[num] && alreadySelectedPoint) document.getElementById('div_table_nacex_shop').style.display = 'block';
                    else if (nxshopids[num] && !alreadySelectedPoint) {
                        //unsetDatosSession('" . $this->storeURL . __PS_BASE_URI__ . "');
                        //nxshopids[num] ? GetShop(num, customopc) : ClearShop('" . $this->storeURL . __PS_BASE_URI__ . "');
                        GetShop(num);
                        document.getElementById('div_table_nacex_shop').style.display = 'block';
                
                        // Rellenamos los datos de la tienda
                        getDatosSession('" . $this->storeURL . __PS_BASE_URI__ . "');
                        var shopDatos = $(\"#shop_datos\");
                        if(shopDatos && shopDatos.length){
                            rellenarNacexShop(shopDatos.attr(\"value\"));
                        }
                    } else {
                        //ClearShop('" . $this->storeURL . __PS_BASE_URI__ . "');
                        document.getElementById('div_table_nacex_shop').style.display = 'none';
                        //carrierNacexshop=2;
                    }
                });
            } else {
            
                if(!isOpcEnabled) {
               
                    $( document ).ajaxStop(function() {
                            
                        // Ponemos los datos del punto guardado en cookie
                        let alreadySelectedPoint = checkSelectedPoint();
    
                        $('input[name^=\"delivery_option\"]').each(function() {
                        
                            var num = $(this).val().match(/\d+/);
                            
                            // Comprobamos si la opción del shop está seleccionada para abrir el mapa directamente
                            //if($(this).prop('checked') && nxshopids[num] && alreadySelectedPoint) {
                            if($(this).prop('checked') && nxshopids[num]) {
                                document.getElementById('div_table_nacex_shop').style.display = 'block';
                            } else if($(this).prop('checked') && nxshopids[num] && alreadySelectedPoint) {
                                $('#'+opc_idBoton).prop('disabled',false);
                            }
                           
                        });
                        
                        // Usamos el unbind para que no se nos ejecute el click más de una vez
                        //$('input[name^=\"delivery_option\"]').unbind().click(function() {
                        $('input[name^=\"delivery_option\"]').parent().parent().unbind().click(function() {
                       
                            //var num = $(this).val().match(/\d+/);
                            var num = $(this).find('input').val().match(/\d+/);
                            establecerCookie('selected_carrier',num);
                            
                            if (nxshopids[num]) {
                                //unsetDatosSession('" . $this->storeURL . __PS_BASE_URI__ . "');
                                //nxshopids[num] ? GetShop(num, customopc) : ClearShop('" . $this->storeURL . __PS_BASE_URI__ . "');
                                GetShop(num, opc_idBoton);
                                document.getElementById('div_table_nacex_shop').style.display = 'block';
                                
                                // Rellenamos los datos de la tienda
                                getDatosSession('" . $this->storeURL . __PS_BASE_URI__ . "');
                                var shopDatos = $(\"#shop_datos\");
                                if(shopDatos && shopDatos.length > 0) {
                                    rellenarNacexShop(shopDatos);
                                    //$('#'+opc_idBoton).prop('disabled',false);
                                }
                            } else {
                                //ClearShop('" . $this->storeURL . __PS_BASE_URI__ . "');
                                document.getElementById('div_table_nacex_shop').style.display = 'none';
                                //carrierNacexshop=2;
                            }
                        });
                    });
                } else {
                
                    // Ponemos los datos del punto guardado en cookie
                    let alreadySelectedPoint = checkSelectedPoint();

                    $('input[name^=\"delivery_option\"]').each(function() {
                    
                        var num = $(this).val().match(/\d+/);
                        
                        // Comprobamos si la opción del shop está seleccionada para abrir el mapa directamente
                        //if($(this).prop('checked') && nxshopids[num] && alreadySelectedPoint) {
                        if($(this).prop('checked') && nxshopids[num]) {
                            document.getElementById('div_table_nacex_shop').style.display = 'block';
                        } else if($(this).prop('checked') && nxshopids[num] && alreadySelectedPoint) {
                            $('#'+opc_idBoton).prop('disabled',false);
                        }
                       
                    });
                    
                    // Usamos el unbind para que no se nos ejecute el click más de una vez
                    //$('input[name^=\"delivery_option\"]').unbind().click(function() {
                    $('input[name^=\"delivery_option\"]').parent().parent().unbind().click(function() {
                        
                        //var num = $(this).val().match(/\d+/);
                        var num = $(this).find('input').val().match(/\d+/);
                        
                        establecerCookie('selected_carrier',num);
                        
                        if (nxshopids[num]){
                            //unsetDatosSession('" . $this->storeURL . __PS_BASE_URI__ . "');
                            //nxshopids[num] ? GetShop(num, customopc) : ClearShop('" . $this->storeURL . __PS_BASE_URI__ . "');
                            GetShop(num, opc_idBoton);
                            document.getElementById('div_table_nacex_shop').style.display = 'block';
                            
                            // Rellenamos los datos de la tienda
                            getDatosSession('" . $this->storeURL . __PS_BASE_URI__ . "');
                            var shopDatos = $(\"#shop_datos\");
                            if(shopDatos && shopDatos.length > 0){
                                rellenarNacexShop(shopDatos);
                                //$('#'+opc_idBoton).prop('disabled',false);
                            }
                        } else{
                            //ClearShop('" . $this->storeURL . __PS_BASE_URI__ . "');
                            document.getElementById('div_table_nacex_shop').style.display = 'none';
                            //carrierNacexshop=2;
                        }
                    });
                }
            
                $('#'+opc_idDivGeneral).on('focusout', 'input[name=delivery_postcode]', function(event){

                    $(event.currentTarget).validate(null, null, messageValidate);

                    if (!OnePageCheckoutPS.IS_LOGGED) {
                        Address.updateAddress({object: 'delivery', load_carriers: true});
                    }
                    
                    codigo_postal_entrega = $('input[name=delivery_postcode]').val();
                });
            }
        });
        
        function checkSelectedPoint() {
            let nxShopCodigo = $(\"#nxshop_codigo\");
            if(nxShopCodigo.length && nxShopCodigo.val() === '') {
                try {
                    var datos = localStorage.getItem('nacex_shop_datos');
                    var cart = localStorage.getItem('nacex_shop_cart');
                    if(datos && cart == id_cart) {
                        rellenarNacexShop(datos, id_cart);
                        return true;
                    }
                } catch(e) {}
                // Fallback a cookie
                var cookieDatos = getCookie('opc_shop_datos');
                if(cookieDatos) {
                    rellenarNacexShop(cookieDatos, id_cart);
                    return true;
                }
            }
            return false;
        }
            
        function GetShop(carrier_sel, opc = false) {
            var uriPS = '" . $this->storeURL . __PS_BASE_URI__ . "';
            $('#order').fadeTo('fast', 0.4);
            modalWin('" . $this->storeURL . __PS_BASE_URI__ . "modules/nacex/nxShop.php?host=www.nacex.es&cp=' + codigo_postal_entrega + '&clientes=' + agencias_clientes + '&uriPS=' + uriPS + '&opc=' + opc);
            $('#order').fadeTo('fast', 1);
            return true;
        }
            
            </script>
                ";

        return $html;
    }

    /*
     * * Hook update carrier
     * *
     */
    public function hookActionCarrierUpdate($params)
    {
        nacexutils::writeNacexLog('---' . $this->context->controller->php_self);
        nacexutils::writeNacexLog('INI hookupdateCarrier :: id_carrier: ' . $params['id_carrier']);

        // Actualiza ID de transportista en Configuración cuando éstos han sido actualizados.
        // Al modificarse, éstos aumentan su ID. Este algoritmo sólo guardará la nueva ID si sólo difiere en -1

        $id_carrier = $params['id_carrier'];
        if ($id_carrier == Configuration::get('TRANSPORTISTA_NACEX')) {
            Configuration::updateValue('TRANSPORTISTA_NACEX', $params['carrier']->id);
        }
        if ($id_carrier == Configuration::get('TRANSPORTISTA_NACEXSHOP')) {
            Configuration::updateValue('TRANSPORTISTA_NACEXSHOP', $params['carrier']->id);
        }

        if ($id_carrier == Configuration::get('TRANSPORTISTA_NACEXINT')) {
            Configuration::updateValue('TRANSPORTISTA_NACEXINT', $params['carrier']->id);
        }

        $transportistas_f_piped = Configuration::get('NACEX_ID_TRANSPORTISTAS_F');
        $array_transportistas_f = explode('|', $transportistas_f_piped);

        if (in_array($id_carrier, $array_transportistas_f)) {
            $transportistas_f_piped = str_replace($id_carrier, $params['carrier']->id, $transportistas_f_piped);
            Configuration::updateValue('NACEX_ID_TRANSPORTISTAS_F', $transportistas_f_piped);
            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET ncx="nacex" WHERE id_carrier = ' . (int)$params['carrier']->id);
            nacexutils::writeNacexLog('hookupdateCarrier :: actualizado campo [ncx = nacex] del carrier');
        }

        $transportistas_nxshop_f_piped = Configuration::get('NACEX_ID_TRANSPORTISTAS_NXSHOP_F');
        $array_transportistas_nxshop_f = explode('|', $transportistas_nxshop_f_piped);
        if (in_array($id_carrier, $array_transportistas_nxshop_f)) {
            $transportistas_nxshop_f_piped = str_replace($id_carrier, $params['carrier']->id, $transportistas_nxshop_f_piped);
            Configuration::updateValue('NACEX_ID_TRANSPORTISTAS_NXSHOP_F', $transportistas_nxshop_f_piped);
            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET ncx="nacexshop" WHERE id_carrier = ' . (int)$params['carrier']->id);
            nacexutils::writeNacexLog('hookupdateCarrier :: actualizado campo [ncx = nacexshop] del carrier');
        }

        $transportistas_nxint_f_piped = Configuration::get('NACEX_ID_TRANSPORTISTAS_NXINT_F');
        $array_transportistas_nxint_f = explode('|', $transportistas_nxint_f_piped);
        if (in_array($id_carrier, $array_transportistas_nxint_f)) {
            $transportistas_nxint_f_piped = str_replace($id_carrier, $params['carrier']->id, $transportistas_nxint_f_piped);
            Configuration::updateValue('NACEX_ID_TRANSPORTISTAS_NXINT_F', $transportistas_nxint_f_piped);
            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET ncx="nacexint" WHERE id_carrier = ' . (int)$params['carrier']->id);
            nacexutils::writeNacexLog('hookupdateCarrier :: actualizado campo [ncx = nacexint] del carrier');
        }

        // Por Ãºltimo, actualizamos el campo de tip_serv apra no perder el servicio al que hace referencia el carrier
        $tip_serv_ant = Db::getInstance()->executeS('SELECT tip_serv,ncx FROM ' . _DB_PREFIX_ . 'carrier c WHERE c.id_carrier = ' . (int)$id_carrier);
        nacexutils::writeNacexLog('hookupdateCarrier :: realizando UPDATE carrier SET tip_serv=' . $tip_serv_ant[0]['tip_serv'] . ',ncx=' . $tip_serv_ant[0]['ncx'] . ' WHERE id_carrier = ' . (int)$params['carrier']->id);
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier SET tip_serv="' . pSQL($tip_serv_ant[0]['tip_serv']) . '",ncx="' . pSQL($tip_serv_ant[0]['ncx']) . '" WHERE id_carrier = ' . (int)$params['carrier']->id);

        nacexutils::writeNacexLog('FIN hookupdateCarrier :: id_carrier: ' . $params['id_carrier']);
        nacexutils::writeNacexLog('---');
    }

    public function hookDisplayOrderDetail($params)
    {
        $nacexDTO = new nacexDTO();

        $id_order = $params['order']->id;
        $id_carrier = (int)$params['order']->id_carrier;
        $id_cart = (int)$params['order']->id_cart;
        $datosexpedicion = nacexDAO::getDatosExpedicionNacex($id_order);
        $expe_codigo = $var_html = '';
        $ver_estado = Configuration::get('NACEX_SHOW_F_EXPE_STATE') == 'SI';
        $isShop = $nacexDTO->isNacexShopCarrier($id_carrier);

        $this->context->controller->registerStylesheet(
            'nacex.css',
            'modules/' . $this->name . '/css/nacex.css',
            [
                'media' => 'all',
                'priority' => 200,
            ]
        );

        $logoImg = 'NACEX_logo.svg';

        if ($isShop) {
            // Codigo|Alias|Nombre|Direccion|CP|Poblacion|Provincia|Telefono
            nacexutils::writeNacexLog('hookOrderDetailDisplayed :: obteniendo datos nacex shop');

            /*
                $datosnacexshop = nacexDAO::getDatosCartNacexShop($id_cart);
                $textoentreganacexshop = $this->l("NacexShop delivery");

                $('li.address_title:eq(1)').html('" . $textoentreganacexshop . " <span style=\"float: right; font-size: smaller; margin-right: 3px;\">" . @$datosnacexshop['shop_codigo'] . "</span>');
                $('ul.address:eq(1)').append('<li><span><i>" . $this->l('Attn') . ": ' + $('span.address_firstname:eq(0)').text() + ' ' + $('span.address_lastname:eq(0)').text() + '</i></span></li>');
             */

            $iso_code = Language::getIsoById(nacexutils::getCurrentLang());
            $logoImg = 'NACEXshop_sostenible_' . $iso_code . '.svg';
        }

        $logo = "<img src='" . _MODULE_DIR_ . 'nacex/images/logos/' . $logoImg . "' alt='Logo' />";
        $var_html .= "<ul class='estado_expedicion'>" . $logo;

        if ($ver_estado) {

            /** Añadir condición si está habilitado la generación de expediciones para transportistas externos **/
            $externos = Configuration::get('NACEX_FORCE_GENFORM') == 'SI';

            if ((($nacexDTO->isNacexCarrier($id_carrier) || $nacexDTO->isNacexShopCarrier($id_carrier) || $nacexDTO->isNacexIntCarrier($id_carrier) || $externos))
                && ((!empty($datosexpedicion)) && (isset($datosexpedicion[0]['exp_cod'])))) {

                // Cache WS frontend: solo llamar si la ultima actualizacion fue hace mas de 5 minutos
                $estadosFinales = ['OK', 'ENTREGADA', 'BAJA', 'ANULADA'];
                $wsCache = false;
                if (in_array($datosexpedicion[0]['estado'], $estadosFinales)) {
                    // Estado final — no necesita llamar al WS
                    $respuestaGetEstadoExpedicion = $datosexpedicion[0];
                    $wsCache = true;
                } elseif (!empty($datosexpedicion[0]['fecha_estado'])) {
                    $lastUpdate = strtotime($datosexpedicion[0]['fecha_estado']);
                    if ($lastUpdate !== false && (time() - $lastUpdate) < 300) {
                        $respuestaGetEstadoExpedicion = $datosexpedicion[0];
                        $wsCache = true;
                        nacexutils::writeNacexLog('hookOrderDetailDisplayed :: usando cache BD (actualizado hace ' . (time() - $lastUpdate) . 's)');
                    }
                }

                if (!$wsCache) {
                    $respuestaGetEstadoExpedicion = nacexWS::ws_getEstadoExpedicion($datosexpedicion[0], true);
                }

                nacexutils::writeNacexLog('hookOrderDetailDisplayed :: estado_code:' . (isset($respuestaGetEstadoExpedicion['estado_code']) ? $respuestaGetEstadoExpedicion['estado_code'] : 'N/A'));
                nacexutils::writeNacexLog('hookOrderDetailDisplayed :: estado:' . (isset($respuestaGetEstadoExpedicion['estado']) ? $respuestaGetEstadoExpedicion['estado'] : 'N/A'));
                if ($respuestaGetEstadoExpedicion['estado_code'] == 4 || $respuestaGetEstadoExpedicion['estado'] == 'OK' || $datosexpedicion['estado'] == 'ENTREGADA') {
                    /*** Cambio de estado al pedido al documentar expedición ***/
                    nacexutils::writeNacexLog('hookOrderDetailDisplayed :: Cambiamos el estado del pedido');
                    nacexDAO::actualizaEstadoPedido($id_order, 'o');

                    // Si está entregada, no hagas consulta a la BBDD
                    $fecha = explode(' ', $datosexpedicion['fecha_alta'])[0];
                    $hora = explode(' ', $datosexpedicion['fecha_alta'])[1];

                    $var_html .= "
                            <h3 class='ncx_checked'>" . $this->l('Expedition status') . '</h3>
                            <p><strong>' . $this->l('Date') . ':</strong> ' . $fecha . '</p>
                            <p><strong>' . $this->l('Hour') . ':</strong> ' . $hora . '</p>
                            <p><strong>' . $this->l('Status') . ':</strong> ' . $datosexpedicion['estado'] . '</p>';
                } else { // Asegurarse del valor cuando la expedición se ha entregado

                    $claseNcx = $cuerpo = '';
                    $botonSeguimientoPedido = "<input class='ncx_button' type='button' style='text-align: center;' onclick=window.open(" . "'" . 'https://www.nacex.es/seguimientoFormularioExterno.do?intcli=' . $datosexpedicion[0]['ag_cod_num_exp'] . '&intcliv=c' . "'" . "); value='Seguimiento' />";

                    if (isset($respuestaGetEstadoExpedicion) && is_array($respuestaGetEstadoExpedicion)) {
                        if (isset($respuestaGetEstadoExpedicion[0]) && $respuestaGetEstadoExpedicion[0] == 'ERROR') {
                            if (isset($respuestaGetEstadoExpedicion[2]) && $respuestaGetEstadoExpedicion[2] == '5611') {
                                $cuerpo = "<p align='center'><strong><em>" . nacexutils::toUtf8($this->l('Pending integration')) . '</em></strong></p>';
                                $claseNcx = 'ncx_pending';
                            } else {
                                $cuerpo = '<p>' . $respuestaGetEstadoExpedicion[0] . ' ' . $respuestaGetEstadoExpedicion[2] . '</p>';
                                $claseNcx = 'ncx_error';
                            }
                        } else {
                            $cuerpo = '
                                <p><strong>' . $this->l('Date') . ':</strong> ' . $respuestaGetEstadoExpedicion['fecha'] . '</p>
                                <p><strong>' . $this->l('Hour') . ':</strong> ' . $respuestaGetEstadoExpedicion['hora'] . '</p>
                                <p><strong>' . $this->l('Obs.') . ':</strong> ' . $respuestaGetEstadoExpedicion['observaciones'] . '</p>
                                <p><strong>' . $this->l('Status') . ':</strong> ' . $respuestaGetEstadoExpedicion['estado'] . '</p>
                                ' . $botonSeguimientoPedido;
                            $claseNcx = 'ncx_checked';
                        }
                    } else {
                        $cuerpo = "<p align='center'><strong><em>" . $this->l('No data') . '</em></strong></p>';
                        $claseNcx = 'ncx_question';
                    }
                }

                $var_html .= "
                        <h3 class='" . $claseNcx . "'>" . $this->l('Expedition status') . '</h3>' .
                    $cuerpo;

            } elseif (($nacexDTO->isNacexCarrier($id_carrier) || $nacexDTO->isNacexShopCarrier($id_carrier) || $externos)) {
                $var_html .= "
                        <h3 class='ncx_warning'>" . $this->l('Expedition status') . '</h3>
                        <em>' . $this->l('Expedition pending on documenting') . '</em>';
            }
            $var_html .= '</ul>';
        }

        $this->_html .= '
            <div class="addresses">
                <div class="col-lg-6 col-md-6 col-sm-6">
                    ' . $var_html . '
                </div>
            </div>';

        // Para que sea un bloque a parte
        $this->_html .= '<div class="clearfix"></div>';

        return $this->_html;
    }

    public function hookDisplayBeforeCarrier($params)
    {
        nacexutils::writeNacexLog('---' . $this->context->controller->php_self);
        nacexutils::writeNacexLog('INI hookBeforeCarrier ::');

        $logoservswidth = Configuration::get('NACEX_LOGOSERVS_WIDTH');
        $logoservswidth .= isset($logoservswidth) && $logoservswidth != null && $logoservswidth != '' ? 'px' : 'auto';
        require_once(dirname(__FILE__) . '/ROnacexshop.php');
        require_once(dirname(__FILE__) . '/nacexWS.php');
        $_nacex_shop = new nacexshop();
        $_ws = new nacexWS();

        $this->_html .= $this->loadNacexCarriers($params);

        // Si hay una dirección insertada es cuando podemos acceder a las llamadas
        if ((int)$this->context->cart->id_address_delivery != 0) {
            //GET AGENCIA COORDENADAS
            nacexutils::writeNacexLog('hookBeforeCarrier :: llamada a get_Agencia3');
            $addressDelivery = new Address((int)$this->context->cart->id_address_delivery);
            if ($addressDelivery->postcode != '') {

                $_coordenadas = $_ws->get_Agencia3($addressDelivery->postcode);
                $_coordenadas = $_ws->treatmentXML($_coordenadas, 'getAgencia3');

                /** Añadimos funcionalidad WS sin conexión **/
                $conn = true;
                if ($_coordenadas[0] != '500ERROR') {
                    $_lat = floatval($_coordenadas[9]);
                    $_long = floatval($_coordenadas[10]);
                    //GET LIST OF NACEXSHOPS
                    nacexutils::writeNacexLog('hookBeforeCarrier :: llamada a getPuntoEntregaGPS');
                    $_agencias = $_ws->getPuntoEntregaGPS($_lat, $_long);
                    $_agencias = $_ws->treatmentXML($_agencias, 'getPuntoEntregaGPS');
                    //GET & ADD SHOP NAME
                    $_nacex_shop->add_nacex_shop_name($_agencias);
                } else { // Si no hay conexión WS
                    $conn = false;
                    $cp = $addressDelivery->postcode;
                    $add = $addressDelivery->address1 . ' ' . $addressDelivery->address2;
                    $prov = State::getNameById($addressDelivery->id_state);
                    // Si no hay coordenadas de los mapas, coger por CP: comparar 5 cifras, 3 y 2 hasta que haga un mínimo de 10 puntos
                    $address = $add . ', ' . $cp . ', ' . $addressDelivery->city . ', ' . $prov . ', ' . $addressDelivery->country;
                    //$address = "$cp?region=$add[3]&geoit=JSON&streetname=$add[0]&cityname=$add[1]";
                    if ($_nacex_shop->getMapsCoordinates($address, $lat, $lon)) { $return = [$lat, $lon, $cp]; } else { $return = [$cp]; }

                    $_agencias = $_nacex_shop->getAgenciasTratadas($return);
                }

                $this->_html .= "<input type='hidden' id='nacex_carrier' name='nacex_carrier' />
                         <input type='hidden' id='shop_datos' name='shop_datos' />
			<style>
				td.carrier_name label img, td.delivery_option_logo img{
					width: " . $logoservswidth . ";
				}
			</style>
			<div id='div_table_nacex_shop' style='display:none'>";

                // IE adaptation - Get browser
                $u_agent = $_SERVER['HTTP_USER_AGENT'];
                $bname = null;

                //First get the platform?
                if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
                    $bname = 'IE';
                    $ub = 'MSIE';
                } elseif (preg_match('/Trident/i', $u_agent)) {
                    $bname = 'IE';
                    $ub = 'MSIE';
                }

                /*if (Configuration::get('NACEX_GOOGLE_API') != "" && is_null($bname)) {
                    $this->_html .= "<script async defer src='https://maps.googleapis.com/maps/api/js?key=" . Configuration::get('NACEX_GOOGLE_API') . "&callback=initMap'></script>";
                    $this->_html .= $_nacex_shop->nacex_shop_map($_agencias, $addressDelivery->postcode, $conn);
                } else {
                    $this->_html .= $_nacex_shop->nacex_shop_list($_agencias, $addressDelivery->postcode, $conn);
                }*/

                // Tipo de Checkout. Es para la extensión "One Page Checkout PrestaShop" de 'PresTeamShop'
                $customopc = (isset($this->context->controller->opc) && @$this->context->controller->opc != null) ? 1 : 0;
                if ($customopc == 0) { $customopc = (isset($this->context->controller->module->conf_prefix) && @$this->context->controller->module->conf_prefix == 'opc_') ? 1 : 0; }

                $this->_html .= "<br>
                             <h3 style='color:#ff5100;'>" . $this->l('Selected NacexShop point') . "</h3>
				<table align='center' id='nacexshopChosen'>
				<!--1085|0831-03|LIBRERíA OPERA|Major 7|08870|SITGES|BARCELONA|938942143-->
				    <tr class='odd'><td>" . $this->l('Code') . ":</td><td><input id='nxshop_codigo' size='60' type='text'readonly/></td></tr>
				    <tr><td>" . $this->l('Alias') . ":</td><td><input id='nxshop_alias' size='60' type='text' readonly/></td></tr>
				    <tr class='odd'><td>" . $this->l('Name') . ":</td><td><input id='nxshop_nombre' size='60' type='text' readonly/></td></tr>
                    <tr><td>" . $this->l('Address') . ":</td><td><input id='nxshop_direccion' size='60' type='text' readonly/></td></tr>
                    <tr class='odd'><td>" . $this->l('Postcode') . ":</td><td><input id='nxshop_cp' size='60' type='text' readonly/></td></tr>
                    <tr><td>" . $this->l('City') . ":</td><td><input id='nxshop_poblacion' size='60' type='text' readonly/></td></tr>
                    <tr class='odd'><td>" . $this->l('State') . ":</td><td><input id='nxshop_provincia' size='60' type='text' readonly/></td></tr>
                    <!-- <tr><td>" . $this->l('Phone') . ":</td><td><input id='nxshop_telefono' size='60' type='text' readonly/></td></tr> -->
                </table>";

                // Añadimos botón de selección de punto
                $this->_html .= '<div align="center" style="clear: left;">
                    <input type="button" align="center" class="ncx_button inverse" id="selectAnotherPoint" value="' . $this->l('Seleccionar otro punto') . "\" onclick=\"GetShop('0')\">
                </div>";
                $this->_html .= '</div>

             <br>
			';
            }

            // <div id='dialog-confirm'></div>
            //nacexutils::writeNacexLog("Llamamos al getOrderShippingCost ::");
            //$shipping_cost = $this->getOrderShippingCost($params, 0);
            nacexutils::writeNacexLog('FIN hookBeforeCarrier ::');
            nacexutils::writeNacexLog('----');
            return $this->_html;
        }
    }

    /*
     * Hook que surge después de que el cliente haya confirmado el pedido.
     * Necesario para modalidad OPC
     */
    public function hookDisplayOrderConfirmation($params)
    {
        nacexutils::writeNacexLog('---' . $this->context->controller->php_self);
        nacexutils::writeNacexLog('INI hookOrderConfirmation ::');

        isset($params['order']->id) ? nacexutils::writeNacexLog('INI hookOrderConfirmation :: id_order: ' . $params['order']->id) : nacexutils::writeNacexLog('INI hookOrderConfirmation :: id_order: NULL !!');

        // En los pagos con TPV pasa que el id_carrier del $params != al que guardamos en la cookie
        $carrier_activo = @unserialize($this->context->cookie->__get('carriers_nacex'));
        if (!is_array($carrier_activo)) {
            $carrier_activo = [];
        }

        // Cogemos el valor del carrier que el cliente ha seleccionado
        $selected_carrier = null;
        if ($this->context->cookie->__isset('selected_carrier')) {
            $selected_carrier = $this->context->cookie->__get('selected_carrier');
        }
        $orderCarrier = isset($params['order']->id_carrier) ? $params['order']->id_carrier : null;
        // Revisamos cuál es el carrier seleccionado correcto
        $isCarrierActivo = (is_array($carrier_activo) && !is_null($orderCarrier) && in_array($orderCarrier, $carrier_activo));

        if ($isCarrierActivo && $selected_carrier == $orderCarrier) { $carrier = $orderCarrier; } elseif (!is_null($selected_carrier) && !empty($selected_carrier)) { $carrier = $selected_carrier; } else { $carrier = $orderCarrier; }

        // global $cookie;
        $nacexDTO = new nacexDTO();

        $id_order = isset($params['order']->id) ? $params['order']->id : null;
        $id_cart = isset($params['order']->id_cart) ? $params['order']->id_cart : null;
        $id_address_delivery = isset($params['order']->id_address_delivery) ? $params['order']->id_address_delivery : null;
        $id_customer = isset($params['order']->id_customer) ? $params['order']->id_customer : null;
        // $nacex_carrier = null;

        if ($nacexDTO->isNacexCarrier($carrier)) {
            Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'cart SET ncx="1" WHERE id_cart = ' . (int)$id_cart);
            nacexutils::writeNacexLog('hookOrderConfirmation :: actualizado campo nacex [ncx=1] de la tabla cart.');
        } elseif ($nacexDTO->isNacexShopCarrier($carrier)) {
            nacexutils::writeNacexLog('hookOrderConfirmation :: isNacexShopCarrier');
            $shop_datos = '';

            // Modalidad OPC
            if (isset($_COOKIE['opc_id_cart']) && isset($_COOKIE['opc_shop_datos']) && $_COOKIE['opc_id_cart'] == $id_cart) {
                $shop_datos = $_COOKIE['opc_shop_datos'];
                // Borramos las dos cookies estableciendo fecha de caducidad en el pasado
                setcookie('opc_shop_datos', '', time() - 3600);
                setcookie('opc_id_cart', '', time() - 3600);

                Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'cart SET ncx="' . pSQL($shop_datos) . '" WHERE id_cart = ' . (int)$id_cart);
                nacexutils::writeNacexLog('hookOrderConfirmation :: actualizado campo nacex [ncx=' . $shop_datos . '] de la tabla cart.');
            } else {
                $ret = Db::getInstance()->executeS('SELECT ncx FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart = ' . (int)$id_cart);
                $shop_datos = $ret[0]['ncx'];
                nacexutils::writeNacexLog('hookOrderConfirmation :: shop_datos obtenidos -' . $shop_datos);
            }

            // --------------------------------------------------------------------------------------------
            //nacexDAO::setNacexShopAddressinBD($id_order, $id_cart, $id_address_delivery, $id_customer, $shop_datos);
            // --------------------------------------------------------------------------------------------
        }

        /* Eliminamos los contenidos de la cookie para no hacer tantas llamadas a WS */
        nacexutils::writeNacexLog('hookOrderConfirmation :: Eliminamos contenido de la cookie');
        foreach ($carrier_activo as $i => $val) {
            $this->context->cookie->__unset('checked_carrier_' . $val);
            $this->context->cookie->__unset('cp_nacex_' . $val);
        }

        $this->context->cookie->__unset('carriers_nacex');

        if (isset($_COOKIE['opc_shop_datos'])){
            setcookie('opc_shop_datos', '', time() - 3600, '/');
            unset($_COOKIE['opc_shop_datos']);
            $this->context->cookie->__unset('opc_shop_datos');
        }

        if (isset($_COOKIE['selected_carrier'])){
            setcookie('selected_carrier', '', time() - 3600, '/');
            unset($_COOKIE['selected_carrier']);
            $this->context->cookie->__unset('selected_carrier');
        }

        // Limpiar localStorage del punto NacexShop seleccionado
        $this->_html .= '<script>try{localStorage.removeItem("nacex_shop_datos");localStorage.removeItem("nacex_shop_cart");}catch(e){}</script>';

        isset($params['order']->id) ? nacexutils::writeNacexLog('FIN hookOrderConfirmation :: id_order: ' . $params['order']->id) : nacexutils::writeNacexLog('FIN hookOrderConfirmation :: id_order: NULL!!');

        nacexutils::writeNacexLog('----');
    }

    private function renderAdminOrder($params, $ver177 = false)
    {
        nacexutils::writeNacexLog('---' . $this->context->controller->php_self);
        nacexutils::writeNacexLog('INI hookAdminOrder :: id_order: ' . $params['id_order']);

        require_once(dirname(__FILE__) . '/ROnacexshop.php');
        $_nacex_shop = new nacexshop();
        $nacexDTO = new nacexDTO();

        $this->context->controller->addJS(_MODULE_DIR_ . 'nacex/js/nacexScroll.js');

        $id_order = (int)$params['id_order'];
        $datospedido = nacexDAO::getDatosPedido($id_order);

        $isNacex = $nacexDTO->isNacexCarrier($datospedido[0]['id_carrier']);
        $isShop = $nacexDTO->isNacexShopCarrier($datospedido[0]['id_carrier']);
        $isInt = $nacexDTO->isNacexIntCarrier($datospedido[0]['id_carrier']);

        // El transportista es de Nacex o por configuración ha elegido forzar mostrar formulario Generar Expedición
        //if ((isset($datospedido[0]['ncx']) && $datospedido[0]['ncx'] != "") || (isset($datospedido[0]['id_carrier']) && $datospedido[0]['id_carrier'] == nacexDTO::getNacexIdCarrier()) || nacexDTO::isNcxCarrier($datospedido[0]['id_carrier']) || Configuration::get('NACEX_FORCE_GENFORM') == "SI") {
        if ((isset($datospedido[0]['ncx']) && $datospedido[0]['ncx'] != '') || Configuration::get('NACEX_FORCE_GENFORM') == 'SI' ||
            ($isNacex || $isShop || $isInt)) {
            // La útlima línea es cuando el campo ncx del pedido está vacío, null o no existe (no lo podemos saber)

            //$externalCarriers = explode('|', Configuration::get('NACEXSHOP_EXTERNAL_MODULES'));

            //$isShop = nacexDTO::isNacexShopCarrier($datospedido[0]["id_carrier"]) || in_array($datospedido[0]['id_carrier'], $externalCarriers);
            //$isShop = nacexDTO::isNacexShopCarrier($datospedido[0]["id_carrier"]);

            // Lo del Active es porque cuando el carrier no estaba activado y tenían la de 3os habilitada daba error 500
            if ($isShop && $isShop['active']) {

                // Comprobamos si la dirección del pedido coincide con la que hemos guardado en el carrito para los Shop
                $shop_address = nacexDAO::getDatosCartNacexShop($datospedido[0]['id_cart']);

                // Comparamos si los nombres son iguales. Como es un shop no hay otra alternativa (se envía a persona diferente, p.ej)
                //if (isset($datospedido[0]['firstname']) && !is_null($shop_address) && !empty($shop_address) && strcmp($datospedido[0]['firstname'], $shop_address['shop_alias']) != 0) {

                // Ahora, con la reasignación de campos para el tema del email, comparamos si las direcciones son iguales
                if (!is_null($shop_address) && !empty($shop_address) && !is_null($shop_address['shop_direccion']) && strcmp($datospedido[0]['address1'], $shop_address['shop_direccion']) != 0) {
                    $datospedido[0]['alias'] = $shop_address['shop_nombre'];
                    $datospedido[0]['company'] = $shop_address['shop_alias'];
                    $datospedido[0]['address1'] = $shop_address['shop_direccion'];
                    $datospedido[0]['address2'] = $shop_address['shop_codigo'] . '|' . $shop_address['shop_nombre'];
                    $datospedido[0]['postcode'] = $shop_address['shop_cp'];
                    $datospedido[0]['city'] = $shop_address['shop_poblacion'];

                    nacexutils::provincia($shop_address['shop_cp'], $prov);
                    $provincia = State::getIdByName($prov);
                    $datospedido[0]['id_state'] = $provincia;
                    $datospedido[0]['ncx'] = implode('|', $shop_address);

                    nacexDAO::setNacexShopAddressinBD($id_order, $datospedido[0]['id_cart'], $datospedido[0]['id_address_delivery'], $datospedido[0]['id_customer'], implode('|', $shop_address));
                } elseif (Tools::getValue('cpPointsChoices') !== false && Tools::getValue('cpPointsChoices') !== '') {

                    require_once(dirname(__FILE__) . '/VInacexshop.php');
                    $vi_nacex_shop = new VInacexshop();
                    $prov = '';

                    $cpPointsChoices = Tools::getValue('cpPointsChoices');

                    // Buscar la tienda de ese id
                    $shopPointSelected = $_nacex_shop->getShopByCode($cpPointsChoices, true)[0];

                    $shopAddress = explode('|', $shopPointSelected[0]);
                    nacexutils::provincia($shopAddress[4], $prov);
                    $provincia = State::getIdByName($prov);

                    $datospedido[0]['alias'] = $shopAddress[2];
                    $datospedido[0]['company'] = $shopAddress[1];
                    $datospedido[0]['address1'] = $shopAddress[3];
                    $datospedido[0]['address2'] = $shopAddress[0] . '|' . $shopAddress[2];
                    $datospedido[0]['postcode'] = $shopAddress[4];
                    $datospedido[0]['city'] = $shopAddress[5];
                    $datospedido[0]['id_state'] = $provincia;
                    $datospedido[0]['ncx'] = implode('|', $shopAddress);

                    nacexDAO::setNacexShopAddressinBD($id_order, $datospedido[0]['id_cart'], $datospedido[0]['id_address_delivery'], $params['cart']->id_customer, implode('|', $shopAddress));
                }

                nacexutils::changeAddressIfNacexShop($datospedido[0]);
            }

            $_nacex_shop->importFromCsvFile(explode('|', $datospedido[0]['ncx'])[0], $isShop);

            $solicitud = 0;
            //if (!empty($_POST)) {
            // Crear Expedición ** 1
            //CHECK HASH FORM
            include_once dirname(__FILE__) . '/hash.php';
            $_validate_hash = new hash();
            $_resultado = $_validate_hash->validate_hash();
            if (Tools::isSubmit('submitputexpedicion') && $_resultado == true) {
                $this->_html = nacexWS::putExpedicion($id_order, $datospedido, null, Tools::isSubmit('submitcambioexpedicion'));
                $solicitud += 1;
            }
            if (Tools::isSubmit('printDevolucion') && $_resultado == true) {
                $this->_html = nacexWS::putExpedicionDev($id_order, $datospedido, null, Tools::isSubmit('submitcambioexpedicion'));
                $solicitud += 1;
            }
            if ((Tools::isSubmit('submitputexpedicion') || Tools::isSubmit('printDevolucion')) && $_resultado == false) {
                $this->_html = '<br><div id="messages-nacex" class="bootstrap" style="margin-top:10px">';
                $this->_html .= '<div class="alert alert-danger" style="width:auto">';
                $this->_html .= '<strong>' . $this->l('Request has already been Processed') . '</strong>';
                $this->_html .= '</div></div>';
            }
            // c@mbio ** 2
            if (Tools::isSubmit('submitcambioexpedicion')) {
                $this->_html = nacexVIEW::showExpedicionForm($id_order, $datospedido, Tools::getValue('exp_cod'), Tools::isSubmit('submitcambioexpedicion'), $ver177);
                $solicitud += 2;
            }

            // Cancelar Expedición ** 3
            if (Tools::isSubmit('submitcancelexpedicion')) {
                nacexutils::writeNacexLog('hookAdminOrder :: Detectado submitcancelexpedicion para exp_cod: ' . Tools::getValue('exp_cod'));
                $this->_html = nacexWS::cancelExpedicion($id_order, Tools::getValue('exp_cod'));
                $solicitud += 3;
            }
            //}

            $arraydatos = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'nacex_expediciones WHERE id_envio_order = ' . (int)$id_order . ' ORDER BY fecha_alta ASC');

            $lastElement = end($arraydatos);

            foreach ($arraydatos as $datos) { // muestra detalle de la expediciones vinculadas a la orden de pedido

                $respuestaGetEstadoExpedicion = nacexWS::ws_getEstadoExpedicion($datos);

                // Código que añadimos para controlar el WS sin conexión
                if (isset($respuestaGetEstadoExpedicion['expe_codigo']) && $respuestaGetEstadoExpedicion['expe_codigo'] == '500ERROR') {
                    $this->_html .= '<p class="alert alert-info">';
                    $this->_html .= $this->l('There is a problem with Nacex Web Service connection and some functionality may be affected, as well as <strong>some expedition status</strong>. Please, wait few minutes until connextion will be restored. We apologize for the inconvenients');
                    $this->_html .= '</p>';
                }

                // Si el estado de la expedición es un OK (4), entonces cambiamos el estado del pedido.
                // Teniendo en cuenta que no sea el estado final en el que debería estar
                $order = new Order($id_order);
                $est = $order->getCurrentState();
                $final_est = Configuration::get('NACEX_CAMBIAR_ESTADO_OK');

                if (($final_est != '' && $est != $final_est) &&
                    ($respuestaGetEstadoExpedicion['estado_code'] == 4 || $respuestaGetEstadoExpedicion['estado'] == 'OK' || $datos['estado'] == 'ENTREGADA')) {

                    /*** Cambio de estado al pedido al documentar expedición ***/
                    nacexutils::writeNacexLog('hookAdminOrder :: Cambiamos el estado del pedido al estar en OK');
                    nacexDAO::actualizaEstadoPedido($id_order, 'o');
                }

                // marcamos la solicitud solo de la Última del listado
                $this->_html .= nacexVIEW::showExpedicionBoxInfo($datos, $id_order, $lastElement == $datos ? $solicitud : 0, $ver177);
            }

            if (!Tools::isSubmit('submitcambioexpedicion')) {
                $this->_html .= nacexVIEW::showExpedicionForm($id_order, $datospedido, null, false, $ver177);
            }

            /* jquery
             * noConflict strategy END

            $this->_html .= '
                <script type="text/javascript">
                   $ = tmp;   // jQuery noConflict strategy, restore main jquery control
                </script>';
            */
        }
        nacexutils::writeNacexLog('FIN hookAdminOrder :: id_order: ' . $params['id_order']);
        nacexutils::writeNacexLog('----');

        return $this->_html;
    }

    /**
     * Instalación / Configuración
     * El simple hecho de añadir esta función provoca la aparición de un enlace
     * "Configurar" en la instalación realizada del módulo.
     */
    public function getContent()
    {
        $this->_html .= '<h2>NACEX</h2>';
        if (! empty($_POST) and Tools::isSubmit('submitSave')) {
            $this->_postValidation();
            if (! sizeof($this->_postErrors)) {
                $this->_postProcess(); } else { foreach ($this->_postErrors as $err) {
                    $this->_html .= '<div class="alert error"><img src="' . _PS_IMG_ . 'admin/forbbiden.gif" alt="nok" />&nbsp;' . $err . '</div>'; } }
        }
        $this->_displayForm();
        return $this->_html;
    }

    private function _displayForm()
    {
        $this->_html = getFormularioConfiguracion($this);
    }

    public function getErroresConfiguracion()
    {
        return $this->_confErrors;
    }

    private function _postValidation()
    {
        $this->_confErrors = validarFormularioConfiguracion($this);
        // Check configuration values
        /*
         * if(Tools::getValue('nacex_agcli') == '' &&
         * Tools::getValue('nacex_wspassword') == '' &&
         * Tools::getValue('nacex_wsusername') == '' &&
         * Tools::getValue('nacex_print_url') == '' &&
         * Tools::getValue('nacex_print_model') == '' &&
         * Tools::getValue('nacex_print_et') == '' &&
         * Tools::getValue('nacex_ws_url') == '')
         */
        if (! empty($this->_confErrors) && count($this->_confErrors) > 0) {
            $this->_postErrors[] .= $this->l('Error on saving module configuration');
        }
    }

    private function _postProcess()
    {
        $result = guardarConfiguracion();

        /** Añadimos el campo de provincia para que se pueda elegir en España **/
        $format_address = Db::getInstance()->executeS('SELECT format FROM ' . _DB_PREFIX_ . 'address_format WHERE id_country = 6');
        if (strpos($format_address[0]['format'], 'State:name') === false) {
            $add_state = substr_replace($format_address[0]['format'], "State:name\r\n", strpos($format_address[0]['format'], 'Country:name'), 0);
            $update = 'UPDATE ' . _DB_PREFIX_ . "address_format SET format = '" . $add_state . "' WHERE id_country = 6;";
            Db::getInstance()->execute($update);
        }

        if ($result) {
            // Miramos si ya están creados los transportistas genéricos
            $selectStd = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'carrier WHERE ncx="nacexG"');
            $selectShp = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'carrier WHERE ncx="nacexshopG"');
            $selectInt = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'carrier WHERE ncx="nacexintG"');

            if (sizeof($selectStd) == 0 && sizeof($selectShp) == 0 && sizeof($selectInt) == 0) {
                nacexDAO::setTransportistasBackend();
            } else {
                nacexDAO::setTransportistasBackend($selectStd, $selectShp, $selectInt);
            }

            Configuration::get('NACEX_SERV_BACK_OR_FRONT') == 'B' ? nacexDAO::activarServicios() : nacexDAO::activarServicios('frontend');
            $this->_html .= $this->displayConfirmation($this->l('Configuration updated'));
        } elseif (!$result) {
            $this->_html .= $this->displayErrors($this->l('Configuration error'));
        }
    }

    //to compute the shipping price depending on the ranges that were set in the back office.
    //getPackageShippingCost($params, $shipping_cost)
    public function getOrderShippingCost($params, $shipping_cost)
    {
        nacexutils::writeNacexLog('----');
        nacexutils::writeNacexLog('INI getOrderShippingCost :: ');

        $id_carrier = $this->id_carrier; // no valido en 1.7 revisar _carriers

        // Guardamos el valor del carrier que el cliente ha seleccionado
        $selected_carrier = $params->id_carrier;
        if (!$this->context->cookie->__isset('selected_carrier')) { $this->context->cookie->__set('selected_carrier', $selected_carrier); } elseif ($this->context->cookie->__get('selected_carrier') != $selected_carrier) { $this->context->cookie->__set('selected_carrier', $selected_carrier); }

        /* Miramos y guardamos el CP para ver si ha cambiado */
        $addr = new Address($params->id_address_delivery);
        $cp_ent = $addr->postcode;

        // Comprobamos el total del pedido
        //$totalPedido = $params->getOrderTotal(true, Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING);
        $totalPedido = (Configuration::get('NACEX_COBRO_PORTES') == 'D') ? $params->getOrderTotal(true, Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING) : $this->getSubtotalWithTaxes(false);

        // Con el OrderConfirmationController y con el page_name para el quickpay se solucionarían muchos problemas
        if ($this->context->controller instanceof CartController || $this->context->controller instanceof OrderController
            || $this->context->controller instanceof OrderConfirmationControllerCore
            || $this->context->controller instanceof RedsyspaymentModuleFrontController
            || $this->context->controller instanceof RedsysOkPaymentModuleFrontController
            || $this->context->controller instanceof RedsysoficialSecurePaymentModuleFrontController
            || $this->context->controller instanceof RedsysoficialProcessPaymentModuleFrontController
            || $this->context->controller instanceof RedsysoficialProcessPaymentRefModuleFrontController
            || $this->context->controller instanceof RedsysoficialSecurePaymentV2ModuleFrontController
            || $this->context->controller instanceof QuickPayValidationModuleFrontController) {

            if (!is_null($cp_ent) && $cp_ent != '') {

                $change_cp = $setNacex = $setTotal = false;

                // Si está el total del pedido en la cookie y es diferente al actual, indicamos que se ha modificado el pedido
                if (!$this->context->cookie->__isset('total_nacex_' . $id_carrier)) { $this->context->cookie->__set('total_nacex_' . $id_carrier, $totalPedido); } elseif ($this->context->cookie->__get('total_nacex_' . $id_carrier) != $totalPedido) { $setTotal = true; }

                if (!$this->context->cookie->__isset('cp_nacex_' . $id_carrier) && !is_null($cp_ent)) {
                    nacexutils::writeNacexLog('isset cookie cp_nacex ::');
                    $cp_nacex = $addr->postcode;
                    //$this->context->cookie->__set('cp_nacex_' . $id_carrier, $cp_nacex[$id_carrier]);
                    $this->context->cookie->__set('cp_nacex_' . $id_carrier, $cp_nacex);
                    $this->context->cookie->write();
                    nacexutils::writeNacexLog('seteada cookie ::');
                    $setNacex = true; // Es el mismo CP pero es la primera ejecución
                } else { $cp_nacex[$id_carrier] = $this->context->cookie->__get('cp_nacex_' . $id_carrier); }

                /* Creamos cookies para evitar llamadas masivas a WS */
                $carrier_activo_cookie = !$this->context->cookie->__isset('carriers_nacex') ? [] : unserialize($this->context->cookie->__get('carriers_nacex'));
                //if (!empty($carrier_activo)) nacexutils::writeNacexLog("carrier_activo :: " . print_r($carrier_activo));

                $carriersList = nacexDAO::getActiveCarriers();
                $carrier_activo = [];

                foreach ($carriersList as $carr) {
                    if (in_array($carr['id_carrier'], $carrier_activo_cookie)) { array_push($carrier_activo, $carr['id_carrier']); } else {
                        // borrar los datos de la cookie referente a ese carrier
                        if ($this->context->cookie->exists('checked_carrier_' . $carr['id_carrier'])) {
                            $this->context->cookie->__unset('carriers_nacex' . $carr['id_carrier']);
                            nacexutils::writeNacexLog('Cookie to delete :: ' . 'carriers_nacex' . $carr['id_carrier']);
                        }
                    }
                }

                if (isset($cp_nacex[$id_carrier]) && $cp_nacex[$id_carrier] != $cp_ent) {
                    $change_cp = true;
                    unset($carrier_activo[array_search($id_carrier, $carrier_activo)]);
                }

                // Comprobamos si el carrier ya se ha mirado antes
                nacexutils::writeNacexLog('carrier ya se ha mirado antes??? :: ' . $change_cp || $setNacex);
                if ($change_cp || $setNacex || $setTotal) {

                    if (!in_array($id_carrier, $carrier_activo) || $setTotal) { // No está entrando cuando cambia el CP porque ya tiene el carrier designado.
                        // Creo un array con los carriers activos
                        array_push($carrier_activo, $id_carrier);
                        $this->context->cookie->__set('carriers_nacex', serialize(array_unique($carrier_activo)));

                        // Miro de crear un array para comprobar si el id se ha mirado y tiene cantidad asignada
                        /*if (!$this->context->cookie->__isset('checked_carrier_' . $id_carrier)) {
                            //$this->context->cookie->__set('checked_carrier', serialize([$id_carrier => false]));
                            $this->context->cookie->__set('checked_carrier_' . $id_carrier, false);
                            $this->context->cookie->write();
                        }*/

                        if (!$this->context->cookie->__isset('checked_carrier_' . $id_carrier)) { $checked_carrier_value = false; }
                        $checked_carrier_value = $this->context->cookie->__get('checked_carrier_' . $id_carrier) !== null ? $this->context->cookie->__get('checked_carrier_' . $id_carrier) : $checked_carrier_value;
                        /* Fin de la declaración de las cookies */

                        $nacex_mostrar_coste_0 = Configuration::get('NACEX_MOSTRAR_COSTE_0') == 'SI';

                        // Funcionalidad de calculo de tarifa

                        nacexutils::writeNacexLog('llamamos a calculoTarifa :: ');
                        $shipping_cost = $this->calculoTarifa($params, $id_carrier);
                        nacexutils::writeNacexLog('llamada a calculoTarifa :: ');

                        // Cogemos los datos del producto por si tienen un gasto de manipulación propio configurado
                        $productos = $params->getProducts();
                        $importe_adicional = 0;
                        foreach ($productos as $prod) {
                            $importe_adicional += $prod['additional_shipping_cost'];
                        }

                        // Sumamos el importe adicional de los productos al coste de envío
                        $shipping_cost += $importe_adicional;

                        //if ($checked_carrier_value == false || $change_cp || $setTotal) {
                        if ($checked_carrier_value == false || $change_cp || $setTotal || $setNacex) {
                            //$this->context->cookie->__set('checked_carrier', serialize([$id_carrier => $shipping_cost]));
                            $this->context->cookie->__set('checked_carrier_' . $id_carrier, $shipping_cost);
                            $this->context->cookie->write();
                        } else { // Como no entramos a ninguna condición, asignamos el precio
                            nacexutils::writeNacexLog('getOrderShippingCost :: Recuperamos valor de la cookie');
                            $shipping_cost = number_format($this->context->cookie->__get('checked_carrier_' . $id_carrier), 2, '.', '');
                        }

                        nacexutils::writeNacexLog('shipping_cost :: ' . $shipping_cost);
                        nacexutils::writeNacexLog('nacex_mostrar_coste_0 :: ' . $nacex_mostrar_coste_0);

                        // Comprobamos si hay que mostrar los transportistas con coste 0 €
                        if (!$nacex_mostrar_coste_0 && $shipping_cost <= 0) {
                            nacexutils::writeNacexLog('getOrderShippingCost :: Carrier descartado debido coste 0 Euros(Carrier:' . $id_carrier . ' => ' . $shipping_cost . ' Euros');
                            return false;
                        }/* else {
                            return $shipping_cost;
                        }*/
                    }
                } else {
                    /*nacexutils::writeNacexLog("COGEMOS EL CONTENIDO DE LA COOKIE :: ");
                    return $this->context->cookie->__get('checked_carrier_' . $id_carrier);*/

                    nacexutils::writeNacexLog('COGEMOS EL CONTENIDO DE LA COOKIE :: ');
                    $shipping_cost = $this->context->cookie->__get('checked_carrier_' . $id_carrier);
                    //                    return $shipping_cost;
                }
            }
        } else {
            nacexutils::writeNacexLog('ESTAMOS EN EL MOMENTO EN QUE LA PÁGINA ES NULL :: ');
            nacexutils::writeNacexLog('COGEMOS EL CONTENIDO DE LA COOKIE :: ');
            $shipping_cost = $this->context->cookie->__get('checked_carrier_' . $id_carrier);

            // Esto se hace para que vuelva a calcular en el coste de envio si la cookie de PS se pierde
            if (!isset($shipping_cost) || empty($shipping_cost) || $shipping_cost == ''){
                nacexutils::writeNacexLog('llamamos a calculoTarifa :: ');

                $shipping_cost = $this->calculoTarifa($params, $id_carrier);

                nacexutils::writeNacexLog('llamada a calculoTarifa :: ');
            }
            //            return $shipping_cost;
        }

        nacexutils::writeNacexLog('FIN getOrderShippingCost :: ');
        nacexutils::writeNacexLog('----');
        $params->id_carrier = $selected_carrier;
        nacexutils::writeNacexLog('Retornamos el shipping_cost :: ' . $shipping_cost);
        return (float)$shipping_cost;
    }

    private function calculoTarifa($params, $id_carrier)
    {
        nacexutils::writeNacexLog('--------');
        nacexutils::writeNacexLog('INI calculoTarifa :: ');
        $nacexDTO = new nacexDTO();

        $carrier = nacexDAO::getCarrierById($id_carrier);
        $aplicar_gastos_manipulacion = Configuration::get('NACEX_GASTOS_MANIPULACION') == 'SI';
        $gastos_manipulacion_val = Configuration::get('NACEX_GASTOS_MANIPULACION_VAL');

        //$calcular_ws_importe = Configuration::get('NACEX_WS_IMPORTE');
        /* Importes Nacex Estándar */
        $calculo_importe_nacex = Configuration::get('NACEX_CALCULO_IMPORTE_STD');
        $importe_fijo_nacex_valor = floatval(str_replace(',', '.', Configuration::get('NACEX_IMP_FIJO_VAL')));
        $nacex_importe_min_grat = Configuration::get('NACEX_IMP_MIN_GRAT') == 'SI';
        $nacex_importe_min_grat_val = Configuration::get('NACEX_IMP_MIN_GRAT_VAL');

        /* Importes NacexShop */
        $calculo_importe_nacexshop = Configuration::get('NACEX_CALCULO_IMPORTE_SHP');
        $importe_fijo_nacexshop_valor = floatval(str_replace(',', '.', Configuration::get('NACEXSHOP_IMP_FIJO_VAL')));
        $nacexshop_importe_min_grat = Configuration::get('NACEXSHOP_IMP_MIN_GRAT') == 'SI';
        $nacexshop_importe_min_grat_val = Configuration::get('NACEXSHOP_IMP_MIN_GRAT_VAL');

        /* Importes Internacional */
        $calculo_importe_nacexint = Configuration::get('NACEX_CALCULO_IMPORTE_INT');
        $importe_fijo_nacexint_valor = floatval(str_replace(',', '.', Configuration::get('NACEXINT_IMP_FIJO_VAL')));
        $nacexint_importe_min_grat = Configuration::get('NACEXINT_IMP_MIN_GRAT') == 'SI';
        $nacexint_importe_min_grat_val = Configuration::get('NACEXINT_IMP_MIN_GRAT_VAL');

        $total = (Configuration::get('NACEX_COBRO_PORTES') == 'D') ? $params->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING) : $this->getSubtotalWithTaxes(false);
        //$total = $params->getOrderTotal(true, Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING);
        // PARA SERVICIOS NACEX ESTANDARD
        if ($nacexDTO->isNacexCarrier($id_carrier)) {

            // Miramos si tiene definido un importe fijo
            if ($calculo_importe_nacex == 'flat_rate') {
                $shipping_cost = number_format($importe_fijo_nacex_valor, 2, '.', '');
                nacexutils::writeNacexLog('getOrderShippingCost :: Transportista con importe fijo (Carrier:' . $id_carrier . ' => ' . $shipping_cost . ' euros');

                // Si no tiene importe fijo, miramos si hay que calcular el precio mediante WS
            } elseif ($calculo_importe_nacex == 'web_service') {
                nacexutils::writeNacexLog("llamamos a getImporteWebservice para el carrier Estándar $id_carrier");
                $shipping_cost = nacexDAO::getImporteWebservice($params, $id_carrier);
            } else {
                // Si no, se aplican las rates especificadas en cada método
                $carrierTemp = new Carrier($id_carrier);
                $tipoCalculoRate = $carrierTemp->getShippingMethod();
                if ($tipoCalculoRate == 1) { // En función del peso
                    $shipping_cost = number_format(round($carrierTemp->getDeliveryPriceByWeight($params->getTotalWeight(), Address::getZoneById($params->id_address_delivery)), 2), 2); } else { // == 2 => En función del precio
                        $shipping_cost = number_format(round($carrierTemp->getDeliveryPriceByPrice($total, Address::getZoneById($params->id_address_delivery)), 2), 2); }
            }

            // Sólo en el caso de calcular el importe por webservice añadimos gastos de manipulacion (si es necesario)
            if ($aplicar_gastos_manipulacion) {
                $shipping_cost += $gastos_manipulacion_val;
                nacexutils::writeNacexLog('getOrderShippingCost :: Aplicamos gastos de manipulacion (Carrier:' . $id_carrier . ' => +' . $gastos_manipulacion_val . ' euros ');
            }

            // Miramos si el transportista es gratuito dado el 'importe minimo gratiuto'
            if ($nacex_importe_min_grat && $total >= (float)$nacex_importe_min_grat_val) {
                $shipping_cost = 0;
                nacexutils::writeNacexLog('getOrderShippingCost :: Transportista gratuito dado el importe min. grat.(Carrier:' . $id_carrier . ' => 0 euros');
            }
        } elseif ($nacexDTO->isNacexShopCarrier($id_carrier)) {

            // Miramos si tiene definido un importe fijo
            if ($calculo_importe_nacexshop == 'flat_rate') {
                $shipping_cost = number_format($importe_fijo_nacexshop_valor, 2, '.', '');
                nacexutils::writeNacexLog('getOrderShippingCost :: Transportista con importe fijo (Carrier:' . $id_carrier . ' => ' . $shipping_cost . ' euros ');

                // Si no tiene importe fijo, miramos si hay que calcular el precio mediante WS
            } elseif ($calculo_importe_nacexshop == 'web_service') {
                nacexutils::writeNacexLog("llamamos a getImporteWebservice para el carrier Shop $id_carrier");
                $shipping_cost = nacexDAO::getImporteWebservice($params, $id_carrier);
            } else {
                // Si no, se aplican las rates especificadas en cada método
                $carrierTemp = new Carrier($id_carrier);
                $tipoCalculoRate = $carrierTemp->getShippingMethod();
                if ($tipoCalculoRate == 1) { // En función del peso
                    $shipping_cost = number_format(round($carrierTemp->getDeliveryPriceByWeight($params->getTotalWeight(), Address::getZoneById($params->id_address_delivery)), 2), 2); } else { // == 2 => En función del precio
                        $shipping_cost = number_format(round($carrierTemp->getDeliveryPriceByPrice($total, Address::getZoneById($params->id_address_delivery)), 2), 2); }
            }

            // Sólo en el caso de calcular el importe por webservice añadimos gastos de manipulacion (si es necesario)
            if ($aplicar_gastos_manipulacion) {
                $shipping_cost += $gastos_manipulacion_val;
                nacexutils::writeNacexLog('getOrderShippingCost :: Aplicamos gastos de manipulacion (Carrier:' . $id_carrier . ' => +' . $gastos_manipulacion_val . ' euros ');
            }

            // Miramos si el transportista es gratuito dado el 'importe minimo gratuito'
            if ($nacexshop_importe_min_grat && $total >= $nacexshop_importe_min_grat_val) {
                $shipping_cost = 0;
                nacexutils::writeNacexLog('getOrderShippingCost :: Transportista gratuito dado el importe min. grat.(Carrier:' . $id_carrier . ' => 0 euros ');
            }
        } elseif ($nacexDTO->isNacexIntCarrier($id_carrier)) {

            // Miramos si tiene definido un importe fijo
            if ($calculo_importe_nacexint == 'flat_rate') {
                $shipping_cost = number_format($importe_fijo_nacexint_valor, 2, '.', '');
                nacexutils::writeNacexLog('getOrderShippingCost :: Transportista con importe fijo (Carrier:' . $id_carrier . ' => ' . $shipping_cost . ' euros ');

                // Si no tiene importe fijo, miramos si hay que calcular el precio mediante WS
            } elseif ($calculo_importe_nacexint == 'web_service') {
                $shipping_cost = nacexDAO::getImporteWebservice($params, $id_carrier);
            } else {
                // Si no, se aplican las rates especificadas en cada método
                $carrierTemp = new Carrier($id_carrier);
                $tipoCalculoRate = $carrierTemp->getShippingMethod();
                if ($tipoCalculoRate == 1) { // En función del peso
                    $shipping_cost = number_format(round($carrierTemp->getDeliveryPriceByWeight($params->getTotalWeight(), Address::getZoneById($params->id_address_delivery)), 2), 2); } else { // == 2 => En función del precio
                        $shipping_cost = number_format(round($carrierTemp->getDeliveryPriceByPrice($total, Address::getZoneById($params->id_address_delivery)), 2), 2); }
            }

            // Sólo en el caso de calcular el importe por webservice añadimos gastos de manipulacion (si es necesario)
            if ($aplicar_gastos_manipulacion) {
                $shipping_cost += $gastos_manipulacion_val;
                nacexutils::writeNacexLog('getOrderShippingCost :: Aplicamos gastos de manipulacion (Carrier:' . $id_carrier . ' => +' . $gastos_manipulacion_val . ' euros ');
            }

            // Miramos si el transportista es gratuito dado el 'importe minimo gratuito'
            if ($nacexint_importe_min_grat && $total >= $nacexint_importe_min_grat_val) {
                $shipping_cost = 0;
                nacexutils::writeNacexLog('getOrderShippingCost :: Transportista gratuito dado el importe min. grat.(Carrier:' . $id_carrier . ' => 0 euros ');
            }
        }
        nacexutils::writeNacexLog('FIN calculoTarifa :: ');
        nacexutils::writeNacexLog('--------');

        return $shipping_cost;
    }

    /**
     * Returns the subtotal of the cart, including or excluding taxes.
     *
     * @param bool $withTaxes Si incluimos impuestos o no
     *
     * @return float The subtotal of the cart.
     */
    private function getSubtotalWithTaxes($withTaxes = true)
    {
        $subtotalWoT = 0.0;

        $cart = $this->context->cart;

        // Revisamos que en el carrito existan productos
        if (!$cart->getNbProducts()) {
            return $subtotalWoT;
        }

        $cartProducts = $this->context->cart->getProducts();

        foreach ($cartProducts as $prod) {
            if ($withTaxes) { $subtotalWoT += $prod['price_with_reduction']; } else { $subtotalWoT += $prod['price_with_reduction_without_tax']; }
        }

        return $subtotalWoT;
    }

    // to compute the shipping price without using the ranges.
    public function getOrderShippingCostExternal($params)
    {
        return nacex::calculoTarifa($params, $this->id_carrier);
    }

    public function hookdisplayAdminOrderMainBottom($params)
    {
        return $this->renderAdminOrder($params, true);
    }

    public function hookactionValidateOrder($params)
    {
        nacexutils::writeNacexLog('---------');
        nacexutils::writeNacexLog('INI hookactionValidateOrder :: ');

        // En los pagos con TPV pasa que el id_carrier del $params != al que guardamos en la cookie
        $carrier_activo = @unserialize($this->context->cookie->__get('carriers_nacex'));
        if (!is_array($carrier_activo)) {
            $carrier_activo = [];
        }

        // Cogemos el valor del carrier que el cliente ha seleccionado
        if (isset($_COOKIE['selected_carrier'])){
            $selected_carrier = $_COOKIE['selected_carrier'];
        } else {
            $selected_carrier = $this->context->cookie->__get('selected_carrier') ?? null;
        }

        $orderCarrier = isset($params['order']->id_carrier) ? $params['order']->id_carrier : null;
        // Revisamos cuál es el carrier seleccionado correcto
        $isCarrierActivo = $carrier_activo ? (!is_null($orderCarrier) && in_array($orderCarrier, $carrier_activo)) : $carrier_activo;

        // Revisamos el valor del carrier para que se muestre correctamente y printarlo en el log
        if ($isCarrierActivo && $selected_carrier == $orderCarrier) {
            $carrier = $orderCarrier;
            nacexutils::writeNacexLog('carrier1 :: ' . $carrier);
        } elseif (!is_null($selected_carrier) || !empty($selected_carrier)){
            $carrier = $selected_carrier;
            nacexutils::writeNacexLog('carrier2 :: ' . $carrier);
        } else {
            $carrier = $orderCarrier;
            nacexutils::writeNacexLog('carrier3 :: ' . $carrier);
        }

        $nacexDTO = new nacexDTO();

        if ($nacexDTO->isNacexShopCarrier($carrier)) {

            $id_cart = isset($params['order']->id_cart) ? $params['order']->id_cart : null;
            $id_address_delivery = isset($params['order']->id_address_delivery) ? $params['order']->id_address_delivery : null;
            $id_order = isset($params['order']->id) ? $params['order']->id : null;
            $order = new Order($id_order);

            // Cogemos el campo del NCX que guardamos con el setSession en BBDD
            $query = Db::getInstance()->executeS('SELECT ncx FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart = "' . $params['cart']->id . '"')[0];
            // Comprobamos si la dirección del pedido coincide con la que hemos guardado en el carrito para los Shop
            //$shop_address = nacexDAO::getDatosCartNacexShop($params['cart']->id);

            if (isset($_COOKIE['opc_id_cart']) && isset($_COOKIE['opc_shop_datos']) && $_COOKIE['opc_id_cart'] == $id_cart) {
                $shop_datos = $_COOKIE['opc_shop_datos'];
            } elseif (!empty($query['ncx'])) {
                $shop_datos = $query['ncx'];
            }

            $new_idDelivery = '';
            nacexDAO::setNacexShopAddressinBD($id_order, $id_cart, $id_address_delivery, $params['order']->id_customer, $shop_datos, $new_idDelivery);
            //$this->context->cart->id_address_delivery = $new_id_address;
            $params['order']->id_address_delivery = $new_idDelivery;
            $order->id_address_delivery = $new_idDelivery;

            // Añadimos el carrier por si el pedido es 0
            // Revisamos si el id_carrier del es igual al seleccionado
            if (is_null($params['order']->id_carrier) || $params['order']->id_carrier != $carrier) { $params['order']->id_carrier = $carrier; }
            if (is_null($order->id_carrier) || $order->id_carrier != $carrier) { $order->id_carrier = $carrier; }

            $order->update();
        } elseif ($nacexDTO->isNacexCarrier($carrier) || $nacexDTO->isNacexIntCarrier($carrier)) {
            $query = Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'cart SET ncx = "1" WHERE id_cart = "' . $params['cart']->id . '"');
            // Eliminar la cookie y comprobar que los datos del pedido sean los correctos
            if (isset($_COOKIE['opc_shop_datos'])){
                setcookie('opc_shop_datos', '', time() - 3600, '/');
                unset($_COOKIE['opc_shop_datos']);
                $this->context->cookie->__unset('opc_shop_datos');
            }

            if (isset($_COOKIE['selected_carrier'])){
                setcookie('selected_carrier', '', time() - 3600, '/');
                unset($_COOKIE['selected_carrier']);
                $this->context->cookie->__unset('selected_carrier');
            }

            $id_order = isset($params['order']->id) ? $params['order']->id : null;
            $order = new Order($id_order);

            // Añadimos el carrier por si el pedido es 0
            // Revisamos si el id_carrier del es igual al seleccionado
            if (is_null($params['order']->id_carrier) || $params['order']->id_carrier != $carrier) { $params['order']->id_carrier = $carrier; }
            if (is_null($order->id_carrier) || $order->id_carrier != $carrier) { $order->id_carrier = $carrier; }

            $order->update();
        }

        nacexutils::writeNacexLog('FIN hookactionValidateOrder :: ');
        nacexutils::writeNacexLog('---------');
    }
}
