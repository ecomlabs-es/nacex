<?php
include_once dirname(__FILE__) . '/nacexWS.php';

session_start();
$dataResponse = [];
$resultado = nacexWS::ws_checkConnection();

if ($resultado) {
    if (is_array($resultado)) {
        if ($resultado[0] == '500ERROR') {
            echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger error" style="width:auto">Problemas de conexión con WS</div></div>';
        } elseif ($resultado[0] != 'ERROR') {
            echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-success conf" style="width:auto">¡Conexi&oacute;n con Webservice correcta!</div></div>';
        } else {
            echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger error" style="width:auto">Usuario o contraseña incorrectos</div></div>';
        }
    } else {
        if (!strpos($resultado, 'HTTP Status 500')) {
            echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-success conf" style="width:auto">¡Conexi&oacute;n con Webservice correcta!</div></div>';
        } else {
            echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger error" style="width:auto">Usuario o contraseña incorrectos</div></div>';
        }
    }
} else {
    echo '<div class="bootstrap" style="margin-top:10px"><div class="alert alert-danger error" style="width:auto">Error obteniendo respuesta del Webservice de Nacex.</div></div>';
}

/** Antes del control de WS sin conexión **/
//	session_start();
//
//	$dataResponse = array();
//
//	//Datos de configuraci�n
//	$urlNacex = $_POST['ws_url'];
//	$URL = $urlNacex."/soap";
//	$nacexWSusername = $_POST['usr'];
//	$nacexWSpassword = strtoupper(md5($_POST['pass']));
//	$pais = "ESP";
//
//	$XML =
//	'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
//		<soapenv:Header/>
//		<soapenv:Body>
//		      <typ:getCodigoPais>
//				<String_1>'.$nacexWSusername.'</String_1>
//				<String_2>'.$nacexWSpassword.'</String_2>
//        <arrayOfString_3>'.$pais.'</arrayOfString_3>
//		      </typ:getCodigoPais>
//		</soapenv:Body>
//	</soapenv:Envelope>';
//	try {
//	$ch = curl_init();
//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//	curl_setopt($ch, CURLOPT_HEADER, FALSE);
//	curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
//	curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
//	curl_setopt($ch, CURLOPT_URL, $URL );
//	curl_setopt($ch, CURLOPT_POSTFIELDS, $XML );
//	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
//	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml; charset=utf-8"));
//
//        /**parche mexpositop 20171222**/
//     //   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//
//        $postResult = curl_exec($ch);
//	} catch (Exception $e) {
//		echo "<div class=\"bootstrap\" style=\"margin-top:10px\"><div class=\"alert alert-success conf\" style=\"width:auto\">Error en el m�dulo CURL de PHP ". $e->getMessage()."</div></div>";
//	}
//
//
//	if (curl_errno($ch) || strpos(strtoupper($postResult), "404")) {
//		echo "<div class=\"bootstrap\" style=\"margin-top:10px\"><div class=\"alert alert-danger error\" style=\"width:auto\">Error al conectar con el Webservice de Nacex.</div></div>";
//		return;
//	}
//        $xml = new  SimpleXMLElement($postResult);// mexpositop 20171128
//        //simplexml_load_string($postResult, NULL, NULL, "http://www.w3.org/2003/05/soap-envelope");
//	$xml->registerXPathNamespace("ns0","urn:soap/types");
//	$resultado = $xml->xpath('//ns0:getCodigoPaisResponse/result');
//
//	if($resultado){
//		if($resultado[0] != "ERROR"){
//			echo "<div class=\"bootstrap\" style=\"margin-top:10px\"><div class=\"alert alert-success conf\" style=\"width:auto\">¡Conexi&oacute;n con Webservice correcta!</div></div>";
//		}else{
//			echo "<div class=\"bootstrap\" style=\"margin-top:10px\"><div class=\"alert alert-danger error\" style=\"width:auto\">Error de Webservice: ".$resultado[1].".</div></div>";
//		}
//	}else{
//		echo "<div class=\"bootstrap\" style=\"margin-top:10px\"><div class=\"alert alert-danger error\" style=\"width:auto\">Error obteniendo respuesta del Webservice de Nacex.</div></div>";
//	}
?>	