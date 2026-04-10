<?php

include_once(dirname(__FILE__) . '/nacex.php');

class VInacexlogs
{
    public static function getNacex()
    {
        return new nacex();
    }

    public static function header()
    {
        $nacex = self::getNacex();
        $mensaje = $nacex->l('Are you sure you want to delete all log files?');
        $webimg = _MODULE_DIR_ . 'nacex/images/logos/nacex_logista.png';

        return "
            <div class='panel'>
                <div class='panel-heading' style='display:flex;align-items:center;justify-content:space-between;'>
                    <div style='display:flex;align-items:center;gap:1em;'>
                        <a target='_blank' href='https://www.nacex.es'>
                            <img style='width:130px;height:auto;' src='" . $webimg . "' />
                        </a>
                        <span style='font-size:1.1em;'>" . $nacex->l('Log files') . "</span>
                    </div>
                    <div>
                        <button type='button' class='btn btn-default btn-sm' id='btnrefrescarvolver' onclick=\"nacexlogs.get('init',Base_uri);\">
                            <i class='icon-refresh'></i> " . $nacex->l('Refresh/Back') . "
                        </button>
                        <button type='button' class='btn btn-danger btn-sm' id='btnborrartodo' onclick=\"nacexlogs.get('delete_all',Base_uri,'" . $mensaje . "');\">
                            <i class='icon-trash'></i> " . $nacex->l('Delete logs') . "
                        </button>
                    </div>
                </div>
            </div>";
    }

    public static function content_directory($_file, $path, $index)
    {
        $nacex = self::getNacex();
        $mensaje = $nacex->l('Are you sure you want to delete file') . ' ' . $_file . '?';

        return "<tr>
                    <td><strong>" . $_file . "</strong></td>
                    <td>" . self::formatSizeUnits(filesize($path . DIRECTORY_SEPARATOR . $_file)) . "</td>
                    <td>
                        <a href='#' title='" . $nacex->l('Open file') . "' onclick='nacexlogs.get(\"read\",Base_uri,\"\",\"" . $_file . "\");return false;'>
                            <i class='icon-eye-open'></i>
                        </a>
                        &nbsp;
                        <a href='#' title='" . $nacex->l('Delete file') . "' onclick='nacexlogs.get(\"delete\",Base_uri,\"" . $mensaje . "\",\"" . $_file . "\");return false;' style='color:#dc3545;'>
                            <i class='icon-trash'></i>
                        </a>
                    </td>
                </tr>";
    }

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

    public static function content_directory_no_files()
    {
        $nacex = self::getNacex();
        return "<div class='alert alert-info' style='text-align:center;'>" . $nacex->l('Do not exist log files') . '</div>';
    }

    public static function response_delete($_message)
    {
        return "<div class='alert alert-success' style='text-align:center;'>" . $_message . '</div>';
    }

    public static function response_open($_message)
    {
        return "<div class='alert alert-info' style='text-align:center;'>" . $_message . '</div>';
    }

    public static function content_file_title($_file)
    {
        $nacex = self::getNacex();
        return "<div class='panel-heading'><strong>" . $nacex->l('Content of') . " '" . $_file . "'</strong></div>";
    }

    public static function content_file($_line)
    {
        return '<p style="margin:0;padding:2px 0;font-family:monospace;font-size:12px;">' . $_line . '</p>';
    }
}
