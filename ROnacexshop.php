<?php

class nacexshop
{

    protected $FILE_NAME = '';
    protected $pluginPath = '';

    public function __construct()
    {
        $this->pluginPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'files/';
    }

    public function importFromCsvFile($shop_codigo, $isShop)
    {

        // Actualizamos el fihcero si es necesario
        $this->checkFileDate();

        // Si no existe la carpeta 'files' la creo
        $file = $this->pluginPath . $this->FILE_NAME;

        if ($matches = $this->searchFile($shop_codigo, $file, $isShop)) {
            return $matches[0];
        } else {
            nacexutils::writeNacexLog("No se ha encontrado la tienda con código $shop_codigo en el archivo $file");
            return false;
        }
    }

    private static $fileDateChecked = false;

    public function checkFileDate()
    {
        // Solo comprobar una vez por request
        if (self::$fileDateChecked) {
            // Ya comprobado, solo necesitamos setear FILE_NAME
            if ($url = glob($this->pluginPath . 'droppoint*.csv')) {
                $this->FILE_NAME = substr(strrchr($url[0], '/'), 1);
            }
            return;
        }
        self::$fileDateChecked = true;

        // Miro el nombre del archivo que tengo guardado en la ruta
        if ($url = glob($this->pluginPath . 'droppoint*.csv')) {
            // Nos quedamos con el nombre del archivo sin la / inicial ni la extensión
            $fileName = substr(strrchr($url[0], '/'), 1, -4);

            $hoy = date('Ymd');

            // Cogemos la fecha del archivo que nos quedemos descargar
            $fileDate = explode('_', $fileName);
            $fileDate = $fileDate[1];

            $this->FILE_NAME = $fileName . '.csv';

            // Miramos los meses para descargar el archivo (se actualiza 1 vez al mes)
            /*$mesFichero = date('m', strtotime($fileDate));
            $mesHoy = date('m');*/

            // Actualizamos el archivo cada dia
            $tiempoMaximo = 3600 * 24; // 1 dia
            $limit = time() - $tiempoMaximo;

            // La fecha de hoy es más nueva que la del fichero
            //if (strtotime($hoy) > strtotime($fileDate) && $mesFichero != $mesHoy) {
            if (strtotime($hoy) > strtotime($fileDate) && strtotime($fileDate) < $limit) {
                // Descargamos el nuevo fichero
                if ($newFileName = $this->nacex_fichero_nacexshop()) {
                    // Borrar archivo antiguo
                    unlink($this->pluginPath . $fileName . '.csv') or die();
                    $this->FILE_NAME = $newFileName;
                }
            }
        } else { // Si no hay ningún archivo descargado
            // Descargamos el nuevo fichero
            if ($newFileName = $this->nacex_fichero_nacexshop()) {
                // Borrar archivo antiguo
                $this->FILE_NAME = $newFileName;
            } else {
                $this->FILE_NAME = '';
            }
        }
    }

    private function nacex_fichero_nacexshop()
    {

        $url = $this->get_Fichero_Nacex_Shop('ALL');

        // Cambiar este método por el de CURL
        //$source = file_get_contents($url);

        // Accedemos a través de Curl para poder controlar los posibles errores 500 o de conexión que puedan haber
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        //curl_setopt($ch, CURLOPT_HEADER, 1);

        $report = curl_exec($ch);

        curl_close($ch);

        // Manejamos errores
        $res = preg_split("/\n+/", $report);
        if (preg_match('(500|error)', strtolower($res[0]))) {  // Si el elemento contiene
            return false;
        }

        if ($report) {

            /*$headers = get_headers($url, true);
            $headers = array_combine(array_map("strtolower", array_keys($headers)), $headers);

            $fileName = isset($headers['content-disposition']) ? strstr($headers['content-disposition'], "=") : null;
            $fileName = trim($fileName, "=\"'");*/

            $hoy = date('Ymd');
            $fileName = "droppoints_$hoy.csv";

            file_put_contents($this->pluginPath . $fileName, $report);
            //file_put_contents($my_save_dir . $fileName, $report);

            return $fileName;
        } else {
            nacexutils::writeNacexLog('No se ha podido descargar el archivo de Información de NacexShop');
        }

        //file_put_contents($path . '/droppoint_' . $fecha . '.csv', $source);
    }

