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
        $webimg = _MODULE_DIR_ . 'nacex/images/logos/nacex_logista.png';

        $hoy = date('Y-m-d');
        $ayer = date('Y-m-d', strtotime('-1 day'));
        $estasemana_desde = date('Y-m-d', strtotime('monday this week'));
        $estasemana_hasta = date('Y-m-d', strtotime('sunday this week'));
        $semanapasada_desde = date('Y-m-d', strtotime('monday last week'));
        $semanapasada_hasta = date('Y-m-d', strtotime('sunday last week'));
        $estemes_desde = date('Y-m-01');
        $estemes_hasta = date('Y-m-t');

        $desde = Tools::getValue('date_from', $hoy);
        $hasta = Tools::getValue('date_to', $hoy);

        $nuevaConsulta = Tools::getValue('date_from', '') != '' && Tools::getValue('date_to', '') != '' ? 1 : 0;

        $this->_html .= "
            <script>
                function setRango(desde, hasta){
                    $('#ncx_desde').val(desde);
                    $('#ncx_hasta').val(hasta);
                }
                function printSelection(node){
                    var content = node.innerHTML;
                    var titulo = '<h3 style=\"text-align:center\">" . $this->nacex->l('Nacex list') . "</h3>';
                    var pwin=window.open('','print_content','width=800,height=600');
                    pwin.document.open();
                    pwin.document.write('<html><head><link rel=\"stylesheet\" type=\"text/css\" href=\"" . _MODULE_DIR_ . "nacex/css/print.css\" /></head><body onload=\"window.print()\">'+titulo+content+'</body></html>');
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
                <a target='_blank' href='https://www.nacex.es'>
                    <img style='width:130px;height:auto;' src='" . $webimg . "' />
                </a>
                <span style='font-size:1.1em;'>" . $this->nacex->l('Documented orders to Nacex') . "</span>
            </div>
            <div class='panel-body'>
                <form method='post'>
                    <div class='form-group' style='display:flex;align-items:center;justify-content:center;gap:1em;flex-wrap:wrap;'>
                        <label for='ncx_desde' style='margin:0;'>" . $this->nacex->l('From') . "</label>
                        <input id='ncx_desde' type='date' class='form-control' style='width:auto;' value='" . $desde . "' name='date_from'>
                        <label for='ncx_hasta' style='margin:0;'>" . $this->nacex->l('To') . "</label>
                        <input id='ncx_hasta' type='date' class='form-control' style='width:auto;' value='" . $hasta . "' name='date_to'>
                    </div>
                    <div style='text-align:center;margin-bottom:1em;'>
                        <div class='btn-group' role='group'>
                            <button type='button' class='btn btn-default btn-sm' onclick=\"setRango('" . $hoy . "','" . $hoy . "')\">" . $this->nacex->l('Today') . "</button>
                            <button type='button' class='btn btn-default btn-sm' onclick=\"setRango('" . $ayer . "','" . $ayer . "')\">" . $this->nacex->l('Yesterday') . "</button>
                            <button type='button' class='btn btn-default btn-sm' onclick=\"setRango('" . $estasemana_desde . "','" . $estasemana_hasta . "')\">" . $this->nacex->l('This week') . "</button>
                            <button type='button' class='btn btn-default btn-sm' onclick=\"setRango('" . $semanapasada_desde . "','" . $semanapasada_hasta . "')\">" . $this->nacex->l('Last week') . "</button>
                            <button type='button' class='btn btn-default btn-sm' onclick=\"setRango('" . $estemes_desde . "','" . $estemes_hasta . "')\">" . $this->nacex->l('This month') . "</button>
                        </div>
                    </div>
                    <div style='text-align:center;'>
                        <button type='submit' class='btn btn-primary' name='submitListado'>" . $this->nacex->l('Generate list') . "</button>
                    </div>
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
                $this->_html .= "
                <div class='panel' id='ncx_div_listado'>
                    <div class='panel-heading' style='display:flex;align-items:center;justify-content:space-between;'>
                        <span>" . $this->nacex->l('Delivery notes list from') . ' ' . date('d/m/Y', strtotime($desde)) . ' ' . $this->nacex->l('to') . ' ' . date('d/m/Y', strtotime($hasta)) . "</span>
                        <a href='#' id='printIcon' class='btn btn-default btn-sm noprint' title='" . $this->nacex->l('Print list') . "'>
                            <i class='material-icons' style='font-size:14px;vertical-align:middle;'>print</i> " . $this->nacex->l('Print') . "
                        </a>
                    </div>
                    <div class='table-responsive'>
                    <table id='ncx_tabla_listado' class='table table-hover'>
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>" . $this->nacex->l('Barcode') . '</th>
                                <th>' . $this->nacex->l('Delivery Note') . '</th>
                                <th>' . $this->nacex->l('Reference') . '</th>
                                <th>' . $this->nacex->l('Service') . '</th>
                                <th>' . $this->nacex->l('Packages') . '</th>
                                <th>' . $this->nacex->l('Kilos') . '</th>
                            </tr>
                        </thead>
                        <tbody>
		  		';

                $linkOrders = Context::getContext()->link->getAdminLink('AdminOrders');

                $sum_bul = 0;
                $sum_kil = 0;
                $sum_ree = 0;
                $agclis = [];
                $barcodeData = [];

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
                    $imgbarcodePrint = preg_replace("/\//", '', $exp['ag_cod_num_exp']) . '00';
                    $array_agcod_numexp = explode('/', $exp['ag_cod_num_exp']);
                    $agcod_numexp = $array_agcod_numexp[0] . ' / ' . $array_agcod_numexp[1];
                    $cpPoblacion = $isNacexShop && isset($exp['shop_cp']) && isset($exp['shop_poblacion']) ? $exp['shop_cp'] . ' ' . $exp['shop_poblacion'] : $cp_ent . ' ' . $pob_ent;
                    // Datos de facturacion ya vienen en el JOIN (invoice_firstname, invoice_lastname)
                    $attShop = !$isNacexShop ? '' : '<p><i><b>Att:</b> ' . htmlspecialchars($exp['invoice_firstname'] . ' ' . $exp['invoice_lastname'], ENT_QUOTES, 'UTF-8') . '</i></p>';
                    $direccion = $isNacexShop && isset($exp['shop_direccion']) ? $exp['shop_direccion'] : $dir_ent;

                    $barcodeData[] = $imgbarcodePrint;

                    $this->_html .= "
                        <tr>
                            <th scope='row'>
                                <a href='" . $linkOrders . '&id_order=' . $exp['id_envio_order'] . "&vieworder'>" . $exp['id_envio_order'] . "</a>
                            </th>
                            <td>
                                <div class='noprint' id='" . $imgbarcodePrint . "' style='max-width:165px;max-height:56px;'></div>
                                <div class='noscreen' id='" . $imgbarcodePrint . "_2' style='max-width:380px;max-height:170px;'></div>
                            </td>
                            <td>
                                <strong>" . $agcod_numexp . "</strong><br>
                                " . htmlspecialchars($nom_ent, ENT_QUOTES, 'UTF-8') . "<br>
                                <small class='text-muted'>" . htmlspecialchars($cpPoblacion, ENT_QUOTES, 'UTF-8') . "</small>
                                " . $attShop . "
                            </td>
                            <td>
                                " . $exp['ref'] . "<br>
                                <small class='text-muted'>" . htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8') . "</small>
                            </td>
                            <td>
                                " . $exp['serv'] . "<br>
                                <small class='text-muted'>" . nacexutils::normalizarDecimales($ree, 2, ',', ' ', true, true) . "</small>
                            </td>
                            <td>" . nacexutils::getDefValue($exp, 'bultos', '1') . "</td>
                            <td>" . nacexutils::normalizarDecimales($peso, 2, ',', ' ', true, true) . "</td>
                        </tr>";

                    $cont += 1;
                }
                $this->_html .= '</tbody>';

                $this->_html .= "<tfoot><tr>
                            <td colspan='3' style='text-align:right;'><strong>" . $this->nacex->l('Total') . ":</strong></td>
                            <td style='text-align:right;'>" . ($cont - 1) . ' ' . $this->nacex->l('Exp.') . "</td>
                            <td style='text-align:center;'>" . number_format($sum_ree, 2, ',', ' ') . " &euro;</td>
                            <td style='text-align:center;'>" . $sum_bul . ' ' . $this->nacex->l('Packages') . "</td>
                            <td style='text-align:center;'>" . number_format($sum_kil, 2, ',', ' ') . " Kg</td>
                         </tr></tfoot>
                    </table>
                    </div>
                    <div style='padding:0.5em 1em;color:#999;font-size:0.85em;'>";
                $this->_html .= Configuration::get('NACEX_WSUSERNAME');
                foreach ($agclis as $ag) {
                    if ($ag != '') {
                        $this->_html .= ' | ' . $this->nacex->l('Customer') . ' ' . $ag;
                    }
                }
                $this->_html .= "</div>
                </div>
                <script>
                    $(document).ready(function() {";
                foreach ($barcodeData as $bc) {
                    $this->_html .= "$('#" . $bc . "').barcode({code: '" . $bc . "', crc:false}, 'int25',{barWidth:1, barHeight:40, fontSize:10});";
                    $this->_html .= "$('#" . $bc . "_2').barcode({code: '" . $bc . "', crc:false}, 'int25',{barWidth:2, barHeight:100, fontSize:24});";
                }
                $this->_html .= "});
                </script>";
            } else {
                $this->_html .= "
                <div class='panel'>
                    <div class='alert alert-info' style='margin:0;text-align:center;'>" . $this->nacex->l('No results') . "</div>
                </div>";
            }
        }

        $this->context->smarty->assign('content', $this->_html);
    }
}
