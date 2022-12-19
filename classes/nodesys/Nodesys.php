<?php
namespace classes\nodesys;
use controllers\MainController;

class Nodesys extends MainController{
    public $params;

    public function __construct(){
        $this->contructUrlParams();
        parent::__construct();
        $this->loadAppFunctions();
    }

    private function contructUrlParams(){
        $path = trim($_SERVER["REQUEST_URI"], " \t\n\r\0\x0B");
        $path = filter_var($path, FILTER_SANITIZE_URL);
        $path = explode('/', str_replace('', URL, $path));
        unset($path[0]);
        unset($path[1]);
        $path   = array_values($path); // 'reindex' array
        $params = ['', '', '', ''];
        foreach(range(0, count($path)-1) as $key) $params[$key] = $path[$key];
        foreach ($params as $key => $value) define('param'.$key, $value);
        $this->params = $params;
    }

    private function loadAppFunctions():void{
        if(!in_array(strtolower($this->params[0]), ['login', 'api']))
            $this->validSession();
        $this->loadController();
    }
    
}

