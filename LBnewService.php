<?php
require_once(dirname(__FILE__) . '/nacexDTO.php');
require_once(dirname(__FILE__) . '/nacexDAO.php');
// En versiones 1.7.7.X no puede estar al principio cuando se invoca desde una clase porque peta
//include_once __DIR__ . '/../../config/config.inc.php';
include_once dirname(__FILE__) . '/../../config/config.inc.php';

class LBnewService
{
    protected $file;
    protected $nacexDTO;

    public function __construct()
    {
        $this->file = nacexDTO::_path_new_services . nacexDTO::_new_services_filename;
        $this->nacexDTO = new nacexDTO();
    }

    // Retornamos todos los transportistas iniciales
    public function getAllServices()
    {
        $codesStd = $this->nacexDTO->getServiciosNacex();
        $codesShp = $this->nacexDTO->getServiciosNacexShop();
        $codesInt = $this->nacexDTO->getServiciosNacexInt();

        return $codesStd + $codesShp + $codesInt;
    }

    /** Operaciones con fichero CSV **/
    public function manageCSV()
    {
        // Loop through the array containing our CSV data.
        $rowfile = '';

        // Creamos esta variable para quitarle el action
        $rows = $_POST;
        unset($rows['action']);

        foreach ($rows as $row) {
            $rowfile .= $row . ';';
        }
        // Cambiamos el último ; por un \n para comenzar en la siguiente línea
        $rowfile = substr_replace($rowfile, "\n", -1);

        // Guardamos/creamos el archivo
        // Con el FILE_APPEND lo que hago es sobreescribir el archivo si ya existe
        file_put_contents($this->file, $rowfile, FILE_APPEND);

        // Instalamos transportistas en BBDD
        $this->installNewService(substr_replace($rowfile, "", -1));
    }

    /** Replicamos las operaciones anteriores en BBDD **/
    public function installNewService($servicio)
    {
        nacexutils::writeNacexLog("----");
        nacexutils::writeNacexLog("INI installNewService");

        $tracking_url = nacexDTO::$url_seguimiento . '/seguimientoFormularioExterno.do?intcli=@';
        $transportista = explode(';', $servicio);
        $ncx = $configCarrierValue = '';

        if (trim($transportista[2]) === 'Std') {
            $ncx = 'nacex';
            $configCarrierValue = 'NACEX_ID_TRANSPORTISTAS_F';
        } else {
            $ncx = 'nacexshop';
            $configCarrierValue = 'NACEX_ID_TRANSPORTISTAS_NXSHOP_F';
        }

        // Compruebo que el servicio tenga un icono; si no, recuperamos el genérico del servicio
        $nombre_fichero = _PS_MODULE_DIR_ . 'nacex/images/servicios/' . $ncx . '_servicio_' . $transportista[0] . '.jpg';
        if (!file_exists($nombre_fichero)) $nombreLogo = $ncx . '.jpg';
        else $nombreLogo = $ncx . '_servicio_' . $transportista[0] . '.jpg';

        // Miramos la versión que es el PS y adecuamos el shipping_external a ello
        if (version_compare(_PS_VERSION_, '1.7.8.2', '>=')) $shipping_external = 1;
        else $shipping_external = 0;

        // Damos por hecho que no existe el transportista y que el código es único (controlamos la entrada
        $carrierNacex = array(
            'name' => $transportista[1],
            'id_tax_rules_group' => 0,
            'active' => false,  // Inicializamos que no se vean
            'deleted' => 1,     // y se activen cuando las seleccione el cliente
            'shipping_handling' => false,
            'range_behavior' => 0,
            'delay' => array(
                Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => $transportista[1]
            ),
            'id_zone' => 1,
            //'is_module' => true,
            'shipping_external' => $shipping_external,
            'external_module_name' => nacexutils::_moduleName,
            'ncx' => $ncx,
            'need_range' => true,
            'tip_serv' => $transportista[0],
            'url' => $tracking_url
        );

        $ids_instalados = nacexDAO::instalarTransportista($carrierNacex, $nombreLogo);
        nacexutils::writeNacexLog("installNewService :: instalado newService " . $transportista[1]);
        Configuration::updateValue($configCarrierValue, $ids_instalados);

        nacexutils::writeNacexLog("FIN installNewService");
        nacexutils::writeNacexLog("----");
    }

