<?php
define('DOC_ROOT', $_SERVER["DOCUMENT_ROOT"]."/nodesys/");
$doenv = Dotenv\Dotenv::createImmutable(DOC_ROOT);
$doenv->load();
define('AMBIENTE', 'dev');
$https  = ((AMBIENTE == 'prod') ? 'https':'http');
define('URL', $https."://{$_ENV['HOSTNAME']}/nodesys/");
define('ASSETS', $https."://{$_ENV['HOSTNAME']}/nodesys/assets/");
define('IP',  $https."://{$_ENV['HOSTIP']}/nodesys/");