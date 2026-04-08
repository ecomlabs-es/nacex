<?php

require_once(dirname(__FILE__) . '/nacexDTO.php');

class CheckVersion
{
    //    public function check_new_version() {
    //        $module = Module::getInstanceByName('nacex');
    //        $module_version = $module->version;
    //        //$module_version = '1.4.7'; // Pruebas para que aparezca el mensaje de actualización
    //        $uploadedVersion = '';
    //        $html = '';
    //        $beta = true;
    //
    //        $ftp_files = $this->FTPCurlConnection('list');
    //
    //        // Buscaremos si el archivo está en la carpeta de uploads
    //        $file = glob(_PS_MODULE_DIR_ . 'nacex/files/nacex*.zip'); // Devuelve array con las coincidencias
    //        if ($file)
    //            unlink($file[0]); // Como sólo habrá 1 archivo zip, elimino ese
    //
    //        if($ftp_files) {
    //            // Buscamos la última versión por el nombre del archivo
    //            foreach ($ftp_files as $el) {
    //                // Busco la carpeta y la version del módulo
    //                if(strpos($el, 'nacex') !== false) {
    //                    $file = trim(substr($el, strpos($el, 'nacex')));
    //                    if($beta)
    //                        $uploadedVersion = trim(substr(explode('BETA', $file)[1],0,-4));
    //                    else
    //                        $uploadedVersion = trim(substr(explode('-', $file)[1],0,-4));
    //                }
    //            }
    //        } else
    //            return false;
    //
    //        // Si hay espacios en blanco es que es una versión BETA
    //        if(strpos($uploadedVersion, ' ') !== false) {
    //            $beta = true;
    //            $uploadedVersion = explode(' ',$uploadedVersion)[0];
    //        }
    //
    //        // Versión actual MENOR o INFERIOR que la subida en el FTP (-1)
    //        if(version_compare($module_version,$uploadedVersion) == -1) {
    //            $html .= '<div class="new-version">';
    //            $html .= 'Nueva versión disponible: <strong>' . $uploadedVersion . '</strong>';
    //            $html .= $beta ? ' BETA ' : ' ';
    //            $html .= '<a href="javascript:void(0)" id="download-ncx-new-version">Descargar ahora</a>';
    //            $html .= '</div>';
    //            $html .= "<div id='loader-download-ncx-new-version' style='display: none;'>
    //                <img src='" . _MODULE_DIR_ . "nacex/images/loading.gif'>
    //            </div>";
    //
    //            $html .= '<div id="accordion" class="changelog" onclick="javascript:toggleAccordion();">
    //                <span id="seeChangelog">Ver lista de cambios <i class="material-icons sub-tabs-arrow" style="vertical-align: middle;">keyboard_arrow_down</i></span>
    //                <div id="contentChangelog">';
    //            $html .= $this->getChangeLog($module_version);
    //            $html .= '</div></div>';
    //            $html .= '<script>
    //                function toggleAccordion() {
    //                  jQuery(\'#contentChangelog\').slideToggle();
    //                  var arrow = jQuery(\'#seeChangelog i.material-icons.sub-tabs-arrow\').text();
    //                  // Tengo que cambiar el contenido de la <i> para que aparezca la flecha hacia arriba
    //                  if(arrow.indexOf("down") != -1) jQuery(\'#seeChangelog i.material-icons.sub-tabs-arrow\').text(arrow.replace("down","up"));
    //                  else jQuery(\'#seeChangelog i.material-icons.sub-tabs-arrow\').text(arrow.replace("up","down"));
    //                }
    //            </script>';
    //
    //            $html .= $this->get_nacex_file('download-ncx-new-version',$uploadedVersion);
    //        }
    //
    //        return $module_version . $html;
    //    }
    //
    //    public function get_nacex_file($id, $uploadedVersion)
    //    {
    //        $html = "<script>
    //            jQuery( document ).ready(function() {
    //
    //                jQuery('#$id').on('click', function() {
    //
    //                    jQuery.ajax({
    //                        type: 'POST',
    //                        url: '" . nacexDTO::getPath() . "NacexFeedsController.php',
    //                        data: {action: 'download_nacex_new_version' , version: '". $uploadedVersion ."'},
    //                        beforeSend: function(){
    //                            // Show image container
    //                            jQuery('#loader-$id').show();
    //                        },
    //                        success: function(data) {
    //                            if(data.url) {
    //                                window.open(data.url);
    //                                // Añadimos el timeout para que se elimine el archivo descargado recientemente de la carpeta del servidor
    //                                setTimeout(function(){window.location.reload();},1000);
    //                            }
    //                        },
    //                        error: function(error) {
    //                            console.log('data response error:: ' + JSON.stringify(error));
    //                            alert('There has been an error on downloading the file');
    //                        },
    //                        complete:function(data){
    //                            // Hide image container
    //                            jQuery('#loader-$id').hide();
    //                        }
    //                    });
    //                });
    //            })";
    //        $html .= '</script>';
    //
    //        return $html;
    //    }
    //
    //    public function get_new_version($version) {
    //        try{
    //            if($lista[] = $this->readFTPFolder($version)) {
    //
    //                $fileName = $lista[0];
    //                $localpath = _PS_MODULE_DIR_ . 'nacex/files/'.$fileName;
    //
    //                if( in_array( $fileName, $lista ) ) {
    //                    nacexutils::writeNacexLog('Función get_new_version:: trayendo archivo de FTP a la carpeta del servidor ' . $localpath);
    //                    if($this->FTPCurlConnection('file',$localpath,$fileName)) return $fileName;
    //                    else return false;
    //                } else {
    //                    echo 'The file does not exist.';
    //                    nacexutils::writeNacexLog('El archivo no existe');
    //                    return false;
    //                }
    //            }
    //        } catch(\Exception $e) {
    //            nacexutils::writeNacexLog("Error en la función getNewVersion::" . $e->getMessage());
    //            $this->mostrarErrores($e->getMessage());
    //        }
    //    }
    //
    //    private function readFTPFolder($searchFile) {
    //        $i = 0;
    //        $return = false;
    //
    //        foreach($this->FTPCurlConnection('list') as $row) {
    //            if($i < 2) {
    //                $i++;
    //                continue;
    //            } else {
    //                $l = explode(' ', $row);
    //                if(strpos(trim($l[sizeof($l)-1]), $searchFile) !== false) {
    //                    $return = trim($l[sizeof($l)-1]);
    //                    break;
    //                }
    //                $i++;
    //            }
    //        };
    //
    //        return $return;
    //    }
    //
    //    public function getChangeLog($currentVersion) {
    //        $fileArray = array();
    //        $html = '';
    //        try{
    //            $fileName = 'history.txt';
    //
    //            if($file = $this->readFTPFolder($fileName)) {
    //                $localpath = _PS_MODULE_DIR_ . 'nacex/files/'.$fileName;
    //
    //                if($this->FTPCurlConnection('file',$localpath,$fileName)) {
    //                    $fileArray = file($localpath);  // Transfiero contenido archivo a Array
    //                    unlink($localpath);
    //                }
    //            }
    //
    //            // Para comprobar el archivo local de una versión más actualizada y que está correcto
    //            $foundPrevious = preg_grep("/$currentVersion \^---/", $fileArray);
    //
    //            for ($i = 0; $i < key($foundPrevious); $i++) {  // Printamos desde el principio hasta la versión actual
    //                if(strpos($fileArray[$i],'----') !== false) {
    //                    $vers = trim(preg_replace("/[^a-zA-Z0-9\.]/", "", $fileArray[$i]));
    //                    $html .= '<p><strong>' . $vers . '</strong></p>';
    //                } else
    //                    $html .= utf8_encode($fileArray[$i]) . '<br/>';
    //            }
    //            return $html;
    //
    //        } catch(\Exception $e) {
    //            nacexutils::writeNacexLog("Error en la función getChangeLog::" . $e->getMessage());
    //            $this->mostrarErrores($e->getMessage());
    //        }
    //    }
    //
    //    public function FTPCurlConnection($tipo, $filepath = null, $filename = null) {
    //        $datos_ftp = nacexDTO::URIS['ftp'];
    //
    //        // Creamos la ruta completa a los directorios de FTP
    //        $ftp_path = [
    //            'B' => 'BETA/Para PRESTASHOP 1.7/',
    //            'P' => 'PRODUCTION/Para PRESTASHOP 1.7/'
    //        ];
    //
    //        if ($datos_ftp) {
    //            // Separo URL y credenciales de acceso
    //            $acceso = explode('@', $datos_ftp);
    //            $cred = preg_replace('#^ftp?://#', '', $acceso[0]);
    //            $url_ftp = $acceso[1];
    //
    //            $ch = curl_init();
    //
    //            if($tipo == 'list') {
    //                curl_setopt($ch, CURLOPT_URL, "ftp://$url_ftp".nacexDTO::URIS['rutaFtp'].$ftp_path[Configuration::get('NACEX_FOLDER_FTP')] );
    //                //curl_setopt($ch, CURLOPT_PORT, FTP_CONNECTION_PORT);
    //                curl_setopt($ch, CURLOPT_USERPWD, $cred);
    //                //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    //                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //                curl_setopt($ch, CURLOPT_DIRLISTONLY, TRUE);
    //                $files_list = curl_exec($ch);
    //                curl_close($ch);
    //
    //                // The list of all files names on folder
    //                return explode("\n", $files_list);
    //
    //            } elseif($tipo == 'file' && !is_null($filepath) && !is_null($filename)) {
    //                $fp = fopen($filepath, 'w+');
    //                curl_setopt($ch, CURLOPT_URL, "ftp://$url_ftp".nacexDTO::URIS['rutaFtp'].$ftp_path[Configuration::get('NACEX_FOLDER_FTP')].$filename);
    //                //curl_setopt($ch, CURLOPT_PORT, FTP_CONNECTION_PORT);
    //                curl_setopt($ch, CURLOPT_USERPWD, $cred);
    //                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //                curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    //                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //                curl_setopt($ch, CURLOPT_FILE, $fp);
    //                curl_exec($ch);
    //
    //                curl_close($ch);
    //                fclose($fp);
    //
    //                return (filesize($filepath) > 0)? true : false;
    //            }
    //        } else return false;
    //    }

    /* Deberemos trasladar estas funciones a el NacexDTO, por ejemplo, donde todos puedan llamarlas al importar el archivo */
    public static function mostrarErrores($errors = [])
    {
        $error_message = '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger error" style="width:auto">';

        foreach ($errors as $error) {
            $error_message .= $error . '<br/>';
        }

        $error_message .= '</div></div>';
        return $error_message;
    }

    public static function mostrarSuccess($success = []) {
        $success_message = '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-success conf" style="width:auto">';

        foreach ($success as $aux) {
            $success_message .= $aux . '<br/>';
        }

        $success_message .= '</div></div>';
        return $success_message;
    }
}
