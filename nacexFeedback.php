<?php

//SET ENVIRONMENT
include_once(dirname(__FILE__) . '/../../init.php');
include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once dirname(__FILE__) . '/nacexDTO.php';
include_once dirname(__FILE__) . '/nacexWS.php';

class NacexFeedback
{
    public $fileurl;
    public $filepath;
    //public $removepath;
    protected $filename;
    protected $nws;

    public function __construct() {
        $this->fileurl = _MODULE_DIR_ . 'nacex/files/';
        $this->filepath = _PS_MODULE_DIR_ . 'nacex/files/';
        $this->filename = $this->filepath . 'sendEmail_'. date('dmY_His') .'.eml';
        $this->nws = new NacexWS();
    }

    public function getFileUrl() {
        return $this->fileurl;
    }

    public function getFilePath() {
        return $this->filepath;
    }

    public function getReceiverDetail($tipoId) {
        /* Receiver Detail */

        $a = $this->getOperativaEmail();

        $receiverEmail = [
            0 => [
                'email' => 'atencion.informatica@nacex.com',
                'opciones' => ['imm2', 'dim', 'dcm']
            ],
            1 => [
                'email' => $this->getOperativaEmail(),
                'opciones' => ['co','ca','ien','iex','dg']
            ],
            2 => [
                'email' => $this->getComercialEmail(),
                'opciones' => ['cc']
            ]
        ];

        // Con un array_column miro si la opción existe en opciones y me devuelva el email
        $column = array_column($receiverEmail, 'opciones');
        $key = '';
        foreach ($column as $k => $value) {
            if (array_search($tipoId, $value) !== false) {
                $key = $k;
                break;
            }
        }

        return $receiverEmail[$key]['email'];
    }

    /** EML FILE **/
    public function createEmlFile($tipo, $tipoId, $nombre, $company, $telf, $consulta, $copia, $email) {
        $wsUser = Configuration::get('NACEX_WSUSERNAME');
        $abonado = Configuration::get('NACEX_AGCLI');
        $sender = Configuration::get('NACEX_FEEDBACK_SENDER');

        $eml = 'From: ' . $sender . '
To: ' . $this->getReceiverDetail($tipoId) . '
';
        if ($copia) {
            $eml .= "Bcc: $email
"; }
        $eml .= "MIME-Version: 1.0
Content-Type: text/html; charset=\"utf-8\"
Content-Transfer-Encoding: 8bit
Subject:=?utf-8?Q?Feedback Ecommerce | $tipo desde " . Configuration::get('PS_SHOP_NAME') . '?=

<p>Correo de Atención al cliente de ' . Configuration::get('PS_SHOP_NAME') . '</p>
<p>Información del sistema: ' . $this->nws->getSystemInfo() . "</p>
<ul>
    <li><strong>Usuario de WS:</strong> $wsUser</li>
    <li><strong>URL Tienda:</strong> " . __PS_BASE_URI__ . "</li>
    <li><strong>Abonado:</strong> $abonado</li>
    <li><strong>Tipo de consulta:</strong> $tipo</li>
    <li><strong>Nombre y apellidos:</strong> $nombre</li>
    <li><strong>Empresa:</strong> $company</li>
    <li><strong>Email:</strong> $email</li>
    <li><strong>Teléfono:</strong> $telf</li>
</ul>
<p><strong>Consulta:</strong></p><p>$consulta</p>";

        file_put_contents($this->filename, $eml);
    }

    public function deleteEmlFile($file = null) {
        if ($file) {
            unlink($this->filepath . $file); } else { unlink($this->filename); }
    }

    public function filesExist() {
        if ($files = glob($this->filepath . 'sendEmail_*.eml')) {
            return $files; } else { return false; }
    }

    public function readFile($file) {
        $datosEmail = [];
        $lines = file($file);
        $subj = 0;

        $datosEmail['from'] = trim(explode(': ', $lines[0])[1]);
        $datosEmail['to'] = trim(explode(': ', $lines[1])[1]);
        // Si hay bcc
        if (strpos($lines[2], 'Bcc:') !== false) {
            $datosEmail['bcc'] = trim(explode(': ', $lines[2])[1]);
            $subj = 6;
        } else { $subj = 5; }

        $datosEmail['subject'] = substr(trim(explode(':', $lines[$subj])[1]), 10, -2);
        $datosEmail['body'] = '';

        for ($i = $subj + 1; $i < sizeof($lines); $i++) {
            if (strpos($lines[$i], '<li>') !== false) {
                $datosEmail['body'] .= '    - ' . trim(strip_tags($lines[$i])) . '%0D%0A';
            } elseif (strpos($lines[$i], 'ul>') !== false) {  // <ul> & </ul>
                $datosEmail['body'] .= '';
            } else {
                if ($i == $subj + 1) { $datosEmail['body'] .= trim(strip_tags($lines[$i])); } else { $datosEmail['body'] .= trim(strip_tags($lines[$i])) . '%0D%0A%0D%0A'; }
            }
        }

        return $datosEmail;
    }

    public function getInfoFile($file) {
        $datosEmail = $this->readFile($file);

        $text = $datosEmail['from'].'?'.$datosEmail['to'];
        isset($datosEmail['bcc']) ? $text .= '&'.$datosEmail['bcc'] : $text .= '';
        $text .= '&subject='.$datosEmail['subject'].'&body='.$datosEmail['body'];

        return $text;
    }

    /*** ASIGNACIÓN DE CORREOS DE ENVÍO DE FEEDBACK ***/
    public function formEmail() {
        $abo = Configuration::get('NACEX_AGCLI');
        $agencia = explode('/', $abo)[0];

        $datos = [$agencia . '.'];

        if (substr($agencia, 0, 1) == '7') { // Clientes Portugal
            $datos[] = 'pt'; } else { $datos[] = 'es'; }

        return $datos;
    }

    public function getOperativaEmail() {
        $datos = $this->formEmail();
        $dir = $datos[1] == 'es' ? 'operativa' : 'operativo';

        $email = $datos[0] . $dir . '@nacex.' . $datos[1];
        return $email;
    }

    public function getComercialEmail() {
        $datos = $this->formEmail();
        $dir = $datos[1] == 'es' ? 'comercial' : 'comercial_pt';

        $email = $datos[0] . $dir . '@nacex.' . $datos[1];
        return $email;
    }
    //
    //    /*public function getDelegadoEmail() {
    //        $datos = $this->formEmail();
    //        $dir = $datos[1] == 'es' ? 'delegado' : 'delecao';
    //
    //        $email = $datos[0] . $dir . '@nacex.' . $datos[1];
    //        return $email;
    //    }*/
}
