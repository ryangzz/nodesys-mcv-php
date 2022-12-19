<?php

namespace classes\nodesys;
use classes\services\Response;
class Authentication extends Response{

    protected $user = "";
	protected $key  = "";
	protected $ait;
	protected $token;
	protected $tokenDecode;

	private $request;

	public function __construct(){
        parent::__construct();
		// $this->authentication();
    }

	public function getToken(){
		return $this->token;
	}
	public function setToken($token = ""){
		$this->token = $token;
	}
	
	public function authentication(){
		$this->getBearerToken();
	}

    private function getAuthorizationHeader(){
		$headers = null;
		if (isset($_SERVER['Authorization']))  				$headers = trim($_SERVER["Authorization"]);
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))  	$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		else if (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization'])) { 	$headers = trim($requestHeaders['Authorization']);}
		}
		return $headers;
	}

	public function getBearerToken() {
		$headers 		= $this->getAuthorizationHeader();
		$this->token 	= "";
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				$this->token = $matches[1];
			}
		}
        $this->validExistBearerToken();
		// return $this->token;
	}

	public function validExistBearerToken(){
		switch ($this->token) {
			case '':
				http_response_code(401);
				// $this->request->setResponse(["error"=>"true",  "message"=>"Request Not Authoriced"]);
				exit();
			break;
			default:
				$this->validJWT();
			break;
		}
	}

	protected function validJWT(){
		$this->isValidJWT();
		$this->checkedJWTInDB();
	}

	public function isValidJWT(){
		try {
			$this->tokenDecode = Tokenitation::jwtDecode($this->token);
		} catch (\Throwable $th) {
			$this->request->setResponse(["error"=>"true",  "message"=>"Request Not Authoriced, Invalid Token Format"]);
			exit();
		}
	}

	public function getTokenDecode(){
		return $this->tokenDecode;
	}

	protected function checkedJWTInDB(){
		$time = time();
		$sql  = 'SELECT * FROM api.key WHERE jwt = ? AND ait > ?';
		$data = $this->request->dba('api')->prepareQuery($sql, [$this->token, $time]);
		if(count($data)==0){
			http_response_code(401);
			$this->request->setResponse(["error"=>"true",  "message"=>"Request Not Authoriced, Invalid Token"]);
			exit();
		}
	}

	public function getAuthenticationBasic(){
		if(empty($_SERVER["PHP_AUTH_USER"]) || empty($_SERVER["PHP_AUTH_PW"])){
			http_response_code(401);
			$this->request->setResponse(["error"=>"true",  "message"=>"Incomplete Basic Authentication Params"]);
			exit();
		}else{
			$this->user = $_SERVER["PHP_AUTH_USER"];
			$this->key	= $_SERVER["PHP_AUTH_PW"];
			$this->loginAuthenticationBasic();
		}
	}

	protected function loginAuthenticationBasic(){
		$this->validJWTCurrent();
		$sql  = 'SELECT id_key, unidad_negocio_key, cliente_key, id_producto_key, nombre_key, key, user_key, jwt, ait FROM api.key WHERE "key" = ? AND user_key = ? ;';
		$data = $this->request->dba('api')->prepareQuery($sql, [$this->key, $this->user]);
		if(count($data)<=0){
			http_response_code(401);
			$this->request->setResponse(["error"=>"true",  "message"=>"Incorrect Data, Verify Authentication Information"]);
			exit();
		}else{
			$datos = Tokenitation::jwtEncode($this->key, $this->user, $data[0]["id_key"]);
			$this->token = $datos["token"];
			$this->ait   = $datos["ait"];
			$this->updateTokenAuthenticationDB();
		}
		
	}

	protected function validJWTCurrent(){
		$time = time();
		$sql  = 'SELECT * FROM api.key WHERE jwt IS NOT NULL and ait >= ? AND  "key" = ? AND user_key = ? ;';
		$data = $this->request->dba('api')->prepareQuery($sql, [$time, $this->key, $this->user]);
		if(count($data)>0){
			http_response_code(200);
			$this->request->setResponse(["error"=>"true",  "message"=>"There Is Currently A Calid Token"]);
			exit();
		}
	}

	protected function updateTokenAuthenticationDB(){
		$sql 	= 'UPDATE api.key SET 	jwt = ?, ait = ?, fecha_moficiacion_key = NOW() WHERE "key" = ? AND user_key = ? returning jwt;';
		$data 	= $this->request->dba('api')->prepareQuery($sql, [$this->token, $this->ait, $this->key, $this->user]);
		if(count($data)==0){
			http_response_code(409);
			$this->request->setResponse(["error"=>"true",  "message"=>"Failed Authentication, For Some Reason The Operation Could Not Be Completed, Please Try Again"]);
			exit();
		}
	}

}