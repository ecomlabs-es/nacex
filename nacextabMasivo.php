<?php

//20180620 mexpositop
include_once dirname(__FILE__) . '/nacexutils.php';
include_once dirname(__FILE__) . '/nacexDAO.php';
include_once dirname(__FILE__) . '/nacexDTO.php';
include_once dirname(__FILE__) . '/nacexVIEW.php';
include_once dirname(__FILE__) . '/nacexWS.php';
include_once dirname(__FILE__) . '/nacex.php';

if (Configuration::get('NACEX_SHOW_ERRORS') == 'SI') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

class nacextabMasivo extends AdminController
{
    private $_html = '';
    private $nacex = '';
    public function __construct()
    {
        $this->nacex = new nacex();
        $this->display = 'view';
        parent::__construct();

        $this->meta_title = $this->nacex->l('Massive expeditions');
        $this->displayName = $this->nacex->l('Massive expeditions');
        $this->title = $this->nacex->l('Massive expeditions');
        $this->description = $this->nacex->l('Massive expeditions');
        $this->page_header_toolbar_title = $this->nacex->l('Massive expeditions');
        $this->bootstrap = true;

        $this->context->controller->addCSS(_MODULE_DIR_ . 'nacex/css/nacex.css');
        $this->context->controller->addJS(_MODULE_DIR_ . 'nacex/js/nacex.js');
        //$this->context->controller->addJS(_MODULE_DIR_.  'nacex/js/jquery.printElement.min.js');
        //$this->context->controller->addJqueryUI('ui.datepicker');
    }

    /*public function setMedia($isNewTheme = false)
    {
        $this->addCSS(_MODULE_DIR_. 'nacex/css/nacex.css');

        //$this->addJquery('3.3.1', _MODULE_DIR_ . 'nacex/js/jquery-3.3.1.min.js');

        $this->addJS(_MODULE_DIR_.  'nacex/js/nacex.js');
        $this->addJS(_MODULE_DIR_.  'nacex/js/jquery.printElement.min.js');
        //$this->addJS(_MODULE_DIR_.  'nacex/js/jquery.cluetip.js');
        //$this->addJS(_PS_JS_DIR_ .  'admin.js');
        return parent::setMedia();
    }*/

    public function initContent() {
        $accion = Tools::getValue('accion', '');
        $html = '';
        $this->_html .= "<form id='nacex_filtro_masivo' name='nacex_filtro_masivo' method='post'>";

        $this->getFiltrosMasivos();

        if ($accion == 'generar') {
            $html = $this->generarExpediciones();
            $html .= $this->_html;
            $this->_html = $html;
            $this->getListadoPedidos();
        } elseif ($accion == 'buscar') {
            $this->getListadoPedidos(); }

        $this->_html .= '</form>';

        //return $this->_html;
        $this->context->smarty->assign('content', $this->_html);
    }

