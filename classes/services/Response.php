<?php
namespace classes\services;

class Response{

    protected $response;
    protected $ip;
    protected $urlCurrent;
    protected $method;
    protected $domain;
    protected $idTransaction;
    protected $startTime;
    protected $endTime;
    protected $memoryIni;
    protected $memoryEnd;
	
    public function __construct(){
		$this->memoryIni = memory_get_usage();
		$this->setDomain();
		$this->startingTime = time();
		$this->setMethod();
		$this->setDataHeaders();
	}

	public function setResponse($response){
		$this->response = $response;
	}
	
	public function getRseponse(){
		return json_encode($this->response, true);
	}

	protected function setDataHeaders() {
		$this->ip = null;
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) 	$this->ip["HTTP_X_FORWARDED_FOR"] 	= $_SERVER['HTTP_X_FORWARDED_FOR'];
		if (isset($_SERVER['HTTP_VIA'])) 				$this->ip["HTTP_VIA"] 				= $_SERVER['HTTP_VIA'];
		if (isset($_SERVER['REMOTE_ADDR']))				$this->ip["REMOTE_ADDR"] 			= $_SERVER['REMOTE_ADDR'];
		$headers = [];
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) <> 'HTTP_') 
				continue;
			$header 			= str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$header]   = $value;
		}
		$this->headers = $headers;
		$this->urlCurrent = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	}

	protected function setMethod(){
		$this->method = $_SERVER['REQUEST_METHOD'];
	}

	protected function setDomain(){
		switch (AMBIENTE) {
			case 'dev':
				$this->domain = "1"; 
				break;
			case 'qa':
				$this->domain = "2"; 
				break;
			default:
				$this->domain = "3"; 
				break;
		}
	}
	public function getDomain(){
		return $this->domain;
	}
	public function getMethod(){ return $this->method; }

	public function response404(){
		http_response_code(404);
		$response 	= ["error"=>"true", "code"=>404, "message"=>"Resource Not Found"];
		$this->setResponse($response);
		exit();
	}

	public function response405(){
		http_response_code(405);
		$response 	= ["error"=>"true", "code"=>405, "message"=>"Incorrect Method"];
		$this->setResponse($response);
		exit();
	}

	protected function validRouteMethodExist($class, $function = ""){
		if(!method_exists( $class,$function)) $this->response404();
	}

	public function response400($data = null){
		http_response_code(400);
		$response 	= ["error"=>"true", "code"=>400, "message"=>"Params In Request Are Incomplete Or Aren't Valid"];
		if($data != null)
			$response = array_merge($response, $data);
		$this->setResponse($response);
		exit();
	}

	public function setErrorCustomReponse($data = [], $code = 400){
		http_response_code($code);
		$response 	= ["error"=>"true", "code"=>$code];
		if($data != null)
			$response = array_merge($response, $data);
		$this->setResponse($response);
		exit();
	}

	public function validateFields($fields = [], $method = '', $raw = false){
        $bodyFields = [];
        $method 	= "_".$method;
		if($raw)  $GLOBALS[$method] = json_decode(file_get_contents('php://input'), true);
        foreach ($fields as  $field) {
			switch($field["requerid"]) {
				case 'multi':
					$evaluador = $GLOBALS[$method][$field["param"]];
					$opciones  = $field["values"];
					$result    = array_filter($opciones, function($item) use($evaluador) {
						return $evaluador == $item;
					});
					switch (count($result)) {
						case 1:
							if(empty($GLOBALS[$method][$field["name"]])) 
								$this->response400();
							elseif($field["include"]) 
								$bodyFields[$field["name"]] = $GLOBALS[$method][$field["name"]];
						break;
					}
				break;
				case 'multi2':
					if(empty($GLOBALS[$method][$field["name"]])){
						foreach ($field["param"] as $key => $param) {
							if(empty($GLOBALS[$method][$param]))
								$this->response400();
						}
					}else{
						foreach ($field["param"] as $key => $param) {
							if(!empty($GLOBALS[$method][$param]))
								$this->response400();
						}
						if($field["include"]) 
							$bodyFields[$field["name"]] = $GLOBALS[$method][$field["name"]];
					}
				break;
				case "true":
					if(empty($GLOBALS[$method][$field["name"]])) 
						$this->response400(); 
					elseif(isset($field["include"])) 
						$bodyFields[$field["name"]] = $GLOBALS[$method][$field["name"]];
				break;
				case "false":
					if(isset($GLOBALS[$method][$field["name"]])) 
						$bodyFields[$field["name"]] = $GLOBALS[$method][$field["name"]];
					elseif(isset($field["default"])) 
						$bodyFields[$field["name"]] = $field["default"];
				break;
				default:
					$this->response400(); 
				break;
			}
			if(!empty($bodyFields[$field["name"]]) && !empty($field["parse"])){
				switch ($field["parse"]) {
					case 'boolean':
						$bodyFields[$field["name"]] = (bool)$bodyFields[$field["name"]];
					break;
					case 'integer':
						$bodyFields[$field["name"]] = (int)$bodyFields[$field["name"]];
					break;
				}
			}
        }
		return $bodyFields;
    }
	
	public function saveRequest(){
		$id_key	= $this->getTokenDecode()->data->id_key;
		$data	= [];
		$info_a = [
			"tiempo"        => ($this->finishingTime - $this->startingTime),
			"memory"        => ($this->memoryEnd - $this->memoryIni),
			"memorystart"   =>	$this->memoryIni,
			"memoryend"     =>	$this->memoryEnd
		];
		$sql    = "INSERT INTO api.transaccion(id_key, url, ip, headers, fecha, time_start, time_end, data_response, estatus, info_aditional)
					VALUES ($id_key, '{$this->urlCurrent}', '".json_encode($this->ip)."', '".json_encode($this->headers)."',
					NOW(), '{$this->startingTime}', '{$this->finishingTime}', '{$this->getRseponse()}', 1, '".json_encode($info_a)."') returning id_transaccion;";

		$data 	= $this->dba('api')->query('fetch', $sql, true);
		$this->idTransaction = ((count($data)>0) ? $data[0]["id_transaccion"]: 0);
		return $this->idTransaction;
	}
	

	public function updateRequest(){
		$sql 	= " UPDATE api.transaccion SET data_response = '{$this->getRseponse()}' , estatus=2, time_end = {$this->finishingTime}, fecha_update = NOW(), 
					data_aditional = '".json_encode(array_merge($this->headers, $this->ip))."', 
					info_aditional = info_aditional || ('{\"tiempo\": '|| ((info_aditional->>'tiempo')::int + ({$this->finishingTime} - time_start)) ||'}')::jsonb
			    	WHERE id_transaccion = {$this->idTransaction} returning id_transaccion;";
		$data 	= $this->dba('api')->query('fetch', $sql, true);
		$this->idTransaction = ((count($data)>0) ? $data[0]["id_transaccion"]: 0);
		return $this->idTransaction;
	}

	public function json_validate($string){
		// decode the JSON data
		$result = json_decode($string);
		// switch and check possible JSON errors
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$error = ''; // JSON is valid // No error has occurred
				break;
			case JSON_ERROR_DEPTH:
				$error = 'The maximum stack depth has been exceeded.';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Invalid or malformed JSON.';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Control character error, possibly incorrectly encoded.';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON.';
				break;
			// PHP >= 5.3.3
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_RECURSION:
				$error = 'One or more recursive references in the value to be encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_INF_OR_NAN:
				$error = 'One or more NAN or INF values in the value to be encoded.';
				break;
			case JSON_ERROR_UNSUPPORTED_TYPE:
				$error = 'A value of a type that cannot be encoded was given.';
				break;
			default:
				$error = 'Unknown JSON error occured.';
				break;
		}

		if ($error !== '') {
			$this->response400(["detail"=>$error]);
		}

		// everything is OK
		return $result;
	}
}