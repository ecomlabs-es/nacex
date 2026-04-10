<?php

/*
 * Config
 */
//require_once __DIR__ . '/../../config/defines.inc.php';
require_once dirname(__FILE__) . '/../../config/defines.inc.php';

class nacexutils
{
    public const nacexVersion = '2.5.0';
    public const _moduleName = 'nacex';

    public static function getModuleName()
    {
        return self::_moduleName;
    }

    /**
     * Reemplazo compatible de utf8_encode() eliminado en PHP 8.2.
     * Convierte de ISO-8859-1 a UTF-8.
     */
    public static function toUtf8($string)
    {
        if ($string === null || $string === '') {
            return '';
        }
        // Si ya es UTF-8 válido, no convertir (evita doble codificación)
        if (mb_check_encoding($string, 'UTF-8')) {
            return $string;
        }
        return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }

    public static function getDefValue($array, $index, $def) {
        if (isset($array[$index])) {
            return $array[$index];
        } else {
            return $def;
        }
    }

    public static function normalizarDecimales($val, $numdecs, $sepdec, $sepmil, $noprintifzero, $noprintifcerodec) {

        if ($noprintifzero && (!isset($val) || $val == '0')) {
            return '';
        } else {
            if ($noprintifcerodec){
                return $val;
            } else {
                return number_format($val, $numdecs, $sepdec, $sepmil);
            }
        }
    }

    public static function getDatosListado($id_pedido) {

        $datoslistado = [];

        $datoslistado['id_order'] = $id_pedido;
        $datoslistado['nom_ent'] = null;
        $datoslistado['tel_ent'] = null;
        $datoslistado['dir_ent'] = null;
        $datoslistado['pob_ent'] = null;
        $datoslistado['cp_ent'] = null;
        $datoslistado['tel_ent'] = null;
        $datoslistado['email_ent'] = null;
        $datoslistado['pais_ent'] = null;

        $datoslistado['peso'] = 1;
        $datoslistado['bultos'] = 1;

        $datoslistado['importe'] = 0;
        $datoslistado['ree'] = 0;

        //Datos relacionados al pedido
        $datos = Db::getInstance()->executeS(
            'SELECT o.id_order,o.module,u.email,a.firstname,
				a.lastname,a.address1,a.postcode,a.city,a.phone,a.phone_mobile,z.iso_code,
				case when o.total_paid_real > 0 
					then o.total_paid_real
				else
					o.total_paid
				end as total_paid_real
				FROM ' . _DB_PREFIX_ . 'orders AS o
				JOIN ' . _DB_PREFIX_ . 'customer AS u
				JOIN ' . _DB_PREFIX_ . 'address a
				JOIN ' . _DB_PREFIX_ . 'country AS z
				WHERE o.id_order = "' . $id_pedido . '"
				AND u.id_customer = o.id_customer
				AND a.id_address = o.id_address_delivery
				AND a.id_country = z.id_country'
        );

        //Detalles del pedido
        $productospedido = Db::getInstance()->executeS(
            'SELECT product_quantity, product_weight FROM ' . _DB_PREFIX_ . 'order_detail
				where id_order = "' . $id_pedido . '"'
        );

        //Detalle de la expedicion
        $datosexpedicion = nacexDAO::getDatosExpedicion($id_pedido);

        foreach ($productospedido as $producto) {
            $datoslistado['peso'] += floatval($producto['product_quantity'] * $producto['product_weight']);
            $datoslistado['bultos'] += $producto['product_quantity'];
        }
        if ($datoslistado['peso'] < 1) {
            $datoslistado['peso'] = 1;
        }
        if ($datoslistado['bultos'] < 1) {
            $datoslistado['bultos'] = 1;
        }

        //Obtenemos el importe total del pedido
        $datoslistado['importe'] = $datos[0]['total_paid_real'];

        //Datos del comprador (entrega)
        $datoslistado['nom_ent'] = $datos[0]['firstname'] . ' ' . $datos[0]['lastname'];
        $datoslistado['dir_ent'] = $datos[0]['address1'];
        $datoslistado['pob_ent'] = $datos[0]['city'];
        $datoslistado['cp_ent'] = $datos[0]['postcode'];

        //$nacex_cod_provincia_destinatario= $dir_pedido->getRegion();
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
        $datoslistado['tel_ent'] = $tlf;
        $datoslistado['email_ent'] = $datos[0]['email'];
        $datoslistado['pais_ent'] = $datos[0]['iso_code'];

        //HAY QUE CONTROLAR SI EL COMPRADOR A ELEGIDO CONTRA REEMBOLSO
        // $array_modsree = array();
        $array_modsree = explode('|', Configuration::get('NACEX_MODULOS_REEMBOLSO'));

        $metodo_pago = strtolower($datos[0]['module']);

        if (in_array($metodo_pago, $array_modsree) || strpos($metodo_pago, 'cashondelivery') !== false) {
            if (isset($datosexpedicion) && $datosexpedicion[0]['imp_ree'] != 0) {
                $datoslistado['ree'] = floatval($datosexpedicion[0]['imp_ree']);
            } else {
                $datoslistado['ree'] = floatval($datos[0]['total_paid_real']);
            }
        }

        return $datoslistado;
    }