    public function getFiltrosMasivos() {

        global $cookie;
        $host = '';

        $webtext = $this->nacex->l('Go to Nacex web');
        $webdir = 'https://www.nacex.es';
        $webimg = _MODULE_DIR_ . 'nacex/images/logos/NACEX_logo.svg';

        $desde = date('Y-m-d H:i:s');
        $hasta = $desde;
        $hoy_desde = date('Y-m-d') . ' 00:00:00';
        $hoy_hasta = date('Y-m-d') . ' 23:59:59';
        $ayer_desde = date('Y-m-d', strtotime('-1 day')) . ' 00:00:00';
        $ayer_hasta = date('Y-m-d', strtotime('-1 day')) . ' 23:59:59';
        $estasemana_desde = date('Y-m-d', time() + (1 - date('w')) * 24 * 3600) . ' 00:00:00';
        $estasemana_hasta = date('Y-m-d', time() + (7 - date('w')) * 24 * 3600) . ' 23:59:59';
        $timestamp_ultimodomingo = strtotime('last Sunday');
        $semanapasada_desde = date('Y-m-d', $timestamp_ultimodomingo - 6 * 24 * 3600) . ' 00:00:00';
        $semanapasada_hasta = date('Y-m-d', $timestamp_ultimodomingo) . ' 23:59:59';
        $estemes_desde = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - date('d') + 1, date('Y'))) . ' 00:00:00';
        $estemes_hasta = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, date('d') - date('d'), date('Y'))) . ' 23:59:59';

        $usr = Configuration::get('NACEX_WSUSERNAME');
        $pass = Configuration::get('NACEX_WSPASSWORD');
        $model = Configuration::get('NACEX_PRINT_MODEL');
        $eti = Configuration::get('NACEX_PRINT_ET');

        //Recogemos las variables del formulario para inicializar los filtros
        $fecha_desde = Tools::getValue('date_from') == '' ? $desde : Tools::getValue('date_from');
        $fecha_hasta = Tools::getValue('date_to') == '' ? $hasta : Tools::getValue('date_to');
        $estado_pedido = Tools::getValue('ncx_estado') == '' ? -1 : Tools::getValue('ncx_estado');

        if (!Tools::getValue('ncx_carrier_sel')) {
            $carriers_seleccionados = [];
        } else {
            $carriers_seleccionados = count(Tools::getValue('ncx_carrier_sel')) > 0 ? Tools::getValue('ncx_carrier_sel') : [];
        }
        $statuses = OrderState::getOrderStates((int)($cookie->id_lang));

        // En vez de aprovechar la función por defecto de Prestashop, tendremos que crear una que coja los carriers de los pedidos hechos
        //$carriers = Carrier::getCarriers((int) ($cookie->id_lang), true, false, false, null, Carrier::ALL_CARRIERS);
        $carriers = $this->getOrdersCarriers();

        //Si no permitimos generar expediciones de transportistas externos, no mostramos carriers externos
        if (Configuration::get('NACEX_FORCE_GENFORM') == 'NO') {
            foreach ($carriers as $key => $carrier) {
                if (strpos($carrier['ncx'], 'nacex') === false) { unset($carriers[$key]); } }
        }

        $this->_html .= "<div class='subheader'>
            <fieldset class='header-masivo'>
 			<div>
                <a target='_blank' title='" . $webtext . "' href='" . $webdir . "'><img style='width:100px;height: 24px;' src='" . $webimg . "' /></a>				
    			<div class='pageTitle'>
                    <h3 style='padding-left: 15px;'>" . $this->nacex->l('Mass generation of shipments') . "</h3>
                </div>
			</div>
			<div align='left' style='width:100%;margin: 0 auto'>
			<span class='ncx_minibutton' onclick='setHoy()'>" . $this->nacex->l('Today') . "</span>
			<span class='ncx_minibutton' onclick='setAyer()'>" . $this->nacex->l('Yesterday') . "</span>
			<span class='ncx_minibutton' onclick='setEstaSemana()'>" . $this->nacex->l('This week') . "</span>
			<span class='ncx_minibutton' onclick='setSemanaPasada()'>" . $this->nacex->l('Last week') . "</span>
			<span class='ncx_minibutton' onclick='setEsteMes()'>" . $this->nacex->l('This month') . "</span>
            <span class='ncx_date_input'><b>" . $this->nacex->l('From') . ": </b><input id='ncx_desde' type='text' style='width:130px;margin-right:10px' value='" . $fecha_desde . "' name='date_from' maxlength='19' size='4'></span>
			<span class='ncx_date_input'><b>" . $this->nacex->l('To') . ": </b><input id='ncx_hasta' type='text' style='width:130px' value='" . $fecha_hasta . "' name='date_to' maxlength='19' size='4'></span>
            <span style='margin-right: 35px; margin-left: 20px;' title='" . $this->nacex->l('Search orders') . "'>
                <img class='zoomable' id='searchIcon' src='" . nacexDTO::getPath() . "images/Buscar_expediciones.svg' 
                    title='" . $this->nacex->l('Search orders') . "' alt='" . $this->nacex->l('Search orders') . "' width='38px'>
            </span>
            <input type='hidden' id='accion' name='accion' value='' />
            
            <table style='width:100%;border:0px solid #000'>
			<tr>
			<td>			
			
			</td>
			<td rowspan='2'><br>
			<b>" . $this->nacex->l('Order status') . ": </b>
			<select id='ncx_estado' name='ncx_estado' style='width:175px'>
                        <option value='-1'>" . $this->nacex->l('All') . '</option>';
        foreach ($statuses as $status) {
            $this->_html .= $status['id_order_state'] == $estado_pedido ?
                "<option value='" . $status['id_order_state'] . "' selected>" . $status['name'] . '</option>' :
                "<option value='" . $status['id_order_state'] . "'>" . $status['name'] . '</option>';
        }
        /** Quitar tabla de alternar-transportistas y cambiar estilos del <div> **/
        $this->_html .= "</select>
			</td>
			<td rowspan='2' style='vertical-align:top;'>
			<div style='width:auto;float: left;margin-right: 2rem;margin-top: 2rem;'><b><a href='#' id='alternar-transportistas'>" . $this->nacex->l('Carriers') . ": </a></b>
            </div>
			<div style='margin: auto; margin-top: -10%; width: 40%;'><div id='transportistas' style='display:none'>";
        /** /Quitar tabla de Transportistas y cambiar estilos del <div> **/

        // Mostramos los carriers en un multiselect
        $this->_html .= "<select id='ncx_carrier_sel' name='ncx_carrier_sel[]' multiple='multiple'>";

        if (count($carriers_seleccionados) > 0 && $carriers_seleccionados !== false) {
            //Si vienen carriers en la variable $carriers_seleccionados marcamos los que haya en el array $carriers_seleccionados
            foreach ($carriers as $carrier) {
                $this->_html .= in_array($carrier['id_carrier'], $carriers_seleccionados) ?
                    "<option value='" . $carrier['id_carrier'] . "' selected>" . $carrier['name'] . '</option>' :
                    "<option value='" . $carrier['id_carrier'] . "'>" . $carrier['name'] . '</option>';
            }
        } else {
            //Si no vienen carriers en la variable $carriers_seleccionados los marcamos todos por defecto
            foreach ($carriers as $carrier) {
                $this->_html .= "<option value='" . $carrier['id_carrier'] . "'>" . $carrier['name'] . '</option>'; }
        }

        $this->_html .= '</select>';
        $this->_html .= "<br /><input type='button' id='select_all_carriers' name='select_all_carriers' class='ncx_button' value='" . $this->nacex->l('Select all') . "'>";

        $this->_html .= "</div>											
			</td>
			</tr>
			<tr>
			<td colspan='4' >
             
			</td>
			</tr>
			</table>
			</div>									
			</div>
			</fieldset>
			</div> <!-- subheader -->";

        $this->_html .= "
            <script type='text/javascript'>
                //$(document).ready(function() { 
                $(window).load(function() { 
                    /** Añadir datepicker **/
                    //$('#ncx_desde').datepicker({dateFormat: 'yy-mm-dd'});
                    //$('#ncx_hasta').datepicker({dateFormat: 'yy-mm-dd'});

                    $('#alternar-transportistas').on('click', function(){ 
                        $('#transportistas').toggle();
                    }); 

                    $('#searchIcon').on('click',function(){ 
                            console.log('click accionBuscarExpediciones');
                           accionBuscarExpediciones();
                    });

                    /** Cambiar visualización formulario expedición **/
                    //$('#createIcon, #btnOcultar').on('click',function(){ 
                    $('#createIcon').on('click',function(){ 
                           if ( $('input[name=\"idPedidoBox[]\"]:checked').length > 0) {   
                                  $('.accordion.panel').show();
                                  $(document).scrollTop( $('.accordion.panel').offset().top - 150 );
                           }else{ 
                                alert('Debe seleccionar pedidos');
                           } 
                    });
                    
                    $('input[name=\"idPedidoBox[]\"]').on('click',function() {
                        // Oculto el formulario al deseleccionar todas las opciones
                        if ( $('input[name=\"idPedidoBox[]\"]:checked').length === 0) $('.accordion.panel').hide();
                    });

                    $('#printIcon').on('click',function(){
                        if ( $('input[name=\"idPedidoBoxPrint[]\"]:checked').length > 0) {   
                             imprimirEtiquetasMasivo();
                        } else {
                            alert('Debe seleccionar expediciones (Agencia/Num)');
                        } 
                    });
                    
                    // Seleccionar todos los carriers del multiselect
                    $('#select_all_carriers').click(function() {
                        $('#ncx_carrier_sel option').prop('selected', true);
                    });
                    
                    // Toggle options del multiselect
                    $('select[multiple] option').mousedown(function(){
                       var self = $(this);
                    
                       if (self.prop('selected'))
                              self.prop('selected', false);
                       else
                           self.prop('selected', true);
                    
                       return false;
                    });

                });
                
			function setHoy(){
    			$('#ncx_desde').val('" . $hoy_desde . "');
    			$('#ncx_hasta').val('" . $hoy_hasta . "');
			}

			function setAyer(){
    			$('#ncx_desde').val('" . $ayer_desde . "');
    			$('#ncx_hasta').val('" . $ayer_hasta . "');
			}

			function setEstaSemana(){
    			$('#ncx_desde').val('" . $estasemana_desde . "');
    			$('#ncx_hasta').val('" . $estasemana_hasta . "');
			}

			function setSemanaPasada(){
    			$('#ncx_desde').val('" . $semanapasada_desde . "');
    			$('#ncx_hasta').val('" . $semanapasada_hasta . "');
			}									

			function setEsteMes(){
    			$('#ncx_desde').val('" . $estemes_desde . "');
    			$('#ncx_hasta').val('" . $estemes_hasta . "');
			}

			function accionBuscarExpediciones(){
    			procesando();
    			$('#accion').val('buscar');
    			document.nacex_filtro_masivo.submit();										 
			}

			function generarExpediciones(){
			     procesando();
			     $('#accion').val('generar');
			     document.nacex_filtro_masivo.submit();		
			}

			function imprimirEtiquetasMasivo(){
			    $('#accion').val('imprimir');
			    var array_exp = [];
    			var info = '';
                $('input[name=\"idPedidoBoxPrint[]\"]:checked').each(function(){
                    info = this.value.split(\"|\");
                    if(info[1]!=\"\" && info[1].length>0) {
                        array_exp.push(info);
                    }											
  			    });	 
                imprimir_iona(array_exp);
            }
            
            function formattedNow() {
                var today = new Date();
                var yyyy = today.getFullYear();
                let mm = today.getMonth() + 1; // Months start at 0!
                let dd = today.getDate();
            
                if (dd < 10) dd = '0' + dd;
                if (mm < 10) mm = '0' + mm;
            
                return now = yyyy + '-' + mm + '-' + dd;
            }
            
            // Validar que la fecha no es una anterior
            function validateFutureDate(customerDate) {
                //var now = moment(new Date()).format('YYYY-MM-DD');
                var now = formattedNow();
                return customerDate >= now;
            }
            </script>";
    }

    public function getListadoPedidos() {

        global $cookie;
        $id_lang = (int)($cookie->id_lang);

        /**
         * Loop para encontrar los padres de los carriers duplicados
         * lluis.casamajor@nacex.com 21102021 - Loop para encontrar los hijos huerfanos y añadirlos al filtro
         **/
        $padres = [];
        $hijoshuer = [];
        $padresDesaparecidos = [];
        $padresConsulta = Db::getInstance()->executeS('SELECT id_carrier,id_reference FROM ' . _DB_PREFIX_ . 'carrier ORDER BY id_carrier');
        $hijosSinPadreConsulta = Db::getInstance()->executeS('SELECT id_carrier,id_reference FROM ' . _DB_PREFIX_ . 'carrier WHERE id_reference NOT IN (SELECT id_carrier FROM ps_carrier) ORDER BY id_carrier');

        for ($i = 0; $i < count($padresConsulta); $i++) {
            // Cogemos las keys porque como se van a ir borrando elementos del array, para localizar la nueva posición de los valores
            $keysPadresConsulta = array_keys($padresConsulta);
            // Buscamos los elementos que tengan el mismo padre
            $hijos = array_keys(array_column($padresConsulta, 'id_reference'), $padresConsulta[$keysPadresConsulta[$i]]['id_carrier']);
            if (count($hijos) < 2) {// Si es 1 (siempre encontrará resultado)
                //if(isset($hijos[0])) $padres[$padresConsulta[$i]['id_carrier']] = $padresConsulta[$hijos[0]]['id_carrier'];
                if (isset($hijos[0])) { $padres[$padresConsulta[$keysPadresConsulta[$i]]['id_carrier']] = $padresConsulta[$keysPadresConsulta[$hijos[0]]]['id_carrier']; }
            } else {
                foreach ($hijos as $hijo) {
                    $valueHijos[] = $padresConsulta[$keysPadresConsulta[$hijo]]['id_carrier'];

                    // Si no es el "padre", eliminamos del array
                    if ($hijo != $i) { unset($padresConsulta[$keysPadresConsulta[$hijo]]); }
                }
                $valueHijos = array_reverse($valueHijos);
                $padres[$valueHijos[0]] = implode(',', $valueHijos);
                $valueHijos = [];
            }
        }

        /**
         * lluis.casamajor@nacex.com 21102021 - Loop para encontrar los hijos huerfanos y añadirlos al filtro
         **/
        foreach ($hijosSinPadreConsulta as $arrayhijo) {
            foreach ($arrayhijo as $key => $idhijo) {
                if ($key == 'id_carrier') {
                    if (!in_array($idhijo, $hijoshuer)) {
                        array_push($hijoshuer, $idhijo);
                    }
                }
                if ($key == 'id_reference') {
                    if (!in_array($idhijo, $padresDesaparecidos)) {
                        array_push($padresDesaparecidos, $idhijo);
                    }
                }
            }
        }
        /** Asignar carriers **/

        $sql = " select o.id_order, o.current_state, os.color, osl.name as status_name, DATE_FORMAT(o.date_add,'%d/%m/%Y %H:%i:%s') as date_add,
                    c.firstname, c.lastname, c.email, e.ref,e.agcli, e.fecha_alta, e.fecha_baja, e.ref, e.exp_cod, 
                    e.ag_cod_num_exp, e.serv_cod, e.serv, ca.name as carrier_name,ca.tip_serv as carrier_serv_id, ca.ncx, o.id_carrier,
                    p.iso_code, e.estado
		from " . _DB_PREFIX_ . 'orders o left join ' . _DB_PREFIX_ . 'customer c on (o.id_customer = c.id_customer)
                    left join ' . _DB_PREFIX_ . 'carrier ca on (o.id_carrier = ca.id_carrier)
                    left join ' . _DB_PREFIX_ . 'order_state os on (o.current_state = os.id_order_state)
                    left join ' . _DB_PREFIX_ . "order_state_lang osl on (o.current_state = osl.id_order_state and osl.id_lang = '" . (int)$id_lang . "')
                    left join " . _DB_PREFIX_ . 'nacex_expediciones e on (o.id_order = e.id_envio_order and e.fecha_baja is null)
                    left join ' . _DB_PREFIX_ . 'address a on (o.id_address_invoice= a.id_address )
                    left join ' . _DB_PREFIX_ . 'country p on (p.id_country= a.id_country )
                where 1=1 ';

        $fecha_desde = Tools::getValue('date_from');
        if (empty($fecha_desde)) {
            $errors[] = $this->nacex->l('You must report the date From');
        } else {
            $sql .= " and o.date_add >= '" . $fecha_desde . "'";
        }

        $fecha_hasta = Tools::getValue('date_to');
        if (empty($fecha_hasta)) {
            $errors[] = $this->nacex->l('You must report the date To');
        } else {
            $sql .= " and o.date_add < '" . $fecha_hasta . "'";
        }

        $estado_pedido = Tools::getValue('ncx_estado');
        if (empty($estado_pedido)) {
            $errors[] = $this->nacex->l('You must report the order status');
        } elseif ($estado_pedido != -1) {
            //solo añadimos la condicion con el estado de pedido !=-1 ya que el estado -1 es todos los estados.
            //Modo de compatibilidad con versiones de prestashop 1.4.x
            $sql .= ' and o.current_state = ' . $estado_pedido;
        }

        $carriers = Tools::getValue('ncx_carrier_sel');
        if (!(empty($carriers) || count($carriers) <= 0)) {
            $carrier_ids = '';
            foreach ($carriers as $carrier) {
                /** Asignar carriers **/
                if (isset($padres[$carrier]) && $padres[$carrier]) {
                    $carrier_ids .= $padres[$carrier] . ','; } else { // Buscamos el texto que corresponde al carrier y devolvemos la key (id) del actual
                        $id_car = array_keys(preg_grep("/.*,?$carrier,?.*/", $padres));
                        if (count($id_car) < 2 && isset($id_car[0])) {
                            $id_car = $id_car[0];
                            $carrier_ids .= $padres[$id_car] . ',';
                        } else {
                            foreach ($id_car as $id => $val) {
                                $index = explode(',', $padres[$val]);
                                if (in_array($carrier, $index)) {
                                    $id_car = $id_car[$id];
                                    $carrier_ids .= $padres[$id_car] . ',';
                                    break;
                                }
                            }
                        }
                    }
            }

            // Quitamos el último , del string
            $carrier_ids = substr($carrier_ids, 0, -1);
            // Convertimos en array para poder quedarnos con los elementos que no están repetidos y que están vacíos
            $array_carrier_ids = array_filter(array_unique(explode(',', $carrier_ids)));

            // Añadimos los padres desaparecidos
            if (is_array($padresDesaparecidos)) {
                foreach ($padresDesaparecidos as $padreDesaparecido) {
                    if (!in_array($padreDesaparecido, $array_carrier_ids)) {
                        array_push($array_carrier_ids, $padreDesaparecido);
                    }
                }
            }

            // Añadir a padres los hijos huérfanos también para que se puedan filtrar si no tienen padre
            if (is_array($hijoshuer)) {
                foreach ($hijoshuer as $hijoHuerfano) {
                    if (!in_array($hijoHuerfano, $array_carrier_ids)) {
                        array_push($array_carrier_ids, $hijoHuerfano);
                    }
                }
            }

            // Volvemos a crear un string con los elementos del array que nos han quedado
            $carrier_ids = implode(',', $array_carrier_ids);
            $sql .= ' and o.id_carrier in (' . $carrier_ids . ') ';
            //$sql .= " and o.id_carrier in (" . substr($carrier_ids, 0, strlen($carrier_ids)-1) . ") ";
        }

        if (isset($errors) && count($errors) > 0) {
            $this->_html .= $this->mostrarErrores($errors);
        } else {

            $sql .= ' order by id_order ';

            $expediciones = Db::getInstance()->executeS($sql);

            $this->_html .= $this->getTablaDatosListado($expediciones);
        }
    }

    public function getTablaDatosListado($datos = []) {

        $tabla = '';
        $hay_registros = 0;

        $nacexDTO = new nacexDTO();

        $tabla .= "<table name='list_table' id='list_table' class='table table-bordered' style='width:100%'>
                     <thead class='thead-default'>
            <tr>";
        if (nacexWS::ws_checkConnection()[0] != '500ERROR') {
            $tabla .= "<td colspan='8'>
                    <img class='zoomable' id='createIcon' src='" . nacexDTO::getPath() . "images/generar_expediciones.svg'  title='" . $this->nacex->l('Generate Expeditions') . "'
                    title='" . $this->nacex->l('Generate Expeditions') . "' alt='" . $this->nacex->l('Generate Expeditions') . "' width='38px)'>
                    </td>
                    <td colspan='4'>
                        <img class='zoomable' id='printIcon' src='" . nacexDTO::getPath() . "images/Print-outlined-circular-interface-button.svg' title='" . $this->nacex->l('Print labels') . "'
                        title='" . $this->nacex->l('Print labels') . "' alt='" . $this->nacex->l('Print labels') . "' width='38px'>";
        } else {
            $tabla .= "<td colspan='12'>
                            <p class='alert alert-info'>
                            " . $this->nacex->l('There is a problem with Nacex Web Service connection and some functionality may be affected, as well as <strong>some expedition status</strong>. Please, wait few minutes until connextion will be restored. We apologize for the inconvenients') . '
                            </p>';
        }
        $tabla .= "</td>
            </tr>
            <tr class='column-headers'>
                <th>
                    <span>" . $this->nacex->l('Generate Expeditions') . "</span>
                    <!-- <input class='noborder' type='checkbox' onclick='checkDelBoxes(this.form, \"idPedidoBox[]\", this.checked)' name='checkme' /> -->
                    <select class='selectByFilter'>
                        <option name='' value=''></option>    
                        <option name='T' value='nacex'>Nacex</option>    
                        <option name='S' value='nacexshop'>NacexShop</option>    
                        <option name='I' value='nacexint'>" . $this->nacex->l('Nacex International') . "</option>    
                        <option name='E' value='otros'>" . $this->nacex->l('Others') . "</option>    
                    </select>
                </th>
                <th class='center'>ID</th>
                <th class='center'>" . $this->nacex->l('Status') . "</th>
                <th class='center'>" . $this->nacex->l('Date') . "</th>
                <th class='center'>" . $this->nacex->l('Customer') . "</th>
                <th class='center'>" . $this->nacex->l('Email') . "</th>
                <th class='center'>" . $this->nacex->l('Phone') . "</th>
                <th class='center'>" . $this->nacex->l('Nacex Ref.') . "</th>
                <th class='center'>
                    <span>" . $this->nacex->l('Agency/Number') . "</span>
                    <input class='noborder' type='checkbox' onclick='checkDelBoxes(this.form, \"idPedidoBoxPrint[]\", this.checked)' name='checkme' /> 
                </th>
                <th class='center'>" . $this->nacex->l('Carriers') . "</th>
                <th class='center'>" . $this->nacex->l('Service') . "</th> 
                <th class='center'>" . $this->nacex->l('Exp. status') . '</th>
            </tr>
        </thead>
        <tbody>';

        if (!$datos || count($datos) <= 0) {
            $tabla .= " <tr style='height: 50px;' class='row_hover'>
		        <td class='center' colspan='12'>" . $this->nacex->l('No data found') . '</td>
		    </tr>';
        } else {
            $hay_registros = 1;

            foreach ($datos as $value) {

                // Comprobamos que no esté cancelada en Diana si existe expedición y si no está anulada o enviada
                if (!is_null($value['estado']) && ($value['estado'] != 'ANULADA' || $value['estado'] != 'OK')) {
                    $estadoExp = nacexWS::ws_getEstadoExpedicion($value);
                    $value['estado'] = $estadoExp['estado'];
                }

                //Datos del pedido
                $datos = nacexDAO::getDetallepedido($value['id_order']);

                $phone = !empty($datos[0]['phone_mobile']) ? $phone = $datos[0]['phone_mobile'] : $phone = $datos[0]['phone'];

                // Badge del estado de la expedición
                $estado = isset($value['estado']) ? $value['estado'] : '';
                if (empty($value['ag_cod_num_exp']) || empty($estado)) {
                    $badgeColor = '#999';
                    $badgeText = $this->nacex->l('Pending');
                } elseif ($estado == 'ANULADA' || $estado == 'BAJA') {
                    $badgeColor = '#dc3545';
                    $badgeText = $estado;
                } elseif ($estado == 'OK') {
                    $badgeColor = '#28a745';
                    $badgeText = $this->nacex->l('Delivered');
                } elseif ($estado == 'INCIDENCIA' || $estado == 'INCIDENCIA EXPEDICION') {
                    $badgeColor = '#daa520';
                    $badgeText = $this->nacex->l('Incident');
                } elseif ($estado == 'TRANSITO') {
                    $badgeColor = '#2196F3';
                    $badgeText = $this->nacex->l('In transit');
                } elseif ($estado == 'REPARTO') {
                    $badgeColor = '#17a2b8';
                    $badgeText = $this->nacex->l('Delivery');
                } elseif ($estado == 'PENDIENTE' || $estado == 'PENDIENTE DE INTEGRA') {
                    $badgeColor = '#ff9800';
                    $badgeText = $this->nacex->l('Notified');
                } else {
                    $badgeColor = '#ff9800';
                    $badgeText = $estado;
                }
                $badgeExp = "<span style='background-color:" . $badgeColor . ";color:white;padding:0.25em 0.6em;border-radius:1em;font-size:0.85em;white-space:nowrap;display:inline-block;'>" . $badgeText . "</span>";
                $link = Context::getContext()->link->getAdminLink('AdminOrders');

                $zona = 'NAC';
                $servicios_shop = array_keys($nacexDTO->getServiciosNacexShop());
                if (in_array($value['carrier_serv_id'], $servicios_shop)) { $zona = 'SHP'; }
                if (!empty($value['iso_code'])) {
                    if ($value['iso_code'] != 'ES' && $value['iso_code'] != 'PT' && $value['iso_code'] != 'AD') {
                        $zona = 'INT';
                    }
                }

                $tabla .= " <tr style='height: 35px;' class='row_hover'>
			<td class='left'>";
                if ($value['ag_cod_num_exp'] == '' || $value['estado'] == 'ANULADA') {
                    $tabla .= "<input class='noborderNacex' type='checkbox' value='" . $value['id_order'] . '|' . $value['exp_cod'] . '|' . $value['ncx'] . "' name='idPedidoBox[]' onclick='ambito(this,\"" . $zona . "\");'>";
                }
                $tabla .= "</td>
			<td class='left'><a href='"   . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $value['id_order'] . "</a></td>
			<td class='left'><a href='"   . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\"><span style='background-color:" . $value['color'] . ";color:white;padding:0.25em 0.6em;border-radius:1em;font-size:0.85em;white-space:nowrap;display:inline-block;'>" . $value['status_name'] . "</span></a></td>
			<td class='left'><a href='"   . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $value['date_add'] . "</a></td>
			<td class='left'><a href='"   . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $value['firstname'] . '&nbsp;' . $value['lastname'] . "</a></td>
			<td class='left'><a href='" . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $value['email'] . "</a></td>
			<td class='left'><a href='" . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $phone . "</a></td>
			<td class='left'><a href='" . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $value['ref'] . "</a></td>
			<td class='left'>";
                if ($value['ag_cod_num_exp'] != '' && $value['estado'] != 'ANULADA') {
                    $tabla .= "<input class='noborderNacex' type='checkbox' value='" . $value['id_order'] . '|' . $value['exp_cod'] . "' name='idPedidoBoxPrint[]' onclick='ambito(this,\"" . $zona . "\");'>";
                }
                $tabla .= "
                <a href='" . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none" . ($value['estado'] == 'ANULADA' ? ';color:red;' : ';') . ' ">' . $value['ag_cod_num_exp'] . '</a></td>';

                if ($value['ncx'] == 'nacexshop' || $value['ncx'] == 'nacexshopG') {
                    $iso_code = Language::getIsoById(nacexutils::getCurrentLang());
                    $img_servicio = '<img src="' . _MODULE_DIR_ . 'nacex/images/logos/NACEXshop_sostenible_' . $iso_code . '.svg" title="' . $value['carrier_name'] . '" alt="' . $value['carrier_name'] . '" style="height: 15px; width: auto;" />';
                } else {
                    $img_servicio = '<img src="' . _MODULE_DIR_ . 'nacex/images/logos/NACEX_logo.svg" title="' . $value['carrier_name'] . '" alt="' . $value['carrier_name'] . '" style="height: 15px; width: auto;" />';
                }

                $tabla .= "<td class='left'><a href='" . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $img_servicio . "</a></td>
            <td class='center'><a href='" . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $value['serv'] . "</a></td>
            <td class='center'><a href='" . $link . '&id_order=' . $value['id_order'] . "&vieworder' style=\"text-decoration:none\">" . $badgeExp . "</a></td>									 
			</tr>";
            }
        }

        $tabla .= '
			</tbody>
			</table>';

        if ($hay_registros) {
            $tabla .= $this->showDatosForm();
            $tabla .= Tools::getValue('nacex_print_iona', nacexVIEW::showIoNA());
        }

        $tabla .= "
  		<script>
		// control de checks para envios Nacionales o internacionales
		var nacional=0;
		var shop=0;
		var internacional=0;
  		var frecDep=0; 
		
  		function ambito(cb, zona) {
            //contador
            if (cb.checked){
                if (zona ==='NAC') nacional++;
                else if(zona === 'SHP') shop++;
                else internacional++;
            }else {
                if (zona ==='NAC') nacional--;
                else if(zona === 'SHP') shop--;
                else internacional--;
            }
            //control
            if (nacional !==0 && shop !==0 || nacional !==0 && internacional !== 0 || shop !==0 && internacional !== 0){
                alert('En documentación masiva debe gestionar por separado los envíos estándar, shop e internacionales');  						
                cb.checked = cb.checked ? false : true;
                if(zona ==='NAC') nacional--;
                else if (zona ==='SHP')  shop--;
                else internacional--;
                return;
            }
            // ajuste de formulario
            if (nacional===1 || shop===1){
                        $('#opt_serv_nac').prop( 'disabled', false);
                        $('#opt_serv_int').removeAttr('selected');
                        $('#opt_serv_int').prop( 'disabled', true);
                        $('#nacex_tip_ser').val(0); 
                        $('#nacex_contenido').prop( 'disabled', true);
                        $('#nacex_contenido').val(0);
                        $('#nacex_frecuencia').hide();
                        frecDep=0; 				 				
                        /*$('input:radio').each(function() { if($(this).next('label').text() == 'O - P. Origen' ) {  $(this).attr('disabled', false); $(this).prop('checked', 'checked');  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'D - P. Destino' ){  $(this).attr('disabled', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'T - P. Tercera' ){  $(this).attr('disabled', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'O - R. Origen' ) {  $(this).attr('disabled', false);	$(this).attr('checked', false); } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'D - R. Destino' ){  $(this).attr('disabled', false); $(this).attr('checked', true); } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'T - R. Tercera' ){  $(this).attr('disabled', false); $(this).attr('checked', false);  } }); 				 		
                        $('input:radio').each(function() { if($(this).next('label').text() == '0 - DOCS')       {  $(this).attr('disabled', false);	$(this).attr('checked', false); } });
                        $('input:radio').each(function() { if($(this).next('label').text() == '1 - BAG' )       {  $(this).attr('disabled', false);	$(this).attr('checked', false); } });
                        $('input:radio').each(function() { if($(this).next('label').text() == '2 - PAQ' )       {  $(this).attr('disabled', false); $(this).prop('checked', 'checked');  } });*/
                        $('input:radio').each(function() { if($(this).next('label').text() == 'M - Muestras'   ) { $(this).attr('disabled', true);  $(this).prop('checked', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'D - Documentos' ) { $(this).attr('disabled', true);  } });
                        /*$('input:radio').each(function() { if($(this).next('label').text() == 'SI' )             { $(this).attr('checked',  false); 	$(this).attr('disabled', false); } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'NO' )             { $(this).attr('checked', 'checked'); $(this).attr('disabled', false); } });*/
            }
            if (internacional===1){			 
                        $('#opt_serv_nac').removeAttr('selected');
                        $('#opt_serv_nac').prop( 'disabled', true);
                        $('#opt_serv_int').prop( 'disabled', false);
                        $('#nacex_frecuencia').hide();
                        frecDep=0;
                        $('#nacex_tip_ser').val(0);
                        $('#nacex_contenido').prop( 'disabled', false);
                        $('input:radio').each(function() { if($(this).next('label').text() == 'O - P. Origen' ) { $(this).attr('disabled', false); 	$(this).prop('checked', 'checked');  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'D - P. Destino' ){ $(this).attr('disabled', true);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'T - P. Tercera' ){ $(this).attr('disabled', true);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'O - R. Origen' ) { $(this).attr('disabled', true); 	$(this).attr('checked', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'D - R. Destino' ){ $(this).attr('disabled', true); 	$(this).attr('checked', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'T - R. Tercera' ){ $(this).attr('disabled', true);	$(this).attr('checked', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == '0 - DOCS')       { $(this).attr('disabled', true);	$(this).attr('checked', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == '1 - BAG' )       { $(this).attr('disabled', true);	$(this).attr('checked', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == '2 - PAQ' )       { $(this).attr('disabled', true);	$(this).attr('checked', false);   } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'M - Muestras'   ){ $(this).attr('disabled', false); 	$(this).prop('checked', 'checked');  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'D - Documentos' ){ $(this).attr('disabled', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'SI' )            { $(this).attr('disabled', true);	$(this).attr('checked', false);  } });
                        $('input:radio').each(function() { if($(this).next('label').text() == 'NO' )            { $(this).attr('disabled', false);	$(this).attr('checked', 'checked');  } });
            }
  						
		}
 		</script>";

        return $tabla;
    }

    private function showDatosForm() {

        $html = '';
        $nacexDTO = new nacexDTO();

        $nacex_impseg = (Tools::getValue('nacex_imp_seg') ? Tools::getValue('nacex_imp_seg') : Configuration::get('NACEX_DEFAULT_IMP_SEG'));
        $modo_f = (Configuration::get('NACEX_SERV_BACK_OR_FRONT') == 'F');

        $att = '';
        $cliente_tlf = '';
        $cliente_email = '';

        $html .= '<script>
            var cli_tlf = "' . substr($cliente_tlf, 0, 50) . '";
            var cli_email = "' . substr($cliente_email, 0, 50) . '";
            function setprealerta(tipo){
                if(tipo==="N")
                    deshabilitamodosprealerta();
                else
                    habilitamodosprealerta();
            }
            function setprealertaplus(tipo) {
                if(eval(document.getElementById("nacex_pre1_plus"))) {
                    if(tipo==="S" || tipo==="R") {
                        document.getElementById("nacex_pre1_plus").value = "";
                        document.getElementById("nacex_pre1_plus").disabled = true;
                    }else if(tipo === "P" || tipo==="E") {
                        document.getElementById("nacex_pre1_plus").value = "' . Configuration::get('NACEX_PREAL_PLUS_TXT') . '";	
                        document.getElementById("nacex_pre1_plus").disabled = false;
                        document.getElementById("nacex_pre1_plus").focus();
                    }
                }
            }
            function deshabilitamodosprealerta() {
                if(eval(document.getElementById("nacex_pre1_plus"))) {
                    document.getElementById("nacex_pre1_plus").value = "";
                    document.getElementById("nacex_pre1_plus").disabled = true;
                }	
                var obj = document.getElementsByName("nacex_mod_pre1");
                for(i=0; i<obj.length; i++) {
                    obj[i].checked=false;
                    obj[i].disabled=true;
                }
            }
            function habilitamodosprealerta() {
                var obj = document.getElementsByName("nacex_mod_pre1");
                for(i=0; i<obj.length; i++){
                    obj[i].disabled=false;
                    //Miramos si hay alguno que esté seleccionado
                    if(obj[i].checked === false) break;
                }
            }
        
            function blurDateValidation(customerDate) {
                var valida = validateFutureDate(customerDate);
                if(valida) saveDateLocalStorage(customerDate);
                return valida;
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

        //SELECTOR DE AGENCIAS Y CLIENTES ----------------------------------------------------------------------------
        $array_agclis = [];
        $array_agclis = explode(',', Configuration::get('NACEX_AGCLI'));
        $select_agcli = '';
        for ($i = 0; $i < count($array_agclis); $i++) {
            $select_agcli = $select_agcli . '<option ' . nacexutils::markSelectedOption('nacex_agcli', 'NACEX_AGCLI', trim($array_agclis[$i])) . " value='" . trim($array_agclis[$i]) . "'>" . trim($array_agclis[$i]) . '</option>';
        }

        //SELECTOR DE SERVICIOS DISPONIBLES ----------------------------------------------------------------------------
        $num_serv = '';
        $modo_f_con_b = false;
        $array_servicios = null;
        $def_serv = Configuration::get('NACEX_DEFAULT_TIP_SER');
        $opt_serv = explode('|', Configuration::get('NACEX_AVAILABLE_TIP_SER'));
        $array_servicios = $nacexDTO->getServiciosNacex();
        $num_serv = $def_serv;
        $select_serv = '';

        $shop_def_serv = Configuration::get('NACEX_DEFAULT_TIP_NXSHOP_SER');
        $shop_opt_serv = explode('|', Configuration::get('NACEX_AVAILABLE_TIP_NXSHOP_SER'));
        $array_servicios_shop = $nacexDTO->getServiciosNacexShop();
        $num_shop_serv = $shop_def_serv;

        // servicios internacionales
        $def_serv_int = Configuration::get('NACEX_DEFAULT_TIP_SER_INT');
        $opt_serv_int = explode('|', Configuration::get('NACEX_AVAILABLE_TIP_SER_INT'));
        $array_servicios_int = $nacexDTO->getServiciosNacexInt();

        if (isset($opt_serv)) {
            $select_serv = $select_serv . "<optgroup label='" . $this->nacex->l('National: available services') . "' id='opt_serv_nac'>";
        }
        for ($i = 0; $i < count($opt_serv); $i++) {
            $select_serv = $select_serv . '<option ' . nacexutils::markSelectedOption('nacex_tip_ser', 'NACEX_DEFAULT_TIP_SER', $opt_serv[$i]) . " value='" . $opt_serv[$i] . "'>" . $opt_serv[$i] . ' - ' . $array_servicios[$opt_serv[$i]]['nombre'] . '</option>';
        }
        if (!$modo_f_con_b && $modo_f && !in_array($num_serv, $opt_serv)) {
            $select_serv = $select_serv . "<option selected='selected' value='" . $num_serv . "'>" . $num_serv . ' - ' . @$array_servicios[$num_serv]['nombre'] . '</option>';
        }

        if (isset($shop_opt_serv)) {
            $select_serv = $select_serv . "<optgroup label='" . $this->nacex->l('NacexShop: available services') . "' id='opt_serv_nac'>";
        }
        for ($i = 0; $i < count($shop_opt_serv); $i++) {
            $select_serv = $select_serv . '<option ' . nacexutils::markSelectedOption('nacex_tip_ser', 'NACEX_DEFAULT_TIP_SER', $shop_opt_serv[$i]) . " value='" . $shop_opt_serv[$i] . "'>" . $shop_opt_serv[$i] . ' - ' . $array_servicios_shop[$shop_opt_serv[$i]]['nombre'] . '</option>';
        }
        if (!$modo_f_con_b && $modo_f && !in_array($num_shop_serv, $shop_opt_serv)) {
            $select_serv = $select_serv . "<option selected='selected' value='" . $num_shop_serv . "'>" . $num_shop_serv . ' - ' . @$array_servicios_shop[$num_shop_serv]['nombre'] . '</option>';
        }

        if (isset($opt_serv_int)) {
            $select_serv = $select_serv . "<optgroup label='" . $this->nacex->l('International: available services') . "' id='opt_serv_int'>";
            for ($i = 0; $i < count($opt_serv_int); $i++) {
                $select_serv = $select_serv . '<option ' . nacexutils::markSelectedOption('nacex_tip_ser', 'NACEX_DEFAULT_TIP_SER_INT', $opt_serv_int[$i]) . " value='" . $opt_serv_int[$i] . "'>" . $opt_serv_int[$i] . ' - ' . $array_servicios_int[$opt_serv_int[$i]]['nombre'] . '</option>';
            }
        }

        if (isset($opt_serv)) {
            $select_serv = $select_serv . '</optgroup>';
        }

        //SELECTOR TIPOS DE SEGURO ----------------------------------------------------------------------------
        $select_tipseg = '';
        $array_seguros = $nacexDTO->getSeguros();
        foreach ($array_seguros as $seg => $value) {
            $segname = $value['nombre'];
            $select_tipseg .= '<option ' . nacexutils::markSelectedFrontendOption('nacex_tip_seg', Configuration::get('NACEX_DEFAULT_TIP_SEG'), $seg) . " value='" . $seg . "'>" . $segname . '</option>';
        }

        //SELECTOR DEPARTAMENTOS ----------------------------------------------------------------------------
        $departamentos = Configuration::get('NACEX_DEPARTAMENTOS');
        $select_dpt = '';
        if ($departamentos) {
            $array_dept = explode(',', $departamentos);
            $deptdef = $array_dept[0];
            foreach ($array_dept as $dpt) {
                $select_dpt .= '<option ' . nacexutils::markSelectedFrontendOption('nacex_departamentos', $array_dept[0], $dpt) . " value='" . $dpt . "'>" . $dpt . '</option>';
            }
        }

        //CALCULAMOS EL NUMERO DE BULTOS POR DEFECTO
        //Al tener más de un pedido, sólo actualizamos el número de bultos si está configurado como Fijo, sino, por defecto 1
        $numbultos = max(1, (Configuration::get('NACEX_BULTOS') == 'F' ? Tools::getValue('nacex_bul', Configuration::get('NACEX_BULTOS_NUMERO')) : 1));

        //INSTRUCCIONES ADICIONALES CUSTOMIZADAS----------------------------------------------------------------------------------------
        $instrucciones = '';
        $inst_adi_pers = (Configuration::get('NACEX_INST_PERS') == 'SI');
        $inst_adi_val = Configuration::get('NACEX_CUSTOM_INST_ADI');
        $inst_adi_cantyref = (Configuration::get('NACEX_INS_ADI_Q_R') == 'SI');

        // Cogemos también las observaciones
        $obs_def = '';

        if ($inst_adi_pers) {
            $obs_def = Configuration::get('NACEX_CUSTOM_OBS');
        }

        // Lo tengo que hacer vía JS por pedido seleccionado
        /*if ($inst_adi_cantyref) {
            //Obtenemos más detalles del pedido, como el peso y las referencias
            $productospedido = Db::getInstance()->executeS('SELECT product_quantity, product_weight, product_reference FROM ' . _DB_PREFIX_ . 'order_detail where id_order = "' . $id_order . '"');
            foreach ($productospedido as $producto) {
                $prodref = str_replace(";", ",", $producto['product_reference']);
                $instrucciones .= " ** " . $producto['product_quantity'] . " # " . $prodref;
            }
            $instrucciones .= " ";
        }*/
        $instrucciones .= $inst_adi_val;

        $texto_instrucciones = Tools::getValue('inst_adi', $instrucciones);

        $html .= '
            <!-- Botón para volver arriba y poder pulsar el botón de generar expedición más fácilmente -->
            <a onclick="topFunction()" id="nacexToTop" title="' . $this->nacex->l('Go to top') . '" style=""><i class="arrow up"></i></a>
            
            <script>
                /* Funcionalidad toTop de Masivos */
                //Get the button:
                var mybutton = document.getElementById("nacexToTop");
                
                // When the user scrolls down 20px from the top of the document, show the button
                window.onscroll = function() {scrollFunction()};
                
                function scrollFunction() {
                    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                        mybutton.style.display = "block";
                    } else {
                        mybutton.style.display = "none";
                    }
                }
                
                // When the user clicks on the button, scroll to the top of the document
                function topFunction() {
                    document.body.scrollTop = 0; // For Safari
                    document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
                }
            </script>

		    <div class="accordion panel">
                <span class="panel-heading">
					<i class="icon-truck"></i>
					' . $this->nacex->l('Document shipments') . '
				</span>	
				
            <div align="center" id="ncx_boxinfo_masivo">	
            <div id="ncx_info_exp" style="width: 800px;">
		<fieldset class="diana">
		<legend><p>' . $this->nacex->l('You must fill in the following fields in order to generate the expeditions properly') . '</p>
		<p>' . $this->nacex->l('These parameters will be applied automatically to all expeditions to generate') . '</p></legend>
		<div class="inlineSuperior">
		    <strong>' . $this->nacex->l('Agency/Customer') . ':</strong> 								
            <select name="nacex_agcli" size="1">' . $select_agcli . '</select>																																											
                            
            <strong>' . $this->nacex->l('Service') . ':</strong> 								
            <select name="nacex_tip_ser" id="nacex_tip_ser" size="1">' . $select_serv . '</select>
            <select name="nacex_frecuencia" id="nacex_frecuencia" size="1" style="display:none;text-align:right;border: 0;">
                <option value="1">1 - ' . $this->nacex->l('Morning freq.') . '</option>
                <option selected="selected" value="2">2 - ' . $this->nacex->l('Afternoon freq.') . '</option>
                <option value="8">8 - ' . $this->nacex->l('Night freq.') . '</option>
            </select>
            <script>
                
            if ( $("#nacex_tip_ser option:selected").val()=== "09") {
            $("#nacex_frecuencia").slideToggle();
            frecDep=1;
            }
                                        
            $(document).on("change","#nacex_tip_ser",function() { 
                if ( $("#nacex_tip_ser option:selected").val()=== "09") {
                    alert("' . $this->nacex->l('Select the requency for Interdia service.\\n Frecuencies are availables in certain deliver and pickup postcodes depending on shipment request schedule.') . '");
                    $("#nacex_frecuencia").slideToggle();
                    frecDep=1;
                } else {
                    if (frecDep){
                        $("#nacex_frecuencia").slideToggle();
                        frecDep=0;
                    }
                }
            });
            </script>
            <strong>' . $this->nacex->l('Packages') . ':</strong>
            <input type="text" id="nacex_bul" name="nacex_bul" value="' . $numbultos . '" size=2 length=1 maxlength="3" />		
    
            <script type="text/javascript">
                //$(document).ready(function() {
                $(window).load(function() {
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
                    $(".selectByFilter").on("change", function() {
                        var serviciosNacex = ["nacex","nacexshop","nacexint","nacexG","nacexshopG","nacexintG"];
                        var tipo = $(".selectByFilter option[value=\'"+this.value+"\']").attr(\'name\');
                        var campo = eval($("#list_table [name=\'idPedidoBox[]\']"));
                        var incrementIdArray = [];
                        
                        if (eval($("#list_table [name=\'idPedidoBox[]\']"))) {
                            if (campo.length != undefined) {
                                for (i=0;i<campo.length;i++) {
                                    //this.value.split(\'~\')[3]
                                    var servicio = campo[i].value.split(\'|\')[2];
                                    
                                    // Si el servicio es estandar, y hemos pulsado sobre nacex, márcalos
                                    if((servicio === \'nacex\' || servicio === \'nacexG\') && tipo === \'T\') {
                                        campo[i].checked=true;
                                    } else if((servicio === \'nacexshop\' || servicio === \'nacexshopG\') && tipo === \'S\') {
                                        campo[i].checked=true;
                                    } else if((servicio === \'nacexint\' || servicio === \'nacexintG\') && tipo === \'I\') {
                                        campo[i].checked=true;
                                        incrementIdArray.push(campo[i].value.split(\'~\')[0]);
                                    } else if( serviciosNacex.indexOf(servicio) < 0 && tipo === \'E\') {
                                        campo[i].checked=true;
                                    } else {
                                        campo[i].checked=false;
                                    }
                                }
                                if($("#list_table [name=\'idPedidoBox[]\']:checked").length === 0)
                                    $(".accordion.panel").hide();
                                /*else
                                    $(".accordion.panel").show();*/
                                
                            } else {
                                campo.checked=false;
                            }
                        }
                        
                    });
                });
            
        function checkTipoPrealerta(tipo){
            var obj = document.getElementsByName("nacex_tip_pre1");
            for(i=0; i<obj.length; i++){
                if(obj[i].value===tipo){
                    obj[i].checked = true;
                    setprealerta(tipo);
                    // Parece que si estaba seleccionado de base la opción de Email hacía un bucle infinito
                    if(tipo=="E") break;
                }
            }	
        }
        
        function checkModoPrealerta(modo){
            var obj = document.getElementsByName("nacex_mod_pre1");
            for(i=0; i<obj.length; i++){
                if(obj[i].value===modo){
                    obj[i].checked = true;
                    setprealertaplus(modo);
                    // Comprobar tema del bucle infinito como pasaba en el tipo Prealerta
                    //if(tipo=="E") break;
                }
            }	
        }
                        
        function checkTipoSeguro(tipo){
            if (tipo !== "N"){
                document.getElementById("nacex_imp_seg").disabled = "";
                document.getElementById("nacex_imp_seg").value = "' . $nacex_impseg . '";
            }else{
                document.getElementById("nacex_imp_seg").disabled = "disabled";
                document.getElementById("nacex_imp_seg").value = "";
                }	
        }
        
        </script>
            <br>';
        if (strlen($select_dpt) > 1) {
            $html .= '<strong>' . $this->nacex->l('Department') . ':</strong> 								
                <select name="nacex_departamentos" style="max-width:150px">' . $select_dpt . '</select>';
        }

        $html .= '<p style="display: block;margin-top: 10px;"><strong>' . $this->nacex->l('Expedition date') . '</strong>
                <input type="date" id="nacex_fec" name="nacex_fec"
                        min="' . date('Y-m-d') . '" 
                        value=""
                        onkeydown="return false"
                        onblur="blurDateValidation(this.value)"
                        style="margin-left:15px;width: auto;text-align:right;border: 0;" />
            </p>
        </div>
				        
		<table style="width:100%">
		<tr>
            <td><b>' . $this->nacex->l('Charges') . ':</b></td>
            <td><b>' . $this->nacex->l('Refund') . ':</b></td>
            <td><b>' . $this->nacex->l('Shipment') . ':</b></td>
            <td><b>' . $this->nacex->l('Return') . ':</b></td>
            <td><b>' . $this->nacex->l('Insurance') . ':</b></td>
		</tr>
		<tr>
		<td style="width:20%;vertical-align:top;text-align:left">
            <input type="radio" name="nacex_tip_cob" value="O" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'O') . '/><label style="float: inherit;">O - P. Origen</label><br>  
            <input type="radio" name="nacex_tip_cob" value="D" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'D') . '/><label style="float: inherit;">D - P. Destino</label><br>
            <input type="radio" name="nacex_tip_cob" value="T" ' . nacexutils::markCheckedOption('nacex_tip_cob', 'NACEX_TIP_COB', 'T') . '/><label style="float: inherit;">T - P. Tercera</label> 
		</td>
		<td style="width:20%;vertical-align:top;text-align:left">
            <input type="radio" name="nacex_tip_ree" value="O" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'O') . '/><label style="float: inherit;">O - R. Origen</label> <br>  
            <input type="radio" name="nacex_tip_ree" value="D" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'D') . '/><label style="float: inherit;">D - R. Destino</label> <br>
            <input type="radio" name="nacex_tip_ree" value="T" ' . nacexutils::markCheckedOption('nacex_tip_ree', 'NACEX_TIP_REE', 'T') . '/><label style="float: inherit;">T - R. Tercera</label>  
		</td>
		<td style="width:20%;vertical-align:top;text-align:left">
            <input type="radio" name="nacex_tip_env" value="0" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '0') . '/><label style="float: inherit;">0 - DOCS</label> <br>
            <input type="radio" name="nacex_tip_env" value="1" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '1') . '/><label style="float: inherit;">1 - BAG</label> <br>
            <input type="radio" name="nacex_tip_env" value="2" ' . nacexutils::markCheckedOption('nacex_tip_env', 'NACEX_TIP_ENV', '2') . '/><label style="float: inherit;">2 - PAQ</label> <br> 		
            <input type="radio" name="nacex_tip_env" value="M" ' . nacexutils::markCheckedOption('nacex_tip_env_INT', 'NACEX_TIP_ENV_INT', 'M') . '/><label style="float: inherit;">M - Muestras</label> <br>
            <input type="radio" name="nacex_tip_env" value="D" ' . nacexutils::markCheckedOption('nacex_tip_env_INT', 'NACEX_TIP_ENV_INT', 'D') . '/><label style="float: inherit;">D - Documentos</label> <br>
		</td>
		<td style="width:20%;vertical-align:top;text-align:left">
            <input type="radio" name="nacex_ret" value="NO" ' . nacexutils::markCheckedOption('nacex_ret', 'NACEX_RET', 'NO') . '/><label style="float: inherit;">' . $this->nacex->l('No') . '</label> <br>
            <input type="radio" name="nacex_ret" value="SI" ' . nacexutils::markCheckedOption('nacex_ret', 'NACEX_RET', 'SI') . '/><label style="float: inherit;">' . $this->nacex->l('Yes') . '</label> 
		</td>
		<td style="width:20%;vertical-align:top;text-align:left">
            <select name="nacex_tip_seg" size="1" onchange="checkTipoSeguro(this.value);">' . $select_tipseg . '</select>
            <br><br>
            <b>' . $this->nacex->l('Insured amount') . ':</b>
            <br>
            <input type="text" id="nacex_imp_seg" name="nacex_imp_seg" value="' . $nacex_impseg . '" style="width:50px" length=1 maxlength="35" ' . ($nacex_impseg == '' ? 'disabled="disabled"' : '') . '/>&euro;									
		</td>
		</tr>
		</table>
								
		<hr/>
								
		<table style="width:100%">
		<tr>
		<td>
		<b>' . $this->nacex->l('Prealert') . ': </b><br>
            <input type="radio" name="nacex_tip_pre1" value="N" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'N') . ' onclick="setprealerta(&quot;N&quot;);"  checked="true"/>N - No
            <input type="radio" name="nacex_tip_pre1" value="S" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'S') . ' onclick="setprealerta(&quot;S&quot;);" />S - SMS
            <input type="radio" name="nacex_tip_pre1" value="E" ' . nacexutils::markCheckedOption('nacex_tip_pre1', 'NACEX_TIP_PREAL', 'E') . ' onclick="setprealerta(&quot;E&quot;);" />E - Email
		</td>
		<td>
		<b>' . $this->nacex->l('Prealert mode') . ':</b><br>
            <input type="radio" name="nacex_mod_pre1" value="S" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'S') . ' onclick="setprealertaplus(&quot;S&quot;);"  />S - Standard
            <input type="radio" name="nacex_mod_pre1" value="P" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'P') . ' onclick="setprealertaplus(&quot;P&quot;);"  />P - Plus
            <input type="radio" name="nacex_mod_pre1" value="R" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'R') . ' onclick="setprealertaplus(&quot;R&quot;);"  />R - Reparto							
            <input type="radio" name="nacex_mod_pre1" value="E" ' . nacexutils::markCheckedOption('nacex_mod_pre1', 'NACEX_MOD_PREAL', 'E') . ' onclick="setprealertaplus(&quot;E&quot;);"  />E - Reparto Plus
		</td>
		</tr>
		<tr>
            <td></td>
            <td><input style="text-align:center;width: 100%;" type="text" id="nacex_pre1_plus" name="nacex_pre1_plus" value="' . Tools::getValue('nacex_pre1_plus', Configuration::get('NACEX_PREAL_PLUS_TXT')) . '" length=1 maxlength="719" disabled="true"/></td>
		</tr>
		</table>
				            
		<hr style="margin-top:2px" />
							
		<b>' . $this->nacex->l('Delivery observations') . ':</b>
		<br>
		<input type="text" style="width:740px" value="' . $obs_def . '" name="obs" id="obs">
		<hr>
				        		
		<b>' . $this->nacex->l('International') . ' <br> ' . $this->nacex->l('Order information will be used as declared value for shipment') . '</b><br>						
		<br>';

        $html .= '<p>' . $this->nacex->l('Shipment content') . ':';
        $html .= '<select id="nacex_contenido" name="nacex_contenido" value="' . Tools::getValue('nacex_contenido', '') . '" style="width:375px" length=1 maxlength="38">';
        $contenidos = $nacexDTO->getContenidos();
        foreach ($contenidos as $cont) {
            $html .= '	<option ' . nacexutils::markSelectedOption('nacex_contenido', 'NACEX_DEFAULT_CONTENIDO', $cont) . ' value="' . $cont . '">' . $cont . '</option>';
        }
        $html .= '</select><br>';

        // INSTRUCCIONES ADICIONALES
        $html .= '<hr style="margin-top:2px" />
                    <p><b style="margin-left:5px;">' . $this->nacex->l('Additional shipping instructions') . '</b>';
        if ($inst_adi_cantyref) {
            $html .= '<span style="color:#555"><i>&nbsp;&nbsp;&nbsp;(' . $this->nacex->l('They will be added right after product reference and quantity if only one is selected. For multiple selection it only will show default values but all the info will be processed') . ')</i></span>';
        }
        $html .= ':</p>
                    <textarea style="width:94%; margin-left:15px;height:75px;" length=1 maxlength="600" name="inst_adi">' . $texto_instrucciones . '</textarea>';

        $html .= ' <table width="100%" border="0" cellpading="0" cellspacing="0">
			<tr>
			<td align="center">
			<input id="btngenerarExpediciones" name="btngenerarExpediciones" type="button" class="button" value="' . $this->nacex->l('Generate Expeditions') . '" onclick="javascript:generarExpediciones();">
			</td>
			</tr>
			</table>
				        	
			</fieldset>
			</div>
			</div>
			</div> <!-- accordion -->';
        if (Tools::getValue('nacex_tip_pre1', Configuration::get('NACEX_TIP_PREAL')) == 'S') {
            $html .= '<script type="text/javascript">checkTipoPrealerta("S");checkModoPrealerta("' . Tools::getValue('nacex_mod_pre1', Configuration::get('NACEX_MOD_PREAL')) . '");</script>';
        } elseif (Tools::getValue('nacex_tip_pre1', Configuration::get('NACEX_TIP_PREAL')) == 'E') {
            $html .= '<script type="text/javascript">checkTipoPrealerta("E");checkModoPrealerta("' . Tools::getValue('nacex_mod_pre1', Configuration::get('NACEX_MOD_PREAL')) . '");</script>';
        } else {
            $html .= '<script type="text/javascript">checkTipoPrealerta("N");checkModoPrealerta("' . Tools::getValue('nacex_mod_pre1', Configuration::get('NACEX_MOD_PREAL')) . '");</script>';
        }
        $html .= "<script type=\"text/javascript\">checkTipoSeguro('" . Tools::getValue('nacex_tip_seg', Configuration::get('NACEX_DEFAULT_TIP_SEG')) . "');</script>";

        return $html;
    }

    /** Añadir funcionalidad de coger los carriers de los pedidos hechos */
    private function getOrdersCarriers() {
        $query = 'SELECT DISTINCT id_carrier FROM ' . _DB_PREFIX_ . 'orders';
        $carriersOrder = '';
        foreach (Db::getInstance()->executeS($query) as $co) {
            $carriersOrder .= $co['id_carrier'] . ','; }

        $carriersOrder = substr($carriersOrder, 0, -1);
        $query = 'SELECT * FROM ' . _DB_PREFIX_ . "carrier WHERE id_carrier IN ($carriersOrder)";
        return Db::getInstance()->executeS($query);

    }

    private function generarExpediciones() {

        $html = '';

        $pedidos = Tools::getValue('idPedidoBox');
        if (!$pedidos || empty($pedidos) || count($pedidos) <= 0) {
            $errors[] = $this->nacex->l('You must select the orders you want to generate');
        }

        $nacex_agencia = '';
        $nacex_cliente = '';
        $nacex_agcli = Tools::getValue('nacex_agcli');
        if (!$nacex_agcli || empty($nacex_agcli)) {
            $errors[] = $this->nacex->l('Wrong agency and/or customer selected');
        } else {
            $nacex_agcli = explode('/', $nacex_agcli);
            $nacex_agencia = $nacex_agcli[0];
            $nacex_cliente = $nacex_agcli[1];
        }

        $nacex_tip_ser = Tools::getValue('nacex_tip_ser');
        if (!$nacex_tip_ser || empty($nacex_tip_ser)) {
            $errors[] = $this->nacex->l('Wrong service type');
        }

        $nacex_bul = Tools::getValue('nacex_bul', 1);
        $nacex_tip_cob = Tools::getValue('nacex_tip_cob', Configuration::get('NACEX_TIP_COB'));
        $nacex_tip_ree = Tools::getValue('nacex_tip_ree', Configuration::get('NACEX_TIP_REE'));
        $nacex_tip_env = Tools::getValue('nacex_tip_env', Configuration::get('NACEX_TIP_ENV'));
        $nacex_tip_env_int = Tools::getValue('nacex_tip_env_int', Configuration::get('NACEX_TIP_ENV_INT'));
        $nacex_ret = Tools::getValue('nacex_ret', Configuration::get('NACEX_RET'));
        $nacex_tip_seg = Tools::getValue('nacex_tip_seg', Configuration::get('NACEX_DEFAULT_TIP_SEG'));
        $nacex_imp_seg = Tools::getValue('nacex_imp_seg', 0);
        $nacex_tip_pre1 = Tools::getValue('nacex_tip_pre1', 'N');
        $nacex_mod_pre1 = Tools::getValue('nacex_mod_pre1', '');
        $nacex_pre1_plus = Tools::getValue('nacex_pre1_plus', '');
        $obs1 = substr(Tools::getValue('obs', ''), 0, 38);
        $obs2 = substr(Tools::getValue('obs', ''), 38, 38);

        //Instrucciones adicionales
        $inst_adi = Tools::getValue('inst_adi');

        $data = ['nacex_agencia' => $nacex_agencia,
            'nacex_cliente' => $nacex_cliente,
            'nacex_tip_ser' => $nacex_tip_ser,
            'nacex_bul' => $nacex_bul,
            'nacex_tip_cob' => $nacex_tip_cob,
            'nacex_tip_ree' => $nacex_tip_ree,
            'nacex_tip_env' => $nacex_tip_env,
            'nacex_ret' => $nacex_ret,
            'nacex_tip_seg' => $nacex_tip_seg,
            'nacex_imp_seg' => $nacex_imp_seg,
            'nacex_tip_pre1' => $nacex_tip_pre1,
            'nacex_mod_pre1' => $nacex_mod_pre1,
            'nacex_pre1_plus' => $nacex_pre1_plus,
            'obs1' => $obs1,
            'obs2' => $obs2,
            'inst_adi' => $inst_adi];

        if (!empty($errors) && count($errors) > 0) {
            $html = nacexVIEW::mostrarErrores($errors);
            return $html;
        } else {

            foreach ($pedidos as $pedido) {

                $infocheckbox = explode('|', $pedido);
                $pedido = $infocheckbox[0];
                $expe_codigo = $infocheckbox[1];

                $expedicion = nacexDAO::getDatosExpedicion($pedido, $expe_codigo);

                if (empty($expe_codigo) || $expedicion[0]['estado'] == 'ANULADA') {
                    $response = nacexWS::putExpedicionMasivo($pedido, $data);

                    if ($response['tipo'] == 'ERROR') {
                        $errors[] = $response['msg'];
                    } elseif ($response['tipo'] == 'SUCCESS') {
                        $success[] = $response['msg'];
                    }
                }
            }

            if (!empty($success) && count($success) > 0) {
                $html = nacexVIEW::mostrarSuccess($success);
            }
            if (!empty($errors) && count($errors) > 0) {
                $html = nacexVIEW::mostrarErrores($errors);
            }
        }

        return $html;
    }

    private function ws_getInfoEnvio($expe_codigo) {

        $dataResponse = [];
        //Datos de configuración
        $urlNacex = Configuration::get('NACEX_WS_URL');
        $URL = $urlNacex . '/soap';
        $nacexWSusername = Configuration::get('NACEX_WSUSERNAME');
        $nacexWSpassword = Configuration::get('NACEX_WSPASSWORD');

        $XML = '<soapenv:Envelope xmlns:soapenv="https://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
			      <typ:getInfoEnvio>
					<String_1>' . $nacexWSusername . '</String_1>
					<String_2>' . $nacexWSpassword . '</String_2>
			        <String_3>E</String_3>
			        <String_4>' . $expe_codigo . '</String_4>
			        <String_5></String_5>
			        <String_6></String_6>
			      </typ:getInfoEnvio>
			</soapenv:Body>
		</soapenv:Envelope>';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $XML);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml; charset=utf-8']);

        /**parche mexpositop 20171222**/
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $postResult = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = $this->language->get('error_ws_connect');
            $this->model_shipping_nacex->error($error);
        }
        $xml = new SimpleXMLElement($postResult);// mexpositop 20171128
        //simplexml_load_string($postResult, NULL, NULL, "https://www.w3.org/2003/05/soap-envelope");
        $xml->registerXPathNamespace('ns0', 'urn:soap/types');
        $resultado = $xml->xpath('//ns0:getInfoEnvioResponse/result');

        if ($resultado[0] != 'ERROR') {

            $dataResponse = ['expe_codigo' => $resultado[0],
                'del_ori' => $resultado[1],
                'num_exp' => $resultado[2],
                'del_cli' => $resultado[3],
                'cod_cli' => $resultado[4],
                'dpto' => $resultado[5],
                'f_exp' => $resultado[6],
                'cod_serv' => $resultado[7],
                'desc_serv' => $resultado[8],
                'tipo_envase' => $resultado[9],
                'desc_envase' => $resultado[10],
                'bultos' => $resultado[11],
                'peso' => $resultado[12],
                'excesos' => $resultado[13],
                'ref' => $resultado[14],
                'tipo_cobro' => $resultado[15],
                'rec_nombre' => $resultado[16],
                'rec_dir' => $resultado[17],
                'rec_cp' => $resultado[18],
                'rec_pob' => $resultado[19],
                'rec_prov' => $resultado[20],
                'rec_contacto' => $resultado[21],
                'rec_telf' => $resultado[22],
                'rec_agencia' => $resultado[23],
                'rec_telf_agencia' => $resultado[24],
                'ent_nombre' => $resultado[25],
                'ent_dir' => $resultado[26],
                'ent_cp' => $resultado[27],
                'ent_pob' => $resultado[28],
                'ent_prov' => $resultado[29],
                'ent_contacto' => $resultado[30],
                'ent_telf' => $resultado[31],
                'ent_del' => $resultado[32],
                'ent_telf_agencia' => $resultado[33],
                'observaciones' => $resultado[34],
                'aduana' => $resultado[35],
                'ok_15' => $resultado[36],
                'retorno' => $resultado[37],
                'gestion' => $resultado[38],
                'alerta' => $resultado[39],
                'prealerta1' => $resultado[40],
                'prealerta2' => $resultado[41],
                'prealerta3' => $resultado[42],
                'prealerta4' => $resultado[43],
                'prealerta5' => $resultado[44],
                'hist_estados' => $resultado[45]];
        }
        return $dataResponse;
    }
}
