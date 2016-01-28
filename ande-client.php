<?php

	/*
	  Consultar php de la ANDE
		Ejemplo muy simple y muy sencillo, por ende elegante :) .
		Autor: Carlos Vallejos
		Empresa: Vamyal S.A.
		Fecha: Enero del 2016
	*/

namespace com\vamyal\ande\get {

	error_reporting(-1);
	ini_set('display_errors', 'On');

	class nisAnde {

		private static function utf8ize($mixed) {
		    if (is_array($mixed)) {
		        foreach ($mixed as $key => $value) {
		            $mixed[$key] = self::utf8ize($value);
		        }
		    } else if (is_string ($mixed)) {
		        return utf8_encode($mixed);
		    }
		    return $mixed;
		}

		public static function safe_json_encode($value){
		    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
		        $encoded = json_encode($value, JSON_PRETTY_PRINT);
		    } else {
		        $encoded = json_encode($value);
		    }
		    switch (json_last_error()) {
		        case JSON_ERROR_NONE:
		            return $encoded;
		        case JSON_ERROR_DEPTH:
		            return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
		        case JSON_ERROR_STATE_MISMATCH:
		            return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
		        case JSON_ERROR_CTRL_CHAR:
		            return 'Unexpected control character found';
		        case JSON_ERROR_SYNTAX:
		            return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
		        case JSON_ERROR_UTF8:
		            $clean = self::utf8ize($value);
		            return self::safe_json_encode($clean);
		        default:
		            return 'Unknown error'; // or trigger_error() or throw new Exception()
		    }
		}

		public static function sendRequestJSON($code, $data){
			header("X-Powered-By: Vamyal/2016");
			header("X-Hello-Human: Somos @vamyalsa, escribinos a contacto@vamyal.com");
			header("HTTP/1.1 {$code} Bad Request");
			header("Content-Type: application/json");
			$res = array( "data" => $data );
			print self::safe_json_encode( $res );
		}

		public static function postApiAnde($nis){

			$body = array('name' => $nis);

			// Es importante usar nombres de dominio y no direcciones IPs
		  $URL="http://201.217.43.238:9080/consulta/consulta_02.php";

			// Cross-Origin Resources Sharing ..... (Es del fron-end)
			$headers = array('Origin : http://201.217.43.238:9080');

			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$URL);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false );
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false );
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

				$request = curl_exec ($ch);
				$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			}  catch (Exception $e) {
				$request = 'Error: '.$e;
				$status_code = 500;
			} finally {
				curl_close ($ch);
			}
			// Tratamos segun el codigo
			switch ($status_code) {
				case 200:
					return $request;
					break;
				case 500: // si hay catch - error
					return $request;
					break;
				default:
					return 'Status('.$status_code.'): La consulta no arrojo resultados.';
					break;
			}

		}

	}

	// Vamos a tratar solo el GET
	switch($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			$nis = (isset($_GET['nis']) ? $_GET['nis'] : 0 );
			// filtramos un rango razonable
			if ( ($nis < 99999) || ($nis > 9999999) ) {
				$html = "Ingrese un numero de NIS valido.";
				nisAnde::sendRequestJSON( 200, $html );
				return;
			}
			$html = nisAnde::postApiAnde($nis);
			nisAnde::sendRequestJSON( 200, $html );
			break;
		default:
			$html = "Metodo no implementado.";
			nisAnde::sendRequestJSON( 400, $html );
			break;
	}

}


?>
