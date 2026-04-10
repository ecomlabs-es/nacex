<?php

//20180620 mexpositop
include_once dirname(__FILE__) . '/nacexutils.php';
include_once dirname(__FILE__) . '/nacexDAO.php';
include_once dirname(__FILE__) . '/nacex.php';

if (Configuration::get('NACEX_SHOW_ERRORS') == 'SI') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

class nacextab extends AdminController
{
    private $nacex = '';

    public function __construct()
    {
        $this->nacex = new nacex();
        $this->display = 'view';
        parent::__construct();
        $this->title = $this->nacex->l('Delivery notes listing');
        $this->description = $this->nacex->l('Delivery notes listing');
        $this->meta_title = $this->nacex->l('Delivery notes listing');
        $this->page_header_toolbar_title = $this->nacex->l('Delivery notes listing');
        $this->bootstrap = true;
    }

    public function setMedia($isNewTheme = false)
    {

        $this->addCSS(_MODULE_DIR_ . 'nacex/css/nacex.css', 'all', null, true);
        $this->addCSS(_MODULE_DIR_ . 'nacex/css/print.css', 'all', null, true);
        parent::setMedia();

        //$this->addJquery('3.3.1', _MODULE_DIR_ . 'nacex/js/jquery-3.3.1.min.js');

        $this->addJs(_MODULE_DIR_.'nacex/js/nacex.js');
        $this->addJS(_MODULE_DIR_.'nacex/js/jquery-barcode.js');
        $this->addJS(_MODULE_DIR_.'nacex/js/jquery.printElement.min.js');
    }

    private $_html = '';