    public function removeServicesCSV($toRemoveServices)
    {
        // De file a array: cada fila del archivo se almacena en una posición del array
        $file_out = file($this->file);
        $servicio = '';

        foreach ($toRemoveServices as $toRemove) {
            foreach ($file_out as $key => $value) {
                if (false !== stripos($value, $toRemove . ';')) {
                    //Delete the recorded line
                    $servicio = $value;
                    unset($file_out[$key]);
                    break;
                }
            }
        }

        //Recorded in a file
        file_put_contents($this->file, implode("", $file_out));

        // Actualizamos en BBDD
        $this->removeNewService($servicio);
    }

    private function removeNewService($servicio)
    {
        nacexutils::writeNacexLog("----");
        nacexutils::writeNacexLog("INI removeNewService");

        $transportista = explode(';', $servicio);
        $ncx = trim($transportista[2]) === 'Std' ? 'nacex' : 'nacexshop';

        // Eliminamos las referencias del servicio
        nacexutils::writeNacexLog("removeNewService:: Eliminamos las referencias del servicio");
        $query = "SELECT id_carrier from " . _DB_PREFIX_ . "carrier WHERE ncx = '" . $ncx . "' AND tip_serv = '" . $transportista[0] . "'";
        $result = Db::getInstance()->executeS($query);
        foreach ($result as $value) {
            $query = "DELETE from " . _DB_PREFIX_ . "carrier WHERE id_carrier = " . $value['id_carrier'];
            if (!Db::getInstance()->execute($query)) {
                nacexutils::writeNacexLog("removeNewService :: Error al borrar el servicio.");
            }
        }
        nacexutils::writeNacexLog("removeNewService:: Servicio $transportista[0] eliminado");

        nacexutils::writeNacexLog("FIN removeNewService");
        nacexutils::writeNacexLog("----");
    }

    public function editServiceCSV()
    {
        // De file a array: cada fila del archivo se almacena en una posición del array
        $file_out = file($this->file);

        // Guardamos los datos del servicio modificado
        $servicio = '';

        // Tengo que buscar el código y reemplazar el nombre por el nuevo
        foreach ($file_out as $key => $value) {
            if (false !== stripos($value, $_POST['code'] . ';')) {
                // Creo la nueva línea
                $servicio = $_POST['code'] . ';' . $_POST['editName'] . ';' . $_POST['newServiceType'];
                $file_out[$key] = $servicio . "\n";
                break;
            }
        }

        //Recorded in a file
        file_put_contents($this->file, implode("", $file_out));

        // Actualizamos en BBDD
        $this->updateNewService($servicio);
    }

    private function updateNewService($servicio)
    {
        nacexutils::writeNacexLog("----");
        nacexutils::writeNacexLog("INI updateNewService");

        $transportista = explode(';', $servicio);
        $ncx = trim($transportista[2]) === 'Std' ? 'nacex' : 'nacexshop';

        // Actualizamos los datos por el nombre y el tipo de servicio
        nacexutils::writeNacexLog("updateNewService:: Actualizamos los datos por el nombre y el tipo de servicio");
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'carrier 
                SET name="' . $transportista[1] . '"
                WHERE ncx = "' . $ncx . '" 
                AND tip_serv = "' . $transportista[0] . '"');

        nacexutils::writeNacexLog("updateNewService:: Base de datos actualizada");

        nacexutils::writeNacexLog("FIN updateNewService");
        nacexutils::writeNacexLog("----");
    }
}