    public static function markSelectedOption($option_field_name, $config_name, $val) {
        if ($config_name != null && $config_name != '') {
            if (Tools::getValue($option_field_name, Configuration::get($config_name)) == $val) {
                return 'selected="selected"';
            }
        }
        return '';
    }

    public static function markSelectedFrontendOption($option_field_name, $valuef, $val) {
        if ($valuef != null && $valuef != '') {
            if (Tools::getValue($option_field_name, $valuef) == $val) {
                return 'selected="selected"';
            }
        }
        return '';
    }

    public static function markCheckedOption($option_field_name, $config_name, $val) {
        if ($config_name != null && $config_name != '') {
            if (Tools::getValue($option_field_name, Configuration::get($config_name)) == $val) {
                return 'checked="checked"';
            }
        }
        return '';
    }

    public static function markSelectedMultiOption($array_multi_option_field_name, $config_name, $sep, $val) {

        $results = Tools::getValue($array_multi_option_field_name, null);
        if (!isset($results)) {
            $results = explode($sep, Configuration::get($config_name));
        }

        for ($i = 0; $i < count($results); $i++) {
            if ($results[$i] == $val){
                return 'selected="selected"';
            }
        }
        return '';
    }

    public static function isMultiOptionChecked($fieldName, $configName, $sep, $val)
    {
        $results = Tools::getValue($fieldName, null);
        if (!isset($results)) {
            $results = explode($sep, Configuration::get($configName));
        }
        return in_array($val, $results) ? 'checked' : '';
    }

    public static function renderCheckboxGroup($fieldName, $configName, $sep, $options)
    {
        $html = '<div class="nacex-checkbox-group" style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:8px;border-radius:4px;width:335px;">';
        foreach ($options as $value => $label) {
            $checked = self::isMultiOptionChecked($fieldName, $configName, $sep, $value);
            $id = htmlspecialchars($fieldName . '_' . $value, ENT_QUOTES, 'UTF-8');
            $html .= '<label style="display:block;padding:3px 0;cursor:pointer;" for="' . $id . '">';
            $html .= '<input type="checkbox" id="' . $id . '" name="' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . '[]" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" ' . $checked . ' style="margin-right:6px;" />';
            $html .= htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $html .= '</label>';
        }
        $html .= '</div>';
        return $html;
    }

    public static function markCheckedCheckBoxes($array_multi_option_field_name, $config_name, $sep, $val) {

        $results = Tools::getValue($array_multi_option_field_name, null);
        if (isset($results)) {

        } else {
            $results = explode($sep, Configuration::get($config_name));
        }
        for ($i = 0; $i < count($results); $i++) {
            if ($results[$i] == $val) {
                return 'checked'; }
        }
        return '';
    }

    public static function isNacexGenericCarrier($id_carrier) {
        $datoscarrier = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'carrier c WHERE c.id_carrier = "' . $id_carrier . '"');