    public function initContent()
    {
        global $cookie;

        $webtext = $this->nacex->l('Go to Nacex web');
        $webdir = 'https://www.nacex.es';
        $webimg = _MODULE_DIR_ . 'nacex/images/logos/nacex_logista.png';

        $png_barcode_url = 'https://www.nacex.es/impCodBarras.do?x=150&y=60&fontsizeB=10&codebar=';

        $hoy_desde = date('Y-m-d');
        $hoy_hasta = date('Y-m-d');

        $ayer_desde = date('Y-m-d', strtotime('-1 day'));
        $ayer_hasta = date('Y-m-d', strtotime('-1 day'));

        $estasemana_desde = date('Y-m-d', time() + (1 - date('w')) * 24 * 3600);
        $estasemana_hasta = date('Y-m-d', time() + (7 - date('w')) * 24 * 3600);

        $timestamp_ultimodomingo = strtotime('last Sunday');
        $semanapasada_desde = date('Y-m-d', $timestamp_ultimodomingo - 6 * 24 * 3600);
        $semanapasada_hasta = date('Y-m-d', $timestamp_ultimodomingo);

        $estemes_desde = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
        $estemes_hasta = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, 0, date('Y')));

        $desde = Tools::getValue('date_from', date('Y-m-d'));
        $hasta = Tools::getValue('date_to', date('Y-m-d'));

        $nuevaConsulta = Tools::getValue('date_from', '') != '' && Tools::getValue('date_to', '') != '' ? 1 : 0;

        /* jquery
         * noConflict strategy

        $this->_html .= '
                <script type="text/javascript">
                    var tmp = $;     // jQuery noConflict strategy, temporary variable.
                    $ = $j331;
                 </script>';
         */
        $this->_html .= "
            <script>
                $(document).ready(function() {
                    $('#ncx_desde').datepicker({dateFormat: 'yy-mm-dd'});
                    $('#ncx_hasta').datepicker({dateFormat: 'yy-mm-dd'});
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
                function printSelection(node){
                    var content = node.innerHTML;
                    var titulo = '<h3 style=\"text-align:center\">[prestashop] " . $this->nacex->l('Nacex list') . "</h3>';
                    var pwin=window.open('','print_content','width=500,height=300');
                    pwin.document.open();
                    pwin.document.write('<html><head><link rel=\"stylesheet\" type=\"text/css\" href=\"" . _MODULE_DIR_ . "nacex/css/print.css\" /></head><body onload=\"window.print()\"><style>#nacex_listado_titulo{display:none;}</style>'+titulo+content+'</body></html>');
                    pwin.document.close();
                    setTimeout(function(){pwin.close();},1000);
                }
                $(document).on('click','#printIcon', function() {
                    printSelection(document.getElementById('ncx_div_listado'));
                    return false;
                });
            </script>

        <div class='panel'>
            <div class='panel-heading' style='display:flex;align-items:center;gap:1em;'>
                <a target='_blank' title='" . $webtext . "' href='" . $webdir . "'>
                    <img style='width:130px;height:auto;' src='" . $webimg . "' />
                </a>
                <span style='font-size:1.1em;'>" . $this->nacex->l('Documented orders to Nacex') . "</span>
            </div>
            <div class='panel-body'>
                <form method='post' style='text-align:center;'>
                    <div style='margin-bottom:1em;'>
                        <b>" . $this->nacex->l('From') . ": </b>
                        <input id='ncx_desde' type='text' style='width:135px;margin-right:10px' value='" . $desde . "' name='date_from' maxlength='19' size='4'>
                        <b>" . $this->nacex->l('To') . ": </b>
                        <input id='ncx_hasta' type='text' style='width:135px' value='" . $hasta . "' name='date_to' maxlength='19' size='4'>
                    </div>
                    <div style='margin-bottom:1em;'>
                        <span class='ncx_minibutton' onclick='setHoy()'>" . $this->nacex->l('Today') . "</span>
                        <span class='ncx_minibutton' onclick='setAyer()'>" . $this->nacex->l('Yesterday') . "</span>
                        <span class='ncx_minibutton' onclick='setEstaSemana()'>" . $this->nacex->l('This week') . "</span>
                        <span class='ncx_minibutton' onclick='setSemanaPasada()'>" . $this->nacex->l('Last week') . "</span>
                        <span class='ncx_minibutton' onclick='setEsteMes()'>" . $this->nacex->l('This month') . "</span>
                    </div>
                    <input class='btn btn-primary' onclick='procesando();' type='submit' name='submitListado' value='" . $this->nacex->l('Generate list') . "'>
                </form>
            </div>
        </div>";

        if ($nuevaConsulta) {

            // Query optimizada: traemos expediciones + datos del pedido en un solo JOIN
            // evitando el problema N+1 (antes se hacían 3-4 queries por expedición)
            $expediciones = Db::getInstance()->executeS(
                'SELECT e.*,
                    o.module, o.total_paid_real, o.total_paid,
                    u.email,
                    a.firstname, a.lastname, a.address1, a.postcode, a.city, a.phone, a.phone_mobile,
                    z.iso_code,
                    ai.firstname AS invoice_firstname, ai.lastname AS invoice_lastname
                FROM ' . _DB_PREFIX_ . 'nacex_expediciones e
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON o.id_order = e.id_envio_order
                LEFT JOIN ' . _DB_PREFIX_ . 'customer u ON u.id_customer = o.id_customer
                LEFT JOIN ' . _DB_PREFIX_ . 'address a ON a.id_address = o.id_address_delivery
                LEFT JOIN ' . _DB_PREFIX_ . 'country z ON z.id_country = a.id_country
                LEFT JOIN ' . _DB_PREFIX_ . 'address ai ON ai.id_address = o.id_address_invoice
                WHERE e.fecha_alta >= \'' . pSQL($desde) . ' 00:00:00\'
                AND e.fecha_alta <= \'' . pSQL($hasta) . ' 23:59:59\'
                AND e.fecha_baja IS NULL
                ORDER BY e.fecha_alta DESC'
            );

            // Pre-cargar productos de todos los pedidos en una sola query
            $orderIds = [];
            if ($expediciones) {
                foreach ($expediciones as $exp) {
                    $orderIds[] = (int) $exp['id_envio_order'];
                }
                $orderIds = array_unique($orderIds);
            }

            $productosMap = [];
            if (!empty($orderIds)) {
                $productos = Db::getInstance()->executeS(
                    'SELECT id_order, product_quantity, product_weight
                    FROM ' . _DB_PREFIX_ . 'order_detail
                    WHERE id_order IN (' . implode(',', $orderIds) . ')'
                );
                foreach ($productos as $p) {
                    $productosMap[$p['id_order']][] = $p;
                }
            }

            // Cargar modulos de reembolso una sola vez
            $array_modsree = explode('|', Configuration::get('NACEX_MODULOS_REEMBOLSO'));

            if ($expediciones) {
                $this->_html .= "<div id='ncx_div_listado'>" . "<h2 align='center'>" . $this->nacex->l('Delivery notes list from') . ' (' . $desde . ') ' . $this->nacex->l('to') . ' (' . $hasta . ") 
                    <img id='printIcon' style='float:right;cursor:pointer;margin-right:10%' alt='" . $this->nacex->l('Print list') . "' title='" . $this->nacex->l('Print list') . "' class='noprint' src='../modules/nacex/images/print_icon.png' /></h2>
                    <table id='ncx_tabla_listado' class=\"table table-bordered\">
                        <thead class=\"thead-default\">
                            <tr class=\"column-headers\">
                                <th>Id</th>
                                <th>" . $this->nacex->l('Barcode') . '</th>
                                <th>
                                    <p>' . $this->nacex->l('Delivery Note') . '</p>
                                    <p>' . $this->nacex->l('Recipient\'s name') . '</p>
                                    <p>' . $this->nacex->l('Delivery Region postcode') . '</p>	  						  				
                                </th>
                                <th>
                                    <p>' . $this->nacex->l('Reference') . '</p>
                                    <p>' . $this->nacex->l('Shipping address') . '</p>
                                </th>
                                <th>
                                    <p>' . $this->nacex->l('Service') . '</p>
                                    <p>' . $this->nacex->l('Refund amount') . '</p>		  				
                                </th>
                                <th>' . $this->nacex->l('Packages') . '</th>
                                <th>' . $this->nacex->l('Kilos') . '</th>
                            </tr>
                        </thead>
                        <tbody>
		  		';

                $token = Tools::getAdminToken('AdminOrders' . (int) Tab::getIdFromClassName('AdminOrders') . (int) $cookie->id_employee);

                $sum_bul = 0;
                $sum_kil = 0;
                $sum_ree = 0;
                $agclis = [];

                $cont = 1;
                foreach ($expediciones as $exp) {

                    $isNacexShop = isset($exp['shop_codigo']) && $exp['shop_codigo'] != '1' && $exp['shop_codigo'] != '';
                    $agcli = nacexutils::getDefValue($exp, 'agcli', null);
                    if ($agcli != null) {
                        $array_agclis = explode('/', $agcli);
                        if (count($array_agclis) == 2) {
                            $agclis[$agcli] = $array_agclis[0] . ' / ' . $array_agclis[1];
                        }
                    }

                    // Calcular peso y bultos desde los productos pre-cargados (sin query adicional)
                    $orderId = $exp['id_envio_order'];
                    $peso = 1;
                    $bultos_calc = 1;
                    if (isset($productosMap[$orderId])) {
                        foreach ($productosMap[$orderId] as $producto) {
                            $peso += floatval($producto['product_quantity'] * $producto['product_weight']);
                            $bultos_calc += $producto['product_quantity'];
                        }
                    }
                    if ($peso < 1) { $peso = 1; }
                    if ($bultos_calc < 1) { $bultos_calc = 1; }

                    // Datos del pedido ya vienen en el JOIN
                    $nom_ent = $exp['firstname'] . ' ' . $exp['lastname'];
                    $dir_ent = $exp['address1'];
                    $pob_ent = $exp['city'];
                    $cp_ent = $exp['postcode'];

                    // Reembolso
                    $ree = 0;
                    $total_paid = $exp['total_paid_real'] > 0 ? $exp['total_paid_real'] : $exp['total_paid'];
                    $metodo_pago = strtolower($exp['module']);
                    if (in_array($metodo_pago, $array_modsree) || strpos($metodo_pago, 'cashondelivery') !== false) {
                        if (isset($exp['imp_ree']) && $exp['imp_ree'] != 0) {
                            $ree = floatval($exp['imp_ree']);
                        } else {
                            $ree = floatval($total_paid);
                        }
                    }

                    $sum_bul += nacexutils::getDefValue($exp, 'bultos', 1);
                    $sum_kil += $peso;
                    $sum_ree += $ree;
                    $ref = nacexutils::getReferenciaGeneral() . $orderId;
                    $codbar = preg_replace('/;/', '<br>', $exp['barcode']);
                    $imgbarcodePrint = preg_replace("/\//", '', $exp['ag_cod_num_exp']) . '00';
                    $array_agcod_numexp = explode('/', $exp['ag_cod_num_exp']);
                    $agcod_numexp = $array_agcod_numexp[0] . ' / ' . $array_agcod_numexp[1];
                    $cpPoblacion = $isNacexShop && isset($exp['shop_cp']) && isset($exp['shop_poblacion']) ? $exp['shop_cp'] . ' ' . $exp['shop_poblacion'] : $cp_ent . ' ' . $pob_ent;
                    // Datos de facturacion ya vienen en el JOIN (invoice_firstname, invoice_lastname)
                    $attShop = !$isNacexShop ? '' : '<p><i><b>Att:</b> ' . htmlspecialchars($exp['invoice_firstname'] . ' ' . $exp['invoice_lastname'], ENT_QUOTES, 'UTF-8') . '</i></p>';
                    $direccion = $isNacexShop && isset($exp['shop_direccion']) ? $exp['shop_direccion'] : $dir_ent;

                    $odd = $cont % 2 != 0 ? ' class="odd"' : '';

                    $this->_html .= '
		  				<tr' . $odd . ">
		  					<td style='text-align:center'>
								<a class='ballLink' target='_blank' href='" . $_SERVER['PHP_SELF'] . '?tab=AdminOrders&id_order=' . $exp['id_envio_order'] . '&vieworder&token=' . $token . "'>" . $exp['id_envio_order'] . "</a>
							</td>
		  					<td style='padding: 5px 5px 0 5px;' id= 'td_cb'>
		  						<div class='noprint screen' id='" . $imgbarcodePrint . "' style='max-width:165px !important;max-height:56px !important;width: auto;height: auto;'></div>
                                <div class='noscreen print' id='" . $imgbarcodePrint . "_2' style='max-width:380px !important;max-height:170px !important;width: auto;height: auto;'></div>  
		  								<script>
                                            $(document).ready(function() {
                                                $('#" . $imgbarcodePrint . "').barcode({code: '" . $imgbarcodePrint . "', crc:false}, 'int25',{barWidth:1, barHeight:40, fontSize:10});
                                                $('#" . $imgbarcodePrint . "_2').barcode({code: '" . $imgbarcodePrint . "', crc:false}, 'int25',{barWidth:2, barHeight:100, fontSize:24});
                                            }); 
                                        </script>
		  					</td>			  				
			  				<td>		  					
		  						<p>" . $agcod_numexp . '</p>
		  						<p>' . htmlspecialchars($nom_ent, ENT_QUOTES, 'UTF-8') . '</p>
		  						<p>' . htmlspecialchars($cpPoblacion, ENT_QUOTES, 'UTF-8') . '</p>
		  							' . $attShop . "		  							  							  					
		  					</td>
			  				<td>
		  						<p style='margin-top:4px;'>" . $exp['ref'] . "</p>
		  						<p style='margin-top:25px;'>" . $direccion . "</p>
		  					</td>
			  				<td>
		  						<p style='margin-top:4px;'>" . $exp['serv'] . "</p>
		  						<p style='margin-top:25px;'>" . nacexutils::normalizarDecimales($ree, 2, ',', ' ', true, true) . "</p>			  					
		  					</td>		  					
		  					<td style='text-align:center;vertical-align:middle;'>" . nacexutils::getDefValue($exp, 'bultos', '1') . "
							</td>
		  					<td style='text-align:center;vertical-align:middle;'>" . nacexutils::normalizarDecimales($peso, 2, ',', ' ', true, true);

                    //salto de p�gina
                    if ($cont % 14 == 0) {
                        $this->_html .= "<div class='pagebreak'> </div>";
                    }
                    $cont += 1;

                    $this->_html .= ' </td>
		  			    </tr>';
                }
                $this->_html .= '</tbody>';

                $this->_html .=  "<div id='info-usuario'><sub>" . Configuration::get('NACEX_WSUSERNAME') . '</sub><br>';
                foreach ($agclis as $ag) {
                    if ($ag != '') {
                        $this->_html .= '<sub> ' . $this->nacex->l('Customer') . ' ' . $ag . '</sub><br>';
                    }
                }
                $this->_html .= '</div>';

                $this->_html .= "<tfoot><tr style='border-top-width: 2px'>
                            <td colspan='3' style='text-align:right;margin-right:10px'><strong>" . $this->nacex->l('Total') . ":</strong></td>
                            <td  style='text-align:right;margin-right:10px'>" . ($cont - 1) . ' ' . $this->nacex->l('Exp.') . "</td>
                            <td style='text-align:center;'>" . number_format($sum_ree, 2, ',', ' ') . " &euro;</td>
                            <td style='text-align:center;'>" . $sum_bul . ' ' . $this->nacex->l('Packages') . "</td>
                            <td style='text-align:center;'>" . number_format($sum_kil, 2, ',', ' ') . ' Kg</td>		  				  		
                         </tr>
		  			</tfoot>
                </table>
              </div>';
            } else {
                $this->_html .= "<fieldset>
							<div align='center' style='color:grey'><i>(" . $this->nacex->l('No results') . ')</i></div>
						</fieldset>';
            }
            $this->_html .= '<fieldset>';
        }

        /* jquery
         * noConflict strategy END

        $this->_html .= '
                <script type="text/javascript">
                   $ = tmp;   // jQuery noConflict strategy, restore main jquery control
                </script>';
       */
        $this->context->smarty->assign('content', $this->_html);
    }
}
