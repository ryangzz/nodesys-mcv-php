<?php
namespace models\system\auth;
use models\mainModel;
class login extends mainModel{

    public function __construct(){
        echo "Hola desde el modelo de login<br>";
        parent::__construct();
    }
}