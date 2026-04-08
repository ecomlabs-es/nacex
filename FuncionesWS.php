<?php
function ws_getValoracion($cp_ent, $tip_ser, $kil) {
	$cp_rec = Configuration::get ( 'NACEX_CP_REC' );
	$urlNacex = Configuration::get ( 'NACEX_WS_URL' );
	$URL = $urlNacex . "/soap";
	
	$getValoracionResponse = array ();
	$nacexWSusername = Configuration::get ( 'NACEX_WSUSERNAME' );
	$nacexWSpassword = strtoupper ( md5 ( Configuration::get ( 'NACEX_WSPASSWORD_ORIGINAL' ) ) );
	
	$tip_env = Configuration::get ( 'NACEX_TIP_ENV' );
	$tip_env_int = Configuration::get ( 'NACEX_TIP_ENV_INT' );
	
	$ag_cli = Configuration::get ( 'NACEX_AGCLI' );
	
	$array_agclis = explode ( ",", $ag_cli );
	// Cogemos la primera pareja
	$firstagcli = trim ( $array_agclis [0] );
	$arr_ag_cli = explode ( "/", $firstagcli );
	
	$del_cli = trim ( $arr_ag_cli [0] );
	$num_cli = trim ( $arr_ag_cli [1] );
	
	// El motor no esta preparado para valorar pesos < 1Kg, as� que si el peso es menor a 1Kg lo modificamos.
	$kil = floatval ( $kil ) < 1? 1: $kil;
	
	
	$XML = '<soapenv:Envelope xmlns:soapenv="https://schemas.xmlsoap.org/soap/envelope/" xmlns:typ="urn:soap/types">
			<soapenv:Header/>
			<soapenv:Body>
			      <typ:getValoracion>
					 <String_1>' . $nacexWSusername . '</String_1>
					 <String_2>' . $nacexWSpassword . '</String_2>
					 <arrayOfString_3>cp_rec=' . $cp_rec . '</arrayOfString_3>
					 <arrayOfString_3>cp_ent=' . $cp_ent . '</arrayOfString_3>
					 <arrayOfString_3>tip_ser=' . $tip_ser . '</arrayOfString_3>         
					 <arrayOfString_3>tip_env=' . $tip_env . '</arrayOfString_3>         
					 <arrayOfString_3>kil=' . $kil . '</arrayOfString_3>         					    
					 <arrayOfString_3>del_cli=' . $del_cli . '</arrayOfString_3>
					 <arrayOfString_3>num_cli=' . $num_cli . '</arrayOfString_3>
			      </typ:getValoracion>
			</soapenv:Body>
		</soapenv:Envelope>';
	
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
	curl_setopt ( $ch, CURLOPT_HEADER, FALSE );
	curl_setopt ( $ch, CURLOPT_FORBID_REUSE, TRUE );
	curl_setopt ( $ch, CURLOPT_FRESH_CONNECT, TRUE );
	curl_setopt ( $ch, CURLOPT_URL, $URL );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $XML );
	curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)' );
	curl_setopt ( $ch, CURLOPT_HTTPHEADER, Array (
			"Content-Type: text/xml; charset=utf-8" 
	) );
	
	$postResult = curl_exec ( $ch );
	
	if (curl_errno ( $ch ) || curl_getinfo ( $ch, CURLINFO_HTTP_CODE ) > 499) {
		// return $this->errorComunicacionWS('getEstadoExpedicion', $ch, $style="width:auto;", true, $errorplain);
		return null;
	}
    $xml = simplexml_load_string($postResult, NULL, NULL, "https://https://www.w3.org/2003/05/soap-envelope");
	
	if (! ($xml instanceof SimpleXMLElement)) {
		// return "error comunicaci�n";
		return null;
	}
	
	$xml->registerXPathNamespace ( "ns0", "urn:soap/types" );
	$resultado = $xml->xpath ( '//ns0:getValoracionResponse/result' );
	$posible_error_solicitud_mal_formada = $xml->xpath ( '//ns0:Response/result' );
	
	if ((isset ( $resultado [0] ) && $resultado [0] == "ERROR") || (isset ( $posible_error_solicitud_mal_formada [0] ) && $posible_error_solicitud_mal_formada [0] == "ERROR")) {
		if ((isset ( $resultado [0] ) && $resultado [0] == "ERROR")) {
			return $resultado;
		} else {
			return null;
		}
	} else {
		return $resultado;
	}
}
?>