    private function get_Fichero_Nacex_Shop($type)
    {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'nacexDTO.php');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'nacexWS.php');
        $url = nacexDTO::$url_ws;
        $get = $url . '/ws?method=getFicheroNacexShop&user=' . Configuration::get('NACEX_WSUSERNAME') . '&pass=' . Configuration::get('NACEX_WSPASSWORD') . '&data=' . $type . '|' . nacexWS::getSystemInfo();
        return $get;
    }

    private static $fileContentsCache = array();

    private function searchFile($shop_codigo, $file, $isShop)
    {
        // Cache del contenido del CSV en memoria para evitar releer en cada busqueda
        if (!isset(self::$fileContentsCache[$file])) {
            $raw = file_get_contents($file);
            self::$fileContentsCache[$file] = $raw !== false ? nacexutils::toUtf8(trim($raw)) : '';
        }
        $contents = self::$fileContentsCache[$file];

        // Si es tienda coge el código, si no, coge el alias
        if ($isShop) {
            // escape special characters in the query
            $pattern = preg_quote($shop_codigo . '|', '/');
            // finalise the regular expression, matching the whole line (starting with)
            $pattern = "/^$pattern.*\$/m";
        } else {
            // EL -00 es de las agencias
            $pattern = preg_quote($shop_codigo . '-00', '/');
            // finalise the regular expression, matching the whole line (contains)
            $pattern = "/^.*$pattern.*\$/m";
        }

        // search, and store all matching occurences in $matches
        preg_match_all($pattern, $contents, $matches);

        return $matches;
    }

    public function getFileData($shop_codigo, $isShop, $isConnection = true, $cpSearch = false, $cpCenter = false)
    {
        // Número de puntos shop que se mostrarán cuando haya un error de WS
        $numResultados = 15;
        $file = glob($this->pluginPath . 'droppoint*.csv');

        // La conexión WS funciona correctamente
        if ($isConnection) {
            if ($matches = $this->searchFile($shop_codigo, $file[0], $isShop)) {
                return $matches[0];
            } else {
                nacexutils::writeNacexLog("No se ha encontrado la tienda con código $shop_codigo en el archivo");
                return false;
            }
        } else { // No hay conexión WS
            if (!$cpSearch) {
                if ($matches = $this->searchCP($shop_codigo, $file[0], 3)) return $matches[0];
                else {
                    nacexutils::writeNacexLog("No se ha encontrado el código postal $shop_codigo en el archivo");
                    return false;
                }
            } else {    // Buscamos por CP
                $digits = [5, 4, 3, 2];  // Num. de digitos del CP por los que hacer la búsqueda de coincidencias
                $contador = 0;
                $arrayShops = array();
                foreach ($digits as $d) {
                    $matches = $this->searchCP($shop_codigo, $file[0], $d);
                    $resultados[] = $matches[0];
                    //$contador += sizeof($resultados[sizeof($resultados) - 1]);
                    if (!empty($arrayShops)) {
                        $arrayShops = array_merge($arrayShops, $resultados[sizeof($resultados) - 1]);
                        $arrayShops = array_unique($arrayShops);
                    } else
                        $arrayShops = $resultados[sizeof($resultados) - 1];

                    $contador += sizeof($arrayShops);
                    if ($numResultados < $contador) {
                        $arrayShops = array_slice($arrayShops, 0, $numResultados);
                        break;
                    }

                    // Para buscar el centro de un CP para selección de punto de usuario
                    if (!empty($arrayShops) && $cpCenter) break;
                }
                return $arrayShops;
            }
        }
    }

    public function getMapsCoordinates($address, &$lat, &$lon)
    {
        //$address = explode('|', $add); // Google HQ
        $prepAddr = str_replace('C/', '', $address);
        $prepAddr = str_replace('  ', ' ', $prepAddr);
        $prepAddr = str_replace(' ', '+', $prepAddr);
        $mapUrl = 'https://maps.google.com/maps/api/geocode/json?key=' . Configuration::get('NACEX_GOOGLE_API') . '&address=' . $prepAddr . '&sensor=false';
        //$mapUrl = 'https://geocode.xyz/'.$prepAddr;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $mapUrl);
        $geocode = curl_exec($ch);
        curl_close($ch);

        $output = json_decode($geocode);
        if (isset($output->results[0])) {
            $lat = $output->results[0]->geometry->location->lat;
            $lon = $output->results[0]->geometry->location->lng;
            return true;
        } else return false;
    }

    private function searchCP($cp, $file, $digits)
    {

        // get the file contents, assuming the file to be readable (and exist)
        $contents = trim(file_get_contents($file));
        // Codificamos los caracteres correctamente
        $contents = nacexutils::toUtf8($contents);

        // Aislamos el código de la provincia
        if (strlen($cp) == 5 && $digits != 5) $pc = substr($cp, 0, $digits);

        // escape special characters in the query
        if (isset($pc)) $pattern = '\|' . $pc . '(\d{' . (5 - $digits) . '})\|';
        else $pattern = '\|' . $cp . '\|';
        // finalise the regular expression, matching the whole line (starting with)
        $pattern = "/^.*$pattern.*\$/m";

        preg_match_all($pattern, $contents, $matches);

        return $matches;
    }

    public function getAgenciasTratadas($latLon)
    {
        $json = array();

        // Cogemos las agencias con el CP de la provincia
        $listadoAgencias = $this->getFileData($latLon[0], true, false, true);

        foreach ($listadoAgencias as $key => $value) {

            if (isset($agenciasCPFound))
                $values = explode('|', $agenciasCPFound[$key]);
            else
                $values = explode('|', $value);

            // Construimos el array para poder crear el JSON
            $json[] = array(
                $values[0],
                $values[3],
                $values[5],
                $values[4],
                '',
                $values[9],
                $values[10],
                $values[11],
                $values[12],
                $values[13],
                $values[14],
                $values[15],
                $values[1],
                trim($values[7]),
                trim($values[8]),
                $values[2],
                $values[16]
            );
        }

        //return json_encode($json, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public function add_nacex_shop_name(&$_agencias)
    {
        //$this->get_actual_file($fichero, $path);
        for ($i = 0; $i < sizeof($_agencias); ++$i) {
            if ($i != 0) {
                $ag = explode("~", $_agencias[$i]);
                //foreach (array_map('str_getcsv', file($path . DIRECTORY_SEPARATOR . $fichero), ["|"]) as $line) {
                // Para WS sin conexión
                //foreach (array_map('str_getcsv', file(dirname(__FILE__) . '/files' . '/droppoint_' . date("Ymd") . '.csv'), ["|"]) as $line) {

                $fichero = glob(_PS_MODULE_DIR_ . 'nacex/files/droppoints_*.csv'); // Devuelve array con las coincidencias
                if (empty($fichero)) continue;
                $lines = file($fichero[0], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $csvLine) {
                    $line2 = explode("|", $csvLine);
                    if (isset($line2[1]) && $line2[1] == $ag[12]) {
                        //$_agencias[$i] = $_agencias[$i] . "~" . $line2[2];
                        $_agencias[$i] = $_agencias[$i] . "~" . nacexutils::toUtf8($line2[2]);
                        break;
                    }
                }
            }
        }
    }
    /*private function get_actual_file (&$fichero,&$path){
        $path = dirname(__FILE__) . '/files';
        $content = scandir($path);
        foreach ($content as $ficheros){
            if (substr($ficheros,0,9) == 'droppoint'){
                $fichero = $ficheros;
            }
        }
    }*/

    /** Función que devuelve las agencias del CP indicado o seleccionado por el cliente **/
    public function searchAgenciaShopCustomerSelected($shop_codigo, $agencia)
    {

        $file = glob(_PS_MODULE_DIR_ . '/nacex/files/' . 'drop*.csv');

        $tienda = ($agencia) ? $this->searchFile($shop_codigo, $file[0], false)[0] : $this->searchFile($shop_codigo, $file[0], true)[0];

        $contents = file_get_contents($file[0]);
        // Codificamos los caracteres correctamente
        $contents = nacexutils::toUtf8($contents);

        // Cogemos el alias de la tienda ( codigo|ALIAS-xx|... )
        $alias = explode('-', explode('|', $tienda[0])[1])[0];

        $pattern = preg_quote('|' . $alias, '|');
        // finalise the regular expression, matching the whole line (contains)
        $pattern = "/^.*$pattern-[0-9]{2}\|.*\$/m";

        // search, and store all matching occurences in $matches
        preg_match_all($pattern, $contents, $matches);

        return $matches;
    }

    /** Función modificada para WS sin conexión */
    private function get_map_csv_rows_number(&$csvAsArray, &$array_num, $file)
    {
        $csvAsArray = array_map(function ($v) {
            return str_getcsv($v, "|");
        }, file($file));
        $array_num = count($csvAsArray);
    }

    /*private function get_map_csv_rows_number (&$csvAsArray,&$array_num,$fichero,$path){
        $file = $path."/". $fichero;
        $csvAsArray = array_map(function ($v){return str_getcsv($v,"|");}, file($file));
        $array_num = count($csvAsArray);
    }*/

    //public function nacex_shop_list($_agencias,$cp){
    public function nacex_shop_list($_agencias, $cp, $conn)
    {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VInacexshop.php');

        $nacex = new nacex();
        $_vista = new VInacexshop();
        $tienda = array();
        $_html = "";
//GET ACTUAL FILE
        //$this->get_actual_file($fichero, $path);
        $fichero = glob($this->pluginPath . 'droppoint*.csv')[0];
//GET MAP CSV & ROWS NUMBER
        //$this->get_map_csv_rows_number($csvAsArray, $array_num, $fichero, $this->pluginPath);
        $this->get_map_csv_rows_number($csvAsArray, $array_num, $fichero);
        $chivato = 0;
//GET PROVINCIA
        nacexutils::provincia($cp, $prov);
        $_html .= '<br>
                   <h1 style="color:orange;">' . $nacex->l('Nacexshop points') . '</h1>';
        $_html .= "<div style='font-size:10pt; color:#000000; font-family:Calibri'>";
        $_html .= "<table align='left'>
                            <tr>
                                <th width='10%'></th>
                                <th width='50%'>" . $nacex->l('Name') . "</th>
                                <th width='60%'>" . $nacex->l('Address') . "</th>
                                <th width='25%'>" . $nacex->l('Location') . "</th>
                                <th width='10%'>" . $nacex->l('Schedule') . "</th>
                            </tr>
                   </table>";
        $_html .= "</div>";
        if ($conn) {
            foreach ($_agencias as $_agencia) {
                if ($chivato != 0) {
                    $ag = explode("~", $_agencia);
                    for ($i = 0; $i < $array_num; ++$i) {
                        if ($csvAsArray[$i][1] == $ag[12]) {
                            $tienda = $csvAsArray[$i];
                            break;
                        }
                    }
                    //GET LIST OF NACEX SHOPS
                    //$_vista->get_list_of_nacex_shop($_html,$chivato,$ag,$tienda,$prov);
                    $_vista->get_list_of_nacex_shop($_html, $chivato, $ag, $prov, $tienda);
                }
                $chivato++;
            }
        } else {
            foreach ($_agencias as $_agencia) {
                $_vista->get_list_of_nacex_shop($_html, $chivato, $_agencia, $prov);
                $chivato++;
            }
        }
//SET FUNCTION
        VInacexshop::get_nacex_shop_list_footer_jq($_html);
        $_vista->get_list_of_nacex_shop_script($_html);
        return $_html;
    }

    //public function nacex_shop_map ($lat,$long,$tienda,$cp){
    public function nacex_shop_map($tienda, $cp, $conn)
    {
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VInacexshop.php');
        $_googlemap = new VInacexshop();
//GET HEADER
        //$_googlemap->get_nacex_shop_map_header($_html, $cp, $lat, $long);
        $_googlemap->get_nacex_shop_map_header($_html);
        $puntos = array();
        $chivatomarker = 0;

        if ($conn) {
            foreach ($tienda as $agencia) {
                if ($chivatomarker != 0) {
                    $punto = explode("~", $agencia);
                    // Recuperamos los datos del fichero que no nos devuelve WS
                    $csv_ag = explode("|", $this->importFromCsvFile($punto[0], true)[0]);
                    $punto[] = $csv_ag[2];
                    $punto[] = $csv_ag[16];

                    array_push($puntos, $punto);
                }
                $chivatomarker++;
            }

            /*foreach ($tienda as $punto) {
                $punto=explode("~",$punto);
                if ($punto[9]!="") {
//GET BODY
                    $_googlemap->get_nacex_shop_map_body($cp,$_html,$punto,$chivatomarker,sizeof($tienda));
                    $chivatomarker++;
                }
            }*/
        } else {
            foreach ($tienda as $punto) {
                if ($chivatomarker == 0) {
                    array_push($puntos, $punto);
                }
                $chivatomarker++;
            }
        }
//GET FOOTER
        $_googlemap->get_nacex_shop_map_body($cp, $_html, $puntos);
        //$_googlemap->get_nacex_shop_map_footer($_html); // Lo necesito para los infowindows
        return $_html;
    }

    public function getShopByCode($shop_codigo, $isShop){
        $file = glob(dirname(__FILE__) . '/files/droppoint*.csv')[0];

        // get the file contents, assuming the file to be readable (and exist)
        $contents = file_get_contents($file);
        // Codificamos los caracteres correctamente
        $contents = nacexutils::toUtf8($contents);

        // Si es tienda coge el código, si no, coge el alias
        if($isShop) {
            // escape special characters in the query
            $pattern = preg_quote($shop_codigo . '|', '/');
            // finalise the regular expression, matching the whole line (starting with)
            $pattern = "/^$pattern.*\$/m";
        } else {
            // EL -00 es de las agencias
            $pattern = preg_quote($shop_codigo . '-00', '/');
            // finalise the regular expression, matching the whole line (contains)
            $pattern = "/^.*$pattern.*\$/m";
        }

        // search, and store all matching occurences in $matches
        preg_match_all($pattern, $contents, $matches);

        return $matches;
    }


}