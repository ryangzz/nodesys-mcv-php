<?php
/* Nodesys Create 13/NOV/2022 BY ryangzz and emmagrro */
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once('vendor/autoload.php');
require_once('includes/config.php');
require_once('autoload.php');

use classes\nodesys\Nodesys;
$app = new Nodesys();