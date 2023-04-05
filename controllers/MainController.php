<?php
namespace controllers;
use classes\nodesys\Authentication;
use controllers\system\auth\login;

class MainController extends Authentication{

    protected $controller;
    protected $session;
    public function __construct(){
        parent::__construct();
        $this->getSession();
    }

    protected function getController(){
        return $this->controller;
    }
    
    protected function getSession(){
        session_start();
        $this->session = $_SESSION;
        session_write_close();
        return $this->session;
    }

    protected function setSession(String $key, $value):void{
        session_start();
        $_SESSION[$key] = $value;
        $this->session  = $_SESSION;
        session_write_close();
    }

    protected function validSession():void{
        if($this->session = null || count($this->session) == 0){
            header('Location: '.URL.'login');
        }
    }

    protected function loadController():void{
        switch ($this->params[0]) {
            case 'login':
                $this->controller = new login();
            break;
            default:
            break;
        }
    }

    public function loadView($type = 'normal'){
        switch ($view) {
            case "normal":
                # code...
                break;
            case "login":
                
                break;
            default:
                # code...
                break;
        }
    }
}