        if (isset($datoscarrier) && isset($datoscarrier[0])) {
            nacexutils::writeNacexLog('isNacexGenericCarrier :: [' . $id_carrier . '] => true');
            return (
                $datoscarrier[0]['ncx'] == 'nacexG' || $datoscarrier[0]['ncx'] == 'nacexshopG' || $datoscarrier[0]['ncx'] == 'nacexintG'
            );
        } else {
            nacexutils::writeNacexLog('isNacexGenericCarrier :: [' . $id_carrier . '] => false');
            return false;
        }
    }

    public static function printEtiqueta($id_pedido) {
        $urlPrint = Configuration::get('NACEX_PRINT_URL');
        $modelPrint = Configuration::get('NACEX_PRINT_MODEL');
        $etPrint = Configuration::get('NACEX_PRINT_ET');
        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');
        $parametros = 'user=' . $nacexWSusername . '&pass=' . $nacexWSpassword . '&model=' . $modelPrint . '&et=' . $etPrint . '&ref=' . getReferenciaGeneral() . $id_pedido;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlPrint . '?' . $parametros);
        /**parche mexpositop 20171222**/
        //  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postResult = curl_exec($ch);
        curl_close($ch);
    }

    public static function getMapCambios($cambios) {

        // "key1=value1|key2=value2|...|keyN=valueN"

        $mapa = [];

        $pares = explode('|', $cambios);

        for ($i = 0; $i < count($pares); $i++) {
            $par = $pares[$i];
            if (strlen($par)) {
                $kv = explode('=', $par);
                $k = $kv[0];
                $v = $kv[1];
                $mapa[$k] = $v;
            }
        }
        return $mapa;
    }

    public static function cutupString($str, $cutLength, $numCuts) {

        $text = preg_replace("/[\r\n]+/", '', $str);

        $ret = [];
        // $lastIndex = 0;

        for ($i = 0; $i < $numCuts; $i++) {
            $ret[$i] = substr($text, 0, $cutLength);
            $text = substr($text, $cutLength, strlen($text));
        }
        return $ret;
    }

    public static function getReferenciaGeneral() {
        return Configuration::get('NACEX_REF_PERS') == 'SI' ?
                    Configuration::get('NACEX_REF_PERS_PREFIJO') :
                    nacexDTO::$PREFIJO_REFERENCIA;
    }

    public static function showAppletImpresion() {
        $html = "</script>
						<script type='text/javascript'>
								 if (navigator.userAgent.match(/msie/i) || navigator.userAgent.match(/trident/i) ){
									document.write(\"<OBJECT \",
				                   			\"tabindex='-1'\",			   
												   			\"codeBase='https://java.sun.com/products/plugin/autodl/jinstall-1_5_0-windows-i586.cab#Version=1,5,0,0'\",
				 								   			\"classid='clsid:8AD9C840-044E-11D1-B3E9-00805F499D93'\",
												   			\"id='ImpEtiqueta'\",
												   			\"width='1'\",
				                   			\"height='1'>\",
				                				\"<PARAM name='code' value='ImpEtiqueta.class'>\",
				                				\"<PARAM name='java_archive' value='NacexImpresion_signed.jar'/>\",
				                				\"<PARAM name='java_type' value='application/x-java-applet;version=1.5.0'/>\",
				                				\"<PARAM name='java_codebase' value='" . Configuration::get('NACEX_PRINT_URL') . "'/>\",
				                				\"<PARAM name='initial_focus' value='false'/>\",
				                				\"<PARAM NAME='MAYSCRIPT' VALUE='true'/>\",
											       		\"<PARAM NAME='SHOW_PRINTER_DIALOG' VALUE='true'/>\",
												   			\"<PARAM NAME='SINDATOS' VALUE='No hay resultados'/>\",
				                   			\"</OBJECT>\");
								 }else{
									document.write(\"<embed code='ImpEtiqueta.class' id='ImpEtiqueta'\",
												   			 \"codebase='" . Configuration::get('NACEX_PRINT_URL') . "'\",
												   			 \"width='0'\",
												   			 \"height='0'\",
												   			 \"archive='NacexImpresion_signed.jar'\",
												   			 \"type='application/x-java-applet;version=1.5'\",
												   			 \"initial_focus='false'\",
												   			 \"MAYSCRIPT='true'\",
												   			 \"SHOW_PRINTER_DIALOG='true'\",
												   			 \"SINDATOS='No hay resultados' >\");
								}
							</script>";

        return $html;
    }

    public function getToken($tab) {
        global $cookie;
        return Tools::getAdminToken($tab . (int) Tab::getIdFromClassName($tab) . (int) $cookie->id_employee);
    }

    public static function changeAddressIfNacexShop($datospedido) {
        nacexutils::writeNacexLog('----');
        nacexutils::writeNacexLog('INI changeAddressIfNacexShop :: id_order:' . $datospedido['id_order']);

        $array_address_invoice = nacexDAO::getAddressInvoiceByOrder($datospedido['id_order']);

        //nacexutils::writeNacexLog("changeAddressIfNacexShop :: array_address_invoice:" . print_r($array_address_invoice[0]));

        if (nacexDTO::isNacexShopCarrier($datospedido['id_carrier'])) {
            echo "<script>				
					$(function() { 
						var fieldset = $('a[href^=\'?tab=AdminAddresses&id_address\']:eq(0)').closest('fieldset');						
						$(fieldset).find('form>div').css('display', 'none');
						var legend = $(fieldset).find('legend');																		
						$(legend).html('Dirección de Entrega Nacex!Shop');
						$(fieldset).addClass('bg_nacexshop').prepend(legend);	
						$(fieldset).append('<hr/><span><i>Att: " . $array_address_invoice[0]['firstname'] . ' ' . $array_address_invoice[0]['lastname'] . "</i></span>');
						$('a[href^=\'?tab=AdminAddresses&id_address\']:eq(0)').css('display', 'none');						
					});
			      </script>";
            nacexutils::writeNacexLog('changeAddressIfNacexShop :: insertada informacion de direccion nacexshop');
        }
        nacexutils::writeNacexLog('FIN changeAddressIfNacexShop :: id_order:' . $datospedido['id_order']);
        nacexutils::writeNacexLog('----');
    }

    public static function print_messages(&$_response, $tipo, $texto)
    {
        $_clase = [
            'ERROR' => "<div class='bootstrap' style='margin-top:10px'><div class='alert alert-danger error' style='width:auto;border-radius: 5px !important'>",
            'INFO' => "<div class='bootstrap' style='margin-top:10px'><div class='alert alert-info alert' style='width:auto;border-radius: 5px !important'>",
            'WARNING' => "<div class='bootstrap' style='margin-top:10px'><div class='alert alert-warning warning' style='width:auto;border-radius: 5px !important'>",
            'SUCCESS' => "<div class='bootstrap' style='margin-top:10px'><div class='alert alert-success conf' style='width:auto;border-radius: 5px !important'>",
            'MESSAGE' => "<p class='tip'>"
        ];

        $_response = $_clase[$tipo];
        $_response .= '<strong>' . $texto . '</strong><br>';
        if ($tipo != 'MESSAGE') {
            $_response .= '</div></div>';
        } else {
            $_response .= '</p>';
        }
    }

    public static function explodeShopData($shop_data)
    {
        $ret = [];

        $array_shop_data = isset($shop_data) ? explode('|', $shop_data) : null;
        $ret['shop_codigo'] = isset($array_shop_data[0]) ? trim($array_shop_data[0]) : null;
        $ret['shop_alias'] = isset($array_shop_data[1]) ? trim($array_shop_data[1]) : null;
        $ret['shop_nombre'] = isset($array_shop_data[2]) ? trim($array_shop_data[2]) : null;
        $ret['shop_direccion'] = isset($array_shop_data[3]) ? trim($array_shop_data[3]) : null;
        $ret['shop_cp'] = isset($array_shop_data[4]) ? trim($array_shop_data[4]) : null;
        $ret['shop_poblacion'] = isset($array_shop_data[5]) ? trim($array_shop_data[5]) : null;
        $ret['shop_provincia'] = isset($array_shop_data[6]) ? trim($array_shop_data[6]) : null;
        return $ret;
    }

    public static function writeNacexLog($txt) {
        if (Configuration::get('NACEX_SAVE_LOG') == 'SI') {
            $logDir = _PS_MODULE_DIR_ . 'nacex' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
            $logname = $logDir . 'nacex_' . date('Ymd') . '.log';

            $logfile = @fopen($logname, 'a');
            if ($logfile) {
                fwrite($logfile, '[' . date('Y-m-d H:i:s') . '] <PS:' . _PS_VERSION_ . ' - NCX:' . self::nacexVersion . '> ' . $txt . "\n");
                fclose($logfile);
            }
        }
    }

    public static function arrayFlatten($array) {
        $flattern = [];
        foreach ($array as $key => $value){
            $new_key = array_keys($value);
            $flattern[] = $value[$new_key[0]];
        }
        return $flattern;
    }

    //-----------NACEXLOGS-----------------------------
    public function delete_file($_file = '*') {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VInacexlogs.php');
        $nacex = new nacex();
        $_path = $this->path_directory_log();
        $_path .= DIRECTORY_SEPARATOR . $_file;
        if ($_file != '*'){
            if (unlink($_path)){
                return VInacexlogs::response_delete($nacex->l('The file') . ' ' . $_file . ' ' . $nacex->l('it\'s been removed'));
            } else {
                return VInacexlogs::response_delete($nacex->l('Couldn\'t remove the file') . " '" . $_file . "'");
            }
        } else {
            $_files = glob($_path);
            foreach ($_files as $_file){ // iterate files
                if (is_file($_file)) {
                    unlink($_file); } // delete file
            }
            return VInacexlogs::response_delete($nacex->l('All files deleted'));
        }
    }
    public function read_file($_file) {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VInacexlogs.php');
        $nacex = new nacex();
        $_path = $this->path_directory_log();
        $_path .= DIRECTORY_SEPARATOR . $_file;
        $_html = VInacexlogs::content_file_title($_file);
        if ($_file = fopen($_path, 'r')){
            while (!feof($_file)) {
                $_line = fgets($_file);
                $_html .= VInacexlogs::content_file($_line);
            }
        } else {
            return VInacexlogs::response_open($nacex->l('Couldn\'t open the file') . ' ' . $_GET['file']);
        }
        return $_html;
    }
    public function content_directory() {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VInacexlogs.php');
        $nacex = new nacex();
        $_html = '';
        $_path = $this->path_directory_log();
        $_files = scandir($_path);
        if (sizeof($_files) > 2) {
            /** Creamos la tabla para que se visualicen bien **/
            $_html .= '<div class="table-responsive">
                        <table id="tabla-nacexlogs" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>' . $nacex->l('File') . '</th>
                                    <th style="width:120px;">' . $nacex->l('Size') . '</th>
                                    <th style="width:120px;">' . $nacex->l('Actions') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

            // Ordeno los files de más reciente a más antiguo
            rsort($_files);

            foreach ($_files as $index => $_file) {
                if ($_file != '.' && $_file != '..' && $_file != '.gitkeep' && pathinfo($_file, PATHINFO_EXTENSION) === 'log') {
                    $_html .= VInacexlogs::content_directory($_file, $this->path_directory_log(), $index);
                }
            }
            /** Cerramos tabla creada **/
            $_html .= '</tbody></table></div>';

        } else {
            $_html = VInacexlogs::content_directory_no_files();
        }
        return $_html;
    }

    private function path_directory_log()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'log';
    }

    /* Problemas con módulos que afectan al pago pero no aparecen como módulos de pago*/
    public static function getPaymentModulesExtra(&$paymentModules)
    {
        $modulosPagoExtra = ['codwfeeplus'];
        $modules = Module::getModulesInstalled();

        $extraModules = [];
        foreach ($modulosPagoExtra as $mpe) {
            $search = array_search($mpe, array_column($modules, 'name')); // Se comprueba que existe
            $isIncluded = array_search($mpe, array_column($paymentModules, 'name')); // Ya está incluido

            if ($search !== false && $isIncluded === false) {
                $extraModules[] = $modules[$search]; }
        }

        // Juntamos los métodos de pago con los módulos que pueden estar dando problemas con los pagos
        $paymentModules = array_merge($paymentModules, $extraModules);
    }

    public static function getCurrentLang()
    {
        $context = Context::getContext();
        if (isset($context->language) && isset($context->language->id)) {
            return $context->language->id;
        }
        global $cookie;
        return isset($cookie->id_lang) ? $cookie->id_lang : Configuration::get('PS_LANG_DEFAULT');
    }

    //TO GET PROVINCIA
    public static function provincia($cp, &$prov)
    {
        $provincias = nacexDTO::PROVINCIAS_ES;
        foreach ($provincias as $key => $provincia) {
            if (substr($cp, 0, 2) == $provincia['Codigo']) {
                $prov = $key;
                break;
            }
        }
    }

    /** Función que monta el HTML de los radio buttons de la Configuración como en PS **/
    public static function getRadioHTML($label, $radioName, $valueN, $selectedN, $valueY, $selectedY, $small = null, $onchangeN = null, $onchangeY = null, $labelN = 'No', $labelY = 'Yes')
    {
        $nacex = new nacex();

        $ocN = !is_null($onchangeN) ? 'onchange="' . $onchangeN . '"' : '';
        $ocY = !is_null($onchangeY) ? 'onchange="' . $onchangeY . '"' : '';

        $radioHTML = '<div class="form-group row">
                        <label class="col-lg-3 col-form-label">' . $nacex->l($label) . '</label>
                        <div class="col-lg-9" id="' . $radioName . '">
                            <span class="ps-switch">
                                <input id="' . $radioName . '_n" type="radio" name="' . $radioName . '" ' . $ocN . ' value="' . $valueN . '" ' . $selectedN . '/>
                                <label for="' . $radioName . '_n">' . $nacex->l($labelN) . '</label>
                                <input id="' . $radioName . '_s" type="radio" name="' . $radioName . '" ' . $ocY . ' value="' . $valueY . '" ' . $selectedY . '/>
                                <label for="' . $radioName . '_s">' . $nacex->l($labelY) . '</label>
                                <span class="slide-button"></span>
                            </span>';
        if (!is_null($small)) {
            $radioHTML .= '<small class="form-text text-muted">' . $nacex->l($small) . '</small>';
        }
        $radioHTML .= '</div></div>';

        return $radioHTML;
    }

    /** Descargamos los PDF de las etquetas **/
    public static function generateLabelPDF($base64, $folder, $exp)
    {
        $url = 'https://pda.nacex.com/nacex_ws/base64topdf.jsp';

        // create a new cURL resource
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'base64=' . $base64);
        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        if ($server_output) {

            $filePath = _PS_MODULE_DIR_ . 'nacex/files/' . $folder . '/';
            $fileName = $exp . '.pdf';

            file_put_contents($filePath . $fileName, $server_output);
        } else {
            nacexutils::writeNacexLog('No se ha podido generar el PDF de la etiqueta');
        }

        curl_close($ch);
    }

    public static function existFileLabelPDF($folder, $exp)
    {

        self::checkFilesLabelPDF();

        $filePath = _PS_MODULE_DIR_ . 'nacex/files/' . $folder . '/';
        $fileName = $exp . '.pdf';

        return file_exists($filePath . $fileName);
    }

    /**
     * Comprobamos los archivos que hay en la carpeta de las etiquetas de devolución porque sólo se pueden imprimir una vez (tienen un solo uso)
     * Se mantendrán en la carpeta durante 7 días
     */
    public static function checkFilesLabelPDF()
    {

        // Comprobamos los archivos que hay en la carpeta de las etiquetas de de
        $filePath = _PS_MODULE_DIR_ . 'nacex/files/etiquetas_dev/';

        // Miramos los archivos del directorio
        $files = scandir($filePath);
        $tiempoMaximo = 3600 * 24 * 7; // 7 días

        $limit = time() - $tiempoMaximo;

        foreach ($files as $file) {
            if (is_file($filePath . $file)) {
                if (filemtime($filePath . $file) < $limit) {
                    unlink($filePath . $file);
                }
            }
        }
    }

    /**
     * Comprobamos si hay instalados módulos de OPC que no podemos verificar con los datos de la página, como en "Supercheckout"
     */
    private static $installedModulesCache = null;

    public static function checkInstalledModule($opc_modules)
    {
        // Cache de módulos instalados para evitar query en cada page load
        if (self::$installedModulesCache === null) {
            self::$installedModulesCache = Module::getModulesInstalled();
        }
        $names = array_column(self::$installedModulesCache, 'name');

        foreach ($opc_modules as $mi) {
            $search = array_search($mi, $names);
            if ($search !== false) {
                return self::$installedModulesCache[$search];
            }
        }

        return null;
    }

    private static $enabledModulesCache = [];

    /**
     * Comprobamos si hay habilitados módulos
     */
    public static function checkEnabledModule($opc_modules)
    {
        $cacheKey = implode('|', $opc_modules);
        if (isset(self::$enabledModulesCache[$cacheKey])) {
            return self::$enabledModulesCache[$cacheKey];
        }

        $module = self::checkInstalledModule($opc_modules);

        if (is_null($module)) {
            self::$enabledModulesCache[$cacheKey] = false;
            return false;
        }

        $activo = (bool) $module['active'];
        $result = $activo && Module::isEnabled($module['name']);
        self::$enabledModulesCache[$cacheKey] = $result;
        return $result;
    }
}
