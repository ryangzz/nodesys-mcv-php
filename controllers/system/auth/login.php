<?php
namespace controllers\system\auth;
use models\system\auth\login as loginModel;
class login extends loginModel{
    
    public function __construct(){
        echo "Hola soy el login controller<br>";
        parent::__construct();
    }
}