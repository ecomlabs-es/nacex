<?php

include_once dirname(__FILE__) . '/nacexDAO.php';
include_once dirname(__FILE__) . '/nacex.php';

include_once dirname(__FILE__) . '/../../config/config.inc.php';

session_start();

if (isset($_POST['editarCarrier']) && $_POST['editarCarrier'] == 1) {

    $idCarrier = (int) Tools::getValue('idCarrier');
    $is_module = (int) Tools::getValue('is_module');
    $shipping_external = (int) Tools::getValue('shipping_external');
    $external_module_name = pSQL(Tools::getValue('external_module_name'));
    $ncx = pSQL(Tools::getValue('ncx'));
    $tip_serv = pSQL(Tools::getValue('tip_serv'));

    if ($idCarrier <= 0) {
        echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger conf" style="width:auto">ID de carrier no valido</div></div>';
    } else {
        $queryExec = Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . 'carrier
             SET is_module = ' . $is_module . ',
                 shipping_external = ' . $shipping_external . ',
                 external_module_name = \'' . $external_module_name . '\',
                 ncx = \'' . $ncx . '\',
                 tip_serv = \'' . $tip_serv . '\'
             WHERE id_carrier = ' . $idCarrier
        );

        if ($queryExec) {
            echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-success conf" style="width:auto">Fila editada correctamente</div></div>';
        } else {
            echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger conf" style="width:auto">Error al actualizar</div></div>';
        }
    }
} else {
    $campos = ['id_carrier', 'name', 'active', 'is_module', 'shipping_external', 'external_module_name', 'ncx', 'tip_serv'];
    $nacex = new nacex();

    $queryS = Db::getInstance()->executeS(
        'SELECT ' . implode(',', $campos) . ' FROM ' . _DB_PREFIX_ . 'carrier WHERE ncx IS NOT NULL'
    );

    $html = getTableFromQuery($queryS, $campos);
    echo $html;
}

function getTableFromQuery($queryS, $campos)
{
    if (!empty($queryS)) {

        $html = '<table id="tableCarriers" class="grid-table js-grid-table table" style="padding: 2px; text-align: center;"><thead class="thead-default"><tr class="column-headers ">';

        foreach ($campos as $campo) {
            $campoEscaped = htmlspecialchars($campo, ENT_QUOTES, 'UTF-8');
            $html .= '<th scope="col"><div class="ps-sortable-column" data-sort-col-name="' . $campoEscaped . '" data-sort-prefix="carrier">
              <span role="columnheader">' . $campoEscaped . '</span>
              <span role="button" class="ps-sort" aria-label="Ordenar por"></span>
            </div></th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($queryS as $q) {
            $html .= '<tr>';
            foreach ($campos as $campo) {
                $valor = isset($q[$campo]) ? htmlspecialchars($q[$campo], ENT_QUOTES, 'UTF-8') : '';
                $html .= '<td>' . $valor . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $html .= "<div id='tableEditCarrier'>
            <span>
                <label>Carrier ID</label>
                <input type='number' id='edit_carrierId' name='edit_carrierId' />

                <label>is_module</label>
                <input type='number' id='edit_isModule' name='edit_isModule' />
            </span>";
        $html .= "<span>
                <label>shipping_external</label>
                <input type='number' id='edit_shihppingExternal' name='edit_shihppingExternal' />

                <label>external_module_name</label>
                <input type='text' id='edit_externalModuleName' name='edit_externalModuleName' />
            </span>";
        $html .= "<span>
                <label>ncx</label>
                <input type='text' id='edit_ncx' name='edit_ncx' />

                <label>tip_serv</label>
                <input type='text' id='edit_tip_serv' name='edit_tip_serv' />
            </span>";
        $html .= "<br>
                <input type='button' class='ncx_button green' value='Editar fila' onclick='javascript: editarFila();' />
            </div>";

    } else {
        $html = '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger conf" style="width:auto">No hay consultas satisfactorias</div></div>';
    }

    return $html;
}
