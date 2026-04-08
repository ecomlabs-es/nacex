<?php
include_once(dirname(__FILE__) . '/nacex.php');

class VInacexlogs
{
    static function getNacex()
    {
        return new nacex();
    }

    static function header()
    {
        $nacex = self::getNacex();
        $mensaje = $nacex->l("Are you sure you want to delete all log files?");
        return '<center>
                    <a target="_blank" title="' . $nacex->l("Go to Nacex web") . '" href="https://www.nacex.es">
                        <img src="' . _MODULE_DIR_ . 'nacex/images/logos/NACEX_logo.svg" style="width: 200px">
                    </a>    
                </center>
                <div align="right">
                    <input type="button" class="ncx_button" id="btnrefrescarvolver" value ="' . $nacex->l("Refresh/Back") . '"" onclick="nacexlogs.get(\'init\',Base_uri);"</input>
                    <input type="button" class="ncx_button" id="btnborrartodo" value ="' . $nacex->l("Delete logs") . '" onclick="nacexlogs.get(\'delete_all\',Base_uri,\'' . $mensaje . '\');"</input>
                </div>
                <div align="right">' . $nacex->l("Ctrl + F to search") . '</div>
                <hr style="border-bottom: 2px solid #ff5000; border-top: none;">';
    }

    static function content_directory($_file, $path, $index)
    {
        $nacex = self::getNacex();

        /** Modificamos la función para mostrar una tabla con los logs que hay **/
        $html = '';
        //$mensaje = sprintf($nacex->l('Are you sure you want to delete %1 file?$d'), $_file);
        $mensaje = $nacex->l('Are you sure you want to delete file') . ' ' . $_file . '?';

        $odd = $index % 2 != 0 ? 'class="odd"' : '';

        $html .= "<tr " . $odd . ">
                    <td><b>" . $_file . "</b></td>
                    <td>" . self::formatSizeUnits(filesize($path . DIRECTORY_SEPARATOR . $_file)) . "</td>
                    <td>
                        <a href='#' name='view' id=" . $_file . " title='" . $nacex->l("Open file") . "' value='" . $nacex->l("Open file") . "' onclick='nacexlogs.get(\"read\",Base_uri,\"\",this.id);'>
                            <i class='material-icons'>remove_red_eye</i>
                        </a>
                        <a href='#' name='delete' id=" . $_file . " title='" . $nacex->l("Delete file") . "' value='" . $nacex->l("Delete file") . "' onclick='nacexlogs.get(\"delete\",Base_uri,\"$mensaje\",this.id);'>
                            <i class='material-icons'>delete</i>
                        </a>
                    </td>
                </tr>";

        return $html;
    }

    /** Creamos una función para que devuelva el tamaño del archivo en unidades más legibles **/
    private static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    static function content_directory_no_files()
    {
        $nacex = self::getNacex();
        return "<center><h1>" . $nacex->l("Do not exist log files") . "</h1></center>";
    }
    static function response_delete ($_message){
        return "<center><h1>$_message</h1></center>";
    }
    static function response_open ($_message){
        return "<center><h1>$_message</h1></center>";
    }
    static function content_file_title ($_file)
    {
        $nacex = self::getNacex();
        return "<center><h1>" . $nacex->l("Content of") . " '" . $_file . "'</h1></center>
                <hr>";
    }
    static function content_file ($_line){
        return "<p>$_line</p>";
    